-- Migration: Add distance_meter to connectivity_tests and environment_type to interference/penetration tests
-- Date: 2026-07-11
-- Purpose: Enable distance-based analysis for all test types with environment filtering

-- Add distance_meter to connectivity_tests
ALTER TABLE `connectivity_tests` 
ADD COLUMN `distance_meter` DECIMAL(10,2) DEFAULT NULL COMMENT 'Distance between master and slave in meters' 
AFTER `environment_type`;

-- Add environment_type to interference_tests
ALTER TABLE `interference_tests` 
ADD COLUMN `environment_type` ENUM('lapangan','hangar','pantai','gunung','indoor','outdoor') DEFAULT NULL 
AFTER `location_name`;

-- Add environment_type to signal_penetration_tests
ALTER TABLE `signal_penetration_tests` 
ADD COLUMN `environment_type` ENUM('lapangan','hangar','pantai','gunung','indoor','outdoor') DEFAULT NULL 
AFTER `location_name`;
