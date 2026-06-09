<?php
// api/grades.php
require_once '../config/db.php';
require_once '../config/session.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─── LIST GRADES ─────────────────────────────────────────────────────────────
if ($action === 'list') {
    requireLogin();
    $pdo = getDB();

    $studentId = (int)($_GET['student_id'] ?? 0);
    $subjectId = (int)($_GET['subject_id'] ?? 0);
    $quarter   = (int)($_GET['quarter']    ?? 0);

    // Students can only fetch their own grades
    if ($_SESSION['role'] === 'student') {
        $studentId = (int)$_SESSION['user_id'];
    }

    $where  = ['1=1'];
    $params = [];

    if ($studentId) { $where[] = 'g.student_id = ?'; $params[] = $studentId; }
    if ($subjectId) { $where[] = 'g.subject_id = ?'; $params[] = $subjectId; }
    if ($quarter)   { $where[] = 'g.quarter = ?';    $params[] = $quarter;   }

    $sql = "SELECT g.id, g.student_id, g.subject_id, g.quarter, g.school_year_id,
                   g.written_works, g.performance_tasks, g.quarterly_exam,
                   g.final_grade, g.remarks,
                   u.full_name AS student_name, u.lrn
            FROM grades g
            JOIN users    u  ON u.id = g.student_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY u.full_name, g.subject_id, g.quarter";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $grades = $stmt->fetchAll();

    // Also return subjects list for client convenience
    $subjects = $pdo->query("SELECT id, name, code FROM subjects ORDER BY name")
                    ->fetchAll();

    jsonResponse(['success' => true, 'data' => $grades, 'subjects' => $subjects]);
}

// ─── SAVE GRADE (insert or update) ───────────────────────────────────────────
if ($action === 'save') {
    requireAdmin();
    $pdo = getDB();

    $studentId  = (int)($_POST['student_id']  ?? 0);
    $subjectId  = (int)($_POST['subject_id']  ?? 0);
    $quarter    = (int)($_POST['quarter']      ?? 0);
    $syId       = (int)($_POST['school_year_id'] ?? 0);
    $ww         = isset($_POST['written_works'])      ? (float)$_POST['written_works']      : null;
    $pt         = isset($_POST['performance_tasks'])  ? (float)$_POST['performance_tasks']  : null;
    $qe         = isset($_POST['quarterly_exam'])     ? (float)$_POST['quarterly_exam']     : null;

    if (!$studentId || !$subjectId || !$quarter) {
        jsonResponse(['success' => false, 'message' => 'student_id, subject_id, and quarter are required.'], 400);
    }
    if ($quarter < 1 || $quarter > 4) {
        jsonResponse(['success' => false, 'message' => 'Quarter must be 1–4.'], 400);
    }
    foreach ([$ww, $pt, $qe] as $v) {
        if ($v !== null && ($v < 0 || $v > 100)) {
            jsonResponse(['success' => false, 'message' => 'Grades must be between 0 and 100.'], 400);
        }
    }

    // Compute final grade: WW(20%) + PT(60%) + QE(20%)
    $finalGrade = null;
    if ($ww !== null && $pt !== null && $qe !== null) {
        $finalGrade = round(($ww * 0.20) + ($pt * 0.60) + ($qe * 0.20), 2);
    }

    $remarks = null;
    if ($finalGrade !== null) {
        $remarks = $finalGrade >= 75 ? 'Passed' : 'Failed';
    }

    // Resolve active school year if not provided
    if (!$syId) {
        $sy = $pdo->query("SELECT id FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();
        $syId = $sy ? (int)$sy['id'] : 1;
    }

    // Upsert using UNIQUE KEY (student_id, subject_id, quarter, school_year_id)
    $stmt = $pdo->prepare(
        "INSERT INTO grades (student_id, subject_id, quarter, school_year_id,
                             written_works, performance_tasks, quarterly_exam, final_grade, remarks)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
             written_works      = VALUES(written_works),
             performance_tasks  = VALUES(performance_tasks),
             quarterly_exam     = VALUES(quarterly_exam),
             final_grade        = VALUES(final_grade),
             remarks            = VALUES(remarks)"
    );
    $stmt->execute([$studentId, $subjectId, $quarter, $syId, $ww, $pt, $qe, $finalGrade, $remarks]);

    jsonResponse(['success' => true, 'message' => 'Grade saved.', 'final_grade' => $finalGrade, 'remarks' => $remarks]);
}

// ─── DELETE GRADE (admin only) ────────────────────────────────────────────────
if ($action === 'delete') {
    requireAdmin();
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Grade ID required.'], 400);
    }
    getDB()->prepare('DELETE FROM grades WHERE id = ?')->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'Grade deleted.']);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
