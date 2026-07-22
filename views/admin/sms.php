<?php require_once '../../components/under-construction.php'; ?>
<?php
require_once '../../config/session.php';
requireAdmin();
$adminActivePage = 'sms';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>SMS Notifications – OGMS Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../../assets/css/style.css"/>
</head>
<body>
<div class="app-wrapper">
  <?php include '../../components/admin-sidebar.php'; ?>

  <div class="main-content">
    <header class="topbar">
      <div class="topbar-left">
        <button class="topbar-btn hamburger"><i class="fas fa-bars"></i></button>
        <div>
          <div class="topbar-title">SMS Notifications</div>
          <div class="topbar-subtitle">Send SMS alerts to students / guardians</div>
        </div>
      </div>
    </header>

    <main class="page-content fade-in">
      <div class="row g-3 mb-3">
        <div class="col-12">
          <div style="background:#2c3e50;border-radius:4px;padding:1.25rem 1.5rem;color:#fff;display:flex;align-items:center;gap:1rem">
            <div style="font-size:2.5rem">📱</div>
            <div>
              <h5 class="mb-1 fw-bold">SMS Notification System</h5>
              <p class="mb-0" style="font-size:0.85rem;color:rgba(255,255,255,.75)">
                Messages are sent via the Semaphore API (Philippine carrier-compatible).<br>
                Configure your API key in <code>config/db.php</code> to enable real SMS.
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <!-- Compose -->
        <div class="col-md-5">
          <div class="content-card h-100">
            <div class="card-header-custom">
              <span class="card-title"><i class="fas fa-edit me-2 text-primary"></i>Compose Message</span>
            </div>
            <div class="card-body-custom">
              <div class="mb-3">
                <label class="form-label">Recipient Type</label>
                <select id="recipientType" class="form-select" onchange="toggleRecipient()">
                  <option value="single">Single Student</option>
                  <option value="all">All Students</option>
                  <option value="failed">Students with Failed Grades</option>
                </select>
              </div>
              <div class="mb-3" id="singleRecipientGroup">
                <label class="form-label">Select Student</label>
                <select id="recipientStudent" class="form-select">
                  <option value="">-- Select Student --</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Message Template</label>
                <select id="msgTemplate" class="form-select" onchange="applyTemplate()">
                  <option value="">Custom Message</option>
                  <option value="grades">Grade Report Available</option>
                  <option value="meeting">Parent-Teacher Meeting</option>
                  <option value="reminder">Grade Submission Reminder</option>
                  <option value="failed">Failed Grade Notice</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label d-flex justify-content-between">
                  <span>Message</span>
                  <span id="charCount" style="font-size:0.75rem;color:#94a3b8">0 / 160</span>
                </label>
                <textarea id="smsMessage" class="form-control" rows="5"
                  placeholder="Type your message here…" maxlength="160"
                  oninput="updateCharCount()"></textarea>
              </div>
              <div class="d-grid">
                <button class="btn btn-primary" onclick="sendSMS()" style="font-size:1rem;padding:0.75rem">
                  <i class="fas fa-paper-plane me-2"></i>Send SMS
                </button>
              </div>
              <div class="mt-2">
                <button class="btn btn-outline-primary w-100" onclick="sendBulkSMS()">
                  <i class="fas fa-broadcast-tower me-2"></i>Send to All Students
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Log -->
        <div class="col-md-7">
          <div class="content-card">
            <div class="card-header-custom">
              <span class="card-title"><i class="fas fa-history me-2 text-success"></i>Message Log</span>
              <button class="btn-sm-custom btn-delete" onclick="clearLog()">
                <i class="fas fa-trash"></i> Clear All
              </button>
            </div>
            <div class="card-body-custom" id="smsLogContainer" style="max-height:520px;overflow-y:auto">
              <div class="empty-state" id="emptyLog">
                <i class="fas fa-comments"></i>
                <p>No messages sent yet.</p>
              </div>
              <div id="smsLogList"></div>
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
  const templates = {
    grades:   'Dear Parent/Guardian, the quarterly grades of your child are now available in the OGMS portal. Please log in to view them. Thank you.',
    meeting:  'Dear Parent/Guardian, please be informed that a Parent-Teacher Meeting is scheduled on [DATE] at [TIME]. Your attendance is required.',
    reminder: 'This is a reminder that grade submission deadline is approaching. Please ensure all grades are encoded in the OGMS system.',
    failed:   'Dear Parent/Guardian, we would like to inform you that your child has a failing grade in one or more subjects. Please visit the school for consultation.',
  };

  async function init() {
    const res  = await fetch('../../api/students.php?action=list');
    const data = await res.json();
    const sel  = document.getElementById('recipientStudent');
    (data.data||[]).forEach(s =>
      sel.innerHTML += `<option value="${s.id}">${s.full_name} – ${s.phone||'No #'}</option>`
    );
    loadLog();
  }

  function toggleRecipient() {
    const type = document.getElementById('recipientType').value;
    document.getElementById('singleRecipientGroup').style.display = type==='single' ? 'block' : 'none';
  }

  function applyTemplate() {
    const t = document.getElementById('msgTemplate').value;
    if (t && templates[t]) { document.getElementById('smsMessage').value = templates[t]; updateCharCount(); }
  }

  function updateCharCount() {
    const len = document.getElementById('smsMessage').value.length;
    const el  = document.getElementById('charCount');
    el.textContent = `${len} / 160`;
    el.style.color = len > 140 ? '#ef4444' : '#94a3b8';
  }

  async function sendSMS() {
    const type  = document.getElementById('recipientType').value;
    const msg   = document.getElementById('smsMessage').value.trim();
    const stuId = document.getElementById('recipientStudent').value;

    if (!msg) { showToast('Please enter a message.', 'error'); return; }
    if (type === 'single' && !stuId) { showToast('Please select a student.', 'error'); return; }

    const body = new FormData();
    body.append('action',         'send');
    body.append('recipient_type', type);
    body.append('message',        msg);
    if (stuId) body.append('student_id', stuId);

    try {
      const res  = await fetch('../../api/sms.php', {method:'POST', body});
      const data = await res.json();
      if (data.success) {
        showToast(`SMS sent to ${data.count||1} recipient(s)!`, 'success');
        document.getElementById('smsMessage').value = '';
        updateCharCount();
        loadLog();
      } else {
        showToast(data.message || 'Failed to send SMS.', 'error');
      }
    } catch(e) { showToast('Server error.', 'error'); }
  }

  function sendBulkSMS() {
    document.getElementById('recipientType').value = 'all';
    toggleRecipient();
    if (!document.getElementById('smsMessage').value.trim()) {
      document.getElementById('smsMessage').value = templates.grades;
      updateCharCount();
    }
    sendSMS();
  }

  async function loadLog() {
    try {
      const res  = await fetch('../../api/sms.php?action=logs');
      const data = await res.json();
      const log  = data.data || [];
      const empty = document.getElementById('emptyLog');
      const list  = document.getElementById('smsLogList');
      if (!log.length) { empty.style.display='block'; list.innerHTML=''; return; }
      empty.style.display = 'none';
      list.innerHTML = log.map(e => `
        <div class="sms-log-item">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="sms-to"><i class="fas fa-user me-1 text-primary" style="font-size:.75rem"></i>${e.recipient_name||'—'}</div>
              <div style="font-size:.72rem;color:#94a3b8">${e.recipient_phone}</div>
              <div class="sms-msg">"${e.message}"</div>
            </div>
            <div class="text-end">
              <span class="badge ${e.status==='sent'?'bg-success':e.status==='failed'?'bg-danger':'bg-warning'}" style="font-size:.65rem">${e.status}</span>
              <div class="sms-time">${e.sent_at||e.created_at}</div>
            </div>
          </div>
        </div>`).join('');
    } catch(e) { console.error('SMS log error:', e); }
  }

  async function clearLog() {
    if (!confirm('Clear all SMS log?')) return;
    const body = new FormData();
    body.append('action','clear_logs');
    await fetch('../../api/sms.php', {method:'POST', body});
    loadLog();
    showToast('SMS log cleared.', 'info');
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
