-- =============================================
-- MIGRATION: Excel Data -> downtimedb
-- Generated from 2026-03-26T14_08_18.3898181Z.xlsx
-- =============================================

-- =============================================
-- STEP 0: Add missing users (if not already present)
-- =============================================
INSERT IGNORE INTO users (user_id, username, email, password_hash, full_name, role, is_active, changed_password)
VALUES (25, 'anku_bright', 'anku.bright@etz.com', '$2b$10$placeholder', 'Anku Bright', 'user', 1, 0);

INSERT IGNORE INTO users (user_id, username, email, password_hash, full_name, role, is_active, changed_password)
VALUES (26, 'israel_opata', 'israel.opata@etz.com', '$2b$10$placeholder', 'Israel Opata', 'user', 1, 0);


-- =============================================
-- STEP 1: INSERT INCIDENTS
-- =============================================
SET FOREIGN_KEY_CHECKS = 0;

-- Row 1: INC_26_030166
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_030166', 141, NULL, 1311, 'Mainone Host downtime | Systems Affected: None as traffic was channelled through other ISP.', 'High', 'High', 'The link was impacted by a suspected fiber cut on our support partner network.', 'Proactive monitoring of internet traffics.\nHaving a back up ISP', '2026-03-20 19:05:00', 'resolved', 7, 7, '2026-03-21 02:05:00', '2026-03-20 19:13:00', '2026-03-21 02:05:00');

-- Row 2: INC_26_030165
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_030165', 141, NULL, 1311, 'Mainone host downtime | Systems Affected: None as traffic was channelled through another ISP.', 'High', 'High', 'Service outage was due a configuration error on the switch.', 'Proactive monitoring of Internet speed and traffic.', '2026-03-19 16:15:00', 'resolved', 7, 7, '2026-03-19 20:15:00', '2026-03-19 16:05:00', '2026-03-19 20:15:00');

-- Row 3: INC_26_030164
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_030164', 141, NULL, 1311, 'Mainone host was not reachable. | Systems Affected: None as traffic was channelled through another ISP.', 'High', 'High', 'This was due to a to power failure at mainone last mile segment.', 'Always have a back up ISP.\nProactive monitoring of internet speed and traffic.', '2026-03-19 09:20:00', 'resolved', 7, 7, '2026-03-19 14:50:00', '2026-03-19 09:15:00', '2026-03-19 14:50:00');

-- Row 4: INC_26_030167
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_030167', 142, 139, 1314, 'Adsl transaction were failing because we were getting bad request response from them | Systems Affected: All transactions', 'Critical', 'Urgent', 'we were getting bad request response from them', 'There must be a continuous monitoring and asking of update from their team on the issue', '2026-03-17 06:55:00', 'resolved', 2, 2, '2026-03-22 17:01:00', '2026-03-17 06:55:00', '2026-03-22 17:01:00');

-- Row 5: INC_26_030163
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_030163', 141, NULL, 1310, 'Transactions were failing intermittently | Systems Affected: All Transactions', 'High', 'High', 'Transactions were failing intermittently because of a planned maintenance', 'There must be a continuous monitoring and asking of update from their team on the issue', '2026-03-15 00:00:00', 'resolved', 2, 2, '2026-03-15 08:00:00', '2026-03-15 00:00:00', '2026-03-15 08:00:00');

-- Row 6: INC_26_030162
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_030162', 141, NULL, 1310, 'MTN Transactions were failing intermittently | Systems Affected: All transactions', 'Critical', 'Urgent', 'Transactions were failing because of a planned maintenance', 'There must be a continuous monitoring and asking of update from their team on the issue', '2026-03-14 22:00:00', 'resolved', 2, 2, '2026-03-15 10:00:00', '2026-03-14 22:00:00', '2026-03-15 10:00:00');

-- Row 7: INC_26_030161
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_030161', 141, 135, 1308, 'Transactions were failing intermittenly | Systems Affected: Debit and Top up transactions', 'Medium', 'Medium', 'Transactions experienced intermittent failures due to unplanned maintenance at that time.', 'There must be a continuous monitoring and asking of update from their team on the issue', '2026-03-14 20:00:00', 'resolved', 2, 2, '2026-03-14 21:00:00', '2026-03-14 20:00:00', '2026-03-14 21:00:00');

-- Row 8: INC_26_030159
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_030159', 141, NULL, 1306, 'We were not able to connect to their host | Systems Affected: All Abii National transactions', 'Medium', 'Medium', 'Connectivity issue', 'We shoul monitor closely to detect such issues on time', '2026-03-14 09:36:00', 'resolved', 6, 6, '2026-03-14 13:11:00', '2026-03-14 09:37:00', '2026-03-14 13:11:00');

-- Row 9: INC_26_030160
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_030160', 141, NULL, 1306, 'We couldn\'t connect to their host | Systems Affected: All Vision fund transactions', 'Critical', 'Urgent', 'Connectivity issue', 'We should monitor closely to detect such issues on time', '2026-03-14 00:00:00', 'resolved', 6, 6, '2026-03-14 14:09:00', '2026-03-14 00:00:00', '2026-03-14 14:09:00');

-- Row 10: INC_26_030158
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_030158', 141, NULL, 1307, 'USSD Downtime | Systems Affected: USSD Applications', 'Medium', 'Medium', 'we experienced a technical issue affecting our 30.26 network segment, which impacted our USSD infrastructure and resulted in a temporary shutdown of all associated shortcodes. During this period, users may have experienced failed sessions or an inability to access services via USSD.', 'Continus monitoring', '2026-03-12 17:19:00', 'resolved', 25, 25, '2026-03-12 18:38:00', '2026-03-13 17:15:00', '2026-03-12 18:38:00');

-- Row 11: INC_26_030157
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_030157', 141, 135, 1306, 'All Debit and Top up transactions were failing | Systems Affected: All Debit and Top up transactions', 'Medium', 'Medium', 'connectivity issue', 'There must be a continuous monitoring and asking of update from their team on the issue', '2026-03-12 00:03:00', 'resolved', 2, 2, '2026-03-12 01:38:00', '2026-03-12 00:03:00', '2026-03-12 01:38:00');

-- Row 12: INC_26_030156
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_030156', 142, NULL, 1312, 'System Maintenance | Systems Affected: Mobile Money / Vasgate', 'High', 'High', 'MTN Maintenance', 'Continuous monitoring and reporting', '2026-03-10 01:00:00', 'resolved', 4, 4, '2026-03-10 05:00:00', '2026-03-10 01:00:00', '2026-03-10 05:00:00');

-- Row 13: INC_26_030155
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_030155', 142, NULL, 1312, 'Server inaccessible | Systems Affected: Mobile Money / Vasgate', 'Medium', 'Medium', 'The team could not access BESTPOINT server', 'Continuous monitoring and reporting', '2026-03-09 19:52:00', 'resolved', 4, 4, '2026-03-09 22:00:00', '2026-03-09 19:52:00', '2026-03-09 22:00:00');

-- Row 14: INC_26_030153
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_030153', 141, 131, 1308, 'We observed that the majority of debit transactions were in a pending state, and name verification could not be performed on Xcel. Upon investigation, we attempted to access server 30.17, but it was found to be unresponsive. With the assistance of Ezra Ohene and Takyi Owusu Mensah, we restarted the server, after which normal operations were restored and the issue was resolved | Systems Affected: Most debit transactions and Xcel name verification', 'Medium', 'Medium', '30.17 was not accessible', 'Continuous monitoring', '2026-03-08 06:40:00', 'resolved', 25, 25, '2026-03-08 08:20:00', '2026-03-08 06:43:00', '2026-03-08 08:20:00');

