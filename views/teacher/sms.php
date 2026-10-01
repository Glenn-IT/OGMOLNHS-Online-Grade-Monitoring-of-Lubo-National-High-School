<?php
// views/teacher/sms.php
// Teacher Grade SMS Notifications – LNHS OGMS
// Scoped exclusively to the teacher's assigned teaching subject(s) & grade levels.

require_once '../../config/session.php';
requireTeacher();
$teacherActivePage = 'sms';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Subject Grade SMS Alerts – Faculty Portal | Lubo NHS</title>
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
          <div class="topbar-title">Subject Grade SMS Notifications</div>
          <div class="topbar-subtitle">Send automated grade notifications for your teaching subject(s) directly to parents/guardians</div>
        </div>
      </div>
    </header>

    <main class="page-content fade-in">
      <div class="row g-3">
        <!-- Automatic Grade SMS Setup -->
        <div class="col-lg-6">
          <div class="content-card h-100">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
              <span class="card-title"><i class="fas fa-paper-plane me-2 text-primary"></i>Send Subject Grade SMS</span>
              <span class="badge bg-primary-subtle text-primary fw-semibold" id="activeSyBadge">SY Loading…</span>
            </div>
            <div class="card-body-custom">

              <!-- Assigned Teaching Subject Selector -->
              <div class="mb-3">
                <label class="form-label fw-bold" style="font-size:0.85rem">My Teaching Subject</label>
                <select id="subjectSelect" class="form-select" onchange="onSubjectChange()">
                  <option value="">Loading assigned subjects…</option>
                </select>
                <div class="form-text" style="font-size:0.75rem">SMS text will exclusively contain grades for your selected teaching subject.</div>
              </div>
              
              <!-- SMS Focus / Mode -->
              <div class="mb-3">
                <label class="form-label fw-bold" style="font-size:0.85rem">Grade Notification Focus</label>
                <select id="smsMode" class="form-select" onchange="onModeOrTermChange()">
                  <option value="term_grades">📊 Term Grade Notification (1st - 3rd Term)</option>
                  <option value="final_average">🏆 Final Subject Grade (End of Year)</option>
                  <option value="failing_alert">⚠️ Failing Grade Alert (Subject Deficiency)</option>
                </select>
              </div>

              <div class="row g-2 mb-3">
                <!-- School Year -->
                <div class="col-md-6">
                  <label class="form-label" style="font-size:0.82rem">School Year</label>
                  <select id="schoolYearSelect" class="form-select" onchange="onModeOrTermChange()"></select>
                </div>
                <!-- Term Selection -->
                <div class="col-md-6" id="quarterGroup">
                  <label class="form-label" style="font-size:0.82rem">Select Term</label>
                  <select id="quarterSelect" class="form-select" onchange="onModeOrTermChange()">
                    <option value="1">1st Term</option>
                    <option value="2">2nd Term</option>
                    <option value="3">3rd Term</option>
                  </select>
                </div>
              </div>

              <!-- Recipient Selection -->
              <div class="mb-3">
                <label class="form-label fw-bold" style="font-size:0.85rem">Target Parent Recipients</label>
                <select id="recipientType" class="form-select" onchange="toggleRecipientType()">
                  <option value="single">Single Student Parent</option>
                  <option value="section">By Class Section</option>
                  <option value="failed">Learners with Failed / Deficient Grades in My Subject</option>
                  <option value="all">All Enrolled Learners in My Classes</option>
                </select>
              </div>

              <!-- Single Student Select -->
              <div class="mb-3" id="singleRecipientGroup">
                <label class="form-label" style="font-size:0.82rem">Select Learner</label>
                <select id="studentSelect" class="form-select" onchange="fetchPreview()">
                  <option value="">-- Select Learner --</option>
                </select>
              </div>

              <!-- Section Select -->
              <div class="mb-3" id="sectionRecipientGroup" style="display:none">
                <label class="form-label" style="font-size:0.82rem">Select Class Section</label>
                <select id="sectionSelect" class="form-select">
                  <option value="">-- Select Section --</option>
                </select>
              </div>

              <!-- Student & Parent Details Card (for single student) -->
              <div id="studentDetailCard" class="p-2.5 mb-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;display:none;font-size:0.82rem">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <div><strong>Learner:</strong> <span id="previewStudentName">—</span></div>
                  <div><strong>Section:</strong> <span id="previewGradeSection">—</span></div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                  <div><strong>Parent/Guardian:</strong> <span id="previewGuardianName">—</span></div>
                  <div>
                    <strong>Parent Phone:</strong> <span id="previewPhone" class="fw-bold text-primary">—</span>
                    <span id="phoneSourceBadge" class="badge bg-secondary ms-1" style="font-size:0.65rem"></span>
                  </div>
                </div>
              </div>

              <!-- Automatic System SMS Content Display -->
              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="fw-bold" style="font-size:0.85rem"><i class="fas fa-magic me-1 text-primary"></i>Subject SMS Preview</span>
                  <span id="charCount" class="badge bg-secondary" style="font-size:0.72rem">0 chars (0 SMS)</span>
                </div>
                
                <div id="autoSmsBox" class="p-3 rounded border" style="background:#f8fafc;min-height:90px;font-size:0.85rem;color:#1e293b;line-height:1.4">
                  <em class="text-muted"><i class="fas fa-info-circle me-1"></i>Select a learner to preview automated subject grade SMS...</em>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary flex-grow-1" id="refreshBtn" onclick="fetchPreview()">
                  <i class="fas fa-sync-alt me-1"></i>Refresh Preview
                </button>
                <button class="btn btn-primary flex-grow-2" id="sendSmsBtn" onclick="sendSMS()" style="min-width:170px">
                  <i class="fas fa-paper-plane me-2"></i>Send Subject SMS
                </button>
              </div>

            </div>
          </div>
        </div>

        <!-- Log & Queue Status -->
        <div class="col-lg-6">
          <div class="content-card h-100">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
              <span class="card-title"><i class="fas fa-history me-2 text-success"></i>My Sent SMS Dispatches</span>
              <button class="btn-sm-custom btn-delete" onclick="clearLog()">
                <i class="fas fa-trash me-1"></i> Clear My Log
              </button>
            </div>
            <div class="card-body-custom" id="smsLogContainer" style="max-height:560px;overflow-y:auto">
              <div class="empty-state" id="emptyLog">
                <i class="fas fa-comments"></i>
                <p>No SMS grade notifications dispatched yet.</p>
              </div>
              <div id="smsLogList"></div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     SENDING SMS OVERLAY MODAL (Prevents Refresh & Tab Closing)
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="sendingSmsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center p-4" style="background:#0c1326;color:#fff;border-radius:12px;border:1px solid #1e293b;box-shadow:0 15px 35px rgba(0,0,0,0.6)">
      <div class="modal-body py-4">
        <div class="spinner-border text-primary mb-3" style="width:3.8rem;height:3.8rem;border-width:0.35em" role="status">
          <span class="visually-hidden">Sending...</span>
        </div>
        <h4 class="fw-bold mb-2" id="sendingTitle">Dispatching Subject Grade SMS...</h4>
        <p class="text-light opacity-75 mb-3" id="sendingSubtitle" style="font-size:0.9rem">
          Please wait while PhilSMS processes and delivers the subject grade notification(s) to parent contact numbers.
        </p>
        <div class="p-2.5 rounded text-start" style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);font-size:0.8rem">
          <i class="fas fa-exclamation-triangle text-warning me-1"></i> <strong>Do not close, refresh, or switch tabs</strong> until sending is complete to prevent duplicate transmissions.
        </div>
      </div>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
  let optionsData = null;
  let sendingModalInstance = null;

  // DepEd Filipino compound surname helper
  function formatLastFirst(fullName) {
    if (!fullName) return '';
    const str = String(fullName).trim();
    if (str.includes(',')) {
      const parts = str.split(',').map(p => p.trim());
      return `${parts[0]}, ${parts.slice(1).join(' ')}`;
    }
    const parts = str.split(/\s+/);
    if (parts.length === 1) return parts[0];

    const lower = parts.map(p => p.toLowerCase());
    const len = parts.length;
    let lastIdx = len - 1;
    const prefixes = ['dela', 'delos', 'san', 'santa', 'sta', 'sta.', 'sto', 'sto.', 'del', 'de', 'van', 'von', 'mc', 'mac'];

    if (len >= 4 && lower[len - 3] === 'de' && lower[len - 2] === 'la') {
      lastIdx = len - 3;
    } else if (len >= 3 && prefixes.includes(lower[len - 2])) {
      lastIdx = len - 2;
    }

    const lastName = parts.slice(lastIdx).join(' ');
    const firstName = parts.slice(0, lastIdx).join(' ');
    return `${lastName}, ${firstName}`;
  }

  async function init() {
    sendingModalInstance = new bootstrap.Modal(document.getElementById('sendingSmsModal'), {
      backdrop: 'static',
      keyboard: false
    });

    try {
      const res = await fetch('../../api/sms.php?action=options');
      optionsData = await res.json();

      if (optionsData.success) {
        document.getElementById('activeSyBadge').textContent = `Active SY: ${optionsData.active_sy_lbl}`;

        // Populate School Years
        const sySel = document.getElementById('schoolYearSelect');
        sySel.innerHTML = (optionsData.school_years || []).map(s => 
          `<option value="${s.id}" ${s.is_active==1?'selected':''}>${s.label}${s.is_active==1?' (Active)':''}</option>`
        ).join('');

        // Populate Teaching Subjects
        const subSel = document.getElementById('subjectSelect');
        const subjects = optionsData.subjects || [];
        if (subjects.length === 0) {
          subSel.innerHTML = `<option value="">No Teaching Subjects Assigned</option>`;
        } else {
          let opts = '';
          if (subjects.length > 1) {
            opts += `<option value="all">All My Teaching Subjects</option>`;
          }
          opts += subjects.map(s => {
            const grLabel = s.grade_level ? ` (Grade ${s.grade_level})` : '';
            return `<option value="${s.id}" data-grade="${s.grade_level||''}">${s.name}${grLabel}</option>`;
          }).join('');
          subSel.innerHTML = opts;
        }

        // Populate Sections and Students based on active subject selection
        refreshSectionsAndStudents();
      }

      loadLog();
    } catch(e) {
      console.error('Init error:', e);
      showToast('Failed to load SMS options.', 'error');
    }
  }

  function onSubjectChange() {
    refreshSectionsAndStudents();
    onModeOrTermChange();
  }

  function refreshSectionsAndStudents() {
    const subSel = document.getElementById('subjectSelect');
    const selectedOpt = subSel.options[subSel.selectedIndex];
    const gradeFilter = selectedOpt ? selectedOpt.getAttribute('data-grade') : '';

    const allSections = optionsData.sections || [];
    const allStudents = optionsData.students || [];

    // Filter sections by grade if subject has specific grade_level
    const filteredSections = gradeFilter 
      ? allSections.filter(sec => String(sec.grade_level) === String(gradeFilter))
      : allSections;

    // Filter students by grade if subject has specific grade_level
    const filteredStudents = gradeFilter
      ? allStudents.filter(stu => String(stu.grade_level) === String(gradeFilter))
      : allStudents;

    // Populate Sections Dropdown
    const secSel = document.getElementById('sectionSelect');
    secSel.innerHTML = `<option value="">-- Select Section --</option>` +
      filteredSections.map(sec => `<option value="${sec.id}">Grade ${sec.grade_level} - ${sec.name}</option>`).join('');

    // Format students to DepEd standard Lastname, Firstname and sort alphabetically
    const formattedStudents = filteredStudents.map(s => {
      return {
        ...s,
        displayName: formatLastFirst(s.full_name)
      };
    });
    formattedStudents.sort((a, b) => a.displayName.localeCompare(b.displayName, undefined, { sensitivity: 'base' }));

    // Populate Students Dropdown
    const stuSel = document.getElementById('studentSelect');
    stuSel.innerHTML = `<option value="">-- Select Learner --</option>` +
      formattedStudents.map(s => {
        const pPhone = s.guardian_phone || s.phone || 'No Phone';
        const pLabel = s.guardian_phone ? 'Parent' : 'Student Phone';
        const secLabel = s.grade_level ? `Gr.${s.grade_level}-${s.section_name}` : 'Unassigned';
        return `<option value="${s.id}">${s.displayName} (${secLabel}) – ${pLabel}: ${pPhone}</option>`;
      }).join('');
  }

  function toggleRecipientType() {
    const type = document.getElementById('recipientType').value;
    document.getElementById('singleRecipientGroup').style.display = type === 'single' ? 'block' : 'none';
    document.getElementById('sectionRecipientGroup').style.display = type === 'section' ? 'block' : 'none';
    document.getElementById('studentDetailCard').style.display = (type === 'single' && document.getElementById('studentSelect').value) ? 'block' : 'none';
    
    if (type === 'single') {
      fetchPreview();
    } else {
      updateBulkPreviewNotice();
    }
  }

  function onModeOrTermChange() {
    const mode = document.getElementById('smsMode').value;
    document.getElementById('quarterGroup').style.display = (mode === 'term_grades' || mode === 'failing_alert') ? 'block' : 'none';
    
    const recType = document.getElementById('recipientType').value;
    if (recType === 'single') {
      fetchPreview();
    } else {
      updateBulkPreviewNotice();
    }
  }

  function getSelectedSubjectInfo() {
    const subSel = document.getElementById('subjectSelect');
    if (!subSel || subSel.selectedIndex === -1) return { id: null, name: 'Subject' };
    const val = subSel.value;
    const txt = subSel.options[subSel.selectedIndex].text;
    return {
      id: (val && val !== 'all') ? val : null,
      name: txt
    };
  }

  function updateBulkPreviewNotice() {
    const mode = document.getElementById('smsMode').value;
    const q = document.getElementById('quarterSelect').value;
    const subInfo = getSelectedSubjectInfo();
    const termStr = (q==1?'1st Term':(q==2?'2nd Term':'3rd Term'));
    let focusName = mode === 'term_grades' 
      ? `${termStr} Grade Notification` 
      : (mode === 'final_average' ? 'Final Subject Grade Report' : 'Failing Grade Alert');

    document.getElementById('studentDetailCard').style.display = 'none';
    document.getElementById('autoSmsBox').innerHTML = `
      <div class="text-primary fw-semibold mb-1"><i class="fas fa-robot me-1"></i>Automated Group Dispatch: ${focusName} (${subInfo.name})</div>
      <div class="text-muted" style="font-size:0.82rem">The system will query grades strictly for your teaching subject (<strong>${subInfo.name}</strong>) for each learner in the recipient group and send personalized SMS text messages (&lt; 160 chars) directly to their parent/guardian.</div>
    `;
    updateCharCount(0);
  }

  async function fetchPreview() {
    const mode      = document.getElementById('smsMode').value;
    const quarter   = document.getElementById('quarterSelect').value;
    const syId      = document.getElementById('schoolYearSelect').value;
    const stuId     = document.getElementById('studentSelect').value;
    const recType   = document.getElementById('recipientType').value;
    const subInfo   = getSelectedSubjectInfo();

    if (recType !== 'single' || !stuId) {
      updateBulkPreviewNotice();
      return;
    }

    try {
      let url = `../../api/sms.php?action=preview&student_id=${stuId}&mode=${mode}&quarter=${quarter}&school_year_id=${syId}`;
      if (subInfo.id) {
        url += `&subject_id=${subInfo.id}`;
      }

      const res = await fetch(url);
      const data = await res.json();

      if (data.success) {
        document.getElementById('studentDetailCard').style.display = 'block';
        document.getElementById('previewStudentName').textContent  = formatLastFirst(data.student_name) || '—';
        document.getElementById('previewGuardianName').textContent = data.guardian_name || '—';
        document.getElementById('previewPhone').textContent        = data.phone || 'N/A';
        
        const badgeEl = document.getElementById('phoneSourceBadge');
        badgeEl.textContent = data.phone_source || '';
        badgeEl.className = data.phone_source === 'Parent Phone' ? 'badge bg-success ms-1' : 'badge bg-warning text-dark ms-1';
        
        document.getElementById('previewGradeSection').textContent = data.grade_section || data.section_name || 'Unassigned';
        
        document.getElementById('autoSmsBox').innerHTML = `
          <div class="fw-semibold text-dark">"${data.message}"</div>
        `;
        updateCharCount(data.message.length);
      }
    } catch(e) {
      console.error('Preview error:', e);
    }
  }

  function updateCharCount(len) {
    const smsCount = len === 0 ? 0 : Math.ceil(len / 160);
    const el = document.getElementById('charCount');
    el.textContent = `${len} chars (${smsCount} SMS)`;
    el.className = len > 160 ? 'badge bg-danger' : 'badge bg-secondary';
  }

  // Prevent accidental unload during active SMS transmission
  function preventUnloadHandler(e) {
    e.preventDefault();
    e.returnValue = 'SMS dispatch is currently in progress. Closing or refreshing may cause duplicate transmissions.';
    return e.returnValue;
  }

  function lockUI(isSending, messageTitle = 'Dispatching Grade SMS...') {
    const sendBtn = document.getElementById('sendSmsBtn');
    const refreshBtn = document.getElementById('refreshBtn');
    
    if (isSending) {
      document.getElementById('sendingTitle').textContent = messageTitle;
      sendingModalInstance.show();
      sendBtn.disabled = true;
      refreshBtn.disabled = true;
      sendBtn.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>Sending...`;
      window.addEventListener('beforeunload', preventUnloadHandler);
    } else {
      sendingModalInstance.hide();
      sendBtn.disabled = false;
      refreshBtn.disabled = false;
      sendBtn.innerHTML = `<i class="fas fa-paper-plane me-2"></i>Send Subject SMS`;
      window.removeEventListener('beforeunload', preventUnloadHandler);
    }
  }

  async function sendSMS() {
    const mode     = document.getElementById('smsMode').value;
    const quarter  = document.getElementById('quarterSelect').value;
    const syId     = document.getElementById('schoolYearSelect').value;
    const recType  = document.getElementById('recipientType').value;
    const stuId    = document.getElementById('studentSelect').value;
    const secId    = document.getElementById('sectionSelect').value;
    const subInfo  = getSelectedSubjectInfo();

    if (recType === 'single' && !stuId) {
      showToast('Please select a learner to send SMS.', 'error');
      return;
    }
    if (recType === 'section' && !secId) {
      showToast('Please select a class section.', 'error');
      return;
    }

    const body = new FormData();
    body.append('action', 'send');
    body.append('mode', mode);
    body.append('quarter', quarter);
    body.append('school_year_id', syId);
    body.append('recipient_type', recType);
    if (subInfo.id) body.append('subject_id', subInfo.id);
    if (stuId) body.append('student_id', stuId);
    if (secId) body.append('section_id', secId);

    lockUI(true, recType === 'single' ? 'Sending Subject Grade SMS...' : 'Dispatching Group Subject SMS...');

    try {
      const res = await fetch('../../api/sms.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        showToast(data.message || `Subject Grade SMS successfully dispatched!`, 'success');
        loadLog();
      } else {
        showToast(data.message || 'Failed to send SMS.', 'error');
      }
    } catch(e) {
      showToast('Server connection error.', 'error');
    } finally {
      lockUI(false);
    }
  }

  async function loadLog() {
    try {
      const res  = await fetch('../../api/sms.php?action=logs');
      const data = await res.json();
      const log  = data.data || [];
      const empty = document.getElementById('emptyLog');
      const list  = document.getElementById('smsLogList');

      if (!log.length) { 
        empty.style.display = 'block'; 
        list.innerHTML = ''; 
        return; 
      }
      empty.style.display = 'none';
      list.innerHTML = log.map(e => `
        <div class="sms-log-item mb-2 p-2.5 rounded border" style="background:#ffffff">
          <div class="d-flex justify-content-between align-items-start mb-1">
            <div>
              <div class="sms-to font-weight-bold" style="font-size:0.875rem">
                <i class="fas fa-user-circle me-1 text-primary"></i>${e.recipient_name||'Parent/Guardian'}
              </div>
              <div style="font-size:0.75rem;color:#64748b"><i class="fas fa-phone-alt me-1" style="font-size:0.65rem"></i>${e.recipient_phone}</div>
            </div>
            <div class="text-end">
              <span class="badge ${e.status==='sent'?'bg-success':e.status==='failed'?'bg-danger':'bg-warning text-dark'}" style="font-size:0.68rem;padding:0.25em 0.6em">${e.status.toUpperCase()}</span>
              <div class="sms-time text-muted" style="font-size:0.7rem;margin-top:2px">${e.sent_at||e.created_at}</div>
            </div>
          </div>
          <div class="sms-msg p-2 rounded" style="font-size:0.8rem;background:#f8fafc;color:#334155;border-left:3px solid #3b82f6">
            "${e.message}"
          </div>
        </div>`).join('');
    } catch(e) { 
      console.error('SMS log error:', e); 
    }
  }

  async function clearLog() {
    if (!confirm('Are you sure you want to clear your dispatched SMS notification logs?')) return;
    const body = new FormData();
    body.append('action', 'clear_logs');
    await fetch('../../api/sms.php', { method: 'POST', body });
    loadLog();
    showToast('Your SMS logs have been cleared.', 'info');
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
