<?php
$pageConfig = [
    'title' => 'Latency Test',
    'icon' => 'fas fa-clock',
    'description' => 'Input pengujian latency, jitter, dan packet loss.',
    'table' => 'latency_tests',
    'order' => 'test_date DESC, created_at DESC',
    'chart_label_fields' => ['test_date', 'node_id'],
    'chart_label_caption' => 'Label grafik: tanggal - node',
    'chart_metrics' => [
        ['field' => 'latency_ms', 'label' => 'Latency', 'unit' => 'ms', 'type' => 'line'],
        ['field' => 'jitter_ms', 'label' => 'Jitter', 'unit' => 'ms', 'type' => 'line'],
        ['field' => 'packet_loss_percent', 'label' => 'Packet Loss', 'unit' => '%', 'type' => 'bar'],
    ],
    'chart_notes' => [
        'Latency dan jitter yang lebih rendah menunjukkan komunikasi lebih stabil.',
        'Packet loss yang naik perlu dicek bersamaan dengan RSSI, jarak, dan mode jaringan.',
    ],
    'fields' => [
        ['name' => 'test_date', 'label' => 'Test Date', 'type' => 'date', 'required' => true],
        ['name' => 'location_name', 'label' => 'Location Name', 'required' => true],
        ['name' => 'environment_type', 'label' => 'Environment Type', 'type' => 'select', 'options' => ['lapangan', 'hangar', 'pantai', 'gunung', 'indoor', 'outdoor']],
        ['name' => 'node_id', 'label' => 'Node ID', 'required' => true],
        ['name' => 'distance_meter', 'label' => 'Distance (m)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'trial_number', 'label' => 'Trial Number', 'type' => 'number', 'integer' => true, 'default' => 1],
        ['name' => 'timestamp_send_ms', 'label' => 'Timestamp Send (ms)', 'type' => 'number', 'integer' => true],
        ['name' => 'timestamp_receive_ms', 'label' => 'Timestamp Receive (ms)', 'type' => 'number', 'integer' => true],
        ['name' => 'packet_sent', 'label' => 'Packet Sent', 'type' => 'number', 'integer' => true, 'default' => 100],
        ['name' => 'packet_received', 'label' => 'Packet Received', 'type' => 'number', 'integer' => true, 'default' => 100],
        ['name' => 'network_mode', 'label' => 'Network Mode', 'type' => 'select', 'options' => ['HaLow only', 'HaLow + VSAT']],
        ['name' => 'latency_ms', 'label' => 'Latency (ms)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'jitter_ms', 'label' => 'Jitter (ms)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'calculate' => function ($data) {
        $latency = $data['latency_ms'] ?? null;
        if (($latency === null || $latency === '') && !empty($data['timestamp_send_ms']) && !empty($data['timestamp_receive_ms'])) {
            $latency = max(0, (int) $data['timestamp_receive_ms'] - (int) $data['timestamp_send_ms']);
        }

        return [
            'latency_ms' => $latency,
            'packet_loss_percent' => calculatePacketLoss($data['packet_sent'] ?? null, $data['packet_received'] ?? null),
            'average_latency' => $latency,
            'minimum_latency' => $latency,
            'maximum_latency' => $latency,
            'average_jitter' => $data['jitter_ms'] ?? null,
        ];
    },
    'formulas' => [
        'Latency = Timestamp Receive - Timestamp Send',
        'Packet Loss % = (Packet Lost / Packet Sent) x 100',
        'Average/Min/Max mengikuti nilai latency per input.',
    ],
    'columns' => [
        ['label' => 'Date', 'field' => 'test_date', 'format' => 'date'],
        ['label' => 'Location', 'field' => 'location_name'],
        ['label' => 'Node', 'field' => 'node_id'],
        ['label' => 'Mode', 'field' => 'network_mode'],
        ['label' => 'Latency', 'field' => 'latency_ms', 'decimals' => 2, 'suffix' => ' ms'],
        ['label' => 'Jitter', 'field' => 'jitter_ms', 'decimals' => 2, 'suffix' => ' ms'],
        ['label' => 'Packet Loss', 'field' => 'packet_loss_percent', 'decimals' => 2, 'suffix' => '%'],
    ],
];

include __DIR__ . '/_test_page.php';

$latencyPerformanceRows = array_reverse(fetchAll("
    SELECT
        test_date,
        node_id,
        network_mode,
        latency_ms,
        jitter_ms,
        packet_loss_percent
    FROM latency_tests
    ORDER BY test_date DESC, created_at DESC, id DESC
    LIMIT 30
"));

$dailyLatencyRows = array_reverse(fetchAll("
    SELECT
        test_date,
        ROUND(AVG(latency_ms), 2) AS avg_latency,
        ROUND(MIN(latency_ms), 2) AS min_latency,
        ROUND(MAX(latency_ms), 2) AS max_latency,
        COUNT(*) AS sample_count
    FROM latency_tests
    WHERE latency_ms IS NOT NULL
    GROUP BY test_date
    ORDER BY test_date DESC
    LIMIT 14
"));

$latencyPerformanceLabels = [];
$latencyContextLabels = [];
$jitterValues = [];
$packetLossValues = [];

foreach ($latencyPerformanceRows as $row) {
    $labelParts = [];
    if (!empty($row['test_date'])) {
        $labelParts[] = formatDate($row['test_date']);
    }
    if (!empty($row['node_id'])) {
        $labelParts[] = (string) $row['node_id'];
    }

    $contextParts = $labelParts;
    if (!empty($row['network_mode'])) {
        $contextParts[] = (string) $row['network_mode'];
    }

    $latencyPerformanceLabels[] = $labelParts ? implode(' - ', $labelParts) : 'Sample';
    $latencyContextLabels[] = $contextParts ? implode(' | ', $contextParts) : '';
    $jitterValues[] = is_numeric($row['jitter_ms'] ?? null) ? (float) $row['jitter_ms'] : null;
    $packetLossValues[] = is_numeric($row['packet_loss_percent'] ?? null) ? (float) $row['packet_loss_percent'] : null;
}

$dailyLatencyLabels = [];
$dailyAvgLatencyValues = [];
$dailyMinLatencyValues = [];
$dailyMaxLatencyValues = [];
$dailySampleCounts = [];

foreach ($dailyLatencyRows as $row) {
    $dailyLatencyLabels[] = !empty($row['test_date']) ? formatDate($row['test_date']) : '-';
    $dailyAvgLatencyValues[] = is_numeric($row['avg_latency'] ?? null) ? (float) $row['avg_latency'] : null;
    $dailyMinLatencyValues[] = is_numeric($row['min_latency'] ?? null) ? (float) $row['min_latency'] : null;
    $dailyMaxLatencyValues[] = is_numeric($row['max_latency'] ?? null) ? (float) $row['max_latency'] : null;
    $dailySampleCounts[] = (int) ($row['sample_count'] ?? 0);
}
?>

<style>
    .latency-performance-grid {
        row-gap: 1.5rem;
    }

    .latency-performance-card {
        height: 100%;
        border: 1px solid #dbe4ef;
        border-radius: 8px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
    }

    .latency-performance-card .card-header {
        min-height: 74px;
        background: #ffffff;
        border-bottom: 1px solid #e5edf7;
    }

    .latency-performance-card h6 {
        color: #1e3c72;
    }

    .latency-chart-container {
        height: clamp(300px, 42vh, 420px);
        min-height: 300px;
    }
</style>

<div class="content-section">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-wave-square"></i> Latency Performance Analytics</h4>
            <p class="text-muted mb-0">Grafik khusus untuk memantau jitter, packet loss, dan tren latency harian.</p>
        </div>
        <span class="badge bg-secondary"><?php echo count($latencyPerformanceRows); ?> sample terbaru</span>
    </div>

    <div class="row latency-performance-grid">
        <div class="col-xl-6">
            <div class="card latency-performance-card">
                <div class="card-header py-3 d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <h6 class="m-0 font-weight-bold">Jitter Performance</h6>
                        <small class="text-muted">Stabilitas variasi delay per sample</small>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm chart-download-btn" data-chart-target="latencyJitterPerformanceChart">
                        <i class="fas fa-download"></i> PNG
                    </button>
                </div>
                <div class="card-body">
                    <div class="chart-container latency-chart-container">
                        <canvas id="latencyJitterPerformanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card latency-performance-card">
                <div class="card-header py-3 d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <h6 class="m-0 font-weight-bold">Packet-Loss Performance</h6>
                        <small class="text-muted">Persentase paket hilang per sample</small>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm chart-download-btn" data-chart-target="latencyPacketLossPerformanceChart">
                        <i class="fas fa-download"></i> PNG
                    </button>
                </div>
                <div class="card-body">
                    <div class="chart-container latency-chart-container">
                        <canvas id="latencyPacketLossPerformanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card latency-performance-card">
                <div class="card-header py-3 d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <h6 class="m-0 font-weight-bold">Daily Latency Trend</h6>
                        <small class="text-muted">Rata-rata, minimum, dan maksimum latency per hari</small>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm chart-download-btn" data-chart-target="latencyDailyTrendChart">
                        <i class="fas fa-download"></i> PNG
                    </button>
                </div>
                <div class="card-body">
                    <div class="chart-container latency-chart-container">
                        <canvas id="latencyDailyTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    if (!window.Chart) {
        return;
    }

    var latencyLabels = <?php echo json_encode($latencyPerformanceLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var latencyContexts = <?php echo json_encode($latencyContextLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var jitterValues = <?php echo json_encode($jitterValues); ?>;
    var packetLossValues = <?php echo json_encode($packetLossValues); ?>;
    var dailyLabels = <?php echo json_encode($dailyLatencyLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var dailyAvgLatencyValues = <?php echo json_encode($dailyAvgLatencyValues); ?>;
    var dailyMinLatencyValues = <?php echo json_encode($dailyMinLatencyValues); ?>;
    var dailyMaxLatencyValues = <?php echo json_encode($dailyMaxLatencyValues); ?>;
    var dailySampleCounts = <?php echo json_encode($dailySampleCounts); ?>;

    function hasData(values) {
        return (values || []).some(function(value) {
            return value !== null && value !== undefined && !isNaN(Number(value));
        });
    }

    function formatValue(value, unit) {
        if (value === null || value === undefined || isNaN(Number(value))) {
            return '-';
        }

        return Number(value).toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + (unit ? ' ' + unit : '');
    }

    var latencyEmptyChartPlugin = {
        id: 'latencyEmptyChartPlugin',
        afterDraw: function(chart, args, options) {
            var hasVisibleData = chart.data.datasets.some(function(dataset) {
                return hasData(dataset.data);
            });

            if (hasVisibleData) {
                return;
            }

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

    function sampleTooltip(unit) {
        return {
            backgroundColor: '#0f172a',
            titleMarginBottom: 8,
            padding: 12,
            callbacks: {
                afterTitle: function(items) {
                    if (!items.length) return '';
                    return latencyContexts[items[0].dataIndex] || '';
                },
                label: function(context) {
                    return context.dataset.label + ': ' + formatValue(context.raw, unit);
                }
            }
        };
    }

    function createLineChart(canvasId, labels, data, config) {
        var canvas = document.getElementById(canvasId);
        if (!canvas) return;

        new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: config.label,
                    data: data,
                    borderColor: config.color,
                    backgroundColor: config.fillColor,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    tension: 0.35,
                    fill: true
                }]
            },
            plugins: [latencyEmptyChartPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    latencyEmptyChartPlugin: {
                        message: config.emptyMessage
                    },
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true }
                    },
                    tooltip: sampleTooltip(config.unit)
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 0,
                            autoSkip: true
                        },
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: config.axisTitle
                        },
                        ticks: {
                            callback: function(value) {
                                return value + ' ' + config.unit;
                            }
                        },
                        grid: { color: 'rgba(15, 23, 42, 0.08)' }
                    }
                }
            }
        });
    }

    function createPacketLossChart() {
        var canvas = document.getElementById('latencyPacketLossPerformanceChart');
        if (!canvas) return;

        new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: latencyLabels,
                datasets: [{
                    label: 'Packet Loss',
                    data: packetLossValues,
                    backgroundColor: packetLossValues.map(function(value) {
                        var number = Number(value || 0);
                        if (number >= 10) return 'rgba(220, 38, 38, 0.78)';
                        if (number >= 3) return 'rgba(217, 119, 6, 0.78)';
                        return 'rgba(22, 163, 74, 0.78)';
                    }),
                    borderColor: packetLossValues.map(function(value) {
                        var number = Number(value || 0);
                        if (number >= 10) return '#dc2626';
                        if (number >= 3) return '#d97706';
                        return '#16a34a';
                    }),
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            plugins: [latencyEmptyChartPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    latencyEmptyChartPlugin: {
                        message: 'Belum ada data packet loss'
                    },
                    legend: { display: false },
                    tooltip: sampleTooltip('%')
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 0,
                            autoSkip: true
                        },
                        grid: { display: false }
                    },
                    y: {
                        min: 0,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Packet Loss (%)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        grid: { color: 'rgba(15, 23, 42, 0.08)' }
                    }
                }
            }
        });
    }

    function createDailyTrendChart() {
        var canvas = document.getElementById('latencyDailyTrendChart');
        if (!canvas) return;

        new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: dailyLabels,
                datasets: [
                    {
                        label: 'Average Latency',
                        data: dailyAvgLatencyValues,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.16)',
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        tension: 0.35,
                        fill: true
                    },
                    {
                        label: 'Minimum Latency',
                        data: dailyMinLatencyValues,
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22, 163, 74, 0.08)',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        tension: 0.35,
                        fill: false
                    },
                    {
                        label: 'Maximum Latency',
                        data: dailyMaxLatencyValues,
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220, 38, 38, 0.08)',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        tension: 0.35,
                        fill: false
                    }
                ]
            },
            plugins: [latencyEmptyChartPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    latencyEmptyChartPlugin: {
                        message: 'Belum ada data latency harian'
                    },
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleMarginBottom: 8,
                        padding: 12,
                        callbacks: {
                            afterTitle: function(items) {
                                if (!items.length) return '';
                                return 'Sample: ' + (dailySampleCounts[items[0].dataIndex] || 0) + ' data';
                            },
                            label: function(context) {
                                return context.dataset.label + ': ' + formatValue(context.raw, 'ms');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 0,
                            autoSkip: true
                        },
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Latency (ms)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + ' ms';
                            }
                        },
                        grid: { color: 'rgba(15, 23, 42, 0.08)' }
                    }
                }
            }
        });
    }

    createLineChart('latencyJitterPerformanceChart', latencyLabels, jitterValues, {
        label: 'Jitter',
        unit: 'ms',
        axisTitle: 'Jitter (ms)',
        color: '#d97706',
        fillColor: 'rgba(217, 119, 6, 0.16)',
        emptyMessage: 'Belum ada data jitter'
    });
    createPacketLossChart();
    createDailyTrendChart();
});
</script>
