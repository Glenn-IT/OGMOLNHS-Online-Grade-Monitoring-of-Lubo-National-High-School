<?php
// api/teachers.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/school-year.php';
require_once '../config/mailer.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─── LIST ALL TEACHERS (Admin only) ──────────────────────────────────────────
// Helper function to format assignments into a readable string
function formatAssignmentsString(array $assignments) {
    if (empty($assignments)) return '';
    $bySub = [];
    foreach ($assignments as $a) {
        $subKey = $a['subject_name'] . ($a['subject_code'] ? ' (' . $a['subject_code'] . ')' : '');
        $bySub[$subKey][] = 'Gr. ' . $a['grade_level'];
    }
    $parts = [];
    foreach ($bySub as $subName => $grades) {
        $parts[] = $subName . ' [' . implode(', ', $grades) . ']';
    }
    return implode(', ', $parts);
}

// ─── LIST ALL TEACHERS (Admin only) ──────────────────────────────────────────
if ($action === 'list') {
    requireAdmin();
    $pdo  = getDB();
    $syId = activeSchoolYear($pdo);

    // 1. Fetch teachers
    $stmt = $pdo->query(
        "SELECT u.id, u.full_name, u.email, u.phone, u.gender, u.address, u.avatar_url, u.is_active, u.approval_status, u.created_at
         FROM users u
         WHERE u.role = 'teacher'
         ORDER BY u.full_name ASC"
    );
    $teachers = $stmt->fetchAll();

    // 2. Fetch all teaching assignments in active school year (including teacher_name for duplicate checking)
    $tsStmt = $pdo->prepare(
        "SELECT ts.teacher_id, ts.subject_id, ts.grade_level, sub.name AS subject_name, sub.code AS subject_code, sub.level AS subject_level, u.full_name AS teacher_name
         FROM teacher_subjects ts
         JOIN subjects sub ON sub.id = ts.subject_id
         JOIN users u ON u.id = ts.teacher_id
         WHERE ts.school_year_id = ?
         ORDER BY sub.name, ts.grade_level"
    );
    $tsStmt->execute([$syId]);
    $allAssignments = $tsStmt->fetchAll();

    $assignmentsByTeacher = [];
    foreach ($allAssignments as $row) {
        $assignmentsByTeacher[$row['teacher_id']][] = [
            'subject_id'   => (int)$row['subject_id'],
            'grade_level'  => (int)$row['grade_level'],
            'subject_name' => $row['subject_name'],
            'subject_code' => $row['subject_code']
        ];
    }

    // 3. Fallback for subjects.teacher_id if teacher_subjects is empty for a teacher
    $legacyStmt = $pdo->query(
        "SELECT s.id AS subject_id, s.teacher_id, s.name AS subject_name, s.code AS subject_code 
         FROM subjects s WHERE s.teacher_id IS NOT NULL"
    );
    $legacySubjects = $legacyStmt->fetchAll();
    $legacyByTeacher = [];
    foreach ($legacySubjects as $ls) {
        $legacyByTeacher[$ls['teacher_id']][] = $ls;
    }

    // Format output for each teacher
    foreach ($teachers as &$t) {
        $tId = (int)$t['id'];

        if (isset($assignmentsByTeacher[$tId])) {
            $t['teaching_assignments'] = $assignmentsByTeacher[$tId];
            $t['assigned_subjects']    = formatAssignmentsString($assignmentsByTeacher[$tId]);
            $distinctIds = array_values(array_unique(array_column($assignmentsByTeacher[$tId], 'subject_id')));
            $t['assigned_subject_ids'] = implode(',', $distinctIds);
        } elseif (isset($legacyByTeacher[$tId])) {
            // Legacy fallback
            $t['teaching_assignments'] = [];
            $subStrings = [];
            $ids = [];
            foreach ($legacyByTeacher[$tId] as $ls) {
                $subStrings[] = $ls['subject_name'] . ' (' . $ls['subject_code'] . ')';
                $ids[] = $ls['subject_id'];
            }
            $t['assigned_subjects']    = implode(', ', $subStrings);
            $t['assigned_subject_ids'] = implode(',', $ids);
        } else {
            $t['teaching_assignments'] = [];
            $t['assigned_subjects']    = '';
            $t['assigned_subject_ids'] = '';
        }
    }
    unset($t);

    jsonResponse([
        'success'         => true,
        'data'            => $teachers,
        'all_assignments' => $allAssignments
    ]);
}