-- Row 15: INC_26_030152
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_030152', 142, NULL, 1312, 'Thier transactions were failing because we could not connect to their host | Systems Affected: Mobile Money and Vasgate', 'High', 'High', 'Their CBA went off', 'Continuous monitoring and asking for update till issue is resolved', '2026-03-06 07:20:00', 'resolved', 3, 3, '2026-03-06 17:23:00', '2026-03-06 07:20:00', '2026-03-06 17:23:00');

-- Row 16: INC_26_030154
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_030154', 141, NULL, 1306, 'Transactions were failing we could not connect to their host | Systems Affected: All transactions', 'High', 'High', 'We could not connect to their host.', 'There must be a continuous monitoring and asking of update from their team on the issue', '2026-03-06 05:50:00', 'resolved', 2, 2, '2026-03-06 16:26:00', '2026-03-06 05:50:00', '2026-03-06 16:26:00');

-- Row 17: INC_26_030150
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_030150', 142, NULL, 1312, 'Connectivity Issue | Systems Affected: Mobile Money / Vasgate', 'High', 'High', 'We were not able to access the PBL server', 'Continuous monitoring and reporting', '2026-03-03 19:28:00', 'resolved', 4, 4, '2026-03-03 23:50:00', '2026-03-03 19:28:00', '2026-03-03 23:50:00');

-- Row 18: INC_26_030151
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_030151', 141, NULL, 1311, 'we were not able to reach Vision fund  live endpoint | Systems Affected: momo', 'High', 'High', 'there was a configuration that was done that caused the endpoint to change from demo to live', 'we should monior transactions after a configuration change', '2026-03-03 12:00:00', 'resolved', 8, 8, '2026-03-03 16:16:00', '2026-03-04 12:00:00', '2026-03-03 16:16:00');

-- Row 19: INC_26_020147
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020147', 141, NULL, 1306, 'Internet connectivity | Systems Affected: All systems / applications', 'Medium', 'Medium', 'No internet traffic', 'Continuous monitoring and reporting', '2026-02-28 01:40:00', 'pending', 4, NULL, NULL, '2026-02-28 01:40:00', '2026-02-28 01:40:00');

-- Row 20: INC_26_020148
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020148', 142, 138, 1314, 'Telecel airtime transactions were failing | Systems Affected: Vasgate', 'High', 'High', 'All telecel airtime transactions were failing because of connectivity issue', 'There must be a continuous monitoring and asking of update from their team on the issue.', '2026-02-27 23:00:00', 'pending', 2, NULL, NULL, '2026-02-27 23:00:00', '2026-02-27 23:00:00');

-- Row 21: INC_26_020146
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020146', 141, NULL, 1306, 'We were not able to establish connection with them. | Systems Affected: All AKRB transactions', 'Medium', 'Medium', 'Error response from their end', 'We should monitoer closely to detect such issues on time', '2026-02-27 14:05:00', 'resolved', 6, 6, '2026-02-27 15:13:00', '2026-02-27 14:06:00', '2026-02-27 15:13:00');

-- Row 22: INC_26_020145
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020145', 142, NULL, 1316, 'Scheduled Maintenance | Systems Affected: Mobile Money / Vasgate', 'High', 'High', 'Syatem Maintenance', 'Continuous monitoring and reporting', '2026-02-25 23:00:00', 'resolved', 4, 4, '2026-02-26 05:00:00', '2026-02-25 23:00:00', '2026-02-26 05:00:00');

-- Row 23: INC_26_030149
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_030149', 142, 139, 1312, 'The SSL certificate expired causing VODA ADSL transactions to fail | Systems Affected: Telecel ADSL transactions', 'Critical', 'Urgent', 'SSL CERTIFICATE EXPIRY', 'Continuous monitoring and askiung for update till issue is resolved', '2026-02-25 11:54:00', 'resolved', 3, 3, '2026-02-28 17:16:00', '2026-02-25 11:54:00', '2026-02-28 17:16:00');

-- Row 24: INC_26_020144
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020144', 141, NULL, 1306, 'irtime transactions were failing due to an expired SSL certificate. | Systems Affected: All Mtn airtime transactions', 'Medium', 'Medium', 'expired SSL certificate.', 'There must be a continuous monitoring and asking of update from their team on the issue', '2026-02-24 20:48:00', 'resolved', 2, 2, '2026-02-24 22:30:00', '2026-02-24 20:48:00', '2026-02-24 22:30:00');

-- Row 25: INC_26_020143
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020143', 141, NULL, 1306, 'DOWNTIME | Systems Affected: MOBILE MONEY', 'Low', 'Low', 'CONNECTION ISSUE', 'CONTINUOUS MONITORING', '2026-02-24 00:00:00', 'resolved', 26, 26, '2026-02-24 00:00:00', '2026-02-24 00:00:00', '2026-02-24 00:00:00');

-- Row 26: INC_26_020142
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020142', 141, NULL, 1311, 'Insufficient OVA | Systems Affected: ECG Transactions', 'Medium', 'Medium', 'Insufficient OVA balance', 'Continuous monitoring and reporting', '2026-02-18 18:59:00', 'resolved', 4, 4, '2026-02-18 20:14:00', '2026-02-18 18:59:00', '2026-02-18 20:14:00');

-- Row 27: INC_26_020141
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020141', 141, NULL, 1310, 'Planned Maintenance by MTN | Systems Affected: All MTN MoMo transactions', 'High', 'High', 'Planned Maintenance', 'Always just the schedule time time to confirm if maintenance has started and do same for the end time', '2026-02-17 22:00:00', 'resolved', 5, 5, '2026-02-18 04:00:00', '2026-02-17 22:00:00', '2026-02-18 04:00:00');

-- Row 28: INC_26_020140
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020140', 141, 130, 1306, 'Transactions were failing | Systems Affected: All credit transactions', 'Medium', 'Medium', 'Transactions were failing', 'www', '2026-02-17 11:00:00', 'pending', 2, NULL, NULL, '2026-02-17 11:00:00', '2026-02-17 11:00:00');

-- Row 29: INC_26_020139
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020139', 141, NULL, 1311, 'Transactions were failing | Systems Affected: ALL BESTPOINT TRANSACTIONS', 'Medium', 'Medium', 'We couldnt access their server', 'Improve on proactive monitoring', '2026-02-17 10:53:00', 'resolved', 19, 19, '2026-02-17 11:54:00', '2026-02-17 10:54:00', '2026-02-17 11:54:00');

-- Row 30: INC_26_020138
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020138', 141, NULL, 1307, 'Bestpoint transactions were not going through. | Systems Affected: All Bestpoint Transactions', 'Low', 'Low', 'We were not able to access their server', 'There must be a continuous monitoring and asking of update from their team on the issue.', '2026-02-17 07:53:00', 'resolved', 19, 19, '2026-02-17 08:15:00', '2026-02-17 07:53:00', '2026-02-17 08:15:00');

-- Row 31: INC_26_020137
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020137', 142, NULL, 1312, 'Connectivity error | Systems Affected: Mobile Money / Vasgate', 'Critical', 'Urgent', 'The application could not connect to the database', 'Continuous monitoring and reporting', '2026-02-16 17:30:00', 'resolved', 4, 4, '2026-02-17 07:14:00', '2026-02-16 17:30:00', '2026-02-17 07:14:00');

-- Row 32: INC_26_020136
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020136', 141, NULL, 1306, 'Transactions were failing because we could not connect to their host | Systems Affected: All transactions', 'High', 'High', 'we could not connect to their host', 'There must be a continuous monitoring and asking of update from their team on the issue', '2026-02-16 02:46:00', 'pending', 2, NULL, NULL, '2026-02-16 02:46:00', '2026-02-16 02:46:00');

