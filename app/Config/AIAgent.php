<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class AIAgent extends BaseConfig
{
    /**
     * AI Agent Backend Configuration
     */

    /**
     * Python AI Agent Backend URL
     */
    public string $agentBackendUrl = 'http://103.85.60.82:5000';

    /**
     * AI Agent Timeout (seconds)
     */
    public int $agentTimeout = 60;

    /**
     * Enable Python backend fallback
     */
    public bool $enablePythonBackend = true;

    /**
     * Kolosal AI Configuration
     */

    /**
     * Kolosal AI API Key
     */
    public string $kolosalApiKey = 'kol_eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoiNDkyMDgwNTEtZjc0Ni00NzI0LTlkNzQtNTI2OGFkYzhkMmVkIiwia2V5X2lkIjoiM2NkYWY3NzMtNzJlMS00ZTY0LWEzOGEtODI4MjlmYTAwZjVkIiwia2V5X25hbWUiOiJOZ2Vuc2t1eSIsImVtYWlsIjoiYmVudGFwcm9qZWN0LmlkQGdtYWlsLmNvbSIsInJhdGVfbGltaXRfcnBzIjpudWxsLCJtYXhfY3JlZGl0X3VzZSI6bnVsbCwiY3JlYXRlZF9hdCI6MTc2NDk0OTc3MiwiZXhwaXJlc19hdCI6MTc5NjQ4NTc3MiwiaWF0IjoxNzY0OTQ5NzcyfQ.NU8PG1w9Xz6KcyRltd4nN2BJlzQqymOiIw3FL984PTg';

    /**
     * Kolosal AI API URL
     */
    public string $kolosalApiUrl = 'https://api.kolosal.ai/v1/chat/completions';

    /**
     * Kolosal AI Model
     */
    public string $kolosalModel = 'Claude Sonnet 4.5';

    /**
     * Kolosal AI Timeout (seconds)
     */
    public int $kolosalTimeout = 60;

    /**
     * Max tokens per response
     */
    public int $maxTokens = 1024;

    /**
     * Temperature (0-1, higher = more creative)
     */
    public float $temperature = 0.7;

    /**
     * Database Configuration for AI
     */

    /**
     * Store chat logs in database
     */
    public bool $storeChatLogs = true;

    /**
     * Store conversations in database
     */
    public bool $storeConversations = true;

    /**
     * Store detailed interactions
     */
    public bool $storeInteractions = true;

    /**
     * Cache user context (in seconds, 0 = no cache)
     */
    public int $contextCacheTTL = 3600; // 1 hour

    /**
     * AI Features Configuration
     */

    /**
     * Enable payment assistant
     */
    public bool $enablePaymentAssistant = true;

    /**
     * Enable financial analysis
     */
    public bool $enableFinancialAnalysis = true;

    /**
     * Enable device control
     */
    public bool $enableDeviceControl = true;

    /**
     * Enable voice commands
     */
    public bool $enableVoiceCommands = true;

    /**
     * Enable notifications
     */
    public bool $enableNotifications = true;

    /**
     * Enable daily summary
     */
    public bool $enableDailySummary = true;

    /**
     * Rate Limiting
     */

    /**
     * Max requests per user per minute
     */
    public int $rateLimit = 30;

    /**
     * Rate limit window (seconds)
     */
    public int $rateLimitWindow = 60;

    /**
     * System Prompts
     */

    /**
     * Default system prompt for general chat
     */
    public string $defaultSystemPrompt = "Kamu adalah UDARA AI Assistant, asisten cerdas untuk aplikasi UDARA (dompet digital dan smart home).

KEMAMPUAN KAMU:
1. 💰 Membantu dengan top up saldo dan pembayaran
2. 📊 Memberikan analisis keuangan dan saran finansial
3. 🏪 Membantu merchant dengan analisis pendapatan
4. 🏠 Membantu kontrol perangkat smart home
5. 🔔 Memberikan notifikasi dan pengingat cerdas
6. 🗣️ Memahami perintah suara dan natural language
7. 📱 Memberikan quick actions dan saran cerdas

PENTING:
- Jawab dengan ramah, singkat, dan dalam Bahasa Indonesia
- Gunakan emoji untuk membuat respons lebih menarik
- Jika user bertanya tentang saldo/transaksi, gunakan data dari konteks yang diberikan
- Untuk merchant, berikan analisis bisnis yang berguna
- Respons maksimal 2-3 paragraf kecuali user minta penjelasan detail";

    /**
     * Payment assistant prompt
     */
    public string $paymentAssistantPrompt = "Kamu adalah Payment Assistant UDARA. User bertanya tentang pembayaran, top up, atau transaksi keuangan.

Bantu user dengan:
- Memberitahu saldo dan riwayat pembayaran
- Memberikan rekomendasi top up atau pembayaran
- Menjelaskan proses pembayaran
- Memberikan tips menghemat

Jawab singkat dalam Bahasa Indonesia dengan emoji.";

    /**
     * Financial advisor prompt
     */
    public string $financialAdvisorPrompt = "Kamu adalah Financial Advisor cerdas untuk aplikasi UDARA.

Berdasarkan data finansial user, berikan saran keuangan yang berguna dan praktis.
Jawab dalam Bahasa Indonesia, singkat tapi informatif, dan berikan action points yang bisa dilakukan user.";

    /**
     * Logging Configuration
     */

    /**
     * Log AI requests
     */
    public bool $logRequests = true;

    /**
     * Log AI responses
     */
    public bool $logResponses = true;

    /**
     * Log errors
     */
    public bool $logErrors = true;

    /**
     * Analytics Configuration
     */

    /**
     * Track user sentiment
     */
    public bool $trackSentiment = true;

    /**
     * Track response times
     */
    public bool $trackResponseTimes = true;

    /**
     * Security Configuration
     */

    /**
     * API key encryption
     */
    public bool $encryptApiKey = false;

    /**
     * Sanitize user input
     */
    public bool $sanitizeInput = true;

    /**
     * Validate user messages length
     */
    public int $maxMessageLength = 5000;

    /**
     * Get configuration by key
     */
    public function get(string $key, $default = null)
    {
        return $this->$key ?? $default;
    }

    /**
     * Set configuration by key
     */
    public function set(string $key, $value): void
    {
        if (property_exists($this, $key)) {
            $this->$key = $value;
        }
    }
}
