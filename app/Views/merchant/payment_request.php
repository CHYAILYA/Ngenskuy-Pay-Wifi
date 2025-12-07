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
        <h1 style="font-size: 28px; margin-bottom: 8px;">🔗 Create Payment Link</h1>
        <p style="color: #64748b;">Generate shareable payment link with fixed amount</p>
    </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
<div style="background: #ef444420; border: 1px solid #ef4444; padding: 16px; border-radius: 8px; margin-bottom: 24px; color: #fca5a5;">
    <?= session()->getFlashdata('error') ?>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
    <!-- Create Form -->
    <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 32px;">
        <h3 style="font-size: 18px; margin-bottom: 24px;">💳 Payment Details</h3>
        
        <form method="POST" action="/merchant/payment/create">
            <div style="margin-bottom: 20px;">
                <label style="display: block; color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Amount (Rp) *</label>
                <input type="number" name="amount" min="1000" required placeholder="e.g. 100000"
                       style="width: 100%; padding: 14px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: white; font-size: 16px;">
            </div>
            
            <div style="margin-bottom: 24px;">
                <label style="display: block; color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Description (Optional)</label>
                <input type="text" name="description" placeholder="e.g. Invoice #123, Product name"
                       style="width: 100%; padding: 14px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: white; font-size: 16px;">
            </div>
            
            <button type="submit" style="width: 100%; padding: 16px; background: linear-gradient(135deg, #10b981, #059669); border: none; border-radius: 10px; color: white; font-weight: 600; font-size: 16px; cursor: pointer;">
                🔗 Generate Payment Link
            </button>
        </form>
    </div>

    <!-- Instructions -->
    <div>
        <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 24px; margin-bottom: 24px;">
            <h3 style="font-size: 18px; margin-bottom: 20px;">📋 How It Works</h3>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="display: flex; align-items: flex-start; gap: 16px;">
                    <div style="width: 32px; height: 32px; background: #3b82f620; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #3b82f6; font-weight: 700;">1</div>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 4px;">Create payment link</div>
                        <div style="color: #64748b; font-size: 14px;">Enter amount and optional description</div>
                    </div>
                </div>
                <div style="display: flex; align-items: flex-start; gap: 16px;">
                    <div style="width: 32px; height: 32px; background: #3b82f620; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #3b82f6; font-weight: 700;">2</div>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 4px;">Share the link</div>
                        <div style="color: #64748b; font-size: 14px;">Send via WhatsApp, email, or any messenger</div>
                    </div>
                </div>
                <div style="display: flex; align-items: flex-start; gap: 16px;">
                    <div style="width: 32px; height: 32px; background: #3b82f620; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #3b82f6; font-weight: 700;">3</div>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 4px;">Customer pays</div>
                        <div style="color: #64748b; font-size: 14px;">Customer clicks link and pays the exact amount</div>
                    </div>
                </div>
            </div>
        </div>

        <div style="background: linear-gradient(135deg, #3b82f620, #6366f120); border: 1px solid #3b82f6; border-radius: 16px; padding: 24px;">
            <h4 style="margin-bottom: 12px; color: #3b82f6;">💡 Use Cases</h4>
            <ul style="color: #94a3b8; font-size: 14px; line-height: 1.8; margin-left: 16px;">
                <li>Invoice payments for services</li>
                <li>Online orders & reservations</li>
                <li>Split bills with customers</li>
                <li>Collect deposits</li>
            </ul>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
