<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/includes/auth.php';
require_once __DIR__ . '/../../src/includes/activity_logger.php';
requireLogin();
requireRole('admin');

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filters = [
        'user_id' => $_GET['user_id'] ?? null,
        'action' => !empty($_GET['action_types']) ? $_GET['action_types'] : null,
        'start_date' => $_GET['start_date'] ?? null,
        'end_date' => $_GET['end_date'] ?? null,
        'search' => $_GET['search'] ?? null
    ];

    $csv = exportActivityLogsCSV($filters);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="activity_logs_' . date('Y-m-d_His') . '.csv"');
    echo $csv;
    exit;
}

// Get filter parameters
$filters = [
    'user_id' => $_GET['user_id'] ?? null,
    'action' => !empty($_GET['action_types']) ? $_GET['action_types'] : null,
    'start_date' => $_GET['start_date'] ?? null,
    'end_date' => $_GET['end_date'] ?? null,
    'search' => $_GET['search'] ?? null
];

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = max(10, min(100, intval($_GET['per_page'] ?? 25)));
$offset = ($page - 1) * $perPage;

// Get logs and total count
$logs = getActivityLogs($filters, $perPage, $offset);
$totalLogs = getActivityLogsCount($filters);
$totalPages = ceil($totalLogs / $perPage);

// Get statistics
$stats = getActivityStats($filters['start_date'], $filters['end_date']);

// Count how many filters are currently active
$activeFilterCount = (int)!empty($filters['user_id'])
    + (int)!empty($filters['action'])
    + (int)!empty($filters['start_date'])
    + (int)!empty($filters['end_date'])
    + (int)!empty($filters['search']);

// Get all users for filter dropdown
$usersStmt = $pdo->query("SELECT user_id, username, full_name FROM users ORDER BY username");
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

