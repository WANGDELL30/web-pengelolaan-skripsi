USE wifi_holow_testing;

START TRANSACTION;

-- Connectivity: ping tidak dijalankan, sehingga metrik paket bukan 0 terukur.
UPDATE connectivity_tests
SET packet_sent = NULL,
    packet_received = NULL,
    packet_lost = NULL,
    packet_loss_percent = NULL,
    packet_success_rate = NULL
WHERE id = 8
  AND location_name = 'Gedung UAI Lantai 3'
  AND packet_sent = 0
  AND packet_received = 0;

-- Connectivity master lokal tidak memiliki RSSI/SNR link slave.
UPDATE connectivity_tests
SET rssi_dbm = NULL,
    snr_db = NULL
WHERE id = 9
  AND node_type = 'master'
  AND rssi_dbm = 0
  AND snr_db = 0;

-- Latency tanpa paket diterima tidak menghasilkan latency/jitter terukur.
UPDATE latency_tests
SET timestamp_send_ms = NULL,
    timestamp_receive_ms = NULL,
    packet_sent = NULL,
    packet_received = NULL,
    latency_ms = NULL,
    jitter_ms = NULL,
    packet_loss_percent = NULL,
    average_latency = NULL,
    minimum_latency = NULL,
    maximum_latency = NULL,
    average_jitter = NULL
WHERE id = 8
  AND location_name = 'Gedung UAI Lantai 3'
  AND packet_sent = 0
  AND packet_received = 0;

UPDATE latency_tests
SET latency_ms = NULL,
    jitter_ms = NULL,
    average_latency = NULL,
    minimum_latency = NULL,
    maximum_latency = NULL,
    average_jitter = NULL
WHERE id IN (13, 15, 18)
  AND packet_sent > 0
  AND packet_received = 0;

-- Throughput master lokal dan RSSI/SNR kosong dinormalisasi menjadi N/A.
UPDATE throughput_tests
SET rssi_dbm = NULL,
    snr_db = NULL
WHERE id = 8
  AND rssi_dbm = 0
  AND snr_db = 0;

UPDATE throughput_tests
SET data_sent_kb = NULL,
    data_received_kb = NULL,
    rssi_dbm = NULL,
    snr_db = NULL,
    bitrate_kbps = NULL,
    throughput_kbps = NULL,
    packet_delivery_ratio_percent = NULL,
    data_loss_percent = NULL
WHERE id = 9
  AND node_id = 'MASTER-01'
  AND data_received_kb > data_sent_kb;

UPDATE throughput_tests
SET bitrate_kbps = NULL
WHERE id = 15
  AND data_received_kb = 0
  AND bitrate_kbps = 0;

UPDATE throughput_tests
SET rssi_dbm = -82.00
WHERE id = 19
  AND location_name = 'Rusun Boing Kemayoran'
  AND rssi_dbm = 82.00;

-- Interference: baris tanpa ping/ukur disimpan sebagai N/A, bukan 0.
UPDATE interference_tests
SET rssi_dbm = NULL,
    snr_db = NULL,
    throughput_kbps = NULL,
    latency_ms = NULL,
    packet_sent = NULL,
    packet_received = NULL,
    packet_loss_percent = NULL
WHERE id = 3
  AND location_name = 'Gedung UAI Lantai 3'
  AND packet_sent = 0
  AND packet_received = 0;

UPDATE interference_tests
SET rssi_dbm = NULL,
    snr_db = NULL
WHERE id = 4
  AND location_name = 'Gedung UAI Lantai 6'
  AND rssi_dbm = 0
  AND snr_db = 0;

UPDATE interference_tests
SET latency_ms = NULL
WHERE id IN (8, 10, 13)
  AND packet_sent > 0
  AND packet_received <= 0
  AND latency_ms = 0;

UPDATE interference_tests
SET packet_received = 0,
    packet_loss_percent = 100.00
WHERE id = 13
  AND packet_received < 0;

-- Range indoor yang tidak memiliki pengukuran koordinat/jarak diperlakukan N/A.
UPDATE range_tests
SET coordinate_x_meter = NULL,
    coordinate_y_meter = NULL,
    coordinate_z_meter = NULL,
    distance_actual_meter = NULL,
    distance_3d_meter = NULL,
    distance_km = NULL,
    fspl_db = NULL
WHERE id IN (12, 13, 14, 15)
  AND distance_actual_meter = 0;

UPDATE range_tests
SET rssi_dbm = NULL,
    snr_db = NULL,
    bitrate_kbps = NULL,
    signal_margin = NULL
WHERE id = 14
  AND rssi_dbm = 0
  AND snr_db = 0;

UPDATE range_tests
SET rssi_dbm = NULL,
    snr_db = NULL,
    bitrate_kbps = NULL,
    signal_margin = NULL,
    receiver_sensitivity_dbm = NULL,
    status_result = NULL
WHERE id = 15
  AND rssi_dbm = 0
  AND snr_db = 0;

-- Signal penetration tanpa pengukuran sinyal tidak boleh dihitung loss 0.
UPDATE signal_penetration_tests
SET rssi_before_dbm = NULL,
    rssi_after_dbm = NULL,
    snr_before_db = NULL,
    snr_after_db = NULL,
    packet_sent = NULL,
    packet_received = NULL,
    bitrate_kbps = NULL,
    rssi_loss = NULL,
    snr_loss = NULL,
    packet_loss_percent = NULL,
    penetration_loss_db = NULL
WHERE id = 4
  AND rssi_before_dbm = 0
  AND rssi_after_dbm = 0
  AND packet_sent = 0
  AND packet_received = 0;

UPDATE signal_penetration_tests
SET rssi_before_dbm = NULL,
    rssi_after_dbm = NULL,
    snr_before_db = NULL,
    snr_after_db = NULL,
    bitrate_kbps = NULL,
    rssi_loss = NULL,
    snr_loss = NULL,
    penetration_loss_db = NULL
WHERE id = 5
  AND rssi_before_dbm = 0
  AND rssi_after_dbm = 0;

-- Power: kapasitas/runtime baterai tidak tersedia saat tidak memakai baterai.
UPDATE power_consumption_tests
SET battery_voltage_v = NULL,
    current_a = NULL,
    power_w = NULL,
    energy_wh = NULL,
    battery_capacity_wh = NULL,
    estimated_runtime_hour = NULL,
    estimated_runtime_day = NULL,
    rssi_dbm = NULL,
    snr_db = NULL
WHERE id = 6
  AND battery_voltage_v = 0
  AND current_a = 0;

UPDATE power_consumption_tests
SET battery_capacity_mah = NULL,
    battery_capacity_wh = NULL,
    estimated_runtime_hour = NULL,
    estimated_runtime_day = NULL,
    rssi_dbm = NULL,
    snr_db = NULL
WHERE id IN (7, 8)
  AND battery_capacity_mah = 0;

UPDATE power_consumption_tests
SET battery_capacity_wh = NULL,
    estimated_runtime_hour = NULL,
    estimated_runtime_day = NULL,
    rssi_dbm = NULL,
    snr_db = NULL
WHERE id IN (9, 10)
  AND battery_capacity_mah IS NULL
  AND battery_capacity_wh = 0;

COMMIT;
