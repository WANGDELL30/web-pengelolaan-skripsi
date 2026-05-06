-- WiFi HaLow Testing System - Dummy Data
-- Untuk testing dan demonstrasi sistem

USE wifi_holow_testing;

-- Insert sample users
INSERT INTO users (username, password, role, full_name, email, notes) VALUES
('admin', '$2y$10$nTEDKXvfEWtCbiXSFHmBNOc6kqfr0fibJTTF47Kwtli4RYexCgDTW', 'admin', 'System Admin', 'admin@wifiholow.test', 'Administrator utama'),
('operator1', '$2y$10$nTEDKXvfEWtCbiXSFHmBNOc6kqfr0fibJTTF47Kwtli4RYexCgDTW', 'operator', 'John Operator', 'john@wifiholow.test', 'Operator lapangan'),
('viewer1', '$2y$10$nTEDKXvfEWtCbiXSFHmBNOc6kqfr0fibJTTF47Kwtli4RYexCgDTW', 'viewer', 'Jane Viewer', 'jane@wifiholow.test', 'User pembaca')
ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    role = VALUES(role),
    full_name = VALUES(full_name),
    email = VALUES(email),
    notes = VALUES(notes);

-- Insert test locations
INSERT INTO test_locations (location_name, location_type, latitude, longitude, description) VALUES
('Lapangan Terbuka A', 'lapangan', -6.2088, 106.8456, 'Area lapangan terbuka untuk pengujian jangkauan'),
('Hangar Utama', 'hangar', -6.2090, 106.8460, 'Bangunan hangar tertutup'),
('Pantai Indah', 'pantai', -6.2100, 106.8470, 'Area pantai dengan kondisi cuaca ekstrem'),
('Base Camp Gunung', 'gunung', -6.2110, 106.8480, 'Area pegunungan dengan elevasi tinggi'),
('Laboratorium Indoor', 'indoor', -6.2080, 106.8440, 'Ruangan indoor terkontrol');

-- Insert devices
INSERT INTO devices (device_id, device_type, device_name, firmware_version, hardware_version, status) VALUES
('NODE-MASTER-01', 'master', 'Master Node Controller', 'v2.1.0', 'HW-1.0', 'active'),
('NODE-SLAVE-01', 'slave', 'Slave Node 1', 'v2.1.0', 'HW-1.0', 'active'),
('NODE-SLAVE-02', 'slave', 'Slave Node 2', 'v2.1.0', 'HW-1.0', 'active'),
('NODE-SLAVE-03', 'slave', 'Slave Node 3', 'v2.0.5', 'HW-0.9', 'maintenance'),
('NODE-SLAVE-04', 'slave', 'Slave Node 4', 'v2.1.0', 'HW-1.0', 'active');

-- Insert connectivity test data
INSERT INTO connectivity_tests (test_date, location_name, environment_type, node_id, node_type,
    connection_status, rssi_dbm, snr_db, packet_sent, packet_received,
    packet_lost, packet_loss_percent, packet_success_rate, test_duration_second, notes) VALUES
('2026-04-20', 'Lapangan Terbuka A', 'lapangan', 'NODE-SLAVE-01', 'slave',
    'connected', -45.2, 25.3, 1000, 998,
    2, 0.2, 99.8, 60, 'Koneksi stabil pada jarak dekat'),
('2026-04-20', 'Lapangan Terbuka A', 'lapangan', 'NODE-SLAVE-02', 'slave',
    'connected', -52.1, 18.7, 1000, 995,
    5, 0.5, 99.5, 60, 'Koneksi baik, sedikit interferensi'),
('2026-04-20', 'Hangar Utama', 'hangar', 'NODE-SLAVE-01', 'slave',
    'intermittent', -68.5, 12.3, 1000, 980,
    20, 2.0, 98.0, 60, 'Intermittent karena struktur bangunan'),
('2026-04-21', 'Pantai Indah', 'pantai', 'NODE-SLAVE-02', 'slave',
    'connected', -48.3, 22.1, 1000, 999,
    1, 0.1, 99.9, 60, 'Koneksi sangat baik di pantai'),
