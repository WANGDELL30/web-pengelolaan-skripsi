<?php
$pageConfig = [
    'title' => 'Satellite / VSAT Test',
    'icon' => 'fas fa-satellite-dish',
    'description' => 'Pencatatan uji coba koneksi Master ke server melalui Access Point dan jaringan satelit VSAT.',
    'table' => 'satellite_vsat_tests',
    'order' => 'test_date DESC, created_at DESC, id DESC',
    'chart_label_fields' => ['test_session_code', 'trial_number'],
    'chart_label_caption' => 'Label grafik: kode sesi - percobaan ke-',
    'chart_metric_limit' => 5,
    'chart_metrics' => [
        ['field' => 'latency_ms', 'label' => 'Latency', 'unit' => 'ms', 'type' => 'line'],
        ['field' => 'jitter_ms', 'label' => 'Jitter', 'unit' => 'ms', 'type' => 'line'],
        ['field' => 'packet_loss_percent', 'label' => 'Packet Loss', 'unit' => '%', 'type' => 'bar'],
        ['field' => 'download_kbps', 'label' => 'Download', 'unit' => 'kbps', 'type' => 'line'],
        ['field' => 'upload_kbps', 'label' => 'Upload', 'unit' => 'kbps', 'type' => 'line'],
    ],
    'chart_status_field' => 'overall_status',
    'chart_notes' => [
        'Status Lulus membuktikan ping gateway dan internet berhasil serta modem berstatus locked ke satelit.',
        'Latency satelit secara alami lebih tinggi; fokuskan pembacaan pada kestabilan jitter dan packet loss.',
        'Kolom dashboard VSAT boleh dikosongkan bila modem provider tidak memberikan akses admin.',
    ],
    'fields' => [
        [
            'name' => 'test_date',
            'label' => 'Tanggal Pengujian',
            'type' => 'date',
            'required' => true,
            'default' => date('Y-m-d'),
            'section' => '1. Identitas Pengujian',
            'section_help' => 'Catat kapan, di mana, dan perangkat apa yang diuji.',
        ],
        ['name' => 'test_session_code', 'label' => 'Kode Sesi Pengujian', 'required' => true, 'default' => 'VSAT-' . date('Ymd') . '-01', 'placeholder' => 'Contoh: VSAT-20260723-01', 'help' => 'Gunakan kode yang sama untuk seluruh percobaan ke-1 sampai ke-3.'],
        ['name' => 'planned_trials', 'label' => 'Rencana Total Pengulangan', 'type' => 'select', 'required' => true, 'default' => 3, 'options' => [
            ['value' => 1, 'label' => '1 kali'],
            ['value' => 2, 'label' => '2 kali'],
            ['value' => 3, 'label' => '3 kali (disarankan)'],
        ]],
        ['name' => 'trial_number', 'label' => 'Percobaan Ke-', 'type' => 'select', 'required' => true, 'default' => 1, 'options' => [
            ['value' => 1, 'label' => 'Percobaan 1'],
            ['value' => 2, 'label' => 'Percobaan 2'],
            ['value' => 3, 'label' => 'Percobaan 3'],
        ], 'help' => 'Simpan satu record untuk setiap nomor percobaan.'],
        ['name' => 'location_name', 'label' => 'Lokasi Pengujian', 'required' => true, 'placeholder' => 'Contoh: Lapangan Kampus'],
        ['name' => 'test_operator', 'label' => 'Nama Penguji', 'placeholder' => 'Nama operator/penguji'],
        ['name' => 'weather_condition', 'label' => 'Kondisi Cuaca', 'type' => 'select', 'options' => [
            'cerah' => 'Cerah',
            'berawan' => 'Berawan',
            'hujan_ringan' => 'Hujan ringan',
            'hujan_lebat' => 'Hujan lebat',
        ]],
        ['name' => 'node_id', 'label' => 'ID Master', 'required' => true, 'default' => 'MASTER-VSAT'],
        ['name' => 'connection_mode', 'label' => 'Jalur Master ke Modem', 'type' => 'select', 'required' => true, 'default' => 'WiFi AP + VSAT', 'options' => [
            'WiFi AP + VSAT' => 'WiFi AP + VSAT',
            'Ethernet + VSAT' => 'Ethernet + VSAT',
        ]],

        [
            'name' => 'access_point_ssid',
            'label' => 'SSID / Nama Access Point',
            'required' => true,
            'placeholder' => 'Contoh: VSAT-AP-01',
            'section' => '2. Access Point dan Alamat Jaringan',
            'section_help' => 'Bukti bahwa Master sudah masuk ke jaringan lokal milik perangkat VSAT.',
        ],
        ['name' => 'ip_assignment', 'label' => 'Metode IP Master', 'type' => 'select', 'default' => 'DHCP', 'options' => ['DHCP', 'Static']],
        ['name' => 'master_ip', 'label' => 'IP Address Master', 'required' => true, 'placeholder' => 'Contoh: 192.168.1.20'],
        ['name' => 'gateway_ip', 'label' => 'Gateway IP AP/VSAT', 'required' => true, 'placeholder' => 'Contoh: 192.168.1.1'],
        ['name' => 'wan_ip', 'label' => 'WAN IP Modem', 'placeholder' => 'Isi bila terlihat di dashboard modem', 'help' => 'Opsional; boleh berupa IP private/CGNAT dari provider.'],
        ['name' => 'vsat_provider', 'label' => 'Provider / Modem VSAT', 'placeholder' => 'Nama provider atau tipe modem'],

        [
            'name' => 'gateway_ping_status',
            'label' => 'Ping Master ke Gateway',
            'type' => 'select',
            'required' => true,
            'options' => ['success' => 'Berhasil', 'fail' => 'Gagal'],
            'section' => '3. Uji Konektivitas End-to-End',
            'section_help' => 'Uji link lokal terlebih dahulu, lalu ping alamat internet atau server tujuan.',
        ],
        ['name' => 'server_target', 'label' => 'Target Internet / Server', 'required' => true, 'default' => '8.8.8.8', 'placeholder' => 'IP atau domain tujuan ping'],
        ['name' => 'internet_ping_status', 'label' => 'Ping Master ke Internet/Server', 'type' => 'select', 'required' => true, 'options' => ['success' => 'Berhasil', 'fail' => 'Gagal']],
        ['name' => 'packet_sent', 'label' => 'Packet Dikirim', 'type' => 'number', 'integer' => true, 'required' => true, 'default' => 10, 'min' => 1],
        ['name' => 'packet_received', 'label' => 'Packet Diterima', 'type' => 'number', 'integer' => true, 'required' => true, 'default' => 10, 'min' => 0],
        ['name' => 'latency_ms', 'label' => 'Rata-rata Latency / RTT (ms)', 'type' => 'number', 'step' => '0.01', 'min' => 0],
        ['name' => 'jitter_ms', 'label' => 'Jitter (ms)', 'type' => 'number', 'step' => '0.01', 'min' => 0],
        ['name' => 'download_kbps', 'label' => 'Throughput Download (kbps)', 'type' => 'number', 'step' => '0.01', 'min' => 0, 'help' => 'Jika hasil speed test dalam Mbps, kalikan 1.000.'],
        ['name' => 'upload_kbps', 'label' => 'Throughput Upload (kbps)', 'type' => 'number', 'step' => '0.01', 'min' => 0, 'help' => 'Jika hasil speed test dalam Mbps, kalikan 1.000.'],

        [
            'name' => 'wifi_rssi_dbm',
            'label' => 'RSSI WiFi Master ke AP (dBm)',
            'type' => 'number',
            'step' => '0.01',
            'max' => 0,
            'section' => '4. Kualitas Link WiFi dan VSAT',
            'section_help' => 'Parameter dashboard modem bersifat opsional, kecuali status lock untuk bukti koneksi satelit.',
        ],
        ['name' => 'wifi_snr_db', 'label' => 'SNR WiFi Master ke AP (dB)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'vsat_lock_status', 'label' => 'VSAT Lock Status', 'type' => 'select', 'required' => true, 'options' => [
            'locked' => 'Locked (terkunci ke satelit)',
            'unlocked' => 'Unlocked (belum terkunci)',
            'not_checked' => 'Tidak dapat dicek',
        ]],
        ['name' => 'rx_signal_type', 'label' => 'Jenis Indikator RX', 'type' => 'select', 'options' => ['SNR', 'C/N', 'Eb/N0']],
        ['name' => 'rx_signal_db', 'label' => 'Nilai RX SNR / C/N / Eb/N0 (dB)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'tx_power_dbm', 'label' => 'TX Power Modem (dBm)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'modem_uptime_minutes', 'label' => 'Uptime Modem (menit)', 'type' => 'number', 'integer' => true, 'min' => 0],
        ['name' => 'rain_fade_status', 'label' => 'Rain Fade / Penurunan Sinyal', 'type' => 'select', 'default' => 'not_checked', 'options' => [
            'none' => 'Tidak ada',
            'mild' => 'Ringan',
            'moderate' => 'Sedang',
            'severe' => 'Berat',
            'not_checked' => 'Tidak dicek',
        ]],
        ['name' => 'data_usage_mb', 'label' => 'Data Usage (MB)', 'type' => 'number', 'step' => '0.01', 'min' => 0],

        [
            'name' => 'server_protocol',
            'label' => 'Protokol Pengiriman Data',
            'type' => 'select',
            'default' => 'not_tested',
            'options' => [
                'MQTT' => 'MQTT',
                'HTTP API' => 'HTTP API',
                'HTTPS API' => 'HTTPS API',
                'other' => 'Lainnya',
                'not_tested' => 'Tidak diuji',
            ],
            'section' => '5. Pengiriman Data dan Bukti',
            'section_help' => 'Isi bila Master juga diuji mengirim data aplikasi ke server melalui VSAT.',
        ],
        ['name' => 'server_delivery_status', 'label' => 'Status MQTT / API', 'type' => 'select', 'default' => 'not_tested', 'options' => [
            'success' => 'Berhasil',
            'fail' => 'Gagal',
            'not_tested' => 'Tidak diuji',
        ]],
        ['name' => 'reconnect_count', 'label' => 'Reconnect Count', 'type' => 'number', 'integer' => true, 'default' => 0, 'min' => 0],
        ['name' => 'last_successful_send', 'label' => 'Waktu Kirim Terakhir Berhasil', 'type' => 'datetime-local'],
        ['name' => 'evidence_link', 'label' => 'Link Bukti Foto / Screenshot', 'type' => 'url', 'placeholder' => 'https://...'],
        ['name' => 'notes', 'label' => 'Catatan Pengujian', 'type' => 'textarea', 'rows' => 4, 'placeholder' => 'Contoh: cuaca, kendala, konfigurasi modem, atau hasil observasi lain.'],
    ],
    'calculate' => function ($data) {
        $gatewayStatus = $data['gateway_ping_status'] ?? '';
        $internetStatus = $data['internet_ping_status'] ?? '';
        $lockStatus = $data['vsat_lock_status'] ?? '';
        $deliveryStatus = $data['server_delivery_status'] ?? 'not_tested';

        if ($gatewayStatus === 'fail' || $internetStatus === 'fail' || $lockStatus === 'unlocked') {
            $overallStatus = 'failed';
        } elseif ($gatewayStatus === 'success' && $internetStatus === 'success' && $lockStatus === 'locked') {
            $overallStatus = $deliveryStatus === 'fail' ? 'partial' : 'passed';
        } else {
            $overallStatus = 'partial';
        }

        $lastSuccessfulSend = $data['last_successful_send'] ?? null;
        if ($lastSuccessfulSend) {
            $lastSuccessfulSend = str_replace('T', ' ', $lastSuccessfulSend);
        }

        return [
            'packet_loss_percent' => calculatePacketLoss($data['packet_sent'] ?? null, $data['packet_received'] ?? null),
            'overall_status' => $overallStatus,
            'last_successful_send' => $lastSuccessfulSend,
        ];
    },
    'validate' => function ($data, $recordId = 0) {
        $requiredFields = [
            'test_date' => 'Tanggal Pengujian',
            'test_session_code' => 'Kode Sesi Pengujian',
            'planned_trials' => 'Rencana Total Pengulangan',
            'trial_number' => 'Percobaan Ke-',
            'location_name' => 'Lokasi Pengujian',
            'node_id' => 'ID Master',
            'connection_mode' => 'Jalur Master ke Modem',
            'access_point_ssid' => 'SSID / Nama Access Point',
            'master_ip' => 'IP Address Master',
            'gateway_ip' => 'Gateway IP',
            'gateway_ping_status' => 'Ping Master ke Gateway',
            'server_target' => 'Target Internet / Server',
            'internet_ping_status' => 'Ping Master ke Internet/Server',
            'vsat_lock_status' => 'VSAT Lock Status',
        ];
        foreach ($requiredFields as $field => $label) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new RuntimeException($label . ' wajib diisi.');
            }
        }

        foreach (['master_ip' => 'IP Address Master', 'gateway_ip' => 'Gateway IP', 'wan_ip' => 'WAN IP Modem'] as $field => $label) {
            $value = trim((string) ($data[$field] ?? ''));
            if ($value !== '' && filter_var($value, FILTER_VALIDATE_IP) === false) {
                throw new RuntimeException($label . ' tidak valid.');
            }
        }

        $evidenceLink = trim((string) ($data['evidence_link'] ?? ''));
        if ($evidenceLink !== '' && filter_var($evidenceLink, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('Link bukti foto / screenshot tidak valid.');
        }

        $plannedTrials = (int) ($data['planned_trials'] ?? 0);
        $trialNumber = (int) ($data['trial_number'] ?? 0);
        if ($plannedTrials < 1 || $plannedTrials > 3) {
            throw new RuntimeException('Rencana total pengulangan harus antara 1 sampai 3 kali.');
        }
        if ($trialNumber < 1 || $trialNumber > $plannedTrials) {
            throw new RuntimeException('Nomor percobaan harus berada di antara 1 dan rencana total pengulangan.');
        }

        $existingSession = fetchOne(
            'SELECT planned_trials FROM satellite_vsat_tests WHERE test_session_code = ? AND id <> ? LIMIT 1',
            [$data['test_session_code'], (int) $recordId]
        );
        if ($existingSession && (int) $existingSession['planned_trials'] !== $plannedTrials) {
            throw new RuntimeException('Rencana pengulangan untuk sesi ini sudah ditetapkan ' . (int) $existingSession['planned_trials'] . ' kali. Gunakan jumlah yang sama.');
        }

        $duplicate = fetchOne(
            'SELECT id FROM satellite_vsat_tests WHERE test_session_code = ? AND trial_number = ? AND id <> ? LIMIT 1',
            [$data['test_session_code'], $trialNumber, (int) $recordId]
        );
        if ($duplicate) {
            throw new RuntimeException('Percobaan ke-' . $trialNumber . ' untuk kode sesi ini sudah tersimpan. Gunakan nomor percobaan berikutnya atau edit data yang ada.');
        }

        $sent = $data['packet_sent'] ?? null;
        $received = $data['packet_received'] ?? null;
        if (!is_numeric($sent) || (int) $sent <= 0) {
            throw new RuntimeException('Packet dikirim harus lebih dari 0.');
        }
        if (!is_numeric($received) || (int) $received < 0 || (int) $received > (int) $sent) {
            throw new RuntimeException('Packet diterima harus berada di antara 0 dan jumlah packet dikirim.');
        }
    },
    'formulas' => [
        'Packet Loss % = ((Packet Dikirim - Packet Diterima) / Packet Dikirim) x 100',
        'Rata-rata sesi = jumlah nilai seluruh percobaan / jumlah percobaan yang terisi',
        'Status Lulus = ping gateway berhasil + ping internet/server berhasil + VSAT locked',
        'Status Parsial = link satelit belum dapat dibuktikan atau pengiriman MQTT/API gagal',
        'Status Gagal = ping gateway/internet gagal atau VSAT unlocked',
    ],
    'columns' => [
        ['label' => 'Tanggal', 'field' => 'test_date', 'format' => 'date'],
        ['label' => 'Kode Sesi', 'field' => 'test_session_code'],
        ['label' => 'Uji Ke-', 'field' => 'trial_number'],
        ['label' => 'Master', 'field' => 'node_id'],
        ['label' => 'Access Point', 'field' => 'access_point_ssid'],
        ['label' => 'Ping Gateway', 'field' => 'gateway_ping_status', 'format' => 'status'],
        ['label' => 'Ping Internet', 'field' => 'internet_ping_status', 'format' => 'status'],
        ['label' => 'VSAT Lock', 'field' => 'vsat_lock_status', 'format' => 'status'],
        ['label' => 'Latency', 'field' => 'latency_ms', 'decimals' => 2, 'suffix' => ' ms'],
        ['label' => 'Packet Loss', 'field' => 'packet_loss_percent', 'decimals' => 2, 'suffix' => '%'],
        ['label' => 'Hasil', 'field' => 'overall_status', 'format' => 'status'],
    ],
];
?>

