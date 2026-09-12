<?php
require_once '../../config/session.php';
requireAdmin();
$adminActivePage = 'reports';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Reports – OGMS Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../../assets/css/style.css") ?>"/>
  <link rel="stylesheet" href="../../assets/css/sf9.css?v=<?= filemtime(__DIR__ . "/../../assets/css/sf9.css") ?>"/>
  <link rel="stylesheet" href="../../assets/css/print.css?v=<?= filemtime(__DIR__ . "/../../assets/css/print.css") ?>"/>
</head>
<body>
<div class="app-wrapper">
  <aside class="sidebar no-print"><?php
    // Inline only the aside inner content via include trick
    ob_start(); include '../../components/admin-sidebar.php'; $sidebarHtml = ob_get_clean();
    // Strip outer <aside> tags since we wrapped above
    echo preg_replace('/<aside[^>]*>|<\/aside>/i','',$sidebarHtml);
  ?></aside>

  <div class="main-content">
    <header class="topbar no-print">
      <div class="topbar-left">
        <button class="topbar-btn hamburger"><i class="fas fa-bars"></i></button>
        <div>
          <div class="topbar-title">Reports</div>
          <div class="topbar-subtitle">Generate class and student reports</div>
        </div>
      </div>
      <div class="topbar-right">
        <button class="btn btn-success btn-sm" onclick="printReport()">
          <i class="fas fa-print me-1"></i>Print Report
        </button>
      </div>
    </header>

    <main class="page-content fade-in">
      <div class="content-card mb-3 no-print">
        <div class="card-body-custom">
          <div class="row g-2 align-items-end">
            <div class="col-md-3">
              <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600">Report Type</label>
              <select id="reportType" class="form-select form-select-sm" onchange="onTypeChange()">
                <option value="class">Class Summary Report</option>
                <option value="subject">Subject Performance</option>
                <option value="student">Individual Student</option>
              </select>
            </div>
            <div class="col-md-3" id="studentSelectGroup" style="display:none">
              <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600">Select Student</label>
              <select id="reportStudent" class="form-select form-select-sm" onchange="onStudentChange()"></select>
            </div>
            <div class="col-md-2">
              <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600">Grading Term</label>
              <select id="reportPeriod" class="form-select form-select-sm" onchange="generateReport()">
                <option value="0">All Terms</option>
                <option value="1">1st Term</option>
                <option value="2">2nd Term</option>
                <option value="3">3rd Term</option>
              </select>
            </div>
            <div class="col-md-2">
              <button class="btn btn-primary btn-sm w-100" onclick="generateReport()">
                <i class="fas fa-sync me-1"></i>Generate
              </button>
            </div>
            <div class="col-md-2">
              <button class="btn btn-success btn-sm w-100" onclick="printReport()">
                <i class="fas fa-print me-1"></i>Print
              </button>
            </div>
          </div>

          <!-- Individual Student Signatories Bar (Only visible when reportType === 'student') -->
          <div class="row g-2 align-items-end mt-2 pt-2 border-top" id="studentSignatoriesGroup" style="display:none">
            <div class="col-md-6">
              <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600" for="reportAdviser">
                <i class="fas fa-chalkboard-teacher me-1 text-primary"></i>Class Adviser
              </label>
              <input type="text" id="reportAdviser" class="form-control form-control-sm" placeholder="e.g. JOSEPH M. BATUYONG" oninput="updateSignatoriesLive()">
            </div>
            <div class="col-md-6">
              <label class="form-label mb-1" style="font-size:0.8rem;font-weight:600" for="reportPrincipal">
                <i class="fas fa-user-tie me-1 text-primary"></i>School Principal / Head
              </label>
              <input type="text" id="reportPrincipal" class="form-control form-control-sm" placeholder="e.g. MARLON C. VALIENTES" oninput="updateSignatoriesLive()">
            </div>
          </div>
        </div>
      </div>

      <div class="print-header">
        <div class="print-logo"><i class="fas fa-graduation-cap"></i></div>
        <div class="print-school-info">
          <p class="print-dept">Republic of the Philippines &bull; Department of Education &bull; Region II</p>
          <h3>LUBO NATIONAL HIGH SCHOOL</h3>
          <p>Lubo, Sto. Niño, Cagayan &nbsp;|&nbsp; Online Grade Monitoring System</p>
          <p id="printReportTitle">Class Summary Report – All Terms</p>
        </div>
      </div>

      <div id="reportContent"></div>
    </main>
  </div>
