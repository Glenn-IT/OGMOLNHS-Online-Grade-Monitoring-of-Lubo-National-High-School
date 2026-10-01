<?php
// views/teacher/manage-grades.php
require_once '../../config/session.php';
require_once '../../config/db.php';
require_once '../../config/school-year.php';
requireTeacher();

$teacherActivePage = 'manage-grades';
$pdo = getDB();
$syId = activeSchoolYear($pdo);
$syLabel = activeSchoolYearLabel($pdo);
$teacherId = (int)$_SESSION['user_id'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Manage Grades – Faculty Portal | Lubo NHS</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../../assets/css/style.css") ?>"/>
  <style>
    .subject-accordion-btn {
      background-color: #0c1326 !important;
      color: #fff !important;
      font-weight: 600;
      font-size: .95rem;
      border-radius: 8px !important;
      box-shadow: none !important;
    }
    .subject-accordion-btn:not(.collapsed) {
      background-color: #1e293b !important;
      color: #38bdf8 !important;
    }
    .subject-accordion-btn::after {
      filter: brightness(0) invert(1);
    }
    .subject-item {
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 1px 3px rgba(0,0,0,.05);
    }
    .section-card-row {
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 12px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 8px;
      transition: all .15s ease;
    }
    .section-card-row:hover {
      border-color: var(--primary);
      box-shadow: 0 2px 6px rgba(0,0,0,.06);
    }
    .student-grid-table th {
      background: #f8fafc;
      color: #475569;
      font-size: .78rem;
      text-transform: uppercase;
      font-weight: 700;
    }
    .clickable-grade {
      cursor: pointer;
      transition: transform .1s ease;
      display: inline-block;
      min-width: 48px;
      padding: 4px 8px;
      border-radius: 6px;
    }
    .clickable-grade:hover {
      transform: scale(1.08);
      filter: brightness(0.95);
    }
  </style>
</head>
<body>
<div class="app-wrapper">
  <?php include '../../components/teacher-sidebar.php'; ?>

  <div class="main-content">
    <header class="topbar">
      <div class="topbar-left">
        <button class="topbar-btn hamburger"><i class="fas fa-bars"></i></button>
        <div>
          <div class="topbar-title">Manage Subject Grades</div>
          <div class="topbar-subtitle">Encode, update, and review grades for your assigned teaching subjects (SY <?= htmlspecialchars($syLabel) ?>)</div>
        </div>
      </div>
    </header>

    <main class="page-content fade-in">

      <!-- Filter bar -->
      <div class="content-card mb-3">
        <div class="card-body-custom p-3">
          <div class="row g-2 align-items-center">
            <div class="col-md-5">
              <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search student name, LRN, or section…" oninput="renderHierarchy()"/>
              </div>
            </div>
            <div class="col-md-3">
              <select id="filterSubject" class="form-select form-select-sm" onchange="renderHierarchy()">
                <option value="">All My Teaching Subjects</option>
              </select>
            </div>
            <div class="col-md-2">
              <select id="filterSection" class="form-select form-select-sm" onchange="renderHierarchy()">
                <option value="">All Class Sections</option>
              </select>
            </div>
            <div class="col-md-2 text-end">
              <button class="btn btn-outline-secondary btn-sm w-100" onclick="clearFilters()">
                <i class="fas fa-undo me-1"></i>Reset
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Summary Strip -->
      <div class="row g-3 mb-3" id="summaryStrip"></div>

      <!-- Subjects & Sections Hierarchy -->
      <div class="content-card mb-3">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
          <span class="card-title"><i class="fas fa-book me-2 text-primary"></i>My Assigned Teaching Subjects &amp; Class Sections</span>
          <span class="badge bg-primary" id="subjectCount" style="font-size:.8rem">—</span>
        </div>
        <div class="p-3">
          <div class="accordion" id="subjectAccordion">
            <div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading assigned subjects…</div>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     CLASS STUDENT GRADES MODAL (Nested Data Gridview)
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="classGradesModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header" style="background:#0c1326;color:#fff">
        <div>
          <h5 class="modal-title mb-0" id="classModalTitle"><i class="fas fa-graduation-cap me-2"></i>Student Grades Grid</h5>
          <small id="classModalMeta" style="opacity:.85">—</small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-0">
        <!-- Class Quick Stats -->
        <div class="px-3 py-2 bg-light d-flex gap-3 align-items-center border-bottom flex-wrap" id="classModalStats" style="font-size:.85rem"></div>

        <!-- Student Data Gridview -->
        <div class="table-responsive px-3 py-3">
          <table class="table table-hover align-middle student-grid-table" id="classStudentTable">
            <thead>
              <tr>
                <th style="min-width:180px">Learner's Name</th>
                <th style="min-width:110px">LRN</th>
                <th class="text-center" style="min-width:90px">1st Term</th>
                <th class="text-center" style="min-width:90px">2nd Term</th>
                <th class="text-center" style="min-width:90px">3rd Term</th>
                <th class="text-center" style="min-width:90px">Final Grade</th>
                <th class="text-center" style="min-width:90px">Remarks</th>
                <th class="text-center" style="min-width:135px">Print Grade Sheet</th>
              </tr>
            </thead>
            <tbody id="classStudentTableBody">
              <tr><td colspan="8" class="text-center py-4 text-muted">Loading students…</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer bg-light justify-content-between flex-wrap gap-2">
        <div>
          <button class="btn btn-outline-success btn-sm" onclick="printSectionGrades()" title="Print Section Class Summary Grade Sheet">
            <i class="fas fa-table me-1"></i>Print Section Grade Sheet
          </button>
        </div>
        <div class="d-flex align-items-center gap-2">
          <div class="input-group input-group-sm" style="width: auto;">
            <span class="input-group-text bg-white"><i class="fas fa-user-graduate text-primary"></i></span>
            <select id="modalQuickStudentSelect" class="form-select form-select-sm" style="max-width: 230px;" onchange="onModalStudentSelected(this.value)">
              <option value="">Select Student to Print…</option>
            </select>
            <button class="btn btn-primary btn-sm text-nowrap" type="button" onclick="printSelectedStudentGradeSheet()" title="Print Individual Student SF9 Report Card">
              <i class="fas fa-print me-1"></i>Print Grade Sheet
            </button>
          </div>
          <button class="btn btn-secondary btn-sm ms-2" data-bs-dismiss="modal">Close</button>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     ADD / EDIT TERM GRADE MODAL
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="termGradeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background:#0c1326;color:#fff">
        <div>
          <h5 class="modal-title mb-0" id="termModalTitle">Encode Student Grade</h5>
          <small id="termModalSubtitle" style="opacity:.85">—</small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="editGradeStudentId"/>
        <input type="hidden" id="editGradeSubjectId"/>
        <input type="hidden" id="editGradeQuarter"/>

        <div class="alert alert-info py-2 mb-3" style="font-size:.82rem">
          <i class="fas fa-calculator me-1"></i>
          <strong>DepEd Formula:</strong> Final Grade = (Written Works &times; 20%) + (Performance Tasks &times; 50%) + (Quarterly Exam &times; 30%)
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Written Works (20%)</label>
          <div class="input-group">
            <input type="number" id="inputWW" class="form-control" min="0" max="100" step="0.01" placeholder="0.00" oninput="calcPreviewGrade()"/>
            <span class="input-group-text">/ 100</span>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Performance Tasks (50%)</label>
          <div class="input-group">
            <input type="number" id="inputPT" class="form-control" min="0" max="100" step="0.01" placeholder="0.00" oninput="calcPreviewGrade()"/>
            <span class="input-group-text">/ 100</span>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Quarterly Exam (30%)</label>
          <div class="input-group">
            <input type="number" id="inputQE" class="form-control" min="0" max="100" step="0.01" placeholder="0.00" oninput="calcPreviewGrade()"/>
            <span class="input-group-text">/ 100</span>
          </div>
        </div>

        <div class="p-3 bg-light rounded text-center border">
          <div class="text-muted" style="font-size:.8rem;text-transform:uppercase;font-weight:600">Calculated Final Grade</div>
          <div class="h2 mb-0 fw-bold" id="previewGradeVal" style="color:var(--primary)">—</div>
          <div class="mt-1" id="previewRemarksVal">—</div>
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <button class="btn btn-outline-primary btn-sm" type="button" onclick="printCurrentModalStudentReport()" title="View and print this student's full SF9 report card">
          <i class="fas fa-print me-1"></i>Print Grade Sheet
        </button>
        <div>
          <button class="btn btn-secondary btn-sm me-1" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary btn-sm" onclick="saveTermGrade()"><i class="fas fa-save me-1"></i>Save Grade</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
  let allSections = [], allSubjects = [], allStudents = [], allGrades = [];
  let currentClassSubject = null, currentClassSection = null;
  let sectionStudentsMap = {};
  let activeGridStudentId = null;

  async function init() {
    await loadData();
    populateFilters();
    renderSummary();
    renderHierarchy();

    // Check for query param auto-selection
    const urlParams = new URLSearchParams(window.location.search);
    const targetSubjectId = urlParams.get('subject_id');
    if (targetSubjectId && allSubjects.some(s => s.id == targetSubjectId)) {
      document.getElementById('filterSubject').value = targetSubjectId;
      renderHierarchy();
    }

    const targetStudentId = urlParams.get('student_id');
    if (targetStudentId && allSubjects.length && allSections.length) {
      const studentSec = allSections.find(sec => (sectionStudentsMap[sec.id]||[]).some(s => s.id == targetStudentId));
      if (studentSec) {
        openClassGradesModal(allSubjects[0].id, studentSec.id);
      }
    }
  }

  async function loadData() {
    const [secRes, subRes] = await Promise.all([
      fetch('../../api/sections.php?action=list'),
      fetch('../../api/grades.php?action=list'),
    ]);

    const secData = await secRes.json();
    const grData  = await subRes.json();

    allSections = secData.data || [];
    allSubjects = grData.subjects || [];
    allGrades   = grData.data || [];

    // Load students for all sections
    sectionStudentsMap = {};
    for (const sec of allSections) {
      const stuRes = await fetch('../../api/sections.php?action=students&section_id=' + sec.id);
      const stuData = await stuRes.json();
      sectionStudentsMap[sec.id] = stuData.data || [];
      allStudents = allStudents.concat(stuData.data || []);
    }
  }

  function populateFilters() {
    const subSel = document.getElementById('filterSubject');
    subSel.innerHTML = '<option value="">All My Teaching Subjects</option>' +
      allSubjects.map(s => {
        const val = s.grade_level ? `${s.id}_${s.grade_level}` : s.id;
        const grLabel = s.grade_level ? ` - Grade ${s.grade_level}` : '';
        return `<option value="${val}">${esc(s.name)}${grLabel} (${esc(s.code||'')})</option>`;
      }).join('');

    const secSel = document.getElementById('filterSection');
    secSel.innerHTML = '<option value="">All Class Sections</option>' +
      allSections.map(s => `<option value="${s.id}">${esc(s.name)} (Grade ${s.grade_level})</option>`).join('');
  }

  function esc(str) {
    return String(str ?? '').replace(/[&<>"']/g,
      c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }
  function escAttr(str) {
    return esc(str).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
  }

  function renderSummary() {
    const totalStudents = Object.values(sectionStudentsMap).reduce((acc, list) => acc + list.length, 0);
    document.getElementById('summaryStrip').innerHTML = `
      <div class="col-6 col-md-4"><div class="stat-card text-center">
        <div class="stat-value" style="color:var(--primary)">${allSubjects.length}</div>
        <div class="stat-label">My Teaching Subjects</div></div></div>
      <div class="col-6 col-md-4"><div class="stat-card text-center">
        <div class="stat-value" style="color:var(--success)">${allSections.length}</div>
        <div class="stat-label">Class Sections</div></div></div>
      <div class="col-12 col-md-4"><div class="stat-card text-center">
        <div class="stat-value" style="color:#0284c7">${totalStudents}</div>
        <div class="stat-label">Total Enrolled Learners</div></div></div>`;
  }

  function renderHierarchy() {
    const acc = document.getElementById('subjectAccordion');
    const q = (document.getElementById('searchInput').value || '').trim().toLowerCase();
    const subFilter = document.getElementById('filterSubject').value;
    const secFilter = document.getElementById('filterSection').value;

    let filteredSubjects = allSubjects;
    if (subFilter) {
      filteredSubjects = filteredSubjects.filter(s => {
        const key = s.grade_level ? `${s.id}_${s.grade_level}` : String(s.id);
        return key === subFilter || String(s.id) === subFilter;
      });
    }

    document.getElementById('subjectCount').textContent = `${filteredSubjects.length} Subject Assignment${filteredSubjects.length===1?'':'s'}`;

    if (!allSubjects.length) {
      acc.innerHTML = `<div class="text-center py-5 text-muted">
        <i class="fas fa-book-reader fa-3x mb-3 text-secondary"></i>
        <h5>No Teaching Subjects Assigned Yet</h5>
        <p style="font-size:.9rem; max-width:500px; margin: 0 auto;">
          You are not currently assigned to teach any subjects. Please contact the <strong>School Administrator</strong> to assign your subject(s) in Admin &gt; Manage Grades or Manage Teachers.
        </p>
      </div>`;
      return;
    }

    if (!filteredSubjects.length) {
      acc.innerHTML = '<div class="text-center py-4 text-muted">No subjects found matching your search.</div>';
      return;
    }

    const isFiltering = !!(q || subFilter || secFilter);

    acc.innerHTML = filteredSubjects.map((sub, sIdx) => {
      const subCollapseId = `sub-collapse-${sub.id}-${sub.grade_level || 'all'}`;
      const showSubject = isFiltering ? ' show' : (sIdx === 0 ? ' show' : '');
      const collapsedSubjectCls = isFiltering ? '' : (sIdx === 0 ? '' : ' collapsed');

      // CRITICAL: Filter sections strictly to those matching this subject's assigned grade level
      const sectionsListHtml = allSections.filter(sec => {
        if (sub.grade_level && parseInt(sec.grade_level) !== parseInt(sub.grade_level)) return false;
        if (secFilter && sec.id != secFilter) return false;
        if (q) {
          if (sub.name.toLowerCase().includes(q) || sec.name.toLowerCase().includes(q)) return true;
          const students = sectionStudentsMap[sec.id] || [];
          return students.some(stu => (stu.full_name||'').toLowerCase().includes(q) || (stu.lrn||'').includes(q));
        }
        return true;
      }).map(sec => {
        const students = sectionStudentsMap[sec.id] || [];
        return `
          <div class="section-card-row">
            <div>
              <strong style="font-size:.92rem;color:#0f172a"><i class="fas fa-users-class me-2 text-primary"></i>Section ${esc(sec.name)} (Grade ${sec.grade_level})</strong>
              <span class="badge bg-light text-dark border ms-2">${students.length} Student${students.length===1?'':'s'}</span>
            </div>
            <div>
              <button class="btn btn-primary btn-sm" onclick="openClassGradesModal(${sub.id}, ${sec.id}, ${sub.grade_level || sec.grade_level})">
                <i class="fas fa-table me-1"></i>Open Student Grades
              </button>
            </div>
          </div>`;
      }).join('');

      const gradeBadge = sub.grade_level 
        ? `<span class="badge bg-info-subtle text-info border border-info-subtle ms-2" style="font-size:.78rem"><i class="fas fa-graduation-cap me-1"></i>Grade ${sub.grade_level}</span>` 
        : '';

      return `
        <div class="subject-item mb-3">
          <div class="accordion-header d-flex align-items-center rounded-top" id="heading-subject-${sub.id}-${sub.grade_level||'all'}" style="background-color:#0c1326!important; overflow:hidden">
            <button class="accordion-button subject-accordion-btn flex-grow-1 shadow-none ${collapsedSubjectCls}" type="button" data-bs-toggle="collapse" data-bs-target="#${subCollapseId}">
              <i class="fas fa-book me-2"></i>${esc(sub.name)}
              <span class="badge bg-primary-subtle text-primary ms-2" style="font-size:.72rem">${esc(sub.code||'')}</span>
              ${gradeBadge}
            </button>
          </div>
          <div id="${subCollapseId}" class="accordion-collapse collapse${showSubject}">
            <div class="p-3">
              ${sectionsListHtml || '<div class="text-muted p-2" style="font-size:.85rem"><i class="fas fa-info-circle me-1"></i>No class sections found for this grade level.</div>'}
            </div>
          </div>
        </div>`;
    }).join('');
  }

  function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterSubject').value = '';
    document.getElementById('filterSection').value = '';
    renderHierarchy();
  }

  async function openClassGradesModal(subjectId, sectionId, gradeLevel = null) {
    currentClassSubject = allSubjects.find(s => s.id == subjectId && (!gradeLevel || !s.grade_level || s.grade_level == gradeLevel)) || allSubjects.find(s => s.id == subjectId);
    currentClassSection = allSections.find(s => s.id == sectionId);
    if (!currentClassSubject || !currentClassSection) return;

    activeGridStudentId = null;
    const grBadge = currentClassSubject.grade_level ? `<span class="badge bg-info-subtle text-info border border-info-subtle ms-2" style="font-size:.75rem">Grade ${currentClassSubject.grade_level}</span>` : '';
    document.getElementById('classModalTitle').innerHTML = `<i class="fas fa-book-open me-2 text-warning"></i>${esc(currentClassSubject.name)} <span class="badge bg-primary-subtle text-primary" style="font-size:.75rem">${esc(currentClassSubject.code||'')}</span>${grBadge}`;
    document.getElementById('classModalMeta').textContent = `Grade ${currentClassSection.grade_level} - Section ${currentClassSection.name}`;

    const quickSel = document.getElementById('modalQuickStudentSelect');
    if (quickSel) {
      const sectionStudents = sectionStudentsMap[currentClassSection.id] || [];
      quickSel.innerHTML = '<option value="">Select Student to Print…</option>' + 
        sectionStudents.map(s => `<option value="${s.id}">${esc(s.full_name)}</option>`).join('');
    }

    await reloadGradesData();
    renderClassStudentTable();
    new bootstrap.Modal(document.getElementById('classGradesModal')).show();
  }

  async function reloadGradesData() {
    const res = await fetch(`../../api/grades.php?action=list&section_id=${currentClassSection.id}&subject_id=${currentClassSubject.id}`);
    const grData = await res.json();
    const fetchedGrades = grData.data || [];
    allGrades = allGrades.filter(g => !(g.subject_id == currentClassSubject.id && (sectionStudentsMap[currentClassSection.id]||[]).some(s => s.id == g.student_id)));
    allGrades = allGrades.concat(fetchedGrades);
  }

  function renderClassStudentTable() {
    const tbody = document.getElementById('classStudentTableBody');
    const students = sectionStudentsMap[currentClassSection.id] || [];

    if (!students.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No students enrolled in this section.</td></tr>';
      document.getElementById('classModalStats').innerHTML = '';
      return;
    }

    let totalFinals = 0, countFinals = 0, passedCount = 0, failedCount = 0;

    tbody.innerHTML = students.map(stu => {
      const q1 = allGrades.find(g => g.student_id == stu.id && g.subject_id == currentClassSubject.id && g.quarter == 1);
      const q2 = allGrades.find(g => g.student_id == stu.id && g.subject_id == currentClassSubject.id && g.quarter == 2);
      const q3 = allGrades.find(g => g.student_id == stu.id && g.subject_id == currentClassSubject.id && g.quarter == 3);

      const finals = [q1, q2, q3].filter(q => q && q.final_grade !== null).map(q => parseFloat(q.final_grade));
      let finalAvg = null;
      if (finals.length > 0) {
        finalAvg = round2(finals.reduce((a,b)=>a+b, 0) / finals.length);
        totalFinals += finalAvg;
        countFinals++;
        if (finalAvg >= 75) passedCount++; else failedCount++;
      }

      const remarksBadge = finalAvg !== null
        ? (finalAvg >= 75 ? '<span class="badge bg-success">Passed</span>' : '<span class="badge bg-danger">Failed</span>')
        : '<span class="text-muted">—</span>';

      return `
        <tr>
          <td>
            <strong>${esc(stu.full_name)}</strong>
          </td>
          <td><code style="font-size:.82rem">${esc(stu.lrn||'—')}</code></td>
          <td class="text-center">${formatGradeCell(stu.id, 1, q1)}</td>
          <td class="text-center">${formatGradeCell(stu.id, 2, q2)}</td>
          <td class="text-center">${formatGradeCell(stu.id, 3, q3)}</td>
          <td class="text-center fw-bold" style="color:${finalAvg !== null ? (finalAvg>=75 ? 'var(--success)' : 'var(--danger)') : 'inherit'}">
            ${finalAvg !== null ? finalAvg.toFixed(2) : '—'}
          </td>
          <td class="text-center">${remarksBadge}</td>
          <td class="text-center">
            <button class="btn btn-outline-primary btn-sm py-1 px-2" onclick="printStudentSF9Report(${stu.id})" title="Print SF9 Report Card">
              <i class="fas fa-print me-1"></i>Print
            </button>
          </td>
        </tr>`;
    }).join('');

    const classAverage = countFinals > 0 ? (totalFinals / countFinals).toFixed(2) : '—';
    const passRate = countFinals > 0 ? Math.round((passedCount / countFinals) * 100) : '—';

    document.getElementById('classModalStats').innerHTML = `
      <div><strong>Enrolled:</strong> ${students.length} Learners</div>
      <div><strong>Graded:</strong> ${countFinals} / ${students.length}</div>
      <div><strong>Class Average:</strong> <span class="badge bg-primary">${classAverage}</span></div>
      <div><strong>Passing Rate:</strong> <span class="badge bg-success">${passRate}%</span></div>
    `;
  }

  function formatGradeCell(studentId, quarter, gradeObj) {
    if (gradeObj && gradeObj.final_grade !== null) {
      const val = parseFloat(gradeObj.final_grade).toFixed(2);
      const isPassed = parseFloat(gradeObj.final_grade) >= 75;
      const bgCls = isPassed ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
      return `<span class="clickable-grade ${bgCls} fw-bold" onclick="openTermGradeModal(${studentId}, ${quarter})" title="Click to edit Term ${quarter} grade">${val}</span>`;
    }
    return `<button class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:.75rem" onclick="openTermGradeModal(${studentId}, ${quarter})"><i class="fas fa-plus"></i></button>`;
  }

  function openTermGradeModal(studentId, quarter) {
    activeGridStudentId = studentId;
    const stu = (sectionStudentsMap[currentClassSection.id] || []).find(s => s.id == studentId);
    if (!stu) return;

    document.getElementById('termModalTitle').innerHTML = `<i class="fas fa-edit me-2"></i>Term ${quarter} – ${esc(currentClassSubject.name)}`;
    document.getElementById('termModalSubtitle').textContent = `${esc(stu.full_name)} (${esc(stu.lrn||'No LRN')})`;

    document.getElementById('editGradeStudentId').value = studentId;
    document.getElementById('editGradeSubjectId').value = currentClassSubject.id;
    document.getElementById('editGradeQuarter').value = quarter;

    const existing = allGrades.find(g => g.student_id == studentId && g.subject_id == currentClassSubject.id && g.quarter == quarter);
    document.getElementById('inputWW').value = existing && existing.written_works !== null ? existing.written_works : '';
    document.getElementById('inputPT').value = existing && existing.performance_tasks !== null ? existing.performance_tasks : '';
    document.getElementById('inputQE').value = existing && existing.quarterly_exam !== null ? existing.quarterly_exam : '';

    calcPreviewGrade();
    new bootstrap.Modal(document.getElementById('termGradeModal')).show();
  }

  function calcPreviewGrade() {
    const wwVal = document.getElementById('inputWW').value;
    const ptVal = document.getElementById('inputPT').value;
    const qeVal = document.getElementById('inputQE').value;

    const pGrade = document.getElementById('previewGradeVal');
    const pRemarks = document.getElementById('previewRemarksVal');

    if (wwVal === '' || ptVal === '' || qeVal === '') {
      pGrade.textContent = '—';
      pGrade.style.color = 'var(--primary)';
      pRemarks.innerHTML = '<span class="text-muted">Enter all 3 components to calculate grade</span>';
      return;
    }

    const ww = Math.max(0, Math.min(100, parseFloat(wwVal) || 0));
    const pt = Math.max(0, Math.min(100, parseFloat(ptVal) || 0));
    const qe = Math.max(0, Math.min(100, parseFloat(qeVal) || 0));

    const finalVal = round2((ww * 0.20) + (pt * 0.50) + (qe * 0.30));
    pGrade.textContent = finalVal.toFixed(2);

    if (finalVal >= 75) {
      pGrade.style.color = 'var(--success)';
      pRemarks.innerHTML = '<span class="badge bg-success px-3 py-1">PASSED</span>';
    } else {
      pGrade.style.color = 'var(--danger)';
      pRemarks.innerHTML = '<span class="badge bg-danger px-3 py-1">FAILED</span>';
    }
  }

  async function saveTermGrade() {
    const studentId = document.getElementById('editGradeStudentId').value;
    const subjectId = document.getElementById('editGradeSubjectId').value;
    const quarter   = document.getElementById('editGradeQuarter').value;

    const ww = document.getElementById('inputWW').value;
    const pt = document.getElementById('inputPT').value;
    const qe = document.getElementById('inputQE').value;

    const body = new FormData();
    body.append('action', 'save');
    body.append('student_id', studentId);
    body.append('subject_id', subjectId);
    body.append('quarter', quarter);
    if (ww !== '') body.append('written_works', ww);
    if (pt !== '') body.append('performance_tasks', pt);
    if (qe !== '') body.append('quarterly_exam', qe);

    try {
      const res = await fetch('../../api/grades.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        showToast('Grade saved successfully.', 'success');
        bootstrap.Modal.getInstance(document.getElementById('termGradeModal')).hide();
        await reloadGradesData();
        renderClassStudentTable();
      } else {
        showToast(data.message || 'Failed to save grade.', 'error');
      }
    } catch (err) {
      showToast('Network error while saving grade.', 'error');
    }
  }

  function round2(num) {
    return Math.round((num + Number.EPSILON) * 100) / 100;
  }

  function printStudentSF9Report(studentId) {
    window.open(`reports.php?student_id=${studentId}`, '_blank');
  }

  function printCurrentModalStudentReport() {
    if (activeGridStudentId) {
      printStudentSF9Report(activeGridStudentId);
    }
  }

  function onModalStudentSelected(studentId) {
    activeGridStudentId = studentId || null;
  }

  function printSelectedStudentGradeSheet() {
    const sel = document.getElementById('modalQuickStudentSelect');
    const stuId = sel ? sel.value : null;
    if (!stuId) {
      showToast('Please select a student from the dropdown to print their report card.', 'warning');
      return;
    }
    printStudentSF9Report(stuId);
  }

  function printSectionGrades() {
    if (!currentClassSubject || !currentClassSection) return;
    const students = sectionStudentsMap[currentClassSection.id] || [];
    const dateStr = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

    let tableRows = students.map((stu, idx) => {
      const q1 = allGrades.find(g => g.student_id == stu.id && g.subject_id == currentClassSubject.id && g.quarter == 1);
      const q2 = allGrades.find(g => g.student_id == stu.id && g.subject_id == currentClassSubject.id && g.quarter == 2);
      const q3 = allGrades.find(g => g.student_id == stu.id && g.subject_id == currentClassSubject.id && g.quarter == 3);

      const finals = [q1, q2, q3].filter(q => q && q.final_grade !== null).map(q => parseFloat(q.final_grade));
      const finalAvg = finals.length ? (finals.reduce((a,b)=>a+b, 0) / finals.length).toFixed(2) : '—';
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
  <title>Class Grade Sheet - Section ${esc(currentClassSection.name)}</title>
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
    <div><strong>Subject:</strong> ${esc(currentClassSubject.name)} (${esc(currentClassSubject.code||'—')})</div>
    <div><strong>Section:</strong> Grade ${esc(currentClassSection.grade_level)} – ${esc(currentClassSection.name)}</div>
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
      <div class="sig-title"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
      <div class="sig-role">Class Adviser</div>
    </div>
    <div class="sig-box">
      <div class="sig-line"></div>
      <div class="sig-title">Subject Teacher</div>
      <div class="sig-role">Prepared By</div>
    </div>
    <div class="sig-box">
      <div class="sig-line"></div>
      <div class="sig-title">School Principal</div>
      <div class="sig-role">Approved By</div>
    </div>
  </div>
  <script>
    window.onload = function() { window.print(); };
  <\/script>
</body>
</html>`);
    printWindow.document.close();
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
