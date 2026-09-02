<?php
/**
 * Application Configuration
 * Watch Collection - College Project
 */

define('SITE_NAME', 'Watch Collection');
define('SITE_TIMEZONE', 'Asia/Kolkata');

date_default_timezone_set(SITE_TIMEZONE);

// Base URL - update folder name if different in your htdocs/www
define('BASE_URL', 'http://localhost/watch-collection/');

// Upload paths (relative to project root)
define('UPLOAD_WATCHES_PATH', 'assets/uploads/watches/');
define('UPLOAD_PROFILES_PATH', 'assets/uploads/profiles/');
define('UPLOAD_INVOICES_PATH', 'assets/uploads/invoices/');

// Upload constraints
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'webp']);