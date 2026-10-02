<?php
// views/admin/manage-admins.php — Superadmin Administrator Management Portal
require_once '../../config/session.php';
requireSuperAdmin();
$adminActivePage = 'manage-admins';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Manage Administrators – OGMS Superadmin</title>
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
          <div class="topbar-title d-flex align-items-center gap-2">
            <span>Manage Administrators</span>
            <span class="badge bg-warning text-dark border border-warning" style="font-size:0.72rem"><i class="fas fa-crown me-1"></i>Superadmin Only</span>
          </div>
          <div class="topbar-subtitle">Review pending registrations, authorize new administrators, and manage administrative privileges</div>
        </div>
      </div>
      <div class="topbar-right">
        <button class="btn btn-primary btn-sm" onclick="openAddAdminModal()">
          <i class="fas fa-user-plus me-1"></i>Add Administrator
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
                <input type="text" id="searchInput" placeholder="Search by name, email, phone…" oninput="filterAdmins()"/>
              </div>
            </div>
            <div class="col-md-3">
              <select id="filterStatus" class="form-select form-select-sm" onchange="filterAdmins()">
                <option value="">All Account Statuses</option>
                <option value="pending">Pending Approval</option>
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
              <span class="badge bg-primary" id="adminCount" style="font-size:0.8rem">—</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Pending Registrations Alert Banner -->
      <div id="pendingAlertBanner" class="alert alert-warning align-items-center justify-content-between p-3 mb-3 border-warning-subtle shadow-sm" style="display:none;">
        <div class="d-flex align-items-center gap-3">
          <div style="width:44px;height:44px;border-radius:50%;background:#fef3c7;color:#d97706;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0">
            <i class="fas fa-user-clock"></i>
          </div>
          <div>
            <strong id="pendingAlertText" style="color:#92400e;font-size:0.95rem">Administrator registrations awaiting Superadmin approval.</strong>
            <div style="font-size:0.82rem;color:#78350f">Review applicant credentials below and click <strong>Approve</strong> to authorize administrative portal access.</div>
          </div>
        </div>
        <button class="btn btn-warning btn-sm fw-semibold text-dark px-3" onclick="filterByPending()">
          <i class="fas fa-filter me-1"></i>View Pending
        </button>
      </div>

      <div class="content-card">
        <div class="card-header-custom">
          <span class="card-title"><i class="fas fa-user-shield me-2 text-primary"></i>System Administrators Roster</span>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>#</th>
                <th>Administrator</th>
                <th>Login Email</th>
                <th>Phone Number</th>
                <th>Privilege Level</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="adminsTableBody">
              <tr><td colspan="7" class="text-center py-4">Loading administrator records…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Add / Edit Admin Modal -->
