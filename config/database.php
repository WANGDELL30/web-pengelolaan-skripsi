<?php
/**
 * Database Configuration for WiFi HaLow Testing System
 * Design and Implementation of a Wi-Fi HaLow-Based Tactical Monitoring and Communication Support System
 */

// Default XAMPP/local database configuration.
// Hosting credentials are injected only into the generated deployment package.
$config = [
    'host' => 'localhost',
    'dbname' => 'wifi_holow_testing',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

$localConfigFile = __DIR__ . '/database.local.php';
if (is_file($localConfigFile)) {
    $localConfig = require $localConfigFile;
    if (is_array($localConfig)) {
        $config = array_merge($config, array_intersect_key($localConfig, $config));
    }
}

$config['host'] = getenv('DB_HOST') ?: $config['host'];
$config['dbname'] = getenv('DB_NAME') ?: $config['dbname'];
$config['username'] = getenv('DB_USER') ?: $config['username'];
$config['password'] = getenv('DB_PASS') ?: $config['password'];

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

/**
 * Helper function to execute queries
 */
function query($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Helper function to fetch all results
 */
function fetchAll($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Helper function to fetch single result
 */
function fetchOne($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetch();
}

/**
 * Helper function to get last insert ID
 */
function lastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}
