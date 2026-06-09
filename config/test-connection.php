<?php
/**
 * config/test-connection.php
 * Visit once to verify DB connection, then DELETE this file.
 */
require_once 'db.php';

try {
    $pdo  = getDB();
    $stmt = $pdo->query('SELECT COUNT(*) AS cnt FROM users');
    $row  = $stmt->fetch();
    echo '<pre style="font-family:monospace;padding:2rem;background:#0f172a;color:#4ade80">';
    echo "Connection OK\n";
    echo "Users in DB: " . $row['cnt'] . "\n";
    echo "\nDelete this file now: config/test-connection.php";
    echo '</pre>';
} catch (PDOException $e) {
    echo '<pre style="color:#f87171;padding:2rem">';
    echo "Connection FAILED:\n" . $e->getMessage();
    echo '</pre>';
}
