<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/includes/auth.php';
require_once __DIR__ . '/../../src/includes/activity_logger.php';
requireLogin();
requireRole('admin');

$currentUser = getCurrentUser();
$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

// Helper: unlink physical files safely
function unlinkFiles(array $paths): void
{
    foreach (array_unique(array_filter($paths)) as $relPath) {
        $abs = __DIR__ . '/../../' . $relPath;
        if (file_exists($abs)) {
            @unlink($abs);
        }
    }
}

// POST handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $redirectTab = 'downtime';

    try {
        // ── DOWNTIME: single delete ──────────────────────────────────────────
        if ($action === 'delete_single') {
            $incidentId = (int)($_POST['incident_id'] ?? 0);
            if ($incidentId < 1) throw new Exception('Invalid incident ID');

            $stmt = $pdo->prepare(
                "SELECT incident_ref, attachment_path, root_cause_file, lessons_learned_file
                 FROM incidents WHERE incident_id = ?"
            );
            $stmt->execute([$incidentId]);
            $incident = $stmt->fetch();
            if (!$incident) throw new Exception('Incident not found');

            $stmt = $pdo->prepare("SELECT file_path FROM incident_attachments WHERE incident_id = ?");
            $stmt->execute([$incidentId]);
            $attachRows = $stmt->fetchAll();

            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM incidents WHERE incident_id = ?")->execute([$incidentId]);

            $files = array_column($attachRows, 'file_path');
            foreach (['attachment_path', 'root_cause_file', 'lessons_learned_file'] as $col) {
                if (!empty($incident[$col])) $files[] = $incident[$col];
            }
            unlinkFiles($files);
            $pdo->commit();

            logActivity($_SESSION['user_id'], 'incident_deleted',
                "Admin deleted downtime incident {$incident['incident_ref']} (ID: $incidentId)");
            $_SESSION['message'] = ['type' => 'success',
                'text' => "Incident {$incident['incident_ref']} deleted successfully"];

        // ── DOWNTIME: bulk delete ────────────────────────────────────────────
        } elseif ($action === 'bulk_delete') {
            $ids = array_values(array_filter(
                array_unique(array_map('intval', (array)($_POST['incident_ids'] ?? []))),
                fn($id) => $id > 0
            ));
            if (empty($ids)) throw new Exception('No incidents selected');

            $ph = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare(
                "SELECT incident_id, incident_ref, attachment_path, root_cause_file, lessons_learned_file
                 FROM incidents WHERE incident_id IN ($ph)"
            );
            $stmt->execute($ids);
            $incidents = $stmt->fetchAll();
            if (empty($incidents)) throw new Exception('No matching incidents found');

            $stmt = $pdo->prepare("SELECT file_path FROM incident_attachments WHERE incident_id IN ($ph)");
            $stmt->execute($ids);
            $attachRows = $stmt->fetchAll();

            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM incidents WHERE incident_id IN ($ph)")->execute($ids);

            $files = array_column($attachRows, 'file_path');
            foreach ($incidents as $inc) {
                foreach (['attachment_path', 'root_cause_file', 'lessons_learned_file'] as $col) {
                    if (!empty($inc[$col])) $files[] = $inc[$col];
                }
            }
            unlinkFiles($files);
            $pdo->commit();

            $count = count($incidents);
            $refs  = implode(', ', array_column($incidents, 'incident_ref'));
            logActivity($_SESSION['user_id'], 'incident_deleted',
                "Admin bulk-deleted $count downtime incident(s): $refs");
            $_SESSION['message'] = ['type' => 'success', 'text' => "$count incident(s) deleted successfully"];

        // ── SECURITY: single delete ──────────────────────────────────────────
        } elseif ($action === 'delete_security_single') {
            $redirectTab = 'security';
            $incidentId  = (int)($_POST['incident_id'] ?? 0);
            if ($incidentId < 1) throw new Exception('Invalid incident ID');

            $stmt = $pdo->prepare("SELECT incident_ref, attachment_path FROM security_incidents WHERE id = ?");
            $stmt->execute([$incidentId]);
            $incident = $stmt->fetch();
            if (!$incident) throw new Exception('Security incident not found');

            $stmt = $pdo->prepare("SELECT file_path FROM security_incident_attachments WHERE incident_id = ?");
            $stmt->execute([$incidentId]);
            $attachRows = $stmt->fetchAll();

            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM security_incident_updates WHERE incident_id = ?")->execute([$incidentId]);
            $pdo->prepare("DELETE FROM security_incident_attachments WHERE incident_id = ?")->execute([$incidentId]);
            $pdo->prepare("DELETE FROM security_incidents WHERE id = ?")->execute([$incidentId]);

            $files = array_column($attachRows, 'file_path');
            if (!empty($incident['attachment_path'])) $files[] = $incident['attachment_path'];
            unlinkFiles($files);
            $pdo->commit();

            logActivity($_SESSION['user_id'], 'security_incident_deleted',
                "Admin deleted security incident {$incident['incident_ref']} (ID: $incidentId)");
            $_SESSION['message'] = ['type' => 'success',
                'text' => "Security incident {$incident['incident_ref']} deleted successfully"];

        // ── SECURITY: bulk delete ────────────────────────────────────────────
        } elseif ($action === 'bulk_delete_security') {
            $redirectTab = 'security';
            $ids = array_values(array_filter(
                array_unique(array_map('intval', (array)($_POST['incident_ids'] ?? []))),
                fn($id) => $id > 0
            ));
            if (empty($ids)) throw new Exception('No incidents selected');

            $ph = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare(
                "SELECT id, incident_ref, attachment_path FROM security_incidents WHERE id IN ($ph)"
            );
            $stmt->execute($ids);
            $incidents = $stmt->fetchAll();
            if (empty($incidents)) throw new Exception('No matching security incidents found');

            $stmt = $pdo->prepare("SELECT file_path FROM security_incident_attachments WHERE incident_id IN ($ph)");
            $stmt->execute($ids);
            $attachRows = $stmt->fetchAll();

            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM security_incident_updates WHERE incident_id IN ($ph)")->execute($ids);
            $pdo->prepare("DELETE FROM security_incident_attachments WHERE incident_id IN ($ph)")->execute($ids);
            $pdo->prepare("DELETE FROM security_incidents WHERE id IN ($ph)")->execute($ids);

            $files = array_column($attachRows, 'file_path');
            foreach ($incidents as $inc) {
                if (!empty($inc['attachment_path'])) $files[] = $inc['attachment_path'];
            }
            unlinkFiles($files);
            $pdo->commit();

            $count = count($incidents);
            $refs  = implode(', ', array_column($incidents, 'incident_ref'));
            logActivity($_SESSION['user_id'], 'security_incident_deleted',
                "Admin bulk-deleted $count security incident(s): $refs");
            $_SESSION['message'] = ['type' => 'success', 'text' => "$count security incident(s) deleted successfully"];

        // ── FRAUD: single delete ─────────────────────────────────────────────
        } elseif ($action === 'delete_fraud_single') {
            $redirectTab = 'fraud';
            $incidentId  = (int)($_POST['incident_id'] ?? 0);
            if ($incidentId < 1) throw new Exception('Invalid incident ID');

            $stmt = $pdo->prepare("SELECT incident_ref, attachment_path FROM fraud_incidents WHERE id = ?");
            $stmt->execute([$incidentId]);
            $incident = $stmt->fetch();
            if (!$incident) throw new Exception('Fraud incident not found');

            $stmt = $pdo->prepare("SELECT file_path FROM fraud_incident_attachments WHERE incident_id = ?");
            $stmt->execute([$incidentId]);
            $attachRows = $stmt->fetchAll();

            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM fraud_incident_updates WHERE incident_id = ?")->execute([$incidentId]);
            $pdo->prepare("DELETE FROM fraud_incident_attachments WHERE incident_id = ?")->execute([$incidentId]);
            $pdo->prepare("DELETE FROM fraud_incidents WHERE id = ?")->execute([$incidentId]);

            $files = array_column($attachRows, 'file_path');
            if (!empty($incident['attachment_path'])) $files[] = $incident['attachment_path'];
            unlinkFiles($files);
            $pdo->commit();

            logActivity($_SESSION['user_id'], 'fraud_incident_deleted',
                "Admin deleted fraud incident {$incident['incident_ref']} (ID: $incidentId)");
            $_SESSION['message'] = ['type' => 'success',
                'text' => "Fraud incident {$incident['incident_ref']} deleted successfully"];

        // ── FRAUD: bulk delete ───────────────────────────────────────────────
        } elseif ($action === 'bulk_delete_fraud') {
            $redirectTab = 'fraud';
            $ids = array_values(array_filter(
                array_unique(array_map('intval', (array)($_POST['incident_ids'] ?? []))),
                fn($id) => $id > 0
            ));
            if (empty($ids)) throw new Exception('No incidents selected');

            $ph = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare(
                "SELECT id, incident_ref, attachment_path FROM fraud_incidents WHERE id IN ($ph)"
            );
            $stmt->execute($ids);
            $incidents = $stmt->fetchAll();
            if (empty($incidents)) throw new Exception('No matching fraud incidents found');

            $stmt = $pdo->prepare("SELECT file_path FROM fraud_incident_attachments WHERE incident_id IN ($ph)");
            $stmt->execute($ids);
            $attachRows = $stmt->fetchAll();

            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM fraud_incident_updates WHERE incident_id IN ($ph)")->execute($ids);
            $pdo->prepare("DELETE FROM fraud_incident_attachments WHERE incident_id IN ($ph)")->execute($ids);
            $pdo->prepare("DELETE FROM fraud_incidents WHERE id IN ($ph)")->execute($ids);

            $files = array_column($attachRows, 'file_path');
            foreach ($incidents as $inc) {
                if (!empty($inc['attachment_path'])) $files[] = $inc['attachment_path'];
            }
            unlinkFiles($files);
            $pdo->commit();

            $count = count($incidents);
            $refs  = implode(', ', array_column($incidents, 'incident_ref'));
            logActivity($_SESSION['user_id'], 'fraud_incident_deleted',
                "Admin bulk-deleted $count fraud incident(s): $refs");
            $_SESSION['message'] = ['type' => 'success', 'text' => "$count fraud incident(s) deleted successfully"];
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['message'] = ['type' => 'error', 'text' => $e->getMessage()];
    }

    header("Location: delete_incidents.php?tab=$redirectTab");
    exit;
}

