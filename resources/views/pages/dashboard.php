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
    <div class="col-xl-6 col-md-12 mb-4">
        <div class="card h-100">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Distance vs RSSI Analysis</h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="distanceRssiChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6 col-md-12 mb-4">
        <div class="card h-100">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Distance vs SNR Analysis</h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="distanceSnrChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-6 col-md-12 mb-4">
        <div class="card h-100">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Distance vs Bitrate</h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="distanceBitrateChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6 col-md-12 mb-4">
        <div class="card h-100">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Distance vs Latency</h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="distanceLatencyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-6 col-md-12 mb-4">
        <div class="card h-100">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Distance vs Throughput</h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="distanceThroughputChart"></canvas>
                </div>
            </div>
        </div>
    </div>
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
// Distance vs RSSI Chart
var ctxRssi = document.getElementById('distanceRssiChart').getContext('2d');
var distanceRssiChart = new Chart(ctxRssi, {
    type: 'scatter',
    data: {
        datasets: [{
            label: 'RSSI vs Distance',
            data: [
                <?php foreach ($chartData['distance_rssi'] as $point): ?>
                {x: <?php echo $point['distance_actual_meter']; ?>, y: <?php echo $point['rssi_dbm']; ?>},
                <?php endforeach; ?>
            ],
            backgroundColor: 'rgba(0, 123, 255, 0.8)',
            borderColor: 'rgba(0, 123, 255, 1)',
            borderWidth: 1,
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                type: 'linear',
                position: 'bottom',
                title: {
                    display: true,
                    text: 'Distance (meters)'
                }
            },
            y: {
                title: {
                    display: true,
                    text: 'RSSI (dBm)'
                }
            }
        },
        plugins: {
            legend: {
                display: true
            }
        }
    }
});

// Distance vs SNR Chart
var ctxSnr = document.getElementById('distanceSnrChart').getContext('2d');
var distanceSnrChart = new Chart(ctxSnr, {
    type: 'scatter',
    data: {
        datasets: [{
            label: 'SNR vs Distance',
            data: [
                <?php foreach ($chartData['distance_snr'] as $point): ?>
                {x: <?php echo $point['distance_actual_meter']; ?>, y: <?php echo $point['snr_db']; ?>},
                <?php endforeach; ?>
            ],
            backgroundColor: 'rgba(40, 167, 69, 0.8)',
            borderColor: 'rgba(40, 167, 69, 1)',
            borderWidth: 1,
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                type: 'linear',
                position: 'bottom',
                title: {
                    display: true,
                    text: 'Distance (meters)'
                }
            },
            y: {
                title: {
                    display: true,
                    text: 'SNR (dB)'
                }
            }
        },
        plugins: {
            legend: {
                display: true
            }
        }
    }
});

// Distance vs Bitrate Chart
var ctxBitrate = document.getElementById('distanceBitrateChart').getContext('2d');
var distanceBitrateChart = new Chart(ctxBitrate, {
    type: 'scatter',
    data: {
        datasets: [{
            label: 'Bitrate vs Distance',
            data: [
                <?php foreach ($chartData['distance_bitrate'] as $point): ?>
                {x: <?php echo $point['distance_actual_meter']; ?>, y: <?php echo $point['bitrate_kbps']; ?>},
                <?php endforeach; ?>
            ],
            backgroundColor: 'rgba(255, 193, 7, 0.8)',
            borderColor: 'rgba(255, 193, 7, 1)',
            borderWidth: 1,
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                type: 'linear',
                position: 'bottom',
                title: {
                    display: true,
                    text: 'Distance (meters)'
                }
            },
            y: {
                title: {
                    display: true,
                    text: 'Bitrate (kbps)'
                }
            }
        },
        plugins: {
            legend: {
                display: true
            }
        }
    }
});

// Distance vs Latency Chart
var ctxLatency = document.getElementById('distanceLatencyChart').getContext('2d');
var distanceLatencyChart = new Chart(ctxLatency, {
    type: 'scatter',
    data: {
        datasets: [{
            label: 'Latency vs Distance',
            data: [
                <?php foreach ($chartData['distance_latency'] as $point): ?>
                {x: <?php echo $point['distance_meter']; ?>, y: <?php echo $point['avg_latency']; ?>},
                <?php endforeach; ?>
            ],
            backgroundColor: 'rgba(220, 53, 69, 0.8)',
            borderColor: 'rgba(220, 53, 69, 1)',
            borderWidth: 1,
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                type: 'linear',
                position: 'bottom',
                title: {
                    display: true,
                    text: 'Distance (meters)'
                }
            },
            y: {
                title: {
                    display: true,
                    text: 'Latency (ms)'
                }
            }
        },
        plugins: {
            legend: {
                display: true
            }
        }
    }
});

// Distance vs Throughput Chart
var ctxThroughput = document.getElementById('distanceThroughputChart').getContext('2d');
var distanceThroughputChart = new Chart(ctxThroughput, {
    type: 'scatter',
    data: {
        datasets: [{
            label: 'Throughput vs Distance',
            data: [
                <?php foreach ($chartData['distance_throughput'] as $point): ?>
                {x: <?php echo $point['distance_meter']; ?>, y: <?php echo $point['avg_throughput']; ?>},
                <?php endforeach; ?>
            ],
            backgroundColor: 'rgba(108, 117, 125, 0.8)',
            borderColor: 'rgba(108, 117, 125, 1)',
            borderWidth: 1,
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                type: 'linear',
                position: 'bottom',
                title: {
                    display: true,
                    text: 'Distance (meters)'
                }
            },
            y: {
                title: {
                    display: true,
                    text: 'Throughput (kbps)'
                }
            }
        },
        plugins: {
            legend: {
                display: true
            }
        }
    }
});
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
</style>
