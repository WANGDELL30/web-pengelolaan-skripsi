<?php
if (!function_exists('sanitize')) {
    require_once __DIR__ . '/../../../app/Helpers/functions.php';
}

if (!function_exists('textCommunicationNormalizeEndpoint')) {
    function textCommunicationNormalizeEndpoint($endpoint) {
        $endpoint = trim((string) $endpoint);

        if ($endpoint === '') {
            return '/api/message';
        }

        return $endpoint[0] === '/' ? $endpoint : '/' . $endpoint;
    }
}

if (!function_exists('textCommunicationHttpStatus')) {
    function textCommunicationHttpStatus($headers) {
        foreach ((array) $headers as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#', (string) $header, $matches)) {
                return (int) $matches[1];
            }
        }

        return 0;
    }
}

if (!function_exists('textCommunicationIsApiEndpoint')) {
    function textCommunicationIsApiEndpoint($endpoint) {
        return strpos(textCommunicationNormalizeEndpoint($endpoint), '/api/') === 0;
    }
}

if (!function_exists('textCommunicationRequest')) {
    function textCommunicationRequest($method, $url, $body = null, $timeout = 5) {
        $startedAt = microtime(true);
        $timeout = max(1, min(60, (int) $timeout));
        $headers = [
            'Accept: application/json',
            'Connection: close',
        ];

        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_HTTPHEADER => $headers,
            ]);

            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            $responseBody = curl_exec($ch);
            $error = curl_errno($ch) ? curl_error($ch) : null;
            $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return [
                'status_code' => $statusCode,
                'body' => $responseBody === false ? '' : (string) $responseBody,
                'error' => $error,
                'latency_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            ];
        }

        $context = [
            'http' => [
                'method' => $method,
                'timeout' => $timeout,
                'ignore_errors' => true,
                'header' => implode("\r\n", $headers),
            ],
        ];

        if ($body !== null) {
            $context['http']['content'] = $body;
        }

        $responseBody = @file_get_contents($url, false, stream_context_create($context));
        $responseHeaders = $http_response_header ?? [];

        return [
            'status_code' => textCommunicationHttpStatus($responseHeaders),
            'body' => $responseBody === false ? '' : (string) $responseBody,
            'error' => $responseBody === false ? 'Request gagal atau timeout.' : null,
            'latency_ms' => round((microtime(true) - $startedAt) * 1000, 2),
        ];
    }
}

