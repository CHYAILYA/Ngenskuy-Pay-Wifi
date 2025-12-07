<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 * 
 * Routes Configuration
 * This file defines all URL routes for the application
 * Organized by domain: Auth, User, Admin, Payment API
 */

// ============================================
// Midtrans Webhook - MUST BE FIRST (no filters)
// ============================================
$routes->add('webhook/midtrans', 'MidtransWebhook::notify');
$routes->add('midtrans/notify', 'MidtransWebhook::notify');
$routes->add('payment/notify', 'MidtransWebhook::notify');
$routes->add('webhook/test', 'MidtransWebhook::test');

$routes->get('/', 'Home::index');
$routes->get('setup-db', 'Home::setupDb');
$routes->post('setup-db', 'Home::setupDb');

// ============================================
// Auth Routes - Login & Logout
// ============================================
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::login');
$routes->get('Login', 'Auth::login');
$routes->post('Login', 'Auth::login');
$routes->get('logout', 'Auth::logout');

// ============================================
// Auth Routes - Registration
// ============================================
$routes->get('register', 'Auth::register');
$routes->post('register', 'Auth::register');

// ============================================
// Auth Routes - Password Recovery
// ============================================
$routes->get('forgot', 'Auth::forgot');
$routes->post('forgot', 'Auth::forgot');
$routes->get('magic', 'Auth::magic');
$routes->post('magic', 'Auth::magic');

// ============================================
// Dashboard Routes - Role Based Redirect
// ============================================
$routes->get('dashboard', 'Auth::dashboard');

// ============================================
// User Routes
// ============================================
$routes->group('user', function($routes) {
    // Dashboard
    $routes->get('dashboard', 'User\DashboardController::index');
    
    // Top Up
    $routes->get('topup', 'User\TopUpController::index');
    
    // Transfer
    $routes->get('transfer', 'User\TransferController::index');
    $routes->post('transfer/process', 'User\TransferController::process');
    $routes->get('transfer/lookup', 'User\TransferController::lookup');
    
    // Bills
    $routes->get('bills', 'User\BillController::index');
    $routes->get('pay-bill/(:num)', 'User\BillController::pay/$1');
    
    // Payment History
    $routes->get('payments', 'User\PaymentController::index');
    
    // Profile
    $routes->get('profile', 'User\ProfileController::index');
    $routes->post('profile', 'User\ProfileController::update');
    $routes->post('profile/password', 'User\ProfileController::updatePassword');
});

// ============================================
// Payment API Routes (Public - No Auth)
// Handles Midtrans payment gateway callbacks
// ============================================
$routes->group('api/payment', function($routes) {
    $routes->post('process', 'Api\PaymentController::processTopUp');
    $routes->get('check-status', 'Api\PaymentController::checkStatus');
    $routes->post('notification', 'Api\PaymentController::notification');
    $routes->get('finish', 'Api\PaymentController::finish');
});

// ============================================
// ESP Device API Routes (Public - No Auth)
// Handles API requests from ESP8266/ESP32 devices
// URL: /endpoin/esp/...
// ALL GET methods to bypass Cloudflare challenge
// Using non-suspicious endpoint names
// ============================================
$routes->group('endpoin/esp', function($routes) {
    // Merchant endpoints (GET only)
    $routes->get('merchant/validate', 'Api\EspController::validateMerchant');
    $routes->get('merchant/balance', 'Api\EspController::getMerchantBalance');
    $routes->get('merchant/transactions', 'Api\EspController::getMerchantTransactions');
    
    // Customer endpoints - renamed to avoid Cloudflare blocking
    $routes->get('customer/auth', 'Api\EspController::customerAuth');       // was user/login
    $routes->get('customer/card', 'Api\EspController::customerCard');       // was user/login-card
    $routes->get('customer/balance', 'Api\EspController::getUserBalance');
    
    // Transaction endpoint - renamed to avoid Cloudflare blocking  
    $routes->get('transaction/create', 'Api\EspController::transactionCreate'); // was payment/process
    
    // Legacy endpoints - redirect to new endpoints for backward compatibility
    $routes->get('user/balance', 'Api\EspController::getUserBalance');
    $routes->get('payment/process', 'Api\EspController::processPayment');
    
    // CORS preflight
    $routes->options('(:any)', 'Api\EspController::options');
});

// Old API ESP routes (keep for backward compatibility)
$routes->group('api/esp', function($routes) {
    // Merchant endpoints
    $routes->get('merchant/validate', 'Api\EspController::validateMerchant');
    $routes->get('merchant/balance', 'Api\EspController::getMerchantBalance');
    $routes->get('merchant/transactions', 'Api\EspController::getMerchantTransactions');
    
    // User endpoints - use customerAuth and customerCard
    $routes->get('user/balance', 'Api\EspController::getUserBalance');
    
    // Payment endpoint (GET to bypass Cloudflare)
    $routes->get('payment/process', 'Api\EspController::processPayment');
    
    // CORS preflight
    $routes->options('(:any)', 'Api\EspController::options');
});

// ============================================
// Midtrans Webhook - MUST be accessible without auth
// ============================================
$routes->post('webhook/midtrans', 'MidtransWebhook::notify');
$routes->post('midtrans/notify', 'MidtransWebhook::notify');
$routes->post('payment/notify', 'MidtransWebhook::notify');