-- Row 33: INC_26_020133
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020133', 141, NULL, 1308, 'Transactions were failing because we getting response 01 from their cba | Systems Affected: All Transactions', 'Low', 'Low', 'We were getting response code 01 from their cba', 'There must be a continuous monitoring and asking of update from their team on the issue', '2026-02-16 00:58:00', 'resolved', 2, 2, '2026-02-16 01:43:00', '2026-02-16 01:58:00', '2026-02-16 01:43:00');

-- Row 34: INC_26_020134
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020134', 141, 130, 1306, 'All Telecel Transactions were failing | Systems Affected: Credit Transactions', 'Low', 'Low', 'All Telecel Transactions were failing', 'There must be a continuous monitoring and asking of update from their team on the issue', '2026-02-15 22:10:00', 'resolved', 2, 2, '2026-02-15 22:45:00', '2026-02-15 22:10:00', '2026-02-15 22:45:00');

-- Row 35: INC_26_020135
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020135', 142, 138, 1312, 'Telecel airtime transactions were failing | Systems Affected: Vasgate', 'Low', 'Low', 'All telecel airtime transactions were failing because of connectivity issue', 'There must be a continuous monitoring and asking of update from their team on the issue', '2026-02-15 19:37:00', 'resolved', 2, 2, '2026-02-15 20:09:00', '2026-02-15 19:37:00', '2026-02-15 20:09:00');

-- Row 36: INC_26_020132
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020132', 141, NULL, 1310, 'All MTN transactions were failing because of the maintenance | Systems Affected: Mobile Money', 'Critical', 'Urgent', 'System maintenance', 'Continuous monitoring and asking for updates till issue is resolved', '2026-02-14 22:00:00', 'resolved', 3, 3, '2026-02-15 10:00:00', '2026-02-14 22:00:00', '2026-02-15 10:00:00');

-- Row 37: INC_26_020131
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020131', 142, NULL, 1312, 'Server was down | Systems Affected: Mobile Money / Vasgate', 'Medium', 'Medium', 'Both SISL and PBL transactions were not coming through because the server 172.16.30.27 was down', 'Continous monitoring and reporting', '2026-02-13 19:32:00', 'resolved', 4, 4, '2026-02-13 22:12:00', '2026-02-13 19:32:00', '2026-02-13 22:12:00');

-- Row 38: INC_26_020130
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020130', 141, NULL, 1306, 'Transaction were not coming in because we could not connect to their server. | Systems Affected: All Transactions', 'Medium', 'Medium', 'we could not connect to their server.', 'There must be a continuous monitoring and asking of update from their team on the issue', '2026-02-12 17:19:00', 'resolved', 2, 2, '2026-02-12 20:58:00', '2026-02-12 17:19:00', '2026-02-12 20:58:00');

-- Row 39: INC_26_020129
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020129', 142, NULL, 1312, 'Error from PBL host | Systems Affected: Mobile Money / Vasgate', 'Low', 'Low', 'Difficulty connecting to PBL host', 'Continuous monitoring and reporting', '2026-02-12 10:58:00', 'resolved', 4, 4, '2026-02-12 11:45:00', '2026-02-12 10:58:00', '2026-02-12 11:45:00');

-- Row 40: INC_26_020128
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020128', 141, NULL, 1308, 'PBL\'s server and host were down which was causing transactions to fail | Systems Affected: All PBL transactions', 'High', 'High', 'Their host was down', 'There must be a continuous monitoring and asking of update from their team on the issue.', '2026-02-11 13:09:00', 'resolved', 5, 5, '2026-02-11 22:56:00', '2026-02-11 13:07:00', '2026-02-11 22:56:00');

-- Row 41: INC_26_020127
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020127', 142, NULL, 1312, 'Inability to connect to NIB sever | Systems Affected: Mobile Money / Vasgate', 'Medium', 'Medium', 'NIB server was not accessible', 'Continuous monitoring and reporting', '2026-02-09 15:19:00', 'resolved', 4, 4, '2026-02-09 17:33:00', '2026-02-09 15:19:00', '2026-02-09 17:33:00');

-- Row 42: INC_26_020126
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020126', 142, NULL, 1312, 'Transactions wre not coming in due to binding issue | Systems Affected: Mobile Money / Vasgate', 'High', 'High', 'Their CMS went off and it was not able to communicate with the TMC on central (172.16.30.6).', 'Continues monitoring and reporting.', '2026-02-09 07:10:00', 'resolved', 4, 4, '2026-02-09 12:30:00', '2026-02-09 07:10:00', '2026-02-09 12:30:00');

-- Row 43: INC_26_020125
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020125', 142, NULL, 1312, 'NIB server was not accessible resulting in transactions not coming in. | Systems Affected: Mobile Money / Vasgate', 'High', 'High', 'NIB server was not accessible resulting in transactions not coming in.', 'Continues monitoring and seeking rapid updates until issue is resolved.', '2026-02-08 02:00:00', 'resolved', 4, 4, '2026-02-08 07:55:00', '2026-02-08 02:00:00', '2026-02-08 07:55:00');

-- Row 44: INC_26_020124
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020124', 141, NULL, 1310, 'All services were down because of the maintenance by the network team | Systems Affected: All Systems', 'Medium', 'Medium', 'Network team had maintenance to perform causing all transactions to fail', 'Continuous monitoring and asking for updates till issue is resolved', '2026-02-08 01:00:00', 'resolved', 3, 3, '2026-02-08 03:00:00', '2026-02-08 01:00:00', '2026-02-08 03:00:00');

-- Row 45: INC_26_020123
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020123', 141, NULL, 1306, 'Transactions were failing because we could not connect to their host | Systems Affected: All transaction.', 'Medium', 'Medium', 'We could not connect to their host.', 'There must be a continuous monitoring and asking of update from their team on the issue', '2026-02-05 11:13:00', 'resolved', 2, 2, '2026-02-05 13:17:00', '2026-02-05 11:18:00', '2026-02-05 13:17:00');

-- Row 46: INC_26_020122
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020122', 141, NULL, 1306, 'A limit was made on the NIB mobile transactions which was causing transactions to fail | Systems Affected: NIB mobile App transactions', 'Medium', 'Medium', 'Limit made on the NIB mobile transaction', 'Continuously verify service availability and coordinate with all relevant personnel to support the investigation and resolution of the issue.', '2026-02-04 11:30:00', 'resolved', 5, 5, '2026-02-04 14:33:00', '2026-02-04 11:30:00', '2026-02-04 14:33:00');

-- Row 47: INC_26_020121
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020121', 142, NULL, 1312, 'We lost connectivity to their service due to binding issues | Systems Affected: Mobile Money and Vasgate', 'Critical', 'Urgent', 'Binding issue', 'Continuous monitoring and asking for update till issue is resolved', '2026-02-02 23:16:00', 'resolved', 3, 3, '2026-02-03 12:14:00', '2026-02-02 23:16:00', '2026-02-03 12:14:00');

-- Row 48: INC_26_020120
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020120', 141, NULL, 1310, 'GHIPSS was down causing transactions to fail, so we switched to OVA for transactions to start processing fine | Systems Affected: All transactions passing GHIPSS', 'Medium', 'Medium', 'GHIPSS was down causing transactions to fail', 'There must be a continuous monitoring and asking of update from their team on the issue.', '2026-02-02 18:40:00', 'resolved', 5, 5, '2026-02-02 21:15:00', '2026-02-03 13:20:00', '2026-02-02 21:15:00');

