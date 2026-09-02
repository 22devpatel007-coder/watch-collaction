<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/user-auth.php';

require_user();
$user_id = $_SESSION['user_id'];

// Fetch cart items for summary display
$stmt = $pdo->prepare("SELECT c.id AS cart_id, c.quantity, w.id AS watch_id, w.name, w.brand,
                               w.price, w.stock, w.image
                        FROM cart c
                        JOIN watches w ON c.watch_id = w.id
                        WHERE c.user_id = ?
                        ORDER BY c.created_at DESC");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

if (count($cart_items) === 0) {
    set_flash('error', 'Your cart is empty.');
    redirect('user/cart.php');
}

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Prefill shipping address from profile
$u = $pdo->prepare("SELECT address FROM users WHERE id = ?");
$u->execute([$user_id]);
$user_address = (string) $u->fetchColumn();

$errors = [];
$shipping_address_input = $user_address;
$payment_method_input = 'COD';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request. Please try again.');
        redirect('user/checkout.php');
    }

    $shipping_address_input = trim($_POST['shipping_address'] ?? '');
    $payment_method_input = $_POST['payment_method'] ?? '';

    if ($shipping_address_input === '' || mb_strlen($shipping_address_input) < 10) {
        $errors[] = 'Please enter a complete shipping address (min 10 characters).';
    }
    if (!in_array($payment_method_input, ['COD', 'UPI', 'CARD'], true)) {
        $errors[] = 'Please select a valid payment method.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Lock cart + watch rows to prevent race conditions on stock
            $stmt = $pdo->prepare("SELECT c.id AS cart_id, c.quantity, w.id AS watch_id,
                                           w.price, w.stock, w.status
                                    FROM cart c
                                    JOIN watches w ON c.watch_id = w.id
                                    WHERE c.user_id = ?
                                    FOR UPDATE");
            $stmt->execute([$user_id]);
            $items = $stmt->fetchAll();

            if (count($items) === 0) {
                throw new RuntimeException('Your cart is empty.');
            }

            $total = 0;
            foreach ($items as $it) {
                if ($it['status'] !== 'active') {
                    throw new RuntimeException('One of the items in your cart is no longer available.');
                }
                if ($it['quantity'] > $it['stock']) {
                    throw new RuntimeException('One of the items in your cart exceeds available stock.');
                }
                $total += $it['price'] * $it['quantity'];
            }

            // Generate a unique order number
            do {
                $order_number = 'ORD' . date('Ymd') . strtoupper(bin2hex(random_bytes(4)));
                $chk = $pdo->prepare("SELECT id FROM orders WHERE order_number = ?");
                $chk->execute([$order_number]);
            } while ($chk->fetch());

            $ins = $pdo->prepare("INSERT INTO orders
                (user_id, order_number, total_amount, payment_method, order_status, shipping_address, created_at)
                VALUES (?, ?, ?, ?, 'Pending', ?, NOW())");
            $ins->execute([$user_id, $order_number, $total, $payment_method_input, $shipping_address_input]);
            $order_id = $pdo->lastInsertId();

            $item_ins = $pdo->prepare("INSERT INTO order_items (order_id, watch_id, quantity, price, subtotal)
                                        VALUES (?, ?, ?, ?, ?)");
            $stock_upd = $pdo->prepare("UPDATE watches SET stock = stock - ? WHERE id = ? AND stock >= ?");

            foreach ($items as $it) {
                $item_subtotal = $it['price'] * $it['quantity'];
                $item_ins->execute([$order_id, $it['watch_id'], $it['quantity'], $it['price'], $item_subtotal]);

                $stock_upd->execute([$it['quantity'], $it['watch_id'], $it['quantity']]);
                if ($stock_upd->rowCount() === 0) {
                    throw new RuntimeException('Stock changed while placing your order. Please try again.');
                }
            }

            $pay_ins = $pdo->prepare("INSERT INTO payments
                (order_id, payment_method, amount, payment_status, transaction_id, paid_at)
                VALUES (?, ?, ?, 'Pending', NULL, NULL)");
            $pay_ins->execute([$order_id, $payment_method_input, $total]);

            $hist_ins = $pdo->prepare("INSERT INTO order_status_history (order_id, status, updated_at)
                                        VALUES (?, 'Pending', NOW())");
            $hist_ins->execute([$order_id]);

            $del_cart = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $del_cart->execute([$user_id]);

            $pdo->commit();

            redirect('user/order-success.php?order=' . urlencode($order_number));
        } catch (RuntimeException $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('Checkout error: ' . $e->getMessage());
            $errors[] = 'Something went wrong while placing your order. Please try again.';
        }
    }
}

$page_title = 'Checkout - ' . SITE_NAME;
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<style>
.checkout-wrap { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
.checkout-wrap h1 { font-family: 'DM Serif Display', serif; color: var(--color-text-primary); margin-bottom: 24px; }
.checkout-layout { display: grid; grid-template-columns: 1fr 320px; gap: 32px; align-items: start; }
.checkout-form { background: var(--color-card); border: 1px solid var(--color-border); border-radius: 12px; padding: 24px; }
.checkout-form label { display: block; font-weight: 600; margin-bottom: 6px; color: var(--color-text-primary); }
.checkout-form textarea { width: 100%; padding: 10px; border: 1px solid var(--color-border); border-radius: 12px; font-family: inherit; margin-bottom: 20px; resize: vertical; }
.payment-options { display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px; }
.payment-option { display: flex; align-items: center; gap: 10px; border: 1px solid var(--color-border); border-radius: 12px; padding: 12px 14px; cursor: pointer; }
.payment-option input { margin: 0; }
.checkout-summary { background: var(--color-card); border: 1px solid var(--color-border); border-radius: 12px; padding: 20px; position: sticky; top: 90px; }
.checkout-summary h2 { font-family: 'DM Serif Display', serif; font-size: 1.3rem; margin-bottom: 16px; }
.summary-item { display: flex; justify-content: space-between; font-size: 0.9rem; color: var(--color-text-secondary); margin-bottom: 6px; }
.summary-total { display: flex; justify-content: space-between; font-weight: 700; font-size: 1.1rem; color: var(--color-text-primary); border-top: 1px solid var(--color-border); padding-top: 12px; margin-top: 12px; }
.btn-place-order { display: block; width: 100%; text-align: center; background: var(--color-primary); color: #fff; border: none; padding: 12px; border-radius: 12px; font-weight: 600; cursor: pointer; margin-top: 4px; }
.btn-place-order:hover { background: var(--color-accent); }
@media (max-width: 767px) { .checkout-layout { grid-template-columns: 1fr; } }
</style>

<div class="checkout-wrap">
    <h1>Checkout</h1>

    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($err); ?></div>
    <?php endforeach; ?>

    <form method="POST" action="<?php echo base_url('user/checkout.php'); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

        <div class="checkout-layout">
            <div class="checkout-form">
                <label for="shipping_address">Shipping Address</label>
                <textarea id="shipping_address" name="shipping_address" rows="4"
                          placeholder="Full name, address line, city, state, PIN code, phone"
                          required minlength="10"><?php echo htmlspecialchars($shipping_address_input); ?></textarea>

                <label>Payment Method</label>
                <div class="payment-options">
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="COD"
                               <?php echo $payment_method_input === 'COD' ? 'checked' : ''; ?>>
                        Cash On Delivery
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="UPI"
                               <?php echo $payment_method_input === 'UPI' ? 'checked' : ''; ?>>
                        UPI
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="CARD"
                               <?php echo $payment_method_input === 'CARD' ? 'checked' : ''; ?>>
                        Card Payment
                    </label>
                </div>
            </div>

            <div class="checkout-summary">
                <h2>Order Summary</h2>
                <?php foreach ($cart_items as $item): ?>
                    <div class="summary-item">
                        <span><?php echo htmlspecialchars($item['name']); ?> × <?php echo (int) $item['quantity']; ?></span>
                        <span>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="summary-total">
                    <span>Total</span>
                    <span>₹<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <button type="submit" class="btn-place-order">Confirm Order</button>
            </div>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>