<?php
// ── DATABASE CONFIG ───────────────────────────────────────────
// Edit these values to match your WAMP setup
define('DB_HOST', 'localhost');
define('DB_NAME', 'tryharder_db');
define('DB_USER', 'root');
define('DB_PASS', '');          // WAMP default is empty password
define('DB_CHARSET', 'utf8mb4');

define('RATE_PER_HOUR', 5.00);

// ── PDO CONNECTION ────────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}