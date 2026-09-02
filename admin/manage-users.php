<?php
/**
 * Admin: Manage Users
 * Watch Collection - College Project
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();

$page_title = 'Manage Users';

/* ---------- Handle POST: toggle status ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Invalid request. Please try again.');
        redirect('admin/manage-users.php');
    }

    if (($_POST['action'] ?? '') === 'toggle_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $new_status = ($_POST['new_status'] ?? '') === 'active' ? 'active' : 'inactive';

        $stmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
        $stmt->execute([$new_status, $id]);

        set_flash('success', 'User ' . ($new_status === 'active' ? 'activated' : 'deactivated') . '.');
        redirect('admin/manage-users.php' . ($id ? '?view=' . $id : ''));
    }
}

/* ---------- Single user view ---------- */
$view_user = null;
$user_orders = [];

if (isset($_GET['view'])) {
    $vid = (int) $_GET['view'];

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$vid]);
    $view_user = $stmt->fetch();

    if ($view_user) {
        $ord = $pdo->prepare(
            'SELECT id, order_number, total_amount, order_status, created_at
             FROM orders WHERE user_id = ? ORDER BY created_at DESC'
        );
        $ord->execute([$vid]);
        $user_orders = $ord->fetchAll();
    }
}

/* ---------- List all users ---------- */
$users = $pdo->query(
    "SELECT u.id, u.full_name, u.email, u.phone, u.status, u.created_at,
            (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count
     FROM users u ORDER BY u.created_at DESC"
)->fetchAll();

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/admin-sidebar.php';
?>

<style>
    .page-title {
        font-family: var(--font-heading);
        font-size: 1.6rem;
        margin-bottom: 20px;
    }

    .page-subtitle {
        font-family: var(--font-heading);
        font-size: 1.2rem;
        margin: 28px 0 12px;
    }

    .admin-form {
        background-color: var(--color-card);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 24px;
        margin-bottom: 24px;
        max-width: 600px;
    }

    .admin-form p {
        margin: 0 0 12px;
    }

    .btn-sm {
        padding: 6px 14px;
        font-size: 0.85rem;
    }

    .btn-danger {
        background-color: #DC2626;
        color: #FFFFFF;
        border-color: #DC2626;
    }

    .btn-danger:hover {
        background-color: #991B1B;
    }
</style>

<h1 class="page-title">Manage Users</h1>

<?php if ($view_user): ?>

    <div class="admin-form">
        <p><strong>Name:</strong> <?= htmlspecialchars($view_user['full_name'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($view_user['email'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($view_user['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Address:</strong><br><?= nl2br(htmlspecialchars($view_user['address'] ?? '—', ENT_QUOTES, 'UTF-8')) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars(ucfirst($view_user['status']), ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Joined:</strong> <?= htmlspecialchars($view_user['created_at'], ENT_QUOTES, 'UTF-8') ?></p>

        <form method="POST" action="<?= base_url('admin/manage-users.php') ?>"
              onsubmit="return confirm('<?= $view_user['status'] === 'active' ? 'Deactivate' : 'Activate' ?> this user?');">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="toggle_status">
            <input type="hidden" name="id" value="<?= (int) $view_user['id'] ?>">
            <input type="hidden" name="new_status" value="<?= $view_user['status'] === 'active' ? 'inactive' : 'active' ?>">
            <button type="submit" class="btn btn-danger">
                <?= $view_user['status'] === 'active' ? 'Deactivate User' : 'Activate User' ?>
            </button>
        </form>
    </div>

    <h3 class="page-subtitle">Order History</h3>
    <table class="admin-table">
        <thead><tr><th>Order #</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php if (empty($user_orders)): ?>
            <tr><td colspan="4">No orders yet.</td></tr>
        <?php else: foreach ($user_orders as $o): ?>
            <tr>
                <td><?= htmlspecialchars($o['order_number'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>₹<?= number_format((float) $o['total_amount'], 2) ?></td>
                <td><?= htmlspecialchars($o['order_status'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($o['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <a href="<?= base_url('admin/manage-users.php') ?>" class="btn btn-secondary">Back To Users</a>

<?php else: ?>

    <table class="admin-table">
        <thead>
            <tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if (empty($users)): ?>
            <tr><td colspan="6">No users found.</td></tr>
        <?php else: foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($u['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int) $u['order_count'] ?></td>
                <td><?= htmlspecialchars(ucfirst($u['status']), ENT_QUOTES, 'UTF-8') ?></td>
                <td><a href="<?= base_url('admin/manage-users.php?view=' . (int) $u['id']) ?>" class="btn btn-secondary btn-sm">View</a></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

<?php endif; ?>

</main>
</div>
</body>
</html>