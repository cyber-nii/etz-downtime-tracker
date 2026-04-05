-- Migration: Separate security and fraud incidents into their own tables
-- Run once against downtimedb

-- 1. Remove the shared-table approach (safe if already run)
ALTER TABLE `incidents` DROP COLUMN IF EXISTS `category`;
DROP TABLE IF EXISTS `incident_security_details`;
DROP TABLE IF EXISTS `incident_fraud_details`;

-- 2. Security incidents (standalone table)
CREATE TABLE IF NOT EXISTS `security_incidents` (
  `id`                 int(11)      NOT NULL AUTO_INCREMENT,
  `incident_ref`       varchar(50)  NOT NULL,
  `threat_type`        ENUM('phishing','unauthorized_access','data_breach','malware','social_engineering','other') NOT NULL DEFAULT 'other',
  `systems_affected`   text         DEFAULT NULL,
  `description`        text         DEFAULT NULL,
  `impact_level`       ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Low',
  `priority`           ENUM('Low','Medium','High','Urgent')   NOT NULL DEFAULT 'Medium',
  `containment_status` ENUM('contained','ongoing','under_investigation') NOT NULL DEFAULT 'under_investigation',
  `escalated_to`       varchar(500) DEFAULT NULL,
  `root_cause`         text         DEFAULT NULL,
  `attachment_path`    varchar(500) DEFAULT NULL,
  `actual_start_time`  datetime     NOT NULL,
  `status`             ENUM('pending','resolved') NOT NULL DEFAULT 'pending',
  `reported_by`        int(11)      NOT NULL,
  `resolved_by`        int(11)      DEFAULT NULL,
  `resolved_at`        datetime     DEFAULT NULL,
  `created_at`         timestamp    NOT NULL DEFAULT current_timestamp(),
  `updated_at`         timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_si_ref` (`incident_ref`),
  KEY `fk_si_reporter` (`reported_by`),
  KEY `fk_si_resolver` (`resolved_by`),
  CONSTRAINT `fk_si_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_si_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Security incident attachments
CREATE TABLE IF NOT EXISTS `security_incident_attachments` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `incident_id` int(11)      NOT NULL,
  `file_path`   varchar(500) NOT NULL,
  `file_name`   varchar(255) NOT NULL,
  `file_type`   varchar(100) DEFAULT NULL,
  `file_size`   int(11)      DEFAULT NULL,
  `uploaded_at` timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_sia_incident` (`incident_id`),
  CONSTRAINT `fk_sia_incident` FOREIGN KEY (`incident_id`) REFERENCES `security_incidents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Fraud incidents (standalone table)
CREATE TABLE IF NOT EXISTS `fraud_incidents` (
  `id`                  int(11)         NOT NULL AUTO_INCREMENT,
  `incident_ref`        varchar(50)     NOT NULL,
  `fraud_type`          ENUM('card_fraud','account_takeover','transaction_fraud','internal_fraud','other') NOT NULL DEFAULT 'other',
  `service_id`          int(11)         DEFAULT NULL,
  `description`         text            DEFAULT NULL,
  `financial_impact`    decimal(15,2)   DEFAULT NULL,
  `impact_level`        ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Low',
  `priority`            ENUM('Low','Medium','High','Urgent')   NOT NULL DEFAULT 'Medium',
  `regulatory_reported` tinyint(1)      NOT NULL DEFAULT 0,
  `regulatory_details`  text            DEFAULT NULL,
  `root_cause`          text            DEFAULT NULL,
  `attachment_path`     varchar(500)    DEFAULT NULL,
  `actual_start_time`   datetime        NOT NULL,
  `status`              ENUM('pending','resolved') NOT NULL DEFAULT 'pending',
  `reported_by`         int(11)         NOT NULL,
  `resolved_by`         int(11)         DEFAULT NULL,
  `resolved_at`         datetime        DEFAULT NULL,
  `created_at`          timestamp       NOT NULL DEFAULT current_timestamp(),
  `updated_at`          timestamp       NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fi_ref` (`incident_ref`),
  KEY `fk_fi_reporter` (`reported_by`),
  KEY `fk_fi_service`  (`service_id`),
  KEY `fk_fi_resolver` (`resolved_by`),
  CONSTRAINT `fk_fi_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_fi_service`  FOREIGN KEY (`service_id`)  REFERENCES `services` (`service_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_fi_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Fraud incident attachments
CREATE TABLE IF NOT EXISTS `fraud_incident_attachments` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `incident_id` int(11)      NOT NULL,
  `file_path`   varchar(500) NOT NULL,
  `file_name`   varchar(255) NOT NULL,
  `file_type`   varchar(100) DEFAULT NULL,
  `file_size`   int(11)      DEFAULT NULL,
  `uploaded_at` timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_fia_incident` (`incident_id`),
  CONSTRAINT `fk_fia_incident` FOREIGN KEY (`incident_id`) REFERENCES `fraud_incidents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