('2026-04-21', 'Base Camp Gunung', 'gunung', 'NODE-SLAVE-03', 'slave',
    'disconnected', -95.2, 5.2, 1000, 500,
    500, 50.0, 50.0, 60, 'Sinyal lemah di area gunung');

-- Insert range test data
INSERT INTO range_tests (test_date, location_name, environment_type, test_point_code,
    direction, coordinate_x_meter, coordinate_y_meter, coordinate_z_meter,
    distance_actual_meter, distance_3d_meter, distance_km, gps_latitude, gps_longitude,
    frequency_mhz, rssi_dbm, snr_db, bitrate_kbps, connection_status, fspl_db, signal_margin,
    receiver_sensitivity_dbm, status_result, photo_video_link, notes) VALUES
('2026-04-15', 'Lapangan Terbuka A', 'lapangan', 'POINT-01',
    'north', 100, 0, 0, 100, 100,
    0.1, -6.2088, 106.8456, 915, -45.2, 25.3, 54000,
    'connected', 71.46, 26.26, -90, 'good', null,
    'Sinyal sangat baik pada jarak 100m'),
('2026-04-15', 'Lapangan Terbuka A', 'lapangan', 'POINT-02',
    'north', 250, 0, 0, 250, 250,
    0.25, -6.2088, 106.8456, 915, -55.8, 18.2, 48000,
    'connected', 81.49, 32.69, -90, 'good', null,
    'Sinyal baik pada jarak 250m'),
('2026-04-15', 'Lapangan Terbuka A', 'lapangan', 'POINT-03',
    'east', 500, 0, 0, 500, 500,
    0.5, -6.2088, 106.8456, 915, -68.9, 12.1, 36000,
    'connected', 88.52, 21.39, -90, 'moderate', null,
    'Sinyal moderat pada jarak 500m'),
('2026-04-15', 'Lapangan Terbuka A', 'lapangan', 'POINT-04',
    'east', 750, 0, 0, 750, 750,
    0.75, -6.2088, 106.8456, 915, -75.2, 8.5, 24000,
    'connected', 92.23, 16.77, -90, 'moderate', null,
    'Sinyal mulai melemah pada jarak 750m'),
('2026-04-15', 'Lapangan Terbuka A', 'lapangan', 'POINT-05',
    'south', 1000, 0, 0, 1000, 1000,
    1.0, -6.2088, 106.8456, 915, -82.1, 5.2, 12000,
    'disconnected', 96.16, 11.84, -90, 'poor', null,
    'Sinyal lemah, mendekati batas maksimal');

-- Insert latency test data
INSERT INTO latency_tests (test_date, location_name, environment_type, node_id,
    distance_meter, trial_number, timestamp_send_ms, timestamp_receive_ms,
    packet_sent, packet_received, network_mode, latency_ms, jitter_ms,
    packet_loss_percent, average_latency, minimum_latency,
    maximum_latency, average_jitter, notes) VALUES
('2026-04-16', 'Lapangan Terbuka A', 'lapangan', 'NODE-SLAVE-01',
    100, 1, 1000000, 100045, 1000, 1000, 'HaLow only', 45.2, 0,
    0, 45.2, 45.2, 45.2, 0, 'Latency sangat rendah jarak dekat'),
('2026-04-16', 'Lapangan Terbuka A', 'lapangan', 'NODE-SLAVE-01',
    250, 1, 2000000, 200067, 1000, 1000, 'HaLow only', 67.3, 22.1,
    0, 45.2, 45.2, 67.3, 22.1, 'Latency meningkat seiring jarak'),
('2026-04-16', 'Lapangan Terbuka A', 'lapangan', 'NODE-SLAVE-01',
    500, 1, 3000000, 300125, 1000, 998, 'HaLow only', 125.5, 58.2,
    0.2, 79.3, 45.2, 125.5, 40.1, 'Packet loss mulai muncul'),
