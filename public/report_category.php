<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/includes/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script>if(localStorage.getItem('theme')==='dark'||(!localStorage.getItem('theme')&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark')}else{document.documentElement.classList.remove('dark')}</script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Incident - ETZ Downtime</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="relative min-h-screen">
    <!-- <div class="fixed inset-0 z-0">
        <img src="<?= url('src/assets/mainbg.jpg') ?>" alt="Background" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-white/90 dark:bg-gray-900/95"></div>
    </div> -->
    <div class="fixed inset-0 z-0">
        <img src="<?= url('../src/assets/mainbg.jpg') ?>" alt="Background" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-white/90 dark:bg-gray-900/95"></div>
    </div>
    <div class="relative z-10">
        <?php include __DIR__ . '/../src/includes/navbar.php'; ?>
        <?php include __DIR__ . '/../src/includes/loading.php'; ?>

        <main class="py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-10">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Report an Incident</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Select the category that best describes the incident you want to log.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <!-- System Downtime -->
                    <a href="<?= url('report.php') ?>"
                        class="group flex flex-col items-center text-center bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 rounded-2xl p-8 transition-all duration-150 shadow-sm hover:shadow-md">
                        <div class="w-16 h-16 rounded-2xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center mb-5 group-hover:bg-blue-200 dark:group-hover:bg-blue-900/70 transition-colors">
                            <i class="fas fa-server text-blue-600 dark:text-blue-400 text-2xl"></i>
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">System Downtime</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Log a service outage, infrastructure failure, or planned maintenance.</p>
                        <span class="mt-6 inline-flex items-center text-sm font-medium text-blue-600 dark:text-blue-400 group-hover:underline">
                            Select <i class="fas fa-arrow-right ml-1.5 text-xs"></i>
                        </span>
                    </a>

                    <!-- Information Security -->
                    <a href="<?= url('report_security.php') ?>"
                        class="group flex flex-col items-center text-center bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 hover:border-red-500 dark:hover:border-red-400 rounded-2xl p-8 transition-all duration-150 shadow-sm hover:shadow-md">
                        <div class="w-16 h-16 rounded-2xl bg-red-100 dark:bg-red-900/40 flex items-center justify-center mb-5 group-hover:bg-red-200 dark:group-hover:bg-red-900/70 transition-colors">
                            <i class="fas fa-shield-halved text-red-600 dark:text-red-400 text-2xl"></i>
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Information Security</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Log a security threat, data breach, phishing attempt, or unauthorized access.</p>
                        <span class="mt-6 inline-flex items-center text-sm font-medium text-red-600 dark:text-red-400 group-hover:underline">
                            Select <i class="fas fa-arrow-right ml-1.5 text-xs"></i>
                        </span>
                    </a>

                    <!-- Fraud -->
                    <a href="<?= url('report_fraud.php') ?>"
                        class="group flex flex-col items-center text-center bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 hover:border-amber-500 dark:hover:border-amber-400 rounded-2xl p-8 transition-all duration-150 shadow-sm hover:shadow-md">
                        <div class="w-16 h-16 rounded-2xl bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center mb-5 group-hover:bg-amber-200 dark:group-hover:bg-amber-900/70 transition-colors">
                            <i class="fas fa-triangle-exclamation text-amber-600 dark:text-amber-400 text-2xl"></i>
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Fraud</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Log a fraudulent transaction, account compromise, or suspicious financial activity.</p>
                        <span class="mt-6 inline-flex items-center text-sm font-medium text-amber-600 dark:text-amber-400 group-hover:underline">
                            Select <i class="fas fa-arrow-right ml-1.5 text-xs"></i>
                        </span>
                    </a>
                </div>

                <p class="text-center mt-8 text-xs text-gray-400 dark:text-gray-500">
                    Not sure? Choose the category closest to your situation. You can update details after submission.
                </p>
            </div>
        </main>
    </div>
</body>
</html>
