<?php
/**
 * Authentication Functions
 * Core authentication and authorization functions for the application
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include activity logger
require_once __DIR__ . '/activity_logger.php';

/**
 * Authenticate user against the external REST API
 * @param string $usernameOrEmail Username or email
 * @param string $password Plain text password
 * @return array|false User data array on success, false on failure
 */
function login($usernameOrEmail, $password)
{
    try {
        // Call the external authentication API
        $apiResponse = callExternalAuthApi($usernameOrEmail, $password);

        $authOk = !empty($apiResponse['code']) && $apiResponse['code'] === '00'
               && !empty($apiResponse[EXTERNAL_AUTH_RES_USER]['isLoggedIn']);
        if (!$apiResponse || !$authOk) {
            $reason = $apiResponse['message'] ?? 'Invalid credentials';
            logActivity(null, 'login_failed', "Failed login attempt for: {$usernameOrEmail} — {$reason}");
            return false;
        }

        // Find existing user or auto-provision from API response
        $apiUserData = $apiResponse[EXTERNAL_AUTH_RES_USER] ?? [];
        // Use the canonical username from the API response
        $apiUsername = $apiUserData[EXTERNAL_AUTH_RES_USERNAME] ?? $usernameOrEmail;
        $user = findOrProvisionUser($apiUsername, $apiUserData);

        if (!$user) {
            logActivity(null, 'login_failed', "Could not provision user: {$usernameOrEmail}");
            return false;
        }

        // Set session variables
        $_SESSION['user_id']    = $user['user_id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['full_name']  = $user['full_name'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['login_time'] = time();

        // Regenerate session ID for security
        session_regenerate_id(true);

        // Update last login time and log success
        updateLastLogin($user['user_id']);
        logLogin($user['user_id'], true);

        return $user;
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

/**
 * POST credentials to the external auth API and return the decoded response
 * @param string $username
 * @param string $password
 * @return array|null Decoded JSON response, or null on network/HTTP error
 */
function callExternalAuthApi($username, $password)
{
    // Build the credentials JSON, then RSA-encrypt it with the server's public key
    $credentials = json_encode([
        EXTERNAL_AUTH_REQ_USERNAME => $username,
        EXTERNAL_AUTH_REQ_PASSWORD => $password,
    ]);

    $encrypted = '';
    if (!openssl_public_encrypt($credentials, $encrypted, EXTERNAL_AUTH_PUBLIC_KEY, OPENSSL_PKCS1_PADDING)) {
        error_log("External auth API: RSA encryption failed — " . openssl_error_string());
        return null;
    }

    $body = json_encode(['payload' => base64_encode($encrypted)]);

    $ch = curl_init(EXTERNAL_AUTH_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json, text/plain, */*'],
        CURLOPT_TIMEOUT        => EXTERNAL_AUTH_API_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => (APP_ENV !== 'development'),
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("External auth API curl error: {$curlError}");
        return null;
    }

    if ($httpCode !== 200) {
        error_log("External auth API returned HTTP {$httpCode}");
        return null;
    }

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("External auth API returned invalid JSON");
        return null;
    }

    return $decoded;
}

/**
 * Find an existing user by username/email, or create one from API data
 * @param string $usernameOrEmail The value the user typed at login
 * @param array  $apiUserData     User fields returned by the external API
 * @return array|false User row from DB, or false on failure
 */
function findOrProvisionUser($apiUsername, $apiUserData)
{
    global $pdo;

    $apiEmail    = $apiUserData[EXTERNAL_AUTH_RES_EMAIL] ?? '';
    $apiFullName = trim(
        ($apiUserData[EXTERNAL_AUTH_RES_FIRSTNAME] ?? '') . ' ' .
        ($apiUserData[EXTERNAL_AUTH_RES_LASTNAME]  ?? '')
    ) ?: $apiUsername;

    try {
        // Look up by username or email
        $stmt = $pdo->prepare("
            SELECT user_id, username, email, full_name, role, is_active, changed_password
            FROM users
            WHERE (username = ? OR email = ?) AND is_active = TRUE
            LIMIT 1
        ");
        $stmt->execute([$apiUsername, $apiEmail ?: $apiUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Sync name and email from API in case they changed
            if ($apiFullName !== $user['full_name'] || ($apiEmail && $apiEmail !== $user['email'])) {
                $upd = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, updated_at = NOW() WHERE user_id = ?");
                $upd->execute([$apiFullName, $apiEmail ?: $user['email'], $user['user_id']]);
                $user['full_name'] = $apiFullName;
                $user['email']     = $apiEmail ?: $user['email'];
            }
            return $user;
        }

        // Auto-provision: admin field is "NONE" for non-admins, anything else = admin
        $localRole = ($apiUserData[EXTERNAL_AUTH_RES_ADMIN] ?? EXTERNAL_AUTH_ROLE_NONE) !== EXTERNAL_AUTH_ROLE_NONE
            ? 'admin'
            : 'user';

        $ins = $pdo->prepare("
            INSERT INTO users (username, email, full_name, role, is_active, changed_password, password_hash, created_at, updated_at)
            VALUES (?, ?, ?, ?, 1, 1, '', NOW(), NOW())
        ");
        $ins->execute([$apiUsername, $apiEmail, $apiFullName, $localRole]);

        // Return the newly created row
        $stmt2 = $pdo->prepare("SELECT user_id, username, email, full_name, role, is_active, changed_password FROM users WHERE user_id = ?");
        $stmt2->execute([$pdo->lastInsertId()]);
        return $stmt2->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("findOrProvisionUser error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log out current user
 */
function logout()
{
    // Log logout before destroying session
    if (isset($_SESSION['user_id'])) {
        logLogout($_SESSION['user_id']);
    }

    // Unset all session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy the session
    session_destroy();
}

/**
 * Check if user is logged in
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn()
{
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['login_time'])) {
        return false;
    }

    // Check session timeout (default 1 hour)
    $timeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 3600;
    if (time() - $_SESSION['login_time'] > $timeout) {
        logout();
        return false;
    }

    // Update last activity time
    $_SESSION['login_time'] = time();

    return true;
}

/**
 * Require user to be logged in, redirect to login if not
 * @param string $redirectTo URL to redirect to after login
 */
function requireLogin($redirectTo = null)
{
    if (!isLoggedIn()) {
        // Store intended destination
        if ($redirectTo) {
            $_SESSION['redirect_after_login'] = $redirectTo;
        } else {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        }

        // Redirect to login page
        header('Location: ' . getLoginUrl());
        exit;
    }


}

/**
 * Check if current user has specific role
 * @param string $role Role to check
 * @return bool True if user has role, false otherwise
 */
function hasRole($role)
{
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Require user to have specific role
 * @param string $role Required role
 */
function requireRole($role)
{
    if (!isLoggedIn()) {
        requireLogin();
    }

    if (!hasRole($role)) {
        http_response_code(403);
        die('Access denied. You do not have permission to access this page.');
    }
}

/**
 * Get current logged-in user data
 * @return array|null User data or null if not logged in
 */
function getCurrentUser()
{
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];
}

/**
 * Generate a consistent URL based on the environment
 * Handles both router mode and subdirectory (XAMPP) mode
 * @param string $path The relative path from the public directory
 * @return string The absolute URL path
 */
function url($path)
{
    $originalPath = $path;
    $path = ltrim($path, '/');

    // Get the script name (e.g., /index.php or /project/public/index.php)
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $resultUrl = '';

    // Check if we are in a 'public' subdirectory context
    $publicPos = strpos($scriptName, '/public/');

    if ($publicPos !== false) {
        // We are in a subdirectory like /project/public/
        $base = substr($scriptName, 0, $publicPos + 8); // includes the trailing slash
        $resultUrl = $base . $path;
    }
    // Check if we are in 'public' directly (XAMPP root of public)
    elseif (strpos($scriptName, '/public') === 0 && (strlen($scriptName) === 7 || $scriptName[7] === '/')) {
        $resultUrl = '/public/' . $path;
    } else {
        // Default to root-relative path (router mode)
        $resultUrl = '/' . $path;
    }

    // Add cache-busting version parameter for static assets
    $filePath = realpath(__DIR__ . '/../../public/' . $originalPath);
    if ($filePath && file_exists($filePath)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (in_array($ext, ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico'])) {
            $version = filemtime($filePath);
            $separator = (strpos($resultUrl, '?') !== false) ? '&' : '?';
            $resultUrl .= $separator . 'v=' . $version;
        }
    }

    return $resultUrl;
}

/**
 * Update user's last login timestamp
 * @param int $userId User ID
 */
function updateLastLogin($userId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $stmt->execute([$userId]);
    } catch (PDOException $e) {
        error_log("Update last login error: " . $e->getMessage());
    }
}

/**
 * Get login page URL
 * @return string Login page URL
 */
function getLoginUrl()
{
    return url('login.php');
}

/**
 * Validate password strength
 * @param string $password Password to validate
 * @return array Array with 'valid' boolean and 'errors' array
 */
function validatePassword($password)
{
    $errors = [];
    $minLength = defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 8;

    if (strlen($password) < $minLength) {
        $errors[] = "Password must be at least {$minLength} characters long";
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }

    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}
