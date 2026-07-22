<?php
require_once '../../config/session.php';
requireAdmin();
$adminActivePage = 'manage-students';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Manage Students – OGMS Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../../assets/css/style.css"/>
</head>
<body>
<div class="app-wrapper">
  <?php include '../../components/admin-sidebar.php'; ?>

  <div class="main-content">
    <header class="topbar">
      <div class="topbar-left">
        <button class="topbar-btn hamburger"><i class="fas fa-bars"></i></button>
        <div>
          <div class="topbar-title">Manage Students</div>
          <div class="topbar-subtitle">Add, edit and view student records</div>
        </div>
      </div>
      <div class="topbar-right">
        <button class="btn btn-primary btn-sm" onclick="openAddStudentModal()">
          <i class="fas fa-user-plus me-1"></i>Add Student
        </button>
      </div>
    </header>

    <main class="page-content fade-in">
      <div class="content-card mb-3">
        <div class="card-body-custom">
          <div class="row g-2 align-items-end">
            <div class="col-md-4">
              <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search by name, LRN…" oninput="filterStudents()"/>
              </div>
            </div>
            <div class="col-md-2">
              <select id="filterGrade" class="form-select form-select-sm" onchange="filterStudents()">
                <option value="">All Grade Levels</option>
                <option>7</option><option>8</option><option>9</option>
                <option>10</option><option>11</option><option>12</option>
              </select>
            </div>
            <div class="col-md-2">
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
              <span class="badge bg-primary" id="studentCount" style="font-size:0.8rem">—</span>
            </div>
          </div>
        </div>
      </div>

      <div class="content-card">
        <div class="card-header-custom">
          <span class="card-title"><i class="fas fa-users me-2 text-primary"></i>Student Records</span>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr><th>#</th><th>Student</th><th>LRN</th><th>Section</th><th>Contact</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody id="studentsTableBody">
              <tr><td colspan="7" class="text-center py-4">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Add / Edit Student Modal -->
<div class="modal fade" id="studentModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="studentModalTitle"><i class="fas fa-user-plus me-2"></i>Add Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="studentIdField"/>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">First Name *</label><input type="text" id="sFirst" class="form-control" required/></div>
          <div class="col-md-6"><label class="form-label">Last Name *</label><input type="text" id="sLast" class="form-control" required/></div>
          <div class="col-md-6"><label class="form-label">Email *</label><input type="email" id="sEmail" class="form-control" required/></div>
          <div class="col-md-6"><label class="form-label">LRN (12 digits)</label><input type="text" id="sLrn" class="form-control" maxlength="12" inputmode="numeric" oninput="this.value=this.value.replace(/\D/g,'')"/></div>
          <div class="col-md-6"><label class="form-label">Contact Number (11 digits)</label><input type="text" id="sContact" class="form-control" maxlength="11" inputmode="numeric" oninput="this.value=this.value.replace(/\D/g,'')"/></div>
          <div class="col-md-6"><label class="form-label">Gender</label>
            <select id="sGender" class="form-select"><option value="">Select</option><option>Male</option><option>Female</option></select>
          </div>
          <div class="col-md-6"><label class="form-label">Birthdate</label><input type="date" id="sBirthdate" class="form-control"/></div>
          <div class="col-12"><label class="form-label">Address</label><input type="text" id="sAddress" class="form-control"/></div>
          <div class="col-12"><hr/><h6 class="text-muted">Account Credentials</h6></div>
          <div class="col-md-6">
            <label class="form-label">Password <small id="pwdHint" class="text-muted">(required for new)</small></label>
            <input type="password" id="sPwd" class="form-control"/>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="saveStudent()"><i class="fas fa-save me-1"></i>Save Student</button>
      </div>
    </div>
  </div>
</div>

