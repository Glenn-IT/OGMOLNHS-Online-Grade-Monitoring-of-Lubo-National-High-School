<?php
// views/teacher/dashboard.php
require_once '../../config/session.php';
require_once '../../config/db.php';
require_once '../../config/school-year.php';
requireTeacher();

$teacherActivePage = 'dashboard';
$pdo = getDB();
$syId = activeSchoolYear($pdo);
$syLabel = activeSchoolYearLabel($pdo);
$teacherId = (int)$_SESSION['user_id'];

// 1. Fetch subjects assigned to this teacher with grade levels
$stmtTs = $pdo->prepare(
    "SELECT ts.id AS assignment_id, ts.subject_id AS id, s.name, s.code, ts.grade_level
     FROM teacher_subjects ts
     JOIN subjects s ON s.id = ts.subject_id
     WHERE ts.teacher_id = ? AND ts.school_year_id = ?
     ORDER BY s.name, ts.grade_level"
);
$stmtTs->execute([$teacherId, $syId]);
$assignedSubjects = $stmtTs->fetchAll();

if (empty($assignedSubjects)) {
    // Fallback for unmigrated teachers
    $stmtSub = $pdo->prepare(
        "SELECT s.id, s.name, s.code, 7 AS grade_level
         FROM subjects s
         WHERE s.teacher_id = ?
         ORDER BY s.name ASC"
    );
    $stmtSub->execute([$teacherId]);
    $assignedSubjects = $stmtSub->fetchAll();
}
$totalAssignedSubjects = count($assignedSubjects);
$assignedGrades = array_unique(array_filter(array_column($assignedSubjects, 'grade_level')));

// 2. Fetch sections matching assigned grade levels in active school year
if (!empty($assignedGrades)) {
    $inG = implode(',', array_map('intval', $assignedGrades));
    $stmtSec = $pdo->prepare(
        "SELECT s.id, s.name, s.grade_level, COUNT(e.id) AS student_count
         FROM sections s
         LEFT JOIN enrollments e ON e.section_id = s.id AND e.school_year_id = s.school_year_id
         WHERE s.school_year_id = ? AND (s.grade_level IN ($inG) OR s.adviser_id = ?)
         GROUP BY s.id
         ORDER BY s.grade_level, s.name"
    );
    $stmtSec->execute([$syId, $teacherId]);
    $allSections = $stmtSec->fetchAll();
} else {
    $stmtSec = $pdo->prepare(
        "SELECT s.id, s.name, s.grade_level, COUNT(e.id) AS student_count
         FROM sections s
         LEFT JOIN enrollments e ON e.section_id = s.id AND e.school_year_id = s.school_year_id
         WHERE s.school_year_id = ? AND s.adviser_id = ?
         GROUP BY s.id
         ORDER BY s.grade_level, s.name"
    );
    $stmtSec->execute([$syId, $teacherId]);
    $allSections = $stmtSec->fetchAll();
}
$totalSections = count($allSections);
$totalLearners = array_sum(array_column($allSections, 'student_count'));

// 3. Total grades recorded by this teacher in active SY
$stmtGrades = $pdo->prepare(
    "SELECT COUNT(*) FROM grades WHERE encoded_by = ? AND school_year_id = ?"
);
$stmtGrades->execute([$teacherId, $syId]);
$totalGradesRecorded = (int)$stmtGrades->fetchColumn();

