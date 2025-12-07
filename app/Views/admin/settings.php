<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('sidebar') ?>
<?= $this->include('admin/partials/sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div style="max-width: 800px;">
    <!-- Settings Sections -->
    <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 32px; margin-bottom: 24px;">
        <h2 style="margin-bottom: 24px; font-size: 20px; display: flex; align-items: center; gap: 12px;">
            <span>🏢</span> Application Settings
        </h2>
        
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px;">App Name</label>
                    <input type="text" name="app_name" value="BillPay"
                        style="width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 12px 16px; color: #f1f5f9;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px;">Currency</label>
                    <select name="currency"
                        style="width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 12px 16px; color: #f1f5f9;">
                        <option value="IDR" selected>IDR - Indonesian Rupiah</option>
                        <option value="USD">USD - US Dollar</option>
                    </select>
                </div>
            </div>
            
            <div style="margin-top: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px;">Timezone</label>
                <select name="timezone"
                    style="width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 12px 16px; color: #f1f5f9;">
                    <option value="Asia/Jakarta" selected>Asia/Jakarta (WIB)</option>
                    <option value="Asia/Makassar">Asia/Makassar (WITA)</option>
                    <option value="Asia/Jayapura">Asia/Jayapura (WIT)</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 24px;">Save Settings</button>
        </form>
    </div>

    <!-- Notification Settings -->
    <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 32px; margin-bottom: 24px;">
        <h2 style="margin-bottom: 24px; font-size: 20px; display: flex; align-items: center; gap: 12px;">
            <span>🔔</span> Notification Settings
        </h2>
        
        <div style="space-y: 16px;">
            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 16px; background: #0f172a; border-radius: 8px; margin-bottom: 12px;">
                <input type="checkbox" checked style="width: 20px; height: 20px;">
                <div>
                    <div style="font-weight: 600;">Email Notifications</div>
                    <div style="color: #64748b; font-size: 14px;">Send email alerts for important events</div>
                </div>
            </label>
            
            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 16px; background: #0f172a; border-radius: 8px; margin-bottom: 12px;">
                <input type="checkbox" checked style="width: 20px; height: 20px;">
                <div>
                    <div style="font-weight: 600;">Bill Due Reminders</div>
                    <div style="color: #64748b; font-size: 14px;">Notify users before bill due dates</div>
                </div>
            </label>
            
            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 16px; background: #0f172a; border-radius: 8px;">
                <input type="checkbox" style="width: 20px; height: 20px;">
                <div>
                    <div style="font-weight: 600;">Marketing Emails</div>
                    <div style="color: #64748b; font-size: 14px;">Send promotional content to users</div>
                </div>
            </label>
        </div>
    </div>

    <!-- Danger Zone -->
    <div style="background: #1e293b; border-radius: 16px; border: 1px solid #ef4444; padding: 32px;">
        <h2 style="margin-bottom: 16px; font-size: 20px; color: #ef4444; display: flex; align-items: center; gap: 12px;">
            <span>⚠️</span> Danger Zone
        </h2>
        <p style="color: #94a3b8; margin-bottom: 20px;">These actions are irreversible. Please be careful.</p>
        
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <button onclick="alert('This feature is disabled in demo')" class="btn" style="background: #ef4444; color: white;">
                Clear All Logs
            </button>
            <button onclick="alert('This feature is disabled in demo')" class="btn" style="background: transparent; border: 1px solid #ef4444; color: #ef4444;">
                Reset Statistics
            </button>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
