<?php
/**
 * TEKPOD - Configuration Sample
 * Copy this file to config.php and update your settings
 */

// App Settings
define('APP_NAME', 'TEKPOD');
define('APP_VERSION', '1.0.0');
define('APP_TAGLINE', 'Tracking Produksi Percetakan');

// Database Settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

// Session Settings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global Database Connection
global $pdo;
$db_error = null;
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $db_error = $e->getMessage();
}

// Include the rest of the functions from config.php or just require it
// For simplicity, you can copy all helper functions from the original config.php here
