<?php
if (!function_exists('analysisMetric')) {
    function analysisMetric($sql, $key = 'value') {
        try {
            $row = fetchOne($sql);
            return $row[$key] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
}

$summaryCards = [
    [
        'label' => 'Communication Tests',
        'value' => analysisMetric("SELECT
            (SELECT COUNT(*) FROM connectivity_tests) +
            (SELECT COUNT(*) FROM range_tests) +
            (SELECT COUNT(*) FROM signal_penetration_tests) AS value"),
        'icon' => 'fas fa-link',
        'color' => 'primary',
    ],
    [
        'label' => 'Performance Tests',
        'value' => analysisMetric("SELECT
            (SELECT COUNT(*) FROM latency_tests) +
            (SELECT COUNT(*) FROM throughput_tests) +
            (SELECT COUNT(*) FROM interference_tests) AS value"),
        'icon' => 'fas fa-bolt',
        'color' => 'warning',
    ],
    [
        'label' => 'Device & Topology',
        'value' => analysisMetric("SELECT
            (SELECT COUNT(*) FROM slave_camera_tests) +
            (SELECT COUNT(*) FROM power_consumption_tests) +
            (SELECT COUNT(*) FROM star_topology_tests) +
            (SELECT COUNT(*) FROM mesh_topology_analysis) AS value"),
        'icon' => 'fas fa-sitemap',
        'color' => 'success',
    ],
    [
        'label' => 'Security Tests',
        'value' => analysisMetric("SELECT
            (SELECT COUNT(*) FROM authentication_tests) +
            (SELECT COUNT(*) FROM encryption_tests) AS value"),
        'icon' => 'fas fa-shield-alt',
        'color' => 'danger',
    ],
];

$metrics = [
    ['Metric', 'Value', 'Interpretation'],
    ['Average Latency', number_format((float) analysisMetric("SELECT AVG(latency_ms) AS value FROM latency_tests"), 2) . ' ms', 'Semakin rendah semakin baik untuk kontrol real-time.'],
    ['Average Throughput', number_format((float) analysisMetric("SELECT AVG(throughput_kbps) AS value FROM throughput_tests"), 2) . ' kbps', 'Menunjukkan kapasitas transfer data aktual.'],
    ['Average RSSI', number_format((float) analysisMetric("SELECT AVG(rssi_dbm) AS value FROM connectivity_tests"), 2) . ' dBm', 'Nilai mendekati 0 berarti sinyal lebih kuat.'],
    ['Average Power', number_format((float) analysisMetric("SELECT AVG(power_w) AS value FROM power_consumption_tests"), 2) . ' W', 'Dipakai untuk evaluasi konsumsi daya perangkat.'],
    ['Stable Star Topology', (int) analysisMetric("SELECT COUNT(*) AS value FROM star_topology_tests WHERE topology_status = 'stable'") . ' record', 'Jumlah pengujian topologi star dengan status stabil.'],
    ['Secure Encryption', (int) analysisMetric("SELECT COUNT(*) AS value FROM encryption_tests WHERE encryption_status = 'secure'") . ' record', 'Jumlah pengujian enkripsi yang lolos sniffing dan integrity check.'],
];

$summaryLabels = array_column($summaryCards, 'label');
$summaryValues = array_map(function ($card) {
    return (float) $card['value'];
}, $summaryCards);
$metricLabels = array_column(array_slice($metrics, 1), 0);
$metricValues = array_map(function ($row) {
    return (float) preg_replace('/[^0-9.-]/', '', $row[1]);
}, array_slice($metrics, 1));
?>

<div class="content-section">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-chart-bar"></i> Analysis and Discussion</h4>
            <p class="text-muted mb-0">Ringkasan otomatis dari seluruh data pengujian yang sudah masuk.</p>
        </div>
    </div>

    <div class="row">
        <?php foreach ($summaryCards as $card): ?>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs text-uppercase text-muted mb-1"><?php echo htmlspecialchars($card['label']); ?></div>
                                <div class="h4 mb-0"><?php echo number_format((float) $card['value']); ?></div>
                            </div>
                            <i class="<?php echo $card['icon']; ?> fa-2x text-<?php echo $card['color']; ?>"></i>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="content-section">
    <h4 class="mb-4"><i class="fas fa-chart-line"></i> Grafik Analisis</h4>
    <div class="row">
        <div class="col-xl-6 mb-4">
            <div class="card h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Jumlah Data per Kategori</h6>
                    <button type="button" class="btn btn-outline-primary btn-sm chart-download-btn" data-chart-target="analysisCategoryChart">
                        <i class="fas fa-download"></i> PNG
                    </button>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="analysisCategoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-4">
            <div class="card h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Ringkasan Metrik</h6>
                    <button type="button" class="btn btn-outline-primary btn-sm chart-download-btn" data-chart-target="analysisMetricChart">
                        <i class="fas fa-download"></i> PNG
                    </button>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="analysisMetricChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="content-section">
    <h4 class="mb-4"><i class="fas fa-list-check"></i> Key Metrics</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <?php foreach ($metrics[0] as $heading): ?>
                        <th><?php echo htmlspecialchars($heading); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($metrics, 1) as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row[0]); ?></td>
                        <td><?php echo htmlspecialchars($row[1]); ?></td>
                        <td><?php echo htmlspecialchars($row[2]); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="content-section">
    <h4 class="mb-3"><i class="fas fa-lightbulb"></i> Discussion Notes</h4>
    <p class="text-muted mb-0">
        Halaman ini membaca data dari tabel pengujian yang sudah tersedia. Jika nilai masih 0, berarti data pada modul terkait belum diinput atau seed dummy belum diimport.
    </p>
</div>

<script>
$(function() {
    new Chart(document.getElementById('analysisCategoryChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($summaryLabels); ?>,
            datasets: [{
                label: 'Jumlah Data',
                data: <?php echo json_encode($summaryValues); ?>,
                backgroundColor: ['#1e3c72', '#fd7e14', '#28a745', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    new Chart(document.getElementById('analysisMetricChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($metricLabels); ?>,
            datasets: [{
                label: 'Nilai',
                data: <?php echo json_encode($metricValues); ?>,
                backgroundColor: '#1e3c72'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true } }
        }
    });
});
</script>
