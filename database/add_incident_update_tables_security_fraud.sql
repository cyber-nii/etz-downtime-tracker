-- Migration: Add update timeline tables for security and fraud incidents
-- Run once against downtimedb

CREATE TABLE IF NOT EXISTS `security_incident_updates` (
  `update_id`   int NOT NULL AUTO_INCREMENT,
  `incident_id` int NOT NULL,
  `user_id`     int DEFAULT NULL,
  `user_name`   varchar(255) NOT NULL,
  `update_text` text NOT NULL,
  `created_at`  timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`update_id`),
  KEY `fk_siu_incident` (`incident_id`),
  KEY `fk_siu_user` (`user_id`),
  CONSTRAINT `fk_siu_incident` FOREIGN KEY (`incident_id`) REFERENCES `security_incidents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_siu_user`     FOREIGN KEY (`user_id`)     REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `fraud_incident_updates` (
  `update_id`   int NOT NULL AUTO_INCREMENT,
  `incident_id` int NOT NULL,
  `user_id`     int DEFAULT NULL,
  `user_name`   varchar(255) NOT NULL,
  `update_text` text NOT NULL,
  `created_at`  timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`update_id`),
  KEY `fk_fiu_incident` (`incident_id`),
  KEY `fk_fiu_user` (`user_id`),
  CONSTRAINT `fk_fiu_incident` FOREIGN KEY (`incident_id`) REFERENCES `fraud_incidents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fiu_user`     FOREIGN KEY (`user_id`)     REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
