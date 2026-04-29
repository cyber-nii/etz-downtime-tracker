<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/includes/auth.php';
requireLogin();

// ── KPI Queries ────────────────────────────────────────────────────────────
try {
    // ── All-time counts ──────────────────────────────────────────────────
    $total    = $pdo->query("SELECT COUNT(*) FROM incidents")->fetchColumn();
    $resolved = $pdo->query("SELECT COUNT(*) FROM incidents WHERE status = 'resolved'")->fetchColumn();
    $pending  = $pdo->query("SELECT COUNT(*) FROM incidents WHERE status = 'pending'")->fetchColumn();

    // ── This month counts ─────────────────────────────────────────────────
    $thisMonthTotal = $pdo->query("
        SELECT COUNT(*) FROM incidents
        WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
    ")->fetchColumn();

    $thisMonthResolved = $pdo->query("
        SELECT COUNT(*) FROM incidents
        WHERE status = 'resolved'
          AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
    ")->fetchColumn();

    // ── Last month for trend comparison ──────────────────────────────────
    $lastMonthTotal = $pdo->query("
        SELECT COUNT(*) FROM incidents
        WHERE MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
          AND YEAR(created_at)  = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
    ")->fetchColumn();

    // ── Critical incidents ────────────────────────────────────────────────
    $critical = $pdo->query("
        SELECT COUNT(*) FROM incidents WHERE impact_level = 'Critical' AND status = 'pending'
    ")->fetchColumn();

    // ── Avg resolution time (minutes → hours) ────────────────────────────
    $avgMinutes = $pdo->query("
        SELECT AVG(di.downtime_minutes)
        FROM downtime_incidents di
        WHERE di.downtime_minutes IS NOT NULL AND di.downtime_minutes > 0
    ")->fetchColumn();
    $avgResolutionHours = $avgMinutes ? round($avgMinutes / 60, 1) : null;

    // ── Total downtime this month (hours) ─────────────────────────────────
    $downtimeThisMonth = $pdo->query("
        SELECT COALESCE(SUM(di.downtime_minutes), 0)
        FROM downtime_incidents di
        JOIN incidents i ON di.incident_id = i.incident_id
        WHERE MONTH(i.created_at) = MONTH(CURDATE()) AND YEAR(i.created_at) = YEAR(CURDATE())
    ")->fetchColumn();
    $downtimeHours = round($downtimeThisMonth / 60, 1);

    // ── Service breakdown ─────────────────────────────────────────────────
    $serviceBreakdown = $pdo->query("
        SELECT
            s.service_name,
            COUNT(i.incident_id)                                                  AS total,
            SUM(CASE WHEN i.status = 'pending'  THEN 1 ELSE 0 END)               AS pending,
            SUM(CASE WHEN i.status = 'resolved' THEN 1 ELSE 0 END)               AS resolved,
            SUM(CASE WHEN i.impact_level = 'Critical' AND i.status = 'pending' THEN 1 ELSE 0 END) AS critical,
            COALESCE(SUM(di.downtime_minutes), 0)                                  AS downtime_mins
        FROM services s
        LEFT JOIN incidents i  ON s.service_id = i.service_id
        LEFT JOIN downtime_incidents di ON i.incident_id = di.incident_id
        GROUP BY s.service_id, s.service_name
        ORDER BY total DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // ── Open critical / high incidents (highlight panel) ──────────────────
    $urgentIncidents = $pdo->query("
        SELECT
            i.incident_id,
            i.incident_ref,
            i.impact_level,
            i.description,
            i.created_at,
            s.service_name
        FROM incidents i
        JOIN services s ON i.service_id = s.service_id
        WHERE i.status = 'pending' AND i.impact_level IN ('Critical','High')
        ORDER BY FIELD(i.impact_level,'Critical','High'), i.created_at ASC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // ── Recent incidents (table) ──────────────────────────────────────────
    $recent_incidents = $pdo->query("
        SELECT
            s.service_name,
            s.service_id,
            i.incident_id,
            i.incident_ref,
            i.impact_level,
            i.status,
            GROUP_CONCAT(DISTINCT c.company_name ORDER BY c.company_name) AS company_names,
            i.created_at  AS date_reported,
            i.resolved_at AS date_resolved
        FROM incidents i
        JOIN services s ON i.service_id = s.service_id
        LEFT JOIN incident_affected_companies iac ON i.incident_id = iac.incident_id
        LEFT JOIN companies c ON iac.company_id = c.company_id
        GROUP BY i.incident_id
        ORDER BY i.created_at DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // ── Month trend ───────────────────────────────────────────────────────
    $monthTrend = $lastMonthTotal > 0
        ? round((($thisMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100, 1)
        : null;

} catch (PDOException $e) {
    die("ERROR: Could not fetch data. " . $e->getMessage());
}

$resolutionRate = $total > 0 ? round(($resolved / $total) * 100, 1) : 0;
$currentMonth   = date('F Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script>if(localStorage.getItem('theme')==='dark'||(!localStorage.getItem('theme')&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark')}else{document.documentElement.classList.remove('dark')}</script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eTranzact — Downtime Dashboard</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Inter', sans-serif; }

        .kpi-card {
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px -4px rgba(0,0,0,0.10);
        }

        .table-row-hover { transition: background-color 0.15s ease; }
        .table-row-hover:hover { background-color: #f9fafb; }
        .dark .table-row-hover:hover { background-color: #374151; }

        /* Progress bar shimmer */
        @keyframes shimmer {
            from { background-position: -200% center; }
            to   { background-position:  200% center; }
        }
        .progress-fill {
            transition: width 0.8s cubic-bezier(.4,0,.2,1);
        }

        /* Pulse for critical badge */
        @keyframes pulse-red {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.6; }
        }
        .pulse-critical { animation: pulse-red 1.8s ease-in-out infinite; }
    </style>
</head>

<body class="relative min-h-screen">
    <!-- Background -->
    <div class="fixed inset-0 z-0">
        <img src="<?= url('assets/mainbg.jpg') ?>" alt="Background" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-white/90 dark:bg-gray-900/95"></div>
    </div>

    <div class="relative z-10">
    <?php include __DIR__ . '/../src/includes/navbar.php'; ?>
    <?php include __DIR__ . '/../src/includes/loading.php'; ?>

    <main class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- ── Page Header ─────────────────────────────────────────── -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Live incident intelligence — <?= $currentMonth ?>
                    </p>
                </div>
                <div class="mt-4 sm:mt-0 flex items-center gap-3">
                    <span class="text-xs text-gray-400 dark:text-gray-500" id="last-updated">
                        Updated <?php echo date('g:i A'); ?>
                    </span>
                    <button onclick="refreshDashboard()"
                        class="inline-flex items-center gap-2 px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <i class="fas fa-rotate-right text-gray-400"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- ── Row 1: 5 Primary KPI Cards ─────────────────────────── -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">

                <!-- Total Incidents -->
                <div class="kpi-card col-span-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Total Incidents</p>
                            <p class="mt-2 text-4xl font-bold text-gray-900 dark:text-white"><?= number_format($total) ?></p>
                        </div>
                        <div class="w-11 h-11 bg-blue-50 dark:bg-blue-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-layer-group text-blue-600 dark:text-blue-400 text-lg"></i>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">All time across all services</p>
                </div>

                <!-- This Month -->
                <div class="kpi-card col-span-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">This Month</p>
                            <p class="mt-2 text-4xl font-bold text-gray-900 dark:text-white"><?= number_format($thisMonthTotal) ?></p>
                        </div>
                        <div class="w-11 h-11 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-calendar-day text-indigo-600 dark:text-indigo-400 text-lg"></i>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5">
                        <?php if ($monthTrend !== null): ?>
                            <?php if ($monthTrend > 0): ?>
                                <span class="text-xs font-semibold text-red-500"><i class="fas fa-arrow-up mr-0.5"></i><?= abs($monthTrend) ?>%</span>
                                <span class="text-xs text-gray-400">vs last month</span>
                            <?php elseif ($monthTrend < 0): ?>
                                <span class="text-xs font-semibold text-green-500"><i class="fas fa-arrow-down mr-0.5"></i><?= abs($monthTrend) ?>%</span>
                                <span class="text-xs text-gray-400">vs last month</span>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">Same as last month</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-xs text-gray-400">First recorded month</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pending -->
                <div class="kpi-card col-span-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Open / Pending</p>
                            <p class="mt-2 text-4xl font-bold <?= $pending > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white' ?>"><?= number_format($pending) ?></p>
                        </div>
                        <div class="w-11 h-11 bg-amber-50 dark:bg-amber-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-hourglass-half text-amber-500 dark:text-amber-400 text-lg"></i>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">Awaiting resolution</p>
                </div>

                <!-- Critical Open -->
                <div class="kpi-card col-span-1 bg-white dark:bg-gray-800 border border-<?= $critical > 0 ? 'red-200 dark:border-red-900/50' : 'gray-200 dark:border-gray-700' ?> rounded-xl p-5 shadow-sm <?= $critical > 0 ? 'bg-red-50/30 dark:bg-red-900/10' : '' ?>">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Critical Open</p>
                            <p class="mt-2 text-4xl font-bold <?= $critical > 0 ? 'text-red-600 dark:text-red-400 pulse-critical' : 'text-gray-900 dark:text-white' ?>"><?= number_format($critical) ?></p>
                        </div>
                        <div class="w-11 h-11 <?= $critical > 0 ? 'bg-red-100 dark:bg-red-900/30' : 'bg-gray-50 dark:bg-gray-700' ?> rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-triangle-exclamation <?= $critical > 0 ? 'text-red-500' : 'text-gray-400' ?> text-lg"></i>
                        </div>
                    </div>
                    <p class="mt-3 text-xs <?= $critical > 0 ? 'text-red-500 dark:text-red-400 font-medium' : 'text-gray-400' ?>">
                        <?= $critical > 0 ? 'Requires immediate attention' : 'No critical incidents open' ?>
                    </p>
                </div>

                <!-- Resolution Rate -->
                <div class="kpi-card col-span-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Resolution Rate</p>
                            <p class="mt-2 text-4xl font-bold <?= $resolutionRate >= 90 ? 'text-green-600 dark:text-green-400' : ($resolutionRate >= 70 ? 'text-amber-600' : 'text-red-600') ?>"><?= $resolutionRate ?>%</p>
                        </div>
                        <div class="w-11 h-11 bg-green-50 dark:bg-green-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-circle-check text-green-500 dark:text-green-400 text-lg"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                            <div class="progress-fill h-1.5 rounded-full <?= $resolutionRate >= 90 ? 'bg-green-500' : ($resolutionRate >= 70 ? 'bg-amber-500' : 'bg-red-500') ?>"
                                style="width: <?= min($resolutionRate, 100) ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Row 2: Avg Resolution + Downtime + Month Resolved ───── -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                <!-- Avg Resolution Time -->
                <div class="kpi-card bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm flex items-center gap-4">
                    <div class="w-14 h-14 bg-purple-50 dark:bg-purple-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-stopwatch text-purple-600 dark:text-purple-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Avg Resolution Time</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                            <?= $avgResolutionHours !== null ? $avgResolutionHours . ' hrs' : '—' ?>
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Across all resolved incidents</p>
                    </div>
                </div>

                <!-- Downtime This Month -->
                <div class="kpi-card bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm flex items-center gap-4">
                    <div class="w-14 h-14 bg-rose-50 dark:bg-rose-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-chart-line text-rose-600 dark:text-rose-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Downtime This Month</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?= $downtimeHours ?> hrs</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Total logged downtime</p>
                    </div>
                </div>

                <!-- Resolved This Month -->
                <div class="kpi-card bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm flex items-center gap-4">
                    <div class="w-14 h-14 bg-teal-50 dark:bg-teal-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check-double text-teal-600 dark:text-teal-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Resolved This Month</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($thisMonthResolved) ?></p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            <?= $thisMonthTotal > 0 ? round(($thisMonthResolved / $thisMonthTotal) * 100, 1) : 0 ?>% of this month's incidents
                        </p>
                    </div>
                </div>
            </div>

            <!-- ── Row 3: Service Breakdown + Urgent Incidents ─────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Service Breakdown (2/3 width) -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Service Incident Breakdown</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Incidents &amp; downtime by service</p>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-50 dark:divide-gray-700/50">
                        <?php if (empty($serviceBreakdown)): ?>
                            <p class="px-6 py-8 text-sm text-gray-400 text-center">No services configured</p>
                        <?php else: ?>
                            <?php
                            $maxTotal = max(array_column($serviceBreakdown, 'total')) ?: 1;
                            foreach ($serviceBreakdown as $svc):
                                $pct     = $maxTotal > 0 ? round(($svc['total'] / $maxTotal) * 100) : 0;
                                $dtHours = round($svc['downtime_mins'] / 60, 1);
                                $resRate = $svc['total'] > 0 ? round(($svc['resolved'] / $svc['total']) * 100) : 0;
                            ?>
                            <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2.5">
                                        <span class="text-sm font-semibold text-gray-800 dark:text-white">
                                            <?= htmlspecialchars($svc['service_name']) ?>
                                        </span>
                                        <?php if ($svc['critical'] > 0): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400 pulse-critical">
                                                <i class="fas fa-circle-dot text-[8px]"></i> <?= $svc['critical'] ?> Critical
                                            </span>
                                        <?php elseif ($svc['pending'] > 0): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                                <?= $svc['pending'] ?> Open
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                <i class="fas fa-check text-[9px]"></i> All Clear
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                        <span><span class="font-semibold text-gray-700 dark:text-gray-300"><?= $svc['total'] ?></span> incidents</span>
                                        <span><span class="font-semibold text-gray-700 dark:text-gray-300"><?= $dtHours ?></span> hrs downtime</span>
                                        <span class="<?= $resRate >= 90 ? 'text-green-600 font-semibold' : ($resRate >= 70 ? 'text-amber-500 font-semibold' : 'text-gray-400') ?>"><?= $resRate ?>% resolved</span>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                                    <div class="progress-fill h-1.5 rounded-full <?= $svc['critical'] > 0 ? 'bg-red-500' : ($svc['pending'] > 0 ? 'bg-amber-500' : 'bg-green-500') ?>"
                                        style="width: <?= $pct ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Urgent Incidents (1/3 width) -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                        <?php if ($critical > 0): ?>
                            <span class="w-2 h-2 rounded-full bg-red-500 pulse-critical"></span>
                        <?php endif; ?>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Open Critical &amp; High</h3>
                    </div>
                    <div class="divide-y divide-gray-50 dark:divide-gray-700/50">
                        <?php if (empty($urgentIncidents)): ?>
                            <div class="px-5 py-10 flex flex-col items-center justify-center text-center gap-2">
                                <div class="w-12 h-12 bg-green-50 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                                    <i class="fas fa-shield-check text-green-500 text-xl"></i>
                                </div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">All Clear</p>
                                <p class="text-xs text-gray-400">No critical or high incidents open</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($urgentIncidents as $u):
                                $isCrit = $u['impact_level'] === 'Critical';
                            ?>
                            <div class="px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-[10px] font-bold <?= $isCrit ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400' : 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' ?>">
                                            <?= $isCrit ? '!' : '▲' ?>
                                        </span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300"><?= htmlspecialchars($u['service_name']) ?></span>
                                            <?php if ($u['incident_ref']): ?>
                                                <span class="text-[10px] text-gray-400"><?= htmlspecialchars($u['incident_ref']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate" title="<?= htmlspecialchars($u['description'] ?? '') ?>">
                                            <?= htmlspecialchars(mb_substr($u['description'] ?? 'No description', 0, 60)) ?><?= strlen($u['description'] ?? '') > 60 ? '…' : '' ?>
                                        </p>
                                        <p class="text-[10px] text-gray-400 mt-1">
                                            <i class="fas fa-clock mr-1"></i><?= date('M j, g:i A', strtotime($u['created_at'])) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/30">
                                <a href="<?= url('incidents.php') ?>" class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                                    View all open incidents <i class="fas fa-arrow-right text-[10px]"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ── Row 4: Recent Incidents Table ───────────────────────── -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recent Incidents</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Latest 10 across all services</p>
                    </div>
                    <a href="<?= url('incidents.php') ?>"
                        class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                        View all <i class="fas fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ref / Service</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Impact</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">Affected Companies</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden sm:table-cell">Reported</th>
                                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-50 dark:divide-gray-700/50">
                            <?php if (empty($recent_incidents)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <i class="fas fa-inbox text-gray-300 text-4xl mb-3 block"></i>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">No incidents reported yet</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_incidents as $inc):
                                    $impactColors = [
                                        'Critical' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                        'High'     => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                        'Medium'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'Low'      => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                    ][$inc['impact_level']] ?? 'bg-gray-100 text-gray-700';

                                    $statusColors = [
                                        'pending'  => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
                                        'resolved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                    ][$inc['status']] ?? 'bg-gray-100 text-gray-700';

                                    $companies     = !empty($inc['company_names']) ? explode(',', $inc['company_names']) : [];
                                    $extraCount    = max(0, count($companies) - 3);
                                ?>
                                <tr class="table-row-hover">
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($inc['service_name']) ?></div>
                                        <?php if ($inc['incident_ref']): ?>
                                            <div class="text-[11px] text-gray-400 font-mono mt-0.5"><?= htmlspecialchars($inc['incident_ref']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $impactColors ?>">
                                            <?= htmlspecialchars($inc['impact_level']) ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 hidden md:table-cell">
                                        <?php if (empty($companies)): ?>
                                            <span class="text-xs text-gray-400 italic">—</span>
                                        <?php else: ?>
                                            <div class="flex flex-wrap gap-1">
                                                <?php foreach (array_slice($companies, 0, 3) as $co): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300 border border-blue-100 dark:border-blue-800">
                                                        <?= htmlspecialchars(trim($co)) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                                <?php if ($extraCount > 0): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">+<?= $extraCount ?> more</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap hidden sm:table-cell">
                                        <div class="text-sm text-gray-700 dark:text-gray-300"><?= date('M j, Y', strtotime($inc['date_reported'])) ?></div>
                                        <div class="text-xs text-gray-400"><?= date('g:i A', strtotime($inc['date_reported'])) ?></div>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold <?= $statusColors ?>">
                                            <i class="fas <?= $inc['status'] === 'resolved' ? 'fa-check-circle' : 'fa-clock' ?> text-[10px]"></i>
                                            <?= ucfirst($inc['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- /max-w-7xl -->
    </main>

    <script>
        function refreshDashboard() {
            showLoading('Refreshing dashboard...', 'Fetching latest data');
            setTimeout(() => location.reload(), 300);
        }
        // Update timestamp on load
        (() => {
            const el = document.getElementById('last-updated');
            if (el) el.textContent = 'Updated ' + new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
        })();
    </script>
    </div><!-- /Content Wrapper -->
</body>
</html>