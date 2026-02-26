-- ============================================================
-- ll.sql — Full Setup Script
-- 1. Alter incident_templates to add incident_type_id
-- 2. Seed incident_types and service_components for 6 services
-- 3. Seed 19 pre-built templates with types linked by name
--
-- Live Services: 141=Mobile Money, 142=VASGATE, 143=FUNDGATE,
--                144=MPAY, 145=JUSTPAY, 146=OVA
-- Run in phpMyAdmin: downtimedb → SQL → Go
-- ============================================================

SET foreign_key_checks = 0;

-- ============================================================
-- STEP 1: Add incident_type_id column to incident_templates
-- (safe to run even if column exists — use IF NOT EXISTS logic)
-- ============================================================
ALTER TABLE `incident_templates`
    ADD COLUMN IF NOT EXISTS `incident_type_id` INT(11) DEFAULT NULL AFTER `component_id`,
    ADD KEY IF NOT EXISTS `incident_type_id` (`incident_type_id`);

-- Add FK if not already there
-- (comment this out if it causes an error — constraint may already exist)
ALTER TABLE `incident_templates`
    ADD CONSTRAINT `incident_templates_ibfk_4`
    FOREIGN KEY IF NOT EXISTS (`incident_type_id`)
    REFERENCES `incident_types` (`type_id`) ON DELETE SET NULL;

-- ============================================================
-- STEP 2: Seed incident_types (6 real types per service)
-- ============================================================
DELETE FROM `incident_types` WHERE `service_id` IN (141,142,143,144,145,146);

INSERT INTO `incident_types` (`service_id`, `name`, `is_active`) VALUES
(141,'Connectivity Issue',1),(141,'Server Failure / Shut Down',1),
(141,'Service / Application not responding',1),(141,'Insufficient Account Balance',1),
(141,'System Maintenance',1),(141,'Others',1),
(142,'Connectivity Issue',1),(142,'Server Failure / Shut Down',1),
(142,'Service / Application not responding',1),(142,'Insufficient Account Balance',1),
(142,'System Maintenance',1),(142,'Others',1),
(143,'Connectivity Issue',1),(143,'Server Failure / Shut Down',1),
(143,'Service / Application not responding',1),(143,'Insufficient Account Balance',1),
(143,'System Maintenance',1),(143,'Others',1),
(144,'Connectivity Issue',1),(144,'Server Failure / Shut Down',1),
(144,'Service / Application not responding',1),(144,'Insufficient Account Balance',1),
(144,'System Maintenance',1),(144,'Others',1),
(145,'Connectivity Issue',1),(145,'Server Failure / Shut Down',1),
(145,'Service / Application not responding',1),(145,'Insufficient Account Balance',1),
(145,'System Maintenance',1),(145,'Others',1),
(146,'Connectivity Issue',1),(146,'Server Failure / Shut Down',1),
(146,'Service / Application not responding',1),(146,'Insufficient Account Balance',1),
(146,'System Maintenance',1),(146,'Others',1);

-- ============================================================
-- STEP 3: Seed service_components for all 6 services
-- ============================================================
DELETE FROM `service_components` WHERE `service_id` IN (141,142,143,144,145,146);

INSERT INTO `service_components` (`service_id`, `name`, `is_active`) VALUES
(141,'Credit',1),(141,'Debit',1),(141,'Reversal',1),(141,'B2W',1),(141,'W2B',1),(141,'Topup',1),
(142,'Bills',1),(142,'Topup',1),(142,'Airtime',1),(142,'Data Bundle',1),(142,'B2W',1),(142,'W2B',1),
(143,'Credit',1),(143,'Debit',1),(143,'B2W',1),(143,'W2B',1),(143,'Topup',1),(143,'Reversal',1),
(144,'Credit',1),(144,'Debit',1),(144,'Topup',1),(144,'B2W',1),(144,'W2B',1),(144,'Reversal',1),
(145,'Credit',1),(145,'Debit',1),(145,'Bills',1),(145,'Topup',1),(145,'Airtime',1),(145,'Reversal',1),
(146,'Credit',1),(146,'Debit',1),(146,'B2W',1),(146,'W2B',1),(146,'Topup',1),(146,'Reversal',1);

-- ============================================================
-- STEP 4: Seed pre-built incident templates
-- incident_type_id is looked up by name+service from the types
-- just inserted above so it always gets the right ID
-- ============================================================
DELETE FROM `incident_templates`;

