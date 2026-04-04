-- Migration: Add incident_components junction table for multi-component support
-- Run this once against downtimedb

CREATE TABLE IF NOT EXISTS `incident_components` (
  `incident_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  PRIMARY KEY (`incident_id`, `component_id`),
  KEY `fk_ic_component` (`component_id`),
  CONSTRAINT `fk_ic_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ic_component` FOREIGN KEY (`component_id`) REFERENCES `components` (`component_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Migrate existing single-component assignments into the junction table
INSERT IGNORE INTO `incident_components` (`incident_id`, `component_id`)
SELECT `incident_id`, `component_id`
FROM `incidents`
WHERE `component_id` IS NOT NULL;
