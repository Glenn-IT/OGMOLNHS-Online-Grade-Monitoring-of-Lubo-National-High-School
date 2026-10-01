<?php
// views/teacher/reports.php
require_once '../../config/session.php';
require_once '../../config/db.php';
require_once '../../config/school-year.php';
requireTeacher();

$teacherActivePage = 'reports';
$pdo = getDB();
$syId = activeSchoolYear($pdo);
$syLabel = activeSchoolYearLabel($pdo);
$teacherName = $_SESSION['full_name'] ?? 'Class Adviser';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>SF9 Reports – Faculty Portal | Lubo NHS</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../../assets/css/style.css") ?>"/>
  <link rel="stylesheet" href="../../assets/css/sf9.css?v=<?= filemtime(__DIR__ . "/../../assets/css/sf9.css") ?>"/>
  <link rel="stylesheet" href="../../assets/css/print.css?v=<?= filemtime(__DIR__ . "/../../assets/css/print.css") ?>"/>
</head>
<body>
<div class="app-wrapper">
  <aside class="sidebar no-print"><?php
    ob_start(); include '../../components/teacher-sidebar.php'; $sidebarHtml = ob_get_clean();
    echo preg_replace('/<aside[^>]*>|<\/aside>/i','',$sidebarHtml);
  ?></aside>

  <div class="main-content">
    <header class="topbar no-print">
      <div class="topbar-left">
        <button class="topbar-btn hamburger"><i class="fas fa-bars"></i></button>
        <div>
          <div class="topbar-title">Student Report Cards (SF9)</div>
          <div class="topbar-subtitle">Generate official DepEd School Form 9 and class summary reports for SY <?= htmlspecialchars($syLabel) ?></div>
        </div>
      </div>
      <div class="topbar-right">
        <button class="btn btn-success btn-sm" onclick="printReport()">
          <i class="fas fa-print me-1"></i>Print Report
        </button>
      </div>
    </header>

    <main class="page-content fade-in">
      <div class="content-card mb-3 no-print">
        <div class="card-body-custom p-3">
          <div class="row g-2 align-items-end">
            <div class="col-md-3">
              <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600">Report Type</label>
              <select id="reportType" class="form-select form-select-sm" onchange="onTypeChange()">
                <option value="subject" selected>Subject Performance Summary</option>
                <option value="student">Individual Student SF9 Report Card</option>
                <option value="class">Class Summary Report</option>
              </select>
            </div>

            <div class="col-md-3" id="quarterFilterCol">
              <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600">Grading Term</label>
              <select id="filterQuarter" class="form-select form-select-sm" onchange="generateReport()">
                <option value="0">Full School Year (Complete)</option>
                <option value="1">1st Term Only</option>
                <option value="2">2nd Term Only</option>
                <option value="3">3rd Term Only</option>
              </select>
            </div>

            <div class="col-md-4" id="studentFilterCol" style="display:none;">
              <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600">Select Student</label>
              <select id="filterStudent" class="form-select form-select-sm" onchange="generateReport()">
                <option value="">Loading students…</option>
              </select>
            </div>

            <div class="col-md-2 text-end">
              <button class="btn btn-primary btn-sm w-100" onclick="generateReport()">
                <i class="fas fa-sync-alt me-1"></i>Generate
              </button>
            </div>
          </div>

          <!-- Signatories Toolbar (for SF9) -->
          <div class="row g-2 mt-2 pt-2 border-top align-items-center" id="signatoriesBar" style="display:none;">
            <div class="col-auto">
              <span class="badge bg-light text-dark border"><i class="fas fa-signature me-1 text-primary"></i>Signatories</span>
            </div>
            <div class="col-md-4">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="fas fa-user-edit text-muted"></i></span>
                <input type="text" id="customAdviserInput" class="form-control" placeholder="Subject Teacher / Adviser" value="<?= htmlspecialchars($teacherName) ?>" oninput="syncSignatories()"/>
              </div>
            </div>
            <div class="col-md-4">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="fas fa-user-tie text-muted"></i></span>
                <input type="text" id="customPrincipalInput" class="form-control" placeholder="School Principal" value="DR. GLENN M. LUBO" oninput="syncSignatories()"/>
              </div>
            </div>
            <div class="col-md-2 text-muted" style="font-size:0.75rem;">
              <i class="fas fa-info-circle me-1"></i>Edits sync live to print view
            </div>
          </div>

        </div>
      </div>

      <!-- Printable Report Container -->
      <div id="reportContainer">
        <div class="text-center py-5 text-muted">
          <i class="fas fa-file-pdf fa-2x mb-2 text-secondary"></i>
          <div>Select a report type and click <strong>Generate</strong>.</div>
        </div>
      </div>

    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/app.js"></script>
