<?php require_once '../../components/under-construction.php'; ?>
<?php
require_once '../../config/session.php';
requireAdmin();
$adminActivePage = 'reports';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Reports – OGMS Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../../assets/css/style.css"/>
  <link rel="stylesheet" href="../../assets/css/print.css"/>
</head>
<body>
<div class="app-wrapper">
  <aside class="sidebar no-print"><?php
    // Inline only the aside inner content via include trick
    ob_start(); include '../../components/admin-sidebar.php'; $sidebarHtml = ob_get_clean();
    // Strip outer <aside> tags since we wrapped above
    echo preg_replace('/<aside[^>]*>|<\/aside>/i','',$sidebarHtml);
  ?></aside>

  <div class="main-content">
    <header class="topbar no-print">
      <div class="topbar-left">
        <button class="topbar-btn hamburger"><i class="fas fa-bars"></i></button>
        <div>
          <div class="topbar-title">Reports</div>
          <div class="topbar-subtitle">Generate class and student reports</div>
        </div>
      </div>
      <div class="topbar-right">
        <button class="btn btn-success btn-sm" onclick="window.print()">
          <i class="fas fa-print me-1"></i>Print Report
        </button>
      </div>
    </header>

    <main class="page-content fade-in">
      <div class="content-card mb-3 no-print">
        <div class="card-body-custom">
          <div class="row g-2 align-items-end">
            <div class="col-md-3">
              <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600">Report Type</label>
              <select id="reportType" class="form-select form-select-sm" onchange="onTypeChange()">
                <option value="class">Class Summary Report</option>
                <option value="subject">Subject Performance</option>
                <option value="student">Individual Student</option>
              </select>
            </div>
            <div class="col-md-3" id="studentSelectGroup" style="display:none">
              <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600">Select Student</label>
              <select id="reportStudent" class="form-select form-select-sm" onchange="generateReport()"></select>
            </div>
            <div class="col-md-2">
              <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600">Grading Period</label>
              <select id="reportPeriod" class="form-select form-select-sm" onchange="generateReport()">
                <option value="0">All Periods</option>
                <option value="1">1st Quarter</option><option value="2">2nd Quarter</option>
                <option value="3">3rd Quarter</option><option value="4">4th Quarter</option>
              </select>
            </div>
            <div class="col-md-2">
              <button class="btn btn-primary btn-sm w-100" onclick="generateReport()">
                <i class="fas fa-sync me-1"></i>Generate
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="print-header">
        <div class="print-logo"><i class="fas fa-graduation-cap"></i></div>
        <div class="print-school-info">
          <h3>Lubo National High School</h3>
          <p>Brgy. Lubo, Cavite City &nbsp;|&nbsp; Online Grade Monitoring System</p>
          <p id="printReportTitle">Class Summary Report – All Periods</p>
        </div>
      </div>

      <div id="reportContent"></div>
    </main>
  </div>
</div>

