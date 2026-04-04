<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/includes/auth.php';
require_once __DIR__ . '/../../src/includes/activity_logger.php';
requireLogin();
requireRole('admin');

$currentUser = getCurrentUser();
$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

// Helper: delete physical files for an incident row + its attachment rows
function deleteIncidentFiles(array $incident, array $attachmentRows): void
{
    $files = [];
    foreach (['attachment_path', 'root_cause_file', 'lessons_learned_file'] as $col) {
        if (!empty($incident[$col])) {
            $files[] = $incident[$col];
        }
    }
    foreach ($attachmentRows as $row) {
        if (!empty($row['file_path'])) {
            $files[] = $row['file_path'];
        }
    }
    foreach (array_unique($files) as $relPath) {
        $abs = __DIR__ . '/../../' . $relPath;
        if (file_exists($abs)) {
            @unlink($abs);
        }
    }
}

// POST handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'delete_single') {
            $incidentId = (int)($_POST['incident_id'] ?? 0);
            if ($incidentId < 1) {
                throw new Exception('Invalid incident ID');
            }

            // Fetch incident meta
            $stmt = $pdo->prepare(
                "SELECT incident_ref, attachment_path, root_cause_file, lessons_learned_file
                 FROM incidents WHERE incident_id = ?"
            );
            $stmt->execute([$incidentId]);
            $incident = $stmt->fetch();
            if (!$incident) {
                throw new Exception('Incident not found');
            }

            // Fetch attachment file paths
            $stmt = $pdo->prepare("SELECT file_path FROM incident_attachments WHERE incident_id = ?");
            $stmt->execute([$incidentId]);
            $attachmentRows = $stmt->fetchAll();

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("DELETE FROM incidents WHERE incident_id = ?");
            $stmt->execute([$incidentId]);

            deleteIncidentFiles($incident, $attachmentRows);

            $pdo->commit();

            logActivity(
                $_SESSION['user_id'],
                'incident_deleted',
                "Admin deleted incident {$incident['incident_ref']} (ID: $incidentId)"
            );

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => "Incident {$incident['incident_ref']} deleted successfully"
            ];

        } elseif ($action === 'bulk_delete') {
            $rawIds = $_POST['incident_ids'] ?? [];
            $ids = array_values(array_filter(
                array_unique(array_map('intval', (array)$rawIds)),
                fn($id) => $id > 0
            ));

            if (empty($ids)) {
                throw new Exception('No incidents selected');
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            // Fetch incident meta for all selected IDs
            $stmt = $pdo->prepare(
                "SELECT incident_id, incident_ref, attachment_path, root_cause_file, lessons_learned_file
                 FROM incidents WHERE incident_id IN ($placeholders)"
            );
            $stmt->execute($ids);
            $incidents = $stmt->fetchAll();

            if (empty($incidents)) {
                throw new Exception('No matching incidents found');
            }

            // Fetch all attachment rows for these incidents
            $stmt = $pdo->prepare(
                "SELECT file_path FROM incident_attachments WHERE incident_id IN ($placeholders)"
            );
            $stmt->execute($ids);
            $attachmentRows = $stmt->fetchAll();

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("DELETE FROM incidents WHERE incident_id IN ($placeholders)");
            $stmt->execute($ids);
            $deletedCount = $stmt->rowCount();

            // Delete physical files for each incident
            $allFiles = array_column($attachmentRows, 'file_path');
            foreach ($incidents as $inc) {
                foreach (['attachment_path', 'root_cause_file', 'lessons_learned_file'] as $col) {
                    if (!empty($inc[$col])) {
                        $allFiles[] = $inc[$col];
                    }
                }
            }
            foreach (array_unique(array_filter($allFiles)) as $relPath) {
                $abs = __DIR__ . '/../../' . $relPath;
                if (file_exists($abs)) {
                    @unlink($abs);
                }
            }

            $pdo->commit();

            $refs = implode(', ', array_column($incidents, 'incident_ref'));
            logActivity(
                $_SESSION['user_id'],
                'incident_deleted',
                "Admin bulk-deleted $deletedCount incident(s): $refs"
            );

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => "$deletedCount incident(s) deleted successfully"
            ];
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['message'] = ['type' => 'error', 'text' => $e->getMessage()];
    }

    header('Location: delete_incidents.php');
    exit;
}

