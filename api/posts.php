<?php
// api/posts.php
require_once '../config/db.php';
require_once '../config/session.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$pdo    = getDB();

// ─── LIST POSTS (Public or Admin) ───────────────────────────────────────────
if ($action === 'list') {
    $type     = trim($_GET['type'] ?? '');
    $isAdmin  = !empty($_SESSION['role']) && $_SESSION['role'] === 'admin';
    $showAll  = $isAdmin && isset($_GET['all']) && $_GET['all'] === '1';

    $where  = [];
    $params = [];

    if (!$showAll) {
        $where[] = 'is_active = 1';
    }

    if ($type && in_array($type, ['announcement', 'event', 'highlight'])) {
        $where[] = 'type = ?';
        $params[] = $type;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "SELECT id, type, title, content, event_date, author_name, is_active, created_at, updated_at
            FROM school_posts
            $whereSql
            ORDER BY COALESCE(event_date, created_at) DESC, id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

// ─── SAVE POST (insert or update, admin only) ────────────────────────────────
if ($action === 'save') {
    requireAdmin();

    $id         = (int)($_POST['id'] ?? 0);
    $type       = trim($_POST['type'] ?? 'announcement');
    $title      = trim($_POST['title'] ?? '');
    $content    = trim($_POST['content'] ?? '');
    $eventDate  = !empty($_POST['event_date']) ? $_POST['event_date'] : null;
    $authorName = trim($_POST['author_name'] ?? 'School Administration') ?: 'School Administration';
    $isActive   = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

    if (!in_array($type, ['announcement', 'event', 'highlight'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid post type.'], 400);
    }
    if (!$title || !$content) {
        jsonResponse(['success' => false, 'message' => 'Title and content are required.'], 400);
    }

    if ($id) {
        $stmt = $pdo->prepare(
            "UPDATE school_posts
             SET type = ?, title = ?, content = ?, event_date = ?, author_name = ?, is_active = ?
             WHERE id = ?"
        );
        $stmt->execute([$type, $title, $content, $eventDate, $authorName, $isActive, $id]);
        jsonResponse(['success' => true, 'message' => 'Post updated successfully.']);
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO school_posts (type, title, content, event_date, author_name, is_active)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$type, $title, $content, $eventDate, $authorName, $isActive]);
        jsonResponse(['success' => true, 'message' => 'Post created successfully.', 'id' => (int)$pdo->lastInsertId()]);
    }
}

// ─── DELETE POST (admin only) ────────────────────────────────────────────────
if ($action === 'delete') {
    requireAdmin();
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Post ID required.'], 400);
    }

    $pdo->prepare('DELETE FROM school_posts WHERE id = ?')->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'Post deleted successfully.']);
}

// ─── TOGGLE STATUS (admin only) ──────────────────────────────────────────────
if ($action === 'toggle_status') {
    requireAdmin();
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Post ID required.'], 400);
    }

    $stmt = $pdo->prepare('UPDATE school_posts SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?');
    $stmt->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'Post status updated.']);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
