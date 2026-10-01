<?php
// api/students.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/school-year.php';
require_once '../config/mailer.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─── LIST ALL STUDENTS (admin only) ────────────────────────────────────────
if ($action === 'list') {
    requireAdmin();
    $pdo  = getDB();
    $syId = activeSchoolYear($pdo);
    // Scope the enrollment join to the active school year, otherwise a student
    // enrolled across two school years is returned twice.
    $stmt = $pdo->prepare(
        "SELECT u.id, u.lrn, u.full_name, u.email, u.phone, u.guardian_name, u.guardian_phone, u.address,
                u.birthdate, u.gender, u.avatar_url, u.is_active, u.created_at,
                s.id   AS section_id, s.name AS section_name, s.grade_level,
                e.id   AS enrollment_id
         FROM users u
         LEFT JOIN enrollments e ON e.student_id = u.id AND e.school_year_id = ?
         LEFT JOIN sections s    ON s.id = e.section_id
         WHERE u.role = 'student'
         ORDER BY u.full_name"
    );
    $stmt->execute([$syId]);
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

// ─── GET ONE STUDENT ────────────────────────────────────────────────────────
if ($action === 'get') {
    requireLogin();
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

    // Students can only fetch their own profile
    if ($_SESSION['role'] === 'student' && $id !== (int)$_SESSION['user_id']) {
        jsonResponse(['success' => false, 'message' => 'Access denied.'], 403);
    }

    $pdo  = getDB();
    $syId = activeSchoolYear($pdo);
    $stmt = $pdo->prepare(
        "SELECT u.id, u.lrn, u.full_name, u.email, u.phone, u.address,
                u.birthdate, u.gender, u.guardian_name, u.guardian_phone, u.avatar_url,
                s.id AS section_id, s.name AS section_name, s.grade_level,
                e.id AS enrollment_id,
                sy.label AS school_year
         FROM users u
         LEFT JOIN enrollments  e  ON e.student_id = u.id AND e.school_year_id = ?
         LEFT JOIN sections     s  ON s.id = e.section_id
         LEFT JOIN school_years sy ON sy.id = e.school_year_id
         WHERE u.id = ?"
    );
    $stmt->execute([$syId, $id]);
    $student = $stmt->fetch();

    if (!$student) {
        jsonResponse(['success' => false, 'message' => 'Student not found.'], 404);
    }

    $missing = [];
    if (empty(trim((string)($student['lrn'] ?? '')))) {
        $missing[] = 'LRN (12-digit Learner Reference Number)';
    }
    if (empty(trim((string)($student['phone'] ?? '')))) {
        $missing[] = 'Student Contact Number';
    }
    if (empty(trim((string)($student['address'] ?? '')))) {
        $missing[] = 'Home Address';
    }
    if (empty(trim((string)($student['guardian_name'] ?? '')))) {
        $missing[] = 'Parent / Guardian Name';
    }
    if (empty(trim((string)($student['guardian_phone'] ?? '')))) {
        $missing[] = 'Parent Contact Number (for SMS Grade Alerts)';
    }

    $student['is_complete']    = count($missing) === 0;
    $student['missing_fields'] = $missing;

    jsonResponse(['success' => true, 'data' => $student]);
}

// ─── SEND SIGNUP OTP ─────────────────────────────────────────────────────────
if ($action === 'send_signup_otp') {
    $email     = strtolower(trim($_POST['email']      ?? ''));
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $lrn       = trim($_POST['lrn']        ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Valid email address is required.'], 400);
    }
    if ($lrn && !preg_match('/^\d{12}$/', $lrn)) {
        jsonResponse(['success' => false, 'message' => 'LRN must be exactly 12 digits.'], 400);
    }

    $pdo = getDB();

    // Check duplicate email
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'This email address is already registered. Please sign in or use another email.'], 409);
    }

    // Check duplicate LRN
    if ($lrn) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE lrn = ?');
        $stmt->execute([$lrn]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'This 12-digit LRN is already registered.'], 409);
        }
    }

    // Generate 6-digit OTP
    $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

    // Invalidate previous active OTPs for this email
    $pdo->prepare('UPDATE email_verifications SET used = 1 WHERE email = ? AND used = 0')->execute([$email]);

    // Insert new OTP with 10-minute expiry
    $stmt = $pdo->prepare('INSERT INTO email_verifications (email, otp, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))');
    $stmt->execute([$email, $otp]);

    $recipientName = trim("$firstName $lastName") ?: 'Learner';
    $subject = 'Your Account Verification OTP – Lubo National High School';
    $bodyHtml = "
    <div style='font-family:Arial,Helvetica,sans-serif;max-width:540px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:12px;background:#ffffff;'>
      <div style='text-align:center;padding-bottom:18px;border-bottom:2px solid #0c1326;'>
        <h2 style='color:#0c1326;margin:0;font-size:20px;letter-spacing:0.5px;'>LUBO NATIONAL HIGH SCHOOL</h2>
        <p style='color:#64748b;margin:4px 0 0;font-size:13px;'>Online Grade Monitoring System (OGMS)</p>
      </div>
      <div style='padding:24px 0;'>
        <p style='font-size:15px;color:#1e293b;margin:0 0 12px;'>Hello <strong>" . htmlspecialchars($recipientName) . "</strong>,</p>
        <p style='font-size:14px;color:#475569;line-height:1.6;margin:0 0 20px;'>
          Thank you for signing up for the Lubo NHS Online Grade Monitoring System. Please use the One-Time Password (OTP) below to verify your email address and finalize your registration:
        </p>
        <div style='background:#f1f5f9;border:1px dashed #cbd5e1;border-radius:10px;padding:20px;text-align:center;margin:0 0 20px;'>
          <div style='font-size:32px;font-weight:bold;letter-spacing:8px;color:#0284c7;'>" . htmlspecialchars($otp) . "</div>
          <div style='font-size:12px;color:#64748b;margin-top:6px;'><i class='fas fa-clock'></i> This code expires in 10 minutes</div>
        </div>
        <p style='font-size:13px;color:#94a3b8;line-height:1.5;margin:0;'>
          If you did not initiate this registration request, please disregard this email. Never share your OTP with anyone.
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
        jsonResponse(['success' => false, 'message' => 'Unable to send OTP email. Please ensure your email address is correct and try again.'], 500);
    }
}

