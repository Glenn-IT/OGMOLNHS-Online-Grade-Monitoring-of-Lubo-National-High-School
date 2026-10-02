<?php
// api/admins.php — Administrator Management & Self-Registration Endpoint
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/mailer.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─── SEND SIGNUP OTP (Public) ────────────────────────────────────────────────
if ($action === 'send_signup_otp') {
    $email     = strtolower(trim($_POST['email']      ?? ''));
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $phone     = trim($_POST['phone']      ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Valid email address is required.'], 400);
    }
    if ($phone && !preg_match('/^09\d{9}$/', $phone)) {
        jsonResponse(['success' => false, 'message' => 'Phone number must be an 11-digit PH mobile number starting with 09 (e.g. 09XXXXXXXXX).'], 400);
    }

    $pdo = getDB();

    // Check duplicate email
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'This email address is already registered. Please sign in or use another email.'], 409);
    }

    // Generate 6-digit OTP
    $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

    // Invalidate previous active OTPs for this email
    $pdo->prepare('UPDATE email_verifications SET used = 1 WHERE email = ? AND used = 0')->execute([$email]);

    // Insert new OTP with 10-minute expiry
    $stmt = $pdo->prepare('INSERT INTO email_verifications (email, otp, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))');
    $stmt->execute([$email, $otp]);

    $recipientName = trim("$firstName $lastName") ?: 'Administrator Applicant';
    $subject = 'Administrator Registration OTP Verification – Lubo National High School';
    $bodyHtml = "
    <div style='font-family:Arial,Helvetica,sans-serif;max-width:540px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:12px;background:#ffffff;'>
      <div style='text-align:center;padding-bottom:18px;border-bottom:2px solid #0c1326;'>
        <h2 style='color:#0c1326;margin:0;font-size:20px;letter-spacing:0.5px;'>LUBO NATIONAL HIGH SCHOOL</h2>
        <p style='color:#64748b;margin:4px 0 0;font-size:13px;'>Online Grade Monitoring System (OGMS) &bull; Administration Gateway</p>
      </div>
      <div style='padding:24px 0;'>
        <p style='font-size:15px;color:#1e293b;margin:0 0 12px;'>Hello <strong>" . htmlspecialchars($recipientName) . "</strong>,</p>
        <p style='font-size:14px;color:#475569;line-height:1.6;margin:0 0 20px;'>
          You have requested to register an administrative account for the Lubo NHS Online Grade Monitoring System. Please use the One-Time Password (OTP) below to verify your email address:
        </p>
        <div style='background:#fef2f2;border:1px dashed #ef4444;border-radius:10px;padding:20px;text-align:center;margin:0 0 20px;'>
          <div style='font-size:32px;font-weight:bold;letter-spacing:8px;color:#b91c1c;'>" . htmlspecialchars($otp) . "</div>
          <div style='font-size:12px;color:#64748b;margin-top:6px;'>This verification code expires in 10 minutes</div>
        </div>
        <div style='background:#fffbeb;border-left:4px solid #f59e0b;padding:12px;margin:0 0 20px;border-radius:4px;'>
          <p style='font-size:13px;color:#92400e;margin:0;'>
            <strong>High-Privilege Notice:</strong> Administrative access grants oversight of student records, grades, and faculty rosters. For security, your account requires explicit review and approval by the <strong>Superadmin</strong> before you can log in.
          </p>
        </div>
        <p style='font-size:13px;color:#94a3b8;line-height:1.5;margin:0;'>
          If you did not initiate this request, please report this immediately. Never share your OTP with anyone.
        </p>
      </div>
      <div style='border-top:1px solid #e2e8f0;padding-top:16px;text-align:center;color:#94a3b8;font-size:11px;'>
        &copy; " . date('Y') . " Lubo National High School &bull; DepEd Philippines
      </div>
    </div>";

    $mailOk = sendMail($email, $recipientName, $subject, $bodyHtml);

    if ($mailOk) {
        jsonResponse(['success' => true, 'message' => "Verification OTP sent to $email. Please check your inbox (and spam folder)."]);
    } else {
        $detail = function_exists('getMailerLastError') ? getMailerLastError() : '';
        $msg = 'Unable to send OTP email.';
        if ($detail) {
            $msg .= ' ' . $detail;
        } else {
            $msg .= ' Please check your SMTP configuration and internet connection.';
        }
        jsonResponse(['success' => false, 'message' => $msg], 500);
    }
}

