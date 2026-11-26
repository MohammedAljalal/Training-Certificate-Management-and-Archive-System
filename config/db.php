<?php
/**
 * Database Configuration
 * Training Certificate Management and Archive System
 *
 * NFR-08: Database credentials must be stored in config/db.php,
 *         located outside the web-accessible public/ directory.
 * NFR-20: No hardcoded credentials may appear in any other file.
 * NFR-21: PDO configured with ERRMODE_EXCEPTION.
 *
 * USAGE: require_once __DIR__ . '/db.php'; — returns a PDO instance.
 */

declare(strict_types=1);

// ── Database connection parameters ──────────────────────────────────────────
$dbUrl = getenv('MYSQL_URL') ?: (getenv('DATABASE_URL') ?: '');
$parsedUrl = $dbUrl ? (parse_url($dbUrl) ?: []) : [];

define('DB_HOST', getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: ($parsedUrl['host'] ?? '127.0.0.1')));
define('DB_PORT', getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: (isset($parsedUrl['port']) ? (string)$parsedUrl['port'] : '3306')));
define('DB_NAME', getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: (isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : 'tcmas')));
define('DB_USER', getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: ($parsedUrl['user'] ?? 'root')));
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : ($parsedUrl['pass'] ?? '')));
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a singleton PDO connection.
 *
 * @return PDO
 * @throws PDOException if connection fails
 */
function get_pdo(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // NFR-21
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,                     // NFR-01: use native prepared statements
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    return $pdo;
}
