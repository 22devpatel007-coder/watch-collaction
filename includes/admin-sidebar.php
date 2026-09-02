<?php
/**
 * Admin Sidebar Navigation
 * Watch Collection - College Project
 */
?>
<aside class="admin-sidebar">
    <nav class="admin-sidebar__menu">
        <a href="<?= base_url('admin/dashboard.php') ?>">Dashboard</a>
        <a href="<?= base_url('admin/manage-watches.php') ?>">Manage Watches</a>
        <a href="<?= base_url('admin/manage-categories.php') ?>">Manage Categories</a>
        <a href="<?= base_url('admin/manage-orders.php') ?>">Manage Orders</a>
        <a href="<?= base_url('admin/manage-users.php') ?>">Manage Users</a>
        <a href="<?= base_url('admin/manage-messages.php') ?>">Manage Messages</a>
        <a href="<?= base_url('admin/logout.php') ?>">Logout</a>
    </nav>
</aside>

<main class="admin-content">