INSERT INTO `incident_templates`
    (`template_name`, `service_id`, `component_id`, `incident_type_id`, `impact_level`, `description`, `root_cause`, `is_active`, `created_by`)
VALUES

-- ===== GENERIC (all services) =====
(
    'Host Connectivity Issue', NULL, NULL,
    NULL,
    'high',
    'Service is unreachable due to a connectivity failure with the host provider. Transactions are failing and customers are unable to complete operations.',
    'Connectivity issue with the third-party host provider. Loss of network connectivity between our gateway and the external host.',
    1, 1
),
(
    'Server Failure / System Down', NULL, NULL,
    NULL,
    'critical',
    'The service server has gone down unexpectedly, causing a complete service outage. All transactions are affected.',
    'Server failure due to hardware fault or OS-level crash. Services are completely unavailable.',
    1, 1
),
(
    'Application Not Responding', NULL, NULL,
    NULL,
    'high',
    'The application or service API is not responding to requests. Customers are experiencing timeouts and failed transactions.',
    'Application process has hung or crashed. The service endpoint is unresponsive and requires a restart.',
    1, 1
),
(
    'Insufficient Account Balance', NULL, NULL,
    NULL,
    'critical',
    'Service has been suspended due to insufficient float/account balance. Transaction processing has halted.',
    'The float/settlement account balance fell below the required threshold. Transactions cannot be processed until the account is topped up.',
    1, 1
),
(
    'Scheduled System Maintenance', NULL, NULL,
    NULL,
    'medium',
    'Planned system maintenance is currently underway. Service will be temporarily unavailable during this window.',
    'Scheduled maintenance window for system upgrades, patches, or infrastructure improvements.',
    1, 1
),

-- ===== MOBILE MONEY (141) =====
(
    'MoMo Credit Transaction Failure', 141, NULL,
    (SELECT type_id FROM incident_types WHERE service_id=141 AND name='Connectivity Issue' LIMIT 1),
    'high',
    'Mobile Money credit transactions are failing. Customers are unable to receive funds into their Mobile Money wallets.',
    'Connectivity issue or application failure on the Mobile Money credit processing pipeline. Host response timeout on credit requests.',
    1, 1
),
(
    'MoMo Debit Transaction Failure', 141, NULL,
    (SELECT type_id FROM incident_types WHERE service_id=141 AND name='Connectivity Issue' LIMIT 1),
    'high',
    'Mobile Money debit transactions are failing. Customers are unable to send or withdraw funds from their wallets.',
    'Debit processing failure due to host connectivity timeout or insufficient settlement balance.',
    1, 1
),
(
    'Bank-to-Wallet (B2W) Failure', 141, NULL,
    (SELECT type_id FROM incident_types WHERE service_id=141 AND name='Service / Application not responding' LIMIT 1),
    'high',
    'Bank-to-Wallet transfers are failing. Customers cannot move funds from their bank accounts to their Mobile Money wallets.',
    'Third-party bank host is unreachable or the B2W processing service has gone down.',
    1, 1
),
(
    'Wallet-to-Bank (W2B) Failure', 141, NULL,
    (SELECT type_id FROM incident_types WHERE service_id=141 AND name='Service / Application not responding' LIMIT 1),
    'high',
    'Wallet-to-Bank transfers are failing. Customers cannot transfer funds from their Mobile Money wallet to their linked bank accounts.',
    'W2B processing service connectivity failure or bank host downtime.',
    1, 1
),

-- ===== VASGATE (142) =====
(
    'Bill Payment Processing Failure', 142, NULL,
    (SELECT type_id FROM incident_types WHERE service_id=142 AND name='Connectivity Issue' LIMIT 1),
    'high',
    'Bill payment transactions on VASGATE are failing. Customers are unable to pay utility bills, subscriptions, or service charges.',
    'VASGATE bill payment gateway is experiencing connectivity issues with the bill aggregator or biller APIs.',
    1, 1
),
(
    'Airtime Top-Up Failure', 142, NULL,
    (SELECT type_id FROM incident_types WHERE service_id=142 AND name='Service / Application not responding' LIMIT 1),
    'medium',
    'Airtime top-up purchases are failing on VASGATE. Customers are unable to purchase airtime for their mobile networks.',
    'Airtime vendor API is unresponsive or the VASGATE airtime processing service is down.',
    1, 1
),
(
    'Data Bundle Purchase Failure', 142, NULL,
    (SELECT type_id FROM incident_types WHERE service_id=142 AND name='Service / Application not responding' LIMIT 1),
    'medium',
    'Data bundle purchases are failing on VASGATE. Customers cannot purchase mobile data bundles.',
    'Data bundle vendor API connectivity issue or inventory stock depletion on the aggregator side.',
    1, 1
),