// ── GET: active tab & shared params ────────────────────────────────────────
$allowedTabs = ['downtime', 'security', 'fraud'];
$activeTab   = in_array($_GET['tab'] ?? '', $allowedTabs) ? $_GET['tab'] : 'downtime';
$search       = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 15;
$offset       = ($page - 1) * $perPage;

$incidents  = [];
$totalCount = 0;
$totalPages = 0;
$services   = [];

// ── Downtime data ────────────────────────────────────────────────────────
if ($activeTab === 'downtime') {
    $serviceFilter = (int)($_GET['service_id'] ?? 0);
    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[]  = "(i.incident_ref LIKE ? OR s.service_name LIKE ?)";
        $term = "%$search%";
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
    $wc = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM incidents i JOIN services s ON i.service_id = s.service_id $wc");
    $stmt->execute($params);
    $totalCount = (int)$stmt->fetchColumn();
    $totalPages = (int)ceil($totalCount / $perPage);

    $stmt = $pdo->prepare(
        "SELECT i.incident_id, i.incident_ref, i.description, i.impact_level,
                i.priority, i.status, i.created_at,
                s.service_name, u.username AS reported_by_username
         FROM incidents i
         JOIN services s ON i.service_id = s.service_id
         JOIN users    u ON i.reported_by = u.user_id
         $wc
         ORDER BY i.created_at DESC LIMIT $perPage OFFSET $offset"
    );
    $stmt->execute($params);
    $incidents = $stmt->fetchAll();

    $services = $pdo->query("SELECT service_id, service_name FROM services ORDER BY service_name")->fetchAll();

// ── Security data ─────────────────────────────────────────────────────────
} elseif ($activeTab === 'security') {
    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[]  = "(si.incident_ref LIKE ? OR si.threat_type LIKE ?)";
        $term = "%$search%";
        $params[] = $term;
        $params[] = $term;
    }
    if ($statusFilter !== '') {
        $where[]  = "si.status = ?";
        $params[] = $statusFilter;
    }
    $wc = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM security_incidents si $wc");
    $stmt->execute($params);
    $totalCount = (int)$stmt->fetchColumn();
    $totalPages = (int)ceil($totalCount / $perPage);

    $stmt = $pdo->prepare(
        "SELECT si.id, si.incident_ref, si.description, si.threat_type,
                si.impact_level, si.containment_status, si.status, si.created_at,
                u.username AS reported_by_username
         FROM security_incidents si
         JOIN users u ON si.reported_by = u.user_id
         $wc
         ORDER BY si.created_at DESC LIMIT $perPage OFFSET $offset"
    );
    $stmt->execute($params);
    $incidents = $stmt->fetchAll();