-- Row 49: INC_26_010118
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_010118', 141, NULL, 1306, 'We couldn\'t connect to our ISP | Systems Affected: All transactions', 'Medium', 'Medium', 'Internet issue', 'We should monitor closely to detect such issues on time', '2026-01-31 12:00:00', 'resolved', 6, 6, '2026-01-31 14:00:00', '2026-01-31 12:00:00', '2026-01-31 14:00:00');

-- Row 50: INC_26_020119
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_020119', 141, 135, 1306, 'Transactions were throwing 96 | Systems Affected: Credit and Top up transactions', 'Critical', 'Urgent', 'Binding', 'We should monitor closely to detect such issues on time', '2026-01-31 04:00:00', 'resolved', 2, 2, '2026-01-31 19:00:00', '2026-01-31 18:00:00', '2026-01-31 19:00:00');

-- Row 51: INC_26_010115
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_010115', 141, NULL, 1306, 'All Transactions were failing because we could not connect to their host. | Systems Affected: All Transactions', 'Medium', 'Medium', 'We could not connect to their Host', 'There must be a continuous monitoring and asking of update from their team on the issue', '2026-01-27 04:00:00', 'resolved', 2, 2, '2026-01-27 05:00:00', '2026-01-26 04:00:00', '2026-01-27 05:00:00');

-- Row 52: INC_26_010117
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_010117', 141, NULL, 1306, 'It was observed that transactions from Multichoice were no longer being received, while transactions from other merchants continued to process successfully through the same endpoint. This indicated that the issue was merchant-specific rather than a platform-wide outage. | Systems Affected: All Multichoice transactions', 'Critical', 'Urgent', 'Cloudflare security rules automatically blocked Multichoice IP addresses, preventing their requests from reaching the proxy server.  As a result, Cloudflare returned HTTP 403 responses to Multichoice, causing transaction failures.', 'We should monitor continuously to detect such issues on time', '2026-01-26 14:56:00', 'resolved', 6, 6, '2026-01-28 11:57:00', '2026-01-26 14:58:00', '2026-01-28 11:57:00');

-- Row 53: INC_26_010116
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_010116', 141, 131, 1311, 'There were delays on debit transactions. | Systems Affected: DEBIT TANSACTIONS', 'High', 'High', 'There were delays on debit transactions.', 'There must be a continuous monitoring and asking of update from their team on the issue', '2026-01-25 17:37:00', 'resolved', 2, 2, '2026-01-26 00:00:00', '2026-01-25 18:50:00', '2026-01-26 00:00:00');

-- Row 54: INC_26_010114
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_010114', 141, NULL, 1306, 'MTN Mobile money transactions were failing intermitently | Systems Affected: MTN Mobile money transactions', 'High', 'High', 'MTN were experiencing technical issues from their end causing MoMo transactions to fail intermitently', 'There must be a continuous monitoring and asking of update from their team on the issue.', '2026-01-24 19:43:00', 'resolved', 5, 5, '2026-01-25 01:31:00', '2026-01-24 19:43:00', '2026-01-25 01:31:00');

-- Row 55: INC_26_010113
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_010113', 141, 130, 1306, 'Credit transactions were failing intermittently | Systems Affected: All Credit transactions (NIB)', 'High', 'High', 'We could not connect to their host.', 'There must be a continuous monitoring and asking of update from their team on the issue.', '2026-01-23 11:50:00', 'resolved', 5, 5, '2026-01-23 17:45:00', '2026-01-23 11:50:00', '2026-01-23 17:45:00');

-- Row 56: INC_26_010112
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_010112', 141, NULL, 1308, 'Their USSD went down | Systems Affected: Mobile Money', 'Low', 'Low', 'The USSD service went down', 'Continuous monitoring and asking for updates till issue is resolved', '2026-01-22 13:39:00', 'resolved', 3, 3, '2026-01-22 13:54:00', '2026-01-22 13:39:00', '2026-01-22 13:54:00');

-- Row 57: INC_26_010111
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_010111', 142, NULL, 1312, 'Transactions were failing because we could not connect to their host | Systems Affected: Mobile Money and Vasgate', 'Medium', 'Medium', 'Their host went down', 'Continuous monitoring and asking for updates till issue is resolved', '2026-01-20 21:00:00', 'resolved', 3, 3, '2026-01-20 23:25:00', '2026-01-20 21:00:00', '2026-01-20 23:25:00');

-- Row 58: INC_26_010108
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_010108', 141, NULL, 1310, 'MTN Planned Maintenance | Systems Affected: All Transactions', 'Critical', 'Urgent', 'Planned Maintenance', 'There must be a continuous monitoring and asking of update from their team on the issue', '2026-01-19 11:00:00', 'resolved', 2, 2, '2026-01-20 05:00:00', '2026-01-19 11:00:00', '2026-01-20 05:00:00');

-- Row 59: INC_26_010109
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_010109', 141, NULL, 1306, 'PBL had a middleware API downtime | Systems Affected: All PBL transactions', 'Critical', 'Urgent', 'API downtime', 'We should monitor transactions closely to detect such issues on time', '2026-01-19 09:00:00', 'resolved', 6, 6, '2026-01-20 05:00:00', '2026-01-19 09:00:00', '2026-01-20 05:00:00');

-- Row 60: INC_26_010110
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_010110', 143, 143, 1323, 'All telecel debit on fundgate where failing due to wrong reference being sent to telco. We noticed that "#0000" was appended to all the references being sent to the telco causing failures. | Systems Affected: All Telecel debit transactions', 'Critical', 'Urgent', 'Still under investigation', 'We should monitor closely to detect such issues on time', '2026-01-18 09:37:00', 'resolved', 6, 6, '2026-01-20 10:00:00', '2026-01-18 09:40:00', '2026-01-20 10:00:00');

-- Row 61: INC_26_010107
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_010107', 141, NULL, 1306, 'Transactions were failing because we could not connect to their host | Systems Affected: All Transactions', 'Medium', 'Medium', 'We could not connect to their host', 'There must be a continuous monitoring and asking of update from their team on the issue', '2026-01-17 05:32:00', 'resolved', 2, 2, '2026-01-17 06:42:00', '2026-01-17 05:32:00', '2026-01-17 06:42:00');

-- Row 62: INC_26_010106
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_010106', 141, NULL, 1310, 'Transactions were not coming because the was a planned maintenance | Systems Affected: All transactions', 'Medium', 'Medium', 'Planned maintenance', 'There must be a continuous monitoring and asking of update from their team on the issue', '2026-01-17 00:00:00', 'resolved', 2, 2, '2026-01-17 02:00:00', '2026-01-17 00:00:00', '2026-01-17 02:00:00');

-- Row 63: INC_26_010105
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_010105', 142, NULL, 1312, 'We were not receiving transaction request and traffick as at 12:45 am, Fundgate could not connect, AWS my sql could not connect, Vasgate could not connect, Virtual bank could not connect. | Systems Affected: All clients and Fundgate clients', 'Medium', 'Medium', 'We were not receiving traffic as we could not connect to fundgate, AWS mysql, VASgate and Virtual Bank', 'Always monitor all services and reach out to the appropraite teams involved', '2026-01-13 00:44:00', 'resolved', 5, 5, '2026-01-13 03:40:00', '2026-01-13 01:28:00', '2026-01-13 03:40:00');

-- Row 64: INC_26_010103
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_010103', 141, NULL, 1306, 'Transactions were failing because we could not connect to their host. | Systems Affected: All transactions', 'Low', 'Low', 'we could not connect to their host.', 'There must be a continuous monitoring and asking of update from their team on the issue', '2026-01-10 18:26:00', 'resolved', 2, 2, '2026-01-10 19:05:00', '2026-01-10 18:26:00', '2026-01-10 19:05:00');

