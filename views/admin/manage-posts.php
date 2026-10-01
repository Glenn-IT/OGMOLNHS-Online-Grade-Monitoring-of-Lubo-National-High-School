<?php
// views/admin/manage-posts.php
require_once '../../config/session.php';
requireAdmin();

$adminActivePage = 'manage-posts';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Manage School Posts &amp; Events – OGMS Admin</title>
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
          <div class="topbar-title">School Posts &amp; Announcements</div>
          <div class="topbar-subtitle">Publish and manage homepage announcements, upcoming events, and school highlights</div>
        </div>
      </div>
      <div class="topbar-right">
        <button class="btn btn-primary btn-sm" onclick="openPostModal()">
          <i class="fas fa-plus me-1"></i>New Post
        </button>
      </div>
    </header>

    <main class="page-content fade-in">
      <!-- Filter Bar -->
      <div class="content-card mb-3">
        <div class="card-body-custom p-3">
          <div class="row g-2 align-items-center">
            <div class="col-md-5">
              <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search posts, events, highlights…" oninput="renderTable()"/>
              </div>
            </div>
            <div class="col-md-4">
              <select id="filterType" class="form-select form-select-sm" onchange="renderTable()">
                <option value="">All Categories (Announcements, Events, Highlights)</option>
                <option value="announcement">Announcements Only</option>
                <option value="event">Events Only</option>
                <option value="highlight">Highlights Only</option>
              </select>
            </div>
            <div class="col-md-3 text-end">
              <button class="btn btn-outline-secondary btn-sm w-100" onclick="clearFilters()">
                <i class="fas fa-undo me-1"></i>Reset
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Posts Table -->
      <div class="content-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
          <span class="card-title"><i class="fas fa-bullhorn me-2 text-primary"></i>Published School Posts</span>
          <span class="badge bg-primary" id="postCount">—</span>
        </div>
        <div class="table-wrapper">
          <table class="table align-middle">
            <thead>
              <tr>
                <th style="width:130px">Category</th>
                <th>Title &amp; Snippet</th>
                <th style="width:140px">Event / Date</th>
                <th style="width:130px">Author</th>
                <th style="width:100px" class="text-center">Status</th>
                <th style="width:120px" class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody id="postsTableBody">
              <tr><td colspan="6" class="text-center py-4 text-muted">Loading posts…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     ADD / EDIT POST MODAL
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="postModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background:#0c1326;color:#fff">
        <h5 class="modal-title" id="postModalTitle"><i class="fas fa-plus-circle me-2"></i>New School Post</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="postId"/>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Category Type <span class="text-danger">*</span></label>
            <select id="postType" class="form-select" required>
              <option value="announcement">Announcement</option>
              <option value="event">Upcoming Event</option>
              <option value="highlight">School Highlight / Achievement</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Event / Effective Date</label>
            <input type="date" id="postEventDate" class="form-control"/>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Post Title <span class="text-danger">*</span></label>
          <input type="text" id="postTitle" class="form-control" placeholder="e.g. 1st Quarter General Assembly" required/>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Author / Department</label>
          <input type="text" id="postAuthor" class="form-control" placeholder="Office of the Principal"/>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Content / Description <span class="text-danger">*</span></label>
          <textarea id="postContent" class="form-control" rows="5" placeholder="Write full details about the announcement, event, or school highlight..." required></textarea>
        </div>

        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="postActive" checked/>
          <label class="form-check-label fw-semibold" for="postActive">Active (Visible on School Homepage)</label>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary btn-sm" onclick="savePost()"><i class="fas fa-save me-1"></i>Save Post</button>
      </div>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
  let allPosts = [];
  let postModalInstance = null;

  function getPostModal() {
    if (!postModalInstance) {
      postModalInstance = new bootstrap.Modal(document.getElementById('postModal'));
    }
    return postModalInstance;
  }

  async function init() {
    await loadPosts();
  }

  async function loadPosts() {
    try {
      const res = await fetch('../../api/posts.php?action=list&all=1');
      const data = await res.json();
      allPosts = data.data || [];
      renderTable();
    } catch (e) {
      showToast('Error loading posts.', 'error');
    }
  }

  function esc(s) {
    return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  function renderTable() {
    const q = (document.getElementById('searchInput').value || '').trim().toLowerCase();
    const typeFilter = document.getElementById('filterType').value;
    const tbody = document.getElementById('postsTableBody');

    const filtered = allPosts.filter(p => {
      if (typeFilter && p.type !== typeFilter) return false;
      if (q) {
        return p.title.toLowerCase().includes(q) || p.content.toLowerCase().includes(q) || (p.author_name||'').toLowerCase().includes(q);
      }
      return true;
    });

    document.getElementById('postCount').textContent = `${filtered.length} Post${filtered.length===1?'':'s'}`;

    if (!filtered.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No posts found matching your search.</td></tr>';
      return;
    }

    tbody.innerHTML = filtered.map(p => {
      let badgeCls = 'bg-primary';
      let typeLabel = 'Announcement';
      if (p.type === 'event') {
        badgeCls = 'bg-warning text-dark';
        typeLabel = 'Event';
      } else if (p.type === 'highlight') {
        badgeCls = 'bg-success';
        typeLabel = 'Highlight';
      }

      const statusBadge = p.is_active == 1
        ? '<span class="badge bg-success" style="cursor:pointer" onclick="toggleStatus(' + p.id + ')" title="Click to hide">Active</span>'
        : '<span class="badge bg-secondary" style="cursor:pointer" onclick="toggleStatus(' + p.id + ')" title="Click to show">Draft</span>';

      const dateDisplay = p.event_date ? fmtDate(p.event_date) : (p.created_at ? fmtDate(p.created_at.substring(0,10)) : '—');

      return `
        <tr>
          <td><span class="badge ${badgeCls}">${typeLabel}</span></td>
          <td>
            <strong style="color:#0f172a">${esc(p.title)}</strong>
            <div class="text-muted text-truncate" style="max-width:380px;font-size:0.8rem">${esc(p.content)}</div>
          </td>
          <td><small class="text-muted"><i class="fas fa-calendar-alt me-1"></i>${dateDisplay}</small></td>
          <td><small>${esc(p.author_name||'—')}</small></td>
          <td class="text-center">${statusBadge}</td>
          <td class="text-center">
            <button class="btn btn-sm btn-outline-primary py-0 px-2" onclick="openEditPostModal(${p.id})" title="Edit Post">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger py-0 px-2 ms-1" onclick="deletePost(${p.id}, '${esc(p.title)}')" title="Delete Post">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      `;
    }).join('');
  }

  function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterType').value = '';
    renderTable();
  }

  function openPostModal() {
    document.getElementById('postModalTitle').innerHTML = '<i class="fas fa-plus-circle me-2"></i>New School Post';
    document.getElementById('postId').value = '';
    document.getElementById('postType').value = 'announcement';
    document.getElementById('postEventDate').value = new Date().toISOString().substring(0,10);
    document.getElementById('postTitle').value = '';
    document.getElementById('postAuthor').value = 'Office of the Principal';
    document.getElementById('postContent').value = '';
    document.getElementById('postActive').checked = true;
    getPostModal().show();
  }

  function openEditPostModal(id) {
    const post = allPosts.find(p => p.id == id);
    if (!post) return;

    document.getElementById('postModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit School Post';
    document.getElementById('postId').value = post.id;
    document.getElementById('postType').value = post.type;
    document.getElementById('postEventDate').value = post.event_date || '';
    document.getElementById('postTitle').value = post.title;
    document.getElementById('postAuthor').value = post.author_name || 'Office of the Principal';
    document.getElementById('postContent').value = post.content;
    document.getElementById('postActive').checked = post.is_active == 1;
    getPostModal().show();
  }

  async function savePost() {
    const id = document.getElementById('postId').value;
    const type = document.getElementById('postType').value;
    const eventDate = document.getElementById('postEventDate').value;
    const title = document.getElementById('postTitle').value.trim();
    const author = document.getElementById('postAuthor').value.trim();
    const content = document.getElementById('postContent').value.trim();
    const isActive = document.getElementById('postActive').checked ? 1 : 0;

    if (!title || !content) {
      showToast('Title and content are required.', 'error');
      return;
    }

    const body = new FormData();
    body.append('action', 'save');
    if (id) body.append('id', id);
    body.append('type', type);
    body.append('title', title);
    body.append('content', content);
    if (eventDate) body.append('event_date', eventDate);
    body.append('author_name', author);
    body.append('is_active', isActive);

    try {
      const res = await fetch('../../api/posts.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        showToast(data.message || 'Post saved.', 'success');
        getPostModal().hide();
        await loadPosts();
      } else {
        showToast(data.message || 'Error saving post.', 'error');
      }
    } catch (e) {
      showToast('Network error while saving post.', 'error');
    }
  }

  async function deletePost(id, title) {
    if (!confirm(`Are you sure you want to delete "${title}"?`)) return;

    const body = new FormData();
    body.append('action', 'delete');
    body.append('id', id);

    try {
      const res = await fetch('../../api/posts.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        showToast(data.message || 'Post deleted.', 'success');
        await loadPosts();
      } else {
        showToast(data.message || 'Error deleting post.', 'error');
      }
    } catch (e) {
      showToast('Network error.', 'error');
    }
  }

  async function toggleStatus(id) {
    const body = new FormData();
    body.append('action', 'toggle_status');
    body.append('id', id);

    try {
      const res = await fetch('../../api/posts.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        await loadPosts();
      }
    } catch (e) {
      showToast('Error toggling status.', 'error');
    }
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
