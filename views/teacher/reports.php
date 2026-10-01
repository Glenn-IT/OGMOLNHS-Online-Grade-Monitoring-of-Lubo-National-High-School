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
$teacherName = $_SESSION['full_name'] ?? 'Subject Teacher';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Section Grade Sheets – Faculty Portal | Lubo NHS</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../../assets/css/style.css") ?>"/>
  <link rel="stylesheet" href="../../assets/css/print.css?v=<?= filemtime(__DIR__ . "/../../assets/css/print.css") ?>"/>
  <style>
    .paper-sheet {
      max-width: 900px;
      margin: 0 auto;
      background: #ffffff;
    }
    @media print {
      .paper-sheet {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        max-width: 100% !important;
      }
    }
  </style>
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
          <div class="topbar-title">Section Grade Sheets</div>
          <div class="topbar-subtitle">Generate and print official DepEd Section Grade Sheets for SY <?= htmlspecialchars($syLabel) ?></div>
        </div>
      </div>
      <div class="topbar-right">
        <button class="btn btn-success btn-sm" id="topbarPrintBtn" onclick="printSectionGradeSheet()" disabled>
          <i class="fas fa-print me-1"></i>Print Section Grade Sheet
        </button>
      </div>
    </header>

    <main class="page-content fade-in">
      <!-- Selector & Filters Bar -->
      <div class="content-card mb-3 no-print">
        <div class="card-body-custom p-3">
          <div class="row g-2 align-items-end">
            <div class="col-md-5">
              <label class="form-label mb-1 fw-semibold" style="font-size:0.8rem">Teaching Subject</label>
              <select id="filterSubject" class="form-select form-select-sm" onchange="onSubjectChange()">
                <option value="">Loading assigned subjects…</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label mb-1 fw-semibold" style="font-size:0.8rem">Class Section</label>
              <select id="filterSection" class="form-select form-select-sm" onchange="generateGradeSheet()">
                <option value="">Select a teaching subject first</option>
              </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
              <button class="btn btn-primary btn-sm flex-grow-1" onclick="generateGradeSheet()">
                <i class="fas fa-sync-alt me-1"></i>Generate
              </button>
              <button class="btn btn-outline-success btn-sm flex-grow-1" id="filterPrintBtn" onclick="printSectionGradeSheet()" disabled>
                <i class="fas fa-print me-1"></i>Print
              </button>
            </div>
          </div>

          <!-- Signatories Bar -->
          <div class="row g-2 mt-2 pt-2 border-top align-items-center" id="signatoriesBar">
            <div class="col-auto">
              <span class="badge bg-light text-dark border"><i class="fas fa-signature me-1 text-primary"></i>Signatories</span>
            </div>
            <div class="col-md-3">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white" title="Subject Teacher"><i class="fas fa-user-edit text-muted"></i></span>
                <input type="text" id="customTeacherInput" class="form-control" placeholder="Subject Teacher" value="<?= htmlspecialchars($teacherName) ?>" oninput="syncSignatories()"/>
              </div>
            </div>
            <div class="col-md-3">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white" title="Class Adviser"><i class="fas fa-chalkboard-teacher text-muted"></i></span>
                <input type="text" id="customAdviserInput" class="form-control" placeholder="Class Adviser" value="Class Adviser" oninput="syncSignatories()"/>
              </div>
            </div>
            <div class="col-md-3">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white" title="School Principal"><i class="fas fa-user-tie text-muted"></i></span>
                <input type="text" id="customPrincipalInput" class="form-control" placeholder="School Principal" value="DR. GLENN M. LUBO" oninput="syncSignatories()"/>
              </div>
            </div>
            <div class="col text-end text-muted" style="font-size:0.75rem;">
              <i class="fas fa-info-circle me-1"></i>Signatures update live on printout
            </div>
          </div>

        </div>
      </div>

      <!-- Printable Report Container -->
      <div id="reportContainer">
        <div class="content-card p-5 text-center text-muted">
          <i class="fas fa-table fa-3x mb-3 text-secondary"></i>
          <h5>No Section Grade Sheet Selected</h5>
          <p class="text-muted mb-0" style="font-size:0.9rem">Please choose one of your assigned teaching subjects and a class section above, then click <strong>Generate</strong>.</p>
        </div>
      </div>

    </main>
  </div>
