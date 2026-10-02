<?php
// test-mail.php — Comprehensive Diagnostic & SMTP Verification Tool
require_once 'config/db.php';
require_once 'config/mailer.php';

$envPath = __DIR__ . '/.env';
$vendorPath = __DIR__ . '/vendor/autoload.php';

$envExists = file_exists($envPath);
$vendorExists = file_exists($vendorPath);
$phpMailerLoaded = class_exists('PHPMailer\PHPMailer\PHPMailer');
$opensslLoaded = extension_loaded('openssl');
$smtpUserConfigured = defined('SMTP_USER') && !empty(SMTP_USER);
$smtpPassConfigured = defined('SMTP_PASS') && !empty(SMTP_PASS);

$result = null;
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = trim($_POST['to'] ?? '');
    if ($to && filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $ok = sendMail(
            $to,
            'Test Recipient',
            'OGMS SMTP Diagnostic Test',
            '<div style="font-family:sans-serif;padding:20px;border:1px solid #e2e8f0;border-radius:8px;">' .
            '<h2 style="color:#0284c7;">OGMS SMTP Test – Lubo National High School</h2>' .
            '<p>If you are reading this email, your Gmail SMTP configuration is <strong>100% active and working</strong>.</p>' .
            '<p style="color:#64748b;font-size:12px;">Sent at: ' . date('Y-m-d H:i:s') . '</p>' .
            '</div>'
        );
        $result = $ok ? 'success' : 'fail';
        if (!$ok) {
            $errorMsg = getMailerLastError();
        }
    } else {
        $result = 'invalid';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>SMTP &amp; Email Diagnostics – OGMS</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    body { background: #f8fafc; font-family: system-ui, -apple-system, sans-serif; }
    .diag-card { max-width: 680px; margin: 40px auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); padding: 30px; }
    .status-badge { font-size: 0.82rem; padding: 4px 10px; border-radius: 20px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="diag-card">
      <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
        <div style="width:48px;height:48px;border-radius:50%;background:#0284c7;color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px;">
          <i class="fas fa-envelope-open-text"></i>
        </div>
        <div>
          <h4 class="mb-0 fw-bold">OGMS Email &amp; SMTP Diagnostics</h4>
          <small class="text-muted">Verify environment configuration and test OTP email dispatch</small>
        </div>
      </div>

      <!-- Environment Health Checklist -->
      <h6 class="fw-bold mb-2 text-uppercase text-secondary" style="font-size:0.75rem;letter-spacing:0.5px">Environment Health Checklist</h6>
      <ul class="list-group mb-4" style="font-size:0.9rem">
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <i class="fas fa-file-code me-2 <?= $envExists ? 'text-success' : 'text-danger' ?>"></i>
            <strong>.env Configuration File</strong>
            <div class="text-muted" style="font-size:0.78rem">Contains secret SMTP and DB credentials (ignored by Git)</div>
          </div>
          <span class="badge <?= $envExists ? 'bg-success' : 'bg-danger' ?> status-badge">
            <?= $envExists ? 'Found (.env present)' : 'MISSING (.env missing)' ?>
          </span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <i class="fas fa-folder me-2 <?= $vendorExists ? 'text-success' : 'text-danger' ?>"></i>
            <strong>Composer Vendor Directory (`vendor/autoload.php`)</strong>
            <div class="text-muted" style="font-size:0.78rem">PHPMailer vendor library dependencies</div>
          </div>
          <span class="badge <?= $vendorExists ? 'bg-success' : 'bg-danger' ?> status-badge">
            <?= $vendorExists ? 'Found (vendor present)' : 'MISSING (run composer install)' ?>
          </span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <i class="fas fa-lock me-2 <?= $opensslLoaded ? 'text-success' : 'text-danger' ?>"></i>
            <strong>PHP OpenSSL Extension</strong>
            <div class="text-muted" style="font-size:0.78rem">Required for Gmail SMTP TLS encrypted connection</div>
          </div>
          <span class="badge <?= $opensslLoaded ? 'bg-success' : 'bg-danger' ?> status-badge">
            <?= $opensslLoaded ? 'Enabled' : 'Disabled in php.ini' ?>
          </span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <i class="fas fa-at me-2 <?= $smtpUserConfigured ? 'text-success' : 'text-danger' ?>"></i>
            <strong>SMTP_USER (Sender Gmail)</strong>
            <div class="text-muted" style="font-size:0.78rem"><?= defined('SMTP_USER') && SMTP_USER ? htmlspecialchars(SMTP_USER) : 'Not configured' ?></div>
          </div>
          <span class="badge <?= $smtpUserConfigured ? 'bg-success' : 'bg-danger' ?> status-badge">
            <?= $smtpUserConfigured ? 'Configured' : 'Empty' ?>
          </span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <i class="fas fa-key me-2 <?= $smtpPassConfigured ? 'text-success' : 'text-danger' ?>"></i>
            <strong>SMTP_PASS (Gmail 16-char App Password)</strong>
            <div class="text-muted" style="font-size:0.78rem">
              <?php if ($smtpPassConfigured): ?>
                <?php
                  $rawLen = strlen(SMTP_PASS);
                  $cleanLen = strlen(preg_replace('/\s+/', '', (string)SMTP_PASS));
                ?>
                •••••••••••••••• (<?= $cleanLen ?> chars clean)
                <?php if ($rawLen !== $cleanLen): ?>
                  <span class="badge bg-info text-dark ms-1">Spaces auto-stripped (was <?= $rawLen ?> chars)</span>
                <?php endif; ?>
              <?php else: ?>
                Not configured
              <?php endif; ?>
            </div>
          </div>
          <span class="badge <?= $smtpPassConfigured ? 'bg-success' : 'bg-danger' ?> status-badge">
            <?= $smtpPassConfigured ? 'Configured' : 'Empty' ?>
          </span>
        </li>
      </ul>

      <!-- Missing Setup Warning Instructions -->
      <?php if (!$envExists || !$smtpUserConfigured || !$smtpPassConfigured || !$vendorExists): ?>
        <div class="alert alert-warning p-3 mb-4" style="font-size:0.85rem">
          <h6 class="fw-bold text-dark mb-1"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Action Needed for Cloned Devices:</h6>
          <ol class="mb-0 ps-3">
            <?php if (!$envExists): ?>
              <li><strong>Create `.env` file:</strong> In the project root, copy <code>.env.example</code> to <code>.env</code>.</li>
            <?php endif; ?>
            <?php if (!$smtpUserConfigured || !$smtpPassConfigured): ?>
              <li><strong>Add Gmail App Password:</strong> Set <code>SMTP_USER=your_gmail@gmail.com</code> and <code>SMTP_PASS=your_16_digit_app_password</code> inside <code>.env</code>.</li>
            <?php endif; ?>
            <?php if (!$vendorExists): ?>
              <li><strong>Install Composer Dependencies:</strong> Open a terminal in the project folder and run <code>composer install</code>, or copy the <code>vendor</code> directory from your other laptop.</li>
            <?php endif; ?>
          </ol>
        </div>
      <?php endif; ?>

      <!-- Results Banner -->
      <?php if ($result === 'success'): ?>
        <div class="alert alert-success d-flex align-items-center gap-2">
          <i class="fas fa-check-circle fs-5"></i>
          <div><strong>Success!</strong> Test email dispatched successfully. Check your inbox and spam folder.</div>
        </div>
      <?php elseif ($result === 'fail'): ?>
        <div class="alert alert-danger">
          <div class="d-flex align-items-center gap-2 mb-1">
            <i class="fas fa-times-circle fs-5"></i>
            <strong>Failed to send test email.</strong>
          </div>
          <div class="small bg-white p-2 border rounded mt-2 text-dark font-monospace">
            <?= htmlspecialchars($errorMsg ?: 'Unknown error. Check Apache and PHP error logs.') ?>
          </div>

          <?php if (stripos($errorMsg, 'authenticate') !== false): ?>
            <div class="mt-3 p-2 bg-white rounded border border-danger-subtle small text-dark">
              <strong class="text-danger"><i class="fas fa-lightbulb me-1"></i>How to resolve "Could not authenticate":</strong>
              <ul class="mb-1 mt-1 ps-3">
                <li><strong>Remove spaces from <code>SMTP_PASS</code> in <code>.env</code>:</strong> Google shows App Passwords as <code>xxxx xxxx xxxx xxxx</code> (19 chars). Make sure it is written without spaces: <code>SMTP_PASS=xxxxxxxxxxxxxxxx</code> (16 chars).</li>
                <li><strong>Must be a Google "App Password", not your normal login password:</strong> Standard Gmail passwords are blocked by Google for SMTP.</li>
                <li><strong>Generate a fresh App Password:</strong> Go to <a href="https://myaccount.google.com/apppasswords" target="_blank" class="fw-semibold">Google Account &rarr; Security &rarr; 2-Step Verification &rarr; App Passwords</a>, create a new one named "OGMS Laptop", and paste the 16 characters into your <code>.env</code>.</li>
              </ul>
            </div>
          <?php endif; ?>
        </div>
      <?php elseif ($result === 'invalid'): ?>
        <div class="alert alert-warning">Please enter a valid recipient email address.</div>
      <?php endif; ?>

      <!-- Test Dispatch Form -->
      <form method="post" class="mt-3">
        <label class="form-label fw-semibold">Send Test Email To:</label>
        <div class="input-group mb-3">
          <span class="input-group-text"><i class="fas fa-paper-plane text-muted"></i></span>
          <input type="email" name="to" class="form-control" placeholder="your_personal_email@gmail.com" required value="<?= htmlspecialchars($_POST['to'] ?? '') ?>"/>
          <button type="submit" class="btn btn-primary px-4" style="background:#0284c7;border-color:#0284c7">
            <i class="fas fa-paper-plane me-1"></i>Test SMTP
          </button>
        </div>
      </form>

      <div class="text-center mt-3 pt-3 border-top">
        <a href="login.php" class="text-decoration-none text-muted small me-3"><i class="fas fa-arrow-left me-1"></i>Back to Login</a>
        <a href="views/student/signup.php" class="text-decoration-none small text-primary"><i class="fas fa-user-plus me-1"></i>Go to Student Sign Up</a>
      </div>
    </div>
  </div>
</body>
</html>
