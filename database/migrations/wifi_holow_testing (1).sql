-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 20 Bulan Mei 2026 pada 06.38
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wifi_holow_testing`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `authentication_tests`
--

CREATE TABLE `authentication_tests` (
  `id` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `login_attempt_type` enum('valid_user','invalid_user','wrong_password','unauthorized_access') DEFAULT NULL,
  `authentication_method` varchar(50) DEFAULT NULL,
  `attempt_count` int(11) DEFAULT 0,
  `success_count` int(11) DEFAULT 0,
  `failed_count` int(11) DEFAULT 0,
  `authentication_success_rate` decimal(5,2) DEFAULT NULL,
  `authentication_failure_rate` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `authentication_tests`
--

INSERT INTO `authentication_tests` (`id`, `test_date`, `user_role`, `login_attempt_type`, `authentication_method`, `attempt_count`, `success_count`, `failed_count`, `authentication_success_rate`, `authentication_failure_rate`, `notes`, `created_at`, `updated_at`) VALUES
(1, '2026-04-25', 'operator', 'valid_user', 'password', 100, 100, 0, 100.00, 0.00, 'All valid users authenticated', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(2, '2026-04-25', 'operator', 'invalid_user', 'password', 50, 0, 50, 0.00, 100.00, 'All invalid users rejected', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(3, '2026-04-25', 'admin', 'wrong_password', 'password', 20, 0, 20, 0.00, 100.00, 'Failed login attempts blocked', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(4, '2026-04-25', 'viewer', 'valid_user', '2fa', 75, 75, 0, 100.00, 0.00, '2FA authentication successful', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(5, '2026-04-25', 'operator', 'unauthorized_access', 'none', 10, 0, 10, 0.00, 100.00, 'Unauthorized access attempts blocked', '2026-04-27 10:54:22', '2026-04-27 10:54:22');

-- --------------------------------------------------------

--
-- Struktur dari tabel `command_execution_tests`
--

CREATE TABLE `command_execution_tests` (
  `id` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `command_type` varchar(80) DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL,
  `target_node_id` varchar(50) DEFAULT NULL,
  `command_sent_time_ms` bigint(20) DEFAULT NULL,
  `command_received_time_ms` bigint(20) DEFAULT NULL,
  `command_executed_time_ms` bigint(20) DEFAULT NULL,
  `execution_status` enum('success','fail') DEFAULT NULL,
  `command_delivery_delay` decimal(10,2) DEFAULT NULL,
  `command_execution_delay` decimal(10,2) DEFAULT NULL,
  `total_command_time` decimal(10,2) DEFAULT NULL,
  `command_success_rate` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `command_execution_tests`
--

INSERT INTO `command_execution_tests` (`id`, `test_date`, `command_type`, `source`, `target_node_id`, `command_sent_time_ms`, `command_received_time_ms`, `command_executed_time_ms`, `execution_status`, `command_delivery_delay`, `command_execution_delay`, `total_command_time`, `command_success_rate`, `notes`, `created_at`, `updated_at`) VALUES
(1, '2026-04-23', 'reset', 'dashboard', 'NODE-SLAVE-01', 1000000, 1000230, 1001450, 'success', 230.00, 1220.00, 1450.00, 100.00, 'Successful reset command', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(2, '2026-04-23', 'configuration_update', 'dashboard', 'NODE-SLAVE-02', 2000000, 2000560, 2002340, 'success', 560.00, 1780.00, 2340.00, 100.00, 'Configuration updated successfully', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(3, '2026-04-23', 'turn_off', 'dashboard', 'NODE-SLAVE-03', 3000000, 3001200, 3001890, 'success', 1200.00, 690.00, 1890.00, 100.00, 'Node turned off successfully', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(4, '2026-04-23', 'restart', 'dashboard', 'NODE-SLAVE-01', 4000000, 4002500, 4004200, 'success', 2500.00, 1700.00, 4200.00, 100.00, 'Node restarted successfully', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(5, '2026-04-23', 'shutdown', 'dashboard', 'NODE-MASTER-01', 5000000, 5000890, 5001560, 'success', 890.00, 670.00, 1560.00, 100.00, 'Master node shutdown', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(6, '2026-05-08', 'status', 'WEB_MASTER', 'SLAVE_001', 1778228049579, 1778228052582, 1778228052582, 'fail', 3003.00, 0.00, 3003.00, 0.00, 'UDP 192.168.1.113:5555 | payload=halow123 STATUS | reply=timeout', '2026-05-08 08:14:12', '2026-05-08 08:14:12'),
(7, '2026-05-08', 'status', 'WEB_MASTER', 'SLAVE_001', 1778228066199, 1778228069206, 1778228069206, 'fail', 3007.00, 0.00, 3007.00, 0.00, 'UDP 192.168.1.113:5555 | payload=halow123 STATUS | reply=timeout', '2026-05-08 08:14:29', '2026-05-08 08:14:29'),
(8, '2026-05-08', 'status', 'WEB_MASTER', 'SLAVE_001', 1778228300647, 1778228303665, 1778228303665, 'fail', 3018.00, 0.00, 3018.00, 0.00, 'UDP 192.168.1.113:5555 | payload=halow123 STATUS | reply=timeout', '2026-05-08 08:18:23', '2026-05-08 08:18:23');

-- --------------------------------------------------------

--
-- Struktur dari tabel `connectivity_tests`
--

CREATE TABLE `connectivity_tests` (
  `id` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `location_name` varchar(100) DEFAULT NULL,
  `environment_type` enum('lapangan','hangar','pantai','gunung','indoor','outdoor') DEFAULT NULL,
  `node_id` varchar(50) DEFAULT NULL,
  `node_type` enum('master','slave') DEFAULT NULL,
  `connection_status` enum('connected','disconnected','intermittent') DEFAULT NULL,
  `rssi_dbm` decimal(6,2) DEFAULT NULL,
  `snr_db` decimal(6,2) DEFAULT NULL,
  `packet_sent` int(11) DEFAULT 0,
  `packet_received` int(11) DEFAULT 0,
  `packet_lost` int(11) DEFAULT 0,
  `packet_loss_percent` decimal(5,2) DEFAULT 0.00,
  `packet_success_rate` decimal(5,2) DEFAULT 0.00,
  `test_duration_second` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `connectivity_tests`
--

INSERT INTO `connectivity_tests` (`id`, `test_date`, `location_name`, `environment_type`, `node_id`, `node_type`, `connection_status`, `rssi_dbm`, `snr_db`, `packet_sent`, `packet_received`, `packet_lost`, `packet_loss_percent`, `packet_success_rate`, `test_duration_second`, `notes`, `created_at`, `updated_at`) VALUES
(6, '2026-05-07', 'Gedung UAI Lantai 5', 'indoor', 'NODE-SLAVE-01', 'slave', 'connected', -68.00, 30.00, 159, 84, 75, 47.17, 52.83, 60, 'dari lantai 6 ke lantai 5', '2026-05-07 11:27:26', '2026-05-07 12:03:25'),
(7, '2026-05-07', 'Gedung UAI Lantai 4', 'indoor', 'NODE-SLAVE-01', 'slave', 'connected', -85.00, 15.00, 62, 20, 42, 67.74, 32.26, 60, 'Packet loss 67%, average latency 8091.477 ms, min 5449.282 ms, max 11590.802 ms', '2026-05-07 12:07:42', '2026-05-07 12:07:42'),
(8, '2026-05-07', 'Gedung UAI Lantai 3', 'indoor', 'NODE-SLAVE-01', 'slave', 'intermittent', -86.00, 12.00, 0, 0, 0, 0.00, 0.00, 60, 'signal to noise muncul tapi ping ga bisa dijalankan', '2026-05-07 12:36:28', '2026-05-07 12:37:11'),
(9, '2026-05-07', 'Gedung UAI Lantai 6', 'indoor', 'MASTER-01', 'master', 'connected', 0.00, 0.00, 63, 63, 0, 0.00, 100.00, 60, 'Koneksi sangat stabil dan tidak terdapat packet loss.', '2026-05-07 12:56:44', '2026-05-07 12:56:44'),
(10, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'gunung', 'NODE-SLAVE-01', 'slave', 'connected', -54.00, 56.00, 60, 28, 32, 53.33, 46.67, 60, '', '2026-05-10 04:32:03', '2026-05-10 04:52:43'),
(11, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'gunung', 'NODE-SLAVE-01', 'slave', 'connected', -59.00, 50.00, 61, 9, 52, 85.25, 14.75, 60, 'Koneksi berhasil terhubung namun mengalami packet loss sangat tinggi sebesar 85% dengan rata-rata latency 6629.577 ms dan jitter 866.82 ms.', '2026-05-10 05:09:03', '2026-05-10 05:09:03'),
(12, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'gunung', 'NODE-SLAVE-01', 'slave', 'connected', -95.00, 11.00, 60, 27, 33, 55.00, 45.00, 60, 'Koneksi berhasil terhubung dengan packet loss 55%, rata-rata latency 10061.966 ms, dan jitter 448.91 ms.', '2026-05-10 07:52:02', '2026-05-10 07:52:02'),
(13, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'gunung', 'NODE-SLAVE-01', 'slave', 'intermittent', -100.00, 5.00, 60, 0, 60, 100.00, 0.00, 60, '', '2026-05-10 08:14:30', '2026-05-10 08:14:30'),
(14, '2026-05-19', 'Parkiran Jiexpo Kemayoran', 'lapangan', 'NODE-SLAVE-01', 'slave', 'connected', -84.00, 20.00, 62, 26, 36, 58.06, 41.94, 60, '', '2026-05-19 12:10:37', '2026-05-19 12:10:37'),
(15, '2026-05-19', 'Pantai Ancol', 'pantai', 'NODE-SLAVE-01', 'slave', 'intermittent', -103.00, 6.00, 62, 0, 62, 100.00, 0.00, 60, '', '2026-05-20 01:29:08', '2026-05-20 01:29:26'),
(16, '2026-05-19', 'Pantai Ancol', 'pantai', 'NODE-SLAVE-01', 'slave', 'connected', -82.00, 28.00, 61, 28, 33, 54.10, 45.90, 60, '', '2026-05-20 01:49:19', '2026-05-20 01:49:19'),
(17, '2026-05-19', 'Pantai Ancol', 'pantai', 'NODE-SLAVE-01', 'slave', 'connected', -91.00, 16.00, 61, 19, 42, 68.85, 31.15, 60, '', '2026-05-20 02:05:18', '2026-05-20 02:05:18'),
(18, '2026-05-19', 'Pantai Ancol', 'pantai', 'NODE-SLAVE-01', 'slave', 'intermittent', -99.00, 10.00, 61, 0, 61, 100.00, 0.00, 60, '', '2026-05-20 02:14:25', '2026-05-20 02:15:11');

-- --------------------------------------------------------

--
-- Struktur dari tabel `data_monitoring`
--

CREATE TABLE `data_monitoring` (
  `id` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `node_id` varchar(50) DEFAULT NULL,
  `timestamp_ms` bigint(20) DEFAULT NULL,
  `battery_percent` decimal(5,2) DEFAULT NULL,
  `voltage_v` decimal(5,2) DEFAULT NULL,
  `current_a` decimal(5,2) DEFAULT NULL,
  `temperature_c` decimal(5,2) DEFAULT NULL,
  `rssi_dbm` decimal(6,2) DEFAULT NULL,
  `snr_db` decimal(6,2) DEFAULT NULL,
  `gps_latitude` decimal(10,8) DEFAULT NULL,
  `gps_longitude` decimal(11,8) DEFAULT NULL,
  `status_connection` varchar(20) DEFAULT NULL,
  `alert_status` varchar(20) DEFAULT NULL,
  `power_w` decimal(10,2) DEFAULT NULL,
  `status_category` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `data_monitoring`
--

INSERT INTO `data_monitoring` (`id`, `test_date`, `node_id`, `timestamp_ms`, `battery_percent`, `voltage_v`, `current_a`, `temperature_c`, `rssi_dbm`, `snr_db`, `gps_latitude`, `gps_longitude`, `status_connection`, `alert_status`, `power_w`, `status_category`, `notes`, `created_at`, `updated_at`) VALUES
(1, '2026-04-21', 'NODE-SLAVE-01', 1000000, 85.00, 7.20, 1.10, 45.00, -48.30, 22.10, -6.20880000, 106.84560000, 'connected', 'normal', 7.92, 'normal', 'Normal operation', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(2, '2026-04-21', 'NODE-SLAVE-01', 1060000, 84.00, 7.10, 1.20, 46.00, -49.10, 21.80, -6.20890000, 106.84570000, 'connected', 'normal', 8.52, 'normal', 'Normal operation', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(3, '2026-04-21', 'NODE-SLAVE-01', 1120000, 83.00, 7.00, 1.30, 47.00, -50.20, 20.50, -6.20900000, 106.84580000, 'connected', 'warning', 9.10, 'warning', 'Battery decreasing', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(4, '2026-04-21', 'NODE-SLAVE-02', 1000000, 92.00, 7.30, 0.90, 42.00, -45.20, 25.30, -6.20910000, 106.84590000, 'connected', 'normal', 6.57, 'normal', 'Excellent condition', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(5, '2026-04-21', 'NODE-SLAVE-02', 1060000, 91.00, 7.20, 1.00, 43.00, -46.10, 24.80, -6.20920000, 106.84600000, 'connected', 'normal', 7.20, 'normal', 'Excellent condition', '2026-04-27 10:54:22', '2026-04-27 10:54:22');

-- --------------------------------------------------------

--
-- Struktur dari tabel `devices`
--

CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `device_id` varchar(50) NOT NULL,
  `device_type` enum('master','slave') NOT NULL,
  `device_name` varchar(100) DEFAULT NULL,
  `firmware_version` varchar(20) DEFAULT NULL,
  `hardware_version` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `devices`
--

INSERT INTO `devices` (`id`, `device_id`, `device_type`, `device_name`, `firmware_version`, `hardware_version`, `status`, `created_at`, `updated_at`, `notes`) VALUES
(1, 'NODE-MASTER-01', 'master', 'Master Node Controller', 'v2.1.0', 'HW-1.0', 'active', '2026-04-27 10:54:22', '2026-04-27 10:54:22', NULL),
(2, 'NODE-SLAVE-01', 'slave', 'Slave Node 1', 'v2.1.0', 'HW-1.0', 'active', '2026-04-27 10:54:22', '2026-04-27 10:54:22', NULL),
(3, 'NODE-SLAVE-02', 'slave', 'Slave Node 2', 'v2.1.0', 'HW-1.0', 'active', '2026-04-27 10:54:22', '2026-04-27 10:54:22', NULL),
(4, 'NODE-SLAVE-03', 'slave', 'Slave Node 3', 'v2.0.5', 'HW-0.9', 'maintenance', '2026-04-27 10:54:22', '2026-04-27 10:54:22', NULL),
(5, 'NODE-SLAVE-04', 'slave', 'Slave Node 4', 'v2.1.0', 'HW-1.0', 'active', '2026-04-27 10:54:22', '2026-04-27 10:54:22', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `encryption_tests`
--

CREATE TABLE `encryption_tests` (
  `id` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `protocol_used` varchar(50) DEFAULT NULL,
  `encryption_type` varchar(50) DEFAULT NULL,
  `key_length_bit` int(11) DEFAULT NULL,
  `sniffing_test_result` enum('readable','unreadable') DEFAULT NULL,
  `data_integrity_status` enum('valid','invalid') DEFAULT NULL,
  `encryption_status` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `generated_reports`
--

CREATE TABLE `generated_reports` (
  `id` int(11) NOT NULL,
  `report_title` varchar(255) NOT NULL,
  `report_type` varchar(50) DEFAULT NULL,
  `date_range_start` date DEFAULT NULL,
  `date_range_end` date DEFAULT NULL,
  `location_filter` varchar(100) DEFAULT NULL,
  `test_type_filter` varchar(100) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` enum('pdf','csv','html') DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `generated_reports`
