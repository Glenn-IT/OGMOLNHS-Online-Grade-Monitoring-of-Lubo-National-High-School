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
  <link rel="stylesheet" href="../../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../../assets/css/style.css") ?>"/>
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

      <!-- Filter bar -->
      <div class="content-card mb-3">
        <div class="card-body-custom">
          <div class="row g-2 align-items-center">
            <div class="col-md-5">
              <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search section, student name or LRN…" oninput="filterSections()"/>
              </div>
            </div>
            <div class="col-md-3">
              <select id="filterGrade" class="form-select form-select-sm" onchange="filterSections()">
                <option value="">All Grade Levels</option>
                <?php for($g=7;$g<=12;$g++) echo "<option value='$g'>Grade $g</option>"; ?>
              </select>
            </div>
            <div class="col-md-2">
              <button class="btn btn-outline-secondary btn-sm w-100" onclick="clearFilters()">
                <i class="fas fa-undo me-1"></i>Clear
              </button>
            </div>
            <div class="col-md-2 text-end">
              <span class="badge bg-primary" id="sectionCount" style="font-size:.8rem">—</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Summary strip -->
      <div class="row g-3 mb-3" id="summaryStrip"></div>

      <!-- Grade / Section grid -->
      <div class="content-card mb-3">
        <div class="card-header-custom">
          <span class="card-title"><i class="fas fa-layer-group me-2 text-primary"></i>Sections</span>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr><th>Grade Level</th><th>Section</th><th>Students</th><th>Actions</th></tr>
            </thead>
            <tbody id="sectionsTableBody">
              <tr><td colspan="4" class="text-center py-4">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Unassigned students -->
      <div class="content-card" id="unassignedCard" style="display:none">
        <div class="card-header-custom">
          <span class="card-title"><i class="fas fa-user-slash me-2 text-danger"></i>Unassigned Students</span>
        </div>
        <div class="p-3" id="unassignedList"></div>
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
          <input type="text" id="sectionName" class="form-control" placeholder="e.g. Sampaguita"/>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Grade Level</label>
          <select id="sectionGrade" class="form-select">
            <?php for($g=7;$g<=12;$g++) echo "<option value='$g'>Grade $g</option>"; ?>
          </select>
        </div>
        <div class="alert alert-info py-2 mb-0" style="font-size:.82rem">
          <i class="fas fa-info-circle me-1"></i>
          A section name can only be used once per grade level in the active school year.
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="saveSection()"><i class="fas fa-save me-1"></i>Save Section</button>
      </div>
    </div>
  </div>
</div>

