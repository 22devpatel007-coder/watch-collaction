<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/user-auth.php';

require_user();
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, order_number, total_amount, payment_method, order_status, created_at
                        FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

$page_title = 'My Orders - ' . SITE_NAME;
require_once '../includes/header.php';
require_once '../includes/navbar.php';

$status_colors = [
    'Pending'    => '#D97706',
    'Confirmed'  => '#2563EB',
    'Processing' => '#7C3AED',
    'Shipped'    => '#0891B2',
    'Delivered'  => '#16A34A',
    'Cancelled'  => '#DC2626',
];
?>

<style>
.orders-wrap { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
.orders-wrap h1 { font-family: 'DM Serif Display', serif; color: var(--color-text-primary); margin-bottom: 24px; }
.orders-empty { text-align: center; padding: 60px 20px; color: var(--color-text-secondary); }
.order-row { display: flex; justify-content: space-between; align-items: center; background: var(--color-card); border: 1px solid var(--color-border); border-radius: 12px; padding: 16px 20px; margin-bottom: 12px; flex-wrap: wrap; gap: 10px; }
.order-row-main strong { color: var(--color-text-primary); display: block; margin-bottom: 4px; }
.order-row-main span { color: var(--color-text-secondary); font-size: 0.85rem; }
.order-status-badge { padding: 4px 12px; border-radius: 999px; color: #fff; font-size: 0.8rem; font-weight: 600; }
.order-row-link { color: var(--color-accent); text-decoration: none; font-weight: 600; font-size: 0.9rem; }
.order-row-link:hover { text-decoration: underline; }
</style>

<div class="orders-wrap">
    <h1>My Orders</h1>

    <?php $flash = get_flash(); if ($flash): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
    <?php endif; ?>

    <?php if (count($orders) === 0): ?>
        <div class="orders-empty">
            <p>You haven't placed any orders yet.</p>
            <a href="<?php echo base_url('browse-watches.php'); ?>" class="btn-primary">Browse Watches</a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="order-row">
                <div class="order-row-main">
                    <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong>
                    <span><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?> &middot; ₹<?php echo number_format($order['total_amount'], 2); ?> &middot; <?php echo htmlspecialchars($order['payment_method']); ?></span>
                </div>
                <span class="order-status-badge" style="background: <?php echo $status_colors[$order['order_status']] ?? '#6B7280'; ?>;">
                    <?php echo htmlspecialchars($order['order_status']); ?>
                </span>
                <a class="order-row-link" href="<?php echo base_url('user/order-details.php?id=' . (int) $order['id']); ?>">View Details</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>