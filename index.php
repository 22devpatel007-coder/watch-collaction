<?php
/**
 * Homepage
 * Watch Collection - College Project
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/user-auth.php';

$page_title = 'Home';

// Featured Watches (latest 8 active watches, used as "featured" for now)
$featured_watches = [];
try {
    $stmt = $pdo->query(
        "SELECT id, name, brand, price, image
         FROM watches
         WHERE status = 'active'
         ORDER BY created_at DESC
         LIMIT 8"
    );
    $featured_watches = $stmt->fetchAll();
} catch (PDOException $e) {
    $featured_watches = [];
}

// Categories
$categories = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Customer Reviews (latest 6, joined with user name)
$reviews = [];
try {
    $stmt = $pdo->query(
        "SELECT r.rating, r.review, r.created_at, u.full_name, w.name AS watch_name
         FROM reviews r
         JOIN users u ON u.id = r.user_id
         JOIN watches w ON w.id = r.watch_id
         ORDER BY r.created_at DESC
         LIMIT 6"
    );
    $reviews = $stmt->fetchAll();
} catch (PDOException $e) {
    $reviews = [];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
    /* ---------- Hero ---------- */
    .hero {
        background-color: #FFFFFF;
        padding: 80px 0;
    }

    .hero__inner {
        display: flex;
        align-items: center;
        gap: 48px;
        flex-wrap: wrap;
    }

    .hero__content {
        flex: 1 1 420px;
    }

    .hero__content h1 {
        font-size: 2.75rem;
        line-height: 1.2;
        margin-bottom: 20px;
    }

    .hero__content p {
        color: var(--color-text-secondary);
        font-size: 1.05rem;
        margin-bottom: 28px;
        max-width: 480px;
    }

    .hero__image {
        flex: 1 1 380px;
        text-align: center;
    }

    .hero__image img {
        max-width: 100%;
        border-radius: var(--radius);
    }

    /* ---------- Section shared ---------- */
    .section {
        padding: 60px 0;
    }

    .section__header {
        text-align: center;
        margin-bottom: 40px;
    }

    .section__header h2 {
        font-size: 2rem;
        margin-bottom: 8px;
    }

    .section__header p {
        color: var(--color-text-secondary);
        margin: 0;
    }

    .empty-state {
        text-align: center;
        color: var(--color-text-secondary);
        padding: 40px 20px;
        background-color: var(--color-card);
        border: 1px dashed var(--color-border);
        border-radius: var(--radius);
    }

    /* ---------- Product Card ---------- */
    .product-card {
        background-color: var(--color-card);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .product-card__image {
        width: 100%;
        aspect-ratio: 1 / 1;
        background-color: var(--color-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .product-card__image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .product-card__body {
        padding: 16px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .product-card__brand {
        font-size: 0.8rem;
        color: var(--color-text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .product-card__name {
        font-family: var(--font-body);
        font-weight: 600;
        font-size: 1rem;
        margin: 4px 0 8px;
        color: var(--color-text);
    }

    .product-card__price {
        font-weight: 700;
        color: var(--color-primary);
        margin-bottom: 14px;
    }

    .product-card__actions {
        margin-top: auto;
    }

    .product-card__actions .btn {
        width: 100%;
        text-align: center;
        padding: 10px 16px;
        font-size: 0.9rem;
    }

    /* ---------- Categories ---------- */
    .category-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }

    .category-card {
        background-color: var(--color-card);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 28px 16px;
        text-align: center;
        font-weight: 600;
        color: var(--color-text);
        transition: transform var(--transition), color var(--transition);
    }

    .category-card:hover {
        transform: translateY(-4px);
        color: var(--color-accent);
    }

    /* ---------- Reviews ---------- */
    .review-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
    }

    .review-card {
        background-color: var(--color-card);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 24px;
    }

    .review-card__stars {
        color: #F59E0B;
        font-size: 0.95rem;
        margin-bottom: 10px;
    }

    .review-card__text {
        color: var(--color-text);
        font-size: 0.95rem;
        margin-bottom: 16px;
    }

    .review-card__author {
        font-weight: 600;
        font-size: 0.9rem;
    }

    .review-card__watch {
        color: var(--color-text-secondary);
        font-size: 0.82rem;
    }

    @media (max-width: 1023px) {
        .category-grid { grid-template-columns: repeat(2, 1fr); }
        .review-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 639px) {
        .category-grid { grid-template-columns: 1fr; }
        .hero__content h1 { font-size: 2.1rem; }
    }
</style>

<!-- Hero -->
<section class="hero">
    <div class="container hero__inner">
        <div class="hero__content">
            <h1>Timeless Watches,<br>Modern Style</h1>
            <p>Discover a curated collection of premium watches — from classic elegance to modern sport designs, built for every occasion.</p>
            <a href="<?= base_url('browse-watches.php') ?>" class="btn btn-primary">Browse Watches</a>
        </div>
        <div class="hero__image">
            <img src="<?= base_url('assets/images/banners/hero-watch-1.jpg') ?>" alt="Featured Watch" onerror="this.style.display='none'">
        </div>
    </div>
</section>

<!-- Featured Watches -->
<section class="section">
    <div class="container">
        <div class="section__header">
            <h2>Featured Watches</h2>
            <p>Hand-picked pieces from our latest arrivals</p>
        </div>

        <?php if (empty($featured_watches)): ?>
            <div class="empty-state">No watches available yet. Please check back soon.</div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($featured_watches as $watch): ?>
                    <div class="product-card">
                        <div class="product-card__image">
                            <?php if (!empty($watch['image'])): ?>
                                <img src="<?= base_url('uploads/watches/' . htmlspecialchars($watch['image'], ENT_QUOTES, 'UTF-8')) ?>" alt="<?= htmlspecialchars($watch['name'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php endif; ?>
                        </div>
                        <div class="product-card__body">
                            <div class="product-card__brand"><?= htmlspecialchars($watch['brand'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="product-card__name"><?= htmlspecialchars($watch['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="product-card__price">₹<?= number_format((float)$watch['price'], 2) ?></div>
                            <div class="product-card__actions">
                                <a href="<?= base_url('watch-details.php?id=' . (int)$watch['id']) ?>" class="btn btn-secondary">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Categories -->
<section class="section" style="background-color: #FFFFFF;">
    <div class="container">
        <div class="section__header">
            <h2>Shop by Category</h2>
            <p>Find the perfect watch for your style</p>
        </div>

        <?php if (empty($categories)): ?>
            <div class="empty-state">No categories available yet.</div>
        <?php else: ?>
            <div class="category-grid">
                <?php foreach ($categories as $category): ?>
                    <a href="<?= base_url('browse-watches.php?category=' . (int)$category['id']) ?>" class="category-card">
                        <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Customer Reviews -->
<section class="section">
    <div class="container">
        <div class="section__header">
            <h2>What Our Customers Say</h2>
            <p>Real reviews from real watch lovers</p>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="empty-state">No reviews yet. Be the first to review a watch!</div>
        <?php else: ?>
            <div class="review-grid">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-card__stars">
                            <?= str_repeat('★', (int)$review['rating']) . str_repeat('☆', 5 - (int)$review['rating']) ?>
                        </div>
                        <p class="review-card__text">"<?= htmlspecialchars($review['review'], ENT_QUOTES, 'UTF-8') ?>"</p>
                        <div class="review-card__author"><?= htmlspecialchars($review['full_name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="review-card__watch">on <?= htmlspecialchars($review['watch_name'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>