('2026-04-16', 'Lapangan Terbuka A', 'lapangan', 'NODE-SLAVE-01',
    100, 1, 4000000, 400234, 1000, 997, 'HaLow + VSAT', 234.8, 189.7,
    0.3, 105.2, 45.2, 234.8, 92.8, 'Latency tinggi dengan VSAT'),
('2026-04-16', 'Lapangan Terbuka A', 'lapangan', 'NODE-SLAVE-01',
    250, 1, 5000000, 500567, 1000, 995, 'HaLow + VSAT', 567.3, 332.5,
    0.5, 177.5, 45.2, 567.3, 215.5, 'Latency kritis dengan VSAT jarak jauh');

-- Insert throughput test data
INSERT INTO throughput_tests (test_date, location_name, environment_type, node_id,
    distance_meter, data_sent_kb, data_received_kb, transmission_time_second,
    rssi_dbm, snr_db, bitrate_kbps, throughput_kbps, packet_delivery_ratio_percent,
    data_loss_percent, notes) VALUES
('2026-04-17', 'Lapangan Terbuka A', 'lapangan', 'NODE-SLAVE-01',
    100, 10240, 10240, 5, -45.2, 25.3, 54000, 16384,
    100, 0, 'Throughput maksimal jarak dekat'),
('2026-04-17', 'Lapangan Terbuka A', 'lapangan', 'NODE-SLAVE-01',
    250, 10240, 9728, 5, -55.8, 18.2, 48000, 15564.8,
    95, 5, 'Throughput baik dengan sedikit loss'),
('2026-04-17', 'Lapangan Terbuka A', 'lapangan', 'NODE-SLAVE-01',
    500, 10240, 8192, 5, -68.9, 12.1, 36000, 13107.2,
    80, 20, 'Throughput menurun sesuai jarak'),
('2026-04-17', 'Lapangan Terbuka A', 'lapangan', 'NODE-SLAVE-01',
    750, 10240, 6144, 5, -75.2, 8.5, 24000, 9830.4,
    60, 40, 'Throughput kritis jarak jauh'),
('2026-04-17', 'Lapangan Terbuka A', 'lapangan', 'NODE-SLAVE-01',
    1000, 10240, 2048, 5, -82.1, 5.2, 12000, 3276.8,
    20, 80, 'Throughput sangat rendah batas maksimal');

-- Insert power consumption test data
INSERT INTO power_consumption_tests (test_date, device_id, device_type,
    battery_voltage_v, current_a, test_duration_hour, battery_capacity_mah,
    cpu_usage_percent, ram_usage_percent, cpu_temperature_c,
    rssi_dbm, snr_db, power_w, energy_wh, battery_capacity_wh,
    estimated_runtime_hour, estimated_runtime_day, notes) VALUES
('2026-04-18', 'NODE-MASTER-01', 'master', 12.6, 2.5, 2, 10000,
    45, 60, 55, -45.2, 25.3, 31.5, 63, 126,
    4.00, 0.17, 'Master node power consumption normal'),
('2026-04-18', 'NODE-SLAVE-01', 'slave', 7.4, 1.2, 2, 5000,
    35, 45, 48, -52.1, 18.2, 8.88, 17.76, 37,
    4.17, 0.17, 'Slave node power consumption good'),
('2026-04-18', 'NODE-SLAVE-02', 'slave', 7.4, 1.5, 2, 5000,
    40, 50, 52, -55.8, 18.7, 11.1, 22.2, 37,
    3.33, 0.14, 'Higher power under load'),
('2026-04-18', 'NODE-SLAVE-03', 'slave', 6.8, 1.8, 2, 4500,
    50, 65, 65, -68.9, 12.1, 12.24, 24.48, 30.6,
    2.50, 0.10, 'Degraded battery performance'),
('2026-04-18', 'NODE-SLAVE-04', 'slave', 7.4, 0.8, 24, 10000,
    20, 30, 42, -48.3, 22.1, 5.92, 142.08, 74,
    24.00, 1.00, 'Low power mode for 24h patrol');

