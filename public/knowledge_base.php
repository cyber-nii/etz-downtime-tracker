<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/includes/auth.php';
requireLogin();

// Pagination
$itemsPerPage = 12;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Filters
$search        = trim($_GET['search'] ?? '');
$serviceFilter = intval($_GET['service'] ?? 0);
$impactFilter  = in_array($_GET['impact'] ?? '', ['Low','Medium','High','Critical']) ? $_GET['impact'] : '';
$dateFrom      = trim($_GET['date_from'] ?? '');
$dateTo        = trim($_GET['date_to'] ?? '');
$typeFilter    = in_array($_GET['type'] ?? '', ['downtime','security','fraud']) ? $_GET['type'] : '';

$wild = !empty($search) ? '%' . $search . '%' : '';

// ── Build per-type WHERE + params ──────────────────────────────────────────
function buildDowntimeWhere(string $search, int $service, string $impact, string $dateFrom, string $dateTo): array {
    $w = ["i.status = 'resolved'"]; $p = [];
    if ($search)  { $w[] = "(i.incident_ref LIKE ? OR i.description LIKE ? OR i.root_cause LIKE ? OR i.lessons_learned LIKE ?)"; array_push($p, "%$search%", "%$search%", "%$search%", "%$search%"); }
    if ($service) { $w[] = "i.service_id = ?"; $p[] = $service; }
    if ($impact)  { $w[] = "i.impact_level = ?"; $p[] = $impact; }
    if ($dateFrom){ $w[] = "i.resolved_at >= ?"; $p[] = $dateFrom . ' 00:00:00'; }
    if ($dateTo)  { $w[] = "i.resolved_at <= ?"; $p[] = $dateTo   . ' 23:59:59'; }
    return [implode(' AND ', $w), $p];
}

function buildSecurityWhere(string $search, string $impact, string $dateFrom, string $dateTo): array {
    $w = ["s.status = 'resolved'"]; $p = [];
    if ($search)  { $w[] = "(s.incident_ref LIKE ? OR s.description LIKE ? OR s.root_cause LIKE ? OR s.lessons_learned LIKE ? OR s.threat_type LIKE ?)"; array_push($p, "%$search%", "%$search%", "%$search%", "%$search%", "%$search%"); }
    if ($impact)  { $w[] = "s.impact_level = ?"; $p[] = $impact; }
    if ($dateFrom){ $w[] = "s.resolved_at >= ?"; $p[] = $dateFrom . ' 00:00:00'; }
    if ($dateTo)  { $w[] = "s.resolved_at <= ?"; $p[] = $dateTo   . ' 23:59:59'; }
    return [implode(' AND ', $w), $p];
}

function buildFraudWhere(string $search, int $service, string $impact, string $dateFrom, string $dateTo): array {
    $w = ["f.status = 'resolved'"]; $p = [];
    if ($search)  { $w[] = "(f.incident_ref LIKE ? OR f.description LIKE ? OR f.root_cause LIKE ? OR f.lessons_learned LIKE ? OR f.fraud_type LIKE ?)"; array_push($p, "%$search%", "%$search%", "%$search%", "%$search%", "%$search%"); }
    if ($service) { $w[] = "f.service_id = ?"; $p[] = $service; }
    if ($impact)  { $w[] = "f.impact_level = ?"; $p[] = $impact; }
    if ($dateFrom){ $w[] = "f.resolved_at >= ?"; $p[] = $dateFrom . ' 00:00:00'; }
    if ($dateTo)  { $w[] = "f.resolved_at <= ?"; $p[] = $dateTo   . ' 23:59:59'; }
    return [implode(' AND ', $w), $p];
}

[$dtWhere, $dtParams]  = buildDowntimeWhere($search, $serviceFilter, $impactFilter, $dateFrom, $dateTo);
[$secWhere, $secParams] = buildSecurityWhere($search, $impactFilter, $dateFrom, $dateTo);
[$frWhere,  $frParams]  = buildFraudWhere($search, $serviceFilter, $impactFilter, $dateFrom, $dateTo);

