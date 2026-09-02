<?php
/**
 * User Authentication Guard
 * Watch Collection - College Project
 *
 * Session keys set by login.php (Module 3):
 *   $_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']
 */

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Block guests from user-only pages (e.g. user/*).
 */
function require_user(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Please login to continue.');
        redirect('login.php');
    }
}

/**
 * Block logged-in users from guest-only pages (e.g. login.php, signup.php).
 */
function require_guest(): void
{
    if (is_logged_in()) {
        redirect('user/dashboard.php');
    }
}