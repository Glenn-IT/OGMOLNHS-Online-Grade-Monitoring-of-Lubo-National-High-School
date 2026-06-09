<?php
// api/analytics.php
require_once '../config/db.php';
require_once '../config/session.php';
requireLogin();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'summary') {
    $pdo     = getDB();
    $isAdmin = $_SESSION['role'] === 'admin';

    // ── Base WHERE clause ──────────────────────────────────────────────────
    // Admin sees everything; students see only their own grades
    $gradeWhere  = '1=1';
    $gradeParams = [];
    if (!$isAdmin) {
        $gradeWhere    = 'g.student_id = ?';
        $gradeParams[] = (int)$_SESSION['user_id'];
    }

    // ── Overall stats ──────────────────────────────────────────────────────
    $stmt = $pdo->prepare(
        "SELECT COUNT(*)                       AS total_grades,
                ROUND(AVG(g.final_grade), 2)  AS class_avg,
                MAX(g.final_grade)            AS highest,
                MIN(g.final_grade)            AS lowest,
                SUM(g.final_grade >= 75)      AS pass_count,
                SUM(g.final_grade < 75)       AS fail_count
         FROM grades g WHERE $gradeWhere"
    );
    $stmt->execute($gradeParams);
    $stats = $stmt->fetch();

    $totalGrades = (int)$stats['total_grades'];
    $passRate    = $totalGrades > 0
        ? round(($stats['pass_count'] / $totalGrades) * 100, 1)
        : 0;

    // ── Student count (admin only) ─────────────────────────────────────────
    $totalStudents = 0;
    if ($isAdmin) {
        $totalStudents = (int)$pdo->query(
            "SELECT COUNT(*) FROM users WHERE role = 'student' AND is_active = 1"
        )->fetchColumn();
    }

    // ── Subject averages ──────────────────────────────────────────────────
    $stmt = $pdo->prepare(
        "SELECT s.id, s.name,
                ROUND(AVG(g.final_grade), 2) AS avg
         FROM grades g
         JOIN subjects s ON s.id = g.subject_id
         WHERE $gradeWhere
         GROUP BY g.subject_id
         ORDER BY s.name"
    );
    $stmt->execute($gradeParams);
    $subjectAverages = $stmt->fetchAll();

    // ── Quarter averages (trend) ───────────────────────────────────────────
    $stmt = $pdo->prepare(
        "SELECT g.quarter,
                ROUND(AVG(g.final_grade), 2) AS avg
         FROM grades g
         WHERE $gradeWhere
         GROUP BY g.quarter
         ORDER BY g.quarter"
    );
    $stmt->execute($gradeParams);
    $qRows = $stmt->fetchAll();
    // Ensure exactly 4 entries (null if no data for that quarter)
    $quarterMap = [];
    foreach ($qRows as $r) { $quarterMap[(int)$r['quarter']] = (float)$r['avg']; }
    $quarterAverages = array_map(fn($q) => $quarterMap[$q] ?? null, [1, 2, 3, 4]);

    // ── Student ranking (admin) ────────────────────────────────────────────
    $studentRanking = [];
    if ($isAdmin) {
        $stmt = $pdo->query(
            "SELECT u.id, u.full_name, u.lrn,
                    ROUND(AVG(g.final_grade), 2) AS avg
             FROM grades g
             JOIN users u ON u.id = g.student_id
             GROUP BY g.student_id
             ORDER BY avg DESC
             LIMIT 20"
        );
        $studentRanking = $stmt->fetchAll();
    }

    // ── Grade distribution (for histogram) ────────────────────────────────
    $stmt = $pdo->prepare(
        "SELECT
            SUM(final_grade BETWEEN 90 AND 100) AS outstanding,
            SUM(final_grade BETWEEN 85 AND 89)  AS very_satisfactory,
            SUM(final_grade BETWEEN 80 AND 84)  AS satisfactory,
            SUM(final_grade BETWEEN 75 AND 79)  AS fairly_satisfactory,
            SUM(final_grade < 75)               AS did_not_meet
         FROM grades g WHERE $gradeWhere"
    );
    $stmt->execute($gradeParams);
    $dist = $stmt->fetch();

    // ── SMS sent count (admin) ─────────────────────────────────────────────
    $smsSent = 0;
    if ($isAdmin) {
        $smsSent = (int)$pdo->query(
            "SELECT COUNT(*) FROM sms_logs WHERE status = 'sent'"
        )->fetchColumn();
    }

    jsonResponse([
        'success' => true,
        'data'    => [
            'total_students'   => $totalStudents,
            'total_grades'     => $totalGrades,
            'class_avg'        => $stats['class_avg']  ?? 0,
            'highest'          => $stats['highest']    ?? 0,
            'lowest'           => $stats['lowest']     ?? 0,
            'pass_count'       => (int)($stats['pass_count'] ?? 0),
            'fail_count'       => (int)($stats['fail_count'] ?? 0),
            'pass_rate'        => $passRate,
            'sms_sent'         => $smsSent,
            'subject_averages' => $subjectAverages,
            'quarter_averages' => $quarterAverages,
            'student_ranking'  => $studentRanking,
            'grade_distribution' => [
                'Outstanding'         => (int)($dist['outstanding']         ?? 0),
                'Very Satisfactory'   => (int)($dist['very_satisfactory']   ?? 0),
                'Satisfactory'        => (int)($dist['satisfactory']        ?? 0),
                'Fairly Satisfactory' => (int)($dist['fairly_satisfactory'] ?? 0),
                'Did Not Meet'        => (int)($dist['did_not_meet']        ?? 0),
            ],
        ],
    ]);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
