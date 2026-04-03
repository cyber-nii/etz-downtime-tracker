<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/includes/auth.php';
require_once __DIR__ . '/../../src/includes/activity_logger.php';
requireLogin();
requireRole('admin');

$currentUser = getCurrentUser();
$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'bulk_delete') {
        $userIds = $_POST['user_ids'] ?? [];
        if (is_array($userIds) && !empty($userIds)) {
            // Filter out own account
            $userIds = array_filter($userIds, fn($id) => (int)$id !== (int)$currentUser['user_id']);
            $deleted = 0;
            foreach ($userIds as $uid) {
                $uid = (int)$uid;
                try {
                    logUserAction($_SESSION['user_id'], 'deleted', $uid);
                    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->execute([$uid]);
                    $deleted++;
                } catch (PDOException $e) {
                    // continue deleting others
                }
            }
            $_SESSION['message'] = ['type' => 'success', 'text' => "$deleted user(s) deleted successfully"];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'No users selected'];
        }
        header('Location: users.php');
        exit;
    }

    if ($action === 'toggle_status') {
        $userId = $_POST['user_id'] ?? 0;
        $newStatus = $_POST['new_status'] ?? 0;

        // Prevent deactivating own account
        if ($userId != $currentUser['user_id']) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
                $stmt->execute([$newStatus, $userId]);
                $_SESSION['message'] = ['type' => 'success', 'text' => 'User status updated successfully'];
            } catch (PDOException $e) {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Error updating user status'];
            }
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Cannot deactivate your own account'];
        }
        header('Location: users.php');
        exit;
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(username LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($roleFilter) {
    $where[] = "role = ?";
    $params[] = $roleFilter;
}

if ($statusFilter !== '') {
    $where[] = "is_active = ?";
    $params[] = $statusFilter;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countQuery = "SELECT COUNT(*) FROM users $whereClause";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalUsers = $stmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

// Get users
$query = "SELECT * FROM users $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <script>if(localStorage.getItem('theme')==='dark'||(!localStorage.getItem('theme')&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark')}else{document.documentElement.classList.remove('dark')}</script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - eTranzact</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
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
    <div class="relative z-10">
        <?php include __DIR__ . '/../../src/includes/admin_navbar.php'; ?>
        <?php include __DIR__ . '/../../src/includes/loading.php'; ?>

        <main class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">User Management</h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage system users and permissions</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="user_bulk_import.php"
                            class="inline-flex items-center justify-center px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-file-import mr-2"></i>Bulk Import
                        </a>
                        <a href="user_create.php"
                            class="inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-plus mr-2"></i>Create User
                        </a>
                    </div>
                </div>

                <!-- Message -->
                <?php if ($message): ?>
                    <div
                        class="mb-6 p-4 rounded-lg <?= $message['type'] === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' ?>">
                        <p
                            class="text-sm font-medium <?= $message['type'] === 'success' ? 'text-green-800' : 'text-red-800' ?>">
                            <?= htmlspecialchars($message['text']) ?>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-6">
                    <form method="GET" class="space-y-3 sm:space-y-0 sm:grid sm:grid-cols-2 lg:grid-cols-5 sm:gap-3">
                        <div class="sm:col-span-2">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                                placeholder="Search users..."
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <select name="role"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Roles</option>
                            <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="user" <?= $roleFilter === 'user' ? 'selected' : '' ?>>User</option>
                            <option value="viewer" <?= $roleFilter === 'viewer' ? 'selected' : '' ?>>Viewer</option>
                        </select>
                        <select name="status"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Status</option>
                            <option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                        <div class="flex gap-3 sm:col-span-2 lg:col-span-1">
                            <button type="submit"
                                class="flex-1 sm:flex-none px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium">
                                <i class="fas fa-search mr-2"></i>Filter
                            </button>
                            <?php if ($search || $roleFilter || $statusFilter !== ''): ?>
                                <a href="users.php"
                                    class="flex-1 sm:flex-none px-4 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors text-center font-medium">
                                    Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Bulk Action Toolbar -->
                <div id="bulkToolbar" class="hidden mb-4 px-4 py-3 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-xl flex items-center justify-between">
                    <span class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        <span id="selectedCount">0</span> user(s) selected
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

                <!-- Confirmation Modal -->
                <div id="bulkDeleteModal" class="fixed inset-0 z-50 hidden items-center justify-center">
                    <div class="absolute inset-0 bg-black/50" id="modalBackdrop"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mr-3">
                                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Delete Users</h3>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            You are about to permanently delete <strong id="modalCount" class="text-gray-900 dark:text-white">0</strong> user(s).
                        </p>
                        <p class="text-sm text-red-600 dark:text-red-400 mb-6">This action cannot be undone.</p>
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

                <!-- Users Table -->
                <div
                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-3 w-10">
                                        <input type="checkbox" id="selectAll"
                                            class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">
                                        User</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">
                                        Email</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">
                                        Role</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">
                                        Last Login</th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-4 whitespace-nowrap w-10">
                                            <?php if ($user['user_id'] != $currentUser['user_id']): ?>
                                                <input type="checkbox" name="user_ids[]"
                                                    value="<?= $user['user_id'] ?>"
                                                    class="user-checkbox w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div
                                                    class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
                                                    <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                        <?= htmlspecialchars($user['full_name']) ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                                        @<?= htmlspecialchars($user['username']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <div><?= htmlspecialchars($user['email']) ?></div>
                                            <?php if (!empty($user['phone'])): ?>
                                                <div class="text-xs mt-1 text-gray-400 dark:text-gray-500"><i
                                                        class="fas fa-phone mr-1"></i> <?= htmlspecialchars($user['phone']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?= $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' :
                                            ($user['role'] === 'user' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') ?>">
                                                <?= ucfirst($user['role']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?= $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex flex-col sm:flex-row justify-end gap-2">
                                                <a href="user_edit.php?id=<?= $user['user_id'] ?>"
                                                    class="inline-flex items-center justify-center px-3 py-2 bg-blue-100 text-blue-700 hover:bg-blue-200 text-xs font-semibold rounded-lg transition-colors min-h-[44px] sm:min-h-0">
                                                    <i class="fas fa-edit mr-1"></i> Edit
                                                </a>

                                                <?php if ($user['user_id'] != $currentUser['user_id']): ?>
                                                    <form method="POST" class="inline"
                                                        onsubmit="return confirm('Are you sure you want to <?= $user['is_active'] ? 'deactivate' : 'activate' ?> this user?');">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                        <input type="hidden" name="new_status"
                                                            value="<?= $user['is_active'] ? 0 : 1 ?>">
                                                        <button type="submit"
                                                            class="inline-flex items-center justify-center px-3 py-2 text-xs font-semibold rounded-lg transition-colors min-h-[44px] sm:min-h-0 w-full sm:w-auto
                                                        <?= $user['is_active'] ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200' ?>">
                                                            <i
                                                                class="fas <?= $user['is_active'] ? 'fa-user-slash' : 'fa-user-check' ?> mr-1"></i>
                                                            <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div
                            class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="text-sm text-gray-700 dark:text-gray-300 text-center sm:text-left">
                                Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalUsers) ?> of
                                <?= $totalUsers ?>
                                users
                            </div>
                            <div class="flex gap-3 w-full sm:w-auto">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>&status=<?= urlencode($statusFilter) ?>"
                                        class="flex-1 sm:flex-none px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors text-center">
                                        Previous
                                    </a>
                                <?php endif; ?>
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>&status=<?= urlencode($statusFilter) ?>"
                                        class="flex-1 sm:flex-none px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors text-center">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div> <!-- End Content Wrapper -->

<script>
(function () {
    const selectAll = document.getElementById('selectAll');
    const toolbar = document.getElementById('bulkToolbar');
    const countEl = document.getElementById('selectedCount');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const modal = document.getElementById('bulkDeleteModal');
    const modalBackdrop = document.getElementById('modalBackdrop');
    const modalCount = document.getElementById('modalCount');
    const modalCancelBtn = document.getElementById('modalCancelBtn');
    const modalConfirmBtn = document.getElementById('modalConfirmBtn');
    const bulkDeleteForm = document.getElementById('bulkDeleteForm');
    const bulkDeleteInputs = document.getElementById('bulkDeleteInputs');

    function getChecked() {
        return Array.from(document.querySelectorAll('.user-checkbox:checked'));
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
        // Sync select-all state
        const all = document.querySelectorAll('.user-checkbox');
        selectAll.checked = all.length > 0 && checked.length === all.length;
        selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
    }

    selectAll.addEventListener('change', function () {
        document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = this.checked);
        updateToolbar();
    });

    document.querySelectorAll('.user-checkbox').forEach(cb => {
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
            input.type = 'hidden';
            input.name = 'user_ids[]';
            input.value = cb.value;
            bulkDeleteInputs.appendChild(input);
        });
        bulkDeleteForm.submit();
    });
})();
</script>
</body>

</html>