<?php
// api/teachers.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/school-year.php';

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

    // 1. Fetch teachers and active advisory section
    $stmt = $pdo->prepare(
        "SELECT u.id, u.full_name, u.email, u.phone, u.gender, u.address, u.avatar_url, u.is_active, u.created_at,
                s.id AS section_id, s.name AS section_name, s.grade_level AS section_grade_level
         FROM users u
         LEFT JOIN sections s ON s.adviser_id = u.id AND s.school_year_id = ?
         WHERE u.role = 'teacher'
         ORDER BY u.full_name ASC"
    );
    $stmt->execute([$syId]);
    $teachers = $stmt->fetchAll();

    // 2. Fetch all teaching assignments in active school year
    $tsStmt = $pdo->prepare(
        "SELECT ts.teacher_id, ts.subject_id, ts.grade_level, sub.name AS subject_name, sub.code AS subject_code
         FROM teacher_subjects ts
         JOIN subjects sub ON sub.id = ts.subject_id
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
        $t['grade_level'] = $t['section_grade_level']; // keep compatibility with existing UI

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

    jsonResponse(['success' => true, 'data' => $teachers]);
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
        "SELECT u.id, u.full_name, u.email, u.phone, u.gender, u.address, u.is_active, u.created_at,
                s.id AS section_id, s.name AS section_name, s.grade_level
         FROM users u
         LEFT JOIN sections s ON s.adviser_id = u.id AND s.school_year_id = ?
         WHERE u.id = ? AND u.role = 'teacher'"
    );
    $stmt->execute([$syId, $id]);
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
                     SET full_name = ?, email = ?, password = ?, phone = ?, gender = ?, address = ?, is_active = ?, updated_at = NOW()
                     WHERE id = ? AND role = 'teacher'"
                );
                $stmt->execute([$fullName, $email, $hashed, $phone ?: null, $gender, $address ?: null, $isActive, $id]);
            } else {
                // Update profile without changing password
                $stmt = $pdo->prepare(
                    "UPDATE users 
                     SET full_name = ?, email = ?, phone = ?, gender = ?, address = ?, is_active = ?, updated_at = NOW()
                     WHERE id = ? AND role = 'teacher'"
                );
                $stmt->execute([$fullName, $email, $phone ?: null, $gender, $address ?: null, $isActive, $id]);
            }

            $teacherId = $id;
            $msg = 'Teacher account and credentials updated successfully.';
        } else {
            // New Teacher Registration
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
                "INSERT INTO users (full_name, email, password, role, phone, gender, address, is_active, created_at)
                 VALUES (?, ?, ?, 'teacher', ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([$fullName, $email, $hashed, $phone ?: null, $gender, $address ?: null, $isActive]);
            $teacherId = (int)$pdo->lastInsertId();
            $msg = 'Teacher registered successfully.';
        }

        // Handle section advisory assignment
        // 1. Clear any sections currently advised by this teacher in the active school year
        $clearStmt = $pdo->prepare("UPDATE sections SET adviser_id = NULL WHERE adviser_id = ? AND school_year_id = ?");
        $clearStmt->execute([$teacherId, $syId]);

        // 2. If a section was selected, assign this teacher as its adviser
        if ($sectionId && $sectionId > 0) {
            $setStmt = $pdo->prepare("UPDATE sections SET adviser_id = ? WHERE id = ? AND school_year_id = ?");
            $setStmt->execute([$teacherId, $sectionId, $syId]);
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

jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
