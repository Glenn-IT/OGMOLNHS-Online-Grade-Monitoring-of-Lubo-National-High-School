<?php
require_once '../../config/session.php';
requireStudent();
$studentActivePage = 'reports';
$userId = (int)$_SESSION['user_id'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Report Card – OGMS Student</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../../assets/css/style.css"/>
  <link rel="stylesheet" href="../../assets/css/print.css"/>
</head>
<body>
<div class="app-wrapper">
  <aside class="sidebar no-print"><?php
    ob_start(); include '../../components/student-sidebar.php'; $html = ob_get_clean();
    echo preg_replace('/<aside[^>]*>|<\/aside>/i', '', $html);
  ?></aside>

  <div class="main-content">
    <header class="topbar no-print">
      <div class="topbar-left">
        <button class="topbar-btn hamburger"><i class="fas fa-bars"></i></button>
        <div>
          <div class="topbar-title">Report Card</div>
          <div class="topbar-subtitle">Print or save your official grade report</div>
        </div>
      </div>
      <div class="topbar-right">
        <button class="btn btn-success btn-sm" onclick="window.print()">
          <i class="fas fa-print me-1"></i>Print Report Card
        </button>
      </div>
    </header>

    <main class="page-content fade-in">
      <div class="content-card mb-3 no-print">
        <div class="card-body-custom">
          <div class="row g-2 align-items-end">
            <div class="col-md-4">
              <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600">Grading Period</label>
              <select id="reportPeriod" class="form-select form-select-sm" onchange="generateReport()">
                <option value="0">All Periods</option>
                <option value="1">1st Quarter</option>
                <option value="2">2nd Quarter</option>
                <option value="3">3rd Quarter</option>
                <option value="4">4th Quarter</option>
              </select>
            </div>
            <div class="col-md-4">
              <button class="btn btn-primary btn-sm w-100" onclick="generateReport()">
                <i class="fas fa-sync me-1"></i>Refresh
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
          <p id="printReportTitle">Official Report Card – All Periods</p>
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
  const SESSION_USER_ID = <?= $userId ?>;
  const qLabels = ['All Periods','1st Quarter','2nd Quarter','3rd Quarter','4th Quarter'];

  async function generateReport() {
    const period = document.getElementById('reportPeriod').value;
    document.getElementById('printReportTitle').textContent =
      `Official Report Card – ${qLabels[period]}`;

    const params = new URLSearchParams({action:'student', quarter:period, student_id:SESSION_USER_ID});
    try {
      const res  = await fetch('../../api/reports.php?' + params);
      const data = await res.json();
      if (!data.success) {
        document.getElementById('reportContent').innerHTML =
          `<p class="text-center text-muted py-4">${data.message||'No report data available.'}</p>`;
        return;
      }
      renderReport(data.data, period);
    } catch(e) {
      document.getElementById('reportContent').innerHTML =
        '<p class="text-center text-danger py-4">Failed to load report.</p>';
    }
  }

  function renderReport(data, period) {
    const student  = data.student  || {};
    const subjects = data.subjects || [];
    const genAvg   = data.general_average;
    const date     = new Date().toLocaleDateString('en-PH',{dateStyle:'long'});

    document.getElementById('reportContent').innerHTML = `
      <div class="content-card mb-3">
        <div class="card-header-custom"
          style="background:var(--primary);border-radius:4px 4px 0 0">
          <span class="card-title" style="color:#fff">Student Report Card</span>
          <span style="color:rgba(255,255,255,.8);font-size:.8rem">Generated: ${date}</span>
        </div>
        <div class="card-body-custom">
          <div class="row g-2">
            <div class="col-md-4">
              <div class="info-row"><span class="info-label">Full Name</span>
                <span class="info-value fw-bold">${student.full_name||'—'}</span></div>
            </div>
            <div class="col-md-4">
              <div class="info-row"><span class="info-label">LRN</span>
                <span class="info-value"><code>${student.lrn||'—'}</code></span></div>
            </div>
            <div class="col-md-4">
              <div class="info-row"><span class="info-label">Section</span>
                <span class="info-value">${student.section_name||'—'}</span></div>
            </div>
            <div class="col-md-4">
              <div class="info-row"><span class="info-label">School Year</span>
                <span class="info-value">2024–2025</span></div>
            </div>
            <div class="col-md-4">
              <div class="info-row"><span class="info-label">Period</span>
                <span class="info-value">${qLabels[period]}</span></div>
            </div>
            <div class="col-md-4">
              <div class="info-row"><span class="info-label">Status</span>
                <span class="info-value"><span class="badge bg-success">Enrolled</span></span></div>
            </div>
          </div>
        </div>
      </div>
      <div class="content-card">
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Subject</th>
                <th class="text-center">Q1</th>
                <th class="text-center">Q2</th>
                <th class="text-center">Q3</th>
                <th class="text-center">Q4</th>
                <th class="text-center">Average</th>
                <th class="text-center">Remarks</th>
              </tr>
            </thead>
            <tbody>
              ${subjects.map(r=>`<tr>
                <td><strong>${r.name}</strong></td>
                ${[1,2,3,4].map(q=>`<td class="text-center">
                  ${r['q'+q]!=null?`<span style="font-weight:700;color:${gradeBgColor(r['q'+q])}">${r['q'+q]}</span>`:'—'}
                </td>`).join('')}
                <td class="text-center">${r.avg!=null?gradeCell(r.avg):'—'}</td>
                <td class="text-center">${r.avg!=null?getGradeBadge(r.avg):'—'}</td>
              </tr>`).join('')}
            </tbody>
            <tfoot>
              <tr style="background:#f8fafc;font-weight:700">
                <td colspan="5">General Average</td>
                <td class="text-center">${genAvg?gradeCell(genAvg):'—'}</td>
                <td class="text-center">${genAvg?getGradeBadge(genAvg):'—'}</td>
              </tr>
            </tfoot>
          </table>
        </div>
        <div class="card-body-custom" style="border-top:1px solid #e2e8f0;font-size:.78rem;color:#64748b">
          <div class="row">
            <div class="col-md-8">
              <strong>Grade Description:</strong>
              Outstanding (95-100) &nbsp;|&nbsp; Very Satisfactory (90-94) &nbsp;|&nbsp;
              Satisfactory (85-89) &nbsp;|&nbsp; Fairly Satisfactory (80-84) &nbsp;|&nbsp;
              Did Not Meet Expectations (75-79) &nbsp;|&nbsp; Failed (Below 75)
            </div>
            <div class="col-md-4 text-end">
              <strong>Passing Grade:</strong> 75
            </div>
          </div>
        </div>
      </div>`;
  }

  document.addEventListener('DOMContentLoaded', generateReport);
</script>
</body>
</html>
