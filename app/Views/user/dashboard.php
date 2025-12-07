<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('sidebar') ?>
<div class="nav-section">
    <div class="nav-section-title">Main Menu</div>
    <a href="/user/dashboard" class="nav-item active">
        <span class="nav-icon">🏠</span>
        Dashboard
    </a>
    <a href="/user/topup" class="nav-item">
        <span class="nav-icon">💰</span>
        Top Up
    </a>
    <a href="/user/bills" class="nav-item">
        <span class="nav-icon">📄</span>
        My Bills
    </a>
    <a href="/user/payments" class="nav-item">
        <span class="nav-icon">💳</span>
        Payment History
    </a>
</div>

<div class="nav-section">
    <div class="nav-section-title">Account</div>
    <a href="/user/profile" class="nav-item">
        <span class="nav-icon">👤</span>
        Profile
    </a>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if (session()->getFlashdata('success')): ?>
    <div style="background: #065f46; color: #d1fae5; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #10b981;">
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div style="background: #991b1b; color: #fee2e2; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #ef4444;">
        <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>

<!-- Wallet Card -->
<div style="background: linear-gradient(135deg, #1e3a8a, #3730a3); border-radius: 20px; padding: 32px; margin-bottom: 32px; position: relative; overflow: hidden;">
    <div style="position: absolute; top: -50%; right: -20%; width: 300px; height: 300px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%); border-radius: 50%;"></div>
    <div style="position: relative; z-index: 1;">
        <div style="font-size: 14px; opacity: 0.8; margin-bottom: 8px;">💳 Saldo Wallet Anda</div>
        <div style="font-size: 42px; font-weight: 700; margin-bottom: 24px;">Rp <?= number_format($balance ?? 0, 0, ',', '.') ?></div>
        <div style="display: flex; gap: 16px; flex-wrap: wrap;">
            <a href="/user/topup" style="background: #10b981; border: none; color: white; padding: 12px 24px; border-radius: 12px; cursor: pointer; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: all 0.2s;">
                <span>💰</span> Top Up Saldo
            </a>
            <a href="/user/payments" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 12px 24px; border-radius: 12px; cursor: pointer; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                <span>📊</span> Riwayat Transaksi
            </a>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <div style="background: linear-gradient(135deg, #1e293b, #334155); border-radius: 16px; padding: 24px; border: 1px solid #475569;">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px;">
            <span style="font-size: 32px;">💰</span>
            <span style="background: #16a34a20; color: #4ade80; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">Active</span>
        </div>
        <div style="font-size: 28px; font-weight: 700; margin-bottom: 4px;">Rp <?= number_format($balance ?? 0, 0, ',', '.') ?></div>
        <div style="color: #94a3b8; font-size: 14px;">Total Balance</div>
    </div>

    <div style="background: linear-gradient(135deg, #1e293b, #334155); border-radius: 16px; padding: 24px; border: 1px solid #475569;">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px;">
            <span style="font-size: 32px;">📋</span>
            <span style="background: #f97316; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;"><?= $unpaidBills ?? 0 ?> Pending</span>
        </div>
        <div style="font-size: 28px; font-weight: 700; margin-bottom: 4px;">Rp <?= number_format($totalUnpaid ?? 0, 0, ',', '.') ?></div>
        <div style="color: #94a3b8; font-size: 14px;">Unpaid Bills</div>
    </div>

    <div style="background: linear-gradient(135deg, #1e293b, #334155); border-radius: 16px; padding: 24px; border: 1px solid #475569;">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px;">
            <span style="font-size: 32px;">✅</span>
        </div>
        <div style="font-size: 28px; font-weight: 700; margin-bottom: 4px;"><?= $paidBills ?? 0 ?></div>
        <div style="color: #94a3b8; font-size: 14px;">Paid This Month</div>
    </div>

    <div style="background: linear-gradient(135deg, #1e293b, #334155); border-radius: 16px; padding: 24px; border: 1px solid #475569;">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px;">
            <span style="font-size: 32px;">💰</span>
        </div>
        <div style="font-size: 28px; font-weight: 700; margin-bottom: 4px;"><?= $totalTopups ?? 0 ?></div>
        <div style="color: #94a3b8; font-size: 14px;">Total Top Up</div>
    </div>
</div>