<div class="modal fade" id="adminModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="adminModalTitle"><i class="fas fa-user-plus me-2 text-primary"></i>Add Administrator</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="adminForm" onsubmit="saveAdmin(event)">
        <div class="modal-body">
          <input type="hidden" id="adminIdField" value=""/>

          <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center gap-2" style="font-size:0.83rem">
            <i class="fas fa-info-circle text-primary"></i>
            <div>Administrators created directly by the Superadmin are automatically approved and active.</div>
          </div>

          <div class="row g-3">
            <div class="col-md-12">
              <label class="form-label">Full Name <span class="text-danger">*</span></label>
              <input type="text" id="aFullName" class="form-control" placeholder="e.g. Maria A. Santos" required/>
            </div>

            <div class="col-md-12">
              <label class="form-label">Login Email <span class="text-danger">*</span></label>
              <input type="email" id="aEmailInput" class="form-control" placeholder="admin@lnhs.edu.ph" required/>
            </div>

            <div class="col-md-12">
              <label class="form-label" id="aPasswordLabel">Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="password" id="aPassword" class="form-control" placeholder="Min. 8 characters" minlength="8"/>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('aPassword', this)">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
              <small class="text-muted" id="aPasswordHint" style="font-size:0.75rem;display:none;">Leave blank to preserve current password.</small>
            </div>

            <div class="col-md-6">
              <label class="form-label">Mobile Number</label>
              <input type="text" id="aPhoneInput" class="form-control" placeholder="09XXXXXXXXX" maxlength="11" pattern="^09\d{9}$"/>
            </div>

            <div class="col-md-6">
              <label class="form-label">Gender</label>
              <select id="aGenderSelect" class="form-select">
                <option value="Female">Female</option>
                <option value="Male">Male</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div class="col-md-12">
              <label class="form-label">Account Status</label>
              <select id="aIsActive" class="form-select">
                <option value="1">Active (Can Log In)</option>
                <option value="0">Inactive / Deactivated</option>
              </select>
            </div>

            <div class="col-md-12">
              <label class="form-label">Office / Address</label>
              <input type="text" id="aAddressInput" class="form-control" placeholder="e.g. LNHS Main Campus, Lubo"/>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="saveAdminBtn">
            <i class="fas fa-save me-1"></i>Save Administrator
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
  let allAdminsData = [];
  let adminModal = null;

  document.addEventListener('DOMContentLoaded', () => {
    adminModal = new bootstrap.Modal(document.getElementById('adminModal'));
    loadAdmins();
  });

  async function loadAdmins() {
    try {
      const res = await fetch('../../api/admins.php?action=list');
      const json = await res.json();
      if (json.success) {
        allAdminsData = json.data || [];

        // Check pending registrations
        const pendingCount = allAdminsData.filter(a => a.approval_status === 'pending').length;
        const banner = document.getElementById('pendingAlertBanner');
        if (banner) {
          if (pendingCount > 0) {
            banner.style.display = 'flex';
            document.getElementById('pendingAlertText').textContent =
              `${pendingCount} administrator registration${pendingCount > 1 ? 's' : ''} awaiting Superadmin approval.`;
          } else {
            banner.style.display = 'none';
          }
        }

        renderAdmins(allAdminsData);
      } else {
        document.getElementById('adminsTableBody').innerHTML =
          `<tr><td colspan="7" class="text-center text-danger py-4">${esc(json.message || 'Failed to load administrators.')}</td></tr>`;
      }
    } catch (e) {
      document.getElementById('adminsTableBody').innerHTML =
        '<tr><td colspan="7" class="text-center text-danger py-4">Network error loading administrators.</td></tr>';
    }
  }

  function esc(str) {
    return String(str ?? '').replace(/[&<>"']/g,
      c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  function filterByPending() {
    document.getElementById('filterStatus').value = 'pending';
    filterAdmins();
  }

  function filterAdmins() {
    const q      = document.getElementById('searchInput').value.toLowerCase().trim();
    const status = document.getElementById('filterStatus').value;

    const filtered = allAdminsData.filter(a => {
      const matchQ = !q ||
        a.full_name.toLowerCase().includes(q) ||
        a.email.toLowerCase().includes(q) ||
        (a.phone || '').includes(q);

      let matchStatus = true;
      if (status === 'pending') {
        matchStatus = a.approval_status === 'pending';
      } else if (status === '1') {
        matchStatus = a.is_active == 1 && a.approval_status !== 'pending';
      } else if (status === '0') {
        matchStatus = a.is_active == 0 && a.approval_status !== 'pending';
      }

      return matchQ && matchStatus;
    });

    renderAdmins(filtered);
  }

  function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterStatus').value = '';
    renderAdmins(allAdminsData);
  }

  function renderAdmins(data) {
    document.getElementById('adminCount').textContent = `${data.length} Admins`;
    const tbody = document.getElementById('adminsTableBody');

    if (!data.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox me-2"></i>No administrator records found.</td></tr>';
      return;
    }

    tbody.innerHTML = data.map((a, i) => {
      const parts = a.full_name.trim().split(' ');
      const initials = ((parts[0] ? parts[0][0] : '') + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase() || 'A';

      const isSuper = (parseInt(a.is_superadmin) === 1);

      const roleBadge = isSuper
        ? `<span class="badge bg-warning text-dark border border-warning" style="font-size:0.75rem"><i class="fas fa-crown me-1"></i>Superadmin</span>`
        : `<span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.75rem"><i class="fas fa-user-shield me-1"></i>Administrator</span>`;

      let statusBadge = '';
      if (a.approval_status === 'pending') {
        statusBadge = `<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pending Approval</span>`;
      } else if (a.is_active == 1) {
        statusBadge = `<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Active</span>`;
      } else {
        statusBadge = `<span class="badge bg-secondary"><i class="fas fa-ban me-1"></i>Inactive</span>`;
      }

      let actionsHtml = '';
      if (a.approval_status === 'pending') {
        actionsHtml = `
          <button class="btn btn-success btn-sm" title="Approve Administrator" onclick="approveAdmin(${a.id}, '${esc(a.full_name)}')">
            <i class="fas fa-check-circle me-1"></i>Approve
          </button>
          <button class="btn btn-outline-danger btn-sm" title="Reject Application" onclick="rejectAdmin(${a.id}, '${esc(a.full_name)}')">
            <i class="fas fa-times-circle"></i>
          </button>
        `;
      } else if (isSuper) {
        actionsHtml = `
          <span class="badge bg-light text-secondary border py-2 px-2" style="font-size:0.75rem">
            <i class="fas fa-lock me-1"></i>Superadmin
          </span>
        `;
      } else {
        actionsHtml = `
          <button class="btn btn-outline-primary btn-sm" title="Edit Administrator" onclick="openEditAdminModal(${a.id})">
            <i class="fas fa-edit"></i>
          </button>
          <button class="btn btn-outline-${a.is_active == 1 ? 'warning' : 'success'} btn-sm"
                  title="${a.is_active == 1 ? 'Deactivate Account' : 'Activate Account'}"
                  onclick="toggleAdminStatus(${a.id}, '${esc(a.full_name)}')">
            <i class="fas fa-${a.is_active == 1 ? 'user-slash' : 'user-check'}"></i>
          </button>
          <button class="btn btn-outline-danger btn-sm" title="Delete Administrator" onclick="deleteAdmin(${a.id}, '${esc(a.full_name)}')">
            <i class="fas fa-trash"></i>
          </button>
        `;
      }

      return `
        <tr class="${a.approval_status === 'pending' ? 'table-warning-subtle' : ''}">
          <td>${i + 1}</td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div style="width:34px;height:34px;border-radius:50%;background:${isSuper ? '#b45309' : (a.approval_status === 'pending' ? '#d97706' : '#0c1326')};color:#fff;
                          display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8rem;flex-shrink:0">
                ${isSuper ? '<i class="fas fa-crown" style="font-size:0.8rem"></i>' : initials}
              </div>
              <div>
                <strong style="font-size:0.9rem">${esc(a.full_name)}</strong>
                ${isSuper ? '<span class="ms-1 text-warning" title="Primary System Superadmin"><i class="fas fa-certificate"></i></span>' : ''}
              </div>
            </div>
          </td>
          <td><code style="font-size:0.85rem">${esc(a.email)}</code></td>
          <td>${a.phone ? `<span style="font-size:0.85rem">${esc(a.phone)}</span>` : '<span class="text-muted">—</span>'}</td>
          <td>${roleBadge}</td>
          <td>${statusBadge}</td>
          <td>
            <div class="d-flex gap-1 align-items-center">
              ${actionsHtml}
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

  function openAddAdminModal() {
    document.getElementById('adminForm').reset();
    document.getElementById('adminIdField').value = '';
    document.getElementById('adminModalTitle').innerHTML = '<i class="fas fa-user-plus me-2 text-primary"></i>Add Administrator';
    document.getElementById('aPasswordLabel').innerHTML = 'Password <span class="text-danger">*</span>';
    document.getElementById('aPassword').setAttribute('required', 'required');
    document.getElementById('aPasswordHint').style.display = 'none';
    adminModal.show();
  }

  async function openEditAdminModal(id) {
    try {
      const res = await fetch(`../../api/admins.php?action=get&id=${id}`);
      const json = await res.json();
      if (!json.success || !json.data) {
        showToast(json.message || 'Failed to fetch administrator details.', 'error');
        return;
      }
      const a = json.data;
      document.getElementById('adminIdField').value = a.id;
      document.getElementById('aFullName').value = a.full_name || '';
      document.getElementById('aEmailInput').value = a.email || '';
      document.getElementById('aPassword').value = '';
      document.getElementById('aPassword').removeAttribute('required');
      document.getElementById('aPasswordLabel').innerHTML = 'New Password (Optional)';
      document.getElementById('aPasswordHint').style.display = 'block';
      document.getElementById('aPhoneInput').value = a.phone || '';
      document.getElementById('aGenderSelect').value = a.gender || 'Other';
      document.getElementById('aIsActive').value = a.is_active;
      document.getElementById('aAddressInput').value = a.address || '';

      document.getElementById('adminModalTitle').innerHTML = '<i class="fas fa-user-edit me-2 text-primary"></i>Edit Administrator';
      adminModal.show();
    } catch (e) {
      showToast('Error loading administrator details.', 'error');
    }
  }

  async function saveAdmin(e) {
    e.preventDefault();
    const btn = document.getElementById('saveAdminBtn');
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving…';

    const id       = document.getElementById('adminIdField').value;
    const fullName = document.getElementById('aFullName').value.trim();
    const email    = document.getElementById('aEmailInput').value.trim();
    const password = document.getElementById('aPassword').value;
    const phone    = document.getElementById('aPhoneInput').value.trim();
    const gender   = document.getElementById('aGenderSelect').value;
    const isActive = document.getElementById('aIsActive').value;
    const address  = document.getElementById('aAddressInput').value.trim();

    if (!id && (!password || password.length < 8)) {
      showToast('Password must be at least 8 characters long.', 'warning');
      btn.disabled = false;
      btn.innerHTML = origText;
      return;
    }

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

    try {
      const res = await fetch('../../api/admins.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
      });
      const json = await res.json();
      if (json.success) {
        showToast(json.message, 'success');
        adminModal.hide();
        await loadAdmins();
      } else {
        showToast(json.message || 'Failed to save administrator.', 'error');
      }
    } catch (err) {
      showToast('Network error while saving administrator.', 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = origText;
    }
  }

  async function approveAdmin(id, name) {
    if (!confirm(`Are you sure you want to approve administrator "${name}"?\n\nThis will activate their access to the school administration portal.`)) {
      return;
    }
    try {
      const res = await fetch('../../api/admins.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=approve&id=${id}`
      });
      const json = await res.json();
      if (json.success) {
        showToast(json.message || 'Administrator approved successfully!', 'success');
        await loadAdmins();
      } else {
        showToast(json.message || 'Failed to approve administrator.', 'error');
      }
    } catch (e) {
      showToast('Network error while approving administrator.', 'error');
    }
  }

  async function rejectAdmin(id, name) {
    if (!confirm(`Are you sure you want to reject the administrator registration for "${name}"?`)) {
      return;
    }
    try {
      const res = await fetch('../../api/admins.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=reject&id=${id}`
      });
      const json = await res.json();
      if (json.success) {
        showToast(json.message || 'Administrator registration rejected.', 'info');
        await loadAdmins();
      } else {
        showToast(json.message || 'Failed to reject administrator.', 'error');
      }
    } catch (e) {
      showToast('Network error while rejecting administrator.', 'error');
    }
  }

  async function toggleAdminStatus(id, name) {
    if (!confirm(`Toggle active status for administrator "${name}"?`)) return;
    try {
      const res = await fetch('../../api/admins.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=toggle_status&id=${id}`
      });
      const json = await res.json();
      if (json.success) {
        showToast(json.message, 'success');
        await loadAdmins();
      } else {
        showToast(json.message || 'Failed to toggle status.', 'error');
      }
    } catch (e) {
      showToast('Network error toggling status.', 'error');
    }
  }

  async function deleteAdmin(id, name) {
    if (!confirm(`Are you sure you want to delete administrator "${name}"?\n\nThis action cannot be undone.`)) {
      return;
    }
    try {
      const res = await fetch('../../api/admins.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=delete&id=${id}`
      });
      const json = await res.json();
      if (json.success) {
        showToast(json.message, 'success');
        await loadAdmins();
      } else {
        showToast(json.message || 'Failed to delete administrator.', 'error');
      }
    } catch (e) {
      showToast('Network error deleting administrator.', 'error');
    }
  }
</script>
</body>
</html>
