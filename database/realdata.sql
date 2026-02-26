-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 19, 2026 at 01:01 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `downtimedb`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(4, 2, 'login_failed', 'Login attempt failed: Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:05:18'),
(5, 2, 'login_failed', 'Login attempt failed: Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:05:57'),
(6, 2, 'login_failed', 'Login attempt failed: Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:07:53'),
(7, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:13:11'),
(8, 18, 'created_service', 'Created service: Mobile Money', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:20:11'),
(9, 18, 'created_component', 'Created component: Credit for service ID 141', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:25:17'),
(10, 18, 'created_component', 'Created component: Debit for service ID 141', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:25:17'),
(11, 18, 'created_component', 'Created component: Reversal for service ID 141', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:25:17'),
(12, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:37:28'),
(13, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:37:30'),
(14, 18, 'created_service', 'Created service: VAS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:54:40'),
(15, 18, 'updated_service', 'Updated service ID 142 to: VASGATE', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:54:47'),
(16, 18, 'created_service', 'Created service: FUNDGATE', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:55:01'),
(17, 18, 'created_service', 'Created service: MPAY', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:55:13'),
(18, 18, 'created_service', 'Created service: JUSTPAY', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:55:57'),
(19, 18, 'created_service', 'Created service: OVA', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:56:41'),
(20, 18, 'created_component', 'Created component: Bills for service ID 142', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:57:42'),
(21, 18, 'created_component', 'Created component: Topup for service ID 142', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:57:42');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `company_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`company_id`, `company_name`, `category`, `created_at`, `updated_at`) VALUES
(1, 'Abii National', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(2, 'AirtelTigo', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(3, 'All', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(4, 'Atwima', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(5, 'BOA', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(6, 'Bestpoint', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(7, 'ECG', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(8, 'GCB', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(9, 'MTN', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(10, 'Multi Choice', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(11, 'NIB', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(12, 'NLA', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(13, 'PBL', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(14, 'SISL', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(15, 'STCCU', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(16, 'Telecel', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(17, 'VisionFund', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(18, 'eTranzact', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05');

-- --------------------------------------------------------

--
-- Table structure for table `downtime_incidents`
--

CREATE TABLE `downtime_incidents` (
  `downtime_id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `actual_start_time` datetime NOT NULL,
  `actual_end_time` datetime DEFAULT NULL,
  `downtime_minutes` int(11) DEFAULT NULL,
  `is_planned` tinyint(1) DEFAULT 0,
  `downtime_category` enum('Network','Server','Maintenance','Third-party','Other') DEFAULT 'Other',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `downtime_incidents`
--
DELIMITER $$
CREATE TRIGGER `calculate_downtime_minutes` BEFORE UPDATE ON `downtime_incidents` FOR EACH ROW BEGIN
        IF NEW.actual_end_time IS NOT NULL AND (OLD.actual_end_time IS NULL OR NEW.actual_end_time != OLD.actual_end_time) THEN
            SET NEW.downtime_minutes = TIMESTAMPDIFF(MINUTE, NEW.actual_start_time, NEW.actual_end_time);
        END IF;
    END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

CREATE TABLE `incidents` (
  `incident_id` int(11) NOT NULL,
  `incident_ref` varchar(20) DEFAULT NULL,
  `source` enum('Internal','External') DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `service_id` int(11) NOT NULL,
  `component_id` int(11) DEFAULT NULL,
  `incident_type_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL COMMENT 'Detailed description of what happened during the incident',
  `impact_level` enum('Low','Medium','High','Critical') NOT NULL DEFAULT 'Low',
  `priority` enum('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  `root_cause` text DEFAULT NULL,
  `root_cause_file` varchar(255) DEFAULT NULL,
  `lessons_learned` text DEFAULT NULL,
  `lessons_learned_file` varchar(255) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `actual_start_time` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','resolved') NOT NULL DEFAULT 'pending',
  `reported_by` int(11) NOT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_affected_companies`
--

CREATE TABLE `incident_affected_companies` (
  `incident_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_attachments`
--

CREATE TABLE `incident_attachments` (
  `attachment_id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_company_history`
--

CREATE TABLE `incident_company_history` (
  `history_id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `action` enum('added','removed') NOT NULL,
  `changed_by` int(11) NOT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_templates`
--

CREATE TABLE `incident_templates` (
  `template_id` int(11) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `component_id` int(11) DEFAULT NULL,
  `impact_level` enum('low','medium','high','critical') DEFAULT 'medium',
  `description` text NOT NULL,
  `root_cause` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `usage_count` int(11) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_types`
--

CREATE TABLE `incident_types` (
  `type_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incident_types`
--

INSERT INTO `incident_types` (`type_id`, `service_id`, `name`, `is_active`) VALUES
(944, 141, 'Transaction Failure', 1),
(945, 141, 'Payment Gateway Timeout', 1),
(946, 141, 'Declined Transactions Spike', 1),
(947, 141, 'Duplicate Transaction', 1),
(948, 141, 'Settlement Failure', 1),
(949, 141, 'Reversal/Refund Processing Error', 1),
(950, 141, 'Payment Switch Outage', 1),
(951, 141, 'Complete Service Outage', 1),
(952, 141, 'Partial Service Outage', 1),
(953, 141, 'Service Degradation', 1),
(954, 141, 'Planned Maintenance', 1),
(955, 141, 'Unplanned Downtime', 1),
(956, 141, 'High Latency / Slow Response', 1),
(957, 141, 'API Performance Degradation', 1),
(958, 141, 'Database Performance Issue', 1),
(959, 141, 'Queue Backlog / Processing Delay', 1),
(960, 141, 'Server/Hardware Failure', 1),
(961, 141, 'Network Connectivity Issue', 1),
(962, 141, 'DNS Resolution Failure', 1),
(963, 141, 'Load Balancer Failure', 1),
(964, 141, 'SSL/TLS Certificate Issue', 1),
(965, 141, 'Cloud Provider Outage', 1),
(966, 141, 'Unauthorized Access Attempt', 1),
(967, 141, 'Data Breach / Suspected Breach', 1),
(968, 141, 'DDoS Attack', 1),
(969, 141, 'Fraud / Suspicious Activity', 1),
(970, 141, 'Account Takeover Attempt', 1),
(971, 141, 'Third-Party API Failure', 1),
(972, 141, 'Bank/Acquirer Connectivity Issue', 1),
(973, 141, 'NIBSS / Interbank Connectivity Issue', 1),
(974, 141, 'Card Scheme (Visa/Mastercard) Outage', 1),
(975, 141, 'USSD Platform Failure', 1),
(976, 141, 'Mobile Money Integration Failure', 1),
(977, 141, 'Data Sync Failure', 1),
(978, 141, 'Data Corruption', 1),
(979, 141, 'Reporting / Reconciliation Error', 1),
(980, 141, 'Database Connection Failure', 1),
(981, 141, 'Application Bug / Code Error', 1),
(982, 141, 'Deployment / Release Issue', 1),
(983, 141, 'Configuration Error', 1),
(984, 141, 'Authentication / Login Failure', 1),
(985, 141, 'Regulatory Reporting Failure', 1),
(986, 141, 'AML/KYC System Failure', 1);

-- --------------------------------------------------------

--
-- Table structure for table `incident_updates`
--

CREATE TABLE `incident_updates` (
  `update_id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(255) NOT NULL,
  `update_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `service_name`, `created_at`, `updated_at`) VALUES
(141, 'Mobile Money', '2026-02-18 23:20:11', '2026-02-18 23:20:11'),
(142, 'VASGATE', '2026-02-18 23:54:40', '2026-02-18 23:54:47'),
(143, 'FUNDGATE', '2026-02-18 23:55:01', '2026-02-18 23:55:01'),
(144, 'MPAY', '2026-02-18 23:55:13', '2026-02-18 23:55:13'),
(145, 'JUSTPAY', '2026-02-18 23:55:57', '2026-02-18 23:55:57'),
(146, 'OVA', '2026-02-18 23:56:41', '2026-02-18 23:56:41');

-- --------------------------------------------------------

--
-- Table structure for table `service_components`
--

CREATE TABLE `service_components` (
  `component_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_components`
--

INSERT INTO `service_components` (`component_id`, `service_id`, `name`, `is_active`) VALUES
(1, 141, 'Credit', 1),
(2, 141, 'Debit', 1),
(3, 141, 'Reversal', 1),
(4, 142, 'Bills', 1),
(5, 142, 'Topup', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sla_targets`
--

CREATE TABLE `sla_targets` (
  `target_id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `target_uptime` decimal(5,2) NOT NULL DEFAULT 99.99,
  `business_hours_start` time DEFAULT '09:00:00',
  `business_hours_end` time DEFAULT '17:00:00',
  `business_days` set('Mon','Tue','Wed','Thu','Fri','Sat','Sun') DEFAULT 'Mon,Tue,Wed,Thu,Fri',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `changed_password` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `full_name`, `phone`, `location`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`, `changed_password`) VALUES
(2, 'harry_opata', 'harry_opata@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Harry Opata', '256591308', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(3, 'maxwell_eshun', 'maxwell_eshun@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Maxwell Eshun', '533866740', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(4, 'eric_fillipe_arthur', 'eric_fillipe_arthur@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Eric Fillipe Arthur', '201471988', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(5, 'fredrick_hanson', 'fredrick_hanson@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Fredrick Hanson', '552150123', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(6, 'mary_asante', 'mary_asante@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Mary Asante', '534488985', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(7, 'takyi_owusu_mensah', 'takyi_owusu_mensah@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Takyi Owusu Mensah', '542672470', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(8, 'simon_owusu_ansah', 'simon_owusu_ansah@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Simon Owusu Ansah', '593489937', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(9, 'takyi_owusumensah_', 'takyi_owusumensah_@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Takyi Owusu-Mensah ', '542672470', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(10, 'takyi_owusumensah', 'takyi_owusumensah@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Takyi Owusu-Mensah', '542672470', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(11, 'japheth_boateng', 'japheth_boateng@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Japheth Boateng', '233244571716', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(12, 'prince_brako_ofori', 'prince_brako_ofori@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Prince Brako Ofori', '207812962', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(13, 'japthet_boateng', 'japthet_boateng@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Japthet Boateng', '244571716', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(14, 'eshun_maxwell', 'eshun_maxwell@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Eshun Maxwell', '248919669', '3rd Floor Heritage Tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(15, 'philip_larm', 'philip_larm@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Philip Larm', '244418885', '3rd Floor Heritage Tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(16, 'seth_awiakye', 'seth_awiakye@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Seth Awiakye', '541568646', '3rd Floor Heritage Tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(17, 'leonard_l_ninsau', 'leonard_l_ninsau@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Leonard L. Ninsau', '245446733', '3rd Floor Heritage Tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(18, 'admin', 'admin@etranzact.com', '$2y$10$B5h1Jr3E5LLwUDzCgRWFIuCtsvArJBGwJe.aO4lX2Voo2ezCEha8i', 'System Administrator', NULL, NULL, 'admin', 1, '2026-02-18 23:37:30', '2026-02-18 23:12:52', '2026-02-18 23:37:30', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`company_id`);

--
-- Indexes for table `downtime_incidents`
--
ALTER TABLE `downtime_incidents`
  ADD PRIMARY KEY (`downtime_id`),
  ADD KEY `incident_id` (`incident_id`);

--
-- Indexes for table `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`incident_id`),
  ADD UNIQUE KEY `unique_incident_ref` (`incident_ref`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `component_id` (`component_id`),
  ADD KEY `incident_type_id` (`incident_type_id`),
  ADD KEY `reported_by` (`reported_by`),
  ADD KEY `idx_actual_start_time` (`actual_start_time`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_source` (`source`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `incident_affected_companies`
--
ALTER TABLE `incident_affected_companies`
  ADD PRIMARY KEY (`incident_id`,`company_id`),
  ADD KEY `fk_iac_company` (`company_id`);

--
-- Indexes for table `incident_attachments`
--
ALTER TABLE `incident_attachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `idx_incident_id` (`incident_id`);

--
-- Indexes for table `incident_company_history`
--
ALTER TABLE `incident_company_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_incident_id` (`incident_id`),
  ADD KEY `idx_company_id` (`company_id`),
  ADD KEY `idx_changed_at` (`changed_at`);

--
-- Indexes for table `incident_templates`
--
ALTER TABLE `incident_templates`
  ADD PRIMARY KEY (`template_id`),
  ADD UNIQUE KEY `template_name` (`template_name`),
  ADD KEY `component_id` (`component_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_service` (`service_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_usage` (`usage_count`);

--
-- Indexes for table `incident_types`
--
ALTER TABLE `incident_types`
  ADD PRIMARY KEY (`type_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `incident_updates`
--
ALTER TABLE `incident_updates`
  ADD PRIMARY KEY (`update_id`),
  ADD KEY `incident_id` (`incident_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `service_components`
--
ALTER TABLE `service_components`
  ADD PRIMARY KEY (`component_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `sla_targets`
--
ALTER TABLE `sla_targets`
  ADD PRIMARY KEY (`target_id`),
  ADD UNIQUE KEY `unique_company_service` (`company_id`,`service_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `unique_username` (`username`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `downtime_incidents`
--
ALTER TABLE `downtime_incidents`
  MODIFY `downtime_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=255;

--
-- AUTO_INCREMENT for table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `incident_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=258;

--
-- AUTO_INCREMENT for table `incident_attachments`
--
ALTER TABLE `incident_attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incident_company_history`
--
ALTER TABLE `incident_company_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incident_templates`
--
ALTER TABLE `incident_templates`
  MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incident_types`
--
ALTER TABLE `incident_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=987;

--
-- AUTO_INCREMENT for table `incident_updates`
--
ALTER TABLE `incident_updates`
  MODIFY `update_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=469;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=147;

--
-- AUTO_INCREMENT for table `service_components`
--
ALTER TABLE `service_components`
  MODIFY `component_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sla_targets`
--
ALTER TABLE `sla_targets`
  MODIFY `target_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `downtime_incidents`
--
ALTER TABLE `downtime_incidents`
  ADD CONSTRAINT `fk_downtime_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE;

--
-- Constraints for table `incidents`
--
ALTER TABLE `incidents`
  ADD CONSTRAINT `fk_incident_component` FOREIGN KEY (`component_id`) REFERENCES `service_components` (`component_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_incident_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_incident_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_incident_type` FOREIGN KEY (`incident_type_id`) REFERENCES `incident_types` (`type_id`) ON DELETE SET NULL;

--
-- Constraints for table `incident_affected_companies`
--
ALTER TABLE `incident_affected_companies`
  ADD CONSTRAINT `fk_iac_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_iac_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_attachments`
--
ALTER TABLE `incident_attachments`
  ADD CONSTRAINT `fk_attachment_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_company_history`
--
ALTER TABLE `incident_company_history`
  ADD CONSTRAINT `incident_company_history_ibfk_1` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incident_company_history_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incident_company_history_ibfk_3` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_templates`
--
ALTER TABLE `incident_templates`
  ADD CONSTRAINT `incident_templates_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `incident_templates_ibfk_2` FOREIGN KEY (`component_id`) REFERENCES `service_components` (`component_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `incident_templates_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `incident_types`
--
ALTER TABLE `incident_types`
  ADD CONSTRAINT `fk_type_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_updates`
--
ALTER TABLE `incident_updates`
  ADD CONSTRAINT `fk_updates_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE;

--
-- Constraints for table `service_components`
--
ALTER TABLE `service_components`
  ADD CONSTRAINT `fk_component_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;

--
-- Constraints for table `sla_targets`
--
ALTER TABLE `sla_targets`
  ADD CONSTRAINT `fk_sla_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sla_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
