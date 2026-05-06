USE wifi_holow_testing;

ALTER TABLE test_locations
    MODIFY latitude DECIMAL(17, 14) NULL,
    MODIFY longitude DECIMAL(18, 14) NULL;

ALTER TABLE range_tests
    MODIFY master_gps_latitude DECIMAL(17, 14) NULL,
    MODIFY master_gps_longitude DECIMAL(18, 14) NULL,
    MODIFY gps_latitude DECIMAL(17, 14) NULL,
    MODIFY gps_longitude DECIMAL(18, 14) NULL;