<script src="../../assets/js/sf9-renderer.js"></script>
<script>
  let enrolledStudents = [];

  async function init() {
    await loadEnrolledStudents();
    onTypeChange();

    const urlParams = new URLSearchParams(window.location.search);
    const stuParam = urlParams.get('student_id');
    if (stuParam) {
      document.getElementById('reportType').value = 'student';
      onTypeChange();
      document.getElementById('filterStudent').value = stuParam;
      generateReport();
    } else {
      generateReport();
    }
  }

  async function loadEnrolledStudents() {
    try {
      const secRes = await fetch('../../api/sections.php?action=list');
      const secData = await secRes.json();
      const sections = secData.data || [];

      enrolledStudents = [];
      for (const sec of sections) {
        const stuRes = await fetch(`../../api/sections.php?action=students&section_id=${sec.id}`);
        const stuData = await stuRes.json();
        const list = stuData.data || [];
        list.forEach(s => s.section_name = sec.name);
        enrolledStudents = enrolledStudents.concat(list);
      }

      const sel = document.getElementById('filterStudent');
      if (!enrolledStudents.length) {
        sel.innerHTML = '<option value="">No enrolled students found</option>';
        return;
      }

      sel.innerHTML = enrolledStudents.map(s =>
        `<option value="${s.id}">${esc(s.full_name)} (${esc(s.section_name||'')} - LRN: ${esc(s.lrn||'—')})</option>`
      ).join('');
    } catch(e) {}
  }

  function onTypeChange() {
    const type = document.getElementById('reportType').value;
    const isStudent = type === 'student';
    document.getElementById('studentFilterCol').style.display = isStudent ? '' : 'none';
    document.getElementById('signatoriesBar').style.display = isStudent ? '' : 'none';
  }

  function esc(s) {
    return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  async function generateReport() {
    const type = document.getElementById('reportType').value;
    const quarter = parseInt(document.getElementById('filterQuarter').value) || 0;
    const container = document.getElementById('reportContainer');

    showLoading();
    try {
      if (type === 'student') {
        const studentId = document.getElementById('filterStudent').value;
        if (!studentId) {
          container.innerHTML = '<div class="alert alert-warning">Please select a student.</div>';
          hideLoading();
          return;
        }

        const res = await fetch(`../../api/reports.php?action=student&student_id=${studentId}&quarter=${quarter}`);
        const data = await res.json();
        hideLoading();

        if (!data.success) {
          container.innerHTML = `<div class="alert alert-danger">${esc(data.message)}</div>`;
          return;
        }

        renderSf9Report(data, quarter);
      } else if (type === 'subject') {
        const res = await fetch(`../../api/reports.php?action=subject&quarter=${quarter}`);
        const data = await res.json();
        hideLoading();

        if (!data.success) {
          container.innerHTML = `<div class="alert alert-danger">${esc(data.message)}</div>`;
          return;
        }

        renderSubjectPerformance(data, quarter);
      } else {
        const res = await fetch(`../../api/reports.php?action=class&quarter=${quarter}`);
        const data = await res.json();
        hideLoading();

        if (!data.success) {
          container.innerHTML = `<div class="alert alert-danger">${esc(data.message)}</div>`;
          return;
        }

        renderClassSummary(data, quarter);
      }
    } catch (e) {
      hideLoading();
      container.innerHTML = '<div class="alert alert-danger">Error loading report. Please try again.</div>';
    }
  }

  function renderSubjectPerformance(data, quarter) {
    const container = document.getElementById('reportContainer');
    const termLabel = quarter === 0 ? 'Full School Year' : `Term ${quarter}`;
    const subjects = data.data?.subjects || [];

    if (!subjects.length) {
      container.innerHTML = `
        <div class="content-card p-4 text-center text-muted">
          <i class="fas fa-book-reader fa-2x mb-2 text-secondary"></i>
          <h5>No Subject Grades Recorded Yet</h5>
          <p style="font-size:0.9rem">No grades have been recorded yet for your assigned teaching subjects in ${termLabel}.</p>
        </div>`;
      return;
    }

    const rows = subjects.map((sub, idx) => {
      const passCount = parseInt(sub.pass_count) || 0;
      const failCount = parseInt(sub.fail_count) || 0;
      const total = passCount + failCount;
      const passRate = total > 0 ? ((passCount / total) * 100).toFixed(1) + '%' : '—';

      return `
        <tr>
          <td style="text-align:center">${idx + 1}</td>
          <td><strong>${esc(sub.name)}</strong></td>
          <td><code>${esc(sub.code||'—')}</code></td>
          <td style="text-align:center;font-weight:bold">${sub.avg !== null ? parseFloat(sub.avg).toFixed(2) : '—'}</td>
          <td style="text-align:center;color:#16a34a">${sub.highest !== null ? parseFloat(sub.highest).toFixed(2) : '—'}</td>
          <td style="text-align:center;color:#dc2626">${sub.lowest !== null ? parseFloat(sub.lowest).toFixed(2) : '—'}</td>
          <td style="text-align:center"><span class="badge bg-success">${passCount}</span></td>
          <td style="text-align:center"><span class="badge bg-danger">${failCount}</span></td>
          <td style="text-align:center;font-weight:bold">${passRate}</td>
        </tr>`;
    }).join('');

    container.innerHTML = `
      <div class="content-card p-4 printable-area">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
          <img src="../../assets/images/deped_logo.png" style="width:55px;height:55px;object-fit:contain;"/>
          <div class="text-center">
            <h5 class="mb-0 fw-bold">LUBO NATIONAL HIGH SCHOOL</h5>
            <small class="text-muted">Teaching Subject Performance Summary (${termLabel})</small>
          </div>
          <img src="../../assets/images/lubo_logo.png" style="width:55px;height:55px;object-fit:contain;"/>
        </div>

        <table class="table table-bordered table-striped" style="font-size:0.88rem">
          <thead>
            <tr>
              <th style="width:40px;text-align:center">#</th>
              <th>Subject</th>
              <th>Code</th>
              <th style="text-align:center">Average</th>
              <th style="text-align:center">Highest</th>
              <th style="text-align:center">Lowest</th>
              <th style="text-align:center">Passed</th>
              <th style="text-align:center">Failed</th>
              <th style="text-align:center">Passing Rate</th>
            </tr>
          </thead>
          <tbody>
            ${rows}
          </tbody>
        </table>
      </div>`;
  }

  function renderSf9Report(data, term) {
    const container = document.getElementById('reportContainer');
    const adviserName = document.getElementById('customAdviserInput').value.trim();
    const principalName = document.getElementById('customPrincipalInput').value.trim();

    container.innerHTML = renderSf9ReportCard(data, {
      editable: false,
      filterQuarter: term,
      assetPrefix: '../../',
      adviserOverride: adviserName,
      principalOverride: principalName,
    });
  }

  function syncSignatories() {
    const adv = document.getElementById('customAdviserInput').value.trim();
    const pri = document.getElementById('customPrincipalInput').value.trim();

    document.querySelectorAll('.sig-adviser-name').forEach(el => el.textContent = adv || 'Class Adviser');
    document.querySelectorAll('.sig-principal-name').forEach(el => el.textContent = pri || 'School Principal');
  }

  function renderClassSummary(data, quarter) {
    const container = document.getElementById('reportContainer');
    const termLabel = quarter === 0 ? 'Full School Year' : `Term ${quarter}`;

    const rows = (data.students || []).map((s, idx) => `
      <tr>
        <td style="text-align:center">${idx + 1}</td>
        <td><strong>${esc(s.full_name)}</strong></td>
        <td><code>${esc(s.lrn||'—')}</code></td>
        <td>${esc(s.section_name||'—')}</td>
        <td style="text-align:center;font-weight:bold">${s.avg !== null ? parseFloat(s.avg).toFixed(2) : '—'}</td>
        <td style="text-align:center">${s.avg !== null ? (parseFloat(s.avg) >= 75 ? '<span class="badge bg-success">Passed</span>' : '<span class="badge bg-danger">Failed</span>') : '—'}</td>
      </tr>
    `).join('');

    container.innerHTML = `
      <div class="content-card p-4 printable-area">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
          <img src="../../assets/images/deped_logo.png" style="width:55px;height:55px;object-fit:contain;"/>
          <div class="text-center">
            <h5 class="mb-0 fw-bold">LUBO NATIONAL HIGH SCHOOL</h5>
            <small class="text-muted">Advisory Class Summary Performance (${termLabel})</small>
          </div>
          <img src="../../assets/images/lubo_logo.png" style="width:55px;height:55px;object-fit:contain;"/>
        </div>

        <div class="row g-2 mb-3 bg-light p-2 rounded" style="font-size:0.85rem">
          <div class="col-4"><strong>Class Average:</strong> ${data.stats?.class_average ?? '—'}</div>
          <div class="col-4"><strong>Highest Average:</strong> ${data.stats?.highest_average ?? '—'}</div>
          <div class="col-4"><strong>Lowest Average:</strong> ${data.stats?.lowest_average ?? '—'}</div>
        </div>

        <table class="table table-bordered table-striped" style="font-size:0.88rem">
          <thead>
            <tr>
              <th style="width:40px;text-align:center">#</th>
              <th>Learner Name</th>
              <th>LRN</th>
              <th>Section</th>
              <th style="width:100px;text-align:center">Average</th>
              <th style="width:100px;text-align:center">Remarks</th>
            </tr>
          </thead>
          <tbody>
            ${rows || '<tr><td colspan="6" class="text-center py-3">No student records found.</td></tr>'}
          </tbody>
        </table>
      </div>
    `;
  }

  function printReport() {
    window.print();
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
