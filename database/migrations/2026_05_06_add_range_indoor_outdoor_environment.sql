-- Allow indoor and outdoor environment values on range tests.
ALTER TABLE range_tests
MODIFY environment_type ENUM('lapangan', 'hangar', 'pantai', 'gunung', 'indoor', 'outdoor');
