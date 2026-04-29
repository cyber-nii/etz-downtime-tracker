<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/includes/auth.php';
requireLogin();

$incident_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$incident_id) {
    $_SESSION['error'] = "Invalid article ID.";
    header("Location: knowledge_base.php");
    exit;
}

try {
    // Fetch incident details
    $stmt = $pdo->prepare("
        SELECT 
            i.incident_id, i.incident_ref, i.description, i.root_cause, i.lessons_learned,
            i.impact_level, i.priority, i.status, i.created_at, i.resolved_at,
            s.service_name,
            c.name as component_name,
            u.full_name as reported_by_name,
            res.full_name as resolved_by_name,
            i.root_cause_file, i.lessons_learned_file,
            i.resolvers,
            i.attachment_path
        FROM incidents i
        JOIN services s ON i.service_id = s.service_id
        LEFT JOIN components c ON i.component_id = c.component_id
        LEFT JOIN users u ON i.reported_by = u.user_id
        LEFT JOIN users res ON i.resolved_by = res.user_id
        WHERE i.incident_id = ? AND i.status = 'resolved'
    ");
    $stmt->execute([$incident_id]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$article) {
        $_SESSION['error'] = "Article not found or incident is not resolved.";
        header("Location: knowledge_base.php");
        exit;
    }

    // Fetch affected companies
    $compStmt = $pdo->prepare("
        SELECT co.company_name 
        FROM incident_affected_companies iac
        JOIN companies co ON iac.company_id = co.company_id
        WHERE iac.incident_id = ?
        ORDER BY co.company_name
    ");
    $compStmt->execute([$incident_id]);
    $companies = $compStmt->fetchAll(PDO::FETCH_COLUMN);
    $affectedCompanies = empty($companies) ? "None" : implode(', ', $companies);

    // Fetch updates
    $updatesStmt = $pdo->prepare("
        SELECT user_name, update_text, created_at 
        FROM incident_updates 
        WHERE incident_id = ? 
        ORDER BY created_at DESC
    ");
    $updatesStmt->execute([$incident_id]);
    $updates = $updatesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch attachments
    $attachStmt = $pdo->prepare("
        SELECT file_name, file_path, file_size 
        FROM incident_attachments 
        WHERE incident_id = ?
    ");
    $attachStmt->execute([$incident_id]);
    $attachments = $attachStmt->fetchAll(PDO::FETCH_ASSOC);

    // Include main attachment path if there's one that isn't mapped in table yet
    if (!empty($article['attachment_path'])) {
        $pathExists = false;
        foreach ($attachments as $a) {
            if ($a['file_path'] === $article['attachment_path']) {
                $pathExists = true;
                break;
            }
        }
        if (!$pathExists) {
            $attachments[] = [
                'file_name' => basename($article['attachment_path']),
                'file_path' => $article['attachment_path'],
                'file_size' => null
            ];
        }
    }

} catch (PDOException $e) {
    die("Error fetching article details: " . $e->getMessage());
}

// Function to safely output file links
function getDownloadLink($filePath)
{
    if (empty($filePath))
        return '#';
    return url($filePath); // Resolves relative to public dir based on auth.php helper
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <script>if(localStorage.getItem('theme')==='dark'||(!localStorage.getItem('theme')&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark')}else{document.documentElement.classList.remove('dark')}</script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['service_name']) ?> Post-Mortem - KB</title>

    <!-- Tailwind CSS v3.4.17 -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        .timeline-container {
            position: relative;
        }

        .timeline-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 1rem;
            height: 100%;
            width: 2px;
            background: #e5e7eb;
            transform: translateX(-50%);
        }

        .dark .timeline-container::before {
            background: #374151;
        }
    </style>
</head>

<body class="relative min-h-screen text-gray-900 dark:text-gray-100">
    <!-- Background Image with Overlay -->
    <div class="fixed inset-0 z-0">
        <img src="<?= url('assets/mainbg.jpg') ?>" alt="Background" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-white/90 dark:bg-gray-900/95"></div>
    </div>

    <!-- Content Wrapper -->
    <div class="relative z-10">
        <!-- Navbar -->
        <?php include __DIR__ . '/../src/includes/navbar.php'; ?>

        <!-- Loading Overlay -->
        <?php include __DIR__ . '/../src/includes/loading.php'; ?>

        <main class="py-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb / Back Navigation -->
            <div class="mb-6 flex items-center justify-between">
                <a href="knowledge_base.php"
                    class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Knowledge Base
                </a>

                <?php if (!empty($article['incident_ref'])): ?>
                    <div
                        class="inline-flex items-center gap-2 text-xs font-mono bg-gray-100 dark:bg-gray-800 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700">
                        <span class="text-gray-500 dark:text-gray-400">Ref:</span>
                        <span
                            class="font-bold text-gray-800 dark:text-gray-200"><?= htmlspecialchars($article['incident_ref']) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Main Content -->
                <div class="lg:col-span-2 space-y-8">

                    <!-- Article Header -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl p-8 shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="flex flex-wrap items-center gap-3 mb-4">
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-100 dark:border-blue-800">
                                <?= htmlspecialchars($article['service_name']) ?>
                            </span>

                            <?php if ($article['component_name']): ?>
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    <?= htmlspecialchars($article['component_name']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-6 leading-tight">
                            Post-Mortem: <?= htmlspecialchars($article['service_name']) ?> Incident
                        </h1>

                        <div class="text-gray-600 dark:text-gray-300">
                            <h3 class="text-gray-800 dark:text-gray-200 font-semibold mb-2 flex items-center gap-2">
                                <i class="fas fa-align-left text-gray-400"></i> Original Description
                            </h3>
                            <p
                                class=" bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
                                <?= htmlspecialchars($article['description']) ?></p>
                        </div>
                    </div>

                    <!-- Root Cause Analysis -->
                    <?php if (!empty($article['root_cause']) || !empty($article['root_cause_file'])): ?>
                        <div
                            class="bg-white dark:bg-gray-800 rounded-xl p-8 shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-red-50 dark:bg-red-900/10 rounded-bl-full -z-0">
                            </div>

                            <h2
                                class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 relative z-10">
                                <i class="fas fa-search-location text-red-500"></i> Root Cause Analysis
                            </h2>

                            <?php if (!empty($article['root_cause'])): ?>
                                <div class="text-gray-700 dark:text-gray-300  bg-white dark:bg-gray-800 relative z-10">
                                    <?= htmlspecialchars($article['root_cause']) ?></div>
                            <?php endif; ?>

                            <?php if (!empty($article['root_cause_file'])): ?>
                                <div class="mt-4 inline-flex relative z-10">
                                    <a href="<?= getDownloadLink($article['root_cause_file']) ?>" target="_blank"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 dark:bg-red-900/30 dark:hover:bg-red-900/50 dark:text-red-400 rounded-xl text-sm font-medium transition-colors border border-red-100 dark:border-red-800/50">
                                        <i class="fas fa-file-download"></i> Download RCA Document
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Lessons Learned -->
                    <?php if (!empty($article['lessons_learned']) || !empty($article['lessons_learned_file'])): ?>
                        <div
                            class="bg-white dark:bg-gray-800 rounded-xl p-8 shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden">
                            <div
                                class="absolute top-0 right-0 w-32 h-32 bg-blue-50 dark:bg-blue-900/10 rounded-bl-full -z-0">
                            </div>

                            <h2
                                class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 relative z-10">
                                <i class="fas fa-lightbulb text-blue-500"></i> Lessons Learned
                            </h2>

                            <?php if (!empty($article['lessons_learned'])): ?>
                                <div class="text-gray-700 dark:text-gray-300  relative z-10">
                                    <?= htmlspecialchars($article['lessons_learned']) ?></div>
                            <?php endif; ?>

                            <?php if (!empty($article['lessons_learned_file'])): ?>
                                <div class="mt-4 inline-flex relative z-10">
                                    <a href="<?= getDownloadLink($article['lessons_learned_file']) ?>" target="_blank"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 dark:text-blue-400 rounded-xl text-sm font-medium transition-colors border border-blue-100 dark:border-blue-800/50">
                                        <i class="fas fa-file-download"></i> Download Lessons Document
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Timeline / Updates -->
                    <?php if (!empty($updates)): ?>
                        <div
                            class="bg-white dark:bg-gray-800 rounded-xl p-8 shadow-sm border border-gray-100 dark:border-gray-700">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                <i class="fas fa-history text-indigo-500"></i> Incident Timeline
                            </h2>

                            <div class="timeline-container ml-4 pt-2">
                                <?php foreach ($updates as $update): ?>
                                    <div class="relative pl-8 pb-6 last:pb-0">
                                        <!-- Node -->
                                        <div
                                            class="absolute left-[-16px] xl:left-[-16px] top-1.5 w-8 h-8 rounded-full border-4 border-white dark:border-gray-800 bg-indigo-500 shadow-sm z-10 flex items-center justify-center transform -translate-x-1/2">
                                        </div>

                                        <div
                                            class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                                            <div class="flex items-center justify-between gap-4 mb-2">
                                                <div
                                                    class="font-semibold text-sm text-gray-900 dark:text-white flex items-center gap-1.5">
                                                    <i class="fas fa-user-circle text-gray-400"></i>
                                                    <?= htmlspecialchars($update['user_name']) ?>
                                                </div>
                                                <div class="text-xs font-medium text-gray-500">
                                                    <?= date('M j, g:i A', strtotime($update['created_at'])) ?>
                                                </div>
                                            </div>
                                            <div class="text-sm text-gray-700 dark:text-gray-300 ">
                                                <?= htmlspecialchars($update['update_text']) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column: Meta Information -->
                <div class="space-y-6">

                    <!-- Resolution Stats -->
                    <div
                        class="bg-gradient-to-br from-green-500/10 to-teal-500/10 dark:from-green-500/5 dark:to-teal-500/5 border border-green-200/50 dark:border-green-800/50 rounded-xl p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div
                                class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400 flex items-center justify-center border border-green-200 dark:border-green-800 px-shrink-0">
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-green-800 dark:text-green-400">Successfully Resolved</h3>
                                <p class="text-xs text-green-600 dark:text-green-500">
                                    <?= date('M j, Y \a\t g:i A', strtotime($article['resolved_at'])) ?>
                                </p>
                            </div>
                        </div>
                        <?php if ($article['resolved_by_name']): ?>
                            <div
                                class="bg-white/60 dark:bg-gray-800/60 backdrop-blur-sm rounded-xl p-3 flex items-center gap-3">
                                <i class="fas fa-user-shield text-gray-400"></i>
                                <div class="text-sm">
                                    <div class="text-gray-500 dark:text-gray-400 text-xs">Resolved By</div>
                                    <div class="font-semibold text-gray-800 dark:text-gray-200">
                                        <?= htmlspecialchars($article['resolved_by_name']) ?></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php
                        $resolvers = !empty($article['resolvers']) ? json_decode($article['resolvers'], true) : [];
                        if (!empty($resolvers)):
                            ?>
                            <div
                                class="bg-white/60 dark:bg-gray-800/60 backdrop-blur-sm rounded-xl p-3 flex flex-col gap-2 mt-2">
                                <div class="text-gray-500 dark:text-gray-400 text-xs"><i class="fas fa-users mr-1"></i>
                                    Assisted By</div>
                                <div class="flex flex-wrap gap-1.5">
                                    <?php foreach ($resolvers as $resolver): ?>
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600 shadow-sm">
                                            <?= htmlspecialchars($resolver) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Meta Details Card -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                        <h3
                            class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-5 pb-3 border-b border-gray-100 dark:border-gray-700">
                            Incident Matrix</h3>

                        <ul class="space-y-4">
                            <li>
                                <div
                                    class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-semibold mb-1">
                                    Impact Level</div>
                                <?php
                                $impactClass = 'text-gray-600';
                                switch (strtolower($article['impact_level'])) {
                                    case 'critical':
                                        $impactClass = 'text-red-600 font-bold';
                                        break;
                                    case 'high':
                                        $impactClass = 'text-orange-600 font-semibold';
                                        break;
                                    case 'medium':
                                        $impactClass = 'text-yellow-600 font-semibold';
                                        break;
                                    case 'low':
                                        $impactClass = 'text-green-600 font-medium';
                                        break;
                                }
                                ?>
                                <div class="<?= $impactClass ?> flex items-center gap-1.5">
                                    <i class="fas fa-layer-group"></i> <?= htmlspecialchars($article['impact_level']) ?>
                                    Impact
                                </div>
                            </li>

                            <li>
                                <div
                                    class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-semibold mb-1">
                                    Priority</div>
                                <?php
                                $priorityClass = 'text-gray-600';
                                switch (strtolower($article['priority'])) {
                                    case 'urgent':
                                        $priorityClass = 'text-red-600 font-bold';
                                        break;
                                    case 'high':
                                        $priorityClass = 'text-orange-600 font-semibold';
                                        break;
                                    case 'medium':
                                        $priorityClass = 'text-blue-600 font-semibold';
                                        break;
                                }
                                ?>
                                <div class="<?= $priorityClass ?> flex items-center gap-1.5">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <?= htmlspecialchars($article['priority']) ?> Priority
                                </div>
                            </li>

                            <li>
                                <div
                                    class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-semibold mb-1">
                                    Affected Companies</div>
                                <div class="text-sm text-gray-800 dark:text-gray-200 font-medium leading-relaxed">
                                    <?= htmlspecialchars($affectedCompanies) ?>
                                </div>
                            </li>

                            <li>
                                <div
                                    class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-semibold mb-1">
                                    Reported By</div>
                                <div class="text-sm text-gray-800 dark:text-gray-200">
                                    <?= htmlspecialchars($article['reported_by_name'] ?? 'System') ?>
                                </div>
                            </li>

                            <li>
                                <div
                                    class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest font-semibold mb-1">
                                    Initial Report Time</div>
                                <div class="text-sm text-gray-800 dark:text-gray-200">
                                    <?= date('M j, Y g:i A', strtotime($article['created_at'])) ?>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Attachments Card -->
                    <?php if (!empty($attachments)): ?>
                        <div
                            class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                            <h3
                                class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 pb-3 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                                <i class="fas fa-paperclip"></i> Media & Logs
                            </h3>

                            <ul class="space-y-3">
                                <?php foreach ($attachments as $att):
                                    $ext = strtolower(pathinfo($att['file_name'], PATHINFO_EXTENSION));
                                    $icon = 'fa-file';
                                    $color = 'text-gray-500';

                                    if (in_array($ext, ['pdf'])) {
                                        $icon = 'fa-file-pdf';
                                        $color = 'text-red-500';
                                    } elseif (in_array($ext, ['doc', 'docx'])) {
                                        $icon = 'fa-file-word';
                                        $color = 'text-blue-500';
                                    } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                                        $icon = 'fa-file-image';
                                        $color = 'text-emerald-500';
                                    } elseif (in_array($ext, ['txt', 'log'])) {
                                        $icon = 'fa-file-alt';
                                        $color = 'text-gray-600 dark:text-gray-400';
                                    }
                                    ?>
                                    <li>
                                        <a href="<?= getDownloadLink($att['file_path']) ?>" target="_blank"
                                            class="flex items-start gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 border border-transparent hover:border-gray-200 dark:hover:border-gray-600 transition-colors group">
                                            <i class="far <?= $icon ?> <?= $color ?> text-lg mt-0.5"></i>
                                            <div class="overflow-hidden">
                                                <div
                                                    class="text-sm font-medium text-gray-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                                    <?= htmlspecialchars($att['file_name']) ?>
                                                </div>
                                                <?php if ($att['file_size']): ?>
                                                    <div class="text-xs text-gray-500 mt-0.5">
                                                        <?= round($att['file_size'] / 1024, 1) ?> KB
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </main>
    </div> <!-- /Content Wrapper -->
</body>

</html>