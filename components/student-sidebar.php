<?php
// components/student-sidebar.php
// Set $studentActivePage before including: 'dashboard','grades','analytics','reports','profile'
$studentActivePage = $studentActivePage ?? '';
function studentLink(string $page, string $current): string {
    return $page === $current ? 'sidebar-link active' : 'sidebar-link';
}
$fullName  = $_SESSION['full_name'] ?? 'Student';
$nameParts = explode(' ', $fullName);
$initials  = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1));
?>
<aside class="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">
      <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
      <div class="logo-text">
        <strong>OGMS</strong><span>Lubo Nat'l High School</span>
      </div>
    </div>
  </div>
  <div class="sidebar-user">
    <div class="user-avatar"><?= $initials ?></div>
    <div class="user-info">
      <strong><?= htmlspecialchars($fullName) ?></strong>
      <span><?= htmlspecialchars($_SESSION['lrn'] ?? '—') ?></span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Main Menu</div>
    <a href="dashboard.php" class="<?= studentLink('dashboard', $studentActivePage) ?>">
      <i class="fas fa-th-large"></i> Dashboard
    </a>
    <a href="grades.php" class="<?= studentLink('grades', $studentActivePage) ?>">
      <i class="fas fa-list-alt"></i> My Grades
    </a>
    <a href="analytics.php" class="<?= studentLink('analytics', $studentActivePage) ?>">
      <i class="fas fa-chart-bar"></i> Analytics
    </a>
    <a href="reports.php" class="<?= studentLink('reports', $studentActivePage) ?>">
      <i class="fas fa-file-alt"></i> Reports
    </a>
    <div class="nav-section-label" style="margin-top:0.5rem">Account</div>
    <a href="profile.php" class="<?= studentLink('profile', $studentActivePage) ?>">
      <i class="fas fa-user-circle"></i> Profile
    </a>
  </nav>
  <div class="sidebar-footer">
    <button class="btn-logout" onclick="window.location.href='../../logout.php'">
      <i class="fas fa-sign-out-alt"></i> Logout
    </button>
  </div>
</aside>
