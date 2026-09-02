<?php
/**
 * Browse Watches
 * Watch Collection - College Project
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/user-auth.php';

$page_title = 'Browse Watches';

// ---------- Read & validate filters ----------
$search      = isset($_GET['search']) ? trim(sanitize($_GET['search'])) : '';
$category_id = isset($_GET['category']) && ctype_digit($_GET['category']) ? (int)$_GET['category'] : 0;
$sort        = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

$allowed_sorts = ['newest', 'price_asc', 'price_desc', 'name_asc'];
if (!in_array($sort, $allowed_sorts, true)) {
    $sort = 'newest';
}

$order_by = match ($sort) {
    'price_asc'  => 'w.price ASC',
    'price_desc' => 'w.price DESC',
    'name_asc'   => 'w.name ASC',
    default      => 'w.created_at DESC',
};

// ---------- Categories for filter dropdown ----------
$categories = [];
try {
    $categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// ---------- Build watch query ----------
$where  = ["w.status = 'active'"];
$params = [];

if ($search !== '') {
    $where[] = "(w.name LIKE :search OR w.brand LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($category_id > 0) {
    $where[] = "w.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

$where_sql = implode(' AND ', $where);

$watches = [];
try {
    $sql = "SELECT w.id, w.name, w.brand, w.price, w.image
            FROM watches w
            WHERE {$where_sql}
            ORDER BY {$order_by}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $watches = $stmt->fetchAll();
} catch (PDOException $e) {
    $watches = [];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
    .browse-hero {
        background-color: #FFFFFF;
        padding: 40px 0 24px;
        text-align: center;
    }

    .browse-hero h1 {
        font-size: 2rem;
        margin-bottom: 6px;
    }

    .browse-hero p {
        color: var(--color-text-secondary);
        margin: 0;
    }

    .filters {
        background-color: var(--color-card);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 20px;
        margin: 24px 0 32px;
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: flex-end;
    }

    .filters__field {
        flex: 1 1 70px;
        margin-bottom: 0;
    }

    .filters__field label {
        font-size: 0.85rem;
        margin-bottom: 4px;
    }

    .filters__field input,
    .filters__field select {
        width: 100%;
        box-sizing: border-box;
    }
    .filters .form-group {
        margin-bottom: 0;
    }
    .filters__actions {
        display: flex;
        gap: 10px;
    }

    .filters__actions .btn {
        padding: 10px 20px;
        font-size: 0.9rem;
    }

    .results-count {
        color: var(--color-text-secondary);
        font-size: 0.9rem;
        margin-bottom: 16px;
    }

    .product-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    padding-bottom: 60px;
}

@media (max-width: 1023px) {
    .product-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 767px) {
    .product-grid { grid-template-columns: 1fr; }
}

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
        font-weight: 600;
        font-size: 1rem;
        margin: 4px 0 8px;
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

    .empty-state {
        text-align: center;
        color: var(--color-text-secondary);
        padding: 60px 20px;
        background-color: var(--color-card);
        border: 1px dashed var(--color-border);
        border-radius: var(--radius);
        margin-bottom: 60px;
    }

    @media (max-width: 767px) {
        .filters { flex-direction: column; align-items: stretch; }
        .filters__actions { justify-content: stretch; }
        .filters__actions .btn { flex: 1; text-align: center; }
    }
</style>

<section class="browse-hero">
    <div class="container">
        <h1>Browse Watches</h1>
        <p>Explore our full collection and find your perfect match</p>
    </div>
</section>

<section class="container">
    <form method="get" action="<?= base_url('browse-watches.php') ?>" class="filters">
        <div class="filters__field form-group">
            <label for="search">Search</label>
            <input type="text" id="search" name="search" placeholder="Name or brand..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="filters__field form-group">
            <label for="category">Category</label>
            <select id="category" name="category">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>" <?= $category_id === (int)$cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filters__field form-group">
            <label for="sort">Sort By</label>
            <select id="sort" name="sort">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name: A to Z</option>
            </select>
        </div>

        <div class="filters__actions">
            <button type="submit" class="btn btn-primary">Apply</button>
            <a href="<?= base_url('browse-watches.php') ?>" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <p class="results-count"><?= count($watches) ?> watch<?= count($watches) === 1 ? '' : 'es' ?> found</p>

    <?php if (empty($watches)): ?>
        <div class="empty-state">No watches match your search. Try adjusting the filters.</div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($watches as $watch): ?>
                <div class="product-card">
                    <div class="product-card__image">
                        <?php if (!empty($watch['image'])): ?>
                            <img src="<?= base_url('/assets/uploads/watches/' . htmlspecialchars($watch['image'], ENT_QUOTES, 'UTF-8')) ?>" alt="<?= htmlspecialchars($watch['name'], ENT_QUOTES, 'UTF-8') ?>">
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
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>