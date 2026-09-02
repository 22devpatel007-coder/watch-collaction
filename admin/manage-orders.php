<?php
/**
 * Admin: Manage Orders
 * Watch Collection - College Project
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();

$page_title = 'Manage Orders';

const ORDER_STATUSES = ['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
const PAYMENT_STATUSES = ['Pending', 'Paid', 'Failed'];

/* ---------- Handle POST actions ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Invalid request. Please try again.');
        redirect('admin/manage-orders.php');
    }

    $action   = $_POST['action'] ?? '';
    $order_id = (int) ($_POST['order_id'] ?? 0);
    $redirectPath = 'admin/manage-orders.php?view=' . $order_id;

    if ($action === 'update_status') {
        $status = $_POST['status'] ?? '';
        if ($order_id > 0 && in_array($status, ORDER_STATUSES, true)) {
            $stmt = $pdo->prepare('UPDATE orders SET order_status = ? WHERE id = ?');
            $stmt->execute([$status, $order_id]);

            $log = $pdo->prepare('INSERT INTO order_status_history (order_id, status, updated_at) VALUES (?, ?, NOW())');
            $log->execute([$order_id, $status]);

            set_flash('success', 'Order status updated to ' . $status . '.');
        } else {
            set_flash('error', 'Invalid status update.');
        }
        redirect($redirectPath);
    }

    if ($action === 'update_payment') {
        $pstatus = $_POST['payment_status'] ?? '';
        if ($order_id > 0 && in_array($pstatus, PAYMENT_STATUSES, true)) {
            $stmt = $pdo->prepare(
                'UPDATE payments SET payment_status = ?, paid_at = IF(? = "Paid", NOW(), paid_at) WHERE order_id = ?'
            );
            $stmt->execute([$pstatus, $pstatus, $order_id]);
            set_flash('success', 'Payment status updated to ' . $pstatus . '.');
        } else {
            set_flash('error', 'Invalid payment status update.');
        }
        redirect($redirectPath);
    }
}

/* ---------- Single order view ---------- */
$view_order = null;
$order_items = [];
$payment = null;
$history = [];

if (isset($_GET['view'])) {
    $vid = (int) $_GET['view'];

    $stmt = $pdo->prepare(
        'SELECT o.*, u.full_name, u.email, u.phone
         FROM orders o JOIN users u ON u.id = o.user_id
         WHERE o.id = ?'
    );
    $stmt->execute([$vid]);
    $view_order = $stmt->fetch();

    if ($view_order) {
        $items = $pdo->prepare(
            'SELECT oi.*, w.name AS watch_name FROM order_items oi
             LEFT JOIN watches w ON w.id = oi.watch_id WHERE oi.order_id = ?'
        );
        $items->execute([$vid]);
        $order_items = $items->fetchAll();

        $pay = $pdo->prepare('SELECT * FROM payments WHERE order_id = ?');
        $pay->execute([$vid]);
        $payment = $pay->fetch();

        $hist = $pdo->prepare('SELECT * FROM order_status_history WHERE order_id = ? ORDER BY updated_at ASC');
        $hist->execute([$vid]);
        $history = $hist->fetchAll();
    }
}

/* ---------- List all orders ---------- */
$orders = $pdo->query(
    'SELECT o.id, o.order_number, o.total_amount, o.order_status, o.created_at, u.full_name
     FROM orders o JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC'
)->fetchAll();

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/admin-sidebar.php';
?>

<h1 class="page-title">Manage Orders</h1>

<?php if ($view_order): ?>
    <h2 class="page-subtitle">Order <?= htmlspecialchars($view_order['order_number'], ENT_QUOTES, 'UTF-8') ?></h2>

    <div class="admin-form">
        <p><strong>Customer:</strong> <?= htmlspecialchars($view_order['full_name'], ENT_QUOTES, 'UTF-8') ?>
            (<?= htmlspecialchars($view_order['email'], ENT_QUOTES, 'UTF-8') ?>,
            <?= htmlspecialchars($view_order['phone'], ENT_QUOTES, 'UTF-8') ?>)</p>
        <p><strong>Shipping Address:</strong><br><?= nl2br(htmlspecialchars($view_order['shipping_address'], ENT_QUOTES, 'UTF-8')) ?></p>
        <p><strong>Total:</strong> ₹<?= number_format((float) $view_order['total_amount'], 2) ?></p>
        <p><strong>Placed:</strong> <?= htmlspecialchars($view_order['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>

    <h3 class="page-subtitle">Items</h3>
    <table class="admin-table">
        <thead><tr><th>Watch</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
        <tbody>
        <?php foreach ($order_items as $it): ?>
            <tr>
                <td><?= htmlspecialchars($it['watch_name'] ?? 'Deleted watch', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int) $it['quantity'] ?></td>
                <td>₹<?= number_format((float) $it['price'], 2) ?></td>
                <td>₹<?= number_format((float) $it['subtotal'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h3 class="page-subtitle">Order Status</h3>
    <form method="POST" class="admin-form" action="<?= base_url('admin/manage-orders.php') ?>">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="order_id" value="<?= (int) $view_order['id'] ?>">
        <select name="status">
            <?php foreach (ORDER_STATUSES as $s): ?>
                <option value="<?= $s ?>" <?= $view_order['order_status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Update Status</button>
    </form>

    <?php if ($payment): ?>
        <h3 class="page-subtitle">Payment</h3>
        <p><strong>Method:</strong> <?= htmlspecialchars($payment['payment_method'], ENT_QUOTES, 'UTF-8') ?> |
           <strong>Amount:</strong> ₹<?= number_format((float) $payment['amount'], 2) ?> |
           <strong>Transaction ID:</strong> <?= htmlspecialchars($payment['transaction_id'] ?? '—', ENT_QUOTES, 'UTF-8') ?></p>
        <form method="POST" class="admin-form" action="<?= base_url('admin/manage-orders.php') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="update_payment">
            <input type="hidden" name="order_id" value="<?= (int) $view_order['id'] ?>">
            <select name="payment_status">
                <?php foreach (PAYMENT_STATUSES as $ps): ?>
                    <option value="<?= $ps ?>" <?= $payment['payment_status'] === $ps ? 'selected' : '' ?>><?= $ps ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Update Payment</button>
        </form>
    <?php endif; ?>

    <h3 class="page-subtitle">Status History</h3>
    <ul>
        <?php foreach ($history as $h): ?>
            <li><?= htmlspecialchars($h['status'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($h['updated_at'], ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>

    <a href="<?= base_url('admin/manage-orders.php') ?>" class="btn btn-secondary">Back To Orders</a>

<?php else: ?>

    <table class="admin-table">
        <thead>
            <tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if (empty($orders)): ?>
            <tr><td colspan="6">No orders found.</td></tr>
        <?php else: foreach ($orders as $o): ?>
            <tr>
                <td><?= htmlspecialchars($o['order_number'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($o['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>₹<?= number_format((float) $o['total_amount'], 2) ?></td>
                <td><?= htmlspecialchars($o['order_status'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($o['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><a href="<?= base_url('admin/manage-orders.php?view=' . (int) $o['id']) ?>" class="btn btn-secondary btn-sm">View</a></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

<?php endif; ?>

</main>
</div>
</body>
</html>