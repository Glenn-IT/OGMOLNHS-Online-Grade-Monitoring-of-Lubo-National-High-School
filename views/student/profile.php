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
      <div class="row g-3">
        <div class="col-md-4">
          <div class="content-card text-center p-4">
            <div style="width:90px;height:90px;border-radius:50%;background:#0c1326;color:#fff;
              font-size:2.2rem;font-weight:800;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
              <i class="fas fa-user-graduate"></i>
            </div>
            <h5 class="fw-bold mb-1" id="profileName">Loading…</h5>
            <p class="text-muted mb-2" style="font-size:0.85rem">LRN: <code id="profileLrn">—</code></p>
            <span class="badge bg-success mb-3">Active Student</span>
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
            <div class="card-header-custom">
              <span class="card-title"><i class="fas fa-id-card me-2 text-primary"></i>Personal Information</span>
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
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit My Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Full Name</label>
            <input type="text" id="editName" class="form-control"/>
          </div>
          <div class="col-md-6">
            <label class="form-label">Student Contact Number</label>
            <input type="text" id="editPhone" class="form-control" placeholder="e.g. 09XXXXXXXXX"/>
          </div>
          <div class="col-md-6">
            <label class="form-label">Address</label>
            <input type="text" id="editAddress" class="form-control"/>
          </div>
          <div class="col-md-6">
            <label class="form-label">Guardian / Parent Name</label>
            <input type="text" id="editGuardian" class="form-control"/>
          </div>
          <div class="col-md-6">
            <label class="form-label">Parent Contact Number (for Grade SMS)</label>
            <input type="text" id="editGuardianPhone" class="form-control" placeholder="e.g. 09XXXXXXXXX"/>
          </div>
          <div class="col-12"><hr/><h6 class="text-muted">Change Password (optional)</h6></div>
          <div class="col-md-6">
            <label class="form-label">New Password</label>
            <input type="password" id="editNewPwd" class="form-control"/>
          </div>
          <div class="col-md-6">
            <label class="form-label">Confirm Password</label>
            <input type="password" id="editConfirmPwd" class="form-control"/>
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
      document.getElementById('profileLrn').textContent  = profileData.lrn       || '—';
      document.getElementById('profileStatAvg').textContent  = avg     || '—';
      document.getElementById('profileStatPass').textContent = `${passed}/${vals.length}`;
      document.getElementById('profileStatSub').textContent  = subjects.length || '—';

      document.getElementById('profileInfoBody').innerHTML = `
        <div class="info-row"><span class="info-label">Full Name</span><span class="info-value">${profileData.full_name||'—'}</span></div>
        <div class="info-row"><span class="info-label">LRN</span><span class="info-value"><code>${profileData.lrn||'—'}</code></span></div>
        <div class="info-row"><span class="info-label">Email</span><span class="info-value">${profileData.email||'—'}</span></div>
        <div class="info-row"><span class="info-label">Student Contact</span><span class="info-value">${profileData.phone||'—'}</span></div>
        <div class="info-row"><span class="info-label">Address</span><span class="info-value">${profileData.address||'—'}</span></div>
        <div class="info-row"><span class="info-label">Guardian / Parent</span><span class="info-value">${profileData.guardian_name||'—'}</span></div>
        <div class="info-row"><span class="info-label">Parent Contact (Grade SMS)</span><span class="info-value fw-bold text-primary">${profileData.guardian_phone||'—'}</span></div>
        <div class="info-row"><span class="info-label">Section</span><span class="info-value">${profileData.section_name||'—'}</span></div>`;
    } catch(e) { console.error('Profile load error:', e); }
  }

  function openEditModal() {
    document.getElementById('editName').value          = profileData.full_name      || '';
    document.getElementById('editPhone').value         = profileData.phone          || '';
    document.getElementById('editAddress').value       = profileData.address        || '';
    document.getElementById('editGuardian').value      = profileData.guardian_name  || '';
    document.getElementById('editGuardianPhone').value = profileData.guardian_phone || '';
    document.getElementById('editNewPwd').value          = '';
    document.getElementById('editConfirmPwd').value      = '';
    new bootstrap.Modal(document.getElementById('editStudentModal')).show();
  }

  async function saveProfile() {
    const newPwd  = document.getElementById('editNewPwd').value;
    const confPwd = document.getElementById('editConfirmPwd').value;
    if (newPwd && newPwd !== confPwd) { showToast('Passwords do not match.', 'error'); return; }

    const body = new FormData();
    body.append('action',         'update');
    body.append('id',             SESSION_USER_ID);
    body.append('full_name',      document.getElementById('editName').value.trim());
    body.append('phone',          document.getElementById('editPhone').value.trim());
    body.append('address',        document.getElementById('editAddress').value.trim());
    body.append('guardian_name',  document.getElementById('editGuardian').value.trim());
    body.append('guardian_phone', document.getElementById('editGuardianPhone').value.trim());
    if (newPwd) body.append('new_password', newPwd);

    try {
      const res  = await fetch('../../api/students.php', {method:'POST', body});
      const data = await res.json();
      if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('editStudentModal')).hide();
        showToast('Profile updated!', 'success');
        loadProfile();
      } else {
        showToast(data.message || 'Update failed.', 'error');
      }
    } catch(e) { showToast('Server error.', 'error'); }
  }

  document.addEventListener('DOMContentLoaded', loadProfile);
</script>
</body>
</html>