// ─── REGISTER NEW STUDENT (public signup / admin add) ────────────────────────
if ($action === 'register') {
    $firstName     = trim($_POST['first_name']     ?? '');
    $lastName      = trim($_POST['last_name']      ?? '');
    $email         = strtolower(trim($_POST['email'] ?? ''));
    $password      = $_POST['password']            ?? '';
    $lrn           = trim($_POST['lrn']            ?? '');
    $phone         = trim($_POST['phone']          ?? '');
    $guardianName  = trim($_POST['guardian_name']  ?? '');
    $guardianPhone = trim($_POST['guardian_phone'] ?? '');
    $gender        = trim($_POST['gender']         ?? '');
    $birthdate     = trim($_POST['birthdate']      ?? '');
    $address       = trim($_POST['address']        ?? '');

    if (!$firstName || !$lastName || !$email || !$password) {
        jsonResponse(['success' => false, 'message' => 'All fields are required.'], 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Invalid email address.'], 400);
    }
    if (strlen($password) < 8) {
        jsonResponse(['success' => false, 'message' => 'Password must be at least 8 characters.'], 400);
    }
    if ($lrn && !preg_match('/^\d{12}$/', $lrn)) {
        jsonResponse(['success' => false, 'message' => 'LRN must be exactly 12 digits.'], 400);
    }
    if ($phone && !preg_match('/^09\d{9}$/', $phone)) {
        jsonResponse(['success' => false, 'message' => 'Student contact number must be an 11-digit PH mobile number starting with 09 (e.g. 09XXXXXXXXX).'], 400);
    }
    if ($guardianPhone && !preg_match('/^09\d{9}$/', $guardianPhone)) {
        jsonResponse(['success' => false, 'message' => 'Parent/Guardian contact number must be an 11-digit PH mobile number starting with 09 (e.g. 09XXXXXXXXX).'], 400);
    }

    $pdo = getDB();

    // If public signup (not admin logged in), verify OTP
    $isAdmin = !empty($_SESSION['role']) && $_SESSION['role'] === 'admin';
    if (!$isAdmin) {
        $otp = trim($_POST['otp'] ?? '');
        if (!$otp) {
            jsonResponse(['success' => false, 'message' => 'OTP verification code is required.'], 400);
        }
        $stmt = $pdo->prepare('SELECT id FROM email_verifications WHERE email = ? AND otp = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
        $stmt->execute([$email, $otp]);
        $verRow = $stmt->fetch();
        if (!$verRow) {
            jsonResponse(['success' => false, 'message' => 'Invalid or expired OTP code. Please enter the correct code or request a new one.'], 400);
        }
        // Mark OTP as used
        $pdo->prepare('UPDATE email_verifications SET used = 1 WHERE id = ?')->execute([$verRow['id']]);
    }

    // Check email duplicate
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Email is already registered.'], 409);
    }

    // Check LRN duplicate
    if ($lrn) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE lrn = ?');
        $stmt->execute([$lrn]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'LRN is already registered.'], 409);
        }
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $pdo->prepare(
        'INSERT INTO users (lrn, full_name, email, phone, guardian_name, guardian_phone, gender, birthdate, address, password, role)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $lrn ?: null, "$firstName $lastName", $email, $phone ?: null,
        $guardianName ?: null, $guardianPhone ?: null,
        $gender ?: null, $birthdate ?: null, $address ?: null,
        $hash, 'student',
    ]);

    jsonResponse([
        'success' => true,
        'message' => 'Account created successfully! You can now log in.',
        'id'      => (int)$pdo->lastInsertId(),
    ]);
}


