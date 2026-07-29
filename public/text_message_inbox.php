<?php
/**
 * IoT Inbox Endpoint — menerima pesan teks dari slave (ESP32/Raspberry Pi).
 *
 * Autentikasi: token statis di header X-Device-Token atau parameter ?token=
 * Atur token di: config/master.local.php  (key: 'inbox_token')
 * Fallback env:  INBOX_TOKEN
 * Fallback default: 'halow-inbox-2026'  ← ganti di production!
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, X-Device-Token');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// --- Token auth untuk endpoint IoT ---
$expectedToken = getenv('INBOX_TOKEN') ?: 'halow-inbox-2026';

// Bisa juga override dari config/master.local.php
$masterLocalConfig = __DIR__ . '/../config/master.local.php';
if (is_file($masterLocalConfig)) {
    $masterCfg = require $masterLocalConfig;
    if (is_array($masterCfg) && !empty($masterCfg['inbox_token'])) {
        $expectedToken = $masterCfg['inbox_token'];
    }
}

$receivedToken = $_SERVER['HTTP_X_DEVICE_TOKEN']
    ?? ($_GET['token'] ?? ($_POST['token'] ?? ''));

if (trim($receivedToken) !== $expectedToken) {
    http_response_code(401);
    echo json_encode([
        'ok'    => false,
        'error' => 'unauthorized: invalid or missing device token',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function inbox_json_response($statusCode, array $payload) {
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$input = $_GET;
$rawBody = file_get_contents('php://input');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = json_decode($rawBody ?: '', true);
    if (is_array($json)) {
        $input = array_merge($input, $json);
    } else {
        $input = array_merge($input, $_POST);
    }
}

$source = trim((string) ($input['source'] ?? 'SLAVE-HALOW-01'));
$target = trim((string) ($input['target'] ?? 'MASTER-RASPI-4'));
$message = trim((string) ($input['message'] ?? ($input['text'] ?? '')));
$sourceIp = $_SERVER['REMOTE_ADDR'] ?? '';
$rssiDbm = isset($input['rssi_dbm']) && is_numeric($input['rssi_dbm']) ? (int) $input['rssi_dbm'] : null;
$uptimeMs = isset($input['uptime_ms']) && is_numeric($input['uptime_ms']) ? (int) $input['uptime_ms'] : null;

if ($source === '') {
    $source = 'SLAVE-HALOW-01';
}

if ($target === '') {
    $target = 'MASTER-RASPI-4';
}

if ($message === '') {
    inbox_json_response(400, [
        'ok' => false,
        'error' => 'message is empty',
    ]);
}

if (strlen($message) > 512) {
    inbox_json_response(413, [
        'ok' => false,
        'error' => 'message too large',
    ]);
}

$rawPayload = json_encode([
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
    'query' => $_GET,
    'body' => $rawBody,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

try {
    query(
        "INSERT INTO text_message_inbox_logs (
            received_at, source_node, target_node_id, source_ip, message_text,
            raw_payload, rssi_dbm, slave_uptime_ms, delivery_status
        ) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, 'success')",
        [
            substr($source, 0, 50),
            substr($target, 0, 50),
            substr($sourceIp, 0, 45),
            $message,
            $rawPayload,
            $rssiDbm,
            $uptimeMs,
        ]
    );

    inbox_json_response(200, [
        'ok' => true,
        'direction' => 'slave_to_master',
        'id' => (int) lastInsertId(),
        'received_at' => date('c'),
        'source' => $source,
        'target' => $target,
        'source_ip' => $sourceIp,
        'bytes' => strlen($message),
        'message' => $message,
    ]);
} catch (PDOException $e) {
    inbox_json_response(500, [
        'ok' => false,
        'error' => 'database insert failed',
        'detail' => $e->getMessage(),
    ]);
}
