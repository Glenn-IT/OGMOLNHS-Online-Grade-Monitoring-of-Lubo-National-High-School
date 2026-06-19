<?php require_once '../../components/under-construction.php'; ?>
<?php
require_once '../../config/session.php';
requireAdmin();
$adminActivePage = 'profile';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Admin Profile – OGMS</title>
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
          <div class="topbar-title">Admin Profile</div>
          <div class="topbar-subtitle">Manage your administrator credentials</div>
        </div>
      </div>
      <div class="topbar-right">
        <button class="btn btn-primary btn-sm" onclick="openEditModal()">
          <i class="fas fa-edit me-1"></i>Edit Profile
        </button>
      </div>
    </header>

    <main class="page-content fade-in">
      <div class="row g-3 justify-content-center">
        <div class="col-md-4">
          <div class="content-card text-center">
            <div class="card-body-custom py-4">
              <div style="width:96px;height:96px;border-radius:50%;background:#0c1326;
                display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:#fff;margin:0 auto 1rem">
                <i class="fas fa-user-shield"></i>
              </div>
              <h5 class="fw-bold" id="adminProfileName">—</h5>
              <p class="text-muted" id="adminProfileEmail" style="font-size:0.85rem">—</p>
              <span class="badge" style="background:#ede9fe;color:#7c3aed">Administrator</span>
              <hr/>
              <div class="d-flex justify-content-around">
                <div class="text-center">
                  <div style="font-size:1.3rem;font-weight:800;color:var(--primary)" id="adminStatStudents">—</div>
                  <div style="font-size:0.72rem;color:#64748b">Students</div>
                </div>
                <div class="text-center">
                  <div style="font-size:1.3rem;font-weight:800;color:var(--success)" id="adminStatGrades">—</div>
                  <div style="font-size:0.72rem;color:#64748b">Grades</div>
                </div>
                <div class="text-center">
                  <div style="font-size:1.3rem;font-weight:800;color:var(--warning)" id="adminStatSms">—</div>
                  <div style="font-size:0.72rem;color:#64748b">SMS Sent</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-8">
          <div class="content-card mb-3">
            <div class="card-header-custom">
              <span class="card-title"><i class="fas fa-id-card me-2 text-primary"></i>Admin Information</span>
              <button class="btn-sm-custom btn-edit" onclick="openEditModal()"><i class="fas fa-edit"></i> Edit</button>
            </div>
            <div class="card-body-custom" id="adminInfoBody">Loading…</div>
          </div>

          <div class="content-card">
            <div class="card-header-custom">
              <span class="card-title"><i class="fas fa-lock me-2 text-warning"></i>Security</span>
            </div>
            <div class="card-body-custom">
              <div class="info-row">
                <span class="info-label">Password</span>
                <span class="info-value">
                  <span style="letter-spacing:0.2em;color:#94a3b8">●●●●●●●●</span>
                  &nbsp;
                  <button class="btn-sm-custom btn-edit" onclick="openEditModal()" style="font-size:0.72rem">Change</button>
                </span>
              </div>
              <div class="info-row">
                <span class="info-label">Last Login</span>
                <span class="info-value" id="adminLastLogin">—</span>
              </div>
              <div class="info-row">
                <span class="info-label">Role</span>
                <span class="info-value"><span class="badge bg-primary">Administrator</span></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit Admin Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12"><label class="form-label">Full Name</label><input type="text" id="adminEditName" class="form-control"/></div>
          <div class="col-12"><label class="form-label">Contact</label><input type="text" id="adminEditContact" class="form-control"/></div>
          <div class="col-12"><hr/><h6 class="text-muted">Change Password (optional)</h6></div>
          <div class="col-md-6"><label class="form-label">New Password</label><input type="password" id="adminNewPwd" class="form-control"/></div>
          <div class="col-md-6"><label class="form-label">Confirm Password</label><input type="password" id="adminConfirmPwd" class="form-control"/></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="saveAdminProfile()"><i class="fas fa-save me-1"></i>Save Changes</button>
      </div>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
  const ADMIN_ID = <?= (int)$_SESSION['user_id'] ?>;
  let adminData = {};

  async function loadProfile() {
    try {
      const [profileRes, statsRes] = await Promise.all([
        fetch('../../api/students.php?action=get&id=' + ADMIN_ID),
        fetch('../../api/analytics.php?action=summary'),
      ]);
      const pData = await profileRes.json();
      const sData = await statsRes.json();
      adminData = pData.data || {};
      const stats = sData.data || {};

      document.getElementById('adminProfileName').textContent = adminData.full_name || '—';
      document.getElementById('adminProfileEmail').textContent = adminData.email || '—';
      document.getElementById('adminLastLogin').textContent = new Date().toLocaleDateString('en-PH',{dateStyle:'medium'});
      document.getElementById('adminStatStudents').textContent = stats.total_students ?? '—';
      document.getElementById('adminStatGrades').textContent   = stats.total_grades   ?? '—';
      document.getElementById('adminStatSms').textContent      = stats.sms_sent       ?? '—';

      document.getElementById('adminInfoBody').innerHTML = `
        <div class="info-row"><span class="info-label">Full Name</span><span class="info-value">${adminData.full_name||'—'}</span></div>
        <div class="info-row"><span class="info-label">Email</span><span class="info-value">${adminData.email||'—'}</span></div>
        <div class="info-row"><span class="info-label">Contact</span><span class="info-value">${adminData.phone||'—'}</span></div>
        <div class="info-row"><span class="info-label">School</span><span class="info-value">Lubo National High School</span></div>`;
    } catch(e) { console.error('Profile error:', e); }
  }

  function openEditModal() {
    document.getElementById('adminEditName').value    = adminData.full_name || '';
    document.getElementById('adminEditContact').value = adminData.phone || '';
    document.getElementById('adminNewPwd').value      = '';
    document.getElementById('adminConfirmPwd').value  = '';
    new bootstrap.Modal(document.getElementById('editAdminModal')).show();
  }

  async function saveAdminProfile() {
    const newPwd  = document.getElementById('adminNewPwd').value;
    const confPwd = document.getElementById('adminConfirmPwd').value;
    if (newPwd && newPwd !== confPwd) { showToast('Passwords do not match.', 'error'); return; }

    const body = new FormData();
    body.append('action',    'update');
    body.append('id',        ADMIN_ID);
    body.append('full_name', document.getElementById('adminEditName').value.trim());
    body.append('phone',     document.getElementById('adminEditContact').value.trim());
    if (newPwd) body.append('new_password', newPwd);

    try {
      const res  = await fetch('../../api/students.php', {method:'POST', body});
      const data = await res.json();
      if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('editAdminModal')).hide();
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