-- Row 65: INC_26_010102
INSERT INTO incidents (incident_ref, service_id, component_id, incident_type_id, description, impact_level, priority, root_cause, lessons_learned, actual_start_time, status, reported_by, resolved_by, resolved_at, created_at, updated_at)
VALUES ('INC_26_010102', 142, NULL, 1312, 'We could not connect to their host | Systems Affected: Mobile money and Vasgate', 'High', 'High', 'We were unable to connect to their host causing their transactions to fail', 'Continuous monitoring and asking for update till issue has been resolved', '2026-01-07 01:32:00', 'resolved', 3, 3, '2026-01-07 07:19:00', '2026-01-07 01:32:00', '2026-01-07 07:19:00');


-- =============================================
-- STEP 2: INSERT DOWNTIME_INCIDENTS
-- (Using incident references to look up IDs)
-- =============================================

-- Row 1: INC_26_030166
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-03-20 19:05:00', '2026-03-21 02:05:00', 420, 0, 'Other'
FROM incidents WHERE incident_ref = 'INC_26_030166';

-- Row 2: INC_26_030165
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-03-19 16:15:00', '2026-03-19 20:15:00', 240, 0, 'Other'
FROM incidents WHERE incident_ref = 'INC_26_030165';

-- Row 3: INC_26_030164
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-03-19 09:20:00', '2026-03-19 14:50:00', 330, 0, 'Other'
FROM incidents WHERE incident_ref = 'INC_26_030164';

-- Row 4: INC_26_030167
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-03-17 06:55:00', '2026-03-22 17:01:00', 7806, 0, 'Server'
FROM incidents WHERE incident_ref = 'INC_26_030167';

-- Row 5: INC_26_030163
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-03-15 00:00:00', '2026-03-15 08:00:00', 480, 1, 'Maintenance'
FROM incidents WHERE incident_ref = 'INC_26_030163';

-- Row 6: INC_26_030162
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-03-14 22:00:00', '2026-03-15 10:00:00', 720, 1, 'Maintenance'
FROM incidents WHERE incident_ref = 'INC_26_030162';

-- Row 7: INC_26_030161
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-03-14 20:00:00', '2026-03-14 21:00:00', 60, 0, 'Server'
FROM incidents WHERE incident_ref = 'INC_26_030161';

-- Row 8: INC_26_030159
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-03-14 09:36:00', '2026-03-14 13:11:00', 215, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_030159';

-- Row 9: INC_26_030160
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-03-14 00:00:00', '2026-03-14 14:09:00', 849, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_030160';

-- Row 10: INC_26_030158
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-03-12 17:19:00', '2026-03-12 18:38:00', 79, 0, 'Server'
FROM incidents WHERE incident_ref = 'INC_26_030158';

-- Row 11: INC_26_030157
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-03-12 00:03:00', '2026-03-12 01:38:00', 95, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_030157';

-- Row 12: INC_26_030156
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-03-10 01:00:00', '2026-03-10 05:00:00', 240, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_030156';

-- Row 13: INC_26_030155
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-03-09 19:52:00', '2026-03-09 22:00:00', 128, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_030155';

-- Row 14: INC_26_030153
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-03-08 06:40:00', '2026-03-08 08:20:00', 100, 0, 'Server'
FROM incidents WHERE incident_ref = 'INC_26_030153';

-- Row 15: INC_26_030152
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-03-06 07:20:00', '2026-03-06 17:23:00', 603, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_030152';

-- Row 16: INC_26_030154
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-03-06 05:50:00', '2026-03-06 16:26:00', 636, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_030154';

-- Row 17: INC_26_030150
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-03-03 19:28:00', '2026-03-03 23:50:00', 262, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_030150';

-- Row 18: INC_26_030151
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-03-03 12:00:00', '2026-03-03 16:16:00', 256, 0, 'Other'
FROM incidents WHERE incident_ref = 'INC_26_030151';

-- Row 19: INC_26_020147
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-28 01:40:00', '2026-02-28 05:00:00', 200, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_020147';

-- Row 20: INC_26_020148
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-27 23:00:00', '2026-02-28 07:00:00', 480, 0, 'Server'
FROM incidents WHERE incident_ref = 'INC_26_020148';

-- Row 21: INC_26_020146
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-27 14:05:00', '2026-02-27 15:13:00', 68, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_020146';

-- Row 22: INC_26_020145
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-25 23:00:00', '2026-02-26 05:00:00', 360, 1, 'Maintenance'
FROM incidents WHERE incident_ref = 'INC_26_020145';

-- Row 23: INC_26_030149
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-25 11:54:00', '2026-02-28 17:16:00', 4642, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_030149';

-- Row 24: INC_26_020144
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-24 20:48:00', '2026-02-24 22:30:00', 102, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_020144';

-- Row 25: INC_26_020143
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-24 00:00:00', '2026-02-24 00:00:00', 0, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_020143';

-- Row 26: INC_26_020142
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-18 18:59:00', '2026-02-18 20:14:00', 75, 0, 'Other'
FROM incidents WHERE incident_ref = 'INC_26_020142';

-- Row 27: INC_26_020141
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-17 22:00:00', '2026-02-18 04:00:00', 360, 1, 'Maintenance'
FROM incidents WHERE incident_ref = 'INC_26_020141';

-- Row 28: INC_26_020140
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-17 11:00:00', '2026-02-17 12:30:00', 90, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_020140';

-- Row 29: INC_26_020139
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-17 10:53:00', '2026-02-17 11:54:00', 61, 0, 'Other'
FROM incidents WHERE incident_ref = 'INC_26_020139';

-- Row 30: INC_26_020138
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-17 07:53:00', '2026-02-17 08:15:00', 22, 0, 'Server'
FROM incidents WHERE incident_ref = 'INC_26_020138';

-- Row 31: INC_26_020137
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-16 17:30:00', '2026-02-17 07:14:00', 824, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_020137';

-- Row 32: INC_26_020136
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-16 02:46:00', '2026-02-16 09:00:00', 374, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_020136';

-- Row 33: INC_26_020133
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-16 00:58:00', '2026-02-16 01:43:00', 45, 0, 'Server'
FROM incidents WHERE incident_ref = 'INC_26_020133';

-- Row 34: INC_26_020134
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-15 22:10:00', '2026-02-15 22:45:00', 35, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_020134';

-- Row 35: INC_26_020135
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-15 19:37:00', '2026-02-15 20:09:00', 32, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_020135';

-- Row 36: INC_26_020132
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-14 22:00:00', '2026-02-15 10:00:00', 720, 1, 'Maintenance'
FROM incidents WHERE incident_ref = 'INC_26_020132';

-- Row 37: INC_26_020131
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-13 19:32:00', '2026-02-13 22:12:00', 160, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_020131';

-- Row 38: INC_26_020130
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-12 17:19:00', '2026-02-12 20:58:00', 219, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_020130';

-- Row 39: INC_26_020129
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-12 10:58:00', '2026-02-12 11:45:00', 47, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_020129';

-- Row 40: INC_26_020128
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-11 13:09:00', '2026-02-11 22:56:00', 587, 0, 'Server'
FROM incidents WHERE incident_ref = 'INC_26_020128';

-- Row 41: INC_26_020127
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-09 15:19:00', '2026-02-09 17:33:00', 134, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_020127';

-- Row 42: INC_26_020126
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-09 07:10:00', '2026-02-09 12:30:00', 320, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_020126';

-- Row 43: INC_26_020125
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-08 02:00:00', '2026-02-08 07:55:00', 355, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_020125';

-- Row 44: INC_26_020124
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-08 01:00:00', '2026-02-08 03:00:00', 120, 1, 'Maintenance'
FROM incidents WHERE incident_ref = 'INC_26_020124';

-- Row 45: INC_26_020123
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-05 11:13:00', '2026-02-05 13:17:00', 124, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_020123';

