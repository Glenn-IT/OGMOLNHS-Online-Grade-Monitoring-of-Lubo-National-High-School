# Version Control — OGMS Lubo National High School

## Rollout Schedule

| Version | Feature Unlocked                   | Pages Live (Cumulative)                                                | Pages Still Gated              |
| ------- | ---------------------------------- | ---------------------------------------------------------------------- | ------------------------------ |
| v1.00   | Login / Register / Forgot Password | index.php, views/student/signup.php, views/student/forgot-password.php | All admin & student pages (13) |
| v1.01   | Admin: Dashboard                   | + views/admin/dashboard.php                                            | 12 pages                       |
| v1.02   | Admin: Manage Students             | + views/admin/manage-students.php                                      | 11 pages                       |
| v1.03   | Admin: Manage Grades               | + views/admin/manage-grades.php                                        | 10 pages                       |
| v1.04   | Admin: Manage Sections             | + views/admin/manage-sections.php                                      | 9 pages                        |
| v1.05   | Admin: Analytics                   | + views/admin/analytics.php                                            | 8 pages                        |
| v1.06   | Admin: Reports                     | + views/admin/reports.php                                              | 7 pages                        |
| v1.07   | Admin: SMS Notifications           | + views/admin/sms.php                                                  | 6 pages                        |
| v1.08   | Admin: Profile                     | + views/admin/profile.php                                              | 5 pages                        |
| v1.09   | Student: Dashboard                 | + views/student/dashboard.php                                          | 4 pages                        |
| v1.10   | Student: My Grades                 | + views/student/grades.php                                             | 3 pages                        |
| v1.11   | Student: Analytics                 | + views/student/analytics.php                                          | 2 pages                        |
| v1.12   | Student: Report Card               | + views/student/reports.php                                            | 1 page                         |
| v1.13   | Student: Profile (Full System)     | All pages live                                                         | None                           |

---

## Under Construction Strategy

A single shared component `components/under-construction.php` controls all gating:

- It defines `CURRENT_VERSION` at the top (e.g. `define('CURRENT_VERSION', 'v1.00')`).
- It renders a styled full-page card (hard-hat icon, version badge, Go Back button) and calls `exit` at the bottom — so no page content below it ever executes.
- Every gated page has `<?php require_once '../../components/under-construction.php'; ?>` as its very first line.
- To unlock a page for a new version: remove that one gate line from the page, then bump the `CURRENT_VERSION` constant in `under-construction.php`.
- Pages not yet presented naturally show the Under Construction card when any link reaches them.

---

## Git Commands Per Version

```bash
# Stage only the files changed for this version
git add components/under-construction.php views/admin/dashboard.php

# Commit
git commit -m "feat: implement v1.01 - unlock Admin Dashboard"

# Tag the snapshot
git tag v1.01

# Push branch + tag
git push origin main
git push origin v1.01
```

---

## How Git Tags Work as Permanent Snapshots

A Git tag points to a specific commit and never moves (unlike a branch). When you run `git tag v1.01`, Git records the exact SHA of the current HEAD commit under that name. This means:

- **Reproducible demos** — checking out `git checkout v1.01` restores the repo to exactly the state that was presented.
- **GitHub Releases** — each tag automatically becomes a Release on GitHub that can be downloaded as a ZIP.
- **Safe history** — future commits do not affect old tags.

To browse an old snapshot: `git checkout v1.02` (detached HEAD — read-only). Return to main: `git checkout main`.

---

## GitHub Release Tags

| Version | Tag Name | Commit Hash                              |
| ------- | -------- | ---------------------------------------- |
| v1.00   | v1.00    | a52485037eb84e480da75dd8f2e9f382c837f553 |
| v1.01   | v1.01    | 825273a6a8c83542e19588b4cc0b3cccd927a529 |
| v1.02   | v1.02    | da4d7e3b06f7108cc036537fd01fc92875fac5c7 |
| v1.03   | v1.03    | 021c9d71002f489d130ad8a042ba0107d8d2d717 |
| v1.04   | v1.04    | dfdc661beaac8530ad074ba2927a75a064b2da40 |
| v1.05   | v1.05    | c2b8a9e5b8ed8f96e04f71815590b7dbf4d0d7a7 |
| v1.06   | v1.06    | 061767fcd14dd6a7773f8f6ee7d47787e76d20ae |
| v1.07   | v1.07    | cb86dff4f6a4bf0152b6e206429165bbe21bf687 |
| v1.08   | v1.08    | c55124dc067fdfc0a8f150e0a84fc38ea3e9d435 |
| v1.09   | v1.09    | bcbddcd1ccf898af04f541a06bf70c2b7789d083 |
| v1.10   | v1.10    | a7670fbcd7d47ede098cad4ef10dae8b307e8498 |
| v1.11   | v1.11    | c8f8de1d5bee5670b43a627775ca34b38a2fb6b8 |
| v1.12   | v1.12    | f2bc660e1473f27358a4a469b2c36d90c259f82d |
| v1.13   | v1.13    | 00690232cca04389a153c6929be17d56f34fc549 |

---

## When a Prof or Client Requests Changes After a Presentation

Do The Cherry-Pick

Fix the issue on `main` first, then re-point the tag:

```bash
# 1. Make your fix and commit on main
git checkout main
git add <changed-files>
git commit -m "feat: update [page] per feedback"
git push origin main

# 2. Delete the old tag locally and on remote
git tag -d v1.02
git push origin :refs/tags/v1.02

# 3. Re-create the tag at the new commit
git tag v1.02
git push origin v1.02
```

After re-tagging, update the commit hash in the table above and push the docs update.
