<?php
require_once '../../config/session.php';
// Redirect already-logged-in users away from signup
if (!empty($_SESSION['user_id'])) {
    $dest = $_SESSION['role'] === 'admin'
        ? '/OGMS-Lubo-National-High-School/views/admin/dashboard.php'
        : '/OGMS-Lubo-National-High-School/views/student/dashboard.php';
    header("Location: $dest");
    exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <title>Sign Up – OGMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/style.css" />
  </head>
  <body>
    <div class="auth-wrapper">
      <div class="auth-card" style="max-width:520px">
        <div class="school-logo"><i class="fas fa-user-plus"></i></div>
        <h2>Create Account</h2>
        <p class="subtitle">Register as a student of Lubo National High School</p>

        <form id="signupForm" onsubmit="handleSignup(event)">
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label">First Name</label>
              <input type="text" id="signupFirst" class="form-control" placeholder="Juan" required />
            </div>
            <div class="col-6">
              <label class="form-label">Last Name</label>
              <input type="text" id="signupLast" class="form-control" placeholder="dela Cruz" required />
            </div>
            <div class="col-12">
              <label class="form-label">LRN <span style="color:#64748b;font-size:0.8rem">(12-digit Learner Reference Number)</span></label>
              <input type="text" id="signupLrn" class="form-control"
                placeholder="e.g. 123456789012" maxlength="12" pattern="\d{12}"
                title="LRN must be exactly 12 digits" />
            </div>
            <div class="col-12">
              <label class="form-label">Email Address</label>
              <div class="input-group has-validation">
                <input type="email" id="signupEmail" class="form-control" placeholder="you@gmail.com" required
                  autocomplete="off" novalidate />
                <span class="input-group-text" id="signupEmailIcon" style="display:none"></span>
              </div>
              <div id="signupEmailFeedback" style="font-size:0.78rem;margin-top:0.25rem;display:none"></div>
            </div>
            <div class="col-12">
              <label class="form-label">Password</label>
              <div class="input-group">
                <input type="password" id="signupPwd" class="form-control" placeholder="Min. 6 characters" required />
                <button type="button" class="btn btn-outline-secondary"
                  onclick="togglePwd('signupPwd',this)" style="border-radius:0 8px 8px 0">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Confirm Password</label>
              <input type="password" id="signupPwd2" class="form-control" placeholder="Repeat password" required />
            </div>
            <div class="col-12">
              <div class="form-check">
                <input type="checkbox" class="form-check-input" id="agreeTerms" required />
                <label class="form-check-label" style="font-size:0.8rem">
                  I agree to the <a href="#" style="color:var(--primary)">terms and conditions</a>
                </label>
              </div>
            </div>
          </div>
          <button type="submit" class="btn-primary-custom mt-3" id="signupBtn">
            <i class="fas fa-user-plus me-2"></i>Create Account
          </button>
        </form>

        <div class="text-center mt-3">
          <span style="font-size:0.85rem;color:#64748b">Already have an account? </span>
          <a href="../../index.php" style="color:var(--primary);font-weight:600;font-size:0.85rem">Sign In</a>
        </div>
      </div>
    </div>

    <div id="toast-container"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/app.js"></script>
    <script>
      function togglePwd(id, btn) {
        const el   = document.getElementById(id);
        const show = el.type === 'password';
        el.type    = show ? 'text' : 'password';
        btn.innerHTML = `<i class="fas fa-eye${show ? '-slash' : ''}"></i>`;
      }

      const GMAIL_RE = /^[a-zA-Z0-9._%+-]+@gmail\.com$/i;

      function validateSignupEmail() {
        const input    = document.getElementById('signupEmail');
        const icon     = document.getElementById('signupEmailIcon');
        const feedback = document.getElementById('signupEmailFeedback');
        const value    = input.value.trim();

        if (!value) {
          input.classList.remove('is-valid', 'is-invalid');
          icon.style.display = 'none';
          feedback.style.display = 'none';
          return false;
        }

        const isGmail = GMAIL_RE.test(value);
        input.classList.toggle('is-valid', isGmail);
        input.classList.toggle('is-invalid', !isGmail);
        icon.style.display = '';
        icon.innerHTML = isGmail
          ? '<i class="fas fa-check-circle text-success"></i>'
          : '<i class="fas fa-times-circle text-danger"></i>';
        feedback.style.display = '';
        feedback.style.color = isGmail ? '#10b981' : '#dc3545';
        feedback.textContent = isGmail
          ? 'Looks good — a valid Gmail address.'
          : 'Please use a real Gmail address (e.g. you@gmail.com).';
        return isGmail;
      }

      document.getElementById('signupEmail').addEventListener('input', validateSignupEmail);

      async function handleSignup(e) {
        e.preventDefault();

        if (!validateSignupEmail()) {
          showToast('Please enter a valid Gmail address.', 'error');
          document.getElementById('signupEmail').focus();
          return;
        }

        const pwd  = document.getElementById('signupPwd').value;
        const pwd2 = document.getElementById('signupPwd2').value;

        if (pwd !== pwd2) {
          showToast('Passwords do not match!', 'error'); return;
        }
        if (pwd.length < 6) {
          showToast('Password must be at least 6 characters.', 'error'); return;
        }

        const lrn = document.getElementById('signupLrn').value.trim();
        if (lrn && !/^\d{12}$/.test(lrn)) {
          showToast('LRN must be exactly 12 digits.', 'error'); return;
        }

        const btn = document.getElementById('signupBtn');
        btn.disabled  = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating account…';

        try {
          const body = new FormData();
          body.append('action',     'register');
          body.append('first_name', document.getElementById('signupFirst').value.trim());
          body.append('last_name',  document.getElementById('signupLast').value.trim());
          body.append('email',      document.getElementById('signupEmail').value.trim().toLowerCase());
          body.append('password',   pwd);
          body.append('lrn',        lrn);

          const res  = await fetch('../../api/students.php', { method: 'POST', body });
          const data = await res.json();

          if (data.success) {
            showToast('Account created! Redirecting to login…', 'success');
            setTimeout(() => { window.location.href = '../../index.php'; }, 1500);
          } else {
            showToast(data.message || 'Registration failed.', 'error');
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Create Account';
          }
        } catch (err) {
          showToast('Server error. Please try again.', 'error');
          btn.disabled  = false;
          btn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Create Account';
        }
      }
    </script>
  </body>
</html>
