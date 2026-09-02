<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/user-auth.php';

require_user();
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT full_name, email, phone, address FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('logout.php');
}

$profile_errors = [];
$password_errors = [];

$full_name_input = $user['full_name'];
$phone_input = $user['phone'];
$address_input = $user['address'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request. Please try again.');
        redirect('user/profile.php');
    }

    // --- Update Profile ---
    if (isset($_POST['update_profile'])) {
        $full_name_input = trim($_POST['full_name'] ?? '');
        $phone_input = trim($_POST['phone'] ?? '');
        $address_input = trim($_POST['address'] ?? '');

        if ($full_name_input === '' || mb_strlen($full_name_input) < 2) {
            $profile_errors[] = 'Please enter a valid full name.';
        }
        if ($phone_input !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone_input)) {
            $profile_errors[] = 'Please enter a valid phone number.';
        }
        if (mb_strlen($address_input) > 1000) {
            $profile_errors[] = 'Address is too long.';
        }

        if (empty($profile_errors)) {
            $upd = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?");
            $upd->execute([$full_name_input, $phone_input, $address_input, $user_id]);

            $_SESSION['user_name'] = $full_name_input;
            set_flash('success', 'Profile updated successfully.');
            redirect('user/profile.php');
        }
    }

    // --- Change Password ---
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $pw = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $pw->execute([$user_id]);
        $hash = $pw->fetchColumn();

        if (!$hash || !password_verify($current_password, $hash)) {
            $password_errors[] = 'Current password is incorrect.';
        } elseif (mb_strlen($new_password) < 8) {
            $password_errors[] = 'New password must be at least 8 characters.';
        } elseif ($new_password !== $confirm_password) {
            $password_errors[] = 'New password and confirmation do not match.';
        } elseif (password_verify($new_password, $hash)) {
            $password_errors[] = 'New password must be different from current password.';
        }

        if (empty($password_errors)) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->execute([$new_hash, $user_id]);

            set_flash('success', 'Password changed successfully.');
            redirect('user/profile.php');
        }
    }
}

$page_title = 'My Profile';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<style>
.profile-wrap { max-width: 700px; margin: 40px auto; padding: 0 20px; }
.profile-wrap h1 { font-family: 'DM Serif Display', serif; color: var(--color-text-primary); margin-bottom: 24px; }
.profile-card { background: var(--color-card); border: 1px solid var(--color-border); border-radius: 12px; padding: 24px; margin-bottom: 24px; }
.profile-card h2 { font-family: 'DM Serif Display', serif; font-size: 1.2rem; margin-bottom: 16px; }
.profile-card label { display: block; font-weight: 600; margin-bottom: 6px; color: var(--color-text-primary); font-size: 0.9rem; }
.profile-card input[type="text"],
.profile-card input[type="email"],
.profile-card input[type="password"],
.profile-card textarea {
    width: 100%; padding: 10px; border: 1px solid var(--color-border); border-radius: 12px;
    font-family: inherit; margin-bottom: 16px;
}
.profile-card input[disabled] { background: var(--color-bg); color: var(--color-text-secondary); }
.profile-card textarea { resize: vertical; }
.btn-save { background: var(--color-primary); color: #fff; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 600; cursor: pointer; }
.btn-save:hover { background: var(--color-accent); }
</style>

<div class="profile-wrap">
    <h1>My Profile</h1>

    <div class="profile-card">
        <h2>Profile Information</h2>
        <?php foreach ($profile_errors as $err): ?>
            <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>

        <form method="POST" action="<?= base_url('user/profile.php') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <label for="email">Email (cannot be changed)</label>
            <input type="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>

            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" required minlength="2"
                   value="<?= htmlspecialchars($full_name_input) ?>">

            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($phone_input ?? '') ?>">

            <label for="address">Address</label>
            <textarea id="address" name="address" rows="3"><?= htmlspecialchars($address_input ?? '') ?></textarea>

            <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
        </form>
    </div>

    <div class="profile-card">
        <h2>Change Password</h2>
        <?php foreach ($password_errors as $err): ?>
            <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>

        <form method="POST" action="<?= base_url('user/profile.php') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" required>

            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" required minlength="8">

            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="8">

            <button type="submit" name="change_password" class="btn-save">Change Password</button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>