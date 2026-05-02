<?php
// ── Bootstrap ───────────────────────────────────────────────────────────────
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/includes/auth.php';
requireLogin();

// This page has been merged into incidents.php (tabbed interface).
// Permanently redirect old links to the Security tab.
header('Location: incidents.php?tab=security', true, 301);
exit;


// ── CSRF token ──────────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── POST: resolve security incident ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resolve_security') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid CSRF token.';
        header('Location: other_incidents.php?tab=security');
        exit;
    }
    $id = intval($_POST['incident_id'] ?? 0);
    if ($id > 0) {
        try {
            require_once __DIR__ . '/../src/includes/activity_logger.php';
            $stmt = $pdo->prepare("
                UPDATE security_incidents
                SET status = 'resolved', resolved_by = ?, resolved_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $id]);
            logActivity($_SESSION['user_id'], 'resolve_security_incident', "Resolved security incident ID {$id}");
            $_SESSION['success'] = 'Security incident marked as resolved.';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Could not resolve incident: ' . $e->getMessage();
        }
    }
    header('Location: other_incidents.php?tab=security');
    exit;
}

// ── POST: resolve fraud incident ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resolve_fraud') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid CSRF token.';
        header('Location: other_incidents.php?tab=fraud');
        exit;
    }
    $id = intval($_POST['incident_id'] ?? 0);
    if ($id > 0) {
        try {
            require_once __DIR__ . '/../src/includes/activity_logger.php';
            $stmt = $pdo->prepare("
                UPDATE fraud_incidents
                SET status = 'resolved', resolved_by = ?, resolved_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $id]);
            logActivity($_SESSION['user_id'], 'resolve_fraud_incident', "Resolved fraud incident ID {$id}");
            $_SESSION['success'] = 'Fraud incident marked as resolved.';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Could not resolve incident: ' . $e->getMessage();
        }
    }
    header('Location: other_incidents.php?tab=fraud');
    exit;
}

// ── Filter / pagination params ───────────────────────────────────────────────
$tab          = in_array($_GET['tab'] ?? '', ['security', 'fraud']) ? $_GET['tab'] : 'security';
$statusFilter = in_array($_GET['status'] ?? '', ['pending', 'resolved']) ? $_GET['status'] : '';
$searchFilter = trim($_GET['search'] ?? '');

$itemsPerPage = 10;
$currentPage  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset       = ($currentPage - 1) * $itemsPerPage;

// Helper: build URL preserving all filter params
function otherPageUrl(int $page, string $tab, string $status, string $search): string
{
    $p = ['tab' => $tab, 'page' => $page];
    if ($status !== '') $p['status'] = $status;
    if ($search !== '')  $p['search'] = $search;
    return '?' . http_build_query($p);
}

// ── Build WHERE for the active tab ──────────────────────────────────────────
$whereClauses  = [];
$filterParams  = [];
$tableAlias    = ($tab === 'security') ? 's' : 'f';

if ($statusFilter !== '') {
    $whereClauses[] = "{$tableAlias}.status = ?";
    $filterParams[]  = $statusFilter;
}

if ($searchFilter !== '') {
    if ($tab === 'security') {
        $whereClauses[] = "(
            s.incident_ref      LIKE ?
            OR s.threat_type    LIKE ?
            OR s.systems_affected LIKE ?
            OR s.description    LIKE ?
        )";
        $wild = '%' . $searchFilter . '%';
        array_push($filterParams, $wild, $wild, $wild, $wild);
    } else {
        $whereClauses[] = "(
            f.incident_ref   LIKE ?
            OR f.fraud_type  LIKE ?
            OR f.description LIKE ?
        )";
        $wild = '%' . $searchFilter . '%';
        array_push($filterParams, $wild, $wild, $wild);
    }
}

$whereSQL = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// ── Queries ──────────────────────────────────────────────────────────────────
$totalIncidents = 0;
$incidents      = [];

