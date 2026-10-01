<?php
// api/grades.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/school-year.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─── LIST GRADES & SUBJECTS ──────────────────────────────────────────────────
if ($action === 'list') {
    requireLogin();
    $pdo = getDB();

    $studentId = (int)($_GET['student_id'] ?? 0);
    $subjectId = (int)($_GET['subject_id'] ?? 0);
    $sectionId = (int)($_GET['section_id'] ?? 0);
    $quarter   = (int)($_GET['quarter']    ?? 0);

    // Students can only fetch their own grades
    if ($_SESSION['role'] === 'student') {
        $studentId = (int)$_SESSION['user_id'];
    }

    $where  = ['1=1'];
    $params = [];
    $join   = '';

    $syId = activeSchoolYear($pdo);

    if ($studentId) { $where[] = 'g.student_id = ?'; $params[] = $studentId; }
    if ($subjectId) { $where[] = 'g.subject_id = ?'; $params[] = $subjectId; }
    if ($quarter)   { $where[] = 'g.quarter = ?';    $params[] = $quarter;   }
    if ($sectionId) {
        $join   .= ' JOIN enrollments e ON e.student_id = g.student_id AND e.school_year_id = g.school_year_id';
        $where[] = 'e.section_id = ?';
        $params[] = $sectionId;
    }

    if ($_SESSION['role'] === 'teacher') {
        $teacherId = (int)$_SESSION['user_id'];
        $chkTs = $pdo->prepare("SELECT COUNT(*) FROM teacher_subjects WHERE teacher_id = ? AND school_year_id = ?");
        $chkTs->execute([$teacherId, $syId]);
        $hasTs = (int)$chkTs->fetchColumn() > 0;

        if ($hasTs) {
            $join .= ' JOIN enrollments e_ts ON e_ts.student_id = g.student_id AND e_ts.school_year_id = g.school_year_id
                       JOIN sections s_ts ON s_ts.id = e_ts.section_id';
            $where[] = 'EXISTS (
                SELECT 1 FROM teacher_subjects ts 
                WHERE ts.teacher_id = ? 
                  AND ts.subject_id = g.subject_id 
                  AND ts.grade_level = s_ts.grade_level 
                  AND ts.school_year_id = g.school_year_id
            )';
            $params[] = $teacherId;
        } else {
            // Teacher can only view grades and subjects for subjects they teach
            $where[] = 'g.subject_id IN (SELECT id FROM subjects WHERE teacher_id = ?)';
            $params[] = $teacherId;
        }
    }

    $sql = "SELECT g.id, g.student_id, g.subject_id, g.quarter, g.school_year_id,
                   g.written_works, g.performance_tasks, g.quarterly_exam,
                   g.final_grade, g.remarks,
                   u.full_name AS student_name, u.lrn
            FROM grades g
            JOIN users u ON u.id = g.student_id
            $join
            WHERE " . implode(' AND ', $where) . "
            ORDER BY u.full_name, g.subject_id, g.quarter";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $grades = $stmt->fetchAll();

    $teacherAssignments = [];

    // Return subjects list with teacher details
    if ($_SESSION['role'] === 'teacher') {
        $teacherId = (int)$_SESSION['user_id'];
        $tsStmt = $pdo->prepare(
            "SELECT ts.id AS assignment_id, ts.subject_id AS id, s.name, s.code, s.level, ts.grade_level,
                    u.full_name AS teacher_name,
                    CONCAT(s.name, ' (Grade ', ts.grade_level, ')') AS display_name
             FROM teacher_subjects ts
             JOIN subjects s ON s.id = ts.subject_id
             LEFT JOIN users u ON u.id = ts.teacher_id
             WHERE ts.teacher_id = ? AND ts.school_year_id = ?
             ORDER BY s.level, s.name, ts.grade_level"
        );
        $tsStmt->execute([$teacherId, $syId]);
        $subjects = $tsStmt->fetchAll();

        if (empty($subjects)) {
            // Fallback for unmigrated teachers
            $subStmt = $pdo->prepare(
                "SELECT s.id, s.name, s.code, s.level, s.teacher_id, 7 AS grade_level, u.full_name AS teacher_name,
                        s.name AS display_name
                 FROM subjects s 
                 LEFT JOIN users u ON u.id = s.teacher_id 
                 WHERE s.teacher_id = ? 
                 ORDER BY s.level, s.name"
            );
            $subStmt->execute([$teacherId]);
            $subjects = $subStmt->fetchAll();
        }
    } else {
        $subjects = $pdo->query(
            "SELECT s.id, s.name, s.code, s.level, s.teacher_id, u.full_name AS teacher_name,
                    s.name AS display_name
             FROM subjects s 
             LEFT JOIN users u ON u.id = s.teacher_id 
             ORDER BY s.level, s.name"
        )->fetchAll();

        // Include all teacher subject assignments for admin view
        $taStmt = $pdo->prepare(
            "SELECT ts.subject_id, ts.grade_level, ts.teacher_id, u.full_name AS teacher_name
             FROM teacher_subjects ts
             JOIN users u ON u.id = ts.teacher_id
             WHERE ts.school_year_id = ?
             ORDER BY ts.subject_id, ts.grade_level"
        );
        $taStmt->execute([$syId]);
        $teacherAssignments = $taStmt->fetchAll();
    }

    jsonResponse(['success' => true, 'data' => $grades, 'subjects' => $subjects, 'teacher_assignments' => $teacherAssignments]);
}