<!-- View Student Modal -->
<div class="modal fade" id="viewStudentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-id-card me-2"></i>Student Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="viewStudentBody"></div>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
  let allStudentsData = [];

  async function loadStudents() {
    try {
      const res  = await fetch('../../api/students.php?action=list');
      const data = await res.json();
      allStudentsData = data.data || [];
      populateSectionFilter();
      renderStudents(allStudentsData);
    } catch(e) {
      document.getElementById('studentsTableBody').innerHTML =
        '<tr><td colspan="7" class="text-center text-danger">Failed to load students.</td></tr>';
    }
  }

  function populateSectionFilter() {
    const sections = [...new Set(allStudentsData.map(s => s.section_name).filter(Boolean))];
    const sel = document.getElementById('filterSection');
    sel.innerHTML = '<option value="">All Sections</option>';
    sections.forEach(sec => sel.innerHTML += `<option value="${sec}">${sec}</option>`);
  }

  function filterStudents() {
    const q       = document.getElementById('searchInput').value.toLowerCase();
    const grade   = document.getElementById('filterGrade').value;
    const section = document.getElementById('filterSection').value;
    const data = allStudentsData.filter(s => {
      const matchQ = !q || s.full_name.toLowerCase().includes(q) || (s.lrn||'').includes(q) || s.email.toLowerCase().includes(q);
      const matchG = !grade   || s.grade_level == grade;
      const matchS = !section || s.section_name === section;
      return matchQ && matchG && matchS;
    });
    renderStudents(data);
  }

  function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterGrade').value = '';
    document.getElementById('filterSection').value = '';
    renderStudents(allStudentsData);
  }

  function renderStudents(data) {
    document.getElementById('studentCount').textContent = data.length + ' students';
    const tbody = document.getElementById('studentsTableBody');
    if (!data.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox me-2"></i>No students found.</td></tr>';
      return;
    }
    tbody.innerHTML = data.map((s, i) => {
      const nameParts = s.full_name.trim().split(' ');
      const initials  = (nameParts[0][0] + (nameParts[nameParts.length-1][0]||'')).toUpperCase();
      return `<tr>
        <td>${i+1}</td>
        <td>
          <div class="d-flex align-items-center gap-2">
            <div style="width:32px;height:32px;border-radius:50%;background:#0c1326;
              display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.75rem;flex-shrink:0">
              ${initials}
            </div>
            <div><strong style="font-size:.875rem">${s.full_name}</strong><br><small class="text-muted">${s.email}</small></div>
          </div>
        </td>
        <td><code style="font-size:.8rem">${s.lrn||'—'}</code></td>
        <td>${s.section_name||'—'} ${s.grade_level?`(Gr.${s.grade_level})`:''}</td>
        <td style="font-size:.8rem">${s.phone||'—'}</td>
        <td><span class="badge bg-success">Active</span></td>
        <td>
          <button class="btn-sm-custom btn-view me-1" onclick="viewStudent(${s.id})"><i class="fas fa-eye"></i></button>
          <button class="btn-sm-custom btn-edit me-1" onclick="editStudent(${s.id})"><i class="fas fa-edit"></i></button>
        </td>
      </tr>`;
    }).join('');
  }

  function openAddStudentModal() {
    document.getElementById('studentIdField').value = '';
    document.getElementById('studentModalTitle').innerHTML = '<i class="fas fa-user-plus me-2"></i>Add Student';
    document.getElementById('pwdHint').textContent = '(required for new)';
    ['sFirst','sLast','sEmail','sLrn','sContact','sGender','sBirthdate','sAddress','sPwd'].forEach(id => {
      document.getElementById(id).value = '';
    });
    new bootstrap.Modal(document.getElementById('studentModal')).show();
  }

  function editStudent(id) {
    const s = allStudentsData.find(st => st.id === id);
    if (!s) return;
    document.getElementById('studentIdField').value = s.id;
    document.getElementById('studentModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Student';
    document.getElementById('pwdHint').textContent = '(leave blank to keep)';
    document.getElementById('sFirst').value  = s.full_name.split(' ')[0];
    document.getElementById('sLast').value   = s.full_name.split(' ').slice(1).join(' ');
    document.getElementById('sEmail').value  = s.email;
    document.getElementById('sLrn').value    = s.lrn || '';
    document.getElementById('sContact').value= s.phone || '';
    document.getElementById('sGender').value = s.gender || '';
    document.getElementById('sBirthdate').value = s.birthdate || '';
    document.getElementById('sAddress').value= s.address || '';
    document.getElementById('sPwd').value    = '';
    new bootstrap.Modal(document.getElementById('studentModal')).show();
  }

  async function saveStudent() {
    const id    = document.getElementById('studentIdField').value;
    const first = document.getElementById('sFirst').value.trim();
    const last  = document.getElementById('sLast').value.trim();
    const email = document.getElementById('sEmail').value.trim().toLowerCase();
    const pwd   = document.getElementById('sPwd').value;
    const lrn     = document.getElementById('sLrn').value.trim();
    const contact = document.getElementById('sContact').value.trim();

    if (!first || !last || !email) { showToast('Please fill required fields.', 'error'); return; }
    if (!id && !pwd) { showToast('Password is required for new students.', 'error'); return; }
    if (lrn && !/^\d{12}$/.test(lrn)) { showToast('LRN must be exactly 12 digits.', 'error'); return; }
    if (contact && !/^\d{11}$/.test(contact)) { showToast('Contact number must be exactly 11 digits.', 'error'); return; }

    const body = new FormData();
    if (id) {
      body.append('action',    'update');
      body.append('id',        id);
      body.append('full_name', `${first} ${last}`);
      body.append('phone',     contact);
      body.append('gender',    document.getElementById('sGender').value);
      body.append('birthdate', document.getElementById('sBirthdate').value);
      body.append('address',   document.getElementById('sAddress').value.trim());
      if (pwd) body.append('new_password', pwd);
    } else {
      body.append('action',     'register');
      body.append('first_name', first);
      body.append('last_name',  last);
      body.append('email',      email);
      body.append('password',   pwd);
      body.append('lrn',        lrn);
      body.append('phone',      contact);
    }

    try {
      const res  = await fetch('../../api/students.php', {method:'POST', body});
      const data = await res.json();
      if (data.success) {
        showToast(id ? 'Student updated!' : 'Student added!', 'success');
        bootstrap.Modal.getInstance(document.getElementById('studentModal')).hide();
        loadStudents();
      } else {
        showToast(data.message || 'Error saving student.', 'error');
      }
    } catch(e) { showToast('Server error.', 'error'); }
  }

  async function viewStudent(id) {
    const s = allStudentsData.find(st => st.id === id);
    if (!s) return;
    const nameParts = s.full_name.trim().split(' ');
    const initials  = (nameParts[0][0] + (nameParts[nameParts.length-1][0]||'')).toUpperCase();
    document.getElementById('viewStudentBody').innerHTML = `
      <div class="text-center mb-3">
        <div style="width:72px;height:72px;border-radius:50%;background:#0c1326;
          display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.6rem;font-weight:800;margin:0 auto 1rem">
          ${initials}
        </div>
        <h5 class="fw-bold">${s.full_name}</h5>
        <p class="text-muted mb-1" style="font-size:.85rem">${s.lrn||'No LRN'}</p>
        <span class="badge bg-primary">${s.section_name||'—'}</span>
      </div>
      <div class="row g-2">
        <div class="col-md-6">
          <div class="info-row"><span class="info-label">Email</span><span class="info-value">${s.email}</span></div>
          <div class="info-row"><span class="info-label">Contact</span><span class="info-value">${s.phone||'—'}</span></div>
          <div class="info-row"><span class="info-label">Gender</span><span class="info-value">${s.gender||'—'}</span></div>
          <div class="info-row"><span class="info-label">Birthdate</span><span class="info-value">${s.birthdate||'—'}</span></div>
        </div>
        <div class="col-md-6">
          <div class="info-row"><span class="info-label">Address</span><span class="info-value">${s.address||'—'}</span></div>
          <div class="info-row"><span class="info-label">Grade Level</span><span class="info-value">Grade ${s.grade_level||'—'}</span></div>
          <div class="info-row"><span class="info-label">Section</span><span class="info-value">${s.section_name||'—'}</span></div>
          <div class="info-row"><span class="info-label">Status</span><span class="info-value"><span class="badge bg-success">Active</span></span></div>
        </div>
      </div>`;
    new bootstrap.Modal(document.getElementById('viewStudentModal')).show();
  }

  async function deleteStudent(id) {
    confirmAction('Deactivate this student account?', async () => {
      const body = new FormData();
      body.append('action','delete');
      body.append('id', id);
      try {
        const res  = await fetch('../../api/students.php', {method:'POST', body});
        const data = await res.json();
        if (data.success) { showToast('Student deactivated.', 'success'); loadStudents(); }
        else showToast(data.message || 'Error.', 'error');
      } catch(e) { showToast('Server error.', 'error'); }
    });
  }

  document.addEventListener('DOMContentLoaded', loadStudents);
</script>
</body>
</html>
