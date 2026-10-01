<?php
// views/admin/manage-teachers.php
require_once '../../config/session.php';
requireAdmin();
$adminActivePage = 'manage-teachers';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Manage Teachers – OGMS Admin</title>
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
          <div class="topbar-title">Manage Teachers</div>
          <div class="topbar-subtitle">Register faculty accounts, update login credentials, and assign curriculum subjects</div>
        </div>
      </div>
      <div class="topbar-right">
        <button class="btn btn-primary btn-sm" onclick="openAddTeacherModal()">
          <i class="fas fa-user-plus me-1"></i>Register Teacher
        </button>
      </div>
    </header>

    <main class="page-content fade-in">
      <div class="content-card mb-3">
        <div class="card-body-custom">
          <div class="row g-2 align-items-end">
            <div class="col-md-6">
              <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search by name, email, phone, subjects…" oninput="filterTeachers()"/>
              </div>
            </div>
            <div class="col-md-3">
              <select id="filterStatus" class="form-select form-select-sm" onchange="filterTeachers()">
                <option value="">All Account Statuses</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
            <div class="col-md-2">
              <button class="btn btn-outline-secondary btn-sm w-100" onclick="clearFilters()">
                <i class="fas fa-undo me-1"></i>Clear
              </button>
            </div>
            <div class="col-md-1 text-end">
              <span class="badge bg-primary" id="teacherCount" style="font-size:0.8rem">—</span>
            </div>
          </div>
        </div>
      </div>

      <div class="content-card">
        <div class="card-header-custom">
          <span class="card-title"><i class="fas fa-chalkboard-teacher me-2 text-primary"></i>Faculty &amp; Teacher Accounts</span>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>#</th>
                <th>Teacher / Faculty Member</th>
                <th>Login Email</th>
                <th>Contact Phone</th>
                <th>Teaching Subject(s)</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="teachersTableBody">
              <tr><td colspan="7" class="text-center py-4">Loading teacher records…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Register / Edit Teacher Modal -->
