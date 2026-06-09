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
  <link rel="stylesheet" href="../../assets/css/style.css"/>
  <style>
    .student-row { cursor:pointer; transition:background .15s; }
    .student-row:hover { background:#f0f7ff !important; }
    .grade-matrix th { font-size:.78rem; text-align:center; font-weight:600; white-space:nowrap; }
    .grade-matrix td { text-align:center; vertical-align:middle; font-size:.85rem; }
    .grade-matrix td.subject-col { text-align:left; font-weight:600; min-width:130px; }
    .cell-empty { color:#cbd5e1; font-size:.95rem; }
    .cell-grade { display:inline-flex; align-items:center; gap:4px; }
    .btn-cell { border:none; background:none; padding:2px 4px; border-radius:4px; cursor:pointer; font-size:.7rem; line-height:1; }
    .btn-cell:hover { background:#e2e8f0; }
    .btn-cell.edit  { color:#1d4ed8; }
    .btn-cell.del   { color:#ef4444; }
    .student-avatar { width:36px;height:36px;border-radius:50%;background:#0c1326;
      display:inline-flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;color:#fff; }
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
          <div class="topbar-subtitle">Select a student to view and edit their grades</div>
        </div>
      </div>
    </header>

    <main class="page-content fade-in">

      <!-- Filter bar -->
      <div class="content-card mb-3">
        <div class="card-body-custom">
          <div class="row g-2 align-items-center">
            <div class="col-md-5">
              <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search by name or LRN…" oninput="filterStudents()"/>
              </div>
            </div>
            <div class="col-md-3">
              <select id="filterSection" class="form-select form-select-sm" onchange="filterStudents()">
                <option value="">All Sections</option>
              </select>
            </div>
            <div class="col-md-2">
              <button class="btn btn-outline-secondary btn-sm w-100" onclick="clearFilters()">
                <i class="fas fa-undo me-1"></i>Clear
              </button>
            </div>
            <div class="col-md-2 text-end">
              <span class="badge bg-primary" id="studentCount" style="font-size:.8rem">—</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Student roster -->
      <div class="content-card">
        <div class="card-header-custom">
          <span class="card-title"><i class="fas fa-users me-2 text-primary"></i>Students — click a row to manage grades</span>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th style="width:44px"></th>
                <th>Student</th>
                <th>LRN</th>
                <th>Section</th>
                <th class="text-center">Overall Avg</th>
                <th class="text-center">Passed</th>
                <th class="text-center">Grades</th>
                <th class="text-center">Action</th>
              </tr>
            </thead>
            <tbody id="studentTableBody">
              <tr><td colspan="8" class="text-center py-4 text-muted">Loading students…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     GRADE MATRIX MODAL
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="gradeMatrixModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header" style="background:#0c1326;color:#fff">
        <div>
          <h5 class="modal-title mb-0" id="matrixStudentName">—</h5>
          <small id="matrixStudentMeta" style="opacity:.8">—</small>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-light btn-sm" onclick="openAddGradeModal()">
            <i class="fas fa-plus me-1"></i>Add Grade
          </button>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
      </div>

      <div class="modal-body p-0">
        <!-- Quarter filter tabs -->
        <div class="px-3 pt-3 pb-0">
          <ul class="nav nav-pills gap-1" id="quarterTabs">
            <li class="nav-item"><button class="nav-link active" onclick="setQuarterView(0,this)">All Quarters</button></li>
            <li class="nav-item"><button class="nav-link" onclick="setQuarterView(1,this)">1st Quarter</button></li>
            <li class="nav-item"><button class="nav-link" onclick="setQuarterView(2,this)">2nd Quarter</button></li>
            <li class="nav-item"><button class="nav-link" onclick="setQuarterView(3,this)">3rd Quarter</button></li>
            <li class="nav-item"><button class="nav-link" onclick="setQuarterView(4,this)">4th Quarter</button></li>
          </ul>
        </div>

        <!-- Stats strip -->
        <div class="px-3 py-2 d-flex gap-3 flex-wrap" id="matrixStats" style="border-bottom:1px solid #e2e8f0;font-size:.82rem"></div>

        <!-- Grade matrix table -->
        <div class="table-responsive px-3 pb-3">
          <table class="table grade-matrix mt-2" id="gradeMatrix">
            <thead id="gradeMatrixHead"></thead>
            <tbody id="gradeMatrixBody">
              <tr><td colspan="6" class="text-center py-4 text-muted">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     ADD / EDIT SINGLE GRADE MODAL
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="editGradeModal" tabindex="-1" style="z-index:1060">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editGradeTitle"><i class="fas fa-edit me-2"></i>Edit Grade</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="editGradeId"/>
        <div class="mb-3">
          <label class="form-label fw-semibold">Subject</label>
          <select id="editSubjectField" class="form-select"></select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Grading Quarter</label>
          <select id="editQuarterField" class="form-select">
            <option value="1">1st Quarter</option>
            <option value="2">2nd Quarter</option>
            <option value="3">3rd Quarter</option>
            <option value="4">4th Quarter</option>
          </select>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Written Works <small class="text-muted">(20%)</small></label>
            <input type="number" id="editWW" class="form-control" min="0" max="100" step="0.01" oninput="updateEditPreview()"/>
          </div>
          <div class="col-md-4">
            <label class="form-label">Performance Tasks <small class="text-muted">(60%)</small></label>
            <input type="number" id="editPT" class="form-control" min="0" max="100" step="0.01" oninput="updateEditPreview()"/>
          </div>
          <div class="col-md-4">
            <label class="form-label">Quarterly Exam <small class="text-muted">(20%)</small></label>
            <input type="number" id="editQE" class="form-control" min="0" max="100" step="0.01" oninput="updateEditPreview()"/>
          </div>
        </div>
        <div class="mt-3 p-2 rounded" id="editPreviewBox" style="display:none;background:#f8fafc;border:1px solid #e2e8f0">
          <div class="d-flex justify-content-between align-items-center">
            <span style="font-size:.8rem;color:#64748b;font-weight:600">Final Grade Preview</span>
            <div id="editPreviewContent"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="saveGrade()"><i class="fas fa-save me-1"></i>Save Grade</button>
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
  let allStudents = [], filteredStudents = [];
  let allSubjects = [], allGrades = [];
  let selectedStudentId = null;
  let studentGradesCache = {};   // { studentId: [grade, ...] }
  let quarterView = 0;           // 0=all, 1-4=specific quarter

  // ── Boot ──────────────────────────────────────────────────────────────────
  async function init() {
    const [sRes, subRes] = await Promise.all([
      fetch('../../api/students.php?action=list'),
      fetch('../../api/grades.php?action=list'),
    ]);
    const sData   = await sRes.json();
    const subData = await subRes.json();

    allStudents  = sData.data      || [];
    allSubjects  = subData.subjects || [];
    allGrades    = subData.data    || [];

    // Index grades by student_id for quick lookup
    allStudents.forEach(s => {
      studentGradesCache[s.id] = allGrades.filter(g => g.student_id == s.id);
    });

    // Populate section filter
    const sections = [...new Set(allStudents.map(s => s.section_name).filter(Boolean))].sort();
    const secSel = document.getElementById('filterSection');
    sections.forEach(sec => secSel.innerHTML += `<option value="${sec}">${sec}</option>`);

    // Populate subject selects in modal
    const subSel = document.getElementById('editSubjectField');
    allSubjects.forEach(s => subSel.innerHTML += `<option value="${s.id}">${s.name}</option>`);

    filteredStudents = [...allStudents];
    renderStudentTable();
  }

  // ── Student Table ─────────────────────────────────────────────────────────
  function renderStudentTable() {
    document.getElementById('studentCount').textContent = filteredStudents.length + ' students';
    const tbody = document.getElementById('studentTableBody');
    if (!filteredStudents.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No students found.</td></tr>';
      return;
    }
    tbody.innerHTML = filteredStudents.map(s => {
      const grades  = studentGradesCache[s.id] || [];
      const vals    = grades.map(g => parseFloat(g.final_grade)).filter(v => !isNaN(v));
      const avg     = vals.length ? (vals.reduce((a,b)=>a+b,0)/vals.length).toFixed(1) : null;
      const passed  = vals.filter(v => v >= 75).length;
      const initials = s.full_name.split(' ').map(w=>w[0]||'').slice(0,2).join('').toUpperCase();

      return `<tr class="student-row" data-sid="${s.id}" onclick="openGradeMatrix(${s.id})">
        <td><div class="student-avatar">${initials}</div></td>
        <td><strong>${s.full_name}</strong></td>
        <td><code style="font-size:.78rem">${s.lrn||'—'}</code></td>
        <td>${s.section_name||'—'}</td>
        <td class="text-center avg-cell">${avg !== null ? gradeCell(parseFloat(avg)) : '<span class="text-muted">—</span>'}</td>
        <td class="text-center pass-cell">
          ${vals.length
            ? `<span class="badge ${passed===vals.length?'bg-success':'bg-warning text-dark'}">${passed}/${vals.length}</span>`
            : '<span class="text-muted" style="font-size:.8rem">No grades</span>'}
        </td>
        <td class="text-center rec-cell"><span class="badge bg-secondary">${grades.length} records</span></td>
        <td class="text-center">
          <button class="btn btn-primary btn-sm" onclick="event.stopPropagation();openGradeMatrix(${s.id})">
            <i class="fas fa-table me-1"></i>Manage
          </button>
        </td>
      </tr>`;
    }).join('');
  }

  function filterStudents() {
    const q   = document.getElementById('searchInput').value.toLowerCase();
    const sec = document.getElementById('filterSection').value;
    filteredStudents = allStudents.filter(s =>
      (!q   || s.full_name.toLowerCase().includes(q) || (s.lrn||'').includes(q)) &&
      (!sec || s.section_name === sec)
    );
    renderStudentTable();
  }

  function clearFilters() {
    document.getElementById('searchInput').value   = '';
    document.getElementById('filterSection').value = '';
    filteredStudents = [...allStudents];
    renderStudentTable();
  }

  // ── Grade Matrix Modal ────────────────────────────────────────────────────
  function openGradeMatrix(studentId) {
    selectedStudentId = studentId;
    quarterView = 0;
    // Reset quarter tab highlight
    document.querySelectorAll('#quarterTabs .nav-link').forEach((btn,i) => btn.classList.toggle('active', i===0));

    const student = allStudents.find(s => s.id === studentId) || {};
    document.getElementById('matrixStudentName').textContent = student.full_name || '—';
    document.getElementById('matrixStudentMeta').textContent =
      `LRN: ${student.lrn||'—'} · Section: ${student.section_name||'—'}`;

    renderMatrix();
    new bootstrap.Modal(document.getElementById('gradeMatrixModal')).show();
  }

  function setQuarterView(q, btn) {
    quarterView = q;
    document.querySelectorAll('#quarterTabs .nav-link').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    renderMatrix();
  }

  function renderMatrix() {
    const grades  = studentGradesCache[selectedStudentId] || [];
    const qLabels = {1:'Q1',2:'Q2',3:'Q3',4:'Q4'};
    const quarters = quarterView === 0 ? [1,2,3,4] : [quarterView];

    // ── Stats strip ──
    const vals   = grades.map(g => parseFloat(g.final_grade)).filter(v => !isNaN(v));
    const avg    = vals.length ? (vals.reduce((a,b)=>a+b,0)/vals.length).toFixed(2) : '—';
    const passed = vals.filter(v => v >= 75).length;
    document.getElementById('matrixStats').innerHTML = `
      <span><i class="fas fa-star me-1 text-warning"></i><strong>Avg:</strong> ${avg !== '—' ? gradeCell(parseFloat(avg)) : '—'}</span>
      <span><i class="fas fa-check-circle me-1 text-success"></i><strong>Passed:</strong> ${vals.length ? passed+'/'+vals.length : '—'}</span>
      <span><i class="fas fa-book me-1 text-primary"></i><strong>Records:</strong> ${grades.length}</span>`;

    // ── Table head ──
    document.getElementById('gradeMatrixHead').innerHTML = `<tr>
      <th class="text-start" style="min-width:140px">Subject</th>
      ${quarters.map(q=>`<th style="min-width:140px">${qLabels[q]} Quarter</th>`).join('')}
      ${quarterView===0 ? '<th style="min-width:90px">Average</th>' : ''}
    </tr>`;

    // ── Build grade map: subject_id → { quarter → grade row } ──
    const gradeMap = {};
    grades.forEach(g => {
      if (!gradeMap[g.subject_id]) gradeMap[g.subject_id] = {};
      gradeMap[g.subject_id][g.quarter] = g;
    });

    // ── Table body ──
    const tbody = document.getElementById('gradeMatrixBody');
    if (!allSubjects.length) { tbody.innerHTML='<tr><td colspan="6" class="text-center text-muted py-3">No subjects found.</td></tr>'; return; }

    tbody.innerHTML = allSubjects.map(sub => {
      const subGrades = gradeMap[sub.id] || {};
      const qCells = quarters.map(q => {
        const g = subGrades[q];
        if (g) {
          const fg = parseFloat(g.final_grade);
          return `<td>
            <div class="cell-grade flex-column gap-1 d-inline-flex align-items-center">
              <div>${gradeCell(fg)}</div>
              <div style="font-size:.68rem;color:#64748b">
                WW:${g.written_works} PT:${g.performance_tasks} QE:${g.quarterly_exam}
              </div>
              <div class="d-flex gap-1">
                <button class="btn-cell edit" title="Edit" onclick="openEditGrade(${g.id})"><i class="fas fa-edit"></i> Edit</button>
                <button class="btn-cell del"  title="Delete" onclick="deleteGrade(${g.id})"><i class="fas fa-trash"></i> Del</button>
              </div>
            </div>
          </td>`;
        }
        return `<td>
          <button class="btn btn-outline-secondary btn-sm" style="font-size:.72rem;padding:2px 8px"
            onclick="openAddGradeModal(${sub.id}, ${q})">
            <i class="fas fa-plus me-1"></i>Add
          </button>
        </td>`;
      });

      // Per-subject average (all quarters mode only)
      let avgCell = '';
      if (quarterView === 0) {
        const sVals = Object.values(subGrades).map(g => parseFloat(g.final_grade)).filter(v => !isNaN(v));
        avgCell = `<td>${sVals.length ? gradeCell(+(sVals.reduce((a,b)=>a+b,0)/sVals.length).toFixed(2)) : '<span class="cell-empty">—</span>'}</td>`;
      }

      return `<tr>
        <td class="subject-col"><i class="fas fa-book-open me-2 text-primary" style="font-size:.75rem"></i>${sub.name}</td>
        ${qCells.join('')}
        ${avgCell}
      </tr>`;
    }).join('');
  }

  // ── Add / Edit Grade ──────────────────────────────────────────────────────
  function openAddGradeModal(subjectId = null, quarter = null) {
    document.getElementById('editGradeId').value  = '';
    document.getElementById('editGradeTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Add Grade';
    document.getElementById('editSubjectField').value   = subjectId || allSubjects[0]?.id || '';
    document.getElementById('editQuarterField').value   = quarter   || 1;
    document.getElementById('editWW').value = '';
    document.getElementById('editPT').value = '';
    document.getElementById('editQE').value = '';
    document.getElementById('editPreviewBox').style.display = 'none';

    // Pre-select subject/quarter if opened from a cell
    if (subjectId) document.getElementById('editSubjectField').value = subjectId;
    if (quarter)   document.getElementById('editQuarterField').value = quarter;

    new bootstrap.Modal(document.getElementById('editGradeModal')).show();
  }

  function openEditGrade(gradeId) {
    const allStudentGrades = studentGradesCache[selectedStudentId] || [];
    const g = allStudentGrades.find(gr => gr.id == gradeId);
    if (!g) return;

    document.getElementById('editGradeId').value      = g.id;
    document.getElementById('editGradeTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Grade';
    document.getElementById('editSubjectField').value  = g.subject_id;
    document.getElementById('editQuarterField').value  = g.quarter;
    document.getElementById('editWW').value            = g.written_works;
    document.getElementById('editPT').value            = g.performance_tasks;
    document.getElementById('editQE').value            = g.quarterly_exam;
    updateEditPreview();
    new bootstrap.Modal(document.getElementById('editGradeModal')).show();
  }

  function updateEditPreview() {
    const ww = parseFloat(document.getElementById('editWW').value);
    const pt = parseFloat(document.getElementById('editPT').value);
    const qe = parseFloat(document.getElementById('editQE').value);
    const box = document.getElementById('editPreviewBox');
    if (!isNaN(ww) && !isNaN(pt) && !isNaN(qe)) {
      const fg = Math.round((ww*0.2 + pt*0.6 + qe*0.2)*100)/100;
      box.style.display = 'block';
      document.getElementById('editPreviewContent').innerHTML =
        gradeCell(fg) + ' &nbsp; ' + getGradeBadge(fg) +
        `&nbsp;<span style="font-size:.78rem;color:#64748b">${getGradeDesc(fg)}</span>`;
    } else {
      box.style.display = 'none';
    }
  }

  async function saveGrade() {
    const subId   = document.getElementById('editSubjectField').value;
    const quarter = document.getElementById('editQuarterField').value;
    const ww      = document.getElementById('editWW').value;
    const pt      = document.getElementById('editPT').value;
    const qe      = document.getElementById('editQE').value;

    if (!subId || ww==='' || pt==='' || qe==='') {
      showToast('Please fill in all grade fields.', 'error'); return;
    }

    const body = new FormData();
    body.append('action',            'save');
    body.append('student_id',        selectedStudentId);
    body.append('subject_id',        subId);
    body.append('quarter',           quarter);
    body.append('written_works',     ww);
    body.append('performance_tasks', pt);
    body.append('quarterly_exam',    qe);

    try {
      const res  = await fetch('../../api/grades.php', {method:'POST', body});
      const data = await res.json();
      if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('editGradeModal')).hide();
        showToast('Grade saved!', 'success');
        await refreshStudentGrades(selectedStudentId);
        renderMatrix();
        updateStudentRow(selectedStudentId);
      } else {
        showToast(data.message || 'Error saving grade.', 'error');
      }
    } catch(e) { showToast('Server error.', 'error'); }
  }

  async function deleteGrade(gradeId) {
    if (!confirm('Delete this grade record?')) return;
    const body = new FormData();
    body.append('action', 'delete');
    body.append('id', gradeId);
    try {
      const res  = await fetch('../../api/grades.php', {method:'POST', body});
      const data = await res.json();
      if (data.success) {
        showToast('Grade deleted.', 'success');
        await refreshStudentGrades(selectedStudentId);
        renderMatrix();
        updateStudentRow(selectedStudentId);
      } else {
        showToast(data.message || 'Error.', 'error');
      }
    } catch(e) { showToast('Server error.', 'error'); }
  }

  // ── Helpers ───────────────────────────────────────────────────────────────
  async function refreshStudentGrades(studentId) {
    const res  = await fetch('../../api/grades.php?action=list&student_id=' + studentId);
    const data = await res.json();
    studentGradesCache[studentId] = data.data || [];
    // Rebuild allGrades (replace records for this student)
    allGrades = allGrades.filter(g => g.student_id != studentId).concat(studentGradesCache[studentId]);
  }

  function updateStudentRow(studentId) {
    // Re-render just this student's row without full table reload
    const grades  = studentGradesCache[studentId] || [];
    const vals    = grades.map(g => parseFloat(g.final_grade)).filter(v => !isNaN(v));
    const avg     = vals.length ? (vals.reduce((a,b)=>a+b,0)/vals.length).toFixed(1) : null;
    const passed  = vals.filter(v => v >= 75).length;
    const row = document.querySelector(`tr[data-sid="${studentId}"]`);
    if (row) {
      row.querySelector('.avg-cell').innerHTML  = avg !== null ? gradeCell(parseFloat(avg)) : '<span class="text-muted">—</span>';
      row.querySelector('.pass-cell').innerHTML = vals.length
        ? `<span class="badge ${passed===vals.length?'bg-success':'bg-warning text-dark'}">${passed}/${vals.length}</span>`
        : '<span class="text-muted" style="font-size:.8rem">No grades</span>';
      row.querySelector('.rec-cell').innerHTML  = `<span class="badge bg-secondary">${grades.length} records</span>`;
    } else {
      renderStudentTable(); // fallback: full re-render
    }
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
