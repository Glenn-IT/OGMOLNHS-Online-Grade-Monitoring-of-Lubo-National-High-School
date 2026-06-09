<?php
// config/session.php
if (session_status() === PHP_SESSION_NONE) {
    // Harden session cookie before starting the session
    ini_set('session.cookie_httponly',  '1');  // JS cannot read the cookie
    ini_set('session.cookie_samesite',  'Strict');
    ini_set('session.use_strict_mode',  '1');  // reject uninitialized session IDs
    ini_set('session.use_only_cookies', '1');  // no session ID in URL
    session_name('OGMS_SID');
    session_start();
}

// ── Route guards ────────────────────────────────────────────────────────────
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: /OGMS-Lubo-National-High-School/index.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: /OGMS-Lubo-National-High-School/views/student/dashboard.php');
        exit;
    }
}

function requireStudent(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'student') {
        header('Location: /OGMS-Lubo-National-High-School/views/admin/dashboard.php');
        exit;
    }
}

// ── JSON response helper ─────────────────────────────────────────────────────
function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    // Prevent the browser from caching API responses
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    // Security headers on every API response
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
