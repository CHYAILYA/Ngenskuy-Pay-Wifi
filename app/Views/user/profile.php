<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('sidebar') ?>
<div class="nav-section">
    <div class="nav-section-title">Main Menu</div>
    <a href="/user/dashboard" class="nav-item">
        <span class="nav-icon">🏠</span>
        Dashboard
    </a>
    <a href="/user/topup" class="nav-item">
        <span class="nav-icon">💰</span>
        Top Up
    </a>
    <a href="/user/bills" class="nav-item">
        <span class="nav-icon">📄</span>
        My Bills
    </a>
    <a href="/user/payments" class="nav-item">
        <span class="nav-icon">💳</span>
        Payment History
    </a>
</div>

<div class="nav-section">
    <div class="nav-section-title">Account</div>
    <a href="/user/profile" class="nav-item active">
        <span class="nav-icon">👤</span>
        Profile
    </a>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if (session()->getFlashdata('success')): ?>
    <div style="background: #065f46; color: #d1fae5; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #10b981;">
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div style="background: #991b1b; color: #fee2e2; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #ef4444;">
        <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
    <!-- Profile Form -->
    <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 32px;">
        <h2 style="font-size: 20px; font-weight: 600; margin-bottom: 24px;">👤 Profil Saya</h2>
        
        <form method="POST" action="/user/profile">
            <?= csrf_field() ?>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #94a3b8;">Nama Lengkap</label>
                <input type="text" name="name" value="<?= esc($user['name'] ?? '') ?>" required
                       style="width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 12px; padding: 14px 16px; color: white; font-size: 16px;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #94a3b8;">Email</label>
                <input type="email" name="email" value="<?= esc($user['email'] ?? '') ?>" required
                       style="width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 12px; padding: 14px 16px; color: white; font-size: 16px;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #94a3b8;">Role</label>
                <input type="text" value="<?= ucfirst(esc($user['role'] ?? 'user')) ?>" disabled
                       style="width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 12px; padding: 14px 16px; color: #64748b; font-size: 16px;">
            </div>
            
            <button type="submit" style="width: 100%; background: linear-gradient(135deg, #3b82f6, #6366f1); border: none; color: white; padding: 14px; border-radius: 12px; font-weight: 600; font-size: 16px; cursor: pointer;">
                💾 Simpan Perubahan
            </button>
        </form>
    </div>
    
    <!-- Account Info -->
    <div>
        <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 32px; margin-bottom: 24px;">
            <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 20px;">💳 Virtual Card</h3>
            
            <!-- Virtual Card Design -->
            <div class="virtual-card">
                <div class="card-top">
                    <span>UDARA Pay</span>
                    <svg width="40" height="24" viewBox="0 0 40 24" fill="none">
                        <circle cx="14" cy="12" r="10" fill="#eb001b" opacity="0.9"/>
                        <circle cx="26" cy="12" r="10" fill="#f79e1b" opacity="0.9"/>
                        <path d="M20 18.5c2.4-1.8 4-4.7 4-8s-1.6-6.2-4-8c-2.4 1.8-4 4.7-4 8s1.6 6.2 4 8z" fill="#ff5f00"/>
                    </svg>
                </div>
                <div class="card-number-display">
                    <?php 
                    $cardNumber = $user['card_number'] ?? '';
                    if ($cardNumber && strlen($cardNumber) === 16) {
                        echo esc(implode(' ', str_split($cardNumber, 4)));
                    } else {
                        echo '•••• •••• •••• ••••';
                    }
                    ?>
                </div>
                <div class="card-bottom">
                    <div class="card-holder">
                        <span class="holder-label">CARD HOLDER</span>
                        <span class="holder-name"><?= esc($user['name'] ?? 'User') ?></span>
                    </div>
                    <div class="card-valid">
                        <span class="valid-label">VALID THRU</span>
                        <span class="valid-date">12/30</span>
                    </div>
                </div>
            </div>
            
            <p style="text-align: center; color: #64748b; font-size: 13px; margin-top: 16px;">
                Gunakan nomor kartu ini untuk menerima transfer dari pengguna lain
            </p>
            
            <a href="/user/topup" style="display: block; background: #10b981; border: none; color: white; padding: 14px; border-radius: 12px; text-decoration: none; font-weight: 600; text-align: center; margin-top: 16px;">
                💰 Top Up Saldo
            </a>
        </div>
        
        <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 32px;">
            <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 20px;">🔒 Keamanan Akun</h3>
            
            <div style="background: #0f172a; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 500; margin-bottom: 4px;">Password</div>
                        <div style="font-size: 13px; color: #64748b;">••••••••</div>
                    </div>
                    <button style="background: #334155; border: none; color: white; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 13px;">
                        Ubah
                    </button>
                </div>
            </div>
            
            <div style="background: #0f172a; border-radius: 12px; padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 500; margin-bottom: 4px;">Verifikasi Email</div>
                        <div style="font-size: 13px; color: #10b981;">✓ Terverifikasi</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    input:focus {
        outline: none;
        border-color: #3b82f6 !important;
    }
    
    /* Virtual Card Styles */
    @keyframes cardGradient {
        0% { background-position: 0% 10%; }
        50% { background-position: 100% 91%; }
        100% { background-position: 0% 10%; }
    }
    
    .virtual-card {
        background-image: url("https://assets.codepen.io/14762/egg-sour.jpg");
        background-size: cover;
        background-position: center;
        border-radius: 16px;
        height: 200px;
        width: 100%;
        position: relative;
        box-sizing: border-box;
        padding: 24px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        box-shadow: 0 0px 8px rgb(0 0 0 / 12%), 0 2px 16px rgb(0 0 0 / 12%),
            0 4px 20px rgb(0 0 0 / 12%), 0 12px 28px rgb(0 0 0 / 12%);
        color: #1a1d21;
        overflow: hidden;
    }
    
    .virtual-card:before {
        content: "";
        width: 100%;
        height: 100%;
        box-shadow: 0 -1px 0 0 rgb(255 255 255 / 90%), 0 1px 0 0 rgb(0 0 0 / 20%);
        position: absolute;
        z-index: 1;
        border-radius: 16px;
        top: 0;
        left: 0;
        pointer-events: none;
    }
    
    .virtual-card:after {
        content: "";
        width: 100%;
        height: 100%;
        border-radius: 16px;
        background: linear-gradient(
            120deg,
            rgb(255 255 255 / 2%) 30%,
            rgb(255 255 255 / 25%) 40%,
            rgb(255 255 255 / 8%) 40%
        ),
        linear-gradient(0deg, rgb(255 255 255 / 20%), rgb(255 255 255 / 30%));
        background-size: 150% 150%;
        animation: cardGradient 45s ease-in-out infinite;
        position: absolute;
        top: 0;
        left: 0;
        pointer-events: none;
    }
    
    .virtual-card .card-top,
    .virtual-card .card-number-display,
    .virtual-card .card-bottom {
        position: relative;
        z-index: 2;
    }
    
    .virtual-card .card-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .virtual-card .card-top span {
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-weight: 700;
        text-shadow: 0 1px 2px rgba(255,255,255,0.3);
    }
    
    .virtual-card .card-number-display {
        text-align: center;
        font-size: 22px;
        font-weight: 600;
        letter-spacing: 4px;
        font-family: 'Courier New', monospace;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 10px 0;
    }
    
    .virtual-card .card-bottom {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }
    
    .virtual-card .card-holder,
    .virtual-card .card-valid {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    
    .virtual-card .holder-label,
    .virtual-card .valid-label {
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.6;
    }
    
    .virtual-card .holder-name {
        font-size: 14px;
        font-weight: 600;
        letter-spacing: 0.5px;
        max-width: 180px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .virtual-card .valid-date {
        font-size: 14px;
        font-weight: 600;
        letter-spacing: 1px;
    }
</style>
<?= $this->endSection() ?>