// ─── GET SINGLE TEACHER ──────────────────────────────────────────────────────
if ($action === 'get') {
    requireLogin();
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

    if ($_SESSION['role'] === 'teacher' && $id !== (int)$_SESSION['user_id']) {
        jsonResponse(['success' => false, 'message' => 'Access denied.'], 403);
    }
    if ($_SESSION['role'] === 'student') {
        jsonResponse(['success' => false, 'message' => 'Access denied.'], 403);
    }

    $pdo  = getDB();
    $syId = activeSchoolYear($pdo);

    $stmt = $pdo->prepare(
        "SELECT u.id, u.full_name, u.email, u.phone, u.gender, u.address, u.is_active, u.approval_status, u.created_at
         FROM users u
         WHERE u.id = ? AND u.role = 'teacher'"
    );
    $stmt->execute([$id]);
    $teacher = $stmt->fetch();

    if (!$teacher) {
        jsonResponse(['success' => false, 'message' => 'Teacher not found.'], 404);
    }

    // Fetch teaching assignments
    $tsStmt = $pdo->prepare(
        "SELECT ts.subject_id, ts.grade_level, sub.name AS subject_name, sub.code AS subject_code
         FROM teacher_subjects ts
         JOIN subjects sub ON sub.id = ts.subject_id
         WHERE ts.teacher_id = ? AND ts.school_year_id = ?
         ORDER BY sub.name, ts.grade_level"
    );
    $tsStmt->execute([$id, $syId]);
    $assignments = $tsStmt->fetchAll();

    if (!empty($assignments)) {
        $teacher['teaching_assignments'] = $assignments;
        $teacher['assigned_subjects']    = formatAssignmentsString($assignments);
        $teacher['assigned_subject_ids'] = implode(',', array_values(array_unique(array_column($assignments, 'subject_id'))));
    } else {
        // Fallback to subjects.teacher_id
        $legStmt = $pdo->prepare("SELECT id AS subject_id, name AS subject_name, code AS subject_code FROM subjects WHERE teacher_id = ?");
        $legStmt->execute([$id]);
        $leg = $legStmt->fetchAll();
        $teacher['teaching_assignments'] = [];
        $teacher['assigned_subjects']    = implode(', ', array_map(fn($s) => $s['subject_name'] . ' (' . $s['subject_code'] . ')', $leg));
        $teacher['assigned_subject_ids'] = implode(',', array_column($leg, 'subject_id'));
    }

    jsonResponse(['success' => true, 'data' => $teacher]);
}

