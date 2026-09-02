<?php
/**
 * Session Bootstrap
 * Watch Collection - College Project
 */

if (session_status() === PHP_SESSION_NONE) {

    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');

    // Enable this line only when serving over HTTPS
    // ini_set('session.cookie_secure', 1);

    session_start();

    if (!isset($_SESSION['created_at'])) {
        $_SESSION['created_at'] = time();
    } elseif (time() - $_SESSION['created_at'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['created_at'] = time();
    }
}