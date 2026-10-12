<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Database Connection Layer (PDO with Resilient Auto-Port Probing)
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    private static ?int $connectedPort = null;

    /**
     * Get the active PDO connection instance
     * Automatically probes available MySQL ports (3307 & 3306)
     *
     * @return PDO
     * @throws Exception
     */
    public static function getConnection(): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $user = getenv('DB_USER') ?: 'root';
        $pass = '';
        if (getenv('DB_PASSWORD') !== false) {
            $pass = getenv('DB_PASSWORD');
        } elseif (getenv('DB_PASS') !== false) {
            $pass = getenv('DB_PASS');
        }
        $customPort = getenv('DB_PORT') ? (int)getenv('DB_PORT') : null;

        // Candidate ports to test: custom if defined, then standard XAMPP (3307 & 3306)
        $candidatePorts = $customPort ? [$customPort] : [3307, 3306];
        // Candidate database names: explicit env var if provided, or fallback sequence
        $candidateDbs = getenv('DB_NAME') ? [getenv('DB_NAME')] : ['shivkrupa_db', 'shivkrupa_art_jewellery'];
        $lastException = null;

        foreach ($candidateDbs as $db) {
            foreach ($candidatePorts as $port) {
                try {
                    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
                    $options = [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                        PDO::ATTR_TIMEOUT            => 2, // 2 second probe timeout
                    ];

                    self::$instance = new PDO($dsn, $user, $pass, $options);
                    self::$connectedPort = $port;
                    return self::$instance;
                } catch (PDOException $e) {
                    $lastException = $e;
                    continue; // Try next candidate port / db
                }
            }
        }

        // Both ports failed
        error_log("Database connection error: " . $lastException->getMessage());

        $errorMessage = htmlspecialchars($lastException->getMessage());
        $testedPorts = implode(', ', $candidatePorts);

        die("
            <div style='font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;max-width:600px;margin:40px auto;padding:24px;border-left:4px solid #C5A059;background:#fff8ee;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.06);color:#333;'>
                <h2 style='color:#8c6d23;margin-top:0;'>SHIVKRUPA ART JEWELLERY — Database Notice</h2>
                <p>Could not connect to database <code>{$db}</code> on host <code>{$host}</code> (tested ports: {$testedPorts}).</p>
                <p style='background:#fff;padding:10px 14px;border-radius:4px;border:1px solid #e0d0b0;font-size:13px;font-family:monospace;'>{$errorMessage}</p>
                <p><strong>Quick Fix for XAMPP:</strong></p>
                <ol style='padding-left:20px;line-height:1.6;'>
                    <li>Open <strong>XAMPP Control Panel</strong> and verify <strong>MySQL</strong> is started.</li>
                    <li>If the database is not yet initialized, run: <code>php database/setup-database.php</code> in the project root.</li>
                </ol>
            </div>
        ");
    }

    /**
     * Returns the active port number connected to MySQL
     */
    public static function getConnectedPort(): ?int {
        return self::$connectedPort;
    }
}

/**
 * Global helper function to retrieve the active PDO database connection
 *
 * @return PDO
 */
function getDB(): PDO {
    return Database::getConnection();
}
