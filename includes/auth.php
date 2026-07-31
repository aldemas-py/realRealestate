<?php

/**
 * AUTHENTICATION & AUTHORIZATION SYSTEM
 * Policy-as-Code: RBAC with compliance checks & audit trails
 *
 * SESSION SECURITY POLICY:
 * - Session cookie expires when browser is closed (cookie_lifetime = 0)
 * - Session times out after 5 minutes of inactivity (SESSION_TIMEOUT)
 * - HttpOnly + SameSite cookies to prevent XSS/session theft
 */

// Session security hardening - MUST be called before session_start()
session_name('ZAHARA_SESSION');
session_set_cookie_params([
    'lifetime' => 0,             // 0 = cookie expires when browser is closed
    'path' => '/',
    'httponly' => true,          // Prevent JavaScript access to session cookie
    'samesite' => 'Lax',         // CSRF protection
    'secure' => false            // Set to true when using HTTPS in production
]);

session_start();

// Policy: Session inactivity timeout (5 minutes = 300 seconds)
define('SESSION_TIMEOUT', 300);

/**
 * Policy: Session Timeout Enforcement
 * Checks if the session has been inactive for more than SESSION_TIMEOUT seconds.
 * Called on every page load. Logs the timeout to the compliance audit trail.
 */
function checkSessionTimeout(): void
{
    // Only enforce for authenticated sessions
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['last_activity'] = time();
        return;
    }

    // Check for inactivity
    if (isset($_SESSION['last_activity'])) {
        $inactiveSeconds = time() - (int)$_SESSION['last_activity'];

        if ($inactiveSeconds > SESSION_TIMEOUT) {
            $userId = $_SESSION['user_id'];

            // Compliance: log the timeout to audit trail
            logAudit(
                $userId,
                'session.timeout',
                'user',
                $userId,
                null,
                null,
                'pass',
                "Session expired due to " . SESSION_TIMEOUT . " seconds of inactivity"
            );

            // Destroy the session entirely
            session_unset();
            session_destroy();

            // Restart a fresh session to store the flash message
            session_start();
            session_regenerate_id(true);
            $_SESSION['warning'] = 'Your session has expired due to 5 minutes of inactivity. Please log in again.';

            header('Location: /work_folder/realRealestate/public/login.php');
            exit;
        }
    }

    // Update last activity timestamp
    $_SESSION['last_activity'] = time();
}

/**
 * Policy: Regenerate session ID on login (prevents session fixation)
 */
function regenerateSession(): void
{
    session_regenerate_id(true);
    $_SESSION['last_activity'] = time();
}

// Load database helpers (needed by logAudit in checkSessionTimeout)
require_once __DIR__ . '/../config/database.php';

// Run the session timeout check on every page load
checkSessionTimeout();

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Get current user data with role info
 */
function getCurrentUser(): ?array
{
    if (!isLoggedIn()) return null;
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT u.*, r.role_name, r.role_level 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.id = ? AND u.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Policy: RBAC - Check role level
 */
function hasRoleLevel(int $minLevel): bool
{
    $user = getCurrentUser();
    return $user && $user['role_level'] >= $minLevel;
}

function isAdmin(): bool
{
    return hasRoleLevel(100);
}
function isCustomer(): bool
{
    return hasRoleLevel(10);
}

/**
 * Policy: Require authentication with redirect
 */
function requireAuth(): void
{
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        $_SESSION['warning'] = 'Please log in to continue.';
        header('Location: /work_folder/realRealestate/public/login.php');
        exit;
    }
}

function requireAdmin(): void
{
    requireAuth();
    if (!isAdmin()) {
        $_SESSION['error'] = 'Access denied. Admin privileges required.';
        header('Location: /work_folder/realRealestate/index.php');
        exit;
    }
}

/**
 * Policy: Compliance Audit Log
 */
function logAudit(int $userId, string $action, string $entityType, ?int $entityId = null, ?array $oldValues = null, ?array $newValues = null, string $complianceStatus = 'pass', ?string $violation = null): void
{
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO audit_log (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, compliance_status, policy_violation)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
            $complianceStatus,
            $violation
        ]);
    } catch (Exception $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}

/**
 * Login user with compliance check
 */
function loginUser(string $email, string $password): array
{
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        // Policy: Check user status
        if ($user['status'] !== 'active') {
            logAudit($user['id'], 'login.denied', 'user', $user['id'], null, null, 'fail', "Login denied - account status: {$user['status']}");
            return ['success' => false, 'message' => 'Your account is not active. Please contact admin.'];
        }

        $_SESSION['user_id'] = $user['id'];

        // Policy: Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);

        // Set last activity timestamp for inactivity timeout
        $_SESSION['last_activity'] = time();

        // Update last login
        $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

        // Compliance audit
        logAudit($user['id'], 'login.success', 'user', $user['id']);

        return ['success' => true, 'user' => $user];
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'System error. Please try again.'];
    }
}

/**
 * Logout user - clears session fully
 * Policy: Also clears the session cookie on the client side
 */
function logoutUser(): void
{
    if (isLoggedIn()) {
        logAudit($_SESSION['user_id'], 'logout', 'user', $_SESSION['user_id']);
    }

    // Clear session data
    $_SESSION = [];

    // Clear the session cookie on the client side
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 86400, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }

    session_destroy();

    // If this is a beacon request (inactivity timeout via sendBeacon),
    // don't redirect - just return 200 OK
    if (isset($_SERVER['HTTP_SEC_FETCH_MODE']) && $_SERVER['HTTP_SEC_FETCH_MODE'] === 'navigate') {
        // Normal request - redirect
    } elseif (empty($_SERVER['CONTENT_TYPE']) && !isset($_GET['redirect'])) {
        // Beacon request - just return success silently
        http_response_code(200);
        exit;
    }

    header('Location: /work_folder/realRealestate/index.php');
    exit;
}

/**
 * Policy: Validate email format compliance
 */
function validateEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Policy: Validate password strength
 */
function validatePassword(string $password): array
{
    $errors = [];
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Password must contain an uppercase letter.';
    if (!preg_match('/[a-z]/', $password)) $errors[] = 'Password must contain a lowercase letter.';
    if (!preg_match('/[0-9]/', $password)) $errors[] = 'Password must contain a number.';
    return $errors;
}

/**
 * Display flash messages
 */
function displayFlashMessages(): void
{
    $types = ['success', 'error', 'warning', 'info'];
    foreach ($types as $type) {
        if (isset($_SESSION[$type])) {
            $icon = ['success' => '✓', 'error' => '✕', 'warning' => '⚠', 'info' => 'ℹ'][$type];
            echo "<div class='alert alert-{$type}'><span class='alert-icon'>{$icon}</span><p>{$_SESSION[$type]}</p><button class='alert-close' onclick='this.parentElement.remove()'>&times;</button></div>";
            unset($_SESSION[$type]);
        }
    }
}