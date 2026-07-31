# Issues

## Resolved — 2026-07-31

### 1. Manage Sections — reject duplicate entries ✅
Adding the same section twice (e.g. Rizal / Grade 7) is now rejected with a red toast:
*"Section "Rizal" already exists for Grade 7 this school year."*

- Guard in `api/sections.php` (`action=save`) — case- and whitespace-insensitive, scoped to
  grade level **and** school year, and it excludes the row being edited so renaming a section
  to its own name still works.
- Covers the UPDATE branch too, so you cannot rename a section onto an existing one.
- Backed by a real DB constraint: `UNIQUE KEY uq_section (name, grade_level, school_year_id)`.
- "Rizal" in Grade 8 is still allowed — the rule is per grade level.

### 2. Manage Sections — same drill-down layout as Manage Grades ✅
`views/admin/manage-sections.php` replaced the card grid with a nested accordion:

**Grade 7 → its sections → that section's students**

- Open a grade to reveal its sections; open a section to reveal its students.
- Search box matches section names **and** student names/LRNs; a hit auto-expands both levels.
- Grade Level filter + Clear button, same as Manage Grades.
- New **Unassigned Students** panel lists students with no section this school year.
- Every existing function is unchanged: New Section, Edit, Delete, Assign Student
  (multi-select + search), Remove student.
- Fixed: `refresh()` never re-fetched the student list, so the summary counts went stale.

### 3. Manage Students — Grade and Section ✅
- Table now has **separate `Grade Level` and `Section` columns** (previously squeezed into one
  cell as `Sampaguita (Gr.10)`). Unenrolled students read `—` / `Unassigned`.
- Add/Edit modal has a new **Enrollment** section with Grade Level + Section dropdowns; the
  section list re-filters when the grade changes.
- Connected logic verified: enrollment goes through `api/sections.php` `enroll`/`unenroll` —
  the *same* code path Manage Sections uses. Re-assigning a student **moves** them (the
  `uq_enrollment` upsert) instead of creating a second row; clearing Grade Level unenrolls.
- The Section filter is now sourced from the sections list, so sections with zero students
  appear in it.
- `register` now returns the new student id and persists gender / birthdate / address, which
  the modal was collecting but silently dropping.

### 4. School Year (S.Y.) menu ✅
New **School Years** item in the Management block of the admin sidebar.

- `views/admin/school-years.php` — table of academic years with status, section / enrollment /
  grade-record counts, and Set Active · Rename · Delete actions.
- `api/school-years.php` — `list`, `save`, `activate`, `delete`, `active`.
  - Label must be `YYYY-YYYY` with consecutive years; duplicates rejected.
  - `activate` is transactional — exactly one year is active at any time.
  - `delete` refuses (409) for the active year or one that still has sections/enrollments/grades.
- Student dashboard and report card now show the real active S.Y. instead of a hard-coded
  "2024–2025".

#### The bug this exposed (fixed)
`LEFT JOIN school_years sy ON sy.id = e.school_year_id AND sy.is_active = 1` filters **nothing** —
putting the predicate in a LEFT JOIN's ON clause is a no-op. It was masked only because a single
school year existed. Creating a second one would have shown **every student twice** on Manage
Students, Manage Grades, Manage Sections and the dashboard.

Corrected in `api/students.php` (×2), `api/reports.php` (×2) and `api/sections.php` (×2) by
scoping the *enrollments* join to the active school year. Verified: before the fix the query
returned 5 rows for 4 students; after, 4 rows.

---

## Supporting changes

- **`config/school-year.php`** (new) — `activeSchoolYear()`, `activeSchoolYearRow()`,
  `activeSchoolYearLabel()`. Replaces the same query that had been copy-pasted inline into four
  files (one of which defined a helper it never called).
- **`assets/css/style.css`** — the accordion / roster / student-item styles were living inline
  in `manage-grades.php`; moved into style.css so both pages share them, plus new
  `.section-accordion` rules for the second nesting level.
- **Cache busting** — every `style.css` link is now `?v=<?= filemtime(...) ?>`. Without it,
  browsers kept serving a stale stylesheet and the new accordion rendered with wrapped buttons.
- **`database/migrations/2026-07-31-sections-unique-and-guardian.sql`** — adds `uq_section` and
  the missing `users.guardian_name` column. **Already applied to the local ogms_lnhs database.**
  `guardian_name` was in the update whitelist and read/written by the student profile page, but
  the column did not exist — saving a guardian threw a PDO exception.

## Notes / not addressed

- `views/admin/manage-students.php` still hard-codes the Status column to "Active" rather than
  reading `is_active`, and `api/students.php?action=list` returns deactivated students.
- `api/students.php` `action=register` is intentionally left unauthenticated — it doubles as the
  public signup endpoint.
- `docs/PROJECT-STRUCTURE.md` is stale; it documents the pre-PHP localStorage prototype.
