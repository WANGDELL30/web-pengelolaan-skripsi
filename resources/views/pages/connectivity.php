<?php
require_once __DIR__ . '/../../../app/Helpers/functions.php';

// Handle form submission
$success = null;
$error = null;
$canManageProject = canManageProject();

function connectivityNullableNumber($source, $field, $integer = false) {
    if (!array_key_exists($field, $source) || $source[$field] === '') {
        return null;
    }

    return $integer ? (int) $source[$field] : (float) $source[$field];
}

function connectivityChartValue($value, $decimals = 2) {
    return is_numeric($value) ? round((float) $value, $decimals) : null;
}

function connectivityDisplayNumber($value, $decimals = 2, $suffix = '') {
    return formatNullableNumber($value, $decimals, $suffix);
}

function connectivityPayload($source) {
    $packetSent = connectivityNullableNumber($source, 'packet_sent', true);
    $packetReceived = connectivityNullableNumber($source, 'packet_received', true);
    $packetLost = $packetSent !== null && $packetReceived !== null && $packetReceived >= 0 && $packetReceived <= $packetSent
        ? $packetSent - $packetReceived
        : null;

    return [
        'test_date' => sanitize($source['test_date'] ?? ''),
        'location_name' => sanitize($source['location_name'] ?? ''),
        'environment_type' => sanitize($source['environment_type'] ?? ''),
        'distance_meter' => connectivityNullableNumber($source, 'distance_meter'),
        'node_id' => sanitize($source['node_id'] ?? ''),
        'node_type' => sanitize($source['node_type'] ?? ''),
        'connection_status' => sanitize($source['connection_status'] ?? ''),
        'rssi_dbm' => connectivityNullableNumber($source, 'rssi_dbm'),
        'snr_db' => connectivityNullableNumber($source, 'snr_db'),
        'packet_sent' => $packetSent,
        'packet_received' => $packetReceived,
        'packet_lost' => $packetLost,
        'packet_loss_percent' => calculatePacketLoss($packetSent, $packetReceived),
        'packet_success_rate' => calculateSuccessRate($packetSent, $packetReceived),
        'test_duration_second' => connectivityNullableNumber($source, 'test_duration_second', true),
        'notes' => sanitize($source['notes'] ?? ''),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_connectivity_action'] ?? 'create';
    $recordId = (int) ($_POST['id'] ?? 0);

    try {
        if (!$canManageProject) {
            throw new RuntimeException('Akses ditolak. Viewer hanya bisa melihat data.');
        }

        if ($action === 'delete') {
            if ($recordId <= 0) {
                throw new RuntimeException('ID data tidak valid.');
            }

            query('DELETE FROM connectivity_tests WHERE id = ?', [$recordId]);
            $success = 'Data connectivity test berhasil dihapus.';
        } elseif ($action === 'update') {
            if ($recordId <= 0) {
                throw new RuntimeException('ID data tidak valid.');
            }

            $data = connectivityPayload($_POST);
            query(
                "UPDATE connectivity_tests SET
                    test_date = ?, location_name = ?, environment_type = ?, distance_meter = ?, node_id = ?, node_type = ?,
                    connection_status = ?, rssi_dbm = ?, snr_db = ?, packet_sent = ?, packet_received = ?,
                    packet_lost = ?, packet_loss_percent = ?, packet_success_rate = ?,
                    test_duration_second = ?, notes = ?
                WHERE id = ?",
                [
                    $data['test_date'], $data['location_name'], $data['environment_type'], $data['distance_meter'], $data['node_id'], $data['node_type'],
                    $data['connection_status'], $data['rssi_dbm'], $data['snr_db'], $data['packet_sent'], $data['packet_received'],
                    $data['packet_lost'], $data['packet_loss_percent'], $data['packet_success_rate'],
                    $data['test_duration_second'], $data['notes'], $recordId,
                ]
            );
            $success = 'Data connectivity test berhasil diperbarui.';
        } else {
            $data = connectivityPayload($_POST);
            query(
                "INSERT INTO connectivity_tests (
                    test_date, location_name, environment_type, distance_meter, node_id, node_type,
                    connection_status, rssi_dbm, snr_db, packet_sent, packet_received,
                    packet_lost, packet_loss_percent, packet_success_rate,
                    test_duration_second, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['test_date'], $data['location_name'], $data['environment_type'], $data['distance_meter'], $data['node_id'], $data['node_type'],
                    $data['connection_status'], $data['rssi_dbm'], $data['snr_db'], $data['packet_sent'], $data['packet_received'],
                    $data['packet_lost'], $data['packet_loss_percent'], $data['packet_success_rate'],
                    $data['test_duration_second'], $data['notes'],
                ]
            );
            $success = 'Data connectivity test berhasil disimpan.';
        }
    } catch (PDOException $e) {
        $error = 'Gagal memproses data: ' . $e->getMessage();
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

// Fetch data for display
$connectivity_tests = fetchAll("SELECT * FROM connectivity_tests ORDER BY test_date DESC, created_at DESC LIMIT 50");
$connectivityMap = [];
foreach ($connectivity_tests as $test) {
    $connectivityMap[(string) $test['id']] = $test;
}

$statusCounts = [
    (int) (fetchOne("SELECT COUNT(*) as c FROM connectivity_tests WHERE connection_status='connected'")['c'] ?? 0),
    (int) (fetchOne("SELECT COUNT(*) as c FROM connectivity_tests WHERE connection_status='disconnected'")['c'] ?? 0),
    (int) (fetchOne("SELECT COUNT(*) as c FROM connectivity_tests WHERE connection_status='intermittent'")['c'] ?? 0),
];

$nodeQualityRows = fetchAll("
    SELECT
        node_id,
        COALESCE(node_type, '-') AS node_type,
        COUNT(*) AS total_tests,
        AVG(rssi_dbm) AS avg_rssi,
        AVG(snr_db) AS avg_snr,
        AVG(packet_success_rate) AS avg_success,
        AVG(packet_loss_percent) AS avg_loss,
        SUM(connection_status = 'connected') AS connected_count,
        SUM(connection_status = 'intermittent') AS intermittent_count,
        SUM(connection_status = 'disconnected') AS disconnected_count
    FROM connectivity_tests
    WHERE node_id IS NOT NULL AND node_id != ''
    GROUP BY node_id, node_type
    ORDER BY avg_success DESC, avg_snr DESC
    LIMIT 12
");

$nodeQualityLabels = [];
$nodeSuccessValues = [];
$nodeLossValues = [];
$nodeRssiValues = [];
$nodeSnrValues = [];
foreach ($nodeQualityRows as $row) {
    $nodeQualityLabels[] = $row['node_id'];
    $nodeSuccessValues[] = connectivityChartValue($row['avg_success'] ?? null, 2);
    $nodeLossValues[] = connectivityChartValue($row['avg_loss'] ?? null, 2);
    $nodeRssiValues[] = connectivityChartValue($row['avg_rssi'] ?? null, 1);
    $nodeSnrValues[] = connectivityChartValue($row['avg_snr'] ?? null, 1);
}

$environmentRows = fetchAll("
    SELECT
        COALESCE(environment_type, 'unknown') AS environment_type,
        COUNT(*) AS total_tests,
        AVG(packet_success_rate) AS avg_success,
        AVG(packet_loss_percent) AS avg_loss,
        AVG(rssi_dbm) AS avg_rssi,
        AVG(snr_db) AS avg_snr
    FROM connectivity_tests
    GROUP BY environment_type
    ORDER BY avg_success DESC, total_tests DESC
");

$environmentLabels = [];
$environmentSuccessValues = [];
$environmentLossValues = [];
$environmentSnrValues = [];
foreach ($environmentRows as $row) {
    $environmentLabels[] = ucfirst((string) $row['environment_type']);
    $environmentSuccessValues[] = connectivityChartValue($row['avg_success'] ?? null, 2);
    $environmentLossValues[] = connectivityChartValue($row['avg_loss'] ?? null, 2);
    $environmentSnrValues[] = connectivityChartValue($row['avg_snr'] ?? null, 1);
}

$timelineRows = fetchAll("
    SELECT *
    FROM (
        SELECT
            test_date,
            COUNT(*) AS total_tests,
            AVG(packet_success_rate) AS avg_success,
            AVG(packet_loss_percent) AS avg_loss,
            AVG(rssi_dbm) AS avg_rssi,
            AVG(snr_db) AS avg_snr
        FROM connectivity_tests
        GROUP BY test_date
        ORDER BY test_date DESC
        LIMIT 30
    ) AS recent_days
    ORDER BY test_date ASC
");

$timelineLabels = [];
$timelineSuccessValues = [];
$timelineLossValues = [];
$timelineRssiValues = [];
foreach ($timelineRows as $row) {
    $timelineLabels[] = formatDate($row['test_date']);
    $timelineSuccessValues[] = connectivityChartValue($row['avg_success'] ?? null, 2);
    $timelineLossValues[] = connectivityChartValue($row['avg_loss'] ?? null, 2);
    $timelineRssiValues[] = connectivityChartValue($row['avg_rssi'] ?? null, 1);
}

$connectivityGraphRows = fetchAll("
    SELECT
        id,
        test_date,
        location_name,
        environment_type,
        node_id,
        node_type,
        connection_status,
        rssi_dbm,
        snr_db,
        packet_success_rate,
        packet_loss_percent
    FROM connectivity_tests
    WHERE node_id IS NOT NULL AND node_id != ''
    ORDER BY test_date DESC, created_at DESC
    LIMIT 60
");

$connectivityNetworkData = [];
foreach ($connectivityGraphRows as $row) {
    $connectivityNetworkData[] = [
        'id' => (int) $row['id'],
        'date' => $row['test_date'],
        'location' => $row['location_name'] ?: 'Tanpa Lokasi',
        'environment' => $row['environment_type'] ?: 'unknown',
        'node' => $row['node_id'],
        'nodeType' => $row['node_type'] ?: '-',
        'status' => $row['connection_status'] ?: 'unknown',
        'rssi' => connectivityChartValue($row['rssi_dbm'] ?? null, 1),
        'snr' => connectivityChartValue($row['snr_db'] ?? null, 1),
        'success' => connectivityChartValue($row['packet_success_rate'] ?? null, 2),
        'loss' => connectivityChartValue($row['packet_loss_percent'] ?? null, 2),
    ];
}
?>

<div class="content-section">
    <h4 class="mb-4"><i class="fas fa-link"></i> Connectivity Test</h4>
    <p class="text-muted mb-4">Input data pengujian konektivitas WiFi HaLow</p>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <?php if ($canManageProject): ?>
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-edit"></i> Input Data Connectivity Test</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="_connectivity_action" value="create">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Test Date</label>
                                <input type="date" class="form-control" name="test_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Location Name</label>
                                <input type="text" class="form-control" name="location_name" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Environment Type</label>
                                <select class="form-select" name="environment_type" required>
                                    <option value="">Select environment</option>
                                    <option value="lapangan">Lapangan</option>
                                    <option value="hangar">Hangar</option>
                                    <option value="pantai">Pantai</option>
                                    <option value="gunung">Gunung</option>
                                    <option value="indoor">Indoor</option>
                                    <option value="outdoor">Outdoor</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Distance (m)</label>
                                <input type="number" step="0.01" class="form-control" name="distance_meter" placeholder="e.g., 100">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Node Type</label>
                                <select class="form-select" name="node_type" required>
                                    <option value="">Select type</option>
                                    <option value="master">Master</option>
                                    <option value="slave">Slave</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Node ID</label>
                            <input type="text" class="form-control" name="node_id" placeholder="e.g., NODE-01" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Connection Status</label>
                                <select class="form-select" name="connection_status" required>
                                    <option value="">Select status</option>
                                    <option value="connected">Connected</option>
                                    <option value="disconnected">Disconnected</option>
                                    <option value="intermittent">Intermittent</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Test Duration (seconds)</label>
                                <input type="number" class="form-control" name="test_duration_second" value="60" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">RSSI (dBm)</label>
                                <input type="number" step="0.1" class="form-control" name="rssi_dbm" placeholder="-30 to -90">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">SNR (dB)</label>
                                <input type="number" step="0.1" class="form-control" name="snr_db" placeholder="0 to 50">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Packet Sent</label>
                                <input type="number" class="form-control" name="packet_sent" value="1000">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Packet Received</label>
                                <input type="number" class="form-control" name="packet_received" value="1000">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Test Data
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="fas fa-eye"></i> Mode Viewer</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">Form input, edit, dan hapus dinonaktifkan untuk role ini. Data connectivity tetap bisa dilihat dari statistik, tabel, detail, grafik, dan export.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-calculator"></i> Auto Calculations</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6>Formulas Used:</h6>
                        <ul class="mb-0">
                            <li>Packet Lost = Packet Sent - Packet Received</li>
                            <li>Packet Loss % = (Packet Lost / Packet Sent) × 100</li>
                            <li>Success Rate % = (Packet Received / Packet Sent) × 100</li>
                        </ul>
                    </div>
                    <div id="calc-results">
                        <h6>Results will appear here after form submission</h6>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-line"></i> Quick Stats</h6>
                </div>
                <div class="card-body">
                    <?php
                    $total_tests = count($connectivity_tests);
                    $rssiValues = [];
                    $lossValues = [];
                    $connected = 0;
                    foreach ($connectivity_tests as $test) {
                        if (is_numeric($test['rssi_dbm'] ?? null)) {
                            $rssiValues[] = (float) $test['rssi_dbm'];
                        }
                        if (is_numeric($test['packet_loss_percent'] ?? null)) {
                            $lossValues[] = (float) $test['packet_loss_percent'];
                        }
                        if ($test['connection_status'] === 'connected') $connected++;
                    }
                    $avg_rssi = $rssiValues ? round(array_sum($rssiValues) / count($rssiValues), 2) : null;
                    $avg_loss = $lossValues ? round(array_sum($lossValues) / count($lossValues), 2) : null;
                    $success_rate = $total_tests > 0 ? round(($connected / $total_tests) * 100, 2) : 0;
                    ?>
                    <div class="row text-center quick-stats-grid">
                        <div class="col-6 mb-3">
                            <div class="quick-stat-box">
                                <h3 class="text-primary"><?php echo $total_tests; ?></h3>
                                <small>Total Tests</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="quick-stat-box">
                                <h3 class="text-success"><?php echo $success_rate; ?>%</h3>
                                <small>Success Rate</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="quick-stat-box">
                                <h3 class="text-warning"><?php echo connectivityDisplayNumber($avg_rssi, 2); ?></h3>
                                <small>Avg RSSI (dBm)</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="quick-stat-box">
                                <h3 class="text-danger"><?php echo connectivityDisplayNumber($avg_loss, 2, '%'); ?></h3>
                                <small>Avg Loss %</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="content-section">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h4><i class="fas fa-table"></i> Connectivity Test Results</h4>
        <div class="d-flex align-items-center flex-wrap gap-2">
            <a class="btn btn-success btn-sm" href="export_excel.php?table=connectivity_tests">
                <i class="fas fa-file-excel"></i> Export Excel 365
            </a>
            <button class="btn btn-outline-primary btn-sm" onclick="exportTable('connectivity', 'csv')">
                <i class="fas fa-file-csv"></i> Export CSV
            </button>
            <button class="btn btn-outline-danger btn-sm" onclick="exportTable('connectivity', 'pdf')">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-bordered table-hover data-table">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Environment</th>
                    <th>Distance</th>
                    <th>Node</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>RSSI</th>
                    <th>SNR</th>
                    <th>Success</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($connectivity_tests) > 0): ?>
                    <?php $connectivityDisplayId = 1; ?>
                    <?php foreach ($connectivity_tests as $test): ?>
                    <tr>
                        <td><?php echo $connectivityDisplayId++; ?></td>
                        <td><?php echo formatDate($test['test_date']); ?></td>
                        <td><?php echo htmlspecialchars($test['location_name']); ?></td>
                        <td><?php echo ucfirst($test['environment_type']); ?></td>
                        <td><?php echo connectivityDisplayNumber($test['distance_meter'] ?? null, 2, ' m'); ?></td>
                        <td><?php echo htmlspecialchars($test['node_id']); ?></td>
                        <td><?php echo ucfirst($test['node_type']); ?></td>
                        <td><?php echo getStatusBadge($test['connection_status']); ?></td>
                        <td><?php echo connectivityDisplayNumber($test['rssi_dbm'], 2, ' dBm'); ?></td>
                        <td><?php echo connectivityDisplayNumber($test['snr_db'], 2, ' dB'); ?></td>
                        <td><?php echo connectivityDisplayNumber($test['packet_success_rate'], 2, '%'); ?></td>
                        <td class="text-end text-nowrap table-action-buttons">
                            <button class="btn btn-sm btn-outline-primary" onclick="viewConnectivity(<?php echo $test['id']; ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if ($canManageProject): ?>
                                <button class="btn btn-sm btn-outline-warning" onclick="editConnectivity(<?php echo $test['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteConnectivity(<?php echo $test['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="12" class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No data available. Add your first test result above.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart Section -->
<div class="content-section connectivity-analysis-section">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-2 mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-chart-bar"></i> Connectivity Analysis</h4>
            <p class="text-muted mb-0">Ringkasan visual untuk membaca stabilitas node, kualitas sinyal, dan lokasi pengujian.</p>
        </div>
        <div class="connectivity-status-legend">
            <span><i class="legend-dot legend-connected"></i>Connected</span>
            <span><i class="legend-dot legend-intermittent"></i>Intermittent</span>
            <span><i class="legend-dot legend-disconnected"></i>Disconnected</span>
        </div>
    </div>

    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-project-diagram"></i> Peta Relasi Node, Lokasi, dan Status</h6>
                </div>
                <div class="card-body">
                    <div class="connectivity-network-wrap">
                        <canvas id="connectivityNetworkChart"></canvas>
                    </div>
                    <div class="chart-note mt-3">
                        Lingkaran mewakili node, berlian mewakili lokasi, garis menunjukkan node pernah diuji di lokasi tersebut. Warna node menunjukkan status koneksi dominan.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-signal"></i> Perbandingan Kualitas per Node</h6>
                </div>
                <div class="card-body">
                    <div class="chart-note mb-3">Success rate tinggi dan packet loss rendah menunjukkan node lebih stabil.</div>
                    <div class="chart-container connectivity-chart-container">
                        <canvas id="nodeQualityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-circle-nodes"></i> Distribusi Status Koneksi</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container connectivity-chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-wave-square"></i> Peta RSSI vs SNR</h6>
                </div>
                <div class="card-body">
                    <div class="chart-note mb-3">Titik di kanan atas berarti sinyal lebih kuat dan noise lebih rendah.</div>
                    <div class="chart-container connectivity-chart-container">
                        <canvas id="signalQualityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-chart-line"></i> Tren Harian Connectivity</h6>
                </div>
                <div class="card-body">
                    <div class="chart-note mb-3">Bandingkan success rate, packet loss, dan rata-rata RSSI dari tanggal ke tanggal.</div>
                    <div class="chart-container connectivity-chart-container">
                        <canvas id="connectivityTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 mb-4 mb-md-0">
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-map-marked-alt"></i> Perbandingan Environment Pengujian</h6>
                </div>
                <div class="card-body">
                    <div class="chart-note mb-3">Grafik ini membantu melihat lingkungan mana yang paling stabil dan mana yang paling banyak kehilangan paket.</div>
                    <div class="chart-container connectivity-chart-container">
                        <canvas id="environmentQualityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Build distance-based chart data for connectivity
$connectivityDistanceData = [];
$connectivityEnvironments = [];
$connectivityByEnvironment = [];

foreach ($connectivity_tests as $test) {
    $env = $test['environment_type'] ?? 'unknown';
    if ($env === null || $env === '') {
        $env = 'unknown';
    }
    $connectivityEnvironments[$env] = true;
    
    $distance = $test['distance_meter'] ?? null;
    if ($distance === null || !is_numeric($distance)) {
        continue;
    }
    
    if (!isset($connectivityByEnvironment[$env])) {
        $connectivityByEnvironment[$env] = [];
    }
    $connectivityByEnvironment[$env][] = $test;
}

// Sort each environment by distance
foreach ($connectivityByEnvironment as $env => &$envRows) {
    usort($envRows, function ($a, $b) {
        return (float) $a['distance_meter'] <=> (float) $b['distance_meter'];
    });
}
unset($envRows);

$connectivityEnvColors = [
    'lapangan' => '#2563eb',
    'hangar' => '#7c3aed',
    'pantai' => '#0891b2',
    'gunung' => '#16a34a',
    'indoor' => '#d97706',
    'outdoor' => '#dc2626',
    'unknown' => '#64748b',
];

foreach ($connectivityEnvironments as $env => $dummy) {
    $envRows = $connectivityByEnvironment[$env] ?? [];
    if (empty($envRows)) {
        continue;
    }
    
    $distances = [];
    $rssiValues = [];
    $snrValues = [];
    $successValues = [];
    $lossValues = [];
    
    foreach ($envRows as $row) {
        $distances[] = (float) $row['distance_meter'];
        $rssiValues[] = is_numeric($row['rssi_dbm'] ?? null) ? (float) $row['rssi_dbm'] : null;
        $snrValues[] = is_numeric($row['snr_db'] ?? null) ? (float) $row['snr_db'] : null;
        $successValues[] = is_numeric($row['packet_success_rate'] ?? null) ? (float) $row['packet_success_rate'] : null;
        $lossValues[] = is_numeric($row['packet_loss_percent'] ?? null) ? (float) $row['packet_loss_percent'] : null;
    }
    
    $connectivityDistanceData[$env] = [
        'distances' => $distances,
        'rssi' => $rssiValues,
        'snr' => $snrValues,
        'success' => $successValues,
        'loss' => $lossValues,
        'color' => $connectivityEnvColors[$env] ?? '#64748b',
        'count' => count($envRows),
    ];
}
?>

<?php if (!empty($connectivityDistanceData)): ?>
<div class="content-section">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-ruler-horizontal"></i> Grafik Berbasis Jarak</h4>
            <p class="text-muted mb-0">Analisis performa berdasarkan jarak (meter) dengan filter environment. Sumbut X menampilkan jarak, sumbu Y menampilkan nilai metrik.</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h6 class="m-0 font-weight-bold text-primary">Filter Environment</h6>
                        <small class="text-muted">Pilih environment untuk menampilkan data spesifik atau gabungan</small>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-primary connectivity-distance-env-filter active" data-env="all">
                            <i class="fas fa-globe"></i> Semua
                        </button>
                        <?php foreach (array_keys($connectivityDistanceData) as $env): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary connectivity-distance-env-filter" data-env="<?php echo htmlspecialchars($env); ?>">
                                <i class="fas fa-map-marker-alt"></i> <?php echo ucfirst(htmlspecialchars($env)); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 mb-4">
            <div class="card">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">RSSI vs Jarak</h6>
                    <small class="text-muted">Hubungan antara jarak dan kekuatan sinyal (RSSI)</small>
                </div>
                <div class="card-body">
                    <div class="chart-container connectivity-distance-chart-container">
                        <canvas id="connectivityDistanceRssiChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 mb-4">
            <div class="card">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Packet Loss vs Jarak</h6>
                    <small class="text-muted">Hubungan antara jarak dan persentase packet loss</small>
                </div>
                <div class="card-body">
                    <div class="chart-container connectivity-distance-chart-container">
                        <canvas id="connectivityDistanceLossChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="connectivityViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-eye"></i> Detail Connectivity Test</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <tbody id="connectivityViewBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php if ($canManageProject): ?>
<div class="modal fade" id="connectivityEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" id="connectivityEditForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Connectivity Test</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_connectivity_action" value="update">
                    <input type="hidden" name="id" id="connectivityEditId">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Test Date</label>
                            <input type="date" class="form-control" name="test_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location Name</label>
                            <input type="text" class="form-control" name="location_name" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Environment Type</label>
                            <select class="form-select" name="environment_type" required>
                                <option value="">Select environment</option>
                                <option value="lapangan">Lapangan</option>
                                <option value="hangar">Hangar</option>
                                <option value="pantai">Pantai</option>
                                <option value="gunung">Gunung</option>
                                <option value="indoor">Indoor</option>
                                <option value="outdoor">Outdoor</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Distance (m)</label>
                            <input type="number" step="0.01" class="form-control" name="distance_meter" placeholder="e.g., 100">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Node Type</label>
                            <select class="form-select" name="node_type" required>
                                <option value="">Select type</option>
                                <option value="master">Master</option>
                                <option value="slave">Slave</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Node ID</label>
                        <input type="text" class="form-control" name="node_id" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Connection Status</label>
                            <select class="form-select" name="connection_status" required>
                                <option value="">Select status</option>
                                <option value="connected">Connected</option>
                                <option value="disconnected">Disconnected</option>
                                <option value="intermittent">Intermittent</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Test Duration (seconds)</label>
                            <input type="number" class="form-control" name="test_duration_second" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">RSSI (dBm)</label>
                            <input type="number" step="0.1" class="form-control" name="rssi_dbm">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SNR (dB)</label>
                            <input type="number" step="0.1" class="form-control" name="snr_db">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Packet Sent</label>
                            <input type="number" class="form-control" name="packet_sent">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Packet Received</label>
                            <input type="number" class="form-control" name="packet_received">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="connectivityDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash"></i> Hapus Connectivity Test</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_connectivity_action" value="delete">
                    <input type="hidden" name="id" id="connectivityDeleteId">
                    <p class="mb-0">Yakin ingin menghapus data <strong id="connectivityDeleteLabel"></strong>? Data yang sudah dihapus tidak bisa dikembalikan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
var connectivityRows = <?php echo json_encode($connectivityMap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
var connectivityLabels = {
    id: 'ID',
    test_date: 'Test Date',
    location_name: 'Location Name',
    environment_type: 'Environment Type',
    distance_meter: 'Distance (m)',
    node_id: 'Node ID',
    node_type: 'Node Type',
    connection_status: 'Connection Status',
    rssi_dbm: 'RSSI (dBm)',
    snr_db: 'SNR (dB)',
    packet_sent: 'Packet Sent',
    packet_received: 'Packet Received',
    packet_lost: 'Packet Lost',
    packet_loss_percent: 'Packet Loss (%)',
    packet_success_rate: 'Packet Success Rate (%)',
    test_duration_second: 'Test Duration (seconds)',
    notes: 'Notes',
    created_at: 'Created At',
    updated_at: 'Updated At'
};

function connectivityRow(id) {
    return connectivityRows[String(id)] || null;
}

function escapeHtml(value) {
    if (value === null || value === undefined || value === '') {
        return 'N/A';
    }

    return $('<div>').text(value).html();
}

// Initialize DataTable
$(document).ready(function() {
    $('.data-table').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json"
        },
        "order": [[0, 'asc']],
        "columnDefs": [
            { "targets": -1, "orderable": false, "searchable": false }
        ]
    });
});

var statusCountsRaw = <?php echo json_encode($statusCounts); ?>;
var connectivityAnalysis = {
    nodeLabels: <?php echo json_encode($nodeQualityLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    nodeSuccess: <?php echo json_encode($nodeSuccessValues); ?>,
    nodeLoss: <?php echo json_encode($nodeLossValues); ?>,
    nodeRssi: <?php echo json_encode($nodeRssiValues); ?>,
    nodeSnr: <?php echo json_encode($nodeSnrValues); ?>,
    environmentLabels: <?php echo json_encode($environmentLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    environmentSuccess: <?php echo json_encode($environmentSuccessValues); ?>,
    environmentLoss: <?php echo json_encode($environmentLossValues); ?>,
    environmentSnr: <?php echo json_encode($environmentSnrValues); ?>,
    timelineLabels: <?php echo json_encode($timelineLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    timelineSuccess: <?php echo json_encode($timelineSuccessValues); ?>,
    timelineLoss: <?php echo json_encode($timelineLossValues); ?>,
    timelineRssi: <?php echo json_encode($timelineRssiValues); ?>,
    network: <?php echo json_encode($connectivityNetworkData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
};

var chartColors = {
    connected: '#16a34a',
    intermittent: '#d97706',
    disconnected: '#dc2626',
    success: '#15803d',
    loss: '#dc2626',
    rssi: '#2563eb',
    snr: '#7c3aed',
    grid: 'rgba(15, 23, 42, 0.1)'
};

var emptyChartPlugin = {
    id: 'connectivityEmptyMessage',
    afterDraw: function(chart, args, options) {
        var hasData = chart.data.datasets.some(function(dataset) {
            return (dataset.data || []).some(function(value) {
                if (value && typeof value === 'object') {
                    return Number.isFinite(Number(value.x)) || Number.isFinite(Number(value.y));
                }
                return value !== null && value !== undefined && Number.isFinite(Number(value));
            });
        });

        if (hasData) return;

        var ctx = chart.ctx;
        var area = chart.chartArea;
        ctx.save();
        ctx.fillStyle = '#64748b';
        ctx.font = '600 14px Segoe UI, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(options.message || 'Belum ada data grafik', (area.left + area.right) / 2, (area.top + area.bottom) / 2);
        ctx.restore();
    }
};

function chartPluginList(includeLabels) {
    var plugins = [emptyChartPlugin];
    if (includeLabels && window.ChartDataLabels) {
        plugins.push(ChartDataLabels);
    }
    return plugins;
}

function percentTooltip(label) {
    return function(context) {
        var parsedValue = context.parsed.x !== undefined ? context.parsed.x : (context.parsed.y !== undefined ? context.parsed.y : context.raw);
        return label + ': ' + (parsedValue === null || parsedValue === undefined || !Number.isFinite(Number(parsedValue)) ? 'N/A' : Number(parsedValue).toFixed(2) + '%');
    };
}

new Chart(document.getElementById('statusChart').getContext('2d'), {
    type: 'doughnut',
    plugins: chartPluginList(true),
    data: {
        labels: ['Connected', 'Intermittent', 'Disconnected'],
        datasets: [{
            data: [statusCountsRaw[0] || 0, statusCountsRaw[2] || 0, statusCountsRaw[1] || 0],
            backgroundColor: [
                'rgba(22, 163, 74, 0.85)',
                'rgba(217, 119, 6, 0.85)',
                'rgba(220, 38, 38, 0.85)'
            ],
            borderColor: '#ffffff',
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '58%',
        plugins: {
            connectivityEmptyMessage: {
                message: 'Belum ada status koneksi'
            },
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    padding: 16
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        var values = context.dataset.data;
                        var total = values.reduce(function(sum, value) { return sum + Number(value || 0); }, 0);
                        var value = Number(context.raw || 0);
                        var percent = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
                        return context.label + ': ' + value + ' data (' + percent + '%)';
                    }
                }
            },
            datalabels: {
                color: '#111827',
                formatter: function(value, context) {
                    var total = context.dataset.data.reduce(function(sum, item) { return sum + Number(item || 0); }, 0);
                    if (!value || total === 0) return '';
                    return ((value / total) * 100).toFixed(0) + '%';
                },
                font: {
                    weight: '700',
                    size: 13
                }
            }
        }
    }
});

new Chart(document.getElementById('nodeQualityChart').getContext('2d'), {
    type: 'bar',
    plugins: chartPluginList(false),
    data: {
        labels: connectivityAnalysis.nodeLabels,
        datasets: [
            {
                label: 'Success Rate (%)',
                data: connectivityAnalysis.nodeSuccess,
                backgroundColor: 'rgba(21, 128, 61, 0.82)',
                borderColor: chartColors.success,
                borderWidth: 1,
                stack: 'quality'
            },
            {
                label: 'Packet Loss (%)',
                data: connectivityAnalysis.nodeLoss,
                backgroundColor: 'rgba(220, 38, 38, 0.72)',
                borderColor: chartColors.loss,
                borderWidth: 1,
                stack: 'quality'
            }
        ]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                min: 0,
                max: 100,
                stacked: true,
                title: {
                    display: true,
                    text: 'Persentase paket (%)'
                },
                ticks: {
                    callback: function(value) { return value + '%'; }
                },
                grid: {
                    color: chartColors.grid
                }
            },
            y: {
                stacked: true,
                grid: {
                    display: false
                }
            }
        },
        plugins: {
            connectivityEmptyMessage: {
                message: 'Belum ada data node'
            },
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        var nodeIndex = context.dataIndex;
                        var formatMetric = function(value, unit) {
                            return value === null || value === undefined || !Number.isFinite(Number(value))
                                ? 'N/A'
                                : Number(value).toFixed(2) + unit;
                        };
                        var parts = [
                            context.dataset.label + ': ' + formatMetric(context.raw, '%'),
                            'Avg RSSI: ' + formatMetric(connectivityAnalysis.nodeRssi[nodeIndex], ' dBm'),
                            'Avg SNR: ' + formatMetric(connectivityAnalysis.nodeSnr[nodeIndex], ' dB')
                        ];
                        return parts;
                    }
                }
            }
        }
    }
});

function signalDataset(status, label, color) {
    return {
        label: label,
        data: connectivityAnalysis.network
            .filter(function(row) { return row.status === status; })
            .map(function(row) {
                return {
                    x: row.rssi,
                    y: row.snr,
                    node: row.node,
                    location: row.location,
                    success: row.success,
                    loss: row.loss,
                    date: row.date
                };
            }),
        backgroundColor: color,
        borderColor: color,
        pointRadius: function(context) {
            return Math.max(5, Math.min(12, Number(context.raw && context.raw.success ? context.raw.success : 0) / 10));
        },
        pointHoverRadius: 12
    };
}

new Chart(document.getElementById('signalQualityChart').getContext('2d'), {
    type: 'scatter',
    plugins: chartPluginList(false),
    data: {
        datasets: [
            signalDataset('connected', 'Connected', 'rgba(22, 163, 74, 0.78)'),
            signalDataset('intermittent', 'Intermittent', 'rgba(217, 119, 6, 0.78)'),
            signalDataset('disconnected', 'Disconnected', 'rgba(220, 38, 38, 0.78)')
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                suggestedMin: -100,
                suggestedMax: -30,
                title: {
                    display: true,
                    text: 'RSSI (dBm) - semakin ke kanan sinyal semakin kuat'
                },
                grid: {
                    color: chartColors.grid
                }
            },
            y: {
                beginAtZero: true,
                suggestedMax: 35,
                title: {
                    display: true,
                    text: 'SNR (dB) - semakin tinggi semakin bersih'
                },
                grid: {
                    color: chartColors.grid
                }
            }
        },
        plugins: {
            connectivityEmptyMessage: {
                message: 'Belum ada data RSSI dan SNR'
            },
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        var row = context.raw;
                        return [
                            row.node + ' - ' + row.location,
                            'RSSI: ' + row.x + ' dBm',
                            'SNR: ' + row.y + ' dB',
                            'Success: ' + row.success + '%',
                            'Loss: ' + row.loss + '%'
                        ];
                    }
                }
            }
        }
    }
});

