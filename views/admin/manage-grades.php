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
        <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
          <span class="card-title"><i class="fas fa-book me-2 text-primary"></i>Subjects &amp; Grade Level Sections</span>
          <div class="d-flex align-items-center gap-2">
            <button class="btn btn-primary btn-sm" onclick="openAddSubjectModal()">
              <i class="fas fa-plus me-1"></i>New Subject
            </button>
            <button class="btn btn-outline-secondary btn-sm" onclick="restoreDefaultSubjects()" title="Restore 8 default core DepEd subjects">
              <i class="fas fa-rotate me-1"></i>Reset Defaults
            </button>
            <span class="badge bg-primary" id="subjectCount" style="font-size:.8rem">—</span>
          </div>
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

        <div class="mb-3">
          <label class="form-label fw-semibold">Term Grade <span class="text-danger">*</span></label>
          <div class="input-group input-group-lg">
            <input type="number" id="editGrade" class="form-control" min="0" max="100" step="0.01" placeholder="e.g. 85.00" oninput="updateEditPreview()" required/>
            <span class="input-group-text">/ 100</span>
          </div>
          <small class="text-muted">Enter direct numerical term grade (0.00 – 100.00).</small>
        </div>

        <div class="mt-3 p-3 rounded text-center border" id="editPreviewBox" style="display:none;background:#f8fafc">
          <div class="text-muted" style="font-size:.8rem;text-transform:uppercase;font-weight:600">Recorded Term Grade</div>
          <div class="h3 mb-1 fw-bold" id="editPreviewContent">—</div>
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

