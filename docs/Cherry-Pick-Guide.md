# Cherry-Pick Guide — Fixing a Tagged Version Without Breaking It

## What Is This For?

When you have Git tags that represent **demo snapshots** (like `v1.00`, `v1.01`, etc.) and a professor or client requests a fix on a version you already presented — you need to apply the fix to that specific snapshot **without pulling in unrelated changes from `main`**.

This is what the docs call **"Do The Cherry-Pick."**

---

## Why Not Just Re-Point the Tag to Main?

Simple re-pointing looks like this:

```bash
git tag -d v1.00
git tag v1.00   # now points to current main HEAD
```

**The problem:** `main` HEAD contains all your latest code — later versions, unlocked pages, updated constants. Checking out `v1.00` after this would show `v1.13` behavior. Your demo is broken.

---

## The Correct Approach — True Cherry-Pick

### Step 1 — Branch off the target tag (NOT main)

```bash
git checkout -b temp-fix-v1.00 v1.00
```

You are now at the exact state of `v1.00`. Everything — gated pages, version constants, UI — is as it was when you first tagged it.

### Step 2 — Apply only the fix you need

Edit the file(s). Make the smallest change that solves the problem. Do not touch anything else.

```bash
# Example: hide a link, fix a typo, change a button
# Edit your file here, then stage it
git add <changed-file>
```

### Step 3 — Commit on the temp branch

```bash
git commit -m "fix: describe what you fixed and why (vX.XX)"
```

This commit sits on top of the `v1.00` snapshot, inheriting its exact state + your one fix.

### Step 4 — Re-point the tag to the new commit

```bash
git tag -d v1.00          # delete the old tag
git tag v1.00             # new tag now points to temp branch tip
```

### Step 5 — Return to main and clean up

```bash
git checkout main
git branch -D temp-fix-v1.00
```

The branch is gone but the commit is safe — the tag holds a permanent reference to it.

### Step 6 — Record the new hash in your docs

```bash
git rev-parse v1.00       # copy this hash
```

Update the commit hash table in your version control docs, then commit the docs change on `main`:

```bash
git add docs/Version-Control.md
git commit -m "docs: update vX.XX commit hash after cherry-pick"
```

---

## Full Command Reference (Copy-Paste Ready)

Replace `v1.00`, `index.php`, and the commit message with your actual values.

```bash
# 1. Branch off the target snapshot
git checkout -b temp-fix-v1.00 v1.00

# 2. Make your fix, then stage it
git add index.php

# 3. Commit
git commit -m "fix: describe the fix (v1.00)"

# 4. Re-point the tag
git tag -d v1.00
git tag v1.00

# 5. Get the new hash (for your docs)
git rev-parse HEAD

# 6. Return to main and delete temp branch
git checkout main
git branch -D temp-fix-v1.00

# 7. Update your docs, then commit on main
git add docs/Version-Control.md
git commit -m "docs: update v1.00 commit hash after cherry-pick"

# 8. Push everything (branch + updated tag)
git push origin main
git push origin :refs/tags/v1.00   # delete old tag on remote
git push origin v1.00              # push new tag
```

---

## Tips and Things to Watch Out For

**Only touch what needs fixing.**
The whole point is surgical precision. If you edit three files when only one needed changing, the tag no longer represents a clean snapshot of that version.

**The temp branch is just a vehicle.**
It exists only to let you build the right commit. Once the tag is re-pointed, the branch is worthless — delete it immediately so it doesn't clutter your branch list.

**`git branch -D` vs `git branch -d`.**
Git will refuse `-d` (lowercase) because the temp branch was never merged into `main`. Use `-D` (uppercase) to force-delete. This is safe here because you already re-pointed the tag before deleting.

**Delete the remote tag before pushing the new one.**
Git will reject `git push origin v1.00` if the tag already exists on the remote. Always delete the remote tag first:
```bash
git push origin :refs/tags/v1.00   # deletes remote tag
git push origin v1.00              # pushes new tag
```

**Always update your hash table.**
A stale hash in your docs is misleading. Right after re-tagging, run `git rev-parse v1.00`, paste the new hash in the table, and commit on `main`.

**This works for any project with version tags.**
The approach is not OGMS-specific. Any project where you tag demo or release snapshots and later need to patch a specific version uses this same pattern.

---

## When to Use This vs. The Simple Re-Point

| Situation | Use |
|-----------|-----|
| Tag represents a demo/presentation with specific version state (gated pages, version constants, etc.) | **True Cherry-Pick** (this guide) |
| Tag is just a label for "latest stable" with no version-specific behavior | Simple re-point (`git tag -d` + `git tag`) |
| You want the fix in both the old tag AND main | True Cherry-Pick for the tag, then also apply the fix on main separately |

---

## Real Example From This Project

**Situation:** `v1.00` was presented. The Under Construction page had a "Go Back" button (`javascript:history.back()`) that left the session alive. Fix requested: replace with a Logout button.

**Problem with simple re-point:** `main` is at `v1.13` (all pages live, `CURRENT_VERSION = 'v1.13'`). Re-pointing `v1.00` to main HEAD would show a fully unlocked system during a `v1.00` demo.

**Solution:**
1. Branched off `v1.00` → got the gated state with `CURRENT_VERSION = 'v1.00'`
2. Changed only the button color, icon, href, and label in `components/under-construction.php`
3. Committed → `fdae7cc`
4. Re-pointed `v1.00` tag → `fdae7cc`
5. Back to `main`, deleted temp branch
6. Updated hash in `Version-Control.md`

Checking out `v1.00` now shows: login page only, all other pages gated, Logout button on the Under Construction card. Exactly what was needed.