// GET: build filters and fetch data
$search        = trim($_GET['search'] ?? '');
$statusFilter  = $_GET['status'] ?? '';
$serviceFilter = (int)($_GET['service_id'] ?? 0);
$page          = max(1, (int)($_GET['page'] ?? 1));
$perPage       = 15;
$offset        = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($search !== '') {
    $where[]  = "(i.incident_ref LIKE ? OR s.service_name LIKE ?)";
    $term     = "%$search%";
    $params[] = $term;
    $params[] = $term;
}
if ($statusFilter !== '') {
    $where[]  = "i.status = ?";
    $params[] = $statusFilter;
}
if ($serviceFilter > 0) {
    $where[]  = "i.service_id = ?";
    $params[] = $serviceFilter;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM incidents i
     JOIN services s ON i.service_id = s.service_id
     $whereClause"
);
$countStmt->execute($params);
$totalCount = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($totalCount / $perPage);

// Data
$dataStmt = $pdo->prepare(
    "SELECT i.incident_id, i.incident_ref, i.description, i.impact_level,
            i.priority, i.status, i.created_at,
            s.service_name, u.username AS reported_by_username
     FROM incidents i
     JOIN services s ON i.service_id = s.service_id
     JOIN users    u ON i.reported_by = u.user_id
     $whereClause
     ORDER BY i.created_at DESC
     LIMIT $perPage OFFSET $offset"
);
$dataStmt->execute($params);
$incidents = $dataStmt->fetchAll();

