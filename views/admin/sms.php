<?php
require_once '../../config/session.php';
requireAdmin();
$adminActivePage = 'sms';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Student Grade SMS Notifications – OGMS Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../../assets/css/style.css") ?>"/>
</head>
<body>
<div class="app-wrapper">
  <?php include '../../components/admin-sidebar.php'; ?>

  <div class="main-content">
    <header class="topbar">
      <div class="topbar-left">
        <button class="topbar-btn hamburger"><i class="fas fa-bars"></i></button>
        <div>
          <div class="topbar-title">Grade SMS Notifications</div>
          <div class="topbar-subtitle">Automated student grade SMS dispatches via PhilSMS API</div>
        </div>
      </div>
    </header>

    <main class="page-content fade-in">
      <div class="row g-3">
        <!-- Automatic Grade SMS Setup -->
        <div class="col-lg-6">
          <div class="content-card h-100">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
              <span class="card-title"><i class="fas fa-paper-plane me-2 text-primary"></i>Send Automated Grade SMS</span>
              <span class="badge bg-primary-subtle text-primary fw-semibold" id="activeSyBadge">SY Loading…</span>
            </div>
            <div class="card-body-custom">
              
              <!-- SMS Focus / Mode -->
              <div class="mb-3">
                <label class="form-label fw-bold" style="font-size:0.85rem">Select Grade Notification Focus</label>
                <select id="smsMode" class="form-select" onchange="onModeOrTermChange()">
                  <option value="term_grades">📊 Term Grade Summary (1st - 3rd Term)</option>
                  <option value="final_average">🏆 Final Grade Report (End of Year)</option>
                  <option value="failing_alert">⚠️ Failing Grade Alert (Deficiency Notice)</option>
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
                  <option value="section">By Grade &amp; Section</option>
                  <option value="failed">Students with Failed / Deficient Grades</option>
                  <option value="all">All Enrolled Students (Entire School)</option>
                </select>
              </div>

              <!-- Single Student Select -->
              <div class="mb-3" id="singleRecipientGroup">
                <label class="form-label" style="font-size:0.82rem">Select Student</label>
                <select id="studentSelect" class="form-select" onchange="fetchPreview()">
                  <option value="">-- Select Student --</option>
                </select>
              </div>

              <!-- Section Select -->
              <div class="mb-3" id="sectionRecipientGroup" style="display:none">
                <label class="form-label" style="font-size:0.82rem">Select Grade &amp; Section</label>
                <select id="sectionSelect" class="form-select">
                  <option value="">-- Select Section --</option>
                </select>
              </div>

              <!-- Student & Parent Details Card (for single student) -->
              <div id="studentDetailCard" class="p-2.5 mb-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;display:none;font-size:0.82rem">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <div><strong>Student:</strong> <span id="previewStudentName">—</span></div>
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
                  <span class="fw-bold" style="font-size:0.85rem"><i class="fas fa-magic me-1 text-primary"></i>System Automatic Grade SMS Preview</span>
                  <span id="charCount" class="badge bg-secondary" style="font-size:0.72rem">0 chars (0 SMS)</span>
                </div>
                
                <div id="autoSmsBox" class="p-3 rounded border" style="background:#f8fafc;min-height:90px;font-size:0.85rem;color:#1e293b;line-height:1.4">
                  <em class="text-muted"><i class="fas fa-info-circle me-1"></i>Select a student to preview automated grade SMS...</em>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary flex-grow-1" id="refreshBtn" onclick="fetchPreview()">
                  <i class="fas fa-sync-alt me-1"></i>Refresh Preview
                </button>
                <button class="btn btn-primary flex-grow-2" id="sendSmsBtn" onclick="sendSMS()" style="min-width:170px">
                  <i class="fas fa-paper-plane me-2"></i>Send Grade SMS
                </button>
              </div>

            </div>
          </div>
        </div>

        <!-- Log & Queue Status -->
        <div class="col-lg-6">
          <div class="content-card h-100">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
              <span class="card-title"><i class="fas fa-history me-2 text-success"></i>Parent Grade SMS Log</span>
              <button class="btn-sm-custom btn-delete" onclick="clearLog()">
                <i class="fas fa-trash me-1"></i> Clear Log
              </button>
            </div>
            <div class="card-body-custom" id="smsLogContainer" style="max-height:560px;overflow-y:auto">
              <div class="empty-state" id="emptyLog">
                <i class="fas fa-comments"></i>
                <p>No SMS grade notifications sent yet.</p>
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
        <h4 class="fw-bold mb-2" id="sendingTitle">Dispatching Grade SMS...</h4>
        <p class="text-light opacity-75 mb-3" id="sendingSubtitle" style="font-size:0.9rem">
          Please wait while PhilSMS processes and delivers the grade notification(s) to parent contact numbers.
        </p>
        <div class="p-2.5 rounded text-start" style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);font-size:0.8rem">
          <i class="fas fa-exclamation-triangle text-warning me-1"></i> <strong>Do not close, refresh, or switch tabs</strong> until sending is complete to prevent duplicate transmissions or errors.
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

  async function init() {
    sendingModalInstance = new bootstrap.Modal(document.getElementById('sendingSmsModal'), {
      backdrop: 'static',
      keyboard: false
    });

    try {
      const res = await fetch('../../api/sms.php?action=options');
      optionsData = await res.json();

      if (optionsData.success) {
        // Active SY badge
        document.getElementById('activeSyBadge').textContent = `Active SY: ${optionsData.active_sy_lbl}`;

        // PhilSMS API badge status (if element exists)
        const badgeEl = document.getElementById('apiStatusBadge');
        if (badgeEl) {
          if (optionsData.api_configured) {
            badgeEl.innerHTML = `<span class="badge bg-success p-2"><i class="fas fa-check-circle me-1"></i>PhilSMS API Ready</span>`;
          } else {
            badgeEl.innerHTML = `<span class="badge bg-warning text-dark p-2" title="Set PHILSMS_API_TOKEN in config/db.php"><i class="fas fa-exclamation-triangle me-1"></i>PhilSMS Token Pending</span>`;
          }
        }

        // Populate School Years
        const sySel = document.getElementById('schoolYearSelect');
        sySel.innerHTML = (optionsData.school_years || []).map(s => 
          `<option value="${s.id}" ${s.is_active==1?'selected':''}>${s.label}${s.is_active==1?' (Active)':''}</option>`
        ).join('');

        // Populate Students with Parent Contact info
        const stuSel = document.getElementById('studentSelect');
        stuSel.innerHTML = `<option value="">-- Select Student --</option>` + 
          (optionsData.students || []).map(s => {
            const pPhone = s.guardian_phone || s.phone || 'No Phone';
            const pLabel = s.guardian_phone ? 'Parent Phone' : 'Student Phone';
            return `<option value="${s.id}">${s.full_name} (${s.grade_level?`G${s.grade_level}-${s.section_name}`:'Unassigned'}) – ${pLabel}: ${pPhone}</option>`;
          }).join('');

        // Populate Sections
        const secSel = document.getElementById('sectionSelect');
        secSel.innerHTML = `<option value="">-- Select Section --</option>` +
          (optionsData.sections || []).map(sec => 
            `<option value="${sec.id}">Grade ${sec.grade_level} - ${sec.name}</option>`
          ).join('');
      }

      loadLog();
    } catch(e) {
      console.error('Init error:', e);
      showToast('Failed to load SMS options.', 'error');
    }
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

  function updateBulkPreviewNotice() {
    const mode = document.getElementById('smsMode').value;
    const q = document.getElementById('quarterSelect').value;
    const termStr = (q==1?'1st Term':(q==2?'2nd Term':'3rd Term'));
    let focusName = mode === 'term_grades' ? `${termStr} Grade Summary` : (mode === 'final_average' ? 'Final Grade Report' : 'Failing Grade Alert');

    document.getElementById('studentDetailCard').style.display = 'none';
    document.getElementById('autoSmsBox').innerHTML = `
      <div class="text-primary fw-semibold mb-1"><i class="fas fa-robot me-1"></i>PhilSMS Automated Group Dispatch: ${focusName}</div>
      <div class="text-muted" style="font-size:0.82rem">The system will automatically query and calculate individual grades for every student in the recipient group and dispatch personalized SMS text messages (< 160 chars) directly to each parent/guardian.</div>
    `;
    updateCharCount(0);
  }

  async function fetchPreview() {
    const mode      = document.getElementById('smsMode').value;
    const quarter   = document.getElementById('quarterSelect').value;
    const syId      = document.getElementById('schoolYearSelect').value;
    const stuId     = document.getElementById('studentSelect').value;
    const recType   = document.getElementById('recipientType').value;

    if (recType !== 'single' || !stuId) {
      updateBulkPreviewNotice();
      return;
    }

    try {
      const url = `../../api/sms.php?action=preview&student_id=${stuId}&mode=${mode}&quarter=${quarter}&school_year_id=${syId}`;
      const res = await fetch(url);
      const data = await res.json();

      if (data.success) {
        document.getElementById('studentDetailCard').style.display = 'block';
        document.getElementById('previewStudentName').textContent  = data.student_name || '—';
        document.getElementById('previewGuardianName').textContent = data.guardian_name || '—';
        document.getElementById('previewPhone').textContent        = data.phone || 'N/A';
        
        const badgeEl = document.getElementById('phoneSourceBadge');
        badgeEl.textContent = data.phone_source || '';
        badgeEl.className = data.phone_source === 'Parent Phone' ? 'badge bg-success ms-1' : 'badge bg-warning text-dark ms-1';
        
        document.getElementById('previewGradeSection').textContent = data.remarks ? `${data.remarks}` : '—';
        
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

  // Prevent refresh / tab close warning callback
  function preventUnloadHandler(e) {
    e.preventDefault();
    e.returnValue = 'SMS dispatch is currently in progress. Closing or refreshing may cause errors.';
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
      sendBtn.innerHTML = `<i class="fas fa-paper-plane me-2"></i>Send Grade SMS`;
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

    if (recType === 'single' && !stuId) {
      showToast('Please select a student.', 'error');
      return;
    }
    if (recType === 'section' && !secId) {
      showToast('Please select a section.', 'error');
      return;
    }

    const body = new FormData();
    body.append('action', 'send');
    body.append('mode', mode);
    body.append('quarter', quarter);
    body.append('school_year_id', syId);
    body.append('recipient_type', recType);
    if (stuId) body.append('student_id', stuId);
    if (secId) body.append('section_id', secId);

    // LOCK UI & SHOW LOADING OVERLAY
    lockUI(true, recType === 'single' ? 'Sending Parent Grade SMS...' : 'Dispatching Group Grade SMS...');

    try {
      const res = await fetch('../../api/sms.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        showToast(data.message || `Automated Grade SMS successfully dispatched!`, 'success');
        loadLog();
      } else {
        showToast(data.message || 'Failed to send SMS.', 'error');
      }
    } catch(e) {
      showToast('Server connection error.', 'error');
    } finally {
      // UNLOCK UI & HIDE LOADING OVERLAY
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
    if (!confirm('Are you sure you want to clear all SMS notification logs?')) return;
    const body = new FormData();
    body.append('action', 'clear_logs');
    await fetch('../../api/sms.php', { method: 'POST', body });
    loadLog();
    showToast('SMS notification logs cleared.', 'info');
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
