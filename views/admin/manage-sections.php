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

      <!-- Grade → Section → Students drill-down -->
      <div class="content-card">
        <div class="card-header-custom">
          <span class="card-title"><i class="fas fa-layer-group me-2 text-primary"></i>Sections by Grade Level — open a grade, then a section to see its students</span>
        </div>
        <div class="p-3">
          <div class="accordion grade-accordion" id="sectionAccordion">
            <div class="text-center py-4 text-muted">Loading sections…</div>
          </div>
        </div>
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

  // ── Boot ───────────────────────────────────────────────────────────────────
  async function init() {
    await loadData();
    renderSummary();
    renderSections();
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

  /**
   * Returns [{ sec, students }] for sections passing the current filters.
   * A section is kept when its own name matches, or when any of its students
   * match — in which case only the matching students are listed.
   */
  function visibleSections() {
    const { q, grade } = currentFilters();

    return sectionsData.reduce((out, sec) => {
      if (grade && String(sec.grade_level) !== grade) return out;

      const roster     = sectionStudentsCache[sec.id] || [];
      const nameHit    = !q || String(sec.name||'').toLowerCase().includes(q);
      const studentHit = roster.filter(s => studentMatches(s, q));

      if (!q || nameHit) out.push({ sec, students: roster });
      else if (studentHit.length) out.push({ sec, students: studentHit });

      return out;
    }, []);
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

  /** Level 3 — one row per student inside a section. */
  function studentItemHtml(s, sectionId) {
    return `<div class="student-item">
      <div class="s-avatar">${esc(initials(s.full_name))}</div>
      <div class="flex-grow-1">
        <div style="font-size:.85rem;font-weight:600">${esc(s.full_name)}</div>
        <div style="font-size:.72rem;color:#94a3b8">${esc(s.lrn || 'No LRN')}</div>
      </div>
      <button class="btn btn-outline-danger btn-sm" style="font-size:.7rem;padding:2px 8px"
        onclick="removeStudent(${s.enrollment_id}, ${sectionId}, '${escAttr(s.full_name)}')">
        <i class="fas fa-times"></i> Remove
      </button>
    </div>`;
  }

  /** Level 2 — one collapsible panel per section, with its action buttons. */
  function sectionItemHtml(sec, students, expanded) {
    const collapseId = 'sec-collapse-' + sec.id;
    const rows = students.length
      ? students.map(s => studentItemHtml(s, sec.id)).join('')
      : `<div class="empty-section"><i class="fas fa-user-slash me-1"></i>No students assigned yet.</div>`;

    return `<div class="accordion-item">
      <h2 class="accordion-header d-flex align-items-center flex-nowrap">
        <button class="accordion-button${expanded ? '' : ' collapsed'}" type="button"
                data-bs-toggle="collapse" data-bs-target="#${collapseId}">
          <i class="fas fa-chalkboard me-2"></i>${esc(sec.name)}
          <span class="badge bg-secondary ms-2">${plural(students.length, 'student')}</span>
        </button>
        <span class="section-actions">
          <button class="btn btn-primary btn-sm"
            onclick="openAssignModal(${sec.id},'${escAttr(sec.name)}')" title="Assign students">
            <i class="fas fa-user-plus"></i><span class="d-none d-md-inline ms-1">Assign</span>
          </button>
          <button class="btn btn-outline-secondary btn-sm"
            onclick="openSectionModal(${sec.id})" title="Edit section"><i class="fas fa-edit"></i></button>
          <button class="btn btn-outline-danger btn-sm"
            onclick="deleteSection(${sec.id})" title="Delete section"><i class="fas fa-trash"></i></button>
        </span>
      </h2>
      <div id="${collapseId}" class="accordion-collapse collapse${expanded ? ' show' : ''}">
        <div class="accordion-body">${rows}</div>
      </div>
    </div>`;
  }

  /** Level 1 — one collapsible panel per grade level. */
  function renderSections() {
    const container = document.getElementById('sectionAccordion');
    const { q, grade } = currentFilters();
    const isFiltering  = !!(q || grade);

    const visible   = visibleSections();
    const unmatched = q
      ? unassignedStudents().filter(s => studentMatches(s, q))
      : unassignedStudents();

    document.getElementById('sectionCount').textContent =
      plural(visible.length, 'section');

    if (!visible.length && !unmatched.length) {
      container.innerHTML = sectionsData.length
        ? '<div class="text-center text-muted py-4">No sections or students match your filters.</div>'
        : `<div class="empty-state"><i class="fas fa-layer-group"></i>
           <p>No sections yet. Click <strong>New Section</strong> to create one.</p></div>`;
      return;
    }

    // Group the visible sections by grade level
    const grouped = {};
    visible.forEach(({ sec, students }) => {
      const key = sec.grade_level || 'Unassigned';
      (grouped[key] = grouped[key] || []).push({ sec, students });
    });

    const gradeKeys = Object.keys(grouped).sort((a, b) => {
      if (a === 'Unassigned') return 1;
      if (b === 'Unassigned') return -1;
      return Number(a) - Number(b);
    });

    let html = gradeKeys.map(gradeKey => {
      const entries    = grouped[gradeKey];
      const studentSum = entries.reduce((n, e) => n + e.students.length, 0);
      const collapseId = 'grade-collapse-' + String(gradeKey).replace(/\s+/g, '-');
      const label      = gradeKey === 'Unassigned' ? 'Unassigned' : `Grade ${gradeKey}`;

      return `<div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button${isFiltering ? '' : ' collapsed'}" type="button"
                  data-bs-toggle="collapse" data-bs-target="#${collapseId}">
            ${label}
            <span class="badge bg-primary ms-2">${plural(entries.length, 'section')} · ${plural(studentSum, 'student')}</span>
          </button>
        </h2>
        <div id="${collapseId}" class="accordion-collapse collapse${isFiltering ? ' show' : ''}">
          <div class="accordion-body">
            <div class="accordion section-accordion">
              ${entries.map(e => sectionItemHtml(e.sec, e.students, isFiltering)).join('')}
            </div>
          </div>
        </div>
      </div>`;
    }).join('');

    // Trailing panel: students with no section in the active school year
    if (unmatched.length) {
      html += `<div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button${isFiltering ? '' : ' collapsed'}" type="button"
                  data-bs-toggle="collapse" data-bs-target="#grade-collapse-none">
            <i class="fas fa-user-slash me-2"></i>Unassigned Students
            <span class="badge bg-danger ms-2">${plural(unmatched.length, 'student')}</span>
          </button>
        </h2>
        <div id="grade-collapse-none" class="accordion-collapse collapse${isFiltering ? ' show' : ''}">
          <div class="accordion-body p-0">
            ${unmatched.map(s => `<div class="student-item">
              <div class="s-avatar" style="background:#94a3b8">${esc(initials(s.full_name))}</div>
              <div class="flex-grow-1">
                <div style="font-size:.85rem;font-weight:600">${esc(s.full_name)}</div>
                <div style="font-size:.72rem;color:#94a3b8">${esc(s.lrn || 'No LRN')}</div>
              </div>
              <span class="badge bg-light text-muted">Not in a section</span>
            </div>`).join('')}
          </div>
        </div>
      </div>`;
    }

    container.innerHTML = html;
  }

  function filterSections() { renderSections(); }

  function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterGrade').value = '';
    renderSections();
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
    renderSections();
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
