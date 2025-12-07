<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('sidebar') ?>
<?= $this->include('admin/partials/sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div style="max-width: 600px;">
    <!-- Back Link -->
    <a href="/admin/bills" style="color: #94a3b8; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 24px;">
        ← Back to Bills
    </a>

    <!-- Form Card -->
    <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 32px;">
        <h2 style="margin-bottom: 24px; font-size: 20px;">Create New Bill</h2>

        <form method="POST">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px;">User</label>
                <select name="user_id" required
                    style="width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 12px 16px; color: #f1f5f9; font-size: 16px;">
                    <option value="">Select User</option>
                    <?php foreach ($users ?? [] as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= esc($u['name']) ?> (<?= esc($u['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px;">Bill Type</label>
                <select name="type" required
                    style="width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 12px 16px; color: #f1f5f9; font-size: 16px;">
                    <option value="electricity">⚡ Electricity</option>
                    <option value="water">💧 Water</option>
                    <option value="internet">📶 Internet</option>
                    <option value="phone">📱 Phone</option>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px;">Description</label>
                <input type="text" name="description" placeholder="e.g., PLN January 2025"
                    style="width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 12px 16px; color: #f1f5f9; font-size: 16px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px;">Amount (Rp)</label>
                <input type="number" name="amount" required min="1000" step="1000"
                    style="width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 12px 16px; color: #f1f5f9; font-size: 16px;">
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px;">Due Date</label>
                <input type="date" name="due_date" required
                    style="width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 12px 16px; color: #f1f5f9; font-size: 16px;">
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Create Bill</button>
                <a href="/admin/bills" class="btn btn-outline" style="flex: 1; text-align: center;">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
