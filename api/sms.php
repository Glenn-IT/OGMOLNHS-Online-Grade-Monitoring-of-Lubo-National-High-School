<?php
// api/sms.php
// Phase 5.5 — SMS integration is pending (Semaphore API setup not done yet).
// This stub keeps the SMS page functional: logs are shown, sends are queued as 'pending'.
require_once '../config/db.php';
require_once '../config/session.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

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

// ─── SEND SMS (stub — logs as pending) ───────────────────────────────────────
if ($action === 'send') {
    requireAdmin();
    $pdo           = getDB();
    $message       = trim($_POST['message']        ?? '');
    $recipientType = $_POST['recipient_type']       ?? 'single';
    $studentId     = (int)($_POST['student_id']    ?? 0);

    if (!$message) {
        jsonResponse(['success' => false, 'message' => 'Message is required.'], 400);
    }

    $recipients = [];

    if ($recipientType === 'single') {
        if (!$studentId) {
            jsonResponse(['success' => false, 'message' => 'student_id required for single send.'], 400);
        }
        $row = $pdo->prepare("SELECT id, full_name, phone FROM users WHERE id = ? AND role = 'student'");
        $row->execute([$studentId]);
        $s = $row->fetch();
        if ($s) $recipients[] = $s;
    } elseif ($recipientType === 'failed') {
        $stmt = $pdo->query(
            "SELECT DISTINCT u.id, u.full_name, u.phone
             FROM users u
             JOIN grades g ON g.student_id = u.id
             WHERE g.remarks = 'Failed' AND u.role = 'student'"
        );
        $recipients = $stmt->fetchAll();
    } else {
        // all students
        $recipients = $pdo->query(
            "SELECT id, full_name, phone FROM users WHERE role = 'student' AND is_active = 1"
        )->fetchAll();
    }

    if (!$recipients) {
        jsonResponse(['success' => false, 'message' => 'No recipients found.'], 404);
    }

    $ins = $pdo->prepare(
        "INSERT INTO sms_logs (recipient_name, recipient_phone, message, status)
         VALUES (?, ?, ?, 'pending')"
    );

    foreach ($recipients as $r) {
        $ins->execute([$r['full_name'] ?? null, $r['phone'] ?: 'N/A', $message]);
    }

    // TODO Phase 5.5: Replace status 'pending' with real Semaphore API calls here.

    jsonResponse([
        'success' => true,
        'message' => 'SMS queued (pending — Semaphore API not yet configured).',
        'count'   => count($recipients),
    ]);
}

// ─── CLEAR LOGS (admin only) ─────────────────────────────────────────────────
if ($action === 'clear_logs') {
    requireAdmin();
    getDB()->exec("DELETE FROM sms_logs");
    jsonResponse(['success' => true, 'message' => 'SMS log cleared.']);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