try {
    if ($tab === 'security') {
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) FROM security_incidents s
            $whereSQL
        ");
        $countStmt->execute($filterParams);
        $totalIncidents = (int) $countStmt->fetchColumn();
        $totalPages     = (int) ceil($totalIncidents / $itemsPerPage);

        $dataStmt = $pdo->prepare("
            SELECT
                s.id,
                s.incident_ref,
                s.threat_type,
                s.systems_affected,
                s.description,
                s.impact_level,
                s.priority,
                s.containment_status,
                s.escalated_to,
                s.actual_start_time,
                s.status,
                s.resolved_at,
                u.full_name AS reporter_name
            FROM security_incidents s
            JOIN users u ON s.reported_by = u.user_id
            $whereSQL
            ORDER BY
                FIELD(s.status, 'pending', 'resolved'),
                s.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $dataStmt->execute(array_merge($filterParams, [$itemsPerPage, $offset]));
        $incidents = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) FROM fraud_incidents f
            $whereSQL
        ");
        $countStmt->execute($filterParams);
        $totalIncidents = (int) $countStmt->fetchColumn();
        $totalPages     = (int) ceil($totalIncidents / $itemsPerPage);

        $dataStmt = $pdo->prepare("
            SELECT
                f.id,
                f.incident_ref,
                f.fraud_type,
                f.service_id,
                f.description,
                f.financial_impact,
                f.impact_level,
                f.priority,
                f.regulatory_reported,
                f.actual_start_time,
                f.status,
                f.resolved_at,
                u.full_name  AS reporter_name,
                sv.service_name
            FROM fraud_incidents f
            JOIN users u ON f.reported_by = u.user_id
            LEFT JOIN services sv ON f.service_id = sv.service_id
            $whereSQL
            ORDER BY
                FIELD(f.status, 'pending', 'resolved'),
                f.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $dataStmt->execute(array_merge($filterParams, [$itemsPerPage, $offset]));
        $incidents = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die('ERROR: Could not fetch incidents. ' . $e->getMessage());
}

// ── Label maps ───────────────────────────────────────────────────────────────
$threatLabels = [
    'phishing'             => 'Phishing',
    'unauthorized_access'  => 'Unauthorized Access',
    'data_breach'          => 'Data Breach',
    'malware'              => 'Malware',
    'social_engineering'   => 'Social Engineering',
    'other'                => 'Other',
];
$fraudLabels = [
    'card_fraud'         => 'Card Fraud',
    'account_takeover'   => 'Account Takeover',
    'transaction_fraud'  => 'Transaction Fraud',
    'internal_fraud'     => 'Internal Fraud',
    'other'              => 'Other',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <script>if(localStorage.getItem('theme')==='dark'||(!localStorage.getItem('theme')&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark')}else{document.documentElement.classList.remove('dark')}</script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security & Fraud Incidents - ETZ Downtime</title>

    <!-- Tailwind CSS v3 -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>

    <!-- Alpine.js v3 -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome 6.5.1 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Inter', sans-serif; }
        .incident-card { transition: box-shadow 0.15s ease; }
        .incident-card:hover { box-shadow: 0 4px 6px -1px rgba(0,0,0,0.08), 0 2px 4px -1px rgba(0,0,0,0.04); }
    </style>
</head>

<body class="relative min-h-screen">

    <!-- Background -->
    <div class="fixed inset-0 z-0">
        <img src="<?= url('assets/mainbg.jpg') ?>" alt="Background" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-white/90 dark:bg-gray-900/95"></div>
    </div>

    <!-- Content Wrapper -->
    <div class="relative z-10">
        <?php include __DIR__ . '/../src/includes/navbar.php'; ?>
        <?php include __DIR__ . '/../src/includes/loading.php'; ?>

        <main class="py-8">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Flash: success -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-r-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700 dark:text-green-300"><?= htmlspecialchars($_SESSION['success']) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <!-- Flash: error -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="mb-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4 rounded-r-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700 dark:text-red-300"><?= htmlspecialchars($_SESSION['error']) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Page Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Security &amp; Fraud Incidents</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Information security threats and fraud reports</p>
                </div>

                <!-- Tab switcher -->
                <?php
                    $secTabUrl   = '?' . http_build_query(array_filter(['tab' => 'security', 'status' => $statusFilter, 'search' => $searchFilter], fn($v) => $v !== ''));
                    $fraudTabUrl = '?' . http_build_query(array_filter(['tab' => 'fraud',    'status' => $statusFilter, 'search' => $searchFilter], fn($v) => $v !== ''));
                ?>
                <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6">
                    <a href="<?= $secTabUrl ?>"
                       class="px-5 py-3 text-sm font-medium -mb-px transition-colors
                              <?= $tab === 'security'
                                    ? 'border-b-2 border-red-600 text-red-600 dark:text-red-400 dark:border-red-400'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' ?>">
                        <i class="fas fa-shield-halved mr-1.5"></i>Security Incidents
                    </a>
                    <a href="<?= $fraudTabUrl ?>"
                       class="px-5 py-3 text-sm font-medium -mb-px transition-colors
                              <?= $tab === 'fraud'
                                    ? 'border-b-2 border-amber-500 text-amber-600 dark:text-amber-400 dark:border-amber-400'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' ?>">
                        <i class="fas fa-triangle-exclamation mr-1.5"></i>Fraud Incidents
                    </a>
                </div>

                <!-- Filter bar -->
                <div class="mb-5 flex flex-col sm:flex-row gap-3">
                    <!-- Search -->
                    <div class="flex-1">
                        <form method="GET" action="other_incidents.php" id="searchForm">
                            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                            <?php if ($statusFilter): ?>
                                <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                            <?php endif; ?>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <input type="text" name="search" id="incident-search"
                                    placeholder="<?= $tab === 'security' ? 'Search by ref, threat type, systems or description…' : 'Search by ref, fraud type or description…' ?>"
                                    value="<?= htmlspecialchars($searchFilter) ?>"
                                    onchange="document.getElementById('searchForm').submit()"
                                    class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent sm:text-sm">
                                <?php if ($searchFilter): ?>
                                    <a href="?tab=<?= urlencode($tab) ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : '' ?>"
                                       class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <!-- Status toggle -->
                    <div class="flex items-center">
                        <div class="inline-flex rounded-lg shadow-sm" role="group">
                            <a href="?<?= http_build_query(array_filter(['tab' => $tab, 'search' => $searchFilter], fn($v) => $v !== '')) ?>"
                               class="px-4 py-2.5 text-sm font-medium rounded-l-lg border border-gray-200 dark:border-gray-600 transition-colors duration-150 flex items-center
                                      <?= $statusFilter === '' ? 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' ?>">
                                <i class="fas fa-list-ul mr-2 <?= $statusFilter === '' ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500' ?>"></i>All
                            </a>
                            <a href="?<?= http_build_query(array_filter(['tab' => $tab, 'status' => 'pending', 'search' => $searchFilter], fn($v) => $v !== '')) ?>"
                               class="px-4 py-2.5 text-sm font-medium border-t border-b border-gray-200 dark:border-gray-600 transition-colors duration-150 flex items-center
                                      <?= $statusFilter === 'pending' ? 'bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300 dark:border-yellow-700' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-yellow-50 dark:hover:bg-gray-600' ?>">
                                <i class="fas fa-clock mr-2 text-yellow-500"></i>Pending
                            </a>
                            <a href="?<?= http_build_query(array_filter(['tab' => $tab, 'status' => 'resolved', 'search' => $searchFilter], fn($v) => $v !== '')) ?>"
                               class="px-4 py-2.5 text-sm font-medium rounded-r-lg border border-gray-200 dark:border-gray-600 transition-colors duration-150 flex items-center
                                      <?= $statusFilter === 'resolved' ? 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-700' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-600' ?>">
                                <i class="fas fa-check-circle mr-2 text-green-500"></i>Resolved
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Results summary -->
                <div class="mb-4 flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <?php if ($totalIncidents === 0): ?>
                            No incidents found<?= $searchFilter ? ' matching "<strong class="text-gray-700 dark:text-gray-300">' . htmlspecialchars($searchFilter) . '</strong>"' : '' ?>
                        <?php else: ?>
                            Showing <strong class="text-gray-900 dark:text-white"><?= number_format($offset + 1) ?>–<?= number_format(min($offset + $itemsPerPage, $totalIncidents)) ?></strong>
                            of <strong class="text-gray-900 dark:text-white"><?= number_format($totalIncidents) ?></strong>
                            <?= $statusFilter ? ucfirst($statusFilter) . ' ' : '' ?><?= $tab === 'security' ? 'security' : 'fraud' ?> incidents
                            <?= $searchFilter ? ' matching "<strong class="text-gray-900 dark:text-white">' . htmlspecialchars($searchFilter) . '</strong>"' : '' ?>
                        <?php endif; ?>
                    </p>
                    <?php if ($statusFilter || $searchFilter): ?>
                        <a href="?tab=<?= urlencode($tab) ?>" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium">
                            <i class="fas fa-times mr-1"></i>Clear filters
                        </a>
                    <?php endif; ?>
                </div>

                <!-- ═══════════════════════════════════════════════════════════
                     INCIDENT CARDS
                     ═══════════════════════════════════════════════════════════ -->
                <div class="space-y-4">

                    <?php if (empty($incidents)): ?>
                        <div class="text-center py-16">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No incidents found</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                <?= $searchFilter || $statusFilter ? 'Try adjusting your filters.' : 'No ' . ($tab === 'security' ? 'security' : 'fraud') . ' incidents have been reported yet.' ?>
                            </p>
                        </div>

                    <?php else: ?>
                        <?php foreach ($incidents as $inc):

                            // ── shared badge maps ────────────────────────────────
                            $impactKey = strtolower($inc['impact_level'] ?? 'low');
                            $impactBadge = [
                                'critical' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                'high'     => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                'medium'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                'low'      => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                            ][$impactKey] ?? 'bg-gray-100 text-gray-700';

                            $priorityKey = strtolower($inc['priority'] ?? 'medium');
                            $priorityBadge = [
                                'urgent' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                'high'   => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                'medium' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                'low'    => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                            ][$priorityKey] ?? 'bg-gray-100 text-gray-700';

                            $statusBadge = $inc['status'] === 'pending'
                                ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
                                : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';

                            $borderAccent = ($tab === 'security') ? 'border-l-red-500' : 'border-l-amber-500';
                        ?>

                        <!-- ── CARD ────────────────────────────────────────────── -->
                        <div class="incident-card bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm mb-4 p-5 border-l-4 <?= $borderAccent ?> overflow-hidden">

                            <!-- Top row: ref + badges + status -->
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-3">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <!-- Reference badge -->
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-mono font-semibold
                                        <?= $tab === 'security' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' ?>">
                                        <i class="fas fa-hashtag text-[9px]"></i><?= htmlspecialchars($inc['incident_ref']) ?>
                                    </span>

                                    <!-- Impact badge -->
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium <?= $impactBadge ?>">
                                        <?= ucfirst($impactKey) ?> Impact
                                    </span>

                                    <!-- Priority badge -->
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium <?= $priorityBadge ?>">
                                        <?= ucfirst($priorityKey) ?> Priority
                                    </span>

                                    <?php if ($tab === 'security'): ?>
                                        <!-- Containment badge -->
                                        <?php
                                            $containment = $inc['containment_status'] ?? '';
                                            $containBadge = match($containment) {
                                                'contained'          => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                'ongoing'            => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                'under_investigation' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                default              => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                            };
                                            $containLabel = match($containment) {
                                                'contained'          => 'Contained',
                                                'ongoing'            => 'Ongoing',
                                                'under_investigation' => 'Under Investigation',
                                                default              => ucfirst(str_replace('_', ' ', $containment)),
                                            };
                                        ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium <?= $containBadge ?>">
                                            <?= htmlspecialchars($containLabel) ?>
                                        </span>

                                    <?php else: ?>
                                        <!-- Regulatory badge -->
                                        <?php if (!empty($inc['regulatory_reported'])): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                                <i class="fas fa-landmark mr-1 text-[9px]"></i>Regulatory
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <!-- Status badge -->
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium border
                                    <?= $inc['status'] === 'pending'
                                        ? 'bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-700'
                                        : 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-700' ?>">
                                    <i class="fas <?= $inc['status'] === 'pending' ? 'fa-clock' : 'fa-check-circle' ?> mr-1.5 text-[10px]"></i>
                                    <?= $inc['status'] === 'pending' ? 'Pending' : 'Resolved' ?>
                                </span>
                            </div>

                            <!-- Middle row: type, service/date/reporter -->
                            <div class="mb-3">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-600 dark:text-gray-400">
                                    <?php if ($tab === 'security'): ?>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">
                                            <i class="fas fa-shield-halved mr-1 text-red-500"></i>
                                            <?= htmlspecialchars($threatLabels[$inc['threat_type']] ?? ucfirst(str_replace('_', ' ', $inc['threat_type'] ?? ''))) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">
                                            <i class="fas fa-triangle-exclamation mr-1 text-amber-500"></i>
                                            <?= htmlspecialchars($fraudLabels[$inc['fraud_type']] ?? ucfirst(str_replace('_', ' ', $inc['fraud_type'] ?? ''))) ?>
                                        </span>
                                        <?php if (!empty($inc['service_name'])): ?>
                                            <span class="text-gray-400 dark:text-gray-500">·</span>
                                            <span><i class="fas fa-server mr-1 text-gray-400"></i><?= htmlspecialchars($inc['service_name']) ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if (!empty($inc['actual_start_time'])): ?>
                                        <span class="text-gray-400 dark:text-gray-500">·</span>
                                        <span><i class="fas fa-calendar mr-1 text-gray-400"></i><?= date('M j, Y g:i A', strtotime($inc['actual_start_time'])) ?></span>
                                    <?php endif; ?>

                                    <span class="text-gray-400 dark:text-gray-500">·</span>
                                    <span><i class="fas fa-user mr-1 text-gray-400"></i><?= htmlspecialchars($inc['reporter_name'] ?? 'Unknown') ?></span>
                                </div>
                            </div>

                            <!-- Body: description / systems affected / financial impact -->
                            <div class="mb-4 space-y-2">
                                <?php if (!empty($inc['description'])): ?>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        <?= htmlspecialchars(mb_strlen($inc['description']) > 120 ? mb_substr($inc['description'], 0, 120) . '…' : $inc['description']) ?>
                                    </p>
                                <?php endif; ?>

                                <?php if ($tab === 'security' && !empty($inc['systems_affected'])): ?>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <span class="font-medium text-gray-600 dark:text-gray-300">Systems affected:</span>
                                        <?= htmlspecialchars(mb_strlen($inc['systems_affected']) > 120 ? mb_substr($inc['systems_affected'], 0, 120) . '…' : $inc['systems_affected']) ?>
                                    </p>
                                <?php endif; ?>

                                <?php if ($tab === 'fraud' && isset($inc['financial_impact']) && $inc['financial_impact'] !== null && $inc['financial_impact'] !== ''): ?>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <span class="font-medium text-gray-600 dark:text-gray-300">Financial impact:</span>
                                        <span class="font-semibold text-amber-700 dark:text-amber-400">
                                            GH₵ <?= number_format((float)$inc['financial_impact'], 2) ?>
                                        </span>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <!-- Bottom row: resolve button or resolved-at -->
                            <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                    <?php if (!empty($inc['actual_start_time'])): ?>
                                        Reported <?= date('M j, Y', strtotime($inc['actual_start_time'])) ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php if ($inc['status'] === 'pending'): ?>
                                        <button type="button"
                                            onclick="showResolveModal(<?= (int)$inc['id'] ?>, '<?= $tab === 'security' ? 'resolve_security' : 'resolve_fraud' ?>')"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors
                                                <?= $tab === 'security'
                                                    ? 'bg-white dark:bg-gray-700 border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20'
                                                    : 'bg-white dark:bg-gray-700 border-amber-300 dark:border-amber-700 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20' ?>">
                                            <i class="fas fa-check mr-1.5"></i>Mark as Resolved
                                        </button>
                                    <?php else: ?>
                                        <span class="text-xs text-green-600 dark:text-green-400 font-medium">
                                            <i class="fas fa-check-circle mr-1"></i>Resolved
                                            <?= !empty($inc['resolved_at']) ? 'at ' . date('M j, Y g:i A', strtotime($inc['resolved_at'])) : '' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div><!-- /card -->

                        <?php endforeach; ?>
                    <?php endif; ?>

                </div><!-- /space-y-4 -->

                <!-- ── Pagination ────────────────────────────────────────────── -->
                <?php if (isset($totalPages) && $totalPages > 1): ?>
                    <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage   = min($totalPages, $currentPage + 2);
                    ?>
                    <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4 w-fit mx-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-6 py-3 shadow-sm">
                        <p class="text-sm text-gray-500 dark:text-gray-400 order-2 sm:order-1">
                            Showing
                            <span class="font-semibold text-gray-700 dark:text-gray-300"><?= min($offset + 1, $totalIncidents) ?></span>
                            –
                            <span class="font-semibold text-gray-700 dark:text-gray-300"><?= min($offset + $itemsPerPage, $totalIncidents) ?></span>
                            of
                            <span class="font-semibold text-gray-700 dark:text-gray-300"><?= $totalIncidents ?></span>
                            incidents
                        </p>

                        <div class="flex items-center gap-1 order-1 sm:order-2">
                            <!-- Prev -->
                            <?php if ($currentPage > 1): ?>
                                <a href="<?= otherPageUrl($currentPage - 1, $tab, $statusFilter, $searchFilter) ?>"
                                   class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                                    <i class="fas fa-chevron-left text-xs"></i>
                                </a>
                            <?php else: ?>
                                <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-300 dark:text-gray-600 cursor-not-allowed">
                                    <i class="fas fa-chevron-left text-xs"></i>
                                </span>
                            <?php endif; ?>

                            <!-- First page + ellipsis -->
                            <?php if ($startPage > 1): ?>
                                <a href="<?= otherPageUrl(1, $tab, $statusFilter, $searchFilter) ?>"
                                   class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">1</a>
                                <?php if ($startPage > 2): ?>
                                    <span class="inline-flex items-center justify-center w-9 h-9 text-sm text-gray-400">…</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- Page numbers -->
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <?php if ($i === $currentPage): ?>
                                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-600 text-white text-sm font-semibold shadow-sm"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="<?= otherPageUrl($i, $tab, $statusFilter, $searchFilter) ?>"
                                       class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <!-- Ellipsis + last page -->
                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <span class="inline-flex items-center justify-center w-9 h-9 text-sm text-gray-400">…</span>
                                <?php endif; ?>
                                <a href="<?= otherPageUrl($totalPages, $tab, $statusFilter, $searchFilter) ?>"
                                   class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"><?= $totalPages ?></a>
                            <?php endif; ?>

                            <!-- Next -->
                            <?php if ($currentPage < $totalPages): ?>
                                <a href="<?= otherPageUrl($currentPage + 1, $tab, $statusFilter, $searchFilter) ?>"
                                   class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                                    <i class="fas fa-chevron-right text-xs"></i>
                                </a>
                            <?php else: ?>
                                <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-300 dark:text-gray-600 cursor-not-allowed">
                                    <i class="fas fa-chevron-right text-xs"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div><!-- /max-w-5xl -->
        </main>
    </div><!-- /relative z-10 -->

    <!-- ═══════════════════════════════════════════════════════════════════════
         Resolve Confirmation Modal
         ═══════════════════════════════════════════════════════════════════════ -->
    <div id="resolveModal"
         class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-sm w-full mx-4 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <i class="fas fa-check text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Mark as Resolved</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">This action cannot be undone.</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-5">
                Are you sure you want to mark this incident as resolved? The current timestamp will be recorded as the resolution time.
            </p>
            <form method="POST" action="other_incidents.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" id="resolve_action" value="">
                <input type="hidden" name="incident_id" id="resolve_incident_id" value="">
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="hideResolveModal()"
                            class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium rounded-lg bg-green-600 hover:bg-green-700 text-white transition-colors shadow-sm">
                        <i class="fas fa-check mr-1.5"></i>Confirm Resolve
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showResolveModal(id, actionType) {
            document.getElementById('resolve_action').value = actionType;
            document.getElementById('resolve_incident_id').value = id;
            document.getElementById('resolveModal').classList.remove('hidden');
        }

        function hideResolveModal() {
            document.getElementById('resolveModal').classList.add('hidden');
            document.getElementById('resolve_action').value = '';
            document.getElementById('resolve_incident_id').value = '';
        }

        // Close modal on backdrop click
        document.getElementById('resolveModal').addEventListener('click', function(e) {
            if (e.target === this) hideResolveModal();
        });

        // Search on Enter key
        document.getElementById('incident-search').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('searchForm').submit();
            }
        });
    </script>

</body>
</html>
