/**
 * SF9 REPORT CARD RENDERER
 * Lubo National High School - DepEd Form 9 Component
 * Shared across Admin Reports, Student Portal, and SF9 Print Views
 */

function renderSf9ReportCard(data, options = {}) {
  const student = data.student || {};
  const subjects = data.subjects || [];
  const genAvg = (data.general_average !== null && data.general_average !== undefined) 
                 ? parseFloat(data.general_average).toFixed(2) : '—';
  const genRemark = (genAvg !== '—') 
                    ? (parseFloat(genAvg) >= 75 ? 'Passed' : 'Failed') : '—';

  const gradeLevel = parseInt(student.grade_level) || 7;
  const isJhs = gradeLevel <= 10;
  
  const fullName = (student.full_name || '—').toUpperCase();
  const lrn = student.lrn || '—';
  const age = student.age || (gradeLevel + 6);
  const sex = student.gender || 'Male';
  const section = student.section_name || '—';
  const sy = data.school_year || '2026 - 2027';
  const curriculum = data.curriculum || (isJhs ? 'Junior High School (K to 12 Basic Education Curriculum)' : 'Senior High School (TVL - ICT / Academic Track)');
  const nextGrade = data.next_grade || (isJhs ? `Grade ${gradeLevel + 1}` : 'Graduated / Higher Education (Tertiary)');
  const adviser = escapeHtml(options.adviser !== undefined && options.adviser !== null && options.adviser !== ''
    ? options.adviser 
    : (data.adviser_name || 'JOSEPH M. BATUYONG'));
  const schoolHead = escapeHtml(options.schoolHead !== undefined && options.schoolHead !== null && options.schoolHead !== ''
    ? options.schoolHead 
    : (data.school_head || 'MARLON C. VALIENTES'));

  // Determine root path for images
  const assetPrefix = options.assetPrefix || '../../assets/images/';
  const depedLogo = `${assetPrefix}deped_logo.png`;
  const luboLogo = `${assetPrefix}lubo_logo.png`;

  const selectedPeriod = parseInt(options.period ?? data.selected_term ?? 0);
  const periodBadge = selectedPeriod > 0 
    ? `Term ${selectedPeriod} Progress` 
    : 'All Terms';

  // Helper to safely format a grade value: returns rounded integer or '—'
  const fmtG = val => (val !== null && val !== undefined && val !== '' && !isNaN(Number(val))) ? Math.round(Number(val)) : '—';

  // Build subject rows
  let subjectRowsHtml = '';
  const allFinals = [];

  if (isJhs) {
    // Map subjects for JHS
    subjects.forEach(sub => {
      let rawQ1 = sub.q1 ?? sub.term1;
      let rawQ2 = sub.q2 ?? sub.term2;
      let rawQ3 = sub.q3 ?? sub.term3;

      if (selectedPeriod > 0) {
        if (selectedPeriod < 1) rawQ1 = null;
        if (selectedPeriod < 2) rawQ2 = null;
        if (selectedPeriod < 3) rawQ3 = null;
      }

      const q1 = fmtG(rawQ1);
      const q2 = fmtG(rawQ2);
      const q3 = fmtG(rawQ3);

      // Compute final grade from valid numbers
      const validTerms = [q1, q2, q3].filter(v => typeof v === 'number');
      let finalG = '—';
      let remark = '—';
      let remarkColor = '#000000';

      if (validTerms.length > 0) {
        finalG = Math.round(validTerms.reduce((a, b) => a + b, 0) / validTerms.length);
        remark = finalG >= 75 ? 'Passed' : 'Failed';
        remarkColor = remark === 'Failed' ? '#dc2626' : '#000000';
        allFinals.push(finalG);
      }

      subjectRowsHtml += `
        <tr data-subject="${escapeHtml(sub.name)}">
          <td class="subj-name">${escapeHtml(sub.name)}</td>
          <td class="text-center sf9-grade-cell" contenteditable="false">${q1}</td>
          <td class="text-center sf9-grade-cell" contenteditable="false">${q2}</td>
          <td class="text-center sf9-grade-cell" contenteditable="false">${q3}</td>
          <td class="text-center final-cell fw-bold">${finalG}</td>
          <td class="text-center remark-cell" style="color:${remarkColor}">${remark}</td>
        </tr>
      `;

      // If subject is MAPEH, add indented sub-components
      if (sub.name.toUpperCase().includes('MAPEH') || (sub.code && sub.code.toUpperCase() === 'MAPEH')) {
        const mapehSubs = [
          { name: 'Music', grades: [q1, q2, q3] },
          { name: 'Arts', grades: [q1, q2, q3] },
          { name: 'Physical Education (PE)', grades: [q1, q2, q3] },
          { name: 'Health', grades: [q1, q2, q3] }
        ];
        mapehSubs.forEach(m => {
          const subValid = m.grades.filter(v => typeof v === 'number');
          const subFinal = subValid.length > 0 ? Math.round(subValid.reduce((a, b) => a + b, 0) / subValid.length) : '—';
          const subRemark = subFinal !== '—' ? (subFinal >= 75 ? 'Passed' : 'Failed') : '—';
          const subRemarkColor = subRemark === 'Failed' ? '#dc2626' : '#000000';

          subjectRowsHtml += `
            <tr class="sub-row" data-subject="${m.name}">
              <td class="subj-name">${m.name}</td>
              <td class="text-center sf9-grade-cell" contenteditable="false">${m.grades[0]}</td>
              <td class="text-center sf9-grade-cell" contenteditable="false">${m.grades[1]}</td>
              <td class="text-center sf9-grade-cell" contenteditable="false">${m.grades[2]}</td>
              <td class="text-center final-cell fw-bold">${subFinal}</td>
              <td class="text-center remark-cell" style="color:${subRemarkColor}">${subRemark}</td>
            </tr>
          `;
        });
      }
    });

  } else {
    // SHS with groupings (Core, Applied, Specialized)
    const coreList = [];
    const appliedList = [];
    const specList = [];

    subjects.forEach((sub, idx) => {
      if (idx < 5) coreList.push(sub);
      else if (idx < 9) appliedList.push(sub);
      else specList.push(sub);
    });

    const groups = [
      { name: 'Core Subjects', list: coreList },
      { name: 'Applied Subjects', list: appliedList },
      { name: 'Specialized Subjects', list: specList }
    ];

    groups.forEach(grp => {
      if (grp.list.length > 0) {
        subjectRowsHtml += `<tr class="group-row"><td colspan="6">${grp.name}</td></tr>`;
        grp.list.forEach(sub => {
          let rawQ1 = sub.q1 ?? sub.term1;
          let rawQ2 = sub.q2 ?? sub.term2;
          let rawQ3 = sub.q3 ?? sub.term3;

          if (selectedPeriod > 0) {
            if (selectedPeriod < 1) rawQ1 = null;
            if (selectedPeriod < 2) rawQ2 = null;
            if (selectedPeriod < 3) rawQ3 = null;
          }

          const q1 = fmtG(rawQ1);
          const q2 = fmtG(rawQ2);
          const q3 = fmtG(rawQ3);

          const validTerms = [q1, q2, q3].filter(v => typeof v === 'number');
          let finalG = '—';
          let remark = '—';
          let remarkColor = '#000000';

          if (validTerms.length > 0) {
            finalG = Math.round(validTerms.reduce((a, b) => a + b, 0) / validTerms.length);
            remark = finalG >= 75 ? 'Passed' : 'Failed';
            remarkColor = remark === 'Failed' ? '#dc2626' : '#000000';
            allFinals.push(finalG);
          }

          subjectRowsHtml += `
            <tr data-subject="${escapeHtml(sub.name)}">
              <td class="subj-name">${escapeHtml(sub.name)}</td>
              <td class="text-center sf9-grade-cell" contenteditable="false">${q1}</td>
              <td class="text-center sf9-grade-cell" contenteditable="false">${q2}</td>
              <td class="text-center sf9-grade-cell" contenteditable="false">${q3}</td>
              <td class="text-center final-cell fw-bold">${finalG}</td>
              <td class="text-center remark-cell" style="color:${remarkColor}">${remark}</td>
            </tr>
          `;
        });
      }
    });
  }

  const trackTitle = isJhs ? 'Curriculum:' : 'Track / Strand:';

  const finalGenAvg = (allFinals.length > 0)
    ? (allFinals.reduce((a, b) => a + b, 0) / allFinals.length).toFixed(2)
    : genAvg;
  const finalGenRemark = (finalGenAvg !== '—')
    ? (parseFloat(finalGenAvg) >= 75 ? 'Passed' : 'Failed')
    : '—';

  return `
    <!-- SF9 Action Controls (Screen Only) -->
    <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded no-print" style="background:#0f172a;color:#fff">
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary px-2 py-1"><i class="fas fa-file-alt me-1"></i>Official DepEd SF9 &bull; ${periodBadge}</span>
        <span class="small text-white-50">US Letter Landscape (11" &times; 8.5")</span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-warning btn-sm py-1 px-2" onclick="toggleSf9InlineEdit(this)">
          <i class="fas fa-edit me-1"></i><span>Edit Sheet</span>
        </button>
        <button class="btn btn-success btn-sm py-1 px-3" onclick="printSf9Official()">
          <i class="fas fa-print me-1"></i>Print / Save SF9 PDF
        </button>
      </div>
    </div>

    <!-- SF9 Screen Wrapper -->
    <div class="sf9-wrapper">
      <div class="sf9-sheet" id="sf9PrintContainer">

        <!-- =======================================================
             LEFT PANEL: ACADEMIC PERFORMANCE & EVALUATION
             ======================================================= -->
        <section class="sf9-panel">
          
          <!-- Header with Seals -->
          <table class="sf9-header-table">
            <tr>
              <td style="width: 54px; text-align: left;">
                <img src="${depedLogo}" alt="DepEd Seal" class="sf9-logo">
              </td>
              <td class="sf9-header-center">
                <div class="sf9-dept">Republic of the Philippines</div>
                <div class="sf9-dept">Department of Education</div>
                <div class="sf9-dept">Region 02</div>
                <div class="sf9-division">SCHOOLS DIVISION OF CAGAYAN</div>
                <div class="sf9-dept">Sto. Niño District</div>
                <div class="sf9-dept">Sto. Niño, Cagayan</div>
                <div class="sf9-school">LUBO NATIONAL HIGH SCHOOL</div>
              </td>
              <td style="width: 54px; text-align: right;">
                <img src="${luboLogo}" alt="Lubo NHS Seal" class="sf9-logo">
              </td>
            </tr>
          </table>

          <!-- School Year -->
          <div class="sf9-sy">School Year ${sy}</div>

          <!-- Form Title -->
          <div class="sf9-title">LEARNER’S PROGRESS REPORT CARD (SF9)</div>

          <!-- Student Personal Details -->
          <div class="sf9-info-grid">
            <div class="sf9-info-item">
              <span class="sf9-info-label">Name:</span>
              <span class="sf9-info-line" contenteditable="false">${fullName}</span>
            </div>
            <div class="sf9-info-item">
              <span class="sf9-info-label">Age:</span>
              <span class="sf9-info-line text-center" contenteditable="false">${age}</span>
            </div>
            <div class="sf9-info-item">
              <span class="sf9-info-label">Sex:</span>
              <span class="sf9-info-line text-center" contenteditable="false">${sex}</span>
            </div>

            <div class="sf9-info-item">
              <span class="sf9-info-label">LRN:</span>
              <span class="sf9-info-line" contenteditable="false">${lrn}</span>
            </div>
            <div class="sf9-info-item">
              <span class="sf9-info-label">Grade:</span>
              <span class="sf9-info-line text-center" contenteditable="false">${gradeLevel}</span>
            </div>
            <div class="sf9-info-item">
              <span class="sf9-info-label">Section:</span>
              <span class="sf9-info-line text-center" contenteditable="false">${section}</span>
            </div>

            <div class="sf9-info-item" style="grid-column: span 3;">
              <span class="sf9-info-label">${trackTitle}</span>
              <span class="sf9-info-line" contenteditable="false">${curriculum}</span>
            </div>
          </div>

          <!-- Dear Parents Message -->
          <div class="sf9-parents-msg">
            <strong>Dear Parents:</strong>
            This Performance Report shows the ability and progress your child has made in the different learning areas as well as his/her core values. The school welcomes you should you desire to know more about your child’s progress.
          </div>

          <!-- Learning Progress Header -->
          <div class="sf9-section-title">LEARNING PROGRESS AND ACHIEVEMENT</div>

          <!-- Grades Table -->
          <table class="sf9-table" id="sf9GradesTable">
            <thead>
              <tr>
                <th rowspan="2" style="width: 48%;">Learning Areas</th>
                <th colspan="3" style="width: 26%;">TERM</th>
                <th rowspan="2" style="width: 13%;">Final Grade</th>
                <th rowspan="2" style="width: 13%;">Remarks</th>
              </tr>
              <tr>
                <th style="width: 8.6%;" class="${selectedPeriod === 1 ? 'selected-term-header' : ''}">1</th>
                <th style="width: 8.6%;" class="${selectedPeriod === 2 ? 'selected-term-header' : ''}">2</th>
                <th style="width: 8.6%;" class="${selectedPeriod === 3 ? 'selected-term-header' : ''}">3</th>
              </tr>
            </thead>
            <tbody>
              ${subjectRowsHtml}
              <tr class="gen-avg-row">
                <td colspan="4" class="text-right fw-bold" style="padding-right: 8px;">General Average</td>
                <td class="text-center fw-bold" id="sf9GenAvgVal">${finalGenAvg}</td>
                <td class="text-center fw-bold" id="sf9GenAvgRemark" style="color:${finalGenRemark==='Failed'?'#dc2626':'#000000'}">${finalGenRemark}</td>
              </tr>
            </tbody>
          </table>

          <!-- Performance Descriptors Table -->
          <div class="sf9-section-title">PERFORMANCE DESCRIPTORS</div>
          <table class="sf9-desc-table">
            <thead>
              <tr>
                <th style="width: 32%;">Grading Scale</th>
                <th style="width: 42%;">Description</th>
                <th style="width: 26%;">Remarks</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="text-center">90 – 100</td>
                <td class="text-center">Advancing / Outstanding</td>
                <td class="text-center">Passed</td>
              </tr>
              <tr>
                <td class="text-center">80 – 89</td>
                <td class="text-center">Benchmarking / Very Satisfactory</td>
                <td class="text-center">Passed</td>
              </tr>
              <tr>
                <td class="text-center">75 – 79</td>
                <td class="text-center">Connecting / Fairly Satisfactory</td>
                <td class="text-center">Passed</td>
              </tr>
              <tr>
                <td class="text-center">65 – 74</td>
                <td class="text-center">Developing / Did Not Meet Expectations</td>
                <td class="text-center">Failed</td>
              </tr>
              <tr>
                <td class="text-center">Below 65</td>
                <td class="text-center">Emerging</td>
                <td class="text-center">Failed</td>
              </tr>
            </tbody>
          </table>

        </section>


        <!-- =======================================================
             RIGHT PANEL: ATTENDANCE, SIGNATURES & TRANSFER
             ======================================================= -->
        <section class="sf9-panel">

          <!-- Attendance Record Title -->
          <div class="sf9-section-title">ATTENDANCE RECORD</div>

          <!-- Attendance Table -->
          <table class="sf9-attendance-table">
            <thead>
              <tr>
                <th style="width: 23%;" class="text-start ps-1">Month</th>
                <th>Aug</th>
                <th>Sep</th>
                <th>Oct</th>
                <th>Nov</th>
                <th>Dec</th>
                <th>Jan</th>
                <th>Feb</th>
                <th>Mar</th>
                <th>Apr</th>
                <th>May</th>
                <th style="width: 9%;">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="row-label">No. of Class Days</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td class="fw-bold">-</td>
              </tr>
              <tr>
                <td class="row-label">No. of Days Present</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td class="fw-bold">-</td>
              </tr>
              <tr>
                <td class="row-label">No. of Days Absent</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td contenteditable="false">-</td>
                <td class="fw-bold">-</td>
              </tr>
            </tbody>
          </table>

          <!-- Parent / Guardian Signatures -->
          <div class="sf9-section-title">PARENT / GUARDIAN'S SIGNATURE</div>
          <div class="sf9-signatures-block">
            <div class="sf9-sig-row">
              <span class="sf9-sig-label">1st Term:</span>
              <div class="sf9-sig-line"></div>
            </div>
            <div class="sf9-sig-row">
              <span class="sf9-sig-label">2nd Term:</span>
              <div class="sf9-sig-line"></div>
            </div>
            <div class="sf9-sig-row">
              <span class="sf9-sig-label">3rd Term:</span>
              <div class="sf9-sig-line"></div>
            </div>
          </div>

          <!-- Certificate of Transfer -->
          <div class="sf9-section-title">CERTIFICATE OF TRANSFER</div>
          <div class="sf9-transfer-cert">
            <p>
              This is to certify that the above-named learner has satisfactorily completed the requirements for the grade level indicated.
            </p>
            <div class="sf9-transfer-field">
              <span>Admitted to Grade:</span>
              <span class="t-line" contenteditable="false">${nextGrade}</span>
            </div>
            <div class="sf9-transfer-field">
              <span>Eligible for Admission to Grade:</span>
              <span class="t-line" contenteditable="false">${nextGrade}</span>
            </div>

            <div class="sf9-transfer-signers">
              <div class="sf9-signer-box">
                <span class="text-muted small" style="font-size: 7.0pt; margin-bottom: 12px;">Approved by:</span>
                <div class="sf9-signer-name" id="sf9SchoolHeadName" contenteditable="false">${schoolHead}</div>
                <div class="sf9-signer-role">School Head / Principal</div>
              </div>
              <div class="sf9-signer-box">
                <span class="text-muted small" style="font-size: 7.0pt; margin-bottom: 12px;">Prepared by:</span>
                <div class="sf9-signer-name" id="sf9AdviserName" contenteditable="false">${adviser}</div>
                <div class="sf9-signer-role">Class Adviser</div>
              </div>
            </div>
          </div>

          <!-- Cancellation of Eligibility to Transfer -->
          <div class="sf9-section-title">CANCELLATION OF ELIGIBILITY TO TRANSFER</div>
          <div class="sf9-cancellation-cert">
            <div class="sf9-cancel-grid">
              <div class="sf9-transfer-field" style="margin-bottom: 0;">
                <span style="white-space: nowrap;">Admitted in:</span>
                <span class="t-line" contenteditable="false"></span>
              </div>
              <div class="sf9-transfer-field" style="margin-bottom: 0;">
                <span style="white-space: nowrap;">Date:</span>
                <span class="t-line" contenteditable="false"></span>
              </div>
            </div>

            <div class="d-flex justify-content-center mt-2">
              <div class="sf9-signer-box" style="width: 55%;">
                <div class="sf9-signer-name" contenteditable="false">&nbsp;</div>
                <div class="sf9-signer-role">School Head</div>
              </div>
            </div>
          </div>

        </section>

      </div>
    </div>
  `;
}

