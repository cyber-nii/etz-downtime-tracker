<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/includes/auth.php';
require_once __DIR__ . '/../../src/includes/activity_logger.php';
requireLogin();
requireRole('admin');

$action = $_POST['action'] ?? '';
$errors = [];
$previewRows = [];
$importResults = [];
$remapResults = [];

// ─── Step 2: Preview ───────────────────────────────────────────────────────
if ($action === 'preview') {
    if (empty($_FILES['csv_file']['tmp_name'])) {
        $errors[] = 'Please select a CSV file to upload.';
    } else {
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) {
            $errors[] = 'Could not read the uploaded file.';
        } else {
            // Skip header row
            $header = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 3) continue;
                [$fullName, $email, $username] = array_map('trim', $row);
                if ($fullName === '' && $email === '' && $username === '') continue;

                // Check for duplicates
                $stmt = $pdo->prepare("SELECT user_id, username, email FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                $existing = $stmt->fetch();

                $previewRows[] = [
                    'full_name' => $fullName,
                    'email'     => $email,
                    'username'  => $username,
                    'status'    => $existing ? 'exists' : 'new',
                    'conflict'  => $existing ? htmlspecialchars($existing['username'] . ' / ' . $existing['email']) : '',
                ];
            }
            fclose($handle);

            if (empty($previewRows)) {
                $errors[] = 'No valid rows found in the CSV file.';
            }
        }
    }
}

// ─── Step 3: Process ───────────────────────────────────────────────────────
if ($action === 'process') {
    $rows = json_decode($_POST['rows_json'] ?? '[]', true);
    $defaultPassword = 'Etz@1234566';
    $passwordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);
    $created = 0;
    $skipped = 0;

    foreach ($rows as $row) {
        if ($row['status'] !== 'new') {
            $skipped++;
            continue;
        }
        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, full_name, phone, role, is_active, changed_password)
                VALUES (?, ?, ?, ?, NULL, 'user', 1, 0)
            ");
            $stmt->execute([$row['username'], $row['email'], $passwordHash, $row['full_name']]);
            $newUserId = $pdo->lastInsertId();
            logUserAction($_SESSION['user_id'], 'created', $newUserId, [
                'username' => $row['username'],
                'email'    => $row['email'],
                'source'   => 'bulk_import',
            ]);
            $created++;
            $importResults[] = ['status' => 'created', 'full_name' => $row['full_name'], 'username' => $row['username']];
        } catch (PDOException $e) {
            $importResults[] = ['status' => 'error', 'full_name' => $row['full_name'], 'username' => $row['username'], 'error' => $e->getMessage()];
            $skipped++;
        }
    }

    // Fetch users who have incidents for the remap UI
    $stmt = $pdo->query("
        SELECT DISTINCT u.user_id, u.username, u.full_name,
               COUNT(DISTINCT i.incident_id) AS incident_count
        FROM users u
        JOIN incidents i ON i.reported_by = u.user_id
        GROUP BY u.user_id, u.username, u.full_name
        ORDER BY u.full_name
    ");
    $incidentOwners = $stmt->fetchAll();

    // All users for the reassign dropdown
    $allUsers = $pdo->query("SELECT user_id, username, full_name FROM users ORDER BY full_name")->fetchAll();

    $action = 'show_remap'; // Switch view to remap UI
}

