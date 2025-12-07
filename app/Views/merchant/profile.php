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
    <a href="/merchant/transactions" class="nav-item"><span class="nav-icon">💳</span> Transactions</a>
    <a href="/merchant/qrcode" class="nav-item"><span class="nav-icon">📱</span> QR Code</a>
    <a href="/merchant/payment/request" class="nav-item"><span class="nav-icon">🔗</span> Payment Link</a>
</div>
<div class="nav-section">
    <div class="nav-section-title">Finance</div>
    <a href="/merchant/withdraw" class="nav-item"><span class="nav-icon">🏦</span> Withdraw</a>
</div>
<div class="nav-section">
    <div class="nav-section-title">Settings</div>
    <a href="/merchant/profile" class="nav-item active"><span class="nav-icon">⚙️</span> Profile</a>
    <a href="/logout" class="nav-item"><span class="nav-icon">🚪</span> Logout</a>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <div>
        <h1 style="font-size: 28px; margin-bottom: 8px;">⚙️ Merchant Profile</h1>
        <p style="color: #64748b;">Manage your business information</p>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div style="background: #10b98120; border: 1px solid #10b981; padding: 16px; border-radius: 8px; margin-bottom: 24px; color: #6ee7b7;">
    <?= session()->getFlashdata('success') ?>
</div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
<div style="background: #ef444420; border: 1px solid #ef4444; padding: 16px; border-radius: 8px; margin-bottom: 24px; color: #fca5a5;">
    <?= session()->getFlashdata('error') ?>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
    <!-- Merchant Card -->
    <div>
        <div style="background: linear-gradient(135deg, #1e40af, #3730a3); border-radius: 20px; padding: 32px; margin-bottom: 24px; position: relative; overflow: hidden;">
            <!-- Background Pattern -->
            <div style="position: absolute; top: 0; right: 0; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%; transform: translate(50%, -50%);"></div>
            <div style="position: absolute; bottom: 0; left: 0; width: 100px; height: 100px; background: rgba(255,255,255,0.05); border-radius: 50%; transform: translate(-30%, 30%);"></div>
            
            <div style="position: relative; z-index: 1;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px;">
                    <div>
                        <div style="font-size: 12px; opacity: 0.8; margin-bottom: 4px;">MERCHANT ID</div>
                        <div style="font-family: monospace; font-size: 20px; font-weight: 700; letter-spacing: 2px;">
                            <?= esc($merchant['merchant_id'] ?? 'N/A') ?>
                        </div>
                    </div>
                    <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                        🏪
                    </div>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <div style="font-size: 12px; opacity: 0.8; margin-bottom: 4px;">BUSINESS NAME</div>
                    <div style="font-size: 24px; font-weight: 700;"><?= esc($merchant['business_name'] ?? 'Not Set') ?></div>
                </div>
                
                <div style="display: flex; justify-content: space-between;">
                    <div>
                        <div style="font-size: 12px; opacity: 0.8; margin-bottom: 4px;">OWNER</div>
                        <div style="font-size: 14px;"><?= esc($user['name'] ?? 'N/A') ?></div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 12px; opacity: 0.8; margin-bottom: 4px;">STATUS</div>
                        <span style="padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;
                            <?= ($merchant['status'] ?? '') == 'active' ? 'background: #10b98140; color: #6ee7b7;' : 'background: #f59e0b40; color: #fcd34d;' ?>">
                            <?= ucfirst($merchant['status'] ?? 'pending') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- QR Code Display -->
        <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 24px; text-align: center;">
            <h3 style="font-size: 16px; margin-bottom: 16px;">📱 Your Payment QR</h3>
            <div style="background: white; padding: 16px; border-radius: 12px; display: inline-block; margin-bottom: 16px;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode(base_url('pay/' . ($merchant['merchant_id'] ?? ''))) ?>" 
                     alt="Payment QR" style="display: block;">
            </div>
            <div style="color: #64748b; font-size: 14px;">Scan to pay to your business</div>
        </div>
    </div>

    <!-- Edit Profile Form -->
    <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 32px;">
        <h3 style="font-size: 18px; margin-bottom: 24px;">📝 Business Information</h3>
        
        <form method="POST" action="/merchant/profile/update">
            <div style="margin-bottom: 20px;">
                <label style="display: block; color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Business Name *</label>
                <input type="text" name="business_name" value="<?= esc($merchant['business_name'] ?? '') ?>" required
                       style="width: 100%; padding: 14px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: white; font-size: 16px;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Business Type *</label>
                <select name="business_type" required
                        style="width: 100%; padding: 14px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: white; font-size: 16px;">
                    <option value="">Select Type</option>
                    <option value="food" <?= ($merchant['business_type'] ?? '') == 'food' ? 'selected' : '' ?>>🍔 Food & Beverage</option>
                    <option value="retail" <?= ($merchant['business_type'] ?? '') == 'retail' ? 'selected' : '' ?>>🛍️ Retail</option>
                    <option value="services" <?= ($merchant['business_type'] ?? '') == 'services' ? 'selected' : '' ?>>🔧 Services</option>
                    <option value="education" <?= ($merchant['business_type'] ?? '') == 'education' ? 'selected' : '' ?>>📚 Education</option>
                    <option value="health" <?= ($merchant['business_type'] ?? '') == 'health' ? 'selected' : '' ?>>🏥 Health</option>
                    <option value="entertainment" <?= ($merchant['business_type'] ?? '') == 'entertainment' ? 'selected' : '' ?>>🎮 Entertainment</option>
                    <option value="other" <?= ($merchant['business_type'] ?? '') == 'other' ? 'selected' : '' ?>>📦 Other</option>
                </select>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Business Phone</label>
                <input type="tel" name="phone" value="<?= esc($merchant['phone'] ?? '') ?>" placeholder="e.g. 08123456789"
                       style="width: 100%; padding: 14px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: white; font-size: 16px;">
            </div>
            
            <div style="margin-bottom: 24px;">
                <label style="display: block; color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Business Address</label>
                <textarea name="address" rows="3" placeholder="Enter your business address"
                          style="width: 100%; padding: 14px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: white; font-size: 16px; resize: vertical;"><?= esc($merchant['address'] ?? '') ?></textarea>
            </div>
            
            <button type="submit" style="width: 100%; padding: 16px; background: linear-gradient(135deg, #10b981, #059669); border: none; border-radius: 10px; color: white; font-weight: 600; font-size: 16px; cursor: pointer;">
                💾 Update Profile
            </button>
        </form>
    </div>
</div>

<!-- API Information -->
<div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 24px; margin-top: 32px;">
    <h3 style="font-size: 18px; margin-bottom: 20px;">🔗 API Integration</h3>
    <p style="color: #64748b; margin-bottom: 16px;">Use these endpoints to integrate payments into your application:</p>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div style="background: #0f172a; border-radius: 12px; padding: 16px;">
            <div style="font-size: 12px; color: #10b981; margin-bottom: 8px;">Payment Link</div>
            <code style="word-break: break-all; font-size: 12px; color: #94a3b8;"><?= base_url('pay/' . ($merchant['merchant_id'] ?? 'YOUR_ID')) ?></code>
        </div>
        <div style="background: #0f172a; border-radius: 12px; padding: 16px;">
            <div style="font-size: 12px; color: #10b981; margin-bottom: 8px;">API Endpoint</div>
            <code style="word-break: break-all; font-size: 12px; color: #94a3b8;"><?= base_url('api/merchant/payment/initiate') ?></code>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
