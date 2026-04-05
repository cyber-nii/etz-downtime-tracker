-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 05, 2026 at 10:01 PM
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

-- --------------------------------------------------------

--
-- Table structure for table `components`
--

CREATE TABLE `components` (
  `component_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
DELIMITER $$
CREATE TRIGGER `calculate_downtime_minutes_insert` BEFORE INSERT ON `downtime_incidents` FOR EACH ROW BEGIN
  IF NEW.actual_end_time IS NOT NULL THEN
    SET NEW.downtime_minutes = TIMESTAMPDIFF(MINUTE, NEW.actual_start_time, NEW.actual_end_time);
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `fraud_incidents`
--

CREATE TABLE `fraud_incidents` (
  `id` int(11) NOT NULL,
  `incident_ref` varchar(50) NOT NULL,
  `fraud_type` enum('card_fraud','account_takeover','transaction_fraud','internal_fraud','other') NOT NULL DEFAULT 'other',
  `service_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `financial_impact` decimal(15,2) DEFAULT NULL,
  `impact_level` enum('Low','Medium','High','Critical') NOT NULL DEFAULT 'Low',
  `priority` enum('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  `regulatory_reported` tinyint(1) NOT NULL DEFAULT 0,
  `regulatory_details` text DEFAULT NULL,
  `root_cause` text DEFAULT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `actual_start_time` datetime NOT NULL,
  `status` enum('pending','resolved') NOT NULL DEFAULT 'pending',
  `reported_by` int(11) NOT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `lessons_learned` text DEFAULT NULL,
  `resolvers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`resolvers`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fraud_incident_attachments`
--

CREATE TABLE `fraud_incident_attachments` (
  `id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fraud_incident_updates`
--

CREATE TABLE `fraud_incident_updates` (
  `update_id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(255) NOT NULL,
  `update_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

CREATE TABLE `incidents` (
  `incident_id` int(11) NOT NULL,
  `incident_ref` varchar(20) DEFAULT NULL,
  `service_id` int(11) NOT NULL,
  `component_id` int(11) DEFAULT NULL,
  `incident_type_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL COMMENT 'Detailed description of what happened during the incident',
  `impact_level` enum('Low','Medium','High','Critical') NOT NULL DEFAULT 'Low',
  `priority` enum('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  `incident_source` enum('internal','external') NOT NULL DEFAULT 'external',
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resolvers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`resolvers`))
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
-- Table structure for table `incident_components`
--

CREATE TABLE `incident_components` (
  `incident_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_templates`
--

CREATE TABLE `incident_templates` (
  `template_id` int(11) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `component_id` int(11) DEFAULT NULL,
  `incident_type_id` int(11) DEFAULT NULL,
  `impact_level` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
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
  `service_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_type_service_map`
--

CREATE TABLE `incident_type_service_map` (
  `service_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `security_incidents`
--

CREATE TABLE `security_incidents` (
  `id` int(11) NOT NULL,
  `incident_ref` varchar(50) NOT NULL,
  `threat_type` enum('phishing','unauthorized_access','data_breach','malware','social_engineering','other') NOT NULL DEFAULT 'other',
  `systems_affected` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `impact_level` enum('Low','Medium','High','Critical') NOT NULL DEFAULT 'Low',
  `priority` enum('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  `containment_status` enum('contained','ongoing','under_investigation') NOT NULL DEFAULT 'under_investigation',
  `escalated_to` varchar(500) DEFAULT NULL,
  `root_cause` text DEFAULT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `actual_start_time` datetime NOT NULL,
  `status` enum('pending','resolved') NOT NULL DEFAULT 'pending',
  `reported_by` int(11) NOT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `lessons_learned` text DEFAULT NULL,
  `resolvers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`resolvers`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_incident_attachments`
--

CREATE TABLE `security_incident_attachments` (
  `id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_incident_updates`
--

CREATE TABLE `security_incident_updates` (
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

-- --------------------------------------------------------

--
-- Table structure for table `service_component_map`
--

CREATE TABLE `service_component_map` (
  `service_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Indexes for table `components`
--
ALTER TABLE `components`
  ADD PRIMARY KEY (`component_id`),
  ADD UNIQUE KEY `unique_name` (`name`);

--
-- Indexes for table `downtime_incidents`
--
ALTER TABLE `downtime_incidents`
  ADD PRIMARY KEY (`downtime_id`),
  ADD KEY `incident_id` (`incident_id`),
  ADD KEY `idx_start_end` (`actual_start_time`,`actual_end_time`);

--
-- Indexes for table `fraud_incidents`
--
ALTER TABLE `fraud_incidents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_fi_ref` (`incident_ref`),
  ADD KEY `fk_fi_reporter` (`reported_by`),
  ADD KEY `fk_fi_service` (`service_id`),
  ADD KEY `fk_fi_resolver` (`resolved_by`);

--
-- Indexes for table `fraud_incident_attachments`
--
ALTER TABLE `fraud_incident_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_fia_incident` (`incident_id`);

--
-- Indexes for table `fraud_incident_updates`
--
ALTER TABLE `fraud_incident_updates`
  ADD PRIMARY KEY (`update_id`),
  ADD KEY `fk_fiu_incident` (`incident_id`),
  ADD KEY `fk_fiu_user` (`user_id`);

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
  ADD KEY `idx_incident_source` (`incident_source`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_resolved_at` (`resolved_at`);

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
-- Indexes for table `incident_components`
--
ALTER TABLE `incident_components`
  ADD PRIMARY KEY (`incident_id`,`component_id`),
  ADD KEY `fk_ic_component` (`component_id`);

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
  ADD KEY `idx_usage` (`usage_count`),
  ADD KEY `incident_type_id` (`incident_type_id`);

--
-- Indexes for table `incident_types`
--
ALTER TABLE `incident_types`
  ADD PRIMARY KEY (`type_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `incident_type_service_map`
--
ALTER TABLE `incident_type_service_map`
  ADD PRIMARY KEY (`service_id`,`type_id`),
  ADD KEY `fk_itsm_type` (`type_id`);

--
-- Indexes for table `incident_updates`
--
ALTER TABLE `incident_updates`
  ADD PRIMARY KEY (`update_id`),
  ADD KEY `incident_id` (`incident_id`);

--
-- Indexes for table `security_incidents`
--
ALTER TABLE `security_incidents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_si_ref` (`incident_ref`),
  ADD KEY `fk_si_reporter` (`reported_by`),
  ADD KEY `fk_si_resolver` (`resolved_by`);

--
-- Indexes for table `security_incident_attachments`
--
ALTER TABLE `security_incident_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sia_incident` (`incident_id`);

--
-- Indexes for table `security_incident_updates`
--
ALTER TABLE `security_incident_updates`
  ADD PRIMARY KEY (`update_id`),
  ADD KEY `fk_siu_incident` (`incident_id`),
  ADD KEY `fk_siu_user` (`user_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `service_component_map`
--
ALTER TABLE `service_component_map`
  ADD PRIMARY KEY (`service_id`,`component_id`),
  ADD KEY `fk_scm_component` (`component_id`);

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
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `components`
--
ALTER TABLE `components`
  MODIFY `component_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `downtime_incidents`
--
ALTER TABLE `downtime_incidents`
  MODIFY `downtime_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fraud_incidents`
--
ALTER TABLE `fraud_incidents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fraud_incident_attachments`
--
ALTER TABLE `fraud_incident_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fraud_incident_updates`
--
ALTER TABLE `fraud_incident_updates`
  MODIFY `update_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `incident_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incident_updates`
--
ALTER TABLE `incident_updates`
  MODIFY `update_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_incidents`
--
ALTER TABLE `security_incidents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_incident_attachments`
--
ALTER TABLE `security_incident_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_incident_updates`
--
ALTER TABLE `security_incident_updates`
  MODIFY `update_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sla_targets`
--
ALTER TABLE `sla_targets`
  MODIFY `target_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `fraud_incidents`
--
ALTER TABLE `fraud_incidents`
  ADD CONSTRAINT `fk_fi_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_fi_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_fi_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE SET NULL;

--
-- Constraints for table `fraud_incident_attachments`
--
ALTER TABLE `fraud_incident_attachments`
  ADD CONSTRAINT `fk_fia_incident` FOREIGN KEY (`incident_id`) REFERENCES `fraud_incidents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fraud_incident_updates`
--
ALTER TABLE `fraud_incident_updates`
  ADD CONSTRAINT `fk_fiu_incident` FOREIGN KEY (`incident_id`) REFERENCES `fraud_incidents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fiu_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `incidents`
--
ALTER TABLE `incidents`
  ADD CONSTRAINT `fk_incident_component2` FOREIGN KEY (`component_id`) REFERENCES `components` (`component_id`) ON DELETE SET NULL,
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
-- Constraints for table `incident_components`
--
ALTER TABLE `incident_components`
  ADD CONSTRAINT `fk_ic_component` FOREIGN KEY (`component_id`) REFERENCES `components` (`component_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ic_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_templates`
--
ALTER TABLE `incident_templates`
  ADD CONSTRAINT `fk_template_component` FOREIGN KEY (`component_id`) REFERENCES `components` (`component_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `incident_templates_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `incident_templates_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `incident_templates_ibfk_4` FOREIGN KEY (`incident_type_id`) REFERENCES `incident_types` (`type_id`) ON DELETE SET NULL;

--
-- Constraints for table `incident_types`
--
ALTER TABLE `incident_types`
  ADD CONSTRAINT `fk_type_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_type_service_map`
--
ALTER TABLE `incident_type_service_map`
  ADD CONSTRAINT `fk_itsm_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_itsm_type` FOREIGN KEY (`type_id`) REFERENCES `incident_types` (`type_id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_updates`
--
ALTER TABLE `incident_updates`
  ADD CONSTRAINT `fk_updates_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE;

--
-- Constraints for table `security_incidents`
--
ALTER TABLE `security_incidents`
  ADD CONSTRAINT `fk_si_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_si_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `security_incident_attachments`
--
ALTER TABLE `security_incident_attachments`
  ADD CONSTRAINT `fk_sia_incident` FOREIGN KEY (`incident_id`) REFERENCES `security_incidents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `security_incident_updates`
--
ALTER TABLE `security_incident_updates`
  ADD CONSTRAINT `fk_siu_incident` FOREIGN KEY (`incident_id`) REFERENCES `security_incidents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_siu_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `service_component_map`
--
ALTER TABLE `service_component_map`
  ADD CONSTRAINT `fk_scm_component` FOREIGN KEY (`component_id`) REFERENCES `components` (`component_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_scm_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;

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
