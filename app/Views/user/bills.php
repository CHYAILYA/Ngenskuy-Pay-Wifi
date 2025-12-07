<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('sidebar') ?>
<div class="nav-section">
    <div class="nav-section-title">Main Menu</div>
    <a href="/user/dashboard" class="nav-item">
        <span class="nav-icon">🏠</span>
        Dashboard
    </a>
    <a href="/user/topup" class="nav-item">
        <span class="nav-icon">💰</span>
        Top Up
    </a>
    <a href="/user/bills" class="nav-item active">
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

<!-- Current Balance -->
<div style="background: linear-gradient(135deg, #1e3a8a, #3730a3); border-radius: 20px; padding: 32px; margin-bottom: 32px; position: relative; overflow: hidden;">
    <div style="position: absolute; top: -50%; right: -20%; width: 300px; height: 300px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%); border-radius: 50%;"></div>
    <div style="position: relative; z-index: 1; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px;">
        <div>
            <div style="font-size: 14px; opacity: 0.8; margin-bottom: 8px;">💳 Saldo Saat Ini</div>
            <div class="balance-amount">Rp <?= number_format($balance ?? 0, 0, ',', '.') ?></div>
        </div>
        <a href="/user/topup" style="background: #10b981; border: none; color: white; padding: 12px 24px; border-radius: 12px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px;">
            <span>💰</span> Top Up
        </a>
    </div>
</div>

<style>
.balance-amount { font-size: 42px; font-weight: 700; }
.bill-item { display: flex; align-items: center; padding: 20px 24px; border-bottom: 1px solid #334155; gap: 16px; }
.bill-icon { width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
.bill-info { flex: 1; min-width: 0; }
.bill-amount { text-align: right; flex-shrink: 0; }
@media (max-width: 600px) {
    .balance-amount { font-size: 28px; }
    .bill-item { flex-wrap: wrap; padding: 16px; }
    .bill-icon { width: 44px; height: 44px; font-size: 20px; }
    .bill-info { width: calc(100% - 60px); }
    .bill-amount { width: 100%; text-align: left; margin-top: 12px; padding-top: 12px; border-top: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }
}
</style>

<!-- Bills List -->
<div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; overflow: hidden;">
    <div style="padding: 20px 24px; border-bottom: 1px solid #334155;">
        <h2 style="font-size: 18px; font-weight: 600;">📋 Daftar Tagihan</h2>
    </div>
    
    <div style="padding: 0;">
        <?php if (!empty($bills) && count($bills) > 0): ?>
            <?php foreach ($bills as $bill): ?>
                <?php
                $iconColors = [
                    'electricity' => ['icon' => '⚡', 'bg' => 'linear-gradient(135deg, #f43f5e, #e11d48)', 'name' => 'Listrik'],
                    'water' => ['icon' => '💧', 'bg' => 'linear-gradient(135deg, #3b82f6, #2563eb)', 'name' => 'Air'],
                    'internet' => ['icon' => '📶', 'bg' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)', 'name' => 'Internet'],
                    'phone' => ['icon' => '📱', 'bg' => 'linear-gradient(135deg, #10b981, #059669)', 'name' => 'Telepon'],
                ];
                $type = $bill['bill_type'] ?? 'other';
                $icon = $iconColors[$type]['icon'] ?? '📄';
                $bg = $iconColors[$type]['bg'] ?? 'linear-gradient(135deg, #6b7280, #4b5563)';
                $typeName = $iconColors[$type]['name'] ?? 'Lainnya';
                
                $isPaid = $bill['status'] === 'paid';
                $isOverdue = !$isPaid && strtotime($bill['due_date']) < time();
                ?>
                <div class="bill-item" style="<?= $isPaid ? 'opacity: 0.6;' : '' ?>">
                    <div class="bill-icon" style="background: <?= $bg ?>;">
                        <?= $icon ?>
                    </div>
                    <div class="bill-info">
                        <div style="font-weight: 600; font-size: 16px; margin-bottom: 4px;"><?= esc($typeName) ?> - <?= esc($bill['bill_number'] ?? 'N/A') ?></div>
                        <div style="color: #64748b; font-size: 14px; margin-bottom: 4px;"><?= esc($bill['description'] ?? '') ?></div>
                        <div style="color: <?= $isOverdue ? '#ef4444' : '#64748b' ?>; font-size: 13px;">
                            <?php if ($isPaid): ?>
                                ✅ Dibayar: <?= date('d M Y', strtotime($bill['paid_at'])) ?>
                            <?php else: ?>
                                📅 Jatuh tempo: <?= date('d M Y', strtotime($bill['due_date'])) ?>
                                <?= $isOverdue ? ' (Terlambat!)' : '' ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="bill-amount">
                        <div style="font-weight: 700; font-size: 18px; margin-bottom: 8px; color: <?= $isPaid ? '#10b981' : ($isOverdue ? '#ef4444' : 'white') ?>;">
                            Rp <?= number_format($bill['amount'], 0, ',', '.') ?>
                        </div>
                        <?php if ($isPaid): ?>
                            <span style="background: #16a34a; color: white; padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 600;">✓ Paid</span>
                        <?php elseif ($isOverdue): ?>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <span style="background: #dc2626; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">Overdue</span>
                                <button onclick="payBill(<?= $bill['id'] ?>)" style="background: linear-gradient(135deg, #3b82f6, #6366f1); border: none; color: white; padding: 6px 16px; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 12px;">Bayar</button>
                            </div>
                        <?php else: ?>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <span style="background: #f97316; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">Pending</span>
                                <button onclick="payBill(<?= $bill['id'] ?>)" style="background: linear-gradient(135deg, #3b82f6, #6366f1); border: none; color: white; padding: 6px 16px; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 12px;">Bayar</button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 64px; color: #64748b;">
                <div style="font-size: 64px; margin-bottom: 16px;">🎉</div>
                <p style="font-size: 18px; margin-bottom: 8px;">Tidak ada tagihan</p>
                <p style="font-size: 14px;">Semua tagihan Anda sudah terbayar!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function payBill(billId) {
    if (confirm('Apakah Anda yakin ingin membayar tagihan ini dari saldo wallet?\n\nSaldo saat ini: Rp <?= number_format($balance ?? 0, 0, ',', '.') ?>')) {
        window.location.href = '/user/pay-bill/' + billId;
    }
}
</script>
<?= $this->endSection() ?>