// ─── REGISTER NEW ADMIN (Public) ─────────────────────────────────────────────
if ($action === 'register') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $email     = strtolower(trim($_POST['email'] ?? ''));
    $password  = $_POST['password']            ?? '';
    $phone     = trim($_POST['phone']          ?? '');
    $gender    = trim($_POST['gender']         ?? 'Other');
    $address   = trim($_POST['address']        ?? '');
    $otp       = trim($_POST['otp']            ?? '');

    if (!$firstName || !$lastName || !$email || !$password) {
        jsonResponse(['success' => false, 'message' => 'First name, last name, email, and password are required.'], 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Invalid email address.'], 400);
    }
    if (strlen($password) < 8) {
        jsonResponse(['success' => false, 'message' => 'Admin passwords must be at least 8 characters long.'], 400);
    }
    if ($phone && !preg_match('/^09\d{9}$/', $phone)) {
        jsonResponse(['success' => false, 'message' => 'Phone number must be an 11-digit PH mobile number starting with 09 (e.g. 09XXXXXXXXX).'], 400);
    }
    if (!in_array($gender, ['Male', 'Female', 'Other'])) {
        $gender = 'Other';
    }
    if (!$otp) {
        jsonResponse(['success' => false, 'message' => 'OTP verification code is required.'], 400);
    }

    $pdo = getDB();

    // Verify OTP
    $stmt = $pdo->prepare('SELECT id FROM email_verifications WHERE email = ? AND otp = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
    $stmt->execute([$email, $otp]);
    $verRow = $stmt->fetch();
    if (!$verRow) {
        jsonResponse(['success' => false, 'message' => 'Invalid or expired OTP code. Please enter the correct code or request a new one.'], 400);
    }
    // Mark OTP used
    $pdo->prepare('UPDATE email_verifications SET used = 1 WHERE id = ?')->execute([$verRow['id']]);

    // Check duplicate email
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'This email address is already registered.'], 409);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $fullName = trim("$firstName $lastName");

    // All self-registered admins are is_superadmin=0, is_active=0, approval_status='pending'
    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, password, role, is_superadmin, phone, gender, address, is_active, approval_status, created_at)
         VALUES (?, ?, ?, 'admin', 0, ?, ?, ?, 0, 'pending', NOW())"
    );
    $stmt->execute([$fullName, $email, $hash, $phone ?: null, $gender, $address ?: null]);

    jsonResponse([
        'success' => true,
        'message' => 'Administrator registration submitted successfully! Your account is now pending approval by the Superadmin before you can sign in.',
        'id'      => (int)$pdo->lastInsertId(),
    ]);
}

// ─── LIST ALL ADMINS (Superadmin only) ────────────────────────────────────────
if ($action === 'list') {
    requireSuperAdmin();
    $pdo = getDB();

    $stmt = $pdo->query(
        "SELECT id, full_name, email, phone, gender, address, role, is_superadmin, is_active, approval_status, created_at
         FROM users
         WHERE role = 'admin'
         ORDER BY is_superadmin DESC, full_name ASC"
    );
    $admins = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'data'    => $admins
    ]);
}

// ─── GET SINGLE ADMIN (Superadmin only) ───────────────────────────────────────
if ($action === 'get') {
    requireSuperAdmin();
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Admin ID is required.'], 400);
    }

    $pdo = getDB();
    $stmt = $pdo->prepare(
        "SELECT id, full_name, email, phone, gender, address, role, is_superadmin, is_active, approval_status, created_at
         FROM users
         WHERE id = ? AND role = 'admin'"
    );
    $stmt->execute([$id]);
    $admin = $stmt->fetch();

    if (!$admin) {
        jsonResponse(['success' => false, 'message' => 'Administrator not found.'], 404);
    }

    jsonResponse(['success' => true, 'data' => $admin]);
}

