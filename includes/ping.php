<?php
/**
 * SESSION PING ENDPOINT
 * Called by the client-side JS to extend the session timeout.
 * Returns a lightweight JSON response — no HTML output.
 */
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'unauthorized']);
    exit;
}

// Touch the session's last_activity timestamp
$_SESSION['last_activity'] = time();

echo json_encode(['status' => 'ok', 'timeout' => SESSION_TIMEOUT]);