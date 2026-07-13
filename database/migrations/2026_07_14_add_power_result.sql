-- Keep the Power Consumption page aligned with the authoritative workbook.
ALTER TABLE `power_consumption_tests`
ADD COLUMN IF NOT EXISTS `result` VARCHAR(30) NULL AFTER `estimated_runtime_day`;