<div class="modal fade" id="teacherModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="teacherModalTitle"><i class="fas fa-user-plus me-2"></i>Register Teacher</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="teacherForm" onsubmit="saveTeacher(event)">
        <div class="modal-body">
          <input type="hidden" id="teacherIdField" value=""/>

          <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center gap-2" style="font-size:0.85rem">
            <i class="fas fa-shield-alt text-primary fa-lg"></i>
            <div>Teachers sign in using their registered email and password on the unified login portal tab.</div>
          </div>

          <div class="row g-3">
            <div class="col-md-12">
              <label class="form-label">Full Name <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" id="tFullName" class="form-control" placeholder="e.g. Maria A. Santos" required/>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Login Email <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input type="email" id="tEmail" class="form-control" placeholder="teacher@lnhs.edu.ph" required/>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label" id="tPasswordLabel">Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" id="tPassword" class="form-control" placeholder="Min. 6 characters" minlength="6"/>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('tPassword', this)">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
              <small class="text-muted" id="tPasswordHint" style="font-size:0.75rem;display:none;">Leave blank to keep existing password.</small>
            </div>

            <div class="col-md-6">
              <label class="form-label">Mobile Number</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                <input type="text" id="tPhone" class="form-control" placeholder="09XXXXXXXXX" maxlength="11" pattern="^09\d{9}$"/>
              </div>
              <small class="text-muted" style="font-size:0.75rem">11 digits, starts with 09 (e.g. 09123456789)</small>
            </div>

            <div class="col-md-6">
              <label class="form-label">Gender</label>
              <select id="tGender" class="form-select">
                <option value="Female">Female</option>
                <option value="Male">Male</option>
                <option value="Other">Other / Prefer not to say</option>
              </select>
            </div>

            <div class="col-md-12">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label fw-semibold mb-0">Teaching Subject &amp; Grade Level Assignments</label>
                <small class="text-muted">Specify which grade level(s) this teacher teaches for each subject</small>
              </div>
              <div class="table-responsive border rounded bg-white" style="max-height: 240px; overflow-y: auto;">
                <table class="table table-sm table-hover mb-0 align-middle" style="font-size:0.83rem">
                  <thead class="table-light sticky-top" style="z-index:1">
                    <tr>
                      <th style="width:34%">Curriculum Subject</th>
                      <th class="text-center" style="width:9%">Gr. 7</th>
                      <th class="text-center" style="width:9%">Gr. 8</th>
                      <th class="text-center" style="width:9%">Gr. 9</th>
                      <th class="text-center" style="width:9%">Gr. 10</th>
                      <th class="text-center" style="width:9%">Gr. 11</th>
                      <th class="text-center" style="width:9%">Gr. 12</th>
                      <th class="text-center" style="width:12%">Quick Action</th>
                    </tr>
                  </thead>
                  <tbody id="tSubjectsTableBody">
                    <tr><td colspan="8" class="text-center py-3 text-muted">Loading subjects…</td></tr>
                  </tbody>
                </table>
              </div>
              <small class="text-muted d-block mt-1" style="font-size:0.75rem;">
                <i class="fas fa-info-circle text-primary me-1"></i>
                Example: Checking <strong>Gr. 7</strong> under <strong>Filipino</strong> allows this teacher to encode grades exclusively for Grade 7 sections in Filipino.
              </small>
            </div>

            <div class="col-md-12">
              <label class="form-label">Account Status</label>
              <select id="tIsActive" class="form-select">
                <option value="1">Active (Can Log In)</option>
                <option value="0">Inactive / Deactivated</option>
              </select>
            </div>

            <div class="col-md-12">
              <label class="form-label">Home / School Address</label>
              <input type="text" id="tAddress" class="form-control" placeholder="e.g. Lubo, Tanudan, Kalinga"/>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="saveTeacherBtn">
            <i class="fas fa-save me-1"></i>Save Teacher
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
  let allTeachersData = [];
  let allSubjectsData = [];
  let allAssignmentsData = [];
  let currentEditingTeacherId = null;
  let teacherModal = null;

  document.addEventListener('DOMContentLoaded', () => {
    teacherModal = new bootstrap.Modal(document.getElementById('teacherModal'));
    loadInitialData();
  });

  async function loadInitialData() {
    await Promise.all([loadSubjects(), loadTeachers()]);
  }

  async function loadSubjects() {
    try {
      const res = await fetch('../../api/grades.php?action=list');
      const json = await res.json();
      if (json.success) {
        allSubjectsData = json.subjects || [];
        populateSubjectMatrix([], null);
      }
    } catch (e) {
      console.error('Failed to load subjects:', e);
    }
  }

  function populateSubjectMatrix(assignedPairs = [], currentTeacherId = null) {
    const tbody = document.getElementById('tSubjectsTableBody');
    if (!tbody) return;
    if (!allSubjectsData.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center py-3 text-muted">No subjects available.</td></tr>';
      return;
    }

    const pairSet = new Set();
    (assignedPairs || []).forEach(p => {
      if (typeof p === 'object' && p !== null) {
        pairSet.add(`${p.subject_id}_${p.grade_level}`);
      } else {
        pairSet.add(String(p));
      }
    });

    const jhsSubjects = allSubjectsData.filter(s => (s.level || 'JHS') === 'JHS');
    const shsSubjects = allSubjectsData.filter(s => s.level === 'SHS');

    const renderRows = (subs, defaultGrades, isShs = false) => {
      return subs.map(s => {
        const grades = [7, 8, 9, 10, 11, 12];
        const gradeCols = grades.map(gl => {
          const key = `${s.id}_${gl}`;
          const isChecked = pairSet.has(key);
          const isApplicable = defaultGrades.includes(gl);

          if (!isApplicable) {
            return `<td class="text-center text-muted" style="background:#f8fafc;font-size:0.75rem">—</td>`;
          }

          // Check if this subject & grade is assigned to another teacher
          const existing = allAssignmentsData.find(a => 
            a.subject_id == s.id && 
            a.grade_level == gl && 
            (!currentTeacherId || a.teacher_id != currentTeacherId)
          );

          let cellClass = '';
          let warningTag = '';
          if (existing) {
            cellClass = 'bg-warning-subtle';
            const shortName = existing.teacher_name ? existing.teacher_name.split(' ')[0] : 'Assigned';
            warningTag = `<div class="text-truncate mt-1" style="font-size:0.62rem;color:#b45309;font-weight:600" title="Already assigned to ${esc(existing.teacher_name)}"><i class="fas fa-user-check"></i> ${esc(shortName)}</div>`;
          }

          return `
            <td class="text-center ${cellClass}" style="vertical-align:middle;padding:4px">
              <input type="checkbox" class="form-check-input t-assignment-cb ${existing ? 'border-warning' : ''}" 
                     data-subject-id="${s.id}" data-grade="${gl}" id="cb_${key}" ${isChecked ? 'checked' : ''} />
              ${warningTag}
            </td>
          `;
        }).join('');

        const quickBtn = isShs 
          ? `<button type="button" class="btn btn-outline-secondary py-0 px-2" style="font-size:0.72rem" onclick="toggleSubGrades(${s.id}, [11,12])" title="Toggle Grade 11-12">SHS</button>`
          : `<button type="button" class="btn btn-outline-secondary py-0 px-2" style="font-size:0.72rem" onclick="toggleSubGrades(${s.id}, [7,8,9,10])" title="Toggle Grade 7-10">JHS</button>`;

        const lvlBadge = isShs
          ? `<span class="badge bg-warning text-dark ms-1" style="font-size:0.68rem">SHS</span>`
          : `<span class="badge bg-info-subtle text-info border border-info-subtle ms-1" style="font-size:0.68rem">JHS</span>`;

        return `
          <tr>
            <td>
              <strong>${esc(s.name)}</strong>
              <span class="badge bg-light text-dark border ms-1" style="font-size:0.7rem">${esc(s.code||'')}</span>
              ${lvlBadge}
            </td>
            ${gradeCols}
            <td class="text-center">
              ${quickBtn}
            </td>
          </tr>
        `;
      }).join('');
    };

    let html = '';
    if (jhsSubjects.length) {
      html += `<tr class="table-light"><th colspan="8" class="text-primary py-1" style="font-size:0.78rem"><i class="fas fa-school me-1"></i>Junior High School Curriculum (Grades 7–10)</th></tr>`;
      html += renderRows(jhsSubjects, [7, 8, 9, 10], false);
    }
    if (shsSubjects.length) {
      html += `<tr class="table-light"><th colspan="8" class="text-warning-emphasis py-1" style="font-size:0.78rem"><i class="fas fa-graduation-cap me-1"></i>Senior High School Curriculum (Grades 11–12)</th></tr>`;
      html += renderRows(shsSubjects, [11, 12], true);
    }

    tbody.innerHTML = html;
  }

  function toggleSubGrades(subjectId, gradeArray) {
    const cbs = gradeArray.map(gl => document.getElementById(`cb_${subjectId}_${gl}`)).filter(Boolean);
    const allChecked = cbs.every(cb => cb.checked);
    cbs.forEach(cb => { cb.checked = !allChecked; });
  }

  async function loadTeachers() {
    try {
      const res = await fetch('../../api/teachers.php?action=list');
      const json = await res.json();
      if (json.success) {
        allTeachersData = json.data || [];
        allAssignmentsData = json.all_assignments || [];
        renderTeachers(allTeachersData);
      } else {
        document.getElementById('teachersTableBody').innerHTML =
          `<tr><td colspan="7" class="text-center text-danger py-4">${esc(json.message || 'Failed to load teachers.')}</td></tr>`;
      }
    } catch (e) {
      document.getElementById('teachersTableBody').innerHTML =
        '<tr><td colspan="7" class="text-center text-danger py-4">Network error loading teachers.</td></tr>';
    }
  }

  function esc(str) {
    return String(str ?? '').replace(/[&<>"']/g,
      c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  function filterTeachers() {
    const q      = document.getElementById('searchInput').value.toLowerCase().trim();
    const status = document.getElementById('filterStatus').value;

    const filtered = allTeachersData.filter(t => {
      const matchQ = !q ||
        t.full_name.toLowerCase().includes(q) ||
        t.email.toLowerCase().includes(q) ||
        (t.phone || '').includes(q) ||
        (t.assigned_subjects || '').toLowerCase().includes(q);

      const matchStatus = status === '' || String(t.is_active) === String(status);

      return matchQ && matchStatus;
    });

    renderTeachers(filtered);
  }

  function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterStatus').value = '';
    renderTeachers(allTeachersData);
  }

  function renderTeachers(data) {
    document.getElementById('teacherCount').textContent = `${data.length} Teachers`;
    const tbody = document.getElementById('teachersTableBody');

    if (!data.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox me-2"></i>No teacher records found.</td></tr>';
      return;
    }

    tbody.innerHTML = data.map((t, i) => {
      const parts = t.full_name.trim().split(' ');
      const initials = ((parts[0] ? parts[0][0] : '') + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase() || 'T';

      const subjectsBadges = t.assigned_subjects
        ? t.assigned_subjects.split(', ').map(s => `<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1 mb-1" style="font-size:0.75rem"><i class="fas fa-book me-1"></i>${esc(s)}</span>`).join('')
        : `<span class="text-muted" style="font-size:0.82rem"><em>No subjects assigned</em></span>`;

      const statusBadge = t.is_active == 1
        ? `<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Active</span>`
        : `<span class="badge bg-secondary"><i class="fas fa-ban me-1"></i>Inactive</span>`;

      return `
        <tr>
          <td>${i + 1}</td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div style="width:34px;height:34px;border-radius:50%;background:#0c1326;color:#fff;
                          display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8rem;flex-shrink:0">
                ${initials}
              </div>
              <div>
                <strong style="font-size:0.9rem">${esc(t.full_name)}</strong>
              </div>
            </div>
          </td>
          <td><code style="font-size:0.85rem">${esc(t.email)}</code></td>
          <td>${t.phone ? `<span style="font-size:0.85rem">${esc(t.phone)}</span>` : '<span class="text-muted">—</span>'}</td>
          <td>${subjectsBadges}</td>
          <td>${statusBadge}</td>
          <td>
            <div class="d-flex gap-1">
              <button class="btn btn-outline-primary btn-sm" title="Edit Credentials & Details" onclick="openEditTeacherModal(${t.id})">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-outline-${t.is_active == 1 ? 'warning' : 'success'} btn-sm"
                      title="${t.is_active == 1 ? 'Deactivate Account' : 'Activate Account'}"
                      onclick="toggleTeacherStatus(${t.id})">
                <i class="fas fa-${t.is_active == 1 ? 'user-slash' : 'user-check'}"></i>
              </button>
              <button class="btn btn-outline-danger btn-sm" title="Delete Teacher" onclick="deleteTeacher(${t.id}, '${esc(t.full_name)}')">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
      input.type = 'text';
      icon.className = 'fas fa-eye-slash';
    } else {
      input.type = 'password';
      icon.className = 'fas fa-eye';
    }
  }

  function openAddTeacherModal() {
    currentEditingTeacherId = null;
    document.getElementById('teacherForm').reset();
    document.getElementById('teacherIdField').value = '';
    document.getElementById('teacherModalTitle').innerHTML = '<i class="fas fa-user-plus me-2 text-primary"></i>Register Teacher';
    document.getElementById('tPasswordLabel').innerHTML = 'Password <span class="text-danger">*</span>';
    document.getElementById('tPassword').setAttribute('required', 'required');
    document.getElementById('tPassword').placeholder = 'Min. 6 characters';
    document.getElementById('tPasswordHint').style.display = 'none';
    populateSubjectMatrix([], null);
    teacherModal.show();
  }

  async function openEditTeacherModal(id) {
    try {
      currentEditingTeacherId = id;
      const res = await fetch(`../../api/teachers.php?action=get&id=${id}`);
      const json = await res.json();
      if (!json.success || !json.data) {
        showToast(json.message || 'Failed to fetch teacher details.', 'error');
        return;
      }

      const t = json.data;
      document.getElementById('teacherIdField').value = t.id;
      document.getElementById('tFullName').value = t.full_name || '';
      document.getElementById('tEmail').value = t.email || '';
      document.getElementById('tPassword').value = '';
      document.getElementById('tPassword').removeAttribute('required');
      document.getElementById('tPassword').placeholder = 'Leave blank to preserve current password';
      document.getElementById('tPasswordLabel').innerHTML = 'New Password (Optional)';
      document.getElementById('tPasswordHint').style.display = 'block';
      document.getElementById('tPhone').value = t.phone || '';
      document.getElementById('tGender').value = t.gender || 'Other';
      document.getElementById('tIsActive').value = t.is_active;
      document.getElementById('tAddress').value = t.address || '';

      populateSubjectMatrix(t.teaching_assignments || [], id);

      document.getElementById('teacherModalTitle').innerHTML = '<i class="fas fa-user-edit me-2 text-primary"></i>Edit Teacher Credentials &amp; Details';
      teacherModal.show();
    } catch (e) {
      showToast('Error loading teacher data.', 'error');
    }
  }

  async function saveTeacher(e) {
    e.preventDefault();
    const btn = document.getElementById('saveTeacherBtn');
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving…';

    const id        = document.getElementById('teacherIdField').value;
    const fullName  = document.getElementById('tFullName').value.trim();
    const email     = document.getElementById('tEmail').value.trim();
    const password  = document.getElementById('tPassword').value;
    const phone     = document.getElementById('tPhone').value.trim();
    const gender    = document.getElementById('tGender').value;
    const isActive  = document.getElementById('tIsActive').value;
    const address   = document.getElementById('tAddress').value.trim();

    if (!id && (!password || password.length < 6)) {
      showToast('Please enter an initial password of at least 6 characters.', 'warning');
      btn.disabled = false;
      btn.innerHTML = origText;
      return;
    }

    if (id && password && password.length < 6) {
      showToast('New password must be at least 6 characters.', 'warning');
      btn.disabled = false;
      btn.innerHTML = origText;
      return;
    }

    // Collect all checked (subject_id, grade_level) pairs
    const selectedAssignments = [];
    document.querySelectorAll('.t-assignment-cb:checked').forEach(cb => {
      selectedAssignments.push({
        subject_id: parseInt(cb.dataset.subjectId),
        grade_level: parseInt(cb.dataset.grade)
      });
    });

    const distinctSubjectIds = Array.from(new Set(selectedAssignments.map(a => a.subject_id)));

    const formData = new URLSearchParams();
    formData.append('action', 'save');
    if (id) formData.append('id', id);
    formData.append('full_name', fullName);
    formData.append('email', email);
    if (password) formData.append('password', password);
    formData.append('phone', phone);
    formData.append('gender', gender);
    formData.append('is_active', isActive);
    formData.append('address', address);
    formData.append('teaching_assignments', JSON.stringify(selectedAssignments));
    formData.append('subject_ids', distinctSubjectIds.join(','));

    try {
      const res = await fetch('../../api/teachers.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
      });
      const json = await res.json();
      if (json.success) {
        showToast(json.message, 'success');
        teacherModal.hide();
        await loadInitialData();
      } else {
        showToast(json.message || 'Failed to save teacher.', 'error');
      }
    } catch (err) {
      showToast('Network error while saving teacher.', 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = origText;
    }
  }

  async function toggleTeacherStatus(id) {
    if (!confirm('Are you sure you want to toggle this teacher\'s active status?')) return;
    try {
      const res = await fetch('../../api/teachers.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=toggle_status&id=${id}`
      });
      const json = await res.json();
      if (json.success) {
        showToast(json.message, 'success');
        loadTeachers();
      } else {
        showToast(json.message || 'Failed to toggle status.', 'error');
      }
    } catch (e) {
      showToast('Network error toggling status.', 'error');
    }
  }

  async function deleteTeacher(id, name) {
    if (!confirm(`Are you sure you want to delete teacher "${name}"?\n\nIf this teacher is assigned as a section adviser, the advisory will be cleared.`)) {
      return;
    }
    try {
      const res = await fetch('../../api/teachers.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=delete&id=${id}`
      });
      const json = await res.json();
      if (json.success) {
        showToast(json.message, 'success');
        await loadInitialData();
      } else {
        showToast(json.message || 'Failed to delete teacher.', 'error');
      }
    } catch (e) {
      showToast('Network error deleting teacher.', 'error');
    }
  }
</script>
</body>
</html>