// Services list for filter dropdown
$services = $pdo->query(
    "SELECT service_id, service_name FROM services ORDER BY service_name"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <script>if(localStorage.getItem('theme')==='dark'||(!localStorage.getItem('theme')&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark')}else{document.documentElement.classList.remove('dark')}</script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Incidents - eTranzact</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>

<body class="relative min-h-screen">
    <!-- Background -->
    <div class="fixed inset-0 z-0">
        <img src="<?= url('assets/mainbg.jpg') ?>" alt="Background" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-white/90 dark:bg-gray-900/95"></div>
    </div>

    <!-- Content Wrapper -->
    <div class="relative z-10">
        <?php include __DIR__ . '/../../src/includes/admin_navbar.php'; ?>
        <?php include __DIR__ . '/../../src/includes/loading.php'; ?>

        <main class="py-8" x-data="{
            showSingleModal: false,
            singleDeleteId: null,
            singleDeleteRef: ''
        }">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">
                        <i class="fas fa-trash-alt mr-3 text-red-500"></i>Delete Incidents
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Permanently remove reported incidents and all related data. This action cannot be undone.
                    </p>
                </div>

                <!-- Message Banner -->
                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-lg <?= $message['type'] === 'success'
                        ? 'bg-green-50 border border-green-200 dark:bg-green-900/20 dark:border-green-800'
                        : 'bg-red-50 border border-red-200 dark:bg-red-900/20 dark:border-red-800' ?>">
                        <p class="text-sm font-medium <?= $message['type'] === 'success'
                            ? 'text-green-800 dark:text-green-300'
                            : 'text-red-800 dark:text-red-300' ?>">
                            <i class="fas <?= $message['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
                            <?= htmlspecialchars($message['text']) ?>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Filter Bar -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-6">
                    <form method="GET" class="space-y-3 sm:space-y-0 sm:grid sm:grid-cols-2 lg:grid-cols-5 sm:gap-3">
                        <div class="sm:col-span-2">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                                placeholder="Search by ref or service..."
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <select name="status"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Statuses</option>
                            <option value="pending"  <?= $statusFilter === 'pending'  ? 'selected' : '' ?>>Pending</option>
                            <option value="resolved" <?= $statusFilter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                        </select>
                        <select name="service_id"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Services</option>
                            <?php foreach ($services as $svc): ?>
                                <option value="<?= $svc['service_id'] ?>" <?= $serviceFilter === (int)$svc['service_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($svc['service_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="flex gap-3 sm:col-span-2 lg:col-span-1">
                            <button type="submit"
                                class="flex-1 sm:flex-none px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium">
                                <i class="fas fa-search mr-2"></i>Filter
                            </button>
                            <?php if ($search !== '' || $statusFilter !== '' || $serviceFilter > 0): ?>
                                <a href="delete_incidents.php"
                                    class="flex-1 sm:flex-none px-4 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors text-center font-medium">
                                    Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Bulk Action Toolbar -->
                <div id="bulkToolbar" class="hidden mb-4 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl flex items-center justify-between">
                    <span class="text-sm font-medium text-red-800 dark:text-red-200">
                        <span id="selectedCount">0</span> incident(s) selected
                    </span>
                    <button type="button" id="bulkDeleteBtn"
                        class="inline-flex items-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-trash mr-2"></i>Delete Selected
                    </button>
                </div>

                <!-- Hidden bulk delete form -->
                <form id="bulkDeleteForm" method="POST" class="hidden">
                    <input type="hidden" name="action" value="bulk_delete">
                    <div id="bulkDeleteInputs"></div>
                </form>

                <!-- Bulk Confirmation Modal -->
                <div id="bulkDeleteModal" class="fixed inset-0 z-50 hidden items-center justify-center">
                    <div class="absolute inset-0 bg-black/50" id="modalBackdrop"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mr-3">
                                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Delete Incidents</h3>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            You are about to permanently delete <strong id="modalCount" class="text-gray-900 dark:text-white">0</strong> incident(s).
                        </p>
                        <p class="text-sm text-red-600 dark:text-red-400 mb-6">
                            All related data (downtime records, updates, attachments) and uploaded files will be permanently removed. This action cannot be undone.
                        </p>
                        <div class="flex justify-end gap-3">
                            <button type="button" id="modalCancelBtn"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Cancel
                            </button>
                            <button type="button" id="modalConfirmBtn"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <i class="fas fa-trash mr-2"></i>Delete
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Single Delete Confirmation Modal (Alpine) -->
                <div x-show="showSingleModal" x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center">
                    <div class="absolute inset-0 bg-black/50" @click="showSingleModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mr-3">
                                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Delete Incident</h3>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            You are about to permanently delete incident
                            <strong x-text="singleDeleteRef" class="text-gray-900 dark:text-white font-mono"></strong>.
                        </p>
                        <p class="text-sm text-red-600 dark:text-red-400 mb-6">
                            All related data (downtime records, updates, attachments) and uploaded files will be permanently removed. This action cannot be undone.
                        </p>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showSingleModal = false"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Cancel
                            </button>
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="delete_single">
                                <input type="hidden" name="incident_id" :value="singleDeleteId">
                                <button type="submit"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    <i class="fas fa-trash mr-2"></i>Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Incidents Table -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-3 w-10">
                                        <input type="checkbox" id="selectAll"
                                            class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500 cursor-pointer">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Ref</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Service</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Impact</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Reported By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($incidents as $inc): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-4 w-10">
                                            <input type="checkbox" class="incident-checkbox w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500 cursor-pointer"
                                                value="<?= $inc['incident_id'] ?>">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm font-mono font-semibold text-gray-900 dark:text-white">
                                                <?= htmlspecialchars($inc['incident_ref'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            <?= htmlspecialchars($inc['service_name']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs">
                                            <span class="line-clamp-2" title="<?= htmlspecialchars($inc['description'] ?? '') ?>">
                                                <?= htmlspecialchars(mb_strimwidth($inc['description'] ?? '', 0, 80, '...')) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                <?= $inc['status'] === 'resolved'
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                                                    : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' ?>">
                                                <?= ucfirst($inc['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                <?php echo match($inc['impact_level'] ?? '') {
                                                    'Critical' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                                    'High'     => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                                                    'Medium'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                                    default    => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                                }; ?>">
                                                <?= htmlspecialchars($inc['impact_level'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            @<?= htmlspecialchars($inc['reported_by_username']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?= date('M j, Y', strtotime($inc['created_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <button type="button"
                                                @click="singleDeleteId = <?= $inc['incident_id'] ?>; singleDeleteRef = '<?= htmlspecialchars($inc['incident_ref'] ?? 'this incident', ENT_QUOTES) ?>'; showSingleModal = true"
                                                class="inline-flex items-center px-3 py-2 bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50 text-xs font-semibold rounded-lg transition-colors">
                                                <i class="fas fa-trash mr-1"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($incidents)): ?>
                                    <tr>
                                        <td colspan="9" class="px-6 py-16 text-center">
                                            <i class="fas fa-inbox fa-2x text-gray-400 dark:text-gray-600 mb-3 block"></i>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">No incidents found matching your criteria</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalCount) ?> of <?= $totalCount ?> incidents
                            </div>
                            <div class="flex gap-3">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&service_id=<?= $serviceFilter ?>"
                                        class="px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                        Previous
                                    </a>
                                <?php endif; ?>
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&service_id=<?= $serviceFilter ?>"
                                        class="px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>

<script>
(function () {
    const selectAll      = document.getElementById('selectAll');
    const toolbar        = document.getElementById('bulkToolbar');
    const countEl        = document.getElementById('selectedCount');
    const bulkDeleteBtn  = document.getElementById('bulkDeleteBtn');
    const modal          = document.getElementById('bulkDeleteModal');
    const modalBackdrop  = document.getElementById('modalBackdrop');
    const modalCount     = document.getElementById('modalCount');
    const modalCancelBtn = document.getElementById('modalCancelBtn');
    const modalConfirmBtn = document.getElementById('modalConfirmBtn');
    const bulkDeleteForm  = document.getElementById('bulkDeleteForm');
    const bulkDeleteInputs = document.getElementById('bulkDeleteInputs');

    function getChecked() {
        return Array.from(document.querySelectorAll('.incident-checkbox:checked'));
    }

    function updateToolbar() {
        const checked = getChecked();
        if (checked.length > 0) {
            toolbar.classList.remove('hidden');
            toolbar.classList.add('flex');
        } else {
            toolbar.classList.add('hidden');
            toolbar.classList.remove('flex');
        }
        countEl.textContent = checked.length;
        const all = document.querySelectorAll('.incident-checkbox');
        selectAll.checked = all.length > 0 && checked.length === all.length;
        selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
    }

    selectAll.addEventListener('change', function () {
        document.querySelectorAll('.incident-checkbox').forEach(cb => cb.checked = this.checked);
        updateToolbar();
    });

    document.querySelectorAll('.incident-checkbox').forEach(cb => {
        cb.addEventListener('change', updateToolbar);
    });

    bulkDeleteBtn.addEventListener('click', function () {
        const checked = getChecked();
        if (checked.length === 0) return;
        modalCount.textContent = checked.length;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    });

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    modalBackdrop.addEventListener('click', closeModal);
    modalCancelBtn.addEventListener('click', closeModal);

    modalConfirmBtn.addEventListener('click', function () {
        const checked = getChecked();
        bulkDeleteInputs.innerHTML = '';
        checked.forEach(cb => {
            const input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = 'incident_ids[]';
            input.value = cb.value;
            bulkDeleteInputs.appendChild(input);
        });
        bulkDeleteForm.submit();
    });
})();
</script>
</body>

</html>