// ── Fraud data ────────────────────────────────────────────────────────────
} elseif ($activeTab === 'fraud') {
    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[]  = "(fi.incident_ref LIKE ? OR fi.fraud_type LIKE ?)";
        $term = "%$search%";
        $params[] = $term;
        $params[] = $term;
    }
    if ($statusFilter !== '') {
        $where[]  = "fi.status = ?";
        $params[] = $statusFilter;
    }
    $wc = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM fraud_incidents fi $wc");
    $stmt->execute($params);
    $totalCount = (int)$stmt->fetchColumn();
    $totalPages = (int)ceil($totalCount / $perPage);

    $stmt = $pdo->prepare(
        "SELECT fi.id, fi.incident_ref, fi.description, fi.fraud_type,
                fi.financial_impact, fi.impact_level, fi.status, fi.created_at,
                u.username AS reported_by_username
         FROM fraud_incidents fi
         JOIN users u ON fi.reported_by = u.user_id
         $wc
         ORDER BY fi.created_at DESC LIMIT $perPage OFFSET $offset"
    );
    $stmt->execute($params);
    $incidents = $stmt->fetchAll();
}

// ── Shared badge helpers ──────────────────────────────────────────────────
function impactBadge(string $level): string {
    return match($level) {
        'Critical' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        'High'     => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
        'Medium'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        default    => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    };
}
function statusBadge(string $status): string {
    return $status === 'resolved'
        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
}
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
            showSingleModal: false,  singleDeleteId: null,  singleDeleteRef: '',
            showSecModal:    false,  secDeleteId:    null,  secDeleteRef:    '',
            showFrdModal:    false,  frdDeleteId:    null,  frdDeleteRef:    ''
        }">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Header -->
                <div class="mb-6">
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

                <!-- Tab Bar -->
                <div class="flex gap-1 mb-6 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl w-fit">
                    <?php
                    $tabs = [
                        'downtime' => ['label' => 'Downtime', 'icon' => 'fa-server'],
                        'security' => ['label' => 'Security',  'icon' => 'fa-shield-halved'],
                        'fraud'    => ['label' => 'Fraud',     'icon' => 'fa-user-secret'],
                    ];
                    foreach ($tabs as $tab => $meta):
                        $isActive = $activeTab === $tab;
                    ?>
                        <a href="?tab=<?= $tab ?>"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors inline-flex items-center gap-2
                                <?= $isActive
                                    ? 'bg-red-600 text-white shadow-sm'
                                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-white dark:hover:bg-gray-700' ?>">
                            <i class="fas <?= $meta['icon'] ?>"></i>
                            <?= $meta['label'] ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Filter Bar -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-6">
                    <form method="GET" class="space-y-3 sm:space-y-0 sm:grid sm:grid-cols-2 lg:grid-cols-5 sm:gap-3">
                        <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab) ?>">
                        <div class="sm:col-span-2">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                                placeholder="<?= $activeTab === 'downtime' ? 'Search by ref or service...' : ($activeTab === 'security' ? 'Search by ref or threat type...' : 'Search by ref or fraud type...') ?>"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <select name="status"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Statuses</option>
                            <option value="pending"  <?= $statusFilter === 'pending'  ? 'selected' : '' ?>>Pending</option>
                            <option value="resolved" <?= $statusFilter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                        </select>
                        <?php if ($activeTab === 'downtime'): ?>
                            <?php $serviceFilter = (int)($_GET['service_id'] ?? 0); ?>
                            <select name="service_id"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Services</option>
                                <?php foreach ($services as $svc): ?>
                                    <option value="<?= $svc['service_id'] ?>" <?= $serviceFilter === (int)$svc['service_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($svc['service_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <div></div><!-- spacer to keep grid alignment -->
                        <?php endif; ?>
                        <div class="flex gap-3 sm:col-span-2 lg:col-span-1">
                            <button type="submit"
                                class="flex-1 sm:flex-none px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium">
                                <i class="fas fa-search mr-2"></i>Filter
                            </button>
                            <?php
                            $hasFilter = $search !== '' || $statusFilter !== '' ||
                                         ($activeTab === 'downtime' && (int)($_GET['service_id'] ?? 0) > 0);
                            if ($hasFilter): ?>
                                <a href="?tab=<?= htmlspecialchars($activeTab) ?>"
                                    class="flex-1 sm:flex-none px-4 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors text-center font-medium">
                                    Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <?php
                // ─────────────────────────────────────────────────────────────
                // Determine per-tab config
                // ─────────────────────────────────────────────────────────────
                $tabConfig = [
                    'downtime' => [
                        'toolbarId'    => 'bulkToolbar',
                        'formId'       => 'bulkDeleteForm',
                        'inputsId'     => 'bulkDeleteInputs',
                        'modalId'      => 'bulkDeleteModal',
                        'countId'      => 'modalCount',
                        'cancelId'     => 'modalCancelBtn',
                        'confirmId'    => 'modalConfirmBtn',
                        'backdropId'   => 'modalBackdrop',
                        'cbClass'      => 'incident-checkbox',
                        'selectAllId'  => 'selectAll',
                        'selectedId'   => 'selectedCount',
                        'bulkBtnId'    => 'bulkDeleteBtn',
                        'bulkAction'   => 'bulk_delete',
                        'alpineModal'  => 'showSingleModal',
                        'alpineId'     => 'singleDeleteId',
                        'alpineRef'    => 'singleDeleteRef',
                        'singleAction' => 'delete_single',
                    ],
                    'security' => [
                        'toolbarId'    => 'secBulkToolbar',
                        'formId'       => 'secBulkDeleteForm',
                        'inputsId'     => 'secBulkDeleteInputs',
                        'modalId'      => 'secBulkDeleteModal',
                        'countId'      => 'secModalCount',
                        'cancelId'     => 'secModalCancelBtn',
                        'confirmId'    => 'secModalConfirmBtn',
                        'backdropId'   => 'secModalBackdrop',
                        'cbClass'      => 'security-checkbox',
                        'selectAllId'  => 'secSelectAll',
                        'selectedId'   => 'secSelectedCount',
                        'bulkBtnId'    => 'secBulkDeleteBtn',
                        'bulkAction'   => 'bulk_delete_security',
                        'alpineModal'  => 'showSecModal',
                        'alpineId'     => 'secDeleteId',
                        'alpineRef'    => 'secDeleteRef',
                        'singleAction' => 'delete_security_single',
                    ],
                    'fraud' => [
                        'toolbarId'    => 'frdBulkToolbar',
                        'formId'       => 'frdBulkDeleteForm',
                        'inputsId'     => 'frdBulkDeleteInputs',
                        'modalId'      => 'frdBulkDeleteModal',
                        'countId'      => 'frdModalCount',
                        'cancelId'     => 'frdModalCancelBtn',
                        'confirmId'    => 'frdModalConfirmBtn',
                        'backdropId'   => 'frdModalBackdrop',
                        'cbClass'      => 'fraud-checkbox',
                        'selectAllId'  => 'frdSelectAll',
                        'selectedId'   => 'frdSelectedCount',
                        'bulkBtnId'    => 'frdBulkDeleteBtn',
                        'bulkAction'   => 'bulk_delete_fraud',
                        'alpineModal'  => 'showFrdModal',
                        'alpineId'     => 'frdDeleteId',
                        'alpineRef'    => 'frdDeleteRef',
                        'singleAction' => 'delete_fraud_single',
                    ],
                ];
                $tc = $tabConfig[$activeTab];
                ?>

                <!-- Bulk Action Toolbar -->
                <div id="<?= $tc['toolbarId'] ?>" class="hidden mb-4 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl flex items-center justify-between">
                    <span class="text-sm font-medium text-red-800 dark:text-red-200">
                        <span id="<?= $tc['selectedId'] ?>">0</span> incident(s) selected
                    </span>
                    <button type="button" id="<?= $tc['bulkBtnId'] ?>"
                        class="inline-flex items-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-trash mr-2"></i>Delete Selected
                    </button>
                </div>

                <!-- Hidden bulk delete form -->
                <form id="<?= $tc['formId'] ?>" method="POST" class="hidden">
                    <input type="hidden" name="action" value="<?= $tc['bulkAction'] ?>">
                    <div id="<?= $tc['inputsId'] ?>"></div>
                </form>

                <!-- Bulk Confirmation Modal -->
                <div id="<?= $tc['modalId'] ?>" class="fixed inset-0 z-50 hidden items-center justify-center">
                    <div class="absolute inset-0 bg-black/50" id="<?= $tc['backdropId'] ?>"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mr-3">
                                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Delete Incidents</h3>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            You are about to permanently delete <strong id="<?= $tc['countId'] ?>" class="text-gray-900 dark:text-white">0</strong> incident(s).
                        </p>
                        <p class="text-sm text-red-600 dark:text-red-400 mb-6">
                            All related data and uploaded files will be permanently removed. This action cannot be undone.
                        </p>
                        <div class="flex justify-end gap-3">
                            <button type="button" id="<?= $tc['cancelId'] ?>"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Cancel
                            </button>
                            <button type="button" id="<?= $tc['confirmId'] ?>"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <i class="fas fa-trash mr-2"></i>Delete
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Single Delete Confirmation Modal (Alpine) -->
                <div x-show="<?= $tc['alpineModal'] ?>" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
                    <div class="absolute inset-0 bg-black/50" @click="<?= $tc['alpineModal'] ?> = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mr-3">
                                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Delete Incident</h3>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            You are about to permanently delete incident
                            <strong x-text="<?= $tc['alpineRef'] ?>" class="text-gray-900 dark:text-white font-mono"></strong>.
                        </p>
                        <p class="text-sm text-red-600 dark:text-red-400 mb-6">
                            All related data and uploaded files will be permanently removed. This action cannot be undone.
                        </p>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="<?= $tc['alpineModal'] ?> = false"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Cancel
                            </button>
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="<?= $tc['singleAction'] ?>">
                                <input type="hidden" name="incident_id" :value="<?= $tc['alpineId'] ?>">
                                <button type="submit"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    <i class="fas fa-trash mr-2"></i>Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ── DOWNTIME TABLE ─────────────────────────────────────── -->
                <?php if ($activeTab === 'downtime'): ?>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-3 w-10">
                                        <input type="checkbox" id="<?= $tc['selectAllId'] ?>"
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
                                            <input type="checkbox" class="<?= $tc['cbClass'] ?> w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500 cursor-pointer"
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
                                            <?= htmlspecialchars(mb_strimwidth($inc['description'] ?? '', 0, 80, '...')) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= statusBadge($inc['status']) ?>">
                                                <?= ucfirst($inc['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= impactBadge($inc['impact_level'] ?? '') ?>">
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
                                    <tr><td colspan="9" class="px-6 py-16 text-center">
                                        <i class="fas fa-inbox fa-2x text-gray-400 dark:text-gray-600 mb-3 block"></i>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">No downtime incidents found</p>
                                    </td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($totalPages > 1): ?>
                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalCount) ?> of <?= $totalCount ?> incidents
                            </div>
                            <div class="flex gap-3">
                                <?php $q = http_build_query(['tab' => 'downtime', 'search' => $search, 'status' => $statusFilter, 'service_id' => (int)($_GET['service_id'] ?? 0)]); ?>
                                <?php if ($page > 1): ?>
                                    <a href="?<?= $q ?>&page=<?= $page - 1 ?>" class="px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">Previous</a>
                                <?php endif; ?>
                                <?php if ($page < $totalPages): ?>
                                    <a href="?<?= $q ?>&page=<?= $page + 1 ?>" class="px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">Next</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ── SECURITY TABLE ─────────────────────────────────────── -->
                <?php elseif ($activeTab === 'security'): ?>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-3 w-10">
                                        <input type="checkbox" id="<?= $tc['selectAllId'] ?>"
                                            class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500 cursor-pointer">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Ref</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Threat Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Containment</th>
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
                                            <input type="checkbox" class="<?= $tc['cbClass'] ?> w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500 cursor-pointer"
                                                value="<?= $inc['id'] ?>">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm font-mono font-semibold text-gray-900 dark:text-white">
                                                <?= htmlspecialchars($inc['incident_ref'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $inc['threat_type'] ?? ''))) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs">
                                            <?= htmlspecialchars(mb_strimwidth($inc['description'] ?? '', 0, 80, '...')) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $cs = $inc['containment_status'] ?? '';
                                            $csBadge = match($cs) {
                                                'contained'          => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                                'ongoing'            => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                                'under_investigation'=> 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                                default              => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                            };
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $csBadge ?>">
                                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $cs))) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= statusBadge($inc['status']) ?>">
                                                <?= ucfirst($inc['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= impactBadge($inc['impact_level'] ?? '') ?>">
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
                                                @click="secDeleteId = <?= $inc['id'] ?>; secDeleteRef = '<?= htmlspecialchars($inc['incident_ref'] ?? 'this incident', ENT_QUOTES) ?>'; showSecModal = true"
                                                class="inline-flex items-center px-3 py-2 bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50 text-xs font-semibold rounded-lg transition-colors">
                                                <i class="fas fa-trash mr-1"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($incidents)): ?>
                                    <tr><td colspan="10" class="px-6 py-16 text-center">
                                        <i class="fas fa-inbox fa-2x text-gray-400 dark:text-gray-600 mb-3 block"></i>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">No security incidents found</p>
                                    </td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($totalPages > 1): ?>
                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalCount) ?> of <?= $totalCount ?> incidents
                            </div>
                            <div class="flex gap-3">
                                <?php $q = http_build_query(['tab' => 'security', 'search' => $search, 'status' => $statusFilter]); ?>
                                <?php if ($page > 1): ?>
                                    <a href="?<?= $q ?>&page=<?= $page - 1 ?>" class="px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">Previous</a>
                                <?php endif; ?>
                                <?php if ($page < $totalPages): ?>
                                    <a href="?<?= $q ?>&page=<?= $page + 1 ?>" class="px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">Next</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ── FRAUD TABLE ────────────────────────────────────────── -->
                <?php elseif ($activeTab === 'fraud'): ?>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-3 w-10">
                                        <input type="checkbox" id="<?= $tc['selectAllId'] ?>"
                                            class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500 cursor-pointer">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Ref</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Fraud Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">Financial Impact</th>
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
                                            <input type="checkbox" class="<?= $tc['cbClass'] ?> w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500 cursor-pointer"
                                                value="<?= $inc['id'] ?>">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm font-mono font-semibold text-gray-900 dark:text-white">
                                                <?= htmlspecialchars($inc['incident_ref'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300">
                                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $inc['fraud_type'] ?? ''))) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs">
                                            <?= htmlspecialchars(mb_strimwidth($inc['description'] ?? '', 0, 80, '...')) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            <?php if ($inc['financial_impact'] !== null && $inc['financial_impact'] !== ''): ?>
                                                <span class="font-medium text-red-600 dark:text-red-400">
                                                    GHS <?= number_format((float)$inc['financial_impact'], 2) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400 dark:text-gray-600">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= statusBadge($inc['status']) ?>">
                                                <?= ucfirst($inc['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= impactBadge($inc['impact_level'] ?? '') ?>">
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
                                                @click="frdDeleteId = <?= $inc['id'] ?>; frdDeleteRef = '<?= htmlspecialchars($inc['incident_ref'] ?? 'this incident', ENT_QUOTES) ?>'; showFrdModal = true"
                                                class="inline-flex items-center px-3 py-2 bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50 text-xs font-semibold rounded-lg transition-colors">
                                                <i class="fas fa-trash mr-1"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($incidents)): ?>
                                    <tr><td colspan="10" class="px-6 py-16 text-center">
                                        <i class="fas fa-inbox fa-2x text-gray-400 dark:text-gray-600 mb-3 block"></i>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">No fraud incidents found</p>
                                    </td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($totalPages > 1): ?>
                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalCount) ?> of <?= $totalCount ?> incidents
                            </div>
                            <div class="flex gap-3">
                                <?php $q = http_build_query(['tab' => 'fraud', 'search' => $search, 'status' => $statusFilter]); ?>
                                <?php if ($page > 1): ?>
                                    <a href="?<?= $q ?>&page=<?= $page - 1 ?>" class="px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">Previous</a>
                                <?php endif; ?>
                                <?php if ($page < $totalPages): ?>
                                    <a href="?<?= $q ?>&page=<?= $page + 1 ?>" class="px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">Next</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