// Legacy payment routes for backward compatibility
// Use match() to support both GET and POST (bypass Cloudflare)
$routes->match(['get', 'post'], 'user/topup/process', 'Api\\PaymentController::processTopUp');
$routes->match(['get', 'post'], 'user/topup/regenerate', 'Api\\PaymentController::regenerateToken');
$routes->match(['get', 'post'], 'user/topup/callback', 'Api\\PaymentController::callback');
$routes->post('user/topup/notification', 'MidtransWebhook::notify');
$routes->match(['get', 'post'], 'user/topup/finish', 'MidtransWebhook::handleFinishRedirect');
$routes->match(['get', 'post'], 'user/topup/force-success', 'Api\\PaymentController::forceSuccess');
$routes->get('user/topup/check-status', 'Api\\PaymentController::checkStatus');
$routes->get('user/topup/sync', 'Api\\PaymentController::syncPendingTransactions');

// ============================================
// Merchant Routes
// ============================================
$routes->group('merchant', function($routes) {
    // Setup (first time)
    $routes->get('setup', 'Merchant\ProfileController::setup');
    $routes->post('setup', 'Merchant\ProfileController::setup');
    
    // Dashboard
    $routes->get('dashboard', 'Merchant\DashboardController::index');
    
    // Transactions
    $routes->get('transactions', 'Merchant\TransactionController::index');
    
    // QR Code Payment
    $routes->get('qrcode', 'Merchant\PaymentController::qrcode');
    
    // Payment Link
    $routes->get('payment/request', 'Merchant\PaymentController::request');
    $routes->post('payment/create', 'Merchant\PaymentController::create');
    
    // Withdrawal
    $routes->get('withdraw', 'Merchant\ProfileController::withdraw');
    $routes->post('withdraw', 'Merchant\ProfileController::processWithdraw');
    
    // Profile
    $routes->get('profile', 'Merchant\ProfileController::index');
    $routes->post('profile/update', 'Merchant\ProfileController::update');
});

// Public Merchant Payment Page (accessible by anyone)
$routes->get('pay/(:any)', 'PublicPayController::pay/$1');
$routes->post('pay/(:any)', 'PublicPayController::processPay/$1');

// ============================================
// AI Agent Routes (using /agent prefix to avoid conflicts)
// Proxy to Python AI Agent Backend
// Using GET to bypass Cloudflare POST blocking
// ============================================
$routes->group('agent', function($routes) {
    // General Chat (GET and POST for AJAX widget)
    $routes->get('chat', 'Api\AIAgent::chat');
    $routes->post('chat', 'Api\AIAgent::chat');
    $routes->get('chat/v2', 'Api\AIAgent::chatV2');
    $routes->get('clear-cache', 'Api\AIAgent::clearCache');
    
    // Payment Assistant
    $routes->get('payment/assistant', 'Api\AIAgent::paymentAssistant');
    $routes->get('quick-actions', 'Api\AIAgent::quickActions');
    
    // Device Control
    $routes->get('device/command', 'Api\AIAgent::deviceCommand');
    $routes->get('device/list', 'Api\AIAgent::deviceList');
    $routes->get('voice', 'Api\AIAgent::voiceCommand');
    
    // Financial Advisor
    $routes->get('finance/analyze', 'Api\AIAgent::financeAnalyze');
    $routes->get('finance/advice', 'Api\AIAgent::financeAdvice');
    
    // Notifications
    $routes->get('notifications', 'Api\AIAgent::notifications');
    $routes->get('summary', 'Api\AIAgent::summary');
    
    // Advanced AI Features
    $routes->get('insights', 'Api\AIAgent::insights');
    $routes->get('suggestions', 'Api\AIAgent::suggestions');
    $routes->get('clear-history', 'Api\AIAgent::clearHistory');
});

// ============================================
// Direct Kolosal AI Routes (Direct API Connection)
// No database/localhost dependency - direct to https://api.kolosal.ai
// ============================================
$routes->group('direct-ai', function($routes) {
    // General Chat
    $routes->get('chat', 'Api\DirectAIController::chat');
    
    // Payment Assistant
    $routes->get('payment', 'Api\DirectAIController::payment');
    
    // Financial Advisor
    $routes->get('finance', 'Api\DirectAIController::finance');
    
    // Device Control
    $routes->get('device', 'Api\DirectAIController::device');
    
    // Sentiment Analysis
    $routes->get('sentiment', 'Api\DirectAIController::sentiment');
    
    // Text Summarization
    $routes->get('summarize', 'Api\DirectAIController::summarize');
    
    // Test Connection
    $routes->get('test', 'Api\DirectAIController::test');
});

// ============================================
// Admin Routes
// ============================================
$routes->group('admin', function($routes) {
    // Dashboard
    $routes->get('dashboard', 'Admin\DashboardController::index');
    
    // Users Management
    $routes->get('users', 'Admin\UserController::index');
    $routes->get('users/create', 'Admin\UserController::create');
    $routes->post('users/create', 'Admin\UserController::create');
    $routes->get('users/edit/(:num)', 'Admin\UserController::edit/$1');
    $routes->post('users/edit/(:num)', 'Admin\UserController::edit/$1');
    $routes->get('users/delete/(:num)', 'Admin\UserController::delete/$1');
    
    // Transactions
    $routes->get('transactions', 'Admin\TransactionController::index');
    
    // Bills Management
    $routes->get('bills', 'Admin\BillController::index');
    $routes->get('bills/create', 'Admin\BillController::create');
    $routes->post('bills/create', 'Admin\BillController::create');
    
    // Reports
    $routes->get('reports', 'Admin\ReportController::index');
    
    // Settings
    $routes->get('settings', 'Admin\SettingsController::index');
    $routes->post('settings', 'Admin\SettingsController::index');
    
    // Logs
    $routes->get('logs', 'Admin\SettingsController::logs');
});
