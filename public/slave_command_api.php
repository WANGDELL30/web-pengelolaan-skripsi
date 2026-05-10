<?php
/**
 * Browser/API bridge for sending UDP control commands to a Wi-Fi HaLow slave.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function commandApiRespond(int $statusCode, array $payload): void {
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function commandApiNowMs(): int {
    return (int) floor(microtime(true) * 1000);
}

function commandApiNormalizeCommand(string $command, string $argument): array {
    $command = strtoupper(trim($command));
    $argument = strtoupper(trim($argument));

    $allowed = ['PING', 'STATUS', 'LED', 'RESTART', 'REBOOT', 'HELP'];
    if (!in_array($command, $allowed, true)) {
        throw new RuntimeException('Command tidak diizinkan.');
    }

    if ($command === 'LED') {
        $allowedLedArgs = ['ON', 'OFF', 'TOGGLE'];
        if (!in_array($argument, $allowedLedArgs, true)) {
            throw new RuntimeException('Command LED hanya menerima argumen ON, OFF, atau TOGGLE.');
        }
        return [$command, $argument, $command . ' ' . $argument];
    }

    return [$command, '', $command];
}

function commandApiNormalizeHost(string $host): string {
    $host = trim($host);
    if ($host === '') {
        return '';
    }

    if (strpos($host, '://') !== false) {
        $parsedHost = parse_url($host, PHP_URL_HOST);
        return is_string($parsedHost) ? $parsedHost : '';
    }

    $host = rtrim($host, '/');
    if (strpos($host, ':') !== false) {
        $parsedHost = parse_url('udp://' . $host, PHP_URL_HOST);
        return is_string($parsedHost) ? $parsedHost : $host;
    }

    return $host;
}

function commandApiIsAllowedHost(string $host): bool {
    if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return false;
    }

    $long = ip2long($host);
    if ($long === false) {
        return false;
    }

    $ranges = [
        ['10.0.0.0', '10.255.255.255'],
        ['172.16.0.0', '172.31.255.255'],
        ['192.168.0.0', '192.168.255.255'],
        ['169.254.0.0', '169.254.255.255'],
    ];

    foreach ($ranges as [$start, $end]) {
        if ($long >= ip2long($start) && $long <= ip2long($end)) {
            return true;
        }
    }

    return false;
}

if (!isLoggedIn()) {
    commandApiRespond(401, ['status' => 'error', 'message' => 'Session login tidak valid.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    commandApiRespond(405, ['status' => 'error', 'message' => 'Gunakan method POST.']);
}

$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input)) {
    $input = $_POST;
}

$slaveIp = commandApiNormalizeHost((string) ($input['slave_ip'] ?? ''));
$targetNodeId = trim((string) ($input['target_node_id'] ?? 'SLAVE_001'));
$source = trim((string) ($input['source'] ?? 'WEB_MASTER'));
$token = trim((string) ($input['token'] ?? 'halow123'));
$command = trim((string) ($input['command'] ?? ''));
$argument = trim((string) ($input['argument'] ?? ''));
$port = (int) ($input['port'] ?? 5555);
$timeoutMs = (int) ($input['timeout_ms'] ?? 3000);

try {
    if (!commandApiIsAllowedHost($slaveIp)) {
        throw new RuntimeException('IP slave harus IPv4 private/LAN, contoh 192.168.1.50.');
    }

    if ($port < 1 || $port > 65535) {
        throw new RuntimeException('Port UDP tidak valid.');
    }

    if ($token === '' || strlen($token) > 64) {
        throw new RuntimeException('Token wajib diisi dan maksimal 64 karakter.');
    }

    if ($targetNodeId === '' || strlen($targetNodeId) > 50) {
        throw new RuntimeException('Target Node ID wajib diisi dan maksimal 50 karakter.');
    }

    if ($source === '' || strlen($source) > 50) {
        throw new RuntimeException('Source wajib diisi dan maksimal 50 karakter.');
    }

    $timeoutMs = max(500, min(10000, $timeoutMs));
    [$normalizedCommand, $normalizedArgument, $commandLabel] = commandApiNormalizeCommand($command, $argument);

    $payload = $token . ' ' . $normalizedCommand;
    if ($normalizedArgument !== '') {
        $payload .= ' ' . $normalizedArgument;
    }

    $sentAt = commandApiNowMs();
    $errno = 0;
    $errstr = '';
    $socket = @stream_socket_client(
        'udp://' . $slaveIp . ':' . $port,
        $errno,
        $errstr,
        $timeoutMs / 1000,
        STREAM_CLIENT_CONNECT
    );

    if (!$socket) {
        throw new RuntimeException('Gagal membuka UDP socket: ' . ($errstr ?: 'errno ' . $errno));
    }

    stream_set_timeout($socket, intdiv($timeoutMs, 1000), ($timeoutMs % 1000) * 1000);
    $written = fwrite($socket, $payload);
    if ($written === false || $written <= 0) {
        fclose($socket);
        throw new RuntimeException('Gagal mengirim paket UDP ke slave.');
    }

    $reply = fread($socket, 512);
    $meta = stream_get_meta_data($socket);
    fclose($socket);

    $receivedAt = commandApiNowMs();
    $reply = trim((string) $reply);
    $timedOut = !empty($meta['timed_out']) && $reply === '';
    $executionStatus = (!$timedOut && strpos($reply, 'OK') === 0) ? 'success' : 'fail';
    $executedAt = $receivedAt;

    $deliveryDelay = max(0, $receivedAt - $sentAt);
    $executionDelay = max(0, $executedAt - $receivedAt);
    $totalTime = max(0, $executedAt - $sentAt);

    query(
        "INSERT INTO command_execution_tests (
            test_date, command_type, source, target_node_id,
            command_sent_time_ms, command_received_time_ms, command_executed_time_ms,
            execution_status, command_delivery_delay, command_execution_delay,
            total_command_time, command_success_rate, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [
            date('Y-m-d'),
            strtolower($commandLabel),
            $source,
            $targetNodeId,
            $sentAt,
            $receivedAt,
            $executedAt,
            $executionStatus,
            $deliveryDelay,
            $executionDelay,
            $totalTime,
            $executionStatus === 'success' ? 100 : 0,
            'UDP ' . $slaveIp . ':' . $port . ' | payload=' . $payload . ' | reply=' . ($reply ?: ($timedOut ? 'timeout' : '-')),
        ]
    );

    commandApiRespond(200, [
        'status' => $executionStatus,
        'message' => $executionStatus === 'success' ? 'Command terkirim dan ACK diterima.' : 'Command terkirim tetapi ACK gagal/timeout.',
        'slave_ip' => $slaveIp,
        'port' => $port,
        'target_node_id' => $targetNodeId,
        'payload' => $payload,
        'reply' => $reply,
        'timed_out' => $timedOut,
        'timing' => [
            'sent_ms' => $sentAt,
            'received_ms' => $receivedAt,
            'executed_ms' => $executedAt,
            'delivery_delay_ms' => $deliveryDelay,
            'execution_delay_ms' => $executionDelay,
            'total_ms' => $totalTime,
        ],
    ]);
} catch (Throwable $e) {
    $now = commandApiNowMs();
    $safeCommand = $command !== '' ? strtolower($command . ($argument !== '' ? ' ' . $argument : '')) : 'unknown';

    try {
        query(
            "INSERT INTO command_execution_tests (
                test_date, command_type, source, target_node_id,
                command_sent_time_ms, command_received_time_ms, command_executed_time_ms,
                execution_status, command_delivery_delay, command_execution_delay,
                total_command_time, command_success_rate, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'fail', 0, 0, 0, 0, ?)",
            [
                date('Y-m-d'),
                $safeCommand,
                $source ?: 'WEB_MASTER',
                $targetNodeId ?: 'UNKNOWN',
                $now,
                $now,
                $now,
                'UDP command error: ' . $e->getMessage(),
            ]
        );
    } catch (Throwable $ignored) {
    }

    commandApiRespond(400, ['status' => 'error', 'message' => $e->getMessage()]);
}