new Chart(document.getElementById('connectivityTrendChart').getContext('2d'), {
    data: {
        labels: connectivityAnalysis.timelineLabels,
        datasets: [
            {
                type: 'line',
                label: 'Success Rate (%)',
                data: connectivityAnalysis.timelineSuccess,
                borderColor: chartColors.success,
                backgroundColor: 'rgba(21, 128, 61, 0.12)',
                fill: true,
                tension: 0.35,
                yAxisID: 'yPercent'
            },
            {
                type: 'line',
                label: 'Packet Loss (%)',
                data: connectivityAnalysis.timelineLoss,
                borderColor: chartColors.loss,
                backgroundColor: 'rgba(220, 38, 38, 0.1)',
                tension: 0.35,
                yAxisID: 'yPercent'
            },
            {
                type: 'bar',
                label: 'Avg RSSI (dBm)',
                data: connectivityAnalysis.timelineRssi,
                backgroundColor: 'rgba(37, 99, 235, 0.24)',
                borderColor: chartColors.rssi,
                borderWidth: 1,
                yAxisID: 'yRssi'
            }
        ]
    },
    plugins: chartPluginList(false),
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            yPercent: {
                type: 'linear',
                position: 'left',
                min: 0,
                max: 100,
                title: {
                    display: true,
                    text: 'Success / Loss (%)'
                },
                ticks: {
                    callback: function(value) { return value + '%'; }
                },
                grid: {
                    color: chartColors.grid
                }
            },
            yRssi: {
                type: 'linear',
                position: 'right',
                suggestedMin: -100,
                suggestedMax: -30,
                title: {
                    display: true,
                    text: 'RSSI (dBm)'
                },
                grid: {
                    drawOnChartArea: false
                }
            }
        },
        plugins: {
            connectivityEmptyMessage: {
                message: 'Belum ada tren harian'
            },
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true
                }
            }
        }
    }
});

