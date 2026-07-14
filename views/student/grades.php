<?php require_once '../../components/under-construction.php'; ?>
<?php
require_once '../../config/session.php';
requireStudent();
$studentActivePage = 'grades';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>My Grades – OGMS Student</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../../assets/css/style.css"/>
</head>
<body>
<div class="app-wrapper">
  <?php include '../../components/student-sidebar.php'; ?>

  <div class="main-content">
    <header class="topbar">
      <div class="topbar-left">
        <button class="topbar-btn hamburger"><i class="fas fa-bars"></i></button>
        <div>
          <div class="topbar-title">My Grades</div>
          <div class="topbar-subtitle">View your academic performance by subject and quarter</div>
        </div>
      </div>
      <div class="topbar-right">
        <a href="reports.php" class="btn btn-success btn-sm">
          <i class="fas fa-print me-1"></i>Print Report Card
        </a>
      </div>
    </header>

    <main class="page-content fade-in">
      <div class="content-card mb-3">
        <div class="card-body-custom">
          <div class="row g-2 align-items-end">
            <div class="col-md-4">
              <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600">Filter by Quarter</label>
              <select id="filterQuarter" class="form-select form-select-sm" onchange="renderGrades()">
                <option value="all">All Quarters</option>
                <option value="1">1st Quarter</option>
                <option value="2">2nd Quarter</option>
                <option value="3">3rd Quarter</option>
                <option value="4">4th Quarter</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600">Filter by Subject</label>
              <select id="filterSubject" class="form-select form-select-sm" onchange="renderGrades()">
                <option value="all">All Subjects</option>
              </select>
            </div>
            <div class="col-md-4">
              <div class="row g-2 text-center">
                <div class="col-6">
                  <div class="stat-card py-2">
                    <div class="stat-value" id="statsAvg" style="font-size:1.4rem">—</div>
                    <div class="stat-label">Overall Avg</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="stat-card py-2">
                    <div class="stat-value" id="statsPass" style="font-size:1.4rem;color:var(--success)">—</div>
                    <div class="stat-label">Passed</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="content-card">
        <div class="card-header-custom">
          <span class="card-title"><i class="fas fa-clipboard-list me-2 text-primary"></i>Grade Records</span>
          <span id="recordCount" style="font-size:0.78rem;color:#64748b">0 records</span>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Subject</th>
                <th>Quarter</th>
                <th>Written Works<br><small class="text-muted" style="font-weight:400">(20%)</small></th>
                <th>Performance Tasks<br><small class="text-muted" style="font-weight:400">(60%)</small></th>
                <th>Quarterly Exam<br><small class="text-muted" style="font-weight:400">(20%)</small></th>
                <th>Final Grade</th>
                <th>Remarks</th>
              </tr>
            </thead>
            <tbody id="gradesBody">
              <tr><td colspan="7" class="text-center py-4 text-muted">Loading grades…</td></tr>
            </tbody>
          </table>
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
  const SESSION_USER_ID = <?= (int)$_SESSION['user_id'] ?>;
  let allGrades = [], subjectMap = {};

  async function loadGrades() {
    try {
      const res  = await fetch('../../api/grades.php?action=list&student_id=' + SESSION_USER_ID);
      const data = await res.json();
      allGrades = data.data     || [];
      const subjects = data.subjects || [];

      subjects.forEach(s => {
        subjectMap[s.id] = s;
        document.getElementById('filterSubject').innerHTML +=
          `<option value="${s.id}">${s.name}</option>`;
      });

      renderGrades();
    } catch(e) { console.error('Grades load error:', e); }
  }

  function renderGrades() {
    const q   = document.getElementById('filterQuarter').value;
    const sub = document.getElementById('filterSubject').value;

    let rows = allGrades;
    if (q   !== 'all') rows = rows.filter(g => String(g.quarter) === q);
    if (sub !== 'all') rows = rows.filter(g => String(g.subject_id) === sub);

    const qLabels = {1:'1st',2:'2nd',3:'3rd',4:'4th'};
    document.getElementById('recordCount').textContent = `${rows.length} record${rows.length!==1?'s':''}`;

    if (!rows.length) {
      document.getElementById('gradesBody').innerHTML =
        '<tr><td colspan="7" class="text-center text-muted py-4">No grades found for selected filters.</td></tr>';
    } else {
      document.getElementById('gradesBody').innerHTML = rows.map(g => {
        const sub = subjectMap[g.subject_id] || {};
        const fg  = parseFloat(g.final_grade);
        return `<tr>
          <td><strong>${sub.name||'—'}</strong></td>
          <td><span class="badge bg-secondary">${qLabels[g.quarter]||g.quarter} Quarter</span></td>
          <td class="text-center">${g.written_works ?? '—'}</td>
          <td class="text-center">${g.performance_tasks ?? '—'}</td>
          <td class="text-center">${g.quarterly_exam ?? '—'}</td>
          <td>${gradeCell(fg)}</td>
          <td>${getGradeBadge(fg)}</td>
        </tr>`;
      }).join('');
    }

    // Stats for current filter
    const vals    = rows.map(g => parseFloat(g.final_grade)).filter(v => !isNaN(v));
    const avg     = vals.length ? +(vals.reduce((a,b)=>a+b,0)/vals.length).toFixed(2) : null;
    const passed  = vals.filter(v=>v>=75).length;
    document.getElementById('statsAvg').textContent  = avg  ?? '—';
    document.getElementById('statsPass').textContent = vals.length ? `${passed}/${vals.length}` : '—';
    document.getElementById('statsPass').style.color = (passed===vals.length && vals.length>0) ? 'var(--success)' : 'var(--danger)';
  }

  document.addEventListener('DOMContentLoaded', loadGrades);
</script>
</body>
</html>