// ─── APPROVE ADMIN (Superadmin only) ─────────────────────────────────────────
if ($action === 'approve') {
    requireSuperAdmin();
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Admin ID is required.'], 400);
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, full_name, email, is_superadmin, approval_status FROM users WHERE id = ? AND role = 'admin'");
    $stmt->execute([$id]);
    $admin = $stmt->fetch();

    if (!$admin) {
        jsonResponse(['success' => false, 'message' => 'Administrator account not found.'], 404);
    }

    $update = $pdo->prepare("UPDATE users SET is_active = 1, approval_status = 'approved', updated_at = NOW() WHERE id = ?");
    $update->execute([$id]);

    // Send confirmation email
    $subject = 'Administrator Account Approved – Lubo National High School';
    $bodyHtml = "
    <div style='font-family:Arial,Helvetica,sans-serif;max-width:540px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:12px;background:#ffffff;'>
      <div style='text-align:center;padding-bottom:18px;border-bottom:2px solid #0c1326;'>
        <h2 style='color:#0c1326;margin:0;font-size:20px;letter-spacing:0.5px;'>LUBO NATIONAL HIGH SCHOOL</h2>
        <p style='color:#64748b;margin:4px 0 0;font-size:13px;'>Online Grade Monitoring System (OGMS) &bull; Administration Gateway</p>
      </div>
      <div style='padding:24px 0;'>
        <p style='font-size:15px;color:#1e293b;margin:0 0 12px;'>Hello <strong>" . htmlspecialchars($admin['full_name']) . "</strong>,</p>
        <p style='font-size:14px;color:#475569;line-height:1.6;margin:0 0 20px;'>
          Your administrator account registration has been reviewed and <strong>approved</strong> by the Superadmin.
        </p>
        <div style='background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px;margin:0 0 20px;text-align:center;'>
          <p style='margin:0;color:#166534;font-size:14px;font-weight:600;'>
            <i class='fas fa-shield-alt me-1'></i> Administrator access is now fully active.
          </p>
        </div>
        <p style='font-size:14px;color:#475569;line-height:1.6;margin:0 0 20px;'>
          You can now sign in using your registered email (<code>" . htmlspecialchars($admin['email']) . "</code>) and password at the Admin Gateway.
        </p>
      </div>
      <div style='border-top:1px solid #e2e8f0;padding-top:16px;text-align:center;color:#94a3b8;font-size:11px;'>
        &copy; " . date('Y') . " Lubo National High School &bull; DepEd Philippines
      </div>
    </div>";

    @sendMail($admin['email'], $admin['full_name'], $subject, $bodyHtml);

    jsonResponse([
        'success'         => true,
        'message'         => "Administrator {$admin['full_name']} has been approved and activated.",
        'is_active'       => 1,
        'approval_status' => 'approved'
    ]);
}

// ─── REJECT ADMIN (Superadmin only) ──────────────────────────────────────────
if ($action === 'reject') {
    requireSuperAdmin();
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Admin ID is required.'], 400);
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, full_name, email, is_superadmin FROM users WHERE id = ? AND role = 'admin'");
    $stmt->execute([$id]);
    $admin = $stmt->fetch();

    if (!$admin) {
        jsonResponse(['success' => false, 'message' => 'Administrator account not found.'], 404);
    }
    if ((int)$admin['is_superadmin'] === 1) {
        jsonResponse(['success' => false, 'message' => 'Cannot reject the Superadmin account.'], 400);
    }

    $update = $pdo->prepare("UPDATE users SET is_active = 0, approval_status = 'rejected', updated_at = NOW() WHERE id = ?");
    $update->execute([$id]);

    jsonResponse([
        'success'         => true,
        'message'         => "Administrator registration for {$admin['full_name']} was rejected.",
        'is_active'       => 0,
        'approval_status' => 'rejected'
    ]);
}