<script>
(function () {
    const cfg = {
        selectAllId:  '<?= $tc['selectAllId'] ?>',
        toolbarId:    '<?= $tc['toolbarId'] ?>',
        selectedId:   '<?= $tc['selectedId'] ?>',
        bulkBtnId:    '<?= $tc['bulkBtnId'] ?>',
        modalId:      '<?= $tc['modalId'] ?>',
        backdropId:   '<?= $tc['backdropId'] ?>',
        countId:      '<?= $tc['countId'] ?>',
        cancelId:     '<?= $tc['cancelId'] ?>',
        confirmId:    '<?= $tc['confirmId'] ?>',
        formId:       '<?= $tc['formId'] ?>',
        inputsId:     '<?= $tc['inputsId'] ?>',
        cbClass:      '<?= $tc['cbClass'] ?>',
    };

    const selectAll       = document.getElementById(cfg.selectAllId);
    const toolbar         = document.getElementById(cfg.toolbarId);
    const countEl         = document.getElementById(cfg.selectedId);
    const bulkDeleteBtn   = document.getElementById(cfg.bulkBtnId);
    const modal           = document.getElementById(cfg.modalId);
    const modalBackdrop   = document.getElementById(cfg.backdropId);
    const modalCount      = document.getElementById(cfg.countId);
    const modalCancelBtn  = document.getElementById(cfg.cancelId);
    const modalConfirmBtn = document.getElementById(cfg.confirmId);
    const bulkDeleteForm  = document.getElementById(cfg.formId);
    const bulkDeleteInputs = document.getElementById(cfg.inputsId);

    if (!selectAll) return; // no rows rendered

    function getChecked() {
        return Array.from(document.querySelectorAll('.' + cfg.cbClass + ':checked'));
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
        const all = document.querySelectorAll('.' + cfg.cbClass);
        selectAll.checked = all.length > 0 && checked.length === all.length;
        selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
    }

    selectAll.addEventListener('change', function () {
        document.querySelectorAll('.' + cfg.cbClass).forEach(cb => cb.checked = this.checked);
        updateToolbar();
    });

    document.querySelectorAll('.' + cfg.cbClass).forEach(cb => {
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
