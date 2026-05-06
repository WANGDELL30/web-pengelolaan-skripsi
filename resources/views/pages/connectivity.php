<?php
require_once __DIR__ . '/../../../app/Helpers/functions.php';

// Handle form submission
$success = null;
$error = null;

function connectivityPayload($source) {
    $packetSent = (int) ($source['packet_sent'] ?? 0);
    $packetReceived = (int) ($source['packet_received'] ?? 0);

    return [
        'test_date' => sanitize($source['test_date'] ?? ''),
        'location_name' => sanitize($source['location_name'] ?? ''),
        'environment_type' => sanitize($source['environment_type'] ?? ''),
        'node_id' => sanitize($source['node_id'] ?? ''),
        'node_type' => sanitize($source['node_type'] ?? ''),
        'connection_status' => sanitize($source['connection_status'] ?? ''),
        'rssi_dbm' => (float) ($source['rssi_dbm'] ?? 0),
        'snr_db' => (float) ($source['snr_db'] ?? 0),
        'packet_sent' => $packetSent,
        'packet_received' => $packetReceived,
        'packet_lost' => $packetSent - $packetReceived,
        'packet_loss_percent' => calculatePacketLoss($packetSent, $packetReceived),
        'packet_success_rate' => calculateSuccessRate($packetSent, $packetReceived),
        'test_duration_second' => (int) ($source['test_duration_second'] ?? 0),
        'notes' => sanitize($source['notes'] ?? ''),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_connectivity_action'] ?? 'create';
    $recordId = (int) ($_POST['id'] ?? 0);

    try {
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
                    test_date = ?, location_name = ?, environment_type = ?, node_id = ?, node_type = ?,
                    connection_status = ?, rssi_dbm = ?, snr_db = ?, packet_sent = ?, packet_received = ?,
                    packet_lost = ?, packet_loss_percent = ?, packet_success_rate = ?,
                    test_duration_second = ?, notes = ?
                WHERE id = ?",
                [
                    $data['test_date'], $data['location_name'], $data['environment_type'], $data['node_id'], $data['node_type'],
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
                    test_date, location_name, environment_type, node_id, node_type,
                    connection_status, rssi_dbm, snr_db, packet_sent, packet_received,
                    packet_lost, packet_loss_percent, packet_success_rate,
                    test_duration_second, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['test_date'], $data['location_name'], $data['environment_type'], $data['node_id'], $data['node_type'],
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
$rssiNodeLabels = [];
$rssiNodeValues = [];
$nodes = fetchAll("SELECT DISTINCT node_id FROM connectivity_tests WHERE node_id IS NOT NULL LIMIT 10");
foreach ($nodes as $node) {
    $rssiNodeLabels[] = $node['node_id'];
    $avg = fetchOne("SELECT AVG(rssi_dbm) as avg FROM connectivity_tests WHERE node_id = ?", [$node['node_id']]);
    $rssiNodeValues[] = $avg['avg'] ? round((float) $avg['avg'], 1) : 0;
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
                            <div class="col-md-6 mb-3">
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
                            <div class="col-md-6 mb-3">
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
                                <input type="number" step="0.1" class="form-control" name="rssi_dbm" placeholder="-30 to -90" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">SNR (dB)</label>
                                <input type="number" step="0.1" class="form-control" name="snr_db" placeholder="0 to 50" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Packet Sent</label>
                                <input type="number" class="form-control" name="packet_sent" value="1000" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Packet Received</label>
                                <input type="number" class="form-control" name="packet_received" value="1000" required>
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
                    $avg_rssi = 0;
                    $avg_loss = 0;
                    $connected = 0;
                    foreach ($connectivity_tests as $test) {
                        $avg_rssi += $test['rssi_dbm'];
                        $avg_loss += $test['packet_loss_percent'];
                        if ($test['connection_status'] === 'connected') $connected++;
                    }
                    if ($total_tests > 0) {
                        $avg_rssi = round($avg_rssi / $total_tests, 2);
                        $avg_loss = round($avg_loss / $total_tests, 2);
                    }
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
                                <h3 class="text-warning"><?php echo $avg_rssi; ?></h3>
                                <small>Avg RSSI (dBm)</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="quick-stat-box">
                                <h3 class="text-danger"><?php echo $avg_loss; ?>%</h3>
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-table"></i> Connectivity Test Results</h4>
        <div>
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
                    <th>Node</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>RSSI</th>
                    <th>SNR</th>
                    <th>Packets</th>
                    <th>Success</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($connectivity_tests) > 0): ?>
                    <?php foreach ($connectivity_tests as $test): ?>
                    <tr>
                        <td><?php echo $test['id']; ?></td>
                        <td><?php echo formatDate($test['test_date']); ?></td>
                        <td><?php echo htmlspecialchars($test['location_name']); ?></td>
                        <td><?php echo ucfirst($test['environment_type']); ?></td>
                        <td><?php echo htmlspecialchars($test['node_id']); ?></td>
                        <td><?php echo ucfirst($test['node_type']); ?></td>
                        <td><?php echo getStatusBadge($test['connection_status']); ?></td>
                        <td><?php echo $test['rssi_dbm']; ?> dBm</td>
                        <td><?php echo $test['snr_db']; ?> dB</td>
                        <td><?php echo $test['packet_received']; ?>/<?php echo $test['packet_sent']; ?></td>
                        <td><?php echo number_format($test['packet_success_rate'], 2); ?>%</td>
                        <td class="text-end text-nowrap table-action-buttons">
                            <button class="btn btn-sm btn-outline-primary" onclick="viewConnectivity(<?php echo $test['id']; ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning" onclick="editConnectivity(<?php echo $test['id']; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteConnectivity(<?php echo $test['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
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
<div class="content-section">
    <h4 class="mb-4"><i class="fas fa-chart-bar"></i> Connectivity Analysis</h4>
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h6>Connection Status Distribution</h6>
                    <div class="chart-container connectivity-chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h6>RSSI Distribution by Node</h6>
                    <div class="chart-container connectivity-chart-container">
                        <canvas id="rssiChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
                        <div class="col-md-6 mb-3">
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
                        <div class="col-md-6 mb-3">
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
                            <input type="number" step="0.1" class="form-control" name="rssi_dbm" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SNR (dB)</label>
                            <input type="number" step="0.1" class="form-control" name="snr_db" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Packet Sent</label>
                            <input type="number" class="form-control" name="packet_sent" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Packet Received</label>
                            <input type="number" class="form-control" name="packet_received" required>
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

<script>
var connectivityRows = <?php echo json_encode($connectivityMap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
var connectivityLabels = {
    id: 'ID',
    test_date: 'Test Date',
    location_name: 'Location Name',
    environment_type: 'Environment Type',
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
        return '-';
    }

    return $('<div>').text(value).html();
}

// Initialize DataTable
$(document).ready(function() {
    $('.data-table').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json"
        },
        "order": [[0, 'desc']],
        "columnDefs": [
            { "targets": -1, "orderable": false, "searchable": false }
        ]
    });
});

// Status Chart
var ctxStatus = document.getElementById('statusChart').getContext('2d');
var statusChart = new Chart(ctxStatus, {
    type: 'doughnut',
    data: {
        labels: ['Connected', 'Disconnected', 'Intermittent'],
        datasets: [{
            data: <?php echo json_encode($statusCounts); ?>,
            backgroundColor: [
                'rgba(40, 167, 69, 0.8)',
                'rgba(220, 53, 69, 0.8)',
                'rgba(255, 193, 7, 0.8)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// RSSI Chart
var ctxRssi = document.getElementById('rssiChart').getContext('2d');
var rssiChart = new Chart(ctxRssi, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($rssiNodeLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
        datasets: [{
            label: 'Average RSSI (dBm)',
            data: <?php echo json_encode($rssiNodeValues); ?>,
            backgroundColor: 'rgba(0, 123, 255, 0.7)',
            borderColor: 'rgba(0, 123, 255, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: false
            }
        }
    }
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

function editConnectivity(id) {
    var row = connectivityRow(id);
    if (!row) return;

    var form = $('#connectivityEditForm');
    $('#connectivityEditId').val(row.id);
    [
        'test_date',
        'location_name',
        'environment_type',
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
.connectivity-chart-container {
    height: 320px;
    min-height: 320px;
}
</style>
