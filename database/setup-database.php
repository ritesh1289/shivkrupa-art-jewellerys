<?php
/**
 * SHIVKRUPA ART JEWELLERY — Database Installer Script
 * Run from CLI or browser to initialize the database and load seed data.
 */

$sqlFile = __DIR__ . '/database.sql';
if (!file_exists($sqlFile)) {
    die("Error: database.sql not found at " . $sqlFile . "\n");
}

$sql = file_get_contents($sqlFile);

$host = getenv('DB_HOST') ?: '127.0.0.1';
$user = getenv('DB_USER') ?: 'root';
$pass = '';
if (getenv('DB_PASSWORD') !== false) {
    $pass = getenv('DB_PASSWORD');
} elseif (getenv('DB_PASS') !== false) {
    $pass = getenv('DB_PASS');
}
$customPort = getenv('DB_PORT') ? (int)getenv('DB_PORT') : null;
$ports = $customPort ? [$customPort] : [3307, 3306];

// Validate and resolve target database name (Fail-fast validation)
$rawDbEnv = getenv('DB_NAME');
if ($rawDbEnv === false || trim((string)$rawDbEnv) === '') {
    // Unset or empty DB_NAME environment variable defaults intentionally to 'shivkrupa_db'
    $targetDb = 'shivkrupa_db';
} else {
    $trimmedDb = trim((string)$rawDbEnv);
    if (!preg_match('/^[a-zA-Z0-9_]{1,64}$/', $trimmedDb)) {
        fwrite(STDERR, "\n[FATAL ERROR] Invalid DB_NAME value: '{$rawDbEnv}'\n");
        fwrite(STDERR, "Database names must match regex ^[a-zA-Z0-9_]{1,64}$ (alphanumeric and underscore only, 1-64 characters).\n");
        fwrite(STDERR, "Aborting database setup before opening connections.\n\n");
        exit(1);
    }
    $targetDb = $trimmedDb;
}

$success = false;

echo "=== SHIVKRUPA ART JEWELLERY: Database Initialization ===\n";
echo "Target Database: `{$targetDb}`\n";

foreach ($ports as $port) {
    try {
        echo "Attempting connection to MySQL/MariaDB on {$host}:{$port}...\n";
        $pdo = new PDO("mysql:host={$host};port={$port}", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 2
        ]);
        echo "[OK] Connected to port {$port}!\n";

        echo "Importing database schema and sample records into `{$targetDb}`...\n";
        $customSql = ($targetDb !== 'shivkrupa_db')
            ? str_replace('`shivkrupa_db`', "`{$targetDb}`", $sql)
            : $sql;
        $pdo->exec($customSql);

        // Verification query
        $pdo->exec("USE `{$targetDb}`;");
        $catCount = $pdo->query("SELECT COUNT(*) FROM `categories`")->fetchColumn();
        $prodCount = $pdo->query("SELECT COUNT(*) FROM `products`")->fetchColumn();
        $userCount = $pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
        $adminCount = $pdo->query("SELECT COUNT(*) FROM `admins`")->fetchColumn();

        echo "[SUCCESS] Database `{$targetDb}` created and initialized!\n";
        echo " - Categories: {$catCount}\n";
        echo " - Products:   {$prodCount}\n";
        echo " - Users:      {$userCount}\n";
        echo " - Admins:     {$adminCount}\n";
        echo "========================================================\n";
        $success = true;
        break;
    } catch (Exception $e) {
        echo "[FAIL] Port {$port}: " . $e->getMessage() . "\n";
    }
}

if (!$success) {
    echo "Could not initialize database on ports " . implode(', ', $ports) . "\n";
    exit(1);
}