// ── Build UNION SQL ─────────────────────────────────────────────────────────
$dtSQL = "
    SELECT i.incident_id AS id, i.incident_ref, i.description, i.root_cause, i.lessons_learned,
           i.impact_level, i.resolved_at, s.service_name AS context_name, 'downtime' AS incident_type,
           (SELECT COUNT(*) FROM incident_attachments ia WHERE ia.incident_id = i.incident_id) AS attachment_count
    FROM incidents i
    JOIN services s ON i.service_id = s.service_id
    WHERE {$dtWhere}";

$secSQL = "
    SELECT s.id, s.incident_ref, s.description, s.root_cause, s.lessons_learned,
           s.impact_level, s.resolved_at,
           CONCAT('Threat: ', REPLACE(REPLACE(s.threat_type,'_',' '),'other','Other')) AS context_name,
           'security' AS incident_type, 0 AS attachment_count
    FROM security_incidents s
    WHERE {$secWhere}";

$frSQL = "
    SELECT f.id, f.incident_ref, f.description, f.root_cause, f.lessons_learned,
           f.impact_level, f.resolved_at,
           CONCAT(COALESCE(sv.service_name,'—'), ' · ', REPLACE(REPLACE(f.fraud_type,'_',' '),'other','Other')) AS context_name,
           'fraud' AS incident_type, 0 AS attachment_count
    FROM fraud_incidents f
    LEFT JOIN services sv ON f.service_id = sv.service_id
    WHERE {$frWhere}";

// Select which parts to include based on type filter
if ($typeFilter === 'downtime') {
    $unionSQL = $dtSQL;
    $unionParams = $dtParams;
} elseif ($typeFilter === 'security') {
    $unionSQL = $secSQL;
    $unionParams = $secParams;
} elseif ($typeFilter === 'fraud') {
    $unionSQL = $frSQL;
    $unionParams = $frParams;
} else {
    $unionSQL = "({$dtSQL}) UNION ALL ({$secSQL}) UNION ALL ({$frSQL})";
    $unionParams = array_merge($dtParams, $secParams, $frParams);
}

$limitSql = "LIMIT " . intval($itemsPerPage) . " OFFSET " . intval($offset);

try {
    // Total for current view (paginated)
    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM ({$unionSQL}) AS _total");
    $cntStmt->execute($unionParams);
    $totalArticles = (int)$cntStmt->fetchColumn();
    $totalPages    = max(1, (int)ceil($totalArticles / $itemsPerPage));

    // Per-type counts for header badges
    $dtCntStmt = $pdo->prepare("SELECT COUNT(*) FROM incidents i JOIN services s ON i.service_id = s.service_id WHERE {$dtWhere}");
    $dtCntStmt->execute($dtParams);
    $dtCount = (int)$dtCntStmt->fetchColumn();

    $secCntStmt = $pdo->prepare("SELECT COUNT(*) FROM security_incidents s WHERE {$secWhere}");
    $secCntStmt->execute($secParams);
    $secCount = (int)$secCntStmt->fetchColumn();

    $frCntStmt = $pdo->prepare("SELECT COUNT(*) FROM fraud_incidents f LEFT JOIN services sv ON f.service_id = sv.service_id WHERE {$frWhere}");
    $frCntStmt->execute($frParams);
    $frCount = (int)$frCntStmt->fetchColumn();

    // Paginated fetch
    $articlesStmt = $pdo->prepare("{$unionSQL} ORDER BY resolved_at DESC {$limitSql}");
    $articlesStmt->execute($unionParams);
    $articles = $articlesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Services for filter dropdown
    $services = $pdo->query("SELECT service_id, service_name FROM services ORDER BY service_name")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching knowledge base: " . $e->getMessage());
}

