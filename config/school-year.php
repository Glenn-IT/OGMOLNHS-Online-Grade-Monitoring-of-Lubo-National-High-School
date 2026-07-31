<?php
// config/school-year.php
// Single source of truth for resolving the active school year.

/**
 * ID of the school year currently flagged is_active.
 * Falls back to 1 so a freshly-installed DB with no active row still works.
 */
function activeSchoolYear(PDO $pdo): int {
    $row = $pdo->query("SELECT id FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();
    return $row ? (int)$row['id'] : 1;
}

/**
 * Full active school year row (id + label), or null when none is set.
 */
function activeSchoolYearRow(PDO $pdo): ?array {
    $row = $pdo->query("SELECT id, label FROM school_years WHERE is_active = 1 LIMIT 1")->fetch();
    return $row ?: null;
}

/**
 * Display label for the active school year, e.g. "2024-2025".
 */
function activeSchoolYearLabel(PDO $pdo): string {
    $row = activeSchoolYearRow($pdo);
    return $row ? $row['label'] : '—';
}