$success = null;
$error = null;
$notice = null;
$statusResult = null;
$form = [
    'test_date' => date('Y-m-d'),
    'source_node' => 'MASTER-RASPI-4',
    'target_node_id' => 'SLAVE-HALOW-01',
    'target_ip' => $_GET['target_ip'] ?? '',
    'target_port' => '80',
    'endpoint' => '/api/message',
    'timeout' => '20',
    'message_text' => '',
];

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/public/index.php');
$publicPath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$publicPath = $publicPath === '/' ? '' : $publicPath;
$requestHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$hostOnly = preg_replace('/:\d+$/', '', $requestHost);
$masterHost = in_array($hostOnly, ['localhost', '127.0.0.1', '::1'], true) ? '192.168.1.112' : $requestHost;
$defaultMasterInboxUrl = 'http://' . $masterHost . $publicPath . '/text_message_inbox.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_message_action'])) {
    foreach ($form as $key => $value) {
        if (isset($_POST[$key])) {
            $form[$key] = trim((string) $_POST[$key]);
        }
    }

    $action = $_POST['_message_action'];
    $targetIp = $form['target_ip'];
    $targetPort = (int) $form['target_port'];
    $endpoint = textCommunicationNormalizeEndpoint($form['endpoint']);
    $timeout = (int) $form['timeout'];

    try {
        if (!filter_var($targetIp, FILTER_VALIDATE_IP)) {
            throw new RuntimeException('IP slave tidak valid.');
        }

        if ($targetPort < 1 || $targetPort > 65535) {
            throw new RuntimeException('Port slave harus di antara 1 sampai 65535.');
        }

        if ($action === 'browser_log') {
            query(
                "INSERT INTO text_message_logs (
                    test_date, source_node, target_node_id, target_ip, target_port, protocol,
                    endpoint, message_text, request_payload, response_status_code, response_body,
                    latency_ms, delivery_status, error_message, sent_at
                ) VALUES (?, ?, ?, ?, ?, 'HTTP', ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $form['test_date'] ?: date('Y-m-d'),
                    $form['source_node'] ?: 'MASTER-RASPI-4',
                    $form['target_node_id'] ?: 'SLAVE-HALOW-01',
                    $targetIp,
                    $targetPort,
                    $endpoint,
                    trim($form['message_text']),
                    $_POST['request_payload'] ?? null,
                    (int) ($_POST['response_status_code'] ?? 0),
                    $_POST['response_body'] ?? '',
                    (float) ($_POST['latency_ms'] ?? 0),
                    ($_POST['delivery_status'] ?? '') === 'success' ? 'success' : 'fail',
                    $_POST['error_message'] ?? null,
                ]
            );

            if (($_POST['_ajax'] ?? '') === '1') {
                header('Content-Type: application/json');
                echo json_encode(['ok' => true]);
                exit;
            }
        }

        $actualTargetPort = $targetPort;

        if ($actualTargetPort === 5001 && ($action === 'status' || textCommunicationIsApiEndpoint($endpoint))) {
            $actualTargetPort = 80;
            $form['target_port'] = '80';
            $notice = 'Port 5001 dipakai untuk iperf. Request HTTP API dialihkan ke port 80.';
        }

        $url = 'http://' . $targetIp . ':' . $actualTargetPort . $endpoint;

        if ($action === 'status') {
            $statusUrl = 'http://' . $targetIp . ':' . $actualTargetPort . '/api/status';
            $statusResult = textCommunicationRequest('GET', $statusUrl, null, $timeout);

            if (($statusResult['error'] || ($statusResult['status_code'] ?? 0) === 0) && $actualTargetPort !== 80) {
                $actualTargetPort = 80;
                $statusUrl = 'http://' . $targetIp . ':80/api/status';
                $statusResult = textCommunicationRequest('GET', $statusUrl, null, $timeout);

                if (!$statusResult['error'] && ($statusResult['status_code'] ?? 0) >= 200 && ($statusResult['status_code'] ?? 0) < 300) {
                    $notice = 'Status berhasil dibaca via port 80. Port 5001 dipakai iperf, bukan HTTP API.';
                    $form['target_port'] = '80';
                }
            }

            if ($statusResult['error']) {
                throw new RuntimeException('Gagal membaca status slave dari server PHP: ' . $statusResult['error'] . '. Jika IP slave bisa dibuka dari Chrome, gunakan tombol Cek Browser karena request dikirim langsung dari browser.');
            }
        } elseif ($action === 'send') {
            $messageText = trim($form['message_text']);

            if ($messageText === '') {
                throw new RuntimeException('Isi pesan tidak boleh kosong.');
            }

            if (strlen($messageText) > 512) {
                throw new RuntimeException('Pesan maksimal 512 byte.');
            }

            $payload = [
                'source' => $form['source_node'] ?: 'MASTER-RASPI-4',
                'target' => $form['target_node_id'] ?: 'SLAVE-HALOW-01',
                'message' => $messageText,
                'sent_at_ms' => (int) round(microtime(true) * 1000),
            ];
            $requestPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $response = textCommunicationRequest('POST', $url, $requestPayload, $timeout);

            if (($response['error'] || ($response['status_code'] ?? 0) === 0) && $actualTargetPort !== 80 && textCommunicationIsApiEndpoint($endpoint)) {
                $actualTargetPort = 80;
                $url = 'http://' . $targetIp . ':80' . $endpoint;
                $response = textCommunicationRequest('POST', $url, $requestPayload, $timeout);

                if (!$response['error'] && ($response['status_code'] ?? 0) >= 200 && ($response['status_code'] ?? 0) < 300) {
                    $notice = 'Pesan dikirim via port 80. Port 5001 adalah port iperf, bukan HTTP API.';
                    $form['target_port'] = '80';
                }
            }

            $responseJson = json_decode($response['body'], true);
            $isSuccess = !$response['error']
                && $response['status_code'] >= 200
                && $response['status_code'] < 300
                && (!is_array($responseJson) || ($responseJson['ok'] ?? true));

            query(
                "INSERT INTO text_message_logs (
                    test_date, source_node, target_node_id, target_ip, target_port, protocol,
                    endpoint, message_text, request_payload, response_status_code, response_body,
                    latency_ms, delivery_status, error_message, sent_at
                ) VALUES (?, ?, ?, ?, ?, 'HTTP', ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $form['test_date'] ?: date('Y-m-d'),
                    $payload['source'],
                    $payload['target'],
                    $targetIp,
                    $actualTargetPort,
                    $endpoint,
                    $messageText,
                    $requestPayload,
                    $response['status_code'],
                    $response['body'],
                    $response['latency_ms'],
                    $isSuccess ? 'success' : 'fail',
                    $response['error'],
                ]
            );

            if ($isSuccess) {
                $success = 'Pesan berhasil dikirim ke slave dalam ' . number_format($response['latency_ms'], 2) . ' ms.';
                $form['message_text'] = '';
            } else {
                $error = 'Pesan terkirim tapi tidak mendapat ACK sukses dari slave.';
                if ($response['error']) {
                    $error .= ' Detail: ' . $response['error'] . '. Jika IP slave bisa dibuka dari Chrome, gunakan tombol Kirim dari Browser.';
                }
            }

            $statusResult = $response;
        }
    } catch (PDOException $e) {
        $error = 'Gagal menyimpan log. Pastikan migrasi text_message_logs sudah dijalankan. Detail: ' . $e->getMessage();
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

try {
    $messageLogs = fetchAll("SELECT * FROM text_message_logs ORDER BY sent_at DESC, id DESC LIMIT 50");
} catch (PDOException $e) {
    $messageLogs = [];
    $error = $error ?: 'Tabel text_message_logs belum tersedia. Jalankan migrasi database terlebih dahulu.';
}

try {
    $inboxLogs = fetchAll("SELECT * FROM text_message_inbox_logs ORDER BY received_at DESC, id DESC LIMIT 50");
} catch (PDOException $e) {
    $inboxLogs = [];
}
?>

<style>
    .message-panel {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        padding: 20px;
        margin-bottom: 20px;
    }

    .message-response {
        min-height: 160px;
        max-height: 320px;
        overflow: auto;
        white-space: pre-wrap;
        word-break: break-word;
        background: #111827;
        color: #d1fae5;
        border-radius: 8px;
        padding: 14px;
        font-size: 0.85rem;
    }

    .message-help {
        color: #6b7280;
        font-size: 0.9rem;
    }
</style>

<div class="content-section">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h4 class="mb-1"><i class="fas fa-comments"></i> Text Communication</h4>
            <p class="text-muted mb-0">Kirim pesan teks sederhana dari master web/Raspberry Pi ke slave ESP32-S3 WiFi HaLow.</p>
        </div>
        <span class="badge bg-primary">HTTP API /api/message + /api/send-master</span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var recommendedFirmware = 'text-msg-v8-20260507';
    var compatibleFirmwares = ['text-msg-v8-20260507'];
    var form = document.getElementById('textCommunicationForm');
    var responseBody = document.getElementById('messageResponseBody');
    var sendButton = document.getElementById('browserSendMessage');
    var statusButton = document.getElementById('browserCheckStatus');
    var sendToMasterButton = document.getElementById('browserSendToMaster');
    var masterInboxUrlInput = document.getElementById('masterInboxUrl');
    var slaveReplyMessageInput = document.getElementById('slaveReplyMessage');

    if (!form || !responseBody || !sendButton || !statusButton) {
        return;
    }

    function field(name) {
        return form.elements[name] ? form.elements[name].value.trim() : '';
    }

    function normalizeEndpoint(endpoint) {
        endpoint = endpoint || '/api/message';
        return endpoint.charAt(0) === '/' ? endpoint : '/' + endpoint;
    }

    function prettyBody(text) {
        try {
            return JSON.stringify(JSON.parse(text), null, 2);
        } catch (error) {
            return text;
        }
    }

    function writeResponse(header, text) {
        responseBody.textContent = header + "\n\n" + prettyBody(text || '');
    }

    function parseJson(text) {
        try {
            return JSON.parse(text);
        } catch (error) {
            return null;
        }
    }

    async function fetchWithTimeout(url, options, timeoutSeconds) {
        var controller = new AbortController();
        timeoutSeconds = Math.max(1, Math.min(60, parseInt(timeoutSeconds || '20', 10)));
        var timer = setTimeout(function() {
            controller.abort();
        }, timeoutSeconds * 1000);

        try {
            options = options || {};
            options.signal = controller.signal;
            return await fetch(url, options);
        } finally {
            clearTimeout(timer);
        }
    }

    function isAbortError(error) {
        var rawMessage = error && error.message ? error.message : '';
        return (error && error.name === 'AbortError') || /abort/i.test(rawMessage);
    }

    function wait(ms) {
        return new Promise(function(resolve) {
            setTimeout(resolve, ms);
        });
    }

    async function verifyMessageDelivery(baseUrl, timeout, expectedSource, expectedMessage) {
        var lastText = '';
        var lastStatus = 0;

        for (var attempt = 0; attempt < 3; attempt++) {
            if (attempt > 0) {
                await wait(900);
            }

            var startedAt = performance.now();
            var response = await fetchWithTimeout(baseUrl + '/api/message?verify=' + Date.now(), { method: 'GET', cache: 'no-store' }, Math.max(5, timeout));
            var text = await response.text();
            var data = parseJson(text);

            lastText = text;
            lastStatus = response.status;

            if (response.ok && data && data.last_message === expectedMessage && (!data.last_source || data.last_source === expectedSource)) {
                return {
                    ok: true,
                    status: response.status,
                    text: text,
                    latency_ms: Math.round((performance.now() - startedAt) * 100) / 100
                };
            }
        }

        return {
            ok: false,
            status: lastStatus,
            text: lastText,
            latency_ms: 0
        };
    }

    function logBrowserSend(payload, endpoint, ip, actualPort, requestPayload, responseStatus, responseBody, latency, ok, errorMessage) {
        var logParams = new URLSearchParams();
        logParams.set('_message_action', 'browser_log');
        logParams.set('_ajax', '1');
        logParams.set('test_date', field('test_date'));
        logParams.set('source_node', payload.source);
        logParams.set('target_node_id', payload.target);
        logParams.set('target_ip', ip);
        logParams.set('target_port', String(actualPort));
        logParams.set('endpoint', endpoint);
        logParams.set('message_text', payload.message);
        logParams.set('request_payload', requestPayload);
        logParams.set('response_status_code', String(responseStatus));
        logParams.set('response_body', responseBody);
        logParams.set('latency_ms', String(latency));
        logParams.set('delivery_status', ok ? 'success' : 'fail');
        logParams.set('error_message', ok ? '' : errorMessage);

        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: logParams
        }).catch(function() {});
    }

    async function runDirectRequest(mode) {
        var ip = field('target_ip');
        var port = parseInt(field('target_port') || '80', 10);
        var endpoint = normalizeEndpoint(field('endpoint'));
        var timeout = Math.max(1, Math.min(60, parseInt(field('timeout') || '20', 10)));
        var message = field('message_text');
        var payload = {
            source: field('source_node') || 'MASTER-RASPI-4',
            target: field('target_node_id') || 'SLAVE-HALOW-01',
            message: message,
            sent_at_ms: Date.now()
        };

        if (!ip) {
            writeResponse('IP slave belum diisi.', '');
            return;
        }

        if (mode === 'send' && !message) {
            writeResponse('Pesan belum diisi.', '');
            return;
        }

        var requestPayload = JSON.stringify(payload);
        var path = mode === 'status' ? '/api/status' : endpoint;

        if (mode === 'send') {
            var sendParams = new URLSearchParams();
            sendParams.set('source', payload.source);
            sendParams.set('target', payload.target);
            sendParams.set('message', message);
            sendParams.set('sent_at_ms', String(payload.sent_at_ms));
            path += (path.indexOf('?') === -1 ? '?' : '&') + sendParams.toString();
        }

        if (port === 5001 && path.indexOf('/api/') === 0) {
            port = 80;
            form.elements.target_port.value = '80';
        }

        var baseUrl = 'http://' + ip + ':' + port;
        var url = baseUrl + path;
        var options = { method: 'GET', cache: 'no-store' };
        var startedAt = performance.now();
        var response;
        var text = '';
        var actualPort = port;

        try {
            if (mode === 'send') {
                var statusStartedAt = performance.now();
                var statusResponse = await fetchWithTimeout(baseUrl + '/api/status', { method: 'GET', cache: 'no-store' }, timeout);
                var statusText = await statusResponse.text();
                var statusData = parseJson(statusText);

                if (!statusData || compatibleFirmwares.indexOf(statusData.firmware_version) === -1) {
                    var activeFirmware = statusData && statusData.firmware_version ? statusData.firmware_version : 'tidak terdeteksi';
                    writeResponse(
                        'Firmware slave belum siap untuk text message. Aktif: ' + activeFirmware + ' | kompatibel: ' + compatibleFirmwares.join(', '),
                        statusText
                    );
                    return;
                }

                startedAt = performance.now();
            }

            response = await fetchWithTimeout(url, options, timeout);
        } catch (error) {
            if (port !== 80 && path.indexOf('/api/') === 0) {
                actualPort = 80;
                baseUrl = 'http://' + ip + ':80';
                url = baseUrl + path;
                startedAt = performance.now();
                response = await fetchWithTimeout(url, options, timeout);
                form.elements.target_port.value = '80';
            } else if (mode === 'send' && isAbortError(error)) {
                writeResponse('ACK belum diterima sebelum timeout. Mengecek apakah pesan sudah masuk ke slave...', '');
                var verification = await verifyMessageDelivery(baseUrl, timeout, payload.source, message);

                if (verification.ok) {
                    var verifiedLatency = Math.round((performance.now() - startedAt) * 100) / 100;
                    writeResponse('Pesan sudah diterima slave, tetapi ACK awal melewati timeout. Verifikasi HTTP ' + verification.status + ' | ' + verification.latency_ms + ' ms | port ' + actualPort, verification.text);
                    logBrowserSend(payload, endpoint, ip, actualPort, requestPayload, verification.status, verification.text, verifiedLatency, true, '');
                    return;
                }

                logBrowserSend(payload, endpoint, ip, actualPort, requestPayload, verification.status || 0, verification.text || '', Math.round((performance.now() - startedAt) * 100) / 100, false, 'ACK timeout and delivery verification failed');
                throw error;
            } else {
                throw error;
            }
        }

        text = await response.text();
        var latency = Math.round((performance.now() - startedAt) * 100) / 100;
        var ok = response.ok;
        var responseData = parseJson(text);
        var firmwareNote = '';

        if (mode === 'status') {
            var activeFirmware = responseData && responseData.firmware_version ? responseData.firmware_version : 'tidak terdeteksi';
            if (compatibleFirmwares.indexOf(activeFirmware) === -1) {
                firmwareNote = ' | firmware belum kompatibel: ' + activeFirmware;
            } else if (activeFirmware !== recommendedFirmware) {
                firmwareNote = ' | firmware kompatibel, disarankan update: ' + activeFirmware + ' -> ' + recommendedFirmware;
            }
        }

        writeResponse('Browser direct | HTTP ' + response.status + ' | ' + latency + ' ms | port ' + actualPort + firmwareNote, text);

        if (mode === 'send') {
            logBrowserSend(payload, endpoint, ip, actualPort, requestPayload, response.status, text, latency, ok, 'Browser direct request returned HTTP ' + response.status);
        }
    }

    async function runSlaveToMasterRequest() {
        var ip = field('target_ip');
        var port = parseInt(field('target_port') || '80', 10);
        var timeout = Math.max(1, Math.min(60, parseInt(field('timeout') || '20', 10)));
        var masterUrl = masterInboxUrlInput ? masterInboxUrlInput.value.trim() : '';
        var message = slaveReplyMessageInput ? slaveReplyMessageInput.value.trim() : '';
        var payload = {
            source: field('target_node_id') || 'SLAVE-HALOW-01',
            target: field('source_node') || 'MASTER-RASPI-4',
            message: message
        };

        if (!ip) {
            writeResponse('IP slave belum diisi.', '');
            return;
        }

        if (!masterUrl || masterUrl.indexOf('http://') !== 0) {
            writeResponse('Master Inbox URL harus memakai http:// dan bisa dijangkau ESP32.', '');
            return;
        }

        if (!message) {
            writeResponse('Pesan balasan slave belum diisi.', '');
            return;
        }

        if (port === 5001) {
            port = 80;
            form.elements.target_port.value = '80';
        }

        var baseUrl = 'http://' + ip + ':' + port;
        var statusResponse = await fetchWithTimeout(baseUrl + '/api/status', { method: 'GET', cache: 'no-store' }, timeout);
        var statusText = await statusResponse.text();
        var statusData = parseJson(statusText);

        if (!statusData || compatibleFirmwares.indexOf(statusData.firmware_version) === -1) {
            var activeFirmware = statusData && statusData.firmware_version ? statusData.firmware_version : 'tidak terdeteksi';
            writeResponse(
                'Firmware slave belum siap untuk kirim balik ke master. Aktif: ' + activeFirmware + ' | wajib: ' + compatibleFirmwares.join(', '),
                statusText
            );
            return;
        }

        var params = new URLSearchParams();
        params.set('master', masterUrl);
        params.set('source', payload.source);
        params.set('target', payload.target);
        params.set('message', payload.message);
        params.set('sent_at_ms', String(Date.now()));

        var startedAt = performance.now();
        var response = await fetchWithTimeout(baseUrl + '/api/send-master?' + params.toString(), { method: 'GET', cache: 'no-store' }, timeout);
        var text = await response.text();
        var latency = Math.round((performance.now() - startedAt) * 100) / 100;

        writeResponse('Slave -> Master | HTTP ' + response.status + ' | ' + latency + ' ms | port ' + port, text);
    }

    function directErrorMessage(error) {
        var rawMessage = error && error.message ? error.message : 'request gagal';
        if (isAbortError(error)) {
            return 'Timeout ACK browser-direct. Ini bisa hanya berarti balasan lambat; cek /api/message untuk memastikan pesan terakhir sudah masuk.';
        }

        return rawMessage;
    }

    sendButton.addEventListener('click', function() {
        runDirectRequest('send').catch(function(error) {
            writeResponse('Browser direct gagal: ' + directErrorMessage(error), '');
        });
    });

    statusButton.addEventListener('click', function() {
        runDirectRequest('status').catch(function(error) {
            writeResponse('Browser direct gagal: ' + directErrorMessage(error), '');
        });
    });

    if (sendToMasterButton) {
        sendToMasterButton.addEventListener('click', function() {
            runSlaveToMasterRequest().catch(function(error) {
                writeResponse('Slave -> Master gagal: ' + directErrorMessage(error), '');
            });
        });
    }
});
</script>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($notice): ?>
    <div class="alert alert-warning"><?php echo htmlspecialchars($notice); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-7">
        <div class="message-panel">
            <h5 class="mb-3"><i class="fas fa-paper-plane"></i> Kirim Pesan ke Slave</h5>
            <form method="POST" id="textCommunicationForm">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Test</label>
                        <input type="date" class="form-control" name="test_date" value="<?php echo htmlspecialchars($form['test_date']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Source Master</label>
                        <input type="text" class="form-control" name="source_node" value="<?php echo htmlspecialchars($form['source_node']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Target Node</label>
                        <input type="text" class="form-control" name="target_node_id" value="<?php echo htmlspecialchars($form['target_node_id']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">IP Slave</label>
                        <input type="text" class="form-control" name="target_ip" placeholder="192.168.1.xxx" value="<?php echo htmlspecialchars($form['target_ip']); ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">HTTP Port</label>
                        <input type="number" class="form-control" name="target_port" min="1" max="65535" value="<?php echo htmlspecialchars($form['target_port']); ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Endpoint</label>
                        <input type="text" class="form-control" name="endpoint" value="<?php echo htmlspecialchars($form['endpoint']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Timeout</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="timeout" min="1" max="60" value="<?php echo htmlspecialchars($form['timeout']); ?>">
                            <span class="input-group-text">s</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-info mb-0">
                            Gunakan port <strong>80</strong> untuk API ESP32. Port <strong>5001</strong> tetap dipakai untuk iperf throughput. Untuk link HaLow yang lambat, gunakan timeout <strong>20-30 detik</strong>; jika ACK lambat, web akan cek ulang apakah pesan sudah diterima slave.
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Pesan Text</label>
                        <textarea class="form-control" name="message_text" rows="5" maxlength="512" placeholder="Contoh: Halo slave, ini pesan dari master."><?php echo htmlspecialchars($form['message_text']); ?></textarea>
                        <div class="message-help mt-1">Maksimal 512 byte. Slave akan membalas ACK JSON jika endpoint firmware sudah diflash.</div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" name="_message_action" value="send" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Kirim Pesan
                    </button>
                    <button type="submit" name="_message_action" value="status" class="btn btn-outline-secondary">
                        <i class="fas fa-signal"></i> Cek Status Slave
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="browserSendMessage">
                        <i class="fas fa-globe"></i> Kirim dari Browser
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="browserCheckStatus">
                        <i class="fas fa-wifi"></i> Cek Browser
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="message-panel h-100">
            <h5 class="mb-3"><i class="fas fa-reply"></i> Response Slave</h5>
            <?php if ($statusResult): ?>
                <div class="d-flex gap-2 flex-wrap mb-3">
                    <span class="badge bg-<?php echo ($statusResult['status_code'] ?? 0) >= 200 && ($statusResult['status_code'] ?? 0) < 300 ? 'success' : 'danger'; ?>">
                        HTTP <?php echo (int) ($statusResult['status_code'] ?? 0); ?>
                    </span>
                    <span class="badge bg-info"><?php echo number_format((float) ($statusResult['latency_ms'] ?? 0), 2); ?> ms</span>
                </div>
                <pre class="message-response mb-0" id="messageResponseBody"><?php
                    $body = $statusResult['body'] ?? '';
                    $decoded = json_decode($body, true);
                    echo htmlspecialchars(is_array($decoded) ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $body);
                ?></pre>
            <?php else: ?>
                <div class="message-response" id="messageResponseBody">Belum ada response. Isi IP slave, lalu klik Cek Status Slave atau Kirim Pesan.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="message-panel">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h5 class="mb-0"><i class="fas fa-share"></i> Slave ke Master</h5>
        <span class="text-muted small">ESP32 memanggil inbox HTTP master</span>
    </div>
    <div class="row g-3">
        <div class="col-lg-8">
            <label class="form-label">Master Inbox URL</label>
            <input type="url" class="form-control" id="masterInboxUrl" value="<?php echo htmlspecialchars($defaultMasterInboxUrl); ?>">
            <div class="message-help mt-1">Gunakan IP master yang bisa dijangkau ESP32, bukan localhost. Pastikan Apache/XAMPP diizinkan oleh firewall.</div>
        </div>
        <div class="col-lg-4 d-flex align-items-end">
            <button type="button" class="btn btn-success w-100" id="browserSendToMaster">
                <i class="fas fa-reply"></i> Minta Slave Kirim ke Master
            </button>
        </div>
        <div class="col-12">
            <label class="form-label">Pesan dari Slave</label>
            <textarea class="form-control" id="slaveReplyMessage" rows="3" maxlength="512" placeholder="Contoh: Halo master, pesan dari slave sudah diterima.">Halo master, ini pesan dari slave.</textarea>
            <div class="message-help mt-1">Web akan memanggil ESP32 `/api/send-master`, lalu ESP32 mengirim pesan ini ke inbox master.</div>
        </div>
    </div>
