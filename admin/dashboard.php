<?php
/**
 * Admin Dashboard
 * Watch Collection - College Project
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();

$page_title = 'Dashboard';

$stats = [
    'watches'         => $pdo->query('SELECT COUNT(*) FROM watches')->fetchColumn(),
    'categories'      => $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
    'orders'          => $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'users'           => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'messages'        => $pdo->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn(),
    'pending_orders'  => $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Pending'")->fetchColumn(),
];

require __DIR__ . '/../includes/admin-header.php';
require __DIR__ . '/../includes/admin-sidebar.php';
?>

<h1 style="font-family: var(--font-heading); margin-bottom: 24px;">Dashboard</h1>

<div class="admin-stats">
    <div class="admin-stat-card">
        <div class="admin-stat-card__value"><?= (int) $stats['watches'] ?></div>
        <div>Total Watches</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-card__value"><?= (int) $stats['categories'] ?></div>
        <div>Total Categories</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-card__value"><?= (int) $stats['orders'] ?></div>
        <div>Total Orders</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-card__value"><?= (int) $stats['users'] ?></div>
        <div>Total Users</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-card__value"><?= (int) $stats['messages'] ?></div>
        <div>Total Messages</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-card__value"><?= (int) $stats['pending_orders'] ?></div>
        <div>Pending Orders</div>
    </div>
</div>

</main>
</div>
</body>
</html>