<?php
require_once '../../config/session.php';
requireAdmin();
$adminActivePage = 'manage-grades';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Manage Grades – OGMS Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../../assets/css/style.css") ?>"/>
  <style>
    /* Subject Level Accordion Header */
    .subject-accordion-btn {
      background-color: #0c1326 !important;
      color: #ffffff !important;
      font-weight: 600;
      font-size: 1.05rem;
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
      margin-bottom: 0.75rem;
      overflow: hidden;
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }

    /* Grade Level Sub-accordion Header */
    .grade-accordion-btn {
      background-color: #f1f5f9 !important;
      color: #1e293b !important;
      font-weight: 600;
      font-size: 0.95rem;
      border-radius: 6px !important;
      box-shadow: none !important;
    }
    .grade-accordion-btn:not(.collapsed) {
      background-color: #e2e8f0 !important;
      color: #0f172a !important;
    }
    .grade-item {
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      margin-bottom: 0.5rem;
      overflow: hidden;
    }

    /* Section Card */
    .section-card-row {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      padding: 0.75rem 1rem;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      transition: all 0.15s ease-in-out;
    }
    .section-card-row:hover {
      border-color: #94a3b8;
      background: #f8fafc;
    }

    /* Term Cell Styling inside Data Grid */
    .term-cell-grade {
      display: inline-flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 2px;
    }
    .term-cell-actions {
      display: flex;
      gap: 4px;
      margin-top: 4px;
    }
    .btn-term-action {
      border: none;
      background: transparent;
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 0.72rem;
      line-height: 1;
      cursor: pointer;
    }
    .btn-term-action.edit { color: #1d4ed8; }
    .btn-term-action.edit:hover { background: #eff6ff; }
    .btn-term-action.del { color: #ef4444; }
    .btn-term-action.del:hover { background: #fef2f2; }

    .student-grid-table th {
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.03em;
      background: #f1f5f9;
      color: #475569;
      white-space: nowrap;
    }
    .student-grid-table td {
      vertical-align: middle;
      font-size: 0.85rem;
    }
  </style>
</head>
<body>
<div class="app-wrapper">
  <?php include '../../components/admin-sidebar.php'; ?>

  <div class="main-content">
    <header class="topbar">
      <div class="topbar-left">
        <button class="topbar-btn hamburger"><i class="fas fa-bars"></i></button>
        <div>
          <div class="topbar-title">Manage Grades</div>
          <div class="topbar-subtitle">Select a Subject &rarr; Grade Level &rarr; Section to manage student grades</div>
        </div>
      </div>
    </header>

    <main class="page-content fade-in">

      <!-- Filter bar -->
      <div class="content-card mb-3">
        <div class="card-body-custom">
          <div class="row g-2 align-items-center">
            <div class="col-md-4">
              <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search subject, grade, section, student or LRN…" oninput="renderHierarchy()"/>
              </div>
            </div>
            <div class="col-md-2">
              <select id="filterSubject" class="form-select form-select-sm" onchange="renderHierarchy()">
                <option value="">All Subjects</option>
              </select>
            </div>
            <div class="col-md-2">
              <select id="filterGrade" class="form-select form-select-sm" onchange="renderHierarchy()">
                <option value="">All Grade Levels</option>
                <option value="7">Grade 7</option>
                <option value="8">Grade 8</option>
                <option value="9">Grade 9</option>
                <option value="10">Grade 10</option>
                <option value="11">Grade 11</option>
                <option value="12">Grade 12</option>
              </select>
            </div>
            <div class="col-md-2">
              <select id="filterSection" class="form-select form-select-sm" onchange="renderHierarchy()">
                <option value="">All Sections</option>
              </select>
            </div>
            <div class="col-md-2 text-end">
              <button class="btn btn-outline-secondary btn-sm w-100" onclick="clearFilters()">
                <i class="fas fa-undo me-1"></i>Clear
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Summary Strip -->
      <div class="row g-3 mb-3" id="summaryStrip"></div>

      <!-- Hierarchical Subjects Accordion Container -->
      <div class="content-card mb-3">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
          <span class="card-title"><i class="fas fa-book me-2 text-primary"></i>Subjects &amp; Grade Level Sections</span>
          <span class="badge bg-primary" id="subjectCount" style="font-size:.8rem">—</span>
        </div>
        <div class="p-3">
          <div class="accordion" id="subjectAccordion">
            <div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading hierarchy…</div>
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

        <!-- Inner Student Data Gridview -->
        <div class="table-responsive px-3 py-3">
          <table class="table table-hover align-middle student-grid-table" id="classStudentTable">
            <thead>
              <tr>
                <th style="min-width:180px">Learners Name</th>
                <th style="min-width:110px">LRN</th>
                <th class="text-center" style="min-width:110px">1st Term</th>
                <th class="text-center" style="min-width:110px">2nd Term</th>
                <th class="text-center" style="min-width:110px">3rd Term</th>
                <th class="text-center" style="min-width:100px">Final Grade</th>
                <th class="text-center" style="min-width:100px">Remarks</th>
              </tr>
            </thead>
            <tbody id="classStudentTableBody">
              <tr><td colspan="7" class="text-center py-4 text-muted">Loading students…</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer bg-light">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     ADD / EDIT TERM GRADE MODAL
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="editGradeModal" tabindex="-1" style="z-index:1060">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background:#0c1326;color:#fff">
        <h5 class="modal-title" id="editGradeTitle"><i class="fas fa-edit me-2"></i>Term Grade Entry</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="editGradeStudentId"/>
        <input type="hidden" id="editGradeSubjectId"/>
        <input type="hidden" id="editGradeQuarter"/>

        <div class="p-2 mb-3 bg-light rounded border" id="editGradeStudentInfo" style="font-size:.85rem"></div>

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Written Works <small class="text-muted">(20%)</small></label>
            <input type="number" id="editWW" class="form-control" min="0" max="100" step="0.01" oninput="updateEditPreview()"/>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Performance Tasks <small class="text-muted">(50%)</small></label>
            <input type="number" id="editPT" class="form-control" min="0" max="100" step="0.01" oninput="updateEditPreview()"/>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Quarterly Exam <small class="text-muted">(30%)</small></label>
            <input type="number" id="editQE" class="form-control" min="0" max="100" step="0.01" oninput="updateEditPreview()"/>
          </div>
        </div>

        <div class="mt-3 p-2 rounded" id="editPreviewBox" style="display:none;background:#f8fafc;border:1px solid #e2e8f0">
          <div class="d-flex justify-content-between align-items-center">
            <span style="font-size:.8rem;color:#64748b;font-weight:600">Calculated Final Grade</span>
            <div id="editPreviewContent"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary btn-sm" onclick="saveTermGrade()"><i class="fas fa-save me-1"></i>Save Grade</button>
      </div>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
  // ── State ─────────────────────────────────────────────────────────────────
  let allSections = [], allSubjects = [], allStudents = [], allGrades = [];
  let currentClassSubject = null, currentClassSection = null;
  let sectionStudentsMap = {}; // { sectionId: [student, ...] }

  // ── Boot ──────────────────────────────────────────────────────────────────
  async function init() {
    await loadData();
    populateFilters();
    renderSummary();
    renderHierarchy();
  }

  async function loadData() {
    const [secRes, subRes, stuRes] = await Promise.all([
      fetch('../../api/sections.php?action=list'),
      fetch('../../api/grades.php?action=list'),
      fetch('../../api/students.php?action=list'),
    ]);

    const secData = await secRes.json();
    const grData  = await subRes.json();
    const stuData = await stuRes.json();

    allSections = secData.data || [];
    allSubjects = grData.subjects || [];
    allStudents = stuData.data || [];
    allGrades   = grData.data || [];

    // Index students by section
    sectionStudentsMap = {};
    allSections.forEach(sec => {
      sectionStudentsMap[sec.id] = allStudents.filter(s => s.section_id == sec.id || s.section_name == sec.name);
    });
  }

  function populateFilters() {
    const subSel = document.getElementById('filterSubject');
    subSel.innerHTML = '<option value="">All Subjects</option>' +
      allSubjects.map(s => `<option value="${s.id}">${esc(s.name)} (${esc(s.code||'')})</option>`).join('');

    const secSel = document.getElementById('filterSection');
    secSel.innerHTML = '<option value="">All Sections</option>' +
      allSections.map(s => `<option value="${s.id}">${esc(s.name)} (Grade ${s.grade_level})</option>`).join('');
  }

  function esc(str) {
    return String(str ?? '').replace(/[&<>"']/g,
      c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  function escAttr(str) {
    return esc(str).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
  }

  // ── Summary Cards ─────────────────────────────────────────────────────────
  function renderSummary() {
    document.getElementById('summaryStrip').innerHTML = `
      <div class="col-6 col-md-4"><div class="stat-card text-center">
        <div class="stat-value" style="color:var(--primary)">${allSubjects.length}</div>
        <div class="stat-label">Total Subjects</div></div></div>
      <div class="col-6 col-md-4"><div class="stat-card text-center">
        <div class="stat-value" style="color:var(--success)">${allSections.length}</div>
        <div class="stat-label">Total Sections</div></div></div>
      <div class="col-6 col-md-4"><div class="stat-card text-center">
        <div class="stat-value" style="color:var(--warning)">${allGrades.length}</div>
        <div class="stat-label">Grade Records</div></div></div>`;
  }

  // ── Render Hierarchical Accordions (Subject -> Grade Level -> Section) ─────
  function renderHierarchy() {
    const q       = document.getElementById('searchInput').value.trim().toLowerCase();
    const subFilter = document.getElementById('filterSubject').value;
    const gradeFilter = document.getElementById('filterGrade').value;
    const secFilter   = document.getElementById('filterSection').value;

    const container = document.getElementById('subjectAccordion');
    const isFiltering = !!(q || subFilter || gradeFilter || secFilter);

    // Filter subjects
    const filteredSubjects = allSubjects.filter(sub => {
      if (subFilter && sub.id != subFilter) return false;
      if (q && sub.name.toLowerCase().includes(q)) return true;

      return allSections.some(sec => {
        if (gradeFilter && sec.grade_level != gradeFilter) return false;
        if (secFilter && sec.id != secFilter) return false;
        if (!q) return true;
        if (sec.name.toLowerCase().includes(q) || `grade ${sec.grade_level}`.includes(q)) return true;
        const students = sectionStudentsMap[sec.id] || [];
        return students.some(stu => (stu.full_name||'').toLowerCase().includes(q) || (stu.lrn||'').includes(q));
      });
    });

    document.getElementById('subjectCount').textContent = `${filteredSubjects.length} Subject${filteredSubjects.length===1?'':'s'}`;

    if (!filteredSubjects.length) {
      container.innerHTML = `<div class="text-center text-muted py-4"><i class="fas fa-inbox me-2"></i>No subjects match your filter criteria.</div>`;
      return;
    }

    container.innerHTML = filteredSubjects.map((sub, sIdx) => {
      const subCollapseId = `subject-collapse-${sub.id}`;
      const showSubject = isFiltering ? ' show' : (sIdx === 0 ? ' show' : '');
      const collapsedSubjectCls = isFiltering ? '' : (sIdx === 0 ? '' : ' collapsed');

      const gradeLevels = [7, 8, 9, 10, 11, 12].filter(g => {
        if (gradeFilter && g != gradeFilter) return false;
        return true;
      });

      const gradeBlocksHtml = gradeLevels.map((gLevel, gIdx) => {
        const sectionsInGrade = allSections.filter(sec => {
          if (sec.grade_level != gLevel) return false;
          if (secFilter && sec.id != secFilter) return false;
          if (q) {
            if (sub.name.toLowerCase().includes(q) || sec.name.toLowerCase().includes(q) || `grade ${gLevel}`.includes(q)) return true;
            const students = sectionStudentsMap[sec.id] || [];
            return students.some(stu => (stu.full_name||'').toLowerCase().includes(q) || (stu.lrn||'').includes(q));
          }
          return true;
        });

        if (!sectionsInGrade.length && (gradeFilter || secFilter || q)) return '';

        const gradeCollapseId = `grade-collapse-${sub.id}-${gLevel}`;
        const showGrade = isFiltering ? ' show' : (gIdx === 0 ? ' show' : '');
        const collapsedGradeCls = isFiltering ? '' : (gIdx === 0 ? '' : ' collapsed');

        const sectionsListHtml = sectionsInGrade.length ? sectionsInGrade.map(sec => {
          const students = sectionStudentsMap[sec.id] || [];
          return `
            <div class="section-card-row">
              <div>
                <strong style="font-size:.92rem;color:#0f172a"><i class="fas fa-users-class me-2 text-primary"></i>Section ${esc(sec.name)}</strong>
                <span class="badge bg-light text-dark border ms-2">${students.length} Student${students.length===1?'':'s'}</span>
              </div>
              <div>
                <button class="btn btn-primary btn-sm" onclick="openClassGradesModal(${sub.id}, ${sec.id})">
                  <i class="fas fa-table me-1"></i>Open Student Grades
                </button>
              </div>
            </div>`;
        }).join('') : `<div class="text-muted p-2" style="font-size:.85rem"><i class="fas fa-info-circle me-1"></i>No sections created for Grade ${gLevel}.</div>`;

        return `
          <div class="grade-item">
            <h3 class="accordion-header" id="heading-grade-${sub.id}-${gLevel}">
              <button class="accordion-button grade-accordion-btn ${collapsedGradeCls}" type="button" data-bs-toggle="collapse" data-bs-target="#${gradeCollapseId}">
                <i class="fas fa-layer-group me-2 text-secondary"></i>Grade ${gLevel}
                <span class="badge bg-secondary ms-2" style="font-weight:normal">${sectionsInGrade.length} Section${sectionsInGrade.length===1?'':'s'}</span>
              </button>
            </h3>
            <div id="${gradeCollapseId}" class="accordion-collapse collapse${showGrade}">
              <div class="p-3 bg-light">
                ${sectionsListHtml}
              </div>
            </div>
          </div>`;
      }).join('');

      return `
        <div class="subject-item mb-3">
          <h2 class="accordion-header" id="heading-subject-${sub.id}">
            <button class="accordion-button subject-accordion-btn ${collapsedSubjectCls}" type="button" data-bs-toggle="collapse" data-bs-target="#${subCollapseId}">
              <i class="fas fa-book me-2"></i>${esc(sub.name)} <span class="badge bg-primary-subtle text-primary ms-2" style="font-size:.72rem">${esc(sub.code||'')}</span>
            </button>
          </h2>
          <div id="${subCollapseId}" class="accordion-collapse collapse${showSubject}">
            <div class="p-3">
              ${gradeBlocksHtml}
            </div>
          </div>
        </div>`;
    }).join('');
  }

  function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterSubject').value = '';
    document.getElementById('filterGrade').value = '';
    document.getElementById('filterSection').value = '';
    renderHierarchy();
  }

  // ── Open Class Grades Modal Grid ──────────────────────────────────────────
  async function openClassGradesModal(subjectId, sectionId) {
    currentClassSubject = allSubjects.find(s => s.id == subjectId);
    currentClassSection = allSections.find(s => s.id == sectionId);
    if (!currentClassSubject || !currentClassSection) return;

    const modalTitle = document.getElementById('classModalTitle');
    const modalMeta  = document.getElementById('classModalMeta');
    modalTitle.innerHTML = `<i class="fas fa-book-open me-2 text-warning"></i>${esc(currentClassSubject.name)} <span class="badge bg-primary-subtle text-primary" style="font-size:.75rem">${esc(currentClassSubject.code||'')}</span>`;
    modalMeta.textContent = `Grade ${currentClassSection.grade_level} - Section ${currentClassSection.name}`;

    const modal = new bootstrap.Modal(document.getElementById('classGradesModal'));
    modal.show();

    await loadClassStudentGrid();
  }

  async function loadClassStudentGrid() {
    const tbody = document.getElementById('classStudentTableBody');
    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading student grades grid…</td></tr>`;

    try {
      const url = `../../api/grades.php?action=list&subject_id=${currentClassSubject.id}&section_id=${currentClassSection.id}`;
      const res = await fetch(url);
      const data = await res.json();
      const gradeRows = data.data || [];

      // Students in this section
      const students = sectionStudentsMap[currentClassSection.id] || [];

      if (!students.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted"><i class="fas fa-user-slash me-2"></i>No students enrolled in Section ${esc(currentClassSection.name)}.</td></tr>`;
        document.getElementById('classModalStats').innerHTML = `<span>Total Students: <strong>0</strong></span>`;
        return;
      }

      // Build student -> quarter grade map
      const studentGradesMap = {};
      students.forEach(s => { studentGradesMap[s.id] = { 1: null, 2: null, 3: null, 4: null }; });

      gradeRows.forEach(g => {
        if (studentGradesMap[g.student_id]) {
          studentGradesMap[g.student_id][g.quarter] = g;
        }
      });

      let classFinals = [];

      tbody.innerHTML = students.map(stu => {
        const qMap = studentGradesMap[stu.id];
        
        // Calculate average across terms for this subject
        const termVals = [1, 2, 3].map(q => qMap[q] ? parseFloat(qMap[q].final_grade) : null).filter(v => v !== null);
        const subAvg = termVals.length ? (termVals.reduce((a,b)=>a+b,0)/termVals.length).toFixed(2) : null;
        if (subAvg !== null) classFinals.push(parseFloat(subAvg));

        const subRemarks = subAvg !== null ? (subAvg >= 75 ? '<span class="badge bg-success">Passed</span>' : '<span class="badge bg-danger">Failed</span>') : '<span class="text-muted">—</span>';

        const termCellsHtml = [1, 2, 3].map(q => {
          const g = qMap[q];
          if (g && g.final_grade !== null) {
            const val = parseFloat(g.final_grade).toFixed(2);
            const badgeCls = g.final_grade >= 75 ? 'bg-success-subtle text-success fw-bold' : 'bg-danger-subtle text-danger fw-bold';
            return `
              <td class="text-center">
                <div class="term-cell-grade">
                  <span class="badge ${badgeCls}" style="font-size:.85rem">${val}</span>
                  <div class="term-cell-actions">
                    <button class="btn-term-action edit" onclick="openEditGradeModal(${stu.id}, ${currentClassSubject.id}, ${q}, '${escAttr(stu.full_name)}')" title="Edit Grade"><i class="fas fa-edit"></i></button>
                    <button class="btn-term-action del" onclick="deleteGrade(${g.id})" title="Delete Grade"><i class="fas fa-trash-alt"></i></button>
                  </div>
                </div>
              </td>`;
          } else {
            return `
              <td class="text-center">
                <div class="term-cell-grade">
                  <span class="text-muted" style="font-size:.8rem">—</span>
                  <div class="term-cell-actions">
                    <button class="btn btn-outline-primary btn-sm py-0 px-1" style="font-size:.7rem" onclick="openEditGradeModal(${stu.id}, ${currentClassSubject.id}, ${q}, '${escAttr(stu.full_name)}')"><i class="fas fa-plus me-1"></i>Add</button>
                  </div>
                </div>
              </td>`;
          }
        }).join('');

        return `
          <tr>
            <td>
              <div class="fw-semibold text-dark">${esc(stu.full_name)}</div>
            </td>
            <td><code>${stu.lrn||'—'}</code></td>
            ${termCellsHtml}
            <td class="text-center"><strong class="${subAvg >= 75 ? 'text-success':'text-danger'}">${subAvg || '—'}</strong></td>
            <td class="text-center">${subRemarks}</td>
          </tr>`;
      }).join('');

      const classAvg = classFinals.length ? (classFinals.reduce((a,b)=>a+b,0)/classFinals.length).toFixed(2) : '—';
      document.getElementById('classModalStats').innerHTML = `
        <span>Enrolled Students: <strong>${students.length}</strong></span>
        <span class="border-start ps-3">Graded Students: <strong>${classFinals.length}</strong></span>
        <span class="border-start ps-3">Subject Class Average: <strong class="text-primary">${classAvg}</strong></span>
      `;

    } catch(e) {
      console.error('Load grid error:', e);
      tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle me-2"></i>Error loading class student grid.</td></tr>`;
    }
  }

  function openEditGradeModal(studentId, subjectId, quarter, studentName) {
    document.getElementById('editGradeStudentId').value = studentId;
    document.getElementById('editGradeSubjectId').value = subjectId;
    document.getElementById('editGradeQuarter').value   = quarter;

    const termName = quarter == 1 ? '1st Term' : (quarter == 2 ? '2nd Term' : '3rd Term');
    document.getElementById('editGradeTitle').innerHTML = `<i class="fas fa-edit me-2"></i>Grade Entry (${termName})`;
    document.getElementById('editGradeStudentInfo').innerHTML = `<strong>Student:</strong> ${studentName} &nbsp;|&nbsp; <strong>Subject:</strong> ${esc(currentClassSubject.name)} &nbsp;|&nbsp; <strong>Term:</strong> ${termName}`;

    document.getElementById('editWW').value = '';
    document.getElementById('editPT').value = '';
    document.getElementById('editQE').value = '';
    document.getElementById('editPreviewBox').style.display = 'none';

    // Prefill existing grade if any
    const url = `../../api/grades.php?action=list&student_id=${studentId}&subject_id=${subjectId}&quarter=${quarter}`;
    fetch(url).then(r=>r.json()).then(data=>{
      const g = (data.data||[])[0];
      if (g) {
        document.getElementById('editWW').value = g.written_works !== null ? g.written_works : '';
        document.getElementById('editPT').value = g.performance_tasks !== null ? g.performance_tasks : '';
        document.getElementById('editQE').value = g.quarterly_exam !== null ? g.quarterly_exam : '';
        updateEditPreview();
      }
    });

    new bootstrap.Modal(document.getElementById('editGradeModal')).show();
  }

  function updateEditPreview() {
    const ww = parseFloat(document.getElementById('editWW').value);
    const pt = parseFloat(document.getElementById('editPT').value);
    const qe = parseFloat(document.getElementById('editQE').value);

    if (!isNaN(ww) && !isNaN(pt) && !isNaN(qe)) {
      const finalGrade = (ww * 0.20 + pt * 0.50 + qe * 0.30).toFixed(2);
      const remarks = finalGrade >= 75 ? 'Passed' : 'Failed';
      const badgeCls = finalGrade >= 75 ? 'bg-success' : 'bg-danger';

      document.getElementById('editPreviewContent').innerHTML = `<span class="badge ${badgeCls} fs-6">${finalGrade} (${remarks})</span>`;
      document.getElementById('editPreviewBox').style.display = 'block';
    } else {
      document.getElementById('editPreviewBox').style.display = 'none';
    }
  }

  async function saveTermGrade() {
    const studentId = document.getElementById('editGradeStudentId').value;
    const subjectId = document.getElementById('editGradeSubjectId').value;
    const quarter   = document.getElementById('editGradeQuarter').value;
    const ww        = document.getElementById('editWW').value;
    const pt        = document.getElementById('editPT').value;
    const qe        = document.getElementById('editQE').value;

    if (!ww || !pt || !qe) {
      showToast('Please fill in Written Works (20%), Performance Tasks (50%), and Quarterly Exam (30%).', 'error');
      return;
    }

    const body = new FormData();
    body.append('action', 'save');
    body.append('student_id', studentId);
    body.append('subject_id', subjectId);
    body.append('quarter', quarter);
    body.append('written_works', ww);
    body.append('performance_tasks', pt);
    body.append('quarterly_exam', qe);

    try {
      const res = await fetch('../../api/grades.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        showToast('Grade saved!', 'success');
        bootstrap.Modal.getInstance(document.getElementById('editGradeModal')).hide();
        await loadClassStudentGrid();
        await loadData();
        renderSummary();
      } else {
        showToast(data.message || 'Failed to save grade.', 'error');
      }
    } catch(e) {
      showToast('Server error.', 'error');
    }
  }

  async function deleteGrade(id) {
    if (!confirm('Are you sure you want to delete this grade entry?')) return;
    const body = new FormData();
    body.append('action', 'delete');
    body.append('id', id);

    try {
      const res = await fetch('../../api/grades.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        showToast('Grade entry deleted.', 'info');
        await loadClassStudentGrid();
        await loadData();
        renderSummary();
      } else {
        showToast(data.message || 'Failed to delete grade.', 'error');
      }
    } catch(e) { showToast('Server error.', 'error'); }
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
