<?php
// api/sms.php
// Online Grade SMS Notification backend for LNHS OGMS powered by PhilSMS API v3.
// Automatically generates concise student grade SMS to parents/guardians (< 160 chars) per term (1st Term - 3rd Term), Final Grade, and Failing Grade alerts.

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/school-year.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─── HELPER: MAP TERM NUMBER TO LABEL ───────────────────────────────────────
function getTermLabel($quarter): string {
    $q = (int)$quarter;
    if ($q === 1) return '1st Term';
    if ($q === 2) return '2nd Term';
    if ($q === 3) return '3rd Term';
    if ($q === 4) return '4th Term';
    return 'Final Grade';
}

// ─── HELPER: AUTOMATICALLY BUILD CONCISE GRADE SMS (< 160 CHARS) ─────────────
function buildStudentGradeSMS(PDO $pdo, int $studentId, string $mode, $quarter, int $syId): array {
    $stmt = $pdo->prepare(
        "SELECT u.id, u.full_name, u.phone, u.guardian_name, u.guardian_phone, u.lrn,
                s.name AS section_name, s.grade_level,
                sy.label AS school_year_label
         FROM users u
         LEFT JOIN enrollments e ON e.student_id = u.id AND e.school_year_id = ?
         LEFT JOIN sections s ON s.id = e.section_id
         LEFT JOIN school_years sy ON sy.id = ?
         WHERE u.id = ? AND u.role = 'student'"
    );
    $stmt->execute([$syId, $syId, $studentId]);
    $student = $stmt->fetch();

    if (!$student) {
        return ['success' => false, 'message' => 'Student not found.'];
    }

    $guardianName = !empty($student['guardian_name']) ? $student['guardian_name'] : 'Parent/Guardian';
    $studentName  = $student['full_name'];
    
    // Parent phone priority, fall back to student phone
    $recipientPhone = !empty($student['guardian_phone']) 
        ? $student['guardian_phone'] 
        : (!empty($student['phone']) ? $student['phone'] : 'N/A');
    
    $phoneSource = !empty($student['guardian_phone']) 
        ? 'Parent Phone' 
        : (!empty($student['phone']) ? 'Student Phone (Fallback)' : 'No Number');

    $gradeSection = ($student['grade_level'] ? 'Gr.' . $student['grade_level'] . '-' : '') . ($student['section_name'] ?: 'Unassigned');
    $syLabel      = $student['school_year_label'] ?: activeSchoolYearLabel($pdo);

    $message = '';
    $generalAverage = null;
    $remarks = '';

    if ($mode === 'term_grades') {
        $q = (int)$quarter;
        if ($q < 1 || $q > 4) $q = 1;
        $termLabel = getTermLabel($q);

        $gStmt = $pdo->prepare(
            "SELECT sub.code, sub.name, g.final_grade, g.remarks
             FROM subjects sub
             LEFT JOIN grades g ON g.subject_id = sub.id AND g.student_id = ? AND g.quarter = ? AND g.school_year_id = ?
             ORDER BY sub.name"
        );
        $gStmt->execute([$studentId, $q, $syId]);
        $rows = $gStmt->fetchAll();

        $gradesList = [];
        $validGrades = [];
        foreach ($rows as $r) {
            if ($r['final_grade'] !== null) {
                $val = (float)$r['final_grade'];
                $validGrades[] = $val;
                $code = !empty($r['code']) ? $r['code'] : substr($r['name'], 0, 4);
                $gradesList[] = $code . ':' . number_format($val, 0);
            }
        }

        if (!empty($validGrades)) {
            $generalAverage = round(array_sum($validGrades) / count($validGrades), 2);
            $remarks = $generalAverage >= 75 ? 'PASSED' : 'NEEDS IMPR.';
        } else {
            $remarks = 'NO GRADES YET';
        }

        $gradesSummary = !empty($gradesList) ? implode(',', $gradesList) : 'Pending';
        $avgStr = $generalAverage ? number_format($generalAverage, 2) : 'N/A';
        
        // Compact single SMS text (< 160 chars) without greeting prefix
        $message = "LNHS: {$studentName} ({$gradeSection}) {$termLabel} Grades: {$gradesSummary}. Avg: {$avgStr} ({$remarks}).";

    } elseif ($mode === 'final_average') {
        $gStmt = $pdo->prepare(
            "SELECT sub.id, sub.code, sub.name, ROUND(AVG(g.final_grade), 2) AS sub_avg
             FROM subjects sub
             JOIN grades g ON g.subject_id = sub.id AND g.student_id = ? AND g.school_year_id = ?
             WHERE g.final_grade IS NOT NULL
             GROUP BY sub.id
             ORDER BY sub.name"
        );
        $gStmt->execute([$studentId, $syId]);
        $rows = $gStmt->fetchAll();

        $subAvgs = [];
        $gradesList = [];
        foreach ($rows as $r) {
            if ($r['sub_avg'] !== null) {
                $val = (float)$r['sub_avg'];
                $subAvgs[] = $val;
                $code = !empty($r['code']) ? $r['code'] : substr($r['name'], 0, 4);
                $gradesList[] = $code . ':' . number_format($val, 0);
            }
        }

        if (!empty($subAvgs)) {
            $generalAverage = round(array_sum($subAvgs) / count($subAvgs), 2);
            $remarks = $generalAverage >= 75 ? 'PASSED' : 'NEEDS IMPR.';
        } else {
            $remarks = 'NO GRADES RECORDED';
        }

        $gradesSummary = !empty($gradesList) ? implode(',', $gradesList) : 'None';
        $avgStr = $generalAverage ? number_format($generalAverage, 2) : 'N/A';

        // Compact single SMS text (< 160 chars) without greeting prefix
        $message = "LNHS: {$studentName} ({$gradeSection}) SY {$syLabel} Final Grade: {$avgStr} ({$remarks}). Grades: {$gradesSummary}.";

    } else {
        // failing_alert
        $q = (int)$quarter;
        $qWhere = ($q >= 1 && $q <= 4) ? "AND g.quarter = $q" : "";

        $gStmt = $pdo->prepare(
            "SELECT sub.name AS subject_name, sub.code, g.quarter, g.final_grade
             FROM grades g
             JOIN subjects sub ON sub.id = g.subject_id
             WHERE g.student_id = ? AND g.school_year_id = ? AND g.final_grade < 75 $qWhere
             ORDER BY g.quarter, sub.name"
        );
        $gStmt->execute([$studentId, $syId]);
        $rows = $gStmt->fetchAll();

        $failedList = [];
        foreach ($rows as $r) {
            $code = !empty($r['code']) ? $r['code'] : substr($r['subject_name'], 0, 4);
            $failedList[] = $code . "(T" . $r['quarter'] . ":" . number_format($r['final_grade'], 0) . ")";
        }

        $gradesSummary = !empty($failedList) ? implode(',', $failedList) : 'None';
        $remarks = !empty($failedList) ? 'DEFICIENT' : 'PASSED ALL';

        if (!empty($failedList)) {
            $message = "LNHS NOTICE: {$studentName} ({$gradeSection}) Grade Deficiency: {$gradesSummary}. Please visit LNHS for guidance.";
        } else {
            $message = "LNHS: {$studentName} ({$gradeSection}) has no failing grades.";
        }
    }

    return [
        'success'         => true,
        'student_id'      => $studentId,
        'student_name'    => $studentName,
        'guardian_name'   => $guardianName,
        'recipient_name'  => $guardianName . " (Parent of " . $studentName . ")",
        'phone'           => $recipientPhone,
        'student_phone'   => $student['phone'] ?: 'N/A',
        'guardian_phone'  => $student['guardian_phone'] ?: 'N/A',
        'phone_source'    => $phoneSource,
        'message'         => $message,
        'general_average' => $generalAverage,
        'remarks'         => $remarks
    ];
}