</div>

<div id="toast-container"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/app.js"></script>
<script src="../../assets/js/sf9-renderer.js?v=<?= filemtime(__DIR__ . "/../../assets/js/sf9-renderer.js") ?>"></script>
<script>
  let studentsCache = [];
  let isGenerating = false;

  function printReport() {
    const type = document.getElementById('reportType').value;
    if (type === 'student') {
      if (typeof printSf9Official === 'function') {
        printSf9Official();
        return;
      }
      document.body.classList.add('printing-sf9');
    } else {
      document.body.classList.remove('printing-sf9');
    }
    window.print();
  }

  window.addEventListener('afterprint', () => {
    document.body.classList.remove('printing-sf9');
  });

  function getStoredSignatories(studentId) {
    const defaultAdv = 'JOSEPH M. BATUYONG';
    const defaultHead = 'MARLON C. VALIENTES';
    const savedAdv = (studentId ? localStorage.getItem('ogms_sf9_adviser_' + studentId) : null) 
      || localStorage.getItem('ogms_sf9_adviser_default') 
      || defaultAdv;
    const savedHead = (studentId ? localStorage.getItem('ogms_sf9_head_' + studentId) : null) 
      || localStorage.getItem('ogms_sf9_head_default') 
      || defaultHead;
    return { adviser: savedAdv, schoolHead: savedHead };
  }

  function onTypeChange() {
    const type = document.getElementById('reportType').value;
    const isStudent = (type === 'student');
    document.getElementById('studentSelectGroup').style.display = isStudent ? 'block' : 'none';
    const sigGroup = document.getElementById('studentSignatoriesGroup');
    if (sigGroup) sigGroup.style.display = isStudent ? 'flex' : 'none';

    if (isStudent) {
      const stuId = document.getElementById('reportStudent').value;
      const stored = getStoredSignatories(stuId);
      const advIn = document.getElementById('reportAdviser');
      const headIn = document.getElementById('reportPrincipal');
      if (advIn) advIn.value = stored.adviser;
      if (headIn) headIn.value = stored.schoolHead;
    }
    generateReport();
  }

  function onStudentChange() {
    const stuId = document.getElementById('reportStudent').value;
    const stored = getStoredSignatories(stuId);
    const advIn = document.getElementById('reportAdviser');
    const headIn = document.getElementById('reportPrincipal');
    if (advIn) advIn.value = stored.adviser;
    if (headIn) headIn.value = stored.schoolHead;
    generateReport();
  }

  function updateSignatoriesLive() {
    const stuId = document.getElementById('reportStudent')?.value;
    const adviserVal = document.getElementById('reportAdviser')?.value ?? '';
    const principalVal = document.getElementById('reportPrincipal')?.value ?? '';

    if (stuId) {
      localStorage.setItem('ogms_sf9_adviser_' + stuId, adviserVal);
      localStorage.setItem('ogms_sf9_head_' + stuId, principalVal);
    }
    if (adviserVal) localStorage.setItem('ogms_sf9_adviser_default', adviserVal);
    if (principalVal) localStorage.setItem('ogms_sf9_head_default', principalVal);

    const advEl = document.getElementById('sf9AdviserName');
    if (advEl) advEl.textContent = adviserVal || '_______________________';

    const headEl = document.getElementById('sf9SchoolHeadName');
    if (headEl) headEl.textContent = principalVal || '_______________________';
  }

  async function generateReport() {
    if (isGenerating) return;
    isGenerating = true;

    const type    = document.getElementById('reportType').value;
    const period  = document.getElementById('reportPeriod').value;
    const stuId   = document.getElementById('reportStudent').value;
    const qLabels = ['All Terms','1st Term','2nd Term','3rd Term'];
    
    document.getElementById('printReportTitle').textContent =
      `${type==='class'?'Class Summary':type==='subject'?'Subject Performance':'Individual Student'} Report – ${qLabels[period]}`;

    const params = new URLSearchParams({action:type, quarter:period, term:period});
    if (type === 'student' && stuId) params.set('student_id', stuId);

    try {
      document.getElementById('reportContent').innerHTML = `
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="text-muted mt-2">Generating report...</p>
        </div>`;
      
      const res  = await fetch('../../api/reports.php?' + params);
      const data = await res.json();
      if (!data.success) { 
        document.getElementById('reportContent').innerHTML = '<p class="text-muted text-center py-4">' + (data.message||'No data.') + '</p>'; 
        isGenerating = false;
        return; 
      }
      renderReport(type, data.data, period);
    } catch(e) { 
      console.error("Failed to load report:", e);
      document.getElementById('reportContent').innerHTML = '<p class="text-danger text-center py-4">Failed to load report. <small class="d-block text-muted mt-1">Check console for details.</small></p>'; 
    } finally {
      isGenerating = false;
    }
  }

  function renderReport(type, data, period) {
    const date = new Date().toLocaleDateString('en-PH',{dateStyle:'long'});
    const qLabels = ['All Terms','1st Term','2nd Term','3rd Term'];

    if (type === 'class') {
      const rows  = data.students || [];
      const stats = data.stats || {};
      document.getElementById('reportContent').innerHTML = `
        <div class="content-card mb-3">
          <div class="card-header-custom" style="background:#0f172a;border-radius:4px 4px 0 0">
            <span class="card-title" style="color:#fff">Class Summary Report</span>
            <span style="color:rgba(255,255,255,.8);font-size:.8rem">Generated: ${date}</span>
          </div>
          <div class="card-body-custom">
            <div class="row g-3 mb-3">
              <div class="col-md-3 col-3 text-center"><div class="stat-card"><div class="stat-value" style="color:var(--primary)">${stats.total||0}</div><div class="stat-label">Total Students</div></div></div>
              <div class="col-md-3 col-3 text-center"><div class="stat-card"><div class="stat-value" style="color:var(--success)">${stats.class_avg||0}</div><div class="stat-label">Class Average</div></div></div>
              <div class="col-md-3 col-3 text-center"><div class="stat-card"><div class="stat-value" style="color:var(--warning)">${stats.highest||0}</div><div class="stat-label">Highest Avg</div></div></div>
              <div class="col-md-3 col-3 text-center"><div class="stat-card"><div class="stat-value" style="color:var(--danger)">${stats.lowest||0}</div><div class="stat-label">Lowest Avg</div></div></div>
            </div>
          </div>
        </div>
        <div class="content-card">
          <div class="table-wrapper">
            <table class="table">
              <thead><tr><th>Rank</th><th>Student Name</th><th>LRN</th><th>Section</th><th class="text-center">Average</th><th class="text-center">Description</th><th class="text-center">Remarks</th></tr></thead>
              <tbody>${rows.length ? rows.map((r,i)=>`<tr>
                <td class="text-center"><strong>${i+1}</strong></td>
                <td><strong>${r.full_name}</strong></td>
                <td><code style="font-size:.8rem">${r.lrn||'—'}</code></td>
                <td>${r.section_name||'—'}</td>
                <td class="text-center">${r.avg!=null?gradeCell(r.avg):'—'}</td>
                <td class="text-center"><span class="${r.avg!=null?gradeClass(r.avg):''}" style="font-size:.8rem">${r.avg!=null?getGradeDesc(r.avg):'—'}</span></td>
                <td class="text-center">${r.avg!=null?getGradeBadge(r.avg):'—'}</td>
              </tr>`).join('') : '<tr><td colspan="7" class="text-center py-4 text-muted">No student records found.</td></tr>'}</tbody>
            </table>
          </div>
          <div class="card-body-custom print-legend" style="border-top:1px solid #e2e8f0;font-size:.78rem;color:#64748b">
            <strong>Grading Scale:</strong> Outstanding (90-100) &nbsp;|&nbsp; Very Satisfactory (85-89) &nbsp;|&nbsp; Satisfactory (80-84) &nbsp;|&nbsp; Fairly Satisfactory (75-79) &nbsp;|&nbsp; Did Not Meet Expectations (Below 75)
          </div>
        </div>
        
        <div class="print-signatures no-screen">
          <div class="print-sig-box">
            <div class="print-sig-line"></div>
            <div class="print-sig-name">Prepared By</div>
            <div class="print-sig-role">Class Adviser / Teacher</div>
          </div>
          <div class="print-sig-box">
            <div class="print-sig-line"></div>
            <div class="print-sig-name">Verified By</div>
            <div class="print-sig-role">School Registrar</div>
          </div>
          <div class="print-sig-box">
            <div class="print-sig-line"></div>
            <div class="print-sig-name">Approved By</div>
            <div class="print-sig-role">School Principal</div>
          </div>
        </div>`;

    } else if (type === 'subject') {
      const rows = data.subjects || [];
      document.getElementById('reportContent').innerHTML = `
        <div class="content-card">
          <div class="card-header-custom" style="background:#0f172a;border-radius:4px 4px 0 0">
            <span class="card-title" style="color:#fff">Subject Performance Report</span>
            <span style="font-size:.8rem;color:rgba(255,255,255,.8)">Generated: ${date}</span>
          </div>
          <div class="table-wrapper">
            <table class="table">
              <thead><tr><th>Subject</th><th class="text-center">Class Avg</th><th class="text-center">Highest</th><th class="text-center">Lowest</th><th class="text-center">Passed</th><th class="text-center">Failed</th></tr></thead>
              <tbody>${rows.length ? rows.map(r=>`<tr>
                <td><strong>${r.name}</strong></td>
                <td class="text-center">${r.avg!=null?gradeCell(r.avg):'—'}</td>
                <td class="text-center" style="color:var(--success);font-weight:700">${r.highest||'—'}</td>
                <td class="text-center" style="color:var(--danger);font-weight:700">${r.lowest||'—'}</td>
                <td class="text-center"><span class="badge bg-success">${r.pass_count||0}</span></td>
                <td class="text-center"><span class="badge bg-danger">${r.fail_count||0}</span></td>
              </tr>`).join('') : '<tr><td colspan="6" class="text-center py-4 text-muted">No subject records found.</td></tr>'}</tbody>
            </table>
          </div>
          <div class="card-body-custom print-legend" style="border-top:1px solid #e2e8f0;font-size:.78rem;color:#64748b">
            <strong>Passing Standard:</strong> 75.00 &nbsp;|&nbsp; <strong>Target Mastery:</strong> 85.00 and above
          </div>
        </div>
        
        <div class="print-signatures no-screen">
          <div class="print-sig-box">
            <div class="print-sig-line"></div>
            <div class="print-sig-name">Prepared By</div>
            <div class="print-sig-role">School Administrator</div>
          </div>
          <div class="print-sig-box">
            <div class="print-sig-line"></div>
            <div class="print-sig-name">Approved By</div>
            <div class="print-sig-role">School Principal</div>
          </div>
        </div>`;

    } else {
      const stuId = document.getElementById('reportStudent').value;
      const advInput = document.getElementById('reportAdviser');
      const prinInput = document.getElementById('reportPrincipal');

      const stored = getStoredSignatories(stuId);
      if (advInput && !advInput.value.trim()) {
        advInput.value = data.adviser_name || stored.adviser;
      }
      if (prinInput && !prinInput.value.trim()) {
        prinInput.value = data.school_head || stored.schoolHead;
      }

      document.getElementById('reportContent').innerHTML = renderSf9ReportCard(data, {
        assetPrefix: '../../assets/images/',
        adviser: advInput ? advInput.value.trim() : undefined,
        schoolHead: prinInput ? prinInput.value.trim() : undefined,
        period: parseInt(period)
      });

      // Synchronize in case user uses inline "Edit Sheet" directly on the printable card
      const advEl = document.getElementById('sf9AdviserName');
      if (advEl) {
        advEl.addEventListener('input', () => {
          const val = advEl.textContent.trim();
          if (advInput) advInput.value = val;
          if (stuId) localStorage.setItem('ogms_sf9_adviser_' + stuId, val);
          localStorage.setItem('ogms_sf9_adviser_default', val);
        });
      }
      const headEl = document.getElementById('sf9SchoolHeadName');
      if (headEl) {
        headEl.addEventListener('input', () => {
          const val = headEl.textContent.trim();
          if (prinInput) prinInput.value = val;
          if (stuId) localStorage.setItem('ogms_sf9_head_' + stuId, val);
          localStorage.setItem('ogms_sf9_head_default', val);
        });
      }
    }
  }

  async function init() {
    try {
      const res  = await fetch('../../api/students.php?action=list');
      const data = await res.json();
      studentsCache = data.data || [];
      const sel = document.getElementById('reportStudent');
      sel.innerHTML = '';
      studentsCache.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = `${s.full_name}${s.section_name ? ' (' + s.section_name + ')' : ''}`;
        sel.appendChild(opt);
      });
    } catch(e) {
      console.error('Error fetching students:', e);
    }
    generateReport();
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
