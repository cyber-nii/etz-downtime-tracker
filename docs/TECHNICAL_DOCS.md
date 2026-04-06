# Technical Reference — eTranzact Downtime System

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Request Lifecycle](#request-lifecycle)
- [Authentication Deep-Dive](#authentication-deep-dive)
- [Session Variables Reference](#session-variables-reference)
- [PHP Function Index](#php-function-index)
- [Database Schema](#database-schema)
- [API Endpoint Reference](#api-endpoint-reference)
- [Incident Types Reference](#incident-types-reference)
- [Form Processing Patterns](#form-processing-patterns)
- [SLA Calculation](#sla-calculation)
- [Activity Logging](#activity-logging)
- [Admin Modules](#admin-modules)
- [Frontend Patterns](#frontend-patterns)
- [PDF and Excel Export](#pdf-and-excel-export)
- [Security Model](#security-model)
- [Debugging Guide](#debugging-guide)
- [Adding New Features](#adding-new-features)

---

## Architecture Overview

### Technology stack

```
┌──────────────────────────────────────────────────┐
│                  Browser                         │
│  Alpine.js v3  ·  Tailwind CSS v3  ·  Chart.js  │
└────────────────────────┬─────────────────────────┘
                         │ HTTP
┌────────────────────────▼─────────────────────────┐
│              PHP 8.2 (Apache / built-in)         │
│  config/config.php     — DB + app constants      │
│  src/includes/auth.php — session + XPortal auth  │
│  src/includes/activity_logger.php — audit trail  │
│  public/*.php          — page controllers        │
│  public/api/*.php      — JSON endpoints          │
└────────────────────────┬─────────────────────────┘
                         │ PDO (utf8mb4)
┌────────────────────────▼─────────────────────────┐
│              MySQL / MariaDB (downtimedb)         │
│  InnoDB  ·  20 tables  ·  FK constraints         │
│  2 triggers for downtime auto-calculation        │
└──────────────────────────────────────────────────┘
                         │ cURL (login only)
┌────────────────────────▼─────────────────────────┐
│          XPortal Authentication API              │
│  POST https://webpay.etranzactgh.com/XPortal/... │
│  RSA-encrypted payload, JSON response            │
└──────────────────────────────────────────────────┘
```

### Design patterns

- **Page-level controllers** — each PHP file in `public/` handles its own GET/POST logic, then renders HTML. No framework routing.
- **Auth guard** — every protected page calls `requireLogin()` (and `requireRole('admin')` for admin pages) at the top, before any output.
- **Shared components** — navigation, loading overlay, and PDF config are included via `src/includes/`.
- **Three incident domains** — downtime, security, and fraud each have their own tables (`incidents`, `security_incidents`, `fraud_incidents`) with parallel structure (ref, status, updates, attachments).
- **Template system** — `incident_templates` table + two API endpoints enable pre-filled report forms.
- **Global component pool** — components (`components` table) are shared across services via `service_component_map`. Disabling a component (`is_active = 0`) hides it without deleting.

---

## Request Lifecycle

```
Browser GET /etz-downtime-tracker/public/incidents.php
    │
    ▼
Apache routes to public/incidents.php
    │
    ├─ require_once config/config.php
    │       Sets DB constants, creates $pdo (PDO), sets timezone,
    │       configures sessions, sends security headers
    │
    ├─ require_once src/includes/auth.php
    │       Defines all auth functions (does not call them)
    │
    ├─ requireLogin()
    │       Calls isLoggedIn() → checks $_SESSION['user_id'] + timeout
    │       If not logged in: redirect to login.php
    │       If logged in but changed_password==0 and role!=admin:
    │           redirect to change_password.php
    │
    ├─ Business logic (queries, form handling)
    │
    └─ HTML output (inline PHP templates with Alpine.js components)
```

**Key: `$pdo` is a global PDO instance created by `config.php`.** Every page that includes `config.php` gets `$pdo` automatically. Auth functions use `global $pdo`.

---

## Authentication Deep-Dive

### login()

**File:** `src/includes/auth.php:22`

```php
function login(string $usernameOrEmail, string $password): array|false
```

1. Calls `callExternalAuthApi($usernameOrEmail, $password)`
2. Checks `$apiResponse['userExists']` (the `EXTERNAL_AUTH_RES_SUCCESS` constant)
3. On failure: calls `logActivity(null, 'login_failed', ...)`, returns `false`
4. On success: calls `findOrProvisionUser($apiUsername, $apiUserData)`
5. Sets `$_SESSION` variables (see [Session Variables Reference](#session-variables-reference))
6. Calls `session_regenerate_id(true)` — prevents session fixation
7. Calls `updateLastLogin()` and `logLogin()`
8. Returns the user row array

### callExternalAuthApi()

**File:** `src/includes/auth.php:72`

```php
function callExternalAuthApi(string $username, string $password): array|null
```

1. Builds JSON payload: `{"username": "...", "password": "..."}`
2. RSA-encrypts with `openssl_public_encrypt($credentials, $encrypted, EXTERNAL_AUTH_PUBLIC_KEY, OPENSSL_PKCS1_PADDING)`
3. Base64-encodes the ciphertext, wraps it: `{"payload": "<base64>"}`
4. POSTs to `EXTERNAL_AUTH_API_URL` with `Content-Type: application/json`
5. cURL timeout: `EXTERNAL_AUTH_API_TIMEOUT` (10 seconds)
6. SSL verification: **enabled in production, disabled in development** (`CURLOPT_SSL_VERIFYPEER => (APP_ENV !== 'development')`)
7. Returns decoded JSON array, or `null` on network error / non-200 / invalid JSON

**Expected API response shape:**

```json
{
  "userExists": true,
  "userData": {
    "username": "jdoe",
    "email": "jdoe@etranzact.com",
    "firstname": "John",
    "lastname": "Doe",
    "admin": "NONE"
  }
}
```

`admin` field: `"NONE"` → local role `user`; any other value → local role `admin`.

### findOrProvisionUser()

**File:** `src/includes/auth.php:128`

```php
function findOrProvisionUser(string $apiUsername, array $apiUserData): array|false
```

1. Queries `users` WHERE `username = ? OR email = ?` AND `is_active = 1`
2. If found: syncs `full_name` and `email` from API data if they differ, returns the row
3. If not found: INSERTs a new row with `changed_password = 1` (admin) or `0` (user), empty `password_hash`, then returns the new row

**Note:** The INSERT sets `changed_password = 1` for admins (no forced password change) and `0` for regular users (forces change on next page load).

### requireLogin()

**File:** `src/includes/auth.php:231`

Calls `isLoggedIn()`. If false → stores `$_SERVER['REQUEST_URI']` in `$_SESSION['redirect_after_login']` and redirects to `login.php`.

If logged in but `$_SESSION['changed_password'] == 0` and `$_SESSION['role'] !== 'admin'` → redirects to `change_password.php` (except when already on `change_password.php` or `logout.php`).

### requireRole()

**File:** `src/includes/auth.php:276`

```php
function requireRole(string $role): void
```

Calls `requireLogin()` first, then checks `hasRole($role)`. If role does not match: sends HTTP 403 and `die()`. All `admin/` pages call `requireRole('admin')`.

---

## Session Variables Reference

Set by `login()` in `src/includes/auth.php`:

| Key | Type | Description |
|-----|------|-------------|
| `$_SESSION['user_id']` | int | Local `users.user_id` |
| `$_SESSION['username']` | string | Username (from XPortal, synced to local DB) |
| `$_SESSION['full_name']` | string | Display name (firstname + lastname from XPortal) |
| `$_SESSION['role']` | string | `'admin'` or `'user'` |
| `$_SESSION['changed_password']` | int | `0` = must change password, `1` = ok |
| `$_SESSION['login_time']` | int | Unix timestamp — updated on each `isLoggedIn()` call; used for idle timeout |
| `$_SESSION['redirect_after_login']` | string | Set before redirect to login; consumed after successful login |

`isLoggedIn()` refreshes `login_time` on every call, implementing a sliding-window idle timeout.

---

## PHP Function Index

### src/includes/auth.php

| Function | Signature | Returns | Description |
|----------|-----------|---------|-------------|
| `login` | `(string $user, string $pass)` | `array\|false` | Full login flow: calls XPortal API, provisions user, sets session |
| `callExternalAuthApi` | `(string $user, string $pass)` | `array\|null` | RSA-encrypts and POSTs to XPortal; returns decoded response |
| `findOrProvisionUser` | `(string $apiUsername, array $apiData)` | `array\|false` | Looks up or creates local user from API data |
| `logout` | `()` | `void` | Logs the action, clears session, destroys cookie |
| `isLoggedIn` | `()` | `bool` | Checks session vars and idle timeout |
| `requireLogin` | `(?string $redirectTo)` | `void` | Redirects to login if not authenticated; enforces password change |
| `hasRole` | `(string $role)` | `bool` | Returns true if logged in and `$_SESSION['role'] === $role` |
| `requireRole` | `(string $role)` | `void` | Sends 403 and dies if role does not match |
| `getCurrentUser` | `()` | `array\|null` | Returns `[user_id, username, full_name, role]` from session |
| `url` | `(string $path)` | `string` | Generates root-relative URL; adds `?v=<filemtime>` for static assets |
| `updateLastLogin` | `(int $userId)` | `void` | Sets `users.last_login = NOW()` |
| `getLoginUrl` | `()` | `string` | Returns `url('login.php')` |
| `validatePassword` | `(string $password)` | `array` | Returns `['valid' => bool, 'errors' => string[]]`; enforces strength rules |

### src/includes/activity_logger.php

| Function | Signature | Returns | Description |
|----------|-----------|---------|-------------|
| `logActivity` | `(?int $userId, string $action, string $desc)` | `void` | Base log writer; captures IP and user agent |
| `logLogin` | `(int $userId, bool $success)` | `void` | Logs `login` or `login_failed` |
| `logLogout` | `(int $userId)` | `void` | Logs `logout` |
| `logUserAction` | `(int $userId, string $action, int $targetId, array $changes)` | `void` | Logs user management actions |
| `logIncidentAction` | `(int $userId, string $action, int $incidentId, array $changes)` | `void` | Logs incident CRUD actions |
| `logExport` | `(int $userId, string $exportType, array $filters)` | `void` | Logs analytics/report export events |
| `getActivityLogs` | `(array $filters, int $limit, int $offset)` | `array` | Paginated log retrieval with optional filters |
| `getActivityLogsCount` | `(array $filters)` | `int` | Total count matching filters (for pagination) |
| `getUserActivitySummary` | `(int $userId, int $days)` | `array` | Stats for a single user over N days |
| `getRecentActivity` | `(int $limit)` | `array` | Latest N log entries across all users |
| `getActivityStats` | `(string $startDate, string $endDate)` | `array` | Top actions and top users in a date range |
| `cleanupOldLogs` | `(int $daysToKeep)` | `int` | Deletes logs older than N days; returns count deleted |
| `exportActivityLogsCSV` | `(array $filters)` | `void` | Streams CSV download of filtered logs |

### Logged action codes (reference)

| Code | Triggered by |
|------|-------------|
| `login` | Successful login |
| `login_failed` | Failed login attempt |
| `logout` | User logout |
| `user_created` | Admin creates a user |
| `user_updated` | Admin edits a user |
| `user_deleted` | Admin deletes a user |
| `user_role_changed` | Admin changes a user's role |
| `incident_created` | New downtime incident submitted |
| `incident_updated` | Incident updated or resolved |
| `incident_deleted` | Admin deletes an incident |
| `incident_viewed` | Incident detail viewed |
| `reopen_security_incident` | Security incident set back to pending |
| `reopen_fraud_incident` | Fraud incident set back to pending |
| `analytics_exported` | Analytics PDF downloaded |
| `sla_report_exported` | SLA report Excel/PDF downloaded |
| `incident_exported` | Individual incident exported |
| `used_template` | Template applied to a report form |
| `created_service` | Admin creates a service |
| `updated_service` | Admin edits a service |
| `deleted_service` | Admin deletes a service |
| `created_company` / `updated_company` / `deleted_company` | Company CRUD |
| `created_component` / `updated_component` / `deleted_component` | Component CRUD |

---

## Database Schema

Database name: `downtimedb`. Engine: InnoDB. Charset: utf8mb4.

### users

| Column | Type | Notes |
|--------|------|-------|
| `user_id` | INT AUTO_INCREMENT PK | |
| `username` | VARCHAR(50) UNIQUE | Synced from XPortal on login |
| `email` | VARCHAR(100) UNIQUE | Synced from XPortal on login |
| `password_hash` | VARCHAR(255) | Empty string for API-provisioned users; populated only after `change_password.php` |
| `full_name` | VARCHAR(100) | |
| `phone` | VARCHAR(20) | Set via `change_password.php` (required for non-admins) |
| `location` | VARCHAR(100) | Optional |
| `role` | ENUM('admin','user') | Derived from XPortal `admin` field on first provision |
| `is_active` | TINYINT(1) | 0 = disabled (cannot log in) |
| `last_login` | DATETIME | Updated on every successful login |
| `changed_password` | TINYINT(1) | 0 = must change; 1 = changed |
| `created_at` / `updated_at` | DATETIME | |

### companies

| Column | Type | Notes |
|--------|------|-------|
| `company_id` | INT AUTO_INCREMENT PK | |
| `company_name` | VARCHAR(100) | |
| `category` | VARCHAR(50) | Optional grouping label |
| `created_at` / `updated_at` | DATETIME | |

### services

| Column | Type | Notes |
|--------|------|-------|
| `service_id` | INT AUTO_INCREMENT PK | |
| `service_name` | VARCHAR(100) | |
| `created_at` / `updated_at` | DATETIME | |

### components

| Column | Type | Notes |
|--------|------|-------|
| `component_id` | INT AUTO_INCREMENT PK | |
| `name` | VARCHAR(100) UNIQUE | |
| `is_active` | TINYINT(1) | Soft-delete: 0 = hidden from forms |

### service_component_map

Junction table: services ↔ components (many-to-many).

| Column | Type |
|--------|------|
| `service_id` | INT FK → services |
| `component_id` | INT FK → components |
| PK | composite (service_id, component_id) |

### incident_types

| Column | Type | Notes |
|--------|------|-------|
| `type_id` | INT AUTO_INCREMENT PK | |
| `service_id` | INT FK → services | Nullable |
| `name` | VARCHAR(100) | |
| `is_active` | TINYINT(1) | |

### incident_type_service_map

Junction table: incident_types ↔ services.

| Column | Type |
|--------|------|
| `type_id` | INT FK → incident_types |
| `service_id` | INT FK → services |

### incidents (downtime)

| Column | Type | Notes |
|--------|------|-------|
| `incident_id` | INT AUTO_INCREMENT PK | |
| `incident_ref` | VARCHAR(30) UNIQUE | Format: `INC-YYYYMMDD-NNN` |
| `service_id` | INT FK → services | |
| `component_id` | INT FK → components | Nullable (legacy single-component) |
| `incident_type_id` | INT FK → incident_types | Nullable |
| `description` | TEXT | |
| `impact_level` | ENUM('Low','Medium','High','Critical') | |
| `priority` | ENUM('Low','Medium','High','Urgent') | |
| `incident_source` | ENUM('internal','external') | |
| `root_cause` | TEXT | |
| `root_cause_file` | VARCHAR(255) | File path |
| `lessons_learned` | TEXT | |
| `lessons_learned_file` | VARCHAR(255) | File path |
| `attachment_path` | VARCHAR(255) | Legacy single attachment; see `incident_attachments` |
| `actual_start_time` | DATETIME | When the incident started |
| `status` | ENUM('pending','resolved') | |
| `reported_by` | INT FK → users | |
| `resolved_by` | INT FK → users | Nullable |
| `resolved_at` | DATETIME | Nullable |
| `resolvers` | JSON | Array of resolver name strings |
| `created_at` / `updated_at` | DATETIME | |

### incident_affected_companies

| Column | Type |
|--------|------|
| `incident_id` | INT FK → incidents |
| `company_id` | INT FK → companies |
| PK | composite |

### incident_components

Junction table for multi-component incidents (newer style, preferred over `incidents.component_id`).

| Column | Type |
|--------|------|
| `incident_id` | INT FK → incidents |
| `component_id` | INT FK → components |
| PK | composite |

### incident_updates

| Column | Type | Notes |
|--------|------|-------|
| `update_id` | INT AUTO_INCREMENT PK | |
| `incident_id` | INT FK → incidents | |
| `user_id` | INT FK → users | Nullable |
| `user_name` | VARCHAR(100) | Preserved at time of update |
| `update_text` | TEXT | |
| `created_at` | DATETIME | |

### incident_attachments

| Column | Type | Notes |
|--------|------|-------|
| `attachment_id` | INT AUTO_INCREMENT PK | |
| `incident_id` | INT FK → incidents | |
| `file_path` | VARCHAR(255) | Relative path under `public/uploads/incidents/` |
| `file_name` | VARCHAR(255) | Original filename |
| `file_type` | VARCHAR(100) | MIME type |
| `file_size` | INT | Bytes |
| `uploaded_at` | DATETIME | |

### incident_templates

| Column | Type | Notes |
|--------|------|-------|
| `template_id` | INT AUTO_INCREMENT PK | |
| `template_name` | VARCHAR(100) UNIQUE | |
| `service_id` | INT FK → services | Nullable = global template |
| `component_id` | INT FK → components | Nullable |
| `incident_type_id` | INT FK → incident_types | Nullable |
| `impact_level` | VARCHAR(20) | |
| `description` | TEXT | |
| `root_cause` | TEXT | |
| `is_active` | TINYINT(1) | |
| `usage_count` | INT | Auto-incremented by `use_template.php` |
| `created_by` | INT FK → users | |
| `created_at` / `updated_at` | DATETIME | |

### downtime_incidents

Stores granular downtime windows for SLA calculation.

| Column | Type | Notes |
|--------|------|-------|
| `downtime_id` | INT AUTO_INCREMENT PK | |
| `incident_id` | INT FK → incidents | |
| `actual_start_time` | DATETIME | |
| `actual_end_time` | DATETIME | Nullable |
| `downtime_minutes` | INT | **Auto-calculated by trigger** |
| `is_planned` | TINYINT(1) | 1 = planned maintenance |
| `downtime_category` | ENUM('Network','Server','Maintenance','Third-party','Other') | |
| `created_at` / `updated_at` | DATETIME | |

**Triggers on this table:**
- `calculate_downtime_minutes` (BEFORE UPDATE) — sets `downtime_minutes = TIMESTAMPDIFF(MINUTE, actual_start_time, actual_end_time)`
- `calculate_downtime_minutes_insert` (BEFORE INSERT) — same logic on insert

### security_incidents

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PK | |
| `incident_ref` | VARCHAR(30) UNIQUE | Format: `SEC-IN#YYYYMMDDNNN` |
| `threat_type` | ENUM('phishing','unauthorized_access','data_breach','malware','social_engineering','other') | |
| `systems_affected` | TEXT | |
| `description` | TEXT | |
| `impact_level` | ENUM('Low','Medium','High','Critical') | |
| `priority` | ENUM('Low','Medium','High','Urgent') | |
| `containment_status` | ENUM('contained','ongoing','under_investigation') | |
| `escalated_to` | VARCHAR(100) | Whitelist: CISO, IT Security Team, Regulatory Body, Law Enforcement |
| `root_cause` / `lessons_learned` | TEXT | |
| `attachment_path` | VARCHAR(255) | |
| `actual_start_time` | DATETIME | |
| `status` | ENUM('pending','resolved') | |
| `reported_by` / `resolved_by` | INT FK → users | |
| `resolved_at` | DATETIME | |
| `resolvers` | JSON | |
| `created_at` / `updated_at` | DATETIME | |

Plus `security_incident_updates` and `security_incident_attachments` with the same structure as their downtime equivalents.

### fraud_incidents

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PK | |
| `incident_ref` | VARCHAR(30) UNIQUE | Format: `FRD-IN#YYYYMMDDNNN` |
| `fraud_type` | ENUM('card_fraud','account_takeover','transaction_fraud','internal_fraud','other') | |
| `service_id` | INT FK → services | |
| `description` | TEXT | |
| `financial_impact` | DECIMAL(15,2) | Monetary loss amount |
| `impact_level` / `priority` | ENUM | |
| `regulatory_reported` | TINYINT(1) | Was this reported to a regulator? |
| `regulatory_details` | TEXT | |
| `root_cause` / `lessons_learned` | TEXT | |
| `attachment_path` | VARCHAR(255) | |
| `actual_start_time` | DATETIME | |
| `status` / `reported_by` / `resolved_by` / `resolved_at` / `resolvers` | — | Same as security_incidents |

Plus `fraud_incident_updates` and `fraud_incident_attachments`.

### sla_targets

| Column | Type | Notes |
|--------|------|-------|
| `target_id` | INT AUTO_INCREMENT PK | |
| `company_id` | INT FK → companies | Nullable = global target |
| `service_id` | INT FK → services | Nullable = all services |
| `target_uptime_percentage` | DECIMAL(5,2) | Default 99.99 |
| `business_hours_start` | TIME | Default 09:00:00 |
| `business_hours_end` | TIME | Default 17:00:00 |
| `business_days` | SET('Mon','Tue','Wed','Thu','Fri','Sat','Sun') | |
| `created_at` / `updated_at` | DATETIME | |

### activity_logs

| Column | Type | Notes |
|--------|------|-------|
| `log_id` | INT AUTO_INCREMENT PK | |
| `user_id` | INT FK → users | Nullable (anonymous failed logins) |
| `action` | VARCHAR(100) | Short action code |
| `description` | TEXT | Human-readable detail |
| `ip_address` | VARCHAR(45) | IPv4 or IPv6 |
| `user_agent` | TEXT | Browser string |
| `created_at` | DATETIME | |

### incident_company_history

Tracks changes to the `incident_affected_companies` junction (who was added/removed and when).

---

## API Endpoint Reference

All endpoints require an active session (`requireLogin()` is called). There is no token-based API — authentication is session-cookie-based.

---

### GET /get_incident.php

Fetch a single downtime incident with related companies, components, and optionally attachments.

**Auth:** Session required

**Query parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | int | Yes | `incidents.incident_id` |
| `include_attachments` | `1` | No | If `1`, includes attachment array |

**Success response (200):**

```json
{
  "incident_id": 42,
  "incident_ref": "INC-20260401-001",
  "service_id": "3",
  "component_id": "7",
  "incident_type_id": "2",
  "description": "Internet connectivity lost across all branches",
  "impact_level": "High",
  "priority": "Urgent",
  "incident_source": "external",
  "actual_start_time": "2026-04-01 09:15:00",
  "attachment_path": null,
  "service_name": "Internet",
  "affected_companies": [1, 4, 7],
  "component_ids": [7, 12],
  "attachments": [
    {
      "attachment_id": "5",
      "file_path": "uploads/incidents/1743500000_report.pdf",
      "file_name": "report.pdf",
      "file_type": "application/pdf",
      "file_size": "204800",
      "uploaded_at": "2026-04-01 09:20:00"
    }
  ]
}
```

**Notes:**
- `attachments` key is only present when `include_attachments=1`
- Legacy `attachment_path` from the `incidents` table is merged into the `attachments` array if not already in `incident_attachments`
- `affected_companies` and `component_ids` are arrays of integers

**Error responses:**

```json
{ "error": "Incident ID is required" }   // missing ?id=
{ "error": "Incident not found" }         // no row matches
{ "error": "Database error: ..." }        // PDO exception (development only shows detail)
```

---

### GET /api/get_templates.php

Fetch active incident templates, optionally filtered by service.

**Auth:** Session required

**Query parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `service_id` | int | No | Filter to templates for this service (also returns global templates where `service_id IS NULL`) |

**Success response (200):**

```json
{
  "success": true,
  "templates": [
    {
      "template_id": "3",
      "template_name": "Internet Downtime",
      "service_id": "2",
      "component_id": "5",
      "incident_type_id": "1",
      "impact_level": "High",
      "description": "Internet service is unavailable...",
      "root_cause": "ISP outage"
    }
  ]
}
```

Templates are ordered by `usage_count DESC, template_name ASC`.

**Error response (500):**

```json
{ "success": false, "error": "Database error: ..." }
```

---

### POST /api/use_template.php

Record that a template was applied (increments `usage_count`).

**Auth:** Session required

**Request body:** `application/x-www-form-urlencoded`

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `template_id` | int | Yes | `incident_templates.template_id` |

**Success response (200):**

```json
{ "success": true }
```

**Error responses:**

```json
{ "success": false, "error": "Template ID is required" }
{ "success": false, "error": "Template not found" }
{ "success": false, "error": "Database error" }
```

Also logs `used_template` to activity_logs.

---

## Incident Types Reference

### Downtime incidents

| Field | Values |
|-------|--------|
| `incident_ref` format | `INC-YYYYMMDD-NNN` (zero-padded sequential) |
| `status` | `pending`, `resolved` |
| `impact_level` | `Low`, `Medium`, `High`, `Critical` |
| `priority` | `Low`, `Medium`, `High`, `Urgent` |
| `incident_source` | `internal`, `external` |
| Main table | `incidents` |
| Updates table | `incident_updates` |
| Attachments table | `incident_attachments` |
| Companies | `incident_affected_companies` (many-to-many) |
| Components | `incident_components` (many-to-many) + legacy `incidents.component_id` |
| Timing | `downtime_incidents` (triggers auto-calculate `downtime_minutes`) |

### Security incidents

| Field | Values |
|-------|--------|
| `incident_ref` format | `SEC-IN#YYYYMMDDNNN` (3-digit random suffix) |
| `threat_type` | `phishing`, `unauthorized_access`, `data_breach`, `malware`, `social_engineering`, `other` |
| `containment_status` | `contained`, `ongoing`, `under_investigation` |
| `escalated_to` | One of: `CISO`, `IT Security Team`, `Regulatory Body`, `Law Enforcement` (whitelist-validated) |
| Main table | `security_incidents` |
| Updates / Attachments | `security_incident_updates`, `security_incident_attachments` |

### Fraud incidents

| Field | Values |
|-------|--------|
| `incident_ref` format | `FRD-IN#YYYYMMDDNNN` (3-digit random suffix) |
| `fraud_type` | `card_fraud`, `account_takeover`, `transaction_fraud`, `internal_fraud`, `other` |
| `financial_impact` | Decimal — monetary value of the fraud |
| `regulatory_reported` | Boolean — was this reported to a regulator? |
| Main table | `fraud_incidents` |
| Updates / Attachments | `fraud_incident_updates`, `fraud_incident_attachments` |

---

## Form Processing Patterns

### CSRF protection

Every state-changing form generates and validates a CSRF token:

```php
// Generate (at top of page, before output)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// In HTML form
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

// Validate on POST
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    die('CSRF validation failed');
}
```

### Rate limiting

Applied in `report.php`, `incidents.php` (and similar) — 5 requests per minute per session:

```php
$now = time();
$_SESSION['request_times'] = array_filter(
    $_SESSION['request_times'] ?? [],
    fn($t) => $now - $t < 60
);
if (count($_SESSION['request_times']) >= 5) {
    // show error, exit
}
$_SESSION['request_times'][] = $now;
```

### File upload validation

```php
$allowedExtensions = ['jpg', 'jpeg', 'gif', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
$maxSize = 10 * 1024 * 1024; // 10 MB

$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExtensions)) { /* reject */ }
if ($_FILES['file']['size'] > $maxSize) { /* reject */ }

$filename = time() . '_' . basename($_FILES['file']['name']);
$uploadPath = __DIR__ . '/uploads/incidents/' . $filename;
move_uploaded_file($_FILES['file']['tmp_name'], $uploadPath);
```

### Output escaping

All user-sourced data in HTML is escaped:

```php
echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
```

### PDO prepared statements

All queries use named or positional placeholders — never string interpolation with user input:

```php
$stmt = $pdo->prepare("SELECT * FROM incidents WHERE service_id = ? AND status = ?");
$stmt->execute([$serviceId, $status]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

PDO is configured with `ATTR_EMULATE_PREPARES => false` (server-side preparation).

### Transactions (multi-insert operations)

Used in `report_security.php` and `report_fraud.php`:

```php
$pdo->beginTransaction();
try {
    $pdo->prepare("INSERT INTO security_incidents (...) VALUES (...)")->execute([...]);
    $incidentId = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO security_incident_attachments (...) VALUES (...)")->execute([...]);
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    // handle error
}
```

---

## SLA Calculation

**File:** `public/sla_report.php`

**Function:** `calculateSlaData($pdo, $companyId, $startDate, $endDate, $totalMinutes, $slaTarget, $sourceFilter)`

**Formula:**

```
uptimeMinutes    = totalMinutes − totalDowntimeMinutes
uptimePercentage = (uptimeMinutes / totalMinutes) × 100
slaCompliant     = uptimePercentage >= slaTarget
```

**`totalMinutes`** is computed from the date range:

```php
$totalMinutes = (strtotime($endDate) - strtotime($startDate)) / 60;
```

**Downtime query** (simplified):

```sql
SELECT SUM(di.downtime_minutes) AS total_downtime
FROM downtime_incidents di
JOIN incidents i ON di.incident_id = i.incident_id
JOIN incident_affected_companies iac ON i.incident_id = iac.incident_id
WHERE iac.company_id = :company_id
  AND i.actual_start_time BETWEEN :start AND :end
  AND i.status = 'resolved'
  -- optional: AND i.incident_source = :source
```

`downtime_minutes` on `downtime_incidents` is auto-calculated by the MySQL `BEFORE INSERT` and `BEFORE UPDATE` triggers as `TIMESTAMPDIFF(MINUTE, actual_start_time, actual_end_time)`.

**SLA target** defaults to `99.99` (from `sla_targets` table or the constant).

---

## Activity Logging

**File:** `src/includes/activity_logger.php`

### Adding a log entry

```php
// Available anywhere config.php is included
logActivity($_SESSION['user_id'], 'action_code', 'Human-readable description');

// Specialised wrappers
logIncidentAction($_SESSION['user_id'], 'incident_updated', $incidentId, ['status' => 'resolved']);
logExport($_SESSION['user_id'], 'sla_report_pdf', ['company_id' => 3, 'date_range' => '...']);
```

### Adding a new action type

1. Pick a snake_case action code (e.g., `kb_article_viewed`)
2. Call `logActivity(...)` with that code wherever the event occurs
3. Add it to the action dropdown filter in `admin/activity_logs.php` so admins can filter by it

No schema changes required — the `action` column is `VARCHAR(100)`.

### Retention and cleanup

`cleanupOldLogs(ACTIVITY_LOG_RETENTION_DAYS)` deletes rows older than the configured number of days. It is called automatically when `ACTIVITY_LOG_CLEANUP_ENABLED` is true (called from within the logger itself on a random sampling basis, not on every request).

---

## Admin Modules

### manage.php + admin_manage_*.php

`public/admin/manage.php` handles all POST actions for system configuration. After determining the `$action` from `$_POST['action']`, it delegates to one of the included modules:

| Include file | Handles actions |
|-------------|-----------------|
| `admin_manage_services.php` | `create_service`, `update_service`, `delete_service` |
| `admin_manage_companies.php` | `create_company`, `update_company`, `delete_company` |
| `admin_manage_components.php` | `create_component`, `update_component`, `delete_component` (soft-disable) |
| `admin_manage_incident_types.php` | `create_incident_type`, `update_incident_type`, `delete_incident_type` |

All actions:
- Validate for duplicates before INSERT / UPDATE
- Call `logActivity()` on success
- Return a `$message` variable (success or error string) consumed by the HTML template

### users.php bulk actions

- `bulk_delete` — deletes selected users; guards against deleting own account
- `toggle_status` — flips `is_active` on a single user
- `reset_password` — sets `password_hash` = bcrypt of `Etz@1234566`, sets `changed_password = 0`

---

## Frontend Patterns

### Alpine.js conventions

All interactivity uses inline Alpine.js — no separate `.js` files. Each interactive section is a component:

```html
<div x-data="{ open: false, tab: 'downtime' }">
  <button @click="open = !open">Toggle</button>
  <div x-show="open">...</div>
</div>
```

Modal pattern (used for incident details, updates):

```html
<div x-data="{ show: false, incident: {} }">
  <button @click="fetch('/get_incident.php?id=<?= $id ?>').then(r=>r.json()).then(d=>{ incident=d; show=true })">
    View
  </button>
  <div x-show="show" class="fixed inset-0 ...">
    <span x-text="incident.description"></span>
  </div>
</div>
```

### Status badge classes (Tailwind)

```
pending  → bg-yellow-100 text-yellow-800
resolved → bg-green-100  text-green-800
```

Impact level badge classes:

```
Low      → bg-blue-100   text-blue-800
Medium   → bg-yellow-100 text-yellow-800
High     → bg-orange-100 text-orange-800
Critical → bg-red-100    text-red-800
```

### Dark mode

Toggled via a class on `<html>` using Alpine.js + `localStorage`:

```html
<html x-data x-bind:class="$store.darkMode.on ? 'dark' : ''">
```

Tailwind `dark:` variants are used throughout for dark mode styles.

### Loading overlay

Included via `<?php include __DIR__ . '/../src/includes/loading.php'; ?>`. Activates automatically on form submit and navigation. See [LOADING_GUIDE.md](LOADING_GUIDE.md) for usage details.

### url() helper

Always use `url()` for internal links to ensure correct paths in both XAMPP subdirectory and router modes:

```php
href="<?= url('incidents.php') ?>"
href="<?= url('admin/users.php') ?>"
```

For static assets, `url()` appends `?v=<filemtime>` for cache-busting automatically.

---

## PDF and Excel Export

### PDF (TCPDF)

**Config:** `src/includes/pdf_config.php`

Initialises a `TCPDF` instance with eTranzact branding, margins, and fonts. Usage:

```php
require_once __DIR__ . '/../src/includes/pdf_config.php';
// $pdf is now a configured TCPDF object
$pdf->AddPage();
$pdf->writeHTML($htmlContent);
$pdf->Output('report.pdf', 'D'); // D = download
```

**Used in:** `src/exports/export_analytics_pdf.php`, `src/exports/export_sla_report_pdf.php`

### Excel (PhpSpreadsheet)

Used in `public/sla_report.php`:

```php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Service');
// ... populate cells ...

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="sla_report.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
```

---

## Security Model

| Control | Implementation |
|---------|---------------|
| Authentication | External XPortal API + session; no local password check at login |
| Session fixation | `session_regenerate_id(true)` on login |
| Session timeout | Sliding-window idle timeout (default 1 hour) via `$_SESSION['login_time']` |
| CSRF | `bin2hex(random_bytes(32))` token; `hash_equals()` on validation |
| SQL injection | PDO prepared statements everywhere; `ATTR_EMULATE_PREPARES = false` |
| XSS | `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')` on all output |
| Role enforcement | `requireRole('admin')` on every admin page, before any output |
| File uploads | Extension whitelist + size limit; stored with timestamp-prefixed names |
| Rate limiting | Session-based counter (5 req/min) on report forms |
| Security headers | `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection` sent by `config.php` |
| Cookie security | `httponly`, `SameSite=Strict`; set `secure=1` when on HTTPS |
| RSA encryption | `OPENSSL_PKCS1_PADDING` with `openssl_public_encrypt()` for XPortal payload |
| Audit trail | All significant actions logged with IP + user agent; 365-day retention |
| Error exposure | Errors shown in development; suppressed and logged to file in production |

**Enum validation for escalated_to (security incidents):**

```php
$allowedEscalations = ['CISO', 'IT Security Team', 'Regulatory Body', 'Law Enforcement'];
if (!in_array($_POST['escalated_to'], $allowedEscalations)) {
    $escalatedTo = null; // reject unknown values
}
```

---

## Debugging Guide

### Enable verbose errors

In `config/config.php`:

```php
define('APP_ENV', 'development');
```

This sets `error_reporting(E_ALL)` and `ini_set('display_errors', 1)`. Revert to `'production'` before deploying.

### Check the error log

```
# XAMPP Windows
C:\xampp\apache\logs\error.log

# Production Linux
/var/log/apache2/error.log   (or wherever configured in php.ini error_log)

# App-level production log
config/logs/error.log        (set in config.php when APP_ENV = production)
```

All `error_log()` calls in auth.php and activity_logger.php write here.

### Trace a login failure

1. Set `APP_ENV = development` to see errors in browser
2. Check `activity_logs` table: `SELECT * FROM activity_logs WHERE action = 'login_failed' ORDER BY created_at DESC LIMIT 10;`
3. Check Apache error log for lines starting with `External auth API` — these come from `callExternalAuthApi()`

Common causes:
- `External auth API curl error` → machine cannot reach `webpay.etranzactgh.com` (firewall / no internet)
- `External auth API returned HTTP 4xx/5xx` → XPortal is rejecting the request (wrong URL or key)
- `RSA encryption failed` → `openssl` extension not loaded, or `EXTERNAL_AUTH_PUBLIC_KEY` is malformed

### Trace an incident submission

1. Open `report.php` (or `report_security.php` / `report_fraud.php`) — all form handling is at the top of the file before the HTML
2. Add temporary `var_dump($_POST); die;` after the CSRF check to inspect submitted data
3. Wrap the INSERT block in try/catch and dump the PDO exception message
4. Check `activity_logs` for `incident_created` entries — presence confirms the INSERT succeeded

### Inspect session state

Add this temporarily to any page (after `requireLogin()`):

```php
echo '<pre>'; print_r($_SESSION); echo '</pre>'; die;
```

### Debug the downtime trigger

If `downtime_minutes` is not being calculated:

```sql
-- Check trigger exists
SHOW TRIGGERS FROM downtimedb WHERE `Table` = 'downtime_incidents';

-- Manually test calculation
SELECT
  TIMESTAMPDIFF(MINUTE, actual_start_time, actual_end_time) AS expected_minutes,
  downtime_minutes AS stored_minutes
FROM downtime_incidents
ORDER BY downtime_id DESC LIMIT 10;
```

If the trigger is missing, re-run the relevant section of `database/emptydb.sql`.

### Debug API endpoints

Use browser DevTools (Network tab) or curl:

```bash
# Must have a valid session cookie
curl -b "PHPSESSID=<your_session_id>" \
  "http://localhost/etz-downtime-tracker/public/get_incident.php?id=1&include_attachments=1"
```

Or add temporary logging in the endpoint:

```php
error_log("get_incident called with id=" . $_GET['id']);
```

### Common error messages

| Error / Symptom | Likely cause | Fix |
|-----------------|-------------|-----|
| Blank white page | PHP fatal error, errors hidden | Set `APP_ENV = development` |
| "Database Connection Failed" | Wrong credentials or MySQL not running | Check `config.php` and XAMPP |
| "Access denied. You do not have permission" | `requireRole('admin')` failed | User role is `user`, not `admin` |
| "CSRF validation failed" | Token expired (session expired mid-form) or form resubmit | Refresh the page and resubmit |
| Charts show nothing | CDN not reachable, or JS error | Check internet access; open DevTools console |
| PDF download triggers PHP error | TCPDF not installed or GD extension missing | Run `composer install`; enable `gd` in `php.ini` |
| `Allowed memory size exhausted` | Large export | Increase `memory_limit` in `php.ini` to `256M` |
| `openssl_public_encrypt(): key parameter is not a valid asymmetric key` | Malformed RSA key in config | Check `EXTERNAL_AUTH_PUBLIC_KEY` has correct PEM headers and no extra whitespace |

---

## Adding New Features

### Adding a new incident type (e.g., "Compliance")

1. **Database:** Add a record via Admin Panel → Manage → Incident Types, or:
   ```sql
   INSERT INTO incident_types (name, is_active) VALUES ('Compliance', 1);
   INSERT INTO incident_type_service_map (service_id, type_id)
   VALUES (<service_id>, LAST_INSERT_ID());
   ```
2. The `incident_types` table feeds the type dropdown in `report.php` automatically — no PHP changes needed if it follows the downtime incident model.

### Adding a new admin page

1. Create `public/admin/my_page.php`
2. At the top:
   ```php
   <?php
   require_once __DIR__ . '/../../config/config.php';
   require_once __DIR__ . '/../../src/includes/auth.php';
   requireRole('admin');
   ```
3. Include the admin navbar:
   ```php
   <?php include __DIR__ . '/../../src/includes/admin_navbar.php'; ?>
   ```
4. Add a link to it in `src/includes/admin_navbar.php`
5. Log significant actions with `logActivity()`

### Adding a new fourth incident type (e.g., "Operational")

This requires more work — the current three types (downtime, security, fraud) each have dedicated tables. To add a fourth:

1. Create tables: `operational_incidents`, `operational_incident_updates`, `operational_incident_attachments` (model after `security_incidents`)
2. Create `public/report_operational.php` (model after `report_security.php`)
3. Add a tab to `public/incidents.php`
4. Add a new card to `public/report_category.php`
5. Create or extend the admin deletion handler in `admin/delete_incidents.php`
6. Add new action codes to `src/includes/activity_logger.php`

### Adding a new logged action

```php
// In the relevant page, after the action completes:
logActivity($_SESSION['user_id'], 'my_new_action', "Description of what happened");
```

Then add `my_new_action` to the filter dropdown in `public/admin/activity_logs.php` so admins can filter by it.

### Adding a new configuration constant

1. Add `define('MY_CONSTANT', 'value');` to `config/config.php`
2. Add the same line (with a sensible default or `TODO` comment) to `config/config.php.example`
3. Document it in the [Configuration Reference section of README.md](README.md#configuration-reference)

---

**Last updated:** April 2026
**PHP target:** 8.2
