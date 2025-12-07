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
    <a href="/merchant/qrcode" class="nav-item active"><span class="nav-icon">📱</span> QR Code</a>
    <a href="/merchant/payment/request" class="nav-item"><span class="nav-icon">🔗</span> Payment Link</a>
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
        <h1 style="font-size: 28px; margin-bottom: 8px;">📱 QR Code Payment</h1>
        <p style="color: #64748b;">Let customers scan to pay you</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
    <!-- QR Code Display -->
    <div style="background: #1e293b; border-radius: 20px; border: 1px solid #334155; padding: 40px; text-align: center;">
        <div style="background: white; padding: 32px; border-radius: 16px; display: inline-block; margin-bottom: 24px;">
            <!-- QR Code using API -->
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?= urlencode('udara://pay/' . ($merchant['merchant_id'] ?? '')) ?>" 
                 alt="QR Code" style="width: 250px; height: 250px;">
        </div>
        
        <h3 style="font-size: 20px; margin-bottom: 8px;"><?= esc($merchant['business_name'] ?? 'Your Store') ?></h3>
        <p style="color: #64748b; margin-bottom: 16px; font-family: monospace;"><?= esc($merchant['merchant_id'] ?? 'N/A') ?></p>
        
        <button onclick="printQR()" style="padding: 12px 32px; background: linear-gradient(135deg, #10b981, #059669); border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer; margin-right: 8px;">
            🖨️ Print QR
        </button>
        <button onclick="downloadQR()" style="padding: 12px 32px; background: #334155; border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer;">
            ⬇️ Download
        </button>
    </div>

    <!-- Instructions -->
    <div>
        <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 24px; margin-bottom: 24px;">
            <h3 style="font-size: 18px; margin-bottom: 20px;">📋 How It Works</h3>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="display: flex; align-items: flex-start; gap: 16px;">
                    <div style="width: 32px; height: 32px; background: #10b98120; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #10b981; font-weight: 700;">1</div>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 4px;">Customer scans QR</div>
                        <div style="color: #64748b; font-size: 14px;">Customer opens UDARA app and scans your QR code</div>
                    </div>
                </div>
                <div style="display: flex; align-items: flex-start; gap: 16px;">
                    <div style="width: 32px; height: 32px; background: #10b98120; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #10b981; font-weight: 700;">2</div>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 4px;">Enter amount</div>
                        <div style="color: #64748b; font-size: 14px;">Customer enters the payment amount</div>
                    </div>
                </div>
                <div style="display: flex; align-items: flex-start; gap: 16px;">
                    <div style="width: 32px; height: 32px; background: #10b98120; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #10b981; font-weight: 700;">3</div>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 4px;">Confirm payment</div>
                        <div style="color: #64748b; font-size: 14px;">Customer confirms and you receive the payment</div>
                    </div>
                </div>
            </div>
        </div>

        <div style="background: linear-gradient(135deg, #10b98120, #05966920); border: 1px solid #10b981; border-radius: 16px; padding: 24px;">
            <h4 style="margin-bottom: 12px; color: #10b981;">💡 Tips</h4>
            <ul style="color: #94a3b8; font-size: 14px; line-height: 1.8; margin-left: 16px;">
                <li>Print and display QR code at your cashier</li>
                <li>Use standee for table QR payments</li>
                <li>Funds are instantly added to your balance</li>
                <li>2.5% service fee applies per transaction</li>
            </ul>
        </div>
    </div>
</div>

<script>
function printQR() {
    window.print();
}

function downloadQR() {
    const link = document.createElement('a');
    link.href = 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=<?= urlencode('udara://pay/' . ($merchant['merchant_id'] ?? '')) ?>';
    link.download = 'qrcode-<?= $merchant['merchant_id'] ?? 'merchant' ?>.png';
    link.click();
}
</script>
<?= $this->endSection() ?>