// ─── HELPER: PHILSMS API CURL EXECUTION ──────────────────────────────────────
function sendPhilSMS(PDO $pdo, int $logId, string $recipientPhone, string $message): array {
    $token    = defined('PHILSMS_API_TOKEN') ? trim(PHILSMS_API_TOKEN) : '';
    $senderId = defined('PHILSMS_SENDER_ID') && trim(PHILSMS_SENDER_ID) !== '' ? trim(PHILSMS_SENDER_ID) : 'PhilSMS';

    if (empty($token)) {
        return ['status' => 'pending', 'message' => 'PhilSMS API Token is empty in config/db.php'];
    }

    // Format phone to 639XXXXXXXXX for Philippines carriers
    $cleanPhone = preg_replace('/\D/', '', $recipientPhone);
    if (strpos($cleanPhone, '09') === 0) {
        $cleanPhone = '63' . substr($cleanPhone, 1);
    } elseif (strpos($cleanPhone, '9') === 0 && strlen($cleanPhone) === 10) {
        $cleanPhone = '63' . $cleanPhone;
    }

    $executeCurl = function($sId) use ($cleanPhone, $message, $token) {
        $payload = json_encode([
            'recipient' => $cleanPhone,
            'sender_id' => substr($sId, 0, 11),
            'type'      => 'plain',
            'message'   => $message
        ]);

        $ch = curl_init('https://dashboard.philsms.com/api/v3/sms/send');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT        => 15
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        return [$response, $httpCode, $curlErr];
    };

    list($response, $httpCode, $curlErr) = $executeCurl($senderId);

    if ($curlErr) {
        $upd = $pdo->prepare("UPDATE sms_logs SET status = 'failed' WHERE id = ?");
        $upd->execute([$logId]);
        return ['status' => 'failed', 'message' => 'cURL Error: ' . $curlErr];
    }

    $resData = json_decode($response, true);

    // If custom sender ID failed due to authorization, auto-retry with standard 'PhilSMS' sender ID!
    if (isset($resData['status']) && $resData['status'] === 'error' && strpos($resData['message'] ?? '', 'not authorized') !== false && $senderId !== 'PhilSMS') {
        list($response, $httpCode, $curlErr) = $executeCurl('PhilSMS');
        $resData = json_decode($response, true);
    }

    if ($httpCode >= 200 && $httpCode < 300 && isset($resData['status']) && $resData['status'] === 'success') {
        $upd = $pdo->prepare("UPDATE sms_logs SET status = 'sent', sent_at = NOW() WHERE id = ?");
        $upd->execute([$logId]);
        return ['status' => 'sent', 'message' => 'Sent successfully via PhilSMS'];
    } else {
        $errMsg = $resData['message'] ?? 'PhilSMS API Error (HTTP ' . $httpCode . ')';
        $upd = $pdo->prepare("UPDATE sms_logs SET status = 'failed' WHERE id = ?");
        $upd->execute([$logId]);
        return ['status' => 'failed', 'message' => $errMsg];
    }
}

