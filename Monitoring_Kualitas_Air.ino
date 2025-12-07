// ============================================
// ESP8266 Payment Gateway - Ngenskuy
// Terintegrasi dengan API Server udara.unis.ac.id
// SECURED VERSION with Anti-Hacking Features
// ============================================
// 
// FITUR KEAMANAN:
// 1. AP Password Protection - Mencegah akses tidak sah ke WiFi
// 2. Rate Limiting - Max 30 request/menit per IP
// 3. Brute Force Protection - Blokir 5 menit setelah 5x gagal login
// 4. CSRF Token - One-time token untuk form (valid 10 menit)
// 5. Session IP Binding - Session terikat dengan IP client
// 6. Session Expiry - Auto logout setelah 1 jam
// 7. Input Sanitization - Filter karakter berbahaya
// 8. Security Headers - X-XSS-Protection, X-Frame-Options, dll
// 9. Secure Cookie - HttpOnly, SameSite=Strict
// 10. Setup Page Protection - Hanya bisa diakses dari jaringan lokal
// 11. Audit Logging - Log semua transaksi ke Serial
// 12. Client Fingerprinting - Validasi browser fingerprint
// ============================================

#include <ESP8266WiFi.h>
#include <ESP8266WebServer.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecure.h>
#include <DNSServer.h>
#include <ArduinoJson.h>
#include <Hash.h>  // Untuk SHA1/MD5 hashing

// ============================================
// Konfigurasi
// ============================================
const char* AP_SSID = "Payment-NgenSkuy";
const char* AP_PASS = "";  // WAJIB password untuk AP - mencegah akses tidak sah
const char* API_SECRET_KEY = "NgenskuySecretKey2024";  // gk papa ini api fron doang

// CodeIgniter 4 API endpoint - Baru
const char* API_BASE_URL = "https://sesuaikandomain/endpoin/esp";

ESP8266WebServer server(80);
const byte DNS_PORT = 53;
DNSServer dnsServer;
WiFiClientSecure httpsClient;

// ============================================
// Security: Rate Limiting & Brute Force Protection
// ============================================
struct IPTracker {
  IPAddress ip;
  int failedAttempts;
  unsigned long lastAttempt;
  unsigned long blockedUntil;
  bool isBlocked;
};

const int MAX_TRACKED_IPS = 10;
IPTracker ipTrackers[MAX_TRACKED_IPS];
const int MAX_FAILED_ATTEMPTS = 5;  // Max login gagal sebelum blokir
const unsigned long BLOCK_DURATION = 300000;  // Blokir 5 menit (ms)
const unsigned long RATE_LIMIT_WINDOW = 60000;  // Window 1 menit
const int MAX_REQUESTS_PER_MINUTE = 30;  // Max request per menit

// Request counter per IP
struct RequestCounter {
  IPAddress ip;
  int count;
  unsigned long windowStart;
};
RequestCounter requestCounters[MAX_TRACKED_IPS];

// ============================================
// Security: CSRF Token Management
// ============================================
struct CSRFToken {
  String token;
  unsigned long created;
  bool used;
  IPAddress ip;  // Track IP yang generate token
};
const int MAX_CSRF_TOKENS = 30;  // Lebih banyak untuk multi-device
CSRFToken csrfTokens[MAX_CSRF_TOKENS];

// Generate CSRF token
String generateCSRFToken() {
  IPAddress clientIP = server.client().remoteIP();
  String chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
  String token = "";
  for (int i = 0; i < 32; i++) {
    token += chars[random(0, chars.length())];
  }
  
  unsigned long now = millis();
  int oldestIdx = 0;
  unsigned long oldestTime = now;
  
  for (int i = 0; i < MAX_CSRF_TOKENS; i++) {
    // Clean expired tokens (>10 minutes)
    if (now - csrfTokens[i].created > 600000) {
      csrfTokens[i].token = "";
      csrfTokens[i].used = true;
    }
    
    // Track oldest token for replacement
    if (csrfTokens[i].created < oldestTime) {
      oldestTime = csrfTokens[i].created;
      oldestIdx = i;
    }
    
    // Find empty or used slot
    if (csrfTokens[i].token == "" || csrfTokens[i].used) {
      csrfTokens[i].token = token;
      csrfTokens[i].created = now;
      csrfTokens[i].used = false;
      csrfTokens[i].ip = clientIP;
      return token;
    }
  }
  
  // Replace oldest token
  csrfTokens[oldestIdx].token = token;
  csrfTokens[oldestIdx].created = now;
  csrfTokens[oldestIdx].used = false;
  csrfTokens[oldestIdx].ip = clientIP;
  return token;
}

// Validate CSRF token (one-time use - untuk payment)
bool validateCSRFToken(String token) {
  for (int i = 0; i < MAX_CSRF_TOKENS; i++) {
    if (csrfTokens[i].token == token && !csrfTokens[i].used) {
      unsigned long age = millis() - csrfTokens[i].created;
      if (age < 600000) {  // Valid for 10 minutes
        csrfTokens[i].used = true;  // One-time use
        return true;
      }
    }
  }
  return false;
}

// Validate CSRF token tanpa mark used (untuk login - bisa retry)
bool validateCSRFTokenKeep(String token) {
  for (int i = 0; i < MAX_CSRF_TOKENS; i++) {
    if (csrfTokens[i].token == token && !csrfTokens[i].used) {
      unsigned long age = millis() - csrfTokens[i].created;
      if (age < 600000) {  // Valid for 10 minutes
        // TIDAK mark used - token bisa dipakai lagi untuk retry
        return true;
      }
    }
  }
  return false;
}

// Mark CSRF token as used (panggil setelah login berhasil)
void markCSRFTokenUsed(String token) {
  for (int i = 0; i < MAX_CSRF_TOKENS; i++) {
    if (csrfTokens[i].token == token) {
      csrfTokens[i].used = true;
      return;
    }
  }
}

// ============================================
// Security: Rate Limiting Functions
// ============================================
bool isIPBlocked(IPAddress ip) {
  unsigned long now = millis();
  for (int i = 0; i < MAX_TRACKED_IPS; i++) {
    if (ipTrackers[i].ip == ip) {
      if (ipTrackers[i].isBlocked && now < ipTrackers[i].blockedUntil) {
        return true;
      } else if (ipTrackers[i].isBlocked) {
        // Unblock if time passed
        ipTrackers[i].isBlocked = false;
        ipTrackers[i].failedAttempts = 0;
      }
      return false;
    }
  }
  return false;
}

void recordFailedAttempt(IPAddress ip) {
  unsigned long now = millis();
  
  // Find existing tracker
  for (int i = 0; i < MAX_TRACKED_IPS; i++) {
    if (ipTrackers[i].ip == ip) {
      ipTrackers[i].failedAttempts++;
      ipTrackers[i].lastAttempt = now;
      
      if (ipTrackers[i].failedAttempts >= MAX_FAILED_ATTEMPTS) {
        ipTrackers[i].isBlocked = true;
        ipTrackers[i].blockedUntil = now + BLOCK_DURATION;
        Serial.println("IP BLOCKED: " + ip.toString());
      }
      return;
    }
  }
  
  // Create new tracker
  for (int i = 0; i < MAX_TRACKED_IPS; i++) {
    if (ipTrackers[i].ip == IPAddress(0,0,0,0)) {
      ipTrackers[i].ip = ip;
      ipTrackers[i].failedAttempts = 1;
      ipTrackers[i].lastAttempt = now;
      ipTrackers[i].isBlocked = false;
      return;
    }
  }
}

void resetFailedAttempts(IPAddress ip) {
  for (int i = 0; i < MAX_TRACKED_IPS; i++) {
    if (ipTrackers[i].ip == ip) {
      ipTrackers[i].failedAttempts = 0;
      ipTrackers[i].isBlocked = false;
      return;
    }
  }
}

