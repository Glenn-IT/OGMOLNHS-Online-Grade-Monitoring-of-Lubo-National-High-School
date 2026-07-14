<?php
require_once '../../config/session.php';
requireStudent();
$studentActivePage = 'analytics';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Analytics – OGMS Student</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../../assets/css/style.css"/>
</head>
<body>
<div class="app-wrapper">
  <?php include '../../components/student-sidebar.php'; ?>

  <div class="main-content">
    <header class="topbar">
      <div class="topbar-left">
        <button class="topbar-btn hamburger"><i class="fas fa-bars"></i></button>
        <div>
          <div class="topbar-title">My Analytics</div>
          <div class="topbar-subtitle">Personal academic performance insights</div>
        </div>
      </div>
    </header>

    <main class="page-content fade-in">
      <div class="row g-3 mb-3" id="analyticsStats">
        <div class="col-6 col-md-3"><div class="stat-card text-center"><div class="stat-value" id="aAvg">—</div><div class="stat-label">Overall Average</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card text-center"><div class="stat-value" style="color:var(--success)" id="aPass">—</div><div class="stat-label">Passing Rate</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card text-center"><div class="stat-value" style="color:var(--warning)" id="aHigh">—</div><div class="stat-label">Highest Grade</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card text-center"><div class="stat-value" style="color:var(--danger)" id="aLow">—</div><div class="stat-label">Lowest Grade</div></div></div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-lg-8">
          <div class="content-card h-100">
            <div class="card-header-custom">
              <span class="card-title"><i class="fas fa-chart-bar me-2 text-primary"></i>Grade by Subject</span>
            </div>
            <div class="chart-container"><canvas id="subjectChart"></canvas></div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="content-card h-100">
            <div class="card-header-custom">
              <span class="card-title"><i class="fas fa-chart-pie me-2 text-warning"></i>Pass / Fail Split</span>
            </div>
            <div class="chart-container" style="text-align:center">
              <canvas id="passDoughnut" height="180"></canvas>
              <div id="doughnutLabel" style="font-size:.85rem;font-weight:600;color:#374151;margin-top:.5rem">—</div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-12">
          <div class="content-card">
            <div class="card-header-custom">
              <span class="card-title"><i class="fas fa-chart-line me-2 text-success"></i>Quarterly Trend (Average)</span>
            </div>
            <div class="chart-container"><canvas id="trendChart"></canvas></div>
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
  const SESSION_USER_ID = <?= (int)$_SESSION['user_id'] ?>;

  async function loadAnalytics() {
    try {
      const res  = await fetch('../../api/grades.php?action=list&student_id=' + SESSION_USER_ID);
      const data = await res.json();
      const grades   = data.data     || [];
      const subjects = data.subjects || [];
      const subMap   = {};
      subjects.forEach(s => subMap[s.id] = s);

      const vals   = grades.map(g => parseFloat(g.final_grade));
      const avg    = vals.length ? +(vals.reduce((a,b)=>a+b,0)/vals.length).toFixed(2) : 0;
      const passed = vals.filter(v=>v>=75).length;
      const pRate  = vals.length ? +((passed/vals.length)*100).toFixed(1) : 0;
      const high   = vals.length ? Math.max(...vals).toFixed(2) : '—';
      const low    = vals.length ? Math.min(...vals).toFixed(2) : '—';

      document.getElementById('aAvg').textContent  = avg  || '—';
      document.getElementById('aPass').textContent = pRate ? pRate+'%' : '—';
      document.getElementById('aHigh').textContent = high;
      document.getElementById('aLow').textContent  = low;

      // Build per-subject averages
      const subTotals = {}, subCounts = {};
      grades.forEach(g => {
        const sid = g.subject_id;
        const fg  = parseFloat(g.final_grade);
        if (!subTotals[sid]) { subTotals[sid] = 0; subCounts[sid] = 0; }
        subTotals[sid] += fg; subCounts[sid]++;
      });
      const subIds  = Object.keys(subTotals);
      const subAvgs = subIds.map(id => +(subTotals[id]/subCounts[id]).toFixed(2));
      const subNames= subIds.map(id => (subMap[id]||{name:'?'}).name);

      new Chart(document.getElementById('subjectChart'),{
        type:'bar',
        data:{labels:subNames,
          datasets:[{label:'Average Grade',data:subAvgs,
            backgroundColor:subAvgs.map(v=>gradeBgColor(v)+'cc'),
            borderColor:subAvgs.map(v=>gradeBgColor(v)),borderWidth:2,borderRadius:6}]},
        options:{scales:{y:{min:60,max:100,grid:{color:'#f1f5f9'}}},
          plugins:{legend:{display:false}},maintainAspectRatio:false}
      });

      const fail = vals.length - passed;
      document.getElementById('doughnutLabel').textContent = `${pRate}% Passing Rate`;
      new Chart(document.getElementById('passDoughnut'),{
        type:'doughnut',
        data:{labels:['Passed','Failed'],
          datasets:[{data:[passed||0.001,fail||0.001],
            backgroundColor:['#10b981','#ef4444'],borderWidth:0,hoverOffset:4}]},
        options:{cutout:'72%',plugins:{legend:{position:'bottom',labels:{font:{size:11},boxWidth:12}}}}
      });

      // Quarterly trend
      const qAvg = [1,2,3,4].map(q => {
        const qVals = grades.filter(g=>g.quarter==q).map(g=>parseFloat(g.final_grade));
        return qVals.length ? +(qVals.reduce((a,b)=>a+b,0)/qVals.length).toFixed(2) : null;
      });
      new Chart(document.getElementById('trendChart'),{
        type:'line',
        data:{labels:['1st Quarter','2nd Quarter','3rd Quarter','4th Quarter'],
          datasets:[{label:'My Average',data:qAvg,
            borderColor:'#7c3aed',backgroundColor:'rgba(124,58,237,.1)',
            borderWidth:3,pointRadius:6,pointBackgroundColor:'#7c3aed',
            fill:true,tension:0.4,spanGaps:true}]},
        options:{scales:{y:{min:60,max:100,grid:{color:'#f1f5f9'}}},
          plugins:{legend:{display:false}},maintainAspectRatio:false}
      });

    } catch(e) { console.error('Analytics error:', e); }
  }

  document.addEventListener('DOMContentLoaded', loadAnalytics);
</script>
</body>
</html>