-- ===== FUNDGATE (143) =====
(
    'FUNDGATE Credit Processing Failure', 143, NULL,
    (SELECT type_id FROM incident_types WHERE service_id=143 AND name='Connectivity Issue' LIMIT 1),
    'high',
    'FUNDGATE credit transactions are failing. Fund credits into accounts are not being processed.',
    'FUNDGATE credit processing engine connectivity failure or timeout with the receiving bank host.',
    1, 1
),
(
    'FUNDGATE B2W Transfer Failure', 143, NULL,
    (SELECT type_id FROM incident_types WHERE service_id=143 AND name='Service / Application not responding' LIMIT 1),
    'high',
    'Bank-to-Wallet fund transfers via FUNDGATE are failing. Customers cannot move bank funds to wallets.',
    'FUNDGATE B2W pipeline is experiencing host connectivity issues or the processing service is down.',
    1, 1
),

-- ===== MPAY (144) =====
(
    'MPAY Transaction Processing Failure', 144, NULL,
    (SELECT type_id FROM incident_types WHERE service_id=144 AND name='Connectivity Issue' LIMIT 1),
    'high',
    'MPAY payment transactions are failing. Customers are unable to complete mobile payments through the MPAY platform.',
    'MPAY transaction processing service is down or the payment gateway is experiencing connectivity issues.',
    1, 1
),
(
    'MPAY Server Downtime', 144, NULL,
    (SELECT type_id FROM incident_types WHERE service_id=144 AND name='Server Failure / Shut Down' LIMIT 1),
    'critical',
    'The MPAY server has gone down completely. All MPAY payment processing is unavailable.',
    'MPAY server failure due to infrastructure issue or application crash. Full service restoration required.',
    1, 1
),

-- ===== JUSTPAY (145) =====
(
    'JUSTPAY Payment Gateway Failure', 145, NULL,
    (SELECT type_id FROM incident_types WHERE service_id=145 AND name='Connectivity Issue' LIMIT 1),
    'high',
    'JUSTPAY payment processing is unavailable. Customers cannot complete payments through the JUSTPAY gateway.',
    'JUSTPAY gateway connectivity failure with the acquiring bank or payment processor.',
    1, 1
),
(
    'JUSTPAY Bill Payment Failure', 145, NULL,
    (SELECT type_id FROM incident_types WHERE service_id=145 AND name='Service / Application not responding' LIMIT 1),
    'high',
    'Bill payments via JUSTPAY are failing. Customers cannot pay bills using the JUSTPAY platform.',
    'Biller API connectivity issue on the JUSTPAY bill payment processing pipeline.',
    1, 1
),

-- ===== OVA (146) =====
(
    'OVA Wallet Credit Failure', 146, NULL,
    (SELECT type_id FROM incident_types WHERE service_id=146 AND name='Connectivity Issue' LIMIT 1),
    'high',
    'OVA wallet credit transactions are failing. Customers cannot receive funds into their OVA wallets.',
    'OVA wallet credit processing failure due to host connectivity issue or service downtime.',
    1, 1
),
(
    'OVA Low Float / Insufficient Balance', 146, NULL,
    (SELECT type_id FROM incident_types WHERE service_id=146 AND name='Insufficient Account Balance' LIMIT 1),
    'critical',
    'OVA service has been halted due to insufficient float balance. Wallet transactions cannot be processed.',
    'The OVA settlement account/float balance is insufficient to process transactions. Account top-up required immediately.',
    1, 1
);

SET foreign_key_checks = 1;

-- ============================================================
-- VERIFY — run after import
-- ============================================================
SELECT
    t.template_id,
    t.template_name,
    IFNULL(s.service_name, '(All Services)') AS service,
    it.name AS incident_type,
    t.impact_level,
    t.is_active
FROM incident_templates t
LEFT JOIN services s ON t.service_id = s.service_id
LEFT JOIN incident_types it ON t.incident_type_id = it.type_id
ORDER BY t.service_id IS NOT NULL, t.service_id, t.template_name;