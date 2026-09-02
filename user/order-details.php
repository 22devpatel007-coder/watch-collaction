<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/user-auth.php';

require_user();
$user_id = $_SESSION['user_id'];

$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$order_id) {
    redirect('user/orders.php');
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'Order not found.');
    redirect('user/orders.php');
}

$items_stmt = $pdo->prepare("SELECT oi.quantity, oi.price, oi.subtotal, w.id AS watch_id, w.name, w.brand, w.image
                              FROM order_items oi
                              JOIN watches w ON oi.watch_id = w.id
                              WHERE oi.order_id = ?");
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll();

$hist_stmt = $pdo->prepare("SELECT status, updated_at FROM order_status_history WHERE order_id = ? ORDER BY updated_at ASC");
$hist_stmt->execute([$order_id]);
$history = $hist_stmt->fetchAll();

$page_title = 'Order Details - ' . SITE_NAME;
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
.order-details-wrap { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
.order-details-wrap h1 { font-family: 'DM Serif Display', serif; color: var(--color-text-primary); margin-bottom: 4px; }
.order-meta { color: var(--color-text-secondary); margin-bottom: 24px; }
.order-status-badge { padding: 4px 12px; border-radius: 999px; color: #fff; font-size: 0.8rem; font-weight: 600; }
.details-layout { display: grid; grid-template-columns: 1fr 320px; gap: 32px; align-items: start; }
.details-card { background: var(--color-card); border: 1px solid var(--color-border); border-radius: 12px; padding: 20px; margin-bottom: 20px; }
.details-card h2 { font-family: 'DM Serif Display', serif; font-size: 1.2rem; margin-bottom: 16px; }
.order-item-row { display: grid; grid-template-columns: 60px 1fr auto; gap: 12px; align-items: center; margin-bottom: 14px; }
.order-item-row img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
.order-item-img-placeholder { width: 60px; height: 60px; background: var(--color-bg); border-radius: 8px; }
.order-item-name { font-weight: 600; color: var(--color-text-primary); }
.order-item-brand { font-size: 0.8rem; color: var(--color-text-secondary); }
.order-item-qty { font-size: 0.85rem; color: var(--color-text-secondary); }
.timeline { list-style: none; padding: 0; margin: 0; }
.timeline li { position: relative; padding-left: 24px; padding-bottom: 18px; border-left: 2px solid var(--color-border); margin-left: 6px; }
.timeline li:last-child { border-left: 2px solid transparent; }
.timeline li::before { content: ''; position: absolute; left: -7px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: var(--color-accent); }
.timeline-status { font-weight: 600; color: var(--color-text-primary); }
.timeline-date { font-size: 0.8rem; color: var(--color-text-secondary); }
.summary-total { display: flex; justify-content: space-between; font-weight: 700; font-size: 1.1rem; color: var(--color-text-primary); border-top: 1px solid var(--color-border); padding-top: 12px; margin-top: 4px; }
@media (max-width: 767px) { .details-layout { grid-template-columns: 1fr; } }
</style>

<div class="order-details-wrap">
    <h1>Order #<?php echo htmlspecialchars($order['order_number']); ?></h1>
    <p class="order-meta">
        Placed on <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?> &middot;
        <span class="order-status-badge" style="background: <?php echo $status_colors[$order['order_status']] ?? '#6B7280'; ?>;">
            <?php echo htmlspecialchars($order['order_status']); ?>
        </span>
    </p>

    <div class="details-layout">
        <div>
            <div class="details-card">
                <h2>Items</h2>
                <?php foreach ($items as $item): ?>
                    <div class="order-item-row">
                        <?php if (!empty($item['image'])): ?>
                            <img src="<?php echo base_url('assets/uploads/watches/' . htmlspecialchars($item['image'])); ?>"
                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <?php else: ?>
                            <div class="order-item-img-placeholder"></div>
                        <?php endif; ?>
                        <div>
                            <div class="order-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="order-item-brand"><?php echo htmlspecialchars($item['brand']); ?></div>
                            <div class="order-item-qty">Qty: <?php echo (int) $item['quantity']; ?> × ₹<?php echo number_format($item['price'], 2); ?></div>
                        </div>
                        <div><strong>₹<?php echo number_format($item['subtotal'], 2); ?></strong></div>
                    </div>
                <?php endforeach; ?>
                <div class="summary-total">
                    <span>Total</span>
                    <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>

            <div class="details-card">
                <h2>Shipping Address</h2>
                <p style="white-space: pre-line; color: var(--color-text-secondary);"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
            </div>
        </div>

        <div>
            <div class="details-card">
                <h2>Payment</h2>
                <p style="color: var(--color-text-secondary);">Method: <?php echo htmlspecialchars($order['payment_method']); ?></p>
            </div>

            <div class="details-card">
                <h2>Order Status</h2>
                <ul class="timeline">
                    <?php foreach ($history as $h): ?>
                        <li>
                            <div class="timeline-status"><?php echo htmlspecialchars($h['status']); ?></div>
                            <div class="timeline-date"><?php echo date('d M Y, h:i A', strtotime($h['updated_at'])); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>