USE wifi_holow_testing;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'signal_penetration_tests'
      AND COLUMN_NAME = 'obstacle_thickness_meter'
);

SET @drop_sql := IF(
    @column_exists > 0,
    'ALTER TABLE signal_penetration_tests DROP COLUMN obstacle_thickness_meter',
    'SELECT ''obstacle_thickness_meter already removed'' AS status'
);

PREPARE drop_obstacle_thickness FROM @drop_sql;
EXECUTE drop_obstacle_thickness;
DEALLOCATE PREPARE drop_obstacle_thickness;