// ─── SAVE GRADE (insert or update) ───────────────────────────────────────────
if ($action === 'save') {
    requireStaff();
    $pdo = getDB();

    $studentId  = (int)($_POST['student_id']  ?? 0);
    $subjectId  = (int)($_POST['subject_id']  ?? 0);
    $quarter    = (int)($_POST['quarter']      ?? 0);
    $syId       = (int)($_POST['school_year_id'] ?? 0);

    // Direct Term Grade Input (no written works / performance tasks / quarterly exam components needed)
    $grade = null;
    if (isset($_POST['grade']) && $_POST['grade'] !== '') {
        $grade = (float)$_POST['grade'];
    } elseif (isset($_POST['final_grade']) && $_POST['final_grade'] !== '') {
        $grade = (float)$_POST['final_grade'];
    } elseif (isset($_POST['written_works']) || isset($_POST['performance_tasks']) || isset($_POST['quarterly_exam'])) {
        $ww = (float)($_POST['written_works'] ?? 0);
        $pt = (float)($_POST['performance_tasks'] ?? 0);
        $qe = (float)($_POST['quarterly_exam'] ?? 0);
        $grade = round(($ww * 0.20) + ($pt * 0.50) + ($qe * 0.30), 2);
    }

    if (!$studentId || !$subjectId || !$quarter) {
        jsonResponse(['success' => false, 'message' => 'student_id, subject_id, and quarter are required.'], 400);
    }
    if ($quarter < 1 || $quarter > 4) {
        jsonResponse(['success' => false, 'message' => 'Quarter must be 1–4.'], 400);
    }
    if ($grade === null) {
        jsonResponse(['success' => false, 'message' => 'Quarter grade value is required.'], 400);
    }
    if ($grade < 0 || $grade > 100) {
        jsonResponse(['success' => false, 'message' => 'Grade must be between 0 and 100.'], 400);
    }

    // Resolve active school year if not provided
    if (!$syId) $syId = activeSchoolYear($pdo);

    // Teacher authorization: teacher can ONLY grade subjects & grade levels assigned to them
    if ($_SESSION['role'] === 'teacher') {
        $teacherId = (int)$_SESSION['user_id'];
        $chkTs = $pdo->prepare("SELECT COUNT(*) FROM teacher_subjects WHERE teacher_id = ? AND school_year_id = ?");
        $chkTs->execute([$teacherId, $syId]);
        if ((int)$chkTs->fetchColumn() > 0) {
            $authStmt = $pdo->prepare(
                "SELECT 1 FROM enrollments e
                 JOIN sections sec ON sec.id = e.section_id
                 JOIN teacher_subjects ts ON ts.subject_id = ? 
                                         AND ts.grade_level = sec.grade_level 
                                         AND ts.teacher_id = ? 
                                         AND ts.school_year_id = ?
                 WHERE e.student_id = ? AND e.school_year_id = ?"
            );
            $authStmt->execute([$subjectId, $teacherId, $syId, $studentId, $syId]);
            if (!$authStmt->fetch()) {
                jsonResponse(['success' => false, 'message' => 'You are only authorized to encode grades for subjects and grade levels assigned to you.'], 403);
            }
        } else {
            $subCheck = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND teacher_id = ?");
            $subCheck->execute([$subjectId, $teacherId]);
            if (!$subCheck->fetch()) {
                jsonResponse(['success' => false, 'message' => 'You are only authorized to encode grades for subjects assigned to you.'], 403);
            }
        }
    }

    $finalGrade = round($grade, 2);
    $remarks = $finalGrade >= 75 ? 'Passed' : 'Failed';

    // Upsert using UNIQUE KEY (student_id, subject_id, quarter, school_year_id)
    $stmt = $pdo->prepare(
        "INSERT INTO grades (student_id, subject_id, quarter, school_year_id,
                             final_grade, remarks, encoded_by)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
             final_grade        = VALUES(final_grade),
             remarks            = VALUES(remarks),
             encoded_by         = VALUES(encoded_by)"
    );
    $stmt->execute([$studentId, $subjectId, $quarter, $syId, $finalGrade, $remarks, $_SESSION['user_id']]);

    jsonResponse(['success' => true, 'message' => 'Grade saved.', 'final_grade' => $finalGrade, 'remarks' => $remarks]);
}