-- Insert star topology test data
INSERT INTO star_topology_tests (test_date, location_name, master_id,
    total_slave_nodes, active_slave_nodes, distance_average_meter,
    average_latency_ms, average_throughput_kbps, packet_sent,
    packet_received, packet_loss_percent, node_success_rate,
    gateway_cpu_usage_percent, gateway_temperature_c, topology_status,
    notes) VALUES
('2026-04-19', 'Lapangan Terbuka A', 'NODE-MASTER-01',
    4, 4, 250, 85.2, 42000, 4000, 3980, 0.5,
    100, 65, 58, 'stable',
    'All 4 slave nodes connected successfully'),
('2026-04-19', 'Lapangan Terbuka A', 'NODE-MASTER-01',
    4, 3, 500, 125.5, 36000, 3000, 2940, 2.0,
    75, 70, 62, 'degraded',
    'One slave node disconnected'),
('2026-04-19', 'Lapangan Terbuka A', 'NODE-MASTER-01',
    4, 2, 750, 185.3, 28000, 2000, 1900, 5.0,
    50, 75, 68, 'critical',
    'Multiple nodes disconnected, topology degraded'),
('2026-04-19', 'Lapangan Terbuka A', 'NODE-MASTER-01',
    4, 4, 300, 95.8, 45000, 5000, 4985, 0.3,
    100, 55, 48, 'stable',
    'Stable operation with 4 nodes'),
('2026-04-19', 'Lapangan Terbuka A', 'NODE-MASTER-01',
    4, 1, 1000, 250.0, 12000, 1000, 500, 50.0,
    25, 80, 72, 'critical',
    'Critical state - only 1 node available');

-- Insert mesh topology analysis data
INSERT INTO mesh_topology_analysis (analysis_date, scenario_name,
    total_nodes, hop_count, estimated_latency_per_hop_ms,
    estimated_power_per_node_w, estimated_throughput_kbps,
    reliability_score_percent, total_estimated_latency,
    total_estimated_power, efficiency_score, notes) VALUES
('2026-04-20', 'Small Mesh Network', 5, 2, 15.5, 8.5, 45000,
    95, 31.0, 42.5, 2235.29, 'Small tactical mesh network'),
('2026-04-20', 'Medium Mesh Network', 10, 3, 18.2, 8.5, 38000,
    90, 54.6, 85.0, 1058.82, 'Medium tactical mesh network'),
('2026-04-20', 'Large Mesh Network', 15, 4, 20.1, 8.5, 32000,
    85, 80.4, 127.5, 666.67, 'Large tactical mesh network'),
('2026-04-20', 'Dense Mesh Network', 20, 5, 22.3, 8.5, 28000,
    80, 111.5, 170.0, 470.59, 'Dense tactical mesh network'),
('2026-04-20', 'VSAT Hybrid Mesh', 8, 3, 25.8, 12.5, 15000,
    75, 77.4, 100.0, 112.50, 'Hybrid mesh with VSAT integration');

-- Insert data monitoring data
INSERT INTO data_monitoring (test_date, node_id, timestamp_ms,
    battery_percent, voltage_v, current_a, temperature_c,
    rssi_dbm, snr_db, gps_latitude, gps_longitude,
    status_connection, alert_status, power_w, status_category, notes) VALUES
('2026-04-21', 'NODE-SLAVE-01', 1000000, 85, 7.2, 1.1,
    45, -48.3, 22.1, -6.2088, 106.8456,
    'connected', 'normal', 7.92, 'normal', 'Normal operation'),
('2026-04-21', 'NODE-SLAVE-01', 1060000, 84, 7.1, 1.2,
    46, -49.1, 21.8, -6.2089, 106.8457,
    'connected', 'normal', 8.52, 'normal', 'Normal operation'),
('2026-04-21', 'NODE-SLAVE-01', 1120000, 83, 7.0, 1.3,
    47, -50.2, 20.5, -6.2090, 106.8458,
    'connected', 'warning', 9.10, 'warning', 'Battery decreasing'),