// ─── GET OPTIONS (Sections, School Years, Students) ─────────────────────────
if ($action === 'options') {
    requireAdmin();
    $pdo = getDB();

    $sections = $pdo->query(
        "SELECT id, name, grade_level FROM sections ORDER BY grade_level, name"
    )->fetchAll();

    $schoolYears = $pdo->query(
        "SELECT id, label, is_active FROM school_years ORDER BY id DESC"
    )->fetchAll();

    $students = $pdo->query(
        "SELECT u.id, u.full_name, u.lrn, u.phone, u.guardian_name, u.guardian_phone, s.name AS section_name, s.grade_level
         FROM users u
         LEFT JOIN enrollments e ON e.student_id = u.id AND e.school_year_id = (SELECT id FROM school_years WHERE is_active = 1 LIMIT 1)
         LEFT JOIN sections s ON s.id = e.section_id
         WHERE u.role = 'student' AND u.is_active = 1
         ORDER BY u.full_name"
    )->fetchAll();

    jsonResponse([
        'success'       => true,
        'sections'      => $sections,
        'school_years'  => $schoolYears,
        'students'      => $students,
        'active_sy_id'  => activeSchoolYear($pdo),
        'active_sy_lbl' => activeSchoolYearLabel($pdo),
        'api_configured'=> !empty(trim(defined('PHILSMS_API_TOKEN') ? PHILSMS_API_TOKEN : '')),
    ]);
}

// ─── PREVIEW GRADE SMS ───────────────────────────────────────────────────────
if ($action === 'preview') {
    requireAdmin();
    $pdo       = getDB();
    $studentId = (int)($_GET['student_id']     ?? 0);
    $mode      = $_GET['mode']                 ?? 'term_grades';
    $quarter   = $_GET['quarter']              ?? 1;
    $syId      = (int)($_GET['school_year_id'] ?? activeSchoolYear($pdo));

    if (!$studentId) {
        jsonResponse(['success' => false, 'message' => 'Please select a student to preview.'], 400);
    }

    $res = buildStudentGradeSMS($pdo, $studentId, $mode, $quarter, $syId);
    jsonResponse($res);
}

