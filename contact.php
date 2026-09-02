<?php
require_once 'includes/config.php';
require_once 'includes/session.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/user-auth.php';

$errors = [];
$old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $old['name'] = sanitize($_POST['name'] ?? '');
        $old['email'] = sanitize($_POST['email'] ?? '');
        $old['subject'] = sanitize($_POST['subject'] ?? '');
        $old['message'] = sanitize($_POST['message'] ?? '');

        if ($old['name'] === '') {
            $errors[] = 'Name is required.';
        }
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required.';
        }
        if ($old['subject'] === '') {
            $errors[] = 'Subject is required.';
        }
        if ($old['message'] === '') {
            $errors[] = 'Message is required.';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$old['name'], $old['email'], $old['subject'], $old['message']]);

            set_flash('success', 'Your message has been sent. We will get back to you soon.');
            redirect('contact.php');
        }
    }
}

$page_title = 'Contact Us - ' . SITE_NAME;
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<style>
.contact-wrap { max-width: 700px; margin: 40px auto; padding: 0 20px; }
.contact-wrap h1 { font-family: 'DM Serif Display', serif; font-size: 2rem; color: var(--color-text-primary); text-align: center; margin-bottom: 8px; }
.contact-wrap > p { text-align: center; color: var(--color-text-secondary); margin-bottom: 30px; }
.contact-form { background: var(--color-card); border: 1px solid var(--color-border); border-radius: 12px; padding: 30px; }
.form-group { margin-bottom: 18px; }
.form-group label { display: block; margin-bottom: 6px; font-size: 0.9rem; color: var(--color-text-primary); font-weight: 600; }
.form-group input, .form-group textarea {
    width: 100%; padding: 12px; border: 1px solid var(--color-border); border-radius: 12px;
    font-family: 'Inter', sans-serif; font-size: 0.95rem; box-sizing: border-box;
}
.form-group textarea { resize: vertical; min-height: 120px; }
.btn-primary { background: var(--color-primary); color: #fff; border: none; padding: 12px 28px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: background 0.3s; }
.btn-primary:hover { background: var(--color-accent); }
.error-list { background: #FEF2F2; border: 1px solid #FCA5A5; color: #B91C1C; padding: 12px 16px; border-radius: 12px; margin-bottom: 18px; font-size: 0.9rem; }
.error-list ul { margin: 0; padding-left: 18px; }
</style>

<div class="contact-wrap">
    <h1>Contact Us</h1>
    <p>Have a question? We'd love to hear from you.</p>

    <?php $flash = get_flash(); if ($flash): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="error-list">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form class="contact-form" method="POST" action="contact.php">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($old['name']); ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($old['email']); ?>" required>
        </div>

        <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($old['subject']); ?>" required>
        </div>

        <div class="form-group">
            <label for="message">Message</label>
            <textarea id="message" name="message" required><?php echo htmlspecialchars($old['message']); ?></textarea>
        </div>

        <button type="submit" class="btn-primary">Send Message</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>