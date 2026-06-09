<?php
require_once '../../config/session.php';
requireAdmin();
$adminActivePage = 'manage-sections';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Manage Sections – OGMS Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../../assets/css/style.css"/>
  <style>
    .section-card { border-radius:12px;border:1px solid #e2e8f0;background:#fff;padding:0;overflow:hidden;transition:box-shadow .2s; }
    .section-card:hover { box-shadow:0 4px 20px rgba(0,0,0,.08); }
    .section-card-header { background:#0c1326;color:#fff;padding:1rem 1.25rem;display:flex;justify-content:space-between;align-items:center; }
    .section-card-header h6 { margin:0;font-size:1rem;font-weight:700; }
    .section-card-header small { opacity:.7;font-size:.75rem; }
    .section-meta { display:flex;gap:1rem;padding:.75rem 1.25rem;border-bottom:1px solid #f1f5f9;font-size:.82rem;color:#64748b; }
    .student-item { display:flex;align-items:center;gap:.75rem;padding:.6rem 1.25rem;border-bottom:1px solid #f8fafc; }
    .student-item:last-child { border-bottom:none; }
    .student-item:hover { background:#f8fafc; }
    .s-avatar { width:32px;height:32px;border-radius:50%;background:#0c1326;display:flex;align-items:center;
      justify-content:center;color:#fff;font-size:.72rem;font-weight:700;flex-shrink:0; }
    .section-card-footer { padding:.75rem 1.25rem;background:#f8fafc;display:flex;gap:.5rem; }
    .empty-section { padding:2rem 1.25rem;text-align:center;color:#94a3b8;font-size:.85rem; }
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
          <div class="topbar-title">Manage Sections</div>
          <div class="topbar-subtitle">Create sections and assign students</div>
        </div>
      </div>
      <div class="topbar-right">
        <button class="btn btn-primary btn-sm" onclick="openSectionModal()">
          <i class="fas fa-plus me-1"></i>New Section
        </button>
      </div>
    </header>

    <main class="page-content fade-in">

      <!-- Summary strip -->
      <div class="row g-3 mb-3" id="summaryStrip"></div>

      <!-- Section cards grid -->
      <div class="row g-3" id="sectionsGrid">
        <div class="col-12 text-center py-5 text-muted">Loading sections…</div>
      </div>

    </main>
  </div>
</div>

<!-- ── Add / Edit Section Modal ───────────────────────────────────────────── -->
<div class="modal fade" id="sectionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background:#0c1326;color:#fff">
        <h5 class="modal-title" id="sectionModalTitle"><i class="fas fa-layer-group me-2"></i>New Section</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="sectionId"/>
        <div class="mb-3">
          <label class="form-label fw-semibold">Section Name</label>
          <input type="text" id="sectionName" class="form-control" placeholder="e.g. Grade 10 – Sampaguita"/>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Grade Level</label>
          <select id="sectionGrade" class="form-select">
            <?php for($g=7;$g<=12;$g++) echo "<option value='$g'>Grade $g</option>"; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="saveSection()"><i class="fas fa-save me-1"></i>Save Section</button>
      </div>
    </div>
  </div>
</div>

<!-- ── Assign Student Modal ───────────────────────────────────────────────── -->
<div class="modal fade" id="assignModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background:#0c1326;color:#fff">
        <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Assign Student to <span id="assignSectionName">—</span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="assignSectionId"/>
        <div class="mb-3">
          <label class="form-label fw-semibold">Select Student</label>
          <select id="assignStudentSelect" class="form-select">
            <option value="">— Choose a student —</option>
          </select>
        </div>
        <div class="alert alert-info py-2" style="font-size:.82rem">
          <i class="fas fa-info-circle me-1"></i>
          If the student is already in another section, they will be moved here.
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="assignStudent()"><i class="fas fa-user-plus me-1"></i>Assign</button>
      </div>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
  let sectionsData = [], allStudents = [], sectionStudentsCache = {};

  // ── Boot ───────────────────────────────────────────────────────────────────
  async function init() {
    const [secRes, stuRes] = await Promise.all([
      fetch('../../api/sections.php?action=list'),
      fetch('../../api/students.php?action=list'),
    ]);
    const secData = await secRes.json();
    const stuData = await stuRes.json();
    sectionsData = secData.data || [];
    allStudents  = stuData.data || [];

    // Populate student select in assign modal
    const sel = document.getElementById('assignStudentSelect');
    allStudents.forEach(s =>
      sel.innerHTML += `<option value="${s.id}">${s.full_name}${s.lrn?' ('+s.lrn+')':''}</option>`
    );

    // Load students per section
    await Promise.all(sectionsData.map(s => loadSectionStudents(s.id)));

    renderSummary();
    renderSections();
  }

  async function loadSectionStudents(sectionId) {
    const res  = await fetch('../../api/sections.php?action=students&section_id=' + sectionId);
    const data = await res.json();
    sectionStudentsCache[sectionId] = data.data || [];
  }

  // ── Render ─────────────────────────────────────────────────────────────────
  function renderSummary() {
    const totalStudents = Object.values(sectionStudentsCache).reduce((a,s)=>a+s.length,0);
    const unassigned    = allStudents.length - totalStudents;
    document.getElementById('summaryStrip').innerHTML = `
      <div class="col-6 col-md-3"><div class="stat-card text-center">
        <div class="stat-value" style="color:#0c1326">${sectionsData.length}</div>
        <div class="stat-label">Total Sections</div></div></div>
      <div class="col-6 col-md-3"><div class="stat-card text-center">
        <div class="stat-value" style="color:var(--success)">${totalStudents}</div>
        <div class="stat-label">Enrolled Students</div></div></div>
      <div class="col-6 col-md-3"><div class="stat-card text-center">
        <div class="stat-value" style="color:var(--warning)">${allStudents.length}</div>
        <div class="stat-label">Total Students</div></div></div>
      <div class="col-6 col-md-3"><div class="stat-card text-center">
        <div class="stat-value" style="color:var(--danger)">${unassigned}</div>
        <div class="stat-label">Unassigned</div></div></div>`;
  }

  function renderSections() {
    const grid = document.getElementById('sectionsGrid');
    if (!sectionsData.length) {
      grid.innerHTML = `<div class="col-12">
        <div class="empty-state"><i class="fas fa-layer-group"></i><p>No sections yet. Click <strong>New Section</strong> to create one.</p></div>
      </div>`;
      return;
    }

    grid.innerHTML = sectionsData.map(sec => {
      const students = sectionStudentsCache[sec.id] || [];
      const studentRows = students.length
        ? students.map(s => {
            const initials = s.full_name.split(' ').map(w=>w[0]||'').slice(0,2).join('').toUpperCase();
            return `<div class="student-item">
              <div class="s-avatar">${initials}</div>
              <div class="flex-grow-1">
                <div style="font-size:.85rem;font-weight:600">${s.full_name}</div>
                <div style="font-size:.72rem;color:#94a3b8">${s.lrn||'No LRN'}</div>
              </div>
              <button class="btn btn-outline-danger btn-sm" style="font-size:.7rem;padding:2px 8px"
                onclick="removeStudent(${s.enrollment_id}, ${sec.id}, '${s.full_name.replace(/'/g,"\\'")}')">
                <i class="fas fa-times"></i> Remove
              </button>
            </div>`;
          }).join('')
        : `<div class="empty-section"><i class="fas fa-user-slash me-1"></i>No students assigned yet.</div>`;

      return `<div class="col-md-6 col-xl-4">
        <div class="section-card">
          <div class="section-card-header">
            <div>
              <h6>${sec.name}</h6>
              <small>Grade ${sec.grade_level} · ${sec.school_year||'—'}</small>
            </div>
            <span class="badge" style="background:rgba(255,255,255,.2);font-size:.75rem">
              ${students.length} student${students.length!==1?'s':''}
            </span>
          </div>
          <div class="section-meta">
            <span><i class="fas fa-users me-1"></i>${students.length} enrolled</span>
            <span><i class="fas fa-graduation-cap me-1"></i>Grade ${sec.grade_level}</span>
          </div>
          <div style="max-height:280px;overflow-y:auto">${studentRows}</div>
          <div class="section-card-footer">
            <button class="btn btn-primary btn-sm flex-grow-1" onclick="openAssignModal(${sec.id},'${sec.name.replace(/'/g,"\\'")}')">
              <i class="fas fa-user-plus me-1"></i>Assign Student
            </button>
            <button class="btn btn-outline-secondary btn-sm" onclick="openSectionModal(${sec.id})"
              title="Edit section"><i class="fas fa-edit"></i></button>
            <button class="btn btn-outline-danger btn-sm" onclick="deleteSection(${sec.id})"
              title="Delete section"><i class="fas fa-trash"></i></button>
          </div>
        </div>
      </div>`;
    }).join('');
  }

  // ── Section CRUD ──────────────────────────────────────────────────────────
  function openSectionModal(id = null) {
    const sec = id ? sectionsData.find(s => s.id === id) : null;
    document.getElementById('sectionId').value     = sec ? sec.id   : '';
    document.getElementById('sectionName').value   = sec ? sec.name : '';
    document.getElementById('sectionGrade').value  = sec ? sec.grade_level : 7;
    document.getElementById('sectionModalTitle').innerHTML =
      `<i class="fas fa-layer-group me-2"></i>${sec ? 'Edit Section' : 'New Section'}`;
    new bootstrap.Modal(document.getElementById('sectionModal')).show();
  }

  async function saveSection() {
    const id    = document.getElementById('sectionId').value;
    const name  = document.getElementById('sectionName').value.trim();
    const grade = document.getElementById('sectionGrade').value;
    if (!name) { showToast('Section name is required.', 'error'); return; }

    const body = new FormData();
    body.append('action',      'save');
    body.append('name',        name);
    body.append('grade_level', grade);
    if (id) body.append('id', id);

    try {
      const res  = await fetch('../../api/sections.php', {method:'POST', body});
      const data = await res.json();
      if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('sectionModal')).hide();
        showToast(data.message, 'success');
        await refresh();
      } else {
        showToast(data.message || 'Error saving section.', 'error');
      }
    } catch(e) { showToast('Server error.', 'error'); }
  }

  async function deleteSection(id) {
    const sec = sectionsData.find(s => s.id === id);
    if (!confirm(`Delete section "${sec?.name}"? This cannot be undone.`)) return;
    const body = new FormData();
    body.append('action', 'delete');
    body.append('id', id);
    try {
      const res  = await fetch('../../api/sections.php', {method:'POST', body});
      const data = await res.json();
      if (data.success) { showToast(data.message, 'success'); await refresh(); }
      else showToast(data.message || 'Cannot delete section.', 'error');
    } catch(e) { showToast('Server error.', 'error'); }
  }

  // ── Enrollment ────────────────────────────────────────────────────────────
  function openAssignModal(sectionId, sectionName) {
    document.getElementById('assignSectionId').value      = sectionId;
    document.getElementById('assignSectionName').textContent = sectionName;
    document.getElementById('assignStudentSelect').value  = '';
    new bootstrap.Modal(document.getElementById('assignModal')).show();
  }

  async function assignStudent() {
    const sectionId = document.getElementById('assignSectionId').value;
    const studentId = document.getElementById('assignStudentSelect').value;
    if (!studentId) { showToast('Please select a student.', 'error'); return; }

    const body = new FormData();
    body.append('action',     'enroll');
    body.append('section_id', sectionId);
    body.append('student_id', studentId);

    try {
      const res  = await fetch('../../api/sections.php', {method:'POST', body});
      const data = await res.json();
      if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('assignModal')).hide();
        showToast(data.message, 'success');
        await refresh();
      } else {
        showToast(data.message || 'Error assigning student.', 'error');
      }
    } catch(e) { showToast('Server error.', 'error'); }
  }

  async function removeStudent(enrollmentId, sectionId, studentName) {
    if (!confirm(`Remove ${studentName} from this section?`)) return;
    const body = new FormData();
    body.append('action',        'unenroll');
    body.append('enrollment_id', enrollmentId);
    try {
      const res  = await fetch('../../api/sections.php', {method:'POST', body});
      const data = await res.json();
      if (data.success) { showToast(data.message, 'success'); await refresh(); }
      else showToast(data.message || 'Error.', 'error');
    } catch(e) { showToast('Server error.', 'error'); }
  }

  // ── Full refresh ──────────────────────────────────────────────────────────
  async function refresh() {
    sectionStudentsCache = {};
    const res   = await fetch('../../api/sections.php?action=list');
    const data  = await res.json();
    sectionsData = data.data || [];
    await Promise.all(sectionsData.map(s => loadSectionStudents(s.id)));
    renderSummary();
    renderSections();
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
