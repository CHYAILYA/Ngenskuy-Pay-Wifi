# UDARA - Sistem Pembayaran Digital

Demo : https://udara.unis.ac.id/Login

Aplikasi sistem pembayaran digital dengan fitur top-up wallet menggunakan Midtrans Payment Gateway Dan Analisa tranksasi pengeluaran & pendapatan oleh ai (kolosal.ai).

## 📋 Deskripsi

Ngenskuy adalah aplikasi web berbasis CodeIgniter 4 untuk manajemen pembayaran tagihan dan wallet digital untuk pembayaran offline prototype esp8266. Aplikasi ini memungkinkan pengguna untuk:

-Pembayaran Offline
- Top-up saldo wallet menggunakan berbagai metode pembayaran (E-Wallet, Bank Transfer, Credit Card)
- Melihat dan membayar tagihan
- Melacak histori transaksi
- Ringkasan Ai transaksi
- Saran Ai
- Manajemen pengguna (Admin)

## 🛠️ Teknologi yang Digunakan

- **Framework:** CodeIgniter 4 & Python
- **PHP:** 8.1+
- **Database:** MySQL
- **Payment Gateway:** Midtrans Snap API
- **Frontend:** HTML, CSS (Custom), JavaScript

## 📁 Struktur Folder

```
app/
├── Config/
│   ├── Routes.php          # Routing aplikasi
│   └── Midtrans.php        # Konfigurasi Midtrans
├── Controllers/
│   ├── Admin/              # Controllers untuk admin panel
│   │   ├── BaseAdminController.php
│   │   ├── BillController.php
│   │   ├── DashboardController.php
│   │   ├── ReportController.php
│   │   ├── SettingsController.php
│   │   ├── TransactionController.php
│   │   └── UserController.php
│   ├── Api/                # API Controllers
│   │   └── PaymentController.php   # Payment API (Midtrans)
│   ├── User/               # Controllers untuk user
│   │   ├── BillController.php
│   │   ├── DashboardController.php
│   │   ├── ProfileController.php
│   │   └── TopUpController.php
│   ├── Auth.php            # Authentication
│   └── Home.php            # Public pages
├── Libraries/
│   └── MidtransPayment.php # Library integrasi Midtrans
├── Models/
│   ├── BillModel.php
│   ├── TopupModel.php
│   ├── UserModel.php
│   ├── WalletModel.php
│   └── WalletTransactionModel.php
└── Views/
    ├── admin/              # Views untuk admin
    ├── user/               # Views untuk user
    └── layouts/            # Layout templates
```

## 🚀 Cara Instalasi

### Prerequisites

- PHP 8.1 atau lebih tinggi
- MySQL 5.7+
- Composer
- Extension PHP: intl, mbstring, curl, json, mysqlnd, python

### Langkah Instalasi

Upload file .ino ke dalam esp82666 (kalo punya esp32 Minta Ai ubah codingan menjadi esp32)
BACKEND : 
KE FOLDER BACKEND
PYTHON PIP INSTALL requirements.txt
PYTHON run.py
Note : Gunakan Laragon versi 6 kebwah link : https://github.com/leokhoa/laragon/releases/download/6.0.0/laragon-wamp.exe
tambahkan setting root menjadi /public

cara 2 & 3 bisa diskip dengan menambahkan . pada env
1. **Clone Repository**
   ```bash
   git clone [repository-url]
   cd hackaton
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Konfigurasi Environment**
   ```bash
   cp env .env
   ```
   
   Edit file `.env`:
   ```env
   CI_ENVIRONMENT = development
   
   app.baseURL = 'https://your-domain.com/'
   
   database.default.hostname = localhost
   database.default.database = your_database
   database.default.username = your_username
   database.default.password = your_password
   database.default.DBDriver = MySQLi
   
   # Midtrans Configuration
   midtrans.merchantId = YOUR_MERCHANT_ID
   midtrans.clientKey = YOUR_CLIENT_KEY
   midtrans.serverKey = YOUR_SERVER_KEY
   midtrans.isProduction = false
   ```

4. **Setup Database**
   
   Akses URL: `https://your-domain.com/setup-db`
   
   Atau jalankan migration secara manual.