// ─── TOGGLE STATUS (Superadmin only) ─────────────────────────────────────────
if ($action === 'toggle_status') {
    requireSuperAdmin();
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Admin ID is required.'], 400);
    }

    // Guard against modifying self
    if ($id === (int)$_SESSION['user_id']) {
        jsonResponse(['success' => false, 'message' => 'You cannot deactivate your own Superadmin account.'], 400);
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, full_name, is_superadmin, is_active FROM users WHERE id = ? AND role = 'admin'");
    $stmt->execute([$id]);
    $admin = $stmt->fetch();

    if (!$admin) {
        jsonResponse(['success' => false, 'message' => 'Administrator not found.'], 404);
    }
    if ((int)$admin['is_superadmin'] === 1) {
        jsonResponse(['success' => false, 'message' => 'Cannot modify Superadmin account status.'], 400);
    }

    $newStatus = $admin['is_active'] ? 0 : 1;
    $pdo->prepare("UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $id]);

    $label = $newStatus ? 'activated' : 'deactivated';
    jsonResponse(['success' => true, 'message' => "Administrator {$admin['full_name']} has been {$label}.", 'is_active' => $newStatus]);
}

// ─── SAVE / EDIT ADMIN (Superadmin only) ─────────────────────────────────────
if ($action === 'save') {
    requireSuperAdmin();
    $pdo = getDB();

    $id       = (int)($_POST['id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $phone    = trim($_POST['phone'] ?? '');
    $gender   = trim($_POST['gender'] ?? 'Other');
    $address  = trim($_POST['address'] ?? '');
    $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

    if (!$fullName) {
        jsonResponse(['success' => false, 'message' => 'Full name is required.'], 400);
    }
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'A valid email address is required.'], 400);
    }
    if ($phone && !preg_match('/^09\d{9}$/', $phone)) {
        jsonResponse(['success' => false, 'message' => 'Phone number must be in Philippine 09XXXXXXXXX format.'], 400);
    }
    if (!in_array($gender, ['Male', 'Female', 'Other'])) {
        $gender = 'Other';
    }

    // Check email uniqueness
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'This email address is already in use.'], 400);
    }

    if ($id > 0) {
        // Edit existing admin
        $stmt = $pdo->prepare("SELECT id, is_superadmin FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$id]);
        $target = $stmt->fetch();
        if (!$target) {
            jsonResponse(['success' => false, 'message' => 'Administrator not found.'], 404);
        }

        if (!empty($password)) {
            if (strlen($password) < 8) {
                jsonResponse(['success' => false, 'message' => 'Admin password must be at least 8 characters long.'], 400);
            }
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare(
                "UPDATE users 
                 SET full_name = ?, email = ?, password = ?, phone = ?, gender = ?, address = ?, is_active = ?, approval_status = IF(? = 1, 'approved', approval_status), updated_at = NOW()
                 WHERE id = ? AND role = 'admin'"
            );
            $stmt->execute([$fullName, $email, $hash, $phone ?: null, $gender, $address ?: null, $isActive, $isActive, $id]);
        } else {
            $stmt = $pdo->prepare(
                "UPDATE users 
                 SET full_name = ?, email = ?, phone = ?, gender = ?, address = ?, is_active = ?, approval_status = IF(? = 1, 'approved', approval_status), updated_at = NOW()
                 WHERE id = ? AND role = 'admin'"
            );
            $stmt->execute([$fullName, $email, $phone ?: null, $gender, $address ?: null, $isActive, $isActive, $id]);
        }

        jsonResponse(['success' => true, 'message' => 'Administrator account updated successfully.']);
    } else {
        // Create new admin directly by Superadmin
        if (empty($password) || strlen($password) < 8) {
            jsonResponse(['success' => false, 'message' => 'Password is required and must be at least 8 characters long.'], 400);
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare(
            "INSERT INTO users (full_name, email, password, role, is_superadmin, phone, gender, address, is_active, approval_status, created_at)
             VALUES (?, ?, ?, 'admin', 0, ?, ?, ?, ?, 'approved', NOW())"
        );
        $stmt->execute([$fullName, $email, $hash, $phone ?: null, $gender, $address ?: null, $isActive]);

        jsonResponse([
            'success' => true,
            'message' => 'Administrator account created successfully.',
            'id'      => (int)$pdo->lastInsertId()
        ]);
    }
}

// ─── DELETE ADMIN (Superadmin only) ──────────────────────────────────────────
if ($action === 'delete') {
    requireSuperAdmin();
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Admin ID is required.'], 400);
    }

    if ($id === (int)$_SESSION['user_id']) {
        jsonResponse(['success' => false, 'message' => 'You cannot delete your own account.'], 400);
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, full_name, is_superadmin FROM users WHERE id = ? AND role = 'admin'");
    $stmt->execute([$id]);
    $admin = $stmt->fetch();

    if (!$admin) {
        jsonResponse(['success' => false, 'message' => 'Administrator not found.'], 404);
    }
    if ((int)$admin['is_superadmin'] === 1) {
        jsonResponse(['success' => false, 'message' => 'Cannot delete the Superadmin account.'], 400);
    }

    $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'")->execute([$id]);
    jsonResponse(['success' => true, 'message' => "Administrator {$admin['full_name']} has been deleted."]);
}

jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
