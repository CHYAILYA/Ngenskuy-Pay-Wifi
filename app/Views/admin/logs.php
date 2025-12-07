<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('sidebar') ?>
<?= $this->include('admin/partials/sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; overflow: hidden;">
    <div style="padding: 20px 24px; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="font-size: 18px;">Today's Activity Log</h2>
        <span style="color: #64748b; font-size: 14px;"><?= date('d M Y') ?></span>
    </div>
    
    <div style="padding: 24px;">
        <?php if (!empty($logs) && $logs !== 'No logs for today'): ?>
        <pre style="background: #0f172a; border-radius: 8px; padding: 20px; overflow-x: auto; font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.6; color: #94a3b8; max-height: 600px; overflow-y: auto;">
<?= esc($logs) ?>
        </pre>
        <?php else: ?>
        <div style="text-align: center; padding: 60px 20px; color: #64748b;">
            <div style="font-size: 48px; margin-bottom: 16px;">📝</div>
            <p>No activity logs for today</p>
            <p style="font-size: 14px; margin-top: 8px;">Logs will appear here when there's activity in the system</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Info -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 24px;">
    <div style="background: #1e293b; border-radius: 12px; padding: 20px; border: 1px solid #334155;">
        <div style="color: #64748b; font-size: 14px; margin-bottom: 8px;">Log Location</div>
        <div style="font-family: monospace; font-size: 12px; color: #94a3b8; word-break: break-all;">writable/logs/</div>
    </div>
    <div style="background: #1e293b; border-radius: 12px; padding: 20px; border: 1px solid #334155;">
        <div style="color: #64748b; font-size: 14px; margin-bottom: 8px;">Log File</div>
        <div style="font-family: monospace; font-size: 12px; color: #94a3b8;">log-<?= date('Y-m-d') ?>.log</div>
    </div>
    <div style="background: #1e293b; border-radius: 12px; padding: 20px; border: 1px solid #334155;">
        <div style="color: #64748b; font-size: 14px; margin-bottom: 8px;">Server Time</div>
        <div style="font-family: monospace; font-size: 12px; color: #94a3b8;"><?= date('Y-m-d H:i:s') ?></div>
    </div>
</div>
<?= $this->endSection() ?>
