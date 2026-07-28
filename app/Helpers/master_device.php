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

function masterDeviceRequestIsLocal() {
    $requestHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $requestHost = preg_replace('/:\d+$/', '', $requestHost);

    if (
        in_array($requestHost, ['localhost', '127.0.0.1', '::1'], true)
        || substr($requestHost, -6) === '.local'
    ) {
        return true;
    }

    return masterDeviceIsPrivateIpv4($requestHost);
}

function masterDeviceCloudflareConfig() {
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $config = [
        'public_url' => getenv('MASTER_PUBLIC_URL')
            ?: 'https://luci.arndilhmzbr.engineer',
        'client_id' => getenv('CF_ACCESS_CLIENT_ID') ?: '',
        'client_secret' => getenv('CF_ACCESS_CLIENT_SECRET') ?: '',
    ];

    $localConfigFile = __DIR__ . '/../../config/cloudflare_access.local.php';
    if (is_file($localConfigFile)) {
        $localConfig = require $localConfigFile;
        if (is_array($localConfig)) {
            $config = array_merge(
                $config,
                array_intersect_key($localConfig, $config)
            );
        }
    }

    $config['public_url'] = rtrim(trim((string) $config['public_url']), '/');
    $config['client_id'] = trim((string) $config['client_id']);
    $config['client_secret'] = trim((string) $config['client_secret']);

    $urlParts = parse_url($config['public_url']);
    if (
        !$urlParts
        || strtolower((string) ($urlParts['scheme'] ?? '')) !== 'https'
        || empty($urlParts['host'])
        || isset($urlParts['user'])
        || isset($urlParts['pass'])
        || isset($urlParts['query'])
        || isset($urlParts['fragment'])
    ) {
        $config['public_url'] = 'https://luci.arndilhmzbr.engineer';
    }

    return $config;
}

function masterDeviceCloudflareServiceConfigured() {
    $config = masterDeviceCloudflareConfig();

    return $config['client_id'] !== '' && $config['client_secret'] !== '';
}

function masterDeviceCloudflareAccessHeaders() {
    if (!masterDeviceCloudflareServiceConfigured()) {
        return [];
    }

    $config = masterDeviceCloudflareConfig();

    return [
        'CF-Access-Client-Id: ' . $config['client_id'],
        'CF-Access-Client-Secret: ' . $config['client_secret'],
    ];
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

    $localConnectHost = $hasResolvedIp ? $resolvedIp : $host;
    $localConnectBaseUrl = 'http://' . $localConnectHost;
    $cloudflareConfig = masterDeviceCloudflareConfig();
    $requestIsLocal = masterDeviceRequestIsLocal();
    $usePublicTunnel = !$requestIsLocal
        && masterDeviceCloudflareServiceConfigured();
    $connectBaseUrl = $usePublicTunnel
        ? $cloudflareConfig['public_url']
        : $localConnectBaseUrl;
    $connectHost = parse_url($connectBaseUrl, PHP_URL_HOST) ?: $localConnectHost;

    return [
        'host' => $host,
        'base_url' => 'http://' . $host,
        'resolved_ip' => $hasResolvedIp ? $resolvedIp : null,
        'connect_host' => $connectHost,
        'connect_base_url' => $connectBaseUrl,
        'public_url' => $cloudflareConfig['public_url'],
        'request_is_local' => $requestIsLocal,
        'cloudflare_service_configured' => masterDeviceCloudflareServiceConfigured(),
        'uses_public_tunnel' => $usePublicTunnel,
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
