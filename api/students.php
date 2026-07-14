<?php
// api/students.php
require_once '../config/db.php';
require_once '../config/session.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─── LIST ALL STUDENTS (admin only) ────────────────────────────────────────
if ($action === 'list') {
    requireAdmin();
    $pdo  = getDB();
    $stmt = $pdo->query(
        "SELECT u.id, u.lrn, u.full_name, u.email, u.phone, u.address,
                u.birthdate, u.gender, u.avatar_url, u.is_active, u.created_at,
                s.name AS section_name, s.grade_level
         FROM users u
         LEFT JOIN enrollments e ON e.student_id = u.id
         LEFT JOIN sections s    ON s.id = e.section_id
         LEFT JOIN school_years sy ON sy.id = e.school_year_id AND sy.is_active = 1
         WHERE u.role = 'student'
         ORDER BY u.full_name"
    );
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

// ─── GET ONE STUDENT ────────────────────────────────────────────────────────
if ($action === 'get') {
    requireLogin();
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

    // Students can only fetch their own profile
    if ($_SESSION['role'] === 'student' && $id !== (int)$_SESSION['user_id']) {
        jsonResponse(['success' => false, 'message' => 'Access denied.'], 403);
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare(
        "SELECT u.id, u.lrn, u.full_name, u.email, u.phone, u.address,
                u.birthdate, u.gender, u.avatar_url,
                s.name AS section_name, s.grade_level
         FROM users u
         LEFT JOIN enrollments e ON e.student_id = u.id
         LEFT JOIN sections s    ON s.id = e.section_id
         LEFT JOIN school_years sy ON sy.id = e.school_year_id AND sy.is_active = 1
         WHERE u.id = ?"
    );
    $stmt->execute([$id]);
    $student = $stmt->fetch();

    if (!$student) {
        jsonResponse(['success' => false, 'message' => 'Student not found.'], 404);
    }
    jsonResponse(['success' => true, 'data' => $student]);
}

// ─── REGISTER NEW STUDENT (public signup) ──────────────────────────────────
if ($action === 'register') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $email     = trim($_POST['email']      ?? '');
    $password  = $_POST['password']        ?? '';
    $lrn       = trim($_POST['lrn']        ?? '');
    $phone     = trim($_POST['phone']      ?? '');

    if (!$firstName || !$lastName || !$email || !$password) {
        jsonResponse(['success' => false, 'message' => 'All fields are required.'], 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Invalid email address.'], 400);
    }
    if (strlen($password) < 8) {
        jsonResponse(['success' => false, 'message' => 'Password must be at least 8 characters.'], 400);
    }
    if ($lrn && !preg_match('/^\d{12}$/', $lrn)) {
        jsonResponse(['success' => false, 'message' => 'LRN must be exactly 12 digits.'], 400);
    }
    if ($phone && !preg_match('/^\d{11}$/', $phone)) {
        jsonResponse(['success' => false, 'message' => 'Contact number must be exactly 11 digits.'], 400);
    }

    $pdo = getDB();

    // Check email duplicate
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Email is already registered.'], 409);
    }

    // Check LRN duplicate
    if ($lrn) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE lrn = ?');
        $stmt->execute([$lrn]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'LRN is already registered.'], 409);
        }
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $pdo->prepare(
        'INSERT INTO users (lrn, full_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([$lrn ?: null, "$firstName $lastName", $email, $phone ?: null, $hash, 'student']);

    jsonResponse(['success' => true, 'message' => 'Account created. You can now log in.']);
}

// ─── UPDATE PROFILE ─────────────────────────────────────────────────────────
if ($action === 'update') {
    requireLogin();
    $id = (int)($_POST['id'] ?? 0);

    if ($_SESSION['role'] === 'student' && $id !== (int)$_SESSION['user_id']) {
        jsonResponse(['success' => false, 'message' => 'Access denied.'], 403);
    }

    if (isset($_POST['phone']) && trim($_POST['phone']) !== '' && !preg_match('/^\d{11}$/', trim($_POST['phone']))) {
        jsonResponse(['success' => false, 'message' => 'Contact number must be exactly 11 digits.'], 400);
    }

    $allowed = ['full_name', 'phone', 'address', 'birthdate', 'gender', 'avatar_url', 'guardian_name'];
    $set     = [];
    $vals    = [];

    foreach ($allowed as $field) {
        if (isset($_POST[$field])) {
            $set[]  = "$field = ?";
            $vals[] = $_POST[$field];
        }
    }

    if (empty($set)) {
        jsonResponse(['success' => false, 'message' => 'Nothing to update.'], 400);
    }

    // Handle password change
    if (!empty($_POST['new_password'])) {
        if (strlen($_POST['new_password']) < 8) {
            jsonResponse(['success' => false, 'message' => 'New password must be at least 8 characters.'], 400);
        }
        $set[]  = 'password = ?';
        $vals[] = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
    }

    $vals[] = $id;
    $pdo    = getDB();
    $pdo->prepare('UPDATE users SET ' . implode(', ', $set) . ' WHERE id = ?')
        ->execute($vals);

    jsonResponse(['success' => true, 'message' => 'Profile updated.']);
}

// ─── SOFT DELETE (admin only) ───────────────────────────────────────────────
if ($action === 'delete') {
    requireAdmin();
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Student ID required.'], 400);
    }
    getDB()->prepare('UPDATE users SET is_active = 0 WHERE id = ? AND role = ?')
           ->execute([$id, 'student']);
    jsonResponse(['success' => true, 'message' => 'Student deactivated.']);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
