USE wifi_holow_testing;

ALTER TABLE range_tests
    ADD COLUMN master_gps_latitude DECIMAL(17, 14) NULL AFTER distance_km,
    ADD COLUMN master_gps_longitude DECIMAL(18, 14) NULL AFTER master_gps_latitude;

UPDATE range_tests rt
LEFT JOIN test_locations tl ON tl.location_name = rt.location_name
SET
    rt.master_gps_latitude = COALESCE(rt.master_gps_latitude, tl.latitude),
    rt.master_gps_longitude = COALESCE(rt.master_gps_longitude, tl.longitude)
WHERE rt.master_gps_latitude IS NULL
   OR rt.master_gps_longitude IS NULL;
