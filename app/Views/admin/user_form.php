<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('sidebar') ?>
<?= $this->include('admin/partials/sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $isEdit = isset($edit_user); ?>

<div style="max-width: 600px;">
    <!-- Back Link -->
    <a href="/admin/users" style="color: #94a3b8; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 24px;">
        ← Back to Users
    </a>

    <!-- Form Card -->
    <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 32px;">
        <h2 style="margin-bottom: 24px; font-size: 20px;"><?= $isEdit ? 'Edit User' : 'Add New User' ?></h2>

        <form method="POST">
            <?= csrf_field() ?>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px;">Name</label>
                <input type="text" name="name" value="<?= esc($edit_user['name'] ?? '') ?>" required
                    style="width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 12px 16px; color: #f1f5f9; font-size: 16px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px;">Email</label>
                <input type="email" name="email" value="<?= esc($edit_user['email'] ?? '') ?>" required
                    style="width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 12px 16px; color: #f1f5f9; font-size: 16px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px;">
                    Password <?= $isEdit ? '(leave empty to keep current)' : '' ?>
                </label>
                <input type="password" name="password" <?= $isEdit ? '' : 'required' ?>
                    style="width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 12px 16px; color: #f1f5f9; font-size: 16px;">
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px;">Role</label>
                <select name="role" required
                    style="width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 12px 16px; color: #f1f5f9; font-size: 16px;">
                    <option value="user" <?= ($edit_user['role'] ?? '') === 'user' ? 'selected' : '' ?>>User</option>
                    <option value="merchant" <?= ($edit_user['role'] ?? '') === 'merchant' ? 'selected' : '' ?>>Merchant</option>
                    <option value="admin" <?= ($edit_user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <?= $isEdit ? 'Update User' : 'Create User' ?>
                </button>
                <a href="/admin/users" class="btn btn-outline" style="flex: 1; text-align: center;">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