<div id="toast-container"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
  let studentsCache = [], reportData = null;

  function onTypeChange() {
    const type = document.getElementById('reportType').value;
    document.getElementById('studentSelectGroup').style.display = type === 'student' ? 'block' : 'none';
    generateReport();
  }

  async function generateReport() {
    const type    = document.getElementById('reportType').value;
    const period  = document.getElementById('reportPeriod').value;
    const stuId   = document.getElementById('reportStudent').value;
    const qLabels = ['All Periods','1st Quarter','2nd Quarter','3rd Quarter','4th Quarter'];
    document.getElementById('printReportTitle').textContent =
      `${type==='class'?'Class Summary':type==='subject'?'Subject Performance':'Individual Student'} Report – ${qLabels[period]}`;

    const params = new URLSearchParams({action:type, quarter:period});
    if (type === 'student' && stuId) params.set('student_id', stuId);

    try {
      const res  = await fetch('../../api/reports.php?' + params);
      const data = await res.json();
      if (!data.success) { document.getElementById('reportContent').innerHTML = '<p class="text-muted text-center py-4">' + (data.message||'No data.') + '</p>'; return; }
      renderReport(type, data.data, period);
    } catch(e) { document.getElementById('reportContent').innerHTML = '<p class="text-danger text-center py-4">Failed to load report.</p>'; }
  }

  function renderReport(type, data, period) {
    const qLabels = ['','1st','2nd','3rd','4th'];
    const date = new Date().toLocaleDateString('en-PH',{dateStyle:'long'});

    if (type === 'class') {
      const rows  = data.students || [];
      const stats = data.stats || {};
      document.getElementById('reportContent').innerHTML = `
        <div class="content-card mb-3">
          <div class="card-header-custom" style="background:var(--primary);border-radius:4px 4px 0 0">
            <span class="card-title" style="color:#fff">Class Summary Report</span>
            <span style="color:rgba(255,255,255,.8);font-size:.8rem">Generated: ${date}</span>
          </div>
          <div class="card-body-custom">
            <div class="row g-3 mb-3">
              <div class="col-md-3 text-center"><div class="stat-card"><div class="stat-value" style="color:var(--primary)">${stats.total||0}</div><div class="stat-label">Total Students</div></div></div>
              <div class="col-md-3 text-center"><div class="stat-card"><div class="stat-value" style="color:var(--success)">${stats.class_avg||0}</div><div class="stat-label">Class Average</div></div></div>
              <div class="col-md-3 text-center"><div class="stat-card"><div class="stat-value" style="color:var(--warning)">${stats.highest||0}</div><div class="stat-label">Highest Avg</div></div></div>
              <div class="col-md-3 text-center"><div class="stat-card"><div class="stat-value" style="color:var(--danger)">${stats.lowest||0}</div><div class="stat-label">Lowest Avg</div></div></div>
            </div>
          </div>
        </div>
        <div class="content-card">
          <div class="table-wrapper">
            <table class="table">
              <thead><tr><th>Rank</th><th>Student</th><th>LRN</th><th>Section</th><th>Average</th><th>Description</th><th>Remarks</th></tr></thead>
              <tbody>${rows.map((r,i)=>`<tr>
                <td>${i+1}</td><td><strong>${r.full_name}</strong></td>
                <td><code style="font-size:.78rem">${r.lrn||'—'}</code></td>
                <td>${r.section_name||'—'}</td>
                <td>${r.avg!=null?gradeCell(r.avg):'—'}</td>
                <td><span class="${r.avg!=null?gradeClass(r.avg):''}" style="font-size:.8rem">${r.avg!=null?getGradeDesc(r.avg):'—'}</span></td>
                <td>${r.avg!=null?getGradeBadge(r.avg):'—'}</td>
              </tr>`).join('')}</tbody>
            </table>
          </div>
        </div>`;

    } else if (type === 'subject') {
      const rows = data.subjects || [];
      document.getElementById('reportContent').innerHTML = `
        <div class="content-card">
          <div class="card-header-custom">
            <span class="card-title">Subject Performance Report</span>
            <span style="font-size:.8rem;color:#64748b">Generated: ${date}</span>
          </div>
          <div class="table-wrapper">
            <table class="table">
              <thead><tr><th>Subject</th><th>Class Avg</th><th>Highest</th><th>Lowest</th><th>Pass</th><th>Fail</th></tr></thead>
              <tbody>${rows.map(r=>`<tr>
                <td><strong>${r.name}</strong></td>
                <td>${r.avg!=null?gradeCell(r.avg):'—'}</td>
                <td style="color:var(--success);font-weight:700">${r.highest||'—'}</td>
                <td style="color:var(--danger);font-weight:700">${r.lowest||'—'}</td>
                <td><span class="badge bg-success">${r.pass_count||0}</span></td>
                <td><span class="badge bg-danger">${r.fail_count||0}</span></td>
              </tr>`).join('')}</tbody>
            </table>
          </div>
        </div>`;

    } else {
      const student = data.student || {};
      const subjects = data.subjects || [];
      const genAvg  = data.general_average;
      document.getElementById('reportContent').innerHTML = `
        <div class="content-card mb-3">
          <div class="card-header-custom" style="background:var(--primary);border-radius:4px 4px 0 0">
            <span class="card-title" style="color:#fff">Individual Student Report</span>
            <span style="color:rgba(255,255,255,.8);font-size:.8rem">Generated: ${date}</span>
          </div>
          <div class="card-body-custom">
            <div class="row g-2">
              <div class="col-md-3"><div class="info-row"><span class="info-label">Name</span><span class="info-value">${student.full_name||'—'}</span></div></div>
              <div class="col-md-3"><div class="info-row"><span class="info-label">LRN</span><span class="info-value">${student.lrn||'—'}</span></div></div>
              <div class="col-md-3"><div class="info-row"><span class="info-label">Section</span><span class="info-value">${student.section_name||'—'}</span></div></div>
              <div class="col-md-3"><div class="info-row"><span class="info-label">Status</span><span class="info-value"><span class="badge bg-success">Enrolled</span></span></div></div>
            </div>
          </div>
        </div>
        <div class="content-card">
          <div class="table-wrapper">
            <table class="table">
              <thead><tr><th>Subject</th><th>Q1</th><th>Q2</th><th>Q3</th><th>Q4</th><th>Average</th><th>Remarks</th></tr></thead>
              <tbody>${subjects.map(r=>`<tr>
                <td><strong>${r.name}</strong></td>
                ${[1,2,3,4].map(q=>`<td class="text-center">${r['q'+q]!=null?`<span style="font-weight:700;color:${gradeBgColor(r['q'+q])}">${r['q'+q]}</span>`:'—'}</td>`).join('')}
                <td>${r.avg!=null?gradeCell(r.avg):'—'}</td>
                <td>${r.avg!=null?getGradeBadge(r.avg):'—'}</td>
              </tr>`).join('')}</tbody>
              <tfoot><tr style="background:#f8fafc">
                <td colspan="5"><strong>General Average</strong></td>
                <td>${genAvg?gradeCell(genAvg):'—'}</td>
                <td>${genAvg?getGradeBadge(genAvg):'—'}</td>
              </tr></tfoot>
            </table>
          </div>
        </div>`;
    }
  }

  async function init() {
    const res  = await fetch('../../api/students.php?action=list');
    const data = await res.json();
    studentsCache = data.data || [];
    const sel = document.getElementById('reportStudent');
    studentsCache.forEach(s => sel.innerHTML += `<option value="${s.id}">${s.full_name}</option>`);
    generateReport();
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
