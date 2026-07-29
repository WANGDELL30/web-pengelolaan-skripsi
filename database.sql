-- WiFi HaLow Testing System - Database Schema
-- Design and Implementation of a Wi-Fi HaLow-Based Tactical Monitoring and Communication Support System

-- Create database
CREATE DATABASE IF NOT EXISTS wifi_holow_testing;
USE wifi_holow_testing;

-- Users table
-- Role: 'admin' = akses penuh | 'viewer' = read-only
-- Catatan migrasi: jika database sudah ada, jalankan:
--   ALTER TABLE users MODIFY COLUMN role ENUM('admin','viewer') DEFAULT 'viewer';
--   UPDATE users SET role = 'viewer' WHERE role = 'operator';
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'viewer') DEFAULT 'viewer',
    full_name VARCHAR(100),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    notes TEXT
);

-- Default accounts:
-- Admin: admin / admin123
-- Viewer: viewer / admin123
INSERT INTO users (username, password, role, full_name, email, notes) VALUES
('admin', '$2y$10$nTEDKXvfEWtCbiXSFHmBNOc6kqfr0fibJTTF47Kwtli4RYexCgDTW', 'admin', 'System Admin', 'admin@wifiholow.test', 'Administrator utama'),
('viewer', '$2y$10$nTEDKXvfEWtCbiXSFHmBNOc6kqfr0fibJTTF47Kwtli4RYexCgDTW', 'viewer', 'Read Only Viewer', 'viewer@wifiholow.test', 'User viewer read-only')
ON DUPLICATE KEY UPDATE username = VALUES(username);

-- Application settings. adminpsn.local follows the master's DHCP address
-- through mDNS, while the dashboard also allows an admin to enter a new IP.
CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO app_settings (setting_key, setting_value)
VALUES ('master_host', 'adminpsn.local')
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);

-- Test locations table
CREATE TABLE test_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(100) NOT NULL,
    location_type ENUM('lapangan', 'hangar', 'pantai', 'gunung', 'indoor', 'outdoor'),
    latitude DECIMAL(17, 14),
    longitude DECIMAL(18, 14),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    notes TEXT
);

-- Devices table
CREATE TABLE devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(50) NOT NULL UNIQUE,
    device_type ENUM('master', 'slave') NOT NULL,
    device_name VARCHAR(100),
    firmware_version VARCHAR(20),
    hardware_version VARCHAR(20),
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    notes TEXT
);

-- Connectivity Tests table
CREATE TABLE connectivity_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_date DATE NOT NULL,
    location_name VARCHAR(100),
    environment_type ENUM('lapangan', 'hangar', 'pantai', 'gunung', 'indoor', 'outdoor'),
    node_id VARCHAR(50),
    node_type ENUM('master', 'slave'),
    connection_status ENUM('connected', 'disconnected', 'intermittent'),
    rssi_dbm DECIMAL(6,2),
    snr_db DECIMAL(6,2),
    packet_sent INT DEFAULT 0,
    packet_received INT DEFAULT 0,
    packet_lost INT DEFAULT 0,
    packet_loss_percent DECIMAL(5,2) DEFAULT 0,
    packet_success_rate DECIMAL(5,2) DEFAULT 0,
    test_duration_second INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Range Tests table
CREATE TABLE range_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_date DATE NOT NULL,
    location_name VARCHAR(100),
    environment_type ENUM('lapangan', 'hangar', 'pantai', 'gunung', 'indoor', 'outdoor'),
    test_point_code VARCHAR(50),
    direction ENUM('north', 'south', 'east', 'west', 'vertical', 'diagonal'),
    coordinate_x_meter DECIMAL(10,2),
    coordinate_y_meter DECIMAL(10,2),
    coordinate_z_meter DECIMAL(10,2),
    distance_actual_meter DECIMAL(10,2),
    distance_3d_meter DECIMAL(10,2),
    distance_km DECIMAL(10,4),
    master_gps_latitude DECIMAL(17, 14),
    master_gps_longitude DECIMAL(18, 14),
    gps_latitude DECIMAL(17, 14),
    gps_longitude DECIMAL(18, 14),
    frequency_mhz DECIMAL(6,2) DEFAULT 915,
    rssi_dbm DECIMAL(6,2),
    snr_db DECIMAL(6,2),
    bitrate_kbps DECIMAL(10,2),
    connection_status VARCHAR(20),
    fspl_db DECIMAL(6,2),
    signal_margin DECIMAL(6,2),
    receiver_sensitivity_dbm DECIMAL(6,2) DEFAULT -90,
    status_result ENUM('good', 'moderate', 'poor'),
    photo_video_link VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Signal Penetration Tests table
