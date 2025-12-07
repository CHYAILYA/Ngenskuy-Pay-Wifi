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
    <a href="/merchant/dashboard" class="nav-item active">
        <span class="nav-icon">📊</span>
        Dashboard
    </a>
    <a href="/merchant/transactions" class="nav-item">
        <span class="nav-icon">💳</span>
        Transactions
    </a>
    <a href="/merchant/qrcode" class="nav-item">
        <span class="nav-icon">📱</span>
        QR Code
    </a>
    <a href="/merchant/payment/request" class="nav-item">
        <span class="nav-icon">🔗</span>
        Payment Link
    </a>
</div>

<div class="nav-section">
    <div class="nav-section-title">Finance</div>
    <a href="/merchant/withdraw" class="nav-item">
        <span class="nav-icon">🏦</span>
        Withdraw
    </a>
</div>

<div class="nav-section">
    <div class="nav-section-title">Settings</div>
    <a href="/merchant/profile" class="nav-item">
        <span class="nav-icon">⚙️</span>
        Profile
    </a>
    <a href="/logout" class="nav-item">
        <span class="nav-icon">🚪</span>
        Logout
    </a>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Merchant Welcome Banner -->
<div style="background: linear-gradient(135deg, #10b981, #059669, #047857); border-radius: 20px; padding: 32px; margin-bottom: 32px; position: relative; overflow: hidden;">
    <div style="position: absolute; right: -50px; top: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
    <div style="position: absolute; right: 50px; bottom: -80px; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
    <div style="position: relative; z-index: 1;">
        <h2 style="font-size: 28px; font-weight: 700; margin-bottom: 8px;">Welcome, <?= esc($merchant['business_name'] ?? 'Merchant') ?>! 🏪</h2>
        <p style="opacity: 0.9; font-size: 16px; margin-bottom: 8px;">Here's your business overview</p>
        <p style="opacity: 0.7; font-size: 14px;">Merchant ID: <strong><?= esc($merchant['merchant_id'] ?? 'N/A') ?></strong></p>
    </div>
</div>

<!-- Available Balance -->
<div style="background: linear-gradient(135deg, #1e293b, #334155); border-radius: 16px; padding: 24px; border: 1px solid #475569; margin-bottom: 32px;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <div style="color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Merchant Balance</div>
            <div style="font-size: 36px; font-weight: 700; color: #10b981;">Rp <?= number_format($merchant['balance'] ?? 0, 0, ',', '.') ?></div>
        </div>
        <a href="/merchant/withdraw" class="btn btn-primary" style="padding: 14px 28px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 12px; text-decoration: none; color: white; font-weight: 600;">
            🏦 Withdraw
        </a>
    </div>
</div>

<!-- Merchant Stats -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <div style="background: #1e293b; border-radius: 16px; padding: 24px; border: 1px solid #334155;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <span style="font-size: 28px;">💰</span>
        </div>
        <div style="font-size: 28px; font-weight: 700; margin-bottom: 4px; color: #10b981;">Rp <?= number_format($stats['today_revenue'] ?? 0, 0, ',', '.') ?></div>
        <div style="color: #94a3b8; font-size: 14px;">Today's Revenue</div>
    </div>

    <div style="background: #1e293b; border-radius: 16px; padding: 24px; border: 1px solid #334155;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <span style="font-size: 28px;">📈</span>
        </div>
        <div style="font-size: 28px; font-weight: 700; margin-bottom: 4px; color: #3b82f6;">Rp <?= number_format($stats['month_revenue'] ?? 0, 0, ',', '.') ?></div>
        <div style="color: #94a3b8; font-size: 14px;">This Month</div>
    </div>

    <div style="background: #1e293b; border-radius: 16px; padding: 24px; border: 1px solid #334155;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <span style="font-size: 28px;">💵</span>
        </div>
        <div style="font-size: 28px; font-weight: 700; margin-bottom: 4px; color: #8b5cf6;">Rp <?= number_format($stats['total_revenue'] ?? 0, 0, ',', '.') ?></div>
        <div style="color: #94a3b8; font-size: 14px;">Total Revenue</div>
    </div>

    <div style="background: #1e293b; border-radius: 16px; padding: 24px; border: 1px solid #334155;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <span style="font-size: 28px;">🛒</span>
        </div>
        <div style="font-size: 28px; font-weight: 700; margin-bottom: 4px; color: #f97316;"><?= number_format($stats['total_transactions'] ?? 0) ?></div>
        <div style="color: #94a3b8; font-size: 14px;">Total Transactions</div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions-grid" style="margin-bottom: 32px;">
    <a href="/merchant/qrcode" style="background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 24px; text-decoration: none; color: inherit; text-align: center; transition: all 0.2s;">
        <div style="font-size: 40px; margin-bottom: 12px;">📱</div>
        <div style="font-weight: 600; margin-bottom: 4px;">Show QR Code</div>
        <div style="font-size: 12px; color: #64748b;">Let customers scan to pay</div>
    </a>
    <a href="/merchant/payment/request" style="background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 24px; text-decoration: none; color: inherit; text-align: center; transition: all 0.2s;">
        <div style="font-size: 40px; margin-bottom: 12px;">🔗</div>
        <div style="font-weight: 600; margin-bottom: 4px;">Create Payment Link</div>
        <div style="font-size: 12px; color: #64748b;">Share link for payment</div>
    </a>
    <a href="/merchant/withdraw" style="background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 24px; text-decoration: none; color: inherit; text-align: center; transition: all 0.2s;">
        <div style="font-size: 40px; margin-bottom: 12px;">🏦</div>
        <div style="font-weight: 600; margin-bottom: 4px;">Withdraw Funds</div>
        <div style="font-size: 12px; color: #64748b;">Transfer to bank account</div>
    </a>
