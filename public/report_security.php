<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/includes/auth.php';
requireLogin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error   = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid request");
    }

    // Sanitise inputs
    $threat_type       = in_array($_POST['threat_type'] ?? '', ['phishing','unauthorized_access','data_breach','malware','social_engineering','other'])
                         ? $_POST['threat_type'] : 'other';
    $systems_affected  = trim($_POST['systems_affected'] ?? '');
    $description       = trim($_POST['description'] ?? '');
    $impact_level      = in_array($_POST['impact_level'] ?? '', ['Low','Medium','High','Critical']) ? $_POST['impact_level'] : 'Low';
    $priority          = in_array($_POST['priority'] ?? '', ['Low','Medium','High','Urgent']) ? $_POST['priority'] : 'Medium';
    $containment       = in_array($_POST['containment_status'] ?? '', ['contained','ongoing','under_investigation']) ? $_POST['containment_status'] : 'under_investigation';
    $escalated_raw     = isset($_POST['escalated_to']) && is_array($_POST['escalated_to']) ? $_POST['escalated_to'] : [];
    $allowed_esc       = ['CISO','IT Security Team','Regulatory Body','Law Enforcement'];
    $escalated_to      = implode(', ', array_filter($escalated_raw, fn($v) => in_array($v, $allowed_esc)));
    $root_cause        = trim($_POST['root_cause'] ?? '');
    $incident_date     = $_POST['incident_date'] ?? date('Y-m-d');
    $incident_time     = $_POST['incident_time'] ?? date('H:i');
    $actual_start_time = $incident_date . ' ' . $incident_time . ':00';

    // File uploads
    $errors = [];
    $attachment_path = null;
    $uploaded_files  = [];

    if (isset($_FILES['evidence']) && !empty($_FILES['evidence']['name'][0])) {
        $allowedfileExtensions = ['jpg','jpeg','gif','png','pdf','doc','docx','xls','xlsx','txt'];
        $uploadFileDir = __DIR__ . '/uploads/incidents/';
        $maxFileSize   = 10 * 1024 * 1024;
        $fileCount     = count($_FILES['evidence']['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['evidence']['error'][$i] === UPLOAD_ERR_OK) {
                $fileName      = $_FILES['evidence']['name'][$i];
                $fileSize      = $_FILES['evidence']['size'][$i];
                $fileType      = $_FILES['evidence']['type'][$i];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if ($fileSize > $maxFileSize) { $errors[] = "File '{$fileName}' exceeds 10MB."; continue; }
                if (!in_array($fileExtension, $allowedfileExtensions)) { $errors[] = "File '{$fileName}' has invalid type."; continue; }
                $newFileName = md5(time() . $fileName . $i) . '.' . $fileExtension;
                if (move_uploaded_file($_FILES['evidence']['tmp_name'][$i], $uploadFileDir . $newFileName)) {
                    $customName = $fileName;
                    if (!empty(trim($_POST['file_custom_names'][$i] ?? ''))) {
                        $customName = preg_replace('/[^a-zA-Z0-9_\-\.\s]/', '', trim($_POST['file_custom_names'][$i]));
                        if (pathinfo($customName, PATHINFO_EXTENSION) === '') $customName .= '.' . $fileExtension;
                    }
                    $uploaded_files[] = ['file_path' => 'uploads/incidents/' . $newFileName, 'file_name' => $customName, 'file_type' => $fileType, 'file_size' => $fileSize];
                    if ($attachment_path === null) $attachment_path = 'uploads/incidents/' . $newFileName;
                } else {
                    $errors[] = "Error uploading '{$fileName}'.";
                }
            } elseif ($_FILES['evidence']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = "Upload error for '{$_FILES['evidence']['name'][$i]}'.";
            }
        }
    }

    // Validate
    $datetime = DateTime::createFromFormat('Y-m-d H:i:s', $actual_start_time);
    if (!$datetime) $errors[] = "Invalid incident date or time.";
    elseif ($datetime > new DateTime()) $errors[] = "Incident date/time cannot be in the future.";
    if (empty($threat_type)) $errors[] = "Please select a threat type.";

    if (!empty($errors)) {
        $error = implode(' ', $errors);
    } else {
        try {
            $pdo->beginTransaction();

            // Generate ref
            $incident_ref = 'SEC-IN#' . date('ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
            while ($pdo->query("SELECT COUNT(*) FROM incidents WHERE incident_ref = '$incident_ref'")->fetchColumn() > 0) {
                $incident_ref = 'SEC-IN#' . date('ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
            }

            // Insert incident
            $stmt = $pdo->prepare("
                INSERT INTO incidents (incident_ref, category, service_id, impact_level, priority, incident_source, description, root_cause, attachment_path, actual_start_time, status, reported_by)
                VALUES (:ref, 'information_security', NULL, :impact, :priority, 'internal', :description, :root_cause, :attachment, :start_time, 'pending', :reported_by)
            ");
            $stmt->execute([
                ':ref'         => $incident_ref,
                ':impact'      => $impact_level,
                ':priority'    => $priority,
                ':description' => $description ?: null,
                ':root_cause'  => $root_cause ?: null,
                ':attachment'  => $attachment_path,
                ':start_time'  => $actual_start_time,
                ':reported_by' => $_SESSION['user_id'],
            ]);
            $incident_id = $pdo->lastInsertId();

            // Insert security details
            $pdo->prepare("
                INSERT INTO incident_security_details (incident_id, threat_type, systems_affected, containment_status, escalated_to)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([$incident_id, $threat_type, $systems_affected ?: null, $containment, $escalated_to ?: null]);

            // File attachments
            if (!empty($uploaded_files)) {
                $att = $pdo->prepare("INSERT INTO incident_attachments (incident_id, file_path, file_name, file_type, file_size) VALUES (?,?,?,?,?)");
                foreach ($uploaded_files as $f) {
                    $att->execute([$incident_id, $f['file_path'], $f['file_name'], $f['file_type'], $f['file_size']]);
                }
            }

            $pdo->commit();

            require_once __DIR__ . '/../src/includes/activity_logger.php';
            logActivity($_SESSION['user_id'], 'incident_created', "Reported information security incident: {$incident_ref}");

            $_SESSION['success'] = "Information Security incident {$incident_ref} reported successfully.";
            header('Location: ' . url('incidents.php'));
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = "An error occurred. Please try again. " . $e->getMessage();
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
    <title>Report Security Incident - eTranzact</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="relative min-h-screen">
    <div class="fixed inset-0 z-0">
        <img src="<?= url('../src/assets/mainbg.jpg') ?>" alt="Background" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-white/90 dark:bg-gray-900/95"></div>
    </div>
    <div class="relative z-10">
        <?php include __DIR__ . '/../src/includes/navbar.php'; ?>
        <?php include __DIR__ . '/../src/includes/loading.php'; ?>

        <main class="py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden rounded-xl" style="box-shadow: 0 1px 3px 0 rgba(0,0,0,.05), 0 1px 2px 0 rgba(0,0,0,.03);">

                    <!-- Card header -->
                    <div class="px-6 py-5 sm:px-8 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/40 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-shield-halved text-red-600 dark:text-red-400"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-0.5">
                                    <a href="<?= url('report_category.php') ?>" class="hover:text-gray-700 dark:hover:text-gray-200">Report Incident</a>
                                    <i class="fas fa-chevron-right text-[10px]"></i>
                                    <span class="text-red-600 dark:text-red-400 font-medium">Information Security</span>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Information Security Incident</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">Log a security threat, breach, or suspicious activity</p>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 mx-6 mt-6 rounded-r-lg">
                            <div class="flex">
                                <i class="fas fa-exclamation-circle text-red-500 text-xl flex-shrink-0"></i>
                                <p class="ml-3 text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="bg-green-50 border-l-4 border-green-500 p-4 mx-6 mt-6 rounded-r-lg">
                            <div class="flex">
                                <i class="fas fa-check-circle text-green-500 text-xl flex-shrink-0"></i>
                                <p class="ml-3 text-sm font-medium text-green-800"><?= htmlspecialchars($_SESSION['success']) ?></p>
                            </div>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="px-6 pb-8 pt-6 sm:px-8"
                        x-data="{
                            currentStep: 1,
                            stepError: '',
                            filePreviews: [],
                            nextStep() {
                                this.stepError = '';
                                if (this.currentStep === 1) {
                                    const tt = document.getElementById('threat_type').value;
                                    if (!tt) { this.stepError = 'Please select a threat type.'; return; }
                                    const d = document.getElementById('incident_date').value;
                                    const t = document.getElementById('incident_time').value;
                                    if (!d || !t) { this.stepError = 'Please enter the incident date and time.'; return; }
                                }
                                if (this.currentStep < 3) this.currentStep++;
                                window.scrollTo({ top: 0, behavior: 'smooth' });
                            },
                            prevStep() {
                                this.stepError = '';
                                if (this.currentStep > 1) this.currentStep--;
                                window.scrollTo({ top: 0, behavior: 'smooth' });
                            },
                            handleMultipleFileChange(event) {
                                const files = Array.from(event.target.files);
                                const imageTypes = ['image/jpeg','image/jpg','image/png','image/gif'];
                                files.forEach(file => {
                                    const preview = { name: file.name, customName: file.name, type: imageTypes.includes(file.type) ? 'image' : 'document', url: null };
                                    if (preview.type === 'image') {
                                        const reader = new FileReader();
                                        reader.onload = (e) => { preview.url = e.target.result; this.filePreviews.push(preview); };
                                        reader.readAsDataURL(file);
                                    } else { this.filePreviews.push(preview); }
                                });
                            },
                            removeFile(index) {
                                this.filePreviews.splice(index, 1);
                                const fileInput = this.$refs.fileInput;
                                const dt = new DataTransfer();
                                Array.from(fileInput.files).forEach((file, i) => { if (i !== index) dt.items.add(file); });
                                fileInput.files = dt.files;
                            }
                        }">

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                        <!-- ── Progress Bar ── -->
                        <div class="mb-8">
                            <div class="flex items-center">
                                <div class="flex flex-col items-center">
                                    <div class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold transition-colors"
                                        :class="currentStep >= 1 ? 'bg-red-600 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-500'">
                                        <template x-if="currentStep > 1"><i class="fas fa-check text-xs"></i></template>
                                        <template x-if="currentStep <= 1"><span>1</span></template>
                                    </div>
                                    <span class="mt-1.5 text-xs font-medium hidden sm:block"
                                        :class="currentStep >= 1 ? 'text-red-600 dark:text-red-400' : 'text-gray-400'">What Happened</span>
                                </div>
                                <div class="flex-1 h-0.5 mx-2 transition-colors" :class="currentStep > 1 ? 'bg-red-600' : 'bg-gray-200 dark:bg-gray-600'"></div>
                                <div class="flex flex-col items-center">
                                    <div class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold transition-colors"
                                        :class="currentStep >= 2 ? 'bg-red-600 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-500'">
                                        <template x-if="currentStep > 2"><i class="fas fa-check text-xs"></i></template>
                                        <template x-if="currentStep <= 2"><span>2</span></template>
                                    </div>
                                    <span class="mt-1.5 text-xs font-medium hidden sm:block"
                                        :class="currentStep >= 2 ? 'text-red-600 dark:text-red-400' : 'text-gray-400'">Severity</span>
                                </div>
                                <div class="flex-1 h-0.5 mx-2 transition-colors" :class="currentStep > 2 ? 'bg-red-600' : 'bg-gray-200 dark:bg-gray-600'"></div>
                                <div class="flex flex-col items-center">
                                    <div class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold transition-colors"
                                        :class="currentStep >= 3 ? 'bg-red-600 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-500'">
                                        <span>3</span>
                                    </div>
                                    <span class="mt-1.5 text-xs font-medium hidden sm:block"
                                        :class="currentStep >= 3 ? 'text-red-600 dark:text-red-400' : 'text-gray-400'">Response</span>
                                </div>
                            </div>
                        </div>

                        <!-- Step error banner -->
                        <div x-show="stepError !== ''" x-transition
                            class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-3 rounded-r-lg mb-4">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-exclamation-circle text-red-500"></i>
                                <p class="text-sm font-medium text-red-700 dark:text-red-400" x-text="stepError"></p>
                            </div>
                        </div>

                        <!-- ── STEP 1: What Happened ── -->
                        <div x-show="currentStep === 1" class="space-y-6">
                            <p class="text-sm text-gray-500 dark:text-gray-400 -mt-2 pb-2 border-b border-gray-100 dark:border-gray-700">
                                <i class="fas fa-info-circle mr-1"></i> Tell us what security incident occurred, when it happened, and what systems were affected.
                            </p>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">When Did the Incident Occur? <span class="text-red-500">*</span></label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="incident_date" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Date</label>
                                        <input type="date" id="incident_date" name="incident_date" required
                                            value="<?= isset($_POST['incident_date']) ? htmlspecialchars($_POST['incident_date']) : date('Y-m-d') ?>"
                                            max="<?= date('Y-m-d') ?>"
                                            class="block w-full border-gray-300 dark:border-gray-600 rounded-lg shadow-sm py-2.5 px-3.5 text-sm bg-white dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500">
                                    </div>
                                    <div>
                                        <label for="incident_time" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Time</label>
                                        <input type="time" id="incident_time" name="incident_time" required
                                            value="<?= isset($_POST['incident_time']) ? htmlspecialchars($_POST['incident_time']) : date('H:i') ?>"
                                            class="block w-full border-gray-300 dark:border-gray-600 rounded-lg shadow-sm py-2.5 px-3.5 text-sm bg-white dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="threat_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Threat Type <span class="text-red-500">*</span></label>
                                <select id="threat_type" name="threat_type" required
                                    class="block w-full border-gray-300 dark:border-gray-600 rounded-lg shadow-sm py-2.5 px-3.5 text-sm bg-white dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500">
                                    <option value="">Select threat type...</option>
                                    <option value="phishing" <?= (($_POST['threat_type'] ?? '') === 'phishing') ? 'selected' : '' ?>>Phishing</option>
                                    <option value="unauthorized_access" <?= (($_POST['threat_type'] ?? '') === 'unauthorized_access') ? 'selected' : '' ?>>Unauthorized Access</option>
                                    <option value="data_breach" <?= (($_POST['threat_type'] ?? '') === 'data_breach') ? 'selected' : '' ?>>Data Breach</option>
                                    <option value="malware" <?= (($_POST['threat_type'] ?? '') === 'malware') ? 'selected' : '' ?>>Malware</option>
                                    <option value="social_engineering" <?= (($_POST['threat_type'] ?? '') === 'social_engineering') ? 'selected' : '' ?>>Social Engineering</option>
                                    <option value="other" <?= (($_POST['threat_type'] ?? '') === 'other') ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>

                            <div>
                                <label for="systems_affected" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Systems / Data Affected
                                    <span class="text-xs text-gray-500 dark:text-gray-400 font-normal ml-2">(Optional)</span>
                                </label>
                                <textarea id="systems_affected" name="systems_affected" rows="3"
                                    placeholder="List the systems, applications, or data that were compromised or at risk..."
                                    class="block w-full border-gray-300 dark:border-gray-600 rounded-lg shadow-sm py-2.5 px-3.5 text-sm bg-white dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500"><?= htmlspecialchars($_POST['systems_affected'] ?? '') ?></textarea>
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Description
                                    <span class="text-xs text-gray-500 dark:text-gray-400 font-normal ml-2">(Optional)</span>
                                </label>
                                <textarea id="description" name="description" rows="4"
                                    placeholder="Describe what happened in detail..."
                                    class="block w-full border-gray-300 dark:border-gray-600 rounded-lg shadow-sm py-2.5 px-3.5 text-sm bg-white dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- ── STEP 2: Severity ── -->
                        <div x-show="currentStep === 2" class="space-y-6">
                            <p class="text-sm text-gray-500 dark:text-gray-400 -mt-2 pb-2 border-b border-gray-100 dark:border-gray-700">
                                <i class="fas fa-info-circle mr-1"></i> How severe was this incident?
                            </p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="impact_level" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Impact Level <span class="text-red-500">*</span></label>
                                    <select id="impact_level" name="impact_level" required
                                        class="block w-full border-gray-300 dark:border-gray-600 rounded-lg shadow-sm py-2.5 px-3.5 text-sm bg-white dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500">
                                        <option value="Low" <?= (($_POST['impact_level'] ?? 'Low') === 'Low') ? 'selected' : '' ?>>Low</option>
                                        <option value="Medium" <?= (($_POST['impact_level'] ?? '') === 'Medium') ? 'selected' : '' ?>>Medium</option>
                                        <option value="High" <?= (($_POST['impact_level'] ?? '') === 'High') ? 'selected' : '' ?>>High</option>
                                        <option value="Critical" <?= (($_POST['impact_level'] ?? '') === 'Critical') ? 'selected' : '' ?>>Critical</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Priority <span class="text-red-500">*</span></label>
                                    <select id="priority" name="priority" required
                                        class="block w-full border-gray-300 dark:border-gray-600 rounded-lg shadow-sm py-2.5 px-3.5 text-sm bg-white dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500">
                                        <option value="Low" <?= (($_POST['priority'] ?? 'Low') === 'Low') ? 'selected' : '' ?>>Low</option>
                                        <option value="Medium" <?= (($_POST['priority'] ?? '') === 'Medium') ? 'selected' : '' ?>>Medium</option>
                                        <option value="High" <?= (($_POST['priority'] ?? '') === 'High') ? 'selected' : '' ?>>High</option>
                                        <option value="Urgent" <?= (($_POST['priority'] ?? '') === 'Urgent') ? 'selected' : '' ?>>Urgent</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- ── STEP 3: Response ── -->
                        <div x-show="currentStep === 3" class="space-y-6">
                            <p class="text-sm text-gray-500 dark:text-gray-400 -mt-2 pb-2 border-b border-gray-100 dark:border-gray-700">
                                <i class="fas fa-info-circle mr-1"></i> Document the response actions taken and attach any supporting evidence.
                            </p>

                            <div>
                                <label for="containment_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Containment Status <span class="text-red-500">*</span></label>
                                <select id="containment_status" name="containment_status" required
                                    class="block w-full border-gray-300 dark:border-gray-600 rounded-lg shadow-sm py-2.5 px-3.5 text-sm bg-white dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500">
                                    <option value="under_investigation" <?= (($_POST['containment_status'] ?? 'under_investigation') === 'under_investigation') ? 'selected' : '' ?>>Under Investigation</option>
                                    <option value="ongoing" <?= (($_POST['containment_status'] ?? '') === 'ongoing') ? 'selected' : '' ?>>Ongoing</option>
                                    <option value="contained" <?= (($_POST['containment_status'] ?? '') === 'contained') ? 'selected' : '' ?>>Contained</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Escalated / Notified</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <?php foreach (['CISO', 'IT Security Team', 'Regulatory Body', 'Law Enforcement'] as $party): ?>
                                        <label class="flex items-center gap-2.5 p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                            <input type="checkbox" name="escalated_to[]" value="<?= $party ?>"
                                                <?= (isset($_POST['escalated_to']) && in_array($party, $_POST['escalated_to'])) ? 'checked' : '' ?>
                                                class="w-4 h-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                            <span class="text-sm text-gray-700 dark:text-gray-300"><?= $party ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div>
                                <label for="root_cause" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Root Cause
                                    <span class="text-xs text-gray-500 dark:text-gray-400 font-normal ml-2">(Optional)</span>
                                </label>
                                <textarea id="root_cause" name="root_cause" rows="3"
                                    placeholder="What caused this incident?"
                                    class="shadow-sm focus:ring-red-500 focus:border-red-500 block w-full sm:text-sm border border-gray-300 dark:border-gray-600 rounded-md p-3 bg-white dark:bg-gray-700 dark:text-white"><?= htmlspecialchars($_POST['root_cause'] ?? '') ?></textarea>
                            </div>

                            <!-- Evidence / Attachments -->
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Evidence / Attachments
                                    <span class="text-xs text-gray-500 dark:text-gray-400 font-normal ml-2">(Optional)</span>
                                </label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-md hover:border-red-400 dark:hover:border-red-500 transition-colors bg-gray-50 dark:bg-gray-700/50">
                                    <div class="space-y-1 text-center w-full">
                                        <template x-if="filePreviews.length === 0">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </template>
                                        <template x-if="filePreviews.length > 0">
                                            <div class="space-y-3 mb-4">
                                                <template x-for="(preview, index) in filePreviews" :key="index">
                                                    <div class="relative group bg-gray-50 dark:bg-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                                                        <div class="flex items-start gap-3">
                                                            <div class="flex-shrink-0">
                                                                <template x-if="preview.type === 'image'">
                                                                    <img :src="preview.url" class="h-16 w-16 object-cover rounded shadow-sm">
                                                                </template>
                                                                <template x-if="preview.type === 'document'">
                                                                    <div class="h-16 w-16 bg-gray-200 dark:bg-gray-600 rounded shadow-sm flex items-center justify-center">
                                                                        <i class="fas fa-file-alt text-2xl text-gray-500 dark:text-gray-300"></i>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                            <div class="flex-1 min-w-0">
                                                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Display Name</label>
                                                                <input type="text" x-model="preview.customName"
                                                                    class="block w-full text-sm border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-1.5 px-2 bg-white dark:bg-gray-800 dark:text-white focus:ring-red-500 focus:border-red-500"
                                                                    placeholder="Enter display name...">
                                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Original: <span x-text="preview.name"></span></p>
                                                                <input type="hidden" :name="'file_custom_names[' + index + ']'" :value="preview.customName">
                                                            </div>
                                                            <button @click="removeFile(index)" type="button" class="self-center text-gray-400 hover:text-red-500 focus:outline-none transition-colors">
                                                                <i class="fas fa-times text-lg"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                        <div class="flex text-sm text-gray-600 dark:text-gray-400 justify-center">
                                            <label for="evidence" class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-red-600 hover:text-red-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-red-500 px-2 py-0.5 border border-red-600/20">
                                                <span>Upload files</span>
                                                <input id="evidence" name="evidence[]" type="file" class="sr-only" x-ref="fileInput" @change="handleMultipleFileChange" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                                            </label>
                                            <p class="pl-1">or drag and drop</p>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG, GIF, PDF, DOC, TXT up to 10MB each</p>
                                        <p x-show="filePreviews.length > 0" x-text="`${filePreviews.length} file(s) selected`" class="text-sm font-medium text-red-600 dark:text-red-400"></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── Shared Navigation ── -->
                        <div class="pt-6 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between gap-3 mt-6">
                            <div>
                                <a x-show="currentStep === 1" href="<?= url('report_category.php') ?>"
                                    class="inline-flex items-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    Cancel
                                </a>
                                <button x-show="currentStep > 1" type="button" @click="prevStep()"
                                    class="inline-flex items-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <i class="fas fa-arrow-left mr-2 text-xs"></i> Back
                                </button>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-gray-400 dark:text-gray-500" x-text="`Step ${currentStep} of 3`"></span>
                                <button x-show="currentStep < 3" type="button" @click="nextStep()"
                                    class="inline-flex items-center py-2 px-5 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    Next <i class="fas fa-arrow-right ml-2 text-xs"></i>
                                </button>
                                <button x-show="currentStep === 3" type="submit"
                                    class="inline-flex items-center py-2 px-5 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <i class="fas fa-paper-plane mr-2"></i> Submit Incident
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