</div>

<div class="message-panel">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h5 class="mb-0"><i class="fas fa-table"></i> Log Pengiriman Pesan</h5>
        <span class="text-muted small">50 data terbaru</span>
    </div>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Source</th>
                    <th>Target</th>
                    <th>IP</th>
                    <th>Pesan</th>
                    <th>Latency</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($messageLogs): ?>
                    <?php foreach ($messageLogs as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(date('d M Y H:i:s', strtotime($row['sent_at'] ?? $row['created_at']))); ?></td>
                            <td><?php echo htmlspecialchars($row['source_node'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['target_node_id'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars(($row['target_ip'] ?? '-') . ':' . ($row['target_port'] ?? '80')); ?></td>
                            <td style="max-width: 360px;"><?php echo htmlspecialchars($row['message_text'] ?? ''); ?></td>
                            <td><?php echo $row['latency_ms'] !== null ? htmlspecialchars(number_format((float) $row['latency_ms'], 2) . ' ms') : '-'; ?></td>
                            <td><?php echo getStatusBadge($row['delivery_status'] ?? 'fail'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Belum ada log pesan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="message-panel">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h5 class="mb-0"><i class="fas fa-inbox"></i> Log Pesan Masuk dari Slave</h5>
        <span class="text-muted small">50 data terbaru</span>
    </div>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Source</th>
                    <th>Target</th>
                    <th>IP Source</th>
                    <th>Pesan</th>
                    <th>RSSI</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($inboxLogs): ?>
                    <?php foreach ($inboxLogs as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(date('d M Y H:i:s', strtotime($row['received_at'] ?? $row['created_at']))); ?></td>
                            <td><?php echo htmlspecialchars($row['source_node'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['target_node_id'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['source_ip'] ?? '-'); ?></td>
                            <td style="max-width: 420px;"><?php echo htmlspecialchars($row['message_text'] ?? ''); ?></td>
                            <td><?php echo isset($row['rssi_dbm']) && $row['rssi_dbm'] !== null ? htmlspecialchars($row['rssi_dbm'] . ' dBm') : '-'; ?></td>
                            <td><?php echo getStatusBadge($row['delivery_status'] ?? 'success'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Belum ada pesan masuk dari slave.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
