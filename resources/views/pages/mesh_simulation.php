<?php
if (!function_exists('meshSimMetric')) {
    function meshSimMetric($sql, $fallback = null) {
        try {
            $row = fetchOne($sql);
            $value = $row['value'] ?? null;

            if ($value === null || $value === '') {
                return $fallback;
            }

            return is_numeric($value) ? (float) $value : $fallback;
        } catch (Throwable $e) {
            return $fallback;
        }
    }
}

if (!function_exists('meshSimClamp')) {
    function meshSimClamp($value, $min, $max) {
        return max($min, min($max, $value));
    }
}

if (!function_exists('meshSimInputInt')) {
    function meshSimInputInt($key, $default, $min, $max) {
        $value = isset($_GET[$key]) ? filter_var($_GET[$key], FILTER_VALIDATE_INT) : $default;
        if ($value === false || $value === null) {
            $value = $default;
        }

        return (int) meshSimClamp((int) $value, $min, $max);
    }
}

if (!function_exists('meshSimInputFloat')) {
    function meshSimInputFloat($key, $default, $min, $max) {
        $value = isset($_GET[$key]) ? filter_var($_GET[$key], FILTER_VALIDATE_FLOAT) : $default;
        if ($value === false || $value === null) {
            $value = $default;
        }

        return meshSimClamp((float) $value, $min, $max);
    }
}

if (!function_exists('meshSimInputChoice')) {
    function meshSimInputChoice($key, $default, $options) {
        $value = $_GET[$key] ?? $default;
        return array_key_exists($value, $options) ? $value : $default;
    }
}

if (!function_exists('meshSimFormat')) {
    function meshSimFormat($value, $decimals = 2, $suffix = '') {
        if (function_exists('formatNullableNumber')) {
            return formatNullableNumber($value, $decimals, $suffix);
        }

        if ($value === null || $value === '') {
            return 'N/A';
        }

        return number_format((float) $value, $decimals) . $suffix;
    }
}

