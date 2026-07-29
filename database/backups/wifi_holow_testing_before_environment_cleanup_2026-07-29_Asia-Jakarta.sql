-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: wifi_holow_testing
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `wifi_holow_testing`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `wifi_holow_testing` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `wifi_holow_testing`;

--
-- Table structure for table `app_settings`
--

DROP TABLE IF EXISTS `app_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_settings`
--

LOCK TABLES `app_settings` WRITE;
/*!40000 ALTER TABLE `app_settings` DISABLE KEYS */;
INSERT INTO `app_settings` VALUES ('master_host','10.10.1.2','2026-07-28 09:00:11');
/*!40000 ALTER TABLE `app_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `authentication_tests`
--

DROP TABLE IF EXISTS `authentication_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `authentication_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `authentication_tests`
--

LOCK TABLES `authentication_tests` WRITE;
/*!40000 ALTER TABLE `authentication_tests` DISABLE KEYS */;
INSERT INTO `authentication_tests` VALUES (1,'2026-04-25','operator','valid_user','password',100,100,0,100.00,0.00,'All valid users authenticated','2026-04-27 10:54:22','2026-04-27 10:54:22'),(2,'2026-04-25','operator','invalid_user','password',50,0,50,0.00,100.00,'All invalid users rejected','2026-04-27 10:54:22','2026-04-27 10:54:22'),(3,'2026-04-25','admin','wrong_password','password',20,0,20,0.00,100.00,'Failed login attempts blocked','2026-04-27 10:54:22','2026-04-27 10:54:22'),(4,'2026-04-25','viewer','valid_user','2fa',75,75,0,100.00,0.00,'2FA authentication successful','2026-04-27 10:54:22','2026-04-27 10:54:22'),(5,'2026-04-25','operator','unauthorized_access','none',10,0,10,0.00,100.00,'Unauthorized access attempts blocked','2026-04-27 10:54:22','2026-04-27 10:54:22');
/*!40000 ALTER TABLE `authentication_tests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `command_execution_tests`
--

DROP TABLE IF EXISTS `command_execution_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `command_execution_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `command_execution_tests`
--

LOCK TABLES `command_execution_tests` WRITE;
/*!40000 ALTER TABLE `command_execution_tests` DISABLE KEYS */;
INSERT INTO `command_execution_tests` VALUES (1,'2026-04-23','reset','dashboard','NODE-SLAVE-01',1000000,1000230,1001450,'success',230.00,1220.00,1450.00,100.00,'Successful reset command','2026-04-27 10:54:22','2026-04-27 10:54:22'),(2,'2026-04-23','configuration_update','dashboard','NODE-SLAVE-02',2000000,2000560,2002340,'success',560.00,1780.00,2340.00,100.00,'Configuration updated successfully','2026-04-27 10:54:22','2026-04-27 10:54:22'),(3,'2026-04-23','turn_off','dashboard','NODE-SLAVE-03',3000000,3001200,3001890,'success',1200.00,690.00,1890.00,100.00,'Node turned off successfully','2026-04-27 10:54:22','2026-04-27 10:54:22'),(4,'2026-04-23','restart','dashboard','NODE-SLAVE-01',4000000,4002500,4004200,'success',2500.00,1700.00,4200.00,100.00,'Node restarted successfully','2026-04-27 10:54:22','2026-04-27 10:54:22'),(5,'2026-04-23','shutdown','dashboard','NODE-MASTER-01',5000000,5000890,5001560,'success',890.00,670.00,1560.00,100.00,'Master node shutdown','2026-04-27 10:54:22','2026-04-27 10:54:22'),(6,'2026-05-08','status','WEB_MASTER','SLAVE_001',1778228049579,1778228052582,1778228052582,'fail',3003.00,0.00,3003.00,0.00,'UDP 192.168.1.113:5555 | payload=halow123 STATUS | reply=timeout','2026-05-08 08:14:12','2026-05-08 08:14:12'),(7,'2026-05-08','status','WEB_MASTER','SLAVE_001',1778228066199,1778228069206,1778228069206,'fail',3007.00,0.00,3007.00,0.00,'UDP 192.168.1.113:5555 | payload=halow123 STATUS | reply=timeout','2026-05-08 08:14:29','2026-05-08 08:14:29'),(8,'2026-05-08','status','WEB_MASTER','SLAVE_001',1778228300647,1778228303665,1778228303665,'fail',3018.00,0.00,3018.00,0.00,'UDP 192.168.1.113:5555 | payload=halow123 STATUS | reply=timeout','2026-05-08 08:18:23','2026-05-08 08:18:23'),(9,'2026-06-10','status','WEB_MASTER','SLAVE_001',1781090253043,1781090256047,1781090256047,'fail',3004.00,0.00,3004.00,0.00,'UDP 192.168.1.112:5555 | payload=halow123 STATUS | reply=timeout','2026-06-10 11:17:36','2026-06-10 11:17:36'),(10,'2026-07-27','status','WEB_MASTER','SLAVE_001',1785155652714,1785155655727,1785155655727,'fail',3013.00,0.00,3013.00,0.00,'UDP 10.20.10.5:5555 | payload=halow123 STATUS | reply=timeout','2026-07-27 12:34:15','2026-07-27 12:34:15'),(11,'2026-07-27','status','WEB_MASTER','SLAVE_001',1785162175065,1785162178075,1785162178075,'fail',3010.00,0.00,3010.00,0.00,'UDP 192.168.1.12:5555 | payload=halow123 STATUS | reply=timeout','2026-07-27 14:22:58','2026-07-27 14:22:58'),(12,'2026-07-27','status','WEB_MASTER','SLAVE_001',1785162184670,1785162187670,1785162187670,'fail',3000.00,0.00,3000.00,0.00,'UDP 192.168.1.12:5555 | payload=halow123 STATUS | reply=timeout','2026-07-27 14:23:07','2026-07-27 14:23:07');
/*!40000 ALTER TABLE `command_execution_tests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `connectivity_tests`
--

DROP TABLE IF EXISTS `connectivity_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `connectivity_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_date` date NOT NULL,
  `location_name` varchar(100) DEFAULT NULL,
  `environment_type` enum('lapangan','hangar','pantai','gunung','indoor','outdoor') DEFAULT NULL,
  `distance_meter` decimal(10,2) DEFAULT NULL COMMENT 'Distance between master and slave in meters',
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `connectivity_tests`
--

LOCK TABLES `connectivity_tests` WRITE;
/*!40000 ALTER TABLE `connectivity_tests` DISABLE KEYS */;
INSERT INTO `connectivity_tests` VALUES (6,'2026-05-07','Gedung UAI Lantai 5','indoor',25.00,'NODE-SLAVE-01','slave','connected',-68.00,30.00,159,84,75,47.17,52.83,60,'Indoor test from floor 6 to floor 5.','2026-05-07 11:27:26','2026-07-13 18:55:03'),(7,'2026-05-07','Gedung UAI Lantai 4','indoor',50.00,'NODE-SLAVE-01','slave','connected',-85.00,15.00,62,20,42,67.74,32.26,60,'Indoor/same coordinate reference.','2026-05-07 12:07:42','2026-07-13 18:55:03'),(8,'2026-05-07','Gedung UAI Lantai 3','indoor',75.00,'NODE-SLAVE-01','slave','intermittent',-86.00,12.00,0,0,0,0.00,0.00,60,'No packet data recorded; signal indicator detected but ping could not run.','2026-05-07 12:36:28','2026-07-13 18:55:03'),(9,'2026-05-07','Gedung UAI Lantai 6','indoor',0.00,'MASTER-01','master','connected',0.00,0.00,63,63,0,0.00,100.00,60,'Local baseline reference; received data exceeds sent data.','2026-05-07 12:56:44','2026-07-13 18:55:03'),(10,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung',128.64,'NODE-SLAVE-01','slave','connected',-54.00,56.00,60,28,32,53.33,46.67,60,'Throughput duration corrected from raw value 60000 ms to 60 s.','2026-05-10 04:32:03','2026-07-13 18:55:03'),(11,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung',174.07,'NODE-SLAVE-01','slave','connected',-59.00,50.00,61,9,52,85.25,14.75,60,'Throughput duration corrected from raw value 60000 ms to 60 s.','2026-05-10 05:09:03','2026-07-13 18:55:03'),(12,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung',185.95,'NODE-SLAVE-01','slave','connected',-95.00,11.00,60,27,33,55.00,45.00,60,'Farthest stable connected distance.','2026-05-10 07:52:02','2026-07-13 18:55:03'),(13,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung',234.28,'NODE-SLAVE-01','slave','intermittent',-100.00,5.00,60,0,60,100.00,0.00,60,'Maximum measured distance; intermittent/no packet received for connectivity.','2026-05-10 08:14:30','2026-07-13 18:55:03'),(14,'2026-05-19','Parkiran JIExpo Kemayoran','lapangan',141.57,'NODE-SLAVE-01','slave','connected',-84.00,20.00,62,26,36,58.06,41.94,60,'Open-field test point.','2026-05-19 12:10:37','2026-07-13 18:55:03'),(15,'2026-05-19','Pantai Ancol','pantai',190.40,'NODE-SLAVE-01','slave','intermittent',-103.00,6.00,62,0,62,100.00,0.00,60,'Timeout / no packet received.','2026-05-20 01:29:08','2026-07-13 18:55:03'),(16,'2026-05-19','Pantai Ancol','pantai',92.47,'NODE-SLAVE-01','slave','connected',-82.00,28.00,61,28,33,54.10,45.90,60,'Coastal connected point.','2026-05-20 01:49:19','2026-07-13 18:55:03'),(17,'2026-05-19','Pantai Ancol','pantai',168.38,'NODE-SLAVE-01','slave','connected',-91.00,16.00,61,19,42,68.85,31.15,60,'High jitter and high data loss.','2026-05-20 02:05:18','2026-07-13 18:55:03'),(18,'2026-05-19','Pantai Ancol','pantai',200.93,'NODE-SLAVE-01','slave','intermittent',-99.00,10.00,61,0,61,100.00,0.00,60,'Raw obstruction data recorded negative received packets in one dataset; treat as invalid/outlier.','2026-05-20 02:14:25','2026-07-13 18:55:03'),(19,'2026-06-03','Rusun Boing Kemayoran','outdoor',87.23,'NODE-SLAVE-01','slave','connected',-82.00,26.00,62,33,29,46.77,53.23,60,'Outdoor residential test point.','2026-06-03 10:33:31','2026-07-13 18:55:03'),(20,'2026-06-03','Rusun Boing Kemayoran','outdoor',120.44,'NODE-SLAVE-01','slave','connected',-88.00,19.00,62,28,34,54.84,45.16,60,'Check raw SNR before/after signs if needed.','2026-06-03 10:56:28','2026-07-13 18:55:03'),(21,'2026-06-03','Rusun Boing Kemayoran','outdoor',102.52,'NODE-SLAVE-01','slave','connected',-87.00,22.00,62,35,27,43.55,56.45,60,'Outdoor residential test point.','2026-06-03 11:06:40','2026-07-13 18:55:03');
/*!40000 ALTER TABLE `connectivity_tests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_monitoring`
--

DROP TABLE IF EXISTS `data_monitoring`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data_monitoring` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_monitoring`
--

LOCK TABLES `data_monitoring` WRITE;
/*!40000 ALTER TABLE `data_monitoring` DISABLE KEYS */;
INSERT INTO `data_monitoring` VALUES (1,'2026-04-21','NODE-SLAVE-01',1000000,85.00,7.20,1.10,45.00,-48.30,22.10,-6.20880000,106.84560000,'connected','normal',7.92,'normal','Normal operation','2026-04-27 10:54:22','2026-04-27 10:54:22'),(2,'2026-04-21','NODE-SLAVE-01',1060000,84.00,7.10,1.20,46.00,-49.10,21.80,-6.20890000,106.84570000,'connected','normal',8.52,'normal','Normal operation','2026-04-27 10:54:22','2026-04-27 10:54:22'),(3,'2026-04-21','NODE-SLAVE-01',1120000,83.00,7.00,1.30,47.00,-50.20,20.50,-6.20900000,106.84580000,'connected','warning',9.10,'warning','Battery decreasing','2026-04-27 10:54:22','2026-04-27 10:54:22'),(4,'2026-04-21','NODE-SLAVE-02',1000000,92.00,7.30,0.90,42.00,-45.20,25.30,-6.20910000,106.84590000,'connected','normal',6.57,'normal','Excellent condition','2026-04-27 10:54:22','2026-04-27 10:54:22'),(5,'2026-04-21','NODE-SLAVE-02',1060000,91.00,7.20,1.00,43.00,-46.10,24.80,-6.20920000,106.84600000,'connected','normal',7.20,'normal','Excellent condition','2026-04-27 10:54:22','2026-04-27 10:54:22');
/*!40000 ALTER TABLE `data_monitoring` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `devices`
--

DROP TABLE IF EXISTS `devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` varchar(50) NOT NULL,
  `device_type` enum('master','slave') NOT NULL,
  `device_name` varchar(100) DEFAULT NULL,
  `firmware_version` varchar(20) DEFAULT NULL,
  `hardware_version` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_id` (`device_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `devices`
--

LOCK TABLES `devices` WRITE;
/*!40000 ALTER TABLE `devices` DISABLE KEYS */;
INSERT INTO `devices` VALUES (1,'NODE-MASTER-01','master','Master Node Controller','v2.1.0','HW-1.0','active','2026-04-27 10:54:22','2026-04-27 10:54:22',NULL),(2,'NODE-SLAVE-01','slave','Slave Node 1','v2.1.0','HW-1.0','active','2026-04-27 10:54:22','2026-04-27 10:54:22',NULL),(3,'NODE-SLAVE-02','slave','Slave Node 2','v2.1.0','HW-1.0','active','2026-04-27 10:54:22','2026-04-27 10:54:22',NULL),(4,'NODE-SLAVE-03','slave','Slave Node 3','v2.0.5','HW-0.9','maintenance','2026-04-27 10:54:22','2026-04-27 10:54:22',NULL),(5,'NODE-SLAVE-04','slave','Slave Node 4','v2.1.0','HW-1.0','active','2026-04-27 10:54:22','2026-04-27 10:54:22',NULL);
/*!40000 ALTER TABLE `devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `encryption_tests`
--

DROP TABLE IF EXISTS `encryption_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `encryption_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_date` date NOT NULL,
  `protocol_used` varchar(50) DEFAULT NULL,
  `encryption_type` varchar(50) DEFAULT NULL,
  `key_length_bit` int(11) DEFAULT NULL,
  `sniffing_test_result` enum('readable','unreadable') DEFAULT NULL,
  `data_integrity_status` enum('valid','invalid') DEFAULT NULL,
  `encryption_status` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `encryption_tests`
--

LOCK TABLES `encryption_tests` WRITE;
/*!40000 ALTER TABLE `encryption_tests` DISABLE KEYS */;
/*!40000 ALTER TABLE `encryption_tests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `generated_reports`
--

DROP TABLE IF EXISTS `generated_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `generated_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `generated_by` (`generated_by`),
  CONSTRAINT `generated_reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `generated_reports`
--

LOCK TABLES `generated_reports` WRITE;
/*!40000 ALTER TABLE `generated_reports` DISABLE KEYS */;
INSERT INTO `generated_reports` VALUES (1,'Weekly Connectivity Test Report','connectivity','2026-04-15','2026-04-21','Lapangan Terbuka A','connectivity_tests','Weekly connectivity test analysis report','/reports/weekly_connectivity_20260421.pdf','pdf',1,'2026-04-27 10:54:22'),(2,'Range Test Analysis Report','range','2026-04-15','2026-04-15','Lapangan Terbuka A','range_tests','Range test distance analysis','/reports/range_test_20260415.pdf','pdf',1,'2026-04-27 10:54:22'),(3,'Power Consumption Weekly Report','power','2026-04-18','2026-04-24','All Locations','power_consumption_tests','Weekly power analysis','/reports/power_weekly_20260424.csv','csv',1,'2026-04-27 10:54:22');
/*!40000 ALTER TABLE `generated_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `interference_tests`
--

DROP TABLE IF EXISTS `interference_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `interference_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_date` date NOT NULL,
  `location_name` varchar(100) DEFAULT NULL,
  `environment_type` enum('lapangan','hangar','pantai','gunung','indoor','outdoor') DEFAULT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interference_tests`
--

LOCK TABLES `interference_tests` WRITE;
/*!40000 ALTER TABLE `interference_tests` DISABLE KEYS */;
INSERT INTO `interference_tests` VALUES (1,'2026-05-07','Gedung UAI Lantai 5','indoor','medium','wall, building',25.00,-68.00,30.00,201.42,3434.00,159,84,47.17,NULL,NULL,NULL,'Indoor test from floor 6 to floor 5.','2026-05-07 11:59:40','2026-07-13 18:55:03'),(2,'2026-05-07','Gedung UAI Lantai 4','indoor','medium','wall, building',50.00,-85.00,15.00,415.94,8091.47,62,20,67.74,NULL,NULL,NULL,'Indoor/same coordinate reference.','2026-05-07 12:27:42','2026-07-13 18:55:03'),(3,'2026-05-07','Gedung UAI Lantai 3','indoor','medium','wall, building',75.00,-86.00,12.00,21.00,0.00,0,0,0.00,NULL,NULL,NULL,'No packet data recorded; signal indicator detected but ping could not run.','2026-05-07 12:44:55','2026-07-13 18:55:03'),(4,'2026-05-07','Gedung UAI Lantai 6','indoor','low','none',0.00,0.00,0.00,20.97,0.19,63,63,0.00,NULL,NULL,NULL,'Local baseline reference; received data exceeds sent data.','2026-05-07 13:04:47','2026-07-13 18:55:03'),(5,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung','normal','Tree',128.64,-54.00,56.00,842.99,6976.49,60,28,53.33,NULL,NULL,NULL,'Throughput duration corrected from raw value 60000 ms to 60 s.','2026-05-10 04:55:14','2026-07-13 18:55:03'),(6,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung','high','Tree',174.07,-59.00,50.00,288.00,6629.57,61,9,85.25,NULL,NULL,NULL,'Throughput duration corrected from raw value 60000 ms to 60 s.','2026-05-10 05:21:51','2026-07-13 18:55:03'),(7,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung','high','tree & rock',185.95,-95.00,11.00,173.00,10061.96,60,27,55.00,NULL,NULL,NULL,'Farthest stable connected distance.','2026-05-10 08:03:28','2026-07-13 18:55:03'),(8,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung','high','tree & rock',234.28,-100.00,5.00,21.00,0.00,60,0,100.00,NULL,NULL,NULL,'Maximum measured distance; intermittent/no packet received for connectivity.','2026-05-10 08:27:18','2026-07-13 18:55:03'),(9,'2026-05-19','Parkiran JIExpo Kemayoran','lapangan','medium','Palm Tree',141.57,-84.00,20.00,105.00,9079.11,62,26,58.06,NULL,NULL,NULL,'Open-field test point.','2026-05-19 12:17:34','2026-07-13 18:55:03'),(10,'2026-05-19','Pantai Ancol','pantai','medium','Coconut Tree & rock',190.40,-103.00,6.00,21.00,0.00,62,0,100.00,NULL,NULL,NULL,'Timeout / no packet received.','2026-05-20 01:41:09','2026-07-13 18:55:03'),(11,'2026-05-19','Pantai Ancol','pantai','medium','Coconut Tree & rock',92.47,-82.00,28.00,342.00,8733.81,61,28,54.10,NULL,NULL,NULL,'Coastal connected point.','2026-05-20 02:00:09','2026-07-13 18:55:03'),(12,'2026-05-19','Pantai Ancol','pantai','high','Coconut Tree & rock',168.38,-91.00,16.00,403.00,7620.46,61,19,68.85,NULL,NULL,NULL,'High jitter and high data loss.','2026-05-20 02:11:48','2026-07-13 18:55:03'),(13,'2026-05-19','Pantai Ancol','pantai','high','Coconut Tree & rock',200.93,-99.00,10.00,21.00,0.00,61,0,100.00,NULL,NULL,NULL,'Raw obstruction data recorded negative received packets in one dataset; treat as invalid/outlier.','2026-05-20 02:19:53','2026-07-13 18:55:03'),(14,'2026-06-03','Rusun Boing Kemayoran','outdoor','medium','Building, Trees',87.23,-82.00,26.00,223.00,6818.61,62,33,46.77,NULL,NULL,NULL,'Outdoor residential test point.','2026-06-03 10:49:42','2026-07-13 18:55:03'),(15,'2026-06-03','Rusun Boing Kemayoran','outdoor','medium','Building, Trees',120.44,-88.00,19.00,253.00,4927.79,62,28,54.84,NULL,NULL,NULL,'Check raw SNR before/after signs if needed.','2026-06-03 11:04:51','2026-07-13 18:55:03'),(16,'2026-06-03','Rusun Boing Kemayoran','outdoor','medium','Building, Trees',102.52,-87.00,22.00,1020.00,1185.34,62,35,43.55,NULL,NULL,NULL,'Outdoor residential test point.','2026-06-03 11:13:56','2026-07-13 18:55:03');
/*!40000 ALTER TABLE `interference_tests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `latency_tests`
--

DROP TABLE IF EXISTS `latency_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `latency_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `latency_tests`
--

LOCK TABLES `latency_tests` WRITE;
/*!40000 ALTER TABLE `latency_tests` DISABLE KEYS */;
INSERT INTO `latency_tests` VALUES (6,'2026-05-07','Gedung UAI Lantai 5','indoor','NODE-SLAVE-01',25.00,1,1000,3000,159,84,'HaLow only',3434.00,587.26,47.17,3434.00,3434.00,3434.00,587.26,'Indoor test from floor 6 to floor 5.','2026-05-07 11:50:04','2026-07-13 18:55:03'),(7,'2026-05-07','Gedung UAI Lantai 4','indoor','NODE-SLAVE-01',50.00,1,NULL,NULL,62,20,'HaLow only',8091.47,956.44,67.74,8091.47,8091.47,8091.47,956.44,'Indoor/same coordinate reference.','2026-05-07 12:23:03','2026-07-13 18:55:03'),(8,'2026-05-07','Gedung UAI Lantai 3','indoor','NODE-SLAVE-01',75.00,1,NULL,NULL,0,0,'HaLow only',0.00,0.00,0.00,0.00,0.00,0.00,0.00,'No packet data recorded; signal indicator detected but ping could not run.','2026-05-07 12:42:30','2026-07-13 18:55:03'),(9,'2026-05-07','Gedung UAI Lantai 6','indoor','MASTER-01',0.00,1,500,1000,63,63,'HaLow only',0.19,0.01,0.00,0.19,0.19,0.19,0.01,'Local baseline reference; received data exceeds sent data.','2026-05-07 13:01:57','2026-07-13 18:55:03'),(10,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung','NODE-SLAVE-01',128.64,1,4000,17000,60,28,'HaLow only',6976.49,885.37,53.33,6976.49,6976.49,6976.49,885.37,'Throughput duration corrected from raw value 60000 ms to 60 s.','2026-05-10 04:49:50','2026-07-13 18:55:03'),(11,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung','NODE-SLAVE-01',174.07,1,3000,11000,61,9,'HaLow only',6629.57,866.82,85.25,6629.57,6629.57,6629.57,866.82,'Throughput duration corrected from raw value 60000 ms to 60 s.','2026-05-10 05:19:00','2026-07-13 18:55:03'),(12,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung','NODE-SLAVE-01',185.95,1,2000,10000,60,27,'HaLow only',10061.96,448.90,55.00,10061.96,10061.96,10061.96,448.90,'Farthest stable connected distance.','2026-05-10 07:57:44','2026-07-13 18:55:03'),(13,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung','NODE-SLAVE-01',234.28,1,1000,10000,60,0,'HaLow only',9000.00,0.00,100.00,9000.00,9000.00,9000.00,0.00,'Maximum measured distance; intermittent/no packet received for connectivity.','2026-05-10 08:24:41','2026-07-13 18:55:03'),(14,'2026-05-19','Parkiran JIExpo Kemayoran','lapangan','NODE-SLAVE-01',141.57,1,1000,11000,62,26,'HaLow only',9079.11,683.13,58.06,9079.11,9079.11,9079.11,683.13,'Open-field test point.','2026-05-19 12:14:49','2026-07-13 18:55:03'),(15,'2026-05-19','Pantai Ancol','pantai','NODE-SLAVE-01',190.40,1,1000,60000,62,0,'HaLow only',59000.00,0.00,100.00,59000.00,59000.00,59000.00,0.00,'Timeout / no packet received.','2026-05-20 01:37:36','2026-07-13 18:55:03'),(16,'2026-05-19','Pantai Ancol','pantai','NODE-SLAVE-01',92.47,1,1000,8000,61,28,'HaLow only',8733.81,795.30,54.10,8733.81,8733.81,8733.81,795.30,'Coastal connected point.','2026-05-20 01:58:15','2026-07-13 18:55:03'),(17,'2026-05-19','Pantai Ancol','pantai','NODE-SLAVE-01',168.38,1,1000,9000,61,19,'HaLow only',7620.48,2217.91,68.85,7620.48,7620.48,7620.48,2217.91,'High jitter and high data loss.','2026-05-20 02:09:25','2026-07-13 18:55:03'),(18,'2026-05-19','Pantai Ancol','pantai','NODE-SLAVE-01',200.93,1,1000,60000,61,0,'HaLow only',59000.00,0.00,100.00,59000.00,59000.00,59000.00,0.00,'Raw obstruction data recorded negative received packets in one dataset; treat as invalid/outlier.','2026-05-20 02:18:30','2026-07-13 18:55:03'),(19,'2026-06-03','Rusun Boing Kemayoran','outdoor','NODE-SLAVE-01',87.23,1,1000,6000,62,33,'HaLow only',6818.61,953.13,46.77,6818.61,6818.61,6818.61,953.13,'Outdoor residential test point.','2026-06-03 10:47:38','2026-07-13 18:55:03'),(20,'2026-06-03','Rusun Boing Kemayoran','outdoor','NODE-SLAVE-01',120.44,1,1000,2000,62,28,'HaLow only',4927.79,1277.72,54.84,4927.79,4927.79,4927.79,1277.72,'Check raw SNR before/after signs if needed.','2026-06-03 11:02:40','2026-07-13 18:55:03'),(21,'2026-06-03','Rusun Boing Kemayoran','outdoor','NODE-SLAVE-01',102.52,1,1000,10000,62,35,'HaLow only',7640.23,1185.34,43.55,7640.23,7640.23,7640.23,1185.34,'Outdoor residential test point.','2026-06-03 11:12:21','2026-07-13 18:55:03');
/*!40000 ALTER TABLE `latency_tests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mesh_topology_analysis`
--

DROP TABLE IF EXISTS `mesh_topology_analysis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mesh_topology_analysis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mesh_topology_analysis`
--

LOCK TABLES `mesh_topology_analysis` WRITE;
/*!40000 ALTER TABLE `mesh_topology_analysis` DISABLE KEYS */;
INSERT INTO `mesh_topology_analysis` VALUES (1,'2026-04-20','Small Mesh Network',5,2,15.50,8.50,45000.00,95.00,31.00,42.50,2235.2900,'Small tactical mesh network','2026-04-27 10:54:22','2026-04-27 10:54:22'),(2,'2026-04-20','Medium Mesh Network',10,3,18.20,8.50,38000.00,90.00,54.60,85.00,1058.8200,'Medium tactical mesh network','2026-04-27 10:54:22','2026-04-27 10:54:22'),(3,'2026-04-20','Large Mesh Network',15,4,20.10,8.50,32000.00,85.00,80.40,127.50,666.6700,'Large tactical mesh network','2026-04-27 10:54:22','2026-04-27 10:54:22'),(4,'2026-04-20','Dense Mesh Network',20,5,22.30,8.50,28000.00,80.00,111.50,170.00,470.5900,'Dense tactical mesh network','2026-04-27 10:54:22','2026-04-27 10:54:22'),(5,'2026-04-20','VSAT Hybrid Mesh',8,3,25.80,12.50,15000.00,75.00,77.40,100.00,112.5000,'Hybrid mesh with VSAT integration','2026-04-27 10:54:22','2026-04-27 10:54:22');
/*!40000 ALTER TABLE `mesh_topology_analysis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `monitoring_delay_tests`
--

DROP TABLE IF EXISTS `monitoring_delay_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `monitoring_delay_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `monitoring_delay_tests`
--

LOCK TABLES `monitoring_delay_tests` WRITE;
/*!40000 ALTER TABLE `monitoring_delay_tests` DISABLE KEYS */;
INSERT INTO `monitoring_delay_tests` VALUES (1,'2026-04-22','Motion Detected','NODE-SLAVE-01',1000000,1000450,'HaLow only',450.00,450.00,'fast','Fast real-time monitoring','2026-04-27 10:54:22','2026-04-27 10:54:22'),(2,'2026-04-22','Signal Loss','NODE-SLAVE-02',2000000,2002340,'HaLow only',2340.00,1395.00,'acceptable','Acceptable delay','2026-04-27 10:54:22','2026-04-27 10:54:22'),(3,'2026-04-22','Temperature Alert','NODE-SLAVE-03',3000000,3008900,'HaLow + VSAT',8900.00,4880.00,'slow','High delay with VSAT','2026-04-27 10:54:22','2026-04-27 10:54:22'),(4,'2026-04-22','Battery Low','NODE-SLAVE-01',4000000,4001200,'HaLow only',1200.00,3700.00,'acceptable','Normal delay','2026-04-27 10:54:22','2026-04-27 10:54:22'),(5,'2026-04-22','Connection Restored','NODE-SLAVE-02',5000000,5003400,'HaLow + VSAT',3400.00,3980.00,'acceptable','VSAT delay acceptable','2026-04-27 10:54:22','2026-04-27 10:54:22');
/*!40000 ALTER TABLE `monitoring_delay_tests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `power_consumption_tests`
--

DROP TABLE IF EXISTS `power_consumption_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `power_consumption_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `result` varchar(30) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `power_consumption_tests`
--

LOCK TABLES `power_consumption_tests` WRITE;
/*!40000 ALTER TABLE `power_consumption_tests` DISABLE KEYS */;
INSERT INTO `power_consumption_tests` VALUES (6,'2026-05-07','MASTER-01','master',0.00,0.00,2.14,NULL,0.77,NULL,NULL,0.00,0.00,0.00,0.0000,NULL,NULL,NULL,'Not Evaluated','Voltage and current recorded as zero','2026-05-07 13:07:27','2026-07-13 18:56:26'),(7,'2026-05-08','MASTER-01','master',5.16,0.67,3.53,NULL,1.49,NULL,NULL,0.00,0.00,3.46,12.2100,NULL,NULL,NULL,'Achieved','Testing with charger and multimeter check','2026-05-08 10:22:53','2026-07-13 18:56:26'),(8,'2026-05-10','MASTER-01','master',5.17,0.61,2.45,NULL,1.31,NULL,NULL,0.00,0.00,3.15,7.7200,NULL,NULL,NULL,'Achieved','Kaki Gunung Salak camp Cidahu','2026-05-10 06:18:28','2026-07-13 18:56:26'),(9,'2026-05-19','MASTER-01','master',5.01,0.67,0.85,NULL,0.37,NULL,NULL,-9.00,100.00,3.36,2.8600,NULL,NULL,NULL,'Achieved','Master valid record','2026-05-20 01:43:35','2026-07-13 18:56:26'),(10,'2026-06-03','MASTER-01','master',5.05,0.61,1.00,NULL,3.00,NULL,NULL,-16.00,106.00,3.08,3.0800,NULL,NULL,NULL,'Achieved','Master valid record','2026-06-03 10:51:01','2026-07-13 18:56:26'),(11,'2026-06-04','SLAVE-01','slave',5.04,0.60,1.00,NULL,NULL,NULL,NULL,NULL,NULL,3.02,3.0200,NULL,NULL,NULL,'Achieved','Slave power test','2026-06-19 09:11:30','2026-07-13 18:56:26'),(12,'2026-06-05','SLAVE-01','slave',5.08,0.63,2.00,NULL,NULL,NULL,NULL,NULL,NULL,3.20,6.4000,NULL,NULL,NULL,'Achieved','Slave power test','2026-06-19 09:11:30','2026-07-13 18:56:26'),(13,'2026-06-06','SLAVE-01','slave',5.12,0.65,3.00,NULL,NULL,NULL,NULL,NULL,NULL,3.33,9.9900,NULL,NULL,NULL,'Achieved','Slave power test','2026-06-19 09:11:30','2026-07-13 18:56:26');
/*!40000 ALTER TABLE `power_consumption_tests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `range_tests`
--

DROP TABLE IF EXISTS `range_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `range_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_date` date NOT NULL,
  `location_name` varchar(100) DEFAULT NULL,
  `environment_type` enum('lapangan','hangar','pantai','gunung','indoor','outdoor') DEFAULT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `range_tests`
--

LOCK TABLES `range_tests` WRITE;
/*!40000 ALTER TABLE `range_tests` DISABLE KEYS */;
INSERT INTO `range_tests` VALUES (12,'2026-05-07','Gedung UAI Lantai 5','indoor','POINT-01',NULL,NULL,NULL,NULL,25.00,NULL,0.0250,-6.23607000000000,106.79914300000000,-6.23607000000000,106.79914300000000,915.50,-68.00,30.00,1400.00,'connected',59.63,26.00,-98.00,'good','https://drive.google.com/file/d/1RBN3pxOakvJmYquJT6WCBsMYVthzVgLI/view?usp=drive_link','Indoor test from floor 6 to floor 5.','2026-05-07 11:35:16','2026-07-13 18:55:03'),(13,'2026-05-07','Gedung UAI Lantai 4','indoor','POINT-02','diagonal',NULL,NULL,NULL,50.00,NULL,0.0500,-6.23607000000000,106.79914300000000,-6.23607000000000,106.79914300000000,915.50,-85.00,15.00,416.00,'connected',65.65,9.00,-101.00,'poor','https://drive.google.com/file/d/1A_RIUVOr-6X39YHzgNKfAfcuS-biUmZA/view?usp=drive_link','Indoor/same coordinate reference.','2026-05-07 12:14:29','2026-07-13 18:55:03'),(14,'2026-05-07','Gedung UAI Lantai 3','indoor','POINT-03','diagonal',NULL,NULL,NULL,75.00,NULL,0.0750,-6.23607000000000,106.79914300000000,-6.23607000000000,106.79914300000000,915.50,-86.00,12.00,21.00,'intermittent',69.17,NULL,-98.00,'poor','https://drive.google.com/file/d/16dyM4oT4O6bRUTNhspHDuSaxra3eVN1F/view?usp=drive_link','No packet data recorded; signal indicator detected but ping could not run.','2026-05-07 12:40:40','2026-07-13 18:55:03'),(15,'2026-05-07','Gedung UAI Lantai 6','indoor','POINT-04','vertical',NULL,NULL,NULL,0.00,NULL,NULL,-6.23607000000000,106.79914300000000,-6.23607000000000,106.79914300000000,915.50,0.00,0.00,21.00,'connected',NULL,NULL,NULL,'poor','https://drive.google.com/file/d/1ioj8fCfKX9ujSA0twRCeR6VFUdz0zIUl/view?usp=drive_link','Local baseline reference; received data exceeds sent data.','2026-05-07 12:59:41','2026-07-13 18:55:03'),(16,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung','POINT-05','west',-128.33,-8.84,840.00,128.64,849.79,0.1286,-6.75350300000000,106.72691100000000,-6.75358250000000,106.72574880000000,915.50,-54.00,56.00,843.00,'connected',73.86,56.00,-110.00,'good','https://drive.google.com/file/d/1nQPyl8bPOXGmyNTct4a_sPfk-7I6xt_m/view?usp=drive_link','Throughput duration corrected from raw value 60000 ms to 60 s.','2026-05-10 04:39:57','2026-07-13 18:55:03'),(17,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung','POINT-06','south',-166.24,-51.61,850.00,174.07,867.64,0.1741,-6.75350300000000,106.72691100000000,-6.75396710000000,106.72540550000000,915.50,-59.00,50.00,288.00,'connected',76.49,50.00,-109.00,'good','https://drive.google.com/file/d/1oC2mFTzSop3UQBxEbBlKnKR0h9HWPOdK/view?usp=drive_link','Throughput duration corrected from raw value 60000 ms to 60 s.','2026-05-10 05:13:56','2026-07-13 18:55:03'),(18,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung','POINT-07','south',-59.96,-176.02,853.00,185.95,873.03,0.1859,-6.75350300000000,106.72691100000000,-6.75508600000000,106.72636800000000,915.50,-95.00,11.00,173.00,'connected',77.06,11.00,-106.00,'moderate','https://drive.google.com/file/d/15Fzs2FJAhOtMb_sLZ13WZFiqPeF6NX9N/view?usp=drive_link','Farthest stable connected distance.','2026-05-10 07:55:40','2026-07-13 18:55:03'),(19,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung','POINT-08','south',-45.38,-229.84,0.00,234.28,234.28,0.2343,-6.75350300000000,106.72691100000000,-6.75557000000000,106.72650000000000,915.50,-100.00,5.00,25.60,'intermittent',79.07,5.00,-105.00,'poor','https://drive.google.com/file/d/1hBwSkxcNMtGkJxMk91BVhIjqOO3gQBdU/view?usp=drive_link','Maximum measured distance; intermittent/no packet received for connectivity.','2026-05-10 08:18:55','2026-07-13 18:55:03'),(20,'2026-05-19','Parkiran JIExpo Kemayoran','lapangan','POINT-09','west',-141.18,10.56,69.00,141.57,157.49,0.1416,-6.14818700000000,106.84862100000000,-6.14809200000000,106.84734400000000,915.50,-84.00,20.00,207.00,'connected',74.69,20.00,-104.00,'moderate','https://drive.google.com/file/d/1wlUTzFAU9UqPrmYZdWpAq-Ja8-A__yQ-/view?usp=drive_link','Open-field test point.','2026-05-19 09:38:35','2026-07-13 18:55:03'),(21,'2026-05-19','Pantai Ancol','pantai','POINT-10','east',164.52,95.85,72.00,190.40,203.56,0.1904,-6.12011900000000,106.84976200000000,-6.11925700000000,106.85125000000000,915.50,-103.00,6.00,21.00,'intermittent',77.27,6.00,-109.00,'poor','https://drive.google.com/file/d/1D21GeIpWS7HSZ0v7lCi63phxNkgws0J2/view?usp=drive_link','Timeout / no packet received.','2026-05-20 01:34:04','2026-07-13 18:55:03'),(22,'2026-05-19','Pantai Ancol','pantai','POINT-11','east',64.77,65.99,70.00,92.47,115.97,0.0925,-6.12011900000000,106.84976200000000,-6.11952550000000,106.85034780000000,915.50,-82.00,28.00,342.00,'connected',70.99,28.00,-110.00,'good','https://drive.google.com/file/d/1Tu7J24aOZFYrQQEYqeoxLpdd8-bnT802/view?usp=drive_link','Coastal connected point.','2026-05-20 01:51:34','2026-07-13 18:55:03'),(23,'2026-05-19','Pantai Ancol','pantai','POINT-12','west',-168.13,-9.18,70.00,168.38,182.35,0.1684,-6.12011900000000,106.84976200000000,-6.12020160000000,106.84824130000000,915.50,-91.00,16.00,403.00,'connected',76.20,16.00,-107.00,'moderate','https://drive.google.com/file/d/1qLtmYp7UL3oBAS8MqtMK0usmabo3qa4S/view?usp=drive_link','High jitter and high data loss.','2026-05-20 02:06:39','2026-07-13 18:55:03'),(24,'2026-05-19','Pantai Ancol','pantai','POINT-13','west',-198.98,-27.93,70.00,200.93,212.77,0.2009,-6.12011900000000,106.84976200000000,-6.12037020000000,106.84796230000000,915.50,-99.00,10.00,21.00,'intermittent',77.73,10.00,-109.00,'moderate','https://drive.google.com/file/d/1TCraVj_Fs3T8crw0qV1g6hNdtEOgOfy4/view?usp=drive_link','Raw obstruction data recorded negative received packets in one dataset; treat as invalid/outlier.','2026-05-20 02:16:37','2026-07-13 18:55:03'),(25,'2026-06-03','Rusun Boing Kemayoran','outdoor','POINT-14','south',-49.97,-71.50,0.00,87.23,87.23,0.0872,-6.15402700000000,106.85419200000000,-6.15467000000000,106.85374000000000,915.50,-82.00,26.00,20000.00,'connected',70.49,26.00,-108.00,'good','https://drive.google.com/file/d/1vwKqQY9ol_tJnMD7j1xope4AFC5TOkCw/view?usp=drive_link','Outdoor residential test point.','2026-06-03 10:37:05','2026-07-13 18:55:03'),(26,'2026-06-03','Rusun Boing Kemayoran','outdoor','POINT-15','east',39.58,-113.75,0.00,120.44,120.44,0.1204,-6.15402700000000,106.85419200000000,-6.15505000000000,106.85455000000000,915.50,-88.00,19.00,20000.00,'connected',73.29,19.00,-107.00,'moderate','https://drive.google.com/file/d/1W45DUb0jpuo3mygXEel2iXBpXj5NxrCk/view?usp=drive_link','Check raw SNR before/after signs if needed.','2026-06-03 10:58:24','2026-07-13 18:55:03'),(27,'2026-06-03','Rusun Boing Kemayoran','outdoor','POINT-16','diagonal',46.21,-91.51,0.00,102.52,102.52,0.1025,-6.15402700000000,106.85419200000000,-6.15485000000000,106.85461000000000,915.50,-87.00,22.00,20000.00,'connected',71.89,22.00,-109.00,'good','https://drive.google.com/file/d/19CMPovV55qoRwXXJl2h2rOt9bb6mrV_m/view?usp=drive_link','Outdoor residential test point.','2026-06-03 11:07:58','2026-07-13 18:55:03');
/*!40000 ALTER TABLE `range_tests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `response_time_tests`
--

DROP TABLE IF EXISTS `response_time_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `response_time_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `response_time_tests`
--

LOCK TABLES `response_time_tests` WRITE;
/*!40000 ALTER TABLE `response_time_tests` DISABLE KEYS */;
/*!40000 ALTER TABLE `response_time_tests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `satellite_vsat_tests`
--

DROP TABLE IF EXISTS `satellite_vsat_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `satellite_vsat_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_date` date NOT NULL,
  `test_session_code` varchar(80) NOT NULL,
  `planned_trials` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `trial_number` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `location_name` varchar(100) DEFAULT NULL,
  `test_operator` varchar(100) DEFAULT NULL,
  `weather_condition` enum('cerah','berawan','hujan_ringan','hujan_lebat') DEFAULT NULL,
  `node_id` varchar(50) NOT NULL DEFAULT 'MASTER-VSAT',
  `connection_mode` enum('WiFi AP + VSAT','Ethernet + VSAT') DEFAULT NULL,
  `access_point_ssid` varchar(100) DEFAULT NULL,
  `ip_assignment` enum('DHCP','Static') DEFAULT 'DHCP',
  `master_ip` varchar(45) DEFAULT NULL,
  `gateway_ip` varchar(45) NOT NULL,
  `wan_ip` varchar(45) DEFAULT NULL,
  `vsat_provider` varchar(100) DEFAULT NULL,
  `satellite_name` varchar(100) DEFAULT NULL,
  `signal_quality_factor` smallint(5) unsigned DEFAULT NULL,
  `gateway_ping_status` enum('success','fail') NOT NULL,
  `server_target` varchar(150) NOT NULL,
  `internet_ping_status` enum('success','fail') NOT NULL,
  `packet_sent` int(11) NOT NULL DEFAULT 10,
  `packet_received` int(11) NOT NULL DEFAULT 10,
  `latency_min_ms` decimal(10,3) DEFAULT NULL,
  `latency_ms` decimal(10,3) DEFAULT NULL,
  `latency_max_ms` decimal(10,3) DEFAULT NULL,
  `jitter_ms` decimal(10,2) DEFAULT NULL,
  `packet_loss_percent` decimal(5,2) DEFAULT NULL,
  `download_kbps` decimal(12,2) DEFAULT NULL,
  `upload_kbps` decimal(12,2) DEFAULT NULL,
  `wifi_rssi_dbm` decimal(6,2) DEFAULT NULL,
  `wifi_snr_db` decimal(6,2) DEFAULT NULL,
  `vsat_lock_status` enum('locked','unlocked','not_checked') NOT NULL,
  `association_status` enum('associated','not_associated','not_checked') DEFAULT NULL,
  `tdma_status` enum('active','inactive','not_checked') DEFAULT NULL,
  `association_time` datetime DEFAULT NULL,
  `rx_signal_type` enum('SNR','C/N','Eb/N0') DEFAULT NULL,
  `rx_signal_db` decimal(8,2) DEFAULT NULL,
  `tx_power_dbm` decimal(8,2) DEFAULT NULL,
  `modem_uptime_minutes` bigint(20) DEFAULT NULL,
  `rain_fade_status` enum('none','mild','moderate','severe','not_checked') DEFAULT 'not_checked',
  `data_usage_mb` decimal(12,2) DEFAULT NULL,
  `server_protocol` enum('MQTT','HTTP API','HTTPS API','other','not_tested') DEFAULT 'not_tested',
  `server_delivery_status` enum('success','fail','not_tested') DEFAULT 'not_tested',
  `reconnect_count` int(11) DEFAULT 0,
  `last_successful_send` datetime DEFAULT NULL,
  `evidence_link` varchar(255) DEFAULT NULL,
  `overall_status` enum('passed','partial','failed') NOT NULL DEFAULT 'partial',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_satellite_vsat_session_trial` (`test_session_code`,`trial_number`),
  KEY `idx_satellite_vsat_test_date` (`test_date`),
  KEY `idx_satellite_vsat_status` (`overall_status`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `satellite_vsat_tests`
--

LOCK TABLES `satellite_vsat_tests` WRITE;
/*!40000 ALTER TABLE `satellite_vsat_tests` DISABLE KEYS */;
INSERT INTO `satellite_vsat_tests` VALUES (6,'2026-07-23','VSAT-20260723-01',3,1,NULL,'Aranda dan Adnan',NULL,'MASTER-VSAT',NULL,NULL,'DHCP',NULL,'10.20.10.1',NULL,NULL,'PSN-VI-PSN',65,'success','8.8.8.8','success',61,61,545.958,600.803,680.238,NULL,0.00,NULL,NULL,NULL,NULL,'locked','associated','active','2026-07-23 08:09:13',NULL,NULL,NULL,NULL,'not_checked',NULL,'not_tested','not_tested',0,NULL,NULL,'passed','Sumber terminal ping 8.8.8.8: 61 transmitted, 61 received, 0% loss, min/avg/max 545.958/600.803/680.238 ms. Screenshot modem menunjukkan System State Code 0.0.0, FLL Locked, SQF 65, TDMA Active, dan Association State ASSOCIATED.','2026-07-24 04:45:30','2026-07-24 04:45:30'),(7,'2026-07-23','VSAT-20260723-01',3,2,NULL,'Aranda dan Adnan',NULL,'MASTER-VSAT',NULL,NULL,'DHCP',NULL,'10.20.10.1',NULL,NULL,'PSN-VI-PSN',65,'success','8.8.8.8','success',61,61,545.300,599.880,660.605,NULL,0.00,NULL,NULL,NULL,NULL,'locked','associated','active','2026-07-23 08:09:13',NULL,NULL,NULL,NULL,'not_checked',NULL,'not_tested','not_tested',0,NULL,NULL,'passed','Sumber terminal ping 8.8.8.8: 61 transmitted, 61 received, 0% loss, min/avg/max 545.300/599.880/660.605 ms. Screenshot modem menunjukkan System State Code 0.0.0, FLL Locked, SQF 65, TDMA Active, dan Association State ASSOCIATED.','2026-07-24 04:45:30','2026-07-24 04:45:30'),(8,'2026-07-23','VSAT-20260723-01',3,3,NULL,'Aranda dan Adnan',NULL,'MASTER-VSAT',NULL,NULL,'DHCP',NULL,'10.20.10.1',NULL,NULL,'PSN-VI-PSN',65,'success','8.8.8.8','success',61,61,537.816,593.397,946.680,NULL,0.00,NULL,NULL,NULL,NULL,'locked','associated','active','2026-07-23 08:09:13',NULL,NULL,NULL,NULL,'not_checked',NULL,'not_tested','not_tested',0,NULL,NULL,'passed','Sumber terminal ping 8.8.8.8: 61 transmitted, 61 received, 0% loss, min/avg/max 537.816/593.397/946.680 ms. Screenshot modem menunjukkan System State Code 0.0.0, FLL Locked, SQF 65, TDMA Active, dan Association State ASSOCIATED.','2026-07-24 04:45:30','2026-07-24 04:45:30');
/*!40000 ALTER TABLE `satellite_vsat_tests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `signal_penetration_tests`
--

DROP TABLE IF EXISTS `signal_penetration_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `signal_penetration_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_date` date NOT NULL,
  `location_name` varchar(100) DEFAULT NULL,
  `environment_type` enum('lapangan','hangar','pantai','gunung','indoor','outdoor') DEFAULT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `signal_penetration_tests`
--

LOCK TABLES `signal_penetration_tests` WRITE;
/*!40000 ALTER TABLE `signal_penetration_tests` DISABLE KEYS */;
INSERT INTO `signal_penetration_tests` VALUES (2,'2026-05-07','Gedung UAI Lantai 5','indoor','building','NLOS',25.00,-23.00,-74.00,76.00,19.00,159,84,201.42,51.00,57.00,47.17,51.00,'Indoor test from floor 6 to floor 5.','2026-05-07 11:39:51','2026-07-13 18:55:03'),(3,'2026-05-07','Gedung UAI Lantai 4','indoor','wall','NLOS',50.00,-23.00,-92.00,19.00,9.00,62,20,415.94,69.00,10.00,67.74,69.00,'Indoor/same coordinate reference.','2026-05-07 12:19:29','2026-07-13 18:55:03'),(4,'2026-05-07','Gedung UAI Lantai 3','indoor','wall','NLOS',75.00,0.00,0.00,0.00,0.00,0,0,21.00,0.00,0.00,0.00,0.00,'No packet data recorded; signal indicator detected but ping could not run.','2026-05-07 12:41:29','2026-07-13 18:55:03'),(5,'2026-05-07','Gedung UAI Lantai 6','indoor','none','LOS',0.00,0.00,0.00,0.00,0.00,63,63,20.97,0.00,0.00,0.00,0.00,'Local baseline reference; received data exceeds sent data.','2026-05-07 13:00:12','2026-07-13 18:55:03'),(6,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung','trees','NLOS',128.64,-6.00,-54.00,104.00,55.00,60,28,842.99,48.00,49.00,53.33,48.00,'Throughput duration corrected from raw value 60000 ms to 60 s.','2026-05-10 04:45:12','2026-07-13 18:55:03'),(7,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung','trees','NLOS',174.07,-6.00,-59.00,101.00,50.00,61,9,288.00,53.00,51.00,85.25,53.00,'Throughput duration corrected from raw value 60000 ms to 60 s.','2026-05-10 05:17:26','2026-07-13 18:55:03'),(9,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung','trees','NLOS',185.95,-11.00,-95.01,95.00,11.00,60,27,173.00,84.01,84.00,55.00,84.01,'Farthest stable connected distance.','2026-05-10 08:06:37','2026-07-13 18:55:03'),(10,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung','trees','NLOS',234.28,-11.00,-100.00,95.00,11.00,60,0,21.00,89.00,84.00,100.00,89.00,'Maximum measured distance; intermittent/no packet received for connectivity.','2026-05-10 08:22:27','2026-07-13 18:55:03'),(11,'2026-05-19','Parkiran JIExpo Kemayoran','lapangan','none','LOS',141.57,-18.00,-84.00,86.00,20.00,62,26,105.00,66.00,66.00,58.06,66.00,'Open-field test point.','2026-05-19 12:13:49','2026-07-13 18:55:03'),(12,'2026-05-19','Pantai Ancol','pantai','trees','NLOS',190.40,-9.00,-103.00,100.00,6.00,62,0,21.00,94.00,94.00,100.00,94.00,'Timeout / no packet received.','2026-05-20 01:36:30','2026-07-13 18:55:03'),(13,'2026-05-19','Pantai Ancol','pantai','trees','NLOS',92.47,-9.00,-82.00,100.00,28.00,61,28,342.00,73.00,72.00,54.10,73.00,'Coastal connected point.','2026-05-20 01:56:53','2026-07-13 18:55:03'),(14,'2026-05-19','Pantai Ancol','pantai','trees','NLOS',168.38,-9.00,-91.00,100.00,16.00,61,19,403.00,82.00,84.00,68.85,82.00,'High jitter and high data loss.','2026-05-20 02:08:24','2026-07-13 18:55:03'),(15,'2026-05-19','Pantai Ancol','pantai','trees','NLOS',200.93,-9.00,-99.00,100.00,10.00,61,0,21.00,90.00,90.00,100.00,90.00,'Raw obstruction data recorded negative received packets in one dataset; treat as invalid/outlier.','2026-05-20 02:17:42','2026-07-13 18:55:03'),(16,'2026-06-03','Rusun Boing Kemayoran','outdoor','building','NLOS',87.23,-16.00,-82.00,90.00,26.00,62,33,223.00,66.00,64.00,46.77,66.00,'Outdoor residential test point.','2026-06-03 10:43:32','2026-07-13 18:55:03'),(17,'2026-06-03','Rusun Boing Kemayoran','outdoor','building','NLOS',120.44,-16.00,-88.00,-90.00,-19.00,62,28,253.00,72.00,-71.00,54.84,72.00,'Check raw SNR before/after signs if needed.','2026-06-03 11:01:06','2026-07-13 18:55:03'),(18,'2026-06-03','Rusun Boing Kemayoran','outdoor','building','NLOS',102.52,-16.00,-87.00,90.00,22.00,62,35,1020.00,71.00,68.00,43.55,71.00,'Outdoor residential test point.','2026-06-03 11:11:02','2026-07-13 18:55:03');
/*!40000 ALTER TABLE `signal_penetration_tests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `slave_camera_tests`
--

DROP TABLE IF EXISTS `slave_camera_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `slave_camera_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `slave_camera_tests`
--

LOCK TABLES `slave_camera_tests` WRITE;
/*!40000 ALTER TABLE `slave_camera_tests` DISABLE KEYS */;
INSERT INTO `slave_camera_tests` VALUES (1,'2026-04-27','uai','82y492492',8.00,'720p',89,5,3.00,4.00,'success',3.00,89.00,'good',NULL,'2026-04-27 10:58:35','2026-04-27 10:58:35');
/*!40000 ALTER TABLE `slave_camera_tests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `star_topology_tests`
--

DROP TABLE IF EXISTS `star_topology_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `star_topology_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `star_topology_tests`
--

LOCK TABLES `star_topology_tests` WRITE;
/*!40000 ALTER TABLE `star_topology_tests` DISABLE KEYS */;
INSERT INTO `star_topology_tests` VALUES (1,'2026-04-19','Lapangan Terbuka A','NODE-MASTER-01',4,4,250.00,85.20,42000.00,4000,3980,0.50,100.00,65.00,58.00,'stable','All 4 slave nodes connected successfully','2026-04-27 10:54:22','2026-04-27 10:54:22'),(2,'2026-04-19','Lapangan Terbuka A','NODE-MASTER-01',4,3,500.00,125.50,36000.00,3000,2940,2.00,75.00,70.00,62.00,'degraded','One slave node disconnected','2026-04-27 10:54:22','2026-04-27 10:54:22'),(3,'2026-04-19','Lapangan Terbuka A','NODE-MASTER-01',4,2,750.00,185.30,28000.00,2000,1900,5.00,50.00,75.00,68.00,'critical','Multiple nodes disconnected, topology degraded','2026-04-27 10:54:22','2026-04-27 10:54:22'),(4,'2026-04-19','Lapangan Terbuka A','NODE-MASTER-01',4,4,300.00,95.80,45000.00,5000,4985,0.30,100.00,55.00,48.00,'stable','Stable operation with 4 nodes','2026-04-27 10:54:22','2026-04-27 10:54:22'),(5,'2026-04-19','Lapangan Terbuka A','NODE-MASTER-01',4,1,1000.00,250.00,12000.00,1000,500,50.00,25.00,80.00,72.00,'critical','Critical state - only 1 node available','2026-04-27 10:54:22','2026-04-27 10:54:22');
/*!40000 ALTER TABLE `star_topology_tests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test_locations`
--

DROP TABLE IF EXISTS `test_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location_name` varchar(100) NOT NULL,
  `location_type` enum('lapangan','hangar','pantai','gunung','indoor','outdoor') DEFAULT NULL,
  `latitude` decimal(17,14) DEFAULT NULL,
  `longitude` decimal(18,14) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_locations`
--

LOCK TABLES `test_locations` WRITE;
/*!40000 ALTER TABLE `test_locations` DISABLE KEYS */;
INSERT INTO `test_locations` VALUES (1,'Lapangan Terbuka A','lapangan',-6.20880000000000,106.84560000000000,'Area lapangan terbuka untuk pengujian jangkauan','2026-04-27 10:54:22','2026-04-27 10:54:22',NULL),(2,'Hangar Utama','hangar',-6.20900000000000,106.84600000000000,'Bangunan hangar tertutup','2026-04-27 10:54:22','2026-04-27 10:54:22',NULL),(3,'Pantai Indah','pantai',-6.21000000000000,106.84700000000000,'Area pantai dengan kondisi cuaca ekstrem','2026-04-27 10:54:22','2026-04-27 10:54:22',NULL),(4,'Base Camp Gunung','gunung',-6.21100000000000,106.84800000000000,'Area pegunungan dengan elevasi tinggi','2026-04-27 10:54:22','2026-04-27 10:54:22',NULL),(5,'Laboratorium Indoor','indoor',-6.20800000000000,106.84400000000000,'Ruangan indoor terkontrol','2026-04-27 10:54:22','2026-04-27 10:54:22',NULL);
/*!40000 ALTER TABLE `test_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `text_message_inbox_logs`
--

DROP TABLE IF EXISTS `text_message_inbox_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `text_message_inbox_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `text_message_inbox_logs`
--

LOCK TABLES `text_message_inbox_logs` WRITE;
/*!40000 ALTER TABLE `text_message_inbox_logs` DISABLE KEYS */;
INSERT INTO `text_message_inbox_logs` VALUES (3,'2026-05-07 01:15:35','SLAVE-HALOW-01','MASTER-RASPI-4','192.168.1.113','Halo master, ini pesan dari slave.','{\"method\":\"GET\",\"query\":{\"source\":\"SLAVE-HALOW-01\",\"target\":\"MASTER-RASPI-4\",\"message\":\"Halo master, ini pesan dari slave.\",\"uptime_ms\":\"269119\",\"rssi_dbm\":\"0\",\"firmware_version\":\"text-msg-v8-20260507\"},\"body\":\"\"}',0,269119,'success','2026-05-06 18:15:35','2026-05-06 18:15:35');
/*!40000 ALTER TABLE `text_message_inbox_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `text_message_logs`
--

DROP TABLE IF EXISTS `text_message_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `text_message_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `text_message_logs`
--

LOCK TABLES `text_message_logs` WRITE;
/*!40000 ALTER TABLE `text_message_logs` DISABLE KEYS */;
INSERT INTO `text_message_logs` VALUES (1,'2026-05-06','MASTER-RASPI-4','SLAVE-HALOW-01','192.168.1.113',80,'HTTP','/api/message','tess','{\"source\":\"MASTER-RASPI-4\",\"target\":\"SLAVE-HALOW-01\",\"message\":\"tess\",\"sent_at_ms\":1778084360653}',0,'',5006.95,'fail','Connection timed out after 5006 milliseconds','2026-05-06 23:19:25','2026-05-06 16:19:25','2026-05-06 16:19:25'),(2,'2026-05-06','MASTER-RASPI-4','SLAVE-HALOW-01','192.168.1.113',80,'HTTP','/api/message','tess','{\"source\":\"MASTER-RASPI-4\",\"target\":\"SLAVE-HALOW-01\",\"message\":\"tess\",\"sent_at_ms\":1778086306055}',0,'',5002.70,'fail','Connection timed out after 5002 milliseconds','2026-05-06 23:51:51','2026-05-06 16:51:51','2026-05-06 16:51:51'),(3,'2026-05-06','MASTER-RASPI-4','SLAVE-HALOW-01','192.168.1.113',80,'HTTP','/api/message','yes','{\"source\":\"MASTER-RASPI-4\",\"target\":\"SLAVE-HALOW-01\",\"message\":\"yes\",\"sent_at_ms\":1778089218073}',200,'{\"ok\":true,\"device\":\"SLAVE-HALOW-01\",\"message_id\":4,\"received_ms\":438412,\"bytes\":3,\"source\":\"MASTER-RASPI-4\",\"message\":\"yes\"}',70.60,'success','','2026-05-07 00:40:32','2026-05-06 17:40:32','2026-05-06 17:40:32'),(4,'2026-05-06','MASTER-RASPI-4','SLAVE-HALOW-01','192.168.1.113',80,'HTTP','/api/message','tes 123','{\"source\":\"MASTER-RASPI-4\",\"target\":\"SLAVE-HALOW-01\",\"message\":\"tes 123\",\"sent_at_ms\":1778091424294}',200,'{\"ok\":true,\"device\":\"SLAVE-HALOW-01\",\"message_count\":1,\"has_message\":true,\"last_received_ms\":395213,\"last_source\":\"MASTER-RASPI-4\",\"last_message\":\"tes 123\"}',33458.90,'success','','2026-05-07 01:17:55','2026-05-06 18:17:55','2026-05-06 18:17:55');
/*!40000 ALTER TABLE `text_message_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `throughput_tests`
--

DROP TABLE IF EXISTS `throughput_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `throughput_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `throughput_tests`
--

LOCK TABLES `throughput_tests` WRITE;
/*!40000 ALTER TABLE `throughput_tests` DISABLE KEYS */;
INSERT INTO `throughput_tests` VALUES (6,'2026-05-07','Gedung UAI Lantai 5','indoor','NODE-SLAVE-01',25.00,322.00,253.00,60.00,-68.00,30.00,1400.00,201.42,78.57,21.43,'Indoor test from floor 6 to floor 5.','2026-05-07 11:54:50','2026-07-13 18:55:03'),(7,'2026-05-07','Gedung UAI Lantai 4','indoor','NODE-SLAVE-01',50.00,525.00,525.00,60.00,-85.00,15.00,416.00,415.94,100.00,0.00,'Indoor/same coordinate reference.','2026-05-07 12:26:15','2026-07-13 18:55:03'),(8,'2026-05-07','Gedung UAI Lantai 3','indoor','NODE-SLAVE-01',75.00,25.60,25.60,60.00,-86.00,12.00,21.00,20.97,100.00,0.00,'No packet data recorded; signal indicator detected but ping could not run.','2026-05-07 12:43:40','2026-07-13 18:55:03'),(9,'2026-05-07','Gedung UAI Lantai 6','indoor','MASTER-01',0.00,17.83,25.60,60.00,0.00,0.00,21.00,20.97,143.58,-43.58,'Local baseline reference; received data exceeds sent data.','2026-05-07 13:03:04','2026-07-13 18:55:03'),(10,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung','NODE-SLAVE-01',128.64,1044.48,383.00,60.00,-54.00,56.00,843.00,52.29,36.67,63.33,'Throughput duration corrected from raw value 60000 ms to 60 s.','2026-05-10 04:53:19','2026-07-13 18:55:03'),(11,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung','NODE-SLAVE-01',174.07,380.00,278.00,60.00,-59.00,50.00,288.00,37.96,73.16,26.84,'Throughput duration corrected from raw value 60000 ms to 60 s.','2026-05-10 05:20:38','2026-07-13 18:55:03'),(12,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung','NODE-SLAVE-01',185.95,217.00,70.30,60.00,-95.00,11.00,173.00,9.60,32.40,67.60,'Farthest stable connected distance.','2026-05-10 08:02:06','2026-07-13 18:55:03'),(13,'2026-05-10','Kaki Gunung Salak (Cidahu)','gunung','NODE-SLAVE-01',234.28,25.60,25.60,60.00,-100.00,5.00,25.60,3.50,100.00,0.00,'Maximum measured distance; intermittent/no packet received for connectivity.','2026-05-10 08:25:53','2026-07-13 18:55:03'),(14,'2026-05-19','Parkiran JIExpo Kemayoran','lapangan','NODE-SLAVE-01',141.57,273.00,115.00,10.80,-84.00,20.00,207.00,87.23,42.12,57.88,'Open-field test point.','2026-05-19 12:15:52','2026-07-13 18:55:03'),(15,'2026-05-19','Pantai Ancol','pantai','NODE-SLAVE-01',190.40,62.00,0.00,60.00,-103.00,6.00,21.00,0.00,0.00,100.00,'Timeout / no packet received.','2026-05-20 01:39:30','2026-07-13 18:55:03'),(16,'2026-05-19','Pantai Ancol','pantai','NODE-SLAVE-01',92.47,431.00,149.00,60.00,-82.00,28.00,342.00,20.34,34.57,65.43,'Coastal connected point.','2026-05-20 01:59:09','2026-07-13 18:55:03'),(17,'2026-05-19','Pantai Ancol','pantai','NODE-SLAVE-01',168.38,537.00,25.80,60.00,-91.00,16.00,403.00,3.52,4.80,95.20,'High jitter and high data loss.','2026-05-20 02:10:18','2026-07-13 18:55:03'),(18,'2026-05-19','Pantai Ancol','pantai','NODE-SLAVE-01',200.93,25.60,0.00,60.00,-99.00,10.00,21.00,0.00,0.00,100.00,'Raw obstruction data recorded negative received packets in one dataset; treat as invalid/outlier.','2026-05-20 02:19:08','2026-07-13 18:55:03'),(19,'2026-06-03','Rusun Boing Kemayoran','outdoor','NODE-SLAVE-01',87.23,291.00,0.00,60.00,-82.00,26.00,20000.00,0.00,0.00,100.00,'Outdoor residential test point.','2026-06-03 10:48:23','2026-07-13 18:55:03'),(20,'2026-06-03','Rusun Boing Kemayoran','outdoor','NODE-SLAVE-01',120.44,314.00,0.00,60.00,-88.00,19.00,20000.00,0.00,0.00,100.00,'Check raw SNR before/after signs if needed.','2026-06-03 11:03:29','2026-07-13 18:55:03'),(21,'2026-06-03','Rusun Boing Kemayoran','outdoor','NODE-SLAVE-01',102.52,1259.52,0.00,60.00,-87.00,22.00,20000.00,0.00,0.00,100.00,'Outdoor residential test point.','2026-06-03 11:13:03','2026-07-13 18:55:03');
/*!40000 ALTER TABLE `throughput_tests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','operator','viewer') DEFAULT 'operator',
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$jueOHsLy1eNqUVCnC9JDpOwi2DfdI3WPA1EuoCxSdB63fa0PGRAe6','admin','System Admin','admin@wifiholow.test','2026-04-27 10:33:23','2026-07-27 12:15:38','Administrator utama'),(2,'operator1','$2y$10$nTEDKXvfEWtCbiXSFHmBNOc6kqfr0fibJTTF47Kwtli4RYexCgDTW','operator','John Operator','john@wifiholow.test','2026-04-27 10:54:22','2026-04-27 10:54:22','Operator lapangan'),(3,'viewer1','$2y$10$nTEDKXvfEWtCbiXSFHmBNOc6kqfr0fibJTTF47Kwtli4RYexCgDTW','viewer','Jane Viewer','jane@wifiholow.test','2026-04-27 10:54:22','2026-04-27 10:54:22','User pembaca'),(4,'viewer','$2y$10$nTEDKXvfEWtCbiXSFHmBNOc6kqfr0fibJTTF47Kwtli4RYexCgDTW','viewer','Read Only Viewer','viewer@wifiholow.test','2026-06-04 06:06:04','2026-06-04 06:06:04','User viewer read-only'),(6,'kakocta','$2y$10$t66jVMuqeBenxj/UDeDaB.EmxfwFpzm6jg/XFrCS/108PyiE1x9ve','viewer','Kak Octa','octarina@uai.ac.id','2026-06-04 06:14:08','2026-06-04 06:14:08',''),(7,'kitingshi','$2y$10$aBtZQz8i61NqwBqEAm4a2uRI2Vos5rpNxOlxPf2NsHFKfihr4P7JG','admin','Kiting','kitingshi123@gmail.com','2026-06-04 08:05:09','2026-06-04 08:05:09','');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'wifi_holow_testing'
--

--
-- Dumping routines for database 'wifi_holow_testing'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-29 15:42:28
