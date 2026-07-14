<?php require_once '../../components/under-construction.php'; ?>
<?php
require_once '../../config/session.php';
requireAdmin();
$adminActivePage = 'analytics';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Analytics – OGMS Admin</title>
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
          <div class="topbar-title">Analytics</div>
          <div class="topbar-subtitle">Class and subject performance overview</div>
        </div>
      </div>
    </header>

    <main class="page-content fade-in">
      <div class="page-header">
        <h1>Class Analytics</h1>
        <p>School-wide academic performance insights for all students and subjects.</p>
      </div>

      <div class="row g-3 mb-3" id="analyticsStats"></div>

      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <div class="content-card h-100">
            <div class="card-header-custom">
              <span class="card-title"><i class="fas fa-chart-bar me-2 text-primary"></i>Subject Averages (Class)</span>
            </div>
            <div class="chart-container"><canvas id="classSubjectChart"></canvas></div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="content-card h-100">
            <div class="card-header-custom">
              <span class="card-title"><i class="fas fa-chart-line me-2 text-success"></i>Class Average per Quarter</span>
            </div>
            <div class="chart-container"><canvas id="classTrendChart"></canvas></div>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <div class="content-card h-100">
            <div class="card-header-custom">
              <span class="card-title"><i class="fas fa-chart-pie me-2 text-warning"></i>Subject Average Distribution</span>
            </div>
            <div class="chart-container" style="text-align:center">
              <canvas id="passFailSubjectChart" height="200"></canvas>
            </div>
          </div>
        </div>
        <div class="col-md-8">
          <div class="content-card h-100">
            <div class="card-header-custom">
              <span class="card-title"><i class="fas fa-users me-2 text-info"></i>Student Performance Ranking</span>
            </div>
            <div class="table-wrapper">
              <table class="table">
                <thead><tr><th>Rank</th><th>Student</th><th>Average</th><th>Status</th></tr></thead>
                <tbody id="rankingBody"></tbody>
              </table>
            </div>
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
  async function loadAnalytics() {
    try {
      const res  = await fetch('../../api/analytics.php?action=summary');
      const data = await res.json();
      const d    = data.data || {};

      document.getElementById('analyticsStats').innerHTML = `
        <div class="col-6 col-md-3"><div class="stat-card text-center">
          <div class="stat-value" style="color:var(--primary)">${d.class_avg??'—'}</div>
          <div class="stat-label">Class Average</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card text-center">
          <div class="stat-value" style="color:var(--success)">${d.pass_rate!=null?d.pass_rate+'%':'—'}</div>
          <div class="stat-label">Passing Rate</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card text-center">
          <div class="stat-value" style="color:var(--warning)">${d.highest??'—'}</div>
          <div class="stat-label">Highest Grade</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card text-center">
          <div class="stat-value" style="color:var(--danger)">${d.lowest??'—'}</div>
          <div class="stat-label">Lowest Grade</div></div></div>`;

      const subAvgs = d.subject_averages || [];
      if (subAvgs.length) {
        new Chart(document.getElementById('classSubjectChart'),{
          type:'bar',
          data:{labels:subAvgs.map(s=>s.name.length>10?s.name.substr(0,10)+'…':s.name),
            datasets:[{label:'Class Average',data:subAvgs.map(s=>s.avg),
              backgroundColor:subAvgs.map(s=>gradeBgColor(s.avg)+'cc'),
              borderColor:subAvgs.map(s=>gradeBgColor(s.avg)),borderWidth:2,borderRadius:6}]},
          options:{scales:{y:{min:60,max:100}},plugins:{legend:{display:false}},maintainAspectRatio:false}
        });

        new Chart(document.getElementById('passFailSubjectChart'),{
          type:'doughnut',
          data:{labels:subAvgs.map(s=>s.name.substr(0,8)),
            datasets:[{data:subAvgs.map(s=>s.avg),
              backgroundColor:subAvgs.map(s=>gradeBgColor(s.avg)+'cc'),borderWidth:0,hoverOffset:6}]},
          options:{plugins:{legend:{position:'bottom',labels:{font:{size:10},boxWidth:10}}}}
        });
      }

      const quarterAvgs = d.quarter_averages || [];
      new Chart(document.getElementById('classTrendChart'),{
        type:'line',
        data:{labels:['1st Quarter','2nd Quarter','3rd Quarter','4th Quarter'],
          datasets:[{label:'Class Average',data:quarterAvgs,
            borderColor:'#7c3aed',backgroundColor:'rgba(124,58,237,.1)',
            borderWidth:3,pointRadius:6,pointBackgroundColor:'#7c3aed',fill:true,tension:0.4}]},
        options:{scales:{y:{min:60,max:100}},plugins:{legend:{display:false}},maintainAspectRatio:false}
      });

      const ranking = d.student_ranking || [];
      const medals  = ['🥇','🥈','🥉'];
      document.getElementById('rankingBody').innerHTML = ranking.map((r,i)=>`<tr>
        <td><strong>${medals[i]??'#'+(i+1)}</strong></td>
        <td><strong>${r.full_name}</strong><br><small class="text-muted">${r.lrn||'—'}</small></td>
        <td>${r.avg>0?gradeCell(r.avg):'—'}</td>
        <td>${r.avg>0?getGradeBadge(r.avg):'—'}</td>
      </tr>`).join('') || '<tr><td colspan="4" class="text-muted text-center">No data.</td></tr>';

    } catch(e) { console.error('Analytics error:', e); }
  }

  document.addEventListener('DOMContentLoaded', loadAnalytics);
</script>
</body>
</html>
