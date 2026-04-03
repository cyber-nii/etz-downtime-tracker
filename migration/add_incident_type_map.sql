-- ============================================================
-- Migration: Global incident_types pool + junction table
-- Run in phpMyAdmin on the downtime tracker database
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Step 1: Create the junction table
CREATE TABLE IF NOT EXISTS incident_type_service_map (
    service_id INT NOT NULL,
    type_id    INT NOT NULL,
    PRIMARY KEY (service_id, type_id),
    CONSTRAINT fk_itsm_service FOREIGN KEY (service_id) REFERENCES services(service_id)      ON DELETE CASCADE,
    CONSTRAINT fk_itsm_type    FOREIGN KEY (type_id)    REFERENCES incident_types(type_id)   ON DELETE CASCADE
);

-- Step 2: Find the canonical (lowest) type_id for each unique name
CREATE TEMPORARY TABLE canonical_type_ids AS
    SELECT MIN(type_id) AS canonical_id, name
    FROM incident_types
    GROUP BY name;

-- Step 3: Populate junction table — map each canonical type_id to every service
--         that previously had a row with that name
INSERT IGNORE INTO incident_type_service_map (type_id, service_id)
    SELECT DISTINCT ct.canonical_id, it.service_id
    FROM incident_types it
    JOIN canonical_type_ids ct ON ct.name = it.name
    WHERE it.service_id IS NOT NULL;

-- Step 4: Re-point incidents to the canonical type_id
UPDATE incidents i
    JOIN incident_types it ON i.incident_type_id = it.type_id
    JOIN canonical_type_ids ct ON ct.name = it.name
    SET i.incident_type_id = ct.canonical_id
    WHERE i.incident_type_id IS NOT NULL;

-- Step 5: Re-point templates to the canonical type_id
UPDATE incident_templates tmpl
    JOIN incident_types it ON tmpl.incident_type_id = it.type_id
    JOIN canonical_type_ids ct ON ct.name = it.name
    SET tmpl.incident_type_id = ct.canonical_id
    WHERE tmpl.incident_type_id IS NOT NULL;

-- Step 6: Delete duplicate (non-canonical) rows from incident_types
DELETE it
    FROM incident_types it
    LEFT JOIN canonical_type_ids ct ON ct.canonical_id = it.type_id
    WHERE ct.canonical_id IS NULL;

DROP TEMPORARY TABLE canonical_type_ids;

-- Step 7: Make service_id nullable (now managed by the map table)
ALTER TABLE incident_types
    MODIFY COLUMN service_id INT NULL;

-- Step 8: Clear the legacy service_id — it is no longer used
UPDATE incident_types SET service_id = NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- Verify: should show each unique type name exactly once
SELECT it.type_id, it.name,
       GROUP_CONCAT(s.service_name ORDER BY s.service_name SEPARATOR ', ') AS assigned_services
FROM incident_types it
LEFT JOIN incident_type_service_map itsm ON itsm.type_id = it.type_id
LEFT JOIN services s ON s.service_id = itsm.service_id
GROUP BY it.type_id
ORDER BY it.name;
