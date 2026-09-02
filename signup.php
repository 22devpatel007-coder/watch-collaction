<?php
/**
 * User Signup
 * Watch Collection - College Project
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/user-auth.php';

require_guest();

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $full_name = sanitize($_POST['full_name'] ?? '');
    $email     = sanitize($_POST['email'] ?? '');
    $phone     = sanitize($_POST['phone'] ?? '');
    $password  = (string) ($_POST['password'] ?? '');
    $confirm   = (string) ($_POST['confirm_password'] ?? '');

    $old['full_name'] = $full_name;
    $old['email']     = $email;
    $old['phone']     = $phone;

    if ($full_name === '' || strlen($full_name) > 100) {
        $errors[] = 'Please enter a valid full name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($phone === '' || !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO users (full_name, email, phone, password, status)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$full_name, $email, $phone, $hash, 'active']);

        set_flash('success', 'Account created successfully. Please login.');
        redirect('login.php');
    }
}

$page_title = 'Sign Up';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<style>
    .auth-wrap {
        max-width: 460px;
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
        <h1>Create Account</h1>

        <?php if (!empty($errors)): ?>
            <div class="auth-errors">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form id="signupForm" method="POST" action="<?= base_url('signup.php') ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" maxlength="100"
                       value="<?= htmlspecialchars($old['full_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                <span class="field-error" data-error-for="full_name">Please enter your full name.</span>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" maxlength="100"
                       value="<?= htmlspecialchars($old['email'], ENT_QUOTES, 'UTF-8') ?>" required>
                <span class="field-error" data-error-for="email">Please enter a valid email.</span>
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" maxlength="20"
                       value="<?= htmlspecialchars($old['phone'], ENT_QUOTES, 'UTF-8') ?>" required>
                <span class="field-error" data-error-for="phone">Please enter a valid phone number.</span>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" minlength="8" required>
                <span class="field-error" data-error-for="password">Password must be at least 8 characters.</span>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>
                <span class="field-error" data-error-for="confirm_password">Passwords do not match.</span>
            </div>

            <button type="submit" class="btn-primary">Sign Up</button>
        </form>

        <p class="auth-footer-link">
            Already have an account? <a href="<?= base_url('login.php') ?>">Login</a>
        </p>
    </div>
</div>

<script>
document.getElementById('signupForm').addEventListener('submit', function (e) {
    var valid = true;
    var fullName = document.getElementById('full_name');
    var email = document.getElementById('email');
    var phone = document.getElementById('phone');
    var password = document.getElementById('password');
    var confirm = document.getElementById('confirm_password');

    function showError(field, show) {
        var el = document.querySelector('[data-error-for="' + field.id + '"]');
        if (el) el.style.display = show ? 'block' : 'none';
    }

    if (fullName.value.trim() === '') { showError(fullName, true); valid = false; } else { showError(fullName, false); }

    var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email.value.trim())) { showError(email, true); valid = false; } else { showError(email, false); }

    var phonePattern = /^[0-9+\-\s]{7,20}$/;
    if (!phonePattern.test(phone.value.trim())) { showError(phone, true); valid = false; } else { showError(phone, false); }

    if (password.value.length < 8) { showError(password, true); valid = false; } else { showError(password, false); }

    if (confirm.value !== password.value || confirm.value === '') { showError(confirm, true); valid = false; } else { showError(confirm, false); }

    if (!valid) e.preventDefault();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>