<!-- ── View Section (student roster) Modal ────────────────────────────────── -->
<div class="modal fade" id="viewSectionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header" style="background:#0c1326;color:#fff">
        <h5 class="modal-title" id="viewSectionTitle"><i class="fas fa-users me-2"></i>Section Students</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="viewSectionBody"></div>
      <div class="modal-footer">
        <button class="btn btn-outline-primary btn-sm" onclick="openAssignModalFromView()">
          <i class="fas fa-user-plus me-1"></i>Assign Existing Student
        </button>
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
        <div class="mb-2">
          <label class="form-label fw-semibold">Search Students</label>
          <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="assignSearchInput" placeholder="Search by name or LRN…" oninput="renderAssignList()"/>
          </div>
        </div>
        <div id="assignStudentList" style="max-height:260px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px"></div>
        <div class="d-flex justify-content-between align-items-center mt-2">
          <small class="text-muted"><span id="assignSelectedCount">0</span> selected</small>
        </div>
        <div class="alert alert-info py-2 mt-2" style="font-size:.82rem">
          <i class="fas fa-info-circle me-1"></i>
          If a student is already in another section, they will be moved here.
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="assignStudent()"><i class="fas fa-user-plus me-1"></i>Assign Selected</button>
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
  let assignSelectedIds = new Set(), assignCurrentSectionId = null;
  let currentViewSectionId = null;

  // ── Boot ───────────────────────────────────────────────────────────────────
  async function init() {
    await loadData();
    renderSummary();
    renderGrid();
    renderUnassigned();
  }

  async function loadData() {
    const [secRes, stuRes] = await Promise.all([
      fetch('../../api/sections.php?action=list'),
      fetch('../../api/students.php?action=list'),
    ]);
    const secData = await secRes.json();
    const stuData = await stuRes.json();
    sectionsData = secData.data || [];
    allStudents  = stuData.data || [];

    // Load students per section
    sectionStudentsCache = {};
    await Promise.all(sectionsData.map(s => loadSectionStudents(s.id)));
  }

  async function loadSectionStudents(sectionId) {
    const res  = await fetch('../../api/sections.php?action=students&section_id=' + sectionId);
    const data = await res.json();
    sectionStudentsCache[sectionId] = data.data || [];
  }

  // ── Helpers ────────────────────────────────────────────────────────────────
  function esc(str) {
    return String(str ?? '').replace(/[&<>"']/g,
      c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }
  // For values embedded inside an onclick="..." attribute
  function escAttr(str) {
    return esc(str).replace(/\\/g, '\\\\');
  }
  function initials(name) {
    return String(name||'').split(' ').map(w=>w[0]||'').slice(0,2).join('').toUpperCase();
  }
  function plural(n, word) {
    return `${n} ${word}${n === 1 ? '' : 's'}`;
  }

  /** Students with no enrollment in the active school year. */
  function unassignedStudents() {
    const enrolled = new Set();
    Object.values(sectionStudentsCache).forEach(list => list.forEach(s => enrolled.add(s.id)));
    return allStudents.filter(s => !enrolled.has(s.id));
  }

  // ── Filtering (client-side) ────────────────────────────────────────────────
  function currentFilters() {
    return {
      q:     document.getElementById('searchInput').value.trim().toLowerCase(),
      grade: document.getElementById('filterGrade').value,
    };
  }

  function studentMatches(s, q) {
    if (!q) return true;
    return String(s.full_name||'').toLowerCase().includes(q)
        || String(s.lrn||'').toLowerCase().includes(q);
  }

  /** Sections passing the current filters — matched by section name, grade, or a student inside it. */
  function visibleSections() {
    const { q, grade } = currentFilters();

    return sectionsData.filter(sec => {
      if (grade && String(sec.grade_level) !== grade) return false;
      if (!q) return true;
      if (String(sec.name||'').toLowerCase().includes(q)) return true;
      return (sectionStudentsCache[sec.id] || []).some(s => studentMatches(s, q));
    });
  }

  // ── Render ─────────────────────────────────────────────────────────────────
  function renderSummary() {
    const totalEnrolled = Object.values(sectionStudentsCache).reduce((a,s)=>a+s.length,0);
    const unassigned    = unassignedStudents().length;
    document.getElementById('summaryStrip').innerHTML = `
      <div class="col-6 col-md-3"><div class="stat-card text-center">
        <div class="stat-value" style="color:#0c1326">${sectionsData.length}</div>
        <div class="stat-label">Total Sections</div></div></div>
      <div class="col-6 col-md-3"><div class="stat-card text-center">
        <div class="stat-value" style="color:var(--success)">${totalEnrolled}</div>
        <div class="stat-label">Enrolled Students</div></div></div>
      <div class="col-6 col-md-3"><div class="stat-card text-center">
        <div class="stat-value" style="color:var(--warning)">${allStudents.length}</div>
        <div class="stat-label">Total Students</div></div></div>
      <div class="col-6 col-md-3"><div class="stat-card text-center">
        <div class="stat-value" style="color:var(--danger)">${unassigned}</div>
        <div class="stat-label">Unassigned</div></div></div>`;
  }

  /** Grid — one row per section, sorted by grade then name. */
  function renderGrid() {
    const tbody = document.getElementById('sectionsTableBody');
    const visible = visibleSections()
      .slice()
      .sort((a, b) => a.grade_level - b.grade_level || a.name.localeCompare(b.name));

    document.getElementById('sectionCount').textContent = plural(visible.length, 'section');

    if (!visible.length) {
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-4">
        <i class="fas fa-inbox me-2"></i>${sectionsData.length ? 'No sections match your filters.' : 'No sections yet. Click New Section to create one.'}
      </td></tr>`;
      return;
    }

    tbody.innerHTML = visible.map(sec => {
      const count = (sectionStudentsCache[sec.id] || []).length;
      return `<tr>
        <td>Grade ${sec.grade_level}</td>
        <td>${esc(sec.name)}</td>
        <td><span class="badge bg-primary">${count}</span></td>
        <td>
          <button class="btn-sm-custom btn-view me-1" onclick="viewSection(${sec.id})" title="View students"><i class="fas fa-eye"></i></button>
          <button class="btn-sm-custom btn-edit me-1" onclick="openSectionModal(${sec.id})" title="Edit section"><i class="fas fa-edit"></i></button>
          <button class="btn-sm-custom btn-delete" onclick="deleteSection(${sec.id})" title="Delete section"><i class="fas fa-trash"></i></button>
        </td>
      </tr>`;
    }).join('');
  }

  /** Read-only card listing students with no enrollment this school year. */
  function renderUnassigned() {
    const list = unassignedStudents();
    const card = document.getElementById('unassignedCard');
    if (!list.length) { card.style.display = 'none'; return; }
    card.style.display = '';
    document.getElementById('unassignedList').innerHTML = list.map(s => `
      <div class="student-item">
        <div class="s-avatar" style="background:#94a3b8">${esc(initials(s.full_name))}</div>
        <div class="flex-grow-1">
          <div style="font-size:.85rem;font-weight:600">${esc(s.full_name)}</div>
          <div style="font-size:.72rem;color:#94a3b8">${esc(s.lrn || 'No LRN')}</div>
        </div>
        <span class="badge bg-light text-muted">Not in a section</span>
      </div>`).join('');
  }

  function filterSections() { renderGrid(); }

  function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterGrade').value = '';
    renderGrid();
  }

  // ── View Section (student roster) ────────────────────────────────────────
  function viewSection(id) {
    currentViewSectionId = id;
    renderViewModal();
    new bootstrap.Modal(document.getElementById('viewSectionModal')).show();
  }

  function renderViewModal() {
    const sec = sectionsData.find(s => s.id === currentViewSectionId);
    if (!sec) return;
    const students = sectionStudentsCache[sec.id] || [];

    document.getElementById('viewSectionTitle').innerHTML =
      `<i class="fas fa-users me-2"></i>Grade ${sec.grade_level} – ${esc(sec.name)}`;

    document.getElementById('viewSectionBody').innerHTML = students.length
      ? students.map(s => `<div class="student-item">
          <div class="s-avatar">${esc(initials(s.full_name))}</div>
          <div class="flex-grow-1">
            <div style="font-size:.85rem;font-weight:600">${esc(s.full_name)}</div>
            <div style="font-size:.72rem;color:#94a3b8">${esc(s.lrn || 'No LRN')} · ${esc(s.email||'')}</div>
          </div>
          <button class="btn btn-outline-danger btn-sm" style="font-size:.7rem;padding:2px 8px"
            onclick="removeStudent(${s.enrollment_id}, ${sec.id}, '${escAttr(s.full_name)}')">
            <i class="fas fa-times"></i> Remove
          </button>
        </div>`).join('')
      : `<div class="empty-section"><i class="fas fa-user-slash me-1"></i>No students assigned yet.</div>`;
  }

  function openAssignModalFromView() {
    const sec = sectionsData.find(s => s.id === currentViewSectionId);
    if (!sec) return;
    openAssignModal(sec.id, sec.name);
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
    assignSelectedIds = new Set();
    assignCurrentSectionId = sectionId;
    document.getElementById('assignSectionId').value      = sectionId;
    document.getElementById('assignSectionName').textContent = sectionName;
    document.getElementById('assignSearchInput').value    = '';
    renderAssignList();
    new bootstrap.Modal(document.getElementById('assignModal')).show();
  }

  function renderAssignList() {
    const query = document.getElementById('assignSearchInput').value.trim().toLowerCase();
    const alreadyIn = new Set((sectionStudentsCache[assignCurrentSectionId] || []).map(s => s.id));

    const matches = allStudents.filter(s => {
      if (alreadyIn.has(s.id)) return false;
      return studentMatches(s, query);
    });

    const list = document.getElementById('assignStudentList');
    list.innerHTML = matches.length ? matches.map(s => `
      <div class="student-item">
        <input type="checkbox" class="form-check-input" id="assignChk${s.id}"
          ${assignSelectedIds.has(s.id) ? 'checked' : ''}
          onchange="toggleAssignStudent(${s.id}, this.checked)"/>
        <label class="flex-grow-1 mb-0" for="assignChk${s.id}" style="cursor:pointer">
          <div style="font-size:.85rem;font-weight:600">${esc(s.full_name)}</div>
          <div style="font-size:.72rem;color:#94a3b8">${esc(s.lrn || 'No LRN')}${
            s.section_name ? ` · currently in ${esc(s.section_name)}` : ''}</div>
        </label>
      </div>`).join('')
      : `<div class="empty-section"><i class="fas fa-user-slash me-1"></i>No matching students.</div>`;

    document.getElementById('assignSelectedCount').textContent = assignSelectedIds.size;
  }

  function toggleAssignStudent(id, checked) {
    if (checked) assignSelectedIds.add(id);
    else assignSelectedIds.delete(id);
    document.getElementById('assignSelectedCount').textContent = assignSelectedIds.size;
  }

  async function assignStudent() {
    const sectionId = document.getElementById('assignSectionId').value;
    if (!assignSelectedIds.size) { showToast('Please select at least one student.', 'error'); return; }

    let okCount = 0, failCount = 0;
    for (const studentId of assignSelectedIds) {
      const body = new FormData();
      body.append('action',     'enroll');
      body.append('section_id', sectionId);
      body.append('student_id', studentId);
      try {
        const res  = await fetch('../../api/sections.php', {method:'POST', body});
        const data = await res.json();
        if (data.success) okCount++; else failCount++;
      } catch(e) { failCount++; }
    }

    if (okCount) {
      bootstrap.Modal.getInstance(document.getElementById('assignModal')).hide();
      showToast(`${plural(okCount, 'student')} assigned.${failCount?` ${failCount} failed.`:''}`,
        failCount ? 'error' : 'success');
      await refresh();
    } else {
      showToast('Error assigning students.', 'error');
    }
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
    await loadData();
    renderSummary();
    renderGrid();
    renderUnassigned();
    if (currentViewSectionId) renderViewModal();
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
