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
    <a href="/user/bills" class="nav-item">
        <span class="nav-icon">📄</span>
        My Bills
    </a>
    <a href="/user/payments" class="nav-item active">
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
    <div style="position: relative; z-index: 1;">
        <div style="font-size: 14px; opacity: 0.8; margin-bottom: 8px;">💳 Saldo Saat Ini</div>
        <div style="font-size: 42px; font-weight: 700;">Rp <?= number_format($balance ?? 0, 0, ',', '.') ?></div>
    </div>
</div>

<!-- Filter Tabs -->
<div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 24px;">
    <a href="/user/payments" style="background: <?= ($filter ?? 'all') === 'all' ? 'linear-gradient(135deg, #3b82f6, #6366f1)' : '#1e293b' ?>; border: 1px solid <?= ($filter ?? 'all') === 'all' ? 'transparent' : '#334155' ?>; color: white; padding: 10px 20px; border-radius: 12px; text-decoration: none; font-weight: 500; font-size: 14px;">
        Semua
    </a>
    <a href="/user/payments?filter=topup" style="background: <?= ($filter ?? '') === 'topup' ? 'linear-gradient(135deg, #10b981, #059669)' : '#1e293b' ?>; border: 1px solid <?= ($filter ?? '') === 'topup' ? 'transparent' : '#334155' ?>; color: white; padding: 10px 20px; border-radius: 12px; text-decoration: none; font-weight: 500; font-size: 14px;">
        💰 Top Up
    </a>
    <a href="/user/payments?filter=payment" style="background: <?= ($filter ?? '') === 'payment' ? 'linear-gradient(135deg, #f97316, #ea580c)' : '#1e293b' ?>; border: 1px solid <?= ($filter ?? '') === 'payment' ? 'transparent' : '#334155' ?>; color: white; padding: 10px 20px; border-radius: 12px; text-decoration: none; font-weight: 500; font-size: 14px;">
        💸 Pembayaran
    </a>
</div>

<!-- Transaction History -->
<div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; overflow: hidden;">
    <div style="padding: 20px 24px; border-bottom: 1px solid #334155;">
        <h2 style="font-size: 18px; font-weight: 600;">📊 Riwayat Transaksi</h2>
    </div>
    
    <div style="padding: 0;">
        <?php if (!empty($transactions) && count($transactions) > 0): ?>
            <?php foreach ($transactions as $tx): ?>
                <div style="display: flex; align-items: center; padding: 20px 24px; border-bottom: 1px solid #334155;">
                    <div style="width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 16px;
                        background: <?= $tx['type'] === 'credit' ? 'rgba(16, 185, 129, 0.2)' : 'rgba(239, 68, 68, 0.2)' ?>;">
                        <?= $tx['type'] === 'credit' ? '↗️' : '↘️' ?>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; margin-bottom: 4px;"><?= esc($tx['description'] ?? 'Transaksi') ?></div>
                        <div style="color: #64748b; font-size: 14px;"><?= date('d M Y, H:i', strtotime($tx['created_at'])) ?></div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 700; font-size: 18px; color: <?= $tx['type'] === 'credit' ? '#10b981' : '#ef4444' ?>;">
                            <?= $tx['type'] === 'credit' ? '+' : '-' ?>Rp <?= number_format($tx['amount'], 0, ',', '.') ?>
                        </div>
                        <?php if (isset($tx['status'])): ?>
                            <span style="padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;
                                <?php
                                $statusColors = [
                                    'success' => 'background: #16a34a; color: white;',
                                    'completed' => 'background: #16a34a; color: white;',
                                    'pending' => 'background: #f97316; color: white;',
                                    'failed' => 'background: #dc2626; color: white;',
                                ];
                                echo $statusColors[$tx['status']] ?? 'background: #475569; color: white;';
                                ?>">
                                <?= ucfirst($tx['status']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 64px; color: #64748b;">
                <div style="font-size: 64px; margin-bottom: 16px;">📭</div>
                <p style="font-size: 18px; margin-bottom: 8px;">Belum ada transaksi</p>
                <p style="font-size: 14px;">Mulai dengan top up saldo Anda</p>
                <a href="/user/topup" style="display: inline-block; margin-top: 16px; background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 12px 24px; border-radius: 12px; text-decoration: none; font-weight: 600;">
                    💰 Top Up Sekarang
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Summary Stats -->
<?php if (!empty($transactions)): ?>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 24px;">
    <div style="background: #1e293b; border-radius: 12px; padding: 20px; border: 1px solid #334155;">
        <div style="font-size: 24px; margin-bottom: 8px;">💰</div>
        <div style="font-size: 24px; font-weight: 700; color: #10b981; margin-bottom: 4px;">
            Rp <?= number_format($totalCredit ?? 0, 0, ',', '.') ?>
        </div>
        <div style="color: #64748b; font-size: 14px;">Total Top Up</div>
    </div>
    <div style="background: #1e293b; border-radius: 12px; padding: 20px; border: 1px solid #334155;">
        <div style="font-size: 24px; margin-bottom: 8px;">💸</div>
        <div style="font-size: 24px; font-weight: 700; color: #ef4444; margin-bottom: 4px;">
            Rp <?= number_format($totalDebit ?? 0, 0, ',', '.') ?>
        </div>
        <div style="color: #64748b; font-size: 14px;">Total Pengeluaran</div>
    </div>
    <div style="background: #1e293b; border-radius: 12px; padding: 20px; border: 1px solid #334155;">
        <div style="font-size: 24px; margin-bottom: 8px;">📊</div>
        <div style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">
            <?= count($transactions) ?>
        </div>
        <div style="color: #64748b; font-size: 14px;">Total Transaksi</div>
    </div>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
