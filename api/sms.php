<?php
// api/sms.php
// Online Grade SMS Notification backend for LNHS OGMS powered by PhilSMS API v3.
// Automatically generates concise student grade SMS to parents/guardians (< 160 chars) per term (1st Term - 3rd Term), Final Grade, and Failing Grade alerts.
// Supports both Admin (School-wide overview) and Teacher (scoped strictly to teacher's assigned teaching subject(s) & grade levels).

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
function buildStudentGradeSMS(PDO $pdo, int $studentId, string $mode, $quarter, int $syId, ?int $teacherId = null, ?int $subjectId = null): array {
    $stmt = $pdo->prepare(
        "SELECT u.id, u.full_name, u.phone, u.guardian_name, u.guardian_phone, u.lrn,
                s.id AS section_id, s.name AS section_name, s.grade_level,
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
    $studentGradeLevel = (int)($student['grade_level'] ?? 0);

    $message = '';
    $generalAverage = null;
    $remarks = '';

    // ─── IF TEACHER MODE: RESTRICT EXCLUSIVELY TO TEACHING SUBJECT(S) ───────────
    if ($teacherId !== null) {
        $tStmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $tStmt->execute([$teacherId]);
        $teacherName = $tStmt->fetchColumn() ?: 'Faculty Teacher';

        // Resolve subject(s) taught by this teacher for this student's grade level & active SY
        if ($subjectId) {
            $subStmt = $pdo->prepare(
                "SELECT s.id, s.name, s.code 
                 FROM subjects s 
                 WHERE s.id = ? AND (
                     EXISTS (
                         SELECT 1 FROM teacher_subjects ts 
                         WHERE ts.teacher_id = ? AND ts.subject_id = s.id AND ts.school_year_id = ? 
                           AND (ts.grade_level = ? OR ts.grade_level IS NULL)
                     )
                     OR s.teacher_id = ?
                 )"
            );
            $subStmt->execute([$subjectId, $teacherId, $syId, $studentGradeLevel, $teacherId]);
            $teacherSubs = $subStmt->fetchAll();
        } else {
            $subStmt = $pdo->prepare(
                "SELECT DISTINCT s.id, s.name, s.code 
                 FROM subjects s 
                 JOIN teacher_subjects ts ON ts.subject_id = s.id 
                 WHERE ts.teacher_id = ? AND ts.school_year_id = ? 
                   AND (ts.grade_level = ? OR ts.grade_level IS NULL)
                 UNION
                 SELECT s.id, s.name, s.code FROM subjects s WHERE s.teacher_id = ?
                 ORDER BY name"
            );
            $subStmt->execute([$teacherId, $syId, $studentGradeLevel, $teacherId]);
            $teacherSubs = $subStmt->fetchAll();
        }

        if (empty($teacherSubs)) {
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
                'grade_section'   => $gradeSection,
                'section_name'    => $student['section_name'] ?: 'Unassigned',
                'grade_level'     => $student['grade_level'] ?: null,
                'message'         => "LNHS: {$studentName} ({$gradeSection}) has no enrolled classes with Teacher {$teacherName}.",
                'general_average' => null,
                'remarks'         => 'N/A'
            ];
        }

        // Case A: Exactly 1 teaching subject (or individually chosen subject)
        if (count($teacherSubs) === 1) {
            $sub = $teacherSubs[0];
            $subName = $sub['name'];
            $subId = (int)$sub['id'];

            if ($mode === 'term_grades') {
                $q = (int)$quarter;
                if ($q < 1 || $q > 4) $q = 1;
                $termLabel = getTermLabel($q);

                $gStmt = $pdo->prepare(
                    "SELECT final_grade, remarks FROM grades 
                     WHERE student_id = ? AND subject_id = ? AND quarter = ? AND school_year_id = ?"
                );
                $gStmt->execute([$studentId, $subId, $q, $syId]);
                $gRow = $gStmt->fetch();

                if ($gRow && $gRow['final_grade'] !== null) {
                    $val = (float)$gRow['final_grade'];
                    $rem = $val >= 75 ? 'PASSED' : 'NEEDS IMPR.';
                    $generalAverage = $val;
                    $remarks = $rem;
                    $valFmt = number_format($val, 0);
                    $message = "LNHS: {$studentName} ({$gradeSection}) {$termLabel} Grade in {$subName}: {$valFmt} ({$rem}). Teacher: {$teacherName}.";
                } else {
                    $generalAverage = null;
                    $remarks = 'PENDING';
                    $message = "LNHS: {$studentName} ({$gradeSection}) {$termLabel} Grade in {$subName}: Pending. Teacher: {$teacherName}.";
                }

            } elseif ($mode === 'final_average') {
                $gStmt = $pdo->prepare(
                    "SELECT quarter, final_grade FROM grades 
                     WHERE student_id = ? AND subject_id = ? AND school_year_id = ? AND final_grade IS NOT NULL
                     ORDER BY quarter"
                );
                $gStmt->execute([$studentId, $subId, $syId]);
                $gRows = $gStmt->fetchAll();
                $quartersFound = array_column($gRows, 'quarter');

                // Check complete 3 terms (Terms 1, 2, and 3)
                $hasTerms123 = in_array(1, $quartersFound) && in_array(2, $quartersFound) && in_array(3, $quartersFound);

                if ($hasTerms123) {
                    $sum = 0;
                    foreach ($gRows as $gr) {
                        if (in_array((int)$gr['quarter'], [1, 2, 3])) {
                            $sum += (float)$gr['final_grade'];
                        }
                    }
                    $finalSubAvg = round($sum / 3, 2);
                    $rem = $finalSubAvg >= 75 ? 'PASSED' : 'NEEDS IMPR.';
                    $generalAverage = $finalSubAvg;
                    $remarks = $rem;
                    $avgFmt = number_format($finalSubAvg, 2);
                    $message = "LNHS: {$studentName} ({$gradeSection}) SY {$syLabel} Final Grade in {$subName}: {$avgFmt} ({$rem}). Teacher: {$teacherName}.";
                } elseif (!empty($gRows)) {
                    $vals = array_map(function($r){ return (float)$r['final_grade']; }, $gRows);
                    $partialAvg = round(array_sum($vals) / count($vals), 2);
                    $rem = $partialAvg >= 75 ? 'PASSED' : 'NEEDS IMPR.';
                    $generalAverage = $partialAvg;
                    $remarks = $rem;
                    $avgFmt = number_format($partialAvg, 2);
                    $message = "LNHS: {$studentName} ({$gradeSection}) SY {$syLabel} {$subName} Current Avg: {$avgFmt} ({$rem}). Teacher: {$teacherName}.";
                } else {
                    $generalAverage = null;
                    $remarks = 'NO GRADES';
                    $message = "LNHS: {$studentName} ({$gradeSection}) SY {$syLabel} {$subName}: No grades recorded yet. Teacher: {$teacherName}.";
                }

            } else {
                // failing_alert
                $q = (int)$quarter;
                $qWhere = ($q >= 1 && $q <= 4) ? "AND quarter = $q" : "";

                $gStmt = $pdo->prepare(
                    "SELECT quarter, final_grade FROM grades 
                     WHERE student_id = ? AND subject_id = ? AND school_year_id = ? AND final_grade < 75 $qWhere
                     ORDER BY quarter"
                );
                $gStmt->execute([$studentId, $subId, $syId]);
                $failedRows = $gStmt->fetchAll();

                if (!empty($failedRows)) {
                    $failedItems = [];
                    foreach ($failedRows as $fr) {
                        $failedItems[] = "T" . $fr['quarter'] . ":" . number_format($fr['final_grade'], 0);
                    }
                    $gradesSummary = implode(',', $failedItems);
                    $remarks = 'DEFICIENT';
                    $message = "LNHS NOTICE: {$studentName} ({$gradeSection}) Grade Deficiency in {$subName}: {$gradesSummary}. Please contact Teacher {$teacherName}.";
                } else {
                    $remarks = 'PASSED';
                    $message = "LNHS: {$studentName} ({$gradeSection}) has no failing grade in {$subName}.";
                }
            }

        } else {
            // Case B: Teacher teaches multiple subjects to this student & none was specifically filtered
            $subIds = array_column($teacherSubs, 'id');
            $inSubs = implode(',', array_map('intval', $subIds));

            if ($mode === 'term_grades') {
                $q = (int)$quarter;
                if ($q < 1 || $q > 4) $q = 1;
                $termLabel = getTermLabel($q);

                $gStmt = $pdo->prepare(
                    "SELECT sub.code, sub.name, g.final_grade, g.remarks
                     FROM subjects sub
                     LEFT JOIN grades g ON g.subject_id = sub.id AND g.student_id = ? AND g.quarter = ? AND g.school_year_id = ?
                     WHERE sub.id IN ($inSubs)
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
                $message = "LNHS: {$studentName} ({$gradeSection}) {$termLabel} Grades: {$gradesSummary}. Avg: {$avgStr} ({$remarks}). Teacher: {$teacherName}.";

            } elseif ($mode === 'final_average') {
                $gStmt = $pdo->prepare(
                    "SELECT sub.id, sub.code, sub.name, ROUND(AVG(g.final_grade), 2) AS sub_avg
                     FROM subjects sub
                     JOIN grades g ON g.subject_id = sub.id AND g.student_id = ? AND g.school_year_id = ?
                     WHERE sub.id IN ($inSubs) AND g.final_grade IS NOT NULL
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
                $message = "LNHS: {$studentName} ({$gradeSection}) SY {$syLabel} Final Grade: {$avgStr} ({$remarks}). Grades: {$gradesSummary}. Teacher: {$teacherName}.";

            } else {
                // failing_alert
                $q = (int)$quarter;
                $qWhere = ($q >= 1 && $q <= 4) ? "AND g.quarter = $q" : "";

                $gStmt = $pdo->prepare(
                    "SELECT sub.name AS subject_name, sub.code, g.quarter, g.final_grade
                     FROM grades g
                     JOIN subjects sub ON sub.id = g.subject_id
                     WHERE g.student_id = ? AND g.school_year_id = ? AND g.final_grade < 75 
                       AND sub.id IN ($inSubs) $qWhere
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
                    $message = "LNHS NOTICE: {$studentName} ({$gradeSection}) Grade Deficiency: {$gradesSummary}. Please contact Teacher {$teacherName}.";
                } else {
                    $message = "LNHS: {$studentName} ({$gradeSection}) has no failing grades in your subjects.";
                }
            }
        }

    } else {
        // ─── ADMIN MODE: COMPREHENSIVE SCHOOL-WIDE OVERVIEW ─────────────────────────
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
        'grade_section'   => $gradeSection,
        'section_name'    => $student['section_name'] ?: 'Unassigned',
        'grade_level'     => $student['grade_level'] ?: null,
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

// ─── GET OPTIONS (Sections, School Years, Students, Subjects) ───────────────
if ($action === 'options') {
    requireStaff();
    $pdo = getDB();
    $isTeacher = ($_SESSION['role'] === 'teacher');
    $teacherId = $isTeacher ? (int)$_SESSION['user_id'] : null;
    $syId = activeSchoolYear($pdo);

    $schoolYears = $pdo->query(
        "SELECT id, label, is_active FROM school_years ORDER BY id DESC"
    )->fetchAll();

    if ($isTeacher) {
        // Fetch teacher's assigned subjects
        $tsStmt = $pdo->prepare(
            "SELECT DISTINCT ts.subject_id AS id, s.name, s.code, s.level, ts.grade_level,
                    CONCAT(s.name, ' (Grade ', ts.grade_level, ')') AS display_name
             FROM teacher_subjects ts
             JOIN subjects s ON s.id = ts.subject_id
             WHERE ts.teacher_id = ? AND ts.school_year_id = ?
             ORDER BY ts.grade_level, s.name"
        );
        $tsStmt->execute([$teacherId, $syId]);
        $teacherSubjects = $tsStmt->fetchAll();

        if (empty($teacherSubjects)) {
            $tsStmt2 = $pdo->prepare(
                "SELECT id, name, code, level, NULL AS grade_level, name AS display_name
                 FROM subjects WHERE teacher_id = ? ORDER BY name"
            );
            $tsStmt2->execute([$teacherId]);
            $teacherSubjects = $tsStmt2->fetchAll();
        }

        $assignedGrades = array_unique(array_filter(array_column($teacherSubjects, 'grade_level')));

        // Fetch sections taught by this teacher
        if (!empty($assignedGrades)) {
            $inGrades = implode(',', array_map('intval', $assignedGrades));
            $secStmt = $pdo->prepare(
                "SELECT id, name, grade_level FROM sections 
                 WHERE school_year_id = ? AND (grade_level IN ($inGrades) OR adviser_id = ?)
                 ORDER BY grade_level, name"
            );
            $secStmt->execute([$syId, $teacherId]);
            $sections = $secStmt->fetchAll();
        } else {
            $secStmt = $pdo->prepare(
                "SELECT id, name, grade_level FROM sections 
                 WHERE school_year_id = ? AND adviser_id = ?
                 ORDER BY grade_level, name"
            );
            $secStmt->execute([$syId, $teacherId]);
            $sections = $secStmt->fetchAll();
        }

        // Fetch students in those sections
        $sectionIds = array_column($sections, 'id');
        if (!empty($sectionIds)) {
            $inSec = implode(',', array_map('intval', $sectionIds));
            $stuStmt = $pdo->prepare(
                "SELECT u.id, u.full_name, u.lrn, u.phone, u.guardian_name, u.guardian_phone, 
                        s.id AS section_id, s.name AS section_name, s.grade_level
                 FROM users u
                 JOIN enrollments e ON e.student_id = u.id AND e.school_year_id = ?
                 JOIN sections s ON s.id = e.section_id
                 WHERE u.role = 'student' AND u.is_active = 1 AND s.id IN ($inSec)
                 ORDER BY u.full_name"
            );
            $stuStmt->execute([$syId]);
            $students = $stuStmt->fetchAll();
        } else {
            $students = [];
        }

        jsonResponse([
            'success'        => true,
            'subjects'       => $teacherSubjects,
            'sections'       => $sections,
            'school_years'   => $schoolYears,
            'students'       => $students,
            'active_sy_id'   => $syId,
            'active_sy_lbl'  => activeSchoolYearLabel($pdo),
            'api_configured' => !empty(trim(defined('PHILSMS_API_TOKEN') ? PHILSMS_API_TOKEN : '')),
            'is_teacher'     => true,
            'teacher_name'   => $_SESSION['full_name'] ?? 'Faculty Teacher'
        ]);

    } else {
        // Admin options
        $sections = $pdo->query(
            "SELECT id, name, grade_level FROM sections ORDER BY grade_level, name"
        )->fetchAll();

        $students = $pdo->query(
            "SELECT u.id, u.full_name, u.lrn, u.phone, u.guardian_name, u.guardian_phone, 
                    s.id AS section_id, s.name AS section_name, s.grade_level
             FROM users u
             LEFT JOIN enrollments e ON e.student_id = u.id AND e.school_year_id = (SELECT id FROM school_years WHERE is_active = 1 LIMIT 1)
             LEFT JOIN sections s ON s.id = e.section_id
             WHERE u.role = 'student' AND u.is_active = 1
             ORDER BY u.full_name"
        )->fetchAll();

        jsonResponse([
            'success'        => true,
            'subjects'       => [],
            'sections'       => $sections,
            'school_years'   => $schoolYears,
            'students'       => $students,
            'active_sy_id'   => $syId,
            'active_sy_lbl'  => activeSchoolYearLabel($pdo),
            'api_configured' => !empty(trim(defined('PHILSMS_API_TOKEN') ? PHILSMS_API_TOKEN : '')),
            'is_teacher'     => false
        ]);
    }
}

// ─── PREVIEW GRADE SMS ───────────────────────────────────────────────────────
if ($action === 'preview') {
    requireStaff();
    $pdo       = getDB();
    $studentId = (int)($_GET['student_id']     ?? 0);
    $mode      = $_GET['mode']                 ?? 'term_grades';
    $quarter   = $_GET['quarter']              ?? 1;
    $syId      = (int)($_GET['school_year_id'] ?? activeSchoolYear($pdo));
    $subjectId = !empty($_GET['subject_id'])   ? (int)$_GET['subject_id'] : null;

    if (!$studentId) {
        jsonResponse(['success' => false, 'message' => 'Please select a student to preview.'], 400);
    }

    $isTeacher = ($_SESSION['role'] === 'teacher');
    $teacherId = $isTeacher ? (int)$_SESSION['user_id'] : null;

    $res = buildStudentGradeSMS($pdo, $studentId, $mode, $quarter, $syId, $teacherId, $subjectId);
    jsonResponse($res);
}

// ─── GET SMS LOGS ────────────────────────────────────────────────────────────
if ($action === 'logs') {
    requireStaff();
    $pdo = getDB();
    $isTeacher = ($_SESSION['role'] === 'teacher');
    $teacherId = $isTeacher ? (int)$_SESSION['user_id'] : null;

    if ($isTeacher) {
        $stmt = $pdo->prepare(
            "SELECT id, message, status, sent_at, created_at,
                    recipient_phone, recipient_name
             FROM sms_logs
             WHERE sender_id = ?
             ORDER BY created_at DESC
             LIMIT 100"
        );
        $stmt->execute([$teacherId]);
    } else {
        $stmt = $pdo->query(
            "SELECT id, message, status, sent_at, created_at,
                    recipient_phone, recipient_name
             FROM sms_logs
             ORDER BY created_at DESC
             LIMIT 100"
        );
    }

    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

// ─── SEND SMS ────────────────────────────────────────────────────────────────
if ($action === 'send') {
    requireStaff();
    $pdo           = getDB();
    $mode          = $_POST['mode']                 ?? 'term_grades';
    $quarter       = $_POST['quarter']              ?? 1;
    $recipientType = $_POST['recipient_type']       ?? 'single';
    $studentId     = (int)($_POST['student_id']    ?? 0);
    $sectionId     = (int)($_POST['section_id']    ?? 0);
    $syId          = (int)($_POST['school_year_id'] ?? activeSchoolYear($pdo));
    $subjectId     = !empty($_POST['subject_id'])   ? (int)$_POST['subject_id'] : null;

    $isTeacher = ($_SESSION['role'] === 'teacher');
    $teacherId = $isTeacher ? (int)$_SESSION['user_id'] : null;

    $studentIds = [];

    if ($isTeacher) {
        // Resolve teacher's allowed sections
        $chkTs = $pdo->prepare("SELECT DISTINCT grade_level FROM teacher_subjects WHERE teacher_id = ? AND school_year_id = ?");
        $chkTs->execute([$teacherId, $syId]);
        $assignedGrades = $chkTs->fetchAll(PDO::FETCH_COLUMN);

        $whereSec = ["s.school_year_id = ?"];
        $paramsSec = [$syId];
        if (!empty($assignedGrades)) {
            $inGrades = implode(',', array_map('intval', $assignedGrades));
            $whereSec[] = "(s.grade_level IN ($inGrades) OR s.adviser_id = ?)";
            $paramsSec[] = $teacherId;
        } else {
            $whereSec[] = "s.adviser_id = ?";
            $paramsSec[] = $teacherId;
        }

        $secStmt = $pdo->prepare("SELECT id FROM sections s WHERE " . implode(' AND ', $whereSec));
        $secStmt->execute($paramsSec);
        $allowedSectionIds = $secStmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($allowedSectionIds)) {
            jsonResponse(['success' => false, 'message' => 'No assigned classes or sections found for your account.'], 403);
        }

        $inAllowedSections = implode(',', array_map('intval', $allowedSectionIds));

        if ($recipientType === 'single') {
            if (!$studentId) {
                jsonResponse(['success' => false, 'message' => 'student_id is required for single recipient.'], 400);
            }
            // Verify student is in teacher's sections
            $chkStu = $pdo->prepare(
                "SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND school_year_id = ? AND section_id IN ($inAllowedSections)"
            );
            $chkStu->execute([$studentId, $syId]);
            if ((int)$chkStu->fetchColumn() === 0) {
                jsonResponse(['success' => false, 'message' => 'Selected student is not enrolled in your classes.'], 403);
            }
            $studentIds = [$studentId];

        } elseif ($recipientType === 'section') {
            if (!$sectionId || !in_array($sectionId, $allowedSectionIds)) {
                jsonResponse(['success' => false, 'message' => 'Valid section within your teaching classes is required.'], 400);
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

            // Find teacher's subjects
            if ($subjectId) {
                $teacherSubIds = [$subjectId];
            } else {
                $subStmt = $pdo->prepare(
                    "SELECT DISTINCT subject_id FROM teacher_subjects WHERE teacher_id = ? AND school_year_id = ?
                     UNION
                     SELECT id FROM subjects WHERE teacher_id = ?"
                );
                $subStmt->execute([$teacherId, $syId, $teacherId]);
                $teacherSubIds = $subStmt->fetchAll(PDO::FETCH_COLUMN);
            }

            if (empty($teacherSubIds)) {
                jsonResponse(['success' => false, 'message' => 'No teaching subjects assigned.'], 403);
            }
            $inTeacherSubs = implode(',', array_map('intval', $teacherSubIds));

            $stmt = $pdo->prepare(
                "SELECT DISTINCT u.id
                 FROM users u
                 JOIN enrollments e ON e.student_id = u.id AND e.school_year_id = ?
                 JOIN grades g ON g.student_id = u.id AND g.school_year_id = ?
                 WHERE e.section_id IN ($inAllowedSections)
                   AND g.subject_id IN ($inTeacherSubs)
                   AND g.final_grade < 75 
                   AND u.role = 'student' AND u.is_active = 1 $qWhere"
            );
            $stmt->execute([$syId, $syId]);
            $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        } else {
            // all active students enrolled in teacher's sections
            $stmt = $pdo->prepare(
                "SELECT DISTINCT u.id
                 FROM users u
                 JOIN enrollments e ON e.student_id = u.id AND e.school_year_id = ?
                 WHERE e.section_id IN ($inAllowedSections) AND u.role = 'student' AND u.is_active = 1
                 ORDER BY u.full_name"
            );
            $stmt->execute([$syId]);
            $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

    } else {
        // Admin recipient resolution
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
    }

    if (empty($studentIds)) {
        jsonResponse(['success' => false, 'message' => 'No eligible student recipients found.'], 404);
    }

    $ins = $pdo->prepare(
        "INSERT INTO sms_logs (recipient_name, recipient_phone, message, sender_id, status)
         VALUES (?, ?, ?, ?, 'pending')"
    );

    $queuedCount = 0;
    $sentCount   = 0;
    $failedCount = 0;
    $lastErr     = '';
    $hasToken    = !empty(trim(defined('PHILSMS_API_TOKEN') ? PHILSMS_API_TOKEN : ''));
    $senderId    = (int)($_SESSION['user_id'] ?? 0);

    foreach ($studentIds as $sId) {
        $data = buildStudentGradeSMS($pdo, (int)$sId, $mode, $quarter, $syId, $teacherId, $subjectId);
        if ($data['success']) {
            $ins->execute([
                $data['recipient_name'],
                $data['phone'],
                $data['message'],
                $senderId ?: null
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

// ─── CLEAR LOGS ─────────────────────────────────────────────────────────────
if ($action === 'clear_logs') {
    requireStaff();
    $pdo = getDB();
    if ($_SESSION['role'] === 'teacher') {
        $teacherId = (int)$_SESSION['user_id'];
        $del = $pdo->prepare("DELETE FROM sms_logs WHERE sender_id = ?");
        $del->execute([$teacherId]);
    } else {
        $pdo->exec("DELETE FROM sms_logs");
    }
    jsonResponse(['success' => true, 'message' => 'SMS log cleared successfully.']);
}

if (!empty($action)) {
    jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
}
