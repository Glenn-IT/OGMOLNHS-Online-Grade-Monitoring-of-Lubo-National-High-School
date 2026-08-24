<?php
require_once '../../config/session.php';
requireStudent();
$studentActivePage = 'dashboard';
$fullName  = $_SESSION['full_name'] ?? 'Student';
$nameParts = explode(' ', $fullName);
$initials  = strtoupper(substr($nameParts[0],0,1) . substr(end($nameParts),0,1));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Dashboard – OGMS Student</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../../assets/css/style.css") ?>"/>
</head>
<body>
<div class="app-wrapper">
  <?php include '../../components/student-sidebar.php'; ?>

  <div class="main-content">
    <header class="topbar">
      <div class="topbar-left">
        <button class="topbar-btn hamburger"><i class="fas fa-bars"></i></button>
        <div>
          <div class="topbar-title">Dashboard</div>
          <div class="topbar-subtitle" id="topbarDate">—</div>
        </div>
      </div>
      <div class="topbar-right">
        <div class="topbar-btn" title="Notifications"><i class="fas fa-bell"></i></div>
        <div class="topbar-avatar" onclick="window.location.href='profile.php'"><?= $initials ?></div>
      </div>
    </header>

    <main class="page-content fade-in">
      <div id="profileAlertBanner"></div>

      <div class="welcome-banner">
        <div class="school-badge"><i class="fas fa-school me-1"></i>Lubo National High School</div>
        <h2>Welcome back, <?= htmlspecialchars($nameParts[0]) ?>! 👋</h2>
        <p id="welcomeSub">Loading your section info…</p>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:#eff6ff"><i class="fas fa-star" style="color:#1d4ed8"></i></div>
            <div class="stat-label">Average Grade</div>
            <div class="stat-value" id="statAvg">—</div>
            <div class="stat-change up" id="statAvgRemark"><i class="fas fa-arrow-up me-1"></i>Overall</div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:#f0fdf4"><i class="fas fa-book" style="color:#16a34a"></i></div>
            <div class="stat-label">Subjects Enrolled</div>
            <div class="stat-value" id="statSubjects">—</div>
            <div class="stat-change">Current semester</div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:#fefce8"><i class="fas fa-trophy" style="color:#d97706"></i></div>
            <div class="stat-label">Highest Q4 Grade</div>
            <div class="stat-value" id="statLatest">—</div>
            <div class="stat-change">4th Quarter</div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:#fdf4ff"><i class="fas fa-check-circle" style="color:#9333ea"></i></div>
            <div class="stat-label">Passed Subjects</div>
            <div class="stat-value" id="statPassed">—</div>
            <div class="stat-change up">This quarter</div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-8">
          <div class="content-card">
            <div class="card-header-custom">
              <span class="card-title"><i class="fas fa-clipboard-list me-2 text-primary"></i>Recent Grades – 4th Quarter</span>
              <a href="grades.php" class="btn btn-sm btn-outline-primary" style="font-size:0.75rem">View All</a>
            </div>
            <div class="table-wrapper">
              <table class="table">
                <thead><tr><th>Subject</th><th>Grade</th><th>Remarks</th></tr></thead>
                <tbody id="recentGradesBody"><tr><td colspan="3" class="text-center py-4 text-muted">Loading…</td></tr></tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="col-lg-4 d-flex flex-column gap-3">
          <div class="content-card">
            <div class="card-header-custom">
              <span class="card-title"><i class="fas fa-chart-pie me-2 text-warning"></i>Performance</span>
            </div>
            <div class="card-body-custom" style="text-align:center">
              <canvas id="miniDoughnutChart" height="160"></canvas>
              <div id="perfLabel" class="mt-2" style="font-size:0.875rem;font-weight:600;color:#374151">—</div>
            </div>
          </div>
          <div class="content-card">
            <div class="card-header-custom">
              <span class="card-title"><i class="fas fa-bolt me-2 text-success"></i>Quick Actions</span>
            </div>
            <div class="card-body-custom d-flex flex-column gap-2">
              <a href="grades.php"    class="btn btn-outline-primary btn-sm w-100 text-start"><i class="fas fa-list me-2"></i>View My Grades</a>
              <a href="analytics.php" class="btn btn-outline-info btn-sm w-100 text-start"><i class="fas fa-chart-line me-2"></i>Performance Analytics</a>
              <a href="reports.php"   class="btn btn-outline-secondary btn-sm w-100 text-start"><i class="fas fa-print me-2"></i>Print Report Card</a>
              <a href="profile.php"   class="btn btn-outline-success btn-sm w-100 text-start"><i class="fas fa-user-edit me-2"></i>Edit Profile</a>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Incomplete Profile Warning Modal -->
