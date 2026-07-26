-- Tailor the VSAT page to the available ping output and modem-dashboard evidence.
ALTER TABLE `satellite_vsat_tests`
    MODIFY COLUMN `location_name` VARCHAR(100) NULL,
    MODIFY COLUMN `connection_mode` ENUM('WiFi AP + VSAT', 'Ethernet + VSAT') NULL,
    MODIFY COLUMN `access_point_ssid` VARCHAR(100) NULL,
    MODIFY COLUMN `master_ip` VARCHAR(45) NULL,
    ADD COLUMN IF NOT EXISTS `satellite_name` VARCHAR(100) NULL AFTER `vsat_provider`,
    ADD COLUMN IF NOT EXISTS `signal_quality_factor` SMALLINT UNSIGNED NULL AFTER `satellite_name`,
    ADD COLUMN IF NOT EXISTS `association_status` ENUM('associated', 'not_associated', 'not_checked') NULL AFTER `vsat_lock_status`,
    ADD COLUMN IF NOT EXISTS `tdma_status` ENUM('active', 'inactive', 'not_checked') NULL AFTER `association_status`,
    ADD COLUMN IF NOT EXISTS `association_time` DATETIME NULL AFTER `tdma_status`,
    ADD COLUMN IF NOT EXISTS `latency_min_ms` DECIMAL(10,3) NULL AFTER `packet_received`,
    MODIFY COLUMN `latency_ms` DECIMAL(10,3) NULL,
    ADD COLUMN IF NOT EXISTS `latency_max_ms` DECIMAL(10,3) NULL AFTER `latency_ms`;

-- Import the three ping summaries recorded on 23 July 2026.
INSERT INTO `satellite_vsat_tests` (
    `test_date`, `test_session_code`, `planned_trials`, `trial_number`,
    `test_operator`, `node_id`, `gateway_ip`, `satellite_name`, `signal_quality_factor`,
    `gateway_ping_status`, `server_target`, `internet_ping_status`,
    `packet_sent`, `packet_received`, `latency_min_ms`, `latency_ms`, `latency_max_ms`,
    `packet_loss_percent`, `vsat_lock_status`, `association_status`, `tdma_status`,
    `association_time`, `overall_status`, `notes`
) VALUES
(
    '2026-07-23', 'VSAT-20260723-01', 3, 1,
    'Aranda dan Adnan', 'MASTER-VSAT', '10.20.10.1', 'PSN-VI-PSN', 65,
    'success', '8.8.8.8', 'success',
    61, 61, 545.958, 600.803, 680.238,
    0.00, 'locked', 'associated', 'active',
    '2026-07-23 08:09:13', 'passed',
    'Sumber terminal ping 8.8.8.8: 61 transmitted, 61 received, 0% loss, min/avg/max 545.958/600.803/680.238 ms. Screenshot modem menunjukkan System State Code 0.0.0, FLL Locked, SQF 65, TDMA Active, dan Association State ASSOCIATED.'
),
(
    '2026-07-23', 'VSAT-20260723-01', 3, 2,
    'Aranda dan Adnan', 'MASTER-VSAT', '10.20.10.1', 'PSN-VI-PSN', 65,
    'success', '8.8.8.8', 'success',
    61, 61, 545.300, 599.880, 660.605,
    0.00, 'locked', 'associated', 'active',
    '2026-07-23 08:09:13', 'passed',
    'Sumber terminal ping 8.8.8.8: 61 transmitted, 61 received, 0% loss, min/avg/max 545.300/599.880/660.605 ms. Screenshot modem menunjukkan System State Code 0.0.0, FLL Locked, SQF 65, TDMA Active, dan Association State ASSOCIATED.'
),
(
    '2026-07-23', 'VSAT-20260723-01', 3, 3,
    'Aranda dan Adnan', 'MASTER-VSAT', '10.20.10.1', 'PSN-VI-PSN', 65,
    'success', '8.8.8.8', 'success',
    61, 61, 537.816, 593.397, 946.680,
    0.00, 'locked', 'associated', 'active',
    '2026-07-23 08:09:13', 'passed',
    'Sumber terminal ping 8.8.8.8: 61 transmitted, 61 received, 0% loss, min/avg/max 537.816/593.397/946.680 ms. Screenshot modem menunjukkan System State Code 0.0.0, FLL Locked, SQF 65, TDMA Active, dan Association State ASSOCIATED.'
)
ON DUPLICATE KEY UPDATE
    `test_date` = VALUES(`test_date`),
    `planned_trials` = VALUES(`planned_trials`),
    `test_operator` = VALUES(`test_operator`),
    `node_id` = VALUES(`node_id`),
    `gateway_ip` = VALUES(`gateway_ip`),
    `satellite_name` = VALUES(`satellite_name`),
    `signal_quality_factor` = VALUES(`signal_quality_factor`),
    `gateway_ping_status` = VALUES(`gateway_ping_status`),
    `server_target` = VALUES(`server_target`),
    `internet_ping_status` = VALUES(`internet_ping_status`),
    `packet_sent` = VALUES(`packet_sent`),
    `packet_received` = VALUES(`packet_received`),
    `latency_min_ms` = VALUES(`latency_min_ms`),
    `latency_ms` = VALUES(`latency_ms`),
    `latency_max_ms` = VALUES(`latency_max_ms`),
    `packet_loss_percent` = VALUES(`packet_loss_percent`),
    `vsat_lock_status` = VALUES(`vsat_lock_status`),
    `association_status` = VALUES(`association_status`),
    `tdma_status` = VALUES(`tdma_status`),
    `association_time` = VALUES(`association_time`),
    `overall_status` = VALUES(`overall_status`),
    `notes` = VALUES(`notes`);
