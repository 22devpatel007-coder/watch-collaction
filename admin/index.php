<?php
/**
 * Admin Entry Point
 * Watch Collection - College Project
 *
 * Handles requests to /admin/ (no filename).
 * Redirects to dashboard if logged in, else to login page.
 */

require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/admin-auth.php';

if (is_admin()) {
    redirect('admin/dashboard.php');
} else {
    redirect('admin/login.php');
}