<div class="modal fade" id="profileIncompleteModal" tabindex="-1" aria-labelledby="profileIncompleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-warning bg-opacity-25 border-bottom border-warning">
        <h5 class="modal-title fw-bold text-dark" id="profileIncompleteModalLabel">
          <i class="fas fa-exclamation-triangle text-warning me-2"></i>Action Required: Update Personal Details
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div style="width:48px;height:48px;border-radius:50%;background:#fef3c7;display:flex;align-items:center;justify-content:center;color:#d97706;font-size:1.4rem;flex-shrink:0;">
            <i class="fas fa-user-pen"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-1" id="modalStudentGreeting">Welcome to OGMS LNHS!</h6>
            <p class="text-muted small mb-0">Your student profile is missing some essential personal details.</p>
          </div>
        </div>
        <p class="text-secondary" style="font-size:0.9rem;line-height:1.5;">
          To ensure you receive official academic notifications and automated <strong>SMS Grade Alerts</strong> for your parent/guardian, please complete your personal information.
        </p>
        <div class="p-3 bg-light rounded border mb-2">
          <div class="fw-semibold text-dark small mb-2"><i class="fas fa-list-check me-1 text-primary"></i>Missing Information:</div>
          <ul id="missingFieldsList" class="mb-0 text-danger small ps-3"></ul>
        </div>
      </div>
      <div class="modal-footer bg-light border-0">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal" onclick="dismissProfileModal()">Remind Me Later</button>
        <a href="profile.php?edit=1" class="btn btn-primary btn-sm px-3">
          <i class="fas fa-user-edit me-1"></i>Update Personal Information
        </a>
      </div>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
  const SESSION_USER_ID = <?= (int)$_SESSION['user_id'] ?>;
  document.getElementById('topbarDate').textContent = new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'});

  function dismissProfileModal() {
    sessionStorage.setItem('ogms_profile_modal_dismissed', '1');
  }

  async function loadDashboard() {
    try {
      const [profileRes, gradesRes, syRes] = await Promise.all([
        fetch('../../api/students.php?action=get&id=' + SESSION_USER_ID),
        fetch('../../api/grades.php?action=list&student_id=' + SESSION_USER_ID),
        fetch('../../api/school-years.php?action=active'),
      ]);
      const pData = await profileRes.json();
      const gData = await gradesRes.json();
      const syData = await syRes.json();
      const student  = pData.data  || {};
      const grades   = gData.data  || [];
      const subjects = gData.subjects || [];
      const schoolYear = (syData.data && syData.data.label) || '—';

      document.getElementById('welcomeSub').textContent =
        `${student.section_name||'—'} | School Year ${schoolYear}`;

      // Check missing profile details
      const missing = student.missing_fields || [];
      if (missing.length > 0) {
        // Render banner
        const bannerContainer = document.getElementById('profileAlertBanner');
        if (bannerContainer) {
          bannerContainer.innerHTML = `
            <div class="alert alert-warning d-flex align-items-center justify-content-between p-3 mb-4 rounded-3 border-warning" role="alert">
              <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle text-warning fs-4 me-3"></i>
                <div>
                  <strong class="d-block text-dark">Personal Information Incomplete</strong>
                  <span class="text-secondary small">You have ${missing.length} missing detail(s) on your profile. Please update your details to enable parent SMS grade alerts.</span>
                </div>
              </div>
              <a href="profile.php?edit=1" class="btn btn-warning btn-sm fw-bold text-nowrap ms-3">
                <i class="fas fa-edit me-1"></i>Complete Profile
              </a>
            </div>
          `;
        }

        // Show popup modal once per session
        if (!sessionStorage.getItem('ogms_profile_modal_dismissed')) {
          const listEl = document.getElementById('missingFieldsList');
          if (listEl) {
            listEl.innerHTML = missing.map(f => `<li><strong>${f}</strong></li>`).join('');
          }
          const greetingEl = document.getElementById('modalStudentGreeting');
          if (greetingEl && student.full_name) {
            greetingEl.textContent = `Welcome, ${student.full_name.split(' ')[0]}!`;
          }
          const modalEl = document.getElementById('profileIncompleteModal');
          if (modalEl && typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
          }
        }
      }

      const allVals = grades.map(g => parseFloat(g.final_grade));
      const q4      = grades.filter(g => g.quarter == 4);
      const avgAll  = allVals.length ? +(allVals.reduce((a,b)=>a+b,0)/allVals.length).toFixed(2) : 0;
      const passed  = q4.filter(g => parseFloat(g.final_grade) >= 75).length;
      const latest  = q4.length ? Math.max(...q4.map(g=>parseFloat(g.final_grade))) : 0;

      document.getElementById('statAvg').textContent      = avgAll || '—';
      document.getElementById('statSubjects').textContent = subjects.length || '—';
      document.getElementById('statLatest').textContent   = latest  || '—';
      document.getElementById('statPassed').textContent   = `${passed}/${q4.length}`;

      const avgRemark = document.getElementById('statAvgRemark');
      if (avgAll >= 75) {
        avgRemark.className   = 'stat-change up';
        avgRemark.innerHTML   = '<i class="fas fa-arrow-up me-1"></i>Passing';
      } else {
        avgRemark.className   = 'stat-change down';
        avgRemark.innerHTML   = '<i class="fas fa-arrow-down me-1"></i>Below Passing';
      }

      // Q4 table
      const subMap = {};
      subjects.forEach(s => subMap[s.id] = s);
      document.getElementById('recentGradesBody').innerHTML = q4.length
        ? q4.map(g => {
            const sub = subMap[g.subject_id] || {};
            return `<tr>
              <td><strong>${sub.name||'—'}</strong></td>
              <td>${gradeCell(parseFloat(g.final_grade))}</td>
              <td>${getGradeBadge(parseFloat(g.final_grade))}</td>
            </tr>`;
          }).join('')
        : '<tr><td colspan="3" class="text-center text-muted py-3">No grades recorded yet.</td></tr>';

      // Doughnut
      const passCount = allVals.filter(g=>g>=75).length;
      const failCount = allVals.length - passCount;
      const perf      = allVals.length ? +((passCount/allVals.length)*100).toFixed(1) : 0;
      document.getElementById('perfLabel').textContent = `${perf}% Passing Rate`;
      new Chart(document.getElementById('miniDoughnutChart'),{
        type:'doughnut',
        data:{labels:['Passed','Failed'],datasets:[{data:[passCount||0.001,failCount||0.001],backgroundColor:['#10b981','#ef4444'],borderWidth:0,hoverOffset:4}]},
        options:{cutout:'72%',plugins:{legend:{position:'bottom',labels:{font:{size:11},boxWidth:12}}}}
      });
    } catch(e) { console.error('Dashboard error:', e); }
  }

  document.addEventListener('DOMContentLoaded', loadDashboard);
</script>
</body>
</html>
