<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/user-auth.php';

require_user();
$user_id = $_SESSION['user_id'];

$order_number = trim($_GET['order'] ?? '');

$stmt = $pdo->prepare("SELECT id, order_number, total_amount, payment_method, order_status, created_at
                        FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->execute([$order_number, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'Order not found.');
    redirect('user/orders.php');
}

$page_title = 'Order Confirmed - ' . SITE_NAME;
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<style>
.success-wrap { max-width: 600px; margin: 60px auto; padding: 0 20px; text-align: center; }
.success-icon { width: 64px; height: 64px; border-radius: 50%; background: #16A34A; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 20px; }
.success-wrap h1 { font-family: 'DM Serif Display', serif; color: var(--color-text-primary); margin-bottom: 8px; }
.success-card { background: var(--color-card); border: 1px solid var(--color-border); border-radius: 12px; padding: 24px; margin: 24px 0; text-align: left; }
.success-row { display: flex; justify-content: space-between; margin-bottom: 10px; color: var(--color-text-secondary); }
.success-row strong { color: var(--color-text-primary); }
.success-actions { display: flex; gap: 12px; justify-content: center; margin-top: 20px; }
</style>

<div class="success-wrap">
    <div class="success-icon">&#10003;</div>
    <h1>Order Confirmed!</h1>
    <p style="color:var(--color-text-secondary);">Thank you for your order. We'll start processing it shortly.</p>

    <div class="success-card">
        <div class="success-row"><span>Order Number</span><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></div>
        <div class="success-row"><span>Total Amount</span><strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong></div>
        <div class="success-row"><span>Payment Method</span><strong><?php echo htmlspecialchars($order['payment_method']); ?></strong></div>
        <div class="success-row"><span>Status</span><strong><?php echo htmlspecialchars($order['order_status']); ?></strong></div>
    </div>

    <div class="success-actions">
        <a href="<?php echo base_url('user/order-details.php?id=' . (int) $order['id']); ?>" class="btn-primary">View Order Details</a>
        <a href="<?php echo base_url('browse-watches.php'); ?>" class="btn-secondary">Continue Shopping</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>