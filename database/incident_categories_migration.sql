-- Migration: Add incident categories (system_downtime, information_security, fraud)
-- Run once against downtimedb

-- 1. Add category column to incidents
ALTER TABLE `incidents`
  ADD COLUMN `category` ENUM('system_downtime','information_security','fraud')
  NOT NULL DEFAULT 'system_downtime'
  AFTER `incident_ref`;

-- 2. Information Security details table
CREATE TABLE IF NOT EXISTS `incident_security_details` (
  `incident_id` int(11) NOT NULL,
  `threat_type` ENUM('phishing','unauthorized_access','data_breach','malware','social_engineering','other') NOT NULL DEFAULT 'other',
  `systems_affected` text DEFAULT NULL,
  `containment_status` ENUM('contained','ongoing','under_investigation') NOT NULL DEFAULT 'under_investigation',
  `escalated_to` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`incident_id`),
  CONSTRAINT `fk_isd_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Fraud details table
CREATE TABLE IF NOT EXISTS `incident_fraud_details` (
  `incident_id` int(11) NOT NULL,
  `fraud_type` ENUM('card_fraud','account_takeover','transaction_fraud','internal_fraud','other') NOT NULL DEFAULT 'other',
  `financial_impact` decimal(15,2) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `regulatory_reported` tinyint(1) NOT NULL DEFAULT 0,
  `regulatory_details` text DEFAULT NULL,
  PRIMARY KEY (`incident_id`),
  KEY `fk_ifd_service` (`service_id`),
  CONSTRAINT `fk_ifd_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ifd_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