<div class="content-section satellite-test-overview">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h4 class="mb-1"><i class="fas fa-route"></i> Skenario Pengujian</h4>
            <p class="text-muted mb-0">Form ini khusus membuktikan bahwa Master dapat mengakses internet/server melalui jalur VSAT, bukan pengujian jarak.</p>
        </div>
        <span class="badge bg-primary">Uji coba end-to-end</span>
    </div>
    <div class="satellite-path mt-4" aria-label="Master menuju server melalui VSAT">
        <div><i class="fas fa-microchip"></i><strong>Master</strong><small>IP lokal</small></div>
        <i class="fas fa-arrow-right satellite-path-arrow"></i>
        <div><i class="fas fa-wifi"></i><strong>Access Point</strong><small>Gateway</small></div>
        <i class="fas fa-arrow-right satellite-path-arrow"></i>
        <div><i class="fas fa-satellite-dish"></i><strong>Modem VSAT</strong><small>Locked</small></div>
        <i class="fas fa-arrow-right satellite-path-arrow"></i>
        <div><i class="fas fa-satellite"></i><strong>Satelit</strong><small>Link provider</small></div>
        <i class="fas fa-arrow-right satellite-path-arrow"></i>
        <div><i class="fas fa-server"></i><strong>Internet / Server</strong><small>Ping, MQTT, API</small></div>
    </div>
