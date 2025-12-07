<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay to <?= esc($merchant['business_name']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            width: 100%;
            max-width: 480px;
        }
        .card {
            background: #1e293b;
            border-radius: 20px;
            border: 1px solid #334155;
            padding: 32px;
            margin-bottom: 16px;
        }
        .merchant-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .merchant-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin: 0 auto 16px;
        }
        .merchant-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .merchant-id {
            font-family: monospace;
            color: #10b981;
            font-size: 14px;
        }
        .amount-display {
            background: #0f172a;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            margin-bottom: 24px;
        }
        .amount-label {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .amount-value {
            font-size: 36px;
            font-weight: 700;
            color: #10b981;
        }
        .description {
            color: #94a3b8;
            font-size: 14px;
            margin-top: 8px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .form-input {
            width: 100%;
            padding: 14px 16px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 10px;
            color: white;
            font-size: 16px;
        }
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
        }
        .balance-info {
            background: #0f172a;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .balance-label {
            color: #64748b;
            font-size: 14px;
        }
        .balance-value {
            font-weight: 700;
            color: #10b981;
        }
        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .btn-secondary {
            background: #334155;
            color: white;
            margin-top: 12px;
        }
        .login-required {
            text-align: center;
            padding: 32px;
        }
        .login-required p {
            color: #94a3b8;
            margin-bottom: 16px;
        }
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #ef444420;
            border: 1px solid #ef4444;
            color: #fca5a5;
        }
        .alert-success {
            background: #10b98120;
            border: 1px solid #10b981;
            color: #6ee7b7;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #10b98130;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 20px;
        }
        .hidden { display: none; }
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <!-- Merchant Header -->
            <div class="merchant-header">
                <div class="merchant-logo">🏪</div>
                <div class="merchant-name"><?= esc($merchant['business_name']) ?></div>
                <div class="merchant-id">ID: <?= esc($merchant['merchant_id']) ?></div>
            </div>

            <!-- Amount Display -->
            <div class="amount-display" id="amountDisplay">
                <div class="amount-label">Amount to Pay</div>
                <?php if ($amount): ?>
                <div class="amount-value">Rp <?= number_format($amount, 0, ',', '.') ?></div>
                <?php if ($description): ?>
                <div class="description"><?= esc($description) ?></div>
                <?php endif; ?>
                <?php else: ?>
                <div class="amount-value" id="displayAmount">Rp 0</div>
                <?php endif; ?>
            </div>

            <!-- Alert Message -->
            <div id="alertMessage" class="alert hidden"></div>

            <!-- Success View -->
            <div id="successView" class="hidden">
                <div class="success-icon">✓</div>
                <h2 style="text-align: center; margin-bottom: 8px;">Payment Successful!</h2>
                <p style="text-align: center; color: #64748b; margin-bottom: 24px;" id="successDetail"></p>
                <a href="/user/dashboard" class="btn btn-primary" style="display: block; text-align: center; text-decoration: none;">Back to Dashboard</a>
            </div>

            <!-- Payment Form -->
            <div id="paymentForm">
                <?php if ($isLoggedIn): ?>
                    <!-- Balance Info -->
                    <div class="balance-info">
                        <span class="balance-label">💰 Your Wallet Balance</span>
                        <span class="balance-value">Rp <?= number_format($walletBalance, 0, ',', '.') ?></span>
                    </div>

                    <form id="payForm" method="POST">
                        <?php if (!$amount): ?>
                        <div class="form-group">
                            <label class="form-label">Amount (Rp)</label>
                            <input type="number" name="amount" id="inputAmount" class="form-input" min="1000" required placeholder="Enter amount">
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="amount" value="<?= $amount ?>">
                        <?php endif; ?>
                        
                        <input type="hidden" name="description" value="<?= esc($description ?? '') ?>">
                        
                        <button type="submit" class="btn btn-primary" id="payButton">
                            <span id="btnText">💳 Pay Now</span>
                            <span id="btnLoading" class="hidden"><span class="loading"></span> Processing...</span>
                        </button>
                    </form>
                    
                    <a href="/user/topup" class="btn btn-secondary" style="display: block; text-align: center; text-decoration: none;">
                        Top Up Wallet
                    </a>
                <?php else: ?>
                    <div class="login-required">
                        <p>Please login to make a payment</p>
                        <a href="/login?redirect=<?= urlencode(current_url() . '?' . http_build_query(['amount' => $amount, 'desc' => $description])) ?>" class="btn btn-primary" style="display: inline-block; text-decoration: none;">
                            Login to Continue
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="text-align: center; color: #64748b; font-size: 12px;">
            Powered by UDARA Payment System
        </div>

        <!-- AI Chat Widget for Payment -->
        <?php
        // Always inject payment context for AI chat widget, even for public/guest users
        $aiPaymentContext = [
            'merchant_name' => $merchant['business_name'] ?? ($merchant['name'] ?? ''),
            'merchant_id' => $merchant['merchant_id'] ?? '',
            'amount' => $amount ?? '',
            'description' => $description ?? '',
            'page' => $_SERVER['REQUEST_URI'] ?? '',
            // Optionally add more context if available
            'is_logged_in' => $isLoggedIn ?? false,
            'wallet_balance' => $walletBalance ?? null,
        ];
        ?>
        <script>
        // Expose payment context to widget
        window.AI_PAYMENT_CONTEXT = <?= json_encode($aiPaymentContext) ?>;
        </script>
        <?php echo view('components/ai_chat_widget_payment'); ?>
    </div>

    <script>
        <?php if (!$amount): ?>
        document.getElementById('inputAmount').addEventListener('input', function(e) {
            const value = parseInt(e.target.value) || 0;
            document.getElementById('displayAmount').textContent = 'Rp ' + value.toLocaleString('id-ID');
        });
        <?php endif; ?>

        document.getElementById('payForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('payButton');
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');
            const alertBox = document.getElementById('alertMessage');
            
            btn.disabled = true;
            btnText.classList.add('hidden');
            btnLoading.classList.remove('hidden');
            alertBox.classList.add('hidden');
            
            const formData = new FormData(this);
            
            fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('paymentForm').classList.add('hidden');
                    document.getElementById('amountDisplay').classList.add('hidden');
                    document.getElementById('successView').classList.remove('hidden');
                    document.getElementById('successDetail').textContent = 
                        `Rp ${parseInt(data.amount).toLocaleString('id-ID')} to ${data.merchant}\nTransaction ID: ${data.transaction_id}`;
                } else {
                    alertBox.className = 'alert alert-error';
                    alertBox.textContent = data.message;
                    alertBox.classList.remove('hidden');
                    btn.disabled = false;
                    btnText.classList.remove('hidden');
                    btnLoading.classList.add('hidden');
                }
            })
            .catch(err => {
                alertBox.className = 'alert alert-error';
                alertBox.textContent = 'Payment failed. Please try again.';
                alertBox.classList.remove('hidden');
                btn.disabled = false;
                btnText.classList.remove('hidden');
                btnLoading.classList.add('hidden');
            });
        });
    </script>
</body>
</html>
