<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('sidebar') ?>
<?= $this->include('admin/partials/sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Flash Messages -->
<?php if (session()->getFlashdata('success')): ?>
    <div style="background: #16a34a20; border: 1px solid #16a34a; color: #4ade80; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div style="background: #ef444420; border: 1px solid #ef4444; color: #f87171; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
        <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>

<!-- Header Actions -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <!-- Search -->
        <form method="GET" style="display: flex; gap: 8px;">
            <input type="text" name="search" value="<?= esc($search ?? '') ?>" placeholder="Search users..." 
                style="background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 10px 16px; color: #f1f5f9; width: 200px;">
            <select name="role" style="background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 10px 16px; color: #f1f5f9;">
                <option value="">All Roles</option>
                <option value="admin" <?= ($role_filter ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="user" <?= ($role_filter ?? '') === 'user' ? 'selected' : '' ?>>User</option>
                <option value="merchant" <?= ($role_filter ?? '') === 'merchant' ? 'selected' : '' ?>>Merchant</option>
            </select>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>
    <a href="/admin/users/create" class="btn btn-primary">➕ Add User</a>
</div>

<!-- Users Table -->
<div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; overflow: hidden;">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #0f172a;">
                    <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: #94a3b8; font-size: 13px;">ID</th>
                    <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: #94a3b8; font-size: 13px;">Name</th>
                    <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: #94a3b8; font-size: 13px;">Email</th>
                    <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: #94a3b8; font-size: 13px;">Role</th>
                    <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: #94a3b8; font-size: 13px;">Joined</th>
                    <th style="padding: 16px 24px; text-align: center; font-weight: 600; color: #94a3b8; font-size: 13px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $u): ?>
                    <tr style="border-bottom: 1px solid #334155;">
                        <td style="padding: 16px 24px; color: #94a3b8;">#<?= $u['id'] ?></td>
                        <td style="padding: 16px 24px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                </div>
                                <?= esc($u['name']) ?>
                            </div>
                        </td>
                        <td style="padding: 16px 24px; color: #94a3b8;"><?= esc($u['email']) ?></td>
                        <td style="padding: 16px 24px;">
                            <?php 
                            $roleColors = ['admin' => '#ef4444', 'merchant' => '#f97316', 'user' => '#3b82f6'];
                            $color = $roleColors[$u['role']] ?? '#64748b';
                            ?>
                            <span style="background: <?= $color ?>; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; text-transform: capitalize;"><?= $u['role'] ?></span>
                        </td>
                        <td style="padding: 16px 24px; color: #94a3b8;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        <td style="padding: 16px 24px; text-align: center;">
                            <div style="display: flex; gap: 8px; justify-content: center;">
                                <a href="/admin/users/edit/<?= $u['id'] ?>" style="background: #3b82f6; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px;">Edit</a>
                                <?php if ($u['id'] != $user['id']): ?>
                                <a href="/admin/users/delete/<?= $u['id'] ?>" onclick="return confirm('Hapus user ini?')" style="background: #ef4444; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px;">Delete</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="padding: 40px; text-align: center; color: #64748b;">No users found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Summary -->
<div style="margin-top: 16px; color: #64748b; font-size: 14px;">
    Total: <?= count($users ?? []) ?> users
</div>
<?= $this->endSection() ?>
