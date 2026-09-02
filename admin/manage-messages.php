<?php
/**
 * Admin: Manage Messages
 * Watch Collection - College Project
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();

$page_title = 'Manage Messages';

/* ---------- Handle POST: delete ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Invalid request. Please try again.');
        redirect('admin/manage-messages.php');
    }

    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM contact_messages WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('success', 'Message deleted.');
        redirect('admin/manage-messages.php');
    }
}

/* ---------- Single message view ---------- */
$view_message = null;

if (isset($_GET['view'])) {
    $stmt = $pdo->prepare('SELECT * FROM contact_messages WHERE id = ?');
    $stmt->execute([(int) $_GET['view']]);
    $view_message = $stmt->fetch();
}

/* ---------- List all messages ---------- */
$messages = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();

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

<h1 class="page-title">Manage Messages</h1>

<?php if ($view_message): ?>

    <div class="admin-form">
        <p><strong>From:</strong> <?= htmlspecialchars($view_message['name'], ENT_QUOTES, 'UTF-8') ?>
            (<?= htmlspecialchars($view_message['email'], ENT_QUOTES, 'UTF-8') ?>)</p>
        <p><strong>Subject:</strong> <?= htmlspecialchars($view_message['subject'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Date:</strong> <?= htmlspecialchars($view_message['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Message:</strong><br><?= nl2br(htmlspecialchars($view_message['message'], ENT_QUOTES, 'UTF-8')) ?></p>

        <form method="POST" action="<?= base_url('admin/manage-messages.php') ?>"
              onsubmit="return confirm('Delete this message?');">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $view_message['id'] ?>">
            <button type="submit" class="btn btn-danger">Delete Message</button>
        </form>
    </div>

    <a href="<?= base_url('admin/manage-messages.php') ?>" class="btn btn-secondary">Back To Messages</a>

<?php else: ?>

    <table class="admin-table">
        <thead>
            <tr><th>Name</th><th>Email</th><th>Subject</th><th>Date</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if (empty($messages)): ?>
            <tr><td colspan="5">No messages found.</td></tr>
        <?php else: foreach ($messages as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($m['email'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($m['subject'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($m['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><a href="<?= base_url('admin/manage-messages.php?view=' . (int) $m['id']) ?>" class="btn btn-secondary btn-sm">View</a></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

<?php endif; ?>

</main>
</div>
</body>
</html>