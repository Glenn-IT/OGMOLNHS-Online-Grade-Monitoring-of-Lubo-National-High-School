<?php
// components/teacher-sidebar.php
// Set $teacherActivePage before including: 'dashboard','manage-grades','reports','sms','profile'
$teacherActivePage = $teacherActivePage ?? '';
function teacherLink(string $page, string $current): string {
    return $page === $current ? 'sidebar-link active' : 'sidebar-link';
}
?>
<aside class="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">
      <div class="logo-icon" style="background:#0284c7"><i class="fas fa-chalkboard-teacher"></i></div>
      <div class="logo-text">
        <strong>OGMS Teacher</strong><span>Lubo Nat'l High School</span>
      </div>
    </div>
  </div>
  <div class="sidebar-user">
    <div class="user-avatar" style="background:#0284c7">
      <i class="fas fa-user-graduate" style="font-size:0.9rem"></i>
    </div>
    <div class="user-info">
      <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'Faculty Teacher') ?></strong>
      <span>Subject Teacher / Faculty</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>
    <a href="dashboard.php" class="<?= teacherLink('dashboard', $teacherActivePage) ?>">
      <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <div class="nav-section-label" style="margin-top:0.5rem">Grading &amp; Records</div>
    <a href="manage-grades.php" class="<?= teacherLink('manage-grades', $teacherActivePage) ?>">
      <i class="fas fa-clipboard-check"></i> Manage Subject Grades
    </a>
    <a href="reports.php" class="<?= teacherLink('reports', $teacherActivePage) ?>">
      <i class="fas fa-table"></i> Section Grade Sheets
    </a>
    <a href="sms.php" class="<?= teacherLink('sms', $teacherActivePage) ?>">
      <i class="fas fa-paper-plane"></i> Grade SMS Alerts
    </a>
    <div class="nav-section-label" style="margin-top:0.5rem">Account</div>
    <a href="profile.php" class="<?= teacherLink('profile', $teacherActivePage) ?>">
      <i class="fas fa-user-cog"></i> My Profile
    </a>
  </nav>
  <div class="sidebar-footer">
    <button class="btn-logout" onclick="window.location.href='../../logout.php'">
      <i class="fas fa-sign-out-alt"></i> Logout
    </button>
  </div>
</aside>
