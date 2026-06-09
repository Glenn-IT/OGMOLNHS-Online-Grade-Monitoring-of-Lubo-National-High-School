<?php
// api/auth.php
require_once '../config/db.php';
require_once '../config/session.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─── LOGIN ─────────────────────────────────────────────────────────────────
if ($action === 'login') {
    // Simple brute-force guard: max 10 failed attempts per session
    $attempts = (int)($_SESSION['login_attempts'] ?? 0);
    if ($attempts >= 10) {
        jsonResponse(['success' => false, 'message' => 'Too many failed attempts. Please restart your browser.'], 429);
    }

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        jsonResponse(['success' => false, 'message' => 'Email and password are required.'], 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Invalid email format.'], 400);
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['login_attempts'] = $attempts + 1;
        jsonResponse(['success' => false, 'message' => 'Invalid email or password.'], 401);
    }

    // Reset counter on successful login
    unset($_SESSION['login_attempts']);

    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['email']     = $user['email'];
    $_SESSION['lrn']       = $user['lrn'];

    $redirect = match($user['role']) {
        'admin'   => '/OGMS-Lubo-National-High-School/views/admin/dashboard.php',
        'teacher' => '/OGMS-Lubo-National-High-School/views/admin/dashboard.php',
        default   => '/OGMS-Lubo-National-High-School/views/student/dashboard.php',
    };

    jsonResponse([
        'success'  => true,
        'role'     => $user['role'],
        'name'     => $user['full_name'],
        'redirect' => $redirect,
    ]);
}

// ─── LOGOUT ────────────────────────────────────────────────────────────────
if ($action === 'logout') {
    session_destroy();
    jsonResponse(['success' => true]);
}

// ─── SESSION CHECK ─────────────────────────────────────────────────────────
if ($action === 'check') {
    if (!empty($_SESSION['user_id'])) {
        jsonResponse([
            'logged_in' => true,
            'role'      => $_SESSION['role'],
            'name'      => $_SESSION['full_name'],
            'user_id'   => $_SESSION['user_id'],
        ]);
    }
    jsonResponse(['logged_in' => false]);
}

// ─── PASSWORD RESET REQUEST ────────────────────────────────────────────────
if ($action === 'reset_request') {
    $email = trim($_POST['email'] ?? '');
    if (!$email) {
        jsonResponse(['success' => false, 'message' => 'Email is required.'], 400);
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND is_active = 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always return success to prevent email enumeration
    if ($user) {
        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)')
            ->execute([$user['id'], $token, $expiresAt]);
        // TODO: send reset link via email or SMS
    }

    jsonResponse(['success' => true, 'message' => 'If that email exists, a reset link has been sent.']);
}

// ─── PASSWORD RESET CONFIRM ────────────────────────────────────────────────
if ($action === 'reset_confirm') {
    $token    = trim($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$token || strlen($password) < 8) {
        jsonResponse(['success' => false, 'message' => 'Password must be at least 8 characters.'], 400);
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()'
    );
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset) {
        jsonResponse(['success' => false, 'message' => 'Reset link is invalid or has expired.'], 400);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
        ->execute([$hash, $reset['user_id']]);
    $pdo->prepare('UPDATE password_resets SET used = 1 WHERE id = ?')
        ->execute([$reset['id']]);

    jsonResponse(['success' => true, 'message' => 'Password updated successfully.']);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
