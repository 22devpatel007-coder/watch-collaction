<?php
/**
 * User Logout
 * Watch Collection - College Project
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

session_destroy();

session_start();
set_flash('success', 'You have been logged out.');
redirect('index.php');  