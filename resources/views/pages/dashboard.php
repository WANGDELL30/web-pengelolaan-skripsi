<?php
if (!function_exists('dashboardChartPoints')) {
    function dashboardChartPoints($rows, $xField, $yField, $contextFields = []) {
        $points = [];

        foreach ($rows as $row) {
            if (!isset($row[$xField], $row[$yField]) || !is_numeric($row[$xField]) || !is_numeric($row[$yField])) {
                continue;
            }

            $point = [
                'x' => round((float) $row[$xField], 2),
                'y' => round((float) $row[$yField], 2),
            ];

            foreach ($contextFields as $key => $field) {
                $point[$key] = $row[$field] ?? null;
            }

            $points[] = $point;
        }

        return $points;
    }
}

if (!function_exists('dashboardChartSummary')) {
    function dashboardChartSummary($points, $unit) {
        $values = array_column($points, 'y');
        $distances = array_column($points, 'x');

        if (!$values) {
            return [
                'avg' => '-',
                'min' => '-',
                'max' => '-',
                'distance_min' => '-',
                'distance_max' => '-',
                'unit' => $unit,
            ];
        }

        return [
            'avg' => number_format(array_sum($values) / count($values), 2),
            'min' => number_format(min($values), 2),
            'max' => number_format(max($values), 2),
            'distance_min' => number_format(min($distances), 0),
            'distance_max' => number_format(max($distances), 0),
            'unit' => $unit,
        ];
    }
}

$dashboardDistanceCharts = [
    [
        'id' => 'distanceRssiChart',
        'title' => 'Distance vs RSSI Analysis',
        'subtitle' => 'Kekuatan sinyal terhadap jarak pengujian range.',
        'note' => 'RSSI makin mendekati 0 dBm berarti sinyal makin kuat. Penurunan tajam saat jarak bertambah menandakan link mulai melemah.',
        'label' => 'RSSI',
        'unit' => 'dBm',
        'color' => '#2563eb',
        'suggestedMin' => -100,
        'suggestedMax' => -30,
        'reverseGood' => false,
        'points' => dashboardChartPoints($chartData['distance_rssi'], 'distance_actual_meter', 'rssi_dbm', [
            'location' => 'location_name',
            'point' => 'test_point_code',
            'status' => 'status_result',
            'date' => 'test_date',
        ]),
    ],
    [
        'id' => 'distanceSnrChart',
        'title' => 'Distance vs SNR Analysis',
        'subtitle' => 'Kebersihan sinyal terhadap jarak.',
        'note' => 'SNR tinggi berarti sinyal lebih bersih dari noise. Nilai rendah biasanya membuat koneksi tidak stabil.',
        'label' => 'SNR',
        'unit' => 'dB',
        'color' => '#16a34a',
        'suggestedMin' => 0,
        'suggestedMax' => 35,
        'reverseGood' => false,
        'points' => dashboardChartPoints($chartData['distance_snr'], 'distance_actual_meter', 'snr_db', [
            'location' => 'location_name',
            'point' => 'test_point_code',
            'status' => 'status_result',
            'date' => 'test_date',
        ]),
    ],
    [
        'id' => 'distanceBitrateChart',
        'title' => 'Distance vs Bitrate',
        'subtitle' => 'Kapasitas link dari data range test.',
        'note' => 'Bitrate tinggi menunjukkan link masih mampu membawa data dengan kapasitas lebih besar pada jarak tersebut.',
        'label' => 'Bitrate',
        'unit' => 'kbps',
        'color' => '#d97706',
        'suggestedMin' => 0,
        'suggestedMax' => null,
        'reverseGood' => false,
        'points' => dashboardChartPoints($chartData['distance_bitrate'], 'distance_actual_meter', 'bitrate_kbps', [
            'location' => 'location_name',
            'point' => 'test_point_code',
            'status' => 'status_result',
            'date' => 'test_date',
        ]),
    ],
    [
        'id' => 'distanceLatencyChart',
        'title' => 'Distance vs Latency',
        'subtitle' => 'Rata-rata latency pada tiap jarak.',
        'note' => 'Latency makin rendah makin baik. Kenaikan latency menunjukkan respons kontrol semakin lambat.',
        'label' => 'Latency',
        'unit' => 'ms',
        'color' => '#dc2626',
        'suggestedMin' => 0,
        'suggestedMax' => null,
        'reverseGood' => true,
        'points' => dashboardChartPoints($chartData['distance_latency'], 'distance_meter', 'avg_latency', [
            'totalTests' => 'total_tests',
            'avgJitter' => 'avg_jitter',
            'avgLoss' => 'avg_packet_loss',
            'firstDate' => 'first_test_date',
            'lastDate' => 'last_test_date',
        ]),
    ],
    [
        'id' => 'distanceThroughputChart',
        'title' => 'Distance vs Throughput',
        'subtitle' => 'Rata-rata throughput pada tiap jarak.',
        'note' => 'Throughput makin tinggi makin baik. Penurunan throughput saat jarak naik menunjukkan kapasitas link menurun.',
        'label' => 'Throughput',
        'unit' => 'kbps',
        'color' => '#7c3aed',
        'suggestedMin' => 0,
        'suggestedMax' => null,
        'reverseGood' => false,
        'points' => dashboardChartPoints($chartData['distance_throughput'], 'distance_meter', 'avg_throughput', [
            'totalTests' => 'total_tests',
            'avgPdr' => 'avg_pdr',
            'avgLoss' => 'avg_data_loss',
            'firstDate' => 'first_test_date',
            'lastDate' => 'last_test_date',
        ]),
    ],
];