</div>

<style>
.satellite-test-overview {
    border-top: 4px solid #2563eb;
}
.satellite-path {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    overflow-x: auto;
    padding: 6px 2px 10px;
}
.satellite-path > div {
    min-width: 130px;
    padding: 14px 12px;
    text-align: center;
    border: 1px solid #dbeafe;
    border-radius: 10px;
    background: #f8fbff;
}
.satellite-path > div > i,
.satellite-path > div > strong,
.satellite-path > div > small {
    display: block;
}
.satellite-path > div > i {
    margin-bottom: 8px;
    color: #2563eb;
    font-size: 22px;
}
.satellite-path > div > small {
    margin-top: 3px;
    color: #64748b;
}
.satellite-path-arrow {
    color: #94a3b8;
}
@media (max-width: 991.98px) {
    .satellite-path {
        justify-content: flex-start;
    }
}
</style>

<?php include __DIR__ . '/_test_page.php'; ?>

<?php
$satelliteRepetitionRows = fetchAll("
    SELECT
        test_session_code,
        MAX(test_date) AS test_date,
        MAX(location_name) AS location_name,
        MAX(node_id) AS node_id,
        MAX(planned_trials) AS planned_trials,
        COUNT(DISTINCT trial_number) AS completed_trials,
        ROUND(AVG(latency_ms), 2) AS avg_latency_ms,
        ROUND(AVG(jitter_ms), 2) AS avg_jitter_ms,
        ROUND(AVG(packet_loss_percent), 2) AS avg_packet_loss_percent,
        ROUND(AVG(download_kbps), 2) AS avg_download_kbps,
        ROUND(AVG(upload_kbps), 2) AS avg_upload_kbps,
        ROUND(AVG(wifi_rssi_dbm), 2) AS avg_wifi_rssi_dbm,
        ROUND(AVG(wifi_snr_db), 2) AS avg_wifi_snr_db,
        SUM(overall_status = 'passed') AS passed_trials,
        MAX(created_at) AS latest_created_at
    FROM satellite_vsat_tests
    WHERE test_session_code IS NOT NULL AND test_session_code <> ''
    GROUP BY test_session_code
    ORDER BY test_date DESC, latest_created_at DESC
    LIMIT 30
");

if (!function_exists('satelliteAverageValue')) {
    function satelliteAverageValue($value, $suffix = '') {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return 'N/A';
        }
        return number_format((float) $value, 2) . $suffix;
    }
}
?>