new Chart(document.getElementById('environmentQualityChart').getContext('2d'), {
    data: {
        labels: connectivityAnalysis.environmentLabels,
        datasets: [
            {
                type: 'bar',
                label: 'Success Rate (%)',
                data: connectivityAnalysis.environmentSuccess,
                backgroundColor: 'rgba(21, 128, 61, 0.78)',
                borderColor: chartColors.success,
                borderWidth: 1,
                yAxisID: 'yPercent'
            },
            {
                type: 'bar',
                label: 'Packet Loss (%)',
                data: connectivityAnalysis.environmentLoss,
                backgroundColor: 'rgba(220, 38, 38, 0.62)',
                borderColor: chartColors.loss,
                borderWidth: 1,
                yAxisID: 'yPercent'
            },
            {
                type: 'line',
                label: 'Avg SNR (dB)',
                data: connectivityAnalysis.environmentSnr,
                borderColor: chartColors.snr,
                backgroundColor: 'rgba(124, 58, 237, 0.1)',
                tension: 0.35,
                yAxisID: 'ySnr'
            }
        ]
    },
    plugins: chartPluginList(false),
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            yPercent: {
                min: 0,
                max: 100,
                position: 'left',
                title: {
                    display: true,
                    text: 'Success / Loss (%)'
                },
                ticks: {
                    callback: function(value) { return value + '%'; }
                },
                grid: {
                    color: chartColors.grid
                }
            },
            ySnr: {
                beginAtZero: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'SNR (dB)'
                },
                grid: {
                    drawOnChartArea: false
                }
            }
        },
        plugins: {
            connectivityEmptyMessage: {
                message: 'Belum ada data environment'
            },
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true
                }
            }
        }
    }
});

