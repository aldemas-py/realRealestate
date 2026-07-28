<?php

/**
 * DATABASE CONFIGURATION
 * PDO Connection with Policy-as-Code compliance checks
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'realestate_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO database connection
 */
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection error. Please try again later.");
        }
    }
    return $pdo;
}

/**
 * Get a policy rule value from the database
 */
function getPolicyValue(string $policyKey, string $default = ''): string
{
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT default_value FROM policy_rules WHERE policy_key = ? AND is_active = TRUE LIMIT 1");
        $stmt->execute([$policyKey]);
        $row = $stmt->fetch();
        return $row ? $row['default_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}