<!-- ═══════════════════════════════════════════════════════════
     ADD / EDIT SUBJECT MODAL
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="subjectModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background:#0c1326;color:#fff">
        <h5 class="modal-title" id="subjectModalTitle"><i class="fas fa-book me-2"></i>New Subject</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="modalSubjectId"/>
        <div class="mb-3">
          <label class="form-label fw-semibold">Subject Name <span class="text-danger">*</span></label>
          <input type="text" id="modalSubjectName" class="form-control" placeholder="e.g. Mathematics" required/>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Subject Code <span class="text-danger">*</span></label>
          <input type="text" id="modalSubjectCode" class="form-control" placeholder="e.g. MATH" required/>
          <small class="text-muted">Short abbreviation used in report cards and tables.</small>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Curriculum Level <span class="text-danger">*</span></label>
          <select id="modalSubjectLevel" class="form-select" required>
            <option value="JHS">Junior High School (JHS – Grades 7–10)</option>
            <option value="SHS">Senior High School (SHS – Grades 11–12)</option>
          </select>
          <small class="text-muted">Determines whether this subject appears in JHS or SHS grade levels and SF9 forms.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary btn-sm" onclick="saveSubject()"><i class="fas fa-save me-1"></i>Save Subject</button>
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
  let allSections = [], allSubjects = [], allStudents = [], allGrades = [], allTeachersList = [], allTeacherAssignments = [];
  let currentClassSubject = null, currentClassSection = null;
  let sectionStudentsMap = {}; // { sectionId: [student, ...] }
  let activeGridStudentId = null;

  // ── Boot ──────────────────────────────────────────────────────────────────
  async function init() {
    await loadData();
    populateFilters();
    renderSummary();
    renderHierarchy();
  }

  async function loadData() {
    const [secRes, subRes, stuRes, advRes] = await Promise.all([
      fetch('../../api/sections.php?action=list'),
      fetch('../../api/grades.php?action=list'),
      fetch('../../api/students.php?action=list'),
      fetch('../../api/sections.php?action=advisers'),
    ]);

    const secData = await secRes.json();
    const grData  = await subRes.json();
    const stuData = await stuRes.json();
    const advData = await advRes.json();

    allSections          = secData.data || [];
    allSubjects          = grData.subjects || [];
    allStudents          = stuData.data || [];
    allGrades            = grData.data || [];
    allTeachersList      = advData.data || [];
    allTeacherAssignments = grData.teacher_assignments || [];

    // Index students by section
    sectionStudentsMap = {};
    allSections.forEach(sec => {
      let list = allStudents.filter(s => s.section_id == sec.id || s.section_name == sec.name);
      list.forEach(s => {
        s.raw_name = s.full_name;
        s.full_name = formatLastFirst(s.full_name);
      });
      list.sort((a, b) => a.full_name.localeCompare(b.full_name, undefined, { sensitivity: 'base' }));
      sectionStudentsMap[sec.id] = list;
    });
  }

  function formatLastFirst(fullName) {
    if (!fullName) return '';
    const str = String(fullName).trim();
    if (str.includes(',')) {
      const parts = str.split(',').map(p => p.trim());
      return `${parts[0]}, ${parts.slice(1).join(' ')}`;
    }
    const parts = str.split(/\s+/);
    if (parts.length === 1) return parts[0];

    const lower = parts.map(p => p.toLowerCase());
    const len = parts.length;
    let lastIdx = len - 1;

    const prefixes = ['dela', 'delos', 'san', 'santa', 'sta', 'sta.', 'sto', 'sto.', 'del', 'de', 'van', 'von', 'mc', 'mac'];

    if (len >= 4 && lower[len - 3] === 'de' && lower[len - 2] === 'la') {
      lastIdx = len - 3;
    } else if (len >= 3 && prefixes.includes(lower[len - 2])) {
      lastIdx = len - 2;
    }

    const lastName = parts.slice(lastIdx).join(' ');
    const firstName = parts.slice(0, lastIdx).join(' ');
    return `${lastName}, ${firstName}`;
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

      const subLevel = sub.level || 'JHS';
      const defaultGrades = (subLevel === 'SHS') ? [11, 12] : [7, 8, 9, 10];
      const gradeLevels = defaultGrades.filter(g => {
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

      const levelBadge = subLevel === 'SHS'
        ? `<span class="badge bg-warning text-dark ms-2" style="font-size:.72rem"><i class="fas fa-graduation-cap me-1"></i>SHS</span>`
        : `<span class="badge bg-info-subtle text-info border border-info-subtle ms-2" style="font-size:.72rem">JHS</span>`;

      return `
        <div class="subject-item mb-3">
          <div class="accordion-header d-flex align-items-center rounded-top" id="heading-subject-${sub.id}" style="background-color:#0c1326!important; overflow:hidden">
            <button class="accordion-button subject-accordion-btn flex-grow-1 shadow-none ${collapsedSubjectCls}" type="button" data-bs-toggle="collapse" data-bs-target="#${subCollapseId}">
              <i class="fas fa-book me-2"></i>${esc(sub.name)} 
              <span class="badge bg-primary-subtle text-primary ms-2" style="font-size:.72rem">${esc(sub.code||'')}</span>
              ${levelBadge}
            </button>
            <div class="pe-3 d-flex gap-1" style="background:#0c1326; z-index:2">
              <button class="btn btn-sm btn-outline-light py-0 px-2" style="font-size:.78rem" title="Edit Subject" onclick="event.stopPropagation(); openEditSubjectModal(${sub.id}, '${escAttr(sub.name)}', '${escAttr(sub.code||'')}', '${escAttr(subLevel)}')">
                <i class="fas fa-edit me-1"></i>Edit
              </button>
              <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:.78rem" title="Delete Subject" onclick="event.stopPropagation(); deleteSubject(${sub.id}, '${escAttr(sub.name)}')">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
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

    activeGridStudentId = null;
    const modalTitle = document.getElementById('classModalTitle');
    const modalMeta  = document.getElementById('classModalMeta');
    modalTitle.innerHTML = `<i class="fas fa-book-open me-2 text-warning"></i>${esc(currentClassSubject.name)} <span class="badge bg-primary-subtle text-primary" style="font-size:.75rem">${esc(currentClassSubject.code||'')}</span>`;
    modalMeta.textContent = `Grade ${currentClassSection.grade_level} - Section ${currentClassSection.name}`;

    // Populate quick student print dropdown
    const quickSel = document.getElementById('modalQuickStudentSelect');
    if (quickSel) {
      const sectionStudents = sectionStudentsMap[currentClassSection.id] || [];
      quickSel.innerHTML = '<option value="">Select Student to Print…</option>' + 
        sectionStudents.map(s => `<option value="${s.id}">${esc(s.full_name)}</option>`).join('');
    }

    const modal = new bootstrap.Modal(document.getElementById('classGradesModal'));
    modal.show();

    await loadClassStudentGrid();
  }

  async function loadClassStudentGrid() {
    const tbody = document.getElementById('classStudentTableBody');
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading student grades grid…</td></tr>`;

    try {
      const url = `../../api/grades.php?action=list&subject_id=${currentClassSubject.id}&section_id=${currentClassSection.id}`;
      const res = await fetch(url);
      const data = await res.json();
      const gradeRows = data.data || [];

      // Students in this section
      const students = sectionStudentsMap[currentClassSection.id] || [];

      if (!students.length) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted"><i class="fas fa-user-slash me-2"></i>No students enrolled in Section ${esc(currentClassSection.name)}.</td></tr>`;
        document.getElementById('classModalStats').innerHTML = `<span>Total Students: <strong>0</strong></span>`;
        return;
      }

      // Build student -> term grade map (3 Terms)
      const studentGradesMap = {};
      students.forEach(s => { studentGradesMap[s.id] = { 1: null, 2: null, 3: null }; });

      gradeRows.forEach(g => {
        if (studentGradesMap[g.student_id]) {
          studentGradesMap[g.student_id][g.quarter] = g;
        }
      });

      let classFinals = [];

      tbody.innerHTML = students.map(stu => {
        const qMap = studentGradesMap[stu.id];
        
        // Calculate average across 3 terms for this subject ONLY if all 3 terms exist
        const hasT1 = qMap[1] && qMap[1].final_grade !== null && qMap[1].final_grade !== '';
        const hasT2 = qMap[2] && qMap[2].final_grade !== null && qMap[2].final_grade !== '';
        const hasT3 = qMap[3] && qMap[3].final_grade !== null && qMap[3].final_grade !== '';

        let subAvg = null;
        if (hasT1 && hasT2 && hasT3) {
          const v1 = parseFloat(qMap[1].final_grade);
          const v2 = parseFloat(qMap[2].final_grade);
          const v3 = parseFloat(qMap[3].final_grade);
          subAvg = ((v1 + v2 + v3) / 3).toFixed(2);
          classFinals.push(parseFloat(subAvg));
        }

        const subRemarks = subAvg !== null ? (parseFloat(subAvg) >= 75 ? '<span class="badge bg-success">Passed</span>' : '<span class="badge bg-danger">Failed</span>') : '<span class="text-muted">—</span>';

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

        const isSelected = activeGridStudentId && activeGridStudentId == stu.id;

        return `
          <tr data-student-id="${stu.id}" class="${isSelected ? 'table-primary' : ''}" onclick="selectGridStudent(${stu.id}, this)" style="cursor:pointer">
            <td>
              <div class="fw-semibold text-dark">${esc(stu.full_name)}</div>
            </td>
            <td><code>${stu.lrn||'—'}</code></td>
            ${termCellsHtml}
            <td class="text-center"><strong class="${subAvg !== null ? (parseFloat(subAvg) >= 75 ? 'text-success':'text-danger') : ''}">${subAvg || '—'}</strong></td>
            <td class="text-center">${subRemarks}</td>
            <td class="text-center" onclick="event.stopPropagation()">
              <button class="btn btn-sm btn-primary py-1 px-2 text-nowrap" onclick="openStudentReportCard(${stu.id})" title="Print Individual SF9 Grade Sheet for ${escAttr(stu.full_name)}">
                <i class="fas fa-print me-1"></i>Print Grade Sheet
              </button>
            </td>
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
      tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle me-2"></i>Error loading class student grid.</td></tr>`;
    }
  }

  function openEditGradeModal(studentId, subjectId, quarter, studentName) {
    document.getElementById('editGradeStudentId').value = studentId;
    document.getElementById('editGradeSubjectId').value = subjectId;
    document.getElementById('editGradeQuarter').value   = quarter;

    const termNames = { 1: '1st Quarter / Term', 2: '2nd Quarter / Term', 3: '3rd Quarter / Term', 4: '4th Quarter / Term' };
    const termName = termNames[quarter] || `Quarter ${quarter}`;
    document.getElementById('editGradeTitle').innerHTML = `<i class="fas fa-edit me-2"></i>Grade Entry (${termName})`;
    document.getElementById('editGradeStudentInfo').innerHTML = `<strong>Student:</strong> ${studentName} &nbsp;|&nbsp; <strong>Subject:</strong> ${esc(currentClassSubject.name)} &nbsp;|&nbsp; <strong>Period:</strong> ${termName}`;

    document.getElementById('editGrade').value = '';
    document.getElementById('editPreviewBox').style.display = 'none';

    // Prefill existing grade if any
    const url = `../../api/grades.php?action=list&student_id=${studentId}&subject_id=${subjectId}&quarter=${quarter}`;
    fetch(url).then(r=>r.json()).then(data=>{
      const g = (data.data||[])[0];
      if (g && g.final_grade !== null) {
        document.getElementById('editGrade').value = parseFloat(g.final_grade).toFixed(2);
        updateEditPreview();
      }
    });

    new bootstrap.Modal(document.getElementById('editGradeModal')).show();
  }

  function updateEditPreview() {
    const rawVal = document.getElementById('editGrade').value;

    if (rawVal !== '') {
      const finalGrade = Math.max(0, Math.min(100, parseFloat(rawVal) || 0)).toFixed(2);
      const isPassed = parseFloat(finalGrade) >= 75;
      const remarks = isPassed ? 'Passed' : 'Failed';
      const badgeCls = isPassed ? 'bg-success' : 'bg-danger';

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
    const gradeVal  = document.getElementById('editGrade').value;

    if (gradeVal === '') {
      showToast('Please enter a term grade.', 'error');
      return;
    }

    const numGrade = parseFloat(gradeVal);
    if (isNaN(numGrade) || numGrade < 0 || numGrade > 100) {
      showToast('Term grade must be a number between 0 and 100.', 'error');
      return;
    }

    const body = new FormData();
    body.append('action', 'save');
    body.append('student_id', studentId);
    body.append('subject_id', subjectId);
    body.append('quarter', quarter);
    body.append('grade', numGrade);

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

  // ── Student Selection & Report Navigation ────────────────────────────────
  function selectGridStudent(stuId, rowEl) {
    activeGridStudentId = stuId;
    const sel = document.getElementById('modalQuickStudentSelect');
    if (sel) sel.value = stuId;
    const rows = document.querySelectorAll('#classStudentTableBody tr');
    rows.forEach(r => r.classList.remove('table-primary'));
    if (rowEl) rowEl.classList.add('table-primary');
  }

  function onModalStudentSelected(stuId) {
    activeGridStudentId = stuId ? parseInt(stuId) : null;
    const rows = document.querySelectorAll('#classStudentTableBody tr');
    rows.forEach(r => {
      r.classList.remove('table-primary');
      if (stuId && r.getAttribute('data-student-id') == stuId) {
        r.classList.add('table-primary');
        r.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    });
  }

  function openStudentReportCard(studentId) {
    if (!studentId) {
      showToast('No student selected.', 'warning');
      return;
    }
    window.open(`reports.php?type=student&student_id=${studentId}`, '_blank');
  }

  function printSelectedStudentGradeSheet() {
    const sel = document.getElementById('modalQuickStudentSelect');
    const stuId = (sel && sel.value) ? sel.value : activeGridStudentId;
    if (!stuId) {
      showToast('Please select a student to print their individual grade sheet.', 'info');
      if (sel) sel.focus();
      return;
    }
    openStudentReportCard(stuId);
  }

  function printCurrentModalStudentReport() {
    const stuId = document.getElementById('editGradeStudentId')?.value;
    if (!stuId) {
      showToast('No student currently opened.', 'warning');
      return;
    }
    openStudentReportCard(stuId);
  }

  function printSectionGrades() {
    if (!currentClassSubject || !currentClassSection) {
      showToast('No section or subject selected to print.', 'warning');
      return;
    }
    const printTitle = `${currentClassSubject.name} – ${currentClassSection.name} (Grade ${currentClassSection.grade_level})`;
    const students = sectionStudentsMap[currentClassSection.id] || [];
    const dateStr = new Date().toLocaleDateString('en-PH', { dateStyle: 'long' });
    
    let tableRows = '';
    students.forEach((stu, i) => {
      const q1 = getGrade(stu.id, currentClassSubject.id, 1);
      const q2 = getGrade(stu.id, currentClassSubject.id, 2);
      const q3 = getGrade(stu.id, currentClassSubject.id, 3);
      const hasAll = (q1 !== null && !isNaN(q1)) && (q2 !== null && !isNaN(q2)) && (q3 !== null && !isNaN(q3));
      const avg = hasAll ? ((q1 + q2 + q3) / 3).toFixed(2) : '—';
      const isPassed = avg !== '—' && parseFloat(avg) >= 75;
      const remarks = avg === '—' ? '—' : (isPassed ? 'Passed' : 'Failed');
      
      tableRows += `
        <tr>
          <td style="text-align:center">${i + 1}</td>
          <td><strong>${esc(stu.full_name)}</strong></td>
          <td><code>${esc(stu.lrn || '—')}</code></td>
          <td style="text-align:center">${q1 !== null ? q1.toFixed(2) : '—'}</td>
          <td style="text-align:center">${q2 !== null ? q2.toFixed(2) : '—'}</td>
          <td style="text-align:center">${q3 !== null ? q3.toFixed(2) : '—'}</td>
          <td style="text-align:center;font-weight:bold">${avg}</td>
          <td style="text-align:center;font-weight:bold;color:${isPassed ? '#16a34a' : '#dc2626'}">${remarks}</td>
        </tr>`;
    });

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8"/>
  <title>Grade Sheet - ${printTitle}</title>
  <style>
    @page { size: portrait; margin: 12mm 12mm 15mm 12mm; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; color: #0f172a; margin: 0; padding: 10px; font-size: 10pt; }
    .header { display: flex; align-items: center; justify-content: space-between; gap: 15px; border-bottom: 2.5px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 15px; }
    .logo { width: 62px; height: 62px; min-width: 62px; display: flex; align-items: center; justify-content: center; }
    .dept { font-size: 8pt; text-transform: uppercase; color: #64748b; margin: 0; letter-spacing: 0.05em; }
    h2 { margin: 2px 0; font-size: 13pt; color: #0f172a; font-weight: 700; }
    p { margin: 0; font-size: 8.5pt; color: #475569; }
    .meta-box { display: flex; justify-content: space-between; background: #f8fafc; border: 1px solid #cbd5e1; padding: 8px 12px; border-radius: 6px; margin-bottom: 15px; font-size: 9pt; }
    table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 15px; }
    th, td { border: 1px solid #cbd5e1; padding: 5px 8px; vertical-align: middle; }
    th { background: #f1f5f9; text-transform: uppercase; font-size: 8pt; font-weight: bold; color: #1e293b; }
    .signatures { display: flex; justify-content: space-between; margin-top: 35px; page-break-inside: avoid; }
    .sig-box { width: 28%; text-align: center; font-size: 9pt; }
    .sig-line { border-bottom: 1px solid #0f172a; height: 35px; margin-bottom: 4px; }
    .sig-title { font-weight: bold; }
    .sig-role { font-size: 8pt; color: #64748b; }
  </style>
</head>
<body>
  <div class="header">
    <div class="logo"><img src="../../assets/images/deped_logo.png" alt="DepEd Seal" style="width:60px;height:60px;object-fit:contain;"/></div>
    <div style="flex:1;text-align:center;">
      <p class="dept">Republic of the Philippines &bull; Department of Education &bull; Region II &bull; Schools Division of Cagayan</p>
      <h2>LUBO NATIONAL HIGH SCHOOL</h2>
      <p>Lubo, Sto. Niño, Cagayan &nbsp;|&nbsp; Online Grade Monitoring System</p>
      <div style="font-weight:700;font-size:10pt;color:#1e3a8a;margin-top:2px;">SECTION GRADE SHEET SUMMARY</div>
    </div>
    <div class="logo"><img src="../../assets/images/lubo_logo.png" alt="Lubo NHS Logo" style="width:60px;height:60px;object-fit:contain;"/></div>
  </div>
  <div class="meta-box">
    <div><strong>Subject:</strong> ${esc(currentClassSubject.name)} (${esc(currentClassSubject.code||'—')})</div>
    <div><strong>Section:</strong> ${esc(currentClassSection.name)} (Grade ${esc(currentClassSection.grade_level)})</div>
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
      <div class="sig-title">Subject Teacher</div>
      <div class="sig-role">Prepared By</div>
    </div>
    <div class="sig-box">
      <div class="sig-line"></div>
      <div class="sig-title">Class Adviser</div>
      <div class="sig-role">Verified By</div>
    </div>
    <div class="sig-box">
      <div class="sig-line"></div>
      <div class="sig-title">School Principal</div>
      <div class="sig-role">Approved By</div>
    </div>
  </div>
  <script>
    window.onload = function() {
      window.print();
    };
  <\/script>
</body>
</html>`);
    printWindow.document.close();
  }

  // ── Subject Management Functions ─────────────────────────────────────────
  let subjectModalInstance = null;
  function getSubjectModal() {
    if (!subjectModalInstance) {
      subjectModalInstance = new bootstrap.Modal(document.getElementById('subjectModal'));
    }
    return subjectModalInstance;
  }

  function populateSubjectTeacherSelect(selectedId = null) {
    const sel = document.getElementById('modalSubjectTeacher');
    if (!sel) return;
    sel.innerHTML = '<option value="">— No Assigned Teacher (None) —</option>' +
      allTeachersList.map(t => `<option value="${t.id}" ${selectedId && String(selectedId) === String(t.id) ? 'selected' : ''}>${esc(t.full_name)} (${esc(t.email)})</option>`).join('');
  }

  function openAddSubjectModal() {
    document.getElementById('subjectModalTitle').innerHTML = '<i class="fas fa-plus-circle me-2"></i>New Subject';
    document.getElementById('modalSubjectId').value = '';
    document.getElementById('modalSubjectName').value = '';
    document.getElementById('modalSubjectCode').value = '';
    document.getElementById('modalSubjectLevel').value = 'JHS';
    getSubjectModal().show();
  }

  function openEditSubjectModal(id, name, code, level = 'JHS') {
    document.getElementById('subjectModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Subject';
    document.getElementById('modalSubjectId').value = id;
    document.getElementById('modalSubjectName').value = name;
    document.getElementById('modalSubjectCode').value = code;
    document.getElementById('modalSubjectLevel').value = (level === 'SHS') ? 'SHS' : 'JHS';
    getSubjectModal().show();
  }

  async function saveSubject() {
    const id    = document.getElementById('modalSubjectId').value.trim();
    const name  = document.getElementById('modalSubjectName').value.trim();
    const code  = document.getElementById('modalSubjectCode').value.trim().toUpperCase();
    const level = document.getElementById('modalSubjectLevel').value;

    if (!name || !code) {
      showToast('Subject name and code are required.', 'error');
      return;
    }

    const action = id ? 'update_subject' : 'add_subject';
    const body = new FormData();
    body.append('action', action);
    if (id) body.append('id', id);
    body.append('name', name);
    body.append('code', code);
    body.append('level', level);

    try {
      const res  = await fetch('../../api/grades.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        showToast(data.message || 'Subject saved successfully.', 'success');
        getSubjectModal().hide();
        await loadData();
        populateFilters();
        renderSummary();
        renderHierarchy();
      } else {
        showToast(data.message || 'Failed to save subject.', 'error');
      }
    } catch (err) {
      showToast('Network error while saving subject.', 'error');
    }
  }

  async function deleteSubject(id, name) {
    if (!confirm(`Are you sure you want to delete "${name}"? This will also remove any grades linked to this subject.`)) {
      return;
    }
    const body = new FormData();
    body.append('action', 'delete_subject');
    body.append('id', id);

    try {
      const res  = await fetch('../../api/grades.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        showToast(data.message || 'Subject deleted.', 'success');
        await loadData();
        populateFilters();
        renderSummary();
        renderHierarchy();
      } else {
        showToast(data.message || 'Failed to delete subject.', 'error');
      }
    } catch (err) {
      showToast('Network error while deleting subject.', 'error');
    }
  }

  async function restoreDefaultSubjects() {
    if (!confirm('This will restore the 8 default DepEd subjects and clear grades. Are you sure?')) {
      return;
    }
    const body = new FormData();
    body.append('action', 'restore_subjects');

    try {
      const res  = await fetch('../../api/grades.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        showToast(data.message || 'Default subjects restored.', 'success');
        await loadData();
        populateFilters();
        renderSummary();
        renderHierarchy();
      } else {
        showToast(data.message || 'Failed to restore default subjects.', 'error');
      }
    } catch (err) {
      showToast('Network error while restoring subjects.', 'error');
    }
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
