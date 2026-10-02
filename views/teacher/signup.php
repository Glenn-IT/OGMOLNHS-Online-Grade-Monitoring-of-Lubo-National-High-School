<?php
// views/teacher/signup.php — Teacher Self-Registration Portal
require_once '../../config/session.php';

// Redirect logged in users away from signup
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
    <title>Teacher Registration – OGMS | Lubo National High School</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../../assets/css/style.css") ?>" />
    <style>
      .auth-card {
        max-width: 540px;
        margin: 30px auto;
      }
      .school-logo-teacher {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: #0284c7;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        margin: 0 auto 12px;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
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
          <i class="fas fa-arrow-left"></i> Back to Login Portal
        </a>

        <div class="school-logo-teacher"><i class="fas fa-chalkboard-teacher"></i></div>
        <h2>Teacher Registration</h2>
        <p class="subtitle">Apply for a faculty account at Lubo National High School</p>

        <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-start gap-2" style="font-size:0.82rem;border-left:4px solid #0284c7;">
          <i class="fas fa-info-circle text-primary mt-1"></i>
          <div>
            <strong>Registration Workflow:</strong> An email OTP code will verify your identity. Once submitted, your account will be <strong>queued for administrator review and approval</strong> before portal access is activated.
          </div>
        </div>

        <form id="teacherSignupForm" onsubmit="handleTeacherSignup(event)">
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label">First Name <span class="text-danger">*</span></label>
              <input type="text" id="tFirst" class="form-control" placeholder="Maria" required />
            </div>
            <div class="col-6">
              <label class="form-label">Last Name <span class="text-danger">*</span></label>
              <input type="text" id="tLast" class="form-control" placeholder="Santos" required />
            </div>
            <div class="col-12">
              <label class="form-label">Email Address <span class="text-danger">*</span></label>
              <div class="input-group has-validation">
                <input type="email" id="tEmail" class="form-control" placeholder="teacher@gmail.com" required
                  autocomplete="username" />
                <span class="input-group-text" id="tEmailIcon" style="display:none"></span>
              </div>
              <div id="tEmailFeedback" style="font-size:0.78rem;margin-top:0.25rem;display:none"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Mobile Number</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-phone text-muted"></i></span>
                <input type="text" id="tPhone" class="form-control" placeholder="09XXXXXXXXX" maxlength="11" pattern="^09\d{9}$" />
              </div>
              <small class="text-muted" style="font-size:0.74rem">11 digits, starts with 09</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Gender</label>
              <select id="tGender" class="form-select">
                <option value="Female">Female</option>
                <option value="Male">Male</option>
                <option value="Other">Other / Prefer not to say</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="password" id="tPwd" class="form-control" placeholder="Min. 6 characters" minlength="6" required autocomplete="new-password"/>
                <button type="button" class="btn btn-outline-secondary"
                  onclick="togglePwd('tPwd',this)" style="border-radius:0 8px 8px 0">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="password" id="tPwd2" class="form-control" placeholder="Repeat password" minlength="6" required autocomplete="new-password"/>
                <button type="button" class="btn btn-outline-secondary"
                  onclick="togglePwd('tPwd2',this)" style="border-radius:0 8px 8px 0">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Residential Address</label>
              <input type="text" id="tAddress" class="form-control" placeholder="e.g. Lubo, Tanudan, Kalinga" />
            </div>
            <div class="col-12">
              <div class="form-check">
                <input type="checkbox" class="form-check-input" id="agreeTerms" required />
                <label class="form-check-label" style="font-size:0.82rem">
                  I certify that all provided details are true and correct, and I agree to the school's privacy and grading policy.
                </label>
              </div>
            </div>
          </div>

          <button type="submit" class="btn-primary-custom w-100 mt-3" id="signupBtn" style="background:#0284c7">
            <i class="fas fa-user-plus me-2"></i>Verify Email &amp; Submit Registration
          </button>
        </form>

        <div class="text-center mt-3 pt-2 border-top">
          <span style="font-size:0.85rem;color:#64748b">Already have a faculty account? </span>
          <a href="../../login.php" style="color:#0284c7;font-weight:600;font-size:0.85rem">Sign In</a>
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
            <h4 class="fw-bold mb-1" style="color: #0c1326;">Verify Your Email</h4>
            <p class="text-muted mb-3" style="font-size: 0.88rem;">
              We sent a 6-digit verification code to<br/>
              <strong id="otpTargetEmail" style="color: #0284c7;">—</strong>
            </p>

            <form id="otpForm" onsubmit="verifyOtpAndRegisterTeacher(event)">
              <div class="mb-3">
                <input type="text" id="otpInput" class="form-control text-center fw-bold"
                       placeholder="123456" maxlength="6" pattern="\d{6}" required
                       style="font-size: 1.7rem; letter-spacing: 10px; border: 2px solid #cbd5e1; border-radius: 12px; height: 58px;"
                       autocomplete="one-time-code"/>
                <small class="text-muted d-block mt-2" style="font-size: 0.78rem;">Please check your Inbox or Spam folder</small>
              </div>

              <button type="submit" class="btn-primary-custom w-100 mb-2" id="verifyOtpBtn" style="background:#0284c7;height:46px;">
                <i class="fas fa-check-circle me-2"></i>Verify &amp; Submit Application
              </button>
            </form>

            <div class="mt-3" style="font-size: 0.84rem;">
              <span class="text-muted">Didn't receive the email? </span>
              <button type="button" class="btn btn-link p-0 text-decoration-none" id="resendOtpBtn" onclick="resendTeacherOtp()" style="font-size: 0.84rem; font-weight: 600; color: #0284c7;">Resend Code</button>
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

    <!-- Registration Completed / Pending Approval Modal -->
    <div class="modal fade" id="pendingApprovalModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content text-center p-4 border-0 shadow">
          <div class="modal-body">
            <div style="width: 76px; height: 76px; border-radius: 50%; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; font-size: 34px;">
              <i class="fas fa-clock"></i>
            </div>
            <h3 class="fw-bold mb-2" style="color: #0c1326;">Registration Submitted!</h3>
            <p class="text-muted mb-3" style="font-size: 0.92rem; line-height: 1.5;">
              Thank you, <strong id="registeredTeacherName">—</strong>.<br/>
              Your email has been successfully verified, and your faculty registration is now <span class="badge bg-warning text-dark px-2 py-1">Pending Admin Approval</span>.
            </p>

            <div class="alert alert-light border text-start p-3 mb-3" style="font-size: 0.84rem; color: #475569;">
              <div class="d-flex align-items-start gap-2">
                <i class="fas fa-info-circle text-primary mt-1"></i>
                <div>
                  <strong>Next Steps:</strong> An administrator will review your application and activate your account. You will receive an approval confirmation via email, after which you can log in using your registered credentials.
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

      function validateTeacherEmail() {
        const input    = document.getElementById('tEmail');
        const icon     = document.getElementById('tEmailIcon');
        const feedback = document.getElementById('tEmailFeedback');
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

      document.getElementById('tEmail').addEventListener('input', validateTeacherEmail);

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

      async function handleTeacherSignup(e) {
        e.preventDefault();

        if (!validateTeacherEmail()) {
          showToast('Please enter a valid email address.', 'error');
          document.getElementById('tEmail').focus();
          return;
        }

        const pwd  = document.getElementById('tPwd').value;
        const pwd2 = document.getElementById('tPwd2').value;

        if (pwd !== pwd2) {
          showToast('Passwords do not match!', 'error');
          return;
        }
        if (pwd.length < 6) {
          showToast('Password must be at least 6 characters.', 'error');
          return;
        }

        const phone = document.getElementById('tPhone').value.trim();
        if (phone && !/^09\d{9}$/.test(phone)) {
          showToast('Phone number must be an 11-digit mobile number starting with 09.', 'error');
          return;
        }

        const email     = document.getElementById('tEmail').value.trim().toLowerCase();
        const firstName = document.getElementById('tFirst').value.trim();
        const lastName  = document.getElementById('tLast').value.trim();

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

          const res  = await fetch('../../api/teachers.php', { method: 'POST', body });
          const data = await res.json();

          btn.disabled  = false;
          btn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Verify Email &amp; Submit Registration';

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
          btn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Verify Email &amp; Submit Registration';
          showToast('Server error while sending OTP. Please try again.', 'error');
        }
      }

      async function verifyOtpAndRegisterTeacher(e) {
        e.preventDefault();
        const otp = document.getElementById('otpInput').value.trim();
        if (!otp || !/^\d{6}$/.test(otp)) {
          showToast('Please enter the 6-digit OTP code.', 'error');
          return;
        }

        const verifyBtn = document.getElementById('verifyOtpBtn');
        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting application…';

        const firstName = document.getElementById('tFirst').value.trim();
        const lastName  = document.getElementById('tLast').value.trim();
        const email     = document.getElementById('tEmail').value.trim().toLowerCase();
        const password  = document.getElementById('tPwd').value;
        const phone     = document.getElementById('tPhone').value.trim();
        const gender    = document.getElementById('tGender').value;
        const address   = document.getElementById('tAddress').value.trim();

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

          const res  = await fetch('../../api/teachers.php', { method: 'POST', body });
          const data = await res.json();

          if (data.success) {
            getOtpModal().hide();
            document.getElementById('registeredTeacherName').textContent = `${firstName} ${lastName}`;
            getApprovalModal().show();
          } else {
            showToast(data.message || 'Registration failed.', 'error');
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Verify &amp; Submit Application';
          }
        } catch (err) {
          showToast('Server error. Please try again.', 'error');
          verifyBtn.disabled = false;
          verifyBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Verify &amp; Submit Application';
        }
      }

      async function resendTeacherOtp() {
        const resendBtn = document.getElementById('resendOtpBtn');
        if (resendBtn.disabled) return;

        const email     = document.getElementById('tEmail').value.trim().toLowerCase();
        const firstName = document.getElementById('tFirst').value.trim();
        const lastName  = document.getElementById('tLast').value.trim();
        const phone     = document.getElementById('tPhone').value.trim();

        resendBtn.disabled = true;
        resendBtn.textContent = 'Sending…';

        try {
          const body = new FormData();
          body.append('action',     'send_signup_otp');
          body.append('email',      email);
          body.append('first_name', firstName);
          body.append('last_name',  lastName);
          if (phone) body.append('phone', phone);

          const res  = await fetch('../../api/teachers.php', { method: 'POST', body });
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
