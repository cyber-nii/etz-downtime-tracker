-- =============================================
-- FIX SERVICE_ID MAPPING
-- Based on Systems_Affected from Excel data
-- Cross-referenced with downtimedb.csv patterns
-- =============================================

-- Vasgate-only incidents
UPDATE incidents SET service_id = 142 WHERE incident_ref = 'INC_26_020148';
UPDATE incidents SET service_id = 142 WHERE incident_ref = 'INC_26_020135';

-- Mobile Money / Vasgate (both affected -> mapped to VASGATE per reference data pattern)
UPDATE incidents SET service_id = 142 WHERE incident_ref = 'INC_26_030156';
UPDATE incidents SET service_id = 142 WHERE incident_ref = 'INC_26_030155';
UPDATE incidents SET service_id = 142 WHERE incident_ref = 'INC_26_030152';
UPDATE incidents SET service_id = 142 WHERE incident_ref = 'INC_26_030150';
UPDATE incidents SET service_id = 142 WHERE incident_ref = 'INC_26_020145';
UPDATE incidents SET service_id = 142 WHERE incident_ref = 'INC_26_020137';
UPDATE incidents SET service_id = 142 WHERE incident_ref = 'INC_26_020131';
UPDATE incidents SET service_id = 142 WHERE incident_ref = 'INC_26_020129';
UPDATE incidents SET service_id = 142 WHERE incident_ref = 'INC_26_020127';
UPDATE incidents SET service_id = 142 WHERE incident_ref = 'INC_26_020126';
UPDATE incidents SET service_id = 142 WHERE incident_ref = 'INC_26_020125';
UPDATE incidents SET service_id = 142 WHERE incident_ref = 'INC_26_020121';
UPDATE incidents SET service_id = 142 WHERE incident_ref = 'INC_26_010111';
UPDATE incidents SET service_id = 142 WHERE incident_ref = 'INC_26_010102';

-- Fundgate-related (All clients and Fundgate clients)
UPDATE incidents SET service_id = 143 WHERE incident_ref = 'INC_26_010105';

-- =============================================
-- Total: 17 updates
-- 16 → VASGATE (142)
-- 1  → FUNDGATE (143)
-- =============================================
