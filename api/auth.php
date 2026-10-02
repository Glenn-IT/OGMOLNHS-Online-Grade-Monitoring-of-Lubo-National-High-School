<?php
// api/auth.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/mailer.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─── LOGIN ─────────────────────────────────────────────────────────────────
if ($action === 'login') {
    // Lockout guard: max 3 failed attempts per tab (student/teacher/admin tracked separately),
    // then a 30-second cooldown before the next attempt is allowed.
    $rawType     = $_POST['login_type'] ?? 'student';
    $loginType   = in_array($rawType, ['admin', 'teacher', 'student']) ? $rawType : 'student';
    $attemptsKey = "login_attempts_$loginType";
    $lockKey     = "login_lockout_until_$loginType";

    $lockUntil = (int)($_SESSION[$lockKey] ?? 0);
    if ($lockUntil > time()) {
        jsonResponse([
            'success' => false,
            'locked'  => true,
            'seconds' => $lockUntil - time(),
            'message' => 'Too many failed attempts. Please wait before trying again.',
        ], 429);
    }

    $attempts = (int)($_SESSION[$attemptsKey] ?? 0);

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        jsonResponse(['success' => false, 'message' => 'Email and password are required.'], 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Invalid email format.'], 400);
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        $attempts++;

        if ($attempts >= 3) {
            $_SESSION[$attemptsKey] = 0;
            $_SESSION[$lockKey]     = time() + 30;
            jsonResponse([
                'success' => false,
                'locked'  => true,
                'seconds' => 30,
                'message' => 'Too many failed attempts. Please wait 30 seconds before trying again.',
            ], 429);
        }

        $_SESSION[$attemptsKey] = $attempts;
        jsonResponse([
            'success'       => false,
            'attempts_left' => 3 - $attempts,
            'message'       => 'Invalid email or password. ' . (3 - $attempts) . ' attempt(s) left.',
        ], 401);
    }

    // Role check against tab
    if ($user['role'] !== $loginType) {
        $properTab = ucfirst($user['role']);
        jsonResponse([
            'success' => false,
            'message' => "This account is registered as a $properTab. Please use the $properTab tab to log in.",
        ], 403);
    }

    // Teacher approval check
    if ($user['role'] === 'teacher') {
        $appStatus = $user['approval_status'] ?? 'approved';
        if ($appStatus === 'pending') {
            jsonResponse([
                'success'          => false,
                'pending_approval' => true,
                'message'          => 'Your teacher account is pending administrator approval. Please wait for an administrator to review and activate your account before logging in.',
            ], 403);
        }
        if ($appStatus === 'rejected') {
            jsonResponse([
                'success' => false,
                'message' => 'Your teacher registration was not approved. Please contact the LNHS administration for inquiries.',
            ], 403);
        }
    }

    // Admin approval check
    if ($user['role'] === 'admin') {
        $appStatus = $user['approval_status'] ?? 'approved';
        if ($appStatus === 'pending') {
            jsonResponse([
                'success'          => false,
                'pending_approval' => true,
                'message'          => 'Your administrator account is pending approval by the Superadmin. Please wait for the Superadmin to review and activate your account before logging in.',
            ], 403);
        }
        if ($appStatus === 'rejected') {
            jsonResponse([
                'success' => false,
                'message' => 'Your administrator account registration was not approved. Please contact the Superadmin.',
            ], 403);
        }
    }

    // Account active check
    if ((int)$user['is_active'] !== 1) {
        jsonResponse([
            'success' => false,
            'message' => 'Your account is currently inactive or deactivated. Please contact the administrator.',
        ], 403);
    }

    // Reset counter on successful login
    unset($_SESSION[$attemptsKey], $_SESSION[$lockKey]);

    session_regenerate_id(true);
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['full_name']     = $user['full_name'];
    $_SESSION['role']          = $user['role'];
    $_SESSION['is_superadmin'] = (int)($user['is_superadmin'] ?? 0);
    $_SESSION['email']         = $user['email'];
    $_SESSION['lrn']           = $user['lrn'];

    $redirect = match($user['role']) {
        'admin'   => '/OGMS-Lubo-National-High-School/views/admin/dashboard.php',
        'teacher' => '/OGMS-Lubo-National-High-School/views/teacher/dashboard.php',
        default   => '/OGMS-Lubo-National-High-School/views/student/dashboard.php',
    };

    jsonResponse([
        'success'       => true,
        'role'          => $user['role'],
        'is_superadmin' => (int)($user['is_superadmin'] ?? 0),
        'name'          => $user['full_name'],
        'redirect'      => $redirect,
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
            'logged_in'     => true,
            'role'          => $_SESSION['role'],
            'is_superadmin' => !empty($_SESSION['is_superadmin']),
            'name'          => $_SESSION['full_name'],
            'user_id'       => $_SESSION['user_id'],
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
