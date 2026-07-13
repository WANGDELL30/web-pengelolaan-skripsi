<?php
/**
 * Comprehensive Reports Page for WiFi HaLow Testing System
 * Menampilkan ringkasan semua pengujian dengan grafik yang mudah dibaca
 * Cocok untuk presentasi sidang skripsi
 */

// Summary statistics for all test types
$summaryStats = [
    'connectivity' => fetchAll("SELECT COUNT(*) as total FROM connectivity_tests")[0]['total'] ?? 0,
    'range' => fetchAll("SELECT COUNT(*) as total FROM range_tests")[0]['total'] ?? 0,
    'latency' => fetchAll("SELECT COUNT(*) as total FROM latency_tests")[0]['total'] ?? 0,
    'throughput' => fetchAll("SELECT COUNT(*) as total FROM throughput_tests")[0]['total'] ?? 0,
    'interference' => fetchAll("SELECT COUNT(*) as total FROM interference_tests")[0]['total'] ?? 0,
    'power' => fetchAll("SELECT COUNT(*) as total FROM power_consumption_tests")[0]['total'] ?? 0,
    'command' => fetchAll("SELECT COUNT(*) as total FROM command_execution_tests")[0]['total'] ?? 0,
    'text_message' => fetchAll("SELECT COUNT(*) as total FROM text_message_logs")[0]['total'] ?? 0,
];