// 4. Recent grades encoded by this teacher
$stmtRecent = $pdo->prepare(
    "SELECT g.id, g.quarter, g.final_grade, g.remarks, g.updated_at,
            u.full_name AS student_name, u.lrn,
            sub.name AS subject_name, sub.code AS subject_code,
            s.name AS section_name, s.grade_level
     FROM grades g
     JOIN users u ON u.id = g.student_id
     JOIN subjects sub ON sub.id = g.subject_id
     LEFT JOIN enrollments e ON e.student_id = u.id AND e.school_year_id = g.school_year_id
     LEFT JOIN sections s ON s.id = e.section_id
     WHERE g.encoded_by = ? AND g.school_year_id = ?
     ORDER BY g.updated_at DESC
     LIMIT 5"
);
$stmtRecent->execute([$teacherId, $syId]);
$recentGrades = $stmtRecent->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Teacher Dashboard – OGMS Lubo NHS</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../../assets/css/style.css") ?>"/>
</head>
<body>
<div class="app-wrapper">
  <?php include '../../components/teacher-sidebar.php'; ?>

  <div class="main-content">
    <header class="topbar">
      <div class="topbar-left">
        <button class="topbar-btn hamburger"><i class="fas fa-bars"></i></button>
        <div>
          <div class="topbar-title">Teacher Dashboard</div>
          <div class="topbar-subtitle">Academic Year <?= htmlspecialchars($syLabel) ?> &bull; Subject Teacher Portal</div>
        </div>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar" style="background:#0284c7" onclick="window.location.href='profile.php'">
          <i class="fas fa-chalkboard-teacher" style="font-size:0.85rem"></i>
        </div>
      </div>
    </header>

    <main class="page-content fade-in">
      <div class="welcome-banner" style="background: linear-gradient(135deg, #0c1326 0%, #1e3a8a 100%);">
        <div class="school-badge"><i class="fas fa-chalkboard-teacher me-1"></i>Subject Teacher Portal</div>
        <h2>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?>!</h2>
        <p>Encode, manage, and review quarterly student grades for your assigned teaching subjects across all sections.</p>
      </div>

      <!-- Quick Metrics Strip -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="stat-card text-center p-3 bg-white border rounded">
            <div class="stat-value" style="color:var(--primary); font-size:1.8rem; font-weight:700;"><?= $totalAssignedSubjects ?></div>
            <div class="stat-label text-muted" style="font-size:0.85rem;">My Teaching Subjects</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card text-center p-3 bg-white border rounded">
            <div class="stat-value" style="color:var(--success); font-size:1.8rem; font-weight:700;"><?= $totalSections ?></div>
            <div class="stat-label text-muted" style="font-size:0.85rem;">Class Sections</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card text-center p-3 bg-white border rounded">
            <div class="stat-value" style="color:#0284c7; font-size:1.8rem; font-weight:700;"><?= $totalLearners ?></div>
            <div class="stat-label text-muted" style="font-size:0.85rem;">Enrolled Learners</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card text-center p-3 bg-white border rounded">
            <div class="stat-value" style="color:var(--warning); font-size:1.8rem; font-weight:700;"><?= $totalGradesRecorded ?></div>
            <div class="stat-label text-muted" style="font-size:0.85rem;">Grades Recorded</div>
          </div>
        </div>
      </div>

      <!-- Assigned Subjects Section -->
      <div class="content-card mb-4">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
          <span class="card-title"><i class="fas fa-book me-2 text-primary"></i>My Assigned Teaching Subjects</span>
          <span class="badge bg-primary"><?= $totalAssignedSubjects ?> Assigned</span>
        </div>
        <div class="card-body-custom p-3">
          <?php if (empty($assignedSubjects)): ?>
            <div class="text-center py-5">
              <i class="fas fa-book-reader fa-3x text-muted mb-3"></i>
              <h5 class="text-muted">No Teaching Subjects Assigned Yet</h5>
              <p class="text-muted" style="max-width: 500px; margin: 0 auto; font-size: 0.9rem;">
                Your account is currently not assigned to any teaching subjects. Please contact the <strong>School Administrator</strong> to assign your subjects in <em>Admin &gt; Manage Grades</em> or <em>Admin &gt; Manage Teachers</em>.
              </p>
            </div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($assignedSubjects as $sub): ?>
                <div class="col-md-6 col-lg-4">
                  <div class="card h-100 border shadow-sm">
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size:0.8rem">
                          <?= htmlspecialchars($sub['code'] ?: 'SUB') ?>
                        </span>
                        <?php if (!empty($sub['grade_level'])): ?>
                          <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1" style="font-size:0.75rem">
                            <i class="fas fa-graduation-cap me-1"></i>Grade <?= (int)$sub['grade_level'] ?>
                          </span>
                        <?php else: ?>
                          <span class="text-muted" style="font-size:0.75rem;"><i class="fas fa-check-circle text-success me-1"></i>Active</span>
                        <?php endif; ?>
                      </div>
                      <h5 class="card-title mb-1" style="color:#0c1326; font-weight:700;"><?= htmlspecialchars($sub['name']) ?></h5>
                      <p class="text-muted mb-3" style="font-size:0.82rem;">
                        You are assigned to encode and manage grades for <?= htmlspecialchars($sub['name']) ?><?= !empty($sub['grade_level']) ? ' (Grade ' . (int)$sub['grade_level'] . ')' : '' ?>.
                      </p>
                      <div class="d-grid">
                        <a href="manage-grades.php?subject_id=<?= $sub['id'] ?><?= !empty($sub['grade_level']) ? '&grade_level=' . (int)$sub['grade_level'] : '' ?>" class="btn btn-primary btn-sm">
                          <i class="fas fa-edit me-1"></i>Encode Subject Grades
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Recent Grade Activity & Quick Shortcuts -->
      <div class="row g-3">
        <div class="col-lg-8">
          <div class="content-card">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
              <span class="card-title"><i class="fas fa-history me-2 text-primary"></i>Recent Grade Activity</span>
              <a href="manage-grades.php" class="btn btn-outline-primary btn-sm">View All Grades</a>
            </div>
            <div class="table-wrapper">
              <table class="table align-middle">
                <thead>
                  <tr>
                    <th>Learner</th>
                    <th>Section</th>
                    <th>Subject</th>
                    <th>Term</th>
                    <th>Grade</th>
                    <th>Remarks</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($recentGrades)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-inbox me-2"></i>No recent grades recorded yet.</td></tr>
                  <?php else: ?>
                    <?php foreach ($recentGrades as $rg): ?>
                      <tr>
                        <td><strong><?= htmlspecialchars($rg['student_name']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($rg['lrn']) ?></small></td>
                        <td>Gr. <?= htmlspecialchars($rg['grade_level'] ?? '—') ?> - <?= htmlspecialchars($rg['section_name'] ?? '—') ?></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($rg['subject_code'] ?: $rg['subject_name']) ?></span></td>
                        <td>Term <?= (int)$rg['quarter'] ?></td>
                        <td><strong><?= number_format((float)$rg['final_grade'], 2) ?></strong></td>
                        <td>
                          <?php if ($rg['remarks'] === 'Passed'): ?>
                            <span class="badge bg-success">Passed</span>
                          <?php else: ?>
                            <span class="badge bg-danger">Failed</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="content-card">
            <div class="card-header-custom">
              <span class="card-title"><i class="fas fa-bolt me-2 text-primary"></i>Quick Actions</span>
            </div>
            <div class="card-body-custom p-3 d-flex flex-column gap-2">
              <a href="manage-grades.php" class="btn btn-outline-primary d-flex align-items-center justify-content-between p-2 text-start">
                <span><i class="fas fa-clipboard-list me-2"></i>Manage Subject Grades</span>
                <i class="fas fa-chevron-right text-muted"></i>
              </a>
              <a href="reports.php" class="btn btn-outline-primary d-flex align-items-center justify-content-between p-2 text-start">
                <span><i class="fas fa-table me-2"></i>Section Grade Sheets</span>
                <i class="fas fa-chevron-right text-muted"></i>
              </a>
              <a href="profile.php" class="btn btn-outline-secondary d-flex align-items-center justify-content-between p-2 text-start">
                <span><i class="fas fa-user-cog me-2"></i>Teacher Profile &amp; Password</span>
                <i class="fas fa-chevron-right text-muted"></i>
              </a>
            </div>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/app.js"></script>
</body>
</html>
