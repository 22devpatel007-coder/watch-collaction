<?php
/**
 * Admin Authentication Guard
 * Watch Collection - College Project
 *
 * Session keys set by admin/login.php (Module 8):
 *   $_SESSION['admin_id'], $_SESSION['admin_name']
 */

function is_admin(): bool
{
    return isset($_SESSION['admin_id']);
}

/**
 * Block everyone except logged-in admins (e.g. admin/*).
 */
function require_admin(): void
{
    if (!is_admin()) {
        redirect('admin/login.php');
    }
}