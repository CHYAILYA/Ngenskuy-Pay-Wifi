<?php
/**
 * Admin Sidebar Partial
 * Usage: <?= $this->include('admin/partials/sidebar') ?>
 */
$activePage = $activePage ?? '';
?>

<div class="nav-section">
    <div class="nav-section-title">Main Menu</div>
    <a href="/admin/dashboard" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
        <span class="nav-icon">🏠</span>
        Dashboard
    </a>
    <a href="/admin/users" class="nav-item <?= $activePage === 'users' ? 'active' : '' ?>">
        <span class="nav-icon">👥</span>
        Users
    </a>
    <a href="/admin/transactions" class="nav-item <?= $activePage === 'transactions' ? 'active' : '' ?>">
        <span class="nav-icon">💰</span>
        Transactions
    </a>
</div>

<div class="nav-section">
    <div class="nav-section-title">Billing</div>
    <a href="/admin/bills" class="nav-item <?= $activePage === 'bills' ? 'active' : '' ?>">
        <span class="nav-icon">📋</span>
        All Bills
    </a>
    <a href="/admin/reports" class="nav-item <?= $activePage === 'reports' ? 'active' : '' ?>">
        <span class="nav-icon">📊</span>
        Reports
    </a>
</div>

<div class="nav-section">
    <div class="nav-section-title">System</div>
    <a href="/admin/settings" class="nav-item <?= $activePage === 'settings' ? 'active' : '' ?>">
        <span class="nav-icon">⚙️</span>
        Settings
    </a>
    <a href="/admin/logs" class="nav-item <?= $activePage === 'logs' ? 'active' : '' ?>">
        <span class="nav-icon">📝</span>
        Activity Logs
    </a>
    <a href="/logout" class="nav-item">
        <span class="nav-icon">🚪</span>
        Logout
    </a>
</div>