// Available action types
$actionTypes = [
    'login',
    'logout',
    'login_failed',
    'user_created',
    'user_updated',
    'user_deleted',
    'user_role_changed',
    'incident_created',
    'incident_updated',
    'incident_deleted',
    'analytics_exported',
    'sla_report_exported',
    'incident_exported',
    'password_changed',
    'profile_updated',
    'settings_changed',
    'other'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <script>if(localStorage.getItem('theme')==='dark'||(!localStorage.getItem('theme')&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark')}else{document.documentElement.classList.remove('dark')}</script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - eTranzact</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="relative min-h-screen">
    <!-- Background Image with Overlay -->
    <div class="fixed inset-0 z-0">
        <img src="<?= url('assets/mainbg.jpg') ?>" alt="Background" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-white/90 dark:bg-gray-900/95"></div>
    </div>

    <!-- Content Wrapper -->
    <div class="relative z-10" x-data="{ showFilters: true, detailModal: null }">
    <?php include __DIR__ . '/../../src/includes/admin_navbar.php'; ?>
    <?php include __DIR__ . '/../../src/includes/loading.php'; ?>

    <main class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Activity Logs</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Monitor all user actions and system events</p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                            <i class="fas fa-list-ul text-xl text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Logs</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($stats['total_logs']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/40 flex items-center justify-center">
                            <i class="fas fa-users text-xl text-green-600 dark:text-green-400"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Unique Users</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($stats['unique_users']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-900/40 flex items-center justify-center">
                            <i class="fas fa-chart-line text-xl text-purple-600 dark:text-purple-400"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Top Action</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white truncate max-w-[140px]">
                                <?= !empty($stats['top_actions']) ? ucfirst(str_replace('_', ' ', $stats['top_actions'][0]['action_type'])) : 'N/A' ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-orange-100 dark:bg-orange-900/40 flex items-center justify-center">
                            <i class="fas fa-user-check text-xl text-orange-600 dark:text-orange-400"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Most Active</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white truncate max-w-[140px]">
                                <?= !empty($stats['top_users']) ? htmlspecialchars($stats['top_users'][0]['username']) : 'N/A' ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            <i class="fas fa-filter mr-2"></i>Filters
                        </h2>
                        <?php if ($activeFilterCount > 0): ?>
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-600 text-white text-xs font-bold">
                                <?= $activeFilterCount ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <button @click="showFilters = !showFilters"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                        <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </button>
                </div>

                <form method="GET" x-show="showFilters" x-transition class="px-6 py-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Date Range -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start
                                Date</label>
                            <input type="date" name="start_date"
                                value="<?= htmlspecialchars($filters['start_date'] ?? '') ?>"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End
                                Date</label>
                            <input type="date" name="end_date"
                                value="<?= htmlspecialchars($filters['end_date'] ?? '') ?>"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        </div>

                        <!-- User Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">User</label>
                            <select name="user_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                                <option value="">All Users</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['user_id'] ?>" <?= (isset($filters['user_id']) && $filters['user_id'] == $user['user_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['username']) ?>
                                        (<?= htmlspecialchars($user['full_name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Search -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                            <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                                placeholder="Search descriptions..."
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        </div>
                    </div>

                    <!-- Action Types -->
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Action Types</label>
                        <?php
                        $pillColors = [
                            'login'              => 'peer-checked:bg-green-100 peer-checked:text-green-800 peer-checked:border-green-300 dark:peer-checked:bg-green-900 dark:peer-checked:text-green-200 dark:peer-checked:border-green-700',
                            'logout'             => 'peer-checked:bg-gray-200 peer-checked:text-gray-800 peer-checked:border-gray-400 dark:peer-checked:bg-gray-600 dark:peer-checked:text-gray-100 dark:peer-checked:border-gray-500',
                            'login_failed'       => 'peer-checked:bg-red-100 peer-checked:text-red-800 peer-checked:border-red-300 dark:peer-checked:bg-red-900 dark:peer-checked:text-red-200 dark:peer-checked:border-red-700',
                            'user_created'       => 'peer-checked:bg-blue-100 peer-checked:text-blue-800 peer-checked:border-blue-300 dark:peer-checked:bg-blue-900 dark:peer-checked:text-blue-200 dark:peer-checked:border-blue-700',
                            'user_updated'       => 'peer-checked:bg-yellow-100 peer-checked:text-yellow-800 peer-checked:border-yellow-300 dark:peer-checked:bg-yellow-900 dark:peer-checked:text-yellow-200 dark:peer-checked:border-yellow-700',
                            'user_deleted'       => 'peer-checked:bg-red-100 peer-checked:text-red-800 peer-checked:border-red-300 dark:peer-checked:bg-red-900 dark:peer-checked:text-red-200 dark:peer-checked:border-red-700',
                            'user_role_changed'  => 'peer-checked:bg-purple-100 peer-checked:text-purple-800 peer-checked:border-purple-300 dark:peer-checked:bg-purple-900 dark:peer-checked:text-purple-200 dark:peer-checked:border-purple-700',
                            'incident_created'   => 'peer-checked:bg-orange-100 peer-checked:text-orange-800 peer-checked:border-orange-300 dark:peer-checked:bg-orange-900 dark:peer-checked:text-orange-200 dark:peer-checked:border-orange-700',
                            'incident_updated'   => 'peer-checked:bg-amber-100 peer-checked:text-amber-800 peer-checked:border-amber-300 dark:peer-checked:bg-amber-900 dark:peer-checked:text-amber-200 dark:peer-checked:border-amber-700',
                            'incident_deleted'   => 'peer-checked:bg-red-100 peer-checked:text-red-800 peer-checked:border-red-300 dark:peer-checked:bg-red-900 dark:peer-checked:text-red-200 dark:peer-checked:border-red-700',
                            'analytics_exported' => 'peer-checked:bg-cyan-100 peer-checked:text-cyan-800 peer-checked:border-cyan-300 dark:peer-checked:bg-cyan-900 dark:peer-checked:text-cyan-200 dark:peer-checked:border-cyan-700',
                            'sla_report_exported'=> 'peer-checked:bg-teal-100 peer-checked:text-teal-800 peer-checked:border-teal-300 dark:peer-checked:bg-teal-900 dark:peer-checked:text-teal-200 dark:peer-checked:border-teal-700',
                            'incident_exported'  => 'peer-checked:bg-indigo-100 peer-checked:text-indigo-800 peer-checked:border-indigo-300 dark:peer-checked:bg-indigo-900 dark:peer-checked:text-indigo-200 dark:peer-checked:border-indigo-700',
                            'password_changed'   => 'peer-checked:bg-pink-100 peer-checked:text-pink-800 peer-checked:border-pink-300 dark:peer-checked:bg-pink-900 dark:peer-checked:text-pink-200 dark:peer-checked:border-pink-700',
                            'profile_updated'    => 'peer-checked:bg-sky-100 peer-checked:text-sky-800 peer-checked:border-sky-300 dark:peer-checked:bg-sky-900 dark:peer-checked:text-sky-200 dark:peer-checked:border-sky-700',
                            'settings_changed'   => 'peer-checked:bg-violet-100 peer-checked:text-violet-800 peer-checked:border-violet-300 dark:peer-checked:bg-violet-900 dark:peer-checked:text-violet-200 dark:peer-checked:border-violet-700',
                            'other'              => 'peer-checked:bg-gray-100 peer-checked:text-gray-800 peer-checked:border-gray-300 dark:peer-checked:bg-gray-700 dark:peer-checked:text-gray-200 dark:peer-checked:border-gray-600',
                        ];
                        ?>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($actionTypes as $type):
                                $isChecked = isset($filters['action']) && is_array($filters['action']) && in_array($type, $filters['action']);
                                $pill = $pillColors[$type] ?? 'peer-checked:bg-gray-100 peer-checked:text-gray-800 peer-checked:border-gray-300';
                            ?>
                                <label class="relative cursor-pointer">
                                    <input type="checkbox" name="action_types[]" value="<?= $type ?>"
                                        <?= $isChecked ? 'checked' : '' ?>
                                        class="peer sr-only">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:border-gray-400 dark:hover:border-gray-400 transition-all cursor-pointer select-none <?= $pill ?>">
                                        <?= ucfirst(str_replace('_', ' ', $type)) ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Filter Actions -->
                    <div class="mt-4 flex flex-col sm:flex-row gap-3">
                        <button type="submit"
                            class="inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-search mr-2"></i>Apply Filters
                        </button>
                        <a href="activity_logs.php<?= $perPage !== 25 ? '?per_page=' . $perPage : '' ?>"
                            class="inline-flex items-center justify-center px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <i class="fas fa-times mr-2"></i>Clear
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>"
                            class="inline-flex items-center justify-center px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <i class="fas fa-download mr-2"></i>Export CSV
                        </a>
                    </div>
                </form>
            </div>

            <!-- Activity Logs Table -->
            <div
                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Activity Logs</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            <?php if ($totalLogs === 0): ?>
                                No logs found<?= $activeFilterCount > 0 ? ' matching current filters' : '' ?>
                            <?php else: ?>
                                Showing <strong class="text-gray-700 dark:text-gray-300"><?= number_format($offset + 1) ?>–<?= number_format(min($offset + $perPage, $totalLogs)) ?></strong>
                                of <strong class="text-gray-700 dark:text-gray-300"><?= number_format($totalLogs) ?></strong> results
                                <?= $activeFilterCount > 0 ? '<span class="text-blue-600 dark:text-blue-400">(filtered)</span>' : '' ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <!-- Per-page selector -->
                    <form method="GET" class="flex items-center gap-2 flex-shrink-0">
                        <?php foreach ($_GET as $k => $v): ?>
                            <?php if ($k !== 'per_page' && $k !== 'page'): ?>
                                <?php if (is_array($v)): ?>
                                    <?php foreach ($v as $item): ?>
                                        <input type="hidden" name="<?= htmlspecialchars($k) ?>[]" value="<?= htmlspecialchars($item) ?>">
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <label class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">Rows per page:</label>
                        <select name="per_page" onchange="this.form.submit()"
                            class="px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500">
                            <?php foreach ([10, 25, 50, 100] as $opt): ?>
                                <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <?php if (empty($logs)): ?>
                    <div class="px-6 py-16 text-center">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                            <i class="fas fa-<?= $activeFilterCount > 0 ? 'filter' : 'inbox' ?> text-2xl text-gray-400 dark:text-gray-500"></i>
                        </div>
                        <p class="text-base font-medium text-gray-700 dark:text-gray-300">No activity logs found</p>
                        <?php if ($activeFilterCount > 0): ?>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No logs match your current filters.</p>
                            <a href="activity_logs.php<?= $perPage !== 25 ? '?per_page=' . $perPage : '' ?>"
                               class="mt-4 inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                               <i class="fas fa-times mr-1.5"></i> Clear filters
                            </a>
                        <?php else: ?>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Activity will appear here as users interact with the system.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        User</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Action</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Description</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        IP Address</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Timestamp</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($logs as $log): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <?php
                                        $avatarPalette = [
                                            'bg-blue-500', 'bg-green-500', 'bg-purple-500',
                                            'bg-orange-500', 'bg-pink-500', 'bg-teal-500',
                                            'bg-indigo-500', 'bg-rose-500',
                                        ];
                                        $avatarBg = $log['username']
                                            ? $avatarPalette[abs(crc32($log['username'])) % count($avatarPalette)]
                                            : 'bg-gray-400';
                                        ?>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-8 w-8 rounded-full <?= $avatarBg ?> flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
                                                    <?= $log['username'] ? strtoupper(substr($log['username'], 0, 2)) : 'SY' ?>
                                                </div>
                                                <div class="ml-3">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                        <?= $log['username'] ? htmlspecialchars($log['username']) : 'System' ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $actionColors = [
                                                'login'                 => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                'logout'                => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                                'login_failed'          => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                'user_created'          => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                                'user_updated'          => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                                'user_deleted'          => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                'user_role_changed'     => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                                'incident_created'      => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                                'incident_updated'      => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
                                                'incident_deleted'      => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                'analytics_exported'    => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
                                                'sla_report_exported'   => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
                                                'incident_exported'     => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
                                                'password_changed'      => 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
                                                'profile_updated'       => 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200',
                                                'settings_changed'      => 'bg-violet-100 text-violet-800 dark:bg-violet-900 dark:text-violet-200',
                                                'other'                 => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                            ];
                                            $actionKey = $log['action'] ?? '';
                                            $colorClass = $actionColors[$actionKey] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
                                            ?>
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $colorClass ?>">
                                                <?= ucfirst(str_replace('_', ' ', $actionKey)) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white max-w-md truncate"
                                            title="<?= htmlspecialchars($log['description']) ?>">
                                            <?= htmlspecialchars($log['description']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <div title="<?= $log['created_at'] ?>">
                                                <?php
                                                $timestamp = strtotime($log['created_at'] ?? 'now');
                                                $diff = time() - $timestamp;
                                                if ($diff < 60)
                                                    echo 'Just now';
                                                elseif ($diff < 3600)
                                                    echo floor($diff / 60) . 'm ago';
                                                elseif ($diff < 86400)
                                                    echo floor($diff / 3600) . 'h ago';
                                                elseif ($diff < 604800)
                                                    echo floor($diff / 86400) . 'd ago';
                                                else
                                                    echo date('M j, Y', $timestamp);
                                                ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <button @click="detailModal = <?= htmlspecialchars(json_encode($log)) ?>"
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1):
                        $startPage = max(1, $page - 2);
                        $endPage   = min($totalPages, $page + 2);
                    ?>
                        <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4 w-fit mx-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-6 py-3 shadow-sm mb-4">
                            <div class="flex items-center gap-1">
                                <!-- Previous -->
                                <?php if ($page > 1): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                                        <i class="fas fa-chevron-left text-xs"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-300 dark:text-gray-600 cursor-not-allowed">
                                        <i class="fas fa-chevron-left text-xs"></i>
                                    </span>
                                <?php endif; ?>

                                <!-- First + ellipsis -->
                                <?php if ($startPage > 1): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>"
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">1</a>
                                    <?php if ($startPage > 2): ?>
                                        <span class="inline-flex items-center justify-center w-9 h-9 text-sm text-gray-400">…</span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- Page numbers -->
                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <?php if ($i === $page): ?>
                                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-600 text-white text-sm font-semibold shadow-sm"><?= $i ?></span>
                                    <?php else: ?>
                                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"><?= $i ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <!-- Ellipsis + last -->
                                <?php if ($endPage < $totalPages): ?>
                                    <?php if ($endPage < $totalPages - 1): ?>
                                        <span class="inline-flex items-center justify-center w-9 h-9 text-sm text-gray-400">…</span>
                                    <?php endif; ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>"
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"><?= $totalPages ?></a>
                                <?php endif; ?>

                                <!-- Next -->
                                <?php if ($page < $totalPages): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
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
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Detail Modal -->
    <div x-show="detailModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="detailModal = null"></div>

            <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Log Details</h3>
                    <button @click="detailModal = null" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <template x-if="detailModal">
                    <div class="space-y-3">
                        <div class="grid grid-cols-3 gap-4">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Log ID:</div>
                            <div class="col-span-2 text-sm text-gray-900 dark:text-white"
                                x-text="'#' + detailModal.log_id"></div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">User:</div>
                            <div class="col-span-2 text-sm text-gray-900 dark:text-white"
                                x-text="detailModal.username || 'System'"></div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Action Type:</div>
                            <div class="col-span-2 text-sm text-gray-900 dark:text-white"
                                x-text="detailModal.action"></div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Description:</div>
                            <div class="col-span-2 text-sm text-gray-900 dark:text-white"
                                x-text="detailModal.description"></div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">IP Address:</div>
                            <div class="col-span-2 text-sm text-gray-900 dark:text-white"
                                x-text="detailModal.ip_address || 'N/A'"></div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">User Agent:</div>
                            <div class="col-span-2 text-sm text-gray-900 dark:text-white break-all"
                                x-text="detailModal.user_agent || 'N/A'"></div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Timestamp:</div>
                            <div class="col-span-2 text-sm text-gray-900 dark:text-white"
                                x-text="detailModal.created_at"></div>
                        </div>
                        <template x-if="detailModal.metadata">
                            <div class="grid grid-cols-3 gap-4">
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Metadata:</div>
                                <div class="col-span-2">
                                    <pre class="text-xs bg-gray-100 dark:bg-gray-900 p-3 rounded overflow-x-auto"
                                        x-text="(() => { try { return JSON.stringify(JSON.parse(detailModal.metadata), null, 2); } catch(e) { return detailModal.metadata; } })()"></pre>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
    </div> <!-- End Content Wrapper -->
</body>

</html>