CREATE TABLE signal_penetration_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_date DATE NOT NULL,
    location_name VARCHAR(100),
    obstacle_type ENUM('wall', 'building', 'trees', 'vehicle', 'hangar', 'hill', 'none'),
    condition_type ENUM('LOS', 'NLOS'),
    distance_meter DECIMAL(10,2),
    rssi_before_dbm DECIMAL(6,2),
    rssi_after_dbm DECIMAL(6,2),
    snr_before_db DECIMAL(6,2),
    snr_after_db DECIMAL(6,2),
    packet_sent INT DEFAULT 0,
    packet_received INT DEFAULT 0,
    bitrate_kbps DECIMAL(10,2),
    rssi_loss DECIMAL(6,2),
    snr_loss DECIMAL(6,2),
    packet_loss_percent DECIMAL(5,2),
    penetration_loss_db DECIMAL(6,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Latency Tests table
CREATE TABLE latency_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_date DATE NOT NULL,
    location_name VARCHAR(100),
    environment_type ENUM('lapangan', 'hangar', 'pantai', 'gunung', 'indoor', 'outdoor'),
    node_id VARCHAR(50),
    distance_meter DECIMAL(10,2),
    trial_number INT,
    timestamp_send_ms BIGINT,
    timestamp_receive_ms BIGINT,
    packet_sent INT DEFAULT 0,
    packet_received INT DEFAULT 0,
    network_mode ENUM('HaLow only', 'HaLow + VSAT'),
    latency_ms DECIMAL(10,2),
    jitter_ms DECIMAL(10,2),
    packet_loss_percent DECIMAL(5,2),
    average_latency DECIMAL(10,2),
    minimum_latency DECIMAL(10,2),
    maximum_latency DECIMAL(10,2),
    average_jitter DECIMAL(10,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Throughput Tests table
CREATE TABLE throughput_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_date DATE NOT NULL,
    location_name VARCHAR(100),
    environment_type ENUM('lapangan', 'hangar', 'pantai', 'gunung', 'indoor', 'outdoor'),
    node_id VARCHAR(50),
    distance_meter DECIMAL(10,2),
    data_sent_kb DECIMAL(10,2),
    data_received_kb DECIMAL(10,2),
    transmission_time_second DECIMAL(10,2),
    rssi_dbm DECIMAL(6,2),
    snr_db DECIMAL(6,2),
    bitrate_kbps DECIMAL(10,2),
    throughput_kbps DECIMAL(10,2),
    packet_delivery_ratio_percent DECIMAL(5,2),
    data_loss_percent DECIMAL(5,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Satellite / VSAT End-to-End Tests table
CREATE TABLE satellite_vsat_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_date DATE NOT NULL,
    test_session_code VARCHAR(80) NOT NULL,
    planned_trials TINYINT UNSIGNED NOT NULL DEFAULT 3,
    trial_number TINYINT UNSIGNED NOT NULL DEFAULT 1,
    location_name VARCHAR(100),
    test_operator VARCHAR(100),
    weather_condition ENUM('cerah', 'berawan', 'hujan_ringan', 'hujan_lebat'),
    node_id VARCHAR(50) NOT NULL DEFAULT 'MASTER-VSAT',
    connection_mode ENUM('WiFi AP + VSAT', 'Ethernet + VSAT'),
    access_point_ssid VARCHAR(100),
    ip_assignment ENUM('DHCP', 'Static') DEFAULT 'DHCP',
    master_ip VARCHAR(45),
    gateway_ip VARCHAR(45) NOT NULL,
    wan_ip VARCHAR(45),
    vsat_provider VARCHAR(100),
    satellite_name VARCHAR(100),
    signal_quality_factor SMALLINT UNSIGNED,
    gateway_ping_status ENUM('success', 'fail') NOT NULL,
    server_target VARCHAR(150) NOT NULL,
    internet_ping_status ENUM('success', 'fail') NOT NULL,
    packet_sent INT NOT NULL DEFAULT 10,
    packet_received INT NOT NULL DEFAULT 10,
    latency_min_ms DECIMAL(10,3),
    latency_ms DECIMAL(10,3),
    latency_max_ms DECIMAL(10,3),
    jitter_ms DECIMAL(10,2),
    packet_loss_percent DECIMAL(5,2),
    download_kbps DECIMAL(12,2),
    upload_kbps DECIMAL(12,2),
    wifi_rssi_dbm DECIMAL(6,2),
    wifi_snr_db DECIMAL(6,2),
    vsat_lock_status ENUM('locked', 'unlocked', 'not_checked') NOT NULL,
    association_status ENUM('associated', 'not_associated', 'not_checked'),
    tdma_status ENUM('active', 'inactive', 'not_checked'),
    association_time DATETIME,
    rx_signal_type ENUM('SNR', 'C/N', 'Eb/N0'),
    rx_signal_db DECIMAL(8,2),
    tx_power_dbm DECIMAL(8,2),
    modem_uptime_minutes BIGINT,
    rain_fade_status ENUM('none', 'mild', 'moderate', 'severe', 'not_checked') DEFAULT 'not_checked',
    data_usage_mb DECIMAL(12,2),
    server_protocol ENUM('MQTT', 'HTTP API', 'HTTPS API', 'other', 'not_tested') DEFAULT 'not_tested',
    server_delivery_status ENUM('success', 'fail', 'not_tested') DEFAULT 'not_tested',
    reconnect_count INT DEFAULT 0,
    last_successful_send DATETIME,
    evidence_link VARCHAR(255),
    overall_status ENUM('passed', 'partial', 'failed') NOT NULL DEFAULT 'partial',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_satellite_vsat_test_date (test_date),
    INDEX idx_satellite_vsat_status (overall_status),
    UNIQUE INDEX uq_satellite_vsat_session_trial (test_session_code, trial_number)
);

-- Interference Resistance Tests table
CREATE TABLE interference_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_date DATE NOT NULL,
    location_name VARCHAR(100),
    interference_level ENUM('normal', 'low', 'medium', 'high'),
    interference_source VARCHAR(100),
    distance_meter DECIMAL(10,2),
    rssi_dbm DECIMAL(6,2),
    snr_db DECIMAL(6,2),
    throughput_kbps DECIMAL(10,2),
    latency_ms DECIMAL(10,2),
    packet_sent INT DEFAULT 0,
    packet_received INT DEFAULT 0,
    packet_loss_percent DECIMAL(5,2),
    throughput_degradation_percent DECIMAL(5,2),
    latency_increase_percent DECIMAL(5,2),
    snr_degradation_db DECIMAL(6,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Slave Camera Tests table
CREATE TABLE slave_camera_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_date DATE NOT NULL,
    location_name VARCHAR(100),
    node_id VARCHAR(50),
    distance_meter DECIMAL(10,2),
    resolution VARCHAR(50),
    fps INT,
    image_quality_score INT,
    camera_delay_ms DECIMAL(10,2),
    packet_loss_percent DECIMAL(5,2),
    status ENUM('success', 'fail'),
    average_camera_delay DECIMAL(10,2),
    average_fps DECIMAL(5,2),
    camera_quality_category VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Power Consumption Tests table
CREATE TABLE power_consumption_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_date DATE NOT NULL,
    device_id VARCHAR(50),
    device_type ENUM('master', 'slave'),
    battery_voltage_v DECIMAL(5,2),
    current_a DECIMAL(5,2),
    test_duration_hour DECIMAL(5,2),
    battery_capacity_mah INT,
    cpu_usage_percent DECIMAL(5,2),
    ram_usage_percent DECIMAL(5,2),
    cpu_temperature_c DECIMAL(5,2),
    rssi_dbm DECIMAL(6,2),
    snr_db DECIMAL(6,2),
    power_w DECIMAL(10,2),
    energy_wh DECIMAL(10,4),
    battery_capacity_wh DECIMAL(10,4),
    estimated_runtime_hour DECIMAL(10,2),
    estimated_runtime_day DECIMAL(10,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Command Execution Tests table
CREATE TABLE command_execution_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_date DATE NOT NULL,
    command_type VARCHAR(80),
    source VARCHAR(50),
    target_node_id VARCHAR(50),
    command_sent_time_ms BIGINT,
    command_received_time_ms BIGINT,
    command_executed_time_ms BIGINT,
    execution_status ENUM('success', 'fail'),
    command_delivery_delay DECIMAL(10,2),
    command_execution_delay DECIMAL(10,2),
    total_command_time DECIMAL(10,2),
    command_success_rate DECIMAL(5,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Text Message Communication Logs table
CREATE TABLE text_message_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_date DATE NOT NULL,
    source_node VARCHAR(50),
    target_node_id VARCHAR(50),
    target_ip VARCHAR(45) NOT NULL,
    target_port INT DEFAULT 80,
    protocol ENUM('HTTP') DEFAULT 'HTTP',
    endpoint VARCHAR(120) DEFAULT '/api/message',
    message_text TEXT NOT NULL,
    request_payload TEXT,
    response_status_code INT,
    response_body TEXT,
    latency_ms DECIMAL(10,2),
    delivery_status ENUM('success', 'fail') DEFAULT 'fail',
    error_message TEXT,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Slave to Master Text Message Inbox Logs table
CREATE TABLE text_message_inbox_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    received_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    source_node VARCHAR(50),
    target_node_id VARCHAR(50),
    source_ip VARCHAR(45),
    message_text TEXT NOT NULL,
    raw_payload TEXT,
    rssi_dbm INT NULL,
    slave_uptime_ms BIGINT NULL,
    delivery_status ENUM('success', 'fail') DEFAULT 'success',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Response Time Tests table
CREATE TABLE response_time_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_date DATE NOT NULL,
    command_type VARCHAR(50),
    target_node_id VARCHAR(50),
    request_time_ms BIGINT,
    response_time_ms BIGINT,
    network_mode VARCHAR(50),
    status VARCHAR(20),
    response_time_total_ms DECIMAL(10,2),
    average_response_time DECIMAL(10,2),
    minimum_response_time DECIMAL(10,2),
    maximum_response_time DECIMAL(10,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Authentication Tests table
CREATE TABLE authentication_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_date DATE NOT NULL,
    user_role VARCHAR(50),
    login_attempt_type ENUM('valid_user', 'invalid_user', 'wrong_password', 'unauthorized_access'),
    authentication_method VARCHAR(50),
    attempt_count INT DEFAULT 0,
    success_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    authentication_success_rate DECIMAL(5,2),
    authentication_failure_rate DECIMAL(5,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Encryption Tests table
CREATE TABLE encryption_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_date DATE NOT NULL,
    protocol_used VARCHAR(50),
    encryption_type VARCHAR(50),
    key_length_bit INT,
    sniffing_test_result ENUM('readable', 'unreadable'),
    data_integrity_status ENUM('valid', 'invalid'),
    encryption_status VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Generated Reports table
CREATE TABLE generated_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_title VARCHAR(255) NOT NULL,
    report_type VARCHAR(50),
    date_range_start DATE,
    date_range_end DATE,
    location_filter VARCHAR(100),
    test_type_filter VARCHAR(100),
    content TEXT,
    file_path VARCHAR(255),
    file_type ENUM('pdf', 'csv', 'html'),
    generated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id)
);
