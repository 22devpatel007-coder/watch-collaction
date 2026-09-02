<?php
/**
 * Admin: Manage Watches
 * Watch Collection - College Project
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();

$page_title = 'Manage Watches';
$edit_watch = null;

/**
 * Handle secure image upload. Returns new filename on success, null if no file given.
 * Throws RuntimeException on validation failure.
 */
function handle_watch_image_upload(array $file): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed. Please try again.');
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        throw new RuntimeException('Image must be smaller than 2MB.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_TYPES, true)) {
        throw new RuntimeException('Invalid image type. Allowed: ' . implode(', ', ALLOWED_IMAGE_TYPES));
    }

    // Verify it's actually an image (not just a renamed file)
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new RuntimeException('The uploaded file is not a valid image.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destDir  = __DIR__ . '/../' . UPLOAD_WATCHES_PATH;
    $destPath = $destDir . $filename;

    if (!is_dir($destDir) || !move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new RuntimeException('Could not save uploaded image.');
    }

    return $filename;
}

/* ---------- Handle POST actions ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Invalid request. Please try again.');
        redirect('admin/manage-watches.php');
    }

    $action = $_POST['action'] ?? '';

    /* ---- Add or Update ---- */
    if ($action === 'save') {
        $id             = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
        $category_id    = (int) ($_POST['category_id'] ?? 0);
        $name           = sanitize($_POST['name'] ?? '');
        $brand          = sanitize($_POST['brand'] ?? '');
        $price          = (float) ($_POST['price'] ?? 0);
        $stock          = (int) ($_POST['stock'] ?? 0);
        $description    = sanitize($_POST['description'] ?? '');
        $specifications = sanitize($_POST['specifications'] ?? '');
        $status         = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        $redirectPath = 'admin/manage-watches.php' . ($id ? '?edit=' . $id : '');

        if ($name === '' || $brand === '' || $category_id <= 0 || $price <= 0) {
            set_flash('error', 'Name, brand, category, and a valid price are required.');
            redirect($redirectPath);
        }

        try {
            $newImage = handle_watch_image_upload($_FILES['image'] ?? []);
        } catch (RuntimeException $e) {
            set_flash('error', $e->getMessage());
            redirect($redirectPath);
        }

        if ($id) {
            if ($newImage) {
                $old = $pdo->prepare('SELECT image FROM watches WHERE id = ?');
                $old->execute([$id]);
                $oldImage = $old->fetchColumn();

                $stmt = $pdo->prepare(
                    'UPDATE watches SET category_id=?, name=?, brand=?, price=?, stock=?, image=?,
                     description=?, specifications=?, status=? WHERE id=?'
                );
                $stmt->execute([$category_id, $name, $brand, $price, $stock, $newImage,
                    $description, $specifications, $status, $id]);

                if ($oldImage) {
                    $oldPath = __DIR__ . '/../' . UPLOAD_WATCHES_PATH . $oldImage;
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE watches SET category_id=?, name=?, brand=?, price=?, stock=?,
                     description=?, specifications=?, status=? WHERE id=?'
                );
                $stmt->execute([$category_id, $name, $brand, $price, $stock,
                    $description, $specifications, $status, $id]);
            }
            set_flash('success', 'Watch updated successfully.');
        } else {
            if (!$newImage) {
                set_flash('error', 'Please upload an image for the watch.');
                redirect($redirectPath);
            }
            $stmt = $pdo->prepare(
                'INSERT INTO watches (category_id, name, brand, price, stock, image, description, specifications, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$category_id, $name, $brand, $price, $stock, $newImage,
                $description, $specifications, $status]);
            set_flash('success', 'Watch added successfully.');
        }

        redirect('admin/manage-watches.php');
    }

    /* ---- Delete ---- */
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        $stmt = $pdo->prepare('SELECT image FROM watches WHERE id = ?');
        $stmt->execute([$id]);
        $image = $stmt->fetchColumn();

        $del = $pdo->prepare('DELETE FROM watches WHERE id = ?');
        $del->execute([$id]);

        if ($image) {
            $path = __DIR__ . '/../' . UPLOAD_WATCHES_PATH . $image;
            if (is_file($path)) {
                @unlink($path);
            }
        }

        set_flash('success', 'Watch deleted successfully.');
        redirect('admin/manage-watches.php');
    }
}