foreach ($dashboardDistanceCharts as $index => $config) {
    $dashboardDistanceCharts[$index]['summary'] = dashboardChartSummary($config['points'], $config['unit']);
}
?>

<!-- Dashboard Content -->
<div class="row mb-4">
    <!-- Total Connectivity Tests -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Total Connectivity Tests</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['total_connectivity']); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-link fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Locations -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card green h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Total Locations</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['total_locations']); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-map-marker-alt fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Nodes -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card orange h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Total Nodes/Master/Slave</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['total_nodes']); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-server fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Status -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="text-xs font-weight-bold text-uppercase mb-2">System Status</div>
                <div class="mb-3">
                    <span class="status-badge status-<?php echo $stats['system_status']; ?> p-3">
                        <i class="fas fa-circle me-2"></i>
                        <?php echo strtoupper($stats['system_status']); ?>
                    </span>
                </div>
                <small class="text-muted">System health check</small>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Average Latency -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Avg Latency (ms)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['avg_latency'], 2); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Average Throughput -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Avg Throughput (kbps)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['avg_throughput'], 2); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-bolt fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Average RSSI -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Avg RSSI (dBm)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['avg_rssi'], 2); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-signal fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Average SNR -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Avg SNR (dB)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['avg_snr'], 2); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chart-line fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Packet Loss -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Avg Packet Loss (%)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['avg_packet_loss'], 2); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-percentage fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Power Consumption -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Avg Power (W)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['avg_power'], 2); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-battery-full fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <?php foreach ($dashboardDistanceCharts as $chart): ?>
    <div class="col-xl-6 col-md-12 mb-4">
        <div class="card h-100 dashboard-distance-card">
            <div class="card-header py-3 d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($chart['title']); ?></h6>
                    <small class="text-muted"><?php echo htmlspecialchars($chart['subtitle']); ?></small>
                </div>
            </div>
            <div class="card-body">
                <div class="dashboard-chart-summary">
                    <div>
                        <span>Avg</span>
                        <strong><?php echo $chart['summary']['avg']; ?> <?php echo htmlspecialchars($chart['summary']['unit']); ?></strong>
                    </div>
                    <div>
                        <span>Min</span>
                        <strong><?php echo $chart['summary']['min']; ?> <?php echo htmlspecialchars($chart['summary']['unit']); ?></strong>
                    </div>
                    <div>
                        <span>Max</span>
                        <strong><?php echo $chart['summary']['max']; ?> <?php echo htmlspecialchars($chart['summary']['unit']); ?></strong>
                    </div>
                    <div>
                        <span>Distance</span>
                        <strong><?php echo $chart['summary']['distance_min']; ?>-<?php echo $chart['summary']['distance_max']; ?> m</strong>
                    </div>
                </div>
                <div class="chart-container dashboard-distance-chart-container">
                    <canvas id="<?php echo htmlspecialchars($chart['id']); ?>"></canvas>
                </div>
                <div class="dashboard-chart-note">
                    <i class="fas fa-circle-info"></i>
                    <span><?php echo htmlspecialchars($chart['note']); ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Recent Tests Table -->