-- Row 46: INC_26_020122
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-04 11:30:00', '2026-02-04 14:33:00', 183, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_020122';

-- Row 47: INC_26_020121
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-02 23:16:00', '2026-02-03 12:14:00', 778, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_020121';

-- Row 48: INC_26_020120
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-02-02 18:40:00', '2026-02-02 21:15:00', 155, 1, 'Maintenance'
FROM incidents WHERE incident_ref = 'INC_26_020120';

-- Row 49: INC_26_010118
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-01-31 12:00:00', '2026-01-31 14:00:00', 120, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_010118';

-- Row 50: INC_26_020119
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-01-31 04:00:00', '2026-01-31 19:00:00', 900, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_020119';

-- Row 51: INC_26_010115
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-01-27 04:00:00', '2026-01-27 05:00:00', 60, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_010115';

-- Row 52: INC_26_010117
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-01-26 14:56:00', '2026-01-28 11:57:00', 2701, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_010117';

-- Row 53: INC_26_010116
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-01-25 17:37:00', '2026-01-26 00:00:00', 383, 0, 'Other'
FROM incidents WHERE incident_ref = 'INC_26_010116';

-- Row 54: INC_26_010114
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-01-24 19:43:00', '2026-01-25 01:31:00', 348, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_010114';

-- Row 55: INC_26_010113
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-01-23 11:50:00', '2026-01-23 17:45:00', 355, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_010113';

-- Row 56: INC_26_010112
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-01-22 13:39:00', '2026-01-22 13:54:00', 15, 0, 'Server'
FROM incidents WHERE incident_ref = 'INC_26_010112';

-- Row 57: INC_26_010111
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-01-20 21:00:00', '2026-01-20 23:25:00', 145, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_010111';

-- Row 58: INC_26_010108
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-01-19 11:00:00', '2026-01-20 05:00:00', 1080, 1, 'Maintenance'
FROM incidents WHERE incident_ref = 'INC_26_010108';

-- Row 59: INC_26_010109
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-01-19 09:00:00', '2026-01-20 05:00:00', 1200, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_010109';

-- Row 60: INC_26_010110
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-01-18 09:37:00', '2026-01-20 10:00:00', 2903, 0, 'Other'
FROM incidents WHERE incident_ref = 'INC_26_010110';

-- Row 61: INC_26_010107
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-01-17 05:32:00', '2026-01-17 06:42:00', 70, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_010107';

-- Row 62: INC_26_010106
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-01-17 00:00:00', '2026-01-17 02:00:00', 120, 1, 'Maintenance'
FROM incidents WHERE incident_ref = 'INC_26_010106';

-- Row 63: INC_26_010105
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-01-13 00:44:00', '2026-01-13 03:40:00', 176, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_010105';

-- Row 64: INC_26_010103
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-01-10 18:26:00', '2026-01-10 19:05:00', 39, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_010103';

-- Row 65: INC_26_010102
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time, downtime_minutes, is_planned, downtime_category)
SELECT incident_id, '2026-01-07 01:32:00', '2026-01-07 07:19:00', 347, 0, 'Network'
FROM incidents WHERE incident_ref = 'INC_26_010102';


-- =============================================
-- STEP 3: INSERT INCIDENT_AFFECTED_COMPANIES
-- (Link incidents to affected companies)
-- =============================================

INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 3 FROM incidents WHERE incident_ref = 'INC_26_030166';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 3 FROM incidents WHERE incident_ref = 'INC_26_030165';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 3 FROM incidents WHERE incident_ref = 'INC_26_030164';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 16 FROM incidents WHERE incident_ref = 'INC_26_030167';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 2 FROM incidents WHERE incident_ref = 'INC_26_030163';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 9 FROM incidents WHERE incident_ref = 'INC_26_030162';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 9 FROM incidents WHERE incident_ref = 'INC_26_030161';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 1 FROM incidents WHERE incident_ref = 'INC_26_030159';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 17 FROM incidents WHERE incident_ref = 'INC_26_030160';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 3 FROM incidents WHERE incident_ref = 'INC_26_030158';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 9 FROM incidents WHERE incident_ref = 'INC_26_030157';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 9 FROM incidents WHERE incident_ref = 'INC_26_030156';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 6 FROM incidents WHERE incident_ref = 'INC_26_030155';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 3 FROM incidents WHERE incident_ref = 'INC_26_030153';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 13 FROM incidents WHERE incident_ref = 'INC_26_030152';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 13 FROM incidents WHERE incident_ref = 'INC_26_030154';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 13 FROM incidents WHERE incident_ref = 'INC_26_030150';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 17 FROM incidents WHERE incident_ref = 'INC_26_030151';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 3 FROM incidents WHERE incident_ref = 'INC_26_020147';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 16 FROM incidents WHERE incident_ref = 'INC_26_020148';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 4 FROM incidents WHERE incident_ref = 'INC_26_020146';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 9 FROM incidents WHERE incident_ref = 'INC_26_020145';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 16 FROM incidents WHERE incident_ref = 'INC_26_030149';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 9 FROM incidents WHERE incident_ref = 'INC_26_020144';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 2 FROM incidents WHERE incident_ref = 'INC_26_020143';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 7 FROM incidents WHERE incident_ref = 'INC_26_020142';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 9 FROM incidents WHERE incident_ref = 'INC_26_020141';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 16 FROM incidents WHERE incident_ref = 'INC_26_020140';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 6 FROM incidents WHERE incident_ref = 'INC_26_020139';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 6 FROM incidents WHERE incident_ref = 'INC_26_020138';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 5 FROM incidents WHERE incident_ref = 'INC_26_020137';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 13 FROM incidents WHERE incident_ref = 'INC_26_020136';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 13 FROM incidents WHERE incident_ref = 'INC_26_020133';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 16 FROM incidents WHERE incident_ref = 'INC_26_020134';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 16 FROM incidents WHERE incident_ref = 'INC_26_020135';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 9 FROM incidents WHERE incident_ref = 'INC_26_020132';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 13 FROM incidents WHERE incident_ref = 'INC_26_020131';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 14 FROM incidents WHERE incident_ref = 'INC_26_020131';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 11 FROM incidents WHERE incident_ref = 'INC_26_020130';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 13 FROM incidents WHERE incident_ref = 'INC_26_020129';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 13 FROM incidents WHERE incident_ref = 'INC_26_020128';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 11 FROM incidents WHERE incident_ref = 'INC_26_020127';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 4 FROM incidents WHERE incident_ref = 'INC_26_020126';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 11 FROM incidents WHERE incident_ref = 'INC_26_020125';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 3 FROM incidents WHERE incident_ref = 'INC_26_020124';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 6 FROM incidents WHERE incident_ref = 'INC_26_020123';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 11 FROM incidents WHERE incident_ref = 'INC_26_020122';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 4 FROM incidents WHERE incident_ref = 'INC_26_020121';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 1 FROM incidents WHERE incident_ref = 'INC_26_020120';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 6 FROM incidents WHERE incident_ref = 'INC_26_020120';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 5 FROM incidents WHERE incident_ref = 'INC_26_020120';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 11 FROM incidents WHERE incident_ref = 'INC_26_020120';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 13 FROM incidents WHERE incident_ref = 'INC_26_020120';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 14 FROM incidents WHERE incident_ref = 'INC_26_020120';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 17 FROM incidents WHERE incident_ref = 'INC_26_020120';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 4 FROM incidents WHERE incident_ref = 'INC_26_020120';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 3 FROM incidents WHERE incident_ref = 'INC_26_010118';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 4 FROM incidents WHERE incident_ref = 'INC_26_020119';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 1 FROM incidents WHERE incident_ref = 'INC_26_010115';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 10 FROM incidents WHERE incident_ref = 'INC_26_010117';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 9 FROM incidents WHERE incident_ref = 'INC_26_010116';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 9 FROM incidents WHERE incident_ref = 'INC_26_010114';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 11 FROM incidents WHERE incident_ref = 'INC_26_010113';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 14 FROM incidents WHERE incident_ref = 'INC_26_010112';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 13 FROM incidents WHERE incident_ref = 'INC_26_010112';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 11 FROM incidents WHERE incident_ref = 'INC_26_010111';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 9 FROM incidents WHERE incident_ref = 'INC_26_010108';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 13 FROM incidents WHERE incident_ref = 'INC_26_010109';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 16 FROM incidents WHERE incident_ref = 'INC_26_010110';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 1 FROM incidents WHERE incident_ref = 'INC_26_010107';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 3 FROM incidents WHERE incident_ref = 'INC_26_010106';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 3 FROM incidents WHERE incident_ref = 'INC_26_010105';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 1 FROM incidents WHERE incident_ref = 'INC_26_010103';
INSERT INTO incident_affected_companies (incident_id, company_id)
SELECT incident_id, 11 FROM incidents WHERE incident_ref = 'INC_26_010102';

