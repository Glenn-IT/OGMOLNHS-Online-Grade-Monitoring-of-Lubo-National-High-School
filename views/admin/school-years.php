<?php
require_once '../../config/session.php';
requireAdmin();
$adminActivePage = 'school-years';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>School Years – OGMS Admin</title>
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
          <div class="topbar-title">School Years</div>
          <div class="topbar-subtitle">Manage academic years and set the active S.Y.</div>
        </div>
      </div>
      <div class="topbar-right">
        <button class="btn btn-primary btn-sm" onclick="openSyModal()">
          <i class="fas fa-plus me-1"></i>New School Year
        </button>
      </div>
    </header>

    <main class="page-content fade-in">

      <div class="alert alert-info py-2 mb-3" style="font-size:.85rem">
        <i class="fas fa-info-circle me-1"></i>
        The <strong>active</strong> school year decides which sections, enrollments and grades
        the rest of the system reads and writes. Only one can be active at a time.
      </div>

      <div class="content-card">
        <div class="card-header-custom">
          <span class="card-title"><i class="fas fa-calendar-alt me-2 text-primary"></i>Academic Years</span>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>School Year</th>
                <th class="text-center">Status</th>
                <th class="text-center">Sections</th>
                <th class="text-center">Enrollments</th>
                <th class="text-center">Grade Records</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody id="syTableBody">
              <tr><td colspan="6" class="text-center py-4 text-muted">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- ── Add / Edit School Year Modal ────────────────────────────────────────── -->
<div class="modal fade" id="syModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background:#0c1326;color:#fff">
        <h5 class="modal-title" id="syModalTitle"><i class="fas fa-calendar-alt me-2"></i>New School Year</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="syId"/>
        <label class="form-label fw-semibold">School Year Label</label>
        <input type="text" id="syLabel" class="form-control" placeholder="e.g. 2025-2026" maxlength="9"/>
        <small class="text-muted">Format <code>YYYY-YYYY</code>, consecutive years.</small>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="saveSchoolYear()"><i class="fas fa-save me-1"></i>Save</button>
      </div>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
  let syData = [];

  async function init() {
    const res  = await fetch('../../api/school-years.php?action=list');
    const data = await res.json();
    syData = data.data || [];
    renderTable();
  }

  function renderTable() {
    const body = document.getElementById('syTableBody');
    if (!syData.length) {
      body.innerHTML = `<tr><td colspan="6">
        <div class="empty-state"><i class="fas fa-calendar-alt"></i>
        <p>No school years yet. Click <strong>New School Year</strong> to add one.</p></div></td></tr>`;
      return;
    }

    body.innerHTML = syData.map(sy => {
      const active = Number(sy.is_active) === 1;
      const inUse  = Number(sy.section_count) + Number(sy.enrollment_count) + Number(sy.grade_count) > 0;
      return `<tr>
        <td><strong style="font-size:.95rem">${esc(sy.label)}</strong></td>
        <td class="text-center">
          ${active ? '<span class="badge bg-success">Active</span>'
                   : '<span class="badge bg-secondary">Inactive</span>'}
        </td>
        <td class="text-center">${sy.section_count}</td>
        <td class="text-center">${sy.enrollment_count}</td>
        <td class="text-center">${sy.grade_count}</td>
        <td class="text-center">
          ${active ? '' : `<button class="btn btn-outline-success btn-sm me-1"
              onclick="activateSchoolYear(${sy.id})" title="Set as active school year">
              <i class="fas fa-check me-1"></i>Set Active</button>`}
          <button class="btn btn-outline-secondary btn-sm me-1"
            onclick="openSyModal(${sy.id})" title="Rename"><i class="fas fa-edit"></i></button>
          <button class="btn btn-outline-danger btn-sm"
            onclick="deleteSchoolYear(${sy.id})"
            ${active || inUse ? 'disabled' : ''}
            title="${active ? 'Cannot delete the active school year'
                   : inUse ? 'Cannot delete — this school year still has data'
                   : 'Delete school year'}">
            <i class="fas fa-trash"></i></button>
        </td>
      </tr>`;
    }).join('');
  }

  function esc(str) {
    return String(str ?? '').replace(/[&<>"']/g,
      c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  // ── CRUD ───────────────────────────────────────────────────────────────────
  function openSyModal(id = null) {
    const sy = id ? syData.find(s => s.id === id) : null;
    document.getElementById('syId').value    = sy ? sy.id    : '';
    document.getElementById('syLabel').value = sy ? sy.label : '';
    document.getElementById('syModalTitle').innerHTML =
      `<i class="fas fa-calendar-alt me-2"></i>${sy ? 'Rename School Year' : 'New School Year'}`;
    new bootstrap.Modal(document.getElementById('syModal')).show();
  }

  async function saveSchoolYear() {
    const id    = document.getElementById('syId').value;
    const label = document.getElementById('syLabel').value.trim();
    if (!label) { showToast('School year label is required.', 'error'); return; }

    const body = new FormData();
    body.append('action', 'save');
    body.append('label',  label);
    if (id) body.append('id', id);

    try {
      const res  = await fetch('../../api/school-years.php', {method:'POST', body});
      const data = await res.json();
      if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('syModal')).hide();
        showToast(data.message, 'success');
        await init();
      } else {
        showToast(data.message || 'Error saving school year.', 'error');
      }
    } catch(e) { showToast('Server error.', 'error'); }
  }

  async function activateSchoolYear(id) {
    const sy = syData.find(s => s.id === id);
    if (!confirm(`Switch the active school year to ${sy?.label}?\n\n`
      + `New sections, enrollments and grades will be recorded under it, and the rest of the `
      + `system will show ${sy?.label} data.`)) return;

    const body = new FormData();
    body.append('action', 'activate');
    body.append('id', id);
    try {
      const res  = await fetch('../../api/school-years.php', {method:'POST', body});
      const data = await res.json();
      if (data.success) { showToast(data.message, 'success'); await init(); }
      else showToast(data.message || 'Could not switch school year.', 'error');
    } catch(e) { showToast('Server error.', 'error'); }
  }

  async function deleteSchoolYear(id) {
    const sy = syData.find(s => s.id === id);
    if (!confirm(`Delete school year "${sy?.label}"? This cannot be undone.`)) return;

    const body = new FormData();
    body.append('action', 'delete');
    body.append('id', id);
    try {
      const res  = await fetch('../../api/school-years.php', {method:'POST', body});
      const data = await res.json();
      if (data.success) { showToast(data.message, 'success'); await init(); }
      else showToast(data.message || 'Cannot delete school year.', 'error');
    } catch(e) { showToast('Server error.', 'error'); }
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
