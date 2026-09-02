<?php
/**
 * Database Connection (PDO)
 * Watch Collection - College Project
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'watch_collection');
define('DB_USER', 'root');
define('DB_PASS', ''); // set your MySQL password if any (XAMPP/WAMP default is empty)
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}