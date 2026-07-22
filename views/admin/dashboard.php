<?php
require_once '../../config/session.php';
requireAdmin();
$adminActivePage = 'dashboard';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Admin Dashboard – OGMS</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../../assets/css/style.css"/>
</head>
<body>
<div class="app-wrapper">
  <?php include '../../components/admin-sidebar.php'; ?>

  <div class="main-content">
    <header class="topbar">
      <div class="topbar-left">
        <button class="topbar-btn hamburger"><i class="fas fa-bars"></i></button>
        <div>
          <div class="topbar-title">Admin Dashboard</div>
          <div class="topbar-subtitle" id="topbarDate">—</div>
        </div>
      </div>
      <div class="topbar-right">
        <div class="topbar-btn"><i class="fas fa-bell"></i><span class="badge-notif">—</span></div>
        <div class="topbar-avatar" style="background:#2c3e50"
          onclick="window.location.href='profile.php'">
          <i class="fas fa-user-shield" style="font-size:0.85rem"></i>
        </div>
      </div>
    </header>

    <main class="page-content fade-in">
      <div class="welcome-banner" style="background:#2c3e50">
        <div class="school-badge"><i class="fas fa-shield-alt me-1"></i>Administrator Panel</div>
        <h2>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?>!</h2>
        <p>Manage grades, students, and generate reports for Lubo National High School.</p>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:#f0fdf4"><i class="fas fa-users" style="color:#16a34a"></i></div>
            <div class="stat-label">Total Students</div>
            <div class="stat-value" id="statStudents">—</div>
            <div class="stat-change up"><i class="fas fa-arrow-up me-1"></i>Enrolled</div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:#eff6ff"><i class="fas fa-book" style="color:#1d4ed8"></i></div>
            <div class="stat-label">Total Subjects</div>
            <div class="stat-value" id="statSubjects">—</div>
            <div class="stat-change">Current semester</div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:#fefce8"><i class="fas fa-chart-line" style="color:#d97706"></i></div>
            <div class="stat-label">Average Performance</div>
            <div class="stat-value" id="statAvgPerf">—</div>
            <div class="stat-change up">Class average</div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:#fdf4ff"><i class="fas fa-percentage" style="color:#9333ea"></i></div>
            <div class="stat-label">Passing Rate</div>
            <div class="stat-value" id="statPassRate">—</div>
            <div class="stat-change up">All students</div>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-5">
          <div class="content-card h-100">
            <div class="card-header-custom">
              <span class="card-title"><i class="fas fa-chart-pie me-2 text-warning"></i>Pass / Fail Rate</span>
            </div>
            <div class="chart-container" style="text-align:center">
              <canvas id="passFailChart" height="200"></canvas>
            </div>
          </div>
        </div>
        <div class="col-md-7">
          <div class="content-card h-100">
            <div class="card-header-custom">
              <span class="card-title"><i class="fas fa-chart-bar me-2 text-primary"></i>Grade Distribution</span>
            </div>
            <div class="chart-container"><canvas id="gradeDistChart"></canvas></div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <div class="content-card">
            <div class="card-header-custom">
              <span class="card-title"><i class="fas fa-clock me-2 text-info"></i>Recent Students</span>
              <a href="manage-students.php" class="btn btn-sm btn-outline-primary" style="font-size:0.75rem">View All</a>
            </div>
            <div class="table-wrapper">
              <table class="table">
                <thead><tr><th>Name</th><th>Section</th><th>Status</th></tr></thead>
                <tbody id="recentStudentsBody"><tr><td colspan="3" class="text-center py-3">Loading…</td></tr></tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="content-card">
            <div class="card-header-custom">
              <span class="card-title"><i class="fas fa-star me-2 text-warning"></i>Subject Averages</span>
            </div>
            <div class="chart-container"><canvas id="subjectAvgChart"></canvas></div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<div id="toast-container"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
  document.getElementById('topbarDate').textContent = new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'});

  async function loadDashboard() {
    try {
      const [statsRes, studentsRes] = await Promise.all([
        fetch('../../api/analytics.php?action=summary'),
        fetch('../../api/students.php?action=list'),
      ]);
      const stats    = await statsRes.json();
      const stuData  = await studentsRes.json();
      const students = stuData.data || [];

      document.getElementById('statStudents').textContent  = stats.data?.total_students  ?? students.length;
      document.getElementById('statSubjects').textContent  = stats.data?.total_subjects  ?? '—';
      document.getElementById('statAvgPerf').textContent   = stats.data?.class_avg       ?? '—';
      document.getElementById('statPassRate').textContent  = (stats.data?.pass_rate ?? '—') + (stats.data?.pass_rate != null ? '%' : '');

      const passCount = stats.data?.pass_count ?? 0;
      const failCount = stats.data?.fail_count ?? 0;

      new Chart(document.getElementById('passFailChart'),{
        type:'doughnut',
        data:{labels:['Passed','Failed'],datasets:[{data:[passCount||0.001,failCount||0.001],backgroundColor:['#10b981','#ef4444'],borderWidth:0,hoverOffset:6}]},
        options:{cutout:'72%',plugins:{legend:{position:'bottom'}}}
      });

      const buckets = stats.data?.grade_distribution ?? {'90-100':0,'85-89':0,'80-84':0,'75-79':0,'Below 75':0};
      new Chart(document.getElementById('gradeDistChart'),{
        type:'bar',
        data:{labels:Object.keys(buckets),datasets:[{label:'Students',data:Object.values(buckets),backgroundColor:['#10b981','#3b82f6','#6366f1','#f59e0b','#ef4444'],borderRadius:6,borderWidth:0}]},
        options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}
      });

      // Recent students
      document.getElementById('recentStudentsBody').innerHTML = students.slice(0,5).map(s=>`
        <tr>
          <td><strong>${s.full_name}</strong><br><small class="text-muted">${s.lrn||'—'}</small></td>
          <td>${s.section_name||'—'}</td>
          <td><span class="badge bg-success">Enrolled</span></td>
        </tr>`).join('') || '<tr><td colspan="3" class="text-center text-muted">No students.</td></tr>';

      // Subject averages
      const subAvgs = stats.data?.subject_averages ?? [];
      if (subAvgs.length) {
        new Chart(document.getElementById('subjectAvgChart'),{
          type:'bar',
          data:{
            labels: subAvgs.map(s=>s.name.length>12?s.name.substr(0,12)+'…':s.name),
            datasets:[{label:'Average Grade',data:subAvgs.map(s=>s.avg),backgroundColor:subAvgs.map(s=>gradeBgColor(s.avg)+'cc'),borderRadius:6,borderWidth:0}]
          },
          options:{indexAxis:'y',scales:{x:{min:60,max:100}},plugins:{legend:{display:false}}}
        });
      }
    } catch(e) {
      console.error('Dashboard load error:', e);
    }
  }

  document.addEventListener('DOMContentLoaded', loadDashboard);
</script>
</body>
</html>
