<?php
// views/admin/signup.php — Administrator Self-Registration Gateway
require_once '../../config/session.php';

// Redirect logged in users
if (!empty($_SESSION['user_id'])) {
    $dest = match($_SESSION['role'] ?? '') {
        'admin'   => '/OGMS-Lubo-National-High-School/views/admin/dashboard.php',
        'teacher' => '/OGMS-Lubo-National-High-School/views/teacher/dashboard.php',
        default   => '/OGMS-Lubo-National-High-School/views/student/dashboard.php',
    };
    header("Location: $dest");
    exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <title>Administrator Registration – OGMS | Lubo National High School</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../../assets/css/style.css") ?>" />
    <style>
      .auth-card {
        max-width: 540px;
        margin: 30px auto;
      }
      .school-logo-admin {
        width: 66px;
        height: 66px;
        border-radius: 50%;
        background: #0c1326;
        color: #f59e0b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        margin: 0 auto 12px;
        box-shadow: 0 4px 14px rgba(12, 19, 38, 0.35);
        border: 2px solid #f59e0b;
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
        <a href="../../login.php" class="back-home-link">
          <i class="fas fa-arrow-left"></i> Back to Portal Login
        </a>

        <div class="school-logo-admin"><i class="fas fa-user-shield"></i></div>
        <h2>Admin Registration</h2>
        <p class="subtitle">Apply for an administrative account at Lubo National High School</p>

        <div class="alert alert-warning py-2 px-3 mb-3 d-flex align-items-start gap-2" style="font-size:0.82rem;border-left:4px solid #f59e0b;">
          <i class="fas fa-shield-alt text-warning mt-1"></i>
          <div>
            <strong>Superadmin Approval Required:</strong> Administrative accounts grant high-privilege access. All registrations require email OTP verification and must be <strong>approved by the Superadmin</strong> before portal login is activated.
          </div>
        </div>

        <form id="adminSignupForm" onsubmit="handleAdminSignup(event)">
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label">First Name <span class="text-danger">*</span></label>
              <input type="text" id="aFirst" class="form-control" placeholder="Glenn" required />
            </div>
            <div class="col-6">
              <label class="form-label">Last Name <span class="text-danger">*</span></label>
              <input type="text" id="aLast" class="form-control" placeholder="Dela Cruz" required />
            </div>
            <div class="col-12">
              <label class="form-label">Administrator Email <span class="text-danger">*</span></label>
              <div class="input-group has-validation">
                <input type="email" id="aEmail" class="form-control" placeholder="admin.name@gmail.com" required
                  autocomplete="username" />
                <span class="input-group-text" id="aEmailIcon" style="display:none"></span>
              </div>
              <div id="aEmailFeedback" style="font-size:0.78rem;margin-top:0.25rem;display:none"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Mobile Number</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-phone text-muted"></i></span>
                <input type="text" id="aPhone" class="form-control" placeholder="09XXXXXXXXX" maxlength="11" pattern="^09\d{9}$" />
              </div>
              <small class="text-muted" style="font-size:0.74rem">11 digits, starts with 09</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Gender</label>
              <select id="aGender" class="form-select">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other / Prefer not to say</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="password" id="aPwd" class="form-control" placeholder="Min. 8 characters" minlength="8" required autocomplete="new-password"/>
                <button type="button" class="btn btn-outline-secondary"
                  onclick="togglePwd('aPwd',this)" style="border-radius:0 8px 8px 0">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
              <small class="text-muted" style="font-size:0.74rem">Must be at least 8 characters</small>
            </div>
            <div class="col-12">
              <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="password" id="aPwd2" class="form-control" placeholder="Repeat password" minlength="8" required autocomplete="new-password"/>
                <button type="button" class="btn btn-outline-secondary"
                  onclick="togglePwd('aPwd2',this)" style="border-radius:0 8px 8px 0">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Office / Residential Address</label>
              <input type="text" id="aAddress" class="form-control" placeholder="e.g. Lubo, Tanudan, Kalinga" />
            </div>
            <div class="col-12">
              <div class="form-check">
                <input type="checkbox" class="form-check-input" id="agreeTerms" required />
                <label class="form-check-label" style="font-size:0.82rem">
                  I certify that I am an authorized administrative staff member of Lubo NHS, and I agree to comply with school data privacy standards.
                </label>
              </div>
            </div>
          </div>

          <button type="submit" class="btn-primary-custom w-100 mt-3" id="signupBtn" style="background:#0c1326;border:1px solid #f59e0b">
            <i class="fas fa-shield-alt me-2 text-warning"></i>Verify Email &amp; Submit Application
          </button>
        </form>

        <div class="text-center mt-3 pt-2 border-top">
          <span style="font-size:0.85rem;color:#64748b">Already have an admin account? </span>
          <a href="../../login.php" style="color:var(--primary);font-weight:600;font-size:0.85rem">Sign In</a>
        </div>
      </div>
    </div>

    <!-- OTP Verification Modal -->
    <div class="modal fade" id="otpModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content text-center p-3 border-0 shadow">
          <div class="modal-body">
            <div style="width: 68px; height: 68px; border-radius: 50%; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 28px;">
              <i class="fas fa-shield-alt"></i>
            </div>
            <h4 class="fw-bold mb-1" style="color: #0c1326;">Verify Email Address</h4>
            <p class="text-muted mb-3" style="font-size: 0.88rem;">
              We sent a 6-digit confirmation code to<br/>
              <strong id="otpTargetEmail" style="color: #b45309;">—</strong>
            </p>

            <form id="otpForm" onsubmit="verifyOtpAndRegisterAdmin(event)">
              <div class="mb-3">
                <input type="text" id="otpInput" class="form-control text-center fw-bold"
                       placeholder="123456" maxlength="6" pattern="\d{6}" required
                       style="font-size: 1.7rem; letter-spacing: 10px; border: 2px solid #cbd5e1; border-radius: 12px; height: 58px;"
                       autocomplete="one-time-code"/>
                <small class="text-muted d-block mt-2" style="font-size: 0.78rem;">Please check your Inbox or Spam folder</small>
              </div>

              <button type="submit" class="btn-primary-custom w-100 mb-2" id="verifyOtpBtn" style="background:#0c1326;height:46px;">
                <i class="fas fa-check-circle me-2 text-warning"></i>Verify &amp; Submit Application
              </button>
            </form>

            <div class="mt-3" style="font-size: 0.84rem;">
              <span class="text-muted">Didn't receive the email? </span>
              <button type="button" class="btn btn-link p-0 text-decoration-none" id="resendOtpBtn" onclick="resendAdminOtp()" style="font-size: 0.84rem; font-weight: 600; color: #0284c7;">Resend Code</button>
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

    <!-- Registration Completed / Pending Superadmin Approval Modal -->
    <div class="modal fade" id="pendingApprovalModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content text-center p-4 border-0 shadow">
          <div class="modal-body">
            <div style="width: 76px; height: 76px; border-radius: 50%; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; font-size: 34px;">
              <i class="fas fa-user-lock"></i>
            </div>
            <h3 class="fw-bold mb-2" style="color: #0c1326;">Application Submitted!</h3>
            <p class="text-muted mb-3" style="font-size: 0.92rem; line-height: 1.5;">
              Thank you, <strong id="registeredAdminName">—</strong>.<br/>
              Your email has been verified. Your administrator account is now <span class="badge bg-warning text-dark px-2 py-1">Pending Superadmin Approval</span>.
            </p>

            <div class="alert alert-light border text-start p-3 mb-3" style="font-size: 0.84rem; color: #475569;">
              <div class="d-flex align-items-start gap-2">
                <i class="fas fa-info-circle text-primary mt-1"></i>
                <div>
                  <strong>Authorization Workflow:</strong> The Superadmin has been notified to review your credentials and authorize your account. Once approved, you will be notified via email and can sign in via the Admin Gateway.
                </div>
              </div>
            </div>

            <a href="../../login.php" class="btn-primary-custom w-100 d-inline-block text-decoration-none py-2" style="background:#0c1326">
              <i class="fas fa-arrow-right me-2"></i>Go to Portal Login
            </a>
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

      function validateAdminEmail() {
        const input    = document.getElementById('aEmail');
        const icon     = document.getElementById('aEmailIcon');
        const feedback = document.getElementById('aEmailFeedback');
        const value    = input.value.trim();

        if (!value) {
          input.classList.remove('is-valid', 'is-invalid');
          icon.style.display = 'none';
          feedback.style.display = 'none';
          return false;
        }

        const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValid = emailRe.test(value);
        input.classList.toggle('is-valid', isValid);
        input.classList.toggle('is-invalid', !isValid);
        icon.style.display = '';
        icon.innerHTML = isValid
          ? '<i class="fas fa-check-circle text-success"></i>'
          : '<i class="fas fa-times-circle text-danger"></i>';
        feedback.style.display = '';
        feedback.style.color = isValid ? '#10b981' : '#dc3545';
        feedback.textContent = isValid
          ? 'Valid email address format.'
          : 'Please enter a valid email address.';
        return isValid;
      }

      document.getElementById('aEmail').addEventListener('input', validateAdminEmail);

      let otpModalInstance = null;
      let approvalModalInstance = null;
      let resendTimerInterval = null;

      function getOtpModal() {
        if (!otpModalInstance) {
          otpModalInstance = new bootstrap.Modal(document.getElementById('otpModal'));
        }
        return otpModalInstance;
      }

      function getApprovalModal() {
        if (!approvalModalInstance) {
          approvalModalInstance = new bootstrap.Modal(document.getElementById('pendingApprovalModal'));
        }
        return approvalModalInstance;
      }

      async function handleAdminSignup(e) {
        e.preventDefault();

        if (!validateAdminEmail()) {
          showToast('Please enter a valid email address.', 'error');
          document.getElementById('aEmail').focus();
          return;
        }

        const pwd  = document.getElementById('aPwd').value;
        const pwd2 = document.getElementById('aPwd2').value;

        if (pwd !== pwd2) {
          showToast('Passwords do not match!', 'error');
          return;
        }
        if (pwd.length < 8) {
          showToast('Password must be at least 8 characters long.', 'error');
          return;
        }

        const phone = document.getElementById('aPhone').value.trim();
        if (phone && !/^09\d{9}$/.test(phone)) {
          showToast('Phone number must be an 11-digit mobile number starting with 09.', 'error');
          return;
        }

        const email     = document.getElementById('aEmail').value.trim().toLowerCase();
        const firstName = document.getElementById('aFirst').value.trim();
        const lastName  = document.getElementById('aLast').value.trim();

        const btn = document.getElementById('signupBtn');
        btn.disabled  = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending Verification OTP…';

        try {
          const body = new FormData();
          body.append('action',     'send_signup_otp');
          body.append('first_name', firstName);
          body.append('last_name',  lastName);
          body.append('email',      email);
          if (phone) body.append('phone', phone);

          const res  = await fetch('../../api/admins.php', { method: 'POST', body });
          const data = await res.json();

          btn.disabled  = false;
          btn.innerHTML = '<i class="fas fa-shield-alt me-2 text-warning"></i>Verify Email &amp; Submit Application';

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
          btn.innerHTML = '<i class="fas fa-shield-alt me-2 text-warning"></i>Verify Email &amp; Submit Application';
          showToast('Server error while sending OTP. Please try again.', 'error');
        }
      }

      async function verifyOtpAndRegisterAdmin(e) {
        e.preventDefault();
        const otp = document.getElementById('otpInput').value.trim();
        if (!otp || !/^\d{6}$/.test(otp)) {
          showToast('Please enter the 6-digit OTP code.', 'error');
          return;
        }

        const verifyBtn = document.getElementById('verifyOtpBtn');
        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting application…';

        const firstName = document.getElementById('aFirst').value.trim();
        const lastName  = document.getElementById('aLast').value.trim();
        const email     = document.getElementById('aEmail').value.trim().toLowerCase();
        const password  = document.getElementById('aPwd').value;
        const phone     = document.getElementById('aPhone').value.trim();
        const gender    = document.getElementById('aGender').value;
        const address   = document.getElementById('aAddress').value.trim();

        try {
          const body = new FormData();
          body.append('action',     'register');
          body.append('first_name', firstName);
          body.append('last_name',  lastName);
          body.append('email',      email);
          body.append('password',   password);
          body.append('phone',      phone);
          body.append('gender',     gender);
          body.append('address',    address);
          body.append('otp',        otp);

          const res  = await fetch('../../api/admins.php', { method: 'POST', body });
          const data = await res.json();

          if (data.success) {
            getOtpModal().hide();
            document.getElementById('registeredAdminName').textContent = `${firstName} ${lastName}`;
            getApprovalModal().show();
          } else {
            showToast(data.message || 'Registration failed.', 'error');
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '<i class="fas fa-check-circle me-2 text-warning"></i>Verify &amp; Submit Application';
          }
        } catch (err) {
          showToast('Server error. Please try again.', 'error');
          verifyBtn.disabled = false;
          verifyBtn.innerHTML = '<i class="fas fa-check-circle me-2 text-warning"></i>Verify &amp; Submit Application';
        }
      }

      async function resendAdminOtp() {
        const resendBtn = document.getElementById('resendOtpBtn');
        if (resendBtn.disabled) return;

        const email     = document.getElementById('aEmail').value.trim().toLowerCase();
        const firstName = document.getElementById('aFirst').value.trim();
        const lastName  = document.getElementById('aLast').value.trim();
        const phone     = document.getElementById('aPhone').value.trim();

        resendBtn.disabled = true;
        resendBtn.textContent = 'Sending…';

        try {
          const body = new FormData();
          body.append('action',     'send_signup_otp');
          body.append('email',      email);
          body.append('first_name', firstName);
          body.append('last_name',  lastName);
          if (phone) body.append('phone', phone);

          const res  = await fetch('../../api/admins.php', { method: 'POST', body });
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
