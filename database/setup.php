<?php
/**
 * Database Setup Script
 * Training Certificate Management and Archive System
 *
 * Run from project root:
 *   php database/setup.php
 *
 * This script:
 *  1. Connects to MariaDB as root (no password — XAMPP default).
 *  2. Creates the 'tcmas' database if it does not exist.
 *  3. Executes database/schema.sql to create all tables.
 *  4. Executes database/seed.sql to insert the initial admin account.
 *  5. Verifies the tables and admin user exist.
 *  6. Prints a plain-text summary of results.
 *
 * Requirements: PHP 8.2+, PDO_MySQL, MariaDB 10.4+ running on 127.0.0.1:3306.
 * This script does NOT use Apache or the application autoloader.
 */

declare(strict_types=1);
set_time_limit(60);

// ── Configuration ─────────────────────────────────────────────────────────────
define('DB_HOST',    '127.0.0.1');
define('DB_PORT',    '3306');
define('DB_USER',    'root');
define('DB_PASS',    '');           // XAMPP default: no password
define('DB_NAME',    'tcmas');
define('SCHEMA_SQL', __DIR__ . '/schema.sql');
define('SEED_SQL',   __DIR__ . '/seed.sql');

// ── Helpers ───────────────────────────────────────────────────────────────────
function printOk(string $msg): void  { echo "[OK]    $msg\n"; }
function printFail(string $msg): void { echo "[FAIL]  $msg\n"; exit(1); }
function printInfo(string $msg): void { echo "[INFO]  $msg\n"; }

// ── Split a SQL file into individual statements ───────────────────────────────
function split_sql(string $sql): array
{
    $statements = [];
    $current    = '';
    $delimiter  = ';';

    foreach (explode("\n", $sql) as $line) {
        $trimmed = rtrim($line);

        // Skip pure comment lines and empty lines when buffer is empty
        if ($current === '' && (str_starts_with($trimmed, '--') || $trimmed === '')) {
            continue;
        }

        $current .= $line . "\n";

        if (str_ends_with(rtrim($trimmed), $delimiter)) {
            $stmt = trim($current);
            if ($stmt !== '' && $stmt !== ';') {
                $statements[] = $stmt;
            }
            $current = '';
        }
    }

    // Any remaining content
    $stmt = trim($current);
    if ($stmt !== '' && $stmt !== ';') {
        $statements[] = $stmt;
    }

    return $statements;
}

// ── Main ──────────────────────────────────────────────────────────────────────
echo "\n=== TCMAS Database Setup ===\n\n";

// Step 1: Connect without selecting a database
printInfo("Connecting to MariaDB at " . DB_HOST . ":" . DB_PORT . " as " . DB_USER . " ...");
try {
    $pdoRoot = new PDO(
        sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT),
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    printOk("Connected to MariaDB.");
} catch (PDOException $e) {
    printFail("Cannot connect to MariaDB: " . $e->getMessage());
}

// Step 2: Create database
printInfo("Creating database '" . DB_NAME . "' if it does not exist ...");
try {
    $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    printOk("Database '" . DB_NAME . "' ready.");
} catch (PDOException $e) {
    printFail("Failed to create database: " . $e->getMessage());
}

// Step 3: Reconnect with database selected
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME),
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]
    );
    printOk("Selected database '" . DB_NAME . "'.");
} catch (PDOException $e) {
    printFail("Cannot select database: " . $e->getMessage());
}

// Step 4: Execute schema.sql
if (!file_exists(SCHEMA_SQL)) {
    printFail("schema.sql not found at: " . SCHEMA_SQL);
}

printInfo("Executing schema.sql ...");
$schemaSql = file_get_contents(SCHEMA_SQL);
$statements = split_sql($schemaSql);
$executed = 0;
foreach ($statements as $stmt) {
    try {
        $pdo->exec($stmt);
        $executed++;
    } catch (PDOException $e) {
        // Ignore "table already exists" (1050) — schema uses CREATE TABLE IF NOT EXISTS
        if (!str_contains($e->getMessage(), '1050')) {
            printFail("Schema error on statement:\n$stmt\nError: " . $e->getMessage());
        }
    }
}
printOk("schema.sql executed ($executed statements processed).");

// Step 5: Execute seed.sql
if (!file_exists(SEED_SQL)) {
    printFail("seed.sql not found at: " . SEED_SQL);
}

printInfo("Executing seed.sql ...");
$seedSql    = file_get_contents(SEED_SQL);
$statements = split_sql($seedSql);
$executed   = 0;
foreach ($statements as $stmt) {
    try {
        $pdo->exec($stmt);
        $executed++;
    } catch (PDOException $e) {
        // Ignore duplicate key (1062) — seed uses INSERT IGNORE
        if (!str_contains($e->getMessage(), '1062') && !str_contains($e->getMessage(), 'Duplicate')) {
            printFail("Seed error on statement:\n$stmt\nError: " . $e->getMessage());
        } else {
            printInfo("Admin account already exists — skipped duplicate insert.");
        }
    }
}
printOk("seed.sql executed ($executed statements processed).");

// Step 6: Verify tables
printInfo("Verifying tables ...");
$requiredTables = ['users', 'courses', 'enrollments', 'certificates'];
$stmt = $pdo->query("SHOW TABLES");
$existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

$missing = array_diff($requiredTables, $existing);
if (!empty($missing)) {
    printFail("Missing tables: " . implode(', ', $missing));
}
printOk("All required tables exist: " . implode(', ', $requiredTables));

// Step 7: Verify admin user
printInfo("Verifying admin account ...");
$stmt = $pdo->prepare("SELECT id, email, role FROM users WHERE role = 'admin' LIMIT 1");
$stmt->execute();
$admin = $stmt->fetch();

if (!$admin) {
    printFail("No admin user found in the users table.");
}
printOk("Admin account found — Email: {$admin['email']} | Role: {$admin['role']} | ID: {$admin['id']}");

// Step 8: Row counts
$counts = [];
foreach ($requiredTables as $table) {
    $row = $pdo->query("SELECT COUNT(*) AS cnt FROM `$table`")->fetch();
    $counts[] = "$table: {$row['cnt']} row(s)";
}
printInfo("Row counts — " . implode(' | ', $counts));

echo "\n=== Setup Complete ===\n";
echo "Login credentials:\n";
echo "  Email   : admin\@tcmas.local\n";
echo "  Password: Admin\@1234\n";
echo "\nNext step: Configure XAMPP to serve public/ as the document root.\n";
echo "See docs/SRS.md §8 for deployment instructions.\n\n";
