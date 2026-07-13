<?php
require_once 'config/session.php';
if (!empty($_SESSION['user_id'])) {
    $redirect = $_SESSION['role'] === 'admin'
        ? '/OGMS-Lubo-National-High-School/views/admin/dashboard.php'
        : '/OGMS-Lubo-National-High-School/views/student/dashboard.php';
    header("Location: $redirect");
    exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>OGMS – Login | Lubo National High School</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
  </head>
  <body>
    <div class="auth-wrapper">
      <div class="auth-card">
        <div class="school-logo"><i class="fas fa-graduation-cap"></i></div>
        <h2>OGMS</h2>
        <p class="subtitle">
          Online Grade Monitoring System<br />Lubo National High School
        </p>

        <!-- Tabs -->
        <ul class="nav auth-tabs justify-content-center mb-3" id="authTabs">
          <li class="nav-item">
            <button class="nav-link active" onclick="showTab('student')">Student</button>
          </li>
          <li class="nav-item">
            <button class="nav-link" onclick="showTab('admin')">Admin</button>
          </li>
        </ul>

        <!-- Student Login Form -->
        <div id="studentTab">
          <form id="studentLoginForm" onsubmit="doLogin(event,'student')">
            <div class="mb-3">
              <label class="form-label">Email Address</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input type="email" id="stuEmail" class="form-control"
                  placeholder="Enter your email address" required />
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" id="stuPassword" class="form-control"
                  placeholder="Enter your password" required />
                <button type="button" class="btn btn-outline-secondary"
                  onclick="togglePwd('stuPassword',this)" style="border-radius:0 8px 8px 0">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
            <div class="d-flex justify-content-end mb-3">
              <a href="views/student/forgot-password.php" style="font-size:0.8rem;color:var(--primary)">Forgot Password?</a>
            </div>
            <button type="submit" class="btn-primary-custom" id="stuSubmitBtn">
              <i class="fas fa-sign-in-alt me-2"></i>Login as Student
            </button>
          </form>
          <div class="divider"><span>or</span></div>
          <div class="text-center">
            <span style="font-size:0.85rem;color:#64748b">Don't have an account? </span>
            <a href="views/student/signup.php" style="color:var(--primary);font-weight:600;font-size:0.85rem">Sign Up</a>
          </div>
        </div>

        <!-- Admin Login Form -->
        <div id="adminTab" style="display:none">
          <form id="adminLoginForm" onsubmit="doLogin(event,'admin')">
            <div class="mb-3">
              <label class="form-label">Admin Email</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user-shield"></i></span>
                <input type="email" id="adminEmail" class="form-control"
                  placeholder="Enter your email address" required />
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" id="adminPassword" class="form-control"
                  placeholder="Enter your password" required />
                <button type="button" class="btn btn-outline-secondary"
                  onclick="togglePwd('adminPassword',this)" style="border-radius:0 8px 8px 0">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
            <div class="d-flex justify-content-end mb-3">
              <a href="views/student/forgot-password.php" style="font-size:0.8rem;color:var(--primary)">Forgot Password?</a>
            </div>
            <button type="submit" class="btn-primary-custom" id="adminSubmitBtn"
              style="background:linear-gradient(135deg,#7c3aed,#a78bfa)">
              <i class="fas fa-shield-alt me-2"></i>Login as Admin
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
      function showTab(tab) {
        document.getElementById('studentTab').style.display = tab === 'student' ? 'block' : 'none';
        document.getElementById('adminTab').style.display   = tab === 'admin'   ? 'block' : 'none';
        document.querySelectorAll('.auth-tabs .nav-link').forEach((el, i) => {
          el.classList.toggle('active', (i === 0 && tab === 'student') || (i === 1 && tab === 'admin'));
        });
      }

      function togglePwd(id, btn) {
        const el   = document.getElementById(id);
        const show = el.type === 'password';
        el.type    = show ? 'text' : 'password';
        btn.innerHTML = `<i class="fas fa-eye${show ? '-slash' : ''}"></i>`;
      }

      const defaultBtnHtml = {
        student: '<i class="fas fa-sign-in-alt me-2"></i>Login as Student',
        admin:   '<i class="fas fa-shield-alt me-2"></i>Login as Admin',
      };
      const lockTimers = { student: null, admin: null };

      function formFields(type) {
        return {
          email: document.getElementById(type === 'student' ? 'stuEmail'     : 'adminEmail'),
          pwd:   document.getElementById(type === 'student' ? 'stuPassword'  : 'adminPassword'),
          btn:   document.getElementById(type === 'student' ? 'stuSubmitBtn' : 'adminSubmitBtn'),
        };
      }

      function setFormDisabled(type, disabled) {
        const { email, pwd, btn } = formFields(type);
        email.disabled = disabled;
        pwd.disabled    = disabled;
        btn.disabled    = disabled;
      }

      function lockForm(type, seconds) {
        clearInterval(lockTimers[type]);
        localStorage.setItem(`ogms_lockout_${type}`, Date.now() + seconds * 1000);

        const { btn } = formFields(type);
        setFormDisabled(type, true);
        let remaining = seconds;

        const tick = () => {
          btn.innerHTML = `<i class="fas fa-lock me-2"></i>Try again in ${remaining}s`;
          if (remaining <= 0) {
            clearInterval(lockTimers[type]);
            localStorage.removeItem(`ogms_lockout_${type}`);
            setFormDisabled(type, false);
            btn.innerHTML = defaultBtnHtml[type];
            return;
          }
          remaining--;
        };
        tick();
        lockTimers[type] = setInterval(tick, 1000);
      }

      // Resume any lockout still in effect after a page refresh
      ['student', 'admin'].forEach((type) => {
        const until = parseInt(localStorage.getItem(`ogms_lockout_${type}`) || '0', 10);
        const secondsLeft = Math.ceil((until - Date.now()) / 1000);
        if (secondsLeft > 0) lockForm(type, secondsLeft);
      });

      async function doLogin(e, type) {
        e.preventDefault();
        const email    = document.getElementById(type === 'student' ? 'stuEmail'    : 'adminEmail').value.trim();
        const password = document.getElementById(type === 'student' ? 'stuPassword' : 'adminPassword').value;
        const btn      = document.getElementById(type === 'student' ? 'stuSubmitBtn': 'adminSubmitBtn');

        btn.disabled   = true;
        btn.innerHTML  = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in…';

        try {
          const body = new FormData();
          body.append('action',     'login');
          body.append('login_type', type);
          body.append('email',      email);
          body.append('password',   password);

          const res  = await fetch('api/auth.php', { method: 'POST', body });
          const data = await res.json();

          if (data.success) {
            showToast(type === 'admin' ? 'Welcome, Administrator!' : `Welcome back!`, 'success');
            setTimeout(() => { window.location.href = data.redirect; }, 800);
            return;
          }

          if (data.locked) {
            showToast(data.message || 'Too many failed attempts.', 'error');
            lockForm(type, data.seconds || 30);
            return;
          }

          showToast(data.message || 'Invalid credentials.', 'error');
          btn.disabled  = false;
          btn.innerHTML = defaultBtnHtml[type];
        } catch (err) {
          showToast('Server error. Please try again.', 'error');
          btn.disabled  = false;
          btn.innerHTML = defaultBtnHtml[type];
        }
      }
    </script>
  </body>
</html>
