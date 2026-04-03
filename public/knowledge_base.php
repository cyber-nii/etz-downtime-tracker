<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/includes/auth.php';
requireLogin();

// Pagination
$itemsPerPage = 12;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$serviceFilter = isset($_GET['service']) ? intval($_GET['service']) : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Base query for resolved incidents
$whereClauses = ["i.status = 'resolved'"];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(i.incident_ref LIKE ? OR i.description LIKE ? OR i.root_cause LIKE ? OR i.lessons_learned LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($serviceFilter)) {
    $whereClauses[] = "i.service_id = ?";
    $params[] = $serviceFilter;
}

if (!empty($dateFrom)) {
    $whereClauses[] = "i.resolved_at >= ?";
    $params[] = date('Y-m-d 00:00:00', strtotime($dateFrom));
}

if (!empty($dateTo)) {
    $whereClauses[] = "i.resolved_at <= ?";
    $params[] = date('Y-m-d 23:59:59', strtotime($dateTo));
}

$whereSQL = implode(' AND ', $whereClauses);

try {
    // Count for pagination
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM incidents i WHERE {$whereSQL}");
    $countStmt->execute($params);
    $totalArticles = $countStmt->fetchColumn();
    $totalPages = ceil($totalArticles / $itemsPerPage);

    // Fetch articles
    $sqlParams = $params;
    // For LIMIT/OFFSET we bind them as integers if using prepare, but PDO with emulation might treat them as strings unless explicitly typed. 
    // We can just append them directly since they are safely cast to ints.
    $limitSql = "LIMIT " . intval($itemsPerPage) . " OFFSET " . intval($offset);
    
    $articlesStmt = $pdo->prepare("
        SELECT 
            i.incident_id, i.incident_ref, i.description, i.root_cause, i.lessons_learned,
            i.impact_level, i.resolved_at,
            s.service_name,
            c.name as component_name,
            u.full_name as resolved_by_name,
            (SELECT COUNT(*) FROM incident_attachments ia WHERE ia.incident_id = i.incident_id) as attachment_count
        FROM incidents i
        JOIN services s ON i.service_id = s.service_id
        LEFT JOIN components c ON i.component_id = c.component_id
        LEFT JOIN users u ON i.resolved_by = u.user_id
        WHERE {$whereSQL}
        ORDER BY i.resolved_at DESC
        {$limitSql}
    ");
    $articlesStmt->execute($params);
    $articles = $articlesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch services for filter dropdown
    $services = $pdo->query("SELECT service_id, service_name FROM services ORDER BY service_name")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching knowledge base: " . $e->getMessage());
}

/**
 * Text excerpt helper
 */
function getExcerpt($text, $length = 150) {
    if (empty($text)) return '';
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
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
        <div class="mb-8 bg-white dark:bg-gray-800 rounded-xl p-6 sm:p-8 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400">
                        Knowledge Base
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400 text-lg">
                        Browse post-mortems and resolutions of past incidents.
                    </p>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-4 py-3 rounded-xl border border-blue-100 dark:border-blue-800 flex items-center gap-3 shadow-sm">
                    <i class="fas fa-book-open text-xl opacity-80"></i>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wider opacity-80">Total Articles</div>
                        <div class="text-xl font-bold"><?= number_format($totalArticles) ?></div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" action="knowledge_base.php" class="mt-8 border-t border-gray-100 dark:border-gray-700 pt-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search root causes, lessons learned, or descriptions..."
                            class="block w-full pl-10 pr-3 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-shadow">
                    </div>
                    
                    <div>
                        <select name="service" class="block w-full py-2.5 pl-3 pr-10 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-shadow">
                            <option value="">All Services</option>
                            <?php foreach ($services as $srv): ?>
                                <option value="<?= $srv['service_id'] ?>" <?= ($serviceFilter == $srv['service_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($srv['service_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex gap-2">
                        <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" title="Resolved From Date"
                            class="block w-full py-2.5 px-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-shadow">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 rounded-xl shadow-sm transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 flex items-center justify-center min-w-[3rem]">
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
                
                <?php if (!empty($search) || !empty($serviceFilter) || !empty($dateFrom) || !empty($dateTo)): ?>
                    <div class="mt-4 flex items-center gap-2">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Active filters:</span>
                        <a href="knowledge_base.php" class="inline-flex items-center gap-1.5 px-3 py-1 bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 rounded-lg text-xs font-medium transition-colors border border-red-100 dark:border-red-800">
                            <i class="fas fa-times"></i> Clear all
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Articles Grid -->
        <?php if (empty($articles)): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 mb-4">
                    <i class="fas fa-folder-open text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">No articles found</h3>
                <p class="mt-2 text-gray-500 dark:text-gray-400 max-w-sm mx-auto">Try adjusting your search terms or filters to find what you're looking for.</p>
                <?php if (!empty($search) || !empty($serviceFilter)): ?>
                    <a href="knowledge_base.php" class="mt-6 inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium transition-colors">
                        Clear all filters <i class="fas fa-arrow-right ml-1.5 text-xs"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($articles as $article): 
                    // Impact badge
                    $impactClass = 'bg-gray-100 text-gray-800';
                    switch(strtolower($article['impact_level'])) {
                        case 'critical': $impactClass = 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'; break;
                        case 'high': $impactClass = 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300'; break;
                        case 'medium': $impactClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300'; break;
                        case 'low': $impactClass = 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'; break;
                    }
                ?>
                    <div class="kb-card bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm flex flex-col h-full relative group">
                        
                        <a href="kb_article.php?id=<?= $article['incident_id'] ?>" class="absolute inset-0 z-10"><span class="sr-only">View Article</span></a>
                        
                        <div class="p-6 flex-grow flex flex-col">
                            
                            <div class="flex items-start justify-between gap-3 mb-4 relative z-20">
                                <div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-600">
                                        <?= htmlspecialchars($article['service_name']) ?>
                                    </span>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide <?= $impactClass ?>">
                                    <?= htmlspecialchars($article['impact_level']) ?>
                                </span>
                            </div>
                            
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 leading-snug group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                <?php if (!empty($article['incident_ref'])): ?>
                                    <span class="text-gray-400 dark:text-gray-500 font-mono text-sm mr-1">#<?= htmlspecialchars($article['incident_ref']) ?></span><br/>
                                <?php endif; ?>
                                <?= htmlspecialchars(getExcerpt($article['description'], 80)) ?>
                            </h3>
                            
                            <?php if (!empty($article['root_cause'])): ?>
                                <div class="mt-3 text-sm text-gray-600 dark:text-gray-400 line-clamp-3 bg-gray-50/50 dark:bg-gray-800/50 p-3 rounded-lg border border-gray-100 dark:border-gray-700">
                                    <span class="font-semibold text-gray-700 dark:text-gray-300 block mb-1 text-xs uppercase tracking-wider">Root Cause:</span>
                                    <?= htmlspecialchars($article['root_cause']) ?>
                                </div>
                            <?php elseif (!empty($article['lessons_learned'])): ?>
                                <div class="mt-3 text-sm text-gray-600 dark:text-gray-400 line-clamp-3 bg-blue-50/50 dark:bg-blue-900/10 p-3 rounded-lg border border-blue-50 dark:border-blue-900/30">
                                    <span class="font-semibold text-blue-700 dark:text-blue-400 block mb-1 text-xs uppercase tracking-wider">Lessons Learned:</span>
                                    <?= htmlspecialchars($article['lessons_learned']) ?>
                                </div>
                            <?php endif; ?>
                            
                        </div>
                        
                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/80 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-1.5" title="Resolved At">
                                <i class="far fa-calendar-check text-green-500"></i>
                                <?= date('M j, Y', strtotime($article['resolved_at'])) ?>
                            </div>
                            
                            <?php if ($article['attachment_count'] > 0): ?>
                            <div class="flex items-center gap-1.5" title="Attachments">
                                <i class="fas fa-paperclip"></i> <?= $article['attachment_count'] ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="mt-10 flex justify-center">
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php
                        $queryStr = $_GET;
                        // Previous Button
                        if ($currentPage > 1):
                            $queryStr['page'] = $currentPage - 1; ?>
                            <a href="?<?= http_build_query($queryStr) ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <span class="sr-only">Previous</span>
                                <i class="fas fa-chevron-left w-5 h-5 flex justify-center items-center"></i>
                            </a>
                        <?php endif; ?>

                        <?php
                        // Page Numbers
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        if ($startPage > 1) {
                            $queryStr['page'] = 1;
                            echo "<a href='?" . http_build_query($queryStr) . "' class=\"relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700\">1</a>";
                            if ($startPage > 2) {
                                echo "<span class=\"relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300\">...</span>";
                            }
                        }

                        for ($i = $startPage; $i <= $endPage; $i++):
                            $queryStr['page'] = $i;
                            $isActive = ($i == $currentPage);
                            $activeClass = $isActive 
                                ? "z-10 bg-blue-50 dark:bg-blue-900 border-blue-500 text-blue-600 dark:text-blue-200" 
                                : "bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700";
                        ?>
                            <a href="?<?= http_build_query($queryStr) ?>" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?= $activeClass ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo "<span class=\"relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300\">...</span>";
                            }
                            $queryStr['page'] = $totalPages;
                            echo "<a href='?" . http_build_query($queryStr) . "' class=\"relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700\">{$totalPages}</a>";
                        }
                        ?>

                        <?php
                        // Next Button
                        if ($currentPage < $totalPages):
                            $queryStr['page'] = $currentPage + 1; ?>
                            <a href="?<?= http_build_query($queryStr) ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <span class="sr-only">Next</span>
                                <i class="fas fa-chevron-right w-5 h-5 flex justify-center items-center"></i>
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