('2026-04-21', 'NODE-SLAVE-02', 1000000, 92, 7.3, 0.9,
    42, -45.2, 25.3, -6.2091, 106.8459,
    'connected', 'normal', 6.57, 'normal', 'Excellent condition'),
('2026-04-21', 'NODE-SLAVE-02', 1060000, 91, 7.2, 1.0,
    43, -46.1, 24.8, -6.2092, 106.8460,
    'connected', 'normal', 7.20, 'normal', 'Excellent condition');

-- Insert monitoring delay test data
INSERT INTO monitoring_delay_tests (test_date, event_name, node_id,
    timestamp_event_generated_ms, timestamp_displayed_dashboard_ms,
    network_mode, monitoring_delay_ms, average_monitoring_delay,
    delay_status, notes) VALUES
('2026-04-22', 'Motion Detected', 'NODE-SLAVE-01',
    1000000, 1000450, 'HaLow only', 450, 450,
    'fast', 'Fast real-time monitoring'),
('2026-04-22', 'Signal Loss', 'NODE-SLAVE-02',
    2000000, 2002340, 'HaLow only', 2340, 1395,
    'acceptable', 'Acceptable delay'),
('2026-04-22', 'Temperature Alert', 'NODE-SLAVE-03',
    3000000, 3008900, 'HaLow + VSAT', 8900, 4880,
    'slow', 'High delay with VSAT'),
('2026-04-22', 'Battery Low', 'NODE-SLAVE-01',
    4000000, 4001200, 'HaLow only', 1200, 3700,
    'acceptable', 'Normal delay'),
('2026-04-22', 'Connection Restored', 'NODE-SLAVE-02',
    5000000, 5003400, 'HaLow + VSAT', 3400, 3980,
    'acceptable', 'VSAT delay acceptable');

-- Insert command execution test data
INSERT INTO command_execution_tests (test_date, command_type,
    source, target_node_id, command_sent_time_ms,
    command_received_time_ms, command_executed_time_ms,
    execution_status, command_delivery_delay,
    command_execution_delay, total_command_time,
    command_success_rate, notes) VALUES
('2026-04-23', 'reset', 'dashboard', 'NODE-SLAVE-01',
    1000000, 1000230, 1001450, 'success', 230, 1220,
    1450, 100, 'Successful reset command'),
('2026-04-23', 'configuration_update', 'dashboard', 'NODE-SLAVE-02',
    2000000, 2000560, 2002340, 'success', 560, 1780,
    2340, 100, 'Configuration updated successfully'),
('2026-04-23', 'turn_off', 'dashboard', 'NODE-SLAVE-03',
    3000000, 3001200, 3001890, 'success', 1200, 690,
    1890, 100, 'Node turned off successfully'),
('2026-04-23', 'restart', 'dashboard', 'NODE-SLAVE-01',
    4000000, 4002500, 4004200, 'success', 2500, 1700,
    4200, 100, 'Node restarted successfully'),
('2026-04-23', 'shutdown', 'dashboard', 'NODE-MASTER-01',
    5000000, 5000890, 5001560, 'success', 890, 670,
    1560, 100, 'Master node shutdown');

-- Insert response time test data
INSERT INTO response_time_tests (test_date, command_type,
    target_node_id, request_time_ms, response_time_ms,
    network_mode, status, response_time_total_ms,
    average_response_time, minimum_response_time,
    maximum_response_time, notes) VALUES
('2026-04-24', 'status_check', 'NODE-SLAVE-01',
    1000000, 1000234, 'HaLow only', 'success', 234,
    245, 234, 256, 'Fast response time'),
('2026-04-24', 'data_request', 'NODE-SLAVE-02',
    2000000, 2000567, 'HaLow only', 'success', 567,
    450, 234, 567, 'Good response time'),
('2026-04-24', 'configuration_read', 'NODE-SLAVE-01',
    3000000, 3000890, 'HaLow only', 'success', 890,
    564, 234, 890, 'Moderate response'),
