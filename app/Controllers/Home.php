<?php

namespace App\Controllers;

/**
 * Home Controller
 * 
 * Handles public pages and database setup
 * 
 * @package App\Controllers
 */
class Home extends BaseController
{
    /**
     * Parse .env file and return key-value pairs
     */
    private function parseEnvFile(string $filePath): array
    {
        $values = [];
        if (!file_exists($filePath)) return $values;
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                [$key, $val] = explode('=', $line, 2);
                $values[trim($key)] = trim($val);
            }
        }
        return $values;
    }

    /**
     * Check database connection status
     */
    private function checkDatabaseConnection(): array
    {
        try {
            $db = \Config\Database::connect();
            return ['connected' => (bool) $db, 'message' => 'Connected'];
        } catch (\Exception $e) {
            return ['connected' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Landing page
     * 
     * @return string
     */
    public function index(): string
    {
        return view('welcome_message');
    }
    
    /**
     * Setup Wizard - Initial configuration
     * 
     * @return string HTML output
     */
    public function setupDb()
    {
        // Check if this is a POST request (form submission)
        // CI4 getMethod() returns uppercase
        if ($this->request->getMethod() === 'POST' || $this->request->getMethod() === 'post') {
            return $this->processSetup();
        }
        
        // Show setup form
        return $this->showSetupForm();
    }
    
    /**
     * Show setup wizard form
     */
    private function showSetupForm(): string
    {
        // Read current .env values
        $envPath = ROOTPATH . '.env';
        $envValues = $this->parseEnvFile($envPath);
        
        // Check database connection status
        $dbStatus = $this->checkDatabaseConnection();
        
        $html = '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UDARA - Setup Wizard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            color: #e2e8f0;
            padding: 40px 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { font-size: 32px; margin-bottom: 8px; }
        .header p { color: #94a3b8; }
        .card { 
            background: #1e293b; 
            border-radius: 16px; 
            border: 1px solid #334155;
            padding: 32px;
            margin-bottom: 24px;
        }
        .card h2 { 
            font-size: 18px; 
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 500;
            color: #94a3b8;
        }
        .form-group input, .form-group select { 
            width: 100%; 
            padding: 12px 16px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            color: white;
            font-size: 14px;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
        }
        .form-group small { 
            display: block;
            margin-top: 6px;
            color: #64748b;
            font-size: 12px;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .btn { 
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s;
        }
        .btn-primary { 
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            color: white;
            width: 100%;
        }
        .btn-primary:hover { transform: translateY(-2px); }
        .btn-success { background: #10b981; color: white; }
        .btn-secondary { background: #334155; color: white; }
        .status { 
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-success { background: #065f46; color: #6ee7b7; }
        .status-error { background: #7f1d1d; color: #fca5a5; }
        .status-warning { background: #78350f; color: #fcd34d; }
        .alert { 
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-info { background: #1e3a5f; border: 1px solid #3b82f6; }
        .alert-warning { background: #78350f; border: 1px solid #f59e0b; }
        .section-divider { 
            border-top: 1px solid #334155;
            margin: 24px 0;
            padding-top: 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 UDARA Setup Wizard</h1>
            <p>Konfigurasi awal aplikasi pembayaran digital</p>
        </div>
        
        <form method="POST" action="/setup-db">
            <!-- Database Configuration -->
            <div class="card">
                <h2>🗄️ Database Configuration 
                    ' . ($dbStatus['connected'] ? '<span class="status status-success">✓ Connected</span>' : '<span class="status status-error">✗ Not Connected</span>') . '
                </h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Database Host</label>
                        <input type="text" name="db_host" value="' . esc($envValues['database.default.hostname'] ?? 'localhost') . '" placeholder="localhost">
                    </div>
                    <div class="form-group">
                        <label>Database Port</label>
                        <input type="text" name="db_port" value="' . esc($envValues['database.default.port'] ?? '3306') . '" placeholder="3306">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Database Name</label>
                    <input type="text" name="db_name" value="' . esc($envValues['database.default.database'] ?? 'udara') . '" placeholder="udara">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Database Username</label>
                        <input type="text" name="db_user" value="' . esc($envValues['database.default.username'] ?? 'root') . '" placeholder="root">
                    </div>
                    <div class="form-group">
                        <label>Database Password</label>
                        <input type="password" name="db_pass" value="' . esc($envValues['database.default.password'] ?? '') . '" placeholder="••••••••">
                    </div>
                </div>
            </div>
            
            <!-- Application Configuration -->
            <div class="card">
                <h2>⚙️ Application Configuration</h2>
                
                <div class="form-group">
                    <label>Base URL</label>
                    <input type="url" name="app_url" value="' . esc($envValues['app.baseURL'] ?? base_url()) . '" placeholder="https://yourdomain.com/">
                    <small>URL lengkap aplikasi (dengan trailing slash)</small>
                </div>
                
                <div class="form-group">
                    <label>Environment</label>
                    <select name="app_env">
                        <option value="development"' . (($envValues['CI_ENVIRONMENT'] ?? '') === 'development' ? ' selected' : '') . '>Development</option>
                        <option value="production"' . (($envValues['CI_ENVIRONMENT'] ?? '') === 'production' ? ' selected' : '') . '>Production</option>
                    </select>
                    <small>Development = debug mode aktif, Production = optimized</small>
                </div>
            </div>
            
            <!-- Midtrans Configuration -->
            <div class="card">
                <h2>💳 Midtrans Payment Gateway</h2>
                
                <div class="alert alert-info">
                    <strong>ℹ️ Info:</strong> Dapatkan kredensial dari <a href="https://dashboard.midtrans.com" target="_blank" style="color: #60a5fa;">Midtrans Dashboard</a>
                </div>
                
                <div class="form-group">
                    <label>Merchant ID</label>
                    <input type="text" name="midtrans_merchant" value="' . esc($envValues['midtrans.merchantId'] ?? '') . '" placeholder="G123456789">
                </div>
                
                <div class="form-group">
                    <label>Client Key</label>
                    <input type="text" name="midtrans_client" value="' . esc($envValues['midtrans.clientKey'] ?? '') . '" placeholder="Mid-client-xxxxx">
                </div>
                
                <div class="form-group">
                    <label>Server Key</label>
                    <input type="password" name="midtrans_server" value="' . esc($envValues['midtrans.serverKey'] ?? '') . '" placeholder="Mid-server-xxxxx">
                    <small>⚠️ Jangan share server key ke siapapun</small>
                </div>
                
                <div class="form-group">
                    <label>Mode</label>
                    <select name="midtrans_production">
                        <option value="false"' . (($envValues['midtrans.isProduction'] ?? 'false') === 'false' ? ' selected' : '') . '>Sandbox (Testing)</option>
                        <option value="true"' . (($envValues['midtrans.isProduction'] ?? '') === 'true' ? ' selected' : '') . '>Production (Live)</option>
                    </select>
                </div>
            </div>
            
            <!-- Admin Account -->
            <div class="card">
                <h2>👤 Admin Account</h2>
                
                <div class="alert alert-warning">
                    <strong>⚠️ Perhatian:</strong> Isi jika ingin membuat akun admin baru. Kosongkan jika sudah ada.
                </div>
                
                <div class="form-group">
                    <label>Admin Name</label>
                    <input type="text" name="admin_name" value="" placeholder="Administrator">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Admin Email</label>
                        <input type="email" name="admin_email" value="" placeholder="admin@example.com">
                    </div>
                    <div class="form-group">
                        <label>Admin Password</label>
                        <input type="password" name="admin_password" value="" placeholder="Min. 6 karakter">
                    </div>
                </div>
            </div>
            
            <!-- Submit -->
            <button type="submit" class="btn btn-primary">
                🚀 Simpan Konfigurasi & Setup Database
            </button>
        </form>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Process setup form submission
     */
    private function processSetup(): string
    {
        $output = [];
        $errors = [];
        
        // Get form data
        $dbHost = $this->request->getPost('db_host') ?: 'localhost';
        $dbPort = $this->request->getPost('db_port') ?: '3306';
        $dbName = $this->request->getPost('db_name') ?: 'udara';
        $dbUser = $this->request->getPost('db_user') ?: 'root';
        $dbPass = $this->request->getPost('db_pass') ?: '';
        
        $output[] = "📝 Starting setup process...";
        $output[] = "Database: {$dbUser}@{$dbHost}:{$dbPort}/{$dbName}";
        
        // 1. Update .env file
        $envPath = ROOTPATH . '.env';
        $envUpdates = [
            'CI_ENVIRONMENT'             => $this->request->getPost('app_env'),
            'app.baseURL'                => $this->request->getPost('app_url'),
            'database.default.hostname'  => $dbHost,
            'database.default.port'      => $dbPort,
            'database.default.database'  => $dbName,
            'database.default.username'  => $dbUser,
            'database.default.password'  => $dbPass,
            'midtrans.merchantId'        => $this->request->getPost('midtrans_merchant'),
            'midtrans.clientKey'         => $this->request->getPost('midtrans_client'),
            'midtrans.serverKey'         => $this->request->getPost('midtrans_server'),
            'midtrans.isProduction'      => $this->request->getPost('midtrans_production'),
        ];
            $output[] = "⚠️ Note: Konfigurasi .env harus diupdate manual";
        
        // 2. Test database connection & setup tables
        $db = null;
        try {
            // Create manual MySQLi connection to bypass CI4 caching
            $mysqli = new \mysqli($dbHost, $dbUser, $dbPass, $dbName, (int)$dbPort);
            
            if ($mysqli->connect_error) {
                throw new \Exception("Connection failed: " . $mysqli->connect_error);
            }
            
            $mysqli->set_charset('utf8mb4');
            $output[] = "✓ Koneksi database berhasil";
            
            // Setup tables using raw mysqli
            $tableResults = $this->setupTablesRaw($mysqli);
            $output = array_merge($output, $tableResults);
            
            $mysqli->close();
            
        } catch (\Throwable $e) {
            $errors[] = "✗ Database error: " . $e->getMessage();
            $errors[] = "File: " . $e->getFile() . " Line: " . $e->getLine();
        }
        
        // 3. Create admin account if provided
        $adminEmail = $this->request->getPost('admin_email');
        $adminPassword = $this->request->getPost('admin_password');
        $adminName = $this->request->getPost('admin_name');
        
        if (!empty($adminEmail) && !empty($adminPassword) && !empty($adminName)) {
            try {
                $mysqli = new \mysqli($dbHost, $dbUser, $dbPass, $dbName, (int)$dbPort);
                
                if ($mysqli->connect_error) {
                    throw new \Exception("Connection failed: " . $mysqli->connect_error);
                }
                
                // Check if admin exists
                $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->bind_param("s", $adminEmail);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $output[] = "- Admin dengan email {$adminEmail} sudah ada";
                } else {
                    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
                    
                    // Check which columns exist in users table
                    $columns = [];
                    $checkCols = $mysqli->query("SHOW COLUMNS FROM users");
                    while ($col = $checkCols->fetch_assoc()) {
                        $columns[] = $col['Field'];
                    }
                    
                    $hasName = in_array('name', $columns);
                    $hasRole = in_array('role', $columns);
                    $hasCardNumber = in_array('card_number', $columns);
                    
                    // Build INSERT query based on available columns
                    if ($hasName && $hasRole && $hasCardNumber) {
                        $cardNumber = '4' . str_pad(mt_rand(0, 999999999999999), 15, '0', STR_PAD_LEFT);
                        $stmt = $mysqli->prepare("INSERT INTO users (name, email, password, role, card_number, created_at) VALUES (?, ?, ?, 'admin', ?, NOW())");
                        $stmt->bind_param("ssss", $adminName, $adminEmail, $hashedPassword, $cardNumber);
                    } elseif ($hasName && $hasRole) {
                        $stmt = $mysqli->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");
                        $stmt->bind_param("sss", $adminName, $adminEmail, $hashedPassword);
                    } elseif ($hasName) {
                        $stmt = $mysqli->prepare("INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
                        $stmt->bind_param("sss", $adminName, $adminEmail, $hashedPassword);
                    } else {
                        // Fallback: only email and password
                        $stmt = $mysqli->prepare("INSERT INTO users (email, password, created_at) VALUES (?, ?, NOW())");
                        $stmt->bind_param("ss", $adminEmail, $hashedPassword);
                    }
                    $stmt->execute();
                    
                    $output[] = "✓ Admin account created: {$adminEmail}";
                }
                
                $mysqli->close();
            } catch (\Throwable $e) {
                $errors[] = "✗ Gagal buat admin: " . $e->getMessage();
            }
        }
        
        // 4. Insert dummy data: admin, merchant, bills, payments
        try {
            $mysqli = new \mysqli($dbHost, $dbUser, $dbPass, $dbName, (int)$dbPort);
            if ($mysqli->connect_error) {
                throw new \Exception("Connection failed: " . $mysqli->connect_error);
            }

            // Dummy users: 1 admin, 3 merchants, 2 users
            $dummyUsers = [
                ["Admin User", "admin@dummy.com", "admin123", "admin"],
                ["Merchant One", "merchant1@dummy.com", "merchant123", "merchant"],
                ["Merchant Two", "merchant2@dummy.com", "merchant123", "merchant"],
                ["Merchant Three", "merchant3@dummy.com", "merchant123", "merchant"],
                ["User One", "user1@dummy.com", "user123", "user"],
                ["User Two", "user2@dummy.com", "user123", "user"],
            ];
            $validRoles = ['user', 'admin', 'merchant', 'superadmin'];
            foreach ($dummyUsers as $u) {
                $name = $u[0]; $email = $u[1]; $pass = password_hash($u[2], PASSWORD_DEFAULT); $role = strtolower(trim($u[3]));
                if (!in_array($role, $validRoles, true)) {
                    $role = 'user';
                }
                $cardNumber = '4' . str_pad(mt_rand(0, 999999999999999), 15, '0', STR_PAD_LEFT);
                $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 0) {
                    $stmt = $mysqli->prepare("INSERT INTO users (name, email, password, role, card_number, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("sssss", $name, $email, $pass, $role, $cardNumber);
                    $stmt->execute();
                    $output[] = "✓ Dummy user created: $email ($role)";
                }
            }

            // Dummy merchants: create merchant data for each merchant user
            $merchantTypes = ['Toko', 'Restoran', 'Jasa', 'Kantor'];
            $merchantIds = [];
            $res = $mysqli->query("SELECT id, name FROM users WHERE role = 'merchant'");
            $merchantNo = 1;
            $merchantMap = [];
            while ($row = $res->fetch_assoc()) {
                $merchantIdStr = 'MRC' . str_pad($merchantNo, 4, '0', STR_PAD_LEFT);
                $businessName = $row['name'] . " Store";
                $businessType = $merchantTypes[array_rand($merchantTypes)];
                $address = "Jl. Dummy No. " . $merchantNo;
                $phone = "0812" . str_pad($merchantNo, 8, '0', STR_PAD_LEFT);
                $logo = "merchant_logo_$merchantNo.png";
                $balance = mt_rand(1000000, 10000000);
                $commission = 2.5;
                $status = 'active';
                $stmt = $mysqli->prepare("INSERT IGNORE INTO merchants (user_id, merchant_id, business_name, business_type, address, phone, logo, balance, commission_rate, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("issssssdds", $row['id'], $merchantIdStr, $businessName, $businessType, $address, $phone, $logo, $balance, $commission, $status);
                $stmt->execute();
                // Get actual merchant table id
                $merchantRes = $mysqli->query("SELECT id FROM merchants WHERE user_id = " . intval($row['id']));
                $merchantRow = $merchantRes->fetch_assoc();
                if ($merchantRow) {
                    $merchantIds[] = $merchantRow['id'];
                    $merchantMap[$merchantRow['id']] = $row['id']; // merchant_id => user_id
                }
                $merchantNo++;
            }

            // Dummy bills & transactions for each merchant
            $transactionTypes = ['tagihan', 'bayar gaji', 'bayar wifi', 'biaya produksi', 'bayar hal tidak perlu'];
            // Ambil semua user id (selain merchant)
            $userIds = [];
            $resUsers = $mysqli->query("SELECT id FROM users WHERE role = 'user'");
            while ($urow = $resUsers->fetch_assoc()) {
                $userIds[] = $urow['id'];
            }
            foreach ($merchantIds as $mid) {
                // Bills
                $merchantUserId = $merchantMap[$mid];
                for ($i = 1; $i <= 10; $i++) {
                    $billType = $transactionTypes[array_rand($transactionTypes)];
                    $billNumber = "BILL" . $mid . str_pad($i, 3, '0', STR_PAD_LEFT);
                    $desc = "$billType merchant $mid ke-$i";
                    $amount = mt_rand(50000, 500000);
                    $adminFee = 2500;
                    $dueDate = date('Y-m-d', strtotime("-$i days"));
                    $period = date('Ym', strtotime("-$i days"));
                    $status = ($i % 2 == 0) ? 'paid' : 'pending';
                    $stmt = $mysqli->prepare("INSERT INTO bills (user_id, bill_type, bill_number, description, amount, admin_fee, due_date, period, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("isssddsss", $merchantUserId, $billType, $billNumber, $desc, $amount, $adminFee, $dueDate, $period, $status);
                    $stmt->execute();
                }
                // Merchant transactions
                for ($j = 1; $j <= 110; $j++) {
                    $txType = $transactionTypes[array_rand($transactionTypes)];
                    $txId = "TX" . $mid . str_pad($j, 4, '0', STR_PAD_LEFT);
                    $amount = mt_rand(50000, 2000000);
                    $fee = mt_rand(1000, 5000);
                    $netAmount = $amount - $fee;
                    $status = ($j % 3 == 0) ? 'success' : (($j % 3 == 1) ? 'pending' : 'failed');
                    $paymentMethod = 'wallet';
                    $desc = "$txType merchant $mid transaksi ke-$j";
                    // Pilih customer acak
                    $customerId = count($userIds) > 0 ? $userIds[array_rand($userIds)] : null;
                    $stmt = $mysqli->prepare("INSERT INTO merchant_transactions (merchant_id, customer_id, transaction_id, amount, fee, net_amount, status, payment_method, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("iissddsss", $mid, $customerId, $txId, $amount, $fee, $netAmount, $status, $paymentMethod, $desc);
                    $stmt->execute();
                }
            }

            // Dummy payments for paid bills
            $res = $mysqli->query("SELECT id, user_id, bill_number, amount FROM bills WHERE status = 'paid'");
            while ($row = $res->fetch_assoc()) {
                $userId = $row['user_id'];
                $billId = $row['id'];
                $orderId = "PAY" . $billId;
                $amount = $row['amount'];
                $status = 'success';
                $paymentType = 'bank_transfer';
                $snapToken = 'dummy-token-' . $billId;
                $midtransResp = '{"status":"success"}';
                $paidAt = date('Y-m-d H:i:s');
                $stmt = $mysqli->prepare("INSERT INTO topups (user_id, order_id, amount, status, payment_type, snap_token, midtrans_response, paid_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("isdsssss", $userId, $orderId, $amount, $status, $paymentType, $snapToken, $midtransResp, $paidAt);
                $stmt->execute();
                $output[] = "✓ Dummy payment created for bill $billId";
            }

            $mysqli->close();
        } catch (\Throwable $e) {
            $errors[] = "✗ Dummy data error: " . $e->getMessage();
        }

        // Show results
        return $this->showSetupResults($output, $errors);
        }

        /**
         * Tampilkan hasil setup wizard
         */
        private function showSetupResults(array $output, array $errors): string
        {
            $hasErrors = !empty($errors);
            $html = '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Setup Results</title>
        <style>
            body { font-family: Arial, sans-serif; background: #1e293b; color: #e2e8f0; padding: 40px; }
            .card { background: #0f172a; border-radius: 16px; border: 1px solid #334155; padding: 32px; max-width: 600px; margin: 0 auto; }
            .results { background: #1e293b; border-radius: 8px; padding: 20px; margin-bottom: 30px; }
            .results div { padding: 8px 0; border-bottom: 1px solid #334155; font-family: monospace; font-size: 13px; }
            .results div:last-child { border-bottom: none; }
            .success { color: #6ee7b7; }
            .error { color: #fca5a5; }
            .info { color: #94a3b8; }
            .btn { display: inline-block; padding: 14px 28px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 16px; text-decoration: none; margin: 0 8px; }
            .btn-primary { background: linear-gradient(135deg, #3b82f6, #6366f1); color: white; }
            .btn-secondary { background: #334155; color: white; }
        </style>
    </head>
    <body>
        <div class="card">
            <h2>' . ($hasErrors ? 'Setup Selesai dengan Warning' : 'Setup Berhasil!') . '</h2>
            <div class="results">';
            foreach ($output as $line) {
                $class = 'info';
                if (strpos($line, '✓') !== false) $class = 'success';
                if (strpos($line, '✗') !== false) $class = 'error';
                $html .= '<div class="' . $class . '">' . esc($line) . '</div>';
            }
            foreach ($errors as $line) {
                $html .= '<div class="error">' . esc($line) . '</div>';
            }
            $html .= '</div>
            <div>
                <a href="/login" class="btn btn-primary">🚀 Login Sekarang</a>
                <a href="/setup-db" class="btn btn-secondary">⚙️ Konfigurasi Ulang</a>
            </div>
        </div>
    </body>
    </html>';
            return $html;
    }
    
    /**
     * Setup database tables using raw mysqli
     */
    private function setupTablesRaw(\mysqli $mysqli): array
    {
        // Pastikan enum kolom role mengandung 'user', 'admin', dan 'merchant' SEBELUM update data
        $checkRoleCol = $mysqli->query("SHOW COLUMNS FROM users LIKE 'role'");
        if ($checkRoleCol && $checkRoleCol->num_rows > 0) {
            $roleCol = $checkRoleCol->fetch_assoc();
            if (strpos($roleCol['Type'], "enum") !== false) {
                preg_match_all("/'([^']+)'/", $roleCol['Type'], $matches);
                $enumValues = $matches[1];
                $required = ['user', 'admin', 'merchant'];
                $newEnum = array_unique(array_merge($enumValues, $required));
                $enumStr = "enum('" . implode("','", $newEnum) . "')";
                if (count(array_diff($required, $enumValues)) > 0) {
                    $mysqli->query("ALTER TABLE users MODIFY COLUMN role $enumStr DEFAULT 'user'");
                }
            }
        }
        // Setelah enum benar, update data role yang tidak valid
        $mysqli->query("UPDATE users SET role = 'user' WHERE role NOT IN ('user','admin','merchant') OR role IS NULL");
        $output = [];
        
        // ========================================
        // 1. USERS TABLE
        // ========================================
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('user', 'admin', 'merchant') DEFAULT 'user',
            card_number VARCHAR(16) UNIQUE,
            phone VARCHAR(20) NULL,
            address TEXT NULL,
            avatar VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_users_email (email),
            INDEX idx_users_role (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($mysqli->query($sql)) {
            $output[] = "✓ Table 'users' ready";
        } else {
            $output[] = "✗ users: " . $mysqli->error;
        }
        
        // Add missing columns to users table if they don't exist
        $columnsToAdd = [
            ['name' => 'name', 'sql' => "ALTER TABLE users ADD COLUMN name VARCHAR(255) NOT NULL DEFAULT '' AFTER id"],
            ['name' => 'password', 'sql' => "ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL DEFAULT '' AFTER email"],
            ['name' => 'role', 'sql' => "ALTER TABLE users ADD COLUMN role ENUM('user', 'admin', 'merchant') DEFAULT 'user' AFTER password"],
            ['name' => 'card_number', 'sql' => "ALTER TABLE users ADD COLUMN card_number VARCHAR(16) NULL AFTER role"],
            ['name' => 'phone', 'sql' => "ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER card_number"],
            ['name' => 'address', 'sql' => "ALTER TABLE users ADD COLUMN address TEXT NULL AFTER phone"],
            ['name' => 'avatar', 'sql' => "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER address"],
            ['name' => 'created_at', 'sql' => "ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"],
            ['name' => 'updated_at', 'sql' => "ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"],
        ];

        foreach ($columnsToAdd as $colInfo) {
            $checkCol = $mysqli->query("SHOW COLUMNS FROM users LIKE '{$colInfo['name']}'");
            if ($checkCol && $checkCol->num_rows === 0) {
                if ($mysqli->query($colInfo['sql'])) {
                    $output[] = "✓ Added column '{$colInfo['name']}' to users table";
                } else {
                    $output[] = "- Column '{$colInfo['name']}': " . $mysqli->error;
                }
            }
        }

        // Pastikan enum kolom role mengandung 'user', 'admin', dan 'merchant'
        $checkRoleCol = $mysqli->query("SHOW COLUMNS FROM users LIKE 'role'");
        if ($checkRoleCol && $checkRoleCol->num_rows > 0) {
            $roleCol = $checkRoleCol->fetch_assoc();
            if (strpos($roleCol['Type'], "enum") !== false) {
                // Ambil semua value enum yang sudah ada
                preg_match_all("/'([^']+)'/", $roleCol['Type'], $matches);
                $enumValues = $matches[1];
                $required = ['user', 'admin', 'merchant'];
                $newEnum = array_unique(array_merge($enumValues, $required));
                $enumStr = "enum('" . implode("','", $newEnum) . "')";
                if (count(array_diff($required, $enumValues)) > 0) {
                    if ($mysqli->query("ALTER TABLE users MODIFY COLUMN role $enumStr DEFAULT 'user'")) {
                        $output[] = "✓ Added missing values to enum 'role' column: " . implode(', ', array_diff($required, $enumValues));
                    } else {
                        $output[] = "- Failed to update enum for 'role': " . $mysqli->error;
                    }
                }
            }
        }
        
        // ========================================
        // 2. WALLETS TABLE
        // ========================================
        $sql = "CREATE TABLE IF NOT EXISTS wallets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL UNIQUE,
            balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_wallets_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($mysqli->query($sql)) {
            $output[] = "✓ Table 'wallets' ready";
        } else {
            $output[] = "✗ wallets: " . $mysqli->error;
        }
        
        // ========================================
        // 3. TOPUPS TABLE
        // ========================================
        $sql = "CREATE TABLE IF NOT EXISTS topups (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            order_id VARCHAR(100) NOT NULL UNIQUE,
            amount DECIMAL(15,2) NOT NULL,
            status ENUM('pending', 'success', 'failed', 'expired', 'challenge') DEFAULT 'pending',
            payment_type VARCHAR(50) NULL,
            snap_token VARCHAR(255) NULL,
            midtrans_response TEXT NULL,
            paid_at TIMESTAMP NULL,
            expired_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_topups_user (user_id),
            INDEX idx_topups_order (order_id),
            INDEX idx_topups_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($mysqli->query($sql)) {
            $output[] = "✓ Table 'topups' ready";
        } else {
            $output[] = "✗ topups: " . $mysqli->error;
        }
        
        // ========================================
        // 4. WALLET_TRANSACTIONS TABLE
        // ========================================
        $sql = "CREATE TABLE IF NOT EXISTS wallet_transactions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            type ENUM('credit', 'debit') NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            description VARCHAR(255) NULL,
            reference_type VARCHAR(50) NULL,
            reference_id INT UNSIGNED NULL,
            balance_before DECIMAL(15,2) DEFAULT 0.00,
            balance_after DECIMAL(15,2) DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_wallet_tx_user (user_id),
            INDEX idx_wallet_tx_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($mysqli->query($sql)) {
            $output[] = "✓ Table 'wallet_transactions' ready";
        } else {
            $output[] = "✗ wallet_transactions: " . $mysqli->error;
        }
        
        // ========================================
        // 5. BILLS TABLE
        // ========================================
        $sql = "CREATE TABLE IF NOT EXISTS bills (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            bill_type VARCHAR(50) NOT NULL,
            bill_number VARCHAR(100) NULL,
            description VARCHAR(255) NULL,
            amount DECIMAL(15,2) NOT NULL,
            admin_fee DECIMAL(10,2) DEFAULT 0.00,
            due_date DATE NULL,
            period VARCHAR(20) NULL,
            status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
            paid_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_bills_user (user_id),
            INDEX idx_bills_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($mysqli->query($sql)) {
            $output[] = "✓ Table 'bills' ready";
        } else {
            $output[] = "✗ bills: " . $mysqli->error;
        }
        
        // ========================================
        // 6. TRANSACTIONS TABLE
        // ========================================
        $sql = "CREATE TABLE IF NOT EXISTS transactions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            type VARCHAR(50) NOT NULL,
            description VARCHAR(255) NULL,
            amount DECIMAL(15,2) NOT NULL,
            status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
            reference VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_transactions_user (user_id),
            INDEX idx_transactions_type (type),
            INDEX idx_transactions_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($mysqli->query($sql)) {
            $output[] = "✓ Table 'transactions' ready";
        } else {
            $output[] = "✗ transactions: " . $mysqli->error;
        }
        
        // ========================================
        // 7. TRANSFERS TABLE
        // ========================================
        $sql = "CREATE TABLE IF NOT EXISTS transfers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sender_id INT UNSIGNED NOT NULL,
            receiver_id INT UNSIGNED NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            fee DECIMAL(10,2) DEFAULT 0.00,
            note VARCHAR(255) NULL,
            status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_transfers_sender (sender_id),
            INDEX idx_transfers_receiver (receiver_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($mysqli->query($sql)) {
            $output[] = "✓ Table 'transfers' ready";
        } else {
            $output[] = "✗ transfers: " . $mysqli->error;
        }
        
        // ========================================
        // 8. NOTIFICATIONS TABLE
        // ========================================
        $sql = "CREATE TABLE IF NOT EXISTS notifications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NULL,
            data JSON NULL,
            is_read TINYINT(1) DEFAULT 0,
            read_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_notifications_user (user_id),
            INDEX idx_notifications_read (is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($mysqli->query($sql)) {
            $output[] = "✓ Table 'notifications' ready";
        } else {
            $output[] = "✗ notifications: " . $mysqli->error;
        }
        
        // ========================================
        // 9. SETTINGS TABLE
        // ========================================
        $sql = "CREATE TABLE IF NOT EXISTS settings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT NULL,
            setting_type VARCHAR(20) DEFAULT 'string',
            description VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($mysqli->query($sql)) {
            $output[] = "✓ Table 'settings' ready";
            
            // Insert default settings
            $mysqli->query("INSERT IGNORE INTO settings (setting_key, setting_value, setting_type, description) VALUES
                ('app_name', 'UDARA', 'string', 'Application name'),
                ('transfer_fee', '0', 'int', 'Transfer fee in rupiah'),
                ('min_topup', '10000', 'int', 'Minimum top-up amount'),
                ('max_topup', '10000000', 'int', 'Maximum top-up amount')
            ");
        } else {
            $output[] = "✗ settings: " . $mysqli->error;
        }
        
        // ========================================
        // 10. ACTIVITY_LOGS TABLE
        // ========================================
        $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            action VARCHAR(100) NOT NULL,
            description TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            data JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_activity_user (user_id),
            INDEX idx_activity_action (action)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($mysqli->query($sql)) {
            $output[] = "✓ Table 'activity_logs' ready";
        } else {
            $output[] = "✗ activity_logs: " . $mysqli->error;
        }
        
        // ========================================
        // 11. MERCHANTS TABLE
        // ========================================
        $sql = "CREATE TABLE IF NOT EXISTS merchants (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL UNIQUE,
            merchant_id VARCHAR(20) NOT NULL UNIQUE,
            business_name VARCHAR(255) NOT NULL,
            business_type VARCHAR(50) NULL,
            address TEXT NULL,
            phone VARCHAR(20) NULL,
            logo VARCHAR(255) NULL,
            balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            commission_rate DECIMAL(5,2) NOT NULL DEFAULT 2.50,
            status ENUM('active', 'inactive', 'pending', 'suspended') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_merchants_user (user_id),
            INDEX idx_merchants_mid (merchant_id),
            INDEX idx_merchants_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($mysqli->query($sql)) {
            $output[] = "✓ Table 'merchants' ready";
        } else {
            $output[] = "✗ merchants: " . $mysqli->error;
        }
        
        // ========================================
        // 12. MERCHANT_TRANSACTIONS TABLE
        // ========================================
        $sql = "CREATE TABLE IF NOT EXISTS merchant_transactions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            merchant_id INT UNSIGNED NOT NULL,
            customer_id INT UNSIGNED NULL,
            transaction_id VARCHAR(50) NOT NULL UNIQUE,
            amount DECIMAL(15,2) NOT NULL,
            fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            net_amount DECIMAL(15,2) NOT NULL,
            status ENUM('pending', 'success', 'failed', 'refunded') DEFAULT 'pending',
            payment_method VARCHAR(50) DEFAULT 'wallet',
            description VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_mtx_merchant (merchant_id),
            INDEX idx_mtx_customer (customer_id),
            INDEX idx_mtx_status (status),
            INDEX idx_mtx_txid (transaction_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($mysqli->query($sql)) {
            $output[] = "✓ Table 'merchant_transactions' ready";
        } else {
            $output[] = "✗ merchant_transactions: " . $mysqli->error;
        }
        
        // ========================================
        // 13. MERCHANT_WITHDRAWALS TABLE
        // ========================================
        $sql = "CREATE TABLE IF NOT EXISTS merchant_withdrawals (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            merchant_id INT UNSIGNED NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            bank_name VARCHAR(50) NOT NULL,
            account_number VARCHAR(50) NOT NULL,
            account_name VARCHAR(255) NOT NULL,
            status ENUM('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
            admin_note TEXT NULL,
            processed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_mwd_merchant (merchant_id),
            INDEX idx_mwd_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($mysqli->query($sql)) {
            $output[] = "✓ Table 'merchant_withdrawals' ready";
        } else {
            $output[] = "✗ merchant_withdrawals: " . $mysqli->error;
        }
        
        // ========================================
        // POST-SETUP: Generate card numbers (only if column exists)
        // ========================================
        $checkCol = $mysqli->query("SHOW COLUMNS FROM users LIKE 'card_number'");
        if ($checkCol && $checkCol->num_rows > 0) {
            $result = $mysqli->query("SELECT id FROM users WHERE card_number IS NULL OR card_number = ''");
            if ($result) {
                $count = 0;
                while ($row = $result->fetch_assoc()) {
                    $cardNumber = '4' . str_pad(mt_rand(0, 999999999999999), 15, '0', STR_PAD_LEFT);
                    $stmt = $mysqli->prepare("UPDATE users SET card_number = ? WHERE id = ?");
                    $stmt->bind_param("si", $cardNumber, $row['id']);
                    $stmt->execute();
                    $count++;
                }
                if ($count > 0) {
                    $output[] = "✓ Generated card numbers for {$count} users";
                }
            }
        }
        
        // Create wallets for users without one
        $mysqli->query("INSERT IGNORE INTO wallets (user_id, balance, created_at)
            SELECT id, 0, NOW() FROM users WHERE id NOT IN (SELECT user_id FROM wallets)");
        $affected = $mysqli->affected_rows;
        if ($affected > 0) {
            $output[] = "✓ Created wallets for {$affected} users";
        }
        
        $output[] = "";
        $output[] = "📊 Setup completed: 13 tables configured";
        
        return $output;
    }
    
}
