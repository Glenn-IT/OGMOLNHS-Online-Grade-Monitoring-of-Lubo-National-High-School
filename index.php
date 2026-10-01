<?php
// index.php — Lubo National High School Official Homepage (Guest Landing Page)
require_once 'config/session.php';
require_once 'config/db.php';
require_once 'config/school-year.php';

// Check if user is logged in
$isLoggedIn = !empty($_SESSION['user_id']);
$userRole   = $_SESSION['role'] ?? '';
$userName   = $_SESSION['full_name'] ?? '';

$dashboardUrl = match($userRole) {
    'admin'   => 'views/admin/dashboard.php',
    'teacher' => 'views/teacher/dashboard.php',
    default   => 'views/student/dashboard.php',
};

$syLabel       = '2024-2025';
$announcements = [];
$events        = [];
$highlights    = [];
$dbError       = null;

try {
    $pdo = getDB();
    $syLabel = activeSchoolYearLabel($pdo);

    // Fetch published posts directly for instant initial render
    $stmtAnn = $pdo->prepare("SELECT * FROM school_posts WHERE type = 'announcement' AND is_active = 1 ORDER BY COALESCE(event_date, created_at) DESC, id DESC LIMIT 6");
    $stmtAnn->execute();
    $announcements = $stmtAnn->fetchAll();

    $stmtEve = $pdo->prepare("SELECT * FROM school_posts WHERE type = 'event' AND is_active = 1 ORDER BY COALESCE(event_date, created_at) ASC, id DESC LIMIT 6");
    $stmtEve->execute();
    $events = $stmtEve->fetchAll();

    $stmtHl = $pdo->prepare("SELECT * FROM school_posts WHERE type = 'highlight' AND is_active = 1 ORDER BY COALESCE(event_date, created_at) DESC, id DESC LIMIT 6");
    $stmtHl->execute();
    $highlights = $stmtHl->fetchAll();
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

function fmtEventDate(?string $d): array {
    if (!$d) return ['month' => 'LNHS', 'day' => '—', 'year' => ''];
    $ts = strtotime($d);
    return [
        'month' => strtoupper(date('M', $ts)),
        'day'   => date('d', $ts),
        'year'  => date('Y', $ts),
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Lubo National High School – Official Website &amp; Portal</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . "/assets/css/style.css") ?>"/>
  <style>
    /* Homepage Specific Layout Styles */
    :root {
      --navy-dark: #0c1326;
      --navy-blue: #1e3a8a;
      --sky-blue: #0284c7;
    }
    html {
      scroll-behavior: smooth;
    }
    body {
      background-color: #f8fafc;
      color: #334155;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    .school-navbar {
      background-color: #0c1326;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      padding: 12px 0;
      position: sticky;
      top: 0;
      z-index: 1050;
    }
    .school-navbar .navbar-brand {
      color: #fff;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
    }
    .school-navbar .nav-link {
      color: #cbd5e1;
      font-weight: 500;
      font-size: 0.92rem;
      padding: 8px 14px;
      transition: color 0.15s ease;
    }
    .school-navbar .nav-link:hover {
      color: #38bdf8;
    }
    .hero-section {
      background: linear-gradient(135deg, rgba(12, 19, 38, 0.95) 0%, rgba(30, 58, 138, 0.9) 100%),
                  url('assets/images/lubo_logo.png') center/contain no-repeat;
      color: #fff;
      padding: 90px 0 80px;
      text-align: center;
      position: relative;
    }
    .hero-badge {
      display: inline-block;
      background: rgba(255,255,255,0.12);
      border: 1px solid rgba(255,255,255,0.25);
      border-radius: 50px;
      padding: 6px 18px;
      font-size: 0.85rem;
      margin-bottom: 20px;
      letter-spacing: 0.5px;
    }
    .hero-title {
      font-size: 2.8rem;
      font-weight: 800;
      letter-spacing: -0.5px;
      margin-bottom: 16px;
      line-height: 1.2;
    }
    .hero-subtitle {
      font-size: 1.15rem;
      max-width: 760px;
      margin: 0 auto 32px;
      color: #e2e8f0;
      line-height: 1.6;
    }
    .stat-pill-box {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 10px 25px -5px rgba(0,0,0,0.08);
      margin-top: -45px;
      position: relative;
      z-index: 10;
      padding: 24px;
    }
    .section-title-wrap {
      text-align: center;
      margin-bottom: 45px;
    }
    .section-title-wrap .sub-badge {
      text-transform: uppercase;
      font-size: 0.78rem;
      font-weight: 700;
      letter-spacing: 1px;
      color: #0284c7;
      margin-bottom: 8px;
      display: block;
    }
    .section-title-wrap h2 {
      font-size: 2.2rem;
      font-weight: 800;
      color: #0c1326;
      letter-spacing: -0.5px;
    }
    .post-card {
      background: #fff;
      border-radius: 12px;
      border: 1px solid #e2e8f0;
      overflow: hidden;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    .post-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 24px rgba(0,0,0,0.07);
      border-color: #cbd5e1;
    }
    .event-calendar-badge {
      background: #0c1326;
      color: #fff;
      border-radius: 8px;
      padding: 10px 14px;
      text-align: center;
      min-width: 65px;
    }
    .event-calendar-badge .m {
      font-size: 0.72rem;
      font-weight: 700;
      color: #38bdf8;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .event-calendar-badge .d {
      font-size: 1.4rem;
      font-weight: 800;
      line-height: 1;
    }
    .footer-section {
      background-color: #0c1326;
      color: #cbd5e1;
      padding: 60px 0 25px;
      border-top: 4px solid #1e3a8a;
    }
    .footer-section h5 {
      color: #fff;
      font-weight: 700;
      font-size: 1.05rem;
      margin-bottom: 18px;
    }
    .footer-section a {
      color: #94a3b8;
      text-decoration: none;
      transition: color 0.15s ease;
    }
    .footer-section a:hover {
      color: #38bdf8;
    }
  </style>
</head>
<body>
<?php if (!empty($dbError)): ?>
  <div style="background:#fff3cd;color:#856404;padding:12px 20px;font-size:0.88rem;border-bottom:1px solid #ffeeba;text-align:center;">
    <i class="fas fa-exclamation-triangle me-1"></i>
    <strong>Database Setup Notice:</strong> <?= htmlspecialchars($dbError) ?>.
    <span class="ms-2">Please ensure MySQL is running in XAMPP and import <code>database/ogms_schema.sql</code> into phpMyAdmin.</span>
  </div>
<?php endif; ?>

  <!-- Top Announcement Bar -->
  <div style="background:#1e3a8a;color:#e0f2fe;font-size:0.8rem;padding:6px 0;text-align:center;">
    <div class="container">
      <i class="fas fa-bullhorn me-2"></i>Welcome to Lubo National High School Online Grade Monitoring System &bull; Active Academic Year: <strong><?= htmlspecialchars($syLabel) ?></strong>
    </div>
  </div>

  <!-- Main School Navbar -->
  <nav class="navbar navbar-expand-lg school-navbar">
    <div class="container">
      <a class="navbar-brand" href="index.php">
        <img src="assets/images/deped_logo.png" alt="DepEd Logo" style="height:44px;width:auto;"/>
        <img src="assets/images/lubo_logo.png" alt="Lubo NHS Logo" style="height:44px;width:auto;"/>
        <div>
          <div style="font-size:1.05rem;line-height:1.2;">LUBO NATIONAL HIGH SCHOOL</div>
          <div style="font-size:0.75rem;color:#94a3b8;font-weight:normal;">Department of Education &bull; Region II</div>
        </div>
      </a>

      <button class="navbar-toggler text-white border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
        <i class="fas fa-bars"></i>
      </button>

      <div class="collapse navbar-collapse" id="navMenu">
        <ul class="navbar-nav ms-auto align-items-center mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link" href="#hero">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="#about">About LNHS</a></li>
          <li class="nav-item"><a class="nav-link" href="#announcements">Announcements</a></li>
          <li class="nav-item"><a class="nav-link" href="#events">Events</a></li>
          <li class="nav-item"><a class="nav-link" href="#highlights">Highlights</a></li>
          <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>

          <?php if ($isLoggedIn): ?>
            <li class="nav-item ms-lg-3">
              <a href="<?= htmlspecialchars($dashboardUrl) ?>" class="btn btn-primary btn-sm px-3 py-2 rounded-pill">
                <i class="fas fa-tachometer-alt me-1"></i>My Dashboard (<?= htmlspecialchars(ucfirst($userRole)) ?>)
              </a>
            </li>
            <li class="nav-item ms-2">
              <a href="logout.php" class="btn btn-outline-light btn-sm px-3 py-2 rounded-pill" title="Sign Out">
                <i class="fas fa-sign-out-alt"></i>
              </a>
            </li>
          <?php else: ?>
            <li class="nav-item ms-lg-3">
              <a href="views/student/signup.php" class="btn btn-outline-light btn-sm px-3 py-2 rounded-pill">
                <i class="fas fa-user-plus me-1"></i>Sign Up
              </a>
            </li>
            <li class="nav-item ms-2">
              <a href="login.php" class="btn btn-primary btn-sm px-3 py-2 rounded-pill" style="background:#0284c7;border-color:#0284c7">
                <i class="fas fa-sign-in-alt me-1"></i>Portal Login
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Hero Banner -->
  <section class="hero-section" id="hero">
    <div class="container">
      <span class="hero-badge">
        <i class="fas fa-graduation-cap me-1"></i> Republic of the Philippines &bull; Schools Division of Cagayan
      </span>
      <h1 class="hero-title">Empowering Minds, Shaping Futures</h1>
      <p class="hero-subtitle">
        Welcome to Lubo National High School — providing inclusive, accessible, and high-standard basic secondary education for Junior and Senior High School learners.
      </p>
      <div class="d-flex justify-content-center gap-3 flex-wrap">
        <?php if ($isLoggedIn): ?>
          <a href="<?= htmlspecialchars($dashboardUrl) ?>" class="btn btn-primary btn-lg px-4 py-2 rounded-pill shadow">
            <i class="fas fa-tachometer-alt me-2"></i>Go to My Portal Dashboard
          </a>
        <?php else: ?>
          <a href="login.php" class="btn btn-primary btn-lg px-4 py-2 rounded-pill shadow" style="background:#0284c7;border-color:#0284c7">
            <i class="fas fa-sign-in-alt me-2"></i>Access Grade Monitoring Portal
          </a>
          <a href="views/student/signup.php" class="btn btn-outline-light btn-lg px-4 py-2 rounded-pill">
            <i class="fas fa-user-plus me-2"></i>Student Registration
          </a>
        <?php endif; ?>
        <a href="#announcements" class="btn btn-light btn-lg px-4 py-2 rounded-pill text-dark">
          <i class="fas fa-bullhorn me-2 text-primary"></i>Latest News
        </a>
      </div>
    </div>
  </section>

  <!-- Statistics Banner -->
  <div class="container">
    <div class="stat-pill-box">
      <div class="row g-4 text-center">
        <div class="col-6 col-md-3">
          <div class="h2 fw-bold mb-1" style="color:#0284c7;">7 to 12</div>
          <div class="text-muted" style="font-size:0.88rem;font-weight:600;">Junior &amp; Senior High</div>
        </div>
        <div class="col-6 col-md-3">
          <div class="h2 fw-bold mb-1" style="color:#16a34a;">100%</div>
          <div class="text-muted" style="font-size:0.88rem;font-weight:600;">DepEd Curriculum</div>
        </div>
        <div class="col-6 col-md-3">
          <div class="h2 fw-bold mb-1" style="color:#d97706;">DepEd SF9</div>
          <div class="text-muted" style="font-size:0.88rem;font-weight:600;">Digital Grade Reporting</div>
        </div>
        <div class="col-6 col-md-3">
          <div class="h2 fw-bold mb-1" style="color:#9333ea;">SMS Alerts</div>
          <div class="text-muted" style="font-size:0.88rem;font-weight:600;">Automated Grade Delivery</div>
        </div>
      </div>
    </div>
  </div>

  <!-- About Us Section -->
  <section class="py-5 mt-4" id="about">
    <div class="container py-4">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6">
          <span class="text-primary fw-bold text-uppercase" style="font-size:0.85rem;letter-spacing:1px;">About Our School</span>
          <h2 class="fw-bold mt-1 mb-3" style="color:#0c1326;font-size:2.3rem;">Committed to Quality Basic Education</h2>
          <p class="text-secondary" style="line-height:1.7;">
            Lubo National High School stands as a pillar of academic learning and character development in Sto. Niño, Cagayan. Guided by the Department of Education, our school fosters an inspiring environment that prepares every learner for higher education, vocational training, and responsible community leadership.
          </p>
          <div class="row g-3 mt-2">
            <div class="col-sm-6">
              <div class="p-3 rounded border bg-white h-100">
                <h6 class="fw-bold mb-2 text-primary"><i class="fas fa-eye me-2"></i>DepEd Vision</h6>
                <p class="text-muted mb-0" style="font-size:0.82rem;line-height:1.5;">
                  We dream of Filipinos who passionately love their country and whose values and competencies enable them to realize their full potential.
                </p>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="p-3 rounded border bg-white h-100">
                <h6 class="fw-bold mb-2 text-success"><i class="fas fa-bullseye me-2"></i>DepEd Mission</h6>
                <p class="text-muted mb-0" style="font-size:0.82rem;line-height:1.5;">
                  To protect and promote the right of every Filipino to quality, equitable, culture-based, and complete basic education.
                </p>
              </div>
            </div>
          </div>
          <div class="mt-4 p-3 rounded" style="background:#f1f5f9;border-left:4px solid #0284c7;">
            <strong>Core Values:</strong> <em>Maka-Diyos, Makatao, Makakalikasan, at Makabansa</em>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="p-4 bg-white rounded-3 shadow-sm border text-center">
            <img src="assets/images/lubo_logo.png" alt="LNHS Seal" style="max-width:180px;height:auto;margin-bottom:20px;"/>
            <h4 class="fw-bold" style="color:#0c1326;">Online Grade Monitoring System</h4>
            <p class="text-muted" style="font-size:0.9rem;">
              The LNHS OGMS platform bridges school faculty, learners, and parents with real-time grade monitoring, DepEd School Form 9 (SF9) generation, and instant PhilSMS notifications.
            </p>
            <div class="d-flex justify-content-center gap-2 mt-3 flex-wrap">
              <span class="badge bg-light text-dark border p-2"><i class="fas fa-check-circle text-success me-1"></i>Term 1–4 Grades</span>
              <span class="badge bg-light text-dark border p-2"><i class="fas fa-check-circle text-success me-1"></i>Printable SF9</span>
              <span class="badge bg-light text-dark border p-2"><i class="fas fa-check-circle text-success me-1"></i>Parent SMS</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Announcements Section -->
  <section class="py-5" id="announcements" style="background:#f1f5f9;">
    <div class="container py-3">
      <div class="section-title-wrap">
        <span class="sub-badge">Official Notices</span>
        <h2>School Announcements</h2>
        <p class="text-muted">Important advisories, guidelines, and notices from the school administration</p>
      </div>

      <div class="row g-4">
        <?php if (empty($announcements)): ?>
          <div class="col-12 text-center py-4 text-muted">
            <i class="fas fa-bullhorn fa-2x mb-2 text-secondary"></i>
            <div>No announcements posted at this time.</div>
          </div>
        <?php else: ?>
          <?php foreach ($announcements as $post): ?>
            <div class="col-md-6 col-lg-4">
              <div class="post-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <span class="badge bg-primary">Announcement</span>
                  <small class="text-muted"><i class="fas fa-calendar-alt me-1"></i><?= htmlspecialchars($post['event_date'] ?: substr($post['created_at'], 0, 10)) ?></small>
                </div>
                <h5 class="fw-bold mb-2" style="color:#0c1326;"><?= htmlspecialchars($post['title']) ?></h5>
                <p class="text-secondary flex-grow-1" style="font-size:0.9rem;line-height:1.6;">
                  <?= nl2br(htmlspecialchars($post['content'])) ?>
                </p>
                <div class="pt-3 border-top d-flex align-items-center justify-content-between text-muted" style="font-size:0.8rem;">
                  <span><i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($post['author_name'] ?: 'Office of the Principal') ?></span>
                  <span class="text-success fw-bold"><i class="fas fa-check-circle"></i> Official</span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Upcoming Events Section -->
  <section class="py-5" id="events">
    <div class="container py-3">
      <div class="section-title-wrap">
        <span class="sub-badge" style="color:#d97706;">School Calendar</span>
        <h2>Upcoming Events &amp; Assemblies</h2>
        <p class="text-muted">Stay informed on meetings, academic activities, and extracurricular schedules</p>
      </div>

      <div class="row g-4">
        <?php if (empty($events)): ?>
          <div class="col-12 text-center py-4 text-muted">
            <i class="fas fa-calendar-alt fa-2x mb-2 text-secondary"></i>
            <div>No upcoming events scheduled right now.</div>
          </div>
        <?php else: ?>
          <?php foreach ($events as $post): ?>
            <?php $d = fmtEventDate($post['event_date']); ?>
            <div class="col-md-6 col-lg-4">
              <div class="post-card p-4">
                <div class="d-flex gap-3 align-items-start mb-3">
                  <div class="event-calendar-badge">
                    <div class="m"><?= htmlspecialchars($d['month']) ?></div>
                    <div class="d"><?= htmlspecialchars($d['day']) ?></div>
                  </div>
                  <div>
                    <span class="badge bg-warning text-dark mb-1">Upcoming Event</span>
                    <h5 class="fw-bold mb-0" style="color:#0c1326;"><?= htmlspecialchars($post['title']) ?></h5>
                  </div>
                </div>
                <p class="text-secondary flex-grow-1" style="font-size:0.9rem;line-height:1.6;">
                  <?= nl2br(htmlspecialchars($post['content'])) ?>
                </p>
                <div class="pt-3 border-top text-muted" style="font-size:0.8rem;">
                  <i class="fas fa-map-marker-alt me-1 text-danger"></i> LNHS Campus / Gymnasium
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- School Highlights Section -->
  <section class="py-5" id="highlights" style="background:#f1f5f9;">
    <div class="container py-3">
      <div class="section-title-wrap">
        <span class="sub-badge" style="color:#16a34a;">Student &amp; Faculty Achievements</span>
        <h2>School Highlights &amp; Milestones</h2>
        <p class="text-muted">Celebrating excellence, athletic victories, and academic milestones of LNHS</p>
      </div>

      <div class="row g-4">
        <?php if (empty($highlights)): ?>
          <div class="col-12 text-center py-4 text-muted">
            <i class="fas fa-trophy fa-2x mb-2 text-secondary"></i>
            <div>No school highlights posted at this time.</div>
          </div>
        <?php else: ?>
          <?php foreach ($highlights as $post): ?>
            <div class="col-md-6 col-lg-4">
              <div class="post-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <span class="badge bg-success"><i class="fas fa-award me-1"></i>Highlight</span>
                  <small class="text-muted"><?= htmlspecialchars($post['event_date'] ?: substr($post['created_at'], 0, 10)) ?></small>
                </div>
                <h5 class="fw-bold mb-2" style="color:#0c1326;"><?= htmlspecialchars($post['title']) ?></h5>
                <p class="text-secondary flex-grow-1" style="font-size:0.9rem;line-height:1.6;">
                  <?= nl2br(htmlspecialchars($post['content'])) ?>
                </p>
                <div class="pt-3 border-top text-muted" style="font-size:0.8rem;">
                  <i class="fas fa-star me-1 text-warning"></i><?= htmlspecialchars($post['author_name'] ?: 'Lubo NHS') ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Contact & Location Section -->
  <section class="py-5" id="contact">
    <div class="container py-4">
      <div class="section-title-wrap mb-4">
        <span class="sub-badge">Get in Touch</span>
        <h2>School Contact &amp; Information</h2>
        <p class="text-muted">Reach out to the administration for inquiries, admissions, and student records</p>
      </div>

      <div class="row g-4">
        <div class="col-md-4">
          <div class="p-4 bg-white rounded-3 border text-center h-100">
            <div style="width:54px;height:54px;border-radius:50%;background:#e0f2fe;color:#0284c7;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:20px;">
              <i class="fas fa-map-marker-alt"></i>
            </div>
            <h6 class="fw-bold mb-1">Campus Location</h6>
            <p class="text-muted mb-0" style="font-size:0.88rem;">
              Lubo, Sto. Niño, Cagayan<br/>
              Region II – Cagayan Valley, Philippines
            </p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="p-4 bg-white rounded-3 border text-center h-100">
            <div style="width:54px;height:54px;border-radius:50%;background:#dcfce7;color:#16a34a;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:20px;">
              <i class="fas fa-envelope"></i>
            </div>
            <h6 class="fw-bold mb-1">Electronic Mail</h6>
            <p class="text-muted mb-0" style="font-size:0.88rem;">
              lubonationalhighschool@gmail.com<br/>
              prototypev1.03@gmail.com
            </p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="p-4 bg-white rounded-3 border text-center h-100">
            <div style="width:54px;height:54px;border-radius:50%;background:#fef3c7;color:#d97706;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:20px;">
              <i class="fas fa-clock"></i>
            </div>
            <h6 class="fw-bold mb-1">Office Hours</h6>
            <p class="text-muted mb-0" style="font-size:0.88rem;">
              Monday &ndash; Friday: 7:30 AM &ndash; 5:00 PM<br/>
              DepEd Basic Education Calendar
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Official Footer -->
  <footer class="footer-section">
    <div class="container">
      <div class="row g-4 mb-4">
        <div class="col-lg-5">
          <div class="d-flex align-items-center gap-2 mb-3">
            <img src="assets/images/deped_logo.png" alt="DepEd" style="width:40px;height:40px;object-fit:contain;"/>
            <img src="assets/images/lubo_logo.png" alt="LNHS" style="width:40px;height:40px;object-fit:contain;"/>
            <h5 class="mb-0">LUBO NATIONAL HIGH SCHOOL</h5>
          </div>
          <p style="font-size:0.85rem;line-height:1.6;color:#94a3b8;">
            Lubo National High School provides secondary basic education guided by the Department of Education’s core values of service, character, and integrity.
          </p>
        </div>

        <div class="col-6 col-lg-3 offset-lg-1">
          <h5>Quick Navigation</h5>
          <ul class="list-unstyled" style="font-size:0.85rem;line-height:2;">
            <li><a href="#hero">Home</a></li>
            <li><a href="#about">About LNHS</a></li>
            <li><a href="#announcements">School Announcements</a></li>
            <li><a href="#events">Upcoming Events</a></li>
            <li><a href="#highlights">Highlights &amp; Awards</a></li>
          </ul>
        </div>

        <div class="col-6 col-lg-3">
          <h5>Portal Access</h5>
          <ul class="list-unstyled" style="font-size:0.85rem;line-height:2;">
            <li><a href="login.php"><i class="fas fa-sign-in-alt me-1"></i>Portal Sign In</a></li>
            <li><a href="views/student/signup.php"><i class="fas fa-user-plus me-1"></i>Student Sign Up</a></li>
            <li><a href="login.php?role=teacher"><i class="fas fa-chalkboard-teacher me-1"></i>Faculty Portal</a></li>
            <li><a href="login.php?role=admin"><i class="fas fa-user-shield me-1"></i>Admin Gateway</a></li>
          </ul>
        </div>
      </div>

      <div class="pt-4 border-top border-secondary text-center" style="font-size:0.78rem;color:#64748b;">
        &copy; <?= date('Y') ?> Lubo National High School &bull; Department of Education &bull; Region II &bull; All Rights Reserved.
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