$rangeAverage = meshSimMetric("SELECT AVG(distance_actual_meter) AS value FROM range_tests WHERE distance_actual_meter IS NOT NULL AND distance_actual_meter > 0");
$rangeMaximum = meshSimMetric("SELECT MAX(distance_actual_meter) AS value FROM range_tests WHERE distance_actual_meter IS NOT NULL AND distance_actual_meter > 0");
$rangeStable = meshSimMetric("SELECT MAX(distance_actual_meter) AS value FROM range_tests WHERE distance_actual_meter IS NOT NULL AND distance_actual_meter > 0 AND LOWER(COALESCE(status_result, '')) IN ('excellent', 'good', 'stable', 'success', 'passed')");
$successRate = meshSimMetric("SELECT MAX(packet_success_rate) AS value FROM connectivity_tests WHERE packet_success_rate IS NOT NULL AND packet_success_rate > 0");
$successAverage = meshSimMetric("SELECT AVG(packet_success_rate) AS value FROM connectivity_tests WHERE packet_success_rate IS NOT NULL AND packet_success_rate > 0");
$packetLoss = meshSimMetric("SELECT AVG(packet_loss_percent) AS value FROM connectivity_tests WHERE packet_loss_percent IS NOT NULL");
$baseRssi = meshSimMetric("SELECT AVG(rssi_dbm) AS value FROM connectivity_tests WHERE rssi_dbm IS NOT NULL", -78);
$baseSnr = meshSimMetric("SELECT AVG(snr_db) AS value FROM connectivity_tests WHERE snr_db IS NOT NULL", 10);
$latencyReference = meshSimMetric("SELECT MIN(latency_ms) AS value FROM latency_tests WHERE latency_ms IS NOT NULL AND latency_ms > 0");
$throughputReference = meshSimMetric("SELECT MAX(throughput_kbps) AS value FROM throughput_tests WHERE throughput_kbps IS NOT NULL AND throughput_kbps > 0");
$baseLatency = $latencyReference ?? meshSimMetric("SELECT AVG(latency_ms) AS value FROM latency_tests WHERE latency_ms IS NOT NULL", 120);
$baseThroughput = $throughputReference ?? meshSimMetric("SELECT AVG(throughput_kbps) AS value FROM throughput_tests WHERE throughput_kbps IS NOT NULL", 250);
$basePower = meshSimMetric("SELECT AVG(power_w) AS value FROM power_consumption_tests WHERE power_w IS NOT NULL", 1.8);
$referenceRecords = meshSimMetric("SELECT
    (SELECT COUNT(*) FROM range_tests) +
    (SELECT COUNT(*) FROM connectivity_tests) +
    (SELECT COUNT(*) FROM latency_tests) +
    (SELECT COUNT(*) FROM throughput_tests) +
    (SELECT COUNT(*) FROM power_consumption_tests) AS value", 0);

$referenceRange = $rangeStable ?: $rangeMaximum ?: $rangeAverage ?: 150;
$referenceRange = max(30, (float) $referenceRange);
$baseSuccess = $successRate ?? $successAverage;
if ($baseSuccess === null && $packetLoss !== null) {
    $baseSuccess = 100 - $packetLoss;
}
$baseSuccess = meshSimClamp($baseSuccess ?? 82, 1, 97.5);
$baseThroughput = max(1, (float) $baseThroughput);
$baseLatency = max(1, (float) $baseLatency);
$basePower = max(0, (float) $basePower);

$environmentOptions = [
    'outdoor' => ['label' => 'Outdoor terbuka', 'range' => 1.00, 'noise' => 0, 'loss' => 0],
    'field' => ['label' => 'Lapangan luas', 'range' => 1.15, 'noise' => -1, 'loss' => -2],
    'coastal' => ['label' => 'Pantai / LOS kuat', 'range' => 1.08, 'noise' => 0, 'loss' => -1],
    'hill' => ['label' => 'Kontur bukit', 'range' => 0.78, 'noise' => 4, 'loss' => 7],
    'warehouse' => ['label' => 'Hangar / gudang', 'range' => 0.72, 'noise' => 5, 'loss' => 10],
    'indoor' => ['label' => 'Indoor padat', 'range' => 0.45, 'noise' => 8, 'loss' => 18],
];

$trafficProfiles = [
    'telemetry' => ['label' => 'Telemetry', 'demand' => 32, 'load' => 0.75],
    'monitoring' => ['label' => 'Monitoring sensor', 'demand' => 128, 'load' => 1.00],
    'camera' => ['label' => 'Camera preview', 'demand' => 512, 'load' => 1.35],
];

$defaultAreaRadius = meshSimClamp(round($referenceRange * 1.5), 50, 5000);
$defaultTargetDistance = meshSimClamp(round($referenceRange * 1.8), 50, 5000);

$masterCount = meshSimInputInt('master_count', 1, 1, 12);
$slaveCount = meshSimInputInt('slave_count', 6, 1, 60);
$areaRadius = meshSimInputFloat('area_radius_meter', $defaultAreaRadius, 25, 5000);
$targetDistance = meshSimInputFloat('target_distance_meter', $defaultTargetDistance, 25, 5000);
$maxHop = meshSimInputInt('max_hop', 3, 1, 8);
$environmentKey = meshSimInputChoice('environment', 'outdoor', $environmentOptions);
$trafficKey = meshSimInputChoice('traffic', 'monitoring', $trafficProfiles);
$environment = $environmentOptions[$environmentKey];
$traffic = $trafficProfiles[$trafficKey];

$usableRange = max(25, $referenceRange * $environment['range']);
$coverageTarget = max($targetDistance, $areaRadius);
$requiredHops = max(1, (int) ceil($coverageTarget / $usableRange));
$effectiveHops = max(1, min($requiredHops, $maxHop));
$meshReach = $usableRange * $maxHop;
$p2pReach = $usableRange;
$distanceStress = max(0, ($coverageTarget / $usableRange) - 1);
$p2pConnectivity = meshSimClamp($baseSuccess - ($distanceStress * 30) - $environment['loss'], 0, 99.5);

$nodeDensity = max(0, ($masterCount + $slaveCount) - 2);
$densityBonus = min(20, ($nodeDensity * 2.2) + (max(0, $masterCount - 1) * 4));
$coveragePenalty = $coverageTarget > $meshReach ? min(60, (($coverageTarget - $meshReach) / max(1, $meshReach)) * 80) : 0;
$trafficPressure = (($slaveCount * $traffic['demand']) / max(1, $baseThroughput));
$trafficPenalty = min(8, max(0, ($trafficPressure - 1) * 1.2));
$pathQuality = $baseSuccess - (($effectiveHops - 1) * 7.5) - $coveragePenalty - ($environment['loss'] * 0.45);
$meshConnectivity = meshSimClamp($pathQuality + $densityBonus - $trafficPenalty, 0, 99.5);

$throughputPerSlave = $baseThroughput / (1 + (0.38 * ($effectiveHops - 1)) + (0.055 * max(0, $slaveCount - 1)) + (0.10 * max(0, $masterCount - 1)));
$throughputPerSlave = max(1, $throughputPerSlave / $traffic['load']);
$aggregateThroughput = $throughputPerSlave * $slaveCount;
$estimatedLatency = ($baseLatency * $effectiveHops * (1 + (0.025 * max(0, $slaveCount - 1)))) + max(0, $environment['noise'] * 3);
$distanceRatio = max(1, $coverageTarget / max(1, $referenceRange));
$estimatedRssi = meshSimClamp($baseRssi - (10 * log10($distanceRatio)) - (($effectiveHops - 1) * 2.5) - ($environment['noise'] * 0.8), -115, -35);
$estimatedSnr = meshSimClamp($baseSnr - (($effectiveHops - 1) * 2.2) - $environment['noise'] + min(6, $densityBonus * 0.15), -10, 40);
$estimatedPacketLoss = meshSimClamp(100 - $meshConnectivity, 0, 100);
$totalPower = $basePower * ($masterCount + $slaveCount);
$coverageGain = $p2pReach > 0 ? $meshReach / $p2pReach : 1;

$extraCoverageNodes = $targetDistance > $meshReach ? (int) ceil(($targetDistance - $meshReach) / max(1, $usableRange)) : 0;
$extraQualityNodes = $meshConnectivity < 90 ? (int) ceil((90 - $meshConnectivity) / 6) : 0;
$recommendedExtraNodes = max(0, $extraCoverageNodes, $extraQualityNodes);

if ($slaveCount <= 4 && $targetDistance <= ($usableRange * 2)) {
    $packageName = 'Starter Mesh';
} elseif ($slaveCount <= 12) {
    $packageName = 'Field Mesh';
} else {
    $packageName = 'Area Mesh Pro';
}

$recommendationText = $recommendedExtraNodes > 0
    ? 'Tambah ' . $recommendedExtraNodes . ' slave/STA tambahan untuk target koneksi >= 90%.'
    : 'Konfigurasi ini siap dijadikan paket demo/penawaran.';

$baselineMissing = [];
if ($rangeAverage === null && $rangeMaximum === null) {
    $baselineMissing[] = 'range';
}
if ($successRate === null && $successAverage === null && $packetLoss === null) {
    $baselineMissing[] = 'connectivity';
}
if ($latencyReference === null) {
    $baselineMissing[] = 'latency';
}
if ($throughputReference === null) {
    $baselineMissing[] = 'throughput';
}

$coverageScoreP2p = meshSimClamp(($p2pReach / max(1, $targetDistance)) * 100, 0, 100);
$coverageScoreMesh = meshSimClamp(($meshReach / max(1, $targetDistance)) * 100, 0, 100);
$capacityScoreP2p = meshSimClamp(($baseThroughput / max(1, $traffic['demand'] * $slaveCount)) * 100, 0, 100);
$capacityScoreMesh = meshSimClamp(($throughputPerSlave / max(1, $traffic['demand'])) * 100, 0, 100);
$latencyScoreMesh = meshSimClamp(100 - ((($estimatedLatency - $baseLatency) / max(1, $baseLatency)) * 35), 0, 100);

$scoreChart = [
    'labels' => ['Coverage match', 'Connectivity', 'Capacity match', 'Latency score'],
    'p2p' => [
        round($coverageScoreP2p, 2),
        round($p2pConnectivity, 2),
        round($capacityScoreP2p, 2),
        100,
    ],
    'mesh' => [
        round($coverageScoreMesh, 2),
        round($meshConnectivity, 2),
        round($capacityScoreMesh, 2),
        round($latencyScoreMesh, 2),
    ],
];

$topologyData = [
    'masterCount' => $masterCount,
    'slaveCount' => $slaveCount,
    'hopCount' => $effectiveHops,
    'connectivity' => round($meshConnectivity, 1),
    'reach' => round($meshReach, 1),
    'usableRange' => round($usableRange, 1),
    'targetDistance' => round($targetDistance, 1),
    'areaRadius' => round($areaRadius, 1),
    'environmentLabel' => $environment['label'],
];

$salesRows = [
    ['label' => 'Paket', 'value' => $packageName],
    ['label' => 'Perangkat', 'value' => $masterCount . ' master + ' . $slaveCount . ' slave'],
    ['label' => 'Estimasi coverage', 'value' => meshSimFormat($meshReach, 0, ' m')],
    ['label' => 'Estimasi koneksi', 'value' => meshSimFormat($meshConnectivity, 2, '%')],
    ['label' => 'Kebutuhan traffic/slave', 'value' => meshSimFormat($traffic['demand'], 0, ' kbps')],
];
?>

<style>
.mesh-sim-page .sim-card {
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
}

.mesh-sim-page .sim-kpi {
    border-left: 4px solid #2563eb;
}

.mesh-sim-page .sim-kpi.success {
    border-left-color: #16a34a;
}

.mesh-sim-page .sim-kpi.warning {
    border-left-color: #d97706;
}

.mesh-sim-page .sim-kpi.danger {
    border-left-color: #dc2626;
}

.mesh-sim-page .sim-label {
    color: #64748b;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0;
    text-transform: uppercase;
}

.mesh-sim-page .sim-value {
    color: #0f172a;
    font-size: 1.55rem;
    font-weight: 800;
    line-height: 1.15;
}

.mesh-sim-page .sim-subvalue {
    color: #64748b;
    font-size: 0.86rem;
}

.mesh-sim-page .chart-fixed {
    height: 310px;
    min-height: 310px;
    position: relative;
}

.mesh-sim-page .mesh-topology-canvas {
    background: #182313;
    border: 1px solid #35452b;
    border-radius: 8px;
    display: block;
    height: 420px;
    min-height: 420px;
    width: 100%;
}

.mesh-sim-page .table-sm td,
.mesh-sim-page .table-sm th {
    vertical-align: middle;
}

@media (max-width: 767.98px) {
    .mesh-sim-page .sim-value {
        font-size: 1.32rem;
    }

    .mesh-sim-page .mesh-topology-canvas {
        height: 330px;
        min-height: 330px;
    }
}
</style>

<div class="content-section mesh-sim-page">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-diagram-project"></i> Mesh Topology Simulation</h4>
            <p class="text-muted mb-0">Proyeksi ekspansi WiFi HaLow dari data uji point-to-point yang sudah tersimpan.</p>
        </div>
        <span class="badge bg-primary"><?php echo number_format((float) $referenceRecords); ?> reference records</span>
    </div>

    <?php if (!empty($baselineMissing)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-triangle-exclamation"></i>
            Sebagian baseline belum lengkap: <?php echo htmlspecialchars(implode(', ', $baselineMissing)); ?>. Nilai fallback dipakai hanya untuk simulasi.
        </div>
    <?php endif; ?>

    <form method="GET" class="card sim-card mb-4">
        <input type="hidden" name="page" value="mesh-simulation">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Master</label>
                    <input type="number" class="form-control" name="master_count" min="1" max="12" value="<?php echo htmlspecialchars((string) $masterCount); ?>">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Slave</label>
                    <input type="number" class="form-control" name="slave_count" min="1" max="60" value="<?php echo htmlspecialchars((string) $slaveCount); ?>">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Radius Area (m)</label>
                    <input type="number" class="form-control" name="area_radius_meter" min="25" max="5000" step="1" value="<?php echo htmlspecialchars((string) round($areaRadius)); ?>">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Target Jarak (m)</label>
                    <input type="number" class="form-control" name="target_distance_meter" min="25" max="5000" step="1" value="<?php echo htmlspecialchars((string) round($targetDistance)); ?>">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Tahap Koneksi</label>
                    <input type="number" class="form-control" name="max_hop" min="1" max="8" value="<?php echo htmlspecialchars((string) $maxHop); ?>">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Lingkungan</label>
                    <select class="form-select" name="environment">
                        <?php foreach ($environmentOptions as $key => $option): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $environmentKey === $key ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($option['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Traffic</label>
                    <select class="form-select" name="traffic">
                        <?php foreach ($trafficProfiles as $key => $option): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $trafficKey === $key ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($option['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-calculator"></i> Simulate
                    </button>
                    <a href="index.php?page=mesh-simulation" class="btn btn-outline-secondary" title="Reset">
                        <i class="fas fa-rotate-left"></i>
                    </a>
                </div>
            </div>
        </div>
    </form>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card sim-card sim-kpi success h-100">
                <div class="card-body">
                    <div class="sim-label">Connectivity</div>
                    <div class="sim-value"><?php echo meshSimFormat($meshConnectivity, 2, '%'); ?></div>
                    <div class="sim-subvalue">P2P acuan: <?php echo meshSimFormat($p2pConnectivity, 2, '%'); ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card sim-card sim-kpi h-100">
                <div class="card-body">
                    <div class="sim-label">Mesh Reach</div>
                    <div class="sim-value"><?php echo meshSimFormat($meshReach, 0, ' m'); ?></div>
                    <div class="sim-subvalue"><?php echo meshSimFormat($coverageGain, 1, 'x'); ?> dari jarak single hop</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card sim-card sim-kpi warning h-100">
                <div class="card-body">
                    <div class="sim-label">Latency Est.</div>
                    <div class="sim-value"><?php echo meshSimFormat($estimatedLatency, 2, ' ms'); ?></div>
                    <div class="sim-subvalue"><?php echo $effectiveHops; ?> tahap efektif dari <?php echo $maxHop; ?> tahap</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card sim-card sim-kpi danger h-100">
                <div class="card-body">
                    <div class="sim-label">Throughput/Slave</div>
                    <div class="sim-value"><?php echo meshSimFormat($throughputPerSlave, 2, ' kbps'); ?></div>
                    <div class="sim-subvalue">Aggregate: <?php echo meshSimFormat($aggregateThroughput, 2, ' kbps'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 mb-4">
            <div class="card sim-card h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Tactical Master-Slave Area Preview</h6>
                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars($environment['label']); ?></span>
                </div>
                <div class="card-body">
                    <canvas id="meshTopologyCanvas" class="mesh-topology-canvas"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4 mb-4">
            <div class="card sim-card h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Sales Summary</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm mb-3">
                            <tbody>
                                <?php foreach ($salesRows as $row): ?>
                                    <tr>
                                        <th class="text-muted"><?php echo htmlspecialchars($row['label']); ?></th>
                                        <td class="text-end fw-semibold"><?php echo htmlspecialchars($row['value']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert <?php echo $recommendedExtraNodes > 0 ? 'alert-warning' : 'alert-success'; ?> mb-0">
                        <i class="fas <?php echo $recommendedExtraNodes > 0 ? 'fa-circle-exclamation' : 'fa-circle-check'; ?>"></i>
                        <?php echo htmlspecialchars($recommendationText); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-7 mb-4">
            <div class="card sim-card h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Point-to-Point vs Mesh Score</h6>
                </div>
                <div class="card-body">
                    <div class="chart-fixed">
                        <canvas id="meshScoreChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5 mb-4">
            <div class="card sim-card h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Reference Baseline</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <tbody>
                                <tr>
                                    <th>Range acuan</th>
                                    <td class="text-end"><?php echo meshSimFormat($referenceRange, 0, ' m'); ?></td>
                                </tr>
                                <tr>
                                    <th>Packet success</th>
                                    <td class="text-end"><?php echo meshSimFormat($baseSuccess, 2, '%'); ?></td>
                                </tr>
                                <tr>
                                    <th>RSSI / SNR</th>
                                    <td class="text-end"><?php echo meshSimFormat($estimatedRssi, 2, ' dBm'); ?> / <?php echo meshSimFormat($estimatedSnr, 2, ' dB'); ?></td>
                                </tr>
                                <tr>
                                    <th>Packet loss est.</th>
                                    <td class="text-end"><?php echo meshSimFormat($estimatedPacketLoss, 2, '%'); ?></td>
                                </tr>
                                <tr>
                                    <th>Power total est.</th>
                                    <td class="text-end"><?php echo meshSimFormat($totalPower, 2, ' W'); ?></td>
                                </tr>
                                <tr>
                                    <th>Radius area input</th>
                                    <td class="text-end"><?php echo meshSimFormat($areaRadius, 0, ' m'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted small mb-0 mt-3">Simulasi ini memakai baseline data penelitian; hasil lapangan tetap perlu validasi ulang saat jumlah device dan posisi node berubah.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    var scoreData = <?php echo json_encode($scoreChart); ?>;
    var topology = <?php echo json_encode($topologyData); ?>;

    if (window.Chart && document.getElementById('meshScoreChart')) {
        new Chart(document.getElementById('meshScoreChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: scoreData.labels,
                datasets: [
                    {
                        label: 'Point-to-point',
                        data: scoreData.p2p,
                        backgroundColor: '#64748b'
                    },
                    {
                        label: 'Mesh simulation',
                        data: scoreData.mesh,
                        backgroundColor: '#2563eb'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + '%';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    var canvas = document.getElementById('meshTopologyCanvas');
    if (!canvas) {
        return;
    }

    function toPoint(item, width, height) {
        return { x: item[0] * width, y: item[1] * height };
    }

    function drawPolygon(ctx, points, fill, stroke) {
        if (!points.length) {
            return;
        }

        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);
        points.slice(1).forEach(function(point) {
            ctx.lineTo(point.x, point.y);
        });
        ctx.closePath();
        ctx.fillStyle = fill;
        ctx.fill();
        if (stroke) {
            ctx.strokeStyle = stroke;
            ctx.lineWidth = 1;
            ctx.stroke();
        }
    }

    function drawPath(ctx, points, color, width, dash) {
        if (points.length < 2) {
            return;
        }

        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);
        points.slice(1).forEach(function(point) {
            ctx.lineTo(point.x, point.y);
        });
        ctx.strokeStyle = color;
        ctx.lineWidth = width;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.setLineDash(dash || []);
        ctx.stroke();
        ctx.setLineDash([]);
    }

    function drawTextBox(ctx, text, x, y, fill, color) {
        ctx.font = '700 12px Consolas, "Courier New", monospace';
        var width = ctx.measureText(text).width + 14;
        ctx.fillStyle = fill;
        ctx.fillRect(x - 7, y - 15, width, 21);
        ctx.strokeStyle = 'rgba(190, 242, 100, 0.35)';
        ctx.lineWidth = 1;
        ctx.strokeRect(x - 7, y - 15, width, 21);
        ctx.fillStyle = color || '#0f172a';
        ctx.textAlign = 'left';
        ctx.fillText(text, x, y);
    }

    function drawPoint(ctx, x, y, color, border, label, detail, anchor) {
        var isMaster = detail === 'Master';
        var size = isMaster ? 26 : 21;
        var fill = isMaster ? 'rgba(120, 53, 15, 0.96)' : 'rgba(20, 83, 45, 0.96)';
        var stroke = isMaster ? '#facc15' : '#86efac';

        ctx.save();
        ctx.translate(x, y);
        ctx.fillStyle = fill;
        ctx.strokeStyle = stroke;
        ctx.lineWidth = 2;
        if (isMaster) {
            ctx.rotate(Math.PI / 4);
            ctx.fillRect(-size / 2, -size / 2, size, size);
            ctx.strokeRect(-size / 2, -size / 2, size, size);
            ctx.rotate(-Math.PI / 4);
            ctx.beginPath();
            ctx.moveTo(0, -9);
            ctx.lineTo(0, 8);
            ctx.moveTo(-7, 1);
            ctx.lineTo(7, 1);
            ctx.strokeStyle = '#fde68a';
            ctx.lineWidth = 1.5;
            ctx.stroke();
        } else {
            ctx.fillRect(-size / 2, -size / 2, size, size);
            ctx.strokeRect(-size / 2, -size / 2, size, size);
            ctx.beginPath();
            ctx.arc(0, 0, 4, 0, Math.PI * 2);
            ctx.fillStyle = '#bbf7d0';
            ctx.fill();
        }
        ctx.restore();

        var textX = anchor === 'right' ? x + 15 : x - 52;
        var textY = y - 18;
        drawTextBox(ctx, label, textX, textY, 'rgba(6, 22, 13, 0.88)', isMaster ? '#fde68a' : '#bbf7d0');
        if (detail) {
            ctx.font = '600 11px Consolas, "Courier New", monospace';
            ctx.fillStyle = isMaster ? '#fef3c7' : '#dcfce7';
            ctx.textAlign = 'left';
            ctx.fillText(detail, textX, textY + 15);
        }
    }

    function pointOnPath(points, progress) {
        if (points.length === 1) {
            return points[0];
        }

        var lengths = [];
        var total = 0;
        for (var i = 0; i < points.length - 1; i++) {
            var dx = points[i + 1].x - points[i].x;
            var dy = points[i + 1].y - points[i].y;
            var length = Math.sqrt((dx * dx) + (dy * dy));
            lengths.push(length);
            total += length;
        }

        var target = total * Math.max(0, Math.min(1, progress));
        var walked = 0;
        for (var segment = 0; segment < lengths.length; segment++) {
            if (walked + lengths[segment] >= target) {
                var local = (target - walked) / Math.max(1, lengths[segment]);
                return {
                    x: points[segment].x + ((points[segment + 1].x - points[segment].x) * local),
                    y: points[segment].y + ((points[segment + 1].y - points[segment].y) * local)
                };
            }
            walked += lengths[segment];
        }

        return points[points.length - 1];
    }

    function drawDistanceLabel(ctx, from, to, label, offsetY) {
        var x = (from.x + to.x) / 2;
        var y = ((from.y + to.y) / 2) + offsetY;
        drawTextBox(ctx, label, x, y, 'rgba(6, 22, 13, 0.84)', '#e5e7eb');
    }

    function drawMapBackground(ctx, width, height) {
        ctx.fillStyle = '#182313';
        ctx.fillRect(0, 0, width, height);

        ctx.save();
        ctx.strokeStyle = 'rgba(190, 242, 100, 0.10)';
        ctx.lineWidth = 1;
        ctx.font = '600 10px Consolas, "Courier New", monospace';
        ctx.fillStyle = 'rgba(190, 242, 100, 0.45)';
        var grid = Math.max(42, Math.min(58, width / 12));
        for (var x = 0; x <= width; x += grid) {
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, height);
            ctx.stroke();
            if (x > 0) {
                ctx.fillText(('0' + Math.round(x / grid)).slice(-2), x + 3, 13);
            }
        }
        for (var y = 0; y <= height; y += grid) {
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(width, y);
            ctx.stroke();
            if (y > 0) {
                ctx.fillText(('0' + Math.round(y / grid)).slice(-2), 4, y - 4);
            }
        }
        ctx.strokeStyle = 'rgba(190, 242, 100, 0.22)';
        ctx.strokeRect(8, 8, width - 16, height - 16);
        ctx.restore();

        var water = [[0, 0.43], [0.10, 0.38], [0.23, 0.43], [0.30, 0.61], [0.20, 0.79], [0, 0.85]].map(function(point) {
            return toPoint(point, width, height);
        });
        drawPolygon(ctx, water, 'rgba(14, 47, 54, 0.88)', 'rgba(103, 232, 249, 0.24)');

        var terrainPatches = [
            { fill: 'rgba(49, 77, 38, 0.72)', poly: [[0.35, 0.16], [0.98, 0.09], [1.00, 0.32], [0.56, 0.38], [0.42, 0.30]] },
            { fill: 'rgba(64, 83, 37, 0.72)', poly: [[0.36, 0.48], [0.93, 0.45], [0.98, 0.78], [0.56, 0.86], [0.39, 0.70]] },
            { fill: 'rgba(30, 58, 36, 0.74)', poly: [[0.06, 0.08], [0.31, 0.06], [0.36, 0.26], [0.18, 0.35], [0.04, 0.25]] },
            { fill: 'rgba(63, 63, 70, 0.55)', poly: [[0.66, 0.58], [0.91, 0.56], [0.94, 0.78], [0.70, 0.82]] }
        ];
        terrainPatches.forEach(function(patch) {
            drawPolygon(ctx, patch.poly.map(function(point) {
                return toPoint(point, width, height);
            }), patch.fill, 'rgba(190, 242, 100, 0.08)');
        });

        var contours = [
            [[0.03, 0.30], [0.16, 0.25], [0.30, 0.29], [0.43, 0.23], [0.58, 0.21], [0.75, 0.24], [0.96, 0.18]],
            [[0.05, 0.38], [0.19, 0.35], [0.31, 0.40], [0.47, 0.35], [0.63, 0.34], [0.82, 0.36], [0.98, 0.30]],
            [[0.09, 0.54], [0.23, 0.50], [0.39, 0.57], [0.52, 0.51], [0.68, 0.50], [0.88, 0.53], [0.99, 0.48]],
            [[0.02, 0.70], [0.16, 0.66], [0.30, 0.72], [0.46, 0.68], [0.64, 0.70], [0.82, 0.76], [0.98, 0.68]],
            [[0.11, 0.88], [0.25, 0.82], [0.40, 0.88], [0.56, 0.84], [0.72, 0.88], [0.92, 0.83]]
        ];
        contours.forEach(function(line, index) {
            drawPath(ctx, line.map(function(point) {
                return toPoint(point, width, height);
            }), index % 2 === 0 ? 'rgba(190, 242, 100, 0.24)' : 'rgba(132, 204, 22, 0.18)', 1, index % 2 === 0 ? [] : [6, 7]);
        });

        var roadTop = [[0.01, 0.17], [0.22, 0.15], [0.43, 0.12], [0.69, 0.13], [0.99, 0.08]].map(function(point) {
            return toPoint(point, width, height);
        });
        var roadMain = [[0.06, 0.84], [0.22, 0.73], [0.30, 0.54], [0.45, 0.41], [0.69, 0.38], [0.95, 0.31]].map(function(point) {
            return toPoint(point, width, height);
        });

        drawPath(ctx, roadTop, 'rgba(15, 23, 42, 0.75)', 10);
        drawPath(ctx, roadTop, 'rgba(120, 113, 108, 0.90)', 4, [12, 6]);
        drawPath(ctx, roadMain, 'rgba(15, 23, 42, 0.82)', 12);
        drawPath(ctx, roadMain, 'rgba(217, 119, 6, 0.72)', 4, [10, 8]);

        var zones = [
            { label: 'ALPHA NLOS', fill: 'rgba(146, 64, 14, 0.30)', stroke: '#f59e0b', text: '#fde68a', poly: [[0.13, 0.63], [0.31, 0.58], [0.35, 0.78], [0.18, 0.88]] },
            { label: 'BRAVO LOS', fill: 'rgba(22, 101, 52, 0.24)', stroke: '#86efac', text: '#bbf7d0', poly: [[0.34, 0.37], [0.56, 0.34], [0.55, 0.57], [0.36, 0.62]] },
            { label: 'CHARLIE NLOS', fill: 'rgba(146, 64, 14, 0.24)', stroke: '#f59e0b', text: '#fde68a', poly: [[0.58, 0.30], [0.86, 0.29], [0.90, 0.51], [0.60, 0.54]] },
            { label: 'DELTA LOS', fill: 'rgba(8, 145, 178, 0.18)', stroke: '#67e8f9', text: '#a5f3fc', poly: [[0.75, 0.16], [0.98, 0.11], [0.97, 0.31], [0.76, 0.34]] }
        ];

        zones.forEach(function(zone) {
            var poly = zone.poly.map(function(point) {
                return toPoint(point, width, height);
            });
            drawPolygon(ctx, poly, zone.fill, null);
            drawPath(ctx, poly.concat([poly[0]]), zone.stroke, 1.5, [7, 5]);
            drawTextBox(ctx, zone.label, poly[0].x + 8, poly[0].y + 28, 'rgba(6, 22, 13, 0.78)', zone.text);
        });

        drawTextBox(ctx, 'GRID 11AH-MESH / TACTICAL SIM', width * 0.56, 28, 'rgba(6, 22, 13, 0.84)', '#bef264');
    }

    function drawRangeRing(ctx, center, radius, label, color) {
        ctx.save();
        ctx.beginPath();
        ctx.arc(center.x, center.y, radius, 0, Math.PI * 2);
        ctx.strokeStyle = color;
        ctx.lineWidth = 1.5;
        ctx.setLineDash([8, 7]);
        ctx.stroke();
        ctx.restore();
        drawTextBox(ctx, label, center.x + radius * 0.55, center.y - radius * 0.70, 'rgba(6, 22, 13, 0.80)', color);
    }

    function drawCompass(ctx, width, height) {
        var x = width - 46;
        var y = 54;
        ctx.save();
        ctx.strokeStyle = '#bef264';
        ctx.fillStyle = '#bef264';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(x, y - 28);
        ctx.lineTo(x - 8, y - 4);
        ctx.lineTo(x, y - 10);
        ctx.lineTo(x + 8, y - 4);
        ctx.closePath();
        ctx.fill();
        ctx.beginPath();
        ctx.moveTo(x, y - 4);
        ctx.lineTo(x, y + 28);
        ctx.stroke();
        ctx.font = '800 13px Consolas, "Courier New", monospace';
        ctx.textAlign = 'center';
        ctx.fillText('N', x, y - 34);
        ctx.restore();
    }

    function drawScaleBar(ctx, width, height, meters) {
        var x = width - 190;
        var y = height - 34;
        var bar = 140;
        ctx.save();
        ctx.strokeStyle = '#bef264';
        ctx.fillStyle = '#bef264';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(x, y);
        ctx.lineTo(x + bar, y);
        ctx.moveTo(x, y - 6);
        ctx.lineTo(x, y + 6);
        ctx.moveTo(x + bar / 2, y - 5);
        ctx.lineTo(x + bar / 2, y + 5);
        ctx.moveTo(x + bar, y - 6);
        ctx.lineTo(x + bar, y + 6);
        ctx.stroke();
        ctx.font = '700 11px Consolas, "Courier New", monospace';
        ctx.textAlign = 'center';
        ctx.fillText('0', x, y + 18);
        ctx.fillText(Math.round(meters / 2) + 'm', x + bar / 2, y + 18);
        ctx.fillText(Math.round(meters) + 'm', x + bar, y + 18);
        ctx.restore();
    }

    function drawTopology() {
        var rect = canvas.getBoundingClientRect();
        var ratio = window.devicePixelRatio || 1;
        canvas.width = rect.width * ratio;
        canvas.height = rect.height * ratio;
        var ctx = canvas.getContext('2d');
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.clearRect(0, 0, rect.width, rect.height);

        var width = rect.width;
        var height = rect.height;
        var hopCount = Math.max(1, topology.hopCount);
        var masters = [];
        var slaves = [];
        var visibleMasters = Math.min(topology.masterCount, 3);
        var visibleSlaves = Math.min(topology.slaveCount, 10);
        var linkColor = topology.connectivity >= 90 ? '#16a34a' : (topology.connectivity >= 75 ? '#d97706' : '#dc2626');
        var route = [[0.10, 0.78], [0.28, 0.68], [0.35, 0.51], [0.51, 0.38], [0.72, 0.39], [0.92, 0.31]].map(function(point) {
            return toPoint(point, width, height);
        });

        drawMapBackground(ctx, width, height);

        for (var i = 0; i < visibleMasters; i++) {
            masters.push({
                x: width * (0.22 + (i * 0.035)),
                y: height * (0.18 + (i * 0.055))
            });
        }

        if (masters.length) {
            drawRangeRing(ctx, masters[0], Math.min(width, height) * 0.22, '1HOP ' + Math.round(topology.usableRange) + 'm', '#bef264');
            drawRangeRing(ctx, masters[0], Math.min(width, height) * 0.36, 'MESH ' + Math.round(topology.reach) + 'm', '#67e8f9');
        }

        for (var s = 0; s < visibleSlaves; s++) {
            var progress = visibleSlaves === 1 ? 1 : s / (visibleSlaves - 1);
            var point = pointOnPath(route, progress);
            slaves.push({
                x: point.x,
                y: point.y,
                progress: progress
            });
        }

        drawPath(ctx, route, 'rgba(7, 12, 8, 0.85)', 14);
        drawPath(ctx, route, 'rgba(217, 119, 6, 0.88)', 7);
        drawPath(ctx, route, '#fde68a', 2, [10, 7]);

        slaves.forEach(function(slave, index) {
            var master = masters[index % masters.length] || masters[0];
            drawPath(ctx, [master, slave], 'rgba(125, 211, 252, 0.28)', 1.4, [5, 7]);
        });

        if (slaves.length) {
            drawPath(ctx, [masters[0], slaves[slaves.length - 1]], linkColor, 2.8);
        }

        drawDistanceLabel(ctx, masters[0], route[2], Math.round(topology.usableRange) + 'm', -10);
        drawDistanceLabel(ctx, route[0], route[2], Math.round(topology.targetDistance * 0.34) + 'm', 18);
        drawDistanceLabel(ctx, route[3], route[5], Math.round(topology.targetDistance * 0.48) + 'm', -16);

        masters.forEach(function(master, index) {
            drawPoint(ctx, master.x, master.y, '#ef4444', '#ffffff', index === 0 ? '11ah AP' : 'AP ' + (index + 1), 'Master', 'right');
        });

        slaves.forEach(function(slave, index) {
            var label = index === 0 ? 'STA START' : (index === slaves.length - 1 ? 'STA TARGET' : 'STA-' + ('0' + (index + 1)).slice(-2));
            var detail = index === slaves.length - 1 ? Math.round(topology.targetDistance) + 'm TARGET' : 'SLAVE';
            if (index === 0 || index === slaves.length - 1 || index % 3 === 0) {
                drawPoint(ctx, slave.x, slave.y, '#22c55e', '#ffffff', label, detail, index < 2 ? 'right' : 'left');
            } else {
                ctx.fillStyle = 'rgba(20, 83, 45, 0.96)';
                ctx.fillRect(slave.x - 5, slave.y - 5, 10, 10);
                ctx.lineWidth = 2;
                ctx.strokeStyle = '#86efac';
                ctx.strokeRect(slave.x - 5, slave.y - 5, 10, 10);
            }
        });

        drawCompass(ctx, width, height);
        drawScaleBar(ctx, width, height, topology.targetDistance);
        drawTextBox(ctx, 'CONN ' + topology.connectivity + '%', width * 0.04, 28, 'rgba(6, 22, 13, 0.88)', '#bef264');
        drawTextBox(ctx, 'REACH ' + Math.round(topology.reach) + 'm', width * 0.04, 56, 'rgba(6, 22, 13, 0.78)', '#a5f3fc');
        drawTextBox(ctx, 'LINK STAGE ' + hopCount, width * 0.04, 84, 'rgba(6, 22, 13, 0.78)', '#fde68a');

        if (topology.slaveCount > visibleSlaves) {
            drawTextBox(ctx, '+' + (topology.slaveCount - visibleSlaves) + ' STA LAIN', width * 0.76, height - 24, 'rgba(6, 22, 13, 0.84)', '#bbf7d0');
        }
    }

    drawTopology();
    window.addEventListener('resize', drawTopology);
});
</script>
