<?php
// api/reports.php
require_once '../config/db.php';
require_once '../config/session.php';
requireLogin();

$action  = $_GET['action']  ?? '';
$quarter = (int)($_GET['quarter'] ?? 0);   // 0 = all quarters
$pdo     = getDB();

// Helper: resolve active school year ID
function activeSchoolYear(PDO $pdo): int {
    $row = $pdo->query("SELECT id FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();
    return $row ? (int)$row['id'] : 1;
}

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
         LEFT JOIN enrollments e  ON e.student_id = u.id
         LEFT JOIN sections    s  ON s.id = e.section_id
         LEFT JOIN school_years sy ON sy.id = e.school_year_id AND sy.is_active = 1
         LEFT JOIN ($gradeSubquery) agg ON agg.student_id = u.id
         WHERE " . implode(' AND ', $where) . "
         ORDER BY agg.avg DESC, u.full_name"
    );
    $stmt->execute(array_merge($gradeParams, $params));
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
        "SELECT u.id, u.full_name, u.lrn, u.email,
                s.name AS section_name, s.grade_level
         FROM users u
         LEFT JOIN enrollments e  ON e.student_id = u.id
         LEFT JOIN sections    s  ON s.id = e.section_id
         LEFT JOIN school_years sy ON sy.id = e.school_year_id AND sy.is_active = 1
         WHERE u.id = ?"
    );
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();

    if (!$student) {
        jsonResponse(['success' => false, 'message' => 'Student not found.'], 404);
    }

    // Fetch enrolled subjects
    $subjects = $pdo->query("SELECT id, name, code FROM subjects ORDER BY name")
                    ->fetchAll();

    // Fetch grades, optionally filtered by quarter
    $qFilter  = $quarter ? ' AND g.quarter = ?' : '';
    $qParams  = $quarter ? [$studentId, $quarter] : [$studentId];
    $stmt = $pdo->prepare(
        "SELECT g.subject_id, g.quarter, g.final_grade
         FROM grades g
         WHERE g.student_id = ? $qFilter"
    );
    $stmt->execute($qParams);
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
        $qMap  = $gradeMap[$sid] ?? [];
        $qVals = array_values($qMap);
        $subAvg = count($qVals) ? round(array_sum($qVals) / count($qVals), 2) : null;

        if ($subAvg !== null) $allFinals[] = $subAvg;

        $subjectReport[] = [
            'id'   => $sid,
            'name' => $sub['name'],
            'code' => $sub['code'],
            'q1'   => $qMap[1] ?? null,
            'q2'   => $qMap[2] ?? null,
            'q3'   => $qMap[3] ?? null,
            'q4'   => $qMap[4] ?? null,
            'avg'  => $subAvg,
        ];
    }

    $generalAverage = count($allFinals)
        ? round(array_sum($allFinals) / count($allFinals), 2)
        : null;

    jsonResponse([
        'success' => true,
        'data'    => [
            'student'         => $student,
            'subjects'        => $subjectReport,
            'general_average' => $generalAverage,
        ],
    ]);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