<!-- Recent Bills -->
<div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; overflow: hidden;">
    <div style="padding: 20px 24px; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="font-size: 18px; font-weight: 600;">📋 Tagihan Belum Dibayar</h2>
        <a href="/user/bills" class="btn btn-outline" style="padding: 8px 16px; font-size: 14px;">View All</a>
    </div>
    
    <div style="padding: 0;">
        <?php if (!empty($bills) && count($bills) > 0): ?>
            <?php foreach (array_slice($bills, 0, 5) as $bill): ?>
                <?php
                $iconColors = [
                    'electricity' => ['icon' => '⚡', 'bg' => 'linear-gradient(135deg, #f43f5e, #e11d48)'],
                    'water' => ['icon' => '💧', 'bg' => 'linear-gradient(135deg, #3b82f6, #2563eb)'],
                    'internet' => ['icon' => '📶', 'bg' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)'],
                    'phone' => ['icon' => '📱', 'bg' => 'linear-gradient(135deg, #10b981, #059669)'],
                ];
                $type = $bill['bill_type'] ?? 'other';
                $icon = $iconColors[$type]['icon'] ?? '📄';
                $bg = $iconColors[$type]['bg'] ?? 'linear-gradient(135deg, #6b7280, #4b5563)';
                ?>
                <div style="display: flex; align-items: center; padding: 16px 24px; border-bottom: 1px solid #334155;">
                    <div style="width: 48px; height: 48px; background: <?= $bg ?>; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                        <?= $icon ?>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; margin-bottom: 4px;"><?= esc($bill['bill_number'] ?? 'N/A') ?></div>
                        <div style="color: #64748b; font-size: 14px;">Due: <?= date('d M Y', strtotime($bill['due_date'])) ?></div>
                    </div>
                    <div style="text-align: right; display: flex; align-items: center; gap: 12px;">
                        <div>
                            <div style="font-weight: 600; margin-bottom: 4px;">Rp <?= number_format($bill['amount'], 0, ',', '.') ?></div>
                            <span style="background: #f97316; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px;">Pending</span>
                        </div>
                        <button onclick="payBill(<?= $bill['id'] ?>)" style="background: linear-gradient(135deg, #3b82f6, #6366f1); border: none; color: white; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 12px;">Bayar</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 48px; color: #64748b;">
                <div style="font-size: 48px; margin-bottom: 16px;">🎉</div>
                <p>Tidak ada tagihan yang perlu dibayar</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions -->
<div style="margin-top: 32px;">
    <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 16px;">Quick Actions</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
        <a href="/user/bills" style="background: linear-gradient(135deg, #3b82f6, #6366f1); border-radius: 12px; padding: 20px; text-decoration: none; color: white; display: flex; align-items: center; gap: 16px; transition: transform 0.2s;">
            <span style="font-size: 28px;">💳</span>
            <div>
                <div style="font-weight: 600;">Pay Bills</div>
                <div style="font-size: 13px; opacity: 0.8;">Pay your pending bills</div>
            </div>
        </a>
        <a href="/user/topup" style="background: linear-gradient(135deg, #10b981, #059669); border-radius: 12px; padding: 20px; text-decoration: none; color: white; display: flex; align-items: center; gap: 16px; transition: transform 0.2s;">
            <span style="font-size: 28px;">💰</span>
            <div>
                <div style="font-weight: 600;">Top Up</div>
                <div style="font-size: 13px; opacity: 0.8;">Add balance via Midtrans</div>
            </div>
        </a>
        <a href="/user/payments" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); border-radius: 12px; padding: 20px; text-decoration: none; color: white; display: flex; align-items: center; gap: 16px; transition: transform 0.2s;">
            <span style="font-size: 28px;">📊</span>
            <div>
                <div style="font-weight: 600;">History</div>
                <div style="font-size: 13px; opacity: 0.8;">View transaction history</div>
            </div>
        </a>
        <a href="/merchant/setup" style="background: linear-gradient(135deg, #f97316, #ea580c); border-radius: 12px; padding: 20px; text-decoration: none; color: white; display: flex; align-items: center; gap: 16px; transition: transform 0.2s;">
            <span style="font-size: 28px;">🏪</span>
            <div>
                <div style="font-weight: 600;">Gabung Merchant</div>
                <div style="font-size: 13px; opacity: 0.8;">Buka toko & terima pembayaran</div>
            </div>
        </a>
    </div>
</div>

<script>
function payBill(billId) {
    if (confirm('Apakah Anda yakin ingin membayar tagihan ini dari saldo wallet?')) {
        window.location.href = '/user/pay-bill/' + billId;
    }
}
</script>
<?= $this->endSection() ?>
