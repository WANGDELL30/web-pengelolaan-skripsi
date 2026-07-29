-- Reframe interference testing around observable RF-spectrum evidence.
-- Existing compatibility columns remain because reports and simulations use them.
ALTER TABLE `interference_tests`
    ADD COLUMN IF NOT EXISTS `scan_status`
        ENUM('not_scanned','clear','detected','inconclusive') NULL AFTER `environment_type`,
    ADD COLUMN IF NOT EXISTS `scan_start_mhz`
        DECIMAL(7,2) NULL DEFAULT 860.00 AFTER `scan_status`,
    ADD COLUMN IF NOT EXISTS `scan_end_mhz`
        DECIMAL(7,2) NULL DEFAULT 930.00 AFTER `scan_start_mhz`,
    ADD COLUMN IF NOT EXISTS `noise_floor_dbm`
        DECIMAL(6,2) NULL AFTER `scan_end_mhz`,
    ADD COLUMN IF NOT EXISTS `strongest_interferer_frequency_mhz`
        DECIMAL(7,2) NULL AFTER `noise_floor_dbm`,
    ADD COLUMN IF NOT EXISTS `strongest_interferer_power_dbm`
        DECIMAL(6,2) NULL AFTER `strongest_interferer_frequency_mhz`,
    ADD COLUMN IF NOT EXISTS `channel_occupancy_percent`
        DECIMAL(5,2) NULL AFTER `strongest_interferer_power_dbm`,
    ADD COLUMN IF NOT EXISTS `sdr_device`
        VARCHAR(100) NULL AFTER `channel_occupancy_percent`,
    ADD COLUMN IF NOT EXISTS `interference_evidence`
        TEXT NULL AFTER `sdr_device`;

-- Shared observation key prevents repeated trials at one distance from being
-- cross-joined in consolidated reports.
ALTER TABLE `connectivity_tests`
    ADD COLUMN IF NOT EXISTS `measurement_code` VARCHAR(50) NULL AFTER `environment_type`;
ALTER TABLE `range_tests`
    ADD COLUMN IF NOT EXISTS `measurement_code` VARCHAR(50) NULL AFTER `environment_type`;
ALTER TABLE `signal_penetration_tests`
    ADD COLUMN IF NOT EXISTS `measurement_code` VARCHAR(50) NULL AFTER `environment_type`;
ALTER TABLE `latency_tests`
    ADD COLUMN IF NOT EXISTS `measurement_code` VARCHAR(50) NULL AFTER `environment_type`;
ALTER TABLE `throughput_tests`
    ADD COLUMN IF NOT EXISTS `measurement_code` VARCHAR(50) NULL AFTER `environment_type`;
ALTER TABLE `interference_tests`
    ADD COLUMN IF NOT EXISTS `measurement_code` VARCHAR(50) NULL AFTER `environment_type`;
