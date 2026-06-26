<?php
// ============================================================
//  Academon · Database Configuration
//  Place this file in your project root (same folder as index.php)
//  !! Keep this file private — never expose it publicly !!
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'academon');
define('DB_USER', 'root');       // default XAMPP username
define('DB_PASS', 'Moraleda@20067');           // default XAMPP password (empty)
define('DB_CHARSET', 'utf8mb4');

// After login, users are sent here — change to your dashboard page
define('REDIRECT_AFTER_LOGIN', 'dashboard.php');

// ---- PDO connection (used by auth files) ----
function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}
