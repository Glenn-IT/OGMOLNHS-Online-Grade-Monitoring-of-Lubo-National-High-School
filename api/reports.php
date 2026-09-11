<?php
// api/reports.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/school-year.php';
requireLogin();

$action  = $_GET['action']  ?? '';
$quarter = (int)($_GET['quarter'] ?? $_GET['term'] ?? 0);   // 0 = all terms, 1..3 = terms
$pdo     = getDB();

// ─── CLASS SUMMARY REPORT (admin only) ───────────────────────────────────────
if ($action === 'class') {
    requireAdmin();

    $where  = ['u.role = ?', 'u.is_active = 1'];
    $params = ['student'];

    $gradeWhere  = ['1=1'];
    $gradeParams = [];
    if ($quarter) { $gradeWhere[] = 'g.quarter = ?'; $gradeParams[] = $quarter; }

    $gradeSubquery = "SELECT g.student_id,
                             ROUND(AVG(g.final_grade), 2) AS avg
                      FROM grades g
                      WHERE " . implode(' AND ', $gradeWhere) . "
                      GROUP BY g.student_id";

    $stmt = $pdo->prepare(
        "SELECT u.id, u.full_name, u.lrn,
                s.name AS section_name,
                agg.avg
         FROM users u
         LEFT JOIN enrollments e  ON e.student_id = u.id AND e.school_year_id = ?
         LEFT JOIN sections    s  ON s.id = e.section_id
         LEFT JOIN ($gradeSubquery) agg ON agg.student_id = u.id
         WHERE " . implode(' AND ', $where) . "
         ORDER BY agg.avg DESC, u.full_name"
    );
    $stmt->execute(array_merge([activeSchoolYear($pdo)], $gradeParams, $params));
    $students = $stmt->fetchAll();

    $avgs     = array_filter(array_column($students, 'avg'), fn($v) => $v !== null);
    $classAvg = count($avgs) ? round(array_sum($avgs) / count($avgs), 2) : 0;

    jsonResponse([
        'success' => true,
        'data'    => [
            'students' => $students,
            'stats'    => [
                'total'     => count($students),
                'class_avg' => $classAvg,
                'highest'   => $avgs ? max($avgs) : 0,
                'lowest'    => $avgs ? min($avgs) : 0,
            ],
        ],
    ]);
}

// ─── SUBJECT PERFORMANCE REPORT (admin only) ─────────────────────────────────
if ($action === 'subject') {
    requireAdmin();

    $qFilter  = $quarter ? 'AND g.quarter = ?' : '';
    $qParams  = $quarter ? [$quarter] : [];

    $stmt = $pdo->prepare(
        "SELECT s.id, s.name,
                ROUND(AVG(g.final_grade), 2)  AS avg,
                MAX(g.final_grade)            AS highest,
                MIN(g.final_grade)            AS lowest,
                SUM(g.final_grade >= 75)      AS pass_count,
                SUM(g.final_grade < 75)       AS fail_count
         FROM grades g
         JOIN subjects s ON s.id = g.subject_id
         WHERE 1=1 $qFilter
         GROUP BY g.subject_id
         ORDER BY s.name"
    );
    $stmt->execute($qParams);
    $subjects = $stmt->fetchAll();

    jsonResponse(['success' => true, 'data' => ['subjects' => $subjects]]);
}