// ─── SAVE TEACHER (Create or Edit Credentials / Profile) ───────────────────────
if ($action === 'save') {
    requireAdmin();
    $pdo = getDB();
    $syId = activeSchoolYear($pdo);

    $id        = (int)($_POST['id'] ?? 0);
    $fullName  = trim($_POST['full_name'] ?? '');
    $email     = strtolower(trim($_POST['email'] ?? ''));
    $password  = $_POST['password'] ?? '';
    $phone     = trim($_POST['phone'] ?? '');
    $gender    = trim($_POST['gender'] ?? 'Other');
    $address   = trim($_POST['address'] ?? '');
    $isActive  = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
    $sectionId = isset($_POST['section_id']) && $_POST['section_id'] !== '' ? (int)$_POST['section_id'] : null;

    // Support separate first_name & last_name if submitted
    if (!$fullName && (isset($_POST['first_name']) || isset($_POST['last_name']))) {
        $fullName = trim(($_POST['first_name'] ?? '') . ' ' . ($_POST['last_name'] ?? ''));
    }

    if (!$fullName) {
        jsonResponse(['success' => false, 'message' => 'Teacher full name is required.'], 400);
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
        jsonResponse(['success' => false, 'message' => 'This email address is already in use by another account.'], 400);
    }

    $pdo->beginTransaction();
    try {
        if ($id > 0) {
            // Check teacher exists
            $check = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'teacher'");
            $check->execute([$id]);
            if (!$check->fetch()) {
                $pdo->rollBack();
                jsonResponse(['success' => false, 'message' => 'Teacher not found.'], 404);
            }

            // If new password provided, update credentials
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    $pdo->rollBack();
                    jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters long.'], 400);
                }
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare(
                    "UPDATE users 
                     SET full_name = ?, email = ?, password = ?, phone = ?, gender = ?, address = ?, is_active = ?, approval_status = IF(? = 1, 'approved', approval_status), updated_at = NOW()
                     WHERE id = ? AND role = 'teacher'"
                );
                $stmt->execute([$fullName, $email, $hashed, $phone ?: null, $gender, $address ?: null, $isActive, $isActive, $id]);
            } else {
                // Update profile without changing password
                $stmt = $pdo->prepare(
                    "UPDATE users 
                     SET full_name = ?, email = ?, phone = ?, gender = ?, address = ?, is_active = ?, approval_status = IF(? = 1, 'approved', approval_status), updated_at = NOW()
                     WHERE id = ? AND role = 'teacher'"
                );
                $stmt->execute([$fullName, $email, $phone ?: null, $gender, $address ?: null, $isActive, $isActive, $id]);
            }

            $teacherId = $id;
            $msg = 'Teacher account and credentials updated successfully.';
        } else {
            // New Teacher Registration by Admin
            if (empty($password)) {
                $pdo->rollBack();
                jsonResponse(['success' => false, 'message' => 'Initial password is required for registering a teacher.'], 400);
            }
            if (strlen($password) < 6) {
                $pdo->rollBack();
                jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters long.'], 400);
            }

            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare(
                "INSERT INTO users (full_name, email, password, role, phone, gender, address, is_active, approval_status, created_at)
                 VALUES (?, ?, ?, 'teacher', ?, ?, ?, ?, 'approved', NOW())"
            );
            $stmt->execute([$fullName, $email, $hashed, $phone ?: null, $gender, $address ?: null, $isActive]);
            $teacherId = (int)$pdo->lastInsertId();
            $msg = 'Teacher registered successfully.';
        }

        // Handle teaching subjects assignment (supports subject + grade_level pairs)
        if (isset($_POST['teaching_assignments'])) {
            $rawAssignments = json_decode($_POST['teaching_assignments'], true);
            // Clear existing in teacher_subjects for this teacher in active SY
            $pdo->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ? AND school_year_id = ?")->execute([$teacherId, $syId]);
            $insTs = $pdo->prepare("INSERT IGNORE INTO teacher_subjects (teacher_id, subject_id, grade_level, school_year_id) VALUES (?, ?, ?, ?)");

            $distinctSubjectIds = [];
            if (is_array($rawAssignments)) {
                foreach ($rawAssignments as $item) {
                    $subId = (int)($item['subject_id'] ?? 0);
                    $gl    = (int)($item['grade_level'] ?? 0);
                    if ($subId > 0 && $gl >= 7 && $gl <= 12) {
                        $insTs->execute([$teacherId, $subId, $gl, $syId]);
                        $distinctSubjectIds[$subId] = true;
                    }
                }
            }

            // Sync subjects.teacher_id for backward compatibility
            $pdo->prepare("UPDATE subjects SET teacher_id = NULL WHERE teacher_id = ?")->execute([$teacherId]);
            if (!empty($distinctSubjectIds)) {
                $ids = array_keys($distinctSubjectIds);
                $in = implode(',', array_fill(0, count($ids), '?'));
                $setSub = $pdo->prepare("UPDATE subjects SET teacher_id = ? WHERE id IN ($in)");
                $setSub->execute(array_merge([$teacherId], $ids));
            }
        } elseif (isset($_POST['subject_ids'])) {
            $rawSubjects = is_array($_POST['subject_ids']) ? $_POST['subject_ids'] : explode(',', (string)$_POST['subject_ids']);
            $subjectIds = array_filter(array_map('intval', $rawSubjects));

            // Clear any subjects previously assigned to this teacher
            $pdo->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ? AND school_year_id = ?")->execute([$teacherId, $syId]);
            $pdo->prepare("UPDATE subjects SET teacher_id = NULL WHERE teacher_id = ?")->execute([$teacherId]);

            // Assign selected subjects (default to Grade 7 if not specified)
            if (!empty($subjectIds)) {
                $insTs = $pdo->prepare("INSERT IGNORE INTO teacher_subjects (teacher_id, subject_id, grade_level, school_year_id) VALUES (?, ?, ?, ?)");
                foreach ($subjectIds as $sId) {
                    $insTs->execute([$teacherId, $sId, 7, $syId]);
                }
                $in = implode(',', array_fill(0, count($subjectIds), '?'));
                $setSub = $pdo->prepare("UPDATE subjects SET teacher_id = ? WHERE id IN ($in)");
                $setSub->execute(array_merge([$teacherId], $subjectIds));
            }
        }

        $pdo->commit();
        jsonResponse(['success' => true, 'message' => $msg, 'teacher_id' => $teacherId]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
    }
}

