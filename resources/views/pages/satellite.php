<?php
$pageConfig = [
    'title' => 'Uji Konektivitas Master ke Satelit',
    'icon' => 'fas fa-satellite-dish',
    'description' => 'Data pendukung uji coba konektivitas Master melalui modem VSAT; tidak termasuk data laporan pengujian utama.',
    'table' => 'satellite_vsat_tests',
    'order' => 'test_date DESC, created_at DESC, id DESC',
    'chart_label_fields' => ['test_session_code', 'trial_number'],
    'chart_label_caption' => 'Label grafik: kode sesi - percobaan ke-',
    'chart_metric_limit' => 4,
    'chart_metrics' => [
        ['field' => 'latency_min_ms', 'label' => 'Latency Minimum', 'unit' => 'ms', 'type' => 'line'],
        ['field' => 'latency_ms', 'label' => 'Latency Rata-rata', 'unit' => 'ms', 'type' => 'line'],
        ['field' => 'latency_max_ms', 'label' => 'Latency Maksimum', 'unit' => 'ms', 'type' => 'line'],
        ['field' => 'packet_loss_percent', 'label' => 'Packet Loss', 'unit' => '%', 'type' => 'bar'],
    ],
    'chart_status_field' => 'overall_status',
    'chart_notes' => [
        'Status Lulus membuktikan dashboard gateway dapat diakses, ping internet berhasil, downlink Locked, dan modem Associated.',
        'Latency yang ditampilkan adalah ringkasan min/avg/max dari keluaran ping pada setiap pengulangan.',
        'Data ini merupakan uji coba pendukung untuk menjawab pertanyaan sidang dan tidak masuk laporan pengujian utama.',
    ],
    'fields' => [
        [
            'name' => 'test_date',
            'label' => 'Tanggal Pengujian',
            'type' => 'date',
            'required' => true,
            'default' => date('Y-m-d'),
            'section' => '1. Identitas dan Bukti Koneksi Modem',
            'section_help' => 'Identitas uji serta indikator dashboard modem yang membuktikan modem terkunci dan terasosiasi ke jaringan satelit.',
        ],
        [
            'name' => 'test_session_code',
            'label' => 'Kode Sesi Pengujian',
            'required' => true,
            'default' => 'VSAT-' . date('Ymd') . '-01',
            'placeholder' => 'Contoh: VSAT-20260723-01',
            'help' => 'Gunakan kode yang sama untuk seluruh pengulangan dalam satu sesi.',
        ],
        [
            'name' => 'planned_trials',
            'label' => 'Total Pengulangan',
            'type' => 'select',
            'required' => true,
            'default' => 3,
            'options' => [
                ['value' => 1, 'label' => '1 kali'],
                ['value' => 2, 'label' => '2 kali'],
                ['value' => 3, 'label' => '3 kali'],
            ],
        ],
        [
            'name' => 'trial_number',
            'label' => 'Percobaan Ke-',
            'type' => 'select',
            'required' => true,
            'default' => 1,
            'options' => [
                ['value' => 1, 'label' => 'Percobaan 1'],
                ['value' => 2, 'label' => 'Percobaan 2'],
                ['value' => 3, 'label' => 'Percobaan 3'],
            ],
            'help' => 'Simpan satu record untuk setiap ringkasan keluaran ping.',
        ],
        ['name' => 'test_operator', 'label' => 'Nama Penguji', 'required' => true, 'placeholder' => 'Contoh: Aranda dan Adnan'],
        ['name' => 'node_id', 'label' => 'ID Master', 'required' => true, 'default' => 'MASTER-VSAT'],
        ['name' => 'gateway_ip', 'label' => 'IP LAN Modem / Gateway', 'required' => true, 'placeholder' => 'Contoh: 10.20.10.1'],
        ['name' => 'satellite_name', 'label' => 'Nama Satelit pada Modem', 'required' => true, 'placeholder' => 'Contoh: PSN-VI-PSN'],
        [
            'name' => 'signal_quality_factor',
            'label' => 'Signal Quality Factor (SQF)',
            'type' => 'number',
            'integer' => true,
            'required' => true,
            'min' => 0,
        ],
        [
            'name' => 'vsat_lock_status',
            'label' => 'Downlink / FLL Lock Status',
            'type' => 'select',
            'required' => true,
            'options' => [
                'locked' => 'Locked',
                'unlocked' => 'Unlocked',
                'not_checked' => 'Tidak dapat dicek',
            ],
        ],
        [
            'name' => 'association_status',
            'label' => 'Association State',
            'type' => 'select',
            'required' => true,
            'options' => [
                'associated' => 'Associated',
                'not_associated' => 'Not Associated',
                'not_checked' => 'Tidak dapat dicek',
            ],
        ],
        [
            'name' => 'tdma_status',
            'label' => 'TDMA Mode',
            'type' => 'select',
            'required' => true,
            'options' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
                'not_checked' => 'Tidak dapat dicek',
            ],
        ],
        [
            'name' => 'association_time',
            'label' => 'Waktu Association',
            'type' => 'datetime-local',
            'help' => 'Salin Association Time dari dashboard modem bila tersedia.',
        ],
        [
            'name' => 'gateway_ping_status',
            'label' => 'Akses Dashboard Gateway Modem',
            'type' => 'select',
            'required' => true,
            'options' => ['success' => 'Berhasil', 'fail' => 'Gagal'],
            'help' => 'Screenshot dashboard modem yang terbuka menjadi bukti gateway lokal dapat diakses.',
        ],
        [
            'name' => 'server_target',
            'label' => 'Target Ping Internet',
            'required' => true,
            'default' => '8.8.8.8',
            'placeholder' => 'IP atau domain tujuan ping',
            'section' => '2. Hasil Ping Master ke Internet melalui Satelit',
            'section_help' => 'Masukkan ringkasan min/avg/max dan jumlah paket dari setiap keluaran ping. Packet loss dihitung otomatis.',
        ],
        [
            'name' => 'internet_ping_status',
            'label' => 'Status Ping Internet',
            'type' => 'select',
            'required' => true,
            'options' => ['success' => 'Berhasil', 'fail' => 'Gagal'],
        ],
        ['name' => 'packet_sent', 'label' => 'Packet Dikirim', 'type' => 'number', 'integer' => true, 'required' => true, 'default' => 61, 'min' => 1],
        ['name' => 'packet_received', 'label' => 'Packet Diterima', 'type' => 'number', 'integer' => true, 'required' => true, 'default' => 61, 'min' => 0],
        ['name' => 'latency_min_ms', 'label' => 'Latency Minimum (ms)', 'type' => 'number', 'step' => '0.001', 'required' => true, 'min' => 0],
        ['name' => 'latency_ms', 'label' => 'Rata-rata Latency / RTT (ms)', 'type' => 'number', 'step' => '0.001', 'required' => true, 'min' => 0],
        ['name' => 'latency_max_ms', 'label' => 'Latency Maksimum (ms)', 'type' => 'number', 'step' => '0.001', 'required' => true, 'min' => 0],
        [
            'name' => 'notes',
            'label' => 'Catatan / Sumber Bukti',
            'type' => 'textarea',
            'rows' => 4,
            'placeholder' => 'Contoh: nilai disalin dari terminal ping dan screenshot dashboard modem.',
        ],
    ],
    'calculate' => function ($data) {
        $gatewayStatus = $data['gateway_ping_status'] ?? '';
        $internetStatus = $data['internet_ping_status'] ?? '';
        $lockStatus = $data['vsat_lock_status'] ?? '';
        $associationStatus = $data['association_status'] ?? '';

        if ($gatewayStatus === 'fail' || $internetStatus === 'fail' || $lockStatus === 'unlocked' || $associationStatus === 'not_associated') {
            $overallStatus = 'failed';
        } elseif ($gatewayStatus === 'success' && $internetStatus === 'success' && $lockStatus === 'locked' && $associationStatus === 'associated') {
            $overallStatus = 'passed';
        } else {
            $overallStatus = 'partial';
        }

        $associationTime = $data['association_time'] ?? null;
        if ($associationTime) {
            $associationTime = str_replace('T', ' ', $associationTime);
        }

        return [
            'packet_loss_percent' => calculatePacketLoss($data['packet_sent'] ?? null, $data['packet_received'] ?? null),
            'overall_status' => $overallStatus,
            'association_time' => $associationTime,
        ];
    },
    'validate' => function ($data, $recordId = 0) {
        $requiredFields = [
            'test_date' => 'Tanggal Pengujian',
            'test_session_code' => 'Kode Sesi Pengujian',
            'planned_trials' => 'Total Pengulangan',
            'trial_number' => 'Percobaan Ke-',
            'test_operator' => 'Nama Penguji',
            'node_id' => 'ID Master',
            'gateway_ip' => 'IP LAN Modem / Gateway',
            'satellite_name' => 'Nama Satelit pada Modem',
            'signal_quality_factor' => 'Signal Quality Factor',
            'vsat_lock_status' => 'Downlink / FLL Lock Status',
            'association_status' => 'Association State',
            'tdma_status' => 'TDMA Mode',
            'gateway_ping_status' => 'Akses Dashboard Gateway Modem',
            'server_target' => 'Target Ping Internet',
            'internet_ping_status' => 'Status Ping Internet',
            'packet_sent' => 'Packet Dikirim',
            'packet_received' => 'Packet Diterima',
            'latency_min_ms' => 'Latency Minimum',
            'latency_ms' => 'Rata-rata Latency',
            'latency_max_ms' => 'Latency Maksimum',
        ];
        foreach ($requiredFields as $field => $label) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new RuntimeException($label . ' wajib diisi.');
            }
        }

        if (filter_var($data['gateway_ip'], FILTER_VALIDATE_IP) === false) {
            throw new RuntimeException('IP LAN Modem / Gateway tidak valid.');
        }

        $plannedTrials = (int) ($data['planned_trials'] ?? 0);
        $trialNumber = (int) ($data['trial_number'] ?? 0);
        if ($plannedTrials < 1 || $plannedTrials > 3) {
            throw new RuntimeException('Total pengulangan harus antara 1 sampai 3 kali.');
        }
        if ($trialNumber < 1 || $trialNumber > $plannedTrials) {
            throw new RuntimeException('Nomor percobaan harus berada di antara 1 dan total pengulangan.');
        }

        $existingSession = fetchOne(
            'SELECT planned_trials FROM satellite_vsat_tests WHERE test_session_code = ? AND id <> ? LIMIT 1',
            [$data['test_session_code'], (int) $recordId]
        );
        if ($existingSession && (int) $existingSession['planned_trials'] !== $plannedTrials) {
            throw new RuntimeException('Total pengulangan untuk sesi ini sudah ditetapkan ' . (int) $existingSession['planned_trials'] . ' kali.');
        }

        $duplicate = fetchOne(
            'SELECT id FROM satellite_vsat_tests WHERE test_session_code = ? AND trial_number = ? AND id <> ? LIMIT 1',
            [$data['test_session_code'], $trialNumber, (int) $recordId]
        );
        if ($duplicate) {
            throw new RuntimeException('Percobaan ke-' . $trialNumber . ' untuk kode sesi ini sudah tersimpan.');
        }

        $sent = $data['packet_sent'] ?? null;
        $received = $data['packet_received'] ?? null;
        if (!is_numeric($sent) || (int) $sent <= 0) {
            throw new RuntimeException('Packet dikirim harus lebih dari 0.');
        }
        if (!is_numeric($received) || (int) $received < 0 || (int) $received > (int) $sent) {
            throw new RuntimeException('Packet diterima harus berada di antara 0 dan jumlah packet dikirim.');
        }

        $latencyMin = (float) ($data['latency_min_ms'] ?? 0);
        $latencyAvg = (float) ($data['latency_ms'] ?? 0);
        $latencyMax = (float) ($data['latency_max_ms'] ?? 0);
        if ($latencyMin > $latencyAvg || $latencyAvg > $latencyMax) {
            throw new RuntimeException('Urutan latency harus memenuhi minimum <= rata-rata <= maksimum.');
        }
    },
    'formulas' => [
        'Packet Loss % = ((Packet Dikirim - Packet Diterima) / Packet Dikirim) x 100',
        'Rata-rata latency sesi = jumlah latency rata-rata setiap pengulangan / jumlah pengulangan',
        'Rata-rata paket sesi = jumlah paket setiap pengulangan / jumlah pengulangan',
        'Status Lulus = dashboard gateway dapat diakses + ping internet berhasil + Locked + Associated',
        'Pengukuran yang tidak tersedia pada data mentah tidak diisi atau dihitung.',
    ],
    'columns' => [
        ['label' => 'Tanggal', 'field' => 'test_date', 'format' => 'date'],
        ['label' => 'Kode Sesi', 'field' => 'test_session_code'],
        ['label' => 'Uji Ke-', 'field' => 'trial_number'],
        ['label' => 'Master', 'field' => 'node_id'],
        ['label' => 'Satelit', 'field' => 'satellite_name'],
        ['label' => 'SQF', 'field' => 'signal_quality_factor'],
        ['label' => 'Downlink', 'field' => 'vsat_lock_status', 'format' => 'status'],
        ['label' => 'Association', 'field' => 'association_status', 'format' => 'status'],
        ['label' => 'TDMA', 'field' => 'tdma_status', 'format' => 'status'],
        ['label' => 'Ping Internet', 'field' => 'internet_ping_status', 'format' => 'status'],
        ['label' => 'Packet', 'field' => 'packet_received', 'suffix' => ' diterima'],
        ['label' => 'Latency Rata-rata', 'field' => 'latency_ms', 'decimals' => 3, 'suffix' => ' ms'],
        ['label' => 'Packet Loss', 'field' => 'packet_loss_percent', 'decimals' => 2, 'suffix' => '%'],
        ['label' => 'Hasil', 'field' => 'overall_status', 'format' => 'status'],
    ],
];
?>

