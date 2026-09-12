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
  <link rel="stylesheet" href="../../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../../assets/css/style.css") ?>"/>
  <link rel="stylesheet" href="../../assets/css/sf9.css?v=<?= filemtime(__DIR__ . "/../../assets/css/sf9.css") ?>"/>
  <link rel="stylesheet" href="../../assets/css/print.css?v=<?= filemtime(__DIR__ . "/../../assets/css/print.css") ?>"/>
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
        <button class="btn btn-success btn-sm" onclick="printSf9Official()">
          <i class="fas fa-print me-1"></i>Print Report Card
        </button>
      </div>
    </header>

    <main class="page-content fade-in">
      <div class="content-card mb-3 no-print">
        <div class="card-body-custom">
          <div class="row g-2 align-items-end">
            <div class="col-md-4">
              <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600">Grading Term</label>
              <select id="reportPeriod" class="form-select form-select-sm" onchange="generateReport()">
                <option value="0">All Terms</option>
                <option value="1">1st Term</option>
                <option value="2">2nd Term</option>
                <option value="3">3rd Term</option>
              </select>
            </div>
            <div class="col-md-4">
              <button class="btn btn-primary btn-sm w-100" onclick="generateReport()">
                <i class="fas fa-sync me-1"></i>Refresh
              </button>
            </div>
            <div class="col-md-4">
              <button class="btn btn-success btn-sm w-100" onclick="printSf9Official()">
                <i class="fas fa-print me-1"></i>Print SF9 Report Card
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="print-header">
        <div class="print-logo print-logo-left">
          <img src="../../assets/images/deped_logo.png" alt="DepEd Seal" class="print-seal">
        </div>
        <div class="print-school-info text-center">
          <p class="print-dept">Republic of the Philippines &bull; Department of Education &bull; Region II</p>
          <p class="print-subdept" style="font-size:0.74rem;text-transform:uppercase;margin:1px 0 0 0;color:#64748b;letter-spacing:0.04em">Schools Division of Cagayan &bull; Sto. Niño District</p>
          <h3 style="margin:3px 0;font-weight:800;letter-spacing:0.5px">LUBO NATIONAL HIGH SCHOOL</h3>
          <p style="margin:0;font-size:0.82rem;color:#475569">Lubo, Sto. Niño, Cagayan &nbsp;|&nbsp; Online Grade Monitoring System</p>
          <p id="printReportTitle" style="font-weight:700;margin-top:4px;font-size:0.95rem;color:#0f172a">Official Report Card – All Terms</p>
        </div>
        <div class="print-logo print-logo-right">
          <img src="../../assets/images/lubo_logo.png" alt="Lubo NHS Seal" class="print-seal">
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
<script src="../../assets/js/sf9-renderer.js?v=<?= filemtime(__DIR__ . "/../../assets/js/sf9-renderer.js") ?>"></script>
<script>
  const SESSION_USER_ID = <?= $userId ?>;
  const qLabels = ['All Terms','1st Term','2nd Term','3rd Term'];

  async function generateReport() {
    const period = document.getElementById('reportPeriod').value;
    document.getElementById('printReportTitle').textContent =
      `Official Report Card – ${qLabels[period]}`;

    const params = new URLSearchParams({action:'student', quarter:period, term:period, student_id:SESSION_USER_ID});
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
      console.error("Failed to load report:", e);
      document.getElementById('reportContent').innerHTML =
        '<p class="text-center text-danger py-4">Failed to load report.</p>';
    }
  }

  function renderReport(data, period) {
    document.getElementById('reportContent').innerHTML = renderSf9ReportCard(data, {
      assetPrefix: '../../assets/images/',
      period: parseInt(period)
    });
  }

  document.addEventListener('DOMContentLoaded', generateReport);
</script>
</body>
</html>
