<?php
// api/school-years.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/school-year.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$pdo    = getDB();

// ─── ACTIVE SCHOOL YEAR (any logged-in user) ─────────────────────────────────
// Used by the student dashboard/reports to show the real S.Y. instead of a
// hard-coded string.
if ($action === 'active') {
    requireLogin();
    $row = activeSchoolYearRow($pdo);
    jsonResponse(['success' => true, 'data' => $row]);
}

// Everything below is admin-only
requireAdmin();

// ─── LIST SCHOOL YEARS ───────────────────────────────────────────────────────
if ($action === 'list') {
    $stmt = $pdo->query(
        "SELECT sy.id, sy.label, sy.is_active,
                COUNT(DISTINCT s.id) AS section_count,
                COUNT(DISTINCT e.id) AS enrollment_count,
                COUNT(DISTINCT g.id) AS grade_count
         FROM school_years sy
         LEFT JOIN sections    s ON s.school_year_id = sy.id
         LEFT JOIN enrollments e ON e.school_year_id = sy.id
         LEFT JOIN grades      g ON g.school_year_id = sy.id
         GROUP BY sy.id
         ORDER BY sy.label DESC"
    );
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

// ─── SAVE SCHOOL YEAR (insert or update) ─────────────────────────────────────
if ($action === 'save') {
    $id    = (int)($_POST['id']   ?? 0);
    $label = trim($_POST['label'] ?? '');

    if (!$label) {
        jsonResponse(['success' => false, 'message' => 'School year label is required.'], 400);
    }
    if (!preg_match('/^(\d{4})-(\d{4})$/', $label, $m)) {
        jsonResponse(['success' => false, 'message' => 'Use the format YYYY-YYYY, e.g. 2025-2026.'], 400);
    }
    if ((int)$m[2] !== (int)$m[1] + 1) {
        jsonResponse(['success' => false, 'message' => 'The second year must follow the first, e.g. 2025-2026.'], 400);
    }

    // Reject duplicate labels (excludes the row being edited)
    $dup = $pdo->prepare("SELECT id FROM school_years WHERE LOWER(TRIM(label)) = LOWER(TRIM(?)) AND id <> ? LIMIT 1");
    $dup->execute([$label, $id]);
    if ($dup->fetch()) {
        jsonResponse(['success' => false, 'message' => "School year \"$label\" already exists."], 409);
    }

    if ($id) {
        $pdo->prepare("UPDATE school_years SET label = ? WHERE id = ?")->execute([$label, $id]);
        jsonResponse(['success' => true, 'message' => 'School year updated.']);
    }

    // First school year ever created becomes the active one automatically
    $isFirst = (int)$pdo->query("SELECT COUNT(*) FROM school_years")->fetchColumn() === 0;
    $pdo->prepare("INSERT INTO school_years (label, is_active) VALUES (?, ?)")
        ->execute([$label, $isFirst ? 1 : 0]);

    jsonResponse([
        'success' => true,
        'message' => $isFirst ? 'School year created and set active.' : 'School year created.',
        'id'      => (int)$pdo->lastInsertId(),
    ]);
}

// ─── SET ACTIVE SCHOOL YEAR ──────────────────────────────────────────────────
if ($action === 'activate') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'School year ID required.'], 400);

    $check = $pdo->prepare("SELECT label FROM school_years WHERE id = ?");
    $check->execute([$id]);
    $row = $check->fetch();
    if (!$row) jsonResponse(['success' => false, 'message' => 'School year not found.'], 404);

    // Exactly one row must be active at any time
    $pdo->beginTransaction();
    try {
        $pdo->exec("UPDATE school_years SET is_active = 0");
        $pdo->prepare("UPDATE school_years SET is_active = 1 WHERE id = ?")->execute([$id]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Could not switch school year.'], 500);
    }

    jsonResponse(['success' => true, 'message' => "{$row['label']} is now the active school year."]);
}

// ─── DELETE SCHOOL YEAR ──────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'School year ID required.'], 400);

    $check = $pdo->prepare("SELECT label, is_active FROM school_years WHERE id = ?");
    $check->execute([$id]);
    $row = $check->fetch();
    if (!$row) jsonResponse(['success' => false, 'message' => 'School year not found.'], 404);

    if ((int)$row['is_active'] === 1) {
        jsonResponse(['success' => false, 'message' => 'Cannot delete the active school year. Activate another one first.'], 409);
    }

    // Refuse while anything still references it
    foreach ([
        'sections'    => 'section',
        'enrollments' => 'enrollment',
        'grades'      => 'grade',
    ] as $table => $noun) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE school_year_id = ?");
        $stmt->execute([$id]);
        $n = (int)$stmt->fetchColumn();
        if ($n > 0) {
            jsonResponse([
                'success' => false,
                'message' => "Cannot delete {$row['label']} — it still has $n $noun" . ($n === 1 ? '' : 's') . ".",
            ], 409);
        }
    }

    $pdo->prepare("DELETE FROM school_years WHERE id = ?")->execute([$id]);
    jsonResponse(['success' => true, 'message' => "School year {$row['label']} deleted."]);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
