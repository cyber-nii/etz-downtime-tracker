# eTranzact Downtime Tracking System

## Table of Contents

- [Project Summary](#project-summary)
- [Tech Stack](#tech-stack)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration Reference](#configuration-reference)
- [Authentication Model](#authentication-model)
- [Running Locally](#running-locally)
- [Directory Structure](#directory-structure)
- [User Roles](#user-roles)
- [Credentials and First Login](#credentials-and-first-login)
- [Database Migrations](#database-migrations)
- [Composer Dependencies](#composer-dependencies)
- [File Uploads](#file-uploads)
- [Features Overview](#features-overview)
- [Developer Quick Reference](#developer-quick-reference)
- [Troubleshooting](#troubleshooting)
- [Additional Documentation](#additional-documentation)

---

## Project Summary

The **eTranzact Downtime Tracking System** is an internal web application for monitoring, reporting, and analysing service incidents across three domains:

- **Downtime** — service outages and performance degradations
- **Security** — phishing, unauthorised access, data breaches, malware
- **Fraud** — card fraud, account takeover, transaction fraud, internal fraud

It provides a unified incident management UI, SLA compliance reporting, analytics charts, a knowledge base (resolved incidents as searchable articles), an incident template library, and a full admin control panel with a comprehensive audit trail.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.2 (minimum 7.4) |
| Database | MySQL 5.7+ / MariaDB 10.3+ |
| Frontend reactivity | Alpine.js v3 |
| CSS framework | Tailwind CSS v3 (CDN) |
| Charts | Chart.js v4 (CDN) |
| Icons | Font Awesome 6 (CDN) |
| PDF generation | TCPDF (via Composer) |
| Excel export | PhpOffice/PhpSpreadsheet (via Composer) |
| Dev server routing | `router.php` (built-in PHP server) |

No build step is required. Tailwind and Alpine.js are loaded from CDN at runtime.

---

## Prerequisites

| Requirement | Minimum | Notes |
|-------------|---------|-------|
| Apache | 2.4+ | XAMPP bundle works out of the box |
| PHP | 7.4 (8.2 recommended) | Extensions: `pdo`, `pdo_mysql`, `mbstring`, `gd`, `openssl`, `curl` |
| MySQL / MariaDB | MySQL 5.7+ / MariaDB 10.3+ | InnoDB with triggers required |
| Composer | any recent | For TCPDF and PhpSpreadsheet |
| Network access | required at login | App calls the XPortal authentication API on every login |

> **OpenSSL and cURL are required** — the external auth API uses RSA encryption and HTTP requests.

---

## Installation

### Step 1 — Clone or extract the project

```bash
cd C:\xampp\htdocs
git clone <repository-url> etz-downtime-tracker
cd etz-downtime-tracker
```

Or extract the ZIP into `C:\xampp\htdocs\etz-downtime-tracker`.

### Step 2 — Install PHP dependencies

```bash
composer install
```

This installs TCPDF and PhpSpreadsheet into `vendor/`.

### Step 3 — Create the database

Open phpMyAdmin (`http://localhost/phpmyadmin`) or use the CLI:

```bash
# CLI (run from XAMPP mysql bin directory or if mysql is on PATH)
mysql -u root downtimedb < database/emptydb.sql
```

Or via phpMyAdmin:
1. Create a new database named `downtimedb`
2. Select it, go to the **Import** tab
3. Import `database/emptydb.sql`

### Step 4 — Configure the application

Copy the example config and edit it:

```bash
# Windows
copy config\config.php.example config\config.php
```

Edit `config/config.php` — see [Configuration Reference](#configuration-reference) for all options.

The minimum required change for a default XAMPP install is usually nothing — the defaults (root / empty password / downtimedb) match XAMPP out of the box.

### Step 5 — Ensure the uploads directory is writable

```
public/uploads/incidents/
```

On Windows with XAMPP this is typically writable by default. On Linux:

```bash
chmod 755 public/uploads/incidents/
chown www-data:www-data public/uploads/incidents/
```

### Step 6 — Start Apache and MySQL, then visit the app

```
http://localhost/etz-downtime-tracker/public/
```

You will be redirected to the login page. Log in with your eTranzact XPortal credentials.

---

## Configuration Reference

All configuration lives in `config/config.php`. **Do not commit this file** — it is gitignored.

### Database

```php
define('DB_HOST', 'localhost');      // MySQL host; use 'localhost:3307' if on non-default port
define('DB_USER', 'root');           // MySQL username
define('DB_PASS', '');               // MySQL password (empty by default in XAMPP)
define('DB_NAME', 'downtimedb');     // Database name
```

### Application environment

```php
define('APP_ENV', 'development');    // 'development' shows errors; 'production' suppresses them
define('SITE_NAME', 'eTranzact Downtime Management System');
```

> In `development` mode, PHP errors are printed to the browser. In `production` mode, errors are written to `config/logs/error.log` and a generic message is shown.

### Session

```php
define('SESSION_TIMEOUT', 3600);     // Idle timeout in seconds (default: 1 hour)
```

Cookie settings (set via `ini_set` directly in `config.php`):
- `session.cookie_httponly = 1` — prevents JS access to session cookie
- `session.use_only_cookies = 1` — no session ID in URL
- `session.cookie_secure = 0` — **set to 1 when running over HTTPS**
- `session.cookie_samesite = 'Strict'`

### Password validation

```php
define('PASSWORD_MIN_LENGTH', 8);    // Enforced by validatePassword()
```

Password rules (enforced in `src/includes/auth.php::validatePassword()`):
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one digit
- At least one special character

### External authentication API

```php
define('EXTERNAL_AUTH_API_URL',     'https://webpay.etranzactgh.com/XPortal/api/Authenticator');
define('EXTERNAL_AUTH_API_TIMEOUT', 10);  // cURL timeout in seconds
define('EXTERNAL_AUTH_PUBLIC_KEY',  "-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----");
```

These should not need changing unless the XPortal endpoint or RSA key is rotated. See [Authentication Model](#authentication-model) for how these are used.

### Activity log retention

```php
define('ACTIVITY_LOG_RETENTION_DAYS', 365);    // Auto-clean logs older than this
define('ACTIVITY_LOG_CLEANUP_ENABLED', true);  // Set false to disable auto-cleanup
```

### Security headers

Set directly in `config.php` via `header()` calls:

```
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
```

---

## Authentication Model

> This is the most important architectural detail to understand before touching auth-related code.

**Login is handled entirely by an external REST API (XPortal).** There are no locally stored passwords used for initial authentication. The flow is:

```
Browser                  PHP (auth.php)               XPortal API
  │                           │                             │
  │── POST username/pw ──────>│                             │
  │                           │── RSA encrypt payload ─────>│
  │                           │<── { userExists, userData } ─│
  │                           │                             │
  │                           │ (if userExists == true)     │
  │                           │── lookup/create local user ─┤
  │                           │── set $_SESSION vars ───────┤
  │<── redirect to dashboard ─│                             │
```

**Step by step:**

1. User submits credentials on `login.php`
2. `auth.php::login()` calls `callExternalAuthApi()`
3. Credentials are JSON-encoded, then RSA-encrypted with `EXTERNAL_AUTH_PUBLIC_KEY` using `openssl_public_encrypt()`
4. The encrypted payload (base64-encoded) is POSTed as `{"payload": "..."}` to `EXTERNAL_AUTH_API_URL`
5. XPortal responds with `{"userExists": true, "userData": {"username": ..., "email": ..., "firstname": ..., "lastname": ..., "admin": "NONE"|"<role>"}}`
6. If `userExists` is false or the call fails, login fails and the attempt is logged
7. `findOrProvisionUser()` looks up the user in the local `users` table by username or email
   - If found: syncs name and email from API data, returns the row
   - If not found: **auto-creates** the user with `changed_password = 1` (admin) or `0` (regular user)
   - Role mapping: `admin` field = `"NONE"` → local role `user`; anything else → local role `admin`
8. Session variables are set: `user_id`, `username`, `full_name`, `role`, `changed_password`, `login_time`
9. Session ID is regenerated (`session_regenerate_id(true)`)

**What this means for development:**
- You cannot log in without network access to `webpay.etranzactgh.com`
- You do not need to manually create users — they are provisioned on first login
- The `password_hash` column in the `users` table is empty string `''` for API-provisioned users; it is only populated for users who explicitly set a local password via `change_password.php`
- SSL verification is disabled in `development` APP_ENV (`CURLOPT_SSL_VERIFYPEER => false`)

---

## Running Locally

### Option A — XAMPP (recommended for Windows)

1. Start Apache and MySQL from the XAMPP Control Panel
2. Visit `http://localhost/etz-downtime-tracker/public/`

Apache serves `public/` as the web root. All PHP files under `public/` are directly accessible.

### Option B — PHP built-in server with router.php

```bash
cd C:\xampp\htdocs\etz-downtime-tracker
php -S localhost:8000 router.php
```

Then visit `http://localhost:8000/`. The `router.php` handles URL routing, serving static files directly and routing PHP requests to the correct file under `public/`.

### URL structure

All public-facing pages are under `public/`. Use the `url()` helper (from `auth.php`) to generate links — it automatically handles subdirectory vs root-relative paths:

```php
url('index.php')         // → /etz-downtime-tracker/public/index.php
url('admin/users.php')   // → /etz-downtime-tracker/public/admin/users.php
```

---

## Directory Structure

```
etz-downtime-tracker/
│
├── public/                          Web-accessible root (point Apache/nginx here)
│   ├── index.php                    Dashboard
│   ├── login.php                    Login page
│   ├── logout.php                   Session destruction
│   ├── report_category.php          Choose incident type (downtime/security/fraud)
│   ├── report.php                   Downtime incident form
│   ├── report_security.php          Security incident form
│   ├── report_fraud.php             Fraud incident form
│   ├── incidents.php                Unified tabbed incident management
│   ├── other_incidents.php          Additional incident records view
│   ├── analytics.php                Chart.js analytics dashboard
│   ├── sla_report.php               SLA compliance calculator + export
│   ├── knowledge_base.php           Resolved incidents as searchable KB articles
│   ├── kb_article.php               Single KB article detail
│   ├── get_incident.php             JSON API: fetch incident detail by ID
│   ├── profile.php                  User profile view/edit
│   ├── change_password.php          Password change (required on first login for non-admins)
│   │
│   ├── admin/                       Admin-only pages (requireRole('admin'))
│   │   ├── index.php                Admin dashboard
│   │   ├── users.php                User listing, search, bulk actions
│   │   ├── user_create.php          Create user form
│   │   ├── user_edit.php            Edit user form
│   │   ├── user_delete.php          Delete user handler
│   │   ├── user_bulk_import.php     Bulk CSV user import
│   │   ├── manage.php               CRUD for services, companies, components, incident types
│   │   ├── templates.php            Incident template management
│   │   ├── activity_logs.php        Audit log viewer
│   │   └── delete_incidents.php     Admin incident deletion
│   │
│   ├── api/                         Lightweight JSON endpoints (AJAX)
│   │   ├── get_templates.php        GET: templates filtered by service
│   │   └── use_template.php         POST: record template usage
│   │
│   └── uploads/
│       └── incidents/               Uploaded attachments (gitignored, must be writable)
│
├── src/
│   ├── includes/
│   │   ├── auth.php                 Auth functions (login, session, roles, url())
│   │   ├── activity_logger.php      Audit logging functions
│   │   ├── navbar.php               Main navigation component
│   │   ├── admin_navbar.php         Admin navigation component
│   │   ├── loading.php              Loading overlay component
│   │   ├── pdf_config.php           TCPDF initialisation helper
│   │   ├── admin_manage_services.php    Service CRUD logic
│   │   ├── admin_manage_companies.php   Company CRUD logic
│   │   ├── admin_manage_components.php  Component CRUD logic
│   │   └── admin_manage_incident_types.php  Incident type CRUD logic
│   ├── assets/                      Shared images (logo, background)
│   └── exports/                     Export generation helpers (PDF, Excel)
│
├── config/
│   ├── config.php                   Live configuration (gitignored — do not commit)
│   └── config.php.example           Template — copy and edit to create config.php
│
├── database/
│   ├── emptydb.sql                  Clean schema — import this for a fresh install
│   ├── separate_incident_tables_migration.sql
│   ├── add_incident_update_tables_security_fraud.sql
│   ├── add_resolve_fields_security_fraud.sql
│   ├── incident_categories_migration.sql
│   └── incident_components_migration.sql
│
├── docs/
│   ├── README.md                    This file
│   ├── TECHNICAL_DOCS.md            Deep developer reference
│   ├── ACTIVITY_LOGGING.md          Activity logging guide
│   ├── LOADING_GUIDE.md             Loading overlay usage guide
│   └── NGROK_SETUP.md               Remote access via ngrok
│
├── vendor/                          Composer packages (gitignored)
├── composer.json
├── composer.lock
└── router.php                       Dev server routing (PHP built-in server)
```

---

## User Roles

| Role | Capabilities |
|------|-------------|
| `admin` | Full access including admin panel, user management, system configuration, templates, activity logs, incident deletion. Password change not enforced on first login. |
| `user` | Reporting incidents, viewing incidents, analytics, SLA reports, knowledge base, profile. Forced to change password on first login. |

Role is determined by the XPortal API response (`admin` field). It is stored in the local `users` table and in `$_SESSION['role']`.

---

## Credentials and First Login

**There is no hardcoded default admin account.** All users are provisioned automatically when they first log in via XPortal. The XPortal `admin` field determines whether they get the `admin` or `user` role locally.

### First login for regular users

Non-admin users have `changed_password = 0` set when auto-provisioned. On every page load, `requireLogin()` checks this flag and redirects to `change_password.php` until a password is set. Once set, `changed_password` is updated to `1` and the local `password_hash` is populated.

> Note: This local password is **not** used for authentication — XPortal is always the authority. The local password hash is currently stored but not used for login validation.

### Admin password reset

Admins can reset any user's password from `admin/users.php`. The reset sets:
- `password_hash` = bcrypt of `Etz@1234566`
- `changed_password` = `0` (forces the user to set a new password on next login)

---

## Database Migrations

Import files in this order for a fresh install (or use `emptydb.sql` which already includes all schema):

| File | Purpose |
|------|---------|
| `database/emptydb.sql` | Complete current schema — use this for all fresh installs |
| `database/separate_incident_tables_migration.sql` | Creates `security_incidents` and `fraud_incidents` tables (historical — already in emptydb) |
| `database/add_incident_update_tables_security_fraud.sql` | Adds update timeline tables for security and fraud |
| `database/add_resolve_fields_security_fraud.sql` | Adds `lessons_learned` and resolver fields |
| `database/incident_categories_migration.sql` | Incident categorisation improvements |
| `database/incident_components_migration.sql` | Multi-component support via `incident_components` junction table |

> For an existing database that needs to catch up, run the individual migration files in the order listed above. For a brand-new install, just import `emptydb.sql`.

---

## Composer Dependencies

```json
{
  "require": {
    "phpoffice/phpspreadsheet": "^1.29",
    "tecnickcom/tcpdf": "^6.10"
  }
}
```

| Package | Used in | Purpose |
|---------|---------|---------|
| `phpoffice/phpspreadsheet` | `public/sla_report.php` | Generate `.xlsx` SLA report exports |
| `tecnickcom/tcpdf` | `src/exports/`, `src/includes/pdf_config.php` | Generate PDF analytics and SLA reports |

Run `composer install` after cloning. Run `composer update` to pull patch releases.

---

## File Uploads

**Upload path:** `public/uploads/incidents/`

**Constraints (enforced in `report_security.php`, `report_fraud.php`, `report.php`):**

| Setting | Value |
|---------|-------|
| Max file size | 10 MB |
| Allowed extensions | `jpg`, `jpeg`, `gif`, `png`, `pdf`, `doc`, `docx`, `xls`, `xlsx`, `txt` |
| Storage | Flat directory, filename is `{timestamp}_{original_name}` |

Attachments are stored in both:
- `incident_attachments` table (new — preferred)
- `incidents.attachment_path` column (legacy single attachment field — still read for backwards compatibility)

---

## Features Overview

### Dashboard (`public/index.php`)
KPI cards (total / resolved / pending incidents, avg downtime, resolution rate), open critical/high incident alert panel, recent incidents table with service, companies, dates, and status.

### Incident Reporting
- `report_category.php` — Entry point: select downtime, security, or fraud
- `report.php` — Downtime form: service, component, type, impact, priority, companies, attachments, templates
- `report_security.php` — Security form: threat type, systems affected, containment status, escalation target
- `report_fraud.php` — Fraud form: fraud type, financial impact, regulatory reporting flag

### Incident Management (`incidents.php`)
Three-tab interface (Downtime / Security / Fraud). Per incident: status updates, resolution with root cause + lessons learned, resolver names, file attachments, update timeline.

### Analytics (`analytics.php`)
Date range + company filters. Four Chart.js charts: status distribution (doughnut), incidents by company (bar), monthly trend (line), impact level (pie). PDF export.

### SLA Report (`sla_report.php`)
Calculates uptime percentage per service for a selected company and date range. Compares actual vs target (default 99.99%). Excel and PDF export.

### Knowledge Base (`knowledge_base.php`, `kb_article.php`)
Resolved incidents surfaced as searchable articles. Displays root cause, lessons learned, resolution date, resolver names.

### Admin Panel (`admin/`)
User management (create, edit, delete, bulk import, toggle status, reset password), system configuration (services, companies, components, incident types), incident templates, activity log viewer.

---

## Developer Quick Reference

### Adding a new service

```sql
INSERT INTO services (service_name) VALUES ('New Service');

-- Link to existing components
INSERT INTO service_component_map (service_id, component_id)
VALUES (LAST_INSERT_ID(), <component_id>);

-- Create SLA targets for all companies (default 99.99%)
INSERT INTO sla_targets (company_id, service_id, target_uptime_percentage)
SELECT company_id, LAST_INSERT_ID(), 99.99 FROM companies;
```

Or use Admin Panel → Manage → Services.

### Adding a new company

```sql
INSERT INTO companies (company_name, category) VALUES ('Company Name', 'Category');

INSERT INTO sla_targets (company_id, service_id, target_uptime_percentage)
SELECT LAST_INSERT_ID(), service_id, 99.99 FROM services;
```

### Adding a new component

```sql
INSERT INTO components (name, is_active) VALUES ('Component Name', 1);
INSERT INTO service_component_map (service_id, component_id)
VALUES (<service_id>, LAST_INSERT_ID());
```

### Protecting a new page

```php
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/includes/auth.php';

requireLogin();         // redirect to login if not authenticated
// requireRole('admin'); // use this for admin-only pages
```

### Logging an activity

```php
// In any page that has $pdo and the user's ID in session
logActivity($_SESSION['user_id'], 'action_code', 'Human-readable description');
```

### Generating URLs

Always use `url()` — never hardcode paths:

```php
<a href="<?= url('incidents.php') ?>">Incidents</a>
<a href="<?= url('admin/users.php') ?>">Users</a>
```

---

## Troubleshooting

### Blank page / no output

1. Set `APP_ENV` to `'development'` in `config/config.php` — this enables PHP error display
2. Check `C:\xampp\apache\logs\error.log` (Windows) or `/var/log/apache2/error.log` (Linux)
3. Confirm all required extensions are enabled in `php.ini`: `pdo_mysql`, `mbstring`, `gd`, `openssl`, `curl`

### Database connection failed

- Verify credentials in `config/config.php`
- Confirm MySQL is running (check XAMPP Control Panel)
- Check the database exists: run `SHOW DATABASES;` in phpMyAdmin
- If MySQL is on a non-default port: `define('DB_HOST', 'localhost:3307');`

### Login fails / "Invalid credentials"

- The app contacts `webpay.etranzactgh.com` on every login — ensure the machine has outbound internet access
- Check `C:\xampp\apache\logs\error.log` for `External auth API` error lines
- In `development` mode, cURL SSL verification is disabled. In `production` mode it is enforced — ensure the server trusts the XPortal certificate

### Charts not showing

- Chart.js is loaded from CDN — internet access is required for the browser
- Open browser DevTools (F12) → Console tab for JavaScript errors
- Check Network tab to confirm Chart.js CDN URL returns 200

### PDF export not working

- Run `composer install` to ensure TCPDF is present in `vendor/`
- Enable the GD extension in `php.ini` (remove `;` from `extension=gd`)
- Check the error log for TCPDF-specific messages

### File uploads failing

- Confirm `public/uploads/incidents/` exists and the web server user can write to it
- Check PHP limits in `php.ini`: `upload_max_filesize` and `post_max_size` (set both to at least `10M`)
- Confirm the file extension is in the allowed list

### Session issues / constant redirect to login

- Clear browser cookies
- Check that `SESSION_TIMEOUT` in `config.php` is not set too low
- Confirm `session.cookie_httponly = 1` is set (done automatically in `config.php`)

### Apache port conflict (XAMPP)

If port 80 is in use:
1. Open XAMPP → Config → httpd.conf, change `Listen 80` to `Listen 8080`
2. Update all `localhost` URLs to `localhost:8080`

---

## Additional Documentation

| File | Contents |
|------|---------|
| [TECHNICAL_DOCS.md](TECHNICAL_DOCS.md) | Architecture, API reference, function index, DB schema, debugging guide |
| [ACTIVITY_LOGGING.md](ACTIVITY_LOGGING.md) | Activity logging implementation details |
| [LOADING_GUIDE.md](LOADING_GUIDE.md) | Loading overlay usage |
| [NGROK_SETUP.md](NGROK_SETUP.md) | Exposing the local app remotely via ngrok |

---

**Last updated:** April 2026
**PHP target:** 8.2
**Database:** downtimedb (MySQL/MariaDB)
