<?php
/**
 * database/seed.php
 * Re-seeds fresh demo data for OGMS Lubo National High School.
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

// ─── 1. CLEAN EXISTING DATA ──────────────────────────────────────────────────
$pdo->exec("DELETE FROM grades");
$pdo->exec("DELETE FROM enrollments");
$pdo->exec("DELETE FROM subjects");
$pdo->exec("DELETE FROM sections");
$pdo->exec("DELETE FROM school_years");
$pdo->exec("DELETE FROM password_resets");
$pdo->exec("DELETE FROM sms_logs");
$pdo->exec("DELETE FROM users");

// ─── 2. SCHOOL YEAR ──────────────────────────────────────────────────────────
$syId = insert($pdo, 'school_years', ['label' => '2024-2025', 'is_active' => 1]);
$log[] = "school_years: inserted SY 2024-2025 (id=$syId)";

// ─── 3. SECTIONS (Grades 7 to 12) ────────────────────────────────────────────
$sectionDefs = [
    7  => ['Rizal', 'Sampaguita'],
    8  => ['Bonifacio', 'Newton'],
    9  => ['Mabini', 'Einstein'],
    10 => ['Luna', 'Aguinaldo'],
    11 => ['STEM-A', 'HUMSS-A'],
    12 => ['STEM-B', 'TVL-A'],
];

$sections = [];
foreach ($sectionDefs as $grade => $secNames) {
    foreach ($secNames as $name) {
        $key = "$name";
        $sections["G{$grade}-{$name}"] = insert($pdo, 'sections', [
            'name'           => $name,
            'grade_level'    => $grade,
            'school_year_id' => $syId,
        ]);
    }
}
$log[] = 'sections: inserted 12 sections across Grades 7–12';

// ─── 4. ADMIN USER ───────────────────────────────────────────────────────────
$adminId = insert($pdo, 'users', [
    'full_name' => 'Administrator',
    'email'     => 'admin@lnhs.edu.ph',
    'password'  => password_hash('admin123', PASSWORD_BCRYPT),
    'role'      => 'admin',
    'phone'     => '09111222333',
    'is_active' => 1,
]);
$log[] = "users: admin inserted (email=admin@lnhs.edu.ph, password=admin123)";

// ─── 5. TEACHERS ─────────────────────────────────────────────────────────────
$teachers = [
    'T001' => ['Mr. Roberto Santos',    'roberts@lnhs.edu.ph'],
    'T002' => ['Ms. Elena Reyes',       'elenar@lnhs.edu.ph'],
    'T003' => ['Ms. Patricia Cruz',     'patriciac@lnhs.edu.ph'],
    'T004' => ['Mr. Antonio Ramos',     'antonior@lnhs.edu.ph'],
    'T005' => ['Mrs. Carmen Flores',    'carmenf@lnhs.edu.ph'],
    'T006' => ['Mr. Joseph Villanueva', 'josephv@lnhs.edu.ph'],
    'T007' => ['Ms. Grace Domingo',     'graced@lnhs.edu.ph'],
    'T008' => ['Mrs. Maria Concepcion', 'mariac@lnhs.edu.ph'],
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
$log[] = 'users: 8 teachers inserted';

// ─── 6. SUBJECTS ─────────────────────────────────────────────────────────────
$subjectDefs = [
    'SUB001' => ['Araling Panlipunan', 'AP',    'T005'],
    'SUB002' => ['Mathematics',        'MATH',  'T001'],
    'SUB003' => ['Science',            'SCI',   'T002'],
    'SUB004' => ['English',            'ENG',   'T003'],
    'SUB005' => ['Filipino',           'FIL',   'T004'],
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

// ─── 7. STUDENTS ACROSS GRADES 7 TO 12 ───────────────────────────────────────
$studentRaw = [
    // Grade 7 - Rizal
    ['202400000001', 'Juan dela Cruz',    'student@lnhs.edu.ph',   'G7-Rizal',     'Male',   '2011-05-15'],
    ['202400000002', 'Maria Clara Santos', 'mariaclara@lnhs.edu.ph','G7-Rizal',     'Female', '2011-03-22'],
    // Grade 7 - Sampaguita
    ['202400000003', 'Jose Protasio Rizal', 'joserizal@lnhs.edu.ph', 'G7-Sampaguita','Male',   '2011-06-19'],
    ['202400000004', 'Gabriela Silang',    'gabriela@lnhs.edu.ph',  'G7-Sampaguita','Female', '2011-01-10'],

    // Grade 8 - Bonifacio
    ['202400000005', 'Andres Bonifacio',  'andres@lnhs.edu.ph',    'G8-Bonifacio', 'Male',   '2010-11-30'],
    ['202400000006', 'Melchora Aquino',   'melchora@lnhs.edu.ph',  'G8-Bonifacio', 'Female', '2010-01-06'],
    // Grade 8 - Newton
    ['202400000007', 'Isaac Newton Jr.',   'newton@lnhs.edu.ph',    'G8-Newton',    'Male',   '2010-04-12'],
    ['202400000008', 'Marie Curie Santos', 'curie@lnhs.edu.ph',     'G8-Newton',    'Female', '2010-08-25'],

    // Grade 9 - Mabini
    ['202400000009', 'Apolinario Mabini', 'apolinario@lnhs.edu.ph','G9-Mabini',    'Male',   '2009-07-23'],
    ['202400000010', 'Tandang Sora',       'sora@lnhs.edu.ph',      'G9-Mabini',    'Female', '2009-02-14'],
    // Grade 9 - Einstein
    ['202400000011', 'Albert Einstein D.', 'einstein@lnhs.edu.ph',  'G9-Einstein',  'Male',   '2009-03-14'],
    ['202400000012', 'Ada Lovelace Cruz',  'ada@lnhs.edu.ph',       'G9-Einstein',  'Female', '2009-12-10'],

    // Grade 10 - Luna
    ['202400000013', 'Antonio Luna',       'antonioluna@lnhs.edu.ph','G10-Luna',   'Male',   '2008-10-29'],
    ['202400000014', 'Gregoria de Jesus',  'gregoria@lnhs.edu.ph',  'G10-Luna',    'Female', '2008-05-09'],

    // Grade 11 - STEM-A
    ['202400000015', 'Emilio Aguinaldo',   'emilio@lnhs.edu.ph',    'G11-STEM-A',   'Male',   '2007-03-22'],
    ['202400000016', 'Teresa Magbanua',   'teresa@lnhs.edu.ph',    'G11-STEM-A',   'Female', '2007-10-13'],

    // Grade 12 - STEM-B
    ['202400000017', 'Marcelo H. del Pilar','marcelo@lnhs.edu.ph',  'G12-STEM-B',   'Male',   '2006-08-30'],
    ['202400000018', 'Marina Dizon',       'marina@lnhs.edu.ph',    'G12-STEM-B',   'Female', '2006-07-18'],
];

$studentsCreated = [];
foreach ($studentRaw as [$lrn, $name, $email, $secKey, $gender, $bdate]) {
    $sid = insert($pdo, 'users', [
        'lrn'       => $lrn,
        'full_name' => $name,
        'email'     => $email,
        'password'  => password_hash('student123', PASSWORD_BCRYPT),
        'role'      => 'student',
        'phone'     => '0999' . rand(100000, 999999),
        'address'   => 'Brgy. Lubo, Cavite',
        'gender'    => $gender,
        'birthdate' => $bdate,
        'is_active' => 1,
    ]);

    insert($pdo, 'enrollments', [
        'student_id'     => $sid,
        'section_id'     => $sections[$secKey],
        'school_year_id' => $syId,
    ]);

    $studentsCreated[] = $sid;
}
$log[] = 'users: ' . count($studentsCreated) . ' fresh students inserted';

// ─── 8. GENERATE TERM GRADES (WW, PT, QE, Final) ──────────────────────────────
$gradeCount = 0;
foreach ($studentsCreated as $sIdx => $sid) {
    // Generate realistic grades per subject and quarter
    foreach ($subjectIds as $subId) {
        for ($q = 1; $q <= 4; $q++) {
            // Give slightly varied scores (e.g. 78 - 98)
            $base = 78 + (($sIdx * 3 + $q * 2) % 20);
            $ww = min(100, $base + rand(-2, 3));
            $pt = min(100, $base + rand(-3, 4));
            $qe = min(100, $base + rand(-4, 2));

            $final = round(($ww * 0.20) + ($pt * 0.50) + ($qe * 0.30), 2);
            $remarks = $final >= 75 ? 'Passed' : 'Failed';

            insert($pdo, 'grades', [
                'student_id'        => $sid,
                'subject_id'        => $subId,
                'quarter'           => $q,
                'written_works'     => $ww,
                'performance_tasks' => $pt,
                'quarterly_exam'    => $qe,
                'final_grade'       => $final,
                'remarks'           => $remarks,
                'encoded_by'        => $adminId,
                'school_year_id'    => $syId,
            ]);
            $gradeCount++;
        }
    }
}
$log[] = "grades: $gradeCount grade records inserted (all 4 terms per student)";

// ─── OUTPUT ────────────────────────────────────────────────────────────────
if (php_sapi_name() === 'cli') {
    echo "SEED SUCCESS:\n";
    foreach ($log as $line) echo " - $line\n";
} else {
    echo '<!DOCTYPE html><html><head><title>OGMS Seed</title>';
    echo '<style>body{font-family:monospace;padding:2rem;background:#0f172a;color:#e2e8f0}';
    echo '.ok{color:#4ade80}.warn{color:#fb923c}pre{background:#1e293b;padding:1rem;border-radius:.5rem}</style></head><body>';
    echo '<h2 class="ok">Fresh Seed Data Created Successfully</h2><pre>';
    foreach ($log as $line) {
        echo htmlspecialchars($line) . "\n";
    }
    echo '</pre></body></html>';
}