function getExcerpt($text, $length = 150) {
    if (empty($text)) return '';
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

function kbUrl(array $overrides = []): string {
    global $search, $serviceFilter, $impactFilter, $dateFrom, $dateTo, $typeFilter;
    $base = [
        'type'      => $typeFilter,
        'search'    => $search,
        'service'   => $serviceFilter ?: '',
        'impact'    => $impactFilter,
        'date_from' => $dateFrom,
        'date_to'   => $dateTo,
    ];
    $merged = array_merge($base, $overrides);
    $merged = array_filter($merged, fn($v) => $v !== '' && $v !== null && $v !== 0 && $v !== '0');
    return 'knowledge_base.php' . ($merged ? '?' . http_build_query($merged) : '');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <script>if(localStorage.getItem('theme')==='dark'||(!localStorage.getItem('theme')&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark')}else{document.documentElement.classList.remove('dark')}</script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knowledge Base - ETZ Downtime</title>

    <!-- Tailwind CSS v3.4.17 -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>

    <!-- Alpine.js v3.x -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome 6.5.1 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .kb-card {
            transition: all 0.2s ease;
        }

        .kb-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body class="relative min-h-screen text-gray-900 dark:text-gray-100">
    <!-- Background Image with Overlay -->
    <div class="fixed inset-0 z-0">
        <img src="<?= url('../src/assets/mainbg.jpg') ?>" alt="Background" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-white/90 dark:bg-gray-900/95"></div>
    </div>

    <!-- Content Wrapper -->
    <div class="relative z-10">
        <!-- Navbar -->
        <?php include __DIR__ . '/../src/includes/navbar.php'; ?>
        
        <!-- Loading Overlay -->
        <?php include __DIR__ . '/../src/includes/loading.php'; ?>

        <main class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl p-6 sm:p-8 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400">
                        Knowledge Base
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Browse post-mortems and resolutions of past incidents.</p>
                </div>
                <div class="flex gap-3 flex-wrap">
                    <div class="bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-4 py-3 rounded-xl border border-blue-100 dark:border-blue-800 flex items-center gap-3 shadow-sm">
                        <i class="fas fa-clock-rotate-left text-lg opacity-80"></i>
                        <div>
                            <div class="text-[10px] font-semibold uppercase tracking-wider opacity-70">Downtime</div>
                            <div class="text-lg font-bold"><?= number_format($dtCount) ?></div>
                        </div>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 px-4 py-3 rounded-xl border border-red-100 dark:border-red-800 flex items-center gap-3 shadow-sm">
                        <i class="fas fa-shield-halved text-lg opacity-80"></i>
                        <div>
                            <div class="text-[10px] font-semibold uppercase tracking-wider opacity-70">Security</div>
                            <div class="text-lg font-bold"><?= number_format($secCount) ?></div>
                        </div>
                    </div>
                    <div class="bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 px-4 py-3 rounded-xl border border-amber-100 dark:border-amber-800 flex items-center gap-3 shadow-sm">
                        <i class="fas fa-triangle-exclamation text-lg opacity-80"></i>
                        <div>
                            <div class="text-[10px] font-semibold uppercase tracking-wider opacity-70">Fraud</div>
                            <div class="text-lg font-bold"><?= number_format($frCount) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Type tabs -->
            <div class="mt-6 flex gap-1 border-b border-gray-200 dark:border-gray-700">
                <?php
                $tabs = [
                    ''          => ['label' => 'All',      'icon' => 'fa-layer-group',         'active' => 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400'],
                    'downtime'  => ['label' => 'Downtime', 'icon' => 'fa-clock-rotate-left',   'active' => 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400'],
                    'security'  => ['label' => 'Security', 'icon' => 'fa-shield-halved',       'active' => 'border-b-2 border-red-600 text-red-600 dark:text-red-400 dark:border-red-400'],
                    'fraud'     => ['label' => 'Fraud',    'icon' => 'fa-triangle-exclamation','active' => 'border-b-2 border-amber-500 text-amber-600 dark:text-amber-400 dark:border-amber-400'],
                ];
                foreach ($tabs as $key => $tab):
                    $isActive = ($typeFilter === $key);
                    $cls = $isActive ? $tab['active'] : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200';
                ?>
                    <a href="<?= htmlspecialchars(kbUrl(['type' => $key, 'page' => 1])) ?>"
                       class="px-5 py-2.5 text-sm font-medium -mb-px transition-colors <?= $cls ?>">
                        <i class="fas <?= $tab['icon'] ?> mr-1.5"></i><?= $tab['label'] ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Filters -->
            <form method="GET" action="knowledge_base.php" class="mt-5">
                <?php if ($typeFilter): ?><input type="hidden" name="type" value="<?= htmlspecialchars($typeFilter) ?>"><?php endif; ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Search -->
                    <div class="sm:col-span-2 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                            placeholder="Search ref, description, root cause, lessons learned…"
                            class="block w-full pl-9 pr-3 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>

                    <!-- Impact -->
                    <div>
                        <select name="impact" class="block w-full py-2.5 pl-3 pr-10 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm dark:text-white">
                            <option value="">All Impact Levels</option>
                            <?php foreach (['Low','Medium','High','Critical'] as $lvl): ?>
                                <option value="<?= $lvl ?>" <?= $impactFilter === $lvl ? 'selected' : '' ?>><?= $lvl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Service (hidden for security-only tab) -->
                    <?php if ($typeFilter !== 'security'): ?>
                    <div>
                        <select name="service" class="block w-full py-2.5 pl-3 pr-10 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm dark:text-white">
                            <option value="">All Services</option>
                            <?php foreach ($services as $srv): ?>
                                <option value="<?= $srv['service_id'] ?>" <?= ($serviceFilter == $srv['service_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($srv['service_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>

                    <!-- Date from -->
                    <div>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" title="Resolved From"
                            class="block w-full py-2.5 px-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm dark:text-white">
                    </div>

                    <!-- Date to -->
                    <div>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" title="Resolved To"
                            class="block w-full py-2.5 px-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm dark:text-white">
                    </div>

                    <!-- Submit -->
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 px-4 rounded-xl shadow-sm transition-colors text-sm font-medium">
                            <i class="fas fa-filter mr-1.5"></i> Apply
                        </button>
                        <?php if ($search || $serviceFilter || $impactFilter || $dateFrom || $dateTo): ?>
                            <a href="<?= htmlspecialchars(kbUrl(['search'=>'','service'=>'','impact'=>'','date_from'=>'','date_to'=>'','page'=>1])) ?>"
                               class="flex items-center justify-center px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 text-sm transition-colors" title="Clear filters">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results summary -->
        <?php if ($totalArticles > 0): ?>
        <div class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            Showing <strong class="text-gray-900 dark:text-white"><?= number_format($offset + 1) ?>–<?= number_format(min($offset + $itemsPerPage, $totalArticles)) ?></strong>
            of <strong class="text-gray-900 dark:text-white"><?= number_format($totalArticles) ?></strong> resolved incidents
        </div>
        <?php endif; ?>

        <!-- Articles Grid -->
        <?php if (empty($articles)): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 mb-4">
                    <i class="fas fa-folder-open text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">No articles found</h3>
                <p class="mt-2 text-gray-500 dark:text-gray-400 max-w-sm mx-auto">Try adjusting your search terms or filters.</p>
                <?php if ($search || $serviceFilter || $impactFilter || $dateFrom || $dateTo): ?>
                    <a href="<?= htmlspecialchars(kbUrl(['search'=>'','service'=>'','impact'=>'','date_from'=>'','date_to'=>'','page'=>1])) ?>"
                       class="mt-6 inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium transition-colors">
                        Clear filters <i class="fas fa-arrow-right ml-1.5 text-xs"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($articles as $article):
                    $type = $article['incident_type'];

                    // Type badge
                    $typeBadge = match($type) {
                        'security' => ['bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',    'fa-shield-halved',         'Security'],
                        'fraud'    => ['bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300','fa-triangle-exclamation','Fraud'],
                        default    => ['bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300', 'fa-clock-rotate-left',     'Downtime'],
                    };

                    // Impact badge
                    $impactClass = match(strtolower($article['impact_level'])) {
                        'critical' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                        'high'     => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
                        'medium'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
                        'low'      => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                        default    => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                    };

                    // Detail link — only downtime has a detail page
                    $detailHref = $type === 'downtime' ? "kb_article.php?id={$article['id']}" : null;
                ?>
                    <div class="kb-card bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm flex flex-col h-full relative group">
                        <?php if ($detailHref): ?>
                            <a href="<?= $detailHref ?>" class="absolute inset-0 z-10"><span class="sr-only">View Article</span></a>
                        <?php endif; ?>

                        <div class="p-6 flex-grow flex flex-col">
                            <div class="flex items-start justify-between gap-3 mb-4 relative z-20">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <!-- Type badge -->
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold <?= $typeBadge[0] ?>">
                                        <i class="fas <?= $typeBadge[1] ?> text-[9px]"></i><?= $typeBadge[2] ?>
                                    </span>
                                    <!-- Context (service / threat type) -->
                                    <?php if (!empty($article['context_name'])): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600 truncate max-w-[140px]">
                                            <?= htmlspecialchars($article['context_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide <?= $impactClass ?>">
                                    <?= htmlspecialchars($article['impact_level']) ?>
                                </span>
                            </div>

                            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-2 leading-snug <?= $detailHref ? 'group-hover:text-blue-600 dark:group-hover:text-blue-400' : '' ?> transition-colors">
                                <?php if (!empty($article['incident_ref'])): ?>
                                    <span class="text-gray-400 dark:text-gray-500 font-mono text-xs mr-1">#<?= htmlspecialchars($article['incident_ref']) ?></span>
                                <?php endif; ?>
                                <?= htmlspecialchars(getExcerpt($article['description'] ?? '', 80)) ?>
                            </h3>

                            <?php if (!empty($article['root_cause'])): ?>
                                <div class="mt-3 text-sm text-gray-600 dark:text-gray-400 line-clamp-3 bg-gray-50 dark:bg-gray-700/40 p-3 rounded-lg border border-gray-100 dark:border-gray-700">
                                    <span class="font-semibold text-gray-700 dark:text-gray-300 block mb-1 text-[10px] uppercase tracking-wider">Root Cause</span>
                                    <?= htmlspecialchars($article['root_cause']) ?>
                                </div>
                            <?php elseif (!empty($article['lessons_learned'])): ?>
                                <div class="mt-3 text-sm text-gray-600 dark:text-gray-400 line-clamp-3 bg-blue-50/50 dark:bg-blue-900/10 p-3 rounded-lg border border-blue-100 dark:border-blue-900/30">
                                    <span class="font-semibold text-blue-700 dark:text-blue-400 block mb-1 text-[10px] uppercase tracking-wider">Lessons Learned</span>
                                    <?= htmlspecialchars($article['lessons_learned']) ?>
                                </div>
                            <?php else: ?>
                                <p class="mt-3 text-xs text-gray-400 italic">No root cause or lessons documented yet.</p>
                            <?php endif; ?>
                        </div>

                        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-800/80 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-1.5">
                                <i class="far fa-calendar-check text-green-500"></i>
                                <?= $article['resolved_at'] ? date('M j, Y', strtotime($article['resolved_at'])) : '—' ?>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if ($article['attachment_count'] > 0): ?>
                                    <span class="flex items-center gap-1"><i class="fas fa-paperclip"></i><?= $article['attachment_count'] ?></span>
                                <?php endif; ?>
                                <?php if ($detailHref): ?>
                                    <span class="text-blue-500 dark:text-blue-400 font-medium relative z-20">
                                        View <i class="fas fa-arrow-right text-[9px]"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1):
                $startPage = max(1, $currentPage - 2);
                $endPage   = min($totalPages, $currentPage + 2);
            ?>
                <div class="mt-10 flex justify-center">
                    <nav class="inline-flex rounded-lg shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($currentPage > 1): ?>
                            <a href="<?= htmlspecialchars(kbUrl(['page' => $currentPage - 1])) ?>"
                               class="relative inline-flex items-center px-3 py-2 rounded-l-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php if ($startPage > 1): ?>
                            <a href="<?= htmlspecialchars(kbUrl(['page' => 1])) ?>"
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">1</a>
                            <?php if ($startPage > 2): ?>
                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-sm text-gray-500">…</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="<?= htmlspecialchars(kbUrl(['page' => $i])) ?>"
                               class="relative inline-flex items-center px-4 py-2 border text-sm font-medium
                                      <?= $i === $currentPage ? 'z-10 bg-blue-50 dark:bg-blue-900 border-blue-500 text-blue-600 dark:text-blue-200' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-sm text-gray-500">…</span>
                            <?php endif; ?>
                            <a href="<?= htmlspecialchars(kbUrl(['page' => $totalPages])) ?>"
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"><?= $totalPages ?></a>
                        <?php endif; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <a href="<?= htmlspecialchars(kbUrl(['page' => $currentPage + 1])) ?>"
                               class="relative inline-flex items-center px-3 py-2 rounded-r-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    </div> <!-- /Content Wrapper -->
</body>
</html>