function renderConnectivityNetwork() {
    var canvas = document.getElementById('connectivityNetworkChart');
    if (!canvas) return;

    var wrap = canvas.parentElement;
    var box = wrap.getBoundingClientRect();
    var width = Math.max(320, Math.floor(box.width));
    var height = Math.max(360, Math.floor(box.height));
    var ratio = window.devicePixelRatio || 1;
    canvas.width = width * ratio;
    canvas.height = height * ratio;
    canvas.style.width = width + 'px';
    canvas.style.height = height + 'px';

    var ctx = canvas.getContext('2d');
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    ctx.clearRect(0, 0, width, height);
    ctx.fillStyle = '#f8fafc';
    ctx.fillRect(0, 0, width, height);

    var rows = connectivityAnalysis.network || [];
    if (rows.length === 0) {
        ctx.fillStyle = '#64748b';
        ctx.font = '600 15px Segoe UI, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('Belum ada data relasi node dan lokasi', width / 2, height / 2);
        return;
    }

    var locations = {};
    var nodes = {};
    rows.forEach(function(row) {
        var locationKey = row.location || 'Tanpa Lokasi';
        var nodeKey = row.node || 'Node';
        if (!locations[locationKey]) {
            locations[locationKey] = {
                id: locationKey,
                environment: row.environment || 'unknown',
                count: 0
            };
        }
        locations[locationKey].count += 1;

        if (!nodes[nodeKey]) {
            nodes[nodeKey] = {
                id: nodeKey,
                type: row.nodeType || '-',
                count: 0,
                successCount: 0,
                successTotal: 0,
                rssiCount: 0,
                rssiTotal: 0,
                snrCount: 0,
                snrTotal: 0,
                locationCounts: {},
                statusCounts: {
                    connected: 0,
                    intermittent: 0,
                    disconnected: 0
                }
            };
        }
        nodes[nodeKey].count += 1;
        if (row.success !== null && row.success !== undefined && Number.isFinite(Number(row.success))) {
            nodes[nodeKey].successTotal += Number(row.success);
            nodes[nodeKey].successCount += 1;
        }
        if (row.rssi !== null && row.rssi !== undefined && Number.isFinite(Number(row.rssi))) {
            nodes[nodeKey].rssiTotal += Number(row.rssi);
            nodes[nodeKey].rssiCount += 1;
        }
        if (row.snr !== null && row.snr !== undefined && Number.isFinite(Number(row.snr))) {
            nodes[nodeKey].snrTotal += Number(row.snr);
            nodes[nodeKey].snrCount += 1;
        }
        nodes[nodeKey].locationCounts[locationKey] = (nodes[nodeKey].locationCounts[locationKey] || 0) + 1;
        if (nodes[nodeKey].statusCounts[row.status] !== undefined) {
            nodes[nodeKey].statusCounts[row.status] += 1;
        }
    });

    var locationList = Object.values(locations);
    var nodeList = Object.values(nodes);
    var centerX = width / 2;
    var centerY = height / 2;
    var locationRadius = Math.min(width, height) * 0.22;

    locationList.forEach(function(location, index) {
        var angle = (-Math.PI / 2) + (Math.PI * 2 * index / Math.max(1, locationList.length));
        location.x = centerX + Math.cos(angle) * locationRadius;
        location.y = centerY + Math.sin(angle) * locationRadius;
    });

    nodeList.forEach(function(node, index) {
        var primaryLocation = Object.keys(node.locationCounts).sort(function(a, b) {
            return node.locationCounts[b] - node.locationCounts[a];
        })[0];
        var location = locations[primaryLocation] || locationList[index % locationList.length];
        var angle = (Math.PI * 2 * index / Math.max(1, nodeList.length)) + (index % 2 ? 0.45 : -0.3);
        var spread = Math.min(width, height) * (0.15 + ((index % 3) * 0.035));
        node.x = Math.max(34, Math.min(width - 34, location.x + Math.cos(angle) * spread));
        node.y = Math.max(34, Math.min(height - 34, location.y + Math.sin(angle) * spread));
        node.avgSuccess = node.successCount > 0 ? node.successTotal / node.successCount : null;
        node.avgRssi = node.rssiCount > 0 ? node.rssiTotal / node.rssiCount : null;
        node.avgSnr = node.snrCount > 0 ? node.snrTotal / node.snrCount : null;
        node.status = ['connected', 'intermittent', 'disconnected'].sort(function(a, b) {
            return node.statusCounts[b] - node.statusCounts[a];
        })[0];
    });

    ctx.strokeStyle = 'rgba(148, 163, 184, 0.22)';
    ctx.lineWidth = 1;
    for (var gx = 32; gx < width; gx += 48) {
        ctx.beginPath();
        ctx.moveTo(gx, 0);
        ctx.lineTo(gx, height);
        ctx.stroke();
    }
    for (var gy = 32; gy < height; gy += 48) {
        ctx.beginPath();
        ctx.moveTo(0, gy);
        ctx.lineTo(width, gy);
        ctx.stroke();
    }

    rows.forEach(function(row) {
        var node = nodes[row.node];
        var location = locations[row.location || 'Tanpa Lokasi'];
        if (!node || !location) return;
        var color = row.status === 'connected'
            ? 'rgba(22, 163, 74, 0.28)'
            : row.status === 'intermittent'
                ? 'rgba(217, 119, 6, 0.3)'
                : 'rgba(220, 38, 38, 0.3)';
        ctx.beginPath();
        ctx.strokeStyle = color;
        ctx.lineWidth = Math.max(1.2, Math.min(4, Number(row.success || 0) / 28));
        ctx.moveTo(location.x, location.y);
        ctx.lineTo(node.x, node.y);
        ctx.stroke();
    });

    locationList.forEach(function(location) {
        ctx.save();
        ctx.translate(location.x, location.y);
        ctx.rotate(Math.PI / 4);
        ctx.fillStyle = '#ffffff';
        ctx.strokeStyle = '#475569';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.rect(-12, -12, 24, 24);
        ctx.fill();
        ctx.stroke();
        ctx.restore();

        drawNetworkLabel(ctx, location.id, location.x, location.y + 30, '#334155', '#ffffff');
    });

    nodeList.forEach(function(node) {
        var fill = node.status === 'connected'
            ? chartColors.connected
            : node.status === 'intermittent'
                ? chartColors.intermittent
                : chartColors.disconnected;
        var radius = Math.max(10, Math.min(18, 8 + node.count * 2));
        ctx.beginPath();
        ctx.fillStyle = fill;
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 3;
        ctx.arc(node.x, node.y, radius, 0, Math.PI * 2);
        ctx.fill();
        ctx.stroke();

        ctx.fillStyle = '#ffffff';
        ctx.font = '700 10px Segoe UI, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(node.avgSuccess === null ? 'N/A' : Math.round(node.avgSuccess) + '%', node.x, node.y);

        drawNetworkLabel(ctx, node.id, node.x, node.y + radius + 16, '#0f172a', 'rgba(255, 255, 255, 0.92)');
    });
}

