<?php
// api/auth.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/mailer.php';

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
    $stmt = $pdo->prepare('SELECT id, full_name, email FROM users WHERE email = ? AND is_active = 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always return success to prevent email enumeration
    if ($user) {
        $token = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Compute expiry inside MySQL (not PHP) so it's compared against the same
        // clock as the NOW() check in reset_confirm — avoids PHP/MySQL timezone drift.
        $pdo->prepare(
            'INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, NOW() + INTERVAL 15 MINUTE)'
        )->execute([$user['id'], $token]);

        $subject = 'OGMS Password Reset Code';
        $body = '<p>Hello ' . htmlspecialchars($user['full_name']) . ',</p>'
              . '<p>We received a request to reset your OGMS password. Use the code below within the next 15 minutes:</p>'
              . '<p style="font-size:1.5rem;font-weight:bold;letter-spacing:4px">' . htmlspecialchars($token) . '</p>'
              . '<p>If you did not request this, you can safely ignore this email.</p>';
        sendMail($user['email'], $user['full_name'], $subject, $body);
    }

    jsonResponse(['success' => true, 'message' => 'If that email exists, a reset link has been sent.']);
}

// ─── PASSWORD RESET CONFIRM ────────────────────────────────────────────────
if ($action === 'reset_confirm') {
    // Brute-force guard: 6-digit codes have a small keyspace, so cap attempts per session.
    $attempts = (int)($_SESSION['reset_attempts'] ?? 0);
    if ($attempts >= 10) {
        jsonResponse(['success' => false, 'message' => 'Too many attempts. Please request a new code.'], 429);
    }

    // Strip all whitespace (not just leading/trailing) — email clients can
    // insert stray spaces/line breaks when the code wraps and gets copied.
    $token    = preg_replace('/\s+/', '', $_POST['token'] ?? '');
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
        $_SESSION['reset_attempts'] = $attempts + 1;
        jsonResponse(['success' => false, 'message' => 'Reset code is invalid or has expired.'], 400);
    }

    unset($_SESSION['reset_attempts']);

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
        ->execute([$hash, $reset['user_id']]);
    $pdo->prepare('UPDATE password_resets SET used = 1 WHERE id = ?')
        ->execute([$reset['id']]);

    jsonResponse(['success' => true, 'message' => 'Password updated successfully.']);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