/* ---------- Load watch for editing ---------- */
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM watches WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit_watch = $stmt->fetch();
}

/* ---------- Fetch dropdown categories ---------- */
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();

/* ---------- Fetch all watches ---------- */
$watches = $pdo->query(
    'SELECT w.*, c.name AS category_name
     FROM watches w
     LEFT JOIN categories c ON c.id = w.category_id
     ORDER BY w.created_at DESC'
)->fetchAll();

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/admin-sidebar.php';
?>

<h1 class="page-title"><?= $edit_watch ? 'Edit Watch' : 'Add Watch' ?></h1>

<form method="POST" class="admin-form" action="<?= base_url('admin/manage-watches.php') ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="save">
    <?php if ($edit_watch): ?>
        <input type="hidden" name="id" value="<?= (int) $edit_watch['id'] ?>">
    <?php endif; ?>

    <label for="category_id">Category</label>
    <select id="category_id" name="category_id" required>
        <option value="">Select Category</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['id'] ?>" <?= ($edit_watch['category_id'] ?? null) == $cat['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="name">Watch Name</label>
    <input type="text" id="name" name="name" maxlength="150" required
           value="<?= htmlspecialchars($edit_watch['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

    <label for="brand">Brand</label>
    <input type="text" id="brand" name="brand" maxlength="100" required
           value="<?= htmlspecialchars($edit_watch['brand'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

    <label for="price">Price (₹)</label>
    <input type="number" id="price" name="price" step="0.01" min="0.01" required
           value="<?= htmlspecialchars((string) ($edit_watch['price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label for="stock">Stock</label>
    <input type="number" id="stock" name="stock" min="0" required
           value="<?= htmlspecialchars((string) ($edit_watch['stock'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>">

    <label for="image">
        Image <?= $edit_watch ? '(leave empty to keep current)' : '' ?>
    </label>
    <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp" <?= $edit_watch ? '' : 'required' ?>>
    <?php if ($edit_watch && $edit_watch['image']): ?>
        <img src="<?= base_url(UPLOAD_WATCHES_PATH . $edit_watch['image']) ?>" alt="Current image" class="admin-thumb-preview">
    <?php endif; ?>

    <label for="description">Description</label>
    <textarea id="description" name="description" rows="3"><?= htmlspecialchars($edit_watch['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

    <label for="specifications">Specifications</label>
    <textarea id="specifications" name="specifications" rows="3"><?= htmlspecialchars($edit_watch['specifications'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

    <label for="status">Status</label>
    <select id="status" name="status">
        <option value="active" <?= ($edit_watch['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= ($edit_watch['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    </select>

    <button type="submit" class="btn btn-primary"><?= $edit_watch ? 'Update Watch' : 'Add Watch' ?></button>
    <?php if ($edit_watch): ?>
        <a href="<?= base_url('admin/manage-watches.php') ?>" class="btn btn-secondary">Cancel</a>
    <?php endif; ?>
</form>

<h2 class="page-subtitle">All Watches</h2>

<table class="admin-table">
    <thead>
        <tr>
            <th>Image</th>
            <th>Name</th>
            <th>Brand</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($watches)): ?>
            <tr><td colspan="8">No watches found.</td></tr>
        <?php else: foreach ($watches as $w): ?>
            <tr>
                <td>
                    <?php if ($w['image']): ?>
                        <img src="<?= base_url(UPLOAD_WATCHES_PATH . $w['image']) ?>" alt="<?= htmlspecialchars($w['name'], ENT_QUOTES, 'UTF-8') ?>" class="admin-thumb">
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($w['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($w['brand'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($w['category_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td>₹<?= number_format((float) $w['price'], 2) ?></td>
                <td><?= (int) $w['stock'] ?></td>
                <td><?= htmlspecialchars(ucfirst($w['status']), ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <a href="<?= base_url('admin/manage-watches.php?edit=' . (int) $w['id']) ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <form method="POST" action="<?= base_url('admin/manage-watches.php') ?>" style="display:inline"
                          onsubmit="return confirm('Delete this watch?');">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $w['id'] ?>">
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