function drawNetworkLabel(ctx, text, x, y, color, background) {
    var label = String(text || '-');
    ctx.save();
    ctx.font = '600 11px Segoe UI, sans-serif';
    var width = Math.min(150, ctx.measureText(label).width + 12);
    ctx.fillStyle = background;
    ctx.strokeStyle = 'rgba(148, 163, 184, 0.35)';
    ctx.lineWidth = 1;
    drawRoundedRect(ctx, x - width / 2, y - 9, width, 18, 5);
    ctx.fillStyle = color;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    var clipped = label.length > 22 ? label.substring(0, 21) + '...' : label;
    ctx.fillText(clipped, x, y);
    ctx.restore();
}

function drawRoundedRect(ctx, x, y, width, height, radius) {
    var r = Math.min(radius, width / 2, height / 2);
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + width - r, y);
    ctx.quadraticCurveTo(x + width, y, x + width, y + r);
    ctx.lineTo(x + width, y + height - r);
    ctx.quadraticCurveTo(x + width, y + height, x + width - r, y + height);
    ctx.lineTo(x + r, y + height);
    ctx.quadraticCurveTo(x, y + height, x, y + height - r);
    ctx.lineTo(x, y + r);
    ctx.quadraticCurveTo(x, y, x + r, y);
    ctx.closePath();
    ctx.fill();
    ctx.stroke();
}

