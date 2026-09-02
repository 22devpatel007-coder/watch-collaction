<?php
/**
 * User Login
 * Watch Collection - College Project
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/user-auth.php';

require_guest();

$errors = [];
$old_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $email    = sanitize($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $old_email = $email;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id, full_name, email, password, status FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Invalid email or password.';
        } elseif ($user['status'] !== 'active') {
            $errors[] = 'Your account is inactive. Please contact support.';
        }
    }

    if (empty($errors)) {
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];

        set_flash('success', 'Welcome back, ' . $user['full_name'] . '!');
        redirect('user/dashboard.php');
    }
}

$page_title = 'Login';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<style>
    .auth-wrap {
        max-width: 420px;
        margin: 60px auto;
        padding: 40px;
        background: #FFFFFF;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .auth-wrap h1 {
        font-family: 'DM Serif Display', serif;
        font-size: 28px;
        color: #111827;
        margin-bottom: 24px;
        text-align: center;
    }
    .auth-errors {
        background: #FEF2F2;
        border: 1px solid #FCA5A5;
        color: #B91C1C;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
    }
    .auth-errors ul { margin: 0; padding-left: 18px; }
    .form-group { margin-bottom: 18px; }
    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        color: #111827;
        margin-bottom: 6px;
    }
    .form-group input {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #111827;
        transition: border-color 0.3s;
    }
    .form-group input:focus {
        outline: none;
        border-color: #3B82F6;
    }
    .field-error {
        color: #B91C1C;
        font-size: 12px;
        margin-top: 4px;
        display: none;
    }
    .btn-primary {
        width: 100%;
        padding: 12px;
        background: #1F3A5F;
        color: #FFFFFF;
        border: none;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s;
    }
    .btn-primary:hover { background: #3B82F6; }
    .auth-footer-link {
        text-align: center;
        margin-top: 18px;
        font-size: 14px;
        color: #6B7280;
    }
    .auth-footer-link a { color: #3B82F6; text-decoration: none; }
</style>

<div class="container">
    <div class="auth-wrap">
        <h1>Login</h1>

        <?php if (!empty($errors)): ?>
            <div class="auth-errors">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form id="loginForm" method="POST" action="<?= base_url('login.php') ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" maxlength="100"
                       value="<?= htmlspecialchars($old_email, ENT_QUOTES, 'UTF-8') ?>" required>
                <span class="field-error" data-error-for="email">Please enter a valid email.</span>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <span class="field-error" data-error-for="password">Please enter your password.</span>
            </div>

            <button type="submit" class="btn-primary">Login</button>
        </form>

        <p class="auth-footer-link">
            Don't have an account? <a href="<?= base_url('signup.php') ?>">Sign Up</a>
        </p>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', function (e) {
    var valid = true;
    var email = document.getElementById('email');
    var password = document.getElementById('password');

    function showError(field, show) {
        var el = document.querySelector('[data-error-for="' + field.id + '"]');
        if (el) el.style.display = show ? 'block' : 'none';
    }

    var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email.value.trim())) { showError(email, true); valid = false; } else { showError(email, false); }

    if (password.value === '') { showError(password, true); valid = false; } else { showError(password, false); }

    if (!valid) e.preventDefault();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>