function escapeHtml(str) {
  return String(str ?? '').replace(/[&<>"']/g,
    c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function printSf9Official() {
  document.body.classList.add('printing-sf9');
  let styleTag = document.getElementById('sf9-print-page-override');
  if (!styleTag) {
    styleTag = document.createElement('style');
    styleTag.id = 'sf9-print-page-override';
    styleTag.textContent = '@page { size: letter landscape !important; margin: 0.22in 0.28in 0.18in 0.28in !important; }';
    document.head.appendChild(styleTag);
  }
  window.print();
}

if (typeof window !== 'undefined') {
  window.addEventListener('afterprint', () => {
    document.body.classList.remove('printing-sf9');
    const styleTag = document.getElementById('sf9-print-page-override');
    if (styleTag) styleTag.remove();
  });
}

function recalculateSf9Sheet() {
  const container = document.getElementById('sf9PrintContainer');
  if (!container) return;
  const rows = container.querySelectorAll('#sf9GradesTable tbody tr[data-subject]');
  const allFinals = [];

  rows.forEach(r => {
    const isSubRow = r.classList.contains('sub-row');
    const cells = r.querySelectorAll('.sf9-grade-cell');
    const vals = [];
    cells.forEach(c => {
      const txt = c.textContent.trim();
      const num = parseFloat(txt);
      if (!isNaN(num) && txt !== '' && txt !== '—' && txt !== '-') {
        vals.push(num);
      }
    });

    const finalCell = r.querySelector('.final-cell');
    const remarkCell = r.querySelector('.remark-cell');

    if (vals.length > 0) {
      const avg = vals.reduce((a, b) => a + b, 0) / vals.length;
      const rounded = Math.round(avg);
      if (finalCell) finalCell.textContent = rounded;
      if (!isSubRow) allFinals.push(rounded);

      if (remarkCell) {
        if (rounded >= 75) {
          remarkCell.textContent = 'Passed';
          remarkCell.style.color = '#000000';
        } else {
          remarkCell.textContent = 'Failed';
          remarkCell.style.color = '#dc2626';
        }
      }
    } else {
      if (finalCell) finalCell.textContent = '—';
      if (remarkCell) {
        remarkCell.textContent = '—';
        remarkCell.style.color = '#000000';
      }
    }
  });

  const genAvgEl = container.querySelector('#sf9GenAvgVal');
  const genRemarkEl = container.querySelector('#sf9GenAvgRemark');

  if (allFinals.length > 0) {
    const genAvg = (allFinals.reduce((a, b) => a + b, 0) / allFinals.length).toFixed(2);
    if (genAvgEl) genAvgEl.textContent = genAvg;
    if (genRemarkEl) {
      if (parseFloat(genAvg) >= 75) {
        genRemarkEl.textContent = 'Passed';
        genRemarkEl.style.color = '#000000';
      } else {
        genRemarkEl.textContent = 'Failed';
        genRemarkEl.style.color = '#dc2626';
      }
    }
  } else {
    if (genAvgEl) genAvgEl.textContent = '—';
    if (genRemarkEl) {
      genRemarkEl.textContent = '—';
      genRemarkEl.style.color = '#000000';
    }
  }
}

function recalculateSf9Attendance() {
  const container = document.getElementById('sf9PrintContainer');
  if (!container) return;
  const rows = container.querySelectorAll('.sf9-attendance-table tbody tr');
  rows.forEach(r => {
    const cells = r.querySelectorAll('td:not(.row-label):not(.sf9-att-label)');
    if (cells.length > 1) {
      let sum = 0;
      let hasVal = false;
      // All cells except the last (Total) column
      for (let i = 0; i < cells.length - 1; i++) {
        const txt = cells[i].textContent.trim();
        const val = parseFloat(txt);
        if (!isNaN(val) && txt !== '' && txt !== '-' && txt !== '—') {
          sum += val;
          hasVal = true;
        }
      }
      cells[cells.length - 1].textContent = hasVal ? sum : '-';
    }
  });
}

function toggleSf9InlineEdit(btn) {
  const container = document.getElementById('sf9PrintContainer');
  if (!container) return;
  const isEditing = container.classList.toggle('editable-active');
  const editables = container.querySelectorAll('[contenteditable]');
  editables.forEach(el => {
    el.setAttribute('contenteditable', isEditing ? 'true' : 'false');
  });

  if (isEditing) {
    btn.classList.remove('btn-outline-warning');
    btn.classList.add('btn-warning');
    btn.innerHTML = '<i class="fas fa-check me-1"></i><span>Done Editing</span>';

    // Bind real-time input recalculation
    const table = container.querySelector('#sf9GradesTable');
    if (table && !table.dataset.boundLive) {
      table.dataset.boundLive = '1';
      table.addEventListener('input', recalculateSf9Sheet);
    }
    const attTable = container.querySelector('.sf9-attendance-table');
    if (attTable && !attTable.dataset.boundLive) {
      attTable.dataset.boundLive = '1';
      attTable.addEventListener('input', recalculateSf9Attendance);
    }
  } else {
    btn.classList.remove('btn-warning');
    btn.classList.add('btn-outline-warning');
    btn.innerHTML = '<i class="fas fa-edit me-1"></i><span>Edit Sheet</span>';

    // When done editing, ensure any blank grade cells get a clean dash '—'
    const gradeCells = container.querySelectorAll('.sf9-grade-cell');
    gradeCells.forEach(c => {
      const txt = c.textContent.trim();
      if (txt === '' || txt === '-') {
        c.textContent = '—';
      }
    });

    // Ensure any blank attendance cells get '-'
    const attCells = container.querySelectorAll('.sf9-attendance-table tbody td:not(.row-label):not(.sf9-att-label)');
    attCells.forEach(c => {
      const txt = c.textContent.trim();
      if (txt === '' || txt === '—') {
        c.textContent = '-';
      }
    });

    recalculateSf9Sheet();
    recalculateSf9Attendance();
  }
}