// ─── INDIVIDUAL STUDENT REPORT ────────────────────────────────────────────────
if ($action === 'student') {
    requireLogin();

    $studentId = (int)($_GET['student_id'] ?? 0);

    // Students can only pull their own report
    if ($_SESSION['role'] === 'student') {
        $studentId = (int)$_SESSION['user_id'];
    } elseif (!$studentId) {
        jsonResponse(['success' => false, 'message' => 'student_id required.'], 400);
    }

    // Fetch student info
    $stmt = $pdo->prepare(
        "SELECT u.id, u.full_name, u.lrn, u.email, u.gender, u.birthdate, u.guardian_name, u.guardian_phone,
                s.name AS section_name, s.grade_level,
                sy.label AS school_year
         FROM users u
         LEFT JOIN enrollments  e  ON e.student_id = u.id AND e.school_year_id = ?
         LEFT JOIN sections     s  ON s.id = e.section_id
         LEFT JOIN school_years sy ON sy.id = e.school_year_id
         WHERE u.id = ?"
    );
    $stmt->execute([activeSchoolYear($pdo), $studentId]);
    $student = $stmt->fetch();

    if (!$student) {
        jsonResponse(['success' => false, 'message' => 'Student not found.'], 404);
    }

    // Calculate age if birthdate exists, else approximate from grade level
    $age = 13;
    $gradeLevel = (int)($student['grade_level'] ?? 7);
    if (!empty($student['birthdate']) && $student['birthdate'] !== '0000-00-00') {
        try {
            $dob = new DateTime($student['birthdate']);
            $now = new DateTime();
            $age = $now->diff($dob)->y;
        } catch (Exception $e) {
            $age = $gradeLevel + 6;
        }
    } else {
        $age = $gradeLevel + 6;
    }
    $student['age'] = $age;

    // Fetch enrolled subjects
    $subjects = $pdo->query("SELECT id, name, code FROM subjects ORDER BY id")
                    ->fetchAll();

    // Fetch all grades for the student across all quarters (SF9 displays complete progress)
    $stmt = $pdo->prepare(
        "SELECT g.subject_id, g.quarter, g.final_grade, g.written_works, g.performance_tasks, g.quarterly_exam, g.remarks
         FROM grades g
         WHERE g.student_id = ?
         ORDER BY g.subject_id, g.quarter"
    );
    $stmt->execute([$studentId]);
    $gradeRows = $stmt->fetchAll();

    // Build a map: subject_id => [quarter => grade]
    $gradeMap = [];
    foreach ($gradeRows as $r) {
        $gradeMap[(int)$r['subject_id']][(int)$r['quarter']] = (float)$r['final_grade'];
    }

    $subjectReport = [];
    $allFinals     = [];
    foreach ($subjects as $sub) {
        $sid   = (int)$sub['id'];
        $t1    = $gradeMap[$sid][1] ?? null;
        $t2    = $gradeMap[$sid][2] ?? null;
        $t3    = $gradeMap[$sid][3] ?? null;
        $tVals = array_values(array_filter([$t1, $t2, $t3], fn($v) => $v !== null));
        $subAvg = count($tVals) ? round(array_sum($tVals) / count($tVals), 2) : null;

        if ($subAvg !== null) $allFinals[] = $subAvg;

        $subjectReport[] = [
            'id'      => $sid,
            'name'    => $sub['name'],
            'code'    => $sub['code'],
            'q1'      => $t1,
            'q2'      => $t2,
            'q3'      => $t3,
            'term1'   => $t1,
            'term2'   => $t2,
            'term3'   => $t3,
            'avg'     => $subAvg,
            'remarks' => $subAvg !== null ? ($subAvg >= 75 ? 'Passed' : 'Failed') : null
        ];
    }

    $generalAverage = count($allFinals)
        ? round(array_sum($allFinals) / count($allFinals), 2)
        : null;

    // Transfer promotion details
    $nextGrade = 'Grade ' . ($gradeLevel + 1);
    if ($gradeLevel === 10) {
        $nextGrade = 'Grade 11 (Senior High School)';
    } elseif ($gradeLevel >= 12) {
        $nextGrade = 'Graduated / Higher Education (Tertiary)';
    }

    $curriculum = $gradeLevel <= 10 
        ? 'Junior High School (K to 12 Basic Education Curriculum)' 
        : 'Senior High School (TVL - ICT / Academic Track)';

    jsonResponse([
        'success' => true,
        'data'    => [
            'student'         => $student,
            'subjects'        => $subjectReport,
            'general_average' => $generalAverage,
            'school_year'     => activeSchoolYearLabel($pdo),
            'curriculum'      => $curriculum,
            'next_grade'      => $nextGrade,
            'adviser_name'    => 'JOSEPH M. BATUYONG',
            'school_head'     => 'MARLON C. VALIENTES',
        ],
    ]);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