<div class="content-section">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-repeat"></i> Validasi Rata-rata Pengulangan</h4>
            <p class="text-muted mb-0">Ringkasan otomatis seluruh percobaan dengan kode sesi yang sama. Tiga pengulangan memberikan validasi paling kuat.</p>
        </div>
        <span class="badge bg-secondary"><?php echo count($satelliteRepetitionRows); ?> sesi</span>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover data-table" width="100%">
            <thead>
                <tr>
                    <th>Kode Sesi</th>
                    <th>Progress</th>
                    <th>Validasi</th>
                    <th>Rata-rata Latency</th>
                    <th>Rata-rata Jitter</th>
                    <th>Rata-rata Packet Loss</th>
                    <th>Rata-rata Download</th>
                    <th>Rata-rata Upload</th>
                    <th>Rata-rata RSSI</th>
                    <th>Rata-rata SNR</th>
                    <th>Hasil Lulus</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($satelliteRepetitionRows as $summary): ?>
                    <?php
                    $completed = (int) ($summary['completed_trials'] ?? 0);
                    $planned = max(1, (int) ($summary['planned_trials'] ?? 1));
                    if ($completed >= 3) {
                        $validationLabel = '<span class="badge bg-success">Validasi kuat</span>';
                    } elseif ($completed === 2) {
                        $validationLabel = '<span class="badge bg-warning text-dark">Rata-rata sementara</span>';
                    } else {
                        $validationLabel = '<span class="badge bg-secondary">Belum diulang</span>';
                    }
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($summary['test_session_code']); ?></strong>
                            <small class="d-block text-muted"><?php echo formatDate($summary['test_date']); ?> · <?php echo htmlspecialchars($summary['node_id']); ?></small>
                        </td>
                        <td><?php echo $completed; ?>/<?php echo $planned; ?> percobaan</td>
                        <td><?php echo $validationLabel; ?></td>
                        <td><?php echo satelliteAverageValue($summary['avg_latency_ms'], ' ms'); ?></td>
                        <td><?php echo satelliteAverageValue($summary['avg_jitter_ms'], ' ms'); ?></td>
                        <td><?php echo satelliteAverageValue($summary['avg_packet_loss_percent'], '%'); ?></td>
                        <td><?php echo satelliteAverageValue($summary['avg_download_kbps'], ' kbps'); ?></td>
                        <td><?php echo satelliteAverageValue($summary['avg_upload_kbps'], ' kbps'); ?></td>
                        <td><?php echo satelliteAverageValue($summary['avg_wifi_rssi_dbm'], ' dBm'); ?></td>
                        <td><?php echo satelliteAverageValue($summary['avg_wifi_snr_db'], ' dB'); ?></td>
                        <td><?php echo (int) ($summary['passed_trials'] ?? 0); ?>/<?php echo $completed; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