// ─── GET SMS LOGS ────────────────────────────────────────────────────────────
if ($action === 'logs') {
    requireAdmin();
    $stmt = getDB()->query(
        "SELECT id, message, status, sent_at, created_at,
                recipient_phone, recipient_name
         FROM sms_logs
         ORDER BY created_at DESC
         LIMIT 100"
    );
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

// ─── SEND SMS ────────────────────────────────────────────────────────────────
if ($action === 'send') {
    requireAdmin();
    $pdo           = getDB();
    $mode          = $_POST['mode']                 ?? 'term_grades';
    $quarter       = $_POST['quarter']              ?? 1;
    $recipientType = $_POST['recipient_type']       ?? 'single';
    $studentId     = (int)($_POST['student_id']    ?? 0);
    $sectionId     = (int)($_POST['section_id']    ?? 0);
    $syId          = (int)($_POST['school_year_id'] ?? activeSchoolYear($pdo));

    $studentIds = [];

    if ($recipientType === 'single') {
        if (!$studentId) {
            jsonResponse(['success' => false, 'message' => 'student_id is required for single recipient.'], 400);
        }
        $studentIds = [$studentId];
    } elseif ($recipientType === 'section') {
        if (!$sectionId) {
            jsonResponse(['success' => false, 'message' => 'section_id is required for section recipient.'], 400);
        }
        $stmt = $pdo->prepare(
            "SELECT u.id
             FROM users u
             JOIN enrollments e ON e.student_id = u.id AND e.school_year_id = ?
             WHERE e.section_id = ? AND u.role = 'student' AND u.is_active = 1"
        );
        $stmt->execute([$syId, $sectionId]);
        $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } elseif ($recipientType === 'failed') {
        $q = (int)$quarter;
        $qWhere = ($q >= 1 && $q <= 4) ? "AND g.quarter = $q" : "";
        $stmt = $pdo->prepare(
            "SELECT DISTINCT u.id
             FROM users u
             JOIN grades g ON g.student_id = u.id AND g.school_year_id = ?
             WHERE g.final_grade < 75 AND u.role = 'student' AND u.is_active = 1 $qWhere"
        );
        $stmt->execute([$syId]);
        $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        // all active students
        $stmt = $pdo->query(
            "SELECT id FROM users WHERE role = 'student' AND is_active = 1 ORDER BY full_name"
        );
        $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    if (empty($studentIds)) {
        jsonResponse(['success' => false, 'message' => 'No eligible student recipients found.'], 404);
    }

    $ins = $pdo->prepare(
        "INSERT INTO sms_logs (recipient_name, recipient_phone, message, status)
         VALUES (?, ?, ?, 'pending')"
    );

    $queuedCount = 0;
    $sentCount   = 0;
    $failedCount = 0;
    $lastErr     = '';
    $hasToken    = !empty(trim(defined('PHILSMS_API_TOKEN') ? PHILSMS_API_TOKEN : ''));

    foreach ($studentIds as $sId) {
        $data = buildStudentGradeSMS($pdo, (int)$sId, $mode, $quarter, $syId);
        if ($data['success']) {
            $ins->execute([
                $data['recipient_name'],
                $data['phone'],
                $data['message']
            ]);
            $logId = (int)$pdo->lastInsertId();
            $queuedCount++;

            // If PhilSMS API Token is configured in config/db.php, send immediately via PhilSMS!
            if ($hasToken && $data['phone'] !== 'N/A') {
                $sendRes = sendPhilSMS($pdo, $logId, $data['phone'], $data['message']);
                if ($sendRes['status'] === 'sent') {
                    $sentCount++;
                } else {
                    $failedCount++;
                    $lastErr = $sendRes['message'];
                }
            }
        }
    }

    $msg = $hasToken 
        ? ($sentCount > 0 ? "PhilSMS API: {$sentCount} Grade SMS sent successfully!" : "PhilSMS Error: {$lastErr}")
        : "Queued {$queuedCount} Grade SMS (pending — configure PHILSMS_API_TOKEN in config/db.php to enable real SMS).";

    jsonResponse([
        'success'      => $sentCount > 0 || !$hasToken,
        'message'      => $msg,
        'count'        => $queuedCount,
        'sent_count'   => $sentCount,
        'failed_count' => $failedCount,
        'api_active'   => $hasToken,
        'last_error'   => $lastErr
    ]);
}

// ─── CLEAR LOGS (admin only) ─────────────────────────────────────────────────
if ($action === 'clear_logs') {
    requireAdmin();
    getDB()->exec("DELETE FROM sms_logs");
    jsonResponse(['success' => true, 'message' => 'SMS log cleared successfully.']);
}

if (!empty($action)) {
    jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
}