renderConnectivityNetwork();
window.addEventListener('resize', function() {
    window.clearTimeout(window.connectivityNetworkResizeTimer);
    window.connectivityNetworkResizeTimer = window.setTimeout(renderConnectivityNetwork, 150);
});

function exportTable(type, format) {
    alert(format.toUpperCase() + ' export for ' + type + ' data will be implemented');
}

function viewConnectivity(id) {
    var row = connectivityRow(id);
    if (!row) return;

    var html = '';
    Object.keys(connectivityLabels).forEach(function(field) {
        if (!Object.prototype.hasOwnProperty.call(row, field)) return;
        html += '<tr><th style="width: 35%;">' + escapeHtml(connectivityLabels[field]) + '</th><td>' + escapeHtml(row[field]) + '</td></tr>';
    });

    $('#connectivityViewBody').html(html);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('connectivityViewModal')).show();
}

<?php if ($canManageProject): ?>
function editConnectivity(id) {
    var row = connectivityRow(id);
    if (!row) return;

    var form = $('#connectivityEditForm');
    $('#connectivityEditId').val(row.id);
    [
        'test_date',
        'location_name',
        'environment_type',
        'distance_meter',
        'node_id',
        'node_type',
        'connection_status',
        'rssi_dbm',
        'snr_db',
        'packet_sent',
        'packet_received',
        'test_duration_second',
        'notes'
    ].forEach(function(field) {
        form.find('[name="' + field + '"]').val(row[field] === null || row[field] === undefined ? '' : row[field]);
    });

    bootstrap.Modal.getOrCreateInstance(document.getElementById('connectivityEditModal')).show();
}

