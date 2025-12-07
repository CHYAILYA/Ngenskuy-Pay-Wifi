<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('sidebar') ?>
<?= $this->include('admin/partials/sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Flash Messages -->
<?php if (session()->getFlashdata('success')): ?>
    <div style="background: #16a34a20; border: 1px solid #16a34a; color: #4ade80; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>

<!-- Header -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div></div>
    <a href="/admin/bills/create" class="btn btn-primary">➕ Create Bill</a>
</div>

<!-- Bills Table -->
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
                    <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: #94a3b8; font-size: 13px;">Due Date</th>
                    <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: #94a3b8; font-size: 13px;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($bills)): ?>
                    <?php foreach ($bills as $bill): ?>
                    <?php 
                    $isOverdue = strtotime($bill['due_date']) < time() && $bill['status'] === 'pending';
                    ?>
                    <tr style="border-bottom: 1px solid #334155; <?= $isOverdue ? 'background: #ef444410;' : '' ?>">
                        <td style="padding: 16px 24px; font-family: monospace; color: #94a3b8;">#<?= $bill['id'] ?></td>
                        <td style="padding: 16px 24px;"><?= esc($bill['user_name'] ?? 'Unknown') ?></td>
                        <td style="padding: 16px 24px;">
                            <?php 
                            $typeIcons = [
                                'electricity' => '⚡',
                                'water' => '💧',
                                'internet' => '📶',
                                'phone' => '📱',
                                'other' => '📋'
                            ];
                            $billType = $bill['bill_type'] ?? $bill['type'] ?? 'other';
                            ?>
                            <span style="display: flex; align-items: center; gap: 8px;">
                                <?= $typeIcons[$billType] ?? '📋' ?>
                                <span style="text-transform: capitalize;"><?= esc($billType) ?></span>
                            </span>
                        </td>
                        <td style="padding: 16px 24px; color: #94a3b8;"><?= esc($bill['description'] ?? '-') ?></td>
                        <td style="padding: 16px 24px; font-weight: 600;">Rp <?= number_format($bill['amount'], 0, ',', '.') ?></td>
                        <td style="padding: 16px 24px; color: <?= $isOverdue ? '#ef4444' : '#94a3b8' ?>;">
                            <?= date('d M Y', strtotime($bill['due_date'])) ?>
                            <?php if ($isOverdue): ?>
                                <span style="color: #ef4444; font-size: 12px;">(Overdue)</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 16px 24px;">
                            <?php 
                            $statusColors = ['paid' => '#16a34a', 'pending' => '#f97316', 'overdue' => '#ef4444'];
                            $status = $isOverdue ? 'overdue' : $bill['status'];
                            $color = $statusColors[$status] ?? '#64748b';
                            ?>
                            <span style="background: <?= $color ?>; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; text-transform: capitalize;"><?= $status ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="padding: 40px; text-align: center; color: #64748b;">No bills found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Summary -->
<div style="margin-top: 16px; color: #64748b; font-size: 14px;">
    Total: <?= count($bills ?? []) ?> bills
</div>
<?= $this->endSection() ?>
