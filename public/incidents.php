<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/includes/auth.php';
requireLogin();

// ── Active tab (downtime | security | fraud) ────────────────
$activeTab = in_array($_GET['tab'] ?? '', ['downtime', 'security', 'fraud']) ? $_GET['tab'] : 'downtime';

// ── CSRF token ──────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── POST: reopen security incident ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reopen_security') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid CSRF token.';
        header('Location: incidents.php?tab=security');
        exit;
    }
    $id = intval($_POST['incident_id'] ?? 0);
    if ($id > 0) {
        try {
            require_once __DIR__ . '/../src/includes/activity_logger.php';
            $pdo->prepare("UPDATE security_incidents SET status = 'pending', resolved_by = NULL, resolved_at = NULL WHERE id = ?")->execute([$id]);
            $pdo->prepare("INSERT INTO security_incident_updates (incident_id, user_id, user_name, update_text) VALUES (?, ?, 'System', ?)")
                ->execute([$id, $_SESSION['user_id'], 'Incident was reopened by ' . $_SESSION['full_name']]);
            logActivity($_SESSION['user_id'], 'reopen_security_incident', "Reopened security incident ID {$id}");
            $_SESSION['success'] = 'Security incident reopened.';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Could not reopen incident: ' . $e->getMessage();
        }
    }
    header('Location: incidents.php?tab=security');
    exit;
}

// ── POST: reopen fraud incident ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reopen_fraud') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid CSRF token.';
        header('Location: incidents.php?tab=fraud');
        exit;
    }
    $id = intval($_POST['incident_id'] ?? 0);
    if ($id > 0) {
        try {
            require_once __DIR__ . '/../src/includes/activity_logger.php';
            $pdo->prepare("UPDATE fraud_incidents SET status = 'pending', resolved_by = NULL, resolved_at = NULL WHERE id = ?")->execute([$id]);
            $pdo->prepare("INSERT INTO fraud_incident_updates (incident_id, user_id, user_name, update_text) VALUES (?, ?, 'System', ?)")
                ->execute([$id, $_SESSION['user_id'], 'Incident was reopened by ' . $_SESSION['full_name']]);
            logActivity($_SESSION['user_id'], 'reopen_fraud_incident', "Reopened fraud incident ID {$id}");
            $_SESSION['success'] = 'Fraud incident reopened.';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Could not reopen incident: ' . $e->getMessage();
        }
    }
    header('Location: incidents.php?tab=fraud');
    exit;
}

// ── POST: add update to security incident ────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_security_update') {
    $id   = intval($_POST['incident_id'] ?? 0);
    $text = trim($_POST['update_text'] ?? '');
    if ($id > 0 && $text !== '') {
        $pdo->prepare("INSERT INTO security_incident_updates (incident_id, user_id, user_name, update_text) VALUES (?, ?, ?, ?)")
            ->execute([$id, $_SESSION['user_id'], trim($_SESSION['full_name']), $text]);
    }
    header('Location: incidents.php?tab=security');
    exit;
}

// ── POST: add update to fraud incident ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_fraud_update') {
    $id   = intval($_POST['incident_id'] ?? 0);
    $text = trim($_POST['update_text'] ?? '');
    if ($id > 0 && $text !== '') {
        $pdo->prepare("INSERT INTO fraud_incident_updates (incident_id, user_id, user_name, update_text) VALUES (?, ?, ?, ?)")
            ->execute([$id, $_SESSION['user_id'], trim($_SESSION['full_name']), $text]);
    }
    header('Location: incidents.php?tab=fraud');
    exit;
}

