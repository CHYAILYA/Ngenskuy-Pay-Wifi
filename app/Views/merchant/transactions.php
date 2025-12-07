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
    <a href="/merchant/transactions" class="nav-item active"><span class="nav-icon">💳</span> Transactions</a>
    <a href="/merchant/qrcode" class="nav-item"><span class="nav-icon">📱</span> QR Code</a>
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
        <h1 style="font-size: 28px; margin-bottom: 8px;">💳 Transactions</h1>
        <p style="color: #64748b;">View all payment transactions</p>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom: 32px;">
    <div style="background: #1e293b; border-radius: 12px; padding: 20px; border: 1px solid #334155;">
        <div style="color: #64748b; font-size: 14px; margin-bottom: 8px;">Total Revenue</div>
        <div style="font-size: 24px; font-weight: 700; color: #10b981;">Rp <?= number_format($stats['total_revenue'] ?? 0, 0, ',', '.') ?></div>
    </div>
    <div style="background: #1e293b; border-radius: 12px; padding: 20px; border: 1px solid #334155;">
        <div style="color: #64748b; font-size: 14px; margin-bottom: 8px;">This Month</div>
        <div style="font-size: 24px; font-weight: 700; color: #3b82f6;">Rp <?= number_format($stats['month_revenue'] ?? 0, 0, ',', '.') ?></div>
    </div>
    <div style="background: #1e293b; border-radius: 12px; padding: 20px; border: 1px solid #334155;">
        <div style="color: #64748b; font-size: 14px; margin-bottom: 8px;">Today</div>
        <div style="font-size: 24px; font-weight: 700; color: #f97316;">Rp <?= number_format($stats['today_revenue'] ?? 0, 0, ',', '.') ?></div>
    </div>
    <div style="background: #1e293b; border-radius: 12px; padding: 20px; border: 1px solid #334155;">
        <div style="color: #64748b; font-size: 14px; margin-bottom: 8px;">Total Transactions</div>
        <div style="font-size: 24px; font-weight: 700;"><?= number_format($stats['total_transactions'] ?? 0) ?></div>
    </div>
</div>

<!-- Filters -->
<div style="background: #1e293b; border-radius: 12px; padding: 20px; border: 1px solid #334155; margin-bottom: 24px;">
    <form method="GET" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 150px;">
            <label style="display: block; color: #64748b; font-size: 12px; margin-bottom: 6px;">Status</label>
            <select name="status" style="width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: white;">
                <option value="">All Status</option>
                <option value="success" <?= ($filters['status'] ?? '') === 'success' ? 'selected' : '' ?>>Success</option>
                <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="failed" <?= ($filters['status'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
            </select>
        </div>
        <div style="flex: 1; min-width: 150px;">
            <label style="display: block; color: #64748b; font-size: 12px; margin-bottom: 6px;">From Date</label>
            <input type="date" name="from" value="<?= esc($filters['from'] ?? '') ?>" style="width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: white;">
        </div>
        <div style="flex: 1; min-width: 150px;">
            <label style="display: block; color: #64748b; font-size: 12px; margin-bottom: 6px;">To Date</label>
            <input type="date" name="to" value="<?= esc($filters['to'] ?? '') ?>" style="width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: white;">
        </div>
        <button type="submit" style="padding: 10px 24px; background: #10b981; border: none; border-radius: 8px; color: white; cursor: pointer; font-weight: 600;">Filter</button>
    </form>
</div>

<!-- Transactions Table -->
<div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; overflow: hidden;">
    <div class="responsive-table">
        <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
            <thead>
                <tr style="background: #0f172a;">
                    <th style="text-align: left; padding: 14px 16px; color: #64748b; font-weight: 500; font-size: 13px;">Transaction ID</th>
                    <th style="text-align: left; padding: 14px 16px; color: #64748b; font-weight: 500; font-size: 13px;">Customer</th>
                    <th style="text-align: left; padding: 14px 16px; color: #64748b; font-weight: 500; font-size: 13px;">Description</th>
                    <th style="text-align: left; padding: 14px 16px; color: #64748b; font-weight: 500; font-size: 13px;">Amount</th>
                    <th style="text-align: left; padding: 14px 16px; color: #64748b; font-weight: 500; font-size: 13px;">Fee</th>
                    <th style="text-align: left; padding: 14px 16px; color: #64748b; font-weight: 500; font-size: 13px;">Net</th>
                    <th style="text-align: left; padding: 14px 16px; color: #64748b; font-weight: 500; font-size: 13px;">Status</th>
                    <th style="text-align: left; padding: 14px 16px; color: #64748b; font-weight: 500; font-size: 13px;">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($transactions)): ?>
                    <?php foreach ($transactions as $tx): ?>
                    <tr style="border-bottom: 1px solid #334155;">
                        <td style="padding: 14px 16px; font-family: monospace; color: #94a3b8; font-size: 11px;"><?= esc($tx['transaction_id']) ?></td>
                        <td style="padding: 14px 16px; font-size: 14px;"><?= esc($tx['customer_name'] ?? 'Guest') ?></td>
                        <td style="padding: 14px 16px; color: #94a3b8; font-size: 13px;"><?= esc($tx['description'] ?? '-') ?></td>
                        <td style="padding: 14px 16px; font-size: 14px;">Rp <?= number_format($tx['amount'], 0, ',', '.') ?></td>
                        <td style="padding: 14px 16px; color: #ef4444; font-size: 14px;">-Rp <?= number_format($tx['fee'], 0, ',', '.') ?></td>
                        <td style="padding: 14px 16px; color: #10b981; font-weight: 600; font-size: 14px;">Rp <?= number_format($tx['net_amount'], 0, ',', '.') ?></td>
                        <td style="padding: 14px 16px;">
                            <?php
                            $statusColors = ['success' => '#10b981', 'pending' => '#f97316', 'failed' => '#ef4444'];
                            $color = $statusColors[$tx['status']] ?? '#64748b';
                            ?>
                            <span style="background: <?= $color ?>20; color: <?= $color ?>; padding: 4px 10px; border-radius: 12px; font-size: 11px;"><?= ucfirst($tx['status']) ?></span>
                        </td>
                        <td style="padding: 14px 16px; color: #64748b; font-size: 13px;"><?= date('d M H:i', strtotime($tx['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #64748b; padding: 40px;">
                            No transactions found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