// ─── Step 5: Remap ─────────────────────────────────────────────────────────
if ($action === 'remap') {
    $mappings = $_POST['mapping'] ?? []; // [old_user_id => new_user_id]
    $remapCount = 0;

    foreach ($mappings as $oldId => $newId) {
        $oldId = (int)$oldId;
        $newId = (int)$newId;
        if ($newId === 0 || $newId === $oldId) continue;

        $incidentsUpdated = 0;
        $resolvedUpdated  = 0;
        $updatesUpdated   = 0;

        $stmt = $pdo->prepare("UPDATE incidents SET reported_by = ? WHERE reported_by = ?");
        $stmt->execute([$newId, $oldId]);
        $incidentsUpdated = $stmt->rowCount();

        $stmt = $pdo->prepare("UPDATE incidents SET resolved_by = ? WHERE resolved_by = ?");
        $stmt->execute([$newId, $oldId]);
        $resolvedUpdated = $stmt->rowCount();

        $stmt = $pdo->prepare("UPDATE incident_updates SET user_id = ? WHERE user_id = ?");
        $stmt->execute([$newId, $oldId]);
        $updatesUpdated = $stmt->rowCount();

        logUserAction($_SESSION['user_id'], 'updated', $newId, [
            'action'           => 'remap_incidents',
            'from_user_id'     => $oldId,
            'incidents_moved'  => $incidentsUpdated,
            'resolved_moved'   => $resolvedUpdated,
            'updates_moved'    => $updatesUpdated,
        ]);

        $remapResults[] = [
            'old_id'    => $oldId,
            'new_id'    => $newId,
            'incidents' => $incidentsUpdated,
            'resolved'  => $resolvedUpdated,
            'updates'   => $updatesUpdated,
        ];
        $remapCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script>if(localStorage.getItem('theme')==='dark'||(!localStorage.getItem('theme')&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark')}else{document.documentElement.classList.remove('dark')}</script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Import Users - eTranzact</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
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
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Back link -->
                <div class="mb-6">
                    <a href="users.php" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Users
                    </a>
                </div>

                <!-- Page header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Bulk Import Users</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Import multiple users from a CSV file and optionally reassign their incidents.</p>
                </div>

                <?php if ($errors): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <ul class="list-disc list-inside text-sm text-red-800">
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php
                // ── STEP 1: Upload form ────────────────────────────────────────────────
                if ($action === '' || (!empty($errors) && $action === 'preview')):
                ?>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-bold mr-2">1</span>
                            Upload CSV File
                        </h2>
                    </div>
                    <div class="px-6 py-6">
                        <!-- Instructions -->
                        <div class="mb-5 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg text-sm text-blue-800 dark:text-blue-300">
                            <p class="font-medium mb-2"><i class="fas fa-info-circle mr-1"></i> Before uploading:</p>
                            <ol class="list-decimal list-inside space-y-1 ml-1">
                                <li>Open your Excel file (<code class="font-mono bg-blue-100 dark:bg-blue-900 px-1 rounded">users.xlsx</code>)</li>
                                <li>Go to <strong>File → Save As</strong> and choose <strong>CSV (Comma delimited)</strong></li>
                                <li>Upload the saved <code class="font-mono bg-blue-100 dark:bg-blue-900 px-1 rounded">.csv</code> file below</li>
                            </ol>
                            <p class="mt-2">Required columns (in order): <code class="font-mono bg-blue-100 dark:bg-blue-900 px-1 rounded">Full Name</code>, <code class="font-mono bg-blue-100 dark:bg-blue-900 px-1 rounded">Email Address</code>, <code class="font-mono bg-blue-100 dark:bg-blue-900 px-1 rounded">Username</code></p>
                        </div>

                        <form method="POST" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="action" value="preview">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">CSV File *</label>
                                <input type="file" name="csv_file" accept=".csv" required
                                    class="w-full text-sm text-gray-700 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-300">
                            </div>
                            <div class="pt-2">
                                <button type="submit"
                                    class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    <i class="fas fa-eye mr-2"></i>Preview Import
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php
                // ── STEP 2: Preview ────────────────────────────────────────────────────
                elseif ($action === 'preview' && !empty($previewRows)):
                    $newCount = count(array_filter($previewRows, fn($r) => $r['status'] === 'new'));
                    $existsCount = count($previewRows) - $newCount;
                ?>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-bold mr-2">2</span>
                            Preview — <?= count($previewRows) ?> row(s) found
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            <span class="text-green-700 dark:text-green-400 font-medium"><?= $newCount ?> will be created</span>
                            <?php if ($existsCount): ?> · <span class="text-yellow-700 dark:text-yellow-400 font-medium"><?= $existsCount ?> already exist (will be skipped)</span><?php endif; ?>
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">Full Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">Username</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">Email</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">Note</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($previewRows as $row): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3">
                                        <?php if ($row['status'] === 'new'): ?>
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">New</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">Exists</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">@<?= htmlspecialchars($row['username']) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($row['email']) ?></td>
                                    <td class="px-4 py-3 text-xs text-gray-400 dark:text-gray-500">
                                        <?= $row['status'] === 'exists' ? 'Conflicts with: ' . $row['conflict'] : 'Default password will be set' ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($newCount > 0): ?>
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between gap-4">
                        <a href="user_bulk_import.php" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">
                            <i class="fas fa-arrow-left mr-1"></i>Start over
                        </a>
                        <form method="POST">
                            <input type="hidden" name="action" value="process">
                            <input type="hidden" name="rows_json" value="<?= htmlspecialchars(json_encode($previewRows)) ?>">
                            <button type="submit"
                                class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <i class="fas fa-user-plus mr-2"></i>Import <?= $newCount ?> User(s)
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-yellow-700 dark:text-yellow-400">All users already exist in the system. Nothing to import.</p>
                        <a href="user_bulk_import.php" class="mt-2 inline-block text-sm text-blue-600 hover:underline">Upload a different file</a>
                    </div>
                    <?php endif; ?>
                </div>

                <?php
                // ── STEP 3+4: Import results + Remap UI ───────────────────────────────
                elseif ($action === 'show_remap'):
                    $createdCount = count(array_filter($importResults, fn($r) => $r['status'] === 'created'));
                    $skippedCount = count(array_filter($importResults, fn($r) => $r['status'] !== 'created'));
                ?>
                <!-- Import summary -->
                <div class="mb-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100 text-green-700 text-xs font-bold mr-2"><i class="fas fa-check text-xs"></i></span>
                            Import Complete
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            <span class="text-green-700 dark:text-green-400 font-medium"><?= $createdCount ?> user(s) created</span>
                            <?php if ($skippedCount): ?> · <span class="text-gray-500"><?= $skippedCount ?> skipped</span><?php endif; ?>
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">Result</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">Full Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">Username</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($importResults as $r): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3">
                                        <?php if ($r['status'] === 'created'): ?>
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Created</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Error</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($r['full_name']) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">@<?= htmlspecialchars($r['username']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Remap incidents -->
                <?php if (!empty($incidentOwners)): ?>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-bold mr-2">4</span>
                            Remap Incident Ownership
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            For each user below who currently owns incidents, choose who should take over their incidents. Leave blank to keep as-is.
                        </p>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="remap">
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($incidentOwners as $owner): ?>
                            <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center gap-3">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($owner['full_name']) ?></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">@<?= htmlspecialchars($owner['username']) ?> · <?= $owner['incident_count'] ?> incident(s) reported</p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <i class="fas fa-arrow-right text-gray-400 text-xs hidden sm:block"></i>
                                    <select name="mapping[<?= $owner['user_id'] ?>]"
                                        class="w-64 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                        <option value="0">— No change —</option>
                                        <?php foreach ($allUsers as $u): ?>
                                            <?php if ($u['user_id'] != $owner['user_id']): ?>
                                            <option value="<?= $u['user_id'] ?>"><?= htmlspecialchars($u['full_name']) ?> (@<?= htmlspecialchars($u['username']) ?>)</option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <a href="users.php" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">
                                Skip — go to Users
                            </a>
                            <button type="submit"
                                class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <i class="fas fa-exchange-alt mr-2"></i>Apply Remapping
                            </button>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <div class="p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-600 dark:text-gray-400">
                    No existing incidents to remap. <a href="users.php" class="text-blue-600 hover:underline">Go to Users</a>
                </div>
                <?php endif; ?>

                <?php
                // ── STEP 5: Remap results ──────────────────────────────────────────────
                elseif ($action === 'remap'):
                ?>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100 text-green-700 text-xs font-bold mr-2"><i class="fas fa-check text-xs"></i></span>
                            Remapping Complete
                        </h2>
                    </div>
                    <?php if (empty($remapResults)): ?>
                    <div class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">No remappings were applied.</div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">From User ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">To User ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">Incidents Moved</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">Resolved By Updated</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">Updates Moved</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($remapResults as $r): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 text-sm">
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">#<?= $r['old_id'] ?></td>
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">#<?= $r['new_id'] ?></td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400"><?= $r['incidents'] ?></td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400"><?= $r['resolved'] ?></td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400"><?= $r['updates'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <a href="users.php"
                            class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-users mr-2"></i>Go to Users
                        </a>
                    </div>
                </div>

                <?php endif; ?>

            </div>
        </main>
    </div>
</body>
</html>