// Handle status update (security/fraud actions are handled separately above)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])
    && !in_array($_POST['action'], ['resolve_security', 'resolve_fraud', 'reopen_security', 'reopen_fraud', 'add_security_update', 'add_fraud_update'])) {
    if ($_POST['action'] === 'add_update' && !empty($_POST['update_text']) && !empty($_POST['user_name'])) {
        // Add new update
        $stmt = $pdo->prepare("
            INSERT INTO incident_updates (incident_id, user_id, user_name, update_text) 
            VALUES (:incident_id, :user_id, :user_name, :update_text)
        ");
        $stmt->execute([
            ':incident_id' => $_POST['incident_id'],
            ':user_id' => $_SESSION['user_id'],
            ':user_name' => trim($_POST['user_name']),
            ':update_text' => trim($_POST['update_text'])
        ]);
    } elseif ($_POST['action'] === 'update_status' && isset($_POST['incident_id'], $_POST['status'])) {
        $status = $_POST['status'];
        $incidentId = $_POST['incident_id'];
        $userName = $_SESSION['full_name'];
        $errors = [];

        // Handle file uploads for root cause and lessons learned
        $root_cause_file = null;
        $lessons_learned_file = null;
        $uploadDir = __DIR__ . '/uploads/';
        $allowedExtensions = ['pdf', 'doc', 'docx', 'txt'];
        $maxFileSize = 10 * 1024 * 1024; // 10MB

        if ($status === 'resolved') {
            // Validate and process root cause file upload
            if (isset($_FILES['root_cause_file']) && $_FILES['root_cause_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['root_cause_file'];
                $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if ($file['size'] > $maxFileSize) {
                    $errors[] = 'Root cause file exceeds 10MB limit.';
                } elseif (!in_array($fileExt, $allowedExtensions)) {
                    $errors[] = 'Invalid root cause file type. Allowed: PDF, DOC, DOCX, TXT.';
                } else {
                    $newFileName = md5(time() . $file['name']) . '.' . $fileExt;
                    $destPath = $uploadDir . 'root_cause/' . $newFileName;
                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        $root_cause_file = 'uploads/root_cause/' . $newFileName;
                    } else {
                        $errors[] = 'Failed to upload root cause file.';
                    }
                }
            }

            // Validate and process lessons learned file upload
            if (isset($_FILES['lessons_learned_file']) && $_FILES['lessons_learned_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['lessons_learned_file'];
                $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if ($file['size'] > $maxFileSize) {
                    $errors[] = 'Lessons learned file exceeds 10MB limit.';
                } elseif (!in_array($fileExt, $allowedExtensions)) {
                    $errors[] = 'Invalid lessons learned file type. Allowed: PDF, DOC, DOCX, TXT.';
                } else {
                    $newFileName = md5(time() . $file['name']) . '.' . $fileExt;
                    $destPath = $uploadDir . 'lessons_learned/' . $newFileName;
                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        $lessons_learned_file = 'uploads/lessons_learned/' . $newFileName;
                    } else {
                        $errors[] = 'Failed to upload lessons learned file.';
                    }
                }
            }

            // Validation for resolved status
            $root_cause = $_POST['root_cause'] ?? '';
            $lessons_learned = $_POST['lessons_learned'] ?? '';
            $resolved_date = $_POST['resolved_date'] ?? null;
            $resolvers = $_POST['resolvers'] ?? [];

            // Check if root cause is required (either text or file)
            if (empty($root_cause) && empty($root_cause_file)) {
                $errors[] = 'Root cause is required when resolving an incident.';
            }

            // Check if resolvers are provided
            $valid_resolvers = array_filter(array_map('trim', is_array($resolvers) ? $resolvers : []));
            if (empty($valid_resolvers)) {
                $errors[] = 'At least one resolver name is required.';
            }

            // Check if lessons learned is provided (either text or file)
            if (empty($lessons_learned) && empty($lessons_learned_file)) {
                $errors[] = 'Lessons learned is required when resolving an incident.';
            }

            // Validate resolution date
            if (empty($resolved_date)) {
                $errors[] = 'Resolution date is required.';
            } elseif (strtotime($resolved_date) > time()) {
                $errors[] = 'Resolution date cannot be in the future.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode(' ', $errors);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        // Get current incident status to detect if it's being reopened
        $currentStatusStmt = $pdo->prepare("SELECT status FROM incidents WHERE incident_id = :incident_id");
        $currentStatusStmt->execute([':incident_id' => $incidentId]);
        $currentStatus = $currentStatusStmt->fetchColumn();

        // Prepare the SQL based on status
        $sql = "UPDATE incidents SET status = :status";

        // Add fields for resolved status
        if ($status === 'resolved') {
            $sql .= ", resolved_by = :user_id, resolved_at = :resolved_at, resolvers = :resolvers";
            if (!empty($root_cause)) {
                $sql .= ", root_cause = :root_cause";
            }
            if (!empty($root_cause_file)) {
                $sql .= ", root_cause_file = :root_cause_file";
            }
            if (!empty($lessons_learned)) {
                $sql .= ", lessons_learned = :lessons_learned";
            }
            if (!empty($lessons_learned_file)) {
                $sql .= ", lessons_learned_file = :lessons_learned_file";
            }
        } else {
            $sql .= ", resolved_by = NULL, resolved_at = NULL";
        }

        $sql .= " WHERE incident_id = :incident_id";

        $stmt = $pdo->prepare($sql);
        $params = [
            ':status' => $status,
            ':incident_id' => $incidentId
        ];

        if ($status === 'resolved') {
            $params[':user_id'] = $_SESSION['user_id'];
            $params[':resolved_at'] = date('Y-m-d H:i:s', strtotime($resolved_date));
            $params[':resolvers'] = json_encode(array_values($valid_resolvers));
            if (!empty($root_cause)) {
                $params[':root_cause'] = $root_cause;
            }
            if (!empty($root_cause_file)) {
                $params[':root_cause_file'] = $root_cause_file;
            }
            if (!empty($lessons_learned)) {
                $params[':lessons_learned'] = $lessons_learned;
            }
            if (!empty($lessons_learned_file)) {
                $params[':lessons_learned_file'] = $lessons_learned_file;
            }
        }

        $stmt->execute($params);

        // If resolving, close any open downtime record so SLA is calculated correctly
        if ($status === 'resolved') {
            $pdo->prepare("
                UPDATE downtime_incidents
                SET actual_end_time = :resolved_at
                WHERE incident_id = :incident_id AND actual_end_time IS NULL
            ")->execute([
                ':resolved_at'  => date('Y-m-d H:i:s', strtotime($resolved_date)),
                ':incident_id'  => $incidentId
            ]);
        }

        // Add system update with appropriate message
        // Detect if incident is being reopened (changing from resolved to pending)
        $isReopening = ($currentStatus === 'resolved' && $status === 'pending');

        if ($status === 'resolved') {
            $updateText = "Incident has been marked as resolved by " . $userName;
        } elseif ($isReopening) {
            $updateText = "Incident was reopened by " . $userName;
        } else {
            $updateText = "Incident status updated to pending by " . $userName;
        }

        $stmt = $pdo->prepare("
            INSERT INTO incident_updates (incident_id, user_id, user_name, update_text) 
            VALUES (:incident_id, :user_id, :user_name, :update_text)
        ");
        $stmt->execute([
            ':incident_id' => $incidentId,
            ':user_id' => $_SESSION['user_id'],
            ':user_name' => 'System',
            ':update_text' => $updateText
        ]);

        $_SESSION['success'] = "Incident updated successfully!";
    } elseif ($_POST['action'] === 'edit_incident' && isset($_POST['incident_id'])) {
        // Handle incident editing
        $incidentId = intval($_POST['incident_id']);
        $serviceId = intval($_POST['service_id']);
        $componentIdsRaw = isset($_POST['component_ids']) && is_array($_POST['component_ids']) ? $_POST['component_ids'] : [];
        $componentIds = array_values(array_unique(array_filter(array_map('intval', $componentIdsRaw))));
        $componentId = !empty($componentIds) ? $componentIds[0] : null; // backward compat
        $incidentTypeId = !empty($_POST['incident_type_id']) ? intval($_POST['incident_type_id']) : null;
        $impactLevel = $_POST['impact_level'];
        $priority = $_POST['priority'];
        $incidentSource = in_array($_POST['incident_source'] ?? '', ['internal', 'external']) ? $_POST['incident_source'] : 'external';
        $actualStartTime = $_POST['actual_start_time'];
        $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
        $rootCause   = !empty($_POST['root_cause'])   ? trim($_POST['root_cause'])   : null;
        $companies = isset($_POST['companies']) ? $_POST['companies'] : [];

        // Validate required fields
        if (empty($companies)) {
            $_SESSION['error'] = "Please select at least one affected company.";
        } else {
            try {
                $pdo->beginTransaction();

                // Update incident
                $stmt = $pdo->prepare("
                    UPDATE incidents
                    SET service_id = :service_id,
                        component_id = :component_id,
                        incident_type_id = :incident_type_id,
                        impact_level = :impact_level,
                        priority = :priority,
                        incident_source = :incident_source,
                        actual_start_time = :actual_start_time,
                        description = :description,
                        root_cause = :root_cause,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE incident_id = :incident_id
                ");
                $stmt->execute([
                    ':service_id' => $serviceId,
                    ':component_id' => $componentId,
                    ':incident_type_id' => $incidentTypeId,
                    ':impact_level' => $impactLevel,
                    ':priority' => $priority,
                    ':incident_source' => $incidentSource,
                    ':actual_start_time' => $actualStartTime,
                    ':description' => $description,
                    ':root_cause' => $rootCause,
                    ':incident_id' => $incidentId
                ]);

                // Update affected companies with audit trail
                // First, get existing companies to track changes
                $existingStmt = $pdo->prepare("SELECT company_id FROM incident_affected_companies WHERE incident_id = :incident_id");
                $existingStmt->execute([':incident_id' => $incidentId]);
                $existingCompanies = $existingStmt->fetchAll(PDO::FETCH_COLUMN);

                // Convert new companies to integers for comparison
                $newCompanies = array_map('intval', $companies);

                // Determine which companies were added and removed
                $addedCompanies = array_diff($newCompanies, $existingCompanies);
                $removedCompanies = array_diff($existingCompanies, $newCompanies);

                // Log removed companies to history
                if (!empty($removedCompanies)) {
                    $historyStmt = $pdo->prepare("
                        INSERT INTO incident_company_history (incident_id, company_id, action, changed_by) 
                        VALUES (:incident_id, :company_id, 'removed', :changed_by)
                    ");
                    foreach ($removedCompanies as $companyId) {
                        $historyStmt->execute([
                            ':incident_id' => $incidentId,
                            ':company_id' => $companyId,
                            ':changed_by' => $_SESSION['user_id']
                        ]);
                    }
                }

                // Log added companies to history
                if (!empty($addedCompanies)) {
                    $historyStmt = $pdo->prepare("
                        INSERT INTO incident_company_history (incident_id, company_id, action, changed_by) 
                        VALUES (:incident_id, :company_id, 'added', :changed_by)
                    ");
                    foreach ($addedCompanies as $companyId) {
                        $historyStmt->execute([
                            ':incident_id' => $incidentId,
                            ':company_id' => $companyId,
                            ':changed_by' => $_SESSION['user_id']
                        ]);
                    }
                }

                // Delete existing companies
                $deleteStmt = $pdo->prepare("DELETE FROM incident_affected_companies WHERE incident_id = :incident_id");
                $deleteStmt->execute([':incident_id' => $incidentId]);

                // Insert new companies
                $insertStmt = $pdo->prepare("
                    INSERT INTO incident_affected_companies (incident_id, company_id) 
                    VALUES (:incident_id, :company_id)
                ");
                foreach ($newCompanies as $companyId) {
                    $insertStmt->execute([
                        ':incident_id' => $incidentId,
                        ':company_id' => $companyId
                    ]);
                }

                // Sync incident_components junction table
                $pdo->prepare("DELETE FROM incident_components WHERE incident_id = ?")->execute([$incidentId]);
                if (!empty($componentIds)) {
                    $icStmt = $pdo->prepare("INSERT IGNORE INTO incident_components (incident_id, component_id) VALUES (?, ?)");
                    foreach ($componentIds as $cid) {
                        $icStmt->execute([$incidentId, $cid]);
                    }
                }

                // Handle attachment deletions
                if (!empty($_POST['delete_attachments']) && !empty($_POST['delete_attachment_paths'])) {
                    $deleteAttachmentIds = $_POST['delete_attachments'];
                    $deleteAttachmentPaths = $_POST['delete_attachment_paths'];

                    foreach ($deleteAttachmentIds as $index => $attachmentId) {
                        $attachmentId = intval($attachmentId);
                        $filePath = $deleteAttachmentPaths[$index];

                        // Delete from database
                        $deleteAttStmt = $pdo->prepare("DELETE FROM incident_attachments WHERE attachment_id = :attachment_id AND incident_id = :incident_id");
                        $deleteAttStmt->execute([
                            ':attachment_id' => $attachmentId,
                            ':incident_id' => $incidentId
                        ]);

                        // Delete file from server
                        $fullPath = __DIR__ . '/../' . $filePath;
                        if (file_exists($fullPath)) {
                            unlink($fullPath);
                        }
                    }
                }

                // Handle new file uploads
                if (!empty($_FILES['new_attachments']['name'][0])) {
                    $uploadDir = __DIR__ . '/uploads/incidents/';
                    $allowedTypes = [
                        'image/jpeg',
                        'image/jpg',
                        'image/png',
                        'image/gif',
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'text/plain'
                    ];
                    $maxFileSize = 10 * 1024 * 1024; // 10MB

                    foreach ($_FILES['new_attachments']['name'] as $key => $fileName) {
                        if ($_FILES['new_attachments']['error'][$key] === UPLOAD_ERR_OK) {
                            $fileTmpPath = $_FILES['new_attachments']['tmp_name'][$key];
                            $fileType = $_FILES['new_attachments']['type'][$key];
                            $fileSize = $_FILES['new_attachments']['size'][$key];

                            // Get custom name if provided
                            $customName = isset($_POST['new_file_custom_names'][$key]) && !empty($_POST['new_file_custom_names'][$key])
                                ? $_POST['new_file_custom_names'][$key]
                                : $fileName;

                            // Validate file type
                            if (!in_array($fileType, $allowedTypes)) {
                                continue; // Skip invalid files
                            }

                            // Validate file size
                            if ($fileSize > $maxFileSize) {
                                continue; // Skip files that are too large
                            }

                            // Generate unique filename
                            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                            $newFileName = md5(uniqid() . $fileName . time()) . '.' . $fileExtension;
                            $destPath = $uploadDir . $newFileName;

                            // Move uploaded file
                            if (move_uploaded_file($fileTmpPath, $destPath)) {
                                // Insert into database
                                $insertAttStmt = $pdo->prepare("
                                    INSERT INTO incident_attachments (incident_id, file_path, file_name, file_type, file_size)
                                    VALUES (:incident_id, :file_path, :file_name, :file_type, :file_size)
                                ");
                                $insertAttStmt->execute([
                                    ':incident_id' => $incidentId,
                                    ':file_path' => 'uploads/incidents/' . $newFileName,
                                    ':file_name' => $customName,
                                    ':file_type' => $fileType,
                                    ':file_size' => $fileSize
                                ]);
                            }
                        }
                    }
                }

                // Add system update log
                $updateText = "Incident details updated by " . $_SESSION['full_name'];
                $logStmt = $pdo->prepare("
                    INSERT INTO incident_updates (incident_id, user_id, user_name, update_text) 
                    VALUES (:incident_id, :user_id, :user_name, :update_text)
                ");
                $logStmt->execute([
                    ':incident_id' => $incidentId,
                    ':user_id' => $_SESSION['user_id'],
                    ':user_name' => 'System',
                    ':update_text' => $updateText
                ]);

                $pdo->commit();
                $_SESSION['success'] = "Incident details updated successfully!";

            } catch (PDOException $e) {
                $pdo->rollBack();
                $_SESSION['error'] = "Failed to update incident: " . $e->getMessage();
            }
        }
    }

    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ── POST: resolve security incident ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resolve_security') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid CSRF token.';
        header('Location: incidents.php?tab=security');
        exit;
    }
    $id          = intval($_POST['incident_id'] ?? 0);
    $rootCause   = trim($_POST['root_cause'] ?? '');
    $lessonsLearned = trim($_POST['lessons_learned'] ?? '');
    $resolvers   = array_values(array_filter(array_map('trim', (array)($_POST['resolvers'] ?? []))));
    $resolvedDate = $_POST['resolved_date'] ?? null;

    $errors = [];
    if (empty($rootCause))       $errors[] = 'Root cause is required.';
    if (empty($lessonsLearned))  $errors[] = 'Lessons learned is required.';
    if (empty($resolvers))       $errors[] = 'At least one resolver name is required.';
    if (empty($resolvedDate))    $errors[] = 'Resolution date is required.';
    elseif (strtotime($resolvedDate) > time()) $errors[] = 'Resolution date cannot be in the future.';

    if (!empty($errors)) {
        $_SESSION['error'] = implode(' ', $errors);
        header('Location: incidents.php?tab=security');
        exit;
    }

    if ($id > 0) {
        try {
            require_once __DIR__ . '/../src/includes/activity_logger.php';
            $stmt = $pdo->prepare("
                UPDATE security_incidents
                SET status = 'resolved', resolved_by = ?, resolved_at = ?,
                    root_cause = ?, lessons_learned = ?, resolvers = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $resolvedDate,
                $rootCause,
                $lessonsLearned,
                json_encode($resolvers),
                $id,
            ]);
            logActivity($_SESSION['user_id'], 'resolve_security_incident', "Resolved security incident ID {$id}");
            $_SESSION['success'] = 'Security incident marked as resolved.';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Could not resolve incident: ' . $e->getMessage();
        }
    }
    header('Location: incidents.php?tab=security');
    exit;
}

// ── POST: resolve fraud incident ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resolve_fraud') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid CSRF token.';
        header('Location: incidents.php?tab=fraud');
        exit;
    }
    $id          = intval($_POST['incident_id'] ?? 0);
    $rootCause   = trim($_POST['root_cause'] ?? '');
    $lessonsLearned = trim($_POST['lessons_learned'] ?? '');
    $resolvers   = array_values(array_filter(array_map('trim', (array)($_POST['resolvers'] ?? []))));
    $resolvedDate = $_POST['resolved_date'] ?? null;

    $errors = [];
    if (empty($rootCause))       $errors[] = 'Root cause is required.';
    if (empty($lessonsLearned))  $errors[] = 'Lessons learned is required.';
    if (empty($resolvers))       $errors[] = 'At least one resolver name is required.';
    if (empty($resolvedDate))    $errors[] = 'Resolution date is required.';
    elseif (strtotime($resolvedDate) > time()) $errors[] = 'Resolution date cannot be in the future.';

    if (!empty($errors)) {
        $_SESSION['error'] = implode(' ', $errors);
        header('Location: incidents.php?tab=fraud');
        exit;
    }

    if ($id > 0) {
        try {
            require_once __DIR__ . '/../src/includes/activity_logger.php';
            $stmt = $pdo->prepare("
                UPDATE fraud_incidents
                SET status = 'resolved', resolved_by = ?, resolved_at = ?,
                    root_cause = ?, lessons_learned = ?, resolvers = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $resolvedDate,
                $rootCause,
                $lessonsLearned,
                json_encode($resolvers),
                $id,
            ]);
            logActivity($_SESSION['user_id'], 'resolve_fraud_incident', "Resolved fraud incident ID {$id}");
            $_SESSION['success'] = 'Fraud incident marked as resolved.';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Could not resolve incident: ' . $e->getMessage();
        }
    }
    header('Location: incidents.php?tab=fraud');
    exit;
}

// ── Filter & pagination ────────────────────────────────────
$statusFilter = in_array($_GET['status'] ?? '', ['pending', 'resolved']) ? $_GET['status'] : '';
$searchFilter = trim($_GET['search'] ?? '');
$dateFrom     = trim($_GET['date_from'] ?? '');
$dateTo       = trim($_GET['date_to'] ?? '');
$itemsPerPage = 10;
$currentPage  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset       = ($currentPage - 1) * $itemsPerPage;

// Helper: build a URL preserving current filters + tab
function pageUrl(int $page, string $status, string $search, string $tab = 'downtime', string $dateFrom = '', string $dateTo = ''): string {
    $p = ['tab' => $tab, 'page' => $page];
    if ($status) $p['status'] = $status;
    if ($search !== '') $p['search'] = $search;
    if ($dateFrom !== '') $p['date_from'] = $dateFrom;
    if ($dateTo !== '') $p['date_to'] = $dateTo;
    return '?' . http_build_query($p);
}

// ── Threat / fraud label maps ──────────────────────────────
$threatLabels = [
    'phishing'             => 'Phishing',
    'unauthorized_access'  => 'Unauthorized Access',
    'data_breach'          => 'Data Breach',
    'malware'              => 'Malware',
    'social_engineering'   => 'Social Engineering',
    'other'                => 'Other',
];
$fraudLabels = [
    'card_fraud'         => 'Card Fraud',
    'account_takeover'   => 'Account Takeover',
    'transaction_fraud'  => 'Transaction Fraud',
    'internal_fraud'     => 'Internal Fraud',
    'other'              => 'Other',
];

// ── Data fetch based on active tab ────────────────────────
$incidents      = [];
$otherIncidents = [];
$totalIncidents = 0;
$totalPages     = 1;
$services = $components = $incidentTypes = $companies = [];

try {
    if ($activeTab === 'downtime') {
        $whereClauses = [];
        $filterParams = [];
        if ($statusFilter) {
            $whereClauses[] = "i.status = ?";
            $filterParams[] = $statusFilter;
        }
        if ($searchFilter !== '') {
            $whereClauses[] = "(s.service_name LIKE ? OR i.incident_ref LIKE ? OR i.root_cause LIKE ? OR it.name LIKE ? OR EXISTS (SELECT 1 FROM incident_affected_companies iac2 JOIN companies c2 ON iac2.company_id = c2.company_id WHERE iac2.incident_id = i.incident_id AND c2.company_name LIKE ?))";
            $wild = "%" . $searchFilter . "%";
            array_push($filterParams, $wild, $wild, $wild, $wild, $wild);
        }
        if ($dateFrom !== '') {
            $whereClauses[] = "DATE(i.actual_start_time) >= ?";
            $filterParams[] = $dateFrom;
        }
        if ($dateTo !== '') {
            $whereClauses[] = "DATE(i.actual_start_time) <= ?";
            $filterParams[] = $dateTo;
        }
        $whereSQL = $whereClauses ? "WHERE " . implode(" AND ", $whereClauses) : "";

        $countStmt = $pdo->prepare("SELECT COUNT(DISTINCT i.incident_id) FROM incidents i JOIN services s ON i.service_id = s.service_id LEFT JOIN incident_types it ON i.incident_type_id = it.type_id $whereSQL");
        $countStmt->execute($filterParams);
        $totalIncidents = $countStmt->fetchColumn();
        $totalPages = max(1, ceil($totalIncidents / $itemsPerPage));

        $stmt = $pdo->prepare("
            SELECT i.incident_id, i.incident_ref, i.service_id, i.root_cause, i.status,
                i.impact_level, i.priority, i.incident_source, i.attachment_path, i.actual_start_time,
                EXISTS(SELECT 1 FROM downtime_incidents di WHERE di.incident_id = i.incident_id) AS causes_downtime,
                (SELECT COALESCE(SUM(di.downtime_minutes),0) FROM downtime_incidents di WHERE di.incident_id = i.incident_id) AS downtime_minutes,
                i.description, u.full_name as user_name, i.created_at,
                res.full_name as resolved_by, i.resolved_at, i.updated_at,
                i.root_cause_file, i.lessons_learned, i.lessons_learned_file, i.resolvers,
                s.service_name,
                GROUP_CONCAT(DISTINCT sc.name ORDER BY sc.name SEPARATOR ', ') as component_names,
                it.name as incident_type_name,
                CASE WHEN GROUP_CONCAT(DISTINCT c.company_name ORDER BY c.company_name SEPARATOR ', ') LIKE '%All%' THEN 'All' ELSE GROUP_CONCAT(DISTINCT c.company_name ORDER BY c.company_name SEPARATOR ', ') END as affected_companies,
                COUNT(DISTINCT c.company_id) as company_count,
                (SELECT COUNT(*) FROM incident_updates iu WHERE iu.incident_id = i.incident_id) as update_count,
                (SELECT COUNT(*) FROM incident_attachments ia WHERE ia.incident_id = i.incident_id) as attachment_count
            FROM incidents i
            JOIN services s ON i.service_id = s.service_id
            JOIN users u ON i.reported_by = u.user_id
            LEFT JOIN users res ON i.resolved_by = res.user_id
            LEFT JOIN incident_affected_companies iac ON i.incident_id = iac.incident_id
            LEFT JOIN companies c ON iac.company_id = c.company_id
            LEFT JOIN incident_components icomp ON i.incident_id = icomp.incident_id
            LEFT JOIN components sc ON icomp.component_id = sc.component_id
            LEFT JOIN incident_types it ON i.incident_type_id = it.type_id
            $whereSQL
            GROUP BY i.incident_id
            ORDER BY FIELD(i.status, 'pending', 'resolved'), i.updated_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute(array_merge($filterParams, [$itemsPerPage, $offset]));
        $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($incidents as &$incident) {
            $s2 = $pdo->prepare("SELECT * FROM incident_updates WHERE incident_id = ? ORDER BY created_at DESC");
            $s2->execute([$incident['incident_id']]);
            $incident['updates'] = $s2->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($incident);

        $services      = $pdo->query("SELECT service_id, service_name FROM services ORDER BY service_name")->fetchAll();
        $components    = $pdo->query("SELECT component_id, name FROM components WHERE is_active = 1 ORDER BY name")->fetchAll();
        $incidentTypes = $pdo->query("SELECT type_id, name FROM incident_types ORDER BY name")->fetchAll();
        $companies     = $pdo->query("SELECT company_id, company_name FROM companies ORDER BY company_name")->fetchAll();

    } elseif ($activeTab === 'security') {
        $whereClauses = [];
        $filterParams = [];
        if ($statusFilter !== '') { $whereClauses[] = "s.status = ?"; $filterParams[] = $statusFilter; }
        if ($searchFilter !== '') {
            $whereClauses[] = "(s.incident_ref LIKE ? OR s.threat_type LIKE ? OR s.systems_affected LIKE ? OR s.description LIKE ?)";
            $wild = '%' . $searchFilter . '%';
            array_push($filterParams, $wild, $wild, $wild, $wild);
        }
        if ($dateFrom !== '') { $whereClauses[] = "DATE(s.actual_start_time) >= ?"; $filterParams[] = $dateFrom; }
        if ($dateTo !== '')   { $whereClauses[] = "DATE(s.actual_start_time) <= ?"; $filterParams[] = $dateTo; }
        $whereSQL = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM security_incidents s $whereSQL");
        $countStmt->execute($filterParams);
        $totalIncidents = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($totalIncidents / $itemsPerPage));

        $stmt = $pdo->prepare("
            SELECT s.id, s.incident_ref, s.threat_type, s.systems_affected, s.description,
                s.impact_level, s.priority, s.containment_status, s.escalated_to,
                s.actual_start_time, s.status, s.resolved_at, u.full_name AS reporter_name
            FROM security_incidents s
            JOIN users u ON s.reported_by = u.user_id
            $whereSQL
            ORDER BY FIELD(s.status, 'pending', 'resolved'), s.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute(array_merge($filterParams, [$itemsPerPage, $offset]));
        $otherIncidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($otherIncidents as &$inc) {
            $s = $pdo->prepare("SELECT * FROM security_incident_updates WHERE incident_id = ? ORDER BY created_at DESC");
            $s->execute([$inc['id']]);
            $inc['updates'] = $s->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($inc);

    } else { // fraud
        $whereClauses = [];
        $filterParams = [];
        if ($statusFilter !== '') { $whereClauses[] = "f.status = ?"; $filterParams[] = $statusFilter; }
        if ($searchFilter !== '') {
            $whereClauses[] = "(f.incident_ref LIKE ? OR f.fraud_type LIKE ? OR f.description LIKE ?)";
            $wild = '%' . $searchFilter . '%';
            array_push($filterParams, $wild, $wild, $wild);
        }
        if ($dateFrom !== '') { $whereClauses[] = "DATE(f.actual_start_time) >= ?"; $filterParams[] = $dateFrom; }
        if ($dateTo !== '')   { $whereClauses[] = "DATE(f.actual_start_time) <= ?"; $filterParams[] = $dateTo; }
        $whereSQL = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM fraud_incidents f $whereSQL");
        $countStmt->execute($filterParams);
        $totalIncidents = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($totalIncidents / $itemsPerPage));

        $stmt = $pdo->prepare("
            SELECT f.id, f.incident_ref, f.fraud_type, f.service_id, f.description,
                f.financial_impact, f.impact_level, f.priority, f.regulatory_reported,
                f.actual_start_time, f.status, f.resolved_at,
                u.full_name AS reporter_name, sv.service_name
            FROM fraud_incidents f
            JOIN users u ON f.reported_by = u.user_id
            LEFT JOIN services sv ON f.service_id = sv.service_id
            $whereSQL
            ORDER BY FIELD(f.status, 'pending', 'resolved'), f.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute(array_merge($filterParams, [$itemsPerPage, $offset]));
        $otherIncidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($otherIncidents as &$inc) {
            $s = $pdo->prepare("SELECT * FROM fraud_incident_updates WHERE incident_id = ? ORDER BY created_at DESC");
            $s->execute([$inc['id']]);
            $inc['updates'] = $s->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($inc);
    }
} catch (PDOException $e) {
    die("ERROR: Could not fetch incidents. " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <script>if(localStorage.getItem('theme')==='dark'||(!localStorage.getItem('theme')&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark')}else{document.documentElement.classList.remove('dark')}</script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incidents - ETZ Downtime</title>

    <!-- Tailwind CSS v3.4.17 -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>

    <!-- Alpine.js v3.x -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome 6.5.1 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .incident-card {
            transition: box-shadow 0.15s ease;
        }

        .incident-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
        }

        .status-badge {
            @apply inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium border;
        }

        /* Impact level badges */
        .impact-high,
        .impact-critical {
            @apply bg-red-50 text-red-700 border-red-200;
        }

        .impact-medium {
            @apply bg-yellow-50 text-yellow-700 border-yellow-200;
        }

        .impact-low {
            @apply bg-green-50 text-green-700 border-green-200;
        }
    </style>
</head>

<body class="relative min-h-screen">
    <!-- Background Image with Overlay -->
    <div class="fixed inset-0 z-0">
        <img src="<?= url('../src/assets/mainbg.jpg') ?>" alt="Background" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-white/90 dark:bg-gray-900/95"></div>
    </div>

    <!-- Content Wrapper -->
    <div class="relative z-10">
        <!-- Navbar -->
        <?php include __DIR__ . '/../src/includes/navbar.php'; ?>

        <!-- Loading Overlay -->
        <?php include __DIR__ . '/../src/includes/loading.php'; ?>

        <!-- Main Content -->
        <main class="py-6">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
                    <div class="bg-green-50 border-l-4 border-green-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700"><?php echo htmlspecialchars($_SESSION['success']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Page Header -->
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl sm:truncate">
                            Incident Management
                        </h2>
                    </div>
                    <div class="mt-4 sm:mt-0 flex items-center space-x-3">
                        <span class="text-xs text-gray-500 dark:text-gray-400" id="last-updated">
                            Last updated: <?php echo date('g:i A'); ?>
                        </span>
                        <button onclick="refreshIncidents()"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                </path>
                            </svg>
                            Refresh
                        </button>
                    </div>
                </div>

                <!-- ── 3-Tab Switcher: Downtime / Security / Fraud ── -->
                <?php
                    $dtUrl  = '?tab=downtime'  . ($statusFilter ? '&status=' . urlencode($statusFilter) : '') . ($searchFilter ? '&search=' . urlencode($searchFilter) : '') . ($dateFrom ? '&date_from=' . urlencode($dateFrom) : '') . ($dateTo ? '&date_to=' . urlencode($dateTo) : '');
                    $secUrl = '?tab=security'  . ($statusFilter ? '&status=' . urlencode($statusFilter) : '') . ($searchFilter ? '&search=' . urlencode($searchFilter) : '') . ($dateFrom ? '&date_from=' . urlencode($dateFrom) : '') . ($dateTo ? '&date_to=' . urlencode($dateTo) : '');
                    $frUrl  = '?tab=fraud'     . ($statusFilter ? '&status=' . urlencode($statusFilter) : '') . ($searchFilter ? '&search=' . urlencode($searchFilter) : '') . ($dateFrom ? '&date_from=' . urlencode($dateFrom) : '') . ($dateTo ? '&date_to=' . urlencode($dateTo) : '');
                ?>
                <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6">
                    <a href="<?= $dtUrl ?>"
                       class="px-5 py-3 text-sm font-medium -mb-px transition-colors
                              <?= $activeTab === 'downtime'
                                    ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' ?>">
                        <i class="fas fa-clock-rotate-left mr-1.5"></i>Downtime
                    </a>
                    <a href="<?= $secUrl ?>"
                       class="px-5 py-3 text-sm font-medium -mb-px transition-colors
                              <?= $activeTab === 'security'
                                    ? 'border-b-2 border-red-600 text-red-600 dark:text-red-400 dark:border-red-400'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' ?>">
                        <i class="fas fa-shield-halved mr-1.5"></i>Security
                    </a>
                    <a href="<?= $frUrl ?>"
                       class="px-5 py-3 text-sm font-medium -mb-px transition-colors
                              <?= $activeTab === 'fraud'
                                    ? 'border-b-2 border-amber-500 text-amber-600 dark:text-amber-400 dark:border-amber-400'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' ?>">
                        <i class="fas fa-triangle-exclamation mr-1.5"></i>Fraud
                    </a>
                </div>

                <!-- Search and Filter Bar -->
                <div class="mb-6 flex flex-col gap-3">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <!-- Search -->
                        <div class="flex-1">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input type="text" id="incident-search"
                                    placeholder="<?= $activeTab === 'security' ? 'Search by ref, threat type, systems or description…' : ($activeTab === 'fraud' ? 'Search by ref, fraud type or description…' : 'Search by service, company, ref, type or root cause…') ?>"
                                    value="<?= htmlspecialchars($searchFilter) ?>"
                                    class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent sm:text-sm">
                                <?php if ($searchFilter): ?>
                                    <a href="?tab=<?= urlencode($activeTab) ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : '' ?><?= $dateFrom ? '&date_from=' . urlencode($dateFrom) : '' ?><?= $dateTo ? '&date_to=' . urlencode($dateTo) : '' ?>"
                                       class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- Date range -->
                        <div class="flex items-center gap-2">
                            <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>"
                                class="py-2.5 px-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <span class="text-gray-400 text-sm">–</span>
                            <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($dateTo) ?>"
                                class="py-2.5 px-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <!-- status toggle buttons -->
                    <div class="flex md:mt-0">
                        <div class="inline-flex rounded-lg shadow-sm" role="group">
                            <button type="button" data-status=""
                                class="status-toggle px-4 py-2 text-sm font-medium rounded-l-lg border border-gray-200 <?= $statusFilter === '' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-white text-gray-700' ?> hover:bg-gray-50 transition-colors duration-200 ease-in-out focus:outline-none">
                                <span class="flex items-center">
                                    <i class="fas fa-list-ul mr-2 <?= $statusFilter === '' ? 'text-blue-600' : 'text-gray-500' ?>"></i>
                                    <span>All</span>
                                </span>
                            </button>
                            <button type="button" data-status="pending"
                                class="status-toggle px-4 py-2 text-sm font-medium border-t border-b border-gray-200 <?= $statusFilter === 'pending' ? 'bg-yellow-50 text-yellow-700 border-yellow-200' : 'bg-white text-gray-700' ?> hover:bg-yellow-50 transition-colors duration-200 ease-in-out focus:outline-none">
                                <span class="flex items-center">
                                    <i class="fas fa-clock mr-2 text-yellow-500"></i>
                                    <span>Pending</span>
                                </span>
                            </button>
                            <button type="button" data-status="resolved"
                                class="status-toggle px-4 py-2 text-sm font-medium rounded-r-lg border border-gray-200 <?= $statusFilter === 'resolved' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-white text-gray-700' ?> hover:bg-green-50 transition-colors duration-200 ease-in-out focus:outline-none">
                                <span class="flex items-center">
                                    <i class="fas fa-check-circle mr-2 text-green-500"></i>
                                    <span>Resolved</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Results summary -->
                <div class="mb-4 flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <?php if ($totalIncidents === 0): ?>
                            No incidents found<?= $searchFilter ? ' for "<strong>' . htmlspecialchars($searchFilter) . '</strong>"' : '' ?>
                        <?php else: ?>
                            Showing <strong class="text-gray-900 dark:text-white"><?= number_format(($offset + 1)) ?>–<?= number_format(min($offset + $itemsPerPage, $totalIncidents)) ?></strong>
                            of <strong class="text-gray-900 dark:text-white"><?= number_format($totalIncidents) ?></strong>
                            <?= $statusFilter ? ucfirst($statusFilter) . ' ' : '' ?><?= ucfirst($activeTab) ?> incidents
                            <?= $searchFilter ? 'matching "<strong>' . htmlspecialchars($searchFilter) . '</strong>"' : '' ?>
                        <?php endif; ?>
                    </p>
                    <?php if ($statusFilter || $searchFilter || $dateFrom || $dateTo): ?>
                        <a href="incidents.php?tab=<?= urlencode($activeTab) ?>" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium">
                            <i class="fas fa-times mr-1"></i> Clear filters
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Incidents Content (switches based on active tab) -->
                <div class="space-y-4">

                <?php if ($activeTab === 'security' || $activeTab === 'fraud'): ?>
                    <?php if (empty($otherIncidents)): ?>
                        <div class="text-center py-16">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No <?= $activeTab ?> incidents found</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                <?= ($searchFilter || $statusFilter) ? 'Try adjusting your filters.' : 'No ' . $activeTab . ' incidents have been reported yet.' ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($otherIncidents as $inc):
                            $impactKey   = strtolower($inc['impact_level'] ?? 'low');
                            $priorityKey = strtolower($inc['priority'] ?? 'medium');
                        ?>
                        <div class="incident-card bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm mb-4 p-5 overflow-hidden">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-3">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <!-- Ref chip — neutral gray -->
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-mono font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                        <i class="fas fa-hashtag text-[9px]"></i><?= htmlspecialchars($inc['incident_ref']) ?>
                                    </span>
                                    <!-- All metadata badges — uniform neutral gray -->
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                        <?= ucfirst($impactKey) ?> Impact
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                        <?= ucfirst($priorityKey) ?> Priority
                                    </span>
                                    <?php if ($activeTab === 'security' && !empty($inc['containment_status'])): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $inc['containment_status']))) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($activeTab === 'fraud' && !empty($inc['regulatory_reported'])): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                            <i class="fas fa-landmark text-[9px]"></i>Regulatory
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <!-- Status — the only coloured badge -->
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium
                                    <?= $inc['status'] === 'pending'
                                        ? 'bg-amber-500 text-white dark:bg-amber-600'
                                        : 'bg-green-500 text-white dark:bg-green-600' ?>">
                                    <i class="fas <?= $inc['status'] === 'pending' ? 'fa-clock' : 'fa-check-circle' ?> mr-1.5 text-[10px]"></i>
                                    <?= $inc['status'] === 'pending' ? 'Pending' : 'Resolved' ?>
                                </span>
                            </div>

                            <div class="mb-3">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-600 dark:text-gray-400">
                                    <?php if ($activeTab === 'security'): ?>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">
                                            <i class="fas fa-shield-halved mr-1 text-red-500"></i>
                                            <?= htmlspecialchars($threatLabels[$inc['threat_type']] ?? ucfirst(str_replace('_', ' ', $inc['threat_type'] ?? ''))) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">
                                            <i class="fas fa-triangle-exclamation mr-1 text-amber-500"></i>
                                            <?= htmlspecialchars($fraudLabels[$inc['fraud_type']] ?? ucfirst(str_replace('_', ' ', $inc['fraud_type'] ?? ''))) ?>
                                        </span>
                                        <?php if (!empty($inc['service_name'])): ?>
                                            <span class="text-gray-400 dark:text-gray-500">·</span>
                                            <span><i class="fas fa-server mr-1 text-gray-400"></i><?= htmlspecialchars($inc['service_name']) ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if (!empty($inc['actual_start_time'])): ?>
                                        <span class="text-gray-400 dark:text-gray-500">·</span>
                                        <span><i class="fas fa-calendar mr-1 text-gray-400"></i><?= date('M j, Y g:i A', strtotime($inc['actual_start_time'])) ?></span>
                                    <?php endif; ?>
                                    <span class="text-gray-400 dark:text-gray-500">·</span>
                                    <span><i class="fas fa-user mr-1 text-gray-400"></i><?= htmlspecialchars($inc['reporter_name'] ?? 'Unknown') ?></span>
                                </div>
                            </div>

                            <div class="mb-4 space-y-2">
                                <?php if (!empty($inc['description'])): ?>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        <?= htmlspecialchars(mb_strlen($inc['description']) > 120 ? mb_substr($inc['description'], 0, 120) . '…' : $inc['description']) ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($activeTab === 'security' && !empty($inc['systems_affected'])): ?>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <span class="font-medium text-gray-600 dark:text-gray-300">Systems affected:</span>
                                        <?= htmlspecialchars(mb_strlen($inc['systems_affected']) > 120 ? mb_substr($inc['systems_affected'], 0, 120) . '…' : $inc['systems_affected']) ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($activeTab === 'fraud' && isset($inc['financial_impact']) && $inc['financial_impact'] !== null && $inc['financial_impact'] !== ''): ?>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <span class="font-medium text-gray-600 dark:text-gray-300">Financial impact:</span>
                                        <span class="font-semibold text-amber-700 dark:text-amber-400">GH₵ <?= number_format((float)$inc['financial_impact'], 2) ?></span>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <!-- Activity Timeline -->
                            <div class="border-t border-gray-100 dark:border-gray-700 pt-3 mt-4">
                                <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2 flex items-center gap-1.5">
                                    <i class="fas fa-history text-gray-400"></i> Activity
                                    <span class="ml-1 px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 font-bold"><?= count($inc['updates']) ?></span>
                                </p>
                                <div class="space-y-2 max-h-52 overflow-y-auto pr-1 mb-3 scrollbar-thin">
                                    <?php if (empty($inc['updates'])): ?>
                                        <p class="text-xs text-gray-400 italic text-center py-4">No activity logged yet.</p>
                                    <?php else: ?>
                                        <?php foreach ($inc['updates'] as $update): ?>
                                            <div class="flex gap-2.5 text-sm">
                                                <div class="flex-shrink-0 mt-1">
                                                    <div class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                                        <i class="fas <?= $update['user_name'] === 'System' ? 'fa-robot text-purple-400' : 'fa-user text-blue-400' ?> text-[9px]"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-baseline gap-1.5 flex-wrap">
                                                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300"><?= htmlspecialchars($update['user_name']) ?></span>
                                                        <span class="text-[10px] text-gray-400"><?= date('M j, g:i A', strtotime($update['created_at'])) ?></span>
                                                    </div>
                                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5 leading-relaxed"><?= nl2br(htmlspecialchars($update['update_text'])) ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <?php if ($inc['status'] === 'pending'): ?>
                                    <form method="POST" class="mt-2 border-t border-gray-100 dark:border-gray-700 pt-3">
                                        <input type="hidden" name="action" value="<?= $activeTab === 'security' ? 'add_security_update' : 'add_fraud_update' ?>">
                                        <input type="hidden" name="incident_id" value="<?= (int)$inc['id'] ?>">
                                        <div class="flex flex-col gap-2">
                                            <input type="text" name="update_text" placeholder="Describe action taken…" required
                                                class="w-full text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg py-2 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 placeholder-gray-400">
                                            <input type="hidden" name="user_name" value="<?= htmlspecialchars($_SESSION['full_name']) ?>">
                                            <div class="flex items-center justify-between">
                                                <button type="button"
                                                    onclick="showResolveModal(<?= (int)$inc['id'] ?>, '<?= $activeTab === 'security' ? 'resolve_security' : 'resolve_fraud' ?>')"
                                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors
                                                        <?= $activeTab === 'security'
                                                            ? 'bg-white dark:bg-gray-700 border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20'
                                                            : 'bg-white dark:bg-gray-700 border-amber-300 dark:border-amber-700 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20' ?>">
                                                    <i class="fas fa-check mr-1.5"></i>Mark as Resolved
                                                </button>
                                                <button type="submit"
                                                    class="self-end inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-gray-800 dark:bg-gray-600 hover:bg-gray-700 dark:hover:bg-gray-500 transition-colors">
                                                    <i class="fas fa-paper-plane text-[10px]"></i> Post Update
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="mt-2 border-t border-gray-100 dark:border-gray-700 pt-3 flex items-center justify-between flex-wrap gap-2">
                                        <p class="text-[11px] text-gray-400 flex items-center gap-1.5">
                                            <i class="fas fa-lock text-gray-300"></i>
                                            Resolved <?= !empty($inc['resolved_at']) ? date('M j, Y', strtotime($inc['resolved_at'])) : '' ?>
                                        </p>
                                        <button type="button"
                                            onclick="showReopenModal(<?= (int)$inc['id'] ?>, '<?= $activeTab === 'security' ? 'reopen_security' : 'reopen_fraud' ?>')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border border-orange-400 text-orange-600 dark:text-orange-400 bg-white dark:bg-gray-800 hover:bg-orange-50 dark:hover:bg-gray-700 transition-colors">
                                            <i class="fas fa-rotate-left text-[10px]"></i> Reopen
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                <?php else: /* downtime tab */ ?>
                    <?php if (empty($incidents)): ?>
                                <div class="text-center py-12">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No incidents reported yet
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by reporting a new
                                        incident.
                                    </p>
                                </div>
                    <?php else: ?>
                                <?php foreach ($incidents as $incident):
                                    // ── colour maps ────────────────────────────────────
                                    $impactKey = strtolower($incident['impact_level'] ?? 'low');

                                    $impactBadge = [
                                        'critical' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                        'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                        'medium' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
                                        'low' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                    ][$impactKey] ?? 'bg-gray-100 text-gray-700';

                                    $statusBadge = $incident['status'] === 'pending'
                                        ? 'bg-amber-500 text-white dark:bg-amber-600'
                                        : 'bg-green-500 text-white dark:bg-green-600';

                                    $priorityKey = strtolower($incident['priority'] ?? 'medium');
                                    $priorityBadge = [
                                        'urgent' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                        'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                        'medium' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                        'low' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                    ][$priorityKey] ?? 'bg-gray-100 text-gray-700';

                                    // ── attachments ────────────────────────────────────
                                    $attachmentsQuery = $pdo->prepare("SELECT file_path, file_name, uploaded_at FROM incident_attachments WHERE incident_id = :id ORDER BY uploaded_at ASC");
                                    $attachmentsQuery->execute([':id' => $incident['incident_id']]);
                                    $attachments = $attachmentsQuery->fetchAll();
                                    $allAttachments = [];
                                    foreach ($attachments as $att) {
                                        $allAttachments[] = ['file_path' => $att['file_path'], 'file_name' => $att['file_name']];
                                    }
                                    if (!empty($incident['attachment_path'])) {
                                        $ex = false;
                                        foreach ($allAttachments as $ea) {
                                            if ($ea['file_path'] === $incident['attachment_path']) {
                                                $ex = true;
                                                break;
                                            }
                                        }
                                        if (!$ex)
                                            $allAttachments[] = ['file_path' => $incident['attachment_path'], 'file_name' => basename($incident['attachment_path'])];
                                    }
                                    ?>
                                            <!-- ═══════════════ INCIDENT CARD ═══════════════════════ -->
                                            <div class="incident-card bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-5"
                                                data-status="<?= $incident['status'] ?>">

                                                <!-- ── Card Header ─────────────────────────────────── -->
                                                <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                                    <!-- Left: service name + badges -->
                                                    <div class="flex flex-col gap-1.5">
                                                        <div class="flex items-center gap-2 flex-wrap">
                                                            <h3 class="text-base font-bold text-gray-900 dark:text-white leading-tight">
                                                                <?= htmlspecialchars($incident['service_name']) ?>
                                                            </h3>
                                                            <?php if (!empty($incident['incident_ref'])): ?>
                                                                        <button type="button" x-data="{ copied: false }"
                                                                            @click="navigator.clipboard.writeText('<?= htmlspecialchars($incident['incident_ref']) ?>'); copied = true; setTimeout(() => copied = false, 2000)"
                                                                            title="Click to copy"
                                                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-mono font-medium bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 transition-colors">
                                                                            <span x-show="!copied"><i class="fas fa-hashtag text-[9px]"></i>
                                                                                <?= htmlspecialchars($incident['incident_ref']) ?></span>
                                                                            <span x-show="copied" x-cloak class="text-green-600 dark:text-green-400"><i
                                                                                    class="fas fa-check text-[9px]"></i> Copied</span>
                                                                        </button>
                                                            <?php endif; ?>
                                                        </div>
                                                        <!-- Badge row -->
                                                        <?php $srcKey = $incident['incident_source'] ?? 'external'; ?>
                                                        <div class="flex items-center gap-1.5 flex-wrap">
                                                            <!-- Status — the only coloured badge -->
                                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $statusBadge ?>">
                                                                <i class="fas <?= $incident['status'] === 'pending' ? 'fa-hourglass-half' : 'fa-circle-check' ?> text-[9px]"></i>
                                                                <?= ucfirst($incident['status']) ?>
                                                            </span>
                                                            <!-- All other badges — uniform neutral gray -->
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                                <?= ucfirst($incident['impact_level']) ?> Impact
                                                            </span>
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                                <?= ucfirst($incident['priority']) ?> Priority
                                                            </span>
                                                            <?php if (!empty($incident['incident_type_name'])): ?>
                                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                                    <i class="fas fa-tag text-[9px]"></i><?= htmlspecialchars($incident['incident_type_name']) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                                <i class="fas <?= $srcKey === 'internal' ? 'fa-server' : 'fa-building' ?> text-[9px]"></i>
                                                                <?= $srcKey === 'internal' ? 'Internal' : 'External' ?>
                                                            </span>
                                                            <?php if ($incident['attachment_count'] > 0): ?>
                                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                                    <i class="fas fa-paperclip text-[9px]"></i><?= $incident['attachment_count'] ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>

                                                    <!-- Right: action buttons -->
                                                    <div class="flex items-center gap-2 flex-shrink-0">
                                                        <?php if ($incident['status'] === 'pending'): ?>
                                                                    <button type="button" onclick="showEditModal(<?= $incident['incident_id'] ?>)"
                                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                                                        <i class="fas fa-pen-to-square text-gray-400"></i> Edit
                                                                    </button>
                                                                    <button type="button"
                                                                        data-incident-id="<?= $incident['incident_id'] ?>"
                                                                        data-service-name="<?= htmlspecialchars($incident['service_name']) ?>"
                                                                        data-root-cause="<?= htmlspecialchars($incident['root_cause'] ?? '') ?>"
                                                                        onclick="showResolveModal(this.dataset.incidentId, this.dataset.serviceName, this.dataset.rootCause)"
                                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-green-600 hover:bg-green-700 transition-colors shadow-sm">
                                                                        <i class="fas fa-check"></i> Mark Resolved
                                                                    </button>
                                                        <?php else: ?>
                                                                    <div class="flex flex-col items-end gap-1">
                                                                        <span class="text-xs text-green-600 dark:text-green-400 font-medium">
                                                                            <i class="fas fa-check-circle mr-1"></i>Resolved by
                                                                            <?= htmlspecialchars($incident['resolved_by'] ?? 'System') ?>
                                                                        </span>
                                                                        <?php $resolvers = !empty($incident['resolvers']) ? json_decode($incident['resolvers'], true) : []; ?>
                                                                        <?php if (!empty($resolvers)): ?>
                                                                            <span class="text-[11px] text-gray-500 dark:text-gray-400 font-medium mt-0.5" title="Assisted by: <?= htmlspecialchars(implode(', ', $resolvers)) ?>">
                                                                                <i class="fas fa-users mr-1"></i><?= count($resolvers) ?> Helper<?= count($resolvers) > 1 ? 's' : '' ?>
                                                                            </span>
                                                                        <?php endif; ?>
                                                                        <span
                                                                            class="text-[11px] text-gray-400"><?= $incident['resolved_at'] ? date('M j, Y g:i A', strtotime($incident['resolved_at'])) : '' ?></span>
                                                                        <button type="button"
                                                                            onclick="showReopenModal(<?= $incident['incident_id'] ?>, '<?= addslashes(htmlspecialchars($incident['service_name'])) ?>')"
                                                                            class="mt-1 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border border-orange-400 text-orange-600 dark:text-orange-400 bg-white dark:bg-gray-800 hover:bg-orange-50 dark:hover:bg-gray-700 transition-colors">
                                                                            <i class="fas fa-rotate-left text-[10px]"></i> Reopen
                                                                        </button>
                                                                    </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <!-- ── Body ─────────────────────────────────────────── -->
                                                <div class="border-t border-gray-100 dark:border-gray-700 px-5 py-4">
                                                    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

                                                        <!-- LEFT (3/5): meta grid + description + attachments -->
                                                        <div class="lg:col-span-3 space-y-4">

                                                            <!-- Meta info grid -->
                                                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                                                <!-- Incident Type -->
                                                                <div class="bg-gray-50 dark:bg-gray-700/40 rounded-lg px-3 py-2.5">
                                                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Incident Type</p>
                                                                    <div class="flex items-center gap-1.5">
                                                                        <i class="fas fa-tag text-purple-400 text-sm"></i>
                                                                        <span class="text-sm font-medium text-gray-800 dark:text-white truncate">
                                                                            <?= htmlspecialchars($incident['incident_type_name'] ?? 'N/A') ?>
                                                                        </span>
                                                                    </div>
                                                                </div>

                                                                <!-- Component -->
                                                                <div class="bg-gray-50 dark:bg-gray-700/40 rounded-lg px-3 py-2.5">
                                                                    <p
                                                                        class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">
                                                                        Component</p>
                                                                    <p class="text-sm font-medium text-gray-800 dark:text-white truncate">
                                                                        <?= htmlspecialchars($incident['component_names'] ?? 'All / General') ?>
                                                                    </p>
                                                                </div>

                                                                <!-- Affected companies -->
                                                                <?php
                                                                $cos = !empty($incident['affected_companies']) ? explode(', ', $incident['affected_companies']) : [];
                                                                $displayCos = array_slice($cos, 0, 3);
                                                                $moreCos = count($cos) - 3;
                                                                $allCosJson = htmlspecialchars(json_encode(array_map('trim', $cos)), ENT_QUOTES);
                                                                ?>
                                                                <div x-data="{ open: false, allCos: <?= $allCosJson ?> }"
                                                                    class="bg-gray-50 dark:bg-gray-700/40 rounded-lg px-3 py-2.5 col-span-2 sm:col-span-1">
                                                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">
                                                                        Affected Companies (<?= $incident['company_count'] ?>)
                                                                    </p>
                                                                    <button type="button" @click="open = true" class="text-left w-full focus:outline-none">
                                                                        <div class="flex flex-wrap gap-1">
                                                                            <?php foreach ($displayCos as $co): ?>
                                                                                <span class="inline-block px-1.5 py-0.5 rounded text-[11px] bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300 border border-blue-100 dark:border-blue-800">
                                                                                    <?= htmlspecialchars(trim($co)) ?>
                                                                                </span>
                                                                            <?php endforeach; ?>
                                                                            <?php if ($moreCos > 0): ?>
                                                                                <span class="inline-block px-1.5 py-0.5 rounded text-[11px] bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300 font-medium cursor-pointer hover:bg-blue-200 transition-colors">
                                                                                    +<?= $moreCos ?> more
                                                                                </span>
                                                                            <?php endif; ?>
                                                                            <?php if (empty($cos)): ?>
                                                                                <span class="text-xs text-gray-400 italic">None listed</span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </button>
                                                                    <!-- Modal -->
                                                                    <div x-show="open" x-cloak
                                                                        class="fixed inset-0 z-50 flex items-center justify-center p-4"
                                                                        @click.self="open = false">
                                                                        <div class="absolute inset-0 bg-gray-900/50" @click="open = false"></div>
                                                                        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-sm w-full p-5 z-10">
                                                                            <div class="flex items-center justify-between mb-4">
                                                                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                                                                    All Affected Companies (<span x-text="allCos.length"></span>)
                                                                                </h3>
                                                                                <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                                                                    <i class="fas fa-times"></i>
                                                                                </button>
                                                                            </div>
                                                                            <ul class="space-y-1.5 max-h-64 overflow-y-auto">
                                                                                <template x-for="co in allCos" :key="co">
                                                                                    <li class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 py-1 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                                                                        <i class="fas fa-building text-blue-400 text-xs flex-shrink-0"></i>
                                                                                        <span x-text="co"></span>
                                                                                    </li>
                                                                                </template>
                                                                            </ul>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Description -->
                                                            <?php if (!empty($incident['description'])): ?>
                                                                        <div>
                                                                            <p
                                                                                class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">
                                                                                Description</p>
                                                                            <p
                                                                                class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed line-clamp-3">
                                                                                <?= nl2br(htmlspecialchars($incident['description'])) ?>
                                                                            </p>
                                                                        </div>
                                                            <?php endif; ?>

                                                            <!-- Root Cause -->
                                                            <?php if (!empty($incident['root_cause'])): ?>
                                                                        <div
                                                                            class="bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/40 rounded-lg px-3 py-2.5">
                                                                            <p
                                                                                class="text-[10px] font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider mb-1">
                                                                                <i class="fas fa-magnifying-glass mr-1"></i>Root Cause
                                                                            </p>
                                                                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                                                                <?= nl2br(htmlspecialchars($incident['root_cause'])) ?></p>
                                                                        </div>
                                                            <?php endif; ?>

                                                            <!-- Lessons Learned -->
                                                            <?php if ($incident['status'] === 'resolved' && !empty($incident['lessons_learned'])): ?>
                                                                        <div
                                                                            class="bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-800/40 rounded-lg px-3 py-2.5">
                                                                            <p
                                                                                class="text-[10px] font-semibold text-green-700 dark:text-green-400 uppercase tracking-wider mb-1">
                                                                                <i class="fas fa-lightbulb mr-1"></i>Lessons Learned
                                                                            </p>
                                                                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                                                                <?= nl2br(htmlspecialchars($incident['lessons_learned'])) ?></p>
                                                                        </div>
                                                            <?php endif; ?>

                                                            <!-- Attachments -->
                                                            <?php if (!empty($allAttachments)): ?>
                                                                        <div>
                                                                            <p
                                                                                class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">
                                                                                <i
                                                                                    class="fas fa-paperclip mr-1"></i><?= count($allAttachments) > 1 ? 'Attachments' : 'Attachment' ?>
                                                                                (<?= count($allAttachments) ?>)
                                                                            </p>
                                                                            <div class="flex flex-wrap gap-1.5">
                                                                                <?php foreach ($allAttachments as $att): ?>
                                                                                            <button type="button"
                                                                                                onclick="openAttachmentViewer('<?= url($att['file_path']) ?>', '<?= htmlspecialchars($att['file_name'], ENT_QUOTES) ?>')"
                                                                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-medium text-blue-600 dark:text-blue-400 bg-white dark:bg-gray-700 hover:bg-blue-50 dark:hover:bg-gray-600 transition-colors">
                                                                                                <i class="fas fa-file text-gray-400"></i>
                                                                                                <?= htmlspecialchars(strlen($att['file_name']) > 28 ? substr($att['file_name'], 0, 25) . '…' : $att['file_name']) ?>
                                                                                            </button>
                                                                                <?php endforeach; ?>
                                                                            </div>
                                                                        </div>
                                                            <?php endif; ?>
                                                        </div>

                                                        <!-- RIGHT (2/5): Activity timeline + update form -->
                                                        <div
                                                            class="lg:col-span-2 lg:border-l lg:border-gray-100 dark:lg:border-gray-700 lg:pl-5">
                                                            <div class="flex items-center justify-between mb-3">
                                                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">
                                                                    <i class="fas fa-clock-rotate-left mr-1"></i>Activity Log
                                                                    <span
                                                                        class="ml-1 px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 font-bold"><?= $incident['update_count'] ?></span>
                                                                </p>
                                                            </div>

                                                            <!-- Timeline -->
                                                            <div class="space-y-2 max-h-52 overflow-y-auto pr-1 mb-3 scrollbar-thin">
                                                                <!-- Reported by entry -->
                                                                <div class="flex gap-2.5 text-sm">
                                                                    <div class="flex-shrink-0 mt-1">
                                                                        <div class="w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                                                            <i class="fas fa-user text-blue-400 text-[9px]"></i>
                                                                        </div>
                                                                    </div>
                                                                    <div class="flex-1 min-w-0">
                                                                        <div class="flex items-baseline gap-1.5 flex-wrap">
                                                                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300"><?= htmlspecialchars($incident['user_name']) ?></span>
                                                                            <span class="text-[10px] text-gray-400"><?= date('M j, g:i A', strtotime($incident['created_at'])) ?></span>
                                                                        </div>
                                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 italic">Reported this incident</p>
                                                                    </div>
                                                                </div>
                                                                <?php if (empty($incident['updates'])): ?>
                                                                            <p class="text-xs text-gray-400 italic text-center py-2">No further activity logged.</p>
                                                                <?php else: ?>
                                                                            <?php foreach ($incident['updates'] as $update): ?>
                                                                                        <div class="flex gap-2.5 text-sm">
                                                                                            <div class="flex-shrink-0 mt-1">
                                                                                                <div
                                                                                                    class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                                                                                    <i
                                                                                                        class="fas <?= $update['user_name'] === 'System' ? 'fa-robot text-purple-400' : 'fa-user text-blue-400' ?> text-[9px]"></i>
                                                                                                </div>
                                                                                            </div>
                                                                                            <div class="flex-1 min-w-0">
                                                                                                <div class="flex items-baseline gap-1.5 flex-wrap">
                                                                                                    <span
                                                                                                        class="text-xs font-semibold text-gray-700 dark:text-gray-300"><?= htmlspecialchars($update['user_name']) ?></span>
                                                                                                    <span
                                                                                                        class="text-[10px] text-gray-400"><?= date('M j, g:i A', strtotime($update['created_at'])) ?></span>
                                                                                                </div>
                                                                                                <p
                                                                                                    class="text-xs text-gray-600 dark:text-gray-400 mt-0.5 leading-relaxed">
                                                                                                    <?= nl2br(htmlspecialchars($update['update_text'])) ?></p>
                                                                                            </div>
                                                                                        </div>
                                                                            <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </div>

                                                            <!-- Add Update Form -->
                                                            <?php if ($incident['status'] === 'pending'): ?>
                                                                        <form method="POST" class="mt-2 border-t border-gray-100 dark:border-gray-700 pt-3">
                                                                            <input type="hidden" name="action" value="add_update">
                                                                            <input type="hidden" name="incident_id" value="<?= $incident['incident_id'] ?>">
                                                                            <div class="flex flex-col gap-2">
                                                                                <input type="text" name="update_text" placeholder="Describe action taken…"
                                                                                    required
                                                                                    class="w-full text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg py-2 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 placeholder-gray-400">
                                                                                <input type="hidden" name="user_name"
                                                                                    value="<?= htmlspecialchars($_SESSION['full_name']) ?>">
                                                                                <button type="submit"
                                                                                    class="self-end inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-gray-800 dark:bg-gray-600 hover:bg-gray-700 dark:hover:bg-gray-500 transition-colors">
                                                                                    <i class="fas fa-paper-plane text-[10px]"></i> Post Update
                                                                                </button>
                                                                            </div>
                                                                        </form>
                                                            <?php else: ?>
                                                                        <div class="mt-2 border-t border-gray-100 dark:border-gray-700 pt-3">
                                                                            <p class="text-[11px] text-gray-400 flex items-center gap-1.5">
                                                                                <i class="fas fa-lock text-gray-300"></i>
                                                                                Incident resolved. Reopen to add updates.
                                                                            </p>
                                                                        </div>
                                                            <?php endif; ?>
                                                        </div>

                                                    </div><!-- /grid -->
                                                </div><!-- /body -->
                                            </div><!-- /incident-card -->
                                <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; /* end security/fraud vs downtime */ ?>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        ?>
                        <div
                            class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4 w-fit mx-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-6 py-3 shadow-sm">
                            <!-- "Showing X to Y of Z" -->
                            <p class="text-sm text-gray-500 dark:text-gray-400 order-2 sm:order-1">
                                Showing
                                <span
                                    class="font-semibold text-gray-700 dark:text-gray-300"><?= min($offset + 1, $totalIncidents) ?></span>
                                –
                                <span
                                    class="font-semibold text-gray-700 dark:text-gray-300"><?= min($offset + $itemsPerPage, $totalIncidents) ?></span>
                                of
                                <span class="font-semibold text-gray-700 dark:text-gray-300"><?= $totalIncidents ?></span>
                                incidents
                            </p>

                            <!-- Page controls -->
                            <div class="flex items-center gap-1 order-1 sm:order-2">
                                <!-- Previous -->
                                <?php if ($currentPage > 1): ?>
                                            <a href="<?= pageUrl($currentPage - 1, $statusFilter, $searchFilter, $activeTab, $dateFrom, $dateTo) ?>"
                                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                                                <i class="fas fa-chevron-left text-xs"></i>
                                            </a>
                                <?php else: ?>
                                            <span
                                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-300 dark:text-gray-600 cursor-not-allowed">
                                                <i class="fas fa-chevron-left text-xs"></i>
                                            </span>
                                <?php endif; ?>

                                <!-- First page + ellipsis -->
                                <?php if ($startPage > 1): ?>
                                            <a href="<?= pageUrl(1, $statusFilter, $searchFilter, $activeTab, $dateFrom, $dateTo) ?>"
                                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">1</a>
                                            <?php if ($startPage > 2): ?>
                                                        <span class="inline-flex items-center justify-center w-9 h-9 text-sm text-gray-400">…</span>
                                            <?php endif; ?>
                                <?php endif; ?>

                                <!-- Page numbers -->
                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                            <?php if ($i === $currentPage): ?>
                                                        <span
                                                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-600 text-white text-sm font-semibold shadow-sm">
                                                            <?= $i ?>
                                                        </span>
                                            <?php else: ?>
                                                        <a href="<?= pageUrl($i, $statusFilter, $searchFilter, $activeTab, $dateFrom, $dateTo) ?>"
                                                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                                            <?= $i ?>
                                                        </a>
                                            <?php endif; ?>
                                <?php endfor; ?>

                                <!-- Ellipsis + last page -->
                                <?php if ($endPage < $totalPages): ?>
                                            <?php if ($endPage < $totalPages - 1): ?>
                                                        <span class="inline-flex items-center justify-center w-9 h-9 text-sm text-gray-400">…</span>
                                            <?php endif; ?>
                                            <a href="<?= pageUrl($totalPages, $statusFilter, $searchFilter, $activeTab, $dateFrom, $dateTo) ?>"
                                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"><?= $totalPages ?></a>
                                <?php endif; ?>

                                <!-- Next -->
                                <?php if ($currentPage < $totalPages): ?>
                                            <a href="<?= pageUrl($currentPage + 1, $statusFilter, $searchFilter, $activeTab, $dateFrom, $dateTo) ?>"
                                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                                                <i class="fas fa-chevron-right text-xs"></i>
                                            </a>
                                <?php else: ?>
                                            <span
                                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-300 dark:text-gray-600 cursor-not-allowed">
                                                <i class="fas fa-chevron-right text-xs"></i>
                                            </span>
                                <?php endif; ?>
                            </div>
                        </div>
            <?php endif; ?>

    </div>
    </main>

    <!-- Resolve Issue Modal -->
    <div id="resolveModal"
        class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 p-4 transition-opacity duration-300">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] overflow-y-auto"
            id="modalContent">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-1">Resolve Issue</h3>
                <p class="text-sm text-gray-500 mb-4" id="modalServiceName"></p>

                <form id="resolveForm" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="incident_id" id="modal_incident_id" value="">
                    <input type="hidden" name="status" value="resolved">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <div>
                        <label for="resolve_name" class="block text-sm font-medium text-gray-700">Your Name</label>
                        <input type="text" id="resolve_name" name="user_name"
                            value="<?= htmlspecialchars($_SESSION['full_name']) ?>"
                            class="mt-1 block w-full border border-gray-300 bg-gray-100 text-gray-500 rounded-md shadow-sm py-2 px-3 cursor-not-allowed sm:text-sm"
                            readonly autocomplete="off">
                    </div>

                    <!-- Resolvers -->
                    <div x-data="{ resolvers: [''] }" class="space-y-2 mt-4">
                        <label class="block text-sm font-medium text-gray-700">
                            People who helped resolve this <span class="text-red-500">*</span>
                        </label>
                        <template x-for="(resolver, index) in resolvers" :key="index">
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="resolvers[index]" name="resolvers[]" required
                                    class="flex-1 border-gray-300 rounded-md shadow-sm py-2 px-3 text-sm focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter resolver name">
                                <button type="button" @click="resolvers.splice(index, 1)" x-show="resolvers.length > 1"
                                    class="text-red-500 hover:text-red-700 p-2">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </template>
                        <button type="button" @click="resolvers.push('')"
                            class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1 font-medium mt-1">
                            <i class="fas fa-plus-circle"></i> Add another person
                        </button>
                    </div>

                    <!-- Root Cause -->
                    <div>
                        <label for="root_cause_textarea" class="block text-sm font-medium text-gray-700 mb-2">
                            Root Cause <span class="text-red-500">*</span>
                        </label>
                        <textarea name="root_cause" id="root_cause_textarea" rows="3" required
                            class="block w-full border-gray-300 rounded-md shadow-sm py-2 px-3 text-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Describe the root cause of this incident..."></textarea>
                    </div>

                    <!-- Lessons Learned -->
                    <div>
                        <label for="lessons_learned" class="block text-sm font-medium text-gray-700 mb-2">
                            Lessons Learned <span class="text-red-500">*</span>
                        </label>
                        <textarea name="lessons_learned" id="lessons_learned" rows="4" required
                            class="block w-full border-gray-300 rounded-md shadow-sm py-2 px-3 text-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="What did we learn from this incident? How can we prevent it in the future?"></textarea>
                    </div>

                    <!-- Resolution Date -->
                    <div>
                        <label for="resolved_date" class="block text-sm font-medium text-gray-700">
                            Resolution Date & Time <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" name="resolved_date" id="resolved_date" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-500 mt-1">When was this incident actually resolved?</p>
                    </div>

                    <div class="flex justify-end space-x-3 pt-2">
                        <button type="button" onclick="hideResolveModal()"
                            class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancel
                        </button>
                        <button type="submit"
                            class="inline-flex justify-center items-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-check mr-2"></i> Mark as Resolved
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reopen Issue Modal -->
    <div id="reopenModal"
        class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 p-4 transition-opacity duration-300">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full transform transition-all duration-300 scale-95 opacity-0"
            id="reopenModalContent">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div
                        class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-orange-100 dark:bg-orange-900/30">
                        <i class="fas fa-exclamation-triangle text-orange-600 dark:text-orange-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Reopen Incident</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400" id="reopenModalServiceName"></p>
                    </div>
                </div>

                <div
                    class="mb-4 p-3 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-md">
                    <p class="text-sm text-orange-800 dark:text-orange-300">
                        <i class="fas fa-info-circle mr-1"></i>
                        Are you sure you want to reopen this resolved incident? This will allow new updates to be
                        added.
                    </p>
                </div>

                <form id="reopenForm" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="incident_id" id="reopen_incident_id" value="">
                    <input type="hidden" name="status" value="pending">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <div class="flex justify-end space-x-3 pt-2">
                        <button type="button" onclick="hideReopenModal()"
                            class="inline-flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancel
                        </button>
                        <button type="submit"
                            class="inline-flex justify-center items-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                            <i class="fas fa-redo mr-2"></i> Reopen Incident
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Incident Modal -->
    <div id="editModal"
        class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 p-4 transition-opacity duration-300">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-3xl w-full transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] overflow-y-auto"
            id="editModalContent">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">Edit Incident Details</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4" id="editModalIncidentInfo"></p>

                <form id="editForm" method="POST" enctype="multipart/form-data" class="space-y-4" x-data="{
                        existingAttachments: [],
                        attachmentsToDelete: [],
                        newFilePreviews: [],
                        markForDeletion(attachmentId, filePath) {
                            this.attachmentsToDelete.push({ id: attachmentId, path: filePath });
                            // Find and mark the attachment visually
                            const attachment = this.existingAttachments.find(a => a.attachment_id === attachmentId);
                            if (attachment) attachment.markedForDeletion = true;
                        },
                        unmarkForDeletion(attachmentId) {
                            this.attachmentsToDelete = this.attachmentsToDelete.filter(a => a.id !== attachmentId);
                            const attachment = this.existingAttachments.find(a => a.attachment_id === attachmentId);
                            if (attachment) attachment.markedForDeletion = false;
                        },
                        handleNewFiles(event) {
                            const files = Array.from(event.target.files);
                            const imageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                            
                            files.forEach(file => {
                                const preview = {
                                    name: file.name,
                                    customName: file.name,
                                    type: imageTypes.includes(file.type) ? 'image' : 'document',
                                    url: null
                                };
                                
                                if (preview.type === 'image') {
                                    const reader = new FileReader();
                                    reader.onload = (e) => {
                                        preview.url = e.target.result;
                                        this.newFilePreviews.push(preview);
                                    };
                                    reader.readAsDataURL(file);
                                } else {
                                    this.newFilePreviews.push(preview);
                                }
                            });
                        },
                        removeNewFile(index) {
                            this.newFilePreviews.splice(index, 1);
                            const fileInput = this.$refs.newFileInput;
                            const dt = new DataTransfer();
                            const files = Array.from(fileInput.files);
                            files.forEach((file, i) => {
                                if (i !== index) dt.items.add(file);
                            });
                            fileInput.files = dt.files;
                        }
                    }">
                    <input type="hidden" name="action" value="edit_incident">
                    <input type="hidden" name="incident_id" id="edit_incident_id" value="">
                    <!-- Hidden inputs for attachments to delete -->
                    <template x-for="attachment in attachmentsToDelete" :key="attachment.id">
                        <input type="hidden" name="delete_attachments[]" :value="attachment.id">
                    </template>
                    <template x-for="attachment in attachmentsToDelete" :key="attachment.path">
                        <input type="hidden" name="delete_attachment_paths[]" :value="attachment.path">
                    </template>


                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Service -->
                        <div>
                            <label for="edit_service"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Service <span class="text-red-500">*</span>
                            </label>
                            <select id="edit_service" name="service_id" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm py-2 px-3 text-sm focus:ring-blue-500 focus:border-blue-500">
                                <?php foreach ($services as $service): ?>
                                            <option value="<?= $service['service_id'] ?>">
                                                <?= htmlspecialchars($service['service_name']) ?>
                                            </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Component (multi-select) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Component
                            </label>
                            <div class="relative mt-1" id="editComponentWrapper">
                                <button type="button" id="editComponentBtn" onclick="toggleEditComponentDropdown()"
                                    class="w-full flex items-center justify-between border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 text-sm bg-white dark:bg-gray-700 dark:text-white text-left focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <span id="edit-component-text" class="block truncate text-gray-500 dark:text-gray-400">All / General</span>
                                    <i class="fas fa-chevron-down text-gray-400 ml-2 flex-shrink-0"></i>
                                </button>
                                <div id="editComponentMenu" class="hidden absolute z-30 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-44 overflow-y-auto">
                                    <div class="p-2">
                                        <?php foreach ($components as $component): ?>
                                            <label class="flex items-center px-2 py-1.5 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                                <input type="checkbox" name="component_ids[]"
                                                    value="<?= $component['component_id'] ?>"
                                                    class="edit-component-cb w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300"><?= htmlspecialchars($component['name']) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Incident Type -->
                        <div>
                            <label for="edit_incident_type"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Incident Type
                            </label>
                            <select id="edit_incident_type" name="incident_type_id"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm py-2 px-3 text-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All</option>
                                <?php foreach ($incidentTypes as $type): ?>
                                            <option value="<?= $type['type_id'] ?>">
                                                <?= htmlspecialchars($type['name']) ?>
                                            </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Impact Level -->
                        <div>
                            <label for="edit_impact" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Impact Level <span class="text-red-500">*</span>
                            </label>
                            <select id="edit_impact" name="impact_level" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm py-2 px-3 text-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                                <option value="Critical">Critical</option>
                            </select>
                        </div>

                        <!-- Priority -->
                        <div>
                            <label for="edit_priority"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Priority <span class="text-red-500">*</span>
                            </label>
                            <select id="edit_priority" name="priority" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm py-2 px-3 text-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>

                        <!-- Actual Start Time -->
                        <div>
                            <label for="edit_start_time"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Actual Start Time <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" id="edit_start_time" name="actual_start_time" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm py-2 px-3 text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <!-- Incident Source -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Incident Source <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                            Changing the source will affect SLA calculations for this incident.
                        </p>
                        <div class="grid grid-cols-2 gap-3">
                            <label id="edit_source_external_label" onclick="setEditSource('external')"
                                   class="relative flex cursor-pointer rounded-lg border p-3 shadow-sm transition-colors">
                                <input type="radio" name="incident_source" value="external" id="edit_source_external" class="sr-only" checked>
                                <span class="flex flex-1 flex-col">
                                    <span class="flex items-center gap-2">
                                        <i class="fas fa-building text-blue-400 text-sm"></i>
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">External</span>
                                    </span>
                                    <span class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Company's fault &rarr; affects Company SLA</span>
                                </span>
                            </label>
                            <label id="edit_source_internal_label" onclick="setEditSource('internal')"
                                   class="relative flex cursor-pointer rounded-lg border p-3 shadow-sm transition-colors">
                                <input type="radio" name="incident_source" value="internal" id="edit_source_internal" class="sr-only">
                                <span class="flex flex-1 flex-col">
                                    <span class="flex items-center gap-2">
                                        <i class="fas fa-server text-orange-400 text-sm"></i>
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Internal</span>
                                    </span>
                                    <span class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">eTranzact's fault &rarr; affects eTranzact SLA</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="edit_description"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Description
                        </label>
                        <textarea id="edit_description" name="description" rows="3"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm py-2 px-3 text-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Describe the incident..."></textarea>
                    </div>

                    <!-- Root Cause -->
                    <div>
                        <label for="edit_root_cause"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Root Cause
                        </label>
                        <textarea id="edit_root_cause" name="root_cause" rows="3"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm py-2 px-3 text-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="What caused this incident?"></textarea>
                    </div>

                    <!-- Attachments Management -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Attachments
                        </label>

                        <!-- Existing Attachments -->
                        <div x-show="existingAttachments.length > 0" class="mb-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Current Attachments:</p>
                            <div class="space-y-2">
                                <template x-for="(attachment, index) in existingAttachments"
                                    :key="attachment.attachment_id">
                                    <div class="flex items-center justify-between p-2 border rounded-md"
                                        :class="attachment.markedForDeletion ? 'border-red-300 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700'">
                                        <div class="flex items-center space-x-2 flex-1 min-w-0">
                                            <i class="fas fa-file text-gray-400"
                                                :class="attachment.markedForDeletion ? 'text-red-400' : 'text-gray-400'"></i>
                                            <span class="text-sm truncate"
                                                :class="attachment.markedForDeletion ? 'line-through text-red-500' : 'text-gray-700 dark:text-gray-300'"
                                                x-text="attachment.file_name"></span>
                                        </div>
                                        <button type="button"
                                            @click="attachment.markedForDeletion ? unmarkForDeletion(attachment.attachment_id) : markForDeletion(attachment.attachment_id, attachment.file_path)"
                                            class="text-gray-400 hover:text-red-500 focus:outline-none transition-colors ml-2"
                                            :class="attachment.markedForDeletion ? 'text-green-500 hover:text-green-600' : ''">
                                            <i class="fas text-lg"
                                                :class="attachment.markedForDeletion ? 'fa-undo' : 'fa-times'"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- New File Previews -->
                        <template x-if="newFilePreviews.length > 0">
                            <div class="mb-3 space-y-2">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">New Attachments:</p>
                                <template x-for="(preview, index) in newFilePreviews" :key="index">
                                    <div
                                        class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 bg-white dark:bg-gray-700">
                                        <div class="flex items-center gap-3">
                                            <!-- Preview Icon/Image -->
                                            <div class="flex-shrink-0">
                                                <template x-if="preview.type === 'image'">
                                                    <img :src="preview.url" class="w-12 h-12 object-cover rounded"
                                                        alt="Preview">
                                                </template>
                                                <template x-if="preview.type === 'document'">
                                                    <div
                                                        class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded flex items-center justify-center">
                                                        <i
                                                            class="fas fa-file-alt text-blue-600 dark:text-blue-400 text-xl"></i>
                                                    </div>
                                                </template>
                                            </div>

                                            <!-- File Info -->
                                            <div class="flex-1 min-w-0">
                                                <label
                                                    class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                    Display Name
                                                </label>
                                                <input type="text" x-model="preview.customName"
                                                    :name="'new_file_custom_names[' + index + ']'"
                                                    class="block w-full text-sm border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-1.5 px-2 bg-white dark:bg-gray-800 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="Enter display name...">
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    Original: <span x-text="preview.name"></span>
                                                </p>
                                            </div>

                                            <!-- Remove Button -->
                                            <button @click="removeNewFile(index)" type="button"
                                                class="self-center text-gray-400 hover:text-red-500 focus:outline-none transition-colors">
                                                <i class="fas fa-times text-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- File Upload Input -->
                        <div
                            class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center hover:border-blue-400 dark:hover:border-blue-500 transition-colors">
                            <div class="flex text-sm text-gray-600 dark:text-gray-400 justify-center">
                                <label for="new_attachments"
                                    class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500 px-2 py-0.5 border border-blue-600/20">
                                    <span>Upload files</span>
                                    <input id="new_attachments" name="new_attachments[]" type="file" class="sr-only"
                                        x-ref="newFileInput" @change="handleNewFiles" multiple>
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                PNG, JPG, GIF, PDF, DOC, TXT up to 10MB each
                            </p>
                        </div>
                    </div>

                    <!-- Affected Companies -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Affected Companies <span class="text-red-500">*</span>
                        </label>
                        <div
                            class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-48 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-md p-3">
                            <?php foreach ($companies as $company): ?>
                                        <label class="flex items-center space-x-2 text-sm">
                                            <input type="checkbox" name="companies[]" value="<?= $company['company_id'] ?>"
                                                class="edit-company-checkbox rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                            <span class="text-gray-700 dark:text-gray-300">
                                                <?= htmlspecialchars($company['company_name']) ?>
                                            </span>
                                        </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" onclick="hideEditModal()"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-save mr-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Status toggle functionality
        document.addEventListener('DOMContentLoaded', function () {
            const statusToggles = document.querySelectorAll('.status-toggle');

            statusToggles.forEach(button => {
                button.addEventListener('click', function () {
                    const status = this.getAttribute('data-status');

                    // Update active state
                    statusToggles.forEach(btn => {
                        btn.classList.remove(
                            'bg-blue-50', 'text-blue-700', 'border-blue-200',
                            'bg-yellow-50', 'text-yellow-700', 'border-yellow-200',
                            'bg-green-50', 'text-green-700', 'border-green-200'
                        );
                        btn.classList.add('bg-white', 'text-gray-700', 'border-gray-200');

                        // Reset icon colors
                        const icon = btn.querySelector('i');
                        if (icon) {
                            icon.classList.remove('text-blue-500', 'text-yellow-500', 'text-green-500');
                            if (btn.getAttribute('data-status') === 'pending') {
                                icon.classList.add('text-yellow-500');
                            } else if (btn.getAttribute('data-status') === 'resolved') {
                                icon.classList.add('text-green-500');
                            } else {
                                icon.classList.add('text-gray-500');
                            }
                        }
                    });

                    // Set active button styles
                    if (status === 'pending') {
                        this.classList.add('bg-yellow-50', 'text-yellow-700', 'border-yellow-200');
                        this.querySelector('i').classList.add('text-yellow-600');
                    } else if (status === 'resolved') {
                        this.classList.add('bg-green-50', 'text-green-700', 'border-green-200');
                        this.querySelector('i').classList.add('text-green-600');
                    } else {
                        this.classList.add('bg-blue-50', 'text-blue-700', 'border-blue-200');
                        this.querySelector('i').classList.add('text-blue-600');
                    }

                    // Navigate to the filtered URL (server-side, preserves search, resets to page 1)
                    const url = new URL(window.location);
                    if (status === '' || status === 'all') {
                        url.searchParams.delete('status');
                    } else {
                        url.searchParams.set('status', status);
                    }
                    url.searchParams.delete('page');
                    window.location.href = url.toString();
                });
            });

        });

        // Resolve Modal Functions
        function showResolveModal(incidentId, actionOrServiceName, rootCause = '') {
            const modal = document.getElementById('resolveModal');
            const modalContent = document.getElementById('modalContent');

            const isSecOrFraud = actionOrServiceName === 'resolve_security' || actionOrServiceName === 'resolve_fraud';

            // Update the hidden action field so the correct PHP handler runs
            document.querySelector('#resolveForm input[name="action"]').value =
                isSecOrFraud ? actionOrServiceName : 'update_status';

            // Set the incident ID and service name
            document.getElementById('modal_incident_id').value = incidentId;
            document.getElementById('modalServiceName').textContent = isSecOrFraud
                ? (actionOrServiceName === 'resolve_security' ? 'Security Incident' : 'Fraud Incident')
                : `Service: ${actionOrServiceName}`;

            // Set current date/time as default resolution date
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const currentDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
            document.getElementById('resolved_date').value = currentDateTime;

            // Get the form element
            const form = document.getElementById('resolveForm');

            // Set Alpine.js data using x-data attributes
            form.setAttribute('x-data', JSON.stringify({
                rootCauseMode: 'text',
                lessonsMode: 'text',
                rootCauseFileName: '',
                lessonsFileName: ''
            }));

            // Show modal with animation
            modal.classList.remove('hidden');
            setTimeout(() => {
                modalContent.classList.remove('opacity-0', 'scale-95');
                modalContent.classList.add('opacity-100', 'scale-100');

                // Pre-populate the root cause textarea if it exists
                const rootCauseTextarea = document.getElementById('root_cause_textarea');
                if (rootCauseTextarea && rootCause) {
                    rootCauseTextarea.value = rootCause;
                }

                document.getElementById('resolve_name').focus();
            }, 100);
        }

        function hideResolveModal() {
            const modal = document.getElementById('resolveModal');
            const modalContent = document.getElementById('modalContent');

            // Hide with animation
            modalContent.classList.remove('opacity-100', 'scale-100');
            modalContent.classList.add('opacity-0', 'scale-95');

            // Hide modal after animation
            setTimeout(() => {
                modal.classList.add('hidden');
                // Reset form
                document.getElementById('resolveForm').reset();
            }, 200);
        }

        // Close modal when clicking outside
        document.getElementById('resolveModal').addEventListener('click', function (e) {
            if (e.target === this) {
                hideResolveModal();
            }
        });

        // Set incident source selection in edit modal
        function setEditSource(value) {
            const externalLabel = document.getElementById('edit_source_external_label');
            const internalLabel = document.getElementById('edit_source_internal_label');
            document.getElementById('edit_source_external').checked = value === 'external';
            document.getElementById('edit_source_internal').checked = value === 'internal';
            if (value === 'external') {
                externalLabel.className = 'relative flex cursor-pointer rounded-lg border p-3 shadow-sm transition-colors border-blue-500 ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20';
                internalLabel.className = 'relative flex cursor-pointer rounded-lg border p-3 shadow-sm transition-colors border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 hover:border-orange-400';
            } else {
                externalLabel.className = 'relative flex cursor-pointer rounded-lg border p-3 shadow-sm transition-colors border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 hover:border-blue-400';
                internalLabel.className = 'relative flex cursor-pointer rounded-lg border p-3 shadow-sm transition-colors border-orange-500 ring-2 ring-orange-500 bg-orange-50 dark:bg-orange-900/20';
            }
        }

        // Edit Modal Functions
        function showEditModal(incidentId) {
            // Fetch incident data with attachments
            fetch(`get_incident.php?id=${incidentId}&include_attachments=1`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    // Populate form fields
                    document.getElementById('edit_incident_id').value = data.incident_id;
                    document.getElementById('editModalIncidentInfo').textContent = `Incident #${data.incident_id} - ${data.service_name}`;
                    document.getElementById('edit_service').value = data.service_id || '';
                    // Populate component checkboxes
                    document.querySelectorAll('.edit-component-cb').forEach(cb => cb.checked = false);
                    const compIds = data.component_ids || (data.component_id ? [data.component_id] : []);
                    compIds.forEach(cid => {
                        const cb = document.querySelector(`.edit-component-cb[value="${cid}"]`);
                        if (cb) cb.checked = true;
                    });
                    updateEditComponentText();
                    document.getElementById('edit_incident_type').value = data.incident_type_id || '';
                    document.getElementById('edit_impact').value = data.impact_level;
                    document.getElementById('edit_priority').value = data.priority;
                    setEditSource(data.incident_source || 'external');
                    document.getElementById('edit_description').value = data.description || '';
                    document.getElementById('edit_root_cause').value = data.root_cause || '';

                    // Format datetime for datetime-local input
                    if (data.actual_start_time) {
                        const date = new Date(data.actual_start_time);
                        const year = date.getFullYear();
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const day = String(date.getDate()).padStart(2, '0');
                        const hours = String(date.getHours()).padStart(2, '0');
                        const minutes = String(date.getMinutes()).padStart(2, '0');
                        document.getElementById('edit_start_time').value = `${year}-${month}-${day}T${hours}:${minutes}`;
                    }

                    // Check affected companies
                    const allEditCheckboxes = document.querySelectorAll('.edit-company-checkbox');
                    const allEditCheckbox = Array.from(allEditCheckboxes).find(cb => cb.value === '3'); // Assuming "All" has company_id = 3
                    const otherEditCheckboxes = Array.from(allEditCheckboxes).filter(cb => cb.value !== '3');

                    // First, uncheck all
                    allEditCheckboxes.forEach(checkbox => {
                        checkbox.checked = false;
                    });

                    // Then check the appropriate ones
                    data.affected_companies.forEach(companyId => {
                        const checkbox = document.querySelector(`.edit-company-checkbox[value="${companyId}"]`);
                        if (checkbox) {
                            checkbox.checked = true;
                        }
                    });

                    // If "All" is checked, uncheck others
                    if (allEditCheckbox && allEditCheckbox.checked) {
                        otherEditCheckboxes.forEach(cb => cb.checked = false);
                    }

                    // Add event listeners for exclusive selection
                    if (allEditCheckbox) {
                        allEditCheckbox.addEventListener('change', function () {
                            if (this.checked) {
                                otherEditCheckboxes.forEach(cb => cb.checked = false);
                            }
                        });

                        otherEditCheckboxes.forEach(checkbox => {
                            checkbox.addEventListener('change', function () {
                                if (this.checked) {
                                    allEditCheckbox.checked = false;
                                }
                            });
                        });
                    }

                    // Load existing attachments into Alpine.js
                    console.log('Attachment data received:', data.attachments);
                    if (data.attachments && data.attachments.length > 0) {
                        // Wait for modal to be visible before accessing Alpine data
                        setTimeout(() => {
                            const form = document.getElementById('editForm');
                            if (form && form._x_dataStack) {
                                const alpineData = form._x_dataStack[0];
                                console.log('Alpine data found:', alpineData);
                                alpineData.existingAttachments = data.attachments.map(att => ({
                                    ...att,
                                    markedForDeletion: false
                                }));
                                alpineData.attachmentsToDelete = [];
                                alpineData.newFilePreviews = [];
                                console.log('Attachments loaded:', alpineData.existingAttachments);
                            } else {
                                console.error('Alpine.js data not found on form element');
                            }
                        }, 100);
                    } else {
                        console.log('No attachments found for this incident');
                    }

                    // Show modal with animation
                    const modal = document.getElementById('editModal');
                    const modalContent = document.getElementById('editModalContent');
                    modal.classList.remove('hidden');
                    setTimeout(() => {
                        modalContent.classList.remove('opacity-0', 'scale-95');
                        modalContent.classList.add('opacity-100', 'scale-100');
                    }, 10);
                })
                .catch(error => {
                    console.error('Error fetching incident data:', error);
                    alert('Failed to load incident data. Please try again.');
                });
        }

        function hideEditModal() {
            const modal = document.getElementById('editModal');
            const modalContent = document.getElementById('editModalContent');

            // Hide with animation
            modalContent.classList.remove('opacity-100', 'scale-100');
            modalContent.classList.add('opacity-0', 'scale-95');

            // Hide modal after animation
            setTimeout(() => {
                modal.classList.add('hidden');
                document.getElementById('editForm').reset();
            }, 200);
        }

        // Close edit modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function (e) {
            if (e.target === this) {
                hideEditModal();
            }
        });

        // Handle edit form submission
        document.getElementById('editForm').addEventListener('submit', function (e) {
            e.preventDefault();

            // Validate at least one company is selected
            const selectedCompanies = document.querySelectorAll('.edit-company-checkbox:checked');
            if (selectedCompanies.length === 0) {
                alert('Please select at least one affected company.');
                return;
            }

            // Submit form
            this.submit();
        });

        // Handle form submission with validation
        document.getElementById('resolveForm').addEventListener('submit', function (e) {
            e.preventDefault(); // Always prevent default first

            const form = this;
            const errors = [];

            // Validate resolution date
            const resolvedDate = document.getElementById('resolved_date').value;
            if (!resolvedDate) {
                errors.push('Resolution date is required.');
            }

            // Validate root cause text
            const rootCauseText = form.querySelector('textarea[name="root_cause"]')?.value.trim() || '';
            if (!rootCauseText) {
                errors.push('Root cause is required.');
            }

            // Validate lessons learned text
            const lessonsText = form.querySelector('textarea[name="lessons_learned"]')?.value.trim() || '';
            if (!lessonsText) {
                errors.push('Lessons learned is required.');
            }

            // If there are errors, show them and don't submit
            if (errors.length > 0) {
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
                return false;
            }

            // If validation passes, submit the form
            form.submit();
        });


        // Close modal with ESC key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                if (!document.getElementById('resolveModal').classList.contains('hidden')) {
                    hideResolveModal();
                }
                if (!document.getElementById('reopenModal').classList.contains('hidden')) {
                    hideReopenModal();
                }
            }
        });

        // Reopen Modal Functions
        function showReopenModal(incidentId, actionOrServiceName) {
            const modal = document.getElementById('reopenModal');
            const modalContent = document.getElementById('reopenModalContent');

            const isSecOrFraud = actionOrServiceName === 'reopen_security' || actionOrServiceName === 'reopen_fraud';

            // Update the hidden action field so the correct PHP handler runs
            document.querySelector('#reopenForm input[name="action"]').value =
                isSecOrFraud ? actionOrServiceName : 'update_status';

            // Set the incident ID and label
            document.getElementById('reopen_incident_id').value = incidentId;
            document.getElementById('reopenModalServiceName').textContent = isSecOrFraud
                ? (actionOrServiceName === 'reopen_security' ? 'Security Incident' : 'Fraud Incident')
                : `Service: ${actionOrServiceName}`;

            // Show modal with animation
            modal.classList.remove('hidden');
            setTimeout(() => {
                modalContent.classList.remove('opacity-0', 'scale-95');
                modalContent.classList.add('opacity-100', 'scale-100');
            }, 10);
        }

        function hideReopenModal() {
            const modal = document.getElementById('reopenModal');
            const modalContent = document.getElementById('reopenModalContent');

            // Hide with animation
            modalContent.classList.remove('opacity-100', 'scale-100');
            modalContent.classList.add('opacity-0', 'scale-95');

            // Hide modal after animation
            setTimeout(() => {
                modal.classList.add('hidden');
                // Reset form
                document.getElementById('reopenForm').reset();
            }, 200);
        }

        // Close reopen modal when clicking outside
        document.getElementById('reopenModal').addEventListener('click', function (e) {
            if (e.target === this) {
                hideReopenModal();
            }
        });
    </script>

    <script>
        // Edit modal component dropdown
        window.toggleEditComponentDropdown = function() {
            document.getElementById('editComponentMenu').classList.toggle('hidden');
        };
        document.addEventListener('click', function(e) {
            const wrapper = document.getElementById('editComponentWrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                document.getElementById('editComponentMenu').classList.add('hidden');
            }
        });
        window.updateEditComponentText = function() {
            const checked = document.querySelectorAll('.edit-component-cb:checked');
            const textEl = document.getElementById('edit-component-text');
            if (checked.length === 0) {
                textEl.textContent = 'All / General';
                textEl.classList.add('text-gray-500', 'dark:text-gray-400');
            } else {
                const names = Array.from(checked).map(cb => cb.closest('label').querySelector('span').textContent.trim());
                textEl.textContent = names.join(', ');
                textEl.classList.remove('text-gray-500', 'dark:text-gray-400');
            }
        };
        document.querySelectorAll('.edit-component-cb').forEach(cb => {
            cb.addEventListener('change', updateEditComponentText);
        });
    </script>

    <script>
        function toggleRootCause(issueId) {
            const rootCause = document.getElementById(`root-cause-${issueId}`);
            const readMoreBtn = rootCause.nextElementSibling;
            const readMoreText = readMoreBtn.querySelector('.read-more');
            const readLessText = readMoreBtn.querySelector('.read-less');

            if (rootCause.classList.contains('line-clamp-3')) {
                rootCause.classList.remove('line-clamp-3');
                readMoreText.classList.add('hidden');
                readLessText.classList.remove('hidden');
            } else {
                rootCause.classList.add('line-clamp-3');
                readMoreText.classList.remove('hidden');
                readLessText.classList.add('hidden');

                // Scroll the element into view if needed
                rootCause.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        // Auto-resize textareas
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('textarea').forEach(textarea => {
                textarea.addEventListener('input', function () {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
            });
        });
    </script>

    <script>
        // Search — navigate via URL so server filters + pagination stays correct
        (function () {
            const searchInput = document.getElementById('incident-search');
            if (!searchInput) return;

            function submitSearch(value) {
                const url = new URL(window.location);
                if (value.trim()) {
                    url.searchParams.set('search', value.trim());
                } else {
                    url.searchParams.delete('search');
                }
                url.searchParams.delete('page');
                window.location.href = url.toString();
            }

            let searchTimer;
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => submitSearch(this.value), 500);
            });
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    clearTimeout(searchTimer);
                    submitSearch(this.value);
                } else if (e.key === 'Escape') {
                    clearTimeout(searchTimer);
                    this.value = '';
                    submitSearch('');
                }
            });
        })();

        // Date filter — navigate when either date changes
        (function () {
            const fromInput = document.getElementById('date_from');
            const toInput   = document.getElementById('date_to');
            function applyDates() {
                const url = new URL(window.location);
                const from = fromInput ? fromInput.value : '';
                const to   = toInput   ? toInput.value   : '';
                if (from) url.searchParams.set('date_from', from); else url.searchParams.delete('date_from');
                if (to)   url.searchParams.set('date_to',   to);   else url.searchParams.delete('date_to');
                url.searchParams.delete('page');
                window.location.href = url.toString();
            }
            if (fromInput) fromInput.addEventListener('change', applyDates);
            if (toInput)   toInput.addEventListener('change',   applyDates);
        })();

        // Refresh incidents function
        function refreshIncidents() {
            const btn = event.target.closest('button');

            // Show loading state
            btn.disabled = true;
            btn.innerHTML = '<div class="btn-spinner"></div> Refreshing...';

            // Reload the page
            setTimeout(() => {
                location.reload();
            }, 300);
        }

        // Attachment Viewer Functions
        function openAttachmentViewer(fileUrl, fileName) {
            const modal = document.getElementById('attachmentViewerModal');
            const modalTitle = document.getElementById('attachmentViewerTitle');
            const iframe = document.getElementById('attachmentViewerIframe');
            const downloadLink = document.getElementById('attachmentDownloadLink');

            // Set the modal title and iframe source
            modalTitle.textContent = fileName;
            iframe.src = fileUrl;
            downloadLink.href = fileUrl;
            downloadLink.download = fileName;

            // Show the modal
            modal.classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('attachmentViewerContent').classList.remove('scale-95', 'opacity-0');
                document.getElementById('attachmentViewerContent').classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeAttachmentViewer() {
            const modal = document.getElementById('attachmentViewerModal');
            const modalContent = document.getElementById('attachmentViewerContent');
            const iframe = document.getElementById('attachmentViewerIframe');

            // Hide modal with animation
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                modal.classList.add('hidden');
                iframe.src = 'about:blank'; // Clear iframe
            }, 300);
        }
    </script>
    <!-- Attachment Viewer Modal -->
    <div id="attachmentViewerModal"
        class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 p-4 transition-opacity duration-300">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-6xl h-[90vh] transform transition-all duration-300 scale-95 opacity-0 flex flex-col"
            id="attachmentViewerContent">
            <!-- Modal Header -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-file text-blue-600 dark:text-blue-400 text-xl"></i>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="attachmentViewerTitle"></h3>
                </div>
                <div class="flex items-center space-x-2">
                    <a id="attachmentDownloadLink" href="#" download
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-download mr-2"></i>
                        Download
                    </a>
                    <button type="button" onclick="closeAttachmentViewer()"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-times mr-2"></i>
                        Close
                    </button>
                </div>
            </div>

            <!-- Modal Body with iframe -->
            <div class="flex-1 p-4 overflow-hidden">
                <iframe id="attachmentViewerIframe" class="w-full h-full border-0 rounded-lg"
                    src="about:blank"></iframe>
            </div>
        </div>
    </div>

    </div> <!-- End Content Wrapper -->
</body>

</html>