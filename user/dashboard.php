<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/user-auth.php';

require_user();
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT full_name, email, phone, address, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // Account no longer exists (e.g. deactivated/removed) - force logout
    redirect('logout.php');
}

$stmt = $pdo->prepare("SELECT order_number, total_amount, order_status, created_at
                        FROM orders WHERE user_id = ?
                        ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll();

$page_title = 'My Dashboard';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<style>
.dash-wrap { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
.dash-wrap h1 { font-family: 'DM Serif Display', serif; color: var(--color-text-primary); margin-bottom: 24px; }
.dash-layout { display: grid; grid-template-columns: 320px 1fr; gap: 32px; align-items: start; }
.dash-card { background: var(--color-card); border: 1px solid var(--color-border); border-radius: 12px; padding: 20px; }
.dash-card h2 { font-family: 'DM Serif Display', serif; font-size: 1.2rem; margin-bottom: 16px; }
.info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--color-border); font-size: 0.9rem; }
.info-row:last-child { border-bottom: none; }
.info-row span:first-child { color: var(--color-text-secondary); }
.dash-nav { display: flex; flex-direction: column; gap: 10px; margin-top: 16px; }
.dash-nav a { text-align: center; padding: 10px; border-radius: 12px; border: 1px solid var(--color-primary); color: var(--color-primary); text-decoration: none; font-weight: 600; }
.dash-nav a:hover { background: var(--color-primary); color: #fff; }
.order-row { display: grid; grid-template-columns: 1fr auto auto; gap: 12px; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--color-border); font-size: 0.9rem; }
.order-row:last-child { border-bottom: none; }
.status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; background: var(--color-bg); color: var(--color-text-secondary); }
.no-orders { color: var(--color-text-secondary); padding: 20px 0; }
.view-all { display: inline-block; margin-top: 12px; color: var(--color-accent); text-decoration: none; font-weight: 600; }
@media (max-width: 767px) { .dash-layout { grid-template-columns: 1fr; } }
</style>

<div class="dash-wrap">
    <h1>Welcome, <?= htmlspecialchars($user['full_name']) ?></h1>

    <div class="dash-layout">
        <div class="dash-card">
            <h2>Account Information</h2>
            <div class="info-row"><span>Name</span><span><?= htmlspecialchars($user['full_name']) ?></span></div>
            <div class="info-row"><span>Email</span><span><?= htmlspecialchars($user['email']) ?></span></div>
            <div class="info-row"><span>Phone</span><span><?= htmlspecialchars($user['phone'] ?: '-') ?></span></div>
            <div class="info-row"><span>Member Since</span><span><?= htmlspecialchars(date('d M Y', strtotime($user['created_at']))) ?></span></div>

            <div class="dash-nav">
                <a href="<?= base_url('user/profile.php') ?>">Edit Profile</a>
                <a href="<?= base_url('user/orders.php') ?>">My Orders</a>
                <a href="<?= base_url('user/cart.php') ?>">My Cart</a>
            </div>
        </div>

        <div class="dash-card">
            <h2>Recent Orders</h2>
            <?php if (count($recent_orders) === 0): ?>
                <p class="no-orders">You haven't placed any orders yet.</p>
                <a href="<?= base_url('browse-watches.php') ?>" class="btn-primary">Browse Watches</a>
            <?php else: ?>
                <?php foreach ($recent_orders as $o): ?>
                    <div class="order-row">
                        <a href="<?= base_url('user/order-details.php?order=' . urlencode($o['order_number'])) ?>">
                            <?= htmlspecialchars($o['order_number']) ?>
                        </a>
                        <span>₹<?= number_format($o['total_amount'], 2) ?></span>
                        <span class="status-badge"><?= htmlspecialchars($o['order_status']) ?></span>
                    </div>
                <?php endforeach; ?>
                <a href="<?= base_url('user/orders.php') ?>" class="view-all">View All Orders →</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>