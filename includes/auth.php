<?php

/**
 * AUTHENTICATION & AUTHORIZATION SYSTEM
 * Policy-as-Code: RBAC with compliance checks & audit trails
 */

session_start();
require_once __DIR__ . '/../config/database.php';

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
 * Logout user
 */
function logoutUser(): void
{
    if (isLoggedIn()) {
        logAudit($_SESSION['user_id'], 'logout', 'user', $_SESSION['user_id']);
    }
    session_destroy();
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
