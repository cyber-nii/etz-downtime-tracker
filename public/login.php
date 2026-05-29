<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
    unset($_SESSION['redirect_after_login']);
    header('Location: ' . $redirect);
    exit;
}

$error = '';
$username = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $user = login($username, $password);

        if ($user) {
            // Successful login - redirect
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <script>if(localStorage.getItem('theme')==='dark'||(!localStorage.getItem('theme')&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark')}else{document.documentElement.classList.remove('dark')}</script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - eTranzact Downtime Tracker</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="relative min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-200" x-data="{ darkMode: document.documentElement.classList.contains('dark'), showPassword: false }">
    <!-- Background Image with Overlay -->
    <div class="absolute inset-0 z-0">
        <img src="<?= url('assets/bg.jpg') ?>" alt="Background" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-br from-slate-100/90 via-white/80 to-slate-50/90 dark:from-slate-950/95 dark:via-slate-900/90 dark:to-slate-950/95 backdrop-blur-[2px] transition-colors duration-200"></div>
    </div>

    <div class="relative z-10 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo and Header -->
            <div class="text-center">
                <img src="<?= url('assets/logo1.png') ?>" alt="eTranzact Logo" class="mx-auto h-24 w-auto">
                <h2 class="mt-6 text-3xl font-bold text-gray-900 dark:text-white">
                    Welcome Back
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Sign in to access the Downtime Tracker
                </p>
            </div>

            <!-- Login Form -->
            <div
                class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm shadow-xl rounded-xl border border-slate-200/80 dark:border-slate-800/80 overflow-hidden transition-all duration-200">
                <div class="px-8 py-8">
                    <?php if ($error): ?>
                        <div
                            class="mb-6 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900/50 rounded-lg p-4">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 text-red-600 dark:text-red-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span
                                    class="text-sm font-medium text-red-800 dark:text-red-300"><?= htmlspecialchars($error) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-6" x-data="{ submitting: false }" @submit="submitting = true">
                        <!-- Username Field -->
                        <div>
                            <label for="username"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Username or Email
                            </label>
                            <div class="relative rounded-lg shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-slate-400"></i>
                                </div>
                                <input type="text" id="username" name="username"
                                    value="<?= htmlspecialchars($username) ?>" required autofocus
                                    class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 dark:border-slate-700 rounded-lg bg-white/50 dark:bg-slate-850/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-150 text-sm"
                                    placeholder="Enter your username or email">
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div>
                            <label for="password"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Password
                            </label>
                            <div class="relative rounded-lg shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-slate-400"></i>
                                </div>
                                <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                                    class="block w-full pl-10 pr-10 py-2.5 border border-slate-300 dark:border-slate-700 rounded-lg bg-white/50 dark:bg-slate-850/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-150 text-sm"
                                    placeholder="Enter your password">
                                <button type="button" @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                                    aria-label="Toggle password visibility">
                                    <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center justify-between">
                            <label class="inline-flex items-center cursor-pointer select-none group py-1">
                                <input id="remember_me" name="remember_me" type="checkbox"
                                    class="h-4 w-4 text-blue-600 focus:ring-2 focus:ring-blue-500/20 border-slate-300 dark:border-slate-700 rounded transition-all duration-150">
                                <span class="ml-2.5 text-sm text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">
                                    Remember me
                                </span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" :disabled="submitting"
                            class="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed hover:shadow-md transition-all duration-200">
                            <template x-if="!submitting">
                                <span class="flex items-center">
                                    <i class="fas fa-sign-in-alt mr-2"></i>
                                    Sign In
                                </span>
                            </template>
                            <template x-if="submitting">
                                <span class="flex items-center">
                                    <i class="fas fa-circle-notch animate-spin mr-2"></i>
                                    Signing In...
                                </span>
                            </template>
                        </button>
                    </form>
                </div>

                <!-- Footer -->
            </div>

            <!-- Dark Mode Toggle -->
            <div class="text-center">
                <button
                    @click="darkMode = !darkMode; localStorage.setItem('theme', darkMode ? 'dark' : 'light'); darkMode ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark')"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
                    <i :class="darkMode ? 'fas fa-sun' : 'fas fa-moon'" class="mr-2"></i>
                    <span x-text="darkMode ? 'Light Mode' : 'Dark Mode'"></span>
                </button>
            </div>
        </div>
    </div>
</body>

</html>