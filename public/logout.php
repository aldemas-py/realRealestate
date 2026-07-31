<?php

/**
 * LOGOUT - Handles three modes:
 *
 * 1. Normal request (user clicks "Logout")
 *    -> destroys session, redirects to homepage
 *
 * 2. Session expiry navigation (?expired=1)
 *    -> destroys session, redirects to login page with warning
 *
 * 3. Beacon request (sendBeacon from browser close / inactivity)
 *    -> silently destroys session, returns 200 OK (no redirect)
 */

require_once __DIR__ . '/../includes/auth.php';

// Determine request type
$isBeacon = isset($_SERVER['HTTP_SEC_FETCH_MODE']) && $_SERVER['HTTP_SEC_FETCH_MODE'] === 'no-cors';
$isExpiry = isset($_GET['expired']);
$isManual = !$isBeacon && !$isExpiry;

// Log the appropriate audit action
if (isLoggedIn()) {
    $action = $isExpiry ? 'session.timeout' : 'logout';
    logAudit(
        $_SESSION['user_id'],
        $action,
        'user',
        $_SESSION['user_id'],
        null,
        null,
        'pass',
        $isExpiry ? 'Session expired after 5 minutes of inactivity (client-side detection)' : 'User logged out'
    );
}

// Clear session data
$_SESSION = [];

// Clear the session cookie on the client side
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 86400, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

session_destroy();

// Mode 3: Beacon/background request (sendBeacon) -> silent 200, no redirect
if ($isBeacon) {
    http_response_code(200);
    exit;
}

// Mode 2: Session expiry -> start fresh session, show warning, redirect to login
if ($isExpiry) {
    session_start();
    session_regenerate_id(true);
    $_SESSION['warning'] = 'Your session has expired due to 5 minutes of inactivity. Please log in again.';
    header('Location: /work_folder/realRealestate/public/login.php');
    exit;
}

// Mode 1: Manual logout -> redirect to homepage
header('Location: /work_folder/realRealestate/index.php');
exit;