// ─── TOGGLE TEACHER ACTIVE STATUS ────────────────────────────────────────────
if ($action === 'toggle_status') {
    requireAdmin();
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Teacher ID is required.'], 400);
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, is_active, full_name FROM users WHERE id = ? AND role = 'teacher'");
    $stmt->execute([$id]);
    $teacher = $stmt->fetch();

    if (!$teacher) {
        jsonResponse(['success' => false, 'message' => 'Teacher not found.'], 404);
    }

    $newStatus = $teacher['is_active'] ? 0 : 1;
    $update = $pdo->prepare("UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?");
    $update->execute([$newStatus, $id]);

    $statusLabel = $newStatus ? 'activated' : 'deactivated';
    jsonResponse(['success' => true, 'message' => "Teacher {$teacher['full_name']} has been {$statusLabel}.", 'is_active' => $newStatus]);
}

// ─── DELETE TEACHER ──────────────────────────────────────────────────────────
if ($action === 'delete') {
    requireAdmin();
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Teacher ID is required.'], 400);
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ? AND role = 'teacher'");
    $stmt->execute([$id]);
    $teacher = $stmt->fetch();

    if (!$teacher) {
        jsonResponse(['success' => false, 'message' => 'Teacher not found.'], 404);
    }

    $pdo->beginTransaction();
    try {
        // Unassign as adviser
        $pdo->prepare("UPDATE sections SET adviser_id = NULL WHERE adviser_id = ?")->execute([$id]);
        // Unassign from teacher_subjects
        $pdo->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ?")->execute([$id]);
        // Unassign from subjects
        $pdo->prepare("UPDATE subjects SET teacher_id = NULL WHERE teacher_id = ?")->execute([$id]);
        // Delete user record
        $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'teacher'")->execute([$id]);

        $pdo->commit();
        jsonResponse(['success' => true, 'message' => "Teacher {$teacher['full_name']} deleted successfully."]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Failed to delete teacher: ' . $e->getMessage()], 500);
    }
}

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

    $recipientName = trim("$firstName $lastName") ?: 'Faculty Applicant';
    $subject = 'Teacher Registration OTP Verification – Lubo National High School';
    $bodyHtml = "
    <div style='font-family:Arial,Helvetica,sans-serif;max-width:540px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:12px;background:#ffffff;'>
      <div style='text-align:center;padding-bottom:18px;border-bottom:2px solid #0c1326;'>
        <h2 style='color:#0c1326;margin:0;font-size:20px;letter-spacing:0.5px;'>LUBO NATIONAL HIGH SCHOOL</h2>
        <p style='color:#64748b;margin:4px 0 0;font-size:13px;'>Online Grade Monitoring System (OGMS) &bull; Faculty Portal</p>
      </div>
      <div style='padding:24px 0;'>
        <p style='font-size:15px;color:#1e293b;margin:0 0 12px;'>Hello <strong>Teacher " . htmlspecialchars($recipientName) . "</strong>,</p>
        <p style='font-size:14px;color:#475569;line-height:1.6;margin:0 0 20px;'>
          Thank you for registering as a faculty member for the Lubo NHS Online Grade Monitoring System. Please use the One-Time Password (OTP) below to verify your email address and submit your registration:
        </p>
        <div style='background:#f0f9ff;border:1px dashed #0284c7;border-radius:10px;padding:20px;text-align:center;margin:0 0 20px;'>
          <div style='font-size:32px;font-weight:bold;letter-spacing:8px;color:#0284c7;'>" . htmlspecialchars($otp) . "</div>
          <div style='font-size:12px;color:#64748b;margin-top:6px;'>This code expires in 10 minutes</div>
        </div>
        <div style='background:#fffbeb;border-left:4px solid #f59e0b;padding:12px;margin:0 0 20px;border-radius:4px;'>
          <p style='font-size:13px;color:#92400e;margin:0;'>
            <strong>Important:</strong> After email verification, your teacher account will be queued for review and requires administrator approval before you can sign in.
          </p>
        </div>
        <p style='font-size:13px;color:#94a3b8;line-height:1.5;margin:0;'>
          If you did not initiate this registration, please disregard this email. Never share your OTP with anyone.
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

// ─── REGISTER NEW TEACHER (Public) ───────────────────────────────────────────
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
    if (strlen($password) < 6) {
        jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters.'], 400);
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

    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, password, role, phone, gender, address, is_active, approval_status, created_at)
         VALUES (?, ?, ?, 'teacher', ?, ?, ?, 0, 'pending', NOW())"
    );
    $stmt->execute([$fullName, $email, $hash, $phone ?: null, $gender, $address ?: null]);

    jsonResponse([
        'success' => true,
        'message' => 'Your teacher registration was submitted successfully! It is now waiting for the administrator to approve your account before you can log in.',
        'id'      => (int)$pdo->lastInsertId(),
    ]);
}

