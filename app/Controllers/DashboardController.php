<?php
/**
 * Dashboard Controller for WiFi HaLow Testing System
 */

require_once __DIR__ . '/../Helpers/functions.php';

class DashboardController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get dashboard statistics
     */
    public function getStats() {
        $stats = [];
        
        // Total connectivity tests
        $result = fetchOne("SELECT COUNT(*) as total FROM connectivity_tests");
        $stats['total_connectivity'] = $result['total'];
        
        // Total range tests
        $result = fetchOne("SELECT COUNT(*) as total FROM range_tests");
        $stats['total_range'] = $result['total'];
        
        // Total unique locations
        $result = fetchOne("SELECT COUNT(DISTINCT location_name) as total FROM (
            SELECT location_name FROM test_locations
            UNION
            SELECT location_name FROM connectivity_tests
            UNION
            SELECT location_name FROM range_tests
            UNION
            SELECT location_name FROM signal_penetration_tests
            UNION
            SELECT location_name FROM latency_tests
            UNION
            SELECT location_name FROM throughput_tests
            UNION
            SELECT location_name FROM interference_tests
            UNION
            SELECT location_name FROM slave_camera_tests
        ) as all_locations
        WHERE location_name IS NOT NULL AND location_name != ''");
        $stats['total_locations'] = $result['total'];
        
        // Total devices
        $result = fetchOne("SELECT COUNT(DISTINCT node_id) as total FROM (
            SELECT node_id FROM connectivity_tests
            UNION
            SELECT node_id FROM latency_tests
            UNION
            SELECT node_id FROM throughput_tests
            UNION
            SELECT node_id FROM slave_camera_tests
            UNION
            SELECT target_node_id as node_id FROM command_execution_tests
            UNION
            SELECT target_node_id as node_id FROM response_time_tests
        ) as all_nodes");
        $stats['total_nodes'] = $result['total'];
        
        // Average latency
        $result = fetchOne("SELECT AVG(latency_ms) as avg FROM latency_tests WHERE latency_ms IS NOT NULL");
        $stats['avg_latency'] = $result['avg'] !== null ? round($result['avg'], 2) : null;
        
        // Average throughput
        $result = fetchOne("SELECT AVG(throughput_kbps) as avg FROM throughput_tests WHERE throughput_kbps IS NOT NULL");
        $stats['avg_throughput'] = $result['avg'] !== null ? round($result['avg'], 2) : null;
        
        // Average RSSI
        $result = fetchOne("SELECT AVG(rssi_dbm) as avg FROM (
            SELECT rssi_dbm FROM connectivity_tests
            UNION ALL
            SELECT rssi_dbm FROM range_tests
            UNION ALL
            SELECT rssi_before_dbm as rssi_dbm FROM signal_penetration_tests
        ) as all_rssi WHERE rssi_dbm IS NOT NULL");
        $stats['avg_rssi'] = $result['avg'] !== null ? round($result['avg'], 2) : null;
        
        // Average SNR
        $result = fetchOne("SELECT AVG(snr_db) as avg FROM (
            SELECT snr_db FROM connectivity_tests
            UNION ALL
            SELECT snr_db FROM range_tests
            UNION ALL
            SELECT snr_before_db as snr_db FROM signal_penetration_tests
        ) as all_snr WHERE snr_db IS NOT NULL");
        $stats['avg_snr'] = $result['avg'] !== null ? round($result['avg'], 2) : null;
        
        // Average packet loss
        $result = fetchOne("SELECT AVG(packet_loss_percent) as avg FROM (
            SELECT packet_loss_percent FROM connectivity_tests
            UNION ALL
            SELECT packet_loss_percent FROM latency_tests
            UNION ALL
            SELECT data_loss_percent as packet_loss_percent FROM throughput_tests
            UNION ALL
            SELECT packet_loss_percent FROM interference_tests
        ) as all_loss WHERE packet_loss_percent IS NOT NULL");
        $stats['avg_packet_loss'] = $result['avg'] !== null ? round($result['avg'], 2) : null;
        
        // Average power consumption
        $result = fetchOne("SELECT AVG(power_w) as avg FROM power_consumption_tests WHERE power_w IS NOT NULL");
        $stats['avg_power'] = $result['avg'] !== null ? round($result['avg'], 2) : null;
        
        // Determine system status
        $stats['system_status'] = determineSystemStatus($stats);
        
        return $stats;
    }
    
    /**
     * Get recent test data
     */
    public function getRecentTests() {
        $tests = [];
        
        // Get recent connectivity tests
        $tests['connectivity'] = fetchAll("SELECT * FROM connectivity_tests ORDER BY test_date DESC, created_at DESC LIMIT 5");
        
        // Get recent range tests
        $tests['range'] = fetchAll("SELECT * FROM range_tests ORDER BY test_date DESC, created_at DESC LIMIT 5");
        
        // Get recent latency tests
        $tests['latency'] = fetchAll("SELECT * FROM latency_tests ORDER BY test_date DESC, created_at DESC LIMIT 5");
        
        return $tests;
    }
    
    /**
     * Get chart data
     */
    public function getChartData() {
        $data = [];
        
        // Distance vs RSSI (from range tests)
        $data['distance_rssi'] = fetchAll("
            SELECT distance_actual_meter, rssi_dbm, location_name, test_point_code, status_result, test_date
            FROM range_tests
            WHERE distance_actual_meter IS NOT NULL AND rssi_dbm IS NOT NULL
            ORDER BY distance_actual_meter
            LIMIT 30
        ");
        
        // Distance vs SNR
        $data['distance_snr'] = fetchAll("
            SELECT distance_actual_meter, snr_db, location_name, test_point_code, status_result, test_date
            FROM range_tests
            WHERE distance_actual_meter IS NOT NULL AND snr_db IS NOT NULL
            ORDER BY distance_actual_meter
            LIMIT 30
        ");
        
        // Distance vs Bitrate
        $data['distance_bitrate'] = fetchAll("
            SELECT distance_actual_meter, bitrate_kbps, location_name, test_point_code, status_result, test_date
            FROM range_tests
            WHERE distance_actual_meter IS NOT NULL AND bitrate_kbps IS NOT NULL
            ORDER BY distance_actual_meter
            LIMIT 30
        ");
        
        // Distance vs Latency
        $data['distance_latency'] = fetchAll("
            SELECT
                distance_meter,
                AVG(latency_ms) as avg_latency,
                AVG(jitter_ms) as avg_jitter,
                AVG(packet_loss_percent) as avg_packet_loss,
                COUNT(*) as total_tests,
                MIN(test_date) as first_test_date,
                MAX(test_date) as last_test_date
            FROM latency_tests
            WHERE distance_meter IS NOT NULL AND latency_ms IS NOT NULL
            GROUP BY distance_meter
            ORDER BY distance_meter
            LIMIT 30
        ");
        
        // Distance vs Throughput
        $data['distance_throughput'] = fetchAll("
            SELECT
                distance_meter,
                AVG(throughput_kbps) as avg_throughput,
                AVG(packet_delivery_ratio_percent) as avg_pdr,
                AVG(data_loss_percent) as avg_data_loss,
                COUNT(*) as total_tests,
                MIN(test_date) as first_test_date,
                MAX(test_date) as last_test_date
            FROM throughput_tests
            WHERE distance_meter IS NOT NULL AND throughput_kbps IS NOT NULL
            GROUP BY distance_meter
            ORDER BY distance_meter
            LIMIT 30
        ");
        
        return $data;
    }
}

// Initialize controller if needed
if (!isset($dashboardController)) {
    $dashboardController = new DashboardController($pdo);
}
