<?php
/**
 * database/seed.php
 * Run once in the browser: http://localhost/OGMS-Lubo-National-High-School/database/seed.php
 * DELETE this file after seeding is confirmed.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ogms_lnhs');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die('<pre>DB connection failed: ' . $e->getMessage() . '</pre>');
}

$log = [];

function insert(PDO $pdo, string $table, array $row): int {
    $cols = implode(', ', array_keys($row));
    $plh  = implode(', ', array_fill(0, count($row), '?'));
    $stmt = $pdo->prepare("INSERT INTO $table ($cols) VALUES ($plh)");
    $stmt->execute(array_values($row));
    return (int) $pdo->lastInsertId();
}

// ─── 1. SCHOOL YEAR ────────────────────────────────────────────────────────
$pdo->exec("DELETE FROM grades");
$pdo->exec("DELETE FROM enrollments");
$pdo->exec("DELETE FROM subjects");
$pdo->exec("DELETE FROM sections");
$pdo->exec("DELETE FROM school_years");
$pdo->exec("DELETE FROM password_resets");
$pdo->exec("DELETE FROM sms_logs");
$pdo->exec("DELETE FROM users");

$syId = insert($pdo, 'school_years', ['label' => '2024-2025', 'is_active' => 1]);
$log[] = "school_years: inserted SY 2024-2025 (id=$syId)";

// ─── 2. SECTIONS ───────────────────────────────────────────────────────────
$sections = [];
foreach ([7, 8, 9, 10] as $grade) {
    foreach (['Rizal', 'Bonifacio', 'Mabini'] as $name) {
        $key = "Grade $grade - $name";
        $sections[$key] = insert($pdo, 'sections', [
            'name'           => $key,
            'grade_level'    => $grade,
            'school_year_id' => $syId,
        ]);
    }
}
$log[] = 'sections: inserted 12 sections (Grades 7–10, 3 sections each)';

// ─── 3. ADMIN ──────────────────────────────────────────────────────────────
$adminId = insert($pdo, 'users', [
    'full_name' => 'Administrator',
    'email'     => 'admin@lnhs.edu.ph',
    'password'  => password_hash('admin123', PASSWORD_BCRYPT),
    'role'      => 'admin',
    'phone'     => '09111222333',
    'is_active' => 1,
]);
$log[] = "users: admin inserted (id=$adminId, email=admin@lnhs.edu.ph, password=admin123)";

// ─── 4. TEACHERS ───────────────────────────────────────────────────────────
$teachers = [
    'T001' => ['Mr. Roberto Santos',   'roberts@lnhs.edu.ph'],
    'T002' => ['Ms. Elena Reyes',      'elenar@lnhs.edu.ph'],
    'T003' => ['Ms. Patricia Cruz',    'patriciac@lnhs.edu.ph'],
    'T004' => ['Mr. Antonio Ramos',    'antonior@lnhs.edu.ph'],
    'T005' => ['Mrs. Carmen Flores',   'carmenf@lnhs.edu.ph'],
    'T006' => ['Mr. Joseph Villanueva','josephv@lnhs.edu.ph'],
    'T007' => ['Ms. Grace Domingo',    'graced@lnhs.edu.ph'],
    'T008' => ['Mrs. Maria Concepcion','mariac@lnhs.edu.ph'],
];

$teacherIds = [];
foreach ($teachers as $key => [$name, $email]) {
    $teacherIds[$key] = insert($pdo, 'users', [
        'full_name' => $name,
        'email'     => $email,
        'password'  => password_hash('teacher123', PASSWORD_BCRYPT),
        'role'      => 'teacher',
        'is_active' => 1,
    ]);
}
$log[] = 'users: 8 teachers inserted (default password: teacher123)';

// ─── 5. SUBJECTS (shared across all sections) ──────────────────────────────
$subjectDefs = [
    'SUB001' => ['Mathematics',        'MATH',  'T001'],
    'SUB002' => ['Science',            'SCI',   'T002'],
    'SUB003' => ['English',            'ENG',   'T003'],
    'SUB004' => ['Filipino',           'FIL',   'T004'],
    'SUB005' => ['Araling Panlipunan', 'AP',    'T005'],
    'SUB006' => ['MAPEH',              'MAPEH', 'T006'],
    'SUB007' => ['TLE',                'TLE',   'T007'],
    'SUB008' => ['Values Education',   'VE',    'T008'],
];

$subjectIds = [];
foreach ($subjectDefs as $key => [$name, $code, $teacherKey]) {
    $subjectIds[$key] = insert($pdo, 'subjects', [
        'name'       => $name,
        'code'       => $code,
        'teacher_id' => $teacherIds[$teacherKey],
    ]);
}
$log[] = 'subjects: 8 subjects inserted';

// ─── 6. STUDENTS ───────────────────────────────────────────────────────────
$studentDefs = [
    'S001' => ['202400000001', 'Juan dela Cruz',  'student@lnhs.edu.ph',  '09123456789', 'Brgy. Lubo, Cavite City', 'Male',   '2008-05-15', 'Grade 10 - Rizal'],
    'S002' => ['202400000002', 'Maria Santos',    'maria@lnhs.edu.ph',    '09234567890', 'Brgy. Lubo, Cavite City', 'Female', '2008-03-22', 'Grade 10 - Rizal'],
    'S003' => ['202400000003', 'Pedro Reyes',     'pedro@lnhs.edu.ph',    '09345678901', 'Brgy. Lubo, Cavite City', 'Male',   '2008-07-10', 'Grade 10 - Bonifacio'],
    'S004' => ['202400000004', 'Ana Garcia',      'ana@lnhs.edu.ph',      '09456789012', 'Brgy. Lubo, Cavite City', 'Female', '2008-11-05', 'Grade 10 - Bonifacio'],
    'S005' => ['202400000005', 'Carlos Mendoza',  'carlos@lnhs.edu.ph',   '09567890123', 'Brgy. Lubo, Cavite City', 'Male',   '2008-09-18', 'Grade 10 - Mabini'],
];

$studentIds = [];
foreach ($studentDefs as $key => [$lrn, $name, $email, $phone, $address, $gender, $bdate, $section]) {
    $sid = insert($pdo, 'users', [
        'lrn'       => $lrn,
        'full_name' => $name,
        'email'     => $email,
        'password'  => password_hash('student123', PASSWORD_BCRYPT),
        'role'      => 'student',
        'phone'     => $phone,
        'address'   => $address,
        'gender'    => $gender,
        'birthdate' => $bdate,
        'is_active' => 1,
    ]);
    $studentIds[$key] = $sid;

    insert($pdo, 'enrollments', [
        'student_id'     => $sid,
        'section_id'     => $sections[$section],
        'school_year_id' => $syId,
    ]);
}
$log[] = 'users: 5 students inserted (default password: student123)';
$log[] = 'enrollments: 5 enrollment records inserted';

// ─── 7. GRADES ─────────────────────────────────────────────────────────────
// final_grade = (written_works * 0.20) + (performance_tasks * 0.60) + (quarterly_exam * 0.20)
// Mock data has a single composite grade — set all components equal to produce the same final.
$gradeMatrix = [
    'S001' => ['SUB001'=>[88,85,90,87],'SUB002'=>[92,89,91,93],'SUB003'=>[85,87,84,86],'SUB004'=>[90,88,92,91],'SUB005'=>[83,85,80,82],'SUB006'=>[94,92,95,93],'SUB007'=>[89,91,88,90],'SUB008'=>[95,93,96,94]],
    'S002' => ['SUB001'=>[78,80,82,79],'SUB002'=>[85,83,87,86],'SUB003'=>[92,94,91,93],'SUB004'=>[88,87,89,90],'SUB005'=>[75,77,76,78],'SUB006'=>[82,84,81,83],'SUB007'=>[79,81,80,82],'SUB008'=>[90,88,91,89]],
    'S003' => ['SUB001'=>[72,74,73,75],'SUB002'=>[80,79,81,82],'SUB003'=>[76,78,75,77],'SUB004'=>[83,81,84,82],'SUB005'=>[70,72,71,74],'SUB006'=>[85,83,86,84],'SUB007'=>[88,87,89,90],'SUB008'=>[79,80,78,81]],
    'S004' => ['SUB001'=>[95,93,96,94],'SUB002'=>[97,95,98,96],'SUB003'=>[93,95,92,94],'SUB004'=>[91,93,90,92],'SUB005'=>[89,91,88,90],'SUB006'=>[96,94,97,95],'SUB007'=>[92,94,91,93],'SUB008'=>[98,96,97,95]],
    'S005' => ['SUB001'=>[68,70,72,71],'SUB002'=>[75,73,76,74],'SUB003'=>[71,73,70,72],'SUB004'=>[77,75,78,76],'SUB005'=>[65,67,66,68],'SUB006'=>[80,78,81,79],'SUB007'=>[83,81,84,82],'SUB008'=>[74,76,73,75]],
];

$gradeCount = 0;
foreach ($gradeMatrix as $sKey => $subjects) {
    foreach ($subjects as $subKey => $quarters) {
        foreach ($quarters as $q => $g) {
            $quarter = $q + 1;
            insert($pdo, 'grades', [
                'student_id'        => $studentIds[$sKey],
                'subject_id'        => $subjectIds[$subKey],
                'quarter'           => $quarter,
                'written_works'     => $g,
                'performance_tasks' => $g,
                'quarterly_exam'    => $g,
                'final_grade'       => $g,
                'remarks'           => $g >= 75 ? 'Passed' : 'Failed',
                'encoded_by'        => $adminId,
                'school_year_id'    => $syId,
            ]);
            $gradeCount++;
        }
    }
}
$log[] = "grades: $gradeCount grade records inserted";

// ─── OUTPUT ────────────────────────────────────────────────────────────────
echo '<!DOCTYPE html><html><head><title>OGMS Seed</title>';
echo '<style>body{font-family:monospace;padding:2rem;background:#0f172a;color:#e2e8f0}';
echo '.ok{color:#4ade80}.warn{color:#fb923c}pre{background:#1e293b;padding:1rem;border-radius:.5rem}</style></head><body>';
echo '<h2 class="ok">Seed completed successfully</h2><pre>';
foreach ($log as $line) {
    echo htmlspecialchars($line) . "\n";
}
echo '</pre>';
echo '<p class="warn">⚠ Delete this file now: <code>database/seed.php</code></p>';
echo '</body></html>';
