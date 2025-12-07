<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Merchant Profile - UDARA</title>
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
            max-width: 500px;
        }
        .header {
            text-align: center;
            margin-bottom: 32px;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .header p {
            color: #94a3b8;
        }
        .card {
            background: #1e293b;
            border-radius: 20px;
            border: 1px solid #334155;
            padding: 32px;
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
        .form-input, .form-select {
            width: 100%;
            padding: 14px 16px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 10px;
            color: white;
            font-size: 16px;
        }
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #3b82f6;
        }
        .form-select option {
            background: #0f172a;
        }
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }
        .info-box {
            background: #3b82f620;
            border: 1px solid #3b82f6;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .info-box p {
            color: #93c5fd;
            font-size: 14px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏪 Setup Merchant Profile</h1>
            <p>Complete your business information to start accepting payments</p>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-error">
            <?= session()->getFlashdata('error') ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="info-box">
                <p>💡 Setelah setup selesai, Anda akan mendapatkan Merchant ID unik untuk menerima pembayaran dari customer.</p>
            </div>

            <form method="POST" action="/merchant/setup" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label">Business Name *</label>
                    <input type="text" name="business_name" class="form-input" required placeholder="e.g. Warung Makan Sari Rasa">
                </div>

                <div class="form-group">
                    <label class="form-label">Business Type *</label>
                    <select name="business_type" class="form-select" required>
                        <option value="">Select Type</option>
                        <?php foreach ($business_types as $key => $label): ?>
                        <option value="<?= $key ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Business Phone</label>
                    <input type="tel" name="phone" class="form-input" placeholder="e.g. 08123456789">
                </div>

                <div class="form-group">
                    <label class="form-label">Business Address</label>
                    <textarea name="address" class="form-input" rows="3" placeholder="Enter your business address" style="resize: vertical;"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Business Logo (Optional)</label>
                    <input type="file" name="logo" class="form-input" accept="image/*">
                </div>

                <button type="submit" class="btn">🚀 Create Merchant Profile</button>
            </form>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="/user/dashboard" style="color: #94a3b8; text-decoration: none; font-size: 14px;">
                    ← Kembali ke Dashboard User
                </a>
            </div>
        </div>
    </div>
</body>
</html>
