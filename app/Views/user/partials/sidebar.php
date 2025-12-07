<?php
/**
 * User Sidebar Partial
 */
$activePage = $activePage ?? '';
?>
<div class="nav-section">
    <div class="nav-section-title">Main Menu</div>
    <a href="/user/dashboard" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
        <span class="nav-icon">🏠</span>
        Dashboard
    </a>
    <a href="/user/topup" class="nav-item <?= $activePage === 'topup' ? 'active' : '' ?>">
        <span class="nav-icon">💰</span>
        Top Up
    </a>
    <a href="/user/bills" class="nav-item <?= $activePage === 'bills' ? 'active' : '' ?>">
        <span class="nav-icon">📄</span>
        My Bills
    </a>
    <a href="/user/payments" class="nav-item <?= $activePage === 'payments' ? 'active' : '' ?>">
        <span class="nav-icon">💳</span>
        Payment History
    </a>
</div>

<div class="nav-section">
    <div class="nav-section-title">Account</div>
    <a href="/user/profile" class="nav-item <?= $activePage === 'profile' ? 'active' : '' ?>">
        <span class="nav-icon">👤</span>
        Profile
    </a>
</div>