</div>

<!-- Revenue Chart -->
<div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 24px; margin-bottom: 32px;">
    <h3 style="font-size: 18px; margin-bottom: 24px;">📊 Revenue (Last 7 Days)</h3>
    <div style="display: flex; align-items: flex-end; gap: 8px; height: 200px;">
        <?php 
        $maxRevenue = 1;
        if (!empty($daily_revenue)) {
            $revenues = array_column($daily_revenue, 'total');
            $maxRevenue = !empty($revenues) ? max($revenues) : 1;
        }
        
        // Fill in missing days
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $days[$date] = 0;
        }
        foreach ($daily_revenue ?? [] as $row) {
            if (isset($days[$row['date']])) {
                $days[$row['date']] = (float) $row['total'];
            }
        }
        
        foreach ($days as $date => $total):
            $height = $maxRevenue > 0 ? ($total / $maxRevenue * 100) : 0;
            $dayName = date('D', strtotime($date));
        ?>
        <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 8px;">
            <div style="width: 100%; background: linear-gradient(180deg, #10b981, #059669); border-radius: 4px; height: <?= max($height, 2) ?>%; min-height: 4px;"></div>
            <span style="font-size: 10px; color: #64748b;"><?= $dayName ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Recent Transactions -->
<div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; overflow: hidden;">
    <div style="padding: 20px 24px; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <h3 style="font-size: 18px; font-weight: 600;">💳 Recent Transactions</h3>
        <a href="/merchant/transactions" style="color: #10b981; text-decoration: none; font-size: 14px;">View All →</a>
    </div>
    <div class="responsive-table">
        <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
            <thead>
                <tr style="background: #0f172a;">
                    <th style="text-align: left; padding: 12px 16px; color: #64748b; font-weight: 500; font-size: 13px;">Transaction ID</th>
                    <th style="text-align: left; padding: 12px 16px; color: #64748b; font-weight: 500; font-size: 13px;">Customer</th>
                    <th style="text-align: left; padding: 12px 16px; color: #64748b; font-weight: 500; font-size: 13px;">Amount</th>
                    <th style="text-align: left; padding: 12px 16px; color: #64748b; font-weight: 500; font-size: 13px;">Net</th>
                    <th style="text-align: left; padding: 12px 16px; color: #64748b; font-weight: 500; font-size: 13px;">Status</th>
                    <th style="text-align: left; padding: 12px 16px; color: #64748b; font-weight: 500; font-size: 13px;">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recent_transactions)): ?>
                    <?php foreach ($recent_transactions as $tx): ?>
                    <tr style="border-bottom: 1px solid #334155;">
                        <td style="padding: 14px 16px; font-family: monospace; color: #64748b; font-size: 12px;"><?= esc($tx['transaction_id']) ?></td>
                        <td style="padding: 14px 16px; font-size: 14px;"><?= esc($tx['customer_name'] ?? 'Guest') ?></td>
                        <td style="padding: 14px 16px; font-size: 14px;">Rp <?= number_format($tx['amount'], 0, ',', '.') ?></td>
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
                        <td colspan="6" style="text-align: center; color: #64748b; padding: 40px;">
                            No transactions yet. Share your QR code to start receiving payments!
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
