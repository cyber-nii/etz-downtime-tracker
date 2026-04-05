# eTranzact Downtime Tracking System

## 📋 Table of Contents

- [Quick Start Guide](#quick-start-guide)
- [Overview](#overview)
- [Features](#features)
- [System Requirements](#system-requirements)
- [Installation](#installation)
  - [Method 1: XAMPP Installation (Recommended)](#method-1-xampp-installation-recommended-for-windows)
  - [Method 2: Manual Installation](#method-2-manual-installation-advanced-users)
- [Database Schema](#database-schema)
- [Application Structure](#application-structure)
- [User Guide](#user-guide)
- [Developer Guide](#developer-guide)
- [Security Features](#security-features)
- [Activity Logging](#activity-logging-system)
- [XAMPP-Specific Troubleshooting](#xampp-specific-troubleshooting)
- [General Troubleshooting](#general-troubleshooting)
- [Additional Documentation](#additional-documentation)

---

## ⚡ Quick Start Guide

**Want to get started right away?** Here's the fastest path:

1. **Install XAMPP** → Download from [apachefriends.org](https://www.apachefriends.org/)
2. **Start Services** → Open XAMPP Control Panel, start Apache & MySQL
3. **Copy Files** → Extract this project to `C:\xampp\htdocs\etz-downtime-tracker`
4. **Install Dependencies** → Run `composer install` in the project folder
5. **Import Database** → Open [localhost/phpmyadmin](http://localhost/phpmyadmin), import `database/emptydb.sql`
6. **Configure** → Copy `config/config.php.example` to `config/config.php`, set DB credentials (user: `root`, password: empty)
7. **Launch** → Visit [localhost/etz-downtime-tracker/public](http://localhost/etz-downtime-tracker/public)
8. **Login** → Use your admin credentials created during setup

> 📖 **New to XAMPP?** See the detailed [XAMPP Installation Guide](#method-1-xampp-installation-recommended-for-windows) below.

---

## 🎯 Overview

The **eTranzact Downtime Tracking System** is a comprehensive web application designed to monitor, track, and analyze service downtime incidents, security threats, and fraud events across multiple companies and services. Built with PHP and MySQL, it provides real-time incident management, detailed analytics, SLA compliance reporting, a knowledge base, and a full admin control panel.

### Key Capabilities

- **Three Incident Streams**: Separate tracking for Downtime, Security, and Fraud incidents via a unified tabbed interface
- **Multi-Company Support**: Track incidents across partner companies
- **Service & Component Coverage**: Monitor services and their sub-components
- **Advanced Analytics**: Visualize trends with interactive Chart.js dashboards
- **SLA Reporting**: Generate comprehensive uptime and compliance reports
- **Knowledge Base**: Document resolution guides and best practices
- **PDF Export**: Export analytics and SLA reports in professional PDF format
- **Full Admin Panel**: User management, role-based access, incident templates, and audit logs
- **Authentication**: Session-based login with role-based access control (Admin / User)

---

## ✨ Features

### 1. **Dashboard** (`public/index.php`)

- **Quick Statistics**: View total, resolved, and pending incidents at a glance
- **Recent Incidents Table**: See the latest incidents with service, affected companies, dates, and status
- **Real-time Updates**: Refresh button to get the latest data
- **Status Badges**: Color-coded badges (Pending, Resolved)
- **Dark Mode Support**: Toggle between light and dark themes

### 2. **Incident Reporting**

Incidents are categorized at the point of creation:

- **`public/report_category.php`** — Entry point: choose an incident category (Downtime, Security, or Fraud)
- **`public/report.php`** — Downtime incident reporting form
- **`public/report_security.php`** — Security threat reporting form (phishing, unauthorized access, data breach, etc.)
- **`public/report_fraud.php`** — Fraud incident reporting form (card fraud, account takeover, transaction fraud, etc.)

All forms share:
- **Service & Component Selection**: Link incident to a service and optional sub-component
- **Incident Types**: Pre-configured types per service for consistent categorization
- **Impact & Priority Classification**: Low / Medium / High / Critical (impact) + Urgency (priority)
- **Attachment Upload**: File attachments for supporting documentation
- **Templates**: Auto-populate reports using saved incident templates
- **CSRF Protection** and **Rate Limiting**

### 3. **Incident Management** (`public/incidents.php`)

A unified tabbed interface managing all three incident streams:

- **Downtime Tab**: Service outage incidents linked to companies and downtime metrics
- **Security Tab**: Security threat incidents (phishing, breaches, malware, etc.)
- **Fraud Tab**: Financial fraud incidents with regulatory tracking

All tabs support:
- **Status Management**: Update incident status (Pending → Resolved)
- **Resolvers Tracking**: Mandatory input of engineers/staff who resolved the incident
- **Root Cause & Lessons Learned**: Required on resolution (text or file upload)
- **Update Timeline**: Add and view chronological updates
- **File Attachments**: View and manage supporting documents
- **Impact Level Display**: Color-coded severity indicators

### 4. **Other Incidents** (`public/other_incidents.php`)

A separate viewing area for non-primary incident records and historical data.

### 5. **Analytics Dashboard** (`public/analytics.php`)

- **Interactive Charts**:
  - Status Distribution (Doughnut Chart)
  - Incidents by Company (Bar Chart)
  - Monthly Trend Analysis (Line Chart)
  - Impact Level Distribution (Pie Chart)
- **Date Range Filtering**: Analyze data for custom time periods
- **Company Filtering**: Focus on specific company performance
- **PDF Export**: Generate professional analytics reports
- **Responsive Design**: Charts adapt to screen size

### 6. **SLA Reporting** (`public/sla_report.php`)

- **Uptime Calculation**: Precise uptime percentage tracking
- **Business Hours Support**: Configurable business hours per company/service
- **Downtime Analysis**: Detailed breakdown of downtime incidents
- **Target Compliance**: Compare actual vs. target uptime (default 99.99%)
- **Multi-Service Reports**: View SLA compliance across all services
- **Excel Export**: Download SLA data in spreadsheet format
- **PDF Export**: Professional SLA compliance reports

### 7. **Knowledge Base** (`public/knowledge_base.php` / `public/kb_article.php`)

- **Article Management**: Create and manage resolution guides and best practices
- **Article Detail View**: Full article pages with formatted content
- **Searchable & Categorized**: Browse by topic or search for articles

### 8. **Activity Logging** (`public/admin/activity_logs.php`)

- **Comprehensive Audit Trail**: Tracks all user actions and system events
- **Statistics Dashboard**: View total logs, unique users, top actions
- **Advanced Filtering**: Date range, user, action type, full-text search
- **CSV Export**: Download filtered logs for external analysis
- **Pagination**: Configurable results per page

### 9. **Admin Panel** (`public/admin/`)

| Page | Purpose |
|------|---------|
| `admin/index.php` | Admin dashboard overview |
| `admin/users.php` | User listing and management |
| `admin/user_create.php` | Create new user accounts |
| `admin/user_edit.php` | Edit existing users |
| `admin/user_delete.php` | Delete user accounts |
| `admin/user_bulk_import.php` | Bulk import users via CSV |
| `admin/manage.php` | Manage services, companies, components, and incident types |
| `admin/templates.php` | Manage incident report templates |
| `admin/activity_logs.php` | View full activity/audit logs |
| `admin/delete_incidents.php` | Admin-only incident deletion |

### 10. **User Account Features**

- **`public/login.php`** / **`public/logout.php`** — Authentication entry/exit
- **`public/profile.php`** — View and update user profile (name, phone, location)
- **`public/change_password.php`** — Change account password (requires current password)

---

## 💻 System Requirements

### Server Requirements

- **Web Server**: Apache 2.4+ (with mod_rewrite)
- **PHP**: 7.4 or higher (8.0+ recommended, 8.2 used in development)
- **Database**: MySQL 5.7+ or MariaDB 10.3+ (10.4.32 used in development)
- **Extensions**:
  - PDO
  - PDO_MySQL
  - mbstring
  - GD (for PDF generation)
  - OpenSSL

### Client Requirements

- Modern web browser (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)
- JavaScript enabled
- Minimum screen resolution: 1024x768

### Development Tools (Optional)

- Composer (for dependency management)
- Git (for version control)

---

## 🚀 Installation

### Method 1: XAMPP Installation (Recommended for Windows)

#### Step 1: Install XAMPP

1. **Download XAMPP**:
   - Visit [https://www.apachefriends.org/](https://www.apachefriends.org/)
   - Download XAMPP for Windows (PHP 7.4 or higher)
   - Run the installer and follow the installation wizard

2. **Install Components**:
   - Make sure to select **Apache** and **MySQL** during installation
   - Default installation path: `C:\xampp`

3. **Start XAMPP Services**:
   - Open **XAMPP Control Panel**
   - Click **Start** next to **Apache**
   - Click **Start** next to **MySQL**
   - Both should show green "Running" status

#### Step 2: Download/Clone the Application

1. **Navigate to XAMPP's htdocs folder**:

   ```
   C:\xampp\htdocs\
   ```

2. **Option A - Download ZIP**:
   - Extract the project to `C:\xampp\htdocs\etz-downtime-tracker`

3. **Option B - Git Clone**:
   ```bash
   cd C:\xampp\htdocs
   git clone <repository-url> etz-downtime-tracker
   cd etz-downtime-tracker
   ```

#### Step 3: Install PHP Dependencies

1. **Install Composer** (if not already installed):
   - Download from [https://getcomposer.org/download/](https://getcomposer.org/download/)
   - Run the installer
   - Restart your command prompt/terminal

2. **Install Project Dependencies**:
   ```bash
   cd C:\xampp\htdocs\etz-downtime-tracker
   composer install
   ```

#### Step 4: Create the Database

1. **Open phpMyAdmin**:
   - In your browser, go to: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)

2. **Import the Database**:
   - Click **New** in the left sidebar to create a new database named `downtimedb`
   - Select the `downtimedb` database, then click the **Import** tab
   - Click **Choose File**, navigate to:
     ```
     C:\xampp\htdocs\etz-downtime-tracker\database\emptydb.sql
     ```
   - Click **Import**

3. **Verify Database Creation**:
   - You should see `downtimedb` in the left sidebar with these tables:
     - `activity_logs`
     - `companies`
     - `components`
     - `downtime_incidents`
     - `fraud_incidents` + `fraud_incident_attachments` + `fraud_incident_updates`
     - `incidents`
     - `incident_affected_companies`
     - `incident_attachments`
     - `incident_company_history`
     - `incident_components`
     - `incident_templates`
     - `incident_types` + `incident_type_service_map`
     - `incident_updates`
     - `security_incidents` + `security_incident_attachments` + `security_incident_updates`
     - `service_component_map`
     - `services`
     - `sla_targets`
     - `users`

#### Step 5: Configure Database Connection

1. **Create Configuration File**:
   - Navigate to `C:\xampp\htdocs\etz-downtime-tracker\config\`
   - Copy `config.php.example` → rename copy to `config.php`

2. **Edit `config.php`**:
   ```php
   <?php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');           // Default XAMPP username
   define('DB_PASS', '');               // Default XAMPP password is empty
   define('DB_NAME', 'downtimedb');

   define('APP_ENV', 'development');    // Use 'production' when deploying
   define('SITE_NAME', 'eTranzact Downtime Management System');
   ```

> **Note**: XAMPP's default MySQL username is `root` with an **empty password**.

#### Step 6: Create the First Admin User

After importing the database, you need to create your first admin account. Insert directly via phpMyAdmin or CLI:

```sql
INSERT INTO users (username, email, password_hash, full_name, role, is_active)
VALUES (
  'admin',
  'admin@etranzact.com',
  '$2y$10$REPLACE_WITH_BCRYPT_HASH',  -- Use PHP: password_hash('your_password', PASSWORD_BCRYPT)
  'System Administrator',
  'admin',
  1
);
```

Or use PHP via CLI to generate a hash first:
```bash
php -r "echo password_hash('YourSecurePassword', PASSWORD_BCRYPT);"
```

#### Step 7: Access the Application

1. **Open Your Browser**:
   - Navigate to: [http://localhost/etz-downtime-tracker/public/](http://localhost/etz-downtime-tracker/public/)
   - You will be redirected to the login page

2. **Login** with your admin credentials

3. **Verify Installation**:
   - The dashboard should load with statistics cards
   - The navigation should include: Dashboard, Report Incident, Incidents, Analytics, SLA Report, Knowledge Base

---

### Method 2: Manual Installation (Advanced Users)

#### Step 1: Prerequisites

Ensure you have:
- Apache 2.4+ or Nginx
- PHP 7.4+ (with PDO, PDO_MySQL, mbstring, GD extensions)
- MySQL 5.7+ or MariaDB 10.3+
- Composer

#### Step 2: Clone/Download the Repository

```bash
git clone <repository-url> etz-downtime-tracker
cd etz-downtime-tracker
```

#### Step 3: Install Dependencies

```bash
composer install
```

#### Step 4: Database Setup

```bash
mysql -u root -p < database/emptydb.sql
```

#### Step 5: Configure Database Connection

```bash
# Windows
copy config\config.php.example config\config.php

# Linux/Mac
cp config/config.php.example config/config.php
```

Edit `config/config.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'downtimedb');
define('APP_ENV', 'production');
```

#### Step 6: Set Permissions

```bash
# Linux/Mac
chmod 644 config/config.php
chmod 755 src/includes/
chmod 755 public/uploads/

# Windows — Ensure the web server user has read/write permissions on public/uploads/
```

#### Step 7: Configure Web Server

Point your web server document root to `public/`. For Apache, a sample `.htaccess` may redirect all requests through `router.php` at the project root.

---

## 🗄️ Database Schema

### Core Tables

#### `users`

User authentication and role-based access control.

```sql
user_id (PK)           - Auto-increment ID
username               - Unique username
email                  - Unique email address
password_hash          - Bcrypt hashed password
full_name              - User's display name
phone                  - Optional phone number
location               - Optional location/office
role                   - ENUM('admin', 'user')
is_active              - Account enabled flag
last_login             - Last login timestamp
changed_password       - Flag: has user changed their initial password
created_at / updated_at
```

#### `companies`

Partner company records.

```sql
company_id (PK)
company_name
category               - Optional grouping
created_at / updated_at
```

#### `services`

Monitored services (e.g., Mobile Money, Fundgate, GHQR).

```sql
service_id (PK)
service_name
created_at / updated_at
```

#### `components`

Sub-components within services (e.g., API, Database, Gateway).

```sql
component_id (PK)
name                   - Unique component name
is_active              - Enable/disable flag
```

#### `incident_types`

Pre-defined incident categories per service.

```sql
type_id (PK)
service_id (FK)        - Associated service (optional)
name                   - Incident type name
is_active
```

#### `incidents`

Primary downtime incident tracking table.

```sql
incident_id (PK)
incident_ref           - Auto-generated unique reference (e.g., INC-20260405-0001)
service_id (FK)
component_id (FK)      - Optional sub-component
incident_type_id (FK)  - Optional type classification
description            - Detailed description
impact_level           - ENUM('Low','Medium','High','Critical')
priority               - ENUM('Low','Medium','High','Urgent')
incident_source        - ENUM('internal','external')
root_cause             - Root cause text
root_cause_file        - Path to root cause attachment
lessons_learned        - Post-incident analysis
lessons_learned_file   - Path to lessons file
attachment_path        - Primary attachment
actual_start_time      - When the incident began
status                 - ENUM('pending','resolved')
reported_by (FK)       - User who reported
resolved_by (FK)       - User who resolved
resolved_at
resolvers              - JSON array of resolver names
created_at / updated_at
```

#### `incident_affected_companies`

Many-to-many: incidents ↔ companies.

```sql
incident_id (FK, PK)
company_id (FK, PK)
```

#### `incident_updates`

Chronological update timeline per downtime incident.

```sql
update_id (PK)
incident_id (FK)
user_id (FK)           - Optional (nullable)
user_name              - Preserved username at time of update
update_text
created_at
```

#### `incident_attachments`

File attachments for downtime incidents.

```sql
attachment_id (PK)
incident_id (FK)
file_path / file_name / file_type / file_size
uploaded_at
```

#### `incident_templates`

Reusable report templates to speed up incident reporting.

```sql
template_id (PK)
template_name          - Unique template name
service_id (FK)
component_id (FK)
incident_type_id (FK)
impact_level
description            - Template body text
root_cause             - Pre-filled root cause
is_active
usage_count            - Tracks how often it's used
created_by (FK)
created_at / updated_at
```

#### `downtime_incidents`

Granular downtime timing records for SLA calculations.

```sql
downtime_id (PK)
incident_id (FK)
actual_start_time
actual_end_time
downtime_minutes       - Auto-calculated via trigger
is_planned             - Boolean (planned maintenance vs. unplanned outage)
downtime_category      - ENUM('Network','Server','Maintenance','Third-party','Other')
created_at / updated_at
```

**Trigger**: `calculate_downtime_minutes` — auto-calculates duration on INSERT and UPDATE.

#### `security_incidents`

Security threat incident tracking.

```sql
id (PK)
incident_ref           - Unique reference (e.g., SEC-20260405-0001)
threat_type            - ENUM('phishing','unauthorized_access','data_breach','malware','social_engineering','other')
systems_affected       - Text description of affected systems
description
impact_level / priority
containment_status     - ENUM('contained','ongoing','under_investigation')
escalated_to           - Who the incident was escalated to
root_cause / lessons_learned
attachment_path
actual_start_time
status                 - ENUM('pending','resolved')
reported_by (FK) / resolved_by (FK) / resolved_at
resolvers              - JSON array
created_at / updated_at
```

Plus: `security_incident_updates`, `security_incident_attachments`

#### `fraud_incidents`

Financial fraud incident tracking.

```sql
id (PK)
incident_ref           - Unique reference (e.g., FRD-20260405-0001)
fraud_type             - ENUM('card_fraud','account_takeover','transaction_fraud','internal_fraud','other')
service_id (FK)
description
financial_impact       - Decimal (monetary value)
impact_level / priority
regulatory_reported    - Boolean flag
regulatory_details     - Details of regulatory notification
root_cause / lessons_learned
attachment_path
actual_start_time
status / reported_by / resolved_by / resolved_at
resolvers              - JSON array
created_at / updated_at
```

Plus: `fraud_incident_updates`, `fraud_incident_attachments`

#### `sla_targets`

SLA configuration per company/service.

```sql
target_id (PK)
company_id (FK)        - Nullable (global target if NULL)
service_id (FK)        - Nullable
target_uptime          - Decimal (default 99.99%)
business_hours_start   - Time (default 09:00:00)
business_hours_end     - Time (default 17:00:00)
business_days          - SET('Mon','Tue','Wed','Thu','Fri','Sat','Sun')
created_at / updated_at
```

#### `activity_logs`

Full audit trail of user actions.

```sql
log_id (PK)
user_id (FK)           - References users
action                 - Short action code (e.g., 'incident_created', 'user_login')
description            - Human-readable description
ip_address             - IPv4 or IPv6
user_agent             - Browser string
created_at
```

---

## 📁 Application Structure

```
etz-downtime-tracker/
│
├── public/                         # Web-accessible root (point Apache here)
│   ├── index.php                   # Dashboard homepage
│   ├── login.php                   # Login page
│   ├── logout.php                  # Session termination
│   ├── report_category.php         # Incident type selection
│   ├── report.php                  # Downtime incident reporting
│   ├── report_security.php         # Security incident reporting
│   ├── report_fraud.php            # Fraud incident reporting
│   ├── incidents.php               # Unified incident management (tabbed)
│   ├── other_incidents.php         # Additional incident records
│   ├── analytics.php               # Analytics dashboard with charts
│   ├── sla_report.php              # SLA compliance reporting
│   ├── knowledge_base.php          # Knowledge base article list
│   ├── kb_article.php              # Knowledge base article detail
│   ├── get_incident.php            # AJAX: fetch incident details
│   ├── profile.php                 # User profile management
│   ├── change_password.php         # Password change
│   │
│   ├── admin/                      # Admin-only pages
│   │   ├── index.php               # Admin dashboard
│   │   ├── users.php               # User list
│   │   ├── user_create.php         # Create user
│   │   ├── user_edit.php           # Edit user
│   │   ├── user_delete.php         # Delete user
│   │   ├── user_bulk_import.php    # Bulk CSV import
│   │   ├── manage.php              # Manage services, companies, components, types
│   │   ├── templates.php           # Incident templates CRUD
│   │   ├── activity_logs.php       # Audit log viewer
│   │   └── delete_incidents.php    # Admin incident deletion
│   │
│   ├── api/                        # Lightweight AJAX endpoints
│   │   ├── get_templates.php       # Fetch templates for a service
│   │   └── use_template.php        # Load template data into form
│   │
│   ├── assets/                     # Static assets (images, fonts)
│   ├── css/                        # Page-specific or compiled CSS
│   └── uploads/                    # User-uploaded files (gitignored)
│
├── src/                            # Backend shared PHP source
│   ├── includes/                   # Shared PHP components
│   │   ├── navbar.php              # Main navigation bar
│   │   ├── admin_navbar.php        # Admin panel navigation
│   │   ├── loading.php             # Loading overlay
│   │   ├── auth.php                # Authentication guard
│   │   ├── activity_logger.php     # Activity logging helper
│   │   ├── pdf_config.php          # TCPDF configuration
│   │   ├── admin_manage_services.php
│   │   ├── admin_manage_companies.php
│   │   ├── admin_manage_components.php
│   │   └── admin_manage_incident_types.php
│   ├── assets/                     # Shared backend assets
│   ├── css/                        # Global CSS
│   └── exports/                    # Export generation helpers
│
├── config/                         # Configuration (gitignored credentials)
│   ├── config.php                  # Database + app settings (DO NOT COMMIT)
│   └── config.php.example          # Template for config.php
│
├── database/                       # Database files
│   ├── emptydb.sql                 # Clean schema (no seed data)
│   └── *.sql                       # Migration scripts
│
├── migration/                      # Data migration scripts
├── docs/                           # Documentation
│   ├── README.md                   # This file
│   ├── TECHNICAL_DOCS.md           # Developer technical reference
│   ├── ACTIVITY_LOGGING.md         # Activity logging guide
│   └── NGROK_SETUP.md              # Remote access instructions
│
├── vendor/                         # Composer packages (gitignored)
├── composer.json
├── composer.lock
└── router.php                      # Entry routing for dev server
```

---

## 📖 User Guide

### Logging In

1. Navigate to [http://localhost/etz-downtime-tracker/public/](http://localhost/etz-downtime-tracker/public/)
2. Enter your username and password on the login page
3. Admin and regular user roles have different menu access

### Reporting a New Incident

1. Click **Report Incident** in the navbar
2. On the **Category** page, select the incident type:
   - **Downtime** — Service outage or performance degradation
   - **Security** — Phishing, unauthorized access, data breach
   - **Fraud** — Card fraud, account takeover, financial fraud
3. Fill in the form for the selected type
4. Optionally load a **Template** to pre-fill common fields
5. Upload any supporting attachments
6. Click **Submit Report**

### Managing Incidents (`incidents.php`)

1. Go to **Incidents** in the navbar
2. Use the tabs to switch between **Downtime**, **Security**, and **Fraud** views
3. **To add an update**:
   - Click "Add Update" on an incident card
   - Enter your update text and submit
4. **To resolve an incident**:
   - Click "Resolve Incident"
   - Enter root cause and lessons learned (or upload a file)
   - Add at least one resolver name
   - Confirm resolution

### Viewing Analytics

1. Navigate to **Analytics**
2. Filter by company and date range
3. View distribution, trend, and severity charts
4. Click **Export PDF** for a downloadable report

### Generating SLA Reports

1. Go to **SLA Report**
2. Select company and service, choose date range
3. Click **Generate Report**
4. Export as **Excel** or **PDF**

### Knowledge Base

1. Navigate to **Knowledge Base**
2. Browse or search for resolution articles
3. Click any article to view full details

### Using Dark Mode

1. Click the **moon icon** (🌙) in the navbar
2. Preference is saved in browser localStorage

---

## 🛠️ Developer Guide

### Adding a New Service

```sql
INSERT INTO services (service_name) VALUES ('New Service Name');

-- Add SLA targets for all companies
INSERT INTO sla_targets (company_id, service_id, target_uptime)
SELECT c.company_id, LAST_INSERT_ID(), 99.99
FROM companies c;
```

### Adding a New Component to a Service

```sql
INSERT INTO components (name) VALUES ('New Component');

INSERT INTO service_component_map (service_id, component_id)
VALUES (<service_id>, LAST_INSERT_ID());
```

### Adding a New Company

```sql
INSERT INTO companies (company_name, category) VALUES ('Company Name', 'Category');

-- Add SLA targets for all services
INSERT INTO sla_targets (company_id, service_id, target_uptime)
SELECT LAST_INSERT_ID(), s.service_id, 99.99
FROM services s;
```

### Adding a New Incident Type

```sql
INSERT INTO incident_types (name, is_active) VALUES ('New Type', 1);

-- Map to a service
INSERT INTO incident_type_service_map (service_id, type_id)
VALUES (<service_id>, LAST_INSERT_ID());
```

### Creating an Incident Template

Via Admin Panel → Templates, or via SQL:

```sql
INSERT INTO incident_templates
  (template_name, service_id, component_id, incident_type_id, impact_level, description, root_cause, created_by)
VALUES
  ('Internet Downtime Template', 1, 2, 3, 'High', 'Internet connectivity lost...', 'ISP outage', 1);
```

### Customizing SLA Targets

```sql
UPDATE sla_targets
SET target_uptime = 99.95,
    business_hours_start = '08:00:00',
    business_hours_end = '18:00:00',
    business_days = 'Mon,Tue,Wed,Thu,Fri,Sat'
WHERE company_id = 1 AND service_id = 2;
```

### Authentication Guard

All protected pages must include:

```php
require_once __DIR__ . '/../../src/includes/auth.php';
```

`auth.php` checks the session and redirects to `login.php` if not authenticated. Admin-only pages should additionally check `$_SESSION['role'] === 'admin'`.

---

## 🔒 Security Features

### Implemented Security Measures

1. **Authentication**: Session-based login with bcrypt password hashing
2. **Role-Based Access Control**: Admin vs User roles with enforced restrictions
3. **CSRF Protection**: Token generation and validation on all forms
4. **SQL Injection Prevention**: Prepared statements with PDO parameter binding
5. **XSS Protection**: `htmlspecialchars()` on all output
6. **Rate Limiting**: Session-based (5 requests/minute) on report forms
7. **Input Validation**: Server-side for all inputs (length limits, type checking, sanitization)
8. **Error Handling**: Production mode suppresses detailed errors; development mode shows them
9. **Security Headers**: `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`
10. **Activity Logging**: All significant actions are audited with IP and user agent

### Recommended Additional Security

1. **HTTPS**: Always use SSL/TLS in production; set `session.cookie_secure = 1` in `config.php`
2. **Database User**: Create a dedicated MySQL user with minimal privileges
3. **File Permissions**: Restrict `config/config.php` and `public/uploads/` carefully
4. **Regular Updates**: Keep PHP, MySQL, and Composer dependencies updated
5. **Backup Strategy**: Regular automated database backups

---

## 🔧 XAMPP-Specific Troubleshooting

### Apache Won't Start

**Problem**: Apache shows "Port 80 in use"

**Solutions**:

1. **Disable IIS**:
   - Run `services.msc`, find "World Wide Web Publishing Service" → stop it

2. **Change Apache Port** (in XAMPP → Config → httpd.conf):
   - Change `Listen 80` → `Listen 8080`
   - Access app at: `http://localhost:8080/etz-downtime-tracker/public/`

### MySQL Won't Start

**Problem**: MySQL shows "Port 3306 in use"

**Solutions**:

1. Kill conflicting MySQL processes in Task Manager (`mysqld.exe`)
2. Or change MySQL to port 3307 and update `config/config.php`:
   ```php
   define('DB_HOST', 'localhost:3307');
   ```

### Composer Not Found

**Solutions**:

1. Download from [getcomposer.org](https://getcomposer.org/download/) and run installer
2. Add `C:\xampp\php` to Windows PATH and restart terminal

### Database Import Failed

**Problem**: Error importing `emptydb.sql`

**Solutions**:

1. **File too large** — Edit `php.ini`:
   ```ini
   upload_max_filesize = 64M
   post_max_size = 64M
   max_execution_time = 300
   ```

2. **Import via Command Line**:
   ```bash
   cd C:\xampp\mysql\bin
   mysql -u root downtimedb < "C:\xampp\htdocs\etz-downtime-tracker\database\emptydb.sql"
   ```

### Blank Page / White Screen

1. Set `APP_ENV` to `'development'` in `config/config.php` to show errors
2. Check Apache error log: `C:\xampp\apache\logs\error.log`
3. Ensure PHP extensions are enabled in `php.ini`: `pdo_mysql`, `mbstring`, `gd`

### Charts Not Showing

- Charts use CDN for Chart.js — ensure internet access
- Check browser console (F12) for JavaScript errors

### PDF Export Not Working

1. Run `composer install` to ensure dependencies are present
2. Enable PHP GD extension in `php.ini` (remove `;` from `extension=gd`)

### Login Loops / Session Issues

- Ensure `session.cookie_httponly = 1` in PHP configuration
- Clear browser cookies and try again
- Check that `config/config.php` has correct `SESSION_TIMEOUT` value

---

## 🐛 General Troubleshooting

### Database Connection Failed

- Verify credentials in `config/config.php`
- Ensure MySQL is running
- Check database exists: `SHOW DATABASES;`
- Verify user permissions: `GRANT ALL ON downtimedb.* TO 'user'@'localhost';`

### Debug Mode

Enable detailed error reporting in `config/config.php`:

```php
define('APP_ENV', 'development');
```

This enables PHP error display and detailed SQL errors.

> ⚠️ **Never use `development` mode in production!**

### Server Error Logs

```bash
# XAMPP Windows
C:\xampp\apache\logs\error.log

# Linux Apache
tail -f /var/log/apache2/error.log
```

---

## 📞 Support

For issues, questions, or feature requests:

1. Check this documentation
2. Review the troubleshooting sections
3. Check application error logs
4. Contact the eTranzact development team

---

## 📄 License

This application is proprietary software developed for eTranzact. All rights reserved.

---

## 🙏 Credits

**Developed by**: eTranzact Development Team  
**CSS Framework**: Tailwind CSS v3  
**Charts**: Chart.js v4  
**Icons**: Font Awesome 6  
**Fonts**: Inter (Google Fonts)  
**PDF Library**: TCPDF (via Composer)  
**JavaScript Reactivity**: Alpine.js v3  

---

## 📚 Additional Documentation

- **[TECHNICAL_DOCS.md](TECHNICAL_DOCS.md)** — Developer technical reference (queries, patterns, chart config)
- **[ACTIVITY_LOGGING.md](ACTIVITY_LOGGING.md)** — Activity logging implementation guide
- **[NGROK_SETUP.md](NGROK_SETUP.md)** — Remote/public access via ngrok

---

## 🗓️ Activity Logging System

The application includes a comprehensive activity logging system that tracks all user actions and system events.

### Key Features

- **Audit Trail**: Tracks authentication, incident operations, report exports, and admin actions
- **Advanced Filtering**: Filter by date, user, action type, full-text search
- **Statistics Dashboard**: Total logs, unique users, most active users
- **CSV Export**: Download filtered logs for analysis

### Documentation

See **[ACTIVITY_LOGGING.md](ACTIVITY_LOGGING.md)** for complete implementation details.

---

**Last Updated**: April 2026  
**Version**: 2.0.0