<div class="content-section satellite-test-overview">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h4 class="mb-1"><i class="fas fa-route"></i> Skenario Pengujian</h4>
            <p class="text-muted mb-0">Form dua bagian ini khusus membuktikan konektivitas Master melalui modem dan satelit menuju internet.</p>
        </div>
        <span class="badge bg-warning text-dark">Data pendukung sidang · di luar laporan utama</span>
    </div>
    <div class="satellite-path mt-4" aria-label="Master menuju internet melalui VSAT">
        <div><i class="fas fa-microchip"></i><strong>Master</strong><small>Terminal ping</small></div>
        <i class="fas fa-arrow-right satellite-path-arrow"></i>
        <div><i class="fas fa-satellite-dish"></i><strong>Modem VSAT</strong><small>Locked &amp; Associated</small></div>
        <i class="fas fa-arrow-right satellite-path-arrow"></i>
        <div><i class="fas fa-satellite"></i><strong>Satelit</strong><small>Link provider</small></div>
        <i class="fas fa-arrow-right satellite-path-arrow"></i>
        <div><i class="fas fa-globe"></i><strong>Internet</strong><small>Ping 8.8.8.8</small></div>
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
    min-width: 150px;
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
        MAX(node_id) AS node_id,
        MAX(planned_trials) AS planned_trials,
        COUNT(DISTINCT trial_number) AS completed_trials,
        ROUND(AVG(packet_sent), 2) AS avg_packet_sent,
        ROUND(AVG(packet_received), 2) AS avg_packet_received,
        ROUND(AVG(latency_min_ms), 3) AS avg_latency_min_ms,
        ROUND(AVG(latency_ms), 3) AS avg_latency_ms,
        ROUND(AVG(latency_max_ms), 3) AS avg_latency_max_ms,
        ROUND(AVG(packet_loss_percent), 2) AS avg_packet_loss_percent,
        SUM(overall_status = 'passed') AS passed_trials,
        MAX(created_at) AS latest_created_at
    FROM satellite_vsat_tests
    WHERE test_session_code IS NOT NULL AND test_session_code <> ''
    GROUP BY test_session_code
    ORDER BY test_date DESC, latest_created_at DESC
    LIMIT 30