// Connectivity Data
$connectivityData = fetchAll("
    SELECT test_date, location_name, environment_type, node_id,
        connection_status, rssi_dbm, snr_db, packet_loss_percent
    FROM connectivity_tests ORDER BY test_date DESC, created_at DESC LIMIT 50
");
$connectivityStats = fetchAll("
    SELECT connection_status, COUNT(*) as count,
        ROUND(AVG(rssi_dbm), 2) as avg_rssi,
        ROUND(AVG(snr_db), 2) as avg_snr,
        ROUND(AVG(packet_loss_percent), 2) as avg_packet_loss
    FROM connectivity_tests WHERE rssi_dbm IS NOT NULL GROUP BY connection_status
");
$connectivityByEnvironment = fetchAll("
    SELECT 
        COALESCE(NULLIF(environment_type, ''), 'unspecified') as environment_type,
        COUNT(*) as count,
        ROUND(AVG(rssi_dbm), 2) as avg_rssi,
        ROUND(AVG(packet_loss_percent), 2) as avg_packet_loss
    FROM connectivity_tests 
    WHERE rssi_dbm IS NOT NULL
    GROUP BY COALESCE(NULLIF(environment_type, ''), 'unspecified')
    ORDER BY count DESC
");

// Range Data
$rangeData = fetchAll("
    SELECT test_date, location_name, environment_type, test_point_code,
        distance_actual_meter, connection_status, rssi_dbm, snr_db
    FROM range_tests ORDER BY test_date DESC, created_at DESC LIMIT 50
");
$rangeByEnvironment = fetchAll("
    SELECT 
        COALESCE(NULLIF(environment_type, ''), 'unspecified') as environment_type,
        COUNT(*) as count,
        ROUND(MAX(distance_actual_meter), 2) as max_distance,
        ROUND(AVG(distance_actual_meter), 2) as avg_distance,
        ROUND(AVG(rssi_dbm), 2) as avg_rssi
    FROM range_tests 
    WHERE distance_actual_meter IS NOT NULL AND distance_actual_meter > 0
    GROUP BY COALESCE(NULLIF(environment_type, ''), 'unspecified')
    ORDER BY max_distance DESC
");

// Latency Data
$latencyData = fetchAll("
    SELECT test_date, location_name, node_id, network_mode,
        latency_ms, jitter_ms, packet_loss_percent
    FROM latency_tests ORDER BY test_date DESC, created_at DESC LIMIT 50
");
$latencyStats = fetchAll("
    SELECT network_mode, COUNT(*) as count,
        ROUND(AVG(latency_ms), 2) as avg_latency,
        ROUND(MIN(latency_ms), 2) as min_latency,
        ROUND(MAX(latency_ms), 2) as max_latency,
        ROUND(AVG(jitter_ms), 2) as avg_jitter
    FROM latency_tests WHERE latency_ms IS NOT NULL GROUP BY network_mode
");

// Throughput Data
$throughputData = fetchAll("
    SELECT test_date, location_name, node_id, environment_type,
        throughput_kbps, packet_delivery_ratio_percent, data_loss_percent
    FROM throughput_tests ORDER BY test_date DESC, created_at DESC LIMIT 50
");
$throughputStats = fetchAll("
    SELECT environment_type, COUNT(*) as count,
        ROUND(AVG(throughput_kbps), 2) as avg_throughput,
        ROUND(MAX(throughput_kbps), 2) as max_throughput,
        ROUND(AVG(packet_delivery_ratio_percent), 2) as avg_pdr
    FROM throughput_tests WHERE throughput_kbps IS NOT NULL GROUP BY environment_type ORDER BY avg_throughput DESC
");

// Interference Data
$interferenceData = fetchAll("
    SELECT test_date, location_name, interference_level, interference_source,
        distance_meter, rssi_dbm, snr_db, throughput_kbps, latency_ms, packet_loss_percent
    FROM interference_tests ORDER BY test_date DESC, created_at DESC LIMIT 50
");
$interferenceStats = fetchAll("
    SELECT interference_level, COUNT(*) as count,
        ROUND(AVG(rssi_dbm), 2) as avg_rssi,
        ROUND(AVG(throughput_kbps), 2) as avg_throughput,
        ROUND(AVG(latency_ms), 2) as avg_latency,
        ROUND(AVG(packet_loss_percent), 2) as avg_packet_loss
    FROM interference_tests WHERE rssi_dbm IS NOT NULL GROUP BY interference_level
");

// Power Data
$powerData = fetchAll("
    SELECT test_date, device_id, device_type,
        battery_voltage_v, current_a, power_w, estimated_runtime_hour, cpu_temperature_c
    FROM power_consumption_tests ORDER BY test_date DESC, created_at DESC LIMIT 50
");
$powerStats = fetchAll("
    SELECT device_type, COUNT(*) as count,
        ROUND(AVG(battery_voltage_v), 2) as avg_voltage,
        ROUND(AVG(power_w), 2) as avg_power,
        ROUND(AVG(estimated_runtime_hour), 2) as avg_runtime
    FROM power_consumption_tests WHERE power_w IS NOT NULL GROUP BY device_type
");

// Command Data
$commandData = fetchAll("
    SELECT test_date, command_type, source, target_node_id,
        execution_status, command_delivery_delay, total_command_time
    FROM command_execution_tests ORDER BY test_date DESC, created_at DESC LIMIT 50
");
$commandStats = fetchAll("
    SELECT command_type, COUNT(*) as total,
        SUM(CASE WHEN execution_status = 'success' THEN 1 ELSE 0 END) as success_count,
        ROUND(AVG(command_delivery_delay), 2) as avg_delay
    FROM command_execution_tests GROUP BY command_type
");

// Text Message Data
$textMessageData = fetchAll("
    SELECT test_date, source_node, target_node_id, message_text,
        delivery_status, latency_ms
    FROM text_message_logs ORDER BY sent_at DESC LIMIT 50
");
$textMessageStats = fetchAll("
    SELECT delivery_status, COUNT(*) as count,
        ROUND(AVG(latency_ms), 2) as avg_latency
    FROM text_message_logs WHERE latency_ms IS NOT NULL GROUP BY delivery_status
");

// Prepare chart data for LINE CHARTS (time-based)
$connectivityTimeLabels = [];
$connectivityRssiValues = [];
$connectivityLossValues = [];
foreach (array_reverse($connectivityData) as $row) {
    $connectivityTimeLabels[] = formatDate($row['test_date']);
    $connectivityRssiValues[] = (float) ($row['rssi_dbm'] ?? 0);
    $connectivityLossValues[] = (float) ($row['packet_loss_percent'] ?? 0);
}

$rangeTimeLabels = [];
$rangeDistanceValues = [];
$rangeRssiValues = [];
foreach (array_reverse($rangeData) as $row) {
    $rangeTimeLabels[] = formatDate($row['test_date']);
    $rangeDistanceValues[] = (float) ($row['distance_actual_meter'] ?? 0);
    $rangeRssiValues[] = (float) ($row['rssi_dbm'] ?? 0);
}

$latencyTimeLabels = [];
$latencyValues = [];
$jitterValues = [];
foreach (array_reverse($latencyData) as $row) {
    $latencyTimeLabels[] = formatDate($row['test_date']);
    $latencyValues[] = (float) ($row['latency_ms'] ?? 0);
    $jitterValues[] = (float) ($row['jitter_ms'] ?? 0);
}

$throughputTimeLabels = [];
$throughputValues = [];
$pdrValues = [];
foreach (array_reverse($throughputData) as $row) {
    $throughputTimeLabels[] = formatDate($row['test_date']);
    $throughputValues[] = (float) ($row['throughput_kbps'] ?? 0);
    $pdrValues[] = (float) ($row['packet_delivery_ratio_percent'] ?? 0);
}

$interferenceTimeLabels = [];
$interferenceRssiValues = [];
$interferenceThroughputValues = [];
foreach (array_reverse($interferenceData) as $row) {
    $interferenceTimeLabels[] = formatDate($row['test_date']);
    $interferenceRssiValues[] = (float) ($row['rssi_dbm'] ?? 0);
    $interferenceThroughputValues[] = (float) ($row['throughput_kbps'] ?? 0);
}

$powerTimeLabels = [];
$powerVoltageValues = [];
$powerCurrentValues = [];
foreach (array_reverse($powerData) as $row) {
    $powerTimeLabels[] = formatDate($row['test_date']);
    $powerVoltageValues[] = (float) ($row['battery_voltage_v'] ?? 0);
    $powerCurrentValues[] = (float) ($row['current_a'] ?? 0);
}

$commandTimeLabels = [];
$commandDelayValues = [];
foreach (array_reverse($commandData) as $row) {
    $commandTimeLabels[] = formatDate($row['test_date']);
    $commandDelayValues[] = (float) ($row['command_delivery_delay'] ?? 0);
}

$textMessageTimeLabels = [];
$textMessageLatencyValues = [];
foreach (array_reverse($textMessageData) as $row) {
    $textMessageTimeLabels[] = formatDate($row['test_date']);
    $textMessageLatencyValues[] = (float) ($row['latency_ms'] ?? 0);
}

$totalTests = array_sum($summaryStats);
?>

<style>
.report-header {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}
.report-header h2 { margin: 0 0 8px 0; font-weight: 600; }
.report-header p { margin: 0; opacity: 0.9; }
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 16px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
}
.stat-card .stat-number {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1e3c72;
}
.stat-card .stat-label { color: #6b7280; font-size: 0.85rem; }
.test-section {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
}
.test-section h4 { color: #1e3c72; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 2px solid #e5e7eb; }
.chart-wrapper { height: 280px; margin-bottom: 12px; }
.data-table { font-size: 0.85rem; }
.data-table th { background: #f8fafc; font-weight: 600; }
.nav-tabs .nav-link { color: #6b7280; font-weight: 500; border: none; padding: 10px 16px; }
.nav-tabs .nav-link.active { color: #1e3c72; background: white; border-bottom: 3px solid #1e3c72; }
.summary-badge { font-size: 0.75rem; padding: 3px 8px; border-radius: 12px; }
.badge-success-custom { background: #d1fae5; color: #065f46; }
.badge-warning-custom { background: #fef3c7; color: #92400e; }
.badge-danger-custom { background: #fee2e2; color: #991b1b; }
</style>

<div class="report-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2><i class="fas fa-chart-line"></i> Laporan Hasil Pengujian WiFi HaLow</h2>
            <p>Wi-Fi HaLow Tactical Monitoring and Communication Support System</p>
        </div>
        <div class="text-end">
            <div class="fs-4 fw-bold"><?php echo number_format($totalTests); ?> Total Pengujian</div>
            <small>Generated: <?php echo date('d M Y H:i'); ?></small>
        </div>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row g-2 mb-4">
    <div class="col-4 col-md-3 col-lg"><div class="stat-card"><div class="stat-number"><?php echo $summaryStats['connectivity']; ?></div><div class="stat-label">Connectivity</div></div></div>
    <div class="col-4 col-md-3 col-lg"><div class="stat-card"><div class="stat-number"><?php echo $summaryStats['range']; ?></div><div class="stat-label">Range</div></div></div>
    <div class="col-4 col-md-3 col-lg"><div class="stat-card"><div class="stat-number"><?php echo $summaryStats['latency']; ?></div><div class="stat-label">Latency</div></div></div>
    <div class="col-4 col-md-3 col-lg"><div class="stat-card"><div class="stat-number"><?php echo $summaryStats['throughput']; ?></div><div class="stat-label">Throughput</div></div></div>
    <div class="col-4 col-md-3 col-lg"><div class="stat-card"><div class="stat-number"><?php echo $summaryStats['interference']; ?></div><div class="stat-label">Interference</div></div></div>
    <div class="col-4 col-md-3 col-lg"><div class="stat-card"><div class="stat-number"><?php echo $summaryStats['power']; ?></div><div class="stat-label">Power</div></div></div>
    <div class="col-4 col-md-3 col-lg"><div class="stat-card"><div class="stat-number"><?php echo $summaryStats['command']; ?></div><div class="stat-label">Command</div></div></div>
    <div class="col-4 col-md-3 col-lg"><div class="stat-card"><div class="stat-number"><?php echo $summaryStats['text_message']; ?></div><div class="stat-label">Text Msg</div></div></div>
</div>

<!-- Navigation Tabs -->
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-connectivity" type="button"><i class="fas fa-wifi"></i> Connectivity</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-range" type="button"><i class="fas fa-ruler"></i> Range</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-latency" type="button"><i class="fas fa-clock"></i> Latency</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-throughput" type="button"><i class="fas fa-bolt"></i> Throughput</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-interference" type="button"><i class="fas fa-broadcast-tower"></i> Interference</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-power" type="button"><i class="fas fa-battery-half"></i> Power</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-command" type="button"><i class="fas fa-terminal"></i> Command</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-text" type="button"><i class="fas fa-comment"></i> Text Msg</button></li>
</ul>

<div class="tab-content">

    <!-- Connectivity Tab -->
    <div class="tab-pane fade show active" id="tab-connectivity">
        <div class="test-section">
            <h4><i class="fas fa-wifi"></i> Connectivity Test</h4>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <h6 class="text-muted">RSSI (dBm)</h6>
                    <div class="chart-wrapper"><canvas id="connectivityRssiChart"></canvas></div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">Packet Loss (%)</h6>
                    <div class="chart-wrapper"><canvas id="connectivityLossChart"></canvas></div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6 class="text-muted">By Environment</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered data-table">
                            <thead><tr><th>Environment</th><th>Tests</th><th>Avg RSSI</th><th>Avg Loss</th></tr></thead>
                            <tbody>
                                <?php foreach ($connectivityByEnvironment as $stat): ?>
                                <tr>
                                    <td><?php echo ucfirst($stat['environment_type'] ?? 'Unknown'); ?></td>
                                    <td><?php echo $stat['count']; ?></td>
                                    <td><?php echo $stat['avg_rssi']?$stat['avg_rssi'].' dBm':'-'; ?></td>
                                    <td><?php echo $stat['avg_packet_loss']!==null?$stat['avg_packet_loss'].'%':'-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">By Status</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered data-table">
                            <thead><tr><th>Status</th><th>Count</th><th>Avg RSSI</th></tr></thead>
                            <tbody>
                                <?php foreach ($connectivityStats as $stat): ?>
                                <tr>
                                    <td><?php $s=$stat['connection_status']??''; $c=$s==='connected'?'badge-success-custom':($s==='intermittent'?'badge-warning-custom':'badge-danger-custom'); ?><span class="summary-badge <?php echo $c; ?>"><?php echo ucfirst($s); ?></span></td>
                                    <td><?php echo $stat['count']; ?></td>
                                    <td><?php echo $stat['avg_rssi']?$stat['avg_rssi'].' dBm':'-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <h6 class="text-muted">Recent Tests</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover data-table">
                    <thead><tr><th>Date</th><th>Location</th><th>Environment</th><th>Node</th><th>Status</th><th>RSSI</th><th>Loss</th></tr></thead>
                    <tbody>
                        <?php foreach (array_slice($connectivityData, 0, 10) as $row): ?>
                        <tr>
                            <td><?php echo formatDate($row['test_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['location_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($row['environment_type'] ?? '-')); ?></td>
                            <td><?php echo htmlspecialchars($row['node_id'] ?? '-'); ?></td>
                            <td><?php $s=$row['connection_status']??''; $c=$s==='connected'?'badge-success-custom':($s==='intermittent'?'badge-warning-custom':'badge-danger-custom'); ?><span class="summary-badge <?php echo $c; ?>"><?php echo ucfirst($s); ?></span></td>
                            <td><?php echo $row['rssi_dbm']?$row['rssi_dbm'].' dBm':'-'; ?></td>
                            <td><?php echo $row['packet_loss_percent']!==null?$row['packet_loss_percent'].'%':'-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Range Tab -->
    <div class="tab-pane fade" id="tab-range">
        <div class="test-section">
            <h4><i class="fas fa-ruler"></i> Range Test</h4>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <h6 class="text-muted">Distance (m)</h6>
                    <div class="chart-wrapper"><canvas id="rangeDistanceChart"></canvas></div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">RSSI (dBm)</h6>
                    <div class="chart-wrapper"><canvas id="rangeRssiChart"></canvas></div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6 class="text-muted">By Environment</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered data-table">
                            <thead><tr><th>Environment</th><th>Tests</th><th>Max Distance</th><th>Avg RSSI</th></tr></thead>
                            <tbody>
                                <?php foreach ($rangeByEnvironment as $stat): ?>
                                <tr>
                                    <td><strong><?php echo ucfirst($stat['environment_type'] ?? 'Unknown'); ?></strong></td>
                                    <td><?php echo $stat['count']; ?></td>
                                    <td><?php echo number_format($stat['max_distance'], 0); ?> m</td>
                                    <td><?php echo $stat['avg_rssi']?$stat['avg_rssi'].' dBm':'-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">Summary</h6>
                    <div class="p-3 bg-light rounded">
                        <?php
                        $maxDistance = 0;
                        $maxLocation = '';
                        foreach ($rangeData as $r) {
                            if (($r['distance_actual_meter'] ?? 0) > $maxDistance) {
                                $maxDistance = $r['distance_actual_meter'];
                                $maxLocation = $r['location_name'];
                            }
                        }
                        ?>
                        <p class="mb-2"><strong>Max Distance:</strong> <?php echo number_format($maxDistance, 0); ?> m</p>
                        <p class="mb-0 text-muted"><?php echo htmlspecialchars($maxLocation); ?></p>
                    </div>
                </div>
            </div>
            <h6 class="text-muted">Recent Tests</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover data-table">
                    <thead><tr><th>Date</th><th>Location</th><th>Environment</th><th>Distance</th><th>Status</th><th>RSSI</th></tr></thead>
                    <tbody>
                        <?php foreach (array_slice($rangeData, 0, 10) as $row): ?>
                        <tr>
                            <td><?php echo formatDate($row['test_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['location_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($row['environment_type'] ?? '-')); ?></td>
                            <td><strong><?php echo $row['distance_actual_meter']?number_format($row['distance_actual_meter'],0).' m':'-'; ?></strong></td>
                            <td><?php $s=$row['connection_status']??''; $c=$s==='connected'?'badge-success-custom':($s==='intermittent'?'badge-warning-custom':'badge-danger-custom'); ?><span class="summary-badge <?php echo $c; ?>"><?php echo ucfirst($s); ?></span></td>
                            <td><?php echo $row['rssi_dbm']?$row['rssi_dbm'].' dBm':'-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Latency Tab -->
    <div class="tab-pane fade" id="tab-latency">
        <div class="test-section">
            <h4><i class="fas fa-clock"></i> Latency Test</h4>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <h6 class="text-muted">Latency (ms)</h6>
                    <div class="chart-wrapper"><canvas id="latencyChart"></canvas></div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">Jitter (ms)</h6>
                    <div class="chart-wrapper"><canvas id="jitterChart"></canvas></div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover data-table">
                    <thead><tr><th>Date</th><th>Location</th><th>Node</th><th>Latency</th><th>Jitter</th><th>Pkt Loss</th></tr></thead>
                    <tbody>
                        <?php foreach (array_slice($latencyData, 0, 8) as $row): ?>
                        <tr>
                            <td><?php echo formatDate($row['test_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['location_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['node_id'] ?? '-'); ?></td>
                            <td><?php echo $row['latency_ms']!==null?number_format($row['latency_ms'],0).' ms':'-'; ?></td>
                            <td><?php echo $row['jitter_ms']!==null?number_format($row['jitter_ms'],1).' ms':'-'; ?></td>
                            <td><?php echo $row['packet_loss_percent']!==null?$row['packet_loss_percent'].'%':'-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Throughput Tab -->
    <div class="tab-pane fade" id="tab-throughput">
        <div class="test-section">
            <h4><i class="fas fa-bolt"></i> Throughput Test</h4>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <h6 class="text-muted">Throughput (kbps)</h6>
                    <div class="chart-wrapper"><canvas id="throughputChart"></canvas></div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">PDR (%)</h6>
                    <div class="chart-wrapper"><canvas id="pdrChart"></canvas></div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover data-table">
                    <thead><tr><th>Date</th><th>Location</th><th>Node</th><th>Throughput</th><th>PDR</th><th>Loss</th></tr></thead>
                    <tbody>
                        <?php foreach (array_slice($throughputData, 0, 8) as $row): ?>
                        <tr>
                            <td><?php echo formatDate($row['test_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['location_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['node_id'] ?? '-'); ?></td>
                            <td><strong><?php echo $row['throughput_kbps']!==null?number_format($row['throughput_kbps'],0).' kbps':'-'; ?></strong></td>
                            <td><?php echo $row['packet_delivery_ratio_percent']!==null?$row['packet_delivery_ratio_percent'].'%':'-'; ?></td>
                            <td><?php echo $row['data_loss_percent']!==null?$row['data_loss_percent'].'%':'-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Interference Tab -->
    <div class="tab-pane fade" id="tab-interference">
        <div class="test-section">
            <h4><i class="fas fa-broadcast-tower"></i> Interference Test</h4>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <h6 class="text-muted">RSSI (dBm)</h6>
                    <div class="chart-wrapper"><canvas id="interferenceRssiChart"></canvas></div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">Throughput (kbps)</h6>
                    <div class="chart-wrapper"><canvas id="interferenceThroughputChart"></canvas></div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover data-table">
                    <thead><tr><th>Date</th><th>Level</th><th>Source</th><th>RSSI</th><th>Throughput</th><th>Pkt Loss</th></tr></thead>
                    <tbody>
                        <?php foreach (array_slice($interferenceData, 0, 8) as $row): ?>
                        <tr>
                            <td><?php echo formatDate($row['test_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['interference_level'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['interference_source'] ?? '-'); ?></td>
                            <td><?php echo $row['rssi_dbm']?$row['rssi_dbm'].' dBm':'-'; ?></td>
                            <td><?php echo $row['throughput_kbps']!==null?number_format($row['throughput_kbps'],0).' kbps':'-'; ?></td>
                            <td><?php echo $row['packet_loss_percent']!==null?$row['packet_loss_percent'].'%':'-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Power Tab -->
    <div class="tab-pane fade" id="tab-power">
        <div class="test-section">
            <h4><i class="fas fa-battery-half"></i> Power Consumption Test</h4>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <h6 class="text-muted">Voltage (V)</h6>
                    <div class="chart-wrapper"><canvas id="powerVoltageChart"></canvas></div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">Current (A)</h6>
                    <div class="chart-wrapper"><canvas id="powerCurrentChart"></canvas></div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover data-table">
                    <thead><tr><th>Date</th><th>Device</th><th>Type</th><th>Voltage</th><th>Current</th><th>Power</th><th>Runtime</th></tr></thead>
                    <tbody>
                        <?php foreach (array_slice($powerData, 0, 8) as $row): ?>
                        <tr>
                            <td><?php echo formatDate($row['test_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['device_id'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['device_type'] ?? '-'); ?></td>
                            <td><?php echo $row['battery_voltage_v']!==null?$row['battery_voltage_v'].' V':'-'; ?></td>
                            <td><?php echo $row['current_a']!==null?$row['current_a'].' A':'-'; ?></td>
                            <td><?php echo $row['power_w']!==null?$row['power_w'].' W':'-'; ?></td>
                            <td><?php echo $row['estimated_runtime_hour']!==null?$row['estimated_runtime_hour'].' h':'-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Command Tab -->
    <div class="tab-pane fade" id="tab-command">
        <div class="test-section">
            <h4><i class="fas fa-terminal"></i> Command Execution Test</h4>
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <h6 class="text-muted">Delivery Delay (ms)</h6>
                    <div class="chart-wrapper"><canvas id="commandDelayChart"></canvas></div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover data-table">
                    <thead><tr><th>Date</th><th>Command</th><th>Source</th><th>Target</th><th>Status</th><th>Delay</th><th>Total Time</th></tr></thead>
                    <tbody>
                        <?php foreach (array_slice($commandData, 0, 8) as $row): ?>
                        <tr>
                            <td><?php echo formatDate($row['test_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['command_type'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['source'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['target_node_id'] ?? '-'); ?></td>
                            <td><?php $s=$row['execution_status']??''; $c=$s==='success'?'badge-success-custom':'badge-danger-custom'; ?><span class="summary-badge <?php echo $c; ?>"><?php echo ucfirst($s); ?></span></td>
                            <td><?php echo $row['command_delivery_delay']!==null?number_format($row['command_delivery_delay'],0).' ms':'-'; ?></td>
                            <td><?php echo $row['total_command_time']!==null?number_format($row['total_command_time'],0).' ms':'-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Text Message Tab -->
    <div class="tab-pane fade" id="tab-text">
        <div class="test-section">
            <h4><i class="fas fa-comment"></i> Text Message Test</h4>
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <h6 class="text-muted">Latency (ms)</h6>
                    <div class="chart-wrapper"><canvas id="textMessageLatencyChart"></canvas></div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover data-table">
                    <thead><tr><th>Date</th><th>From</th><th>To</th><th>Message</th><th>Status</th><th>Latency</th></tr></thead>
                    <tbody>
                        <?php foreach (array_slice($textMessageData, 0, 8) as $row): ?>
                        <tr>
                            <td><?php echo formatDate($row['test_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['source_node'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['target_node_id'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars(substr($row['message_text'] ?? '-', 0, 25)); ?>...</td>
                            <td><?php $s=$row['delivery_status']??''; $c=$s==='success'?'badge-success-custom':'badge-danger-custom'; ?><span class="summary-badge <?php echo $c; ?>"><?php echo ucfirst($s); ?></span></td>
                            <td><?php echo $row['latency_ms']!==null?number_format($row['latency_ms'],0).' ms':'-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    if (!window.Chart) return;
    
    var lineOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: false, grid: { color: 'rgba(0,0,0,0.05)' } }
        },
        elements: {
            line: { tension: 0.3, borderWidth: 2 },
            point: { radius: 3, hoverRadius: 5 }
        }
    };

    function createLineChart(id, labels, data, color) {
        var ctx = document.getElementById(id);
        if (!ctx) return;
        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{ data: data, borderColor: color, backgroundColor: color + '20', fill: true }]
            },
            options: lineOptions
        });
    }

    // Connectivity Charts
    createLineChart('connectivityRssiChart', <?php echo json_encode($connectivityTimeLabels); ?>, <?php echo json_encode($connectivityRssiValues); ?>, '#1e3c72');
    createLineChart('connectivityLossChart', <?php echo json_encode($connectivityTimeLabels); ?>, <?php echo json_encode($connectivityLossValues); ?>, '#dc2626');

    // Range Charts
    createLineChart('rangeDistanceChart', <?php echo json_encode($rangeTimeLabels); ?>, <?php echo json_encode($rangeDistanceValues); ?>, '#16a34a');
    createLineChart('rangeRssiChart', <?php echo json_encode($rangeTimeLabels); ?>, <?php echo json_encode($rangeRssiValues); ?>, '#1e3c72');

    // Latency Charts
    createLineChart('latencyChart', <?php echo json_encode($latencyTimeLabels); ?>, <?php echo json_encode($latencyValues); ?>, '#d97706');
    createLineChart('jitterChart', <?php echo json_encode($latencyTimeLabels); ?>, <?php echo json_encode($jitterValues); ?>, '#7c3aed');

    // Throughput Charts
    createLineChart('throughputChart', <?php echo json_encode($throughputTimeLabels); ?>, <?php echo json_encode($throughputValues); ?>, '#2563eb');
    createLineChart('pdrChart', <?php echo json_encode($throughputTimeLabels); ?>, <?php echo json_encode($pdrValues); ?>, '#16a34a');

    // Interference Charts
    createLineChart('interferenceRssiChart', <?php echo json_encode($interferenceTimeLabels); ?>, <?php echo json_encode($interferenceRssiValues); ?>, '#1e3c72');
    createLineChart('interferenceThroughputChart', <?php echo json_encode($interferenceTimeLabels); ?>, <?php echo json_encode($interferenceThroughputValues); ?>, '#2563eb');

    // Power Charts
    createLineChart('powerVoltageChart', <?php echo json_encode($powerTimeLabels); ?>, <?php echo json_encode($powerVoltageValues); ?>, '#16a34a');
    createLineChart('powerCurrentChart', <?php echo json_encode($powerTimeLabels); ?>, <?php echo json_encode($powerCurrentValues); ?>, '#d97706');

    // Command Chart
    createLineChart('commandDelayChart', <?php echo json_encode($commandTimeLabels); ?>, <?php echo json_encode($commandDelayValues); ?>, '#dc2626');

    // Text Message Chart
    createLineChart('textMessageLatencyChart', <?php echo json_encode($textMessageTimeLabels); ?>, <?php echo json_encode($textMessageLatencyValues); ?>, '#1e3c72');
});
</script>
