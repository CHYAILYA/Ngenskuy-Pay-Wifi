<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('sidebar') ?>
<div class="nav-section">
    <div class="nav-section-title">Merchant ID</div>
    <div style="font-family: monospace; background: #0f172a; padding: 8px 12px; border-radius: 6px; font-size: 12px; color: #10b981; margin-bottom: 16px;">
        <?= esc($merchant['merchant_id'] ?? 'Not Set') ?>
    </div>
</div>
<div class="nav-section">
    <div class="nav-section-title">Main Menu</div>
    <a href="/merchant/dashboard" class="nav-item"><span class="nav-icon">📊</span> Dashboard</a>
    <a href="/merchant/transactions" class="nav-item"><span class="nav-icon">💳</span> Transactions</a>
    <a href="/merchant/qrcode" class="nav-item"><span class="nav-icon">📱</span> QR Code</a>
    <a href="/merchant/payment/request" class="nav-item active"><span class="nav-icon">🔗</span> Payment Link</a>
</div>
<div class="nav-section">
    <div class="nav-section-title">Finance</div>
    <a href="/merchant/withdraw" class="nav-item"><span class="nav-icon">🏦</span> Withdraw</a>
</div>
<div class="nav-section">
    <div class="nav-section-title">Settings</div>
    <a href="/merchant/profile" class="nav-item"><span class="nav-icon">⚙️</span> Profile</a>
    <a href="/logout" class="nav-item"><span class="nav-icon">🚪</span> Logout</a>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <div>
        <h1 style="font-size: 28px; margin-bottom: 8px;">🔗 Payment Link Created</h1>
        <p style="color: #64748b;">Share this link with your customer</p>
    </div>
    <a href="/merchant/payment/request" style="padding: 10px 20px; background: #334155; border-radius: 8px; color: white; text-decoration: none; font-size: 14px;">
        ← Create Another
    </a>
</div>

<!-- Success Card -->
<div style="background: linear-gradient(135deg, #10b98120, #059669 20); border: 1px solid #10b981; border-radius: 20px; padding: 32px; margin-bottom: 32px; text-align: center;">
    <div style="width: 64px; height: 64px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px;">
        ✓
    </div>
    <h2 style="margin-bottom: 8px;">Payment Link Generated!</h2>
    <p style="color: #94a3b8;">Your customer can use this link to pay directly</p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
    <!-- Payment Link -->
    <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 24px;">
        <h3 style="font-size: 18px; margin-bottom: 20px;">🔗 Payment Link</h3>
        
        <div style="background: #0f172a; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
            <input type="text" id="paymentUrl" value="<?= esc($payment_url) ?>" readonly
                   style="width: 100%; background: transparent; border: none; color: #10b981; font-family: monospace; font-size: 14px;">
        </div>
        
        <div style="display: flex; gap: 12px;">
            <button onclick="copyLink()" style="flex: 1; padding: 12px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer;">
                📋 Copy Link
            </button>
            <a href="https://wa.me/?text=<?= urlencode('Pay Rp ' . number_format($amount, 0, ',', '.') . ' to ' . $merchant['business_name'] . ': ' . $payment_url) ?>" 
               target="_blank" style="flex: 1; padding: 12px; background: #25d366; border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer; text-align: center; text-decoration: none;">
                📱 WhatsApp
            </a>
        </div>
    </div>

    <!-- QR Code -->
    <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 24px; text-align: center;">
        <h3 style="font-size: 18px; margin-bottom: 20px;">📱 Scan to Pay</h3>
        
        <div style="background: white; padding: 16px; border-radius: 12px; display: inline-block; margin-bottom: 16px;">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($payment_url) ?>" 
                 alt="Payment QR" style="display: block;">
        </div>
        
        <p style="color: #64748b; font-size: 14px;">Customer can scan this QR code</p>
    </div>
</div>

<!-- Payment Details -->
<div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 24px; margin-top: 24px;">
    <h3 style="font-size: 18px; margin-bottom: 20px;">📋 Payment Details</h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px;">
        <div style="background: #0f172a; border-radius: 12px; padding: 20px; text-align: center;">
            <div style="color: #64748b; font-size: 14px; margin-bottom: 8px;">Amount</div>
            <div style="font-size: 24px; font-weight: 700; color: #10b981;">Rp <?= number_format($amount, 0, ',', '.') ?></div>
        </div>
        <div style="background: #0f172a; border-radius: 12px; padding: 20px; text-align: center;">
            <div style="color: #64748b; font-size: 14px; margin-bottom: 8px;">Merchant</div>
            <div style="font-size: 18px; font-weight: 600;"><?= esc($merchant['business_name']) ?></div>
        </div>
        <div style="background: #0f172a; border-radius: 12px; padding: 20px; text-align: center;">
            <div style="color: #64748b; font-size: 14px; margin-bottom: 8px;">Description</div>
            <div style="font-size: 16px; color: #94a3b8;"><?= esc($description ?: 'No description') ?></div>
        </div>
    </div>
</div>

<script>
function copyLink() {
    const input = document.getElementById('paymentUrl');
    input.select();
    document.execCommand('copy');
    alert('Payment link copied to clipboard!');
}
</script>
<?= $this->endSection() ?>
