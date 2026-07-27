<?php

/**
 * Shared configuration for the Raspberry Pi Wi-Fi HaLow master.
 *
 * The hostname is stored in the database so it can be changed from the
 * dashboard without editing PHP files. MASTER_HOST may be used as an
 * environment override on deployments where the database is read-only.
 */

function masterDeviceEnsureSettingsTable(PDO $pdo) {
    static $ready = false;

    if ($ready) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS app_settings (
            setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
            setting_value TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $statement = $pdo->prepare(
        "INSERT INTO app_settings (setting_key, setting_value)
         VALUES ('master_host', ?)
         ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key)"
    );
    $statement->execute(['adminpsn.local']);
    $ready = true;
}

function masterDeviceIsPrivateIpv4($ip) {
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return false;
    }

    $address = ip2long($ip);
    $ranges = [
        [ip2long('10.0.0.0'), ip2long('10.255.255.255')],
        [ip2long('172.16.0.0'), ip2long('172.31.255.255')],
        [ip2long('192.168.0.0'), ip2long('192.168.255.255')],
    ];

    foreach ($ranges as [$start, $end]) {
        if ($address >= $start && $address <= $end) {
            return true;
        }
    }

    return false;
}

function masterDeviceNormalizeHost($value) {
    $host = strtolower(trim((string) $value));
    $host = rtrim($host, '.');

    if ($host === '') {
        throw new InvalidArgumentException('IP atau hostname master wajib diisi.');
    }

    if (strpos($host, '://') !== false || preg_match('/[\/\\\\?#@:\s]/', $host)) {
        throw new InvalidArgumentException(
            'Isi hanya IP atau hostname, tanpa http://, port, maupun path.'
        );
    }

    if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        if (!masterDeviceIsPrivateIpv4($host)) {
            throw new InvalidArgumentException('IP master harus berupa IPv4 jaringan lokal/private.');
        }

        return $host;
    }

    $validHostname = preg_match(
        '/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*'
        . '[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/',
        $host
    );

    if (!$validHostname || (strpos($host, '.') !== false && substr($host, -6) !== '.local')) {
        throw new InvalidArgumentException(
            'Hostname harus berupa nama lokal, misalnya adminpsn atau adminpsn.local.'
        );
    }

    $resolvedIp = gethostbyname($host);
    if ($resolvedIp !== $host && !masterDeviceIsPrivateIpv4($resolvedIp)) {
        throw new InvalidArgumentException('Hostname master harus mengarah ke jaringan lokal/private.');
    }

    return $host;
}

function masterDeviceGetHost(PDO $pdo) {
    $environmentHost = getenv('MASTER_HOST');
    if ($environmentHost !== false && trim($environmentHost) !== '') {
        return masterDeviceNormalizeHost($environmentHost);
    }

    masterDeviceEnsureSettingsTable($pdo);
    $statement = $pdo->prepare(
        "SELECT setting_value FROM app_settings WHERE setting_key = 'master_host' LIMIT 1"
    );
    $statement->execute();
    $storedHost = $statement->fetchColumn();

    try {
        return masterDeviceNormalizeHost($storedHost ?: 'adminpsn.local');
    } catch (InvalidArgumentException $exception) {
        return 'adminpsn.local';
    }
}

function masterDeviceSaveHost(PDO $pdo, $host) {
    if (getenv('MASTER_HOST')) {
        throw new RuntimeException(
            'Alamat master dikunci oleh environment variable MASTER_HOST.'
        );
    }

    $host = masterDeviceNormalizeHost($host);
    masterDeviceEnsureSettingsTable($pdo);

    $statement = $pdo->prepare(
        "INSERT INTO app_settings (setting_key, setting_value)
         VALUES ('master_host', ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    );
    $statement->execute([$host]);

    return $host;
}

function masterDeviceGetConfig(PDO $pdo) {
    $host = masterDeviceGetHost($pdo);
    $resolvedIp = gethostbyname($host);
    $hasResolvedIp = filter_var(
        $resolvedIp,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_IPV4
    ) !== false;

    return [
        'host' => $host,
        'base_url' => 'http://' . $host,
        'resolved_ip' => $hasResolvedIp ? $resolvedIp : null,
        'connect_host' => $hasResolvedIp ? $resolvedIp : $host,
        'connect_base_url' => 'http://' . ($hasResolvedIp ? $resolvedIp : $host),
        'environment_override' => (bool) getenv('MASTER_HOST'),
    ];
}

function masterDeviceTokenFile($type, $host) {
    $safeType = preg_replace('/[^a-z0-9_-]/i', '', (string) $type);
    $hostKey = substr(hash('sha256', strtolower((string) $host)), 0, 16);

    return sys_get_temp_dir() . '/luci_' . $safeType . '_' . $hostKey . '.json';
}

function masterDeviceCheckHttp($host) {
    $host = masterDeviceNormalizeHost($host);
    $resolvedIp = gethostbyname($host);
    $connectHost = filter_var($resolvedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
        ? $resolvedIp
        : $host;
    $url = 'http://' . $connectHost . '/cgi-bin/luci/';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_NOBODY => true,
    ]);
    curl_exec($ch);
    $errorNumber = curl_errno($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'online' => $errorNumber === 0 && $statusCode > 0,
        'http_status' => $statusCode ?: null,
    ];
}
