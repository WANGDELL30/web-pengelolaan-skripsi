<?php
$pageConfig = [
    'title' => 'Satellite / VSAT Test',
    'icon' => 'fas fa-satellite-dish',
    'description' => 'Pencatatan uji coba koneksi Master ke server melalui Access Point dan jaringan satelit VSAT.',
    'table' => 'satellite_vsat_tests',
    'order' => 'test_date DESC, created_at DESC, id DESC',
    'chart_label_fields' => ['test_date', 'node_id'],
    'chart_label_caption' => 'Label grafik: tanggal - master',
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
    'validate' => function ($data) {
        $requiredFields = [
            'test_date' => 'Tanggal Pengujian',
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
        'Status Lulus = ping gateway berhasil + ping internet/server berhasil + VSAT locked',
        'Status Parsial = link satelit belum dapat dibuktikan atau pengiriman MQTT/API gagal',
        'Status Gagal = ping gateway/internet gagal atau VSAT unlocked',
    ],
    'columns' => [
        ['label' => 'Tanggal', 'field' => 'test_date', 'format' => 'date'],
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
