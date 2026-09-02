<?php
/**
 * Admin: Manage Categories
 * Watch Collection - College Project
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();

$page_title = 'Manage Categories';
$edit_category = null;

/* ---------- Handle POST actions ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Invalid request. Please try again.');
        redirect('admin/manage-categories.php');
    }

    $action = $_POST['action'] ?? '';

    /* ---- Add or Update ---- */
    if ($action === 'save') {
        $id          = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
        $name        = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');

        if ($name === '') {
            set_flash('error', 'Category name is required.');
            redirect('admin/manage-categories.php' . ($id ? '?edit=' . $id : ''));
        }

        if ($id) {
            $stmt = $pdo->prepare('UPDATE categories SET name = ?, description = ? WHERE id = ?');
            $stmt->execute([$name, $description, $id]);
            set_flash('success', 'Category updated successfully.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
            $stmt->execute([$name, $description]);
            set_flash('success', 'Category added successfully.');
        }

        redirect('admin/manage-categories.php');
    }

    /* ---- Delete ---- */
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        $check = $pdo->prepare('SELECT COUNT(*) FROM watches WHERE category_id = ?');
        $check->execute([$id]);

        if ($check->fetchColumn() > 0) {
            set_flash('error', 'Cannot delete: this category has watches assigned to it.');
        } else {
            $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
            $stmt->execute([$id]);
            set_flash('success', 'Category deleted successfully.');
        }

        redirect('admin/manage-categories.php');
    }
}

/* ---------- Load category for editing ---------- */
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit_category = $stmt->fetch();
}

/* ---------- Fetch all categories with watch counts ---------- */
$categories = $pdo->query(
    'SELECT c.*, COUNT(w.id) AS watch_count
     FROM categories c
     LEFT JOIN watches w ON w.category_id = c.id
     GROUP BY c.id
     ORDER BY c.name ASC'
)->fetchAll();

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/admin-sidebar.php';
?>

<h1 class="page-title"><?= $edit_category ? 'Edit Category' : 'Add Category' ?></h1>

<form method="POST" class="admin-form" action="<?= base_url('admin/manage-categories.php') ?>">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="save">
    <?php if ($edit_category): ?>
        <input type="hidden" name="id" value="<?= (int) $edit_category['id'] ?>">
    <?php endif; ?>

    <label for="name">Category Name</label>
    <input type="text" id="name" name="name" maxlength="100" required
           value="<?= htmlspecialchars($edit_category['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

    <label for="description">Description</label>
    <textarea id="description" name="description" rows="3"><?= htmlspecialchars($edit_category['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

    <button type="submit" class="btn btn-primary"><?= $edit_category ? 'Update Category' : 'Add Category' ?></button>
    <?php if ($edit_category): ?>
        <a href="<?= base_url('admin/manage-categories.php') ?>" class="btn btn-secondary">Cancel</a>
    <?php endif; ?>
</form>

<h2 class="page-subtitle">All Categories</h2>

<table class="admin-table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Description</th>
            <th>Watches</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($categories)): ?>
            <tr><td colspan="4">No categories found.</td></tr>
        <?php else: foreach ($categories as $cat): ?>
            <tr>
                <td><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($cat['description'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int) $cat['watch_count'] ?></td>
                <td>
                    <a href="<?= base_url('admin/manage-categories.php?edit=' . (int) $cat['id']) ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <form method="POST" action="<?= base_url('admin/manage-categories.php') ?>" style="display:inline"
                          onsubmit="return confirm('Delete this category?');">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>

</main>
</div>
</body>
</html>