--

INSERT INTO `generated_reports` (`id`, `report_title`, `report_type`, `date_range_start`, `date_range_end`, `location_filter`, `test_type_filter`, `content`, `file_path`, `file_type`, `generated_by`, `created_at`) VALUES
(1, 'Weekly Connectivity Test Report', 'connectivity', '2026-04-15', '2026-04-21', 'Lapangan Terbuka A', 'connectivity_tests', 'Weekly connectivity test analysis report', '/reports/weekly_connectivity_20260421.pdf', 'pdf', 1, '2026-04-27 10:54:22'),
(2, 'Range Test Analysis Report', 'range', '2026-04-15', '2026-04-15', 'Lapangan Terbuka A', 'range_tests', 'Range test distance analysis', '/reports/range_test_20260415.pdf', 'pdf', 1, '2026-04-27 10:54:22'),
(3, 'Power Consumption Weekly Report', 'power', '2026-04-18', '2026-04-24', 'All Locations', 'power_consumption_tests', 'Weekly power analysis', '/reports/power_weekly_20260424.csv', 'csv', 1, '2026-04-27 10:54:22');

-- --------------------------------------------------------

--
-- Struktur dari tabel `interference_tests`
--

CREATE TABLE `interference_tests` (
  `id` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `location_name` varchar(100) DEFAULT NULL,
  `interference_level` enum('normal','low','medium','high') DEFAULT NULL,
  `interference_source` varchar(100) DEFAULT NULL,
  `distance_meter` decimal(10,2) DEFAULT NULL,
  `rssi_dbm` decimal(6,2) DEFAULT NULL,
  `snr_db` decimal(6,2) DEFAULT NULL,
  `throughput_kbps` decimal(10,2) DEFAULT NULL,
  `latency_ms` decimal(10,2) DEFAULT NULL,
  `packet_sent` int(11) DEFAULT 0,
  `packet_received` int(11) DEFAULT 0,
  `packet_loss_percent` decimal(5,2) DEFAULT NULL,
  `throughput_degradation_percent` decimal(5,2) DEFAULT NULL,
  `latency_increase_percent` decimal(5,2) DEFAULT NULL,
  `snr_degradation_db` decimal(6,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `interference_tests`
--

INSERT INTO `interference_tests` (`id`, `test_date`, `location_name`, `interference_level`, `interference_source`, `distance_meter`, `rssi_dbm`, `snr_db`, `throughput_kbps`, `latency_ms`, `packet_sent`, `packet_received`, `packet_loss_percent`, `throughput_degradation_percent`, `latency_increase_percent`, `snr_degradation_db`, `notes`, `created_at`, `updated_at`) VALUES
(1, '2026-05-07', 'Gedung UAI Lantai 6', 'medium', 'wall gedung', 25.00, -74.00, 26.00, 201.42, 3.43, 159, 84, 47.17, NULL, NULL, NULL, NULL, '2026-05-07 11:59:40', '2026-05-07 11:59:40'),
(2, '2026-05-07', 'Gedung UAI Lantai 4', 'medium', 'wall gedung', 50.00, -85.00, 15.00, 415.94, 8.09, 62, 19, 69.35, NULL, NULL, NULL, NULL, '2026-05-07 12:27:42', '2026-05-07 12:27:42'),
(3, '2026-05-07', 'Gedung UAI Lantai 3', 'medium', 'wall gedung', 75.00, 0.00, 0.00, 21.00, 0.00, 0, 0, 0.00, NULL, NULL, NULL, NULL, '2026-05-07 12:44:55', '2026-05-07 12:44:55'),
(4, '2026-05-07', 'Gedung UAI Lantai 6', 'low', 'none', 0.00, 0.00, 0.00, 20.97, 0.19, 63, 63, 0.00, NULL, NULL, NULL, NULL, '2026-05-07 13:04:47', '2026-05-07 13:04:47'),
(5, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'normal', 'Tree', 128.33, -54.00, 56.00, 842.99, 6976.49, 60, 28, 53.33, NULL, NULL, NULL, NULL, '2026-05-10 04:55:14', '2026-05-10 04:57:04'),
(6, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'high', 'Tree', 174.07, -59.00, 50.00, 288.00, 6629.57, 61, 9, 85.25, NULL, NULL, NULL, NULL, '2026-05-10 05:21:51', '2026-05-10 05:21:51'),
(7, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'high', 'tree &amp; rock', 185.95, -95.00, 11.00, 173.00, 10061.96, 60, 26, 56.67, NULL, NULL, NULL, NULL, '2026-05-10 08:03:28', '2026-05-10 08:03:28'),
(8, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'high', 'tree &amp; rock', 234.28, -100.00, 5.00, 21.00, 0.00, 60, 0, 100.00, NULL, NULL, NULL, NULL, '2026-05-10 08:27:18', '2026-05-10 08:27:18'),
(9, '2026-05-19', 'Parkiran Jiexpo Kemayoran', 'medium', 'Palm Tree', 141.57, -84.00, 20.00, 105.00, 9079.11, 62, 26, 58.06, NULL, NULL, NULL, NULL, '2026-05-19 12:17:34', '2026-05-19 12:17:34'),
(10, '2026-05-19', 'Pantai Ancol', 'medium', 'Coconut Tree &amp;amp; rock', 190.40, -103.00, 6.00, 21.00, 0.00, 62, 0, 100.00, NULL, NULL, NULL, NULL, '2026-05-20 01:41:09', '2026-05-20 01:54:23'),
(11, '2026-05-19', 'Pantai Ancol', 'medium', 'Coconut Tree &amp; rock', 92.47, -82.00, 28.00, 342.00, 8733.81, 61, 28, 54.10, NULL, NULL, NULL, NULL, '2026-05-20 02:00:09', '2026-05-20 02:00:09'),
(12, '2026-05-19', 'Pantai Ancol', 'high', 'Coconut Tree &amp; rock', 168.38, -91.00, 16.00, 403.00, 7620.46, 61, 19, 68.85, NULL, NULL, NULL, NULL, '2026-05-20 02:11:48', '2026-05-20 02:11:48'),
(13, '2026-05-19', 'Pantai Ancol', 'high', 'Coconut Tree &amp; rock', 200.93, -99.00, 10.00, 21.00, 0.00, 61, -2, 103.28, NULL, NULL, NULL, NULL, '2026-05-20 02:19:53', '2026-05-20 02:19:53');

-- --------------------------------------------------------

--
-- Struktur dari tabel `latency_tests`
--

CREATE TABLE `latency_tests` (
  `id` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `location_name` varchar(100) DEFAULT NULL,
  `environment_type` enum('lapangan','hangar','pantai','gunung','indoor','outdoor') DEFAULT NULL,
  `node_id` varchar(50) DEFAULT NULL,
  `distance_meter` decimal(10,2) DEFAULT NULL,
  `trial_number` int(11) DEFAULT NULL,
  `timestamp_send_ms` bigint(20) DEFAULT NULL,
  `timestamp_receive_ms` bigint(20) DEFAULT NULL,
  `packet_sent` int(11) DEFAULT 0,
  `packet_received` int(11) DEFAULT 0,
  `network_mode` enum('HaLow only','HaLow + VSAT') DEFAULT NULL,
  `latency_ms` decimal(10,2) DEFAULT NULL,
  `jitter_ms` decimal(10,2) DEFAULT NULL,
  `packet_loss_percent` decimal(5,2) DEFAULT NULL,
  `average_latency` decimal(10,2) DEFAULT NULL,
  `minimum_latency` decimal(10,2) DEFAULT NULL,
  `maximum_latency` decimal(10,2) DEFAULT NULL,
  `average_jitter` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `latency_tests`
--

INSERT INTO `latency_tests` (`id`, `test_date`, `location_name`, `environment_type`, `node_id`, `distance_meter`, `trial_number`, `timestamp_send_ms`, `timestamp_receive_ms`, `packet_sent`, `packet_received`, `network_mode`, `latency_ms`, `jitter_ms`, `packet_loss_percent`, `average_latency`, `minimum_latency`, `maximum_latency`, `average_jitter`, `notes`, `created_at`, `updated_at`) VALUES
(6, '2026-05-07', 'Gedung UAI Lantai 6', 'indoor', 'NODE-SLAVE-01', 25.00, 1, 1000, 3000, 159, 84, 'HaLow only', 3434.00, 587.26, 47.17, 3434.00, 3434.00, 3434.00, 587.26, NULL, '2026-05-07 11:50:04', '2026-05-07 11:50:04'),
(7, '2026-05-07', 'Gedung UAI Lantai 4', 'indoor', 'NODE-SLAVE-01', 50.00, 1, NULL, NULL, 62, 24, 'HaLow only', 8091.47, 956.44, 61.29, 8091.47, 8091.47, 8091.47, 956.44, 'Hasil pengujian menunjukkan nilai rata-rata latency sebesar 8091.477 ms dan jitter sebesar 956.44 ms dengan packet loss sebesar 67%.', '2026-05-07 12:23:03', '2026-05-07 12:23:03'),
(8, '2026-05-07', 'Gedung UAI Lantai 3', 'indoor', 'NODE-SLAVE-01', 75.00, 1, 60000, 60000, 0, 0, 'HaLow only', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL, '2026-05-07 12:42:30', '2026-05-07 12:42:30'),
(9, '2026-05-07', 'Gedung UAI Lantai 6', 'indoor', 'MASTER-01', 0.00, 1, 500, 1000, 63, 63, NULL, 0.19, 0.01, 0.00, 0.19, 0.19, 0.19, 0.01, NULL, '2026-05-07 13:01:57', '2026-05-07 13:01:57'),
(10, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'gunung', 'NODE-SLAVE-01', 128.33, 1, 4000, 17000, 60, 27, 'HaLow only', 6976.49, 885.37, 55.00, 6976.49, 6976.49, 6976.49, 885.37, NULL, '2026-05-10 04:49:50', '2026-05-10 04:49:50'),
(11, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'gunung', 'NODE-SLAVE-01', 174.07, 1, 3000, 11000, 61, 9, 'HaLow only', 6629.57, 866.82, 85.25, 6629.57, 6629.57, 6629.57, 866.82, NULL, '2026-05-10 05:19:00', '2026-05-10 05:19:00'),
(12, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'gunung', 'NODE-SLAVE-01', 185.95, 1, 2000, 10000, 60, 27, 'HaLow only', 10061.96, 448.90, 55.00, 10061.96, 10061.96, 10061.96, 448.90, NULL, '2026-05-10 07:57:44', '2026-05-10 07:57:44'),
(13, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'gunung', 'NODE-SLAVE-01', 234.28, 1, 1000, 10000, 60, 0, 'HaLow only', 9000.00, 0.00, 100.00, 9000.00, 9000.00, 9000.00, 0.00, NULL, '2026-05-10 08:24:41', '2026-05-10 08:24:41'),
(14, '2026-05-19', 'Parkiran Jiexpo Kemayoran', 'lapangan', 'NODE-SLAVE-01', 141.57, 1, 1000, 11000, 62, 26, 'HaLow only', 9079.11, 683.13, 58.06, 9079.11, 9079.11, 9079.11, 683.13, NULL, '2026-05-19 12:14:49', '2026-05-19 12:14:49'),
(15, '2026-05-19', 'Pantai Ancol', 'pantai', 'NODE-SLAVE-01', 190.40, 1, 1000, 60000, 62, 0, 'HaLow only', 59000.00, 0.00, 100.00, 59000.00, 59000.00, 59000.00, 0.00, NULL, '2026-05-20 01:37:36', '2026-05-20 01:54:08'),
(16, '2026-05-19', 'Pantai Ancol', 'pantai', 'NODE-SLAVE-01', 92.47, 1, 1000, 8000, 61, 28, 'HaLow only', 8733.81, 795.30, 54.10, 8733.81, 8733.81, 8733.81, 795.30, NULL, '2026-05-20 01:58:15', '2026-05-20 01:58:15'),
(17, '2026-05-19', 'Pantai Ancol', 'pantai', 'NODE-SLAVE-01', 168.38, 1, 1000, 9000, 61, 19, 'HaLow only', 7620.48, 2217.91, 68.85, 7620.48, 7620.48, 7620.48, 2217.91, NULL, '2026-05-20 02:09:25', '2026-05-20 02:09:25'),
(18, '2026-05-19', 'Pantai Ancol', 'pantai', 'NODE-SLAVE-01', 200.93, 1, 1000, 60000, 61, 0, 'HaLow only', 59000.00, 0.00, 100.00, 59000.00, 59000.00, 59000.00, 0.00, NULL, '2026-05-20 02:18:30', '2026-05-20 02:18:30');

-- --------------------------------------------------------

--
-- Struktur dari tabel `mesh_topology_analysis`
--

CREATE TABLE `mesh_topology_analysis` (
  `id` int(11) NOT NULL,
  `analysis_date` date NOT NULL,
  `scenario_name` varchar(100) DEFAULT NULL,
  `total_nodes` int(11) DEFAULT NULL,
  `hop_count` int(11) DEFAULT NULL,
  `estimated_latency_per_hop_ms` decimal(10,2) DEFAULT NULL,
  `estimated_power_per_node_w` decimal(10,2) DEFAULT NULL,
  `estimated_throughput_kbps` decimal(10,2) DEFAULT NULL,
  `reliability_score_percent` decimal(5,2) DEFAULT NULL,
  `total_estimated_latency` decimal(10,2) DEFAULT NULL,
  `total_estimated_power` decimal(10,2) DEFAULT NULL,
  `efficiency_score` decimal(10,4) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `mesh_topology_analysis`
--

INSERT INTO `mesh_topology_analysis` (`id`, `analysis_date`, `scenario_name`, `total_nodes`, `hop_count`, `estimated_latency_per_hop_ms`, `estimated_power_per_node_w`, `estimated_throughput_kbps`, `reliability_score_percent`, `total_estimated_latency`, `total_estimated_power`, `efficiency_score`, `notes`, `created_at`, `updated_at`) VALUES
(1, '2026-04-20', 'Small Mesh Network', 5, 2, 15.50, 8.50, 45000.00, 95.00, 31.00, 42.50, 2235.2900, 'Small tactical mesh network', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(2, '2026-04-20', 'Medium Mesh Network', 10, 3, 18.20, 8.50, 38000.00, 90.00, 54.60, 85.00, 1058.8200, 'Medium tactical mesh network', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(3, '2026-04-20', 'Large Mesh Network', 15, 4, 20.10, 8.50, 32000.00, 85.00, 80.40, 127.50, 666.6700, 'Large tactical mesh network', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(4, '2026-04-20', 'Dense Mesh Network', 20, 5, 22.30, 8.50, 28000.00, 80.00, 111.50, 170.00, 470.5900, 'Dense tactical mesh network', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(5, '2026-04-20', 'VSAT Hybrid Mesh', 8, 3, 25.80, 12.50, 15000.00, 75.00, 77.40, 100.00, 112.5000, 'Hybrid mesh with VSAT integration', '2026-04-27 10:54:22', '2026-04-27 10:54:22');

-- --------------------------------------------------------

--
-- Struktur dari tabel `monitoring_delay_tests`
--

CREATE TABLE `monitoring_delay_tests` (
  `id` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `event_name` varchar(100) DEFAULT NULL,
  `node_id` varchar(50) DEFAULT NULL,
  `timestamp_event_generated_ms` bigint(20) DEFAULT NULL,
  `timestamp_displayed_dashboard_ms` bigint(20) DEFAULT NULL,
  `network_mode` varchar(50) DEFAULT NULL,
  `monitoring_delay_ms` decimal(10,2) DEFAULT NULL,
  `average_monitoring_delay` decimal(10,2) DEFAULT NULL,
  `delay_status` enum('fast','acceptable','slow') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `monitoring_delay_tests`
--

INSERT INTO `monitoring_delay_tests` (`id`, `test_date`, `event_name`, `node_id`, `timestamp_event_generated_ms`, `timestamp_displayed_dashboard_ms`, `network_mode`, `monitoring_delay_ms`, `average_monitoring_delay`, `delay_status`, `notes`, `created_at`, `updated_at`) VALUES
(1, '2026-04-22', 'Motion Detected', 'NODE-SLAVE-01', 1000000, 1000450, 'HaLow only', 450.00, 450.00, 'fast', 'Fast real-time monitoring', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(2, '2026-04-22', 'Signal Loss', 'NODE-SLAVE-02', 2000000, 2002340, 'HaLow only', 2340.00, 1395.00, 'acceptable', 'Acceptable delay', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(3, '2026-04-22', 'Temperature Alert', 'NODE-SLAVE-03', 3000000, 3008900, 'HaLow + VSAT', 8900.00, 4880.00, 'slow', 'High delay with VSAT', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(4, '2026-04-22', 'Battery Low', 'NODE-SLAVE-01', 4000000, 4001200, 'HaLow only', 1200.00, 3700.00, 'acceptable', 'Normal delay', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(5, '2026-04-22', 'Connection Restored', 'NODE-SLAVE-02', 5000000, 5003400, 'HaLow + VSAT', 3400.00, 3980.00, 'acceptable', 'VSAT delay acceptable', '2026-04-27 10:54:22', '2026-04-27 10:54:22');

-- --------------------------------------------------------

--
-- Struktur dari tabel `power_consumption_tests`
--

CREATE TABLE `power_consumption_tests` (
  `id` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `device_id` varchar(50) DEFAULT NULL,
  `device_type` enum('master','slave') DEFAULT NULL,
  `battery_voltage_v` decimal(5,2) DEFAULT NULL,
  `current_a` decimal(5,2) DEFAULT NULL,
  `test_duration_hour` decimal(5,2) DEFAULT NULL,
  `battery_capacity_mah` int(11) DEFAULT NULL,
  `cpu_usage_percent` decimal(5,2) DEFAULT NULL,
  `ram_usage_percent` decimal(5,2) DEFAULT NULL,
  `cpu_temperature_c` decimal(5,2) DEFAULT NULL,
  `rssi_dbm` decimal(6,2) DEFAULT NULL,
  `snr_db` decimal(6,2) DEFAULT NULL,
  `power_w` decimal(10,2) DEFAULT NULL,
  `energy_wh` decimal(10,4) DEFAULT NULL,
  `battery_capacity_wh` decimal(10,4) DEFAULT NULL,
  `estimated_runtime_hour` decimal(10,2) DEFAULT NULL,
  `estimated_runtime_day` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `power_consumption_tests`
--

INSERT INTO `power_consumption_tests` (`id`, `test_date`, `device_id`, `device_type`, `battery_voltage_v`, `current_a`, `test_duration_hour`, `battery_capacity_mah`, `cpu_usage_percent`, `ram_usage_percent`, `cpu_temperature_c`, `rssi_dbm`, `snr_db`, `power_w`, `energy_wh`, `battery_capacity_wh`, `estimated_runtime_hour`, `estimated_runtime_day`, `notes`, `created_at`, `updated_at`) VALUES
(6, '2026-05-07', 'MASTER-01', 'master', 0.00, 0.00, 2.14, NULL, 0.77, 5.00, 20.00, 0.00, 0.00, 0.00, 0.0000, 0.0000, 0.00, 0.00, NULL, '2026-05-07 13:07:27', '2026-05-07 13:07:27'),
(7, '2026-05-08', 'MASTER-01', 'master', 5.16, 0.67, 3.53, 0, 1.49, 5.00, NULL, 0.00, 0.00, 3.46, 12.2138, 0.0000, 0.00, 0.00, 'testing with chargeran + modul multimeter check', '2026-05-08 10:22:53', '2026-05-08 10:30:07'),
(8, '2026-05-10', 'MASTER-01', 'master', 5.17, 0.61, 2.45, 0, 1.31, 5.00, NULL, 0.00, 0.00, 3.15, 7.7175, 0.0000, 0.00, 0.00, 'ngecek di kaki gunung salak camp cidahu', '2026-05-10 06:18:28', '2026-05-10 06:18:28'),
(9, '2026-05-19', 'MASTER-01', 'master', 5.01, 0.67, 0.85, NULL, 0.37, 5.00, NULL, -9.00, 100.00, 3.36, 2.8560, 0.0000, 0.00, 0.00, NULL, '2026-05-20 01:43:35', '2026-05-20 01:43:35');

-- --------------------------------------------------------

--
-- Struktur dari tabel `range_tests`
--

CREATE TABLE `range_tests` (
  `id` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `location_name` varchar(100) DEFAULT NULL,
  `environment_type` enum('lapangan','hangar','pantai','gunung') DEFAULT NULL,
  `test_point_code` varchar(50) DEFAULT NULL,
  `direction` enum('north','south','east','west','vertical','diagonal') DEFAULT NULL,
  `coordinate_x_meter` decimal(10,2) DEFAULT NULL,
  `coordinate_y_meter` decimal(10,2) DEFAULT NULL,
  `coordinate_z_meter` decimal(10,2) DEFAULT NULL,
  `distance_actual_meter` decimal(10,2) DEFAULT NULL,
  `distance_3d_meter` decimal(10,2) DEFAULT NULL,
  `distance_km` decimal(10,4) DEFAULT NULL,
  `master_gps_latitude` decimal(17,14) DEFAULT NULL,
  `master_gps_longitude` decimal(18,14) DEFAULT NULL,
  `gps_latitude` decimal(17,14) DEFAULT NULL,
  `gps_longitude` decimal(18,14) DEFAULT NULL,
  `frequency_mhz` decimal(6,2) DEFAULT 915.00,
  `rssi_dbm` decimal(6,2) DEFAULT NULL,
  `snr_db` decimal(6,2) DEFAULT NULL,
  `bitrate_kbps` decimal(10,2) DEFAULT NULL,
  `connection_status` varchar(20) DEFAULT NULL,
  `fspl_db` decimal(6,2) DEFAULT NULL,
  `signal_margin` decimal(6,2) DEFAULT NULL,
  `receiver_sensitivity_dbm` decimal(6,2) DEFAULT -90.00,
  `status_result` enum('good','moderate','poor') DEFAULT NULL,
  `photo_video_link` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `range_tests`
--

INSERT INTO `range_tests` (`id`, `test_date`, `location_name`, `environment_type`, `test_point_code`, `direction`, `coordinate_x_meter`, `coordinate_y_meter`, `coordinate_z_meter`, `distance_actual_meter`, `distance_3d_meter`, `distance_km`, `master_gps_latitude`, `master_gps_longitude`, `gps_latitude`, `gps_longitude`, `frequency_mhz`, `rssi_dbm`, `snr_db`, `bitrate_kbps`, `connection_status`, `fspl_db`, `signal_margin`, `receiver_sensitivity_dbm`, `status_result`, `photo_video_link`, `notes`, `created_at`, `updated_at`) VALUES
(12, '2026-05-07', 'Gedung UAI Lantai 6', '', 'POINT-01', NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.0000, -6.23607000000000, 106.79914300000000, -6.23607000000000, 106.79914300000000, 915.50, -72.00, 24.00, 1400.00, 'connected', 0.00, 26.00, -98.00, 'good', NULL, 'dari lantai 6 ke lantai 5', '2026-05-07 11:35:16', '2026-05-08 06:30:17'),
(13, '2026-05-07', 'Gedung UAI Lantai 4', '', 'POINT-02', 'diagonal', 0.00, 0.00, 0.00, 0.00, 0.00, 0.0000, -6.23607000000000, 106.79914300000000, -6.23607000000000, 106.79914300000000, 915.50, -92.00, 9.00, 416.00, 'connected', 0.00, 9.00, -101.00, 'poor', NULL, NULL, '2026-05-07 12:14:29', '2026-05-08 06:29:50'),
(14, '2026-05-07', 'Gedung UAI Lantai 3', '', 'POINT-03', 'diagonal', 0.00, 0.00, 0.00, 0.00, 0.00, 0.0000, -6.23607000000000, 106.79914300000000, -6.23607000000000, 106.79914300000000, 915.50, 0.00, 0.00, 21.00, 'disconnected', 0.00, 98.00, -98.00, 'poor', NULL, NULL, '2026-05-07 12:40:40', '2026-05-07 12:40:40'),
(15, '2026-05-07', 'Gedung UAI Lantai 6', '', 'POINT-04', 'vertical', 0.00, 0.00, 0.00, 0.00, 0.00, 0.0000, -6.23607000000000, 106.79914300000000, -6.23607000000000, 106.79914300000000, 915.50, 0.00, 0.00, 21.00, 'connected', 0.00, 0.00, 0.00, 'poor', NULL, NULL, '2026-05-07 12:59:41', '2026-05-07 12:59:41'),
(16, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'gunung', 'POINT-05', 'west', -128.33, -8.84, 840.00, 128.64, 849.79, 0.1286, -6.75350300000000, 106.72691100000000, -6.75358250000000, 106.72574880000000, 915.50, -54.00, 55.00, 843.00, 'connected', 73.86, 56.00, -110.00, 'good', NULL, 'Pengujian throughput menghasilkan bitrate 843 kbps dengan data diterima 383 KB dan packet loss sangat tinggi.', '2026-05-10 04:39:57', '2026-05-10 05:02:10'),
(17, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'gunung', 'POINT-06', 'south', -166.24, -51.61, 850.00, 174.07, 867.64, 0.1741, -6.75350300000000, 106.72691100000000, -6.75396710000000, 106.72540550000000, 915.50, -59.00, 50.00, 288.00, 'connected', 76.49, 50.00, -109.00, 'good', NULL, 'Pengujian throughput menghasilkan bitrate 288 kbps dengan packet loss 39% dan data diterima sebesar 278 KB.', '2026-05-10 05:13:56', '2026-05-10 05:13:56'),
(18, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'gunung', 'POINT-07', 'south', -59.96, -176.02, 853.00, 185.95, 873.03, 0.1860, -6.75350300000000, 106.72691100000000, -6.75508600000000, 106.72636800000000, 915.50, -95.00, 11.00, 173.00, 'connected', 77.06, 11.00, -106.00, 'moderate', NULL, 'Pengujian throughput menghasilkan bitrate 173 kbps dengan data diterima 70.3 KB dan packet loss sangat tinggi.', '2026-05-10 07:55:40', '2026-05-10 07:55:40'),
(19, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'gunung', 'POINT-08', 'south', -45.38, -229.84, 0.00, 234.28, 234.28, 0.2343, -6.75350300000000, 106.72691100000000, -6.75557000000000, 106.72650000000000, 915.50, -100.00, 5.00, 25.60, 'intermittent', 79.07, 5.00, -105.00, 'poor', NULL, NULL, '2026-05-10 08:18:55', '2026-05-10 08:18:55'),
(20, '2026-05-19', 'Parkiran Jiexpo Kemayoran', 'lapangan', 'POINT-09', 'west', -141.18, 10.56, 69.00, 141.57, 157.49, 0.1416, -6.14818700000000, 106.84862100000000, -6.14809200000000, 106.84734400000000, 915.50, -84.00, 20.00, 207.00, 'connected', 74.69, 20.00, -104.00, 'moderate', NULL, NULL, '2026-05-19 09:38:35', '2026-05-19 12:11:47'),
(21, '2026-05-19', 'Pantai Ancol', 'pantai', 'POINT-10', 'east', 164.52, 95.85, 72.00, 190.40, 203.56, 0.1904, -6.12011900000000, 106.84976200000000, -6.11925700000000, 106.85125000000000, 915.50, -103.00, 6.00, 21.00, 'intermittent', 77.27, 6.00, -109.00, 'poor', NULL, NULL, '2026-05-20 01:34:04', '2026-05-20 01:53:08'),
(22, '2026-05-19', 'Pantai Ancol', 'pantai', 'POINT-11', 'east', 64.77, 65.99, 70.00, 92.47, 115.97, 0.0925, -6.12011900000000, 106.84976200000000, -6.11952550000000, 106.85034780000000, 915.50, -82.00, 28.00, 342.00, 'connected', 70.99, 28.00, -110.00, 'good', NULL, NULL, '2026-05-20 01:51:34', '2026-05-20 01:51:34'),
(23, '2026-05-19', 'Pantai Ancol', 'pantai', 'POINT-12', 'west', -168.13, -9.18, 70.00, 168.38, 182.35, 0.1684, -6.12011900000000, 106.84976200000000, -6.12020160000000, 106.84824130000000, 915.50, -91.00, 16.00, 403.00, 'connected', 76.20, 16.00, -107.00, 'moderate', NULL, NULL, '2026-05-20 02:06:39', '2026-05-20 02:06:39'),
(24, '2026-05-19', 'Pantai Ancol', 'pantai', 'POINT-13', 'west', -198.98, -27.93, 70.00, 200.93, 212.77, 0.2009, -6.12011900000000, 106.84976200000000, -6.12037020000000, 106.84796230000000, 915.50, -99.00, 10.00, 21.00, 'intermittent', 77.73, 10.00, -109.00, 'moderate', NULL, NULL, '2026-05-20 02:16:37', '2026-05-20 02:16:37');

-- --------------------------------------------------------

--
-- Struktur dari tabel `response_time_tests`
--

CREATE TABLE `response_time_tests` (
  `id` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `command_type` varchar(50) DEFAULT NULL,
  `target_node_id` varchar(50) DEFAULT NULL,
  `request_time_ms` bigint(20) DEFAULT NULL,
  `response_time_ms` bigint(20) DEFAULT NULL,
  `network_mode` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `response_time_total_ms` decimal(10,2) DEFAULT NULL,
  `average_response_time` decimal(10,2) DEFAULT NULL,
  `minimum_response_time` decimal(10,2) DEFAULT NULL,
  `maximum_response_time` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `signal_penetration_tests`
--

CREATE TABLE `signal_penetration_tests` (
  `id` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `location_name` varchar(100) DEFAULT NULL,
  `obstacle_type` enum('wall','building','trees','vehicle','hangar','hill','none') DEFAULT NULL,
  `condition_type` enum('LOS','NLOS') DEFAULT NULL,
  `distance_meter` decimal(10,2) DEFAULT NULL,
  `rssi_before_dbm` decimal(6,2) DEFAULT NULL,
  `rssi_after_dbm` decimal(6,2) DEFAULT NULL,
  `snr_before_db` decimal(6,2) DEFAULT NULL,
  `snr_after_db` decimal(6,2) DEFAULT NULL,
  `packet_sent` int(11) DEFAULT 0,
  `packet_received` int(11) DEFAULT 0,
  `bitrate_kbps` decimal(10,2) DEFAULT NULL,
  `rssi_loss` decimal(6,2) DEFAULT NULL,
  `snr_loss` decimal(6,2) DEFAULT NULL,
  `packet_loss_percent` decimal(5,2) DEFAULT NULL,
  `penetration_loss_db` decimal(6,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `signal_penetration_tests`
--

INSERT INTO `signal_penetration_tests` (`id`, `test_date`, `location_name`, `obstacle_type`, `condition_type`, `distance_meter`, `rssi_before_dbm`, `rssi_after_dbm`, `snr_before_db`, `snr_after_db`, `packet_sent`, `packet_received`, `bitrate_kbps`, `rssi_loss`, `snr_loss`, `packet_loss_percent`, `penetration_loss_db`, `notes`, `created_at`, `updated_at`) VALUES
(2, '2026-05-07', 'Gedung UAI Lantai 6', 'building', 'NLOS', 25.00, -23.00, -74.00, 76.00, 19.00, 159, 84, 700.00, 51.00, 57.00, 47.17, 51.00, 'dilakukan dari lantai 6 ke 5 menggunakan lift', '2026-05-07 11:39:51', '2026-05-07 11:39:51'),
(3, '2026-05-07', 'Gedung UAI Lantai 4', 'wall', 'NLOS', 50.00, -23.00, -92.00, 19.00, 9.00, 62, 24, 415.99, 69.00, 10.00, 61.29, 69.00, NULL, '2026-05-07 12:19:29', '2026-05-07 12:19:29'),
(4, '2026-05-07', 'Gedung UAI Lantai 3', 'wall', 'NLOS', 75.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 21.00, 0.00, 0.00, 0.00, 0.00, NULL, '2026-05-07 12:41:29', '2026-05-07 12:41:29'),
(5, '2026-05-07', 'Gedung UAI Lantai 6', 'none', 'LOS', 0.00, 0.00, 0.00, 0.00, 0.00, 63, 63, 21.00, 0.00, 0.00, 0.00, 0.00, NULL, '2026-05-07 13:00:12', '2026-05-07 13:00:12'),
(6, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'trees', 'NLOS', 128.64, -6.00, -54.00, 104.00, 55.00, 60, 28, 843.00, 48.00, 49.00, 53.33, 48.00, NULL, '2026-05-10 04:45:12', '2026-05-10 04:45:12'),
(7, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'trees', 'NLOS', 174.07, -6.00, -59.00, 101.00, 50.00, 61, 9, 288.00, 53.00, 51.00, 85.25, 53.00, NULL, '2026-05-10 05:17:26', '2026-05-10 05:17:26'),
(9, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'trees', 'NLOS', 185.94, -11.00, -95.01, 95.00, 11.00, 60, 26, 173.00, 84.01, 84.00, 56.67, 84.01, NULL, '2026-05-10 08:06:37', '2026-05-10 08:06:37'),
(10, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'trees', 'NLOS', 234.28, -11.00, -100.00, 95.00, 11.00, 60, 0, 21.00, 89.00, 84.00, 100.00, 89.00, NULL, '2026-05-10 08:22:27', '2026-05-10 08:22:27'),
(11, '2026-05-19', 'Parkiran Jiexpo Kemayoran', 'none', 'LOS', 141.57, -18.00, -84.00, 86.00, 20.00, 62, 26, 207.00, 66.00, 66.00, 58.06, 66.00, NULL, '2026-05-19 12:13:49', '2026-05-19 12:13:49'),
(12, '2026-05-19', 'Pantai Ancol', 'trees', 'NLOS', 190.40, -9.00, -103.00, 100.00, 6.00, 62, 0, 21.00, 94.00, 94.00, 100.00, 94.00, NULL, '2026-05-20 01:36:30', '2026-05-20 01:53:56'),
(13, '2026-05-19', 'Pantai Ancol', 'trees', 'NLOS', 92.47, -9.00, -82.00, 100.00, 28.00, 61, 28, 342.00, 73.00, 72.00, 54.10, 73.00, NULL, '2026-05-20 01:56:53', '2026-05-20 01:56:53'),
(14, '2026-05-19', 'Pantai Ancol', 'trees', 'NLOS', 168.38, -9.00, -91.00, 100.00, 16.00, 61, 19, 403.00, 82.00, 84.00, 68.85, 82.00, NULL, '2026-05-20 02:08:24', '2026-05-20 02:08:24'),
(15, '2026-05-19', 'Pantai Ancol', 'trees', 'NLOS', 200.93, -9.00, -99.00, 100.00, 10.00, 61, 0, 21.00, 90.00, 90.00, 100.00, 90.00, NULL, '2026-05-20 02:17:42', '2026-05-20 02:17:42');

-- --------------------------------------------------------

--
-- Struktur dari tabel `slave_camera_tests`
--

CREATE TABLE `slave_camera_tests` (
  `id` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `location_name` varchar(100) DEFAULT NULL,
  `node_id` varchar(50) DEFAULT NULL,
  `distance_meter` decimal(10,2) DEFAULT NULL,
  `resolution` varchar(50) DEFAULT NULL,
  `fps` int(11) DEFAULT NULL,
  `image_quality_score` int(11) DEFAULT NULL,
  `camera_delay_ms` decimal(10,2) DEFAULT NULL,
  `packet_loss_percent` decimal(5,2) DEFAULT NULL,
  `status` enum('success','fail') DEFAULT NULL,
  `average_camera_delay` decimal(10,2) DEFAULT NULL,
  `average_fps` decimal(5,2) DEFAULT NULL,
  `camera_quality_category` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `slave_camera_tests`
--

INSERT INTO `slave_camera_tests` (`id`, `test_date`, `location_name`, `node_id`, `distance_meter`, `resolution`, `fps`, `image_quality_score`, `camera_delay_ms`, `packet_loss_percent`, `status`, `average_camera_delay`, `average_fps`, `camera_quality_category`, `notes`, `created_at`, `updated_at`) VALUES
(1, '2026-04-27', 'uai', '82y492492', 8.00, '720p', 89, 5, 3.00, 4.00, 'success', 3.00, 89.00, 'good', NULL, '2026-04-27 10:58:35', '2026-04-27 10:58:35');

-- --------------------------------------------------------

--
-- Struktur dari tabel `star_topology_tests`
--

CREATE TABLE `star_topology_tests` (
  `id` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `location_name` varchar(100) DEFAULT NULL,
  `master_id` varchar(50) DEFAULT NULL,
  `total_slave_nodes` int(11) DEFAULT NULL,
  `active_slave_nodes` int(11) DEFAULT NULL,
  `distance_average_meter` decimal(10,2) DEFAULT NULL,
  `average_latency_ms` decimal(10,2) DEFAULT NULL,
  `average_throughput_kbps` decimal(10,2) DEFAULT NULL,
  `packet_sent` int(11) DEFAULT 0,
  `packet_received` int(11) DEFAULT 0,
  `packet_loss_percent` decimal(5,2) DEFAULT NULL,
  `node_success_rate` decimal(5,2) DEFAULT NULL,
  `gateway_cpu_usage_percent` decimal(5,2) DEFAULT NULL,
  `gateway_temperature_c` decimal(5,2) DEFAULT NULL,
  `topology_status` enum('stable','degraded','critical') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `star_topology_tests`
--

INSERT INTO `star_topology_tests` (`id`, `test_date`, `location_name`, `master_id`, `total_slave_nodes`, `active_slave_nodes`, `distance_average_meter`, `average_latency_ms`, `average_throughput_kbps`, `packet_sent`, `packet_received`, `packet_loss_percent`, `node_success_rate`, `gateway_cpu_usage_percent`, `gateway_temperature_c`, `topology_status`, `notes`, `created_at`, `updated_at`) VALUES
(1, '2026-04-19', 'Lapangan Terbuka A', 'NODE-MASTER-01', 4, 4, 250.00, 85.20, 42000.00, 4000, 3980, 0.50, 100.00, 65.00, 58.00, 'stable', 'All 4 slave nodes connected successfully', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(2, '2026-04-19', 'Lapangan Terbuka A', 'NODE-MASTER-01', 4, 3, 500.00, 125.50, 36000.00, 3000, 2940, 2.00, 75.00, 70.00, 62.00, 'degraded', 'One slave node disconnected', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(3, '2026-04-19', 'Lapangan Terbuka A', 'NODE-MASTER-01', 4, 2, 750.00, 185.30, 28000.00, 2000, 1900, 5.00, 50.00, 75.00, 68.00, 'critical', 'Multiple nodes disconnected, topology degraded', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(4, '2026-04-19', 'Lapangan Terbuka A', 'NODE-MASTER-01', 4, 4, 300.00, 95.80, 45000.00, 5000, 4985, 0.30, 100.00, 55.00, 48.00, 'stable', 'Stable operation with 4 nodes', '2026-04-27 10:54:22', '2026-04-27 10:54:22'),
(5, '2026-04-19', 'Lapangan Terbuka A', 'NODE-MASTER-01', 4, 1, 1000.00, 250.00, 12000.00, 1000, 500, 50.00, 25.00, 80.00, 72.00, 'critical', 'Critical state - only 1 node available', '2026-04-27 10:54:22', '2026-04-27 10:54:22');

-- --------------------------------------------------------

--
-- Struktur dari tabel `test_locations`
--

CREATE TABLE `test_locations` (
  `id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL,
  `location_type` enum('lapangan','hangar','pantai','gunung','indoor','outdoor') DEFAULT NULL,
  `latitude` decimal(17,14) DEFAULT NULL,
  `longitude` decimal(18,14) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `test_locations`
--

INSERT INTO `test_locations` (`id`, `location_name`, `location_type`, `latitude`, `longitude`, `description`, `created_at`, `updated_at`, `notes`) VALUES
(1, 'Lapangan Terbuka A', 'lapangan', -6.20880000000000, 106.84560000000000, 'Area lapangan terbuka untuk pengujian jangkauan', '2026-04-27 10:54:22', '2026-04-27 10:54:22', NULL),
(2, 'Hangar Utama', 'hangar', -6.20900000000000, 106.84600000000000, 'Bangunan hangar tertutup', '2026-04-27 10:54:22', '2026-04-27 10:54:22', NULL),
(3, 'Pantai Indah', 'pantai', -6.21000000000000, 106.84700000000000, 'Area pantai dengan kondisi cuaca ekstrem', '2026-04-27 10:54:22', '2026-04-27 10:54:22', NULL),
(4, 'Base Camp Gunung', 'gunung', -6.21100000000000, 106.84800000000000, 'Area pegunungan dengan elevasi tinggi', '2026-04-27 10:54:22', '2026-04-27 10:54:22', NULL),
(5, 'Laboratorium Indoor', 'indoor', -6.20800000000000, 106.84400000000000, 'Ruangan indoor terkontrol', '2026-04-27 10:54:22', '2026-04-27 10:54:22', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `text_message_inbox_logs`
--

CREATE TABLE `text_message_inbox_logs` (
  `id` int(11) NOT NULL,
  `received_at` datetime DEFAULT current_timestamp(),
  `source_node` varchar(50) DEFAULT NULL,
  `target_node_id` varchar(50) DEFAULT NULL,
  `source_ip` varchar(45) DEFAULT NULL,
  `message_text` text NOT NULL,
  `raw_payload` text DEFAULT NULL,
  `rssi_dbm` int(11) DEFAULT NULL,
  `slave_uptime_ms` bigint(20) DEFAULT NULL,
  `delivery_status` enum('success','fail') DEFAULT 'success',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `text_message_inbox_logs`
--

INSERT INTO `text_message_inbox_logs` (`id`, `received_at`, `source_node`, `target_node_id`, `source_ip`, `message_text`, `raw_payload`, `rssi_dbm`, `slave_uptime_ms`, `delivery_status`, `created_at`, `updated_at`) VALUES
(3, '2026-05-07 01:15:35', 'SLAVE-HALOW-01', 'MASTER-RASPI-4', '192.168.1.113', 'Halo master, ini pesan dari slave.', '{\"method\":\"GET\",\"query\":{\"source\":\"SLAVE-HALOW-01\",\"target\":\"MASTER-RASPI-4\",\"message\":\"Halo master, ini pesan dari slave.\",\"uptime_ms\":\"269119\",\"rssi_dbm\":\"0\",\"firmware_version\":\"text-msg-v8-20260507\"},\"body\":\"\"}', 0, 269119, 'success', '2026-05-06 18:15:35', '2026-05-06 18:15:35');

-- --------------------------------------------------------

--
-- Struktur dari tabel `text_message_logs`
--

CREATE TABLE `text_message_logs` (
  `id` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `source_node` varchar(50) DEFAULT NULL,
  `target_node_id` varchar(50) DEFAULT NULL,
  `target_ip` varchar(45) NOT NULL,
  `target_port` int(11) DEFAULT 80,
  `protocol` enum('HTTP') DEFAULT 'HTTP',
  `endpoint` varchar(120) DEFAULT '/api/message',
  `message_text` text NOT NULL,
  `request_payload` text DEFAULT NULL,
  `response_status_code` int(11) DEFAULT NULL,
  `response_body` text DEFAULT NULL,
  `latency_ms` decimal(10,2) DEFAULT NULL,
  `delivery_status` enum('success','fail') DEFAULT 'fail',
  `error_message` text DEFAULT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `text_message_logs`
--

INSERT INTO `text_message_logs` (`id`, `test_date`, `source_node`, `target_node_id`, `target_ip`, `target_port`, `protocol`, `endpoint`, `message_text`, `request_payload`, `response_status_code`, `response_body`, `latency_ms`, `delivery_status`, `error_message`, `sent_at`, `created_at`, `updated_at`) VALUES
(1, '2026-05-06', 'MASTER-RASPI-4', 'SLAVE-HALOW-01', '192.168.1.113', 80, 'HTTP', '/api/message', 'tess', '{\"source\":\"MASTER-RASPI-4\",\"target\":\"SLAVE-HALOW-01\",\"message\":\"tess\",\"sent_at_ms\":1778084360653}', 0, '', 5006.95, 'fail', 'Connection timed out after 5006 milliseconds', '2026-05-06 23:19:25', '2026-05-06 16:19:25', '2026-05-06 16:19:25'),
(2, '2026-05-06', 'MASTER-RASPI-4', 'SLAVE-HALOW-01', '192.168.1.113', 80, 'HTTP', '/api/message', 'tess', '{\"source\":\"MASTER-RASPI-4\",\"target\":\"SLAVE-HALOW-01\",\"message\":\"tess\",\"sent_at_ms\":1778086306055}', 0, '', 5002.70, 'fail', 'Connection timed out after 5002 milliseconds', '2026-05-06 23:51:51', '2026-05-06 16:51:51', '2026-05-06 16:51:51'),
(3, '2026-05-06', 'MASTER-RASPI-4', 'SLAVE-HALOW-01', '192.168.1.113', 80, 'HTTP', '/api/message', 'yes', '{\"source\":\"MASTER-RASPI-4\",\"target\":\"SLAVE-HALOW-01\",\"message\":\"yes\",\"sent_at_ms\":1778089218073}', 200, '{\"ok\":true,\"device\":\"SLAVE-HALOW-01\",\"message_id\":4,\"received_ms\":438412,\"bytes\":3,\"source\":\"MASTER-RASPI-4\",\"message\":\"yes\"}', 70.60, 'success', '', '2026-05-07 00:40:32', '2026-05-06 17:40:32', '2026-05-06 17:40:32'),
(4, '2026-05-06', 'MASTER-RASPI-4', 'SLAVE-HALOW-01', '192.168.1.113', 80, 'HTTP', '/api/message', 'tes 123', '{\"source\":\"MASTER-RASPI-4\",\"target\":\"SLAVE-HALOW-01\",\"message\":\"tes 123\",\"sent_at_ms\":1778091424294}', 200, '{\"ok\":true,\"device\":\"SLAVE-HALOW-01\",\"message_count\":1,\"has_message\":true,\"last_received_ms\":395213,\"last_source\":\"MASTER-RASPI-4\",\"last_message\":\"tes 123\"}', 33458.90, 'success', '', '2026-05-07 01:17:55', '2026-05-06 18:17:55', '2026-05-06 18:17:55');

-- --------------------------------------------------------

--
-- Struktur dari tabel `throughput_tests`
--

CREATE TABLE `throughput_tests` (
  `id` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `location_name` varchar(100) DEFAULT NULL,
  `environment_type` enum('lapangan','hangar','pantai','gunung','indoor','outdoor') DEFAULT NULL,
  `node_id` varchar(50) DEFAULT NULL,
  `distance_meter` decimal(10,2) DEFAULT NULL,
  `data_sent_kb` decimal(10,2) DEFAULT NULL,
  `data_received_kb` decimal(10,2) DEFAULT NULL,
  `transmission_time_second` decimal(10,2) DEFAULT NULL,
  `rssi_dbm` decimal(6,2) DEFAULT NULL,
  `snr_db` decimal(6,2) DEFAULT NULL,
  `bitrate_kbps` decimal(10,2) DEFAULT NULL,
  `throughput_kbps` decimal(10,2) DEFAULT NULL,
  `packet_delivery_ratio_percent` decimal(5,2) DEFAULT NULL,
  `data_loss_percent` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `throughput_tests`
--

INSERT INTO `throughput_tests` (`id`, `test_date`, `location_name`, `environment_type`, `node_id`, `distance_meter`, `data_sent_kb`, `data_received_kb`, `transmission_time_second`, `rssi_dbm`, `snr_db`, `bitrate_kbps`, `throughput_kbps`, `packet_delivery_ratio_percent`, `data_loss_percent`, `notes`, `created_at`, `updated_at`) VALUES
(6, '2026-05-07', 'Gedung UAI Lantai 6', 'indoor', 'NODE-SLAVE-01', 25.00, 322.00, 253.00, 10.29, -74.00, 26.00, 256.00, 201.42, 78.57, 21.43, 'Packet loss 49%, jitter 0.000 ms, 80/164 datagrams lost', '2026-05-07 11:54:50', '2026-05-07 11:54:50'),
(7, '2026-05-07', 'Gedung UAI Lantai 4', 'indoor', 'NODE-SLAVE-01', 50.00, 525.00, 525.00, 10.34, -85.00, 15.00, 416.00, 415.94, 100.00, 0.00, NULL, '2026-05-07 12:26:15', '2026-05-07 12:26:15'),
(8, '2026-05-07', 'Gedung UAI Lantai 3', 'indoor', 'NODE-SLAVE-01', 75.00, 25.60, 25.60, 10.00, 0.00, 0.00, 21.00, 20.97, 100.00, 0.00, NULL, '2026-05-07 12:43:40', '2026-05-07 12:43:40'),
(9, '2026-05-07', 'Gedung UAI Lantai 6', 'indoor', 'MASTER-01', 0.00, 17.83, 25.60, 10.00, 0.00, 0.00, 21.00, 20.97, 143.58, -43.58, NULL, '2026-05-07 13:03:04', '2026-05-07 13:03:04'),
(10, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'gunung', 'NODE-SLAVE-01', 128.33, 1044.48, 383.00, 60000.00, -54.00, 56.00, 843.00, 0.05, 36.67, 63.33, NULL, '2026-05-10 04:53:19', '2026-05-10 05:20:47'),
(11, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'gunung', 'NODE-SLAVE-01', 174.07, 380.00, 278.00, 60000.00, -59.00, 50.00, 288.00, 0.04, 73.16, 26.84, NULL, '2026-05-10 05:20:38', '2026-05-10 05:20:38'),
(12, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'gunung', 'NODE-SLAVE-01', 185.95, 217.00, 70.30, 60.00, -95.00, 11.00, 173.00, 9.60, 32.40, 67.60, NULL, '2026-05-10 08:02:06', '2026-05-10 08:02:06'),
(13, '2026-05-10', 'Kaki Gunung Salak (Cidahu)', 'gunung', 'NODE-SLAVE-01', 234.28, 25.60, 25.60, 60.00, -100.00, 4.99, 21.00, 3.50, 100.00, 0.00, NULL, '2026-05-10 08:25:53', '2026-05-10 08:25:53'),
(14, '2026-05-19', 'Parkiran Jiexpo Kemayoran', 'lapangan', 'NODE-SLAVE-01', 141.57, 273.00, 115.00, 10.80, -84.00, 20.00, 207.00, 87.23, 42.12, 57.88, NULL, '2026-05-19 12:15:52', '2026-05-19 12:15:52'),
(15, '2026-05-19', 'Pantai Ancol', 'pantai', 'NODE-SLAVE-01', 190.40, 62.00, 0.00, 60.00, -103.00, 6.00, 0.00, 0.00, 0.00, 100.00, NULL, '2026-05-20 01:39:30', '2026-05-20 01:54:16'),
(16, '2026-05-19', 'Pantai Ancol', 'pantai', 'NODE-SLAVE-01', 92.47, 431.00, 149.00, 60.00, -82.00, 28.00, 342.00, 20.34, 34.57, 65.43, NULL, '2026-05-20 01:59:09', '2026-05-20 01:59:09'),
(17, '2026-05-19', 'Pantai Ancol', 'pantai', 'NODE-SLAVE-01', 168.38, 537.00, 25.80, 60.00, -91.00, 16.00, 403.00, 3.52, 4.80, 95.20, NULL, '2026-05-20 02:10:18', '2026-05-20 02:10:18'),
(18, '2026-05-19', 'Pantai Ancol', 'pantai', 'NODE-SLAVE-01', 200.93, 25.60, 0.00, 60.00, -99.00, 10.00, 21.00, 0.00, 0.00, 100.00, NULL, '2026-05-20 02:19:08', '2026-05-20 02:19:08');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','operator','viewer') DEFAULT 'operator',
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `full_name`, `email`, `created_at`, `updated_at`, `notes`) VALUES
(1, 'admin', '$2y$10$nTEDKXvfEWtCbiXSFHmBNOc6kqfr0fibJTTF47Kwtli4RYexCgDTW', 'admin', 'System Admin', 'admin@wifiholow.test', '2026-04-27 10:33:23', '2026-04-27 10:33:47', 'Administrator utama'),
(2, 'operator1', '$2y$10$nTEDKXvfEWtCbiXSFHmBNOc6kqfr0fibJTTF47Kwtli4RYexCgDTW', 'operator', 'John Operator', 'john@wifiholow.test', '2026-04-27 10:54:22', '2026-04-27 10:54:22', 'Operator lapangan'),
(3, 'viewer1', '$2y$10$nTEDKXvfEWtCbiXSFHmBNOc6kqfr0fibJTTF47Kwtli4RYexCgDTW', 'viewer', 'Jane Viewer', 'jane@wifiholow.test', '2026-04-27 10:54:22', '2026-04-27 10:54:22', 'User pembaca');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `authentication_tests`
--
ALTER TABLE `authentication_tests`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `command_execution_tests`
--
ALTER TABLE `command_execution_tests`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `connectivity_tests`
--
ALTER TABLE `connectivity_tests`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `data_monitoring`
--
ALTER TABLE `data_monitoring`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `device_id` (`device_id`);

--
-- Indeks untuk tabel `encryption_tests`
--
ALTER TABLE `encryption_tests`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `generated_reports`
--
ALTER TABLE `generated_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indeks untuk tabel `interference_tests`
--
ALTER TABLE `interference_tests`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `latency_tests`
--
ALTER TABLE `latency_tests`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `mesh_topology_analysis`
--
ALTER TABLE `mesh_topology_analysis`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `monitoring_delay_tests`
--
ALTER TABLE `monitoring_delay_tests`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `power_consumption_tests`
--
ALTER TABLE `power_consumption_tests`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `range_tests`
--
ALTER TABLE `range_tests`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `response_time_tests`
--
ALTER TABLE `response_time_tests`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `signal_penetration_tests`
--
ALTER TABLE `signal_penetration_tests`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `slave_camera_tests`
--
ALTER TABLE `slave_camera_tests`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `star_topology_tests`
--
ALTER TABLE `star_topology_tests`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `test_locations`
--
ALTER TABLE `test_locations`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `text_message_inbox_logs`
--
ALTER TABLE `text_message_inbox_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `text_message_logs`
--
ALTER TABLE `text_message_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `throughput_tests`
--
ALTER TABLE `throughput_tests`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `authentication_tests`
--
ALTER TABLE `authentication_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `command_execution_tests`
--
ALTER TABLE `command_execution_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `connectivity_tests`
--
ALTER TABLE `connectivity_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `data_monitoring`
--
ALTER TABLE `data_monitoring`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `encryption_tests`
--
ALTER TABLE `encryption_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `generated_reports`
--
ALTER TABLE `generated_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `interference_tests`
--
ALTER TABLE `interference_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `latency_tests`
--
ALTER TABLE `latency_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `mesh_topology_analysis`
--
ALTER TABLE `mesh_topology_analysis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `monitoring_delay_tests`
--
ALTER TABLE `monitoring_delay_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `power_consumption_tests`
--
ALTER TABLE `power_consumption_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `range_tests`
--
ALTER TABLE `range_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT untuk tabel `response_time_tests`
--
ALTER TABLE `response_time_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `signal_penetration_tests`
--
ALTER TABLE `signal_penetration_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `slave_camera_tests`
--
ALTER TABLE `slave_camera_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `star_topology_tests`
--
ALTER TABLE `star_topology_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `test_locations`
--
ALTER TABLE `test_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `text_message_inbox_logs`
--
ALTER TABLE `text_message_inbox_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `text_message_logs`
--
ALTER TABLE `text_message_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `throughput_tests`
--
ALTER TABLE `throughput_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `generated_reports`
--
ALTER TABLE `generated_reports`
  ADD CONSTRAINT `generated_reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