<div class="row">
    <div class="col-xl-12 mb-4">
        <div class="card">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Recent Test Results</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Test Type</th>
                                <th>Location</th>
                                <th>Date</th>
                                <th>Node ID</th>
                                <th>Key Metrics</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTests['connectivity'] as $test): ?>
                            <tr>
                                <td>Connectivity</td>
                                <td><?php echo htmlspecialchars($test['location_name']); ?></td>
                                <td><?php echo formatDate($test['test_date']); ?></td>
                                <td><?php echo htmlspecialchars($test['node_id']); ?></td>
                                <td>RSSI: <?php echo $test['rssi_dbm']; ?> dBm, Packet Loss: <?php echo $test['packet_loss_percent']; ?>%</td>
                                <td><?php echo getStatusBadge($test['connection_status']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php foreach ($recentTests['range'] as $test): ?>
                            <tr>
                                <td>Range</td>
                                <td><?php echo htmlspecialchars($test['location_name']); ?></td>
                                <td><?php echo formatDate($test['test_date']); ?></td>
                                <td>-</td>
                                <td>Distance: <?php echo $test['distance_actual_meter']; ?>m, SNR: <?php echo $test['snr_db']; ?> dB</td>
                                <td><?php echo getStatusBadge($test['status_result']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php foreach ($recentTests['latency'] as $test): ?>
                            <tr>
                                <td>Latency</td>
                                <td><?php echo htmlspecialchars($test['location_name']); ?></td>
                                <td><?php echo formatDate($test['test_date']); ?></td>
                                <td><?php echo htmlspecialchars($test['node_id']); ?></td>
                                <td>Latency: <?php echo $test['latency_ms']; ?> ms, Jitter: <?php echo $test['jitter_ms']; ?> ms</td>
                                <td><?php echo getStatusBadge($test['latency_ms'] < 100 ? 'good' : ($test['latency_ms'] < 500 ? 'moderate' : 'poor')); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var dashboardDistanceCharts = <?php echo json_encode($dashboardDistanceCharts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

function dashboardHexToRgba(hex, alpha) {
    var value = String(hex || '#2563eb').replace('#', '');
    if (value.length === 3) {
        value = value.split('').map(function(part) { return part + part; }).join('');
    }

    var r = parseInt(value.substring(0, 2), 16);
    var g = parseInt(value.substring(2, 4), 16);
    var b = parseInt(value.substring(4, 6), 16);
    return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
}

function dashboardFormatValue(value, unit) {
    if (value === null || value === undefined || isNaN(Number(value))) {
        return '-';
    }

    var number = Number(value);
    var decimals = Math.abs(number) >= 1000 ? 0 : 2;
    return number.toLocaleString('id-ID', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }) + (unit ? ' ' + unit : '');
}

function dashboardPointColor(point, chart) {
    var status = String(point.status || '').toLowerCase();
    if (status === 'good' || status === 'connected' || status === 'success') {
        return '#16a34a';
    }
    if (status === 'moderate' || status === 'warning') {
        return '#d97706';
    }
    if (status === 'poor' || status === 'disconnected' || status === 'fail') {
        return '#dc2626';
    }

    if (chart.unit === 'ms') {
        if (Number(point.y) <= 100) return '#16a34a';
        if (Number(point.y) <= 500) return '#d97706';
        return '#dc2626';
    }

    return chart.color;
}

var dashboardEmptyChartPlugin = {
    id: 'dashboardEmptyChartPlugin',
    afterDraw: function(chart, args, options) {
        var hasData = chart.data.datasets.some(function(dataset) {
            return (dataset.data || []).some(function(point) {
                return point && point.x !== undefined && point.y !== undefined;
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

function dashboardTooltipLines(point, chart) {
    var lines = [
        'Distance: ' + dashboardFormatValue(point.x, 'm'),
        chart.label + ': ' + dashboardFormatValue(point.y, chart.unit)
    ];

    if (point.location) lines.push('Location: ' + point.location);
    if (point.point) lines.push('Test Point: ' + point.point);
    if (point.status) lines.push('Status: ' + point.status);
    if (point.date) lines.push('Date: ' + point.date);
    if (point.totalTests) lines.push('Total Tests: ' + point.totalTests);
    if (point.avgJitter !== null && point.avgJitter !== undefined) lines.push('Avg Jitter: ' + dashboardFormatValue(point.avgJitter, 'ms'));
    if (point.avgPdr !== null && point.avgPdr !== undefined) lines.push('Avg PDR: ' + dashboardFormatValue(point.avgPdr, '%'));
    if (point.avgLoss !== null && point.avgLoss !== undefined) lines.push('Avg Loss: ' + dashboardFormatValue(point.avgLoss, '%'));
    if (point.firstDate && point.lastDate) lines.push('Date Range: ' + point.firstDate + ' - ' + point.lastDate);

    return lines;
}

function createDashboardDistanceChart(chart) {
    var canvas = document.getElementById(chart.id);
    if (!canvas) return;

    var points = (chart.points || []).slice().sort(function(a, b) {
        return Number(a.x) - Number(b.x);
    });

    new Chart(canvas.getContext('2d'), {
        type: 'scatter',
        data: {
            datasets: [{
                label: chart.label + ' trend',
                data: points,
                showLine: true,
                borderColor: chart.color,
                backgroundColor: function(context) {
                    return dashboardPointColor(context.raw || {}, chart);
                },
                pointBackgroundColor: function(context) {
                    return dashboardPointColor(context.raw || {}, chart);
                },
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 9,
                borderWidth: 3,
                tension: 0.25,
                fill: false
            }]
        },
        plugins: [dashboardEmptyChartPlugin],
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'nearest',
                intersect: false
            },
            scales: {
                x: {
                    type: 'linear',
                    position: 'bottom',
                    title: {
                        display: true,
                        text: 'Distance (m)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value + ' m';
                        }
                    },
                    grid: {
                        color: 'rgba(15, 23, 42, 0.1)'
                    }
                },
                y: {
                    suggestedMin: chart.suggestedMin === null ? undefined : chart.suggestedMin,
                    suggestedMax: chart.suggestedMax === null ? undefined : chart.suggestedMax,
                    title: {
                        display: true,
                        text: chart.label + ' (' + chart.unit + ')'
                    },
                    ticks: {
                        callback: function(value) {
                            return chart.unit ? value + ' ' + chart.unit : value;
                        }
                    },
                    grid: {
                        color: 'rgba(15, 23, 42, 0.1)'
                    }
                }
            },
            plugins: {
                dashboardEmptyChartPlugin: {
                    message: 'Belum ada data ' + chart.label
                },
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 16
                    }
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 12,
                    callbacks: {
                        title: function(items) {
                            if (!items.length) return chart.title;
                            return chart.title;
                        },
                        label: function(context) {
                            return dashboardTooltipLines(context.raw || {}, chart);
                        }
                    }
                }
            }
        }
    });
}

dashboardDistanceCharts.forEach(createDashboardDistanceChart);

</script>

<style>
.stat-card {
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.card {
    border: none;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.table-responsive {
    border-radius: 8px;
}

.badge {
    padding: 8px 12px;
    font-weight: 500;
}

.btn-primary {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    transition: all 0.3s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(30, 60, 114, 0.3);
}

.dashboard-distance-card {
    border: 1px solid #dbe4ef;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
}

.dashboard-distance-card .card-header {
    border-bottom: 1px solid #e5edf7;
    background: #ffffff;
}

.dashboard-chart-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 14px;
}

.dashboard-chart-summary > div {
    min-width: 0;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #f8fafc;
}

.dashboard-chart-summary span {
    display: block;
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}

.dashboard-chart-summary strong {
    display: block;
    margin-top: 4px;
    color: #0f172a;
    font-size: 15px;
    font-weight: 750;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dashboard-distance-chart-container {
    height: 360px;
    min-height: 360px;
}

.dashboard-chart-note {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-top: 14px;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #f8fafc;
    color: #475569;
    font-size: 13px;
    line-height: 1.45;
}

.dashboard-chart-note i {
    margin-top: 2px;
    color: #2563eb;
}

@media (max-width: 767.98px) {
    .dashboard-chart-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .dashboard-distance-chart-container {
        height: 340px;
        min-height: 340px;
    }
}
</style>
