<?php
require_once '../../config/session.php';
requireStudent();
$studentActivePage = 'profile';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>My Profile – OGMS Student</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../../assets/css/style.css") ?>"/>
</head>
<body>
<div class="app-wrapper">
  <?php include '../../components/student-sidebar.php'; ?>

  <div class="main-content">
    <header class="topbar">
      <div class="topbar-left">
        <button class="topbar-btn hamburger"><i class="fas fa-bars"></i></button>
        <div>
          <div class="topbar-title">My Profile</div>
          <div class="topbar-subtitle">Manage personal and guardian information</div>
        </div>
      </div>
    </header>

    <main class="page-content fade-in">
      <div id="profileAlertContainer"></div>
      <div class="row g-3">
        <div class="col-md-4">
          <div class="content-card text-center p-4">
            <div style="width:90px;height:90px;border-radius:50%;background:#0c1326;color:#fff;
              font-size:2.2rem;font-weight:800;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
              <i class="fas fa-user-graduate"></i>
            </div>
            <h5 class="fw-bold mb-1" id="profileName">Loading…</h5>
            <p class="text-muted mb-2" style="font-size:0.85rem">LRN: <code id="profileLrn">—</code></p>
            <span class="badge bg-success mb-3" id="profileStatusBadge">Active Student</span>
            <button class="btn btn-primary btn-sm w-100" onclick="openEditModal()">
              <i class="fas fa-edit me-1"></i>Edit Profile
            </button>
          </div>

          <div class="content-card mt-3 p-3">
            <h6 class="fw-bold mb-3"><i class="fas fa-chart-line me-2 text-primary"></i>Academic Summary</h6>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted" style="font-size:0.85rem">General Average</span>
              <strong id="profileStatAvg" class="text-primary">—</strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted" style="font-size:0.85rem">Passed Subjects</span>
              <strong id="profileStatPass" class="text-success">—</strong>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted" style="font-size:0.85rem">Total Subjects</span>
              <strong id="profileStatSub">—</strong>
            </div>
          </div>
        </div>

        <div class="col-md-8">
          <div class="content-card">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
              <span class="card-title"><i class="fas fa-id-card me-2 text-primary"></i>Personal Information</span>
              <button class="btn btn-outline-primary btn-sm" onclick="openEditModal()">
                <i class="fas fa-user-pen me-1"></i>Update Info
              </button>
            </div>
            <div class="card-body-custom" id="profileInfoBody">
              <div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading profile…</div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-edit me-2 text-primary"></i>Edit My Profile Information</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
            <input type="text" id="editName" class="form-control" placeholder="e.g. Juan dela Cruz" required/>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">LRN (12-digit)</label>
            <input type="text" id="editLrn" class="form-control" maxlength="12" placeholder="123456789012"/>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Student Contact Number</label>
            <input type="text" id="editPhone" class="form-control" maxlength="11" placeholder="e.g. 09123456789"/>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Home Address</label>
            <input type="text" id="editAddress" class="form-control" placeholder="e.g. Lubo, Kibungan, Benguet"/>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Parent / Guardian Name</label>
            <input type="text" id="editGuardian" class="form-control" placeholder="e.g. Maria dela Cruz"/>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Parent Contact Number <span class="text-primary" style="font-size:0.8rem">(for SMS Grade Alerts)</span></label>
            <input type="text" id="editGuardianPhone" class="form-control" maxlength="11" placeholder="e.g. 09987654321"/>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Gender</label>
            <select id="editGender" class="form-select">
              <option value="">Select Gender</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Birthdate</label>
            <input type="date" id="editBirthdate" class="form-control"/>
          </div>
          <div class="col-12"><hr/><h6 class="text-muted"><i class="fas fa-lock me-1"></i>Change Password (optional)</h6></div>
          <div class="col-md-6">
            <label class="form-label">New Password</label>
            <input type="password" id="editNewPwd" class="form-control" placeholder="Min. 8 characters"/>
          </div>
          <div class="col-md-6">
            <label class="form-label">Confirm Password</label>
            <input type="password" id="editConfirmPwd" class="form-control" placeholder="Repeat new password"/>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="saveProfile()"><i class="fas fa-save me-1"></i>Save Changes</button>
      </div>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
  const SESSION_USER_ID = <?= (int)$_SESSION['user_id'] ?>;
  let profileData = {};

  function fieldDisplay(val, isMissingWarning = false) {
    if (val && String(val).trim() !== '') {
      return `<span class="info-value">${val}</span>`;
    }
    return `<span class="info-value text-danger" style="font-size:0.85rem;"><i class="fas fa-exclamation-circle me-1"></i><em>Not Set (Please update)</em></span>`;
  }

  async function loadProfile() {
    try {
      const [pRes, gRes] = await Promise.all([
        fetch('../../api/students.php?action=get&id=' + SESSION_USER_ID),
        fetch('../../api/grades.php?action=list&student_id=' + SESSION_USER_ID),
      ]);
      const pData = await pRes.json();
      const gData = await gRes.json();
      profileData = pData.data || {};
      const grades   = gData.data     || [];
      const subjects = gData.subjects || [];

      const vals   = grades.map(g=>parseFloat(g.final_grade));
      const avg    = vals.length ? +(vals.reduce((a,b)=>a+b,0)/vals.length).toFixed(2) : 0;
      const passed = vals.filter(v=>v>=75).length;

      document.getElementById('profileName').textContent = profileData.full_name || '—';
      document.getElementById('profileLrn').textContent  = profileData.lrn       || 'Not Set';
      document.getElementById('profileStatAvg').textContent  = avg     || '—';
      document.getElementById('profileStatPass').textContent = `${passed}/${vals.length}`;
      document.getElementById('profileStatSub').textContent  = subjects.length || '—';

      const missing = profileData.missing_fields || [];
      const alertContainer = document.getElementById('profileAlertContainer');
      if (missing.length > 0) {
        alertContainer.innerHTML = `
          <div class="alert alert-warning d-flex align-items-center justify-content-between p-3 mb-4 rounded-3 border-warning" role="alert">
            <div class="d-flex align-items-center">
              <i class="fas fa-exclamation-triangle text-warning fs-4 me-3"></i>
              <div>
                <strong class="d-block text-dark">Incomplete Personal Details</strong>
                <span class="text-secondary small">Please complete your profile details (${missing.length} missing: ${missing.join(', ')}).</span>
              </div>
            </div>
            <button class="btn btn-warning btn-sm fw-bold ms-3" onclick="openEditModal()">
              <i class="fas fa-edit me-1"></i>Complete Now
            </button>
          </div>`;
      } else {
        alertContainer.innerHTML = '';
      }

      document.getElementById('profileInfoBody').innerHTML = `
        <div class="info-row"><span class="info-label">Full Name</span><span class="info-value fw-semibold">${profileData.full_name||'—'}</span></div>
        <div class="info-row"><span class="info-label">LRN</span>${profileData.lrn ? `<span class="info-value"><code>${profileData.lrn}</code></span>` : fieldDisplay('', true)}</div>
        <div class="info-row"><span class="info-label">Email</span><span class="info-value">${profileData.email||'—'}</span></div>
        <div class="info-row"><span class="info-label">Student Contact</span>${fieldDisplay(profileData.phone, true)}</div>
        <div class="info-row"><span class="info-label">Home Address</span>${fieldDisplay(profileData.address, true)}</div>
        <div class="info-row"><span class="info-label">Gender</span>${fieldDisplay(profileData.gender)}</div>
        <div class="info-row"><span class="info-label">Birthdate</span>${fieldDisplay(profileData.birthdate ? fmtDate(profileData.birthdate) : '')}</div>
        <div class="info-row"><span class="info-label">Guardian / Parent</span>${fieldDisplay(profileData.guardian_name, true)}</div>
        <div class="info-row"><span class="info-label">Parent Contact (Grade SMS)</span>${fieldDisplay(profileData.guardian_phone, true)}</div>
        <div class="info-row"><span class="info-label">Section</span><span class="info-value">${profileData.section_name||'<em>Unassigned</em>'}</span></div>
        <div class="info-row"><span class="info-label">Grade Level</span><span class="info-value">${profileData.grade_level ? 'Grade ' + profileData.grade_level : '<em>Unassigned</em>'}</span></div>
        <div class="info-row"><span class="info-label">School Year</span><span class="info-value">${profileData.school_year||'—'}</span></div>`;

      // Check if URL specifies auto-opening the edit modal
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('edit') === '1' || urlParams.get('edit') === 'true') {
        openEditModal();
      }
    } catch(e) { console.error('Profile load error:', e); }
  }

  function openEditModal() {
    document.getElementById('editName').value          = profileData.full_name      || '';
    document.getElementById('editLrn').value           = profileData.lrn            || '';
    document.getElementById('editPhone').value         = profileData.phone          || '';
    document.getElementById('editAddress').value       = profileData.address        || '';
    document.getElementById('editGuardian').value      = profileData.guardian_name  || '';
    document.getElementById('editGuardianPhone').value = profileData.guardian_phone || '';
    document.getElementById('editGender').value        = profileData.gender         || '';
    document.getElementById('editBirthdate').value     = profileData.birthdate      || '';
    document.getElementById('editNewPwd').value        = '';
    document.getElementById('editConfirmPwd').value    = '';
    new bootstrap.Modal(document.getElementById('editStudentModal')).show();
  }

  async function saveProfile() {
    const name    = document.getElementById('editName').value.trim();
    const lrn     = document.getElementById('editLrn').value.trim();
    const phone   = document.getElementById('editPhone').value.trim();
    const gPhone  = document.getElementById('editGuardianPhone').value.trim();
    const newPwd  = document.getElementById('editNewPwd').value;
    const confPwd = document.getElementById('editConfirmPwd').value;

    if (!name) { showToast('Full Name is required.', 'error'); return; }
    if (lrn && !/^\d{12}$/.test(lrn)) { showToast('LRN must be exactly 12 digits.', 'error'); return; }
    if (phone && !/^\d{11}$/.test(phone)) { showToast('Student contact number must be exactly 11 digits.', 'error'); return; }
    if (gPhone && !/^\d{11}$/.test(gPhone)) { showToast('Parent contact number must be exactly 11 digits.', 'error'); return; }
    if (newPwd && newPwd.length < 8) { showToast('New password must be at least 8 characters.', 'error'); return; }
    if (newPwd && newPwd !== confPwd) { showToast('Passwords do not match.', 'error'); return; }

    const body = new FormData();
    body.append('action',         'update');
    body.append('id',             SESSION_USER_ID);
    body.append('full_name',      name);
    body.append('lrn',            lrn);
    body.append('phone',          phone);
    body.append('address',        document.getElementById('editAddress').value.trim());
    body.append('guardian_name',  document.getElementById('editGuardian').value.trim());
    body.append('guardian_phone', gPhone);
    body.append('gender',         document.getElementById('editGender').value);
    body.append('birthdate',      document.getElementById('editBirthdate').value);
    if (newPwd) body.append('new_password', newPwd);

    try {
      const res  = await fetch('../../api/students.php', {method:'POST', body});
      const data = await res.json();
      if (data.success) {
        const modalEl = document.getElementById('editStudentModal');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();
        showToast('Profile successfully updated!', 'success');
        loadProfile();
      } else {
        showToast(data.message || 'Update failed.', 'error');
      }
    } catch(e) { showToast('Server error. Please try again.', 'error'); }
  }

  document.addEventListener('DOMContentLoaded', loadProfile);
</script>
</body>
</html>