-- =============================================
-- STEP 4: INSERT INCIDENT_UPDATES (Actions Taken)
-- =============================================

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 7, 'Takyi Owusu Mensah', 'An email was sent to the ISP.'
FROM incidents WHERE incident_ref = 'INC_26_030166';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 7, 'Takyi Owusu Mensah', 'An incident form was completed'
FROM incidents WHERE incident_ref = 'INC_26_030166';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 7, 'Takyi Owusu Mensah', 'Traffic was switched temporary to the other ISP until mainone was stable.'
FROM incidents WHERE incident_ref = 'INC_26_030165';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 7, 'Takyi Owusu Mensah', 'An incident report was completed.'
FROM incidents WHERE incident_ref = 'INC_26_030165';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 7, 'Takyi Owusu Mensah', 'An email was sent to mainone informing them of the downtime.'
FROM incidents WHERE incident_ref = 'INC_26_030164';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 7, 'Takyi Owusu Mensah', 'An incident form was completed.'
FROM incidents WHERE incident_ref = 'INC_26_030164';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 7, 'Takyi Owusu Mensah', 'A ticket was create on the mainone customer portal'
FROM incidents WHERE incident_ref = 'INC_26_030164';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'error was shared with the adsl team via mail'
FROM incidents WHERE incident_ref = 'INC_26_030167';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'The issue was resolved by the Adsl team'
FROM incidents WHERE incident_ref = 'INC_26_030167';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'Mails were sent to clients'
FROM incidents WHERE incident_ref = 'INC_26_030163';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'Issue was resolved by the AT team'
FROM incidents WHERE incident_ref = 'INC_26_030163';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'Mails were sent to clients'
FROM incidents WHERE incident_ref = 'INC_26_030162';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'issue was resolved by mtn'
FROM incidents WHERE incident_ref = 'INC_26_030162';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'Issue was resolved by Team MTN'
FROM incidents WHERE incident_ref = 'INC_26_030161';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 6, 'Mary Asante', 'The issue was escalated to the Abii National Team and was resolved at their end'
FROM incidents WHERE incident_ref = 'INC_26_030159';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 6, 'Mary Asante', 'We escalated the issue to the Vision fund team'
FROM incidents WHERE incident_ref = 'INC_26_030160';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 25, 'Anku Bright', 'The issue was escalated to the DevOps team. And It was solved by them.'
FROM incidents WHERE incident_ref = 'INC_26_030158';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'Mails were sent to clients'
FROM incidents WHERE incident_ref = 'INC_26_030157';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'issue was resolved from our end'
FROM incidents WHERE incident_ref = 'INC_26_030157';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'Clients were informed about the maintenance schedule'
FROM incidents WHERE incident_ref = 'INC_26_030156';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'Clients were informed when the maintenance was over'
FROM incidents WHERE incident_ref = 'INC_26_030156';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'Issue was escalated to BESTPOINT team'
FROM incidents WHERE incident_ref = 'INC_26_030155';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'BESTPOINT team resolved the issue'
FROM incidents WHERE incident_ref = 'INC_26_030155';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 25, 'Anku Bright', 'The 30.17 server was restarted'
FROM incidents WHERE incident_ref = 'INC_26_030153';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 3, 'Maxwell Eshun', 'Issue was escalated to PBL team'
FROM incidents WHERE incident_ref = 'INC_26_030152';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 3, 'Maxwell Eshun', 'PBL tech team resolved the issue from their end'
FROM incidents WHERE incident_ref = 'INC_26_030152';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'The Issue was escalated to the PBL team.'
FROM incidents WHERE incident_ref = 'INC_26_030154';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'The issue was resolved by the Pbl team'
FROM incidents WHERE incident_ref = 'INC_26_030154';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'Issue was escalated to the PBL team'
FROM incidents WHERE incident_ref = 'INC_26_030150';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'The PBL team resolved the issue from their end'
FROM incidents WHERE incident_ref = 'INC_26_030150';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 8, 'Simon Owusu Ansah', 'The issue was escalated to Devops'
FROM incidents WHERE incident_ref = 'INC_26_030151';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'Issue was escalated to the network team'
FROM incidents WHERE incident_ref = 'INC_26_020147';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'A message was sent to Rancard via teams'
FROM incidents WHERE incident_ref = 'INC_26_020148';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'The issue was resolved from their end.'
FROM incidents WHERE incident_ref = 'INC_26_020148';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 6, 'Mary Asante', 'The issue was esclated to the DevOps team'
FROM incidents WHERE incident_ref = 'INC_26_020146';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 6, 'Mary Asante', 'AKRB team was engaged and the issue was resolved at their end'
FROM incidents WHERE incident_ref = 'INC_26_020146';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'Clients were informed about the maintenance activity'
FROM incidents WHERE incident_ref = 'INC_26_020145';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'Clients were informed when the maintenance activity was over'
FROM incidents WHERE incident_ref = 'INC_26_020145';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 3, 'Maxwell Eshun', 'Isssue was escalated to the DEVOPS team'
FROM incidents WHERE incident_ref = 'INC_26_030149';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 3, 'Maxwell Eshun', 'DEVOPS team downloaded the latest certificate and replaced it with the old'
FROM incidents WHERE incident_ref = 'INC_26_030149';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'We checked the logs to know the root cause'
FROM incidents WHERE incident_ref = 'INC_26_020144';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'The issue was resolved at our end'
FROM incidents WHERE incident_ref = 'INC_26_020144';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 26, 'Israel Opata', 'ISSUE WAS ESCALATED'
FROM incidents WHERE incident_ref = 'INC_26_020143';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'Issue was escalated to the finance team'
FROM incidents WHERE incident_ref = 'INC_26_020142';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'The finance team resolved the issue'
FROM incidents WHERE incident_ref = 'INC_26_020142';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 5, 'Fredrick Hanson', 'All clients and Banks were inform via whatsApp and Mail'
FROM incidents WHERE incident_ref = 'INC_26_020141';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'www'
FROM incidents WHERE incident_ref = 'INC_26_020140';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 19, 'Jacob Quarshie Nii Odoi', 'Reached out to the PBL team and waited for further updates'
FROM incidents WHERE incident_ref = 'INC_26_020139';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 19, 'Jacob Quarshie Nii Odoi', 'Bestpoint Team notified and restored connection'
FROM incidents WHERE incident_ref = 'INC_26_020138';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'Issue was escalated to BOA team to restart the server from their end'
FROM incidents WHERE incident_ref = 'INC_26_020137';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'BOA team restarted the server and resolved the issue'
FROM incidents WHERE incident_ref = 'INC_26_020137';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'Issue was esclated to the pbl team via mail'
FROM incidents WHERE incident_ref = 'INC_26_020136';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'A call was made to a member of the pbl team'
FROM incidents WHERE incident_ref = 'INC_26_020133';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'A message containg the error was sent to their Teams page'
FROM incidents WHERE incident_ref = 'INC_26_020133';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'A mail was sent to all clients'
FROM incidents WHERE incident_ref = 'INC_26_020134';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'Telecel restored service from their end'
FROM incidents WHERE incident_ref = 'INC_26_020134';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'A message was sent to Rancard'
FROM incidents WHERE incident_ref = 'INC_26_020135';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'Rancard resolved the issue from their end'
FROM incidents WHERE incident_ref = 'INC_26_020135';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 3, 'Maxwell Eshun', 'Connection was restored by MTN tech team'
FROM incidents WHERE incident_ref = 'INC_26_020132';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'Banks involved were notified'
FROM incidents WHERE incident_ref = 'INC_26_020131';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'The server was restarted to resolve the issue'
FROM incidents WHERE incident_ref = 'INC_26_020131';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'A mail was sent to the NIB team'
FROM incidents WHERE incident_ref = 'INC_26_020130';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'Issue was escalated to PBL team'
FROM incidents WHERE incident_ref = 'INC_26_020129';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'PBL team resolved the issue'
FROM incidents WHERE incident_ref = 'INC_26_020129';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 5, 'Fredrick Hanson', 'PBL tech team was contacted via Teams & mail'
FROM incidents WHERE incident_ref = 'INC_26_020128';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'Issue was escalated to the NIB team'
FROM incidents WHERE incident_ref = 'INC_26_020127';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'The NIB team resolved the issue'
FROM incidents WHERE incident_ref = 'INC_26_020127';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'We restarted the CBA and the CMS'
FROM incidents WHERE incident_ref = 'INC_26_020126';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'Issue was escalated to the NIB team'
FROM incidents WHERE incident_ref = 'INC_26_020125';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 4, 'Eric Fillipe Arthur', 'Issue was resolved by the NIB team'
FROM incidents WHERE incident_ref = 'INC_26_020125';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 3, 'Maxwell Eshun', 'An Email was sent to clients regarding the maintenance'
FROM incidents WHERE incident_ref = 'INC_26_020124';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'Issue was esclated to the Bestpoint team'
FROM incidents WHERE incident_ref = 'INC_26_020123';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'Issue was resolved at their end'
FROM incidents WHERE incident_ref = 'INC_26_020123';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 5, 'Fredrick Hanson', 'We communicated with NIB via WhatsApp'
FROM incidents WHERE incident_ref = 'INC_26_020122';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 3, 'Maxwell Eshun', 'Issue was escalated tO technical operations team'
FROM incidents WHERE incident_ref = 'INC_26_020121';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 3, 'Maxwell Eshun', 'Issue was resolved by the same team'
FROM incidents WHERE incident_ref = 'INC_26_020121';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 5, 'Fredrick Hanson', 'Transactions were switched to OVA and finance was informed'
FROM incidents WHERE incident_ref = 'INC_26_020120';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 6, 'Mary Asante', 'The issue was escalated to Comsis'
FROM incidents WHERE incident_ref = 'INC_26_010118';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'The issue was resolved by our technical team'
FROM incidents WHERE incident_ref = 'INC_26_020119';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'The Issue was escalated to the ABII team via whatsapp.'
FROM incidents WHERE incident_ref = 'INC_26_010115';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'The issue was resolved at their end'
FROM incidents WHERE incident_ref = 'INC_26_010115';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 6, 'Mary Asante', 'Cloudflare Routing / Security Rules were updated to explicitly allow Multichoice IP addresses.'
FROM incidents WHERE incident_ref = 'INC_26_010117';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 6, 'Mary Asante', 'The rule change was applied successfully.'
FROM incidents WHERE incident_ref = 'INC_26_010117';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 6, 'Mary Asante', 'Issue was escalated to the Multichoice team'
FROM incidents WHERE incident_ref = 'INC_26_010117';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'The Issue was escalated to the MTN team.'
FROM incidents WHERE incident_ref = 'INC_26_010116';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'The issue was resolved by the MTN team'
FROM incidents WHERE incident_ref = 'INC_26_010116';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 5, 'Fredrick Hanson', 'A mail was sent to various clients'
FROM incidents WHERE incident_ref = 'INC_26_010114';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 5, 'Fredrick Hanson', 'All clients were informed via whatsApp as well'
FROM incidents WHERE incident_ref = 'INC_26_010114';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 5, 'Fredrick Hanson', 'We contacted the NIB team via WhatsApp'
FROM incidents WHERE incident_ref = 'INC_26_010113';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 5, 'Fredrick Hanson', 'We contacted the NIB Tech team via mail'
FROM incidents WHERE incident_ref = 'INC_26_010113';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 3, 'Maxwell Eshun', 'Issue was escalated to the DEVOPS team'
FROM incidents WHERE incident_ref = 'INC_26_010112';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 3, 'Maxwell Eshun', 'DEVOPS team resolved the issue'
FROM incidents WHERE incident_ref = 'INC_26_010112';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 3, 'Maxwell Eshun', 'Issue was ecalated to NIB team via Whatsapp and Mail'
FROM incidents WHERE incident_ref = 'INC_26_010111';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 3, 'Maxwell Eshun', 'NIB team restored conncetion from their end'
FROM incidents WHERE incident_ref = 'INC_26_010111';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'A mail was sent to all client'
FROM incidents WHERE incident_ref = 'INC_26_010108';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'The issue was resolved by Team Mtn'
FROM incidents WHERE incident_ref = 'INC_26_010108';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 6, 'Mary Asante', 'We escalated the issue to the PBL team'
FROM incidents WHERE incident_ref = 'INC_26_010109';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 6, 'Mary Asante', 'We blocked all their USSD services'
FROM incidents WHERE incident_ref = 'INC_26_010109';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 6, 'Mary Asante', 'A fix was deployed to both fundgate application'
FROM incidents WHERE incident_ref = 'INC_26_010110';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'The Issue was escalated to the ABII team.'
FROM incidents WHERE incident_ref = 'INC_26_010107';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'The issue was resolved at their end'
FROM incidents WHERE incident_ref = 'INC_26_010107';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'Mails were sent to client'
FROM incidents WHERE incident_ref = 'INC_26_010106';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'Issue was resolved by etz team'
FROM incidents WHERE incident_ref = 'INC_26_010106';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 5, 'Fredrick Hanson', 'A mail was sent to all clients'
FROM incidents WHERE incident_ref = 'INC_26_010105';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 5, 'Fredrick Hanson', 'All Clients were informed on various pages'
FROM incidents WHERE incident_ref = 'INC_26_010105';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'The Issue was escalated to the ABII team.'
FROM incidents WHERE incident_ref = 'INC_26_010103';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 2, 'Harry Opata', 'The issue was resolved by the ABII team.'
FROM incidents WHERE incident_ref = 'INC_26_010103';

INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 3, 'Maxwell Eshun', 'Issue was escalated to NIB team'
FROM incidents WHERE incident_ref = 'INC_26_010102';
INSERT INTO incident_updates (incident_id, user_id, user_name, update_text)
SELECT incident_id, 3, 'Maxwell Eshun', 'NIB team restored connection from their end'
FROM incidents WHERE incident_ref = 'INC_26_010102';

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- MIGRATION COMPLETE
-- Total incidents: 65
-- =============================================
