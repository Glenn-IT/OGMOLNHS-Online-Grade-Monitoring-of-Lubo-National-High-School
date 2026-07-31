-- database/migrations/2026-07-31-sections-unique-and-guardian.sql
-- Apply to the live ogms_lnhs database (phpMyAdmin → SQL tab, or:
--   mysql -u root ogms_lnhs < database/migrations/2026-07-31-sections-unique-and-guardian.sql)
--
-- Covers:
--   1. UNIQUE constraint backing the duplicate-section check (docs/Issues.md #1)
--   2. The missing users.guardian_name column that views/student/profile.php
--      already reads and writes (would otherwise throw a PDO exception on save)

USE ogms_lnhs;

-- ─────────────────────────────────────────────────────────────────────────────
-- STEP 1 — Find existing duplicates BEFORE adding the constraint.
-- The ALTER below will FAIL if any duplicates exist. Run this first:
--
--   SELECT name, grade_level, school_year_id, COUNT(*) AS copies,
--          GROUP_CONCAT(id ORDER BY id) AS ids
--   FROM sections
--   GROUP BY LOWER(TRIM(name)), grade_level, school_year_id
--   HAVING COUNT(*) > 1;
--
-- For each group, move any enrollments off the extra rows onto the lowest id,
-- then delete the extras. Example for keeping id 3 and dropping id 9:
--
--   UPDATE enrollments SET section_id = 3 WHERE section_id = 9;
--   DELETE FROM sections WHERE id = 9;
-- ─────────────────────────────────────────────────────────────────────────────

-- STEP 2 — Enforce one section name per grade level per school year.
ALTER TABLE sections
  ADD UNIQUE KEY uq_section (name, grade_level, school_year_id);

-- STEP 3 — Add the guardian column the student profile page expects.
ALTER TABLE users
  ADD COLUMN guardian_name VARCHAR(100) NULL AFTER gender;

-- ─── Verify ──────────────────────────────────────────────────────────────────
-- SHOW INDEX FROM sections;          -- expect uq_section listed
-- SHOW COLUMNS FROM users LIKE 'guardian_name';
