<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/includes/auth.php';
require_once __DIR__ . '/../src/includes/activity_logger.php';

requireLogin();

$currentUser = getCurrentUser();
$userId = $currentUser['user_id'];

// Fetch full user data from DB
$stmt = $pdo->prepare("SELECT user_id, username, email, full_name, phone, role FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$profileErrors = [];
$passwordErrors = [];
$message = null;

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Verify CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $message = ['type' => 'error', 'text' => 'Invalid request. Please try again.'];
    } elseif ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        // Validate
        if (empty($full_name)) {
            $profileErrors[] = 'Full name is required.';
        }
        if (empty($email)) {
            $profileErrors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $profileErrors[] = 'Please enter a valid email address.';
        }

        // Check email uniqueness (exclude self)
        if (!$profileErrors) {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $profileErrors[] = 'That email address is already in use by another account.';
            }
        }

        if (!$profileErrors) {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?");
            $stmt->execute([$full_name, $email, $phone ?: null, $userId]);

            // Update session name immediately
            $_SESSION['full_name'] = $full_name;

            logActivity($userId, 'profile_updated', 'User updated their profile information');

            $message = ['type' => 'success', 'text' => 'Profile updated successfully.'];

            // Refresh user data
            $user['full_name'] = $full_name;
            $user['email'] = $email;
            $user['phone'] = $phone;
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password)) {
            $passwordErrors[] = 'Current password is required.';
        }
        if (empty($new_password)) {
            $passwordErrors[] = 'New password is required.';
        }
        if ($new_password !== $confirm_password) {
            $passwordErrors[] = 'Passwords do not match.';
        }

        // Verify current password
        if (!$passwordErrors) {
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $row = $stmt->fetch();

            if (!$row || !password_verify($current_password, $row['password_hash'])) {
                $passwordErrors[] = 'Current password is incorrect.';
            }
        }

        // Validate new password strength
        if (!$passwordErrors) {
            $validation = validatePassword($new_password);
            if (!$validation['valid']) {
                $passwordErrors = array_merge($passwordErrors, $validation['errors']);
            }
        }

        if (!$passwordErrors) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt->execute([$new_hash, $userId]);

            logActivity($userId, 'password_changed', 'User changed their password');

            $message = ['type' => 'success', 'text' => 'Password changed successfully.'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - eTranzact</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
    <script>
        if (localStorage.getItem('theme') === 'dark' ||
            (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>

<body class="relative min-h-screen">
    <!-- Background -->
    <div class="fixed inset-0 z-0">
        <img src="<?= url('../src/assets/mainbg.jpg') ?>" alt="Background" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-white/90 dark:bg-gray-900/95"></div>
    </div>

    <div class="relative z-10 min-h-screen">
        <?php include __DIR__ . '/../src/includes/navbar.php'; ?>

        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Profile</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your account information and password.</p>
            </div>

            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg flex items-start gap-3
                    <?= $message['type'] === 'success'
                        ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300'
                        : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300' ?>">
                    <i class="fas <?= $message['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mt-0.5 flex-shrink-0"></i>
                    <span class="text-sm"><?= htmlspecialchars($message['text']) ?></span>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Profile Info Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Profile Information</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Update your name, email, and phone number.</p>

                    <?php if ($profileErrors): ?>
                        <div class="mb-5 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <ul class="list-disc list-inside text-sm text-red-800 dark:text-red-300 space-y-1">
                                <?php foreach ($profileErrors as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="update_profile">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Username</label>
                            <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled
                                class="w-full px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 cursor-not-allowed text-sm">
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Username cannot be changed.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Full Name</label>
                            <input type="text" name="full_name"
                                value="<?= htmlspecialchars($_POST['full_name'] ?? $user['full_name']) ?>"
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email Address</label>
                            <input type="email" name="email"
                                value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>"
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Phone Number(s)</label>
                            <input type="text" name="phone"
                                value="<?= htmlspecialchars($_POST['phone'] ?? $user['phone'] ?? '') ?>"
                                placeholder="E.g. +23324..., +23320..."
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Separate multiple numbers with commas.</p>
                        </div>

                        <button type="submit"
                            class="w-full py-2.5 px-4 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                            Save Changes
                        </button>
                    </form>
                </div>

                <!-- Change Password Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6"
                    x-data="{ showPasswords: false }">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Change Password</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Choose a strong password to keep your account secure.</p>

                    <?php if ($passwordErrors): ?>
                        <div class="mb-5 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <ul class="list-disc list-inside text-sm text-red-800 dark:text-red-300 space-y-1">
                                <?php foreach ($passwordErrors as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="change_password">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Current Password</label>
                            <input :type="showPasswords ? 'text' : 'password'" name="current_password" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">New Password</label>
                            <input :type="showPasswords ? 'text' : 'password'" name="new_password" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Min 8 characters with uppercase, lowercase, number, and special character.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Confirm New Password</label>
                            <input :type="showPasswords ? 'text' : 'password'" name="confirm_password" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" x-model="showPasswords" id="show-passwords"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="show-passwords" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Show passwords</label>
                        </div>

                        <button type="submit"
                            class="w-full py-2.5 px-4 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                            Update Password
                        </button>
                    </form>
                </div>

            </div>
        </main>
    </div>
</body>

</html>
