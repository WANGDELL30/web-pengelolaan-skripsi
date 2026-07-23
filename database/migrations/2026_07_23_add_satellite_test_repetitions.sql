-- Group 1-3 repeated measurements into one VSAT test session.
ALTER TABLE `satellite_vsat_tests`
ADD COLUMN IF NOT EXISTS `test_session_code` VARCHAR(80) NULL AFTER `test_date`,
ADD COLUMN IF NOT EXISTS `planned_trials` TINYINT UNSIGNED NOT NULL DEFAULT 3 AFTER `test_session_code`,
ADD COLUMN IF NOT EXISTS `trial_number` TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `planned_trials`;

UPDATE `satellite_vsat_tests`
SET `test_session_code` = CONCAT('VSAT-', DATE_FORMAT(`test_date`, '%Y%m%d'), '-LEGACY-', LPAD(`id`, 4, '0'))
WHERE `test_session_code` IS NULL OR `test_session_code` = '';

ALTER TABLE `satellite_vsat_tests`
MODIFY COLUMN `test_session_code` VARCHAR(80) NOT NULL,
ADD UNIQUE INDEX IF NOT EXISTS `uq_satellite_vsat_session_trial` (`test_session_code`, `trial_number`);
