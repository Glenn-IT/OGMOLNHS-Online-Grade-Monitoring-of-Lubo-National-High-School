<?php
// login.php — OGMS Unified Login Gateway
require_once 'config/session.php';

if (!empty($_SESSION['user_id'])) {
    $redirect = match($_SESSION['role'] ?? '') {
        'admin'   => '/OGMS-Lubo-National-High-School/views/admin/dashboard.php',
        'teacher' => '/OGMS-Lubo-National-High-School/views/teacher/dashboard.php',
        default   => '/OGMS-Lubo-National-High-School/views/student/dashboard.php',
    };
    header("Location: $redirect");
    exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>OGMS – Portal Login | Lubo National High School</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . "/assets/css/style.css") ?>" />
    <style>
      .auth-card {
        max-width: 480px;
        margin: 40px auto;
      }
      .nav-pills .nav-link {
        color: #475569;
        font-weight: 600;
        font-size: 0.9rem;
        border-radius: 8px;
        padding: 8px 16px;
      }
      .nav-pills .nav-link.active {
        background-color: #0c1326;
        color: #fff;
      }
      .back-home-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
        font-size: 0.85rem;
        text-decoration: none;
        margin-bottom: 12px;
      }
      .back-home-link:hover {
        color: #0284c7;
      }
    </style>
  </head>
  <body>
    <div class="auth-wrapper">
      <div class="auth-card">
        <a href="index.php" class="back-home-link">
          <i class="fas fa-arrow-left"></i> Back to School Homepage
        </a>

        <div class="d-flex align-items-center justify-content-center gap-3 mb-2">
          <img src="assets/images/deped_logo.png" alt="DepEd Seal" style="width:52px;height:52px;object-fit:contain;"/>
          <img src="assets/images/lubo_logo.png" alt="Lubo NHS Seal" style="width:52px;height:52px;object-fit:contain;"/>
        </div>

        <h2 class="mt-1">OGMS PORTAL</h2>
        <p class="subtitle mb-3">
          Online Grade Monitoring System<br />Lubo National High School
        </p>

        <!-- Role Tabs: Student, Teacher, Admin -->
        <ul class="nav nav-pills justify-content-center mb-4 bg-light p-1 rounded-3" id="authTabs" role="tablist">
          <li class="nav-item flex-fill text-center">
            <button class="nav-link w-100 active" id="tab-student-btn" onclick="switchLoginTab('student')">
              <i class="fas fa-user-graduate me-1"></i>Student
            </button>
          </li>
          <li class="nav-item flex-fill text-center">
            <button class="nav-link w-100" id="tab-teacher-btn" onclick="switchLoginTab('teacher')">
              <i class="fas fa-chalkboard-teacher me-1"></i>Teacher
            </button>
          </li>
          <li class="nav-item flex-fill text-center">
            <button class="nav-link w-100" id="tab-admin-btn" onclick="switchLoginTab('admin')">
              <i class="fas fa-user-shield me-1"></i>Admin
            </button>
          </li>
        </ul>

        <!-- Student Login -->
        <div id="studentTab">
          <form onsubmit="doLogin(event, 'student')">
            <div class="mb-3">
              <label class="form-label fw-semibold">Student Gmail / Email</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                <input type="email" id="stuEmail" class="form-control" placeholder="student@gmail.com" required autocomplete="username"/>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                <input type="password" id="stuPassword" class="form-control" placeholder="Enter your password" required autocomplete="current-password"/>
                <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('stuPassword', this)">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
            <div class="d-flex justify-content-end mb-3">
              <a href="views/student/forgot-password.php" style="font-size:0.8rem;color:var(--primary);text-decoration:none">Forgot Password?</a>
            </div>
            <button type="submit" class="btn-primary-custom w-100" id="stuSubmitBtn">
              <i class="fas fa-sign-in-alt me-2"></i>Sign In as Student
            </button>
          </form>
          <div class="divider mt-3"><span>or</span></div>
          <div class="text-center">
            <span style="font-size:0.85rem;color:#64748b">Don't have a student account? </span>
            <a href="views/student/signup.php" style="color:var(--primary);font-weight:600;font-size:0.85rem">Sign Up</a>
          </div>
        </div>

        <!-- Teacher Login -->
        <div id="teacherTab" style="display:none">
          <form onsubmit="doLogin(event, 'teacher')">
            <div class="mb-3">
              <label class="form-label fw-semibold">Faculty / Teacher Email</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-chalkboard-teacher text-muted"></i></span>
                <input type="email" id="teacherEmail" class="form-control" placeholder="teacher@lnhs.edu.ph" required autocomplete="username"/>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                <input type="password" id="teacherPassword" class="form-control" placeholder="Enter faculty password" required autocomplete="current-password"/>
                <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('teacherPassword', this)">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
            <button type="submit" class="btn-primary-custom w-100 mt-2" id="teacherSubmitBtn" style="background:#0284c7">
              <i class="fas fa-sign-in-alt me-2"></i>Sign In as Teacher
            </button>
          </form>
          <div class="alert alert-light border mt-3 py-2 text-center" style="font-size:0.8rem;color:#64748b">
            <i class="fas fa-info-circle me-1 text-primary"></i>Faculty accounts are assigned by the LNHS administration.
          </div>
        </div>

        <!-- Admin Login -->
        <div id="adminTab" style="display:none">
          <form onsubmit="doLogin(event, 'admin')">
            <div class="mb-3">
              <label class="form-label fw-semibold">Administrator Email</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user-shield text-muted"></i></span>
                <input type="email" id="adminEmail" class="form-control" placeholder="admin@lnhs.edu.ph" required autocomplete="username"/>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                <input type="password" id="adminPassword" class="form-control" placeholder="Enter administrator password" required autocomplete="current-password"/>
                <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('adminPassword', this)">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
            <button type="submit" class="btn-primary-custom w-100 mt-2" id="adminSubmitBtn">
              <i class="fas fa-shield-alt me-2"></i>Sign In as Admin
            </button>
          </form>
        </div>

      </div>
    </div>

    <div id="toast-container"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/api-client.js"></script>
    <script src="assets/js/app.js"></script>
    <script>
      let currentTab = 'student';

      function switchLoginTab(role) {
        currentTab = role;
        document.querySelectorAll('#authTabs .nav-link').forEach(btn => btn.classList.remove('active'));
        document.getElementById(`tab-${role}-btn`).classList.add('active');

        document.getElementById('studentTab').style.display = role === 'student' ? '' : 'none';
        document.getElementById('teacherTab').style.display = role === 'teacher' ? '' : 'none';
        document.getElementById('adminTab').style.display   = role === 'admin'   ? '' : 'none';
      }

      function togglePwd(id, btn) {
        const input = document.getElementById(id);
        const isText = input.type === 'text';
        input.type = isText ? 'password' : 'text';
        btn.innerHTML = `<i class="fas fa-eye${isText ? '' : '-slash'}"></i>`;
      }

      async function doLogin(e, role) {
        e.preventDefault();

        let email = '', password = '', submitBtnId = '';
        if (role === 'student') {
          email = document.getElementById('stuEmail').value.trim();
          password = document.getElementById('stuPassword').value;
          submitBtnId = 'stuSubmitBtn';
        } else if (role === 'teacher') {
          email = document.getElementById('teacherEmail').value.trim();
          password = document.getElementById('teacherPassword').value;
          submitBtnId = 'teacherSubmitBtn';
        } else {
          email = document.getElementById('adminEmail').value.trim();
          password = document.getElementById('adminPassword').value;
          submitBtnId = 'adminSubmitBtn';
        }

        const btn = document.getElementById(submitBtnId);
        btn.disabled = true;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing in…';

        try {
          const body = new FormData();
          body.append('action', 'login');
          body.append('login_type', role);
          body.append('email', email);
          body.append('password', password);

          const res = await fetch('api/auth.php', { method: 'POST', body });
          const data = await res.json();

          btn.disabled = false;
          btn.innerHTML = originalText;

          if (data.success) {
            showToast(`Welcome back, ${data.name || 'User'}!`, 'success');
            setTimeout(() => {
              window.location.href = data.redirect || 'index.php';
            }, 800);
          } else {
            showToast(data.message || 'Login failed.', 'error');
          }
        } catch (err) {
          btn.disabled = false;
          btn.innerHTML = originalText;
          showToast('Server connection error. Please try again.', 'error');
        }
      }
    </script>
  </body>
</html>