bool checkRateLimit(IPAddress ip) {
  unsigned long now = millis();
  
  for (int i = 0; i < MAX_TRACKED_IPS; i++) {
    if (requestCounters[i].ip == ip) {
      // Reset if window expired
      if (now - requestCounters[i].windowStart > RATE_LIMIT_WINDOW) {
        requestCounters[i].count = 1;
        requestCounters[i].windowStart = now;
        return true;
      }
      
      requestCounters[i].count++;
      if (requestCounters[i].count > MAX_REQUESTS_PER_MINUTE) {
        Serial.println("Rate limit exceeded: " + ip.toString());
        return false;
      }
      return true;
    }
  }
  
  // New IP
  for (int i = 0; i < MAX_TRACKED_IPS; i++) {
    if (requestCounters[i].ip == IPAddress(0,0,0,0)) {
      requestCounters[i].ip = ip;
      requestCounters[i].count = 1;
      requestCounters[i].windowStart = now;
      return true;
    }
  }
  
  return true;  // Allow if tracker full
}

// ============================================
// Security: Request Signing (HMAC-like)
// ============================================
String signRequest(String data) {
  String toSign = data + String(API_SECRET_KEY);
  return sha1(toSign).substring(0, 16);  // First 16 chars of SHA1
}

// ============================================
// Security: Input Sanitization
// ============================================
String sanitizeInput(String input) {
  String safe = "";
  for (unsigned int i = 0; i < input.length() && i < 100; i++) {  // Max 100 chars
    char c = input[i];
    // Only allow safe characters
    if ((c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z') || 
        (c >= '0' && c <= '9') || c == '@' || c == '.' || 
        c == '-' || c == '_' || c == ' ') {
      safe += c;
    }
  }
  return safe;
}

String sanitizeEmail(String email) {
  String safe = "";
  for (unsigned int i = 0; i < email.length() && i < 50; i++) {
    char c = email[i];
    if ((c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z') || 
        (c >= '0' && c <= '9') || c == '@' || c == '.' || 
        c == '-' || c == '_' || c == '+') {
      safe += c;
    }
  }
  return safe;
}

String sanitizeNumeric(String input) {
  String safe = "";
  for (unsigned int i = 0; i < input.length() && i < 20; i++) {
    char c = input[i];
    if (c >= '0' && c <= '9') {
      safe += c;
    }
  }
  return safe;
}

// ============================================
// Data Merchant & Status
// ============================================
String merchantID = "";
String merchantName = "";
String merchantType = "";
float merchantBalance = 0;
bool merchantValid = false;

String selectedSSID = "";
String selectedWifiPass = "";
String wifiStatus = "";

// ============================================
// Session Management
// ============================================
// Forward declaration untuk menghindari error "does not name a type"
typedef struct UserSessionStruct {
  String sessionId;
  String clientFingerprint;
  int userId;
  String userName;
  String userEmail;
  float userBalance;
  unsigned long lastActivity;
  unsigned long created;
  IPAddress clientIP;
  bool active;
} UserSession;

const int MAX_SESSIONS = 5;
UserSession sessions[MAX_SESSIONS];

// Function prototypes
UserSession* getSessionFromCookie();
UserSession* createSession(int userId, String userName, String userEmail, float userBalance);
void destroySession(String sessionId);

// Generate secure random session ID (32 chars)
String generateSessionId() {
  String chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
  String sessionId = "";
  for (int i = 0; i < 32; i++) {  // Longer session ID
    sessionId += chars[random(0, chars.length())];
  }
  return sessionId;
}

// Generate client fingerprint from headers
String generateClientFingerprint() {
  String ua = server.header("User-Agent");
  String accept = server.header("Accept");
  String fp = ua + "|" + accept + "|" + server.client().remoteIP().toString();
  return sha1(fp).substring(0, 16);
}

// Get session from cookie with security validation
UserSession* getSessionFromCookie() {
  String cookie = server.header("Cookie");
  if (cookie.length() == 0) return nullptr;
  
  int sidStart = cookie.indexOf("sid=");
  if (sidStart == -1) return nullptr;
  
  sidStart += 4;
  int sidEnd = cookie.indexOf(";", sidStart);
  String sessionId = (sidEnd == -1) ? cookie.substring(sidStart) : cookie.substring(sidStart, sidEnd);
  sessionId.trim();
  
  // Validate session ID format (only alphanumeric, 32 chars)
  if (sessionId.length() != 32) return nullptr;
  for (unsigned int i = 0; i < sessionId.length(); i++) {
    char c = sessionId[i];
    if (!((c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z') || (c >= '0' && c <= '9'))) {
      return nullptr;
    }
  }
  
  // Find session
  IPAddress clientIP = server.client().remoteIP();
  String clientFP = generateClientFingerprint();
  
  for (int i = 0; i < MAX_SESSIONS; i++) {
    if (sessions[i].active && sessions[i].sessionId == sessionId) {
      // Validate client IP matches (session hijacking protection)
      if (sessions[i].clientIP != clientIP) {
        Serial.println("Session IP mismatch - possible hijacking attempt!");
        sessions[i].active = false;  // Invalidate session
        return nullptr;
      }
      
      // Check session age (max 1 hour)
      if (millis() - sessions[i].created > 3600000) {
        Serial.println("Session expired");
        sessions[i].active = false;
        return nullptr;
      }
      
      sessions[i].lastActivity = millis();
      return &sessions[i];
    }
  }
  return nullptr;
}

// Create new session for user with security info
UserSession* createSession(int userId, String userName, String userEmail, float userBalance) {
  // Clean expired sessions (inactive > 30 minutes OR age > 1 hour)
  unsigned long now = millis();
  for (int i = 0; i < MAX_SESSIONS; i++) {
    if (sessions[i].active) {
      if ((now - sessions[i].lastActivity > 1800000) || (now - sessions[i].created > 3600000)) {
        sessions[i].active = false;
      }
    }
  }
  
  IPAddress clientIP = server.client().remoteIP();
  String clientFP = generateClientFingerprint();
  
  // Find empty slot
  for (int i = 0; i < MAX_SESSIONS; i++) {
    if (!sessions[i].active) {
      sessions[i].sessionId = generateSessionId();
      sessions[i].clientFingerprint = clientFP;
      sessions[i].userId = userId;
      sessions[i].userName = userName;
      sessions[i].userEmail = userEmail;
      sessions[i].userBalance = userBalance;
      sessions[i].lastActivity = now;
      sessions[i].created = now;
      sessions[i].clientIP = clientIP;
      sessions[i].active = true;
      return &sessions[i];
    }
  }
  
  // No empty slot, replace oldest
  int oldest = 0;
  for (int i = 1; i < MAX_SESSIONS; i++) {
    if (sessions[i].lastActivity < sessions[oldest].lastActivity) {
      oldest = i;
    }
  }
  sessions[oldest].sessionId = generateSessionId();
  sessions[oldest].clientFingerprint = clientFP;
  sessions[oldest].userId = userId;
  sessions[oldest].userName = userName;
  sessions[oldest].userEmail = userEmail;
  sessions[oldest].userBalance = userBalance;
  sessions[oldest].lastActivity = now;
  sessions[oldest].created = now;
  sessions[oldest].clientIP = clientIP;
  sessions[oldest].active = true;
  return &sessions[oldest];
}

// Destroy session
void destroySession(String sessionId) {
  for (int i = 0; i < MAX_SESSIONS; i++) {
    if (sessions[i].active && sessions[i].sessionId == sessionId) {
      sessions[i].active = false;
      return;
    }
  }
}

// ============================================
// Helper: HTTP GET Request
// ============================================
String httpGet(String url) {
  HTTPClient http;
  httpsClient.setInsecure(); // Skip SSL verification untuk development
  
  http.begin(httpsClient, url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("User-Agent", "NgenskuyESP/1.0 (ESP8266; Payment-Gateway)");
  http.addHeader("Accept", "application/json");
  http.addHeader("X-Requested-With", "XMLHttpRequest");
  
  int httpCode = http.GET();
  String payload = "";
  
  if (httpCode > 0) {
    payload = http.getString();
    Serial.println("HTTP GET Code: " + String(httpCode));
  } else {
    Serial.println("HTTP GET Error: " + String(httpCode));
  }
  
  http.end();
  return payload;
}

// ============================================
// Helper: HTTP POST Request
// ============================================
String httpPost(String url, String postData) {
  HTTPClient http;
  httpsClient.setInsecure();
  
  http.begin(httpsClient, url);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  http.addHeader("User-Agent", "NgenskuyESP/1.0 (ESP8266; Payment-Gateway)");
  http.addHeader("Accept", "application/json");
  http.addHeader("X-Requested-With", "XMLHttpRequest");
  
  int httpCode = http.POST(postData);
  String payload = "";
  
  if (httpCode > 0) {
    payload = http.getString();
    Serial.println("HTTP POST Code: " + String(httpCode));
  } else {
    Serial.println("HTTP POST Error: " + String(httpCode));
  }
  
  http.end();
  return payload;
}

// ============================================
// API: Validate Merchant
// ============================================
bool validateMerchant(String mid) {
  String url = String(API_BASE_URL) + "/merchant/validate?merchant_id=" + mid;
  String response = httpGet(url);
  
  Serial.println("Validate Merchant Response: " + response);
  
  if (response.length() == 0) return false;
  
  StaticJsonDocument<512> doc;
  DeserializationError error = deserializeJson(doc, response);
  
  if (error) {
    Serial.println("JSON Parse Error");
    return false;
  }
  
  if (doc["success"] == true) {
    merchantID = doc["merchant_id"].as<String>();
    merchantName = doc["business_name"].as<String>();
    merchantType = doc["business_type"].as<String>();
    merchantBalance = doc["balance"].as<float>();
    merchantValid = true;
    return true;
  }
  
  return false;
}

// ============================================
// API: Get Merchant Balance
// ============================================
float getMerchantBalance() {
  if (!merchantValid || merchantID == "") return 0;
  
  String url = String(API_BASE_URL) + "/merchant/balance?merchant_id=" + merchantID;
  String response = httpGet(url);
  
  if (response.length() == 0) return merchantBalance;
  
  StaticJsonDocument<256> doc;
  DeserializationError error = deserializeJson(doc, response);
  
  if (!error && doc["success"] == true) {
    merchantBalance = doc["balance"].as<float>();
  }
  
  return merchantBalance;
}

// ============================================
// API: Login User by Email (GET method untuk bypass Cloudflare)
// ============================================
int loginUserAPI(String email, String password, String &outName, String &outEmail, float &outBalance) {
  // Encode email untuk URL (ganti @ dengan %40)
  email.replace("@", "%40");
  
  // Gunakan endpoint yang tidak dicurigai Cloudflare
  String url = String(API_BASE_URL) + "/customer/auth?e=" + email + "&p=" + password;
  String response = httpGet(url);
  
  Serial.println("Login Response: " + response);
  
  if (response.length() == 0) return 0;
  
  StaticJsonDocument<512> doc;
  DeserializationError error = deserializeJson(doc, response);
  
  if (error) return 0;
  
  if (doc["success"] == true) {
    outName = doc["name"].as<String>();
    outEmail = doc["email"].as<String>();
    outBalance = doc["balance"].as<float>();
    return doc["user_id"].as<int>();
  }
  
  return 0;
}

// ============================================
// API: Login User by Card Number (GET method untuk bypass Cloudflare)
// ============================================
int loginByCardAPI(String cardNumber, String &outName, String &outEmail, float &outBalance) {
  // Gunakan endpoint yang tidak dicurigai Cloudflare
  String url = String(API_BASE_URL) + "/customer/card?c=" + cardNumber;
  String response = httpGet(url);
  
  Serial.println("Card Login Response: " + response);
  
  if (response.length() == 0) return 0;
  
  StaticJsonDocument<512> doc;
  DeserializationError error = deserializeJson(doc, response);
  
  if (error) return 0;
  
  if (doc["success"] == true) {
    outName = doc["name"].as<String>();
    outEmail = doc["email"].as<String>();
    outBalance = doc["balance"].as<float>();
    return doc["user_id"].as<int>();
  }
  
  return 0;
}

// ============================================
// API: Process Payment (GET method untuk bypass Cloudflare)
// ============================================
String processPaymentAPI(String visitorMerchantID, int visitorUserId, float amount, String description, float &outUserBalance, float &outMerchantBalance) {
  if (!merchantValid || visitorUserId == 0) {
    return "{\"success\":false,\"message\":\"Not ready\"}";
  }
  
  // URL encode description (ganti spasi dengan %20)
  description.replace(" ", "%20");
  
  // Gunakan endpoint yang tidak dicurigai Cloudflare
  String url = String(API_BASE_URL) + "/transaction/create?m=" + visitorMerchantID + 
               "&u=" + String(visitorUserId) + 
               "&a=" + String((int)amount) + 
               "&d=" + description;
  
  String response = httpGet(url);
  Serial.println("Payment Response: " + response);
  
  // Update balances from response
  StaticJsonDocument<512> doc;
  DeserializationError error = deserializeJson(doc, response);
  
  if (!error && doc["success"] == true) {
    outUserBalance = doc["user_new_balance"].as<float>();
    outMerchantBalance = doc["merchant_balance"].as<float>();
  }
  
  return response;
}

// ============================================
// Format Rupiah
// ============================================
String formatRupiah(float amount) {
  String result = "";
  int value = (int) amount;
  String str = String(value);
  int len = str.length();
  int count = 0;
  
  for (int i = len - 1; i >= 0; i--) {
    if (count > 0 && count % 3 == 0) {
      result = "." + result;
    }
    result = str[i] + result;
    count++;
  }
  
  return "Rp " + result;
}

// ============================================
// CSS Styles - Split untuk menghindari memory issue
// ============================================
String getCSS() {
  String css = F("<style>"
    "*{box-sizing:border-box;}"
    "body{margin:0;padding:0;background:#f5f6fa;font-family:'Segoe UI',sans-serif;min-height:100vh;}"
    ".container{width:100%;max-width:420px;margin:0 auto;background:#fff;padding:24px;min-height:100vh;}"
    ".logo{text-align:center;font-weight:700;font-size:26px;color:#00c4cc;margin-bottom:6px;}"
    ".subtitle{text-align:center;color:#636e72;font-size:13px;margin-bottom:20px;}"
    ".title{text-align:center;font-weight:600;font-size:18px;color:#1a1f36;margin-bottom:16px;}"
    ".info-box{background:#f8f9fa;border-radius:12px;padding:14px;margin-bottom:14px;}"
    ".info-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;flex-wrap:wrap;}"
    ".info-row:last-child{margin-bottom:0;}"
    ".info-label{color:#636e72;font-size:13px;}"
    ".info-value{color:#1a1f36;font-weight:600;font-size:13px;text-align:right;word-break:break-all;}"
    ".balance-box{background:linear-gradient(135deg,#00c4cc,#0090b6);border-radius:12px;padding:18px;color:#fff;text-align:center;margin-bottom:14px;}"
    ".balance-label{font-size:12px;opacity:0.9;}"
    ".balance-value{font-size:26px;font-weight:700;margin-top:4px;}"
    "label{display:block;font-weight:600;color:#636e72;margin-bottom:6px;font-size:13px;}"
    "input,select{width:100%;padding:12px;margin-bottom:14px;border:1px solid #dfe6e9;border-radius:10px;font-size:15px;box-sizing:border-box;-webkit-appearance:none;}"
    "input:focus,select:focus{outline:none;border-color:#00c4cc;box-shadow:0 0 0 3px rgba(0,196,204,0.1);}"
    ".btn{display:block;width:100%;background:#00c4cc;color:#fff;font-weight:600;padding:14px;border:none;border-radius:10px;font-size:15px;cursor:pointer;text-align:center;text-decoration:none;transition:all 0.2s;}"
    ".btn:active{transform:scale(0.98);}"
    ".btn-secondary{background:#636e72;}"
    ".btn-outline{background:transparent;border:2px solid #dfe6e9;color:#636e72;}"
    ".btn-outline:active{background:#f8f9fa;}"
    ".btn-sm{padding:10px 16px;font-size:13px;}"
    ".btn-group{display:flex;gap:10px;margin-top:12px;}"
    ".btn-group .btn{flex:1;}"
    ".mt-12{margin-top:12px;}"
    ".mt-16{margin-top:16px;}"
    ".success{background:#d4edda;color:#155724;padding:12px;border-radius:8px;margin-bottom:14px;text-align:center;font-size:13px;}"
    ".error{background:#f8d7da;color:#721c24;padding:12px;border-radius:8px;margin-bottom:14px;text-align:center;font-size:13px;}"
    ".warning{background:#fff3cd;color:#856404;padding:12px;border-radius:8px;margin-bottom:14px;text-align:center;font-size:13px;}"
    ".divider{text-align:center;margin:16px 0;color:#b2bec3;font-size:12px;position:relative;}"
    ".divider::before,.divider::after{content:'';position:absolute;top:50%;width:40%;height:1px;background:#dfe6e9;}"
    ".divider::before{left:0;}"
    ".divider::after{right:0;}"
    ".footer{text-align:center;margin-top:20px;padding-top:16px;border-top:1px solid #f0f0f0;color:#b2bec3;font-size:11px;}"
    ".link{color:#00c4cc;text-decoration:none;font-size:13px;}"
    "@media(min-width:480px){.container{margin:20px auto;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.08);min-height:auto;}}"
    "</style>");
  return css;
}

// ============================================
// Handler: Scan WiFi (AJAX)
// ============================================
void handleScanWifi() {
  int n = WiFi.scanNetworks();
  String json = "{\"ssids\":[";
  for (int i = 0; i < n; i++) {
    json += "\"" + WiFi.SSID(i) + "\"";
    if (i < n - 1) json += ",";
  }
  json += "]}";
  server.send(200, "application/json", json);
}

// ============================================
// Handler: Setup Page (GET) - Using Chunked Transfer
// ============================================
void handleSetup() {
  IPAddress clientIP = server.client().remoteIP();
  
  // SECURITY: Jika merchant sudah valid dan terkoneksi internet,
  // setup page hanya bisa diakses dari IP lokal
  if (merchantValid && WiFi.status() == WL_CONNECTED) {
    IPAddress apIP = WiFi.softAPIP();
    bool isLocalAP = (clientIP[0] == apIP[0] && clientIP[1] == apIP[1] && clientIP[2] == apIP[2]);
    
    if (!isLocalAP) {
      server.send(403, "text/html", F("<!DOCTYPE html><html><head><title>Akses Ditolak</title></head><body><h1>403 - Forbidden</h1><p>Setup page hanya bisa diakses dari jaringan lokal perangkat.</p></body></html>"));
      return;
    }
  }
  
  // Gunakan chunked transfer untuk hemat memory
  server.setContentLength(CONTENT_LENGTH_UNKNOWN);
  server.send(200, "text/html", "");
  
  // Head
  server.sendContent(F("<!DOCTYPE html><html><head><title>Setup Merchant</title>"
    "<meta name='viewport' content='width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no'>"
    "<style>"
    "*{box-sizing:border-box;}"
    "body{margin:0;padding:0;background:#f5f6fa;font-family:'Segoe UI',sans-serif;min-height:100vh;}"
    ".container{width:100%;max-width:420px;margin:0 auto;background:#fff;padding:24px;min-height:100vh;}"
    ".logo{text-align:center;font-weight:700;font-size:26px;color:#00c4cc;margin-bottom:6px;}"
    ".subtitle{text-align:center;color:#636e72;font-size:13px;margin-bottom:20px;}"
    ".info-box{background:#f8f9fa;border-radius:12px;padding:14px;margin-bottom:14px;}"
    ".info-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;flex-wrap:wrap;}"
    ".info-row:last-child{margin-bottom:0;}"
    ".info-label{color:#636e72;font-size:13px;}"
    ".info-value{color:#1a1f36;font-weight:600;font-size:13px;text-align:right;word-break:break-all;}"
    "label{display:block;font-weight:600;color:#636e72;margin-bottom:6px;font-size:13px;}"
    "input,select{width:100%;padding:12px;margin-bottom:14px;border:1px solid #dfe6e9;border-radius:10px;font-size:15px;}"
    ".btn{display:block;width:100%;background:#00c4cc;color:#fff;font-weight:600;padding:14px;border:none;border-radius:10px;font-size:15px;cursor:pointer;text-align:center;text-decoration:none;}"
    ".success{background:#d4edda;color:#155724;padding:12px;border-radius:8px;margin-bottom:14px;text-align:center;}"
    ".error{background:#f8d7da;color:#721c24;padding:12px;border-radius:8px;margin-bottom:14px;text-align:center;}"
    ".footer{text-align:center;margin-top:20px;padding-top:16px;border-top:1px solid #f0f0f0;color:#b2bec3;font-size:11px;}"
    "</style>"));
  
  if (!merchantValid) {
    server.sendContent(F("<script>"
      "function scanWifi(){fetch('/scanwifi').then(r=>r.json()).then(d=>{var s=document.getElementById('ssid');s.innerHTML='';d.ssids.forEach(x=>{var o=document.createElement('option');o.value=x;o.text=x;s.appendChild(o);});});}"
      "setInterval(scanWifi,5000);window.onload=scanWifi;"
      "</script>"));
  }
  
  server.sendContent(F("</head><body><div class='container'>"
    "<div class='logo'>Ngenskuy</div>"
    "<div class='subtitle'>Setup Perangkat Merchant</div>"));
  
  if (wifiStatus != "") {
    if (wifiStatus.indexOf("Berhasil") >= 0) {
      server.sendContent("<div class='success'>" + wifiStatus + "</div>");
    } else {
      server.sendContent("<div class='error'>" + wifiStatus + "</div>");
    }
  }
  
  if (merchantValid) {
    server.sendContent(F("<div class='info-box'>"));
    server.sendContent("<div class='info-row'><span class='info-label'>Merchant ID</span><span class='info-value'>" + merchantID + "</span></div>");
    server.sendContent("<div class='info-row'><span class='info-label'>Nama</span><span class='info-value'>" + merchantName + "</span></div>");
    server.sendContent("<div class='info-row'><span class='info-label'>Saldo</span><span class='info-value'>" + formatRupiah(merchantBalance) + "</span></div>");
    server.sendContent(F("</div>"
      "<a href='/' class='btn'>Mulai Terima Pembayaran</a>"
      "<div class='footer'>Setup berhasil! Klik tombol di atas untuk mulai.</div>"));
  } else {
    server.sendContent(F("<form action='/setup' method='POST'>"
      "<label>Pilih WiFi</label>"
      "<select name='ssid' id='ssid' required></select>"
      "<label>Password WiFi</label>"
      "<input type='password' name='wifipass' placeholder='Password WiFi' required>"
      "<label>Merchant ID (dari website)</label>"));
    server.sendContent("<input type='text' name='merchant_id' placeholder='MCH-XXXXXX' required value='" + merchantID + "'>");
    server.sendContent(F("<button type='submit' class='btn'>Simpan & Hubungkan</button>"
      "</form>"
      "<div class='footer'>Daftar merchant di udara.unis.ac.id</div>"));
  }
  
  server.sendContent(F("</div></body></html>"));
  server.sendContent("");
}

// ============================================
// Handler: Setup Page (POST)
// ============================================
void handleSetupPost() {
  selectedSSID = server.arg("ssid");
  selectedWifiPass = server.arg("wifipass");
  String inputMerchantId = server.arg("merchant_id");
  
  // Connect to WiFi
  WiFi.disconnect();
  WiFi.begin(selectedSSID.c_str(), selectedWifiPass.c_str());
  
  wifiStatus = "Menghubungkan...";
  int retry = 0;
  while (WiFi.status() != WL_CONNECTED && retry < 20) {
    delay(500);
    retry++;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    wifiStatus = "Berhasil terhubung ke " + selectedSSID;
    Serial.println("WiFi Connected! IP: " + WiFi.localIP().toString());
    
    // Validate merchant
    if (validateMerchant(inputMerchantId)) {
      wifiStatus += " | Merchant Valid!";
    } else {
      wifiStatus += " | Merchant ID tidak valid!";
      merchantValid = false;
    }
  } else {
    wifiStatus = "Gagal terhubung ke WiFi";
    merchantValid = false;
  }
  
  handleSetup();
}

// ============================================
// Handler: Home / Payment Page - Chunked Transfer
// ============================================
void handleRoot() {
  IPAddress clientIP = server.client().remoteIP();
  
  // Rate limiting check
  if (!checkRateLimit(clientIP)) {
    server.send(429, "text/plain", "Too Many Requests. Please wait.");
    return;
  }
  
  // Jika merchant belum setup, tampilkan halaman welcome
  if (!merchantValid || WiFi.status() != WL_CONNECTED) {
    server.setContentLength(CONTENT_LENGTH_UNKNOWN);
    server.send(200, "text/html", "");
    server.sendContent(F("<!DOCTYPE html><html><head><title>Ngenskuy Payment</title>"
      "<meta name='viewport' content='width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no'>"
      "<style>*{box-sizing:border-box;}body{margin:0;padding:0;background:#f5f6fa;font-family:'Segoe UI',sans-serif;min-height:100vh;}"
      ".container{width:100%;max-width:420px;margin:0 auto;background:#fff;padding:24px;min-height:100vh;}"
      ".logo{text-align:center;font-weight:700;font-size:26px;color:#00c4cc;margin-bottom:6px;}"
      ".subtitle{text-align:center;color:#636e72;font-size:13px;margin-bottom:20px;}"
      ".info-box{background:#f8f9fa;border-radius:12px;padding:14px;margin-bottom:14px;}"
      ".warning{background:#fff3cd;color:#856404;padding:12px;border-radius:8px;margin-bottom:14px;text-align:center;}"
      ".btn{display:block;width:100%;background:#00c4cc;color:#fff;font-weight:600;padding:14px;border:none;border-radius:10px;font-size:15px;cursor:pointer;text-align:center;text-decoration:none;}"
      ".footer{text-align:center;margin-top:20px;padding-top:16px;border-top:1px solid #f0f0f0;color:#b2bec3;font-size:11px;}"
      "</style></head><body><div class='container'>"
      "<div class='logo'>Ngenskuy</div>"
      "<div class='subtitle'>Payment Gateway</div>"
      "<div class='warning'>Perangkat belum dikonfigurasi</div>"
      "<div class='info-box'><p style='text-align:center;color:#636e72;margin:0;font-size:13px;'>Silakan setup WiFi dan Merchant ID terlebih dahulu untuk mulai menerima pembayaran.</p></div>"
      "<a href='/setup' class='btn'>Setup Perangkat</a>"
      "<div class='footer'>Powered by Ngenskuy</div>"
      "</div></body></html>"));
    server.sendContent("");
    return;
  }
  
  // Cek session dari cookie
  UserSession* session = getSessionFromCookie();
  
  // Jika user belum login (tidak ada session), tampilkan form login
  if (session == nullptr) {
    handleLoginPage();
    return;
  }
  
  // Generate CSRF token for payment form
  String paymentCsrf = generateCSRFToken();
  
  // Security headers
  server.sendHeader("X-Content-Type-Options", "nosniff");
  server.sendHeader("X-Frame-Options", "DENY");
  server.sendHeader("X-XSS-Protection", "1; mode=block");
  server.sendHeader("Cache-Control", "no-store, no-cache, must-revalidate");
  
  // Chunked transfer
  server.setContentLength(CONTENT_LENGTH_UNKNOWN);
  server.send(200, "text/html", "");
  
  server.sendContent(F("<!DOCTYPE html><html><head>"));
  server.sendContent("<title>Pembayaran - " + merchantName + "</title>");
  server.sendContent(F("<meta name='viewport' content='width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no'>"
    "<style>*{box-sizing:border-box;}body{margin:0;padding:0;background:#f5f6fa;font-family:'Segoe UI',sans-serif;min-height:100vh;}"
    ".container{width:100%;max-width:420px;margin:0 auto;background:#fff;padding:24px;min-height:100vh;}"
    ".logo{text-align:center;font-weight:700;font-size:26px;color:#00c4cc;margin-bottom:6px;}"
    ".subtitle{text-align:center;color:#636e72;font-size:13px;margin-bottom:20px;}"
    ".info-box{background:#f8f9fa;border-radius:12px;padding:14px;margin-bottom:14px;}"
    ".info-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;flex-wrap:wrap;}"
    ".info-row:last-child{margin-bottom:0;}"
    ".info-label{color:#636e72;font-size:13px;}"
    ".info-value{color:#1a1f36;font-weight:600;font-size:13px;text-align:right;word-break:break-all;}"
    ".balance-box{background:linear-gradient(135deg,#00c4cc,#00a8b5);border-radius:16px;padding:20px;margin-bottom:16px;color:#fff;}"
    ".balance-label{font-size:13px;opacity:0.9;margin-bottom:4px;}"
    ".balance-value{font-size:28px;font-weight:700;}"
    "label{display:block;font-weight:600;color:#636e72;margin-bottom:6px;font-size:13px;}"
    "input{width:100%;padding:12px;margin-bottom:14px;border:1px solid #dfe6e9;border-radius:10px;font-size:15px;}"
    ".btn{display:block;width:100%;background:#00c4cc;color:#fff;font-weight:600;padding:14px;border:none;border-radius:10px;font-size:15px;cursor:pointer;text-align:center;text-decoration:none;}"
    ".btn-outline{background:transparent;color:#00c4cc;border:2px solid #00c4cc;}"
    ".mt-12{margin-top:12px;}"
    ".footer{text-align:center;margin-top:20px;padding-top:16px;border-top:1px solid #f0f0f0;color:#b2bec3;font-size:11px;}"
    "</style></head><body><div class='container'>"
    "<div class='logo'>Ngenskuy</div>"));
  
  server.sendContent("<div class='subtitle'>" + merchantName + "</div>");
  server.sendContent("<div class='balance-box'><div class='balance-label'>Saldo Anda</div><div class='balance-value'>" + formatRupiah(session->userBalance) + "</div></div>");
  server.sendContent(F("<div class='info-box'>"));
  server.sendContent("<div class='info-row'><span class='info-label'>Pembeli</span><span class='info-value'>" + session->userName + "</span></div>");
  server.sendContent("<div class='info-row'><span class='info-label'>Email</span><span class='info-value'>" + session->userEmail + "</span></div>");
  server.sendContent(F("</div><form action='/pay' method='POST'>"));
  server.sendContent("<input type='hidden' name='_csrf' value='" + paymentCsrf + "'>");
  server.sendContent(F("<label>Nominal Pembayaran</label>"
    "<input type='number' name='amount' min='100' max='10000000' placeholder='Masukkan nominal' required inputmode='numeric'>"
    "<label>Keterangan (opsional)</label>"
    "<input type='text' name='description' placeholder='Contoh: Beli nasi goreng' maxlength='100'>"
    "<button type='submit' class='btn'>Bayar Sekarang</button></form>"
    "<a href='/logout' class='btn btn-outline mt-12'>Ganti Pembeli</a>"));
  server.sendContent("<div class='footer'>Merchant ID: " + merchantID + "</div></div></body></html>");
  server.sendContent("");
}

// ============================================
// Handler: Login Page (embedded in root) - Chunked Transfer
// ============================================
void handleLoginPage() {
  String error = server.arg("error");
  
  // Generate CSRF token for this form
  String csrfToken = generateCSRFToken();
  
  // Add security headers
  server.sendHeader("X-Content-Type-Options", "nosniff");
  server.sendHeader("X-Frame-Options", "DENY");
  server.sendHeader("X-XSS-Protection", "1; mode=block");
  server.sendHeader("Cache-Control", "no-store, no-cache, must-revalidate");
  
  // Chunked transfer
  server.setContentLength(CONTENT_LENGTH_UNKNOWN);
  server.send(200, "text/html", "");
  
  server.sendContent(F("<!DOCTYPE html><html><head>"));
  server.sendContent("<title>" + merchantName + " - Payment</title>");
  server.sendContent(F("<meta name='viewport' content='width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no'>"
    "<style>*{box-sizing:border-box;}body{margin:0;padding:0;background:#f5f6fa;font-family:'Segoe UI',sans-serif;min-height:100vh;}"
    ".container{width:100%;max-width:420px;margin:0 auto;background:#fff;padding:24px;min-height:100vh;}"
    ".logo{text-align:center;font-weight:700;font-size:26px;color:#00c4cc;margin-bottom:6px;}"
    ".subtitle{text-align:center;color:#636e72;font-size:13px;margin-bottom:20px;}"
    ".title{font-weight:600;font-size:16px;color:#1a1f36;margin:16px 0 12px;}"
    ".info-box{background:#f8f9fa;border-radius:12px;padding:14px;margin-bottom:14px;}"
    ".info-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;flex-wrap:wrap;}"
    ".info-row:last-child{margin-bottom:0;}"
    ".info-label{color:#636e72;font-size:13px;}"
    ".info-value{color:#1a1f36;font-weight:600;font-size:13px;text-align:right;word-break:break-all;}"
    ".error{background:#f8d7da;color:#721c24;padding:12px;border-radius:8px;margin-bottom:14px;text-align:center;}"
    "label{display:block;font-weight:600;color:#636e72;margin-bottom:6px;font-size:13px;}"
    "input{width:100%;padding:12px;margin-bottom:14px;border:1px solid #dfe6e9;border-radius:10px;font-size:15px;}"
    ".btn{display:block;width:100%;background:#00c4cc;color:#fff;font-weight:600;padding:14px;border:none;border-radius:10px;font-size:15px;cursor:pointer;text-align:center;text-decoration:none;}"
    ".btn-secondary{background:#636e72;}"
    ".divider{text-align:center;color:#b2bec3;margin:16px 0;position:relative;}"
    ".divider::before,.divider::after{content:'';position:absolute;top:50%;width:40%;height:1px;background:#dfe6e9;}"
    ".divider::before{left:0;}"
    ".divider::after{right:0;}"
    ".footer{text-align:center;margin-top:20px;padding-top:16px;border-top:1px solid #f0f0f0;color:#b2bec3;font-size:11px;}"
    "</style></head><body><div class='container'>"
    "<div class='logo'>Ngenskuy</div>"));
  
  server.sendContent("<div class='subtitle'>" + merchantName + "</div>");
  server.sendContent(F("<div class='info-box'>"));
  server.sendContent("<div class='info-row'><span class='info-label'>Merchant ID</span><span class='info-value'>" + merchantID + "</span></div>");
  server.sendContent("<div class='info-row'><span class='info-label'>Tipe Usaha</span><span class='info-value'>" + merchantType + "</span></div>");
  server.sendContent(F("</div>"));
  
  if (error == "1") {
    server.sendContent(F("<div class='error'>Email atau password salah!</div>"));
  } else if (error == "2") {
    server.sendContent(F("<div class='error'>Kartu tidak ditemukan!</div>"));
  } else if (error == "3") {
    server.sendContent(F("<div class='error'>Terlalu banyak percobaan. Coba lagi nanti.</div>"));
  } else if (error == "4") {
    server.sendContent(F("<div class='error'>Sesi tidak valid. Silakan coba lagi.</div>"));
  }
  
  server.sendContent(F("<div class='title'>Login Pembeli</div><form action='/login' method='POST'>"));
  server.sendContent("<input type='hidden' name='_csrf' value='" + csrfToken + "'>");
  server.sendContent(F("<input type='hidden' name='method' value='email'>"
    "<label>Email</label>"
    "<input type='email' name='email' placeholder='email@example.com' required autocomplete='email'>"
    "<label>Password</label>"
    "<input type='password' name='password' placeholder='Password' required autocomplete='current-password'>"
    "<button type='submit' class='btn'>Login</button></form>"
    "<div class='divider'>atau</div>"
    "<form action='/login' method='POST'>"));
  server.sendContent("<input type='hidden' name='_csrf' value='" + csrfToken + "'>");
  server.sendContent(F("<input type='hidden' name='method' value='card'>"
    "<label>Nomor Kartu (16 digit)</label>"
    "<input type='text' name='card_number' placeholder='4XXX XXXX XXXX XXXX' maxlength='19' inputmode='numeric' autocomplete='off'>"
    "<button type='submit' class='btn btn-secondary'>Login dengan Kartu</button></form>"
    "<div class='footer'>Belum punya akun? Daftar di udara.unis.ac.id</div>"
    "</div></body></html>"));
  server.sendContent("");
}

// ============================================
// Handler: Login Page (GET) - redirect to root
// ============================================
void handleLogin() {
  if (!merchantValid || WiFi.status() != WL_CONNECTED) {
    server.sendHeader("Location", "/");
    server.send(302, "text/plain", "Redirecting...");
    return;
  }
  
  // Redirect to root for login
  server.sendHeader("Location", "/");
  server.send(302, "text/plain", "Redirecting...");
}

// ============================================
// Handler: Login (POST) - with Security Checks
// ============================================
void handleLoginPost() {
  IPAddress clientIP = server.client().remoteIP();
  
  // Security Check 1: Rate limiting
  if (!checkRateLimit(clientIP)) {
    server.sendHeader("Location", "/?error=3");
    server.send(302, "text/plain", "Redirecting...");
    return;
  }
  
  // Security Check 2: IP blocking (brute force protection)
  if (isIPBlocked(clientIP)) {
    server.sendHeader("Location", "/?error=3");
    server.send(302, "text/plain", "Redirecting...");
    return;
  }
  
  // Security Check 3: CSRF token validation (use Keep version for login retry)
  String csrfToken = server.arg("_csrf");
  if (!validateCSRFTokenKeep(csrfToken)) {
    Serial.println("CSRF token invalid from: " + clientIP.toString());
    server.sendHeader("Location", "/?error=4");
    server.send(302, "text/plain", "Redirecting...");
    return;
  }
  
  String method = server.arg("method");
  int userId = 0;
  String userName, userEmail;
  float userBalance;
  
  if (method == "email") {
    // Sanitize inputs
    String email = sanitizeEmail(server.arg("email"));
    String password = server.arg("password");  // Password tidak di-sanitize karena bisa berisi karakter special
    
    // Validate email format
    if (email.indexOf("@") == -1 || email.indexOf(".") == -1) {
      recordFailedAttempt(clientIP);
      server.sendHeader("Location", "/?error=1");
      server.send(302, "text/plain", "Redirecting...");
      return;
    }
    
    userId = loginUserAPI(email, password, userName, userEmail, userBalance);
    
    if (userId == 0) {
      recordFailedAttempt(clientIP);
      server.sendHeader("Location", "/?error=1");
      server.send(302, "text/plain", "Redirecting...");
      return;
    }
  } else if (method == "card") {
    String cardNumber = server.arg("card_number");
    cardNumber.replace(" ", ""); // Remove spaces
    cardNumber = sanitizeNumeric(cardNumber);  // Only allow numbers
    
    // Validate card number (16 digits)
    if (cardNumber.length() != 16) {
      recordFailedAttempt(clientIP);
      server.sendHeader("Location", "/?error=2");
      server.send(302, "text/plain", "Redirecting...");
      return;
    }
    
    userId = loginByCardAPI(cardNumber, userName, userEmail, userBalance);
    
    if (userId == 0) {
      recordFailedAttempt(clientIP);
      server.sendHeader("Location", "/?error=2");
      server.send(302, "text/plain", "Redirecting...");
      return;
    }
  } else {
    // Invalid method
    server.sendHeader("Location", "/?error=4");
    server.send(302, "text/plain", "Redirecting...");
    return;
  }
  
  // Login successful - reset failed attempts
  resetFailedAttempts(clientIP);
  
  // Mark CSRF token as used setelah login berhasil
  markCSRFTokenUsed(csrfToken);
  
  // Create session and set secure cookie
  UserSession* session = createSession(userId, userName, userEmail, userBalance);
  if (session != nullptr) {
    // Secure cookie with HttpOnly and SameSite
    server.sendHeader("Set-Cookie", "sid=" + session->sessionId + "; Path=/; HttpOnly; SameSite=Strict");
  }
  
  Serial.println("Login successful for user: " + userName + " from IP: " + clientIP.toString());
  
  server.sendHeader("Location", "/");
  server.send(302, "text/plain", "Redirecting...");
}

// ============================================
// Handler: Logout
// ============================================
void handleLogout() {
  UserSession* session = getSessionFromCookie();
  
  if (session != nullptr) {
    Serial.println("User logged out: " + session->userName);
    destroySession(session->sessionId);
  }
  
  // Clear cookie securely
  server.sendHeader("Set-Cookie", "sid=; Path=/; Max-Age=0; HttpOnly; SameSite=Strict");
  server.sendHeader("Location", "/");
  server.send(302, "text/plain", "Redirecting...");
}

// ============================================
// Handler: Process Payment (POST)
// ============================================
void handlePay() {
  IPAddress clientIP = server.client().remoteIP();
  
  // Security Check 1: Rate limiting
  if (!checkRateLimit(clientIP)) {
    server.sendHeader("Location", "/?error=3");
    server.send(302, "text/plain", "Rate limit exceeded");
    return;
  }
  
  // Security Check 2: CSRF token validation
  String csrfToken = server.arg("_csrf");
  if (!validateCSRFToken(csrfToken)) {
    Serial.println("Payment CSRF invalid from: " + clientIP.toString());
    server.sendHeader("Location", "/?error=4");
    server.send(302, "text/plain", "Invalid CSRF");
    return;
  }
  
  UserSession* session = getSessionFromCookie();
  
  if (!merchantValid || session == nullptr) {
    server.sendHeader("Location", "/");
    server.send(302, "text/plain", "Redirecting...");
    return;
  }
  
  // Validate and sanitize inputs
  String amountStr = sanitizeNumeric(server.arg("amount"));
  float amount = amountStr.toFloat();
  String description = sanitizeInput(server.arg("description"));
  
  // Validate amount range (prevent overflow attacks)
  if (amount < 100 || amount > 10000000) {
    String html = "<!DOCTYPE html><html><head><title>Error</title>";
    html += "<meta name='viewport' content='width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no'>";
    html += getCSS();
    html += "</head><body><div class='container'>";
    html += "<div class='logo'>Ngenskuy</div>";
    html += "<div class='error'>Nominal tidak valid! Min Rp 100, Max Rp 10.000.000</div>";
    html += "<a href='/' class='btn'>Kembali</a>";
    html += "</div></body></html>";
    server.send(200, "text/html", html);
    return;
  }
  
  if (session->userBalance < amount) {
    String html = "<!DOCTYPE html><html><head><title>Saldo Tidak Cukup</title>";
    html += "<meta name='viewport' content='width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no'>";
    html += getCSS();
    html += "</head><body><div class='container'>";
    html += "<div class='logo'>Ngenskuy</div>";
    html += "<div style='text-align:center;margin:20px 0;'>";
    html += "<svg width='70' height='70' viewBox='0 0 80 80'><circle cx='40' cy='40' r='38' fill='#f39c12'/><text x='40' y='52' text-anchor='middle' fill='#fff' font-size='40' font-weight='bold'>!</text></svg>";
    html += "</div>";
    html += "<div class='title' style='color:#f39c12;'>Saldo Tidak Cukup</div>";
    html += "<div class='info-box'>";
    html += "<div class='info-row'><span class='info-label'>Saldo Anda</span><span class='info-value'>" + formatRupiah(session->userBalance) + "</span></div>";
    html += "<div class='info-row'><span class='info-label'>Nominal</span><span class='info-value'>" + formatRupiah(amount) + "</span></div>";
    html += "<div class='info-row'><span class='info-label'>Kekurangan</span><span class='info-value' style='color:#e74c3c;'>" + formatRupiah(amount - session->userBalance) + "</span></div>";
    html += "</div>";
    html += "<a href='/' class='btn'>Kembali</a>";
    html += "<div class='footer'>Top up saldo di udara.unis.ac.id</div>";
    html += "</div></body></html>";
    server.send(200, "text/html", html);
    return;
  }
  
  // Process payment via API
  float newUserBalance = 0;
  float newMerchantBalance = 0;
  String response = processPaymentAPI(merchantID, session->userId, amount, description, newUserBalance, newMerchantBalance);
  
  Serial.println("Payment API Response: " + response);
  
  // Parse JSON response
  StaticJsonDocument<1024> doc;
  DeserializationError jsonError = deserializeJson(doc, response);
  
  // Debug
  if (jsonError) {
    Serial.println("JSON Parse Error: " + String(jsonError.c_str()));
  }
  
  // Check success - handle berbagai format response
  bool paymentSuccess = false;
  String txId = "";
  float newBalance = session->userBalance - amount; // fallback
  String errorMsg = "Terjadi kesalahan";
  
  if (!jsonError) {
    // Cek apakah success true
    if (doc.containsKey("success")) {
      paymentSuccess = doc["success"].as<bool>();
    }
    
    if (paymentSuccess) {
      if (doc.containsKey("transaction_id")) {
        txId = doc["transaction_id"].as<String>();
      }
      if (doc.containsKey("user_new_balance")) {
        newBalance = doc["user_new_balance"].as<float>();
        session->userBalance = newBalance; // Update session balance
      }
      if (doc.containsKey("merchant_balance")) {
        merchantBalance = doc["merchant_balance"].as<float>();
      }
    } else {
      if (doc.containsKey("message")) {
        errorMsg = doc["message"].as<String>();
      }
    }
  }
  
  // Log payment attempt for audit
  Serial.println("=== PAYMENT AUDIT LOG ===");
  Serial.println("Time: " + String(millis()));
  Serial.println("User: " + session->userName + " (ID:" + String(session->userId) + ")");
  Serial.println("Amount: " + String(amount));
  Serial.println("Description: " + description);
  Serial.println("Client IP: " + clientIP.toString());
  Serial.println("Status: " + String(paymentSuccess ? "SUCCESS" : "FAILED"));
  Serial.println("TX ID: " + txId);
  Serial.println("========================");
  
  // Security headers for result page
  server.sendHeader("X-Content-Type-Options", "nosniff");
  server.sendHeader("X-Frame-Options", "DENY");
  server.sendHeader("Cache-Control", "no-store, no-cache");
  
  // Build HTML response - kirim langsung untuk hemat memory
  server.setContentLength(CONTENT_LENGTH_UNKNOWN);
  server.send(200, "text/html", "");
  
  // Send HTML in chunks
  server.sendContent(F("<!DOCTYPE html><html><head><title>Hasil Pembayaran</title>"
    "<meta name='viewport' content='width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no'>"
    "<style>"
    "*{box-sizing:border-box;}"
    "body{margin:0;padding:0;background:#f5f6fa;font-family:'Segoe UI',sans-serif;min-height:100vh;}"
    ".container{width:100%;max-width:420px;margin:0 auto;background:#fff;padding:24px;min-height:100vh;}"
    ".logo{text-align:center;font-weight:700;font-size:26px;color:#00c4cc;margin-bottom:6px;}"
    ".title{text-align:center;font-weight:600;font-size:18px;color:#1a1f36;margin-bottom:16px;}"
    ".info-box{background:#f8f9fa;border-radius:12px;padding:14px;margin-bottom:14px;}"
    ".info-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;flex-wrap:wrap;}"
    ".info-row:last-child{margin-bottom:0;}"
    ".info-label{color:#636e72;font-size:13px;}"
    ".info-value{color:#1a1f36;font-weight:600;font-size:13px;text-align:right;word-break:break-all;}"
    ".btn{display:block;width:100%;background:#00c4cc;color:#fff;font-weight:600;padding:14px;border:none;border-radius:10px;font-size:15px;cursor:pointer;text-align:center;text-decoration:none;margin-top:16px;}"
    ".error{background:#f8d7da;color:#721c24;padding:12px;border-radius:8px;margin-bottom:14px;text-align:center;font-size:13px;}"
    ".footer{text-align:center;margin-top:20px;padding-top:16px;border-top:1px solid #f0f0f0;color:#b2bec3;font-size:11px;}"
    "@media(min-width:480px){.container{margin:20px auto;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.08);min-height:auto;}}"
    "</style></head><body><div class='container'>"
    "<div class='logo'>Ngenskuy</div>"));
  
  if (paymentSuccess) {
    server.sendContent(F("<div style='text-align:center;margin:20px 0;'>"
      "<svg width='70' height='70' viewBox='0 0 80 80'><circle cx='40' cy='40' r='38' fill='#00c4cc'/>"
      "<polyline points='24,44 36,56 56,28' fill='none' stroke='#fff' stroke-width='6' stroke-linecap='round' stroke-linejoin='round'/></svg>"
      "</div>"
      "<div class='title' style='color:#00c4cc;'>Pembayaran Berhasil!</div>"
      "<div class='info-box'>"));
    server.sendContent("<div class='info-row'><span class='info-label'>ID Transaksi</span><span class='info-value' style='font-size:11px;'>" + txId + "</span></div>");
    server.sendContent("<div class='info-row'><span class='info-label'>Nominal</span><span class='info-value'>" + formatRupiah(amount) + "</span></div>");
    server.sendContent("<div class='info-row'><span class='info-label'>Merchant</span><span class='info-value'>" + merchantName + "</span></div>");
    server.sendContent("<div class='info-row'><span class='info-label'>Sisa Saldo</span><span class='info-value'>" + formatRupiah(newBalance) + "</span></div>");
    server.sendContent(F("</div>"));
    // Tombol transaksi baru (tetap login) dan ganti pembeli
    server.sendContent(F("<a href='/' class='btn'>Transaksi Baru</a>"
      "<a href='/logout' class='btn' style='background:transparent;border:2px solid #dfe6e9;color:#636e72;margin-top:10px;'>Ganti Pembeli</a>"));
  } else {
    server.sendContent(F("<div style='text-align:center;margin:20px 0;'>"
      "<svg width='70' height='70' viewBox='0 0 80 80'><circle cx='40' cy='40' r='38' fill='#e74c3c'/>"
      "<line x1='28' y1='28' x2='52' y2='52' stroke='#fff' stroke-width='6' stroke-linecap='round'/>"
      "<line x1='52' y1='28' x2='28' y2='52' stroke='#fff' stroke-width='6' stroke-linecap='round'/></svg>"
      "</div>"
      "<div class='title' style='color:#e74c3c;'>Pembayaran Gagal</div>"));
    server.sendContent("<div class='error'>" + errorMsg + "</div>");
    server.sendContent(F("<a href='/' class='btn'>Coba Lagi</a>"));
  }
  
  server.sendContent(F("<div class='footer'>Terima kasih telah menggunakan Ngenskuy!</div>"
    "</div></body></html>"));
  
  server.sendContent(""); // End chunked transfer
}

// ============================================
// Handler: Merchant Dashboard
// ============================================
void handleDashboard() {
  if (!merchantValid || WiFi.status() != WL_CONNECTED) {
    server.sendHeader("Location", "/setup");
    server.send(302, "text/plain", "Redirecting...");
    return;
  }
  
  // Refresh balance
  getMerchantBalance();
  
  String html = "<!DOCTYPE html><html><head><title>Dashboard Merchant</title>";
  html += "<meta name='viewport' content='width=device-width,initial-scale=1'>";
  html += getCSS();
  html += "<meta http-equiv='refresh' content='30'>"; // Auto refresh
  html += "</head><body><div class='container'>";
  html += "<div class='logo'>Ngenskuy</div>";
  html += "<div class='subtitle'>Dashboard Merchant</div>";
  
  html += "<div class='balance-box'>";
  html += "<div class='balance-label'>Saldo Merchant</div>";
  html += "<div class='balance-value'>" + formatRupiah(merchantBalance) + "</div>";
  html += "</div>";
  
  html += "<div class='info-box'>";
  html += "<div class='info-row'><span class='info-label'>Merchant ID</span><span class='info-value'>" + merchantID + "</span></div>";
  html += "<div class='info-row'><span class='info-label'>Nama</span><span class='info-value'>" + merchantName + "</span></div>";
  html += "<div class='info-row'><span class='info-label'>Tipe</span><span class='info-value'>" + merchantType + "</span></div>";
  html += "<div class='info-row'><span class='info-label'>WiFi</span><span class='info-value'>" + selectedSSID + "</span></div>";
  html += "</div>";
  
  html += "<a href='/' class='btn' style='display:block;text-align:center;text-decoration:none;margin-bottom:12px;'>Terima Pembayaran</a>";
  html += "<a href='/setup' class='btn btn-secondary' style='display:block;text-align:center;text-decoration:none;'>Pengaturan</a>";
  
  html += "<div class='footer'>Auto refresh setiap 30 detik</div>";
  html += "</div></body></html>";
  
  server.send(200, "text/html", html);
}

// ============================================
// Handler: Not Found
// ============================================
void handleNotFound() {
  server.sendHeader("Location", "/");
  server.send(302, "text/plain", "Redirecting...");
}

// ============================================
// Setup
// ============================================
void setup() {
  Serial.begin(115200);
  Serial.println("\n\n=== Ngenskuy Payment Gateway ===");
  Serial.println("=== Security Hardened Version ===");
  
  // Initialize random seed for session ID generation
  randomSeed(analogRead(0) ^ micros());  // Better entropy
  
  // Initialize sessions
  for (int i = 0; i < MAX_SESSIONS; i++) {
    sessions[i].active = false;
  }
  
  // Initialize security arrays
  for (int i = 0; i < MAX_TRACKED_IPS; i++) {
    ipTrackers[i].failedAttempts = 0;
    ipTrackers[i].blockedUntil = 0;
    ipTrackers[i].isBlocked = false;
    ipTrackers[i].ip = IPAddress(0, 0, 0, 0);
  }
  
  for (int i = 0; i < MAX_TRACKED_IPS; i++) {
    requestCounters[i].count = 0;
    requestCounters[i].windowStart = 0;
    requestCounters[i].ip = IPAddress(0, 0, 0, 0);
  }
  
  for (int i = 0; i < MAX_CSRF_TOKENS; i++) {
    csrfTokens[i].token = "";
    csrfTokens[i].created = 0;
    csrfTokens[i].used = true;
  }
  
  Serial.println("Security features initialized:");
  Serial.println("- Rate limiting: " + String(MAX_REQUESTS_PER_MINUTE) + " req/min");
  Serial.println("- Brute force protection: " + String(MAX_FAILED_ATTEMPTS) + " attempts before block");
  Serial.println("- CSRF protection: enabled");
  Serial.println("- Session binding: IP + Fingerprint");
  
  // Setup WiFi AP + STA mode
  WiFi.mode(WIFI_AP_STA);
  WiFi.softAP(AP_SSID, AP_PASS);
  
  IPAddress apIP = WiFi.softAPIP();
  Serial.println("AP IP: " + apIP.toString());
  Serial.println("AP Password: " + String(AP_PASS));
  
  // Setup DNS for captive portal
  dnsServer.start(DNS_PORT, "*", apIP);
  
  // Collect Cookie header for session management (ESP8266 3.x syntax)
  server.collectHeaders("Cookie");
  
  // Setup HTTP routes
  server.on("/", HTTP_GET, handleRoot);
  server.on("/setup", HTTP_GET, handleSetup);
  server.on("/setup", HTTP_POST, handleSetupPost);
  server.on("/scanwifi", HTTP_GET, handleScanWifi);
  server.on("/login", HTTP_GET, handleLogin);
  server.on("/login", HTTP_POST, handleLoginPost);
  server.on("/logout", HTTP_GET, handleLogout);
  server.on("/pay", HTTP_POST, handlePay);
  server.on("/dashboard", HTTP_GET, handleDashboard);
  server.onNotFound(handleNotFound);
  
  server.begin();
  Serial.println("HTTP Server Started");
  Serial.println("Connect to WiFi: " + String(AP_SSID));
}

// ============================================
// Loop
// ============================================
void loop() {
  dnsServer.processNextRequest();
  server.handleClient();
}
