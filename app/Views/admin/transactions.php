<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('sidebar') ?>
<?= $this->include('admin/partials/sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Filters -->
<div style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap;">
    <form method="GET" style="display: flex; gap: 8px; flex-wrap: wrap;">
        <select name="status" style="background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 10px 16px; color: #f1f5f9;">
            <option value="">All Status</option>
            <option value="success" <?= ($status_filter ?? '') === 'success' ? 'selected' : '' ?>>Success</option>
            <option value="pending" <?= ($status_filter ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="failed" <?= ($status_filter ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
        </select>
        <select name="type" style="background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 10px 16px; color: #f1f5f9;">
            <option value="">All Types</option>
            <option value="electricity" <?= ($type_filter ?? '') === 'electricity' ? 'selected' : '' ?>>Electricity</option>
            <option value="water" <?= ($type_filter ?? '') === 'water' ? 'selected' : '' ?>>Water</option>
            <option value="internet" <?= ($type_filter ?? '') === 'internet' ? 'selected' : '' ?>>Internet</option>
            <option value="phone" <?= ($type_filter ?? '') === 'phone' ? 'selected' : '' ?>>Phone</option>
            <option value="topup" <?= ($type_filter ?? '') === 'topup' ? 'selected' : '' ?>>Top Up</option>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="/admin/transactions" class="btn btn-outline">Reset</a>
    </form>
</div>

<!-- Transactions Table -->
<div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; overflow: hidden;">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #0f172a;">
                    <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: #94a3b8; font-size: 13px;">ID</th>
                    <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: #94a3b8; font-size: 13px;">User</th>
                    <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: #94a3b8; font-size: 13px;">Type</th>
                    <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: #94a3b8; font-size: 13px;">Description</th>
                    <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: #94a3b8; font-size: 13px;">Amount</th>
                    <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: #94a3b8; font-size: 13px;">Status</th>
                    <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: #94a3b8; font-size: 13px;">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($transactions)): ?>
                    <?php foreach ($transactions as $trx): ?>
                    <tr style="border-bottom: 1px solid #334155;">
                        <td style="padding: 16px 24px; font-family: monospace; color: #94a3b8;">#<?= $trx['id'] ?></td>
                        <td style="padding: 16px 24px;"><?= esc($trx['user_name'] ?? 'Unknown') ?></td>
                        <td style="padding: 16px 24px;">
                            <?php 
                            $typeIcons = [
                                'electricity' => '⚡',
                                'water' => '💧',
                                'internet' => '📶',
                                'phone' => '📱',
                                'topup' => '💰',
                                'transfer' => '↗️'
                            ];
                            ?>
                            <span style="display: flex; align-items: center; gap: 8px;">
                                <?= $typeIcons[$trx['type']] ?? '📋' ?>
                                <span style="text-transform: capitalize;"><?= esc($trx['type']) ?></span>
                            </span>
                        </td>
                        <td style="padding: 16px 24px; color: #94a3b8; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?= esc($trx['description'] ?? '-') ?>
                        </td>
                        <td style="padding: 16px 24px; font-weight: 600;">Rp <?= number_format($trx['amount'], 0, ',', '.') ?></td>
                        <td style="padding: 16px 24px;">
                            <?php 
                            $statusColors = ['success' => '#16a34a', 'pending' => '#f97316', 'failed' => '#ef4444'];
                            $color = $statusColors[$trx['status']] ?? '#64748b';
                            ?>
                            <span style="background: <?= $color ?>; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; text-transform: capitalize;"><?= $trx['status'] ?></span>
                        </td>
                        <td style="padding: 16px 24px; color: #94a3b8;"><?= date('d M Y H:i', strtotime($trx['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="padding: 40px; text-align: center; color: #64748b;">No transactions found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Summary -->
<div style="margin-top: 16px; color: #64748b; font-size: 14px;">
    Total: <?= count($transactions ?? []) ?> transactions
</div>
<?= $this->endSection() ?>