function deleteConnectivity(id) {
    $('#connectivityDeleteId').val(id);
    $('#connectivityDeleteLabel').text('#' + id);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('connectivityDeleteModal')).show();
}
<?php endif; ?>

<?php if (!empty($connectivityDistanceData)): ?>
// Distance-based charts for connectivity
var connectivityDistanceData = <?php echo json_encode($connectivityDistanceData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
var connectivityDistanceRssiChart = null;
var connectivityDistanceLossChart = null;

function createConnectivityDistanceCharts() {
    var rssiDatasets = [];
    var lossDatasets = [];
    
    Object.keys(connectivityDistanceData).forEach(function(env) {
        var data = connectivityDistanceData[env];
        
        rssiDatasets.push({
            label: ucfirst(env) + ' - RSSI',
            data: data.rssi.map(function(value, index) {
                return { x: data.distances[index], y: value };
            }),
            borderColor: data.color,
            backgroundColor: data.color + '26',
            borderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 8,
            tension: 0.3,
            fill: false,
            showLine: true,
            env: env
        });
        
        lossDatasets.push({
            label: ucfirst(env) + ' - Packet Loss',
            data: data.loss.map(function(value, index) {
                return { x: data.distances[index], y: value };
            }),
            borderColor: data.color,
            backgroundColor: data.color + '26',
            borderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 8,
            tension: 0.3,
            fill: false,
            showLine: true,
            env: env
        });
    });
    
    var rssiCanvas = document.getElementById('connectivityDistanceRssiChart');
    if (rssiCanvas && rssiDatasets.length > 0) {
        connectivityDistanceRssiChart = new Chart(rssiCanvas.getContext('2d'), {
            type: 'scatter',
            data: { datasets: rssiDatasets },
            plugins: [emptyChartPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'nearest', intersect: false },
                plugins: {
                    connectivityEmptyMessage: { message: 'Belum ada data jarak' },
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12 } },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        callbacks: {
                            title: function(items) {
                                if (!items.length) return '';
                                return 'Jarak: ' + items[0].parsed.x.toFixed(2) + ' m';
                            },
                            label: function(context) {
                                return context.dataset.label + ': ' + (context.parsed.y !== null ? context.parsed.y.toFixed(2) + ' dBm' : 'N/A');
                            }
                        }
                    }
                },
                scales: {
                    x: { type: 'linear', position: 'bottom', title: { display: true, text: 'Jarak (m)' }, grid: { color: 'rgba(15, 23, 42, 0.1)' } },
                    y: { title: { display: true, text: 'RSSI (dBm)' }, grid: { color: 'rgba(15, 23, 42, 0.1)' } }
                }
            }
        });
    }
    
    var lossCanvas = document.getElementById('connectivityDistanceLossChart');
    if (lossCanvas && lossDatasets.length > 0) {
        connectivityDistanceLossChart = new Chart(lossCanvas.getContext('2d'), {
            type: 'scatter',
            data: { datasets: lossDatasets },
            plugins: [emptyChartPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'nearest', intersect: false },
                plugins: {
                    connectivityEmptyMessage: { message: 'Belum ada data jarak' },
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12 } },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        callbacks: {
                            title: function(items) {
                                if (!items.length) return '';
                                return 'Jarak: ' + items[0].parsed.x.toFixed(2) + ' m';
                            },
                            label: function(context) {
                                return context.dataset.label + ': ' + (context.parsed.y !== null ? context.parsed.y.toFixed(2) + '%' : 'N/A');
                            }
                        }
                    }
                },
                scales: {
                    x: { type: 'linear', position: 'bottom', title: { display: true, text: 'Jarak (m)' }, grid: { color: 'rgba(15, 23, 42, 0.1)' } },
                    y: { beginAtZero: true, max: 100, title: { display: true, text: 'Packet Loss (%)' }, grid: { color: 'rgba(15, 23, 42, 0.1)' } }
                }
            }
        });
    }
}

