<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/user-auth.php';

require_user();

$user_id = $_SESSION['user_id'];

// Handle POST actions: update quantity / remove item
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request. Please try again.');
        redirect('user/cart.php');
    }

    $cart_id = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT);

    if ($cart_id) {
        // Ownership check on every action
        $own = $pdo->prepare("SELECT c.id, c.watch_id, w.stock
                               FROM cart c
                               JOIN watches w ON c.watch_id = w.id
                               WHERE c.id = ? AND c.user_id = ?");
        $own->execute([$cart_id, $user_id]);
        $item = $own->fetch();

        if ($item) {
            if (isset($_POST['remove'])) {
                $del = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                $del->execute([$cart_id, $user_id]);
                set_flash('success', 'Item removed from cart.');
            } elseif (isset($_POST['update_qty'])) {
                $qty = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
                if ($qty && $qty > 0) {
                    $qty = min($qty, (int)$item['stock']);
                    $upd = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                    $upd->execute([$qty, $cart_id, $user_id]);
                    set_flash('success', 'Cart updated.');
                } else {
                    set_flash('error', 'Invalid quantity.');
                }
            }
        } else {
            set_flash('error', 'Item not found in your cart.');
        }
    }

    redirect('user/cart.php');
}

// Fetch cart items
$stmt = $pdo->prepare("SELECT c.id AS cart_id, c.quantity, w.id AS watch_id, w.name, w.brand,
                               w.price, w.stock, w.image
                        FROM cart c
                        JOIN watches w ON c.watch_id = w.id
                        WHERE c.user_id = ?
                        ORDER BY c.created_at DESC");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$page_title = 'My Cart - ' . SITE_NAME;
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<style>
.cart-wrap { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
.cart-wrap h1 { font-family: 'DM Serif Display', serif; color: var(--color-text-primary); margin-bottom: 24px; }
.cart-empty { text-align: center; padding: 60px 20px; color: var(--color-text-secondary); }
.cart-layout { display: grid; grid-template-columns: 1fr 320px; gap: 32px; align-items: start; }
.cart-item { display: grid; grid-template-columns: 90px 1fr auto; gap: 16px; align-items: center; background: var(--color-card); border: 1px solid var(--color-border); border-radius: 12px; padding: 16px; margin-bottom: 12px; }
.cart-item img { width: 90px; height: 90px; object-fit: cover; border-radius: 8px; }
.cart-item-img-placeholder { width: 90px; height: 90px; background: var(--color-bg); border-radius: 8px; }
.cart-item-name { font-weight: 600; color: var(--color-text-primary); }
.cart-item-brand { font-size: 0.85rem; color: var(--color-text-secondary); }
.cart-item-price { color: var(--color-primary); font-weight: 600; margin-top: 4px; }
.cart-item-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }
.qty-form { display: flex; align-items: center; gap: 8px; }
.qty-form input[type="number"] { width: 60px; padding: 6px; border: 1px solid var(--color-border); border-radius: 8px; }
.qty-btn { background: var(--color-primary); color: #fff; border: none; padding: 6px 12px; border-radius: 8px; font-size: 0.85rem; cursor: pointer; }
.qty-btn:hover { background: var(--color-accent); }
.remove-btn { background: none; border: none; color: #DC2626; font-size: 0.85rem; cursor: pointer; text-decoration: underline; padding: 0; }
.cart-summary { background: var(--color-card); border: 1px solid var(--color-border); border-radius: 12px; padding: 20px; position: sticky; top: 90px; }
.cart-summary h2 { font-family: 'DM Serif Display', serif; font-size: 1.3rem; margin-bottom: 16px; }
.summary-row { display: flex; justify-content: space-between; margin-bottom: 8px; color: var(--color-text-secondary); }
.summary-total { display: flex; justify-content: space-between; font-weight: 700; font-size: 1.1rem; color: var(--color-text-primary); border-top: 1px solid var(--color-border); padding-top: 12px; margin-top: 12px; }
.btn-checkout { display: block; width: 100%; text-align: center; background: var(--color-primary); color: #fff; border: none; padding: 12px; border-radius: 12px; font-weight: 600; cursor: pointer; margin-top: 16px; text-decoration: none; }
.btn-checkout:hover { background: var(--color-accent); }
@media (max-width: 767px) {
    .cart-layout { grid-template-columns: 1fr; }
    .cart-item { grid-template-columns: 70px 1fr; }
    .cart-item-actions { grid-column: 1 / -1; flex-direction: row; justify-content: space-between; align-items: center; }
}
</style>

<div class="cart-wrap">
    <h1>My Cart</h1>

    <?php $flash = get_flash(); if ($flash): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
    <?php endif; ?>

    <?php if (count($cart_items) === 0): ?>
        <div class="cart-empty">
            <p>Your cart is empty.</p>
            <a href="<?php echo base_url('browse-watches.php'); ?>" class="btn-primary">Browse Watches</a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <?php if (!empty($item['image'])): ?>
                            <img src="<?php echo base_url('assets/uploads/watches/' . htmlspecialchars($item['image'])); ?>"
                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <?php else: ?>
                            <div class="cart-item-img-placeholder"></div>
                        <?php endif; ?>

                        <div>
                            <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="cart-item-brand"><?php echo htmlspecialchars($item['brand']); ?></div>
                            <div class="cart-item-price">₹<?php echo number_format($item['price'], 2); ?></div>
                            <?php if ($item['stock'] < $item['quantity']): ?>
                                <div style="color:#DC2626;font-size:0.8rem;">Only <?php echo (int)$item['stock']; ?> left in stock</div>
                            <?php endif; ?>
                        </div>

                        <div class="cart-item-actions">
                            <form class="qty-form" method="POST" action="<?php echo base_url('user/cart.php'); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>"
                                       min="1" max="<?php echo (int)$item['stock']; ?>">
                                <button type="submit" name="update_qty" class="qty-btn">Update</button>
                            </form>
                            <form method="POST" action="<?php echo base_url('user/cart.php'); ?>"
                                  onsubmit="return confirm('Remove this item from cart?');">
                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                <button type="submit" name="remove" class="remove-btn">Remove</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <h2>Order Summary</h2>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>₹<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-total">
                    <span>Total</span>
                    <span>₹<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <a href="<?php echo base_url('user/checkout.php'); ?>" class="btn-checkout">Proceed To Checkout</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.qty-form').forEach(function (form) {
    var input = form.querySelector('input[type="number"]');
    var max = parseInt(input.getAttribute('max'), 10);
    form.addEventListener('submit', function (e) {
        var val = parseInt(input.value, 10);
        if (!val || val < 1) {
            e.preventDefault();
            alert('Quantity must be at least 1.');
        } else if (val > max) {
            e.preventDefault();
            alert('Only ' + max + ' in stock.');
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>