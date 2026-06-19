<?php
define('CURRENT_VERSION', 'v1.03');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Under Construction – OGMS</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #f0f4ff 0%, #e8edf9 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .uc-card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.10);
      padding: 52px 48px 44px;
      max-width: 480px;
      width: 90%;
      text-align: center;
    }

    .uc-icon {
      font-size: 64px;
      color: #f59e0b;
      margin-bottom: 20px;
      line-height: 1;
    }

    .uc-badge {
      display: inline-block;
      background: #eff6ff;
      color: #2563eb;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 1px;
      text-transform: uppercase;
      border-radius: 999px;
      padding: 4px 14px;
      margin-bottom: 18px;
      border: 1.5px solid #bfdbfe;
    }

    .uc-title {
      font-size: 26px;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 12px;
    }

    .uc-desc {
      font-size: 15px;
      color: #64748b;
      line-height: 1.65;
      margin-bottom: 32px;
    }

    .uc-version {
      display: inline-block;
      background: #fef9c3;
      color: #92400e;
      font-size: 13px;
      font-weight: 600;
      border-radius: 8px;
      padding: 5px 14px;
      margin-bottom: 28px;
      border: 1px solid #fde68a;
    }

    .uc-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #2563eb;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 11px 26px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.18s;
    }

    .uc-btn:hover { background: #1d4ed8; }
  </style>
</head>
<body>
  <div class="uc-card">
    <div class="uc-icon"><i class="fa-solid fa-helmet-safety"></i></div>
    <div class="uc-badge">Current Version: <?php echo CURRENT_VERSION; ?></div>
    <h1 class="uc-title">Under Construction</h1>
    <p class="uc-desc">
      This page is not yet available in the current version of the system.
      It will be unlocked in a future release.
    </p>
    <div class="uc-version">
      <i class="fa-solid fa-clock"></i>&nbsp; Coming in a future version
    </div>
    <br />
    <a href="javascript:history.back()" class="uc-btn">
      <i class="fa-solid fa-arrow-left"></i> Go Back
    </a>
  </div>
</body>
</html>
<?php exit; ?>
