<?php
require_once '../../config/session.php';
// Redirect logged-in users away from this page
if (!empty($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'admin') {
        header('Location: /OGMS-Lubo-National-High-School/views/admin/dashboard.php');
    } else {
        header('Location: /OGMS-Lubo-National-High-School/views/student/dashboard.php');
    }
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Forgot Password – OGMS</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../../assets/css/style.css"/>
  <style>
    body { background: #2c3e50; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .auth-card { background: #fff; border-radius: 4px; padding: 2.5rem; width: 100%; max-width: 420px; border: 1px solid #ddd; box-shadow: 0 1px 1px rgba(0,0,0,.05); }
    .auth-logo { width: 64px; height: 64px; border-radius: 50%; background: #337ab7; display:flex; align-items:center; justify-content:center; font-size:1.8rem; color:#fff; margin: 0 auto 1.5rem; }
    .step { display: none; }
    .step.active { display: block; }
    .step-indicator { display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-bottom: 1.5rem; }
    .step-dot { width: 10px; height: 10px; border-radius: 50%; background: #ccc; transition: background .3s; }
    .step-dot.active { background: #337ab7; }
    .step-dot.done   { background: #5cb85c; }
  </style>
</head>
<body>
<div class="auth-card">
  <div class="auth-logo"><i class="fas fa-lock-open"></i></div>
  <h4 class="text-center fw-bold mb-1">Forgot Password?</h4>
  <p class="text-center text-muted mb-3" style="font-size:0.85rem">We'll send a reset link to your email.</p>

  <div class="step-indicator">
    <div class="step-dot active" id="dot1"></div>
    <div style="height:2px;width:40px;background:#e2e8f0"></div>
    <div class="step-dot" id="dot2"></div>
    <div style="height:2px;width:40px;background:#e2e8f0"></div>
    <div class="step-dot" id="dot3"></div>
  </div>

  <!-- Step 1: Enter email -->
  <div class="step active" id="step1">
    <div class="mb-3">
      <label class="form-label">Email Address</label>
      <div class="input-group">
        <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
        <input type="email" id="resetEmail" class="form-control" placeholder="your.email@example.com" autocomplete="email"/>
      </div>
    </div>
    <button class="btn btn-primary w-100" onclick="requestReset()">
      <i class="fas fa-paper-plane me-2"></i>Send Reset Link
    </button>
    <div class="text-center mt-3">
      <a href="/OGMS-Lubo-National-High-School/index.php" style="color:#1d4ed8;font-size:.875rem;text-decoration:none">
        <i class="fas fa-arrow-left me-1"></i>Back to Login
      </a>
    </div>
  </div>

  <!-- Step 2: Enter token + new password -->
  <div class="step" id="step2">
    <div class="alert alert-info py-2" style="font-size:.85rem">
      <i class="fas fa-info-circle me-1"></i>
      Check your email for the 6-digit reset code. Enter it below along with your new password.
    </div>
    <div class="mb-3">
      <label class="form-label">Reset Code</label>
      <input type="text" id="resetToken" class="form-control" placeholder="6-digit code from email" inputmode="numeric" maxlength="6"/>
    </div>
    <div class="mb-3">
      <label class="form-label">New Password</label>
      <div class="input-group">
        <span class="input-group-text"><i class="fas fa-key text-muted"></i></span>
        <input type="password" id="newPassword" class="form-control" placeholder="Min. 8 characters"/>
        <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('newPassword', this)">
          <i class="fas fa-eye"></i>
        </button>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Confirm New Password</label>
      <input type="password" id="confirmPassword" class="form-control" placeholder="Repeat new password"/>
    </div>
    <button class="btn btn-primary w-100" onclick="confirmReset()">
      <i class="fas fa-shield-alt me-2"></i>Reset Password
    </button>
    <div class="text-center mt-2">
      <button class="btn btn-link btn-sm text-muted" onclick="goToStep(1)">Go back</button>
    </div>
  </div>

  <!-- Step 3: Success -->
  <div class="step" id="step3">
    <div class="text-center py-2">
      <div style="font-size:3rem;color:#10b981;margin-bottom:1rem"><i class="fas fa-check-circle"></i></div>
      <h5 class="fw-bold mb-2">Password Reset!</h5>
      <p class="text-muted" style="font-size:.875rem">
        Your password has been changed successfully. You can now log in with your new password.
      </p>
      <a href="/OGMS-Lubo-National-High-School/index.php" class="btn btn-primary w-100 mt-2">
        <i class="fas fa-sign-in-alt me-2"></i>Back to Login
      </a>
    </div>
  </div>

  <div id="errorMsg" class="alert alert-danger mt-3 py-2" style="display:none;font-size:.85rem"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function goToStep(n) {
    [1,2,3].forEach(i => {
      document.getElementById('step'+i).classList.toggle('active', i===n);
      const dot = document.getElementById('dot'+i);
      if (i < n)       { dot.classList.remove('active'); dot.classList.add('done'); }
      else if (i === n){ dot.classList.add('active');    dot.classList.remove('done'); }
      else             { dot.classList.remove('active','done'); }
    });
    hideError();
  }

  function showError(msg) {
    const el = document.getElementById('errorMsg');
    el.textContent = msg;
    el.style.display = 'block';
  }
  function hideError() { document.getElementById('errorMsg').style.display = 'none'; }

  function togglePwd(id, btn) {
    const inp = document.getElementById(id);
    const show = inp.type === 'password';
    inp.type = show ? 'text' : 'password';
    btn.querySelector('i').className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
  }

  async function requestReset() {
    const email = document.getElementById('resetEmail').value.trim();
    if (!email) { showError('Please enter your email address.'); return; }

    const body = new FormData();
    body.append('action', 'reset_request');
    body.append('email',  email);

    try {
      const res  = await fetch('../../api/auth.php', {method:'POST', body});
      const data = await res.json();
      if (data.success) {
        goToStep(2);
      } else {
        showError(data.message || 'Email not found. Please check and try again.');
      }
    } catch(e) { showError('Server error. Please try again later.'); }
  }

  async function confirmReset() {
    const token    = document.getElementById('resetToken').value.trim();
    const password = document.getElementById('newPassword').value;
    const confirm  = document.getElementById('confirmPassword').value;

    if (!token)             { showError('Please enter the reset code.'); return; }
    if (password.length < 8){ showError('Password must be at least 8 characters.'); return; }
    if (password !== confirm){ showError('Passwords do not match.'); return; }

    const body = new FormData();
    body.append('action',   'reset_confirm');
    body.append('token',    token);
    body.append('password', password);

    try {
      const res  = await fetch('../../api/auth.php', {method:'POST', body});
      const data = await res.json();
      if (data.success) {
        goToStep(3);
      } else {
        showError(data.message || 'Invalid or expired reset token.');
      }
    } catch(e) { showError('Server error. Please try again later.'); }
  }
</script>
</body>
</html>
