<?php
/**
 * API endpoint to fetch router status data from the master device.
 * Handles LuCI ubus authentication and returns JSON data.
 */
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Helpers/master_device.php';

if (!isLoggedIn()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Login diperlukan']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!canManageProject()) {
        http_response_code(403);
        echo json_encode(['error' => 'Hanya admin yang dapat mengubah alamat master.']);
        exit;
    }

    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    $sessionToken = $_SESSION['master_config_csrf'] ?? '';
    $requestToken = (string) ($payload['csrf_token'] ?? '');
    if ($sessionToken === '' || !hash_equals($sessionToken, $requestToken)) {
        http_response_code(419);
        echo json_encode(['error' => 'Sesi pengaturan kedaluwarsa. Muat ulang halaman.']);
        exit;
    }

    if (($payload['action'] ?? '') !== 'save_config') {
        http_response_code(400);
        echo json_encode(['error' => 'Aksi pengaturan tidak dikenali.']);
        exit;
    }

    try {
        $oldHost = masterDeviceGetHost($pdo);
        $masterHost = masterDeviceSaveHost($pdo, $payload['host'] ?? '');

        foreach ([$oldHost, $masterHost] as $tokenHost) {
            @unlink(masterDeviceTokenFile('sysauth', $tokenHost));
            @unlink(masterDeviceTokenFile('ubus', $tokenHost));
        }
        @unlink(sys_get_temp_dir() . '/luci_sysauth_token.json');
        @unlink(sys_get_temp_dir() . '/luci_ubus_token.json');

        $config = masterDeviceGetConfig($pdo);
        $connection = masterDeviceCheckHttp($masterHost);
        echo json_encode([
            'success' => true,
            'message' => $connection['online']
                ? 'Alamat master tersimpan dan panel dapat dijangkau.'
                : 'Alamat tersimpan, tetapi panel belum dapat dijangkau.',
            'config' => $config,
            'connection' => $connection,
        ]);
    } catch (InvalidArgumentException $exception) {
        http_response_code(422);
        echo json_encode(['error' => $exception->getMessage()]);
    } catch (Throwable $exception) {
        http_response_code(500);
        echo json_encode(['error' => 'Gagal menyimpan alamat master.']);
    }
    exit;
}

session_write_close();

$_masterCfg = [];
$_masterLocalFile = __DIR__ . '/../config/master.local.php';
if (is_file($_masterLocalFile)) {
    $_masterCfg = require $_masterLocalFile;
}

$masterConfig = masterDeviceGetConfig($pdo);
$masterHost = $masterConfig['connect_host'];
$masterBaseUrl = $masterConfig['connect_base_url'];
$luciUser = getenv('LUCI_USER') ?: ($_masterCfg['luci_user'] ?? 'root');
$luciPass = getenv('LUCI_PASS') ?: ($_masterCfg['luci_pass'] ?? 'psn2026');
unset($_masterCfg, $_masterLocalFile);

$tokenFile = masterDeviceTokenFile('ubus', $masterHost);

function masterApiHeaders($headers = []) {
    return array_merge($headers, masterDeviceCloudflareAccessHeaders());
}

function ubusRequest(string $masterBaseUrl, string $sessionToken, string $subsystem, string $method, $params = []) {
    $ch = curl_init($masterBaseUrl . '/ubus');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => masterApiHeaders([
            'Content-Type: application/json',
        ]),
        CURLOPT_POSTFIELDS => json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'call',
            'params' => [$sessionToken, $subsystem, $method, $params ?: new \stdClass()]
        ]),
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
    ]);
    $result = curl_exec($ch);
    $err = curl_errno($ch);
    unset($ch);

    if ($err || $result === false) return null;
    return json_decode($result, true);
}

function ubusLogin(string $masterBaseUrl, string $user, string $pass) {
    $ch = curl_init($masterBaseUrl . '/ubus');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => masterApiHeaders(['Content-Type: application/json']),
        CURLOPT_POSTFIELDS => json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'call',
            'params' => [
                '00000000000000000000000000000000',
                'session',
                'login',
                ['username' => $user, 'password' => $pass]
            ]
        ]),
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    ]);
    $result = curl_exec($ch);
    $err = curl_errno($ch);
    unset($ch);

    if ($err || $result === false) return null;

    $data = json_decode($result, true);
    if (isset($data['result'][1]['ubus_rpc_session'])) {
        return $data['result'][1]['ubus_rpc_session'];
    }
    return null;
}

function getSessionToken(string $masterBaseUrl, string $user, string $pass, string $tokenFile) {
    // Check cached token
    if (file_exists($tokenFile)) {
        $cached = json_decode(file_get_contents($tokenFile), true);
        if ($cached && !empty($cached['token']) && ($cached['expires'] ?? 0) > time()) {
            // Verify token is still valid
            $test = ubusRequest($masterBaseUrl, $cached['token'], 'session', 'list', new \stdClass());
            if ($test && isset($test['result']) && (!isset($test['result'][0]) || $test['result'][0] === 0)) {
                return $cached['token'];
            }
        }
    }

    // Login fresh
    $token = ubusLogin($masterBaseUrl, $user, $pass);
    if ($token) {
        file_put_contents($tokenFile, json_encode([
            'token' => $token,
            'expires' => time() + 3500, // slightly less than 1 hour
        ]));
    }
    return $token;
}

// Get session token
$token = getSessionToken($masterBaseUrl, $luciUser, $luciPass, $tokenFile);

if (!$token) {
    echo json_encode(['error' => 'Gagal login ke perangkat master', 'online' => false]);
    exit;
}

// Fetch all data in parallel using multi-curl
$calls = [
    'system_info' => ['system', 'info', new \stdClass()],
    'system_board' => ['system', 'board', new \stdClass()],
    'network_devices' => ['luci-rpc', 'getNetworkDevices', new \stdClass()],
    'wireless' => ['luci-rpc', 'getWirelessDevices', new \stdClass()],
    'dhcp_leases' => ['luci-rpc', 'getDHCPLeases', new \stdClass()],
];

$mh = curl_multi_init();
$handles = [];

foreach ($calls as $key => [$subsystem, $method, $params]) {
    $ch = curl_init($masterBaseUrl . '/ubus');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => masterApiHeaders(['Content-Type: application/json']),
        CURLOPT_POSTFIELDS => json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'call',
            'params' => [$token, $subsystem, $method, $params]
        ]),
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
    ]);
    curl_multi_add_handle($mh, $ch);
    $handles[$key] = $ch;
}

// Execute all requests
do {
    $status = curl_multi_exec($mh, $active);
    if ($active) {
        curl_multi_select($mh);
    }
} while ($active && $status === CURLM_OK);

// Collect results
$results = ['online' => true];
foreach ($handles as $key => $ch) {
    $body = curl_multi_getcontent($ch);
    $data = json_decode($body, true);

    if (isset($data['result'][1])) {
        $results[$key] = $data['result'][1];
    } elseif (isset($data['result'][0]) && $data['result'][0] === 6) {
        // Session expired - invalidate and report
        @unlink($tokenFile);
        $results[$key] = null;
    } else {
        $results[$key] = null;
    }

    curl_multi_remove_handle($mh, $ch);
    unset($ch);
}

unset($mh);

echo json_encode($results);
