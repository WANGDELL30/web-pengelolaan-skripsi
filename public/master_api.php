<?php
/**
 * API endpoint to fetch router status data from the master device.
 * Handles LuCI ubus authentication and returns JSON data.
 */
session_start();
require_once __DIR__ . '/../app/Helpers/functions.php';

if (!isLoggedIn()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Login diperlukan']);
    exit;
}
session_write_close();

header('Content-Type: application/json; charset=utf-8');

$masterHost = '192.168.1.50';
$masterBaseUrl = 'http://' . $masterHost;
$luciUser = 'root';
$luciPass = 'psn2026';
$tokenFile = sys_get_temp_dir() . '/luci_ubus_token.json';

function ubusRequest($masterBaseUrl, $sessionToken, $subsystem, $method, $params = []) {
    $ch = curl_init($masterBaseUrl . '/ubus');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
        ],
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
    curl_close($ch);

    if ($err || $result === false) return null;
    return json_decode($result, true);
}

function ubusLogin($masterBaseUrl, $user, $pass) {
    $ch = curl_init($masterBaseUrl . '/ubus');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
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
    curl_close($ch);

    if ($err || $result === false) return null;

    $data = json_decode($result, true);
    if (isset($data['result'][1]['ubus_rpc_session'])) {
        return $data['result'][1]['ubus_rpc_session'];
    }
    return null;
}

function getSessionToken($masterBaseUrl, $user, $pass, $tokenFile) {
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
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
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
    curl_close($ch);
}

curl_multi_close($mh);

echo json_encode($results);
