<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('sidebar') ?>
<?= $this->include('admin/partials/sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<style>
.stat-card { background: #1e293b; border-radius: 16px; padding: 20px; border: 1px solid #334155; }
.stat-card-gradient { border-radius: 16px; padding: 24px; color: white; }
.stat-icon { font-size: 28px; margin-bottom: 12px; }
.stat-value { font-size: 28px; font-weight: 700; margin-bottom: 4px; }
.stat-label { opacity: 0.8; font-size: 13px; }
.stat-change { font-size: 12px; margin-top: 8px; }
.stat-change.positive { color: #4ade80; }
.stat-change.negative { color: #f87171; }
.section-title { font-size: 18px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { padding: 12px 16px; text-align: left; font-weight: 600; color: #94a3b8; font-size: 12px; text-transform: uppercase; background: #0f172a; }
.data-table td { padding: 14px 16px; border-bottom: 1px solid #334155; }
.badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.badge-success { background: #16a34a; color: white; }
.badge-warning { background: #f97316; color: white; }
.badge-danger { background: #ef4444; color: white; }
.badge-info { background: #3b82f6; color: white; }
.badge-purple { background: #8b5cf6; color: white; }
.progress-bar { height: 8px; background: #334155; border-radius: 4px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 4px; transition: width 0.3s; }
.mini-chart { display: flex; align-items: flex-end; gap: 4px; height: 60px; }
.mini-bar { flex: 1; background: linear-gradient(180deg, #3b82f6, #6366f1); border-radius: 2px; min-height: 4px; }
.quick-link { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 16px; text-decoration: none; color: #f1f5f9; display: flex; align-items: center; gap: 12px; transition: all 0.2s; }
.quick-link:hover { border-color: #3b82f6; transform: translateY(-2px); }
@media (max-width: 768px) {
    .stat-value { font-size: 22px; }
    .stat-card { padding: 16px; }
}
</style>

<!-- Welcome Banner -->
<div style="background: linear-gradient(135deg, #1e40af, #7c3aed); border-radius: 20px; padding: 32px; margin-bottom: 32px; position: relative; overflow: hidden;">
    <div style="position: absolute; top: -50%; right: -10%; width: 300px; height: 300px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%); border-radius: 50%;"></div>
    <div style="position: relative; z-index: 1;">
        <h1 style="font-size: 28px; margin-bottom: 8px;">👋 Selamat Datang, <?= esc($user['name'] ?? 'Admin') ?>!</h1>
        <p style="opacity: 0.9; margin-bottom: 16px;">Dashboard Admin - <?= date('l, d F Y') ?></p>
        <div style="display: flex; gap: 16px; flex-wrap: wrap;">
            <div style="background: rgba(255,255,255,0.2); padding: 12px 20px; border-radius: 12px;">
                <div style="font-size: 12px; opacity: 0.8;">Total Pengguna</div>
                <div style="font-size: 24px; font-weight: 700;"><?= number_format(($stats['total_users'] ?? 0) + ($stats['total_merchants'] ?? 0) + ($stats['total_admins'] ?? 0)) ?></div>
            </div>
            <div style="background: rgba(255,255,255,0.2); padding: 12px 20px; border-radius: 12px;">
                <div style="font-size: 12px; opacity: 0.8;">Pendapatan Hari Ini</div>
                <div style="font-size: 24px; font-weight: 700;">Rp <?= number_format($stats['today_revenue'] ?? 0, 0, ',', '.') ?></div>
            </div>
            <div style="background: rgba(255,255,255,0.2); padding: 12px 20px; border-radius: 12px;">
                <div style="font-size: 12px; opacity: 0.8;">User Baru Hari Ini</div>
                <div style="font-size: 24px; font-weight: 700;"><?= number_format($stats['new_users_today'] ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Main Stats Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 32px;">
    <!-- Users -->
    <div class="stat-card-gradient" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
        <div class="stat-icon">👥</div>
        <div class="stat-value"><?= number_format($stats['total_users'] ?? 0) ?></div>
        <div class="stat-label">Total Users</div>
        <div class="stat-change positive">+<?= $stats['new_users_week'] ?? 0 ?> minggu ini</div>
    </div>
    
    <!-- Merchants -->
    <div class="stat-card-gradient" style="background: linear-gradient(135deg, #10b981, #059669);">
        <div class="stat-icon">🏪</div>
        <div class="stat-value"><?= number_format($stats['total_merchants'] ?? 0) ?></div>
        <div class="stat-label">Merchants</div>
        <div class="stat-change"><?= $stats['active_merchants'] ?? 0 ?> aktif, <?= $stats['pending_merchants'] ?? 0 ?> pending</div>
    </div>
    
    <!-- Transactions -->
    <div class="stat-card-gradient" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
        <div class="stat-icon">💳</div>
        <div class="stat-value"><?= number_format($stats['total_transactions'] ?? 0) ?></div>
        <div class="stat-label">Transaksi</div>
        <div class="stat-change"><?= $stats['success_transactions'] ?? 0 ?> sukses</div>
    </div>
    
    <!-- Revenue -->
    <div class="stat-card-gradient" style="background: linear-gradient(135deg, #f97316, #ea580c);">
        <div class="stat-icon">💰</div>
        <div class="stat-value">Rp <?= number_format($stats['total_revenue'] ?? 0, 0, ',', '.') ?></div>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-change positive">Rp <?= number_format($stats['month_revenue'] ?? 0, 0, ',', '.') ?> bulan ini</div>
    </div>
</div>

<!-- Secondary Stats -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 32px;">
    <!-- Bills Stats -->
    <div class="stat-card">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
            <span style="font-size: 24px;">📋</span>
            <span class="badge badge-info"><?= $stats['pending_bills'] ?? 0 ?> pending</span>
        </div>
        <div style="font-size: 24px; font-weight: 700; margin-bottom: 4px;"><?= number_format($stats['total_bills'] ?? 0) ?></div>
        <div style="color: #94a3b8; font-size: 13px;">Total Tagihan</div>
        <div style="margin-top: 12px;">
            <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                <span style="color: #4ade80;">Terbayar</span>
                <span><?= $stats['paid_bills'] ?? 0 ?></span>
            </div>
            <div class="progress-bar">
                <?php $billPercent = ($stats['total_bills'] ?? 0) > 0 ? (($stats['paid_bills'] ?? 0) / $stats['total_bills'] * 100) : 0; ?>
                <div class="progress-fill" style="width: <?= $billPercent ?>%; background: #10b981;"></div>
            </div>
        </div>
    </div>
    
    <!-- Overdue Bills -->
    <div class="stat-card" style="border-color: <?= ($stats['overdue_bills'] ?? 0) > 0 ? '#ef4444' : '#334155' ?>;">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
            <span style="font-size: 24px;">⚠️</span>
            <?php if (($stats['overdue_bills'] ?? 0) > 0): ?>
            <span class="badge badge-danger">Perhatian!</span>
            <?php endif; ?>
        </div>
        <div style="font-size: 24px; font-weight: 700; margin-bottom: 4px; color: <?= ($stats['overdue_bills'] ?? 0) > 0 ? '#ef4444' : 'inherit' ?>;"><?= number_format($stats['overdue_bills'] ?? 0) ?></div>
        <div style="color: #94a3b8; font-size: 13px;">Tagihan Jatuh Tempo</div>
    </div>
    
    <!-- TopUp Stats -->
    <div class="stat-card">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
            <span style="font-size: 24px;">💵</span>
            <span class="badge badge-success"><?= $stats['success_topups'] ?? 0 ?> sukses</span>
        </div>
        <div style="font-size: 24px; font-weight: 700; margin-bottom: 4px;"><?= number_format($stats['total_topups'] ?? 0) ?></div>
        <div style="color: #94a3b8; font-size: 13px;">Total Top Up</div>
        <div style="margin-top: 8px; font-size: 12px; color: #10b981;">
            Rp <?= number_format($stats['topup_amount'] ?? 0, 0, ',', '.') ?>
        </div>
    </div>
    
    <!-- Today's TopUp -->
    <div class="stat-card">
        <div style="font-size: 24px; margin-bottom: 12px;">📈</div>
        <div style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Rp <?= number_format($stats['today_topup'] ?? 0, 0, ',', '.') ?></div>
        <div style="color: #94a3b8; font-size: 13px;">Top Up Hari Ini</div>
    </div>
    
    <!-- Merchant Revenue -->
    <div class="stat-card">
        <div style="font-size: 24px; margin-bottom: 12px;">🏦</div>
        <div style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Rp <?= number_format($stats['merchant_revenue'] ?? 0, 0, ',', '.') ?></div>
        <div style="color: #94a3b8; font-size: 13px;">Merchant Revenue</div>
        <div style="margin-top: 8px; font-size: 12px; color: #64748b;">
            <?= $stats['merchant_transactions'] ?? 0 ?> transaksi
        </div>
    </div>
    
    <!-- Total Wallet Balance -->
    <div class="stat-card">
        <div style="font-size: 24px; margin-bottom: 12px;">💳</div>
        <div style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Rp <?= number_format($stats['total_wallet_balance'] ?? 0, 0, ',', '.') ?></div>
        <div style="color: #94a3b8; font-size: 13px;">Total Saldo User</div>
    </div>
</div>

<!-- Quick Actions -->
<div style="margin-bottom: 32px;">
    <h2 class="section-title">⚡ Aksi Cepat</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">
        <a href="/admin/users/create" class="quick-link">
            <span style="font-size: 20px;">➕</span>
            <span>Tambah User</span>
        </a>
        <a href="/admin/bills/create" class="quick-link">
            <span style="font-size: 20px;">📋</span>
            <span>Buat Tagihan</span>
        </a>
        <a href="/admin/users" class="quick-link">
            <span style="font-size: 20px;">👥</span>
            <span>Kelola Users</span>
        </a>
        <a href="/admin/transactions" class="quick-link">
            <span style="font-size: 20px;">💳</span>
            <span>Lihat Transaksi</span>
        </a>
        <a href="/admin/reports" class="quick-link">
            <span style="font-size: 20px;">📊</span>
            <span>Laporan</span>
        </a>
        <a href="/admin/settings" class="quick-link">
            <span style="font-size: 20px;">⚙️</span>
            <span>Pengaturan</span>
        </a>
    </div>
</div>

<?php 
$typeColors = [
    'electricity' => '#f43f5e',
    'water' => '#3b82f6',
    'internet' => '#8b5cf6',
    'phone' => '#10b981',
    'topup' => '#f97316',
    'transfer' => '#6366f1'
];
$typeIcons = [
    'electricity' => '⚡',
    'water' => '💧',
    'internet' => '📶',
    'phone' => '📱',
    'topup' => '💰',
    'transfer' => '↗️'
];
$billIcons = ['electricity' => '⚡', 'water' => '💧', 'internet' => '📶', 'phone' => '📱'];
?>

<!-- Charts & Analytics Row -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <!-- Daily Revenue Chart -->
    <div class="stat-card">
        <h3 class="section-title">📈 Pendapatan 7 Hari Terakhir</h3>
        <?php 
        $dailyData = $daily_revenue ?? [];
        $maxDaily = !empty($dailyData) ? max(array_column($dailyData, 'total')) : 1;
        $maxDaily = $maxDaily > 0 ? $maxDaily : 1;
        ?>
        <div class="mini-chart" style="height: 120px; margin-bottom: 16px;">
            <?php 
            $last7Days = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $last7Days[$date] = ['date' => $date, 'total' => 0, 'count' => 0];
            }
            foreach ($dailyData as $day) {
                if (isset($last7Days[$day['date']])) {
                    $last7Days[$day['date']] = $day;
                }
            }
            foreach ($last7Days as $day): 
                $height = $maxDaily > 0 ? (($day['total'] ?? 0) / $maxDaily * 100) : 5;
            ?>
            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px;">
                <div class="mini-bar" style="height: <?= max($height, 5) ?>%;"></div>
                <span style="font-size: 10px; color: #64748b;"><?= date('D', strtotime($day['date'])) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="display: flex; justify-content: space-between; padding-top: 12px; border-top: 1px solid #334155;">
            <div>
                <div style="font-size: 12px; color: #64748b;">Total 7 Hari</div>
                <div style="font-size: 18px; font-weight: 700; color: #10b981;">Rp <?= number_format($stats['week_revenue'] ?? 0, 0, ',', '.') ?></div>
            </div>
            <a href="/admin/reports" style="color: #3b82f6; font-size: 13px; text-decoration: none;">Lihat Detail →</a>
        </div>
    </div>
    
    <!-- Revenue by Type -->
    <div class="stat-card">
        <h3 class="section-title">📊 Pendapatan per Kategori</h3>
        <?php 
        $totalByType = array_sum(array_column($revenue_by_type ?? [], 'total'));
        ?>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach (($revenue_by_type ?? []) as $type): 
                $percentage = $totalByType > 0 ? ($type['total'] / $totalByType * 100) : 0;
                $color = $typeColors[$type['type']] ?? '#64748b';
                $icon = $typeIcons[$type['type']] ?? '📋';
            ?>
            <div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                        <?= $icon ?> <span style="text-transform: capitalize;"><?= esc($type['type']) ?></span>
                        <span style="color: #64748b; font-size: 12px;">(<?= $type['count'] ?>)</span>
                    </span>
                    <span style="font-weight: 600; font-size: 14px;">Rp <?= number_format($type['total'], 0, ',', '.') ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $percentage ?>%; background: <?= $color ?>;"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($revenue_by_type)): ?>
            <div style="text-align: center; color: #64748b; padding: 20px;">Belum ada data</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Data Tables Row -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <!-- Recent Transactions -->
    <div class="stat-card" style="padding: 0; overflow: hidden;">
        <div style="padding: 20px; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 16px; font-weight: 600;">💳 Transaksi Terbaru</h3>
            <a href="/admin/transactions" style="color: #3b82f6; font-size: 13px; text-decoration: none;">Lihat Semua →</a>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Tipe</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($recent_transactions ?? [], 0, 5) as $trx): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 500;"><?= esc($trx['user_name'] ?? 'Unknown') ?></div>
                            <div style="font-size: 11px; color: #64748b;"><?= date('d M, H:i', strtotime($trx['created_at'])) ?></div>
                        </td>
                        <td style="text-transform: capitalize;">
                            <?= $typeIcons[$trx['type']] ?? '📋' ?> <?= esc($trx['type']) ?>
                        </td>
                        <td style="font-weight: 600;">Rp <?= number_format($trx['amount'], 0, ',', '.') ?></td>
                        <td>
                            <?php $statusClass = ['success' => 'badge-success', 'pending' => 'badge-warning', 'failed' => 'badge-danger']; ?>
                            <span class="badge <?= $statusClass[$trx['status']] ?? '' ?>"><?= ucfirst($trx['status']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recent_transactions)): ?>
                    <tr><td colspan="4" style="text-align: center; color: #64748b; padding: 20px;">Belum ada transaksi</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Recent Users -->
    <div class="stat-card" style="padding: 0; overflow: hidden;">
        <div style="padding: 20px; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 16px; font-weight: 600;">👥 User Terbaru</h3>
            <a href="/admin/users" style="color: #3b82f6; font-size: 13px; text-decoration: none;">Lihat Semua →</a>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Role</th>
                        <th>Bergabung</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($recent_users ?? []) as $u): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px;">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div style="font-weight: 500;"><?= esc($u['name']) ?></div>
                                    <div style="font-size: 11px; color: #64748b;"><?= esc($u['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php $roleClass = ['admin' => 'badge-danger', 'merchant' => 'badge-warning', 'user' => 'badge-info']; ?>
                            <span class="badge <?= $roleClass[$u['role']] ?? 'badge-info' ?>"><?= ucfirst($u['role']) ?></span>
                        </td>
                        <td style="color: #64748b; font-size: 13px;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recent_users)): ?>
                    <tr><td colspan="3" style="text-align: center; color: #64748b; padding: 20px;">Belum ada user</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- More Data Tables -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <!-- Recent Bills -->
    <div class="stat-card" style="padding: 0; overflow: hidden;">
        <div style="padding: 20px; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 16px; font-weight: 600;">📋 Tagihan Terbaru</h3>
            <a href="/admin/bills" style="color: #3b82f6; font-size: 13px; text-decoration: none;">Lihat Semua →</a>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Tipe</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($recent_bills ?? []) as $bill): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 500;"><?= esc($bill['user_name'] ?? 'Unknown') ?></div>
                            <div style="font-size: 11px; color: #64748b;">Due: <?= date('d M Y', strtotime($bill['due_date'])) ?></div>
                        </td>
                        <td>
                            <?= $billIcons[$bill['bill_type']] ?? '📋' ?> 
                            <span style="text-transform: capitalize;"><?= esc($bill['bill_type']) ?></span>
                        </td>
                        <td style="font-weight: 600;">Rp <?= number_format($bill['amount'], 0, ',', '.') ?></td>
                        <td>
                            <?php 
                            $isOverdue = strtotime($bill['due_date']) < time() && $bill['status'] === 'pending';
                            if ($bill['status'] === 'paid'): ?>
                            <span class="badge badge-success">Paid</span>
                            <?php elseif ($isOverdue): ?>
                            <span class="badge badge-danger">Overdue</span>
                            <?php else: ?>
                            <span class="badge badge-warning">Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recent_bills)): ?>
                    <tr><td colspan="4" style="text-align: center; color: #64748b; padding: 20px;">Belum ada tagihan</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Top Up Terbaru -->
    <div class="stat-card" style="padding: 0; overflow: hidden;">
        <div style="padding: 20px; border-bottom: 1px solid #334155;">
            <h3 style="font-size: 16px; font-weight: 600;">💵 Top Up Terbaru</h3>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($recent_topups ?? []) as $topup): ?>
                    <tr>
                        <td style="font-weight: 500;"><?= esc($topup['user_name'] ?? 'Unknown') ?></td>
                        <td style="font-weight: 600; color: #10b981;">+Rp <?= number_format($topup['amount'], 0, ',', '.') ?></td>
                        <td>
                            <?php $topupStatus = ['success' => 'badge-success', 'pending' => 'badge-warning', 'failed' => 'badge-danger', 'expired' => 'badge-danger']; ?>
                            <span class="badge <?= $topupStatus[$topup['status']] ?? 'badge-info' ?>"><?= ucfirst($topup['status']) ?></span>
                        </td>
                        <td style="color: #64748b; font-size: 13px;"><?= date('d M, H:i', strtotime($topup['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recent_topups)): ?>
                    <tr><td colspan="4" style="text-align: center; color: #64748b; padding: 20px;">Belum ada top up</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Merchants & Overdue Section -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <!-- Recent Merchants -->
    <div class="stat-card" style="padding: 0; overflow: hidden;">
        <div style="padding: 20px; border-bottom: 1px solid #334155;">
            <h3 style="font-size: 16px; font-weight: 600;">🏪 Merchant Terbaru</h3>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Business</th>
                        <th>Owner</th>
                        <th>Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($recent_merchants ?? []) as $m): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 500;"><?= esc($m['business_name'] ?? 'N/A') ?></div>
                            <div style="font-size: 11px; color: #64748b; font-family: monospace;"><?= esc($m['merchant_id']) ?></div>
                        </td>
                        <td style="font-size: 13px;"><?= esc($m['owner_name'] ?? 'N/A') ?></td>
                        <td style="font-weight: 600;">Rp <?= number_format($m['balance'] ?? 0, 0, ',', '.') ?></td>
                        <td>
                            <?php $mStatus = ['active' => 'badge-success', 'pending' => 'badge-warning', 'inactive' => 'badge-danger']; ?>
                            <span class="badge <?= $mStatus[$m['status']] ?? 'badge-info' ?>"><?= ucfirst($m['status']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recent_merchants)): ?>
                    <tr><td colspan="4" style="text-align: center; color: #64748b; padding: 20px;">Belum ada merchant</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Overdue Bills Alert -->
    <div class="stat-card" style="padding: 0; overflow: hidden; border-color: <?= !empty($overdue_bills) ? '#ef4444' : '#334155' ?>;">
        <div style="padding: 20px; border-bottom: 1px solid #334155; background: <?= !empty($overdue_bills) ? '#ef444410' : 'transparent' ?>;">
            <h3 style="font-size: 16px; font-weight: 600; color: <?= !empty($overdue_bills) ? '#ef4444' : 'inherit' ?>;">
                ⚠️ Tagihan Jatuh Tempo
            </h3>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Tipe</th>
                        <th>Amount</th>
                        <th>Overdue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($overdue_bills ?? []) as $ob): 
                        $daysOverdue = floor((time() - strtotime($ob['due_date'])) / 86400);
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight: 500;"><?= esc($ob['user_name'] ?? 'Unknown') ?></div>
                            <div style="font-size: 11px; color: #64748b;"><?= esc($ob['user_email'] ?? '') ?></div>
                        </td>
                        <td>
                            <?= $billIcons[$ob['bill_type']] ?? '📋' ?> 
                            <span style="text-transform: capitalize;"><?= esc($ob['bill_type']) ?></span>
                        </td>
                        <td style="font-weight: 600; color: #ef4444;">Rp <?= number_format($ob['amount'], 0, ',', '.') ?></td>
                        <td>
                            <span class="badge badge-danger"><?= $daysOverdue ?> hari</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($overdue_bills)): ?>
                    <tr><td colspan="4" style="text-align: center; color: #4ade80; padding: 20px;">✅ Tidak ada tagihan jatuh tempo</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Top Users -->
<div class="stat-card" style="padding: 0; overflow: hidden; margin-bottom: 32px;">
    <div style="padding: 20px; border-bottom: 1px solid #334155;">
        <h3 style="font-size: 16px; font-weight: 600;">🏆 Top Users (Berdasarkan Transaksi)</h3>
    </div>
    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>User</th>
                    <th>Total Transaksi</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $rank = 1;
                $medals = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];
                foreach (($top_users ?? []) as $tu): 
                ?>
                <tr>
                    <td style="font-size: 20px;"><?= $medals[$rank-1] ?? $rank ?></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #f97316, #ea580c); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px;">
                                <?= strtoupper(substr($tu['name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-weight: 500;"><?= esc($tu['name'] ?? 'Unknown') ?></div>
                                <div style="font-size: 11px; color: #64748b;"><?= esc($tu['email'] ?? '') ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="font-weight: 600;"><?= number_format($tu['tx_count'] ?? 0) ?> transaksi</td>
                    <td style="font-weight: 700; color: #10b981;">Rp <?= number_format($tu['total_amount'] ?? 0, 0, ',', '.') ?></td>
                </tr>
                <?php $rank++; endforeach; ?>
                <?php if (empty($top_users)): ?>
                <tr><td colspan="4" style="text-align: center; color: #64748b; padding: 20px;">Belum ada data</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- System Info -->
<div style="background: #0f172a; border-radius: 12px; padding: 20px; border: 1px solid #334155;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; gap: 24px; flex-wrap: wrap;">
            <div>
                <span style="color: #64748b; font-size: 12px;">Server Time</span>
                <div style="font-family: monospace;"><?= date('Y-m-d H:i:s') ?></div>
            </div>
            <div>
                <span style="color: #64748b; font-size: 12px;">PHP Version</span>
                <div style="font-family: monospace;"><?= PHP_VERSION ?></div>
            </div>
            <div>
                <span style="color: #64748b; font-size: 12px;">CodeIgniter</span>
                <div style="font-family: monospace;"><?= \CodeIgniter\CodeIgniter::CI_VERSION ?></div>
            </div>
        </div>
        <div style="text-align: right;">
            <span style="color: #10b981; font-size: 12px;">● System Online</span>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