5. **Konfigurasi Web Server**
   
   Arahkan document root ke folder `public/`

## 💳 Fitur Payment Gateway

### Midtrans Integration

Aplikasi terintegrasi dengan Midtrans Snap API yang mendukung:

- **E-Wallet:** GoPay, ShopeePay, OVO, DANA, LinkAja
- **Bank Transfer:** BCA, BNI, BRI, Mandiri, Permata
- **Credit Card:** Visa, Mastercard, JCB
- **Convenience Store:** Alfamart, Indomaret
- **QR Code:** QRIS

### API Endpoints

| Endpoint | Method | Deskripsi |
|----------|--------|-----------|
| `/api/payment/process` | POST | Buat transaksi top-up |
| `/api/payment/check-status` | GET | Cek status pembayaran |
| `/api/payment/notification` | POST | Webhook dari Midtrans |
| `/api/payment/finish` | GET | Redirect setelah pembayaran |

## 👥 Role & Hak Akses

### User
- Dashboard
- Top-up saldo
- Lihat & bayar tagihan
- Profil

### Admin
- Dashboard statistik
- Manajemen pengguna
- Manajemen tagihan
- Laporan transaksi
- Settings

## 📖 API Documentation

Lihat [DOCUMENTATION.md](DOCUMENTATION.md) untuk dokumentasi API lengkap.

## 🔒 Security Features

### Authentication & Authorization
- **Session-based authentication** dengan secure cookie settings
- **Role-based access control** (User, Merchant, Admin)
- **Password hashing** menggunakan Argon2ID algorithm
- **Automatic session regeneration** untuk mencegah session fixation

### Input Validation & Sanitization
- **Custom validation rules** (`app/Validation/CustomRules.php`)
  - `valid_phone` - Validasi nomor telepon Indonesia
  - `valid_amount` - Validasi nominal transaksi
  - `strong_password` - Validasi kekuatan password
  - `no_sql_injection` - Deteksi pola SQL injection
  - `no_xss` - Deteksi pola XSS attack
- **Prepared statements** via CodeIgniter Query Builder
- **HTML entity encoding** untuk output

### Security Filters (`app/Filters/`)
- **AuthFilter** - Authentication middleware
- **RateLimitFilter** - Rate limiting (60 req/min default)
- **ApiSecurityFilter** - Input validation dan security headers
- **MidtransSignatureFilter** - Webhook signature verification

### Rate Limiting
- Proteksi terhadap brute force attack
- Configurable per-endpoint limits
- IP-based tracking dengan cache

### Audit Logging
- **AuditLogger** (`app/Libraries/AuditLogger.php`)
  - Log semua aktivitas authentication
  - Log semua transaksi pembayaran
  - Log security events (rate limit, unauthorized access)
  - Sensitive data masking

### Security Headers
```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
```

### CSRF Protection
- Session-based CSRF tokens
- Token randomization enabled
- Auto-regeneration per request
- Exclusion untuk external webhooks

### Environment Security
- Credentials disimpan di `.env` (tidak di-commit)
- `.env.example` sebagai template
- Production mode enabled
- Force HTTPS

## 📁 Security Files Structure

```
app/
├── Filters/
│   ├── AuthFilter.php           # Authentication middleware
│   ├── RateLimitFilter.php      # Rate limiting protection
│   ├── ApiSecurityFilter.php    # API security & headers
│   └── MidtransSignatureFilter.php # Webhook verification
├── Helpers/
│   └── security_helper.php      # Security utility functions
├── Libraries/
│   └── AuditLogger.php          # Comprehensive audit logging
├── Validation/
│   └── CustomRules.php          # Custom validation rules
└── Config/
    ├── Filters.php              # Filter configuration
    ├── Security.php             # CSRF & security settings
    └── Validation.php           # Validation rules config
```

## 📝 Kontributor
├── King of PHP/
│   ├── Ahmad fikri         
│   ├── Chyailya
│   ├── Dika
│   └── Rachell

## 📄 License

MIT License - Lihat [LICENSE](LICENSE) untuk detail.
