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
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../../assets/css/style.css") ?>" />
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
                <input type="password" id="signupPwd" class="form-control" placeholder="Min. 8 characters" required />
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
          <a href="../../login.php" style="color:var(--primary);font-weight:600;font-size:0.85rem">Sign In</a>
        </div>
      </div>
    </div>

    <!-- OTP Verification Modal -->
    <div class="modal fade" id="otpModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content text-center p-3 border-0 shadow">
          <div class="modal-body">
            <div style="width: 68px; height: 68px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 28px;">
              <i class="fas fa-shield-alt"></i>
            </div>
            <h4 class="fw-bold mb-1" style="color: #0c1326;">Verify Your Gmail</h4>
            <p class="text-muted mb-3" style="font-size: 0.88rem;">
              We sent a 6-digit confirmation code to<br/>
              <strong id="otpTargetEmail" style="color: #0284c7;">—</strong>
            </p>

            <form id="otpForm" onsubmit="verifyOtpAndRegister(event)">
              <div class="mb-3">
                <input type="text" id="otpInput" class="form-control text-center fw-bold"
                       placeholder="123456" maxlength="6" pattern="\d{6}" required
                       style="font-size: 1.7rem; letter-spacing: 10px; border: 2px solid #cbd5e1; border-radius: 12px; height: 58px;"
                       autocomplete="one-time-code"/>
                <small class="text-muted d-block mt-2" style="font-size: 0.78rem;">Please check your Inbox or Spam folder</small>
              </div>

              <button type="submit" class="btn-primary-custom w-100 mb-2" id="verifyOtpBtn" style="height:46px;">
                <i class="fas fa-check-circle me-2"></i>Verify &amp; Create Account
              </button>
            </form>

            <div class="mt-3" style="font-size: 0.84rem;">
              <span class="text-muted">Didn't receive the email? </span>
              <button type="button" class="btn btn-link p-0 text-decoration-none" id="resendOtpBtn" onclick="resendOtp()" style="font-size: 0.84rem; font-weight: 600; color: #0284c7;">Resend Code</button>
              <span id="resendTimer" class="text-muted ms-1" style="display: none;">(60s)</span>
            </div>

            <div class="mt-3 pt-2 border-top">
              <button type="button" class="btn btn-sm btn-link text-secondary text-decoration-none" data-bs-dismiss="modal">
                <i class="fas fa-arrow-left me-1"></i>Edit details / change email
              </button>
            </div>
          </div>
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
          : 'Please use a valid Gmail address (e.g. you@gmail.com).';
        return isGmail;
      }

      document.getElementById('signupEmail').addEventListener('input', validateSignupEmail);

      let otpModalInstance = null;
      let resendTimerInterval = null;

      function getOtpModal() {
        if (!otpModalInstance) {
          otpModalInstance = new bootstrap.Modal(document.getElementById('otpModal'));
        }
        return otpModalInstance;
      }

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
        if (pwd.length < 8) {
          showToast('Password must be at least 8 characters.', 'error'); return;
        }

        const lrn = document.getElementById('signupLrn').value.trim();
        if (lrn && !/^\d{12}$/.test(lrn)) {
          showToast('LRN must be exactly 12 digits.', 'error'); return;
        }

        const email = document.getElementById('signupEmail').value.trim().toLowerCase();
        const firstName = document.getElementById('signupFirst').value.trim();
        const lastName = document.getElementById('signupLast').value.trim();

        const btn = document.getElementById('signupBtn');
        btn.disabled  = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending OTP to email…';

        try {
          const body = new FormData();
          body.append('action',     'send_signup_otp');
          body.append('first_name', firstName);
          body.append('last_name',  lastName);
          body.append('email',      email);
          if (lrn) body.append('lrn', lrn);

          const res  = await fetch('../../api/students.php', { method: 'POST', body });
          const data = await res.json();

          btn.disabled  = false;
          btn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Create Account';

          if (data.success) {
            document.getElementById('otpTargetEmail').textContent = email;
            document.getElementById('otpInput').value = '';
            getOtpModal().show();
            startResendCountdown(60);
            showToast(data.message || 'OTP sent! Please check your inbox.', 'success');
            setTimeout(() => document.getElementById('otpInput').focus(), 400);
          } else {
            showToast(data.message || 'Unable to send verification OTP.', 'error');
          }
        } catch (err) {
          btn.disabled  = false;
          btn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Create Account';
          showToast('Server error while sending OTP. Please try again.', 'error');
        }
      }

      async function verifyOtpAndRegister(e) {
        e.preventDefault();
        const otp = document.getElementById('otpInput').value.trim();
        if (!otp || !/^\d{6}$/.test(otp)) {
          showToast('Please enter the 6-digit OTP code.', 'error');
          return;
        }

        const verifyBtn = document.getElementById('verifyOtpBtn');
        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating account…';

        try {
          const body = new FormData();
          body.append('action',     'register');
          body.append('first_name', document.getElementById('signupFirst').value.trim());
          body.append('last_name',  document.getElementById('signupLast').value.trim());
          body.append('email',      document.getElementById('signupEmail').value.trim().toLowerCase());
          body.append('password',   document.getElementById('signupPwd').value);
          body.append('lrn',        document.getElementById('signupLrn').value.trim());
          body.append('otp',        otp);

          const res  = await fetch('../../api/students.php', { method: 'POST', body });
          const data = await res.json();

          if (data.success) {
            showToast('Account verified and created! Redirecting to login…', 'success');
            getOtpModal().hide();
            setTimeout(() => { window.location.href = '../../login.php'; }, 1500);
          } else {
            showToast(data.message || 'Registration failed.', 'error');
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Verify &amp; Create Account';
          }
        } catch (err) {
          showToast('Server error. Please try again.', 'error');
          verifyBtn.disabled = false;
          verifyBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Verify &amp; Create Account';
        }
      }

      async function resendOtp() {
        const resendBtn = document.getElementById('resendOtpBtn');
        if (resendBtn.disabled) return;

        const email = document.getElementById('signupEmail').value.trim().toLowerCase();
        const firstName = document.getElementById('signupFirst').value.trim();
        const lastName = document.getElementById('signupLast').value.trim();
        const lrn = document.getElementById('signupLrn').value.trim();

        resendBtn.disabled = true;
        resendBtn.textContent = 'Sending…';

        try {
          const body = new FormData();
          body.append('action',     'send_signup_otp');
          body.append('email',      email);
          body.append('first_name', firstName);
          body.append('last_name',  lastName);
          if (lrn) body.append('lrn', lrn);

          const res  = await fetch('../../api/students.php', { method: 'POST', body });
          const data = await res.json();

          if (data.success) {
            showToast('A fresh OTP has been sent to your email.', 'success');
            startResendCountdown(60);
          } else {
            showToast(data.message || 'Failed to resend OTP.', 'error');
            resendBtn.disabled = false;
            resendBtn.textContent = 'Resend Code';
          }
        } catch (err) {
          showToast('Error resending OTP.', 'error');
          resendBtn.disabled = false;
          resendBtn.textContent = 'Resend Code';
        }
      }

      function startResendCountdown(seconds) {
        const resendBtn = document.getElementById('resendOtpBtn');
        const timerSpan = document.getElementById('resendTimer');
        if (resendTimerInterval) clearInterval(resendTimerInterval);

        let remaining = seconds;
        resendBtn.disabled = true;
        resendBtn.style.pointerEvents = 'none';
        resendBtn.style.opacity = '0.5';
        timerSpan.style.display = 'inline';
        timerSpan.textContent = `(${remaining}s)`;

        resendTimerInterval = setInterval(() => {
          remaining--;
          if (remaining <= 0) {
            clearInterval(resendTimerInterval);
            resendBtn.disabled = false;
            resendBtn.style.pointerEvents = '';
            resendBtn.style.opacity = '1';
            resendBtn.textContent = 'Resend Code';
            timerSpan.style.display = 'none';
          } else {
            timerSpan.textContent = `(${remaining}s)`;
          }
        }, 1000);
      }

    </script>
  </body>
</html>
