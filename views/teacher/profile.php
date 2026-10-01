<?php
// views/teacher/profile.php
require_once '../../config/session.php';
require_once '../../config/db.php';
require_once '../../config/school-year.php';
requireTeacher();

$teacherActivePage = 'profile';
$pdo = getDB();
$teacherId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT id, full_name, email, phone, created_at FROM users WHERE id = ?');
$stmt->execute([$teacherId]);
$teacher = $stmt->fetch() ?: [];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Faculty Profile – OGMS</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../../assets/css/style.css") ?>"/>
</head>
<body>
<div class="app-wrapper">
  <?php include '../../components/teacher-sidebar.php'; ?>

  <div class="main-content">
    <header class="topbar">
      <div class="topbar-left">
        <button class="topbar-btn hamburger"><i class="fas fa-bars"></i></button>
        <div>
          <div class="topbar-title">Faculty Profile</div>
          <div class="topbar-subtitle">Manage your account information and password</div>
        </div>
      </div>
    </header>

    <main class="page-content fade-in">
      <div class="row g-3 justify-content-center">
        <div class="col-md-5">
          <div class="content-card text-center p-4">
            <div style="width:96px;height:96px;border-radius:50%;background:#0284c7;display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:#fff;margin:0 auto 1rem">
              <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <h5 class="fw-bold mb-1"><?= htmlspecialchars($teacher['full_name'] ?? 'Faculty') ?></h5>
            <p class="text-muted mb-2" style="font-size:0.85rem"><?= htmlspecialchars($teacher['email'] ?? '') ?></p>
            <span class="badge" style="background:#e0f2fe;color:#0284c7;font-size:0.8rem">Class Adviser / Faculty</span>
            <hr class="my-4"/>
            <div class="text-start" style="font-size:0.9rem">
              <div class="mb-2"><strong>Role:</strong> Faculty Teacher</div>
              <div class="mb-2"><strong>Phone:</strong> <?= htmlspecialchars($teacher['phone'] ?? 'Not set') ?></div>
              <div><strong>Registered:</strong> <?= htmlspecialchars(substr($teacher['created_at'] ?? '', 0, 10)) ?></div>
            </div>
          </div>
        </div>

        <div class="col-md-7">
          <div class="content-card p-4">
            <h5 class="fw-bold mb-3" style="color:#0c1326;"><i class="fas fa-user-edit me-2 text-primary"></i>Update Profile</h5>
            <form onsubmit="handleProfileUpdate(event)">
              <div class="mb-3">
                <label class="form-label fw-semibold">Full Name</label>
                <input type="text" id="profName" class="form-control" value="<?= htmlspecialchars($teacher['full_name'] ?? '') ?>" required/>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Email Address</label>
                <input type="email" id="profEmail" class="form-control" value="<?= htmlspecialchars($teacher['email'] ?? '') ?>" required/>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Contact Number (09XXXXXXXXX)</label>
                <input type="text" id="profPhone" class="form-control" placeholder="09XXXXXXXXX" value="<?= htmlspecialchars($teacher['phone'] ?? '') ?>"/>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">New Password (leave blank to keep current)</label>
                <input type="password" id="profPassword" class="form-control" placeholder="Minimum 8 characters"/>
              </div>
              <button type="submit" class="btn btn-primary" id="saveProfBtn">
                <i class="fas fa-save me-1"></i>Save Changes
              </button>
            </form>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<div id="toast-container"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
  async function handleProfileUpdate(e) {
    e.preventDefault();
    const btn = document.getElementById('saveProfBtn');
    btn.disabled = true;

    const body = new FormData();
    body.append('action', 'update');
    body.append('id', '<?= $teacherId ?>');
    body.append('full_name', document.getElementById('profName').value.trim());
    body.append('email', document.getElementById('profEmail').value.trim());
    body.append('phone', document.getElementById('profPhone').value.trim());

    const pwd = document.getElementById('profPassword').value;
    if (pwd) body.append('new_password', pwd);

    try {
      const res = await fetch('../../api/students.php', { method: 'POST', body });
      const data = await res.json();
      btn.disabled = false;
      if (data.success) {
        showToast(data.message || 'Profile updated successfully.', 'success');
        setTimeout(() => location.reload(), 1000);
      } else {
        showToast(data.message || 'Update failed.', 'error');
      }
    } catch (err) {
      btn.disabled = false;
      showToast('Network error.', 'error');
    }
  }
</script>
</body>
</html>
