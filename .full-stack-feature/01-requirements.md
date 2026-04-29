# Requirements: Export Reported Incidents to Excel

## Problem Statement
Multiple stakeholder groups (management, compliance/audit teams, operations staff) need incident data
exported to Excel for periodic reporting, audit trails, and offline trend analysis. Currently there is
no way to download filtered incident data from the incidents page; users must manually transcribe data.

## Acceptance Criteria

- [ ] A date range picker (start date + end date) is present on the incidents page for each tab (downtime, security, fraud)
- [ ] An "Export to Excel" button is visible on the incidents page toolbar alongside the existing filters
- [ ] Clicking Export generates and downloads a properly formatted .xlsx file instantly
- [ ] The exported file includes all relevant columns for the active incident type (see column lists below)
- [ ] The export respects currently-active filters: date range, status filter, and search text
- [ ] The filename includes the incident type and date range (e.g. `Downtime_Incidents_2026-01-01_to_2026-04-27.xlsx`)
- [ ] The export works for all three tabs: Downtime, Security, and Fraud incidents

## Column Lists per Tab

### Downtime Incidents
Incident Ref, Service, Components, Incident Type, Impact Level, Priority, Source (Internal/External),
Affected Companies, Status, Reported By, Actual Start Time, Resolved At, Downtime Minutes,
Root Cause, Lessons Learned, Description

### Security Incidents
Incident Ref, Threat Type, Systems Affected, Severity, Status, Reported By,
Actual Start Time, Resolved At, Root Cause, Lessons Learned, Description

### Fraud Incidents
Incident Ref, Fraud Type, Amount Involved, Systems Affected, Status, Reported By,
Actual Start Time, Resolved At, Root Cause, Lessons Learned, Description

## Scope

### In Scope
- Export button on `incidents.php` (all three tabs: downtime, security, fraud)
- Date range filter inputs (start date / end date) wired into the export
- Server-side .xlsx generation using PhpSpreadsheet (already installed)
- Export honours active tab's current filters (status, search, date range)
- Styled header row + alternating row shading (consistent with existing export style)

### Out of Scope
- PDF export (already exists separately)
- Scheduled or emailed exports
- CSV format
- Charts or pivot tables embedded in the Excel file
- Export from any page other than incidents.php

## Technical Constraints
- PHP 8.2 / MySQL / XAMPP on Windows
- PhpSpreadsheet ^1.29 already installed via Composer (`vendor/phpoffice/phpspreadsheet`)
- Alpine.js + Tailwind CSS on the frontend
- Follow existing export pattern from `public/exports/export_sla_report.php`
- No new Composer or npm packages required
- Auth guard required: `requireLogin()` must run before any data access

## Technology Stack
- Frontend: Alpine.js, Tailwind CSS, vanilla HTML forms
- Backend: PHP 8.2 with PDO/MySQL
- Excel generation: PhpSpreadsheet (server-side)
- No API layer — direct PHP page with GET parameters

## Dependencies
- Depends on `incidents.php` existing filter logic (status, search, date_from, date_to, tab)
- Uses same DB queries as incidents.php but without LIMIT/OFFSET (full export)
- No impact on other features; purely additive
- New file: `public/exports/export_incidents.php`
- UI change: add Export button + ensure date range inputs exist on `incidents.php`

## Configuration
- Stack: PHP/MySQL/Alpine/Tailwind
- API Style: REST (GET request with query params)
- Complexity: Medium