// ─── DELETE GRADE (admin or teacher) ──────────────────────────────────────────
if ($action === 'delete') {
    requireStaff();
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Grade ID required.'], 400);
    }
    $pdo = getDB();

    if ($_SESSION['role'] === 'teacher') {
        $teacherId = (int)$_SESSION['user_id'];
        $chk = $pdo->prepare(
            "SELECT 1 FROM grades g
             JOIN enrollments e ON e.student_id = g.student_id AND e.school_year_id = g.school_year_id
             JOIN sections sec ON sec.id = e.section_id
             JOIN teacher_subjects ts ON ts.subject_id = g.subject_id 
                                     AND ts.grade_level = sec.grade_level 
                                     AND ts.teacher_id = ? 
                                     AND ts.school_year_id = g.school_year_id
             WHERE g.id = ?"
        );
        $chk->execute([$teacherId, $id]);
        if (!$chk->fetch()) {
            $legacyChk = $pdo->prepare(
                "SELECT 1 FROM grades g
                 JOIN subjects s ON s.id = g.subject_id
                 WHERE g.id = ? AND s.teacher_id = ?"
            );
            $legacyChk->execute([$id, $teacherId]);
            if (!$legacyChk->fetch()) {
                jsonResponse(['success' => false, 'message' => 'Unauthorized to delete this grade.'], 403);
            }
        }
    }

    $pdo->prepare('DELETE FROM grades WHERE id = ?')->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'Grade deleted.']);
}

// ─── ADD SUBJECT (admin only) ────────────────────────────────────────────────
if ($action === 'add_subject') {
    requireAdmin();
    $name  = trim($_POST['name'] ?? '');
    $code  = strtoupper(trim($_POST['code'] ?? ''));
    $level = in_array($_POST['level'] ?? '', ['JHS', 'SHS']) ? $_POST['level'] : 'JHS';
    $teacherId = !empty($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : null;

    if (!$name || !$code) {
        jsonResponse(['success' => false, 'message' => 'Subject name and code are required.'], 400);
    }

    $pdo  = getDB();
    $chk = $pdo->prepare("SELECT id FROM subjects WHERE code = ? OR name = ?");
    $chk->execute([$code, $name]);
    if ($chk->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Subject or code already exists.'], 409);
    }

    if ($teacherId) {
        $tChk = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'teacher'");
        $tChk->execute([$teacherId]);
        if (!$tChk->fetch()) $teacherId = null;
    }

    $stmt = $pdo->prepare("INSERT INTO subjects (name, code, level, teacher_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $code, $level, $teacherId]);

    jsonResponse(['success' => true, 'message' => 'Subject created successfully.', 'id' => (int)$pdo->lastInsertId()]);
}

// ─── UPDATE SUBJECT (admin only) ─────────────────────────────────────────────
if ($action === 'update_subject') {
    requireAdmin();
    $id    = (int)($_POST['id'] ?? 0);
    $name  = trim($_POST['name'] ?? '');
    $code  = strtoupper(trim($_POST['code'] ?? ''));
    $level = in_array($_POST['level'] ?? '', ['JHS', 'SHS']) ? $_POST['level'] : 'JHS';
    $teacherId = isset($_POST['teacher_id']) && $_POST['teacher_id'] !== '' ? (int)$_POST['teacher_id'] : null;

    if (!$id || !$name || !$code) {
        jsonResponse(['success' => false, 'message' => 'Subject ID, name, and code are required.'], 400);
    }

    $pdo = getDB();
    $chk = $pdo->prepare("SELECT id FROM subjects WHERE (code = ? OR name = ?) AND id != ?");
    $chk->execute([$code, $name, $id]);
    if ($chk->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Another subject with this name or code already exists.'], 409);
    }

    if ($teacherId) {
        $tChk = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'teacher'");
        $tChk->execute([$teacherId]);
        if (!$tChk->fetch()) $teacherId = null;
    }

    $stmt = $pdo->prepare("UPDATE subjects SET name = ?, code = ?, level = ?, teacher_id = ? WHERE id = ?");
    $stmt->execute([$name, $code, $level, $teacherId ?: null, $id]);

    jsonResponse(['success' => true, 'message' => 'Subject updated successfully.']);
}

// ─── DELETE SUBJECT (admin only) ─────────────────────────────────────────────
if ($action === 'delete_subject') {
    requireAdmin();
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Subject ID required.'], 400);
    }
    $pdo = getDB();
    $pdo->prepare("DELETE FROM grades WHERE subject_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM teacher_subjects WHERE subject_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM subjects WHERE id = ?")->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'Subject deleted successfully.']);
}

