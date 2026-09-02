<?php
/**
 * Admin Logout
 * Watch Collection - College Project
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

unset($_SESSION['admin_id'], $_SESSION['admin_name']);

session_regenerate_id(true);

set_flash('success', 'You have been logged out.');
redirect('admin/login.php');