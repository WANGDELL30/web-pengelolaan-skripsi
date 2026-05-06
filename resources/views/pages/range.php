<?php
$pageConfig = [
    'title' => 'Range Test',
    'icon' => 'fas fa-ruler',
    'description' => 'Input dan analisis data pengujian jangkauan WiFi HaLow.',
    'table' => 'range_tests',
    'order' => 'test_date DESC, created_at DESC',
    'fields' => [
        ['name' => 'test_date', 'label' => 'Test Date', 'type' => 'date', 'required' => true],
        ['name' => 'location_name', 'label' => 'Location Name', 'required' => true],
        ['name' => 'environment_type', 'label' => 'Environment Type', 'type' => 'select', 'required' => true, 'options' => ['lapangan', 'hangar', 'pantai', 'gunung']],
        ['name' => 'test_point_code', 'label' => 'Test Point Code', 'required' => true],
        ['name' => 'direction', 'label' => 'Direction', 'type' => 'select', 'options' => ['north', 'south', 'east', 'west', 'vertical', 'diagonal']],
        ['name' => 'coordinate_x_meter', 'label' => 'Coordinate X (m)', 'type' => 'number', 'step' => '0.01', 'default' => 0],
        ['name' => 'coordinate_y_meter', 'label' => 'Coordinate Y (m)', 'type' => 'number', 'step' => '0.01', 'default' => 0],
        ['name' => 'coordinate_z_meter', 'label' => 'Coordinate Z (m)', 'type' => 'number', 'step' => '0.01', 'default' => 0],
        ['name' => 'distance_actual_meter', 'label' => 'Actual Distance (m)', 'type' => 'number', 'step' => '0.01', 'required' => true],
        ['name' => 'gps_latitude', 'label' => 'GPS Latitude', 'type' => 'number', 'step' => '0.00000001'],
        ['name' => 'gps_longitude', 'label' => 'GPS Longitude', 'type' => 'number', 'step' => '0.00000001'],
        ['name' => 'frequency_mhz', 'label' => 'Frequency (MHz)', 'type' => 'number', 'step' => '0.01', 'default' => 915],
        ['name' => 'rssi_dbm', 'label' => 'RSSI (dBm)', 'type' => 'number', 'step' => '0.01', 'required' => true],
        ['name' => 'snr_db', 'label' => 'SNR (dB)', 'type' => 'number', 'step' => '0.01', 'required' => true],
        ['name' => 'bitrate_kbps', 'label' => 'Bitrate (kbps)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'connection_status', 'label' => 'Connection Status', 'type' => 'select', 'options' => ['connected', 'disconnected', 'intermittent']],
        ['name' => 'receiver_sensitivity_dbm', 'label' => 'Receiver Sensitivity (dBm)', 'type' => 'number', 'step' => '0.01', 'default' => -90],
        ['name' => 'photo_video_link', 'label' => 'Photo/Video Link'],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'calculate' => function ($data) {
        $distanceMeter = (float) ($data['distance_actual_meter'] ?? 0);
        $distanceKm = $distanceMeter > 0 ? $distanceMeter / 1000 : 0;
        $snr = (float) ($data['snr_db'] ?? 0);
        $status = $data['connection_status'] === 'disconnected' ? 'poor' : ($snr > 20 ? 'good' : ($snr >= 10 ? 'moderate' : 'poor'));

        return [
            'distance_3d_meter' => calculate3DDistance((float) $data['coordinate_x_meter'], (float) $data['coordinate_y_meter'], (float) $data['coordinate_z_meter']),
            'distance_km' => round($distanceKm, 4),
            'fspl_db' => $distanceKm > 0 ? calculateFSPL((float) $data['frequency_mhz'], $distanceKm) : 0,
            'signal_margin' => calculateSignalMargin((float) $data['rssi_dbm'], (float) $data['receiver_sensitivity_dbm']),
            'status_result' => $status,
        ];
    },
    'formulas' => [
        'Distance 3D = sqrt(x^2 + y^2 + z^2)',
        'FSPL = 32.44 + 20log10(frequency) + 20log10(distance km)',
        'Signal Margin = RSSI - Receiver Sensitivity',
    ],
    'columns' => [
        ['label' => 'Date', 'field' => 'test_date', 'format' => 'date'],
        ['label' => 'Location', 'field' => 'location_name'],
        ['label' => 'Point', 'field' => 'test_point_code'],
        ['label' => 'Distance', 'field' => 'distance_actual_meter', 'decimals' => 2, 'suffix' => ' m'],
        ['label' => 'RSSI', 'field' => 'rssi_dbm', 'decimals' => 2, 'suffix' => ' dBm'],
        ['label' => 'SNR', 'field' => 'snr_db', 'decimals' => 2, 'suffix' => ' dB'],
        ['label' => 'FSPL', 'field' => 'fspl_db', 'decimals' => 2, 'suffix' => ' dB'],
        ['label' => 'Status', 'field' => 'status_result', 'format' => 'status'],
    ],
];

include __DIR__ . '/_test_page.php';

if (!function_exists('rangeSpotPointColor')) {
    function rangeSpotPointColor($status) {
        $status = strtolower((string) $status);

        if ($status === 'good') {
            return '#16a34a';
        }

        if ($status === 'moderate') {
            return '#f59e0b';
        }

        return '#dc2626';
    }
}

if (!function_exists('rangeSpotFallbackCoordinate')) {
    function rangeSpotFallbackCoordinate($direction, $distance) {
        $distance = (float) $distance;
        $angleMap = [
            'east' => 0,
            'diagonal' => 45,
            'north' => 90,
            'vertical' => 90,
            'west' => 180,
            'south' => 270,
        ];
        $angle = $angleMap[strtolower((string) $direction)] ?? 0;
        $radians = deg2rad($angle);

        return [
            round(cos($radians) * $distance, 2),
            round(sin($radians) * $distance, 2),
        ];
    }
}

$spotRows = fetchAll("SELECT * FROM range_tests ORDER BY test_date DESC, created_at DESC LIMIT 100");
$spotPoints = [];
$spotEnvelopeMap = [];
$maxAxisValue = 0;
$maxTestedRange = 0;
$goodCoverageRange = 0;
$rssiTotal = 0;
$rssiCount = 0;
$weakestPoint = null;
$strongestPoint = null;

foreach ($spotRows as $row) {
    $distance = (float) ($row['distance_actual_meter'] ?? 0);
    $x = (float) ($row['coordinate_x_meter'] ?? 0);
    $y = (float) ($row['coordinate_y_meter'] ?? 0);

    if (abs($x) < 0.00001 && abs($y) < 0.00001 && $distance > 0) {
        [$x, $y] = rangeSpotFallbackCoordinate($row['direction'] ?? '', $distance);
    }

    if ($distance <= 0) {
        $distance = sqrt(($x * $x) + ($y * $y));
    }

    $status = $row['status_result'] ?? 'poor';
    $rssi = isset($row['rssi_dbm']) ? (float) $row['rssi_dbm'] : null;
    $snr = isset($row['snr_db']) ? (float) $row['snr_db'] : null;
    $angle = rad2deg(atan2($y, $x));

    if ($angle < 0) {
        $angle += 360;
    }

    $point = [
        'x' => round($x, 2),
        'y' => round($y, 2),
        'r' => round($distance, 2),
        'angle' => round($angle, 1),
        'label' => $row['test_point_code'] ?: ('Point #' . $row['id']),
        'direction' => $row['direction'] ?: '-',
        'status' => $status,
        'rssi' => $rssi,
        'snr' => $snr,
        'color' => rangeSpotPointColor($status),
    ];

    $spotPoints[] = $point;
    $maxAxisValue = max($maxAxisValue, abs($x), abs($y), $distance);
    $maxTestedRange = max($maxTestedRange, $distance);

    if (strtolower((string) $status) === 'good') {
        $goodCoverageRange = max($goodCoverageRange, $distance);
    }

    if ($rssi !== null) {
        $rssiTotal += $rssi;
        $rssiCount++;

        if ($weakestPoint === null || $rssi < $weakestPoint['rssi']) {
            $weakestPoint = $point;
        }

        if ($strongestPoint === null || $rssi > $strongestPoint['rssi']) {
            $strongestPoint = $point;
        }
    }

    $bucket = (int) round($angle / 10) * 10;

    if (!isset($spotEnvelopeMap[$bucket]) || $distance > $spotEnvelopeMap[$bucket]['r']) {
        $spotEnvelopeMap[$bucket] = $point;
    }
}

ksort($spotEnvelopeMap);
$spotEnvelope = array_values($spotEnvelopeMap);

if (count($spotEnvelope) > 2) {
    $spotEnvelope[] = $spotEnvelope[0];
}

$axisMax = max(100, (int) ceil(($maxAxisValue * 1.15) / 50) * 50);
$avgRssi = $rssiCount > 0 ? round($rssiTotal / $rssiCount, 2) : null;
$goodCount = count(array_filter($spotPoints, function ($point) {
    return strtolower((string) $point['status']) === 'good';
}));
$moderateCount = count(array_filter($spotPoints, function ($point) {
    return strtolower((string) $point['status']) === 'moderate';
}));
$poorCount = max(0, count($spotPoints) - $goodCount - $moderateCount);
?>

<style>
    .spot-beam-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 12px;
    }

    .spot-beam-stat {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 14px;
        background: #f8fafc;
    }

    .spot-beam-stat span {
        display: block;
        color: #4b5563;
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .spot-beam-stat strong {
        display: block;
        margin-top: 4px;
        color: #1f2937;
        font-size: 1.2rem;
        line-height: 1.2;
    }

    .spot-beam-chart-container {
        height: clamp(420px, 62vh, 680px);
        min-height: 420px;
    }

    .spot-beam-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 10px 16px;
        color: #4b5563;
        font-size: 0.9rem;
    }

    .spot-beam-legend span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .spot-beam-legend i {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        display: inline-block;
    }
</style>

<div class="content-section">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-satellite-dish"></i> Spot Beam Coverage Result</h4>
            <p class="text-muted mb-0">Pola cakupan lingkaran dari data jarak, koordinat, RSSI, dan SNR Range Test.</p>
        </div>
        <span class="badge bg-secondary"><?php echo count($spotPoints); ?> titik ukur</span>
    </div>

    <div class="spot-beam-summary mb-4">
        <div class="spot-beam-stat">
            <span>Max Tested Range</span>
            <strong><?php echo number_format($maxTestedRange, 2); ?> m</strong>
        </div>
        <div class="spot-beam-stat">
            <span>Good Coverage Radius</span>
            <strong><?php echo number_format($goodCoverageRange, 2); ?> m</strong>
        </div>
        <div class="spot-beam-stat">
            <span>Average RSSI</span>
            <strong><?php echo $avgRssi === null ? '-' : number_format($avgRssi, 2) . ' dBm'; ?></strong>
        </div>
        <div class="spot-beam-stat">
            <span>Strongest Point</span>
            <strong><?php echo $strongestPoint ? htmlspecialchars($strongestPoint['label']) . ' (' . number_format($strongestPoint['rssi'], 2) . ' dBm)' : '-'; ?></strong>
        </div>
        <div class="spot-beam-stat">
            <span>Weakest Point</span>
            <strong><?php echo $weakestPoint ? htmlspecialchars($weakestPoint['label']) . ' (' . number_format($weakestPoint['rssi'], 2) . ' dBm)' : '-'; ?></strong>
        </div>
        <div class="spot-beam-stat">
            <span>Status Split</span>
            <strong><?php echo $goodCount; ?> good / <?php echo $moderateCount; ?> moderate / <?php echo $poorCount; ?> poor</strong>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="m-0 font-weight-bold text-primary">Top View Beam Pattern</h6>
            <div class="spot-beam-legend">
                <span><i style="background:#dc2626"></i>Strong RSSI</span>
                <span><i style="background:#f59e0b"></i>Medium</span>
                <span><i style="background:#22c55e"></i>Weak Edge</span>
                <span><i style="background:#1e3c72"></i>Master</span>
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container spot-beam-chart-container">
                <canvas id="rangeSpotBeamChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    var spotPoints = <?php echo json_encode($spotPoints, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var spotEnvelope = <?php echo json_encode($spotEnvelope, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var axisMax = <?php echo (int) $axisMax; ?>;
    var canvas = document.getElementById('rangeSpotBeamChart');

    if (!canvas || !window.Chart) {
        return;
    }

    function heatValue(point) {
        if (typeof point.rssi === 'number') {
            return point.rssi;
        }

        var status = String(point.status || '').toLowerCase();

        if (status === 'good') {
            return -58;
        }

        if (status === 'moderate') {
            return -72;
        }

        return -88;
    }

    function heatColor(value, alpha) {
        if (value >= -55) {
            return 'rgba(220, 38, 38, ' + alpha + ')';
        }

        if (value >= -65) {
            return 'rgba(249, 115, 22, ' + alpha + ')';
        }

        if (value >= -75) {
            return 'rgba(250, 204, 21, ' + alpha + ')';
        }

        if (value >= -85) {
            return 'rgba(34, 197, 94, ' + alpha + ')';
        }

        return 'rgba(45, 212, 191, ' + alpha + ')';
    }

    function clipEnvelope(ctx, xScale, yScale, area) {
        if (spotEnvelope.length > 2) {
            ctx.beginPath();
            spotEnvelope.forEach(function(point, index) {
                var x = xScale.getPixelForValue(point.x);
                var y = yScale.getPixelForValue(point.y);

                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            ctx.closePath();
            ctx.clip();
            return;
        }

        ctx.beginPath();
        ctx.rect(area.left, area.top, area.right - area.left, area.bottom - area.top);
        ctx.clip();
    }

    var heatmapPlugin = {
        id: 'rangeSpotBeamHeatmap',
        beforeDatasetsDraw: function(chart) {
            if (spotPoints.length === 0) {
                return;
            }

            var ctx = chart.ctx;
            var area = chart.chartArea;
            var xScale = chart.scales.x;
            var yScale = chart.scales.y;
            var step = Math.max(7, Math.floor(Math.min(area.width, area.height) / 90));

            ctx.save();
            clipEnvelope(ctx, xScale, yScale, area);

            ctx.fillStyle = 'rgba(187, 247, 208, 0.42)';
            ctx.fillRect(area.left, area.top, area.right - area.left, area.bottom - area.top);

            for (var py = area.top; py <= area.bottom; py += step) {
                for (var px = area.left; px <= area.right; px += step) {
                    var wx = xScale.getValueForPixel(px + step / 2);
                    var wy = yScale.getValueForPixel(py + step / 2);
                    var weighted = 0;
                    var weights = 0;

                    spotPoints.forEach(function(point) {
                        var dx = wx - point.x;
                        var dy = wy - point.y;
                        var distance = Math.sqrt(dx * dx + dy * dy);
                        var influence = Math.max(axisMax * 0.08, Math.min(axisMax * 0.38, Math.max(point.r || 0, axisMax * 0.2) * 0.45));
                        var weight = 1 / (Math.pow(distance / influence, 2) + 0.08);

                        weighted += heatValue(point) * weight;
                        weights += weight;
                    });

                    if (weights > 0) {
                        ctx.fillStyle = heatColor(weighted / weights, 0.58);
                        ctx.fillRect(px, py, step + 1, step + 1);
                    }
                }
            }

            spotPoints.forEach(function(point) {
                var centerX = xScale.getPixelForValue(point.x);
                var centerY = yScale.getPixelForValue(point.y);
                var haloMeters = Math.max(axisMax * 0.08, Math.min(axisMax * 0.22, Math.max(point.r || 0, axisMax * 0.16) * 0.28));
                var haloRadius = Math.abs(xScale.getPixelForValue(point.x + haloMeters) - centerX);
                var gradient = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, haloRadius);

                gradient.addColorStop(0, heatColor(heatValue(point), 0.88));
                gradient.addColorStop(0.45, heatColor(heatValue(point) - 8, 0.38));
                gradient.addColorStop(1, 'rgba(255, 255, 255, 0)');

                ctx.fillStyle = gradient;
                ctx.beginPath();
                ctx.arc(centerX, centerY, haloRadius, 0, Math.PI * 2);
                ctx.fill();
            });

            ctx.restore();
        }
    };

    var ringPlugin = {
        id: 'rangeSpotBeamRings',
        beforeDatasetsDraw: function(chart) {
            var ctx = chart.ctx;
            var area = chart.chartArea;
            var xScale = chart.scales.x;
            var yScale = chart.scales.y;
            var centerX = xScale.getPixelForValue(0);
            var centerY = yScale.getPixelForValue(0);
            var rings = [0.25, 0.5, 0.75, 1];

            ctx.save();
            ctx.strokeStyle = 'rgba(30, 60, 114, 0.16)';
            ctx.fillStyle = '#64748b';
            ctx.lineWidth = 1;
            ctx.setLineDash([5, 5]);

            rings.forEach(function(part) {
                var value = axisMax * part;
                var radius = Math.abs(xScale.getPixelForValue(value) - centerX);

                ctx.beginPath();
                ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
                ctx.stroke();
                ctx.fillText(Math.round(value) + ' m', centerX + radius + 6, Math.max(area.top + 14, centerY - 6));
            });

            ctx.setLineDash([]);
            ctx.strokeStyle = 'rgba(15, 23, 42, 0.2)';
            ctx.beginPath();
            ctx.moveTo(area.left, centerY);
            ctx.lineTo(area.right, centerY);
            ctx.moveTo(centerX, area.top);
            ctx.lineTo(centerX, area.bottom);
            ctx.stroke();
            ctx.restore();
        }
    };

    function byStatus(status) {
        return spotPoints.filter(function(point) {
            return String(point.status).toLowerCase() === status;
        });
    }

    function pointTooltip(context) {
        var point = context.raw || {};

        if (point.master) {
            return 'Master / Gateway';
        }

        return [
            (point.label || 'Point') + ' - ' + (point.status || '-'),
            'Distance: ' + (point.r || 0) + ' m',
            'Direction: ' + (point.direction || '-'),
            'RSSI: ' + (point.rssi === null ? '-' : point.rssi + ' dBm'),
            'SNR: ' + (point.snr === null ? '-' : point.snr + ' dB')
        ];
    }

    new Chart(canvas.getContext('2d'), {
        type: 'scatter',
        plugins: [heatmapPlugin, ringPlugin],
        data: {
            datasets: [
                {
                    type: 'line',
                    label: 'Beam Boundary',
                    data: spotEnvelope,
                    borderColor: '#dc2626',
                    backgroundColor: 'rgba(220, 38, 38, 0.03)',
                    borderWidth: 2.5,
                    pointRadius: 0,
                    tension: 0.25,
                    fill: false,
                    order: 4
                },
                {
                    label: 'Good',
                    data: byStatus('good'),
                    backgroundColor: '#16a34a',
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    pointRadius: 7,
                    pointHoverRadius: 10,
                    order: 1
                },
                {
                    label: 'Moderate',
                    data: byStatus('moderate'),
                    backgroundColor: '#f59e0b',
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    pointRadius: 7,
                    pointHoverRadius: 10,
                    order: 1
                },
                {
                    label: 'Poor',
                    data: byStatus('poor'),
                    backgroundColor: '#dc2626',
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    pointRadius: 7,
                    pointHoverRadius: 10,
                    order: 1
                },
                {
                    label: 'Master',
                    data: [{ x: 0, y: 0, master: true }],
                    backgroundColor: '#1e3c72',
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    pointStyle: 'rectRot',
                    pointRadius: 10,
                    pointHoverRadius: 12,
                    order: 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            parsing: false,
            interaction: {
                mode: 'nearest',
                intersect: true
            },
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: pointTooltip
                    }
                }
            },
            scales: {
                x: {
                    min: -axisMax,
                    max: axisMax,
                    grid: {
                        color: 'rgba(30, 60, 114, 0.08)'
                    },
                    title: {
                        display: true,
                        text: 'X Coordinate (m)'
                    }
                },
                y: {
                    min: -axisMax,
                    max: axisMax,
                    grid: {
                        color: 'rgba(30, 60, 114, 0.08)'
                    },
                    title: {
                        display: true,
                        text: 'Y Coordinate (m)'
                    }
                }
            }
        }
    });
});
</script>
