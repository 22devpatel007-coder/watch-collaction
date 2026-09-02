<?php
require_once 'includes/config.php';
require_once 'includes/session.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/user-auth.php';

$watch_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$watch_id) {
    redirect('browse-watches.php');
}

// Fetch watch details (active only)
$stmt = $pdo->prepare("SELECT w.*, c.name AS category_name
                        FROM watches w
                        LEFT JOIN categories c ON w.category_id = c.id
                        WHERE w.id = ? AND w.status = 'active'");
$stmt->execute([$watch_id]);
$watch = $stmt->fetch();

if (!$watch) {
    redirect('browse-watches.php');
}

// Handle Add to Cart (logged-in users only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!is_logged_in()) {
        redirect('login.php');
    }

    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request. Please try again.');
        redirect('watch-details.php?id=' . $watch_id);
    }

    $qty = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $qty = ($qty && $qty > 0) ? $qty : 1;

    if ($watch['stock'] < 1) {
        set_flash('error', 'This watch is out of stock.');
        redirect('watch-details.php?id=' . $watch_id);
    }

    $user_id = $_SESSION['user_id'];

    // Check if already in cart -> update quantity, else insert
    $check = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND watch_id = ?");
    $check->execute([$user_id, $watch_id]);
    $existing = $check->fetch();

    if ($existing) {
        $new_qty = min($existing['quantity'] + $qty, $watch['stock']);
        $update = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $update->execute([$new_qty, $existing['id']]);
    } else {
        $qty = min($qty, $watch['stock']);
        $insert = $pdo->prepare("INSERT INTO cart (user_id, watch_id, quantity) VALUES (?, ?, ?)");
        $insert->execute([$user_id, $watch_id, $qty]);
    }

    set_flash('success', 'Added to cart.');
    redirect('watch-details.php?id=' . $watch_id);
}
// Handle Buy Now (logged-in users only) — add to cart then go straight to checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_now'])) {
    if (!is_logged_in()) {
        redirect('login.php');
    }

    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request. Please try again.');
        redirect('watch-details.php?id=' . $watch_id);
    }

    $qty = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $qty = ($qty && $qty > 0) ? $qty : 1;

    if ($watch['stock'] < 1) {
        set_flash('error', 'This watch is out of stock.');
        redirect('watch-details.php?id=' . $watch_id);
    }

    $user_id = $_SESSION['user_id'];

    $check = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND watch_id = ?");
    $check->execute([$user_id, $watch_id]);
    $existing = $check->fetch();

    if ($existing) {
        $new_qty = min($existing['quantity'] + $qty, $watch['stock']);
        $update = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $update->execute([$new_qty, $existing['id']]);
    } else {
        $qty = min($qty, $watch['stock']);
        $insert = $pdo->prepare("INSERT INTO cart (user_id, watch_id, quantity) VALUES (?, ?, ?)");
        $insert->execute([$user_id, $watch_id, $qty]);
    }

    redirect('user/checkout.php');
}
// Fetch reviews
$rstmt = $pdo->prepare("SELECT r.rating, r.review, r.created_at, u.full_name
                         FROM reviews r
                         JOIN users u ON r.user_id = u.id
                         WHERE r.watch_id = ?
                         ORDER BY r.created_at DESC");
$rstmt->execute([$watch_id]);
$reviews = $rstmt->fetchAll();

$avg_rating = 0;
if (count($reviews) > 0) {
    $avg_rating = round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1);
}