// ─── UPDATE PROFILE ─────────────────────────────────────────────────────────
if ($action === 'update') {
    requireLogin();
    $id = (int)($_POST['id'] ?? 0);

    if ($_SESSION['role'] === 'student' && $id !== (int)$_SESSION['user_id']) {
        jsonResponse(['success' => false, 'message' => 'Access denied.'], 403);
    }

    $pdo = getDB();

    if (isset($_POST['lrn']) && trim($_POST['lrn']) !== '') {
        $lrn = trim($_POST['lrn']);
        if (!preg_match('/^\d{12}$/', $lrn)) {
            jsonResponse(['success' => false, 'message' => 'LRN must be exactly 12 digits.'], 400);
        }
        $stmt = $pdo->prepare('SELECT id FROM users WHERE lrn = ? AND id != ?');
        $stmt->execute([$lrn, $id]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'LRN is already registered to another account.'], 409);
        }
    }

    if (isset($_POST['phone']) && trim($_POST['phone']) !== '' && !preg_match('/^09\d{9}$/', trim($_POST['phone']))) {
        jsonResponse(['success' => false, 'message' => 'Student contact number must be an 11-digit PH mobile number starting with 09 (e.g. 09XXXXXXXXX).'], 400);
    }
    if (isset($_POST['guardian_phone']) && trim($_POST['guardian_phone']) !== '' && !preg_match('/^09\d{9}$/', trim($_POST['guardian_phone']))) {
        jsonResponse(['success' => false, 'message' => 'Parent/Guardian contact number must be an 11-digit PH mobile number starting with 09 (e.g. 09XXXXXXXXX).'], 400);
    }

    $allowed = ['full_name', 'lrn', 'phone', 'address', 'birthdate', 'gender', 'avatar_url', 'guardian_name', 'guardian_phone'];
    $set     = [];
    $vals    = [];

    foreach ($allowed as $field) {
        if (isset($_POST[$field])) {
            $val = trim((string)$_POST[$field]);
            $set[]  = "$field = ?";
            $vals[] = ($val === '') ? null : $val;
        }
    }

    if (empty($set)) {
        jsonResponse(['success' => false, 'message' => 'Nothing to update.'], 400);
    }

    // Handle password change
    if (!empty($_POST['new_password'])) {
        if (strlen($_POST['new_password']) < 8) {
            jsonResponse(['success' => false, 'message' => 'New password must be at least 8 characters.'], 400);
        }
        $set[]  = 'password = ?';
        $vals[] = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
    }

    $vals[] = $id;
    $pdo->prepare('UPDATE users SET ' . implode(', ', $set) . ' WHERE id = ?')
        ->execute($vals);

    // Keep active session user data updated
    if ($id === (int)($_SESSION['user_id'] ?? 0)) {
        if (!empty($_POST['full_name'])) {
            $_SESSION['full_name'] = trim($_POST['full_name']);
        }
        if (isset($_POST['lrn'])) {
            $_SESSION['lrn'] = trim($_POST['lrn']) ?: null;
        }
    }

    jsonResponse(['success' => true, 'message' => 'Profile updated.']);
}

// ─── SOFT DELETE (admin only) ───────────────────────────────────────────────
if ($action === 'delete') {
    requireAdmin();
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Student ID required.'], 400);
    }
    getDB()->prepare('UPDATE users SET is_active = 0 WHERE id = ? AND role = ?')
           ->execute([$id, 'student']);
    jsonResponse(['success' => true, 'message' => 'Student deactivated.']);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
