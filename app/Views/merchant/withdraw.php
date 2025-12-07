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
    <a href="/merchant/payment/request" class="nav-item"><span class="nav-icon">🔗</span> Payment Link</a>
</div>
<div class="nav-section">
    <div class="nav-section-title">Finance</div>
    <a href="/merchant/withdraw" class="nav-item active"><span class="nav-icon">🏦</span> Withdraw</a>
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
        <h1 style="font-size: 28px; margin-bottom: 8px;">🏦 Withdraw Balance</h1>
        <p style="color: #64748b;">Transfer your earnings to bank account</p>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div style="background: #10b98120; border: 1px solid #10b981; padding: 16px; border-radius: 8px; margin-bottom: 24px; color: #6ee7b7;">
    <?= session()->getFlashdata('success') ?>
</div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
<div style="background: #ef444420; border: 1px solid #ef4444; padding: 16px; border-radius: 8px; margin-bottom: 24px; color: #fca5a5;">
    <?= session()->getFlashdata('error') ?>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
    <!-- Balance Card -->
    <div>
        <div style="background: linear-gradient(135deg, #10b981, #059669); border-radius: 16px; padding: 32px; margin-bottom: 24px;">
            <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">💰 Available Balance</div>
            <div style="font-size: 36px; font-weight: 700;">Rp <?= number_format($merchant['balance'] ?? 0, 0, ',', '.') ?></div>
        </div>

        <!-- Withdrawal Form -->
        <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 32px;">
            <h3 style="font-size: 18px; margin-bottom: 24px;">💳 Withdrawal Request</h3>
            
            <form method="POST" action="/merchant/withdraw">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Bank Name *</label>
                    <select name="bank_name" required
                            style="width: 100%; padding: 14px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: white; font-size: 16px;">
                        <option value="">Select Bank</option>
                        <option value="BCA">BCA</option>
                        <option value="BNI">BNI</option>
                        <option value="BRI">BRI</option>
                        <option value="Mandiri">Mandiri</option>
                        <option value="CIMB">CIMB Niaga</option>
                        <option value="Permata">Permata Bank</option>
                        <option value="BSI">Bank Syariah Indonesia</option>
                        <option value="Danamon">Bank Danamon</option>
                        <option value="OVO">OVO</option>
                        <option value="GoPay">GoPay</option>
                        <option value="DANA">DANA</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Account Number *</label>
                    <input type="text" name="account_number" required placeholder="Enter account number"
                           style="width: 100%; padding: 14px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: white; font-size: 16px;">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Account Holder Name *</label>
                    <input type="text" name="account_name" required placeholder="Name on the account"
                           style="width: 100%; padding: 14px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: white; font-size: 16px;">
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Withdrawal Amount (Rp) *</label>
                    <input type="number" name="amount" id="withdrawAmount" min="10000" max="<?= $merchant['balance'] ?? 0 ?>" required placeholder="Minimum Rp 10,000"
                           style="width: 100%; padding: 14px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: white; font-size: 16px;">
                    <div style="display: flex; gap: 8px; margin-top: 12px;">
                        <button type="button" onclick="setAmount(50000)" style="flex: 1; padding: 8px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #94a3b8; cursor: pointer; font-size: 12px;">50rb</button>
                        <button type="button" onclick="setAmount(100000)" style="flex: 1; padding: 8px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #94a3b8; cursor: pointer; font-size: 12px;">100rb</button>
                        <button type="button" onclick="setAmount(500000)" style="flex: 1; padding: 8px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #94a3b8; cursor: pointer; font-size: 12px;">500rb</button>
                        <button type="button" onclick="setAmount(<?= $merchant['balance'] ?? 0 ?>)" style="flex: 1; padding: 8px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #94a3b8; cursor: pointer; font-size: 12px;">All</button>
                    </div>
                </div>
                
                <button type="submit" style="width: 100%; padding: 16px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border: none; border-radius: 10px; color: white; font-weight: 600; font-size: 16px; cursor: pointer;">
                    🏦 Request Withdrawal
                </button>
            </form>
        </div>
    </div>

    <!-- Withdrawal History -->
    <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 24px;">
        <h3 style="font-size: 18px; margin-bottom: 24px;">📋 Recent Withdrawals</h3>
        
        <?php if (!empty($withdrawals)): ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($withdrawals as $withdrawal): ?>
            <div style="background: #0f172a; border-radius: 12px; padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <div>
                        <div style="font-weight: 600;">Rp <?= number_format($withdrawal['amount'], 0, ',', '.') ?></div>
                        <div style="color: #64748b; font-size: 12px; margin-top: 4px;"><?= $withdrawal['bank_name'] ?> - <?= $withdrawal['account_number'] ?></div>
                    </div>
                    <span style="padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
                        <?php
                        switch($withdrawal['status']) {
                            case 'completed': echo 'background: #10b98120; color: #10b981;'; break;
                            case 'pending': echo 'background: #f59e0b20; color: #f59e0b;'; break;
                            case 'rejected': echo 'background: #ef444420; color: #ef4444;'; break;
                            default: echo 'background: #64748b20; color: #64748b;';
                        }
                        ?>">
                        <?= ucfirst($withdrawal['status']) ?>
                    </span>
                </div>
                <div style="color: #64748b; font-size: 12px;"><?= date('d M Y H:i', strtotime($withdrawal['created_at'])) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 48px; color: #64748b;">
            <div style="font-size: 48px; margin-bottom: 16px;">🏦</div>
            <p>No withdrawal history yet</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function setAmount(amount) {
    document.getElementById('withdrawAmount').value = amount;
}
</script>
<?= $this->endSection() ?>
