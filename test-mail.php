<?php
// test-mail.php — temporary page to verify Gmail SMTP config. Delete when done testing.
require_once 'config/mailer.php';

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = trim($_POST['to'] ?? '');
    if ($to && filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $ok = sendMail(
            $to,
            'Test Recipient',
            'OGMS SMTP Test',
            '<p>This is a test email from OGMS - Lubo National High School.</p><p>If you received this, Gmail SMTP is configured correctly.</p>'
        );
        $result = $ok ? 'success' : 'fail';
    } else {
        $result = 'invalid';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>SMTP Test</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 480px; margin: 60px auto; padding: 0 20px; }
    input, button { font-size: 1rem; padding: 8px; }
    input[type=email] { width: 100%; box-sizing: border-box; margin-bottom: 10px; }
    .msg { padding: 10px; border-radius: 6px; margin-bottom: 15px; }
    .success { background: #d1fae5; color: #065f46; }
    .fail    { background: #fee2e2; color: #991b1b; }
    .invalid { background: #fef3c7; color: #92400e; }
  </style>
</head>
<body>
  <h3>Gmail SMTP Test</h3>
  <?php if ($result === 'success'): ?>
    <div class="msg success">Email sent successfully. Check the inbox (and spam folder).</div>
  <?php elseif ($result === 'fail'): ?>
    <div class="msg fail">Failed to send. Check the PHP error log for details.</div>
  <?php elseif ($result === 'invalid'): ?>
    <div class="msg invalid">Please enter a valid email address.</div>
  <?php endif; ?>

  <form method="post">
    <input type="email" name="to" placeholder="Send test email to..." required/>
    <button type="submit">Send Test Email</button>
  </form>
</body>
</html>
