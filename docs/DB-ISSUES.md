# Database Issues — Task List
**Found:** June 18, 2026  
**Source:** Live audit of `ogms_lnhs` via XAMPP MySQL

---

## Task 1 — Link subjects to sections

**Status:** Done

**Problem:**  
All 8 subjects have `section_id = NULL` in the `subjects` table. Subjects are not linked to any section, which will break any query that tries to filter grades or subjects by section.

**Decision needed first:**  
Choose one of two approaches before fixing:

- **Option A — Global subjects:** One set of subjects shared school-wide. `section_id` stays NULL and is dropped or ignored. Grade filtering by section goes through `enrollments` instead.
- **Option B — Per-section subjects:** Each section has its own subject assignments. `section_id` is populated for every subject row (or subjects are duplicated per section).

**Fix (Option A):**  
Drop `section_id` from `subjects` or leave it nullable and never use it. Update any query that joins `subjects` on `section_id` to go through `enrollments → grades → subjects` instead.

**Fix (Option B):**  
```sql
UPDATE subjects SET section_id = 4 WHERE id IN (9,10,11,12,13,14,15,16); -- Rizal
-- then duplicate rows for Bonifacio (5) and Mabini (6)
```

---

## Task 2 — Non-sequential IDs

**Status:** Done

**Problem:**  
Primary key IDs do not start at 1 — users begin at 15, sections at 4, subjects at 9. This is a side effect of the database being dropped and re-imported at least once (auto-increment counters were not reset).

**Impact:**  
No functional bug. Foreign keys and joins all work correctly. Purely cosmetic, but can confuse during development when reading raw query results.

**Fix:**  
Re-seed the database from scratch with `AUTO_INCREMENT` reset, or run:
```sql
ALTER TABLE users       AUTO_INCREMENT = 1;
ALTER TABLE sections    AUTO_INCREMENT = 1;
ALTER TABLE subjects    AUTO_INCREMENT = 1;
ALTER TABLE enrollments AUTO_INCREMENT = 1;
ALTER TABLE grades      AUTO_INCREMENT = 1;
ALTER TABLE school_years AUTO_INCREMENT = 1;
```
Then truncate all tables and re-run the seed script.

> Only worth doing before going to production. Not urgent during development.

---

## Task 3 — Non-standard student email (`lea@gmail.com`)

**Status:** Done — removed automatically when the database was reseeded in Task 2

**Problem:**  
Student `Lea Gasmen` (id 29) uses `lea@gmail.com` instead of an `@lnhs.edu.ph` address. All other students use school emails. This was likely added as a manual test entry.

**Impact:**  
No functional bug. However, if the system enforces `@lnhs.edu.ph` email format on login or signup in the future, this account will be blocked.

**Fix:**  
```sql
UPDATE users SET email = 'lea@lnhs.edu.ph' WHERE email = 'lea@gmail.com';
```
Or delete the account if it was only a throwaway test entry:
```sql
DELETE FROM grades      WHERE student_id = 29;
DELETE FROM enrollments WHERE student_id = 29;
DELETE FROM users       WHERE id = 29;
```

---

## Task 4 — Only Grade 10 sections exist

**Status:** Done

**Problem:**  
The `sections` table only has 3 entries, all Grade 10 (Rizal, Bonifacio, Mabini). If the system is meant to handle Grade 7 through Grade 10, sections for the other grade levels are missing.

**Impact:**  
Students in Grade 7, 8, or 9 cannot be enrolled. Admin cannot assign sections outside Grade 10.

**Fix:**  
Add sections for all grade levels that the school uses. Example:
```sql
INSERT INTO sections (name, grade_level, school_year_id) VALUES
('Grade 7 - Rizal',    7,  2),
('Grade 7 - Bonifacio',7,  2),
('Grade 8 - Rizal',    8,  2),
('Grade 8 - Bonifacio',8,  2),
('Grade 9 - Rizal',    9,  2),
('Grade 9 - Bonifacio',9,  2);
```
Adjust names and count to match the actual sections in Lubo National High School.

---

## Status Tracker

| # | Issue | Priority | Status |
|---|---|---|---|
| 1 | `subjects.section_id` all NULL | High | Done |
| 2 | Non-sequential IDs | Low | Done |
| 3 | `lea@gmail.com` test account | Low | Done |
| 4 | Missing Grade 7–9 sections | Medium | Done |
