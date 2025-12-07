<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('sidebar') ?>
<?= $this->include('admin/partials/sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Revenue Summary Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <?php 
    $totalRevenue = 0;
    foreach ($monthly_revenue ?? [] as $m) {
        $totalRevenue += $m['total'];
    }
    ?>
    <div style="background: linear-gradient(135deg, #10b981, #059669); border-radius: 16px; padding: 24px;">
        <div style="font-size: 14px; opacity: 0.8; margin-bottom: 8px;">Total Revenue (12 months)</div>
        <div style="font-size: 32px; font-weight: 700;">Rp <?= number_format($totalRevenue, 0, ',', '.') ?></div>
    </div>
    
    <div style="background: linear-gradient(135deg, #3b82f6, #2563eb); border-radius: 16px; padding: 24px;">
        <div style="font-size: 14px; opacity: 0.8; margin-bottom: 8px;">This Month</div>
        <div style="font-size: 32px; font-weight: 700;">Rp <?= number_format(($monthly_revenue[0]['total'] ?? 0), 0, ',', '.') ?></div>
    </div>
    
    <div style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); border-radius: 16px; padding: 24px;">
        <div style="font-size: 14px; opacity: 0.8; margin-bottom: 8px;">Transaction Types</div>
        <div style="font-size: 32px; font-weight: 700;"><?= count($by_type ?? []) ?></div>
    </div>
</div>

<!-- Charts Row -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <!-- Monthly Revenue Chart -->
    <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 24px;">
        <h3 style="margin-bottom: 24px; font-size: 18px;">Monthly Revenue</h3>
        <div style="display: flex; align-items: flex-end; gap: 8px; height: 200px;">
            <?php 
            $revenueData = $monthly_revenue ?? [];
            $revenueValues = !empty($revenueData) ? array_column($revenueData, 'total') : [0];
            $maxRevenue = !empty($revenueValues) ? max($revenueValues) : 1;
            $maxRevenue = $maxRevenue > 0 ? $maxRevenue : 1; // Prevent division by zero
            $reversed = array_reverse($revenueData);
            
            if (!empty($reversed)):
                foreach ($reversed as $index => $month): 
                    $height = ($month['total'] / $maxRevenue * 100);
                    $monthName = date('M', strtotime($month['month'] . '-01'));
            ?>
            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                <div style="width: 100%; background: linear-gradient(180deg, #3b82f6, #6366f1); border-radius: 4px; height: <?= max($height, 5) ?>%; min-height: 4px;"></div>
                <span style="font-size: 10px; color: #64748b;"><?= $monthName ?></span>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div style="flex: 1; display: flex; align-items: center; justify-content: center; color: #64748b;">No data available</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Transaction by Type -->
    <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 24px;">
        <h3 style="margin-bottom: 24px; font-size: 18px;">Revenue by Type</h3>
        <?php 
        $typeColors = [
            'electricity' => '#f43f5e',
            'water' => '#3b82f6',
            'internet' => '#8b5cf6',
            'phone' => '#10b981',
            'topup' => '#f97316',
            'transfer' => '#6366f1'
        ];
        $totalByType = array_sum(array_column($by_type ?? [], 'total'));
        foreach ($by_type ?? [] as $type): 
            $percentage = $totalByType > 0 ? ($type['total'] / $totalByType * 100) : 0;
            $color = $typeColors[$type['type']] ?? '#64748b';
        ?>
        <div style="margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="color: #94a3b8; text-transform: capitalize;"><?= esc($type['type']) ?></span>
                <span style="font-weight: 600;">Rp <?= number_format($type['total'], 0, ',', '.') ?></span>
            </div>
            <div style="height: 8px; background: #334155; border-radius: 4px; overflow: hidden;">
                <div style="width: <?= $percentage ?>%; height: 100%; background: <?= $color ?>; border-radius: 4px;"></div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($by_type)): ?>
        <div style="text-align: center; color: #64748b; padding: 20px;">No transaction data</div>
        <?php endif; ?>
    </div>
</div>

<!-- Monthly Revenue Table -->
<div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; overflow: hidden;">
    <div style="padding: 20px 24px; border-bottom: 1px solid #334155;">
        <h3 style="font-size: 18px;">Monthly Revenue Details</h3>
    </div>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #0f172a;">
                    <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: #94a3b8;">Month</th>
                    <th style="padding: 16px 24px; text-align: right; font-weight: 600; color: #94a3b8;">Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monthly_revenue ?? [] as $month): ?>
                <tr style="border-bottom: 1px solid #334155;">
                    <td style="padding: 16px 24px;"><?= date('F Y', strtotime($month['month'] . '-01')) ?></td>
                    <td style="padding: 16px 24px; text-align: right; font-weight: 600;">Rp <?= number_format($month['total'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($monthly_revenue)): ?>
                <tr>
                    <td colspan="2" style="padding: 40px; text-align: center; color: #64748b;">No revenue data</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>

<style>
@media (max-width: 768px) {
    .reports-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>
