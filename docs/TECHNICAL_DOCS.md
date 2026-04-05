# Technical Documentation — eTranzact Downtime System

## 📚 Table of Contents

- [Architecture Overview](#architecture-overview)
- [Project Structure](#project-structure)
- [Authentication & Authorization](#authentication--authorization)
- [Database Queries Reference](#database-queries-reference)
- [Frontend Components](#frontend-components)
- [Backend Logic](#backend-logic)
- [Three Incident Types](#three-incident-types)
- [API Endpoints](#api-endpoints)
- [Chart.js Implementation](#chartjs-implementation)
- [PDF Generation](#pdf-generation)
- [Session & Security Management](#session--security-management)
- [Activity Logging](#activity-logging)
- [Admin Management Modules](#admin-management-modules)
- [Performance Optimization](#performance-optimization)
- [Testing Checklist](#testing-checklist)
- [Deployment Checklist](#deployment-checklist)

---

## 🏗️ Architecture Overview

### Technology Stack

```
┌──────────────────────────────────────────────┐
│               Frontend Layer                 │
│  - HTML5 + Tailwind CSS v3 (CDN)            │
│  - Alpine.js v3.x (Reactivity)              │
│  - Chart.js v4 (Visualizations)             │
│  - Font Awesome 6 (Icons)                   │
│  - Inter (Google Fonts)                     │
└──────────────────────────────────────────────┘
                      ↓
┌──────────────────────────────────────────────┐
│          Application Layer (PHP)             │
│  - PHP 8.2 (7.4+ compatible)               │
│  - PDO for Database Access                  │
│  - Session-based Auth + CSRF Tokens         │
│  - TCPDF for PDF Generation                 │
│  - Activity Logger (src/includes/)          │
└──────────────────────────────────────────────┘
                      ↓
┌──────────────────────────────────────────────┐
│           Data Layer (MySQL/MariaDB)         │
│  - MariaDB 10.4+ / MySQL 5.7+              │
│  - InnoDB Engine + Foreign Key Constraints  │
│  - Triggers for Auto-calculations           │
│  - 20 tables across 3 incident domains      │
└──────────────────────────────────────────────┘
```

### Design Patterns

1. **MVC-Inspired** — Page-level PHP controllers, embedded templates, direct PDO queries
2. **Auth Guard Middleware** — `src/includes/auth.php` enforces login on every protected page
3. **Shared Components** — `navbar.php`, `loading.php`, `pdf_config.php` via `src/includes/`
4. **Three Incident Domains** — Downtime (`incidents`), Security (`security_incidents`), Fraud (`fraud_incidents`) tracked independently with shared resolution patterns
5. **Tabbed Interface** — `incidents.php` unifies all three streams in one UI
6. **Template System** — `incident_templates` table + API endpoints enable reusable report starters

---

## 📂 Project Structure

```
etz-downtime-tracker/         ← Project root
│
├── public/                   ← Apache document root (web-accessible)
│   ├── index.php             ← Dashboard
│   ├── login.php / logout.php
│   ├── report_category.php   ← Incident type chooser
│   ├── report.php            ← Downtime report form
│   ├── report_security.php   ← Security report form
│   ├── report_fraud.php      ← Fraud report form
│   ├── incidents.php         ← Unified tabbed incident management
│   ├── other_incidents.php
│   ├── analytics.php
│   ├── sla_report.php
│   ├── knowledge_base.php
│   ├── kb_article.php
│   ├── get_incident.php      ← AJAX: incident detail fetch
│   ├── profile.php
│   ├── change_password.php
│   │
│   ├── admin/                ← Admin-only pages
│   │   ├── index.php
│   │   ├── users.php / user_create.php / user_edit.php / user_delete.php
│   │   ├── user_bulk_import.php
│   │   ├── manage.php        ← Services, companies, components, types
│   │   ├── templates.php     ← Incident template management
│   │   ├── activity_logs.php
│   │   └── delete_incidents.php
│   │
│   ├── api/                  ← Lightweight AJAX JSON endpoints
│   │   ├── get_templates.php
│   │   └── use_template.php
│   │
│   └── uploads/              ← User-uploaded files (gitignored)
│
├── src/includes/             ← Shared backend components
│   ├── auth.php              ← Authentication guard
│   ├── activity_logger.php   ← Logging helper class/functions
│   ├── navbar.php            ← Main nav (with dark mode)
│   ├── admin_navbar.php      ← Admin panel nav
│   ├── loading.php           ← Loading overlay
│   ├── pdf_config.php        ← TCPDF custom class
│   ├── admin_manage_services.php
│   ├── admin_manage_companies.php
│   ├── admin_manage_components.php
│   └── admin_manage_incident_types.php
│
├── config/
│   ├── config.php            ← DB credentials + app settings (gitignored)
│   └── config.php.example
│
└── database/
    ├── emptydb.sql           ← Clean schema (no seed data)
    └── *.sql                 ← Migration patches
```

### Standard Page Lifecycle

```php
<?php
// All protected pages follow this structure:

// 1. Load config (DB connection, constants, security headers)
require_once __DIR__ . '/../../config/config.php';

// 2. Enforce authentication
require_once __DIR__ . '/../../src/includes/auth.php';

// 3. Handle POST (form submissions)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check → Input sanitization → Validation → DB transaction → Redirect
}

// 4. Fetch display data
try {
    $stmt = $pdo->prepare("SELECT ...");
    $stmt->execute([...]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error
}

// 5. Render HTML
include __DIR__ . '/../../src/includes/navbar.php';
?>
<!-- Template HTML with embedded PHP -->
```

---

## 🔐 Authentication & Authorization

### Auth Guard (`src/includes/auth.php`)

Every protected page starts with:

```php
require_once __DIR__ . '/../../src/includes/auth.php';
```

This file:
- Calls `session_start()`
- Checks `$_SESSION['user_id']` is set and valid
- Redirects to `login.php` if not authenticated
- Enforces `SESSION_TIMEOUT` (default: 3600 seconds)

### Login Flow (`public/login.php`)

```php
// Verify credentials
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    session_regenerate_id(true); // Prevent session fixation
    $_SESSION['user_id']   = $user['user_id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
    header("Location: index.php");
    exit();
}
```

### Role Checks

```php
// Admin-only pages
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
```

### Password Management

Passwords are hashed with bcrypt:

```php
// Hash on creation/update
$hash = password_hash($newPassword, PASSWORD_BCRYPT);

// Verify on login or password change
password_verify($inputPassword, $storedHash);
```

---

## 🗃️ Database Queries Reference

### 1. Dashboard — Recent Incidents

```sql
SELECT
    i.incident_id,
    i.incident_ref,
    s.service_name,
    c.name AS component_name,
    i.impact_level,
    i.status,
    i.actual_start_time,
    i.created_at,
    GROUP_CONCAT(DISTINCT co.company_name ORDER BY co.company_name) AS companies,
    COUNT(DISTINCT iac.company_id) AS company_count
FROM incidents i
JOIN services s ON i.service_id = s.service_id
LEFT JOIN components c ON i.component_id = c.component_id
LEFT JOIN incident_affected_companies iac ON i.incident_id = iac.incident_id
LEFT JOIN companies co ON iac.company_id = co.company_id
GROUP BY i.incident_id
ORDER BY i.created_at DESC
LIMIT 20
```

### 2. Incidents Page — Downtime Tab

```sql
SELECT
    i.*,
    s.service_name,
    c.name AS component_name,
    it.name AS incident_type,
    GROUP_CONCAT(DISTINCT co.company_name ORDER BY co.company_name) AS companies,
    COUNT(DISTINCT iac.company_id) AS company_count,
    u_rep.full_name AS reporter_name,
    u_res.full_name AS resolver_name
FROM incidents i
JOIN services s ON i.service_id = s.service_id
LEFT JOIN components c ON i.component_id = c.component_id
LEFT JOIN incident_types it ON i.incident_type_id = it.type_id
LEFT JOIN incident_affected_companies iac ON i.incident_id = iac.incident_id
LEFT JOIN companies co ON iac.company_id = co.company_id
JOIN users u_rep ON i.reported_by = u_rep.user_id
LEFT JOIN users u_res ON i.resolved_by = u_res.user_id
GROUP BY i.incident_id
ORDER BY
    FIELD(i.status, 'pending', 'resolved'),
    i.created_at DESC
```

### 3. Security Incidents Tab

```sql
SELECT
    si.*,
    u_rep.full_name AS reporter_name,
    u_res.full_name AS resolver_name
FROM security_incidents si
JOIN users u_rep ON si.reported_by = u_rep.user_id
LEFT JOIN users u_res ON si.resolved_by = u_res.user_id
ORDER BY
    FIELD(si.status, 'pending', 'resolved'),
    si.created_at DESC
```

### 4. Fraud Incidents Tab

```sql
SELECT
    fi.*,
    s.service_name,
    u_rep.full_name AS reporter_name,
    u_res.full_name AS resolver_name
FROM fraud_incidents fi
LEFT JOIN services s ON fi.service_id = s.service_id
JOIN users u_rep ON fi.reported_by = u_rep.user_id
LEFT JOIN users u_res ON fi.resolved_by = u_res.user_id
ORDER BY
    FIELD(fi.status, 'pending', 'resolved'),
    fi.created_at DESC
```

### 5. Analytics — Status Distribution

```sql
SELECT
    status,
    COUNT(*) AS count
FROM incidents
WHERE created_at >= ? AND created_at < ?
GROUP BY status
```

### 6. SLA Report — Uptime Calculation

```sql
SELECT
    SUM(di.downtime_minutes) AS total_downtime,
    COUNT(di.downtime_id) AS incident_count
FROM downtime_incidents di
JOIN incidents i ON di.incident_id = i.incident_id
JOIN incident_affected_companies iac ON i.incident_id = iac.incident_id
WHERE i.service_id = ?
  AND iac.company_id = ?
  AND di.actual_start_time >= ?
  AND di.actual_start_time < ?
```

**Formula**: `Uptime % = ((Total Minutes in Period − Downtime Minutes) / Total Minutes) * 100`

### 7. Incident Templates — Filtered by Service

```sql
SELECT t.*, s.service_name, c.name AS component_name, it.name AS type_name
FROM incident_templates t
LEFT JOIN services s ON t.service_id = s.service_id
LEFT JOIN components c ON t.component_id = c.component_id
LEFT JOIN incident_types it ON t.incident_type_id = it.type_id
WHERE t.is_active = 1
  AND (t.service_id = ? OR t.service_id IS NULL)
ORDER BY t.usage_count DESC, t.template_name ASC
```

### 8. Activity Logs — Filtered & Paginated

```sql
SELECT al.*, u.full_name
FROM activity_logs al
LEFT JOIN users u ON al.user_id = u.user_id
WHERE (? IS NULL OR al.user_id = ?)
  AND (? IS NULL OR al.action = ?)
  AND (? IS NULL OR al.created_at >= ?)
  AND (? IS NULL OR al.created_at <= ?)
  AND (? IS NULL OR al.description LIKE CONCAT('%', ?, '%'))
ORDER BY al.created_at DESC
LIMIT ? OFFSET ?
```

---

## 🎨 Frontend Components

### Navbar Component (`src/includes/navbar.php`)

**Features**:
- Responsive mobile menu (hamburger toggle)
- Active page highlighting based on `$_SERVER['PHP_SELF']`
- Dark mode toggle with localStorage persistence
- User profile dropdown (username, profile link, logout)
- Admin-only "Admin" menu item based on `$_SESSION['role']`

**Dark Mode Implementation**:

```javascript
// Alpine.js component on <html> element
{
    darkMode: localStorage.getItem('darkMode') === 'true',

    toggleDarkMode() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('darkMode', this.darkMode);
        document.documentElement.classList.toggle('dark', this.darkMode);
    },

    init() {
        if (this.darkMode) document.documentElement.classList.add('dark');
    }
}
```

### Loading Overlay (`src/includes/loading.php`)

Provides a full-screen overlay during page loads and AJAX requests.

```javascript
// Show
window.showLoading();

// Auto-hide on load
window.addEventListener("load", () => {
    setTimeout(() => window.hideLoading(), 300);
});
```

### Status Badges

```php
$statusClass = match($status) {
    'pending'  => 'bg-yellow-100 text-yellow-800 border-yellow-200',
    'resolved' => 'bg-green-100 text-green-800 border-green-200',
    default    => 'bg-gray-100 text-gray-800 border-gray-200',
};
```

### Impact Level Badges

```php
$impactClass = match($impact) {
    'Critical' => 'bg-red-50 text-red-700 border-red-200',
    'High'     => 'bg-orange-50 text-orange-700 border-orange-200',
    'Medium'   => 'bg-yellow-50 text-yellow-700 border-yellow-200',
    'Low'      => 'bg-green-50 text-green-700 border-green-200',
    default    => 'bg-gray-50 text-gray-700 border-gray-200',
};
```

---

## ⚙️ Backend Logic

### Standard Form Processing Pattern

```php
// 1. CSRF Token Validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Invalid request");
}

// 2. Input Sanitization
$description = trim(htmlspecialchars($_POST['description'] ?? ''));
$service_id  = filter_var($_POST['service_id'], FILTER_VALIDATE_INT);

// 3. Server-Side Validation
$errors = [];
if (empty($description)) $errors[] = "Description is required.";
if (!$service_id)         $errors[] = "Invalid service.";

// 4. Database Transaction
if (empty($errors)) {
    $pdo->beginTransaction();
    try {
        // Execute queries
        $pdo->commit();
        $_SESSION['success'] = "Incident reported successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Database error: " . $e->getMessage();
    }
}
```

### Incident Resolution & Resolvers

When resolving any incident type, the system requires:
- **Root Cause**: Text input OR uploaded file (at least one required)
- **Lessons Learned**: Same requirement
- **Resolvers**: JSON array of at least one person who assisted

```php
if ($status === 'resolved') {
    $resolvers = $_POST['resolvers'] ?? [];
    $valid_resolvers = array_values(array_filter(
        array_map('trim', is_array($resolvers) ? $resolvers : [])
    ));

    if (empty($valid_resolvers)) {
        $errors[] = 'At least one resolver name is required.';
    }

    // ...
    $params[':resolvers'] = json_encode($valid_resolvers);
    $params[':resolved_at'] = date('Y-m-d H:i:s');
}
```

### File Upload Handling

```php
// Allowed types
$allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword',
                  'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

if ($_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES['attachment']['tmp_name']);

    if (!in_array($mime, $allowed_types)) {
        $errors[] = "Invalid file type.";
    } else {
        $filename   = uniqid() . '_' . basename($_FILES['attachment']['name']);
        $uploadPath = __DIR__ . '/../uploads/' . $filename;
        move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadPath);
    }
}
```

### Incident Reference Generation

```php
// Auto-generated ref format: INC-YYYYMMDD-NNNN
$prefix = match($type) {
    'security' => 'SEC',
    'fraud'    => 'FRD',
    default    => 'INC',
};
$date    = date('Ymd');
$stmt    = $pdo->query("SELECT COUNT(*) FROM incidents WHERE DATE(created_at) = CURDATE()");
$count   = $stmt->fetchColumn() + 1;
$ref     = sprintf('%s-%s-%04d', $prefix, $date, $count);
```

### Rate Limiting Implementation

```php
$rateLimitKey = 'rate_limit_' . $_SERVER['REMOTE_ADDR'];
$maxRequests  = 5;
$timeWindow   = 60; // seconds

if (isset($_SESSION[$rateLimitKey])) {
    [$count, $timestamp] = explode('|', $_SESSION[$rateLimitKey]);

    if (time() - $timestamp < $timeWindow) {
        if ($count >= $maxRequests) {
            die("Too many requests. Please try again later.");
        }
        $count++;
    } else {
        $count = 1;
    }
} else {
    $count = 1;
}

$_SESSION[$rateLimitKey] = "$count|" . time();
```

---

## 🗂️ Three Incident Types

The system manages three independent incident domains, each with their own tables, report forms, and update streams. They share the same resolution pattern (resolvers + root cause required) but differ in domain-specific fields.

| Attribute | Downtime (`incidents`) | Security (`security_incidents`) | Fraud (`fraud_incidents`) |
|-----------|------------------------|----------------------------------|---------------------------|
| Reference | `INC-YYYYMMDD-NNNN` | `SEC-YYYYMMDD-NNNN` | `FRD-YYYYMMDD-NNNN` |
| Report Page | `report.php` | `report_security.php` | `report_fraud.php` |
| Domain Fields | Service, Component, Type, Downtime Minutes | Threat Type, Systems Affected, Containment Status, Escalated To | Fraud Type, Financial Impact, Regulatory Reported |
| Updates Table | `incident_updates` | `security_incident_updates` | `fraud_incident_updates` |
| Attachments Table | `incident_attachments` | `security_incident_attachments` | `fraud_incident_attachments` |
| Affected Companies | `incident_affected_companies` (M2M) | Described in text field | N/A (per-service) |
| SLA Tracking | Yes (via `downtime_incidents`) | No | No |

### Unified Tab Interface (`incidents.php`)

The `incidents.php` page uses an Alpine.js tab switcher:

```javascript
// Alpine.js tab state
{
    activeTab: 'downtime',  // 'downtime' | 'security' | 'fraud'
    switchTab(tab) {
        this.activeTab = tab;
    }
}
```

---

## 🔌 API Endpoints

### `GET public/api/get_templates.php?service_id={id}`

Returns active incident templates filtered by service.

**Response**:
```json
[
    {
        "template_id": 1,
        "template_name": "Internet Downtime",
        "service_id": 2,
        "impact_level": "High",
        "description": "...",
        "root_cause": "..."
    }
]
```

### `POST public/api/use_template.php`

Logs template usage (increments `usage_count`) and returns full template data.

**Request**: `{ "template_id": 1 }`  
**Response**: Full template object

### `GET public/get_incident.php?id={incident_id}`

Returns full incident details (used in AJAX detail modal).

**Response**: JSON object with incident fields, updates, and attachments.

---

## 📊 Chart.js Implementation

### Shared Chart Configuration

```javascript
const defaultTooltip = {
    backgroundColor: 'rgba(0,0,0,0.8)',
    padding: 12,
    titleFont: { size: 13 },
    bodyFont: { size: 12 }
};

const defaultLegend = {
    position: 'bottom',
    labels: { padding: 15, usePointStyle: true }
};
```

### Status Distribution (Doughnut)

```javascript
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Resolved'],
        datasets: [{
            data: <?= json_encode([$pending, $resolved]) ?>,
            backgroundColor: ['#f59e0b', '#10b981'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        cutout: '60%',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: defaultLegend, tooltip: defaultTooltip }
    }
});
```

### Monthly Trend (Line)

```javascript
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'Incidents',
            data: <?= json_encode($counts) ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        scales: { y: { beginAtZero: true } },
        plugins: { legend: defaultLegend, tooltip: defaultTooltip }
    }
});
```

### Company Incidents (Bar)

```javascript
new Chart(document.getElementById('companyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($companies) ?>,
        datasets: [{
            label: 'Incidents',
            data: <?= json_encode($companyCounts) ?>,
            backgroundColor: 'rgba(99,102,241,0.7)',
            borderRadius: 4
        }]
    },
    options: {
        indexAxis: 'y', // Horizontal bar
        plugins: { legend: { display: false } }
    }
});
```

---

## 📄 PDF Generation

### TCPDF Configuration (`src/includes/pdf_config.php`)

```php
require_once __DIR__ . '/../../vendor/autoload.php';

class CustomPDF extends TCPDF {
    public function Header() {
        $this->Image('includes/logo1.png', 15, 10, 30);
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 15, 'eTranzact Downtime Report', 0, false, 'C');
        $this->Line(15, 30, 195, 30);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, false, 'C');
    }
}
```

### Analytics PDF Export

```php
$pdf = new CustomPDF('P', 'mm', 'A4', true, 'UTF-8');
$pdf->SetCreator('eTranzact');
$pdf->SetTitle('Analytics Report - ' . date('Y-m'));
$pdf->AddPage();

$html = '<table border="1" cellpadding="5">' .
    '<thead><tr style="background-color:#1e40af;color:#fff">' .
    '<th>Status</th><th>Count</th></tr></thead><tbody>';

foreach ($data as $row) {
    $html .= '<tr><td>' . htmlspecialchars($row['status']) . '</td>'
           . '<td>' . $row['count'] . '</td></tr>';
}

$html .= '</tbody></table>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('analytics_report.pdf', 'D');
```

### SLA Excel Export

```php
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="sla_report_' . date('Ymd') . '.xls"');

// Output HTML table — Excel interprets it as spreadsheet
echo '<table border="1">
    <thead>
        <tr>
            <th>Service</th><th>Company</th><th>Uptime %</th>
            <th>Downtime (min)</th><th>Incidents</th><th>Status</th>
        </tr>
    </thead>
    <tbody>';

foreach ($slaData as $row) {
    $met  = $row['uptime'] >= $row['target'];
    echo '<tr>
        <td>' . htmlspecialchars($row['service'])  . '</td>
        <td>' . htmlspecialchars($row['company'])  . '</td>
        <td>' . number_format($row['uptime'], 2)   . '%</td>
        <td>' . $row['downtime']                   . '</td>
        <td>' . $row['incidents']                  . '</td>
        <td>' . ($met ? 'Met' : 'Missed')          . '</td>
    </tr>';
}
echo '</tbody></table>';
```

---

## 🔐 Session & Security Management

### Config Settings (`config/config.php`)

```php
// Session hardening
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);    // Set to 1 for HTTPS production
ini_set('session.cookie_samesite', 'Strict');

define('SESSION_TIMEOUT', 3600);         // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('ACTIVITY_LOG_RETENTION_DAYS', 365);

// Security Headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
```

### Session Timeout Enforcement (`auth.php`)

```php
if (isset($_SESSION['last_activity'])
    && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    header("Location: /public/login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time();
```

### CSRF Token Management

```php
// Generate once per session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// In form
echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';

// Validate on submission
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Invalid CSRF token");
}
```

### Flash Messages

```php
// Set
$_SESSION['success'] = "Incident reported successfully!";
$_SESSION['error']   = "An error occurred.";

// Display and clear
if (isset($_SESSION['success'])) {
    echo '<div class="alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
    unset($_SESSION['success']);
}
```

---

## 📋 Activity Logging

### Logger Helper (`src/includes/activity_logger.php`)

```php
/**
 * Log a user action.
 *
 * @param PDO    $pdo
 * @param int    $userId
 * @param string $action      Short code e.g. 'incident_created'
 * @param string $description Human-readable description
 */
function logActivity(PDO $pdo, int $userId, string $action, string $description): void {
    $stmt = $pdo->prepare(
        "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $userId,
        $action,
        $description,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
}
```

### Usage Example

```php
// After creating an incident
logActivity($pdo, $_SESSION['user_id'], 'incident_created',
    "Created downtime incident {$ref} for service: {$service_name}");

// After login
logActivity($pdo, $user['user_id'], 'user_login',
    "User '{$username}' logged in successfully");
```

### Common Action Codes

| Code | Trigger |
|------|---------|
| `user_login` | Successful login |
| `user_logout` | Logout |
| `login_failed` | Failed login attempt |
| `incident_created` | New downtime incident |
| `incident_resolved` | Incident status → resolved |
| `incident_updated` | Update added to incident |
| `security_incident_created` | New security incident |
| `fraud_incident_created` | New fraud incident |
| `user_created` | Admin created new user |
| `user_updated` | User record modified |
| `user_deleted` | User account deleted |
| `report_exported` | PDF/Excel export generated |
| `template_used` | Incident template applied |
| `password_changed` | Password was changed |

---

## 🔧 Admin Management Modules

### Services Management (`admin_manage_services.php`)

Handles CRUD for services. All operations use transactions. Deleting a service cascades to `incidents` (FK `ON DELETE CASCADE`).

### Companies Management (`admin_manage_companies.php`)

CRUD for partner companies. Deletion cascades through `incident_affected_companies`.

### Components Management (`admin_manage_components.php`)

CRUD for service sub-components. Includes the `service_component_map` pivot table.

```php
// When adding a component, map it to services:
INSERT INTO components (name) VALUES (?);
$componentId = $pdo->lastInsertId();

foreach ($selectedServices as $serviceId) {
    $stmt->execute([$serviceId, $componentId]);  // INSERT INTO service_component_map
}
```

### Incident Types Management (`admin_manage_incident_types.php`)

CRUD for incident classification types. Uses `incident_type_service_map` pivot table.

### User Bulk Import (`user_bulk_import.php`)

Accepts CSV with columns: `username, email, full_name, role`. Passwords are auto-generated and (optionally) emailed. Sets `changed_password = 0` to force password change on first login.

---

## ⚡ Performance Optimization

### Database Indexing

Critical indexes already defined in `emptydb.sql`:

```sql
-- incidents table
idx_status, idx_actual_start_time, idx_priority, idx_incident_source, idx_resolved_at

-- activity_logs table
idx_user_id, idx_action, idx_created_at

-- incident_affected_companies (composite PK = implicit index)
-- downtime_incidents: idx_start_end (actual_start_time, actual_end_time)
-- sla_targets: unique_company_service
```

### Query Optimization Tips

1. **Always LIMIT** dashboard/list queries
2. **Avoid SELECT \*** — specify only needed columns
3. **Use EXPLAIN** to diagnose slow queries:
   ```sql
   EXPLAIN SELECT * FROM incidents WHERE status = 'pending' ORDER BY created_at DESC;
   ```
4. **Paginate** activity logs and user lists
5. **Prepared Statements** are reused by PDO's emulated prepare mode (set `ATTR_EMULATE_PREPARES => false` for true server-side prepared statements)

### Frontend Optimization

1. **Lazy Load Charts** — init only after DOM is ready
2. **Debounce Search/Filter** inputs (300ms delay before query)
3. **Minimize AJAX** — batch requests where possible
4. **Loading Overlay** — prevents double submissions and improves perceived performance

---

## 🧪 Testing Checklist

### Manual Testing

- [ ] **Login**: Auth works, session timeout enforced, failed attempts logged
- [ ] **Incident Reporting**: All three forms (Downtime, Security, Fraud) submit correctly
- [ ] **Template Loading**: Templates populate form fields via AJAX
- [ ] **File Uploads**: Attachments save to `uploads/`, blocked for invalid types
- [ ] **Incidents Page**: All three tabs display correct data, resolved incidents move to resolved section
- [ ] **Resolution Workflow**: Resolvers required, root cause required, status updates in DB
- [ ] **Analytics**: Charts render, date/company filters apply correctly, PDF exports
- [ ] **SLA Report**: Uptime percentage accurate, Excel/PDF exports download
- [ ] **Knowledge Base**: Articles list and detail page render
- [ ] **Admin Panel**: User CRUD, bulk import, template management work
- [ ] **Activity Logs**: Actions logged, filters work, CSV exports
- [ ] **Dark Mode**: Persists across all pages via localStorage
- [ ] **Responsive**: Test on mobile, tablet, desktop
- [ ] **Cross-browser**: Chrome, Firefox, Edge

### Database Tests

```sql
-- Verify trigger calculation
INSERT INTO downtime_incidents (incident_id, actual_start_time, actual_end_time)
VALUES (1, '2026-01-01 10:00:00', '2026-01-01 11:30:00');
-- Check: downtime_minutes should be 90

-- Verify incident company linking
SELECT i.incident_ref, GROUP_CONCAT(c.company_name) AS companies
FROM incidents i
JOIN incident_affected_companies iac ON i.incident_id = iac.incident_id
JOIN companies c ON iac.company_id = c.company_id
GROUP BY i.incident_id;

-- Verify SLA target coverage
SELECT s.service_name, COUNT(st.target_id) AS targets
FROM services s
LEFT JOIN sla_targets st ON s.service_id = st.service_id
GROUP BY s.service_id;
```

---

## 🚀 Deployment Checklist

### Pre-Deployment

- [ ] Set `APP_ENV` to `'production'` in `config/config.php`
- [ ] Set `session.cookie_secure = 1` in `config/config.php` (HTTPS required)
- [ ] Ensure `config/config.php` is in `.gitignore`
- [ ] Run `composer install --no-dev`
- [ ] Set file permissions: `644` for files, `755` for directories, `775` for `public/uploads/`
- [ ] Configure HTTPS/SSL certificate
- [ ] Set up automated database backups (daily recommended)
- [ ] Review and purge any test/debug data

### Post-Deployment

- [ ] Test login flow and admin access
- [ ] Submit a test incident of each type (Downtime, Security, Fraud)
- [ ] Resolve a test incident, verify resolvers saved correctly
- [ ] Verify PDF and Excel exports generate without errors
- [ ] Check `public/uploads/` is writable by Apache
- [ ] Verify activity logs are recording
- [ ] Monitor server resource usage (CPU, memory, disk)
- [ ] Set up server uptime monitoring/alerting

---

**Document Version**: 2.0.0  
**Last Updated**: April 2026  
**Maintained By**: eTranzact Development Team
