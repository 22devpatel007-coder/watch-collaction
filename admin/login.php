<?php
/**
 * Admin Login
 * Watch Collection - College Project
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin-auth.php';

if (is_admin()) {
    redirect('admin/dashboard.php');
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $email    = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $errors[] = 'Email and password are required.';
        } else {
            $stmt = $pdo->prepare('SELECT id, full_name, password FROM admins WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id']   = $admin['id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                redirect('admin/dashboard.php');
            } else {
                $errors[] = 'Invalid email or password.';
            }
        }
    }
}

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <style>
        .admin-login-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--color-bg);
            padding: 20px;
        }
        .admin-login-card {
            width: 100%;
            max-width: 380px;
            background-color: var(--color-card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 32px;
        }
        .admin-login-card h1 {
            font-family: var(--font-heading);
            font-size: 1.5rem;
            color: var(--color-text);
            margin: 0 0 24px;
            text-align: center;
        }
        .admin-login-card label {
            display: block;
            font-weight: 500;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }
        .admin-login-card input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            margin-bottom: 16px;
            font-family: var(--font-body);
            font-size: 1rem;
        }
        .admin-login-card input:focus {
            outline: none;
            border-color: var(--color-accent);
        }
        .admin-login-card button {
            width: 100%;
            padding: 12px;
            background-color: var(--color-primary);
            color: #FFFFFF;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .admin-login-card button:hover {
            background-color: var(--color-accent);
        }
        .admin-login-errors {
            background-color: #FEE2E2;
            color: #991B1B;
            padding: 10px 14px;
            border-radius: var(--radius);
            margin-bottom: 16px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<div class="admin-login-wrap">
    <div class="admin-login-card">
        <h1>Admin Login</h1>

        <?php if ($errors): ?>
            <div class="admin-login-errors">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="adminLoginForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button>
        </form>
    </div>
</div>

<script>
document.getElementById('adminLoginForm').addEventListener('submit', function (e) {
    var email = document.getElementById('email').value.trim();
    var password = document.getElementById('password').value;
    if (!email || !password) {
        e.preventDefault();
        alert('Please fill in both email and password.');
    }
});
</script>
</body>
</html>