$page_title = htmlspecialchars($watch['name']) . ' - ' . SITE_NAME;
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<style>
.details-wrap { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
.details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
.details-image { background: var(--color-card); border: 1px solid var(--color-border); border-radius: 12px; overflow: hidden; }
.details-image img { width: 100%; height: 100%; object-fit: cover; display: block; }
.details-brand { color: var(--color-accent); font-family: 'Inter', sans-serif; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
.details-name { font-family: 'DM Serif Display', serif; font-size: 2rem; color: var(--color-text-primary); margin: 8px 0; }
.details-price { font-size: 1.6rem; color: var(--color-primary); font-weight: 700; margin: 12px 0; }
.details-rating { color: #F59E0B; margin-bottom: 12px; }
.details-desc { color: var(--color-text-secondary); line-height: 1.7; margin: 16px 0; }
.details-specs { background: var(--color-card); border: 1px solid var(--color-border); border-radius: 12px; padding: 16px 20px; margin: 16px 0; white-space: pre-line; color: var(--color-text-secondary); }
.details-stock { font-size: 0.9rem; margin-bottom: 16px; }
.in-stock { color: #16A34A; }
.out-stock { color: #DC2626; }
.cart-form { display: flex; gap: 12px; align-items: center; margin-top: 20px; }
.cart-form input[type="number"] { width: 70px; padding: 10px; border: 1px solid var(--color-border); border-radius: 12px; }
.btn-primary { background: var(--color-primary); color: #fff; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: background 0.3s; text-decoration: none; display: inline-block; }
.btn-primary:hover { background: var(--color-accent); }
.btn-disabled { background: #E5E7EB; color: #9CA3AF; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: not-allowed; }
.btn-secondary { background: #fff; color: var(--color-primary); border: 1px solid var(--color-primary); padding: 12px 24px; border-radius: 12px; font-weight: 600; text-decoration: none; display: inline-block; }
.reviews-section { margin-top: 60px; }
.reviews-section h2 { font-family: 'DM Serif Display', serif; margin-bottom: 20px; }
.review-card { background: var(--color-card); border: 1px solid var(--color-border); border-radius: 12px; padding: 16px 20px; margin-bottom: 12px; }
.review-meta { font-size: 0.85rem; color: var(--color-text-secondary); margin-bottom: 6px; }
.no-reviews { color: var(--color-text-secondary); }
@media (max-width: 767px) {
    .details-grid { grid-template-columns: 1fr; }
}
</style>

<div class="details-wrap">
    <?php $flash = get_flash(); if ($flash): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
    <?php endif; ?>

    <div class="details-grid">
        <div class="details-image">
            <?php if (!empty($watch['image'])): ?>
                <img src="assets/uploads/watches/<?php echo htmlspecialchars($watch['image']); ?>"
                     alt="<?php echo htmlspecialchars($watch['name']); ?>"
                     onerror="this.style.display='none'">
            <?php endif; ?>
        </div>

        <div class="details-info">
            <div class="details-brand"><?php echo htmlspecialchars($watch['brand']); ?></div>
            <h1 class="details-name"><?php echo htmlspecialchars($watch['name']); ?></h1>

            <?php if ($avg_rating > 0): ?>
                <div class="details-rating">
                    <?php echo str_repeat('★', round($avg_rating)) . str_repeat('☆', 5 - round($avg_rating)); ?>
                    (<?php echo $avg_rating; ?> / 5, <?php echo count($reviews); ?> review<?php echo count($reviews) !== 1 ? 's' : ''; ?>)
                </div>
            <?php endif; ?>

            <div class="details-price">₹<?php echo number_format($watch['price'], 2); ?></div>

            <div class="details-stock">
                <?php if ($watch['stock'] > 0): ?>
                    <span class="in-stock">In Stock (<?php echo (int)$watch['stock']; ?> available)</span>
                <?php else: ?>
                    <span class="out-stock">Out of Stock</span>
                <?php endif; ?>
            </div>

            <p class="details-desc"><?php echo nl2br(htmlspecialchars($watch['description'])); ?></p>

            <?php if (!empty($watch['specifications'])): ?>
                <div class="details-specs"><?php echo htmlspecialchars($watch['specifications']); ?></div>
            <?php endif; ?>

            <?php if (!is_logged_in()): ?>
                <a href="login.php" class="btn-primary">Login To Purchase</a>
            <?php else: ?>
                <?php if ($watch['stock'] > 0): ?>
                    <form class="cart-form" method="POST" action="watch-details.php?id=<?php echo $watch_id; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <input type="number" name="quantity" value="1" min="1" max="<?php echo (int)$watch['stock']; ?>">
                        <button type="submit" name="add_to_cart" class="btn-primary">Add To Cart</button>
                        <button type="submit" name="buy_now" class="btn-primary">Buy Now</button>
                    </form>
                <?php else: ?>
                    <span class="btn-disabled">Out of Stock</span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="reviews-section">
        <h2>Customer Reviews</h2>
        <?php if (count($reviews) === 0): ?>
            <p class="no-reviews">No reviews yet for this watch.</p>
        <?php else: ?>
            <?php foreach ($reviews as $r): ?>
                <div class="review-card">
                    <div class="review-meta">
                        <?php echo htmlspecialchars($r['full_name']); ?> —
                        <?php echo str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']); ?>
                        — <?php echo date('M d, Y', strtotime($r['created_at'])); ?>
                    </div>
                    <div><?php echo nl2br(htmlspecialchars($r['review'])); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>