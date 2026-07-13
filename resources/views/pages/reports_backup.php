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
    'penetration' => fetchAll("SELECT COUNT(*) as total FROM signal_penetration_tests")[0]['total'] ?? 0,
    'interference' => fetchAll("SELECT COUNT(*) as total FROM interference_tests")[0]['total'] ?? 0,
    'camera' => fetchAll("SELECT COUNT(*) as total FROM slave_camera_tests")[0]['total'] ?? 0,
    'power' => fetchAll("SELECT COUNT(*) as total FROM power_consumption_tests")[0]['total'] ?? 0,
    'command' => fetchAll("SELECT COUNT(*) as total FROM command_execution_tests")[0]['total'] ?? 0,
    'text_message' => fetchAll("SELECT COUNT(*) as total FROM text_message_logs")[0]['total'] ?? 0,
    'response' => fetchAll("SELECT COUNT(*) as total FROM response_time_tests")[0]['total'] ?? 0,
    'authentication' => fetchAll("SELECT COUNT(*) as total FROM authentication_tests")[0]['total'] ?? 0,
    'encryption' => fetchAll("SELECT COUNT(*) as total FROM encryption_tests")[0]['total'] ?? 0,
];

// Connectivity Data
$connectivityData = fetchAll("
    SELECT 
        test_date, location_name, environment_type, node_id, node_type,
        connection_status, rssi_dbm, snr_db, packet_loss_percent, packet_success_rate
    FROM connectivity_tests 
    ORDER BY test_date DESC, created_at DESC 
    LIMIT 50
");

$connectivityStats = fetchAll("
    SELECT 
        connection_status,
        COUNT(*) as count,
        ROUND(AVG(rssi_dbm), 2) as avg_rssi,
        ROUND(AVG(snr_db), 2) as avg_snr,
        ROUND(AVG(packet_loss_percent), 2) as avg_packet_loss
    FROM connectivity_tests 
    WHERE rssi_dbm IS NOT NULL
    GROUP BY connection_status
");

// Range Data
$rangeData = fetchAll("
    SELECT 
        test_date, location_name, environment_type, test_point_code,
        distance_actual_meter, distance_3d_meter, connection_status, rssi_dbm, snr_db
    FROM range_tests 
    ORDER BY test_date DESC, created_at DESC 
    LIMIT 50
");

$rangeByEnvironment = fetchAll("
    SELECT 
        environment_type,
        COUNT(*) as count,
        ROUND(MAX(distance_actual_meter), 2) as max_distance,
        ROUND(AVG(distance_actual_meter), 2) as avg_distance,
        ROUND(AVG(rssi_dbm), 2) as avg_rssi
    FROM range_tests 
    WHERE distance_actual_meter IS NOT NULL
    GROUP BY environment_type
    ORDER BY max_distance DESC
");

// Latency Data
$latencyData = fetchAll("
    SELECT 
        test_date, location_name, node_id, network_mode,
        latency_ms, jitter_ms, packet_loss_percent
    FROM latency_tests 
    ORDER BY test_date DESC, created_at DESC 
    LIMIT 50
");

$latencyStats = fetchAll("
    SELECT 
        network_mode,
        COUNT(*) as count,
        ROUND(AVG(latency_ms), 2) as avg_latency,
        ROUND(MIN(latency_ms), 2) as min_latency,
        ROUND(MAX(latency_ms), 2) as max_latency,
        ROUND(AVG(jitter_ms), 2) as avg_jitter,
        ROUND(AVG(packet_loss_percent), 2) as avg_packet_loss
    FROM latency_tests 
    WHERE latency_ms IS NOT NULL
    GROUP BY network_mode
");

// Throughput Data
$throughputData = fetchAll("
    SELECT 
        test_date, location_name, node_id, environment_type,
        throughput_kbps, packet_delivery_ratio_percent, data_loss_percent, snr_db
    FROM throughput_tests 
    ORDER BY test_date DESC, created_at DESC 
    LIMIT 50
");

$throughputStats = fetchAll("
    SELECT 
        environment_type,
        COUNT(*) as count,
        ROUND(AVG(throughput_kbps), 2) as avg_throughput,
        ROUND(MAX(throughput_kbps), 2) as max_throughput,
        ROUND(AVG(packet_delivery_ratio_percent), 2) as avg_pdr,
        ROUND(AVG(data_loss_percent), 2) as avg_loss
    FROM throughput_tests 
    WHERE throughput_kbps IS NOT NULL
    GROUP BY environment_type
    ORDER BY avg_throughput DESC
");

// Command Execution Data
$commandData = fetchAll("
    SELECT 
        test_date, command_type, source, target_node_id,
        execution_status, command_delivery_delay, total_command_time, command_success_rate
    FROM command_execution_tests 
    ORDER BY test_date DESC, created_at DESC 
    LIMIT 50
");

$commandStats = fetchAll("
    SELECT 
        command_type,
        COUNT(*) as total,
        SUM(CASE WHEN execution_status = 'success' THEN 1 ELSE 0 END) as success_count,
        ROUND(AVG(command_delivery_delay), 2) as avg_delivery_delay,
        ROUND(AVG(total_command_time), 2) as avg_total_time
    FROM command_execution_tests 
    GROUP BY command_type
");

// Text Message Data
$textMessageData = fetchAll("
    SELECT 
        test_date, source_node, target_node_id, message_text,
        delivery_status, latency_ms, response_status_code
    FROM text_message_logs 
    ORDER BY sent_at DESC 
    LIMIT 50
");

$textMessageStats = fetchAll("
    SELECT 
        delivery_status,
        COUNT(*) as count,
        ROUND(AVG(latency_ms), 2) as avg_latency,
        ROUND(MIN(latency_ms), 2) as min_latency,
        ROUND(MAX(latency_ms), 2) as max_latency
    FROM text_message_logs 
    WHERE latency_ms IS NOT NULL
    GROUP BY delivery_status
");

// Prepare chart data
// Interference Data
$interferenceData = fetchAll("
    SELECT 
        test_date, location_name, interference_level, interference_source,
        distance_meter, rssi_dbm, snr_db, throughput_kbps, latency_ms, packet_loss_percent
    FROM interference_tests 
    ORDER BY test_date DESC, created_at DESC 
    LIMIT 50
");

$interferenceStats = fetchAll("
    SELECT 
        interference_level,
        COUNT(*) as count,
        ROUND(AVG(rssi_dbm), 2) as avg_rssi,
        ROUND(AVG(snr_db), 2) as avg_snr,
        ROUND(AVG(throughput_kbps), 2) as avg_throughput,
        ROUND(AVG(latency_ms), 2) as avg_latency,
        ROUND(AVG(packet_loss_percent), 2) as avg_packet_loss
    FROM interference_tests 
    WHERE rssi_dbm IS NOT NULL
    GROUP BY interference_level
    ORDER BY FIELD(interference_level, 'normal', 'low', 'medium', 'high')
");

// Power Consumption Data
$powerData = fetchAll("
    SELECT 
        test_date, device_id, device_type,
        battery_voltage_v, current_a, test_duration_hour,
        power_w, energy_wh, estimated_runtime_hour, cpu_temperature_c
    FROM power_consumption_tests 
    ORDER BY test_date DESC, created_at DESC 
    LIMIT 50
");

$powerStats = fetchAll("
    SELECT 
        device_type,
        COUNT(*) as count,
        ROUND(AVG(battery_voltage_v), 2) as avg_voltage,
        ROUND(AVG(current_a), 2) as avg_current,
        ROUND(AVG(power_w), 2) as avg_power,
        ROUND(AVG(estimated_runtime_hour), 2) as avg_runtime,
        ROUND(AVG(cpu_temperature_c), 2) as avg_temp
    FROM power_consumption_tests 
    WHERE power_w IS NOT NULL
    GROUP BY device_type
");

// Prepare chart data with time-based labels
$connectivityTimeLabels = [];
$connectivityRssiValues = [];
$connectivityLossValues = [];
$connectivityChartData = array_reverse($connectivityData);
foreach ($connectivityChartData as $row) {
    $connectivityTimeLabels[] = formatDate($row['test_date']);
    $connectivityRssiValues[] = (float) ($row['rssi_dbm'] ?? 0);
    $connectivityLossValues[] = (float) ($row['packet_loss_percent'] ?? 0);
}

$rangeTimeLabels = [];
$rangeDistanceValues = [];
$rangeRssiValues = [];
$rangeChartData = array_reverse($rangeData);
foreach ($rangeChartData as $row) {
    $rangeTimeLabels[] = formatDate($row['test_date']);
    $rangeDistanceValues[] = (float) ($row['distance_actual_meter'] ?? 0);
    $rangeRssiValues[] = (float) ($row['rssi_dbm'] ?? 0);
}

$latencyTimeLabels = [];
$latencyValues = [];
$jitterValues = [];
$latencyChartData = array_reverse($latencyData);
foreach ($latencyChartData as $row) {
    $latencyTimeLabels[] = formatDate($row['test_date']);
    $latencyValues[] = (float) ($row['latency_ms'] ?? 0);
    $jitterValues[] = (float) ($row['jitter_ms'] ?? 0);
}

$throughputTimeLabels = [];
$throughputValues = [];
$pdrValues = [];
$throughputChartData = array_reverse($throughputData);
foreach ($throughputChartData as $row) {
    $throughputTimeLabels[] = formatDate($row['test_date']);
    $throughputValues[] = (float) ($row['throughput_kbps'] ?? 0);
    $pdrValues[] = (float) ($row['packet_delivery_ratio_percent'] ?? 0);
}

$commandTimeLabels = [];
$commandDelayValues = [];
$commandChartData = array_reverse($commandData);
foreach ($commandChartData as $row) {
    $commandTimeLabels[] = formatDate($row['test_date']);
    $commandDelayValues[] = (float) ($row['command_delivery_delay'] ?? 0);
}

$textMessageTimeLabels = [];
$textMessageLatencyValues = [];
$textMessageChartData = array_reverse($textMessageData);
foreach ($textMessageChartData as $row) {
    $textMessageTimeLabels[] = formatDate($row['test_date']);
    $textMessageLatencyValues[] = (float) ($row['latency_ms'] ?? 0);
}

$interferenceTimeLabels = [];
$interferenceRssiValues = [];
$interferenceThroughputValues = [];
$interferenceChartData = array_reverse($interferenceData);
foreach ($interferenceChartData as $row) {
    $interferenceTimeLabels[] = formatDate($row['test_date']);
    $interferenceRssiValues[] = (float) ($row['rssi_dbm'] ?? 0);
    $interferenceThroughputValues[] = (float) ($row['throughput_kbps'] ?? 0);
}

$powerTimeLabels = [];
$powerVoltageValues = [];
$powerCurrentValues = [];
$powerChartData = array_reverse($powerData);
foreach ($powerChartData as $row) {
    $powerTimeLabels[] = formatDate($row['test_date']);
    $powerVoltageValues[] = (float) ($row['battery_voltage_v'] ?? 0);
    $powerCurrentValues[] = (float) ($row['current_a'] ?? 0);
}

// Total tests
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

.report-header h2 {
    margin: 0 0 8px 0;
    font-weight: 600;
}

.report-header p {
    margin: 0;
    opacity: 0.9;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid #e5e7eb;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.stat-card .stat-icon {
    font-size: 2rem;
    margin-bottom: 12px;
}

.stat-card .stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #1e3c72;
    margin-bottom: 4px;
}

.stat-card .stat-label {
    color: #6b7280;
    font-size: 0.9rem;
}

.test-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
}

.test-section h4 {
    color: #1e3c72;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid #e5e7eb;
}

.chart-wrapper {
    position: relative;
    height: 300px;
    margin-bottom: 16px;
}

.data-table {
    font-size: 0.85rem;
}

.data-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #374151;
    white-space: nowrap;
}

.data-table td {
    vertical-align: middle;
}

.nav-tabs .nav-link {
    color: #6b7280;
    font-weight: 500;
    border: none;
    padding: 12px 20px;
}

.nav-tabs .nav-link.active {
    color: #1e3c72;
    background: white;
    border-bottom: 3px solid #1e3c72;
}

.nav-tabs .nav-link:hover:not(.active) {
    color: #1e3c72;
    background: #f1f5f9;
}

.summary-badge {
    font-size: 0.75rem;
    padding: 4px 8px;
    border-radius: 20px;
}

.badge-success-custom {
    background: #d1fae5;
    color: #065f46;
}

.badge-warning-custom {
    background: #fef3c7;
    color: #92400e;
}

.badge-danger-custom {
    background: #fee2e2;
    color: #991b1b;
}

.badge-info-custom {
    background: #dbeafe;
    color: #1e40af;
}

.print-btn {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

@media print {
    .print-btn, .nav-tabs, .no-print {
        display: none !important;
    }
    
    .test-section {
        break-inside: avoid;
        page-break-inside: avoid;
    }
    
    .chart-wrapper {
        height: 250px;
    }
}
</style>

<div class="report-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2><i class="fas fa-chart-bar"></i> Laporan Hasil Pengujian WiFi HaLow</h2>
            <p>Design and Implementation of a Wi-Fi HaLow-Based Tactical Monitoring and Communication Support System</p>
        </div>
        <div class="text-end">
            <div class="fs-4 fw-bold"><?php echo number_format($totalTests); ?> Total Pengujian</div>
            <small>Generated: <?php echo date('d M Y H:i'); ?></small>
        </div>
    </div>
</div>

<!-- Summary Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon text-primary"><i class="fas fa-wifi"></i></div>
            <div class="stat-number"><?php echo $summaryStats['connectivity']; ?></div>
            <div class="stat-label">Connectivity</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon text-success"><i class="fas fa-ruler"></i></div>
            <div class="stat-number"><?php echo $summaryStats['range']; ?></div>
            <div class="stat-label">Range</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon text-warning"><i class="fas fa-clock"></i></div>
            <div class="stat-number"><?php echo $summaryStats['latency']; ?></div>
            <div class="stat-label">Latency</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon text-info"><i class="fas fa-bolt"></i></div>
            <div class="stat-number"><?php echo $summaryStats['throughput']; ?></div>
            <div class="stat-label">Throughput</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon text-danger"><i class="fas fa-terminal"></i></div>
            <div class="stat-number"><?php echo $summaryStats['command']; ?></div>
            <div class="stat-label">Command</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon text-secondary"><i class="fas fa-comment"></i></div>
            <div class="stat-number"><?php echo $summaryStats['text_message']; ?></div>
            <div class="stat-label">Text Msg</div>
        </div>
    </div>
</div>

<!-- Navigation Tabs -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-connectivity" type="button">
            <i class="fas fa-wifi"></i> Connectivity
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-range" type="button">
            <i class="fas fa-ruler"></i> Range
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-latency" type="button">
            <i class="fas fa-clock"></i> Latency
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-throughput" type="button">
            <i class="fas fa-bolt"></i> Throughput
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-interference" type="button">
            <i class="fas fa-broadcast-tower"></i> Interference
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-command" type="button">
            <i class="fas fa-terminal"></i> Command
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-text" type="button">
            <i class="fas fa-comment"></i> Text Message
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-power" type="button">
            <i class="fas fa-battery-half"></i> Power
        </button>
    </li>
</ul>

<div class="tab-content">

    <!-- Connectivity Tab -->
    <div class="tab-pane fade show active" id="tab-connectivity">
        <div class="test-section">
            <h4><i class="fas fa-wifi"></i> Connectivity Test Results</h4>
            
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <h6 class="text-muted mb-3">RSSI Over Time (dBm)</h6>
                    <div class="chart-wrapper">
                        <canvas id="connectivityRssiChart"></canvas>
                    </div>
                </div>
                <div class="col-lg-6">
                    <h6 class="text-muted mb-3">Packet Loss Over Time (%)</h6>
                    <div class="chart-wrapper">
                        <canvas id="connectivityLossChart"></canvas>
                    </div>
                </div>
            </div>

            <h6 class="text-muted mb-3">Statistics by Status</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered data-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Avg RSSI</th>
                            <th>Avg SNR</th>
                            <th>Avg Loss</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($connectivityStats as $stat): ?>
                        <tr>
                            <td>
                                <?php 
                                $status = $stat['connection_status'] ?? 'unknown';
                                $badgeClass = $status === 'connected' ? 'badge-success-custom' : ($status === 'intermittent' ? 'badge-warning-custom' : 'badge-danger-custom');
                                ?>
                                <span class="summary-badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span>
                            </td>
                            <td><?php echo $stat['count']; ?></td>
                            <td><?php echo $stat['avg_rssi'] ? $stat['avg_rssi'] . ' dBm' : '-'; ?></td>
                            <td><?php echo $stat['avg_snr'] ? $stat['avg_snr'] . ' dB' : '-'; ?></td>
                            <td><?php echo $stat['avg_packet_loss'] !== null ? $stat['avg_packet_loss'] . '%' : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h6 class="text-muted mb-3 mt-4">Recent Connectivity Tests</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Environment</th>
                            <th>Node</th>
                            <th>Status</th>
                            <th>RSSI</th>
                            <th>SNR</th>
                            <th>Pkt Loss</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($connectivityData, 0, 10) as $row): ?>
                        <tr>
                            <td><?php echo formatDate($row['test_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['location_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['environment_type'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['node_id'] ?? '-'); ?></td>
                            <td>
                                <?php 
                                $status = $row['connection_status'] ?? 'unknown';
                                $badgeClass = $status === 'connected' ? 'badge-success-custom' : ($status === 'intermittent' ? 'badge-warning-custom' : 'badge-danger-custom');
                                ?>
                                <span class="summary-badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span>
                            </td>
                            <td><?php echo $row['rssi_dbm'] ? $row['rssi_dbm'] . ' dBm' : '-'; ?></td>
                            <td><?php echo $row['snr_db'] ? $row['snr_db'] . ' dB' : '-'; ?></td>
                            <td><?php echo $row['packet_loss_percent'] !== null ? $row['packet_loss_percent'] . '%' : '-'; ?></td>
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
            <h4><i class="fas fa-ruler"></i> Range Test Results</h4>
            
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <h6 class="text-muted mb-3">Max Distance by Environment (meters)</h6>
                    <div class="chart-wrapper">
                        <canvas id="rangeDistanceChart"></canvas>
                    </div>
                </div>
                <div class="col-lg-6">
                    <h6 class="text-muted mb-3">Average RSSI by Environment</h6>
                    <div class="chart-wrapper">
                        <canvas id="rangeRssiChart"></canvas>
                    </div>
                </div>
            </div>

            <h6 class="text-muted mb-3">Range Statistics by Environment</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered data-table">
                    <thead>
                        <tr>
                            <th>Environment</th>
                            <th>Tests</th>
                            <th>Max Distance</th>
                            <th>Avg Distance</th>
                            <th>Avg RSSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rangeByEnvironment as $stat): ?>
                        <tr>
                            <td><?php echo ucfirst($stat['environment_type'] ?? 'Unknown'); ?></td>
                            <td><?php echo $stat['count']; ?></td>
                            <td><strong><?php echo number_format($stat['max_distance'], 2); ?> m</strong></td>
                            <td><?php echo number_format($stat['avg_distance'], 2); ?> m</td>
                            <td><?php echo $stat['avg_rssi'] ? $stat['avg_rssi'] . ' dBm' : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h6 class="text-muted mb-3 mt-4">Recent Range Tests</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Environment</th>
                            <th>Node</th>
                            <th>Distance</th>
                            <th>Status</th>
                            <th>RSSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($rangeData, 0, 10) as $row): ?>
                        <tr>
                            <td><?php echo formatDate($row['test_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['location_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['environment_type'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['test_point_code'] ?? '-'); ?></td>
                            <td><strong><?php echo $row['distance_actual_meter'] ? number_format($row['distance_actual_meter'], 2) . ' m' : '-'; ?></strong></td>
                            <td>
                                <?php 
                                $status = $row['connection_status'] ?? 'unknown';
                                $badgeClass = $status === 'connected' ? 'badge-success-custom' : ($status === 'intermittent' ? 'badge-warning-custom' : 'badge-danger-custom');
                                ?>
                                <span class="summary-badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span>
                            </td>
                            <td><?php echo $row['rssi_dbm'] ? $row['rssi_dbm'] . ' dBm' : '-'; ?></td>
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
            <h4><i class="fas fa-clock"></i> Latency Test Results</h4>
            
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <h6 class="text-muted mb-3">Average Latency by Network Mode</h6>
                    <div class="chart-wrapper">
                        <canvas id="latencyModeChart"></canvas>
                    </div>
                </div>
                <div class="col-lg-6">
                    <h6 class="text-muted mb-3">Average Jitter by Network Mode</h6>
                    <div class="chart-wrapper">
                        <canvas id="jitterModeChart"></canvas>
                    </div>
                </div>
            </div>

            <h6 class="text-muted mb-3">Latency Statistics by Network Mode</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered data-table">
                    <thead>
                        <tr>
                            <th>Network Mode</th>
                            <th>Tests</th>
                            <th>Avg Latency</th>
                            <th>Min Latency</th>
                            <th>Max Latency</th>
                            <th>Avg Jitter</th>
                            <th>Avg Pkt Loss</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latencyStats as $stat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['network_mode'] ?? 'Unknown'); ?></td>
                            <td><?php echo $stat['count']; ?></td>
                            <td><?php echo $stat['avg_latency'] ? number_format($stat['avg_latency'], 2) . ' ms' : '-'; ?></td>
                            <td><?php echo $stat['min_latency'] ? number_format($stat['min_latency'], 2) . ' ms' : '-'; ?></td>
                            <td><?php echo $stat['max_latency'] ? number_format($stat['max_latency'], 2) . ' ms' : '-'; ?></td>
                            <td><?php echo $stat['avg_jitter'] ? number_format($stat['avg_jitter'], 2) . ' ms' : '-'; ?></td>
                            <td><?php echo $stat['avg_packet_loss'] !== null ? $stat['avg_packet_loss'] . '%' : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h6 class="text-muted mb-3 mt-4">Recent Latency Tests</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Node</th>
                            <th>Mode</th>
                            <th>Latency</th>
                            <th>Jitter</th>
                            <th>Pkt Loss</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($latencyData, 0, 10) as $row): ?>
                        <tr>
                            <td><?php echo formatDate($row['test_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['location_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['node_id'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['network_mode'] ?? '-'); ?></td>
                            <td><?php echo $row['latency_ms'] !== null ? number_format($row['latency_ms'], 2) . ' ms' : '-'; ?></td>
                            <td><?php echo $row['jitter_ms'] !== null ? number_format($row['jitter_ms'], 2) . ' ms' : '-'; ?></td>
                            <td><?php echo $row['packet_loss_percent'] !== null ? $row['packet_loss_percent'] . '%' : '-'; ?></td>
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
            <h4><i class="fas fa-bolt"></i> Throughput Test Results</h4>
            
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <h6 class="text-muted mb-3">Average Throughput by Environment (kbps)</h6>
                    <div class="chart-wrapper">
                        <canvas id="throughputEnvChart"></canvas>
                    </div>
                </div>
                <div class="col-lg-6">
                    <h6 class="text-muted mb-3">Packet Delivery Ratio by Environment (%)</h6>
                    <div class="chart-wrapper">
                        <canvas id="throughputPdrChart"></canvas>
                    </div>
                </div>
            </div>

            <h6 class="text-muted mb-3">Throughput Statistics by Environment</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered data-table">
                    <thead>
                        <tr>
                            <th>Environment</th>
                            <th>Tests</th>
                            <th>Avg Throughput</th>
                            <th>Max Throughput</th>
                            <th>Avg PDR</th>
                            <th>Avg Data Loss</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($throughputStats as $stat): ?>
                        <tr>
                            <td><?php echo ucfirst($stat['environment_type'] ?? 'Unknown'); ?></td>
                            <td><?php echo $stat['count']; ?></td>
                            <td><strong><?php echo $stat['avg_throughput'] ? number_format($stat['avg_throughput'], 2) . ' kbps' : '-'; ?></strong></td>
                            <td><?php echo $stat['max_throughput'] ? number_format($stat['max_throughput'], 2) . ' kbps' : '-'; ?></td>
                            <td><?php echo $stat['avg_pdr'] !== null ? $stat['avg_pdr'] . '%' : '-'; ?></td>
                            <td><?php echo $stat['avg_loss'] !== null ? $stat['avg_loss'] . '%' : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h6 class="text-muted mb-3 mt-4">Recent Throughput Tests</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Node</th>
                            <th>Environment</th>
                            <th>Throughput</th>
                            <th>PDR</th>
                            <th>Data Loss</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($throughputData, 0, 10) as $row): ?>
                        <tr>
                            <td><?php echo formatDate($row['test_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['location_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['node_id'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['environment_type'] ?? '-'); ?></td>
                            <td><strong><?php echo $row['throughput_kbps'] !== null ? number_format($row['throughput_kbps'], 2) . ' kbps' : '-'; ?></strong></td>
                            <td><?php echo $row['packet_delivery_ratio_percent'] !== null ? $row['packet_delivery_ratio_percent'] . '%' : '-'; ?></td>
                            <td><?php echo $row['data_loss_percent'] !== null ? $row['data_loss_percent'] . '%' : '-'; ?></td>
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
            <h4><i class="fas fa-terminal"></i> Command Execution Results</h4>
            
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <h6 class="text-muted mb-3">Command Success Rate by Type</h6>
                    <div class="chart-wrapper">
                        <canvas id="commandTypeChart"></canvas>
                    </div>
                </div>
                <div class="col-lg-6">
                    <h6 class="text-muted mb-3">Command Statistics</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered data-table">
                            <thead>
                                <tr>
                                    <th>Command</th>
                                    <th>Total</th>
                                    <th>Success</th>
                                    <th>Avg Delay</th>
                                    <th>Avg Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($commandStats as $stat): ?>
                                <tr>
                                    <td><?php echo ucfirst($stat['command_type'] ?? 'Unknown'); ?></td>
                                    <td><?php echo $stat['total']; ?></td>
                                    <td>
                                        <?php 
                                        $successRate = $stat['total'] > 0 ? ($stat['success_count'] / $stat['total']) * 100 : 0;
                                        $badgeClass = $successRate >= 80 ? 'badge-success-custom' : ($successRate >= 50 ? 'badge-warning-custom' : 'badge-danger-custom');
                                        ?>
                                        <span class="summary-badge <?php echo $badgeClass; ?>"><?php echo $stat['success_count']; ?> (<?php echo number_format($successRate, 1); ?>%)</span>
                                    </td>
                                    <td><?php echo $stat['avg_delivery_delay'] ? number_format($stat['avg_delivery_delay'], 2) . ' ms' : '-'; ?></td>
                                    <td><?php echo $stat['avg_total_time'] ? number_format($stat['avg_total_time'], 2) . ' ms' : '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <h6 class="text-muted mb-3">Recent Command Executions</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Command</th>
                            <th>Source</th>
                            <th>Target</th>
                            <th>Status</th>
                            <th>Delay</th>
                            <th>Total Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($commandData, 0, 10) as $row): ?>
                        <tr>
                            <td><?php echo formatDate($row['test_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['command_type'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['source'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['target_node_id'] ?? '-'); ?></td>
                            <td>
                                <?php 
                                $status = $row['execution_status'] ?? 'unknown';
                                $badgeClass = $status === 'success' ? 'badge-success-custom' : 'badge-danger-custom';
                                ?>
                                <span class="summary-badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span>
                            </td>
                            <td><?php echo $row['command_delivery_delay'] ? number_format($row['command_delivery_delay'], 2) . ' ms' : '-'; ?></td>
                            <td><?php echo $row['total_command_time'] ? number_format($row['total_command_time'], 2) . ' ms' : '-'; ?></td>
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
            <h4><i class="fas fa-comment"></i> Text Message Communication Results</h4>
            
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <h6 class="text-muted mb-3">Message Delivery Status</h6>
                    <div class="chart-wrapper">
                        <canvas id="textMessageStatusChart"></canvas>
                    </div>
                </div>
                <div class="col-lg-6">
                    <h6 class="text-muted mb-3">Average Latency by Status</h6>
                    <div class="chart-wrapper">
                        <canvas id="textMessageLatencyChart"></canvas>
                    </div>
                </div>
            </div>

            <h6 class="text-muted mb-3">Text Message Statistics</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered data-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Avg Latency</th>
                            <th>Min Latency</th>
                            <th>Max Latency</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($textMessageStats as $stat): ?>
                        <tr>
                            <td>
                                <?php 
                                $status = $stat['delivery_status'] ?? 'unknown';
                                $badgeClass = $status === 'success' ? 'badge-success-custom' : 'badge-danger-custom';
                                ?>
                                <span class="summary-badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span>
                            </td>
                            <td><?php echo $stat['count']; ?></td>
                            <td><?php echo $stat['avg_latency'] ? number_format($stat['avg_latency'], 2) . ' ms' : '-'; ?></td>
                            <td><?php echo $stat['min_latency'] ? number_format($stat['min_latency'], 2) . ' ms' : '-'; ?></td>
                            <td><?php echo $stat['max_latency'] ? number_format($stat['max_latency'], 2) . ' ms' : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h6 class="text-muted mb-3 mt-4">Recent Text Messages</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Latency</th>
                            <th>HTTP Code</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($textMessageData, 0, 10) as $row): ?>
                        <tr>
                            <td><?php echo formatDate($row['test_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['source_node'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['target_node_id'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars(substr($row['message_text'] ?? '-', 0, 30)) . (strlen($row['message_text'] ?? '') > 30 ? '...' : ''); ?></td>
                            <td>
                                <?php 
                                $status = $row['delivery_status'] ?? 'unknown';
                                $badgeClass = $status === 'success' ? 'badge-success-custom' : 'badge-danger-custom';
                                ?>
                                <span class="summary-badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span>
                            </td>
                            <td><?php echo $row['latency_ms'] ? number_format($row['latency_ms'], 2) . ' ms' : '-'; ?></td>
                            <td><?php echo $row['response_status_code'] ?: '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Print Button -->
<button onclick="window.print()" class="btn btn-primary print-btn">
    <i class="fas fa-print"></i> Print Report
</button>

<script>
$(function() {
    if (!window.Chart) {
        console.warn('Chart.js not loaded');
        return;
    }

    var chartColors = {
        primary: '#1e3c72',
        success: '#16a34a',
        warning: '#d97706',
        danger: '#dc2626',
        info: '#2563eb',
        secondary: '#64748b'
    };

    // Connectivity Status Chart
    new Chart(document.getElementById('connectivityStatusChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($connectivityStatusLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($connectivityStatusValues); ?>,
                backgroundColor: [chartColors.success, chartColors.warning, chartColors.danger]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Range Distance Chart
    new Chart(document.getElementById('rangeDistanceChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($rangeEnvironmentLabels); ?>,
            datasets: [{
                label: 'Max Distance (m)',
                data: <?php echo json_encode($rangeMaxDistanceValues); ?>,
                backgroundColor: chartColors.primary
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Range RSSI Chart
    new Chart(document.getElementById('rangeRssiChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($rangeEnvironmentLabels); ?>,
            datasets: [{
                label: 'Avg RSSI (dBm)',
                data: <?php echo json_encode($rangeAvgRssiValues); ?>,
                backgroundColor: chartColors.info
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });

    // Latency Mode Chart
    new Chart(document.getElementById('latencyModeChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($latencyModeLabels); ?>,
            datasets: [{
                label: 'Avg Latency (ms)',
                data: <?php echo json_encode($latencyAvgValues); ?>,
                backgroundColor: chartColors.warning
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Jitter Mode Chart
    new Chart(document.getElementById('jitterModeChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($latencyModeLabels); ?>,
            datasets: [{
                label: 'Avg Jitter (ms)',
                data: <?php echo json_encode($latencyJitterValues); ?>,
                backgroundColor: chartColors.danger
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Throughput Environment Chart
    new Chart(document.getElementById('throughputEnvChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($throughputEnvLabels); ?>,
            datasets: [{
                label: 'Avg Throughput (kbps)',
                data: <?php echo json_encode($throughputAvgValues); ?>,
                backgroundColor: chartColors.info
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Throughput PDR Chart
    new Chart(document.getElementById('throughputPdrChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($throughputEnvLabels); ?>,
            datasets: [{
                label: 'PDR (%)',
                data: <?php echo json_encode($throughputPdrValues); ?>,
                backgroundColor: chartColors.success
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, max: 100 } }
        }
    });

    // Command Type Chart
    new Chart(document.getElementById('commandTypeChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($commandTypeLabels); ?>,
            datasets: [
                {
                    label: 'Success',
                    data: <?php echo json_encode($commandSuccessValues); ?>,
                    backgroundColor: chartColors.success
                },
                {
                    label: 'Total',
                    data: <?php echo json_encode($commandTotalValues); ?>,
                    backgroundColor: chartColors.secondary
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Text Message Status Chart
    new Chart(document.getElementById('textMessageStatusChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($textMessageStatusLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($textMessageCountValues); ?>,
                backgroundColor: [chartColors.success, chartColors.danger]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Text Message Latency Chart
    new Chart(document.getElementById('textMessageLatencyChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($textMessageStatusLabels); ?>,
            datasets: [{
                label: 'Avg Latency (ms)',
                data: <?php echo json_encode($textMessageLatencyValues); ?>,
                backgroundColor: chartColors.primary
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
});
</script>