function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Environment filter for connectivity distance charts
$(document).on('click', '.connectivity-distance-env-filter', function() {
    var env = $(this).data('env');
    $('.connectivity-distance-env-filter').removeClass('active btn-primary').addClass('btn-outline-secondary');
    $(this).removeClass('btn-outline-secondary').addClass('active btn-primary');
    
    if (connectivityDistanceRssiChart) {
        connectivityDistanceRssiChart.data.datasets.forEach(function(dataset) {
            dataset.hidden = env === 'all' ? false : dataset.env !== env;
        });
        connectivityDistanceRssiChart.update();
    }
    
    if (connectivityDistanceLossChart) {
        connectivityDistanceLossChart.data.datasets.forEach(function(dataset) {
            dataset.hidden = env === 'all' ? false : dataset.env !== env;
        });
        connectivityDistanceLossChart.update();
    }
});

createConnectivityDistanceCharts();
<?php endif; ?>
</script>

<style>
.data-table th {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    font-weight: 600;
}
.data-table:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}
.quick-stat-box {
    height: 100%;
    min-height: 92px;
    padding: 14px 10px;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    background: #f8fafc;
}
.quick-stat-box h3 {
    margin-bottom: 4px;
    font-weight: 700;
    line-height: 1.1;
}
.quick-stat-box small {
    color: #4b5563;
    font-weight: 600;
}
.quick-stat-box .text-warning {
    color: #b45309 !important;
}
.connectivity-analysis-section .card {
    border-color: #dbe4ef;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
}
.connectivity-analysis-section .card-header {
    border-bottom: 1px solid #e5edf7;
}
.connectivity-status-legend {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #ffffff;
    color: #334155;
    font-size: 13px;
    font-weight: 600;
}
.connectivity-status-legend span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}
.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}
.legend-connected {
    background: #16a34a;
}
.legend-intermittent {
    background: #d97706;
}
.legend-disconnected {
    background: #dc2626;
}
.chart-note {
    color: #64748b;
    font-size: 13px;
    line-height: 1.45;
}
.connectivity-network-wrap {
    width: 100%;
    height: 460px;
    min-height: 460px;
    border: 1px solid #dbe4ef;
    border-radius: 8px;
    background: #f8fafc;
    overflow: hidden;
}
#connectivityNetworkChart {
    display: block;
    width: 100%;
    height: 100%;
}
.connectivity-chart-container {
    height: 360px;
    min-height: 360px;
}
.connectivity-distance-chart-container {
    height: 380px;
    min-height: 380px;
}
.connectivity-distance-env-filter {
    transition: all 0.2s ease;
}
.connectivity-distance-env-filter.active {
    font-weight: 700;
}
@media (max-width: 767.98px) {
    .connectivity-network-wrap {
        height: 380px;
        min-height: 380px;
    }
    .connectivity-chart-container {
        height: 340px;
        min-height: 340px;
    }
    .connectivity-distance-chart-container {
        height: 340px;
        min-height: 340px;
    }
    .connectivity-status-legend {
        width: 100%;
    }
}
</style>
