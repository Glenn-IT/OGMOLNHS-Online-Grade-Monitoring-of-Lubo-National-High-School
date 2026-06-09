<?php
// api/sections.php
require_once '../config/db.php';
require_once '../config/session.php';
requireAdmin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$pdo    = getDB();

// ─── LIST SECTIONS ────────────────────────────────────────────────────────────
if ($action === 'list') {
    $stmt = $pdo->query(
        "SELECT s.id, s.name, s.grade_level, s.school_year_id,
                sy.label AS school_year,
                COUNT(e.id) AS student_count
         FROM sections s
         LEFT JOIN school_years sy ON sy.id = s.school_year_id
         LEFT JOIN enrollments  e  ON e.section_id = s.id
         GROUP BY s.id
         ORDER BY s.grade_level, s.name"
    );
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

// ─── GET STUDENTS IN A SECTION ────────────────────────────────────────────────
if ($action === 'students') {
    $sectionId = (int)($_GET['section_id'] ?? 0);
    if (!$sectionId) jsonResponse(['success' => false, 'message' => 'section_id required.'], 400);

    $stmt = $pdo->prepare(
        "SELECT u.id, u.full_name, u.lrn, u.email, u.phone, u.is_active,
                e.id AS enrollment_id, e.enrolled_at
         FROM enrollments e
         JOIN users u ON u.id = e.student_id
         WHERE e.section_id = ?
         ORDER BY u.full_name"
    );
    $stmt->execute([$sectionId]);
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

// ─── SAVE SECTION (insert or update) ─────────────────────────────────────────
if ($action === 'save') {
    $id         = (int)($_POST['id']             ?? 0);
    $name       = trim($_POST['name']            ?? '');
    $gradeLevel = (int)($_POST['grade_level']    ?? 0);
    $syId       = (int)($_POST['school_year_id'] ?? 0);

    if (!$name || !$gradeLevel) {
        jsonResponse(['success' => false, 'message' => 'Section name and grade level are required.'], 400);
    }

    // Resolve active SY if not provided
    if (!$syId) {
        $sy   = $pdo->query("SELECT id FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();
        $syId = $sy ? (int)$sy['id'] : 1;
    }

    if ($id) {
        $pdo->prepare("UPDATE sections SET name=?, grade_level=?, school_year_id=? WHERE id=?")
            ->execute([$name, $gradeLevel, $syId, $id]);
        jsonResponse(['success' => true, 'message' => 'Section updated.']);
    } else {
        $pdo->prepare("INSERT INTO sections (name, grade_level, school_year_id) VALUES (?,?,?)")
            ->execute([$name, $gradeLevel, $syId]);
        jsonResponse(['success' => true, 'message' => 'Section created.', 'id' => (int)$pdo->lastInsertId()]);
    }
}

// ─── DELETE SECTION ───────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'Section ID required.'], 400);

    $count = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE section_id = ?");
    $count->execute([$id]);
    if ((int)$count->fetchColumn() > 0) {
        jsonResponse(['success' => false, 'message' => 'Cannot delete a section that has enrolled students. Remove students first.'], 409);
    }

    $pdo->prepare("DELETE FROM sections WHERE id = ?")->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'Section deleted.']);
}

// ─── ENROLL STUDENT INTO SECTION ─────────────────────────────────────────────
if ($action === 'enroll') {
    $studentId = (int)($_POST['student_id'] ?? 0);
    $sectionId = (int)($_POST['section_id'] ?? 0);
    if (!$studentId || !$sectionId) {
        jsonResponse(['success' => false, 'message' => 'student_id and section_id required.'], 400);
    }

    $sy   = $pdo->query("SELECT id FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();
    $syId = $sy ? (int)$sy['id'] : 1;

    // Update existing enrollment or insert new one (UNIQUE: student + school year)
    $stmt = $pdo->prepare(
        "INSERT INTO enrollments (student_id, section_id, school_year_id)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE section_id = VALUES(section_id)"
    );
    $stmt->execute([$studentId, $sectionId, $syId]);
    jsonResponse(['success' => true, 'message' => 'Student enrolled in section.']);
}

// ─── REMOVE STUDENT FROM SECTION ─────────────────────────────────────────────
if ($action === 'unenroll') {
    $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
    if (!$enrollmentId) jsonResponse(['success' => false, 'message' => 'enrollment_id required.'], 400);

    $pdo->prepare("DELETE FROM enrollments WHERE id = ?")->execute([$enrollmentId]);
    jsonResponse(['success' => true, 'message' => 'Student removed from section.']);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