('2026-04-24', 'status_check', 'NODE-SLAVE-03',
    4000000, 4002340, 'HaLow + VSAT', 'success', 2340,
    1780, 234, 2340, 'High latency with VSAT'),
('2026-04-24', 'data_request', 'NODE-SLAVE-02',
    5000000, 5005670, 'HaLow + VSAT', 'success', 5670,
    2450, 234, 5670, 'Very high latency with VSAT');

-- Insert authentication test data
INSERT INTO authentication_tests (test_date, user_role,
    login_attempt_type, authentication_method,
    attempt_count, success_count, failed_count,
    authentication_success_rate, authentication_failure_rate, notes) VALUES
('2026-04-25', 'operator', 'valid_user', 'password',
    100, 100, 0, 100, 0, 'All valid users authenticated'),
('2026-04-25', 'operator', 'invalid_user', 'password',
    50, 0, 50, 0, 100, 'All invalid users rejected'),
('2026-04-25', 'admin', 'wrong_password', 'password',
    20, 0, 20, 0, 100, 'Failed login attempts blocked'),
('2026-04-25', 'viewer', 'valid_user', '2fa',
    75, 75, 0, 100, 0, '2FA authentication successful'),
('2026-04-25', 'operator', 'unauthorized_access', 'none',
    10, 0, 10, 0, 100, 'Unauthorized access attempts blocked');

-- Insert encryption test data
INSERT INTO encryption_tests (test_date, protocol_used,
    encryption_type, key_length_bit, sniffing_test_result,
    data_integrity_status, encryption_status, notes) VALUES
('2026-04-26', 'MQTT TLS', 'AES-256', 256, 'unreadable',
    'valid', 'secure', 'MQTT with TLS encryption secure'),
('2026-04-26', 'SNMPv3', 'AES-128', 128, 'unreadable',
    'valid', 'secure', 'SNMPv3 encryption secure'),
('2026-04-26', 'WPA3', 'AES-256', 256, 'unreadable',
    'valid', 'secure', 'WPA3 encryption very secure'),
('2026-04-26', 'Custom Protocol', 'AES-192', 192, 'readable',
    'invalid', 'insecure', 'Custom encryption needs improvement'),
('2026-04-26', 'MQTT TLS', 'AES-256', 256, 'unreadable',
    'valid', 'secure', 'End-to-end encryption verified');

-- Insert analysis reports placeholder
INSERT INTO generated_reports (report_title, report_type,
    date_range_start, date_range_end, location_filter,
    test_type_filter, content, file_path, file_type,
    generated_by, created_at) VALUES
('Weekly Connectivity Test Report', 'connectivity',
    '2026-04-15', '2026-04-21', 'Lapangan Terbuka A',
    'connectivity_tests', 'Weekly connectivity test analysis report',
    '/reports/weekly_connectivity_20260421.pdf', 'pdf',
    1, NOW()),
('Range Test Analysis Report', 'range',
    '2026-04-15', '2026-04-15', 'Lapangan Terbuka A',
    'range_tests', 'Range test distance analysis',
    '/reports/range_test_20260415.pdf', 'pdf',
    1, NOW()),
('Power Consumption Weekly Report', 'power',
    '2026-04-18', '2026-04-24', 'All Locations',
    'power_consumption_tests', 'Weekly power analysis',
    '/reports/power_weekly_20260424.csv', 'csv',
    1, NOW());

-- Show inserted records
SELECT '=== USERS ===' as '';
SELECT * FROM users;

SELECT '=== CONNECTIVITY TESTS ===' as '';
SELECT COUNT(*) as total_records FROM connectivity_tests;

SELECT '=== RANGE TESTS ===' as '';
SELECT COUNT(*) as total_records FROM range_tests;

SELECT '=== DUMMY DATA LOADED SUCCESSFULLY ===' as '';
SELECT 'Total tables populated: 11' as status;
SELECT 'Records inserted: 65+' as count;
