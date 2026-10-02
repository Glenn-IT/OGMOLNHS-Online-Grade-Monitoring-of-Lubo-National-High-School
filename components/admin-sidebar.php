<?php
// components/admin-sidebar.php
// Set $adminActivePage before including: 'dashboard','manage-grades','manage-students','manage-sections','school-years','analytics','reports','sms','profile'
$adminActivePage = $adminActivePage ?? '';
function adminLink(string $page, string $current): string {
    return $page === $current ? 'sidebar-link active' : 'sidebar-link';
}
?>
<aside class="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">
      <div class="logo-icon"><i class="fas fa-school"></i></div>
      <div class="logo-text">
        <strong>OGMS Admin</strong><span>Lubo Nat'l High School</span>
      </div>
    </div>
  </div>
<?php $isSuper = !empty($_SESSION['is_superadmin']) && (int)$_SESSION['is_superadmin'] === 1; ?>
  <div class="sidebar-user">
    <div class="user-avatar" style="background:<?= $isSuper ? '#b45309' : '#0c1326' ?>">
      <i class="fas fa-<?= $isSuper ? 'crown' : 'user-shield' ?>" style="font-size:0.9rem;<?= $isSuper ? 'color:#fef08a' : '' ?>"></i>
    </div>
    <div class="user-info">
      <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'Administrator') ?></strong>
      <span><?= $isSuper ? 'Super Administrator' : 'School Administrator' ?></span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Dashboard</div>
    <a href="dashboard.php" class="<?= adminLink('dashboard', $adminActivePage) ?>">
      <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <div class="nav-section-label" style="margin-top:0.5rem">Management</div>
    <?php if ($isSuper): ?>
    <a href="manage-admins.php" class="<?= adminLink('manage-admins', $adminActivePage) ?>">
      <i class="fas fa-user-shield text-warning"></i> Manage Admins
    </a>
    <?php endif; ?>
    <a href="manage-grades.php" class="<?= adminLink('manage-grades', $adminActivePage) ?>">
      <i class="fas fa-clipboard-list"></i> Manage Grades
    </a>
    <a href="manage-students.php" class="<?= adminLink('manage-students', $adminActivePage) ?>">
      <i class="fas fa-users"></i> Manage Students
    </a>
    <a href="manage-teachers.php" class="<?= adminLink('manage-teachers', $adminActivePage) ?>">
      <i class="fas fa-chalkboard-teacher"></i> Manage Teachers
    </a>
    <a href="manage-sections.php" class="<?= adminLink('manage-sections', $adminActivePage) ?>">
      <i class="fas fa-layer-group"></i> Manage Sections
    </a>
    <a href="manage-posts.php" class="<?= adminLink('manage-posts', $adminActivePage) ?>">
      <i class="fas fa-bullhorn"></i> School Posts &amp; Events
    </a>
    <a href="school-years.php" class="<?= adminLink('school-years', $adminActivePage) ?>">
      <i class="fas fa-calendar-alt"></i> School Years
    </a>
    <div class="nav-section-label" style="margin-top:0.5rem">Reports &amp; Analytics</div>
    <a href="analytics.php" class="<?= adminLink('analytics', $adminActivePage) ?>">
      <i class="fas fa-chart-line"></i> Analytics
    </a>
    <a href="reports.php" class="<?= adminLink('reports', $adminActivePage) ?>">
      <i class="fas fa-file-pdf"></i> Reports
    </a>
    <a href="sms.php" class="<?= adminLink('sms', $adminActivePage) ?>">
      <i class="fas fa-sms"></i> SMS Notify
    </a>
    <div class="nav-section-label" style="margin-top:0.5rem">Account</div>
    <a href="profile.php" class="<?= adminLink('profile', $adminActivePage) ?>">
      <i class="fas fa-user-cog"></i> Admin Profile
    </a>
  </nav>
  <div class="sidebar-footer">
    <button class="btn-logout" onclick="window.location.href='../../logout.php'">
      <i class="fas fa-sign-out-alt"></i> Logout
    </button>
  </div>
</aside>
