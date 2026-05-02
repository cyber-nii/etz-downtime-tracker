<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/includes/auth.php';
requireLogin();
requireRole('admin');

$stmt = $pdo->query("SELECT user_id, full_name, username, role, is_active FROM users ORDER BY full_name ASC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script>if(localStorage.getItem('theme')==='dark'||(!localStorage.getItem('theme')&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark')}else{document.documentElement.classList.remove('dark')}</script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Incidents by User - eTranzact</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="relative min-h-screen">
    <div class="fixed inset-0 z-0">
        <img src="<?= url('assets/mainbg.jpg') ?>" alt="Background" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-white/90 dark:bg-gray-900/95"></div>
    </div>

    <div class="relative z-10">
        <?php include __DIR__ . '/../../src/includes/admin_navbar.php'; ?>
        <?php include __DIR__ . '/../../src/includes/loading.php'; ?>

        <main class="py-8">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8"
                 x-data="{
                    search: '',
                    selected: new Set(),
                    get count() { return this.selected.size; },
                    toggleAll(users) {
                        const visible = users.filter(u => u.name.toLowerCase().includes(this.search.toLowerCase()) || u.username.toLowerCase().includes(this.search.toLowerCase()));
                        const allChecked = visible.every(u => this.selected.has(u.id));
                        visible.forEach(u => allChecked ? this.selected.delete(u.id) : this.selected.add(u.id));
                        this.selected = new Set(this.selected);
                    },
                    toggle(id) {
                        this.selected.has(id) ? this.selected.delete(id) : this.selected.add(id);
                        this.selected = new Set(this.selected);
                    },
                    isVisible(u) {
                        const q = this.search.toLowerCase();
                        return !q || u.name.toLowerCase().includes(q) || u.username.toLowerCase().includes(q);
                    },
                    submitExport() {
                        if (this.selected.size === 0) { alert('Please select at least one user.'); return; }
                        const form = document.getElementById('exportForm');
                        document.querySelectorAll('.uid-input').forEach(el => el.remove());
                        this.selected.forEach(id => {
                            const inp = document.createElement('input');
                            inp.type = 'hidden'; inp.name = 'user_ids[]'; inp.value = id;
                            inp.className = 'uid-input';
                            form.appendChild(inp);
                        });
                        form.submit();
                    }
                 }"
                 x-init="
                    const raw = <?= json_encode(array_map(fn($u) => ['id' => (int)$u['user_id'], 'name' => $u['full_name'], 'username' => $u['username']], $users)) ?>;
                    $data.users = raw;
                 ">

                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">Export Incidents by User</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Select one or more users to export the incidents they reported</p>
                </div>

                <form id="exportForm" method="GET" action="<?= url('exports/export_incidents.php') ?>">

                    <!-- Export Options -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 mb-6">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-sliders-h mr-2 text-blue-500"></i>Export Options
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                                <input type="date" name="start_date"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                                <input type="date" name="end_date"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Incident Type</label>
                                <select name="type"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="all">All Types</option>
                                    <option value="downtime">Downtime</option>
                                    <option value="security">Security</option>
                                    <option value="fraud">Fraud</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                <select name="status"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="resolved">Resolved</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Format</label>
                                <select name="format"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="xlsx">Excel (.xlsx)</option>
                                    <option value="csv">CSV (.csv)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- User Selection -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 mb-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                                <i class="fas fa-users mr-2 text-blue-500"></i>
                                Select Users
                                <span x-show="count > 0"
                                    class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300"
                                    x-text="count + ' selected'"></span>
                            </h2>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="toggleAll(users)"
                                    class="text-xs px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-medium">
                                    Toggle All Visible
                                </button>
                                <button type="button" @click="selected = new Set(); selected = new Set(selected)"
                                    class="text-xs px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-medium">
                                    Clear All
                                </button>
                            </div>
                        </div>

                        <!-- Search -->
                        <div class="mb-4">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <input type="text" x-model="search" placeholder="Search users..."
                                    class="w-full pl-9 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- User list -->
                        <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-96 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <?php foreach ($users as $user): ?>
                            <label
                                x-show="isVisible({ name: <?= json_encode($user['full_name']) ?>, username: <?= json_encode($user['username']) ?> })"
                                class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                :class="selected.has(<?= (int)$user['user_id'] ?>) ? 'bg-blue-50 dark:bg-blue-900/20' : ''">
                                <input type="checkbox"
                                    :checked="selected.has(<?= (int)$user['user_id'] ?>)"
                                    @change="toggle(<?= (int)$user['user_id'] ?>)"
                                    class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 cursor-pointer flex-shrink-0">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <div class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center font-semibold text-sm text-white
                                        <?= $user['role'] === 'admin' ? 'bg-purple-600' : ($user['role'] === 'viewer' ? 'bg-gray-500' : 'bg-blue-600') ?>">
                                        <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            <?= htmlspecialchars($user['full_name']) ?>
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            @<?= htmlspecialchars($user['username']) ?>
                                            &middot;
                                            <span class="<?= $user['role'] === 'admin' ? 'text-purple-600 dark:text-purple-400' : ($user['role'] === 'viewer' ? 'text-gray-500' : 'text-blue-600 dark:text-blue-400') ?>">
                                                <?= ucfirst($user['role']) ?>
                                            </span>
                                            <?php if (!$user['is_active']): ?>
                                                &middot; <span class="text-red-500">Inactive</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </label>
                            <?php endforeach; ?>

                            <div x-show="<?= json_encode(count($users)) ?> > 0 && users.filter(u => isVisible(u)).length === 0"
                                class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400 italic">
                                No users match your search.
                            </div>
                        </div>

                        <?php if (empty($users)): ?>
                        <p class="text-center text-sm text-gray-500 dark:text-gray-400 py-6 italic">No users found.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Export Button -->
                    <div class="flex justify-end">
                        <button type="button" @click="submitExport()"
                            class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white font-semibold rounded-lg shadow transition-colors text-sm"
                            :disabled="count === 0">
                            <i class="fas fa-file-export mr-2"></i>
                            Export
                            <span x-show="count > 0" x-text="' (' + count + ' user' + (count === 1 ? '' : 's') + ')'"></span>
                        </button>
                    </div>

                </form>
            </div>
        </main>
    </div>
</body>
</html>