// ─── RESTORE DEFAULT SUBJECTS (admin only) ───────────────────────────────────
if ($action === 'restore_subjects') {
    requireAdmin();
    $pdo = getDB();

    $defaultJhs = [
        ['Araling Panlipunan', 'AP'],
        ['Mathematics',        'MATH'],
        ['Science',            'SCI'],
        ['English',            'ENG'],
        ['Filipino',           'FIL'],
        ['MAPEH',              'MAPEH'],
        ['TLE',                'TLE'],
        ['Values Education',   'VE']
    ];

    $defaultShs = [
        ['Oral Communication in Context', 'OCC'],
        ['Reading and Writing Skills', 'RWS'],
        ['Komunikasyon at Pananaliksik sa Wika at Kulturang Pilipino', 'KPWKP'],
        ['21st Century Literature from the Philippines and the World', '21CLPW'],
        ['Contemporary Philippine Arts from the Regions', 'CPAR'],
        ['Media and Information Literacy', 'MIL'],
        ['General Mathematics', 'GENMATH'],
        ['Statistics and Probability', 'STATPROB'],
        ['Earth and Life Science', 'ELS'],
        ['Physical Science', 'PHYSCI'],
        ['Personal Development', 'PERDEV'],
        ['Understanding Culture, Society, and Politics', 'UCSP'],
        ['Physical Education and Health', 'PEH'],
        ['English for Academic and Professional Purposes', 'EAPP'],
        ['Practical Research 1', 'PR1'],
        ['Practical Research 2', 'PR2'],
        ['Empowerment Technologies', 'EMPTECH'],
        ['Entrepreneurship', 'ENTREP'],
        ['Inquiries, Investigations and Immersion', 'III']
    ];

    foreach ($defaultJhs as [$name, $code]) {
        $stmt = $pdo->prepare("SELECT id FROM subjects WHERE code = ? OR name = ?");
        $stmt->execute([$code, $name]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO subjects (name, code, level) VALUES (?, ?, 'JHS')")->execute([$name, $code]);
        } else {
            $pdo->prepare("UPDATE subjects SET level = 'JHS' WHERE code = ? OR name = ?")->execute([$code, $name]);
        }
    }

    foreach ($defaultShs as [$name, $code]) {
        $stmt = $pdo->prepare("SELECT id FROM subjects WHERE code = ? OR name = ?");
        $stmt->execute([$code, $name]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO subjects (name, code, level) VALUES (?, ?, 'SHS')")->execute([$name, $code]);
        } else {
            $pdo->prepare("UPDATE subjects SET level = 'SHS' WHERE code = ? OR name = ?")->execute([$code, $name]);
        }
    }

    jsonResponse(['success' => true, 'message' => 'Default JHS and SHS core subjects verified & restored.']);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