");

if (!function_exists('satelliteAverageValue')) {
    function satelliteAverageValue($value, $suffix = '', $decimals = 2) {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return 'N/A';
        }
        return number_format((float) $value, $decimals) . $suffix;
    }
}
?>

<div class="content-section">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-calculator"></i> Hasil Olahan Rata-rata Pengulangan</h4>
            <p class="text-muted mb-0">Olahan otomatis seluruh ringkasan ping dengan kode sesi yang sama, termasuk rata-rata latency dan paket.</p>
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
                    <th>Rata-rata Packet Sent</th>
                    <th>Rata-rata Packet Received</th>
                    <th>Rata-rata Latency Minimum</th>
                    <th>Rata-rata Latency</th>
                    <th>Rata-rata Latency Maksimum</th>
                    <th>Rata-rata Packet Loss</th>
                    <th>Hasil Lulus</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($satelliteRepetitionRows as $summary): ?>
                    <?php
                    $completed = (int) ($summary['completed_trials'] ?? 0);
                    $planned = max(1, (int) ($summary['planned_trials'] ?? 1));
                    if ($completed >= 3) {
                        $validationLabel = '<span class="badge bg-success">Validasi 3 pengulangan</span>';
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
                        <td><?php echo satelliteAverageValue($summary['avg_packet_sent'], ' paket'); ?></td>
                        <td><?php echo satelliteAverageValue($summary['avg_packet_received'], ' paket'); ?></td>
                        <td><?php echo satelliteAverageValue($summary['avg_latency_min_ms'], ' ms', 3); ?></td>
                        <td><?php echo satelliteAverageValue($summary['avg_latency_ms'], ' ms', 3); ?></td>
                        <td><?php echo satelliteAverageValue($summary['avg_latency_max_ms'], ' ms', 3); ?></td>
                        <td><?php echo satelliteAverageValue($summary['avg_packet_loss_percent'], '%'); ?></td>
                        <td><?php echo (int) ($summary['passed_trials'] ?? 0); ?>/<?php echo $completed; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
