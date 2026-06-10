<?php
$pageConfig = [
    'title' => 'Throughput Test',
    'icon' => 'fas fa-bolt',
    'description' => 'Input pengujian throughput, PDR, dan data loss.',
    'table' => 'throughput_tests',
    'order' => 'test_date DESC, created_at DESC',
    'chart_label_fields' => ['node_id', 'distance_meter'],
    'chart_label_caption' => 'Label grafik: node - distance meter',
    'chart_metrics' => [
        ['field' => 'throughput_kbps', 'label' => 'Throughput', 'unit' => 'kbps', 'type' => 'line'],
        ['field' => 'packet_delivery_ratio_percent', 'label' => 'PDR', 'unit' => '%', 'type' => 'bar'],
        ['field' => 'data_loss_percent', 'label' => 'Data Loss', 'unit' => '%', 'type' => 'bar'],
        ['field' => 'snr_db', 'label' => 'SNR', 'unit' => 'dB', 'type' => 'line'],
    ],
    'chart_notes' => [
        'Throughput dan PDR yang tinggi menandakan transfer data berjalan baik.',
        'Data Loss yang naik biasanya berbanding terbalik dengan kualitas link.',
    ],
    'fields' => [
        ['name' => 'test_date', 'label' => 'Test Date', 'type' => 'date', 'required' => true],
        ['name' => 'location_name', 'label' => 'Location Name', 'required' => true],
        ['name' => 'environment_type', 'label' => 'Environment Type', 'type' => 'select', 'options' => ['lapangan', 'hangar', 'pantai', 'gunung', 'indoor', 'outdoor']],
        ['name' => 'node_id', 'label' => 'Node ID', 'required' => true],
        ['name' => 'distance_meter', 'label' => 'Distance (m)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'data_sent_kb', 'label' => 'Data Sent (KB)', 'type' => 'number', 'step' => '0.01', 'required' => true],
        ['name' => 'data_received_kb', 'label' => 'Data Received (KB)', 'type' => 'number', 'step' => '0.01', 'required' => true],
        ['name' => 'transmission_time_second', 'label' => 'Transmission Time (s)', 'type' => 'number', 'step' => '0.01', 'required' => true],
        ['name' => 'rssi_dbm', 'label' => 'RSSI (dBm)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'snr_db', 'label' => 'SNR (dB)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'bitrate_kbps', 'label' => 'Bitrate (kbps)', 'type' => 'number', 'step' => '0.01'],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'calculate' => function ($data) {
        $sent = $data['data_sent_kb'] ?? null;
        $received = $data['data_received_kb'] ?? null;
        $hasValidTransfer = is_numeric($sent) && is_numeric($received) && (float) $sent > 0 && (float) $received >= 0 && (float) $received <= (float) $sent;

        return [
            'throughput_kbps' => calculateThroughput($received, $data['transmission_time_second'] ?? null),
            'packet_delivery_ratio_percent' => $hasValidTransfer ? round(((float) $received / (float) $sent) * 100, 2) : null,
            'data_loss_percent' => $hasValidTransfer ? round((((float) $sent - (float) $received) / (float) $sent) * 100, 2) : null,
        ];
    },
    'formulas' => [
        'Throughput = (Data Received x 1024 x 8) / (Time x 1000)',
        'PDR % = (Data Received / Data Sent) x 100',
        'Data Loss % = ((Data Sent - Data Received) / Data Sent) x 100',
    ],
    'columns' => [
        ['label' => 'Date', 'field' => 'test_date', 'format' => 'date'],
        ['label' => 'Location', 'field' => 'location_name'],
        ['label' => 'Node', 'field' => 'node_id'],
        ['label' => 'Distance', 'field' => 'distance_meter', 'decimals' => 2, 'suffix' => ' m'],
        ['label' => 'Throughput', 'field' => 'throughput_kbps', 'decimals' => 2, 'suffix' => ' kbps'],
        ['label' => 'PDR', 'field' => 'packet_delivery_ratio_percent', 'decimals' => 2, 'suffix' => '%'],
        ['label' => 'Data Loss', 'field' => 'data_loss_percent', 'decimals' => 2, 'suffix' => '%'],
    ],
];

include __DIR__ . '/_test_page.php';

$throughputDeliveryRows = array_reverse(fetchAll("
    SELECT
        test_date,
        node_id,
        distance_meter,
        environment_type,
        packet_delivery_ratio_percent,
        data_loss_percent,
        throughput_kbps
    FROM throughput_tests
    ORDER BY test_date DESC, created_at DESC, id DESC
    LIMIT 30
"));

$environmentThroughputRows = fetchAll("
    SELECT
        environment_type,
        ROUND(AVG(throughput_kbps), 2) AS avg_throughput,
        ROUND(MAX(throughput_kbps), 2) AS max_throughput,
        ROUND(AVG(packet_delivery_ratio_percent), 2) AS avg_delivery_ratio,
        ROUND(AVG(data_loss_percent), 2) AS avg_data_loss,
        COUNT(*) AS sample_count
    FROM throughput_tests
    WHERE throughput_kbps IS NOT NULL
    GROUP BY environment_type
    ORDER BY avg_throughput DESC
");

$deliveryLabels = [];
$deliveryContexts = [];
$deliveryRatioValues = [];
$dataLossValues = [];

foreach ($throughputDeliveryRows as $row) {
    $labelParts = [];
    if (!empty($row['node_id'])) {
        $labelParts[] = (string) $row['node_id'];
    }
    if (is_numeric($row['distance_meter'] ?? null)) {
        $labelParts[] = number_format((float) $row['distance_meter'], 0) . ' m';
    }

    $contextParts = [];
    if (!empty($row['test_date'])) {
        $contextParts[] = formatDate($row['test_date']);
    }
    if (!empty($row['environment_type'])) {
        $contextParts[] = (string) $row['environment_type'];
    }
    if (is_numeric($row['throughput_kbps'] ?? null)) {
        $contextParts[] = number_format((float) $row['throughput_kbps'], 2) . ' kbps';
    }

    $deliveryLabels[] = $labelParts ? implode(' - ', $labelParts) : 'Sample';
    $deliveryContexts[] = $contextParts ? implode(' | ', $contextParts) : '';
    $deliveryRatioValues[] = is_numeric($row['packet_delivery_ratio_percent'] ?? null) ? (float) $row['packet_delivery_ratio_percent'] : null;
    $dataLossValues[] = is_numeric($row['data_loss_percent'] ?? null) ? (float) $row['data_loss_percent'] : null;
}

$environmentLabels = [];
$environmentAvgThroughputValues = [];
$environmentMaxThroughputValues = [];
$environmentDeliveryValues = [];
$environmentLossValues = [];
$environmentSampleCounts = [];

foreach ($environmentThroughputRows as $row) {
    $environment = trim((string) ($row['environment_type'] ?? ''));
    $environmentLabels[] = $environment !== '' ? ucfirst($environment) : 'Unknown';
    $environmentAvgThroughputValues[] = is_numeric($row['avg_throughput'] ?? null) ? (float) $row['avg_throughput'] : null;
    $environmentMaxThroughputValues[] = is_numeric($row['max_throughput'] ?? null) ? (float) $row['max_throughput'] : null;
    $environmentDeliveryValues[] = is_numeric($row['avg_delivery_ratio'] ?? null) ? (float) $row['avg_delivery_ratio'] : null;
    $environmentLossValues[] = is_numeric($row['avg_data_loss'] ?? null) ? (float) $row['avg_data_loss'] : null;
    $environmentSampleCounts[] = (int) ($row['sample_count'] ?? 0);
}
?>

<style>
    .throughput-analytics-grid {
        row-gap: 1.5rem;
    }

    .throughput-analytics-card {
        height: 100%;
        border: 1px solid #dbe4ef;
        border-radius: 8px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
    }

    .throughput-analytics-card .card-header {
        min-height: 74px;
        background: #ffffff;
        border-bottom: 1px solid #e5edf7;
    }

    .throughput-analytics-card h6 {
        color: #1e3c72;
    }

    .throughput-chart-container {
        height: clamp(320px, 46vh, 460px);
        min-height: 320px;
    }
</style>

<div class="content-section">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-chart-column"></i> Throughput Performance Analytics</h4>
            <p class="text-muted mb-0">Grafik khusus untuk rasio pengiriman data, data loss, dan perbandingan throughput tiap environment.</p>
        </div>
        <span class="badge bg-secondary"><?php echo count($throughputDeliveryRows); ?> sample terbaru</span>
    </div>

    <div class="row throughput-analytics-grid">
        <div class="col-12">
            <div class="card throughput-analytics-card">
                <div class="card-header py-3 d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <h6 class="m-0 font-weight-bold">Data Delivery Ratio and Data Loss</h6>
                        <small class="text-muted">Perbandingan PDR dan data loss untuk sample throughput terbaru</small>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm chart-download-btn" data-chart-target="throughputDeliveryLossChart">
                        <i class="fas fa-download"></i> PNG
                    </button>
                </div>
                <div class="card-body">
                    <div class="chart-container throughput-chart-container">
                        <canvas id="throughputDeliveryLossChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card throughput-analytics-card">
                <div class="card-header py-3 d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <h6 class="m-0 font-weight-bold">Environmental Throughput Comparison</h6>
                        <small class="text-muted">Rata-rata dan puncak throughput berdasarkan tipe environment</small>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm chart-download-btn" data-chart-target="throughputEnvironmentComparisonChart">
                        <i class="fas fa-download"></i> PNG
                    </button>
                </div>
                <div class="card-body">
                    <div class="chart-container throughput-chart-container">
                        <canvas id="throughputEnvironmentComparisonChart"></canvas>
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

    var deliveryLabels = <?php echo json_encode($deliveryLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var deliveryContexts = <?php echo json_encode($deliveryContexts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var deliveryRatioValues = <?php echo json_encode($deliveryRatioValues); ?>;
    var dataLossValues = <?php echo json_encode($dataLossValues); ?>;
    var environmentLabels = <?php echo json_encode($environmentLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var environmentAvgThroughputValues = <?php echo json_encode($environmentAvgThroughputValues); ?>;
    var environmentMaxThroughputValues = <?php echo json_encode($environmentMaxThroughputValues); ?>;
    var environmentDeliveryValues = <?php echo json_encode($environmentDeliveryValues); ?>;
    var environmentLossValues = <?php echo json_encode($environmentLossValues); ?>;
    var environmentSampleCounts = <?php echo json_encode($environmentSampleCounts); ?>;

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

    var throughputEmptyChartPlugin = {
        id: 'throughputEmptyChartPlugin',
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

    function createDeliveryLossChart() {
        var canvas = document.getElementById('throughputDeliveryLossChart');
        if (!canvas) return;

        new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: deliveryLabels,
                datasets: [
                    {
                        label: 'Data Delivery Ratio',
                        data: deliveryRatioValues,
                        backgroundColor: 'rgba(22, 163, 74, 0.76)',
                        borderColor: '#16a34a',
                        borderWidth: 1,
                        borderRadius: 5
                    },
                    {
                        label: 'Data Loss',
                        data: dataLossValues,
                        backgroundColor: 'rgba(220, 38, 38, 0.72)',
                        borderColor: '#dc2626',
                        borderWidth: 1,
                        borderRadius: 5
                    }
                ]
            },
            plugins: [throughputEmptyChartPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    throughputEmptyChartPlugin: {
                        message: 'Belum ada data delivery ratio'
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
                                return deliveryContexts[items[0].dataIndex] || '';
                            },
                            label: function(context) {
                                return context.dataset.label + ': ' + formatValue(context.raw, '%');
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
                        suggestedMax: 100,
                        title: {
                            display: true,
                            text: 'Ratio (%)'
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

    function createEnvironmentComparisonChart() {
        var canvas = document.getElementById('throughputEnvironmentComparisonChart');
        if (!canvas) return;

        new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: environmentLabels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Average Throughput',
                        data: environmentAvgThroughputValues,
                        backgroundColor: 'rgba(37, 99, 235, 0.76)',
                        borderColor: '#2563eb',
                        borderWidth: 1,
                        borderRadius: 6,
                        yAxisID: 'throughput_axis'
                    },
                    {
                        type: 'line',
                        label: 'Max Throughput',
                        data: environmentMaxThroughputValues,
                        borderColor: '#7c3aed',
                        backgroundColor: 'rgba(124, 58, 237, 0.12)',
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        tension: 0.35,
                        fill: false,
                        yAxisID: 'throughput_axis'
                    },
                    {
                        type: 'line',
                        label: 'Avg Delivery Ratio',
                        data: environmentDeliveryValues,
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22, 163, 74, 0.1)',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        tension: 0.35,
                        fill: false,
                        yAxisID: 'ratio_axis'
                    },
                    {
                        type: 'line',
                        label: 'Avg Data Loss',
                        data: environmentLossValues,
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220, 38, 38, 0.1)',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        tension: 0.35,
                        fill: false,
                        yAxisID: 'ratio_axis'
                    }
                ]
            },
            plugins: [throughputEmptyChartPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    throughputEmptyChartPlugin: {
                        message: 'Belum ada data throughput environment'
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
                                return 'Sample: ' + (environmentSampleCounts[items[0].dataIndex] || 0) + ' data';
                            },
                            label: function(context) {
                                var unit = context.dataset.yAxisID === 'ratio_axis' ? '%' : 'kbps';
                                return context.dataset.label + ': ' + formatValue(context.raw, unit);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            maxRotation: 0,
                            autoSkip: true
                        }
                    },
                    throughput_axis: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Throughput (kbps)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + ' kbps';
                            }
                        },
                        grid: { color: 'rgba(15, 23, 42, 0.08)' }
                    },
                    ratio_axis: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        suggestedMax: 100,
                        title: {
                            display: true,
                            text: 'Ratio (%)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }

    createDeliveryLossChart();
    createEnvironmentComparisonChart();
});
</script>