// ─── APPROVE TEACHER REGISTRATION (Admin only) ──────────────────────────────
if ($action === 'approve') {
    requireAdmin();
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Teacher ID is required.'], 400);
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, full_name, email, approval_status FROM users WHERE id = ? AND role = 'teacher'");
    $stmt->execute([$id]);
    $teacher = $stmt->fetch();

    if (!$teacher) {
        jsonResponse(['success' => false, 'message' => 'Teacher not found.'], 404);
    }

    $update = $pdo->prepare("UPDATE users SET is_active = 1, approval_status = 'approved', updated_at = NOW() WHERE id = ?");
    $update->execute([$id]);

    // Send confirmation email
    $subject = 'Teacher Account Approved – Lubo National High School';
    $bodyHtml = "
    <div style='font-family:Arial,Helvetica,sans-serif;max-width:540px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:12px;background:#ffffff;'>
      <div style='text-align:center;padding-bottom:18px;border-bottom:2px solid #0c1326;'>
        <h2 style='color:#0c1326;margin:0;font-size:20px;letter-spacing:0.5px;'>LUBO NATIONAL HIGH SCHOOL</h2>
        <p style='color:#64748b;margin:4px 0 0;font-size:13px;'>Online Grade Monitoring System (OGMS) &bull; Faculty Portal</p>
      </div>
      <div style='padding:24px 0;'>
        <p style='font-size:15px;color:#1e293b;margin:0 0 12px;'>Hello <strong>Teacher " . htmlspecialchars($teacher['full_name']) . "</strong>,</p>
        <p style='font-size:14px;color:#475569;line-height:1.6;margin:0 0 20px;'>
          Great news! Your teacher account has been <strong>approved and activated</strong> by the school administration.
        </p>
        <div style='background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px;margin:0 0 20px;text-align:center;'>
          <p style='margin:0;color:#166534;font-size:14px;font-weight:600;'>
            <i class='fas fa-check-circle me-1'></i> Your account is now active and ready for use.
          </p>
        </div>
        <p style='font-size:14px;color:#475569;line-height:1.6;margin:0 0 20px;'>
          You may now sign in using your registered email (<code>" . htmlspecialchars($teacher['email']) . "</code>) and password on the OGMS Portal.
        </p>
      </div>
      <div style='border-top:1px solid #e2e8f0;padding-top:16px;text-align:center;color:#94a3b8;font-size:11px;'>
        &copy; " . date('Y') . " Lubo National High School &bull; DepEd Philippines
      </div>
    </div>";

    @sendMail($teacher['email'], $teacher['full_name'], $subject, $bodyHtml);

    jsonResponse([
        'success' => true,
        'message' => "Teacher {$teacher['full_name']} has been approved and activated.",
        'is_active' => 1,
        'approval_status' => 'approved'
    ]);
}

// ─── REJECT TEACHER REGISTRATION (Admin only) ──────────────────────────────
if ($action === 'reject') {
    requireAdmin();
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Teacher ID is required.'], 400);
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE id = ? AND role = 'teacher'");
    $stmt->execute([$id]);
    $teacher = $stmt->fetch();

    if (!$teacher) {
        jsonResponse(['success' => false, 'message' => 'Teacher not found.'], 404);
    }

    $update = $pdo->prepare("UPDATE users SET is_active = 0, approval_status = 'rejected', updated_at = NOW() WHERE id = ?");
    $update->execute([$id]);

    jsonResponse([
        'success' => true,
        'message' => "Teacher {$teacher['full_name']} registration was rejected.",
        'is_active' => 0,
        'approval_status' => 'rejected'
    ]);
}

jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