</div>

<div id="toast-container"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
  let allSubjects = [];
  let allSections = [];
  let currentSubject = null;
  let currentSection = null;
  let currentStudents = [];
  let currentGrades = [];

  async function init() {
    await loadInitialData();

    // Check URL parameters for auto-selection
    const params = new URLSearchParams(window.location.search);
    const subParam = params.get('subject_id');
    const secParam = params.get('section_id');

    if (subParam) {
      const subSel = document.getElementById('filterSubject');
      for (const opt of subSel.options) {
        if (opt.dataset.subjectId === subParam) {
          subSel.value = opt.value;
          break;
        }
      }
      onSubjectChange();
      if (secParam) {
        document.getElementById('filterSection').value = secParam;
      }
      generateGradeSheet();
    }
  }

  async function loadInitialData() {
    try {
      const [secRes, grRes] = await Promise.all([
        fetch('../../api/sections.php?action=list'),
        fetch('../../api/grades.php?action=list')
      ]);

      const secData = await secRes.json();
      const grData  = await grRes.json();

      allSections = secData.data || [];
      allSubjects = grData.subjects || [];

      populateSubjectDropdown();
    } catch(err) {
      console.error(err);
      showToast('Error loading teaching subjects and sections.', 'error');
    }
  }

  function populateSubjectDropdown() {
    const subSel = document.getElementById('filterSubject');
    if (!allSubjects.length) {
      subSel.innerHTML = '<option value="">No assigned subjects found</option>';
      return;
    }

    subSel.innerHTML = '<option value="">-- Select Assigned Subject --</option>' +
      allSubjects.map((s, idx) => {
        const val = `${s.id}_${s.grade_level || 'all'}`;
        const grText = s.grade_level ? ` - Grade ${s.grade_level}` : '';
        const codeText = s.code ? ` (${s.code})` : '';
        return `<option value="${val}" data-subject-id="${s.id}" data-grade-level="${s.grade_level||''}" ${idx===0 ? 'selected' : ''}>${esc(s.name)}${grText}${codeText}</option>`;
      }).join('');

    onSubjectChange();
  }

  function onSubjectChange() {
    const subSel = document.getElementById('filterSubject');
    const selectedOption = subSel.options[subSel.selectedIndex];
    const secSel = document.getElementById('filterSection');

    if (!selectedOption || !selectedOption.value) {
      secSel.innerHTML = '<option value="">Select a teaching subject first</option>';
      secSel.disabled = true;
      togglePrintButtons(false);
      return;
    }

    const gradeLevel = selectedOption.dataset.gradeLevel;
    const matchingSections = allSections.filter(sec => {
      if (!gradeLevel) return true;
      return parseInt(sec.grade_level) === parseInt(gradeLevel);
    });

    if (!matchingSections.length) {
      secSel.innerHTML = '<option value="">No sections found for Grade ' + (gradeLevel || 'level') + '</option>';
      secSel.disabled = true;
      togglePrintButtons(false);
      return;
    }

    secSel.disabled = false;
    secSel.innerHTML = matchingSections.map((sec, idx) =>
      `<option value="${sec.id}" ${idx===0 ? 'selected' : ''}>Grade ${sec.grade_level} - ${esc(sec.name)}</option>`
    ).join('');

    togglePrintButtons(false);
  }

  function togglePrintButtons(enabled) {
    document.getElementById('topbarPrintBtn').disabled = !enabled;
    document.getElementById('filterPrintBtn').disabled = !enabled;
  }

  async function generateGradeSheet() {
    const subSel = document.getElementById('filterSubject');
    const selectedOption = subSel.options[subSel.selectedIndex];
    const secId = document.getElementById('filterSection').value;
    const container = document.getElementById('reportContainer');

    if (!selectedOption || !selectedOption.value || !secId) {
      container.innerHTML = `
        <div class="content-card p-5 text-center text-muted">
          <i class="fas fa-info-circle fa-3x mb-3 text-secondary"></i>
          <h5>Incomplete Selection</h5>
          <p class="text-muted">Please select both an assigned subject and class section to view the Grade Sheet.</p>
        </div>`;
      togglePrintButtons(false);
      return;
    }

    const subjectId = selectedOption.dataset.subjectId;
    const gradeLevel = selectedOption.dataset.gradeLevel;

    currentSubject = allSubjects.find(s => s.id == subjectId && (!gradeLevel || !s.grade_level || s.grade_level == gradeLevel)) || allSubjects.find(s => s.id == subjectId);
    currentSection = allSections.find(s => s.id == secId);

    container.innerHTML = `
      <div class="content-card p-5 text-center text-muted">
        <div class="spinner-border text-primary mb-3" role="status"></div>
        <h5>Loading Section Grade Sheet…</h5>
        <p class="text-muted">Fetching enrolled learners and recorded grades.</p>
      </div>`;

    try {
      const [stuRes, grRes] = await Promise.all([
        fetch(`../../api/sections.php?action=students&section_id=${secId}`),
        fetch(`../../api/grades.php?action=list&section_id=${secId}&subject_id=${subjectId}`)
      ]);

      const stuData = await stuRes.json();
      const grData = await grRes.json();

      currentStudents = stuData.data || [];
      currentGrades = grData.data || [];

      renderGradeSheetView();
      togglePrintButtons(true);
    } catch(err) {
      console.error(err);
      container.innerHTML = `
        <div class="content-card p-4 text-center text-danger">
          <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
          <h5>Error Loading Grade Sheet</h5>
          <p class="text-muted">Could not retrieve section or grade data. Please try again.</p>
        </div>`;
      togglePrintButtons(false);
    }
  }

  function renderGradeSheetView() {
    const container = document.getElementById('reportContainer');
    const dateStr = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    const teacherName = document.getElementById('customTeacherInput').value.trim() || '<?= htmlspecialchars($teacherName) ?>';
    const adviserName = document.getElementById('customAdviserInput').value.trim() || 'Class Adviser';
    const principalName = document.getElementById('customPrincipalInput').value.trim() || 'DR. GLENN M. LUBO';

    let totalFinals = 0, countFinals = 0, passedCount = 0, failedCount = 0;

    let rowsHtml = '';
    if (!currentStudents.length) {
      rowsHtml = '<tr><td colspan="8" class="text-center py-4 text-muted">No students enrolled in this section.</td></tr>';
    } else {
      rowsHtml = currentStudents.map((stu, idx) => {
        const q1 = currentGrades.find(g => g.student_id == stu.id && g.quarter == 1);
        const q2 = currentGrades.find(g => g.student_id == stu.id && g.quarter == 2);
        const q3 = currentGrades.find(g => g.student_id == stu.id && g.quarter == 3);

        const hasQ1 = q1 && q1.final_grade !== null && q1.final_grade !== '';
        const hasQ2 = q2 && q2.final_grade !== null && q2.final_grade !== '';
        const hasQ3 = q3 && q3.final_grade !== null && q3.final_grade !== '';

        let finalAvg = null;
        if (hasQ1 && hasQ2 && hasQ3) {
          const v1 = parseFloat(q1.final_grade);
          const v2 = parseFloat(q2.final_grade);
          const v3 = parseFloat(q3.final_grade);
          finalAvg = Math.round(((v1 + v2 + v3) / 3 + Number.EPSILON) * 100) / 100;
          totalFinals += finalAvg;
          countFinals++;
          if (finalAvg >= 75) passedCount++; else failedCount++;
        }

        const remarks = finalAvg !== null ? (finalAvg >= 75 ? 'Passed' : 'Failed') : '—';
        const remarksBadge = finalAvg !== null
          ? (finalAvg >= 75 ? '<span class="badge bg-success">Passed</span>' : '<span class="badge bg-danger">Failed</span>')
          : '<span class="text-muted">—</span>';

        return `
          <tr>
            <td class="text-center">${idx + 1}</td>
            <td><strong>${esc(stu.full_name)}</strong></td>
            <td class="text-center"><code>${esc(stu.lrn || '—')}</code></td>
            <td class="text-center">${hasQ1 ? parseFloat(q1.final_grade).toFixed(2) : '—'}</td>
            <td class="text-center">${hasQ2 ? parseFloat(q2.final_grade).toFixed(2) : '—'}</td>
            <td class="text-center">${hasQ3 ? parseFloat(q3.final_grade).toFixed(2) : '—'}</td>
            <td class="text-center fw-bold" style="color:${finalAvg !== null ? (finalAvg >= 75 ? 'var(--success)' : 'var(--danger)') : 'inherit'}">
              ${finalAvg !== null ? finalAvg.toFixed(2) : '—'}
            </td>
            <td class="text-center">${remarksBadge}</td>
          </tr>`;
      }).join('');
    }

    const classAverage = countFinals > 0 ? (totalFinals / countFinals).toFixed(2) : '—';
    const passRate = countFinals > 0 ? Math.round((passedCount / countFinals) * 100) : '—';

    container.innerHTML = `
      <div class="printable-area paper-sheet p-4 bg-white border rounded shadow-sm" id="printableGradeSheet">
        <!-- DepEd Official Header -->
        <div class="print-header d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
          <div class="print-logo"><img src="../../assets/images/deped_logo.png" alt="DepEd" style="width:60px;height:60px;object-fit:contain;"/></div>
          <div class="print-school-info text-center flex-grow-1">
            <p class="mb-1 text-muted" style="font-size:8.5pt">Republic of the Philippines &bull; Department of Education &bull; Region II</p>
            <h3 class="mb-0 fw-bold" style="font-size:15pt;color:#1e3a8a;">LUBO NATIONAL HIGH SCHOOL</h3>
            <p class="mb-1 text-muted" style="font-size:8.5pt">Lubo, Sto. Niño, Cagayan &nbsp;|&nbsp; Online Grade Monitoring System</p>
            <div style="font-weight:700;font-size:10.5pt;color:#1e3a8a;margin-top:2px;">SECTION GRADE SHEET SUMMARY</div>
          </div>
          <div class="print-logo"><img src="../../assets/images/lubo_logo.png" alt="Lubo NHS" style="width:60px;height:60px;object-fit:contain;"/></div>
        </div>

        <!-- Metadata Box -->
        <div class="meta-box d-flex justify-content-between align-items-center bg-light p-2 px-3 border rounded mb-3 flex-wrap gap-2" style="font-size:9pt">
          <div><strong>Subject:</strong> ${esc(currentSubject.name)} (${esc(currentSubject.code||'—')})</div>
          <div><strong>Section:</strong> Grade ${esc(currentSection.grade_level)} – ${esc(currentSection.name)}</div>
          <div><strong>School Year:</strong> SY <?= htmlspecialchars($syLabel) ?></div>
          <div><strong>Date Generated:</strong> ${dateStr}</div>
        </div>

        <!-- Grade Sheet Table -->
        <div class="table-responsive">
          <table class="table table-bordered table-sm align-middle mb-3" style="font-size:9pt;border-color:#cbd5e1;">
            <thead class="table-light text-center" style="font-size:8.5pt;">
              <tr>
                <th style="width:38px;">#</th>
                <th style="text-align:left;">Learner Name</th>
                <th style="width:120px;">LRN</th>
                <th style="width:85px;">1st Term</th>
                <th style="width:85px;">2nd Term</th>
                <th style="width:85px;">3rd Term</th>
                <th style="width:95px;">Final Grade</th>
                <th style="width:90px;">Remarks</th>
              </tr>
            </thead>
            <tbody>
              ${rowsHtml}
            </tbody>
          </table>
        </div>

        <!-- Summary Performance Stats -->
        <div class="row g-2 mb-4 p-2 bg-light rounded border text-center" style="font-size:8.5pt">
          <div class="col-6 col-md-3"><strong>Enrolled:</strong> ${currentStudents.length} Learners</div>
          <div class="col-6 col-md-3"><strong>Graded:</strong> ${countFinals} / ${currentStudents.length}</div>
          <div class="col-6 col-md-3"><strong>Class Average:</strong> <span class="badge bg-primary">${classAverage}</span></div>
          <div class="col-6 col-md-3"><strong>Passing Rate:</strong> <span class="badge bg-success">${passRate}%</span></div>
        </div>

        <!-- Signatures Area -->
        <div class="print-signatures d-flex justify-content-between mt-4" style="font-size:9pt;">
          <div class="print-sig-box text-center" style="width:30%;">
            <div class="print-sig-line border-bottom mb-2" style="height:35px;border-color:#334155!important;"></div>
            <div class="print-sig-name fw-bold sig-prep-name">${esc(teacherName)}</div>
            <div class="print-sig-role text-muted" style="font-size:8pt">Subject Teacher (Prepared By)</div>
          </div>
          <div class="print-sig-box text-center" style="width:30%;">
            <div class="print-sig-line border-bottom mb-2" style="height:35px;border-color:#334155!important;"></div>
            <div class="print-sig-name fw-bold sig-ver-name">${esc(adviserName)}</div>
            <div class="print-sig-role text-muted" style="font-size:8pt">Class Adviser (Verified By)</div>
          </div>
          <div class="print-sig-box text-center" style="width:30%;">
            <div class="print-sig-line border-bottom mb-2" style="height:35px;border-color:#334155!important;"></div>
            <div class="print-sig-name fw-bold sig-app-name">${esc(principalName)}</div>
            <div class="print-sig-role text-muted" style="font-size:8pt">School Principal (Approved By)</div>
          </div>
        </div>
      </div>`;
  }

  function syncSignatories() {
    const teacherName = document.getElementById('customTeacherInput').value.trim() || 'Subject Teacher';
    const adviserName = document.getElementById('customAdviserInput').value.trim() || 'Class Adviser';
    const principalName = document.getElementById('customPrincipalInput').value.trim() || 'School Principal';

    document.querySelectorAll('.sig-prep-name').forEach(el => el.textContent = teacherName);
    document.querySelectorAll('.sig-ver-name').forEach(el => el.textContent = adviserName);
    document.querySelectorAll('.sig-app-name').forEach(el => el.textContent = principalName);
  }

  function printSectionGradeSheet() {
    if (!currentSubject || !currentSection) {
      showToast('Please generate a Section Grade Sheet first.', 'warning');
      return;
    }

    const dateStr = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    const teacherName = document.getElementById('customTeacherInput').value.trim() || '<?= htmlspecialchars($teacherName) ?>';
    const adviserName = document.getElementById('customAdviserInput').value.trim() || 'Class Adviser';
    const principalName = document.getElementById('customPrincipalInput').value.trim() || 'DR. GLENN M. LUBO';

    let tableRows = currentStudents.map((stu, idx) => {
      const q1 = currentGrades.find(g => g.student_id == stu.id && g.quarter == 1);
      const q2 = currentGrades.find(g => g.student_id == stu.id && g.quarter == 2);
      const q3 = currentGrades.find(g => g.student_id == stu.id && g.quarter == 3);

      const hasQ1 = q1 && q1.final_grade !== null && q1.final_grade !== '';
      const hasQ2 = q2 && q2.final_grade !== null && q2.final_grade !== '';
      const hasQ3 = q3 && q3.final_grade !== null && q3.final_grade !== '';

      const finalAvg = (hasQ1 && hasQ2 && hasQ3)
        ? ((parseFloat(q1.final_grade) + parseFloat(q2.final_grade) + parseFloat(q3.final_grade)) / 3).toFixed(2)
        : '—';
      const remarks = finalAvg !== '—' ? (parseFloat(finalAvg) >= 75 ? 'Passed' : 'Failed') : '—';

      return `<tr>
        <td style="text-align:center">${idx + 1}</td>
        <td><strong>${esc(stu.full_name)}</strong></td>
        <td>${esc(stu.lrn || '—')}</td>
        <td style="text-align:center">${q1 && q1.final_grade !== null ? parseFloat(q1.final_grade).toFixed(2) : '—'}</td>
        <td style="text-align:center">${q2 && q2.final_grade !== null ? parseFloat(q2.final_grade).toFixed(2) : '—'}</td>
        <td style="text-align:center">${q3 && q3.final_grade !== null ? parseFloat(q3.final_grade).toFixed(2) : '—'}</td>
        <td style="text-align:center;font-weight:bold">${finalAvg}</td>
        <td style="text-align:center">${remarks}</td>
      </tr>`;
    }).join('');

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`<!DOCTYPE html>
<html>
<head>
  <title>Class Grade Sheet - Section ${esc(currentSection.name)}</title>
  <style>
    body { font-family: "Segoe UI", Arial, sans-serif; margin: 25px; color: #1e293b; }
    .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #1e3a8a; padding-bottom: 12px; margin-bottom: 18px; }
    .header h2 { margin: 0; font-size: 16pt; color: #1e3a8a; }
    .header p { margin: 2px 0; font-size: 9pt; color: #475569; }
    .meta-box { display: flex; justify-content: space-between; background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 6px; margin-bottom: 18px; font-size: 9pt; }
    table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 25px; }
    th, td { border: 1px solid #cbd5e1; padding: 7px 9px; }
    th { background: #f1f5f9; font-weight: 700; text-transform: uppercase; font-size: 8pt; }
    .signatures { display: flex; justify-content: space-between; margin-top: 40px; font-size: 9pt; }
    .sig-box { text-align: center; width: 28%; }
    .sig-line { border-bottom: 1px solid #334155; margin-bottom: 6px; height: 35px; }
    @media print { @page { size: portrait; margin: 15mm; } }
  </style>
</head>
<body>
  <div class="header">
    <div class="logo"><img src="../../assets/images/deped_logo.png" alt="DepEd" style="width:60px;height:60px;object-fit:contain;"/></div>
    <div style="flex:1;text-align:center;">
      <p>Republic of the Philippines &bull; Department of Education &bull; Region II</p>
      <h2>LUBO NATIONAL HIGH SCHOOL</h2>
      <p>Lubo, Sto. Niño, Cagayan &nbsp;|&nbsp; Online Grade Monitoring System</p>
      <div style="font-weight:700;font-size:10pt;color:#1e3a8a;margin-top:2px;">SECTION GRADE SHEET SUMMARY</div>
    </div>
    <div class="logo"><img src="../../assets/images/lubo_logo.png" alt="Lubo NHS" style="width:60px;height:60px;object-fit:contain;"/></div>
  </div>
  <div class="meta-box">
    <div><strong>Subject:</strong> ${esc(currentSubject.name)} (${esc(currentSubject.code||'—')})</div>
    <div><strong>Section:</strong> Grade ${esc(currentSection.grade_level)} – ${esc(currentSection.name)}</div>
    <div><strong>School Year:</strong> SY <?= htmlspecialchars($syLabel) ?></div>
    <div><strong>Date Generated:</strong> ${dateStr}</div>
  </div>
  <table>
    <thead>
      <tr>
        <th style="width:35px;text-align:center">#</th>
        <th>Learner Name</th>
        <th style="width:110px">LRN</th>
        <th style="width:75px;text-align:center">1st Term</th>
        <th style="width:75px;text-align:center">2nd Term</th>
        <th style="width:75px;text-align:center">3rd Term</th>
        <th style="width:80px;text-align:center">Final Grade</th>
        <th style="width:75px;text-align:center">Remarks</th>
      </tr>
    </thead>
    <tbody>
      ${tableRows || '<tr><td colspan="8" style="text-align:center;padding:20px">No students enrolled in this section.</td></tr>'}
    </tbody>
  </table>
  <div class="signatures">
    <div class="sig-box">
      <div class="sig-line"></div>
      <div class="sig-title">${esc(teacherName)}</div>
      <div class="sig-role">Subject Teacher (Prepared By)</div>
    </div>
    <div class="sig-box">
      <div class="sig-line"></div>
      <div class="sig-title">${esc(adviserName)}</div>
      <div class="sig-role">Class Adviser (Verified By)</div>
    </div>
    <div class="sig-box">
      <div class="sig-line"></div>
      <div class="sig-title">${esc(principalName)}</div>
      <div class="sig-role">School Principal (Approved By)</div>
    </div>
  </div>
  <script>
    window.onload = function() { window.print(); };
  <\/script>
</body>
</html>`);
    printWindow.document.close();
  }

  function esc(s) {
    return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
