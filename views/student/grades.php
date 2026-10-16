<?php
// views/student/grades.php — Student Academic Grades Portal
require_once '../../config/session.php';
requireStudent();
$studentActivePage = 'grades';
$fullName  = $_SESSION['full_name'] ?? 'Student';
$nameParts = explode(' ', trim($fullName));
$initials  = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Grades – OGMS Student</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../../assets/css/style.css") ?>"/>
  <style>
    /* ── Metric Stat Cards ── */
    .metric-card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 1.15rem 1.25rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .metric-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }
    .metric-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 0.65rem;
    }
    .metric-icon-box {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.15rem;
      flex-shrink: 0;
    }
    .metric-label {
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #64748b;
      font-weight: 600;
      margin-bottom: 0.25rem;
    }
    .metric-value {
      font-size: 1.75rem;
      font-weight: 800;
      line-height: 1.1;
      color: #0f172a;
    }
    .metric-subtitle {
      font-size: 0.78rem;
      margin-top: 0.35rem;
      display: flex;
      align-items: center;
      gap: 0.35rem;
    }

    /* ── Subject Accordion ── */
    .subject-accordion-item {
      border: 1px solid #e2e8f0;
      border-radius: 12px !important;
      margin-bottom: 1rem;
      overflow: hidden;
      background: #ffffff;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .subject-accordion-item:hover {
      border-color: #cbd5e1;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
    }
    .subject-accordion-btn {
      background: #ffffff;
      padding: 1rem 1.25rem;
      border: none;
      box-shadow: none !important;
      cursor: pointer;
      width: 100%;
      text-align: left;
      transition: background 0.15s ease;
    }
    .subject-accordion-btn:hover {
      background: #f8fafc;
    }
    .subject-accordion-btn:not(.collapsed) {
      background: #f8fafc;
      border-bottom: 1px solid #e2e8f0;
    }
    .subject-accordion-btn::after {
      background-size: 1rem;
      transition: transform 0.25s ease-in-out;
      margin-left: 0.75rem;
      flex-shrink: 0;
    }

    .sub-icon-badge {
      width: 44px;
      height: 44px;
      border-radius: 10px;
      background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
      color: #ffffff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      flex-shrink: 0;
      box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25);
    }
    .subject-title {
      font-size: 1.02rem;
      font-weight: 700;
      color: #0f172a;
      line-height: 1.3;
    }
    .subject-faculty {
      font-size: 0.8rem;
      color: #64748b;
      margin-top: 0.15rem;
    }

    /* ── Grade Badges & Pills ── */
    .grade-badge-custom {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 58px;
      padding: 0.35rem 0.65rem;
      font-size: 0.92rem;
      font-weight: 700;
      border-radius: 8px;
      letter-spacing: 0.2px;
    }
    .grade-badge-custom.pass {
      background: #dcfce7;
      color: #15803d;
      border: 1px solid #bbf7d0;
    }
    .grade-badge-custom.fail {
      background: #fee2e2;
      color: #b91c1c;
      border: 1px solid #fecaca;
    }
    .grade-badge-custom.pending {
      background: #f1f5f9;
      color: #64748b;
      border: 1px solid #e2e8f0;
      font-weight: 500;
      font-size: 0.82rem;
    }
    .term-pill {
      display: inline-flex;
      align-items: center;
      font-size: 0.75rem;
      font-weight: 700;
      padding: 0.25rem 0.6rem;
      border-radius: 6px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .term-pill-1 { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .term-pill-2 { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .term-pill-3 { background: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; }

    /* ── Desktop Table View ── */
    .grades-desktop-table {
      min-width: 720px;
      margin-bottom: 0;
    }
    .grades-desktop-table thead th {
      background: #f8fafc;
      color: #475569;
      font-weight: 600;
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      padding: 0.85rem 1rem;
      white-space: nowrap;
      border-bottom: 2px solid #e2e8f0;
    }
    .grades-desktop-table tbody td {
      padding: 0.85rem 1rem;
      vertical-align: middle;
      font-size: 0.88rem;
    }
    .grades-desktop-table tfoot td {
      padding: 0.95rem 1rem;
      vertical-align: middle;
    }

    /* ── Mobile Card View (for screens < 768px) ── */
    .mobile-term-card {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 0.85rem;
      margin-bottom: 0.75rem;
    }
    .mobile-term-card:last-child {
      margin-bottom: 0;
    }
    .mobile-component-tile {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 0.5rem 0.6rem;
      text-align: center;
    }
    .mobile-component-label {
      font-size: 0.68rem;
      font-weight: 600;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.03em;
      margin-bottom: 0.2rem;
      line-height: 1.15;
    }
    .mobile-component-value {
      font-size: 0.95rem;
      font-weight: 700;
      color: #0f172a;
    }
    .mobile-summary-banner {
      background: #f1f5f9;
      border-radius: 8px;
      padding: 0.75rem 1rem;
      border: 1px solid #e2e8f0;
    }

    /* ── Responsive Media Queries ── */
    @media (max-width: 575.98px) {
      .metric-value {
        font-size: 1.45rem;
      }
      .metric-card {
        padding: 0.85rem 1rem;
      }
      .sub-icon-badge {
        width: 36px;
        height: 36px;
        font-size: 1rem;
      }
      .subject-title {
        font-size: 0.92rem;
      }
      .subject-faculty {
        font-size: 0.75rem;
      }
      .subject-accordion-btn {
        padding: 0.85rem 0.95rem;
      }
    }
  </style>
</head>
<body>
<div class="app-wrapper">
  <?php include '../../components/student-sidebar.php'; ?>

  <div class="main-content">
    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="topbar-btn hamburger" aria-label="Toggle Navigation"><i class="fas fa-bars"></i></button>
        <div>
          <div class="topbar-title">My Grades</div>
          <div class="topbar-subtitle">Academic Performance &amp; DepEd Component Grades</div>
        </div>
      </div>
      <div class="topbar-right">
        <div class="topbar-btn" title="Academic Status"><i class="fas fa-graduation-cap"></i></div>
        <div class="topbar-avatar" onclick="window.location.href='profile.php'" title="View Profile"><?= htmlspecialchars($initials) ?></div>
      </div>
    </header>

    <main class="page-content fade-in">
      <!-- 4-Card Responsive Metric Overview Grid -->
      <div class="row g-3 mb-3">
        <!-- Metric 1: Overall GPA -->
        <div class="col-6 col-lg-3">
          <div class="metric-card">
            <div>
              <div class="metric-header">
                <span class="metric-label">Overall GPA</span>
                <div class="metric-icon-box" style="background:#eff6ff;color:#1d4ed8;">
                  <i class="fas fa-trophy"></i>
                </div>
              </div>
              <div class="metric-value" id="statsAvg" style="color:#0284c7">—</div>
            </div>
            <div class="metric-subtitle">
              <span id="statsAvgDesc" class="badge bg-primary-subtle text-primary border px-2 py-0.5">Calculating…</span>
            </div>
          </div>
        </div>

        <!-- Metric 2: Passing Status -->
        <div class="col-6 col-lg-3">
          <div class="metric-card">
            <div>
              <div class="metric-header">
                <span class="metric-label">Passed Subjects</span>
                <div class="metric-icon-box" style="background:#f0fdf4;color:#16a34a;">
                  <i class="fas fa-check-circle"></i>
                </div>
              </div>
              <div class="metric-value text-success" id="statsPass">—</div>
            </div>
            <div class="metric-subtitle">
              <span id="statsPassRate" class="text-success fw-semibold small">All Terms Evaluated</span>
            </div>
          </div>
        </div>

        <!-- Metric 3: Enrolled Subjects -->
        <div class="col-6 col-lg-3">
          <div class="metric-card">
            <div>
              <div class="metric-header">
                <span class="metric-label">Enrolled Subjects</span>
                <div class="metric-icon-box" style="background:#fefce8;color:#d97706;">
                  <i class="fas fa-book-reader"></i>
                </div>
              </div>
              <div class="metric-value" id="statsSubCount">—</div>
            </div>
            <div class="metric-subtitle">
              <span id="statsCurriculum" class="text-muted small">Active Curriculum</span>
            </div>
          </div>
        </div>

        <!-- Metric 4: Academic Year / Terms -->
        <div class="col-6 col-lg-3">
          <div class="metric-card">
            <div>
              <div class="metric-header">
                <span class="metric-label">School Year</span>
                <div class="metric-icon-box" style="background:#fdf4ff;color:#9333ea;">
                  <i class="fas fa-calendar-alt"></i>
                </div>
              </div>
              <div class="metric-value" style="font-size:1.55rem;color:#7e22ce">2024–2025</div>
            </div>
            <div class="metric-subtitle">
              <span class="badge bg-info-subtle text-info border px-2 py-0.5">Terms 1, 2 &amp; 3</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Dedicated Responsive Filter & Action Toolbar Card -->
      <div class="content-card mb-3">
        <div class="card-body-custom py-3">
          <div class="row g-2 align-items-end">
            <!-- Filter by Term -->
            <div class="col-12 col-sm-6 col-md-4">
              <label for="filterQuarter" class="form-label mb-1" style="font-size:0.8rem;font-weight:600">
                <i class="fas fa-clock me-1 text-primary"></i>Filter by Term
              </label>
              <select id="filterQuarter" class="form-select" onchange="renderGrades()">
                <option value="all">All Terms (1st – 3rd)</option>
                <option value="1">1st Term Only</option>
                <option value="2">2nd Term Only</option>
                <option value="3">3rd Term Only</option>
              </select>
            </div>

            <!-- Filter by Subject -->
            <div class="col-12 col-sm-6 col-md-5">
              <label for="filterSubject" class="form-label mb-1" style="font-size:0.8rem;font-weight:600">
                <i class="fas fa-book me-1 text-primary"></i>Filter by Subject
              </label>
              <select id="filterSubject" class="form-select" onchange="handleSubjectFilterChange()">
                <option value="all">All Subjects</option>
              </select>
            </div>

            <!-- Action Controls -->
            <div class="col-12 col-md-3">
              <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary w-100" id="btnToggleAll" onclick="toggleAllAccordions()">
                  <i class="fas fa-expand-arrows-alt me-1"></i>Expand All
                </button>
                <button type="button" class="btn btn-light border text-muted px-3" id="btnResetFilters" onclick="resetFilters()" title="Reset Filters" style="display:none">
                  <i class="fas fa-undo"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Subject Accordion Main Card -->
      <div class="content-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <span class="card-title mb-0">
              <i class="fas fa-layer-group me-2 text-primary"></i>Subject Breakdown
            </span>
            <span id="subjectCountBadge" class="badge bg-light text-secondary border" style="font-size:0.75rem">0 subjects</span>
          </div>
          <small class="text-muted d-none d-sm-inline" style="font-size:0.78rem">
            <i class="fas fa-info-circle me-1 text-primary"></i>DepEd Grading: WW (20%) + PT (50%) + QE (30%)
          </small>
        </div>

        <div class="p-3 p-sm-3">
          <div class="accordion" id="studentGradesAccordion">
            <div class="text-center py-5 text-muted">
              <i class="fas fa-spinner fa-spin fa-2x mb-3 text-primary d-block"></i>
              Loading subject grades…
            </div>
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
  const SESSION_USER_ID = <?= (int)$_SESSION['user_id'] ?>;
  let allGrades = [];
  let allSubjects = [];
  let subjectMap = {};
  let isAllExpanded = false;

  async function loadGrades() {
    try {
      const res  = await fetch('../../api/grades.php?action=list&student_id=' + SESSION_USER_ID);
      const data = await res.json();
      allGrades   = data.data     || [];
      allSubjects = data.subjects || [];

      // Determine curriculum type (JHS or SHS)
      const hasSHS = allSubjects.some(s => s.level === 'SHS');
      const curriculumText = hasSHS ? 'Senior High (SHS)' : 'Junior High (JHS)';
      document.getElementById('statsCurriculum').textContent = curriculumText;
      document.getElementById('statsSubCount').textContent   = `${allSubjects.length} Core`;

      // Populate filterSubject select with curriculum-level subjects
      const filterSelect = document.getElementById('filterSubject');
      filterSelect.innerHTML = '<option value="all">All Subjects</option>';
      allSubjects.forEach(s => {
        subjectMap[s.id] = s;
        filterSelect.innerHTML += `<option value="${s.id}">${s.name} (${s.code || s.name})</option>`;
      });

      renderGrades();
    } catch(e) {
      console.error('Grades load error:', e);
      document.getElementById('studentGradesAccordion').innerHTML =
        '<div class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Failed to load grades. Please try refreshing.</div>';
    }
  }

  function handleSubjectFilterChange() {
    renderGrades();
    const selSubId = document.getElementById('filterSubject').value;
    if (selSubId !== 'all') {
      const collapseEl = document.getElementById(`collapse_sub_${selSubId}`);
      if (collapseEl) {
        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
        bsCollapse.show();
      }
    }
  }

  function resetFilters() {
    document.getElementById('filterQuarter').value = 'all';
    document.getElementById('filterSubject').value = 'all';
    renderGrades();
  }

  function toggleAllAccordions() {
    isAllExpanded = !isAllExpanded;
    const collapseElements = document.querySelectorAll('#studentGradesAccordion .accordion-collapse');
    collapseElements.forEach(el => {
      const bsCollapse = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
      if (isAllExpanded) {
        bsCollapse.show();
      } else {
        bsCollapse.hide();
      }
    });

    const btn = document.getElementById('btnToggleAll');
    btn.innerHTML = isAllExpanded
      ? '<i class="fas fa-compress-arrows-alt me-1"></i>Collapse All'
      : '<i class="fas fa-expand-arrows-alt me-1"></i>Expand All';
  }

  function renderGrades() {
    const qFilter   = document.getElementById('filterQuarter').value;
    const subFilter = document.getElementById('filterSubject').value;

    // Toggle reset filter button visibility
    const isFiltered = (qFilter !== 'all' || subFilter !== 'all');
    document.getElementById('btnResetFilters').style.display = isFiltered ? 'inline-block' : 'none';

    // Filter visible subjects
    let displaySubjects = allSubjects;
    if (subFilter !== 'all') {
      displaySubjects = allSubjects.filter(s => String(s.id) === subFilter);
    }

    document.getElementById('subjectCountBadge').textContent =
      `${displaySubjects.length} Subject${displaySubjects.length !== 1 ? 's' : ''}`;

    const container = document.getElementById('studentGradesAccordion');

    if (!displaySubjects.length) {
      container.innerHTML = `
        <div class="text-center text-muted py-5">
          <i class="fas fa-inbox fa-3x mb-3 text-secondary opacity-50"></i>
          <p class="mb-0">No subject records match your filter criteria.</p>
        </div>`;
      updateSummaryStats([]);
      return;
    }

    let allRelevantGrades = [];

    container.innerHTML = displaySubjects.map((sub, idx) => {
      const subGrades = allGrades.filter(g => g.subject_id == sub.id);

      // Collect numeric grades for this subject across terms 1–3
      const termVals = [1, 2, 3].map(t => {
        const g = subGrades.find(gr => gr.quarter == t);
        return (g && g.final_grade !== null && g.final_grade !== '' && !isNaN(parseFloat(g.final_grade)))
          ? parseFloat(g.final_grade)
          : null;
      });

      const validTerms = termVals.filter(v => v !== null);
      const subAvg = validTerms.length
        ? (validTerms.reduce((a, b) => a + b, 0) / validTerms.length).toFixed(2)
        : null;

      const targetQuarters = (qFilter === 'all') ? [1, 2, 3] : [parseInt(qFilter)];

      // Subject summary badge in accordion header
      let avgHeaderHtml = '';
      if (subAvg !== null) {
        const isPass = parseFloat(subAvg) >= 75;
        avgHeaderHtml = `
          <div class="d-flex align-items-center gap-1 gap-sm-2 flex-wrap justify-content-end">
            <span class="badge ${isPass ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'} px-2 py-1" style="font-size:0.85rem;font-weight:700">
              Avg: ${subAvg}
            </span>
            <span class="badge ${isPass ? 'bg-success' : 'bg-danger'} px-2 py-1" style="font-size:0.75rem">
              ${isPass ? '<i class="fas fa-check-circle me-1"></i>Passed' : '<i class="fas fa-times-circle me-1"></i>Failed'}
            </span>
          </div>`;
      } else {
        avgHeaderHtml = `<span class="badge bg-secondary-subtle text-secondary px-2 py-1" style="font-size:0.78rem">In Progress</span>`;
      }

      // Generate Desktop Table Rows
      const desktopTableRowsHtml = targetQuarters.map(tNum => {
        const g = subGrades.find(gr => gr.quarter == tNum);
        const termPillClass = `term-pill term-pill-${tNum}`;
        const termName = tNum === 1 ? '1st Term' : (tNum === 2 ? '2nd Term' : '3rd Term');

        if (g && g.final_grade !== null && g.final_grade !== '' && !isNaN(parseFloat(g.final_grade))) {
          const fg = parseFloat(g.final_grade).toFixed(2);
          const isPass = parseFloat(fg) >= 75;
          const ww = (g.written_works !== null && g.written_works !== '') ? parseFloat(g.written_works).toFixed(2) : '—';
          const pt = (g.performance_tasks !== null && g.performance_tasks !== '') ? parseFloat(g.performance_tasks).toFixed(2) : '—';
          const qe = (g.quarterly_exam !== null && g.quarterly_exam !== '') ? parseFloat(g.quarterly_exam).toFixed(2) : '—';
          const desc = getGradeDesc(parseFloat(fg));

          allRelevantGrades.push(parseFloat(fg));

          return `
            <tr>
              <td class="ps-3"><span class="${termPillClass}">${termName}</span></td>
              <td class="text-center font-monospace">${ww}</td>
              <td class="text-center font-monospace">${pt}</td>
              <td class="text-center font-monospace">${qe}</td>
              <td class="text-center">
                <span class="grade-badge-custom ${isPass ? 'pass' : 'fail'}">${fg}</span>
              </td>
              <td class="text-center">
                <span class="badge ${isPass ? 'bg-success' : 'bg-danger'}">${isPass ? 'Passed' : 'Failed'}</span>
              </td>
              <td class="pe-3 text-center text-muted" style="font-size:0.83rem">${desc}</td>
            </tr>`;
        } else {
          return `
            <tr>
              <td class="ps-3"><span class="${termPillClass}">${termName}</span></td>
              <td class="text-center text-muted">—</td>
              <td class="text-center text-muted">—</td>
              <td class="text-center text-muted">—</td>
              <td class="text-center"><span class="grade-badge-custom pending">Pending</span></td>
              <td class="text-center"><span class="badge bg-light text-muted border">No Grade</span></td>
              <td class="pe-3 text-center text-muted fst-italic" style="font-size:0.83rem">Not yet encoded</td>
            </tr>`;
        }
      }).join('');

      // Generate Mobile Term Cards (Clean, dedicated stacked view for smartphones)
      const mobileCardsHtml = targetQuarters.map(tNum => {
        const g = subGrades.find(gr => gr.quarter == tNum);
        const termPillClass = `term-pill term-pill-${tNum}`;
        const termName = tNum === 1 ? '1st Term' : (tNum === 2 ? '2nd Term' : '3rd Term');

        if (g && g.final_grade !== null && g.final_grade !== '' && !isNaN(parseFloat(g.final_grade))) {
          const fg = parseFloat(g.final_grade).toFixed(2);
          const isPass = parseFloat(fg) >= 75;
          const ww = (g.written_works !== null && g.written_works !== '') ? parseFloat(g.written_works).toFixed(2) : '—';
          const pt = (g.performance_tasks !== null && g.performance_tasks !== '') ? parseFloat(g.performance_tasks).toFixed(2) : '—';
          const qe = (g.quarterly_exam !== null && g.quarterly_exam !== '') ? parseFloat(g.quarterly_exam).toFixed(2) : '—';
          const desc = getGradeDesc(parseFloat(fg));

          return `
            <div class="mobile-term-card">
              <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                <span class="${termPillClass}">${termName}</span>
                <div class="d-flex align-items-center gap-1">
                  <span class="grade-badge-custom ${isPass ? 'pass' : 'fail'}" style="font-size:0.85rem;padding:0.25rem 0.5rem;min-width:auto">${fg}</span>
                  <span class="badge ${isPass ? 'bg-success' : 'bg-danger'}" style="font-size:0.7rem">${isPass ? 'Passed' : 'Failed'}</span>
                </div>
              </div>

              <!-- Component Scores Grid -->
              <div class="row g-1 mb-2">
                <div class="col-4">
                  <div class="mobile-component-tile">
                    <div class="mobile-component-label">Written (20%)</div>
                    <div class="mobile-component-value">${ww}</div>
                  </div>
                </div>
                <div class="col-4">
                  <div class="mobile-component-tile">
                    <div class="mobile-component-label">Tasks (50%)</div>
                    <div class="mobile-component-value">${pt}</div>
                  </div>
                </div>
                <div class="col-4">
                  <div class="mobile-component-tile">
                    <div class="mobile-component-label">Exam (30%)</div>
                    <div class="mobile-component-value">${qe}</div>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center pt-1" style="font-size:0.75rem">
                <span class="text-muted"><i class="fas fa-award me-1 text-primary"></i>Proficiency:</span>
                <span class="fw-semibold text-dark">${desc}</span>
              </div>
            </div>`;
        } else {
          return `
            <div class="mobile-term-card">
              <div class="d-flex justify-content-between align-items-center">
                <span class="${termPillClass}">${termName}</span>
                <span class="badge bg-light text-muted border">Pending</span>
              </div>
              <p class="mb-0 text-muted small fst-italic mt-2">Term grade not yet encoded.</p>
            </div>`;
        }
      }).join('');

      // Auto-open if only 1 subject filtered or if user requested
      const shouldShow = (subFilter !== 'all' || (displaySubjects.length === 1 && idx === 0));

      return `
        <div class="subject-accordion-item">
          <div class="accordion-header" id="heading_sub_${sub.id}">
            <button class="accordion-button subject-accordion-btn ${shouldShow ? '' : 'collapsed'}"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapse_sub_${sub.id}"
                    aria-expanded="${shouldShow ? 'true' : 'false'}"
                    aria-controls="collapse_sub_${sub.id}">
              <div class="d-flex align-items-center justify-content-between w-100 flex-wrap gap-2 me-2">
                <!-- Left: Subject Details -->
                <div class="d-flex align-items-center gap-2 gap-sm-3 min-w-0">
                  <div class="sub-icon-badge">
                    <i class="fas fa-book-open"></i>
                  </div>
                  <div class="min-w-0">
                    <div class="subject-title">
                      ${sub.name}
                      ${sub.code ? `<span class="badge bg-light text-primary border ms-1" style="font-size:0.75rem">${sub.code}</span>` : ''}
                    </div>
                    <div class="subject-faculty">
                      <i class="fas fa-chalkboard-teacher me-1 text-primary"></i>${sub.teacher_name ? `Faculty: ${sub.teacher_name}` : 'Lubo NHS Faculty'}
                    </div>
                  </div>
                </div>

                <!-- Right: Average Badge -->
                <div>
                  ${avgHeaderHtml}
                </div>
              </div>
            </button>
          </div>

          <div id="collapse_sub_${sub.id}"
               class="accordion-collapse collapse ${shouldShow ? 'show' : ''}"
               aria-labelledby="heading_sub_${sub.id}">
            <div class="accordion-body p-0 bg-white">
              
              <!-- 1. Desktop & Tablet Table (>= 768px) -->
              <div class="d-none d-md-block">
                <div class="table-responsive">
                  <table class="table table-hover align-middle grades-desktop-table">
                    <thead>
                      <tr>
                        <th class="ps-3" style="width:16%">Grading Period</th>
                        <th class="text-center" style="width:14%">Written Works (20%)</th>
                        <th class="text-center" style="width:14%">Performance Tasks (50%)</th>
                        <th class="text-center" style="width:14%">Quarterly Exam (30%)</th>
                        <th class="text-center" style="width:14%">Term Grade</th>
                        <th class="text-center" style="width:12%">Remarks</th>
                        <th class="pe-3 text-center" style="width:16%">Proficiency Level</th>
                      </tr>
                    </thead>
                    <tbody>
                      ${desktopTableRowsHtml}
                    </tbody>
                    ${subAvg !== null ? `
                      <tfoot class="table-light border-top">
                        <tr class="fw-semibold">
                          <td colspan="4" class="ps-3 text-end text-dark">
                            Subject Final Average (Terms 1–3):
                          </td>
                          <td class="text-center">
                            <span class="grade-badge-custom ${parseFloat(subAvg) >= 75 ? 'pass' : 'fail'}">${subAvg}</span>
                          </td>
                          <td class="text-center">
                            <span class="badge ${parseFloat(subAvg) >= 75 ? 'bg-success' : 'bg-danger'}">
                              ${parseFloat(subAvg) >= 75 ? 'Passed' : 'Failed'}
                            </span>
                          </td>
                          <td class="pe-3 text-center text-dark fw-bold" style="font-size:0.85rem">
                            ${getGradeDesc(parseFloat(subAvg))}
                          </td>
                        </tr>
                      </tfoot>
                    ` : ''}
                  </table>
                </div>
              </div>

              <!-- 2. Mobile Term Cards (< 768px) -->
              <div class="d-md-none p-3">
                ${mobileCardsHtml}

                ${subAvg !== null ? `
                  <div class="mobile-summary-banner mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                      <small class="text-muted d-block" style="font-size:0.72rem;line-height:1">Subject Final Average</small>
                      <strong class="text-dark" style="font-size:1.05rem">${subAvg}</strong>
                      <span class="badge ${parseFloat(subAvg) >= 75 ? 'bg-success' : 'bg-danger'} ms-1" style="font-size:0.7rem">
                        ${parseFloat(subAvg) >= 75 ? 'Passed' : 'Failed'}
                      </span>
                    </div>
                    <div class="text-end">
                      <small class="text-muted d-block" style="font-size:0.72rem;line-height:1">Descriptor</small>
                      <span class="fw-bold text-primary" style="font-size:0.85rem">${getGradeDesc(parseFloat(subAvg))}</span>
                    </div>
                  </div>
                ` : ''}
              </div>

            </div>
          </div>
        </div>`;
    }).join('');

    updateSummaryStats(allRelevantGrades);
  }

  function updateSummaryStats(gradesList) {
    if (!gradesList.length) {
      document.getElementById('statsAvg').textContent  = '—';
      document.getElementById('statsPass').textContent = '—';
      document.getElementById('statsAvgDesc').textContent = 'No records';
      return;
    }
    const avg = +(gradesList.reduce((a, b) => a + b, 0) / gradesList.length).toFixed(2);
    const passed = gradesList.filter(v => v >= 75).length;
    const isPassing = (avg >= 75);

    document.getElementById('statsAvg').textContent = avg;
    document.getElementById('statsAvg').style.color = isPassing ? '#0284c7' : '#dc2626';

    const desc = getGradeDesc(avg);
    const descBadge = document.getElementById('statsAvgDesc');
    descBadge.textContent = desc;
    descBadge.className = `badge ${isPassing ? 'bg-primary-subtle text-primary border' : 'bg-danger-subtle text-danger border'} px-2 py-0.5`;

    const passEl = document.getElementById('statsPass');
    passEl.textContent = `${passed} / ${gradesList.length}`;
    passEl.className = (passed === gradesList.length) ? 'metric-value text-success' : 'metric-value text-warning';

    const rate = Math.round((passed / gradesList.length) * 100);
    document.getElementById('statsPassRate').textContent = `${rate}% Passing Rate`;
  }

  document.addEventListener('DOMContentLoaded', loadGrades);
</script>
</body>
</html>
