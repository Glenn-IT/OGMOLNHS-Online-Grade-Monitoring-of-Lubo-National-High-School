<?php
// database/seed_all_student_grades.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/school-year.php';

$pdo = getDB();
$syId = activeSchoolYear($pdo);

echo "Active School Year ID: $syId\n";

// 1. Ensure Carl Anthony (94) is enrolled in Grade 7 PERSEVEARANCE (section 52)
$chk = $pdo->prepare('SELECT id FROM enrollments WHERE student_id = 94 AND school_year_id = ?');
$chk->execute([$syId]);
if (!$chk->fetch()) {
    $pdo->prepare('INSERT INTO enrollments (student_id, section_id, school_year_id) VALUES (94, 52, ?)')->execute([$syId]);
    echo "Enrolled Carl Anthony (94) into section 52 (PERSEVEARANCE, Grade 7).\n";
} else {
    echo "Carl Anthony (94) is already enrolled.\n";
}

// 2. Define subjects
// JHS Core Subjects: AP(17), MATH(18), SCI(19), ENG(20), FIL(21), MAPEH(22), TLE(23), VE(24)
$jhsSubjects = [17, 18, 19, 20, 21, 22, 23, 24];

// SHS Core & Applied Subjects for Grade 11 HUMSS:
// OCC(25), RWS(26), KPWKP(27), 21CLPW(28), GENMATH(31), STATPROB(32), ELS(33), PERDEV(35), UCSP(36), PEH(37)
$shsSubjects = [25, 26, 27, 28, 31, 32, 33, 35, 36, 37];

// JHS students
$jhsStudents = [81, 82, 83, 84, 85, 86, 94];

// SHS students
$shsStudents = [93];

// Grade profiles for variety
$gradeProfiles = [
    81 => ['base' => 86, 'dev' => 4],
    82 => ['base' => 84, 'dev' => 5],
    83 => ['base' => 88, 'dev' => 3],
    84 => ['base' => 91, 'dev' => 3],
    85 => ['base' => 82, 'dev' => 4],
    86 => ['base' => 85, 'dev' => 4],
    94 => ['base' => 87, 'dev' => 3],
    93 => ['base' => 86, 'dev' => 4],
];

$upsert = $pdo->prepare("
    INSERT INTO grades (student_id, subject_id, quarter, school_year_id, written_works, performance_tasks, quarterly_exam, final_grade, remarks, encoded_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        written_works     = VALUES(written_works),
        performance_tasks = VALUES(performance_tasks),
        quarterly_exam    = VALUES(quarterly_exam),
        final_grade       = VALUES(final_grade),
        remarks           = VALUES(remarks),
        encoded_by        = VALUES(encoded_by)
");

$insertedCount = 0;
mt_srand(20261016);

// Populate JHS student grades
foreach ($jhsStudents as $stuId) {
    $prof = $gradeProfiles[$stuId];
    foreach ($jhsSubjects as $subId) {
        $encodedBy = ($subId == 21) ? 91 : 53; // Filipino by Maria Santos, others by Admin
        for ($q = 1; $q <= 3; $q++) {
            $ww = round(min(97, max(75, $prof['base'] + mt_rand(-$prof['dev'], $prof['dev']) + ($q * 0.4))), 2);
            $pt = round(min(98, max(75, $prof['base'] + mt_rand(-$prof['dev'], $prof['dev']) + ($q * 0.5))), 2);
            $qe = round(min(96, max(74, $prof['base'] + mt_rand(-$prof['dev'], $prof['dev']) + ($q * 0.3))), 2);
            $final = round(($ww * 0.20) + ($pt * 0.50) + ($qe * 0.30), 2);
            $remarks = ($final >= 75) ? 'Passed' : 'Failed';

            $upsert->execute([$stuId, $subId, $q, $syId, $ww, $pt, $qe, $final, $remarks, $encodedBy]);
            $insertedCount++;
        }
    }
}

// Populate SHS student grades
foreach ($shsStudents as $stuId) {
    $prof = $gradeProfiles[$stuId];
    foreach ($shsSubjects as $subId) {
        $encodedBy = ($subId == 31) ? 92 : 53; // Gen Math by Peter Parker, others by Admin
        for ($q = 1; $q <= 3; $q++) {
            $ww = round(min(97, max(75, $prof['base'] + mt_rand(-$prof['dev'], $prof['dev']) + ($q * 0.4))), 2);
            $pt = round(min(98, max(75, $prof['base'] + mt_rand(-$prof['dev'], $prof['dev']) + ($q * 0.5))), 2);
            $qe = round(min(96, max(74, $prof['base'] + mt_rand(-$prof['dev'], $prof['dev']) + ($q * 0.3))), 2);
            $final = round(($ww * 0.20) + ($pt * 0.50) + ($qe * 0.30), 2);
            $remarks = ($final >= 75) ? 'Passed' : 'Failed';

            $upsert->execute([$stuId, $subId, $q, $syId, $ww, $pt, $qe, $final, $remarks, $encodedBy]);
            $insertedCount++;
        }
    }
}

echo "Successfully populated $insertedCount grades across all students and subjects.\n";
