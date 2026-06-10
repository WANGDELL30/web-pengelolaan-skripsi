<?php
if (!function_exists('jammingSimMetric')) {
    function jammingSimMetric($sql, $fallback = null) {
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

if (!function_exists('jammingSimClamp')) {
    function jammingSimClamp($value, $min, $max) {
        return max($min, min($max, $value));
    }
}

if (!function_exists('jammingSimInputFloat')) {
    function jammingSimInputFloat($key, $default, $min, $max) {
        $value = isset($_GET[$key]) ? filter_var($_GET[$key], FILTER_VALIDATE_FLOAT) : $default;
        if ($value === false || $value === null) {
            $value = $default;
        }

        return jammingSimClamp((float) $value, $min, $max);
    }
}

if (!function_exists('jammingSimInputChoice')) {
    function jammingSimInputChoice($key, $default, $options) {
        $value = $_GET[$key] ?? $default;
        return array_key_exists($value, $options) ? $value : $default;
    }
}

if (!function_exists('jammingSimFormat')) {
    function jammingSimFormat($value, $decimals = 2, $suffix = '') {
        if (function_exists('formatNullableNumber')) {
            return formatNullableNumber($value, $decimals, $suffix);
        }

        if ($value === null || $value === '') {
            return 'N/A';
        }

        return number_format((float) $value, $decimals) . $suffix;
    }
}

if (!function_exists('jammingSimDbmAdd')) {
    function jammingSimDbmAdd($dbmValues) {
        $mw = 0.0;
        foreach ($dbmValues as $dbm) {
            if ($dbm !== null && is_numeric($dbm)) {
                $mw += pow(10, ((float) $dbm) / 10);
            }
        }

        return $mw > 0 ? 10 * log10($mw) : null;
    }
}

$baseRssi = jammingSimMetric("SELECT AVG(value) AS value FROM (
    SELECT rssi_dbm AS value FROM connectivity_tests WHERE rssi_dbm IS NOT NULL
    UNION ALL
    SELECT rssi_dbm AS value FROM interference_tests WHERE rssi_dbm IS NOT NULL
    UNION ALL
    SELECT rssi_dbm AS value FROM range_tests WHERE rssi_dbm IS NOT NULL
) baseline", -82);

$baseSnr = jammingSimMetric("SELECT AVG(value) AS value FROM (
    SELECT snr_db AS value FROM connectivity_tests WHERE snr_db IS NOT NULL
    UNION ALL
    SELECT snr_db AS value FROM interference_tests WHERE snr_db IS NOT NULL
    UNION ALL
    SELECT snr_db AS value FROM range_tests WHERE snr_db IS NOT NULL
) baseline", 14);

$baseThroughput = jammingSimMetric("SELECT AVG(value) AS value FROM (
    SELECT throughput_kbps AS value FROM throughput_tests WHERE throughput_kbps IS NOT NULL AND throughput_kbps > 0
    UNION ALL
    SELECT throughput_kbps AS value FROM interference_tests WHERE throughput_kbps IS NOT NULL AND throughput_kbps > 0
) baseline", 180);

$baseLatency = jammingSimMetric("SELECT MIN(value) AS value FROM (
    SELECT latency_ms AS value FROM latency_tests WHERE latency_ms IS NOT NULL AND latency_ms > 0
    UNION ALL
    SELECT latency_ms AS value FROM interference_tests WHERE latency_ms IS NOT NULL AND latency_ms > 0
) baseline", 20);

$basePacketLoss = jammingSimMetric("SELECT MIN(value) AS value FROM (
    SELECT packet_loss_percent AS value FROM connectivity_tests WHERE packet_loss_percent IS NOT NULL
    UNION ALL
    SELECT packet_loss_percent AS value FROM interference_tests WHERE packet_loss_percent IS NOT NULL
) baseline", 2);

$referenceRecords = jammingSimMetric("SELECT
    (SELECT COUNT(*) FROM connectivity_tests) +
    (SELECT COUNT(*) FROM interference_tests) +
    (SELECT COUNT(*) FROM throughput_tests) +
    (SELECT COUNT(*) FROM latency_tests) AS value", 0);

$scenarioOptions = [
    'noise_floor' => [
        'label' => 'Noise floor naik',
        'impact' => 1.00,
        'coupling_db' => 0,
        'description' => 'Gangguan seperti noise luas yang menaikkan noise floor receiver.',
    ],
    'narrowband' => [
        'label' => 'Narrowband interference',
        'impact' => 0.72,
        'coupling_db' => -5,
        'description' => 'Gangguan sempit yang hanya mengenai sebagian kanal.',
    ],
    'burst' => [
        'label' => 'Burst / intermittent',
        'impact' => 0.82,
        'coupling_db' => -3,
        'description' => 'Gangguan putus-putus yang menaikkan retry dan latency.',
    ],
    'adjacent' => [
        'label' => 'Adjacent transmitter',
        'impact' => 0.48,
        'coupling_db' => -9,
        'description' => 'Pemancar lain di sekitar kanal, bukan serangan langsung.',
    ],
];

$mitigationOptions = [
    'none' => ['label' => 'Tanpa mitigasi', 'snr_gain' => 0, 'loss_factor' => 1.00, 'throughput_factor' => 1.00],
    'channel_plan' => ['label' => 'Pindah kanal / channel plan', 'snr_gain' => 4, 'loss_factor' => 0.78, 'throughput_factor' => 1.04],
    'directional' => ['label' => 'Antenna directional', 'snr_gain' => 6, 'loss_factor' => 0.70, 'throughput_factor' => 1.08],
    'lower_bitrate' => ['label' => 'Mode bitrate konservatif', 'snr_gain' => 3, 'loss_factor' => 0.68, 'throughput_factor' => 0.78],
    'shielded_lab' => ['label' => 'Uji tertutup / attenuation', 'snr_gain' => 8, 'loss_factor' => 0.55, 'throughput_factor' => 0.92],
];

$scenarioKey = jammingSimInputChoice('scenario', 'noise_floor', $scenarioOptions);
$mitigationKey = jammingSimInputChoice('mitigation', 'none', $mitigationOptions);
$linkRssi = jammingSimInputFloat('link_rssi_dbm', $baseRssi, -115, -30);
$baselineSnr = jammingSimInputFloat('baseline_snr_db', $baseSnr, -5, 45);
$interferencePower = jammingSimInputFloat('interference_dbm', -92, -120, -35);
$dutyCycle = jammingSimInputFloat('duty_cycle', 35, 0, 100);
$testDuration = jammingSimInputFloat('test_duration_second', 60, 10, 3600);

$scenario = $scenarioOptions[$scenarioKey];
$mitigation = $mitigationOptions[$mitigationKey];

$noiseBefore = $linkRssi - $baselineSnr;
$effectiveInterference = $interferencePower + $scenario['coupling_db'] + (10 * log10(max(0.01, $dutyCycle / 100))) + (10 * log10($scenario['impact']));
$noiseAfter = jammingSimDbmAdd([$noiseBefore, $effectiveInterference]);
$rawSnrAfter = $noiseAfter === null ? $baselineSnr : $linkRssi - $noiseAfter;
$snrAfter = $rawSnrAfter + $mitigation['snr_gain'];
$snrDegradation = max(0, $baselineSnr - $snrAfter);
$signalInterferenceRatio = $linkRssi - $interferencePower;

$snrPenalty = max(0, 12 - $snrAfter) * 3.8;
$sirPenalty = max(0, 10 - $signalInterferenceRatio) * 1.35;
$dutyPenalty = $dutyCycle * 0.14 * $scenario['impact'];
$packetLoss = jammingSimClamp(($basePacketLoss + $snrPenalty + $sirPenalty + $dutyPenalty) * $mitigation['loss_factor'], 0, 100);
$availability = jammingSimClamp(100 - $packetLoss - (max(0, 6 - $snrAfter) * 4.5), 0, 100);
$throughputAfter = max(0, $baseThroughput * (1 - ($packetLoss / 100)) * (1 - (($dutyCycle / 100) * $scenario['impact'] * 0.22)) * $mitigation['throughput_factor']);
$latencyAfter = $baseLatency * (1 + (($packetLoss / 100) * 4.5) + (max(0, 10 - $snrAfter) * 0.18) + (($dutyCycle / 100) * $scenario['impact']));
$packetSent = max(1, (int) round($testDuration * 10));
$packetReceived = (int) round($packetSent * (1 - ($packetLoss / 100)));
$packetLost = max(0, $packetSent - $packetReceived);

if ($availability >= 90 && $snrAfter >= 12) {
    $riskLevel = 'LOW';
    $riskClass = 'success';
    $riskText = 'Link masih stabil untuk demo.';
} elseif ($availability >= 65 && $snrAfter >= 6) {
    $riskLevel = 'MEDIUM';
    $riskClass = 'warning';
    $riskText = 'Link terdegradasi, tetapi masih bisa diamati.';
} else {
    $riskLevel = 'HIGH';
    $riskClass = 'danger';
    $riskText = 'Link berisiko drop, cocok untuk skenario stress-test simulasi.';
}

$baselineMissing = [];
if ($baseRssi === null) {
    $baselineMissing[] = 'RSSI';
}
if ($baseSnr === null) {
    $baselineMissing[] = 'SNR';
}
if ($baseThroughput === null) {
    $baselineMissing[] = 'throughput';
}

$impactRows = [
    ['Metric', 'Baseline', 'Simulated Under Interference', 'Delta'],
    ['SNR', jammingSimFormat($baselineSnr, 2, ' dB'), jammingSimFormat($snrAfter, 2, ' dB'), '-' . jammingSimFormat($snrDegradation, 2, ' dB')],
    ['Noise floor', jammingSimFormat($noiseBefore, 2, ' dBm'), jammingSimFormat($noiseAfter, 2, ' dBm'), jammingSimFormat(($noiseAfter - $noiseBefore), 2, ' dB')],
    ['Packet loss', jammingSimFormat($basePacketLoss, 2, '%'), jammingSimFormat($packetLoss, 2, '%'), '+' . jammingSimFormat(max(0, $packetLoss - $basePacketLoss), 2, '%')],
    ['Throughput', jammingSimFormat($baseThroughput, 2, ' kbps'), jammingSimFormat($throughputAfter, 2, ' kbps'), '-' . jammingSimFormat(max(0, $baseThroughput - $throughputAfter), 2, ' kbps')],
    ['Latency', jammingSimFormat($baseLatency, 2, ' ms'), jammingSimFormat($latencyAfter, 2, ' ms'), '+' . jammingSimFormat(max(0, $latencyAfter - $baseLatency), 2, ' ms')],
    ['Packet sample', number_format($packetSent) . ' sent', number_format($packetReceived) . ' received', number_format($packetLost) . ' lost'],
];

$chartData = [
    'labels' => ['Availability', 'SNR score', 'Throughput score', 'Latency score'],
    'baseline' => [
        100,
        jammingSimClamp(($baselineSnr / 20) * 100, 0, 100),
        100,
        100,
    ],
    'simulated' => [
        round($availability, 2),
        round(jammingSimClamp(($snrAfter / 20) * 100, 0, 100), 2),
        round($baseThroughput > 0 ? jammingSimClamp(($throughputAfter / $baseThroughput) * 100, 0, 100) : 0, 2),
        round(jammingSimClamp(100 - ((max(0, $latencyAfter - $baseLatency) / max(10, $baseLatency)) * 100), 0, 100), 2),
    ],
];

$timelineData = [];
for ($i = 0; $i <= 10; $i++) {
    $phase = $i / 10;
    $burstFactor = $scenarioKey === 'burst' ? (0.55 + (0.45 * sin($phase * M_PI * 4))) : 1;
    $phaseLoss = jammingSimClamp($basePacketLoss + (($packetLoss - $basePacketLoss) * $phase * max(0.2, $burstFactor)), 0, 100);
    $timelineData[] = [
        'time' => round($testDuration * $phase),
        'loss' => round($phaseLoss, 2),
        'throughput' => round(max(0, $baseThroughput * (1 - ($phaseLoss / 100))), 2),
    ];
}

$defenseRows = [
    ['Area', 'Jawaban Ringkas'],
    ['Scope uji', 'Pengujian dilakukan sebagai simulasi interference, bukan pemancaran jammer RF nyata.'],
    ['Frekuensi vs IP', 'Jamming berada di layer radio/PHY; IP hanya dipakai untuk mengukur dampaknya seperti ping loss, latency, dan throughput.'],
    ['Bukti teknis', 'Bukti ditunjukkan dari perubahan SNR, noise floor, packet loss, throughput, latency, dan availability.'],
    ['Validasi aman', 'Jika butuh uji fisik, lakukan hanya di lab tertutup/attenuator/shielded box dan dengan izin perangkat berwenang.'],
    ['Mitigasi', 'Channel planning, antenna directional, bitrate konservatif, retry policy, dan monitoring SNR dapat dijadikan rekomendasi pertahanan.'],
];
?>

<style>
.jamming-sim-page .sim-card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
}

.jamming-sim-page .kpi-card {
    border-left: 4px solid #2563eb;
}

.jamming-sim-page .kpi-card.success {
    border-left-color: #16a34a;
}

.jamming-sim-page .kpi-card.warning {
    border-left-color: #d97706;
}

.jamming-sim-page .kpi-card.danger {
    border-left-color: #dc2626;
}

.jamming-sim-page .kpi-label {
    color: #64748b;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0;
    text-transform: uppercase;
}

.jamming-sim-page .kpi-value {
    color: #0f172a;
    font-size: 1.55rem;
    font-weight: 800;
    line-height: 1.15;
}

.jamming-sim-page .chart-fixed {
    height: 310px;
    min-height: 310px;
    position: relative;
}

.jamming-sim-page .tactical-strip {
    background: #101b12;
    border: 1px solid #334d28;
    border-radius: 8px;
    color: #d9f99d;
    font-family: Consolas, "Courier New", monospace;
}
</style>

<div class="content-section jamming-sim-page">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-shield-alt"></i> Jamming / Interference Simulation</h4>
            <p class="text-muted mb-0">Simulasi dampak gangguan radio terhadap link WiFi HaLow tanpa memancarkan sinyal jamming.</p>
        </div>
        <span class="badge bg-success">Simulation only</span>
    </div>

    <?php if (!empty($baselineMissing)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-triangle-exclamation"></i>
            Baseline belum lengkap: <?php echo htmlspecialchars(implode(', ', $baselineMissing)); ?>. Nilai fallback dipakai untuk simulasi.
        </div>
    <?php endif; ?>

    <div class="alert alert-info">
        <i class="fas fa-circle-info"></i>
        Halaman ini tidak membuat jammer, tidak mengirim paket serangan, dan tidak mengontrol frekuensi perangkat. Input hanya merepresentasikan gangguan yang diterima receiver.
    </div>

    <form method="GET" class="card sim-card mb-4">
        <input type="hidden" name="page" value="jamming-simulation">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Scenario</label>
                    <select class="form-select" name="scenario">
                        <?php foreach ($scenarioOptions as $key => $option): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $scenarioKey === $key ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($option['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Link RSSI (dBm)</label>
                    <input type="number" class="form-control" name="link_rssi_dbm" min="-115" max="-30" step="0.1" value="<?php echo htmlspecialchars((string) round($linkRssi, 1)); ?>">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Baseline SNR (dB)</label>
                    <input type="number" class="form-control" name="baseline_snr_db" min="-5" max="45" step="0.1" value="<?php echo htmlspecialchars((string) round($baselineSnr, 1)); ?>">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Interference at RX (dBm)</label>
                    <input type="number" class="form-control" name="interference_dbm" min="-120" max="-35" step="0.1" value="<?php echo htmlspecialchars((string) round($interferencePower, 1)); ?>">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Duty Cycle (%)</label>
                    <input type="number" class="form-control" name="duty_cycle" min="0" max="100" step="1" value="<?php echo htmlspecialchars((string) round($dutyCycle)); ?>">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Durasi Sample (s)</label>
                    <input type="number" class="form-control" name="test_duration_second" min="10" max="3600" step="10" value="<?php echo htmlspecialchars((string) round($testDuration)); ?>">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Mitigasi</label>
                    <select class="form-select" name="mitigation">
                        <?php foreach ($mitigationOptions as $key => $option): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $mitigationKey === $key ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($option['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-calculator"></i> Simulate
                    </button>
                    <a href="index.php?page=jamming-simulation" class="btn btn-outline-secondary" title="Reset">
                        <i class="fas fa-rotate-left"></i>
                    </a>
                </div>
            </div>
        </div>
    </form>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card sim-card kpi-card <?php echo htmlspecialchars($riskClass); ?> h-100">
                <div class="card-body">
                    <div class="kpi-label">Risk Level</div>
                    <div class="kpi-value"><?php echo htmlspecialchars($riskLevel); ?></div>
                    <div class="text-muted small"><?php echo htmlspecialchars($riskText); ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card sim-card kpi-card h-100">
                <div class="card-body">
                    <div class="kpi-label">SNR After</div>
                    <div class="kpi-value"><?php echo jammingSimFormat($snrAfter, 2, ' dB'); ?></div>
                    <div class="text-muted small">Degradation: <?php echo jammingSimFormat($snrDegradation, 2, ' dB'); ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card sim-card kpi-card warning h-100">
                <div class="card-body">
                    <div class="kpi-label">Packet Loss</div>
                    <div class="kpi-value"><?php echo jammingSimFormat($packetLoss, 2, '%'); ?></div>
                    <div class="text-muted small"><?php echo number_format($packetLost); ?> lost of <?php echo number_format($packetSent); ?> packets</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card sim-card kpi-card danger h-100">
                <div class="card-body">
                    <div class="kpi-label">Availability</div>
                    <div class="kpi-value"><?php echo jammingSimFormat($availability, 2, '%'); ?></div>
                    <div class="text-muted small">SIR: <?php echo jammingSimFormat($signalInterferenceRatio, 2, ' dB'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-7 mb-4">
            <div class="card sim-card h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Baseline vs Simulated Interference</h6>
                </div>
                <div class="card-body">
                    <div class="chart-fixed">
                        <canvas id="jammingImpactChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5 mb-4">
            <div class="card sim-card h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tactical Status</h6>
                </div>
                <div class="card-body">
                    <div class="tactical-strip p-3 mb-3">
                        <div>SIM MODE: <?php echo htmlspecialchars(strtoupper($scenario['label'])); ?></div>
                        <div>MITIGATION: <?php echo htmlspecialchars(strtoupper($mitigation['label'])); ?></div>
                        <div>RX NOISE: <?php echo htmlspecialchars(jammingSimFormat($noiseAfter, 2, ' dBm')); ?></div>
                        <div>REFERENCE DATA: <?php echo number_format((float) $referenceRecords); ?> records</div>
                    </div>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($scenario['description']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="content-section p-0">
        <div class="card sim-card mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Impact Table</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead>
                            <tr>
                                <?php foreach ($impactRows[0] as $heading): ?>
                                    <th><?php echo htmlspecialchars($heading); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($impactRows, 1) as $row): ?>
                                <tr>
                                    <?php foreach ($row as $cell): ?>
                                        <td><?php echo htmlspecialchars((string) $cell); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 mb-4">
            <div class="card sim-card h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Packet Loss Timeline</h6>
                </div>
                <div class="card-body">
                    <div class="chart-fixed">
                        <canvas id="jammingTimelineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 mb-4">
            <div class="card sim-card h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Jawaban Sidang</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <?php foreach (array_slice($defenseRows, 1) as $row): ?>
                                    <tr>
                                        <th style="width: 32%;"><?php echo htmlspecialchars($row[0]); ?></th>
                                        <td><?php echo htmlspecialchars($row[1]); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    var impactData = <?php echo json_encode($chartData); ?>;
    var timelineData = <?php echo json_encode($timelineData); ?>;

    if (window.Chart && document.getElementById('jammingImpactChart')) {
        new Chart(document.getElementById('jammingImpactChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: impactData.labels,
                datasets: [
                    {
                        label: 'Baseline',
                        data: impactData.baseline,
                        backgroundColor: '#64748b'
                    },
                    {
                        label: 'Simulated interference',
                        data: impactData.simulated,
                        backgroundColor: '#dc2626'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
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

    if (window.Chart && document.getElementById('jammingTimelineChart')) {
        new Chart(document.getElementById('jammingTimelineChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: timelineData.map(function(row) { return row.time + 's'; }),
                datasets: [
                    {
                        label: 'Packet loss (%)',
                        data: timelineData.map(function(row) { return row.loss; }),
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220, 38, 38, 0.12)',
                        tension: 0.35,
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Throughput (kbps)',
                        data: timelineData.map(function(row) { return row.throughput; }),
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.10)',
                        tension: 0.35,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        position: 'left',
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }
});
</script>
