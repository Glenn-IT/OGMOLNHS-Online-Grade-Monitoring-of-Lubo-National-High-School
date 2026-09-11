<?php
/**
 * SF9 TEST PAGE - Lubo National High School
 * Standard School Form 9 (SF9) for ALL Grade Levels (Junior High & Senior High)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$pdo = null;
$dbStudents = [];
$dbSubjects = [];

try {
    $pdo = getDB();
    // Fetch all active students with their sections & grade levels
    $stmt = $pdo->query("SELECT u.id, u.full_name, u.lrn, u.gender, u.birthdate, s.name AS section_name, s.grade_level 
                         FROM users u 
                         LEFT JOIN enrollments e ON e.student_id = u.id 
                         LEFT JOIN sections s ON s.id = e.section_id 
                         WHERE u.role = 'student' AND u.is_active = 1 
                         ORDER BY s.grade_level, u.full_name");
    $dbStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all subjects from database
    $subStmt = $pdo->query("SELECT id, name, code FROM subjects ORDER BY id");
    $dbSubjects = $subStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Database fallback
}

// Check if student_id requested
$selectedStudentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$requestedLevel = $_GET['level'] ?? '';
$studentData = null;
$studentGrades = [];

if ($selectedStudentId && $pdo) {
    $stmt = $pdo->prepare("SELECT u.id, u.full_name, u.lrn, u.gender, u.birthdate, s.name AS section_name, s.grade_level, sy.label AS school_year
                          FROM users u
                          LEFT JOIN enrollments e ON e.student_id = u.id
                          LEFT JOIN sections s ON s.id = e.section_id
                          LEFT JOIN school_years sy ON sy.id = e.school_year_id
                          WHERE u.id = ?");
    $stmt->execute([$selectedStudentId]);
    $studentData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($studentData) {
        $gStmt = $pdo->prepare("SELECT g.subject_id, s.name AS subject_name, s.code AS subject_code, g.quarter, g.final_grade, g.remarks
                               FROM grades g
                               JOIN subjects s ON s.id = g.subject_id
                               WHERE g.student_id = ?
                               ORDER BY s.id, g.quarter");
        $gStmt->execute([$selectedStudentId]);
        $rows = $gStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $studentGrades[$r['subject_name']][$r['quarter']] = $r['final_grade'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SF9 Form Tester (All Grade Levels) | Lubo National High School</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tinos:ital,wght@0,400;0,700;1,400;1,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* -------------------------------------------------------------
       GLOBAL STYLES & CONTROLS (SCREEN ONLY)
       ------------------------------------------------------------- */
    :root {
      --primary-color: #1e3a8a;
      --accent-color: #0d9488;
      --paper-width: 11in;
      --paper-height: 8.5in;
      --border-color: #000;
    }

    body {
      background-color: #0b1120;
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      margin: 0;
      padding: 0;
      color: #1e293b;
    }

    /* Top Control Bar */
    .top-toolbar {
      background: #090d16;
      color: #f8fafc;
      padding: 8px 18px;
      position: sticky;
      top: 0;
      z-index: 1000;
      box-shadow: 0 4px 14px rgba(0,0,0,0.5);
      border-bottom: 1px solid #1e293b;
    }

    .toolbar-title {
      font-size: 0.96rem;
      font-weight: 700;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .toolbar-badge {
      background: #2563eb;
      color: #fff;
      font-size: 0.7rem;
      padding: 2px 7px;
      border-radius: 4px;
      font-weight: 600;
      letter-spacing: 0.4px;
    }
    .toolbar-badge.jhs {
      background: #059669;
    }
    .toolbar-badge.shs {
      background: #7c3aed;
    }

    .btn-toolbar-custom {
      font-size: 0.8rem;
      font-weight: 500;
      padding: 4px 10px;
      border-radius: 5px;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: all 0.15s ease;
    }

    .form-select-toolbar {
      background-color: #1e293b;
      border: 1px solid #475569;
      color: #f8fafc;
      font-size: 0.8rem;
      padding: 4px 8px;
      border-radius: 5px;
    }
    .form-select-toolbar:focus {
      background-color: #1e293b;
      color: #f8fafc;
      border-color: #3b82f6;
      box-shadow: none;
    }

    /* Workspace canvas */
    .workspace-wrapper {
      padding: 16px 15px 40px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
      min-height: calc(100vh - 55px);
      overflow-x: auto;
    }

    .workspace-notice {
      max-width: var(--paper-width);
      width: 100%;
      background: #eff6ff;
      border: 1px solid #bfdbfe;
      border-left: 4px solid #3b82f6;
      border-radius: 6px;
      padding: 7px 14px;
      margin-bottom: 12px;
      font-size: 0.82rem;
      color: #1e40af;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    /* -------------------------------------------------------------
       THE SF9 SHEET CONTAINER (US Letter Landscape 11" x 8.5")
       ------------------------------------------------------------- */
    .sf9-sheet {
      width: var(--paper-width);
      height: var(--paper-height);
      max-width: var(--paper-width);
      max-height: var(--paper-height);
      background: #ffffff;
      box-shadow: 0 12px 35px rgba(0,0,0,0.6);
      padding: 0.22in 0.28in 0.18in 0.28in;
      box-sizing: border-box;
      display: grid;
      grid-template-columns: 1fr 1fr;
      column-gap: 0.38in;
      position: relative;
      font-family: 'Tinos', 'Times New Roman', Times, serif;
      color: #000000;
      line-height: 1.12;
      font-size: 7.8pt;
      transform-origin: top center;
      transition: transform 0.2s ease;
      page-break-after: avoid;
      page-break-inside: avoid;
      overflow: hidden;
    }

    /* Center folding / cut guide in screen view */
    .sf9-sheet::before {
      content: '';
      position: absolute;
      top: 0.2in;
      bottom: 0.2in;
      left: 50%;
      width: 1px;
      border-left: 1px dashed #cbd5e1;
      pointer-events: none;
    }

    /* Print Preview Simulation Mode */
    body.sim-print-mode {
      background-color: #334155;
    }
    body.sim-print-mode .sf9-sheet {
      box-shadow: 0 0 0 1px #cbd5e1, 0 8px 24px rgba(0,0,0,0.25);
    }
    body.sim-print-mode .sf9-sheet::before {
      display: none;
    }

    /* Style Presets */
    .theme-enhanced .section-title {
      background: #f8fafc;
      border-top: 1px solid #000;
      border-bottom: 1px solid #000;
      padding: 1.5px 2px;
    }
    .theme-accent .section-title {
      background: #f0fdf4;
      color: #166534;
      border-top: 1px solid #16a34a;
      border-bottom: 1px solid #16a34a;
      padding: 1.5px 4px;
    }

    /* -------------------------------------------------------------
       PANEL LAYOUT (LEFT & RIGHT)
       ------------------------------------------------------------- */
    .sf9-panel {
      display: flex;
      flex-direction: column;
      height: 100%;
      justify-content: flex-start;
      gap: 2px;
    }

    /* HEADER BLOCK */
    .header-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 2px;
    }
    .header-table td {
      vertical-align: middle;
      padding: 0;
    }
    .header-logo {
      width: 52px;
      height: 52px;
      object-fit: contain;
    }
    .header-center-text {
      text-align: center;
      font-size: 7.6pt;
      line-height: 1.15;
    }
    .header-dept {
      font-size: 7.5pt;
    }
    .header-division {
      font-weight: bold;
      font-size: 7.8pt;
      text-transform: uppercase;
    }
    .header-school {
      font-weight: bold;
      font-size: 9.2pt;
      text-transform: uppercase;
      letter-spacing: 0.3px;
      margin-top: 1px;
    }
    .header-sy {
      text-align: center;
      font-size: 8.2pt;
      font-weight: bold;
      margin: 2px 0 1px;
    }

    .report-main-title {
      text-align: center;
      font-weight: bold;
      font-size: 8.6pt;
      letter-spacing: 0.4px;
      margin-bottom: 4px;
      text-transform: uppercase;
    }

    /* LEARNER INFO */
    .info-grid {
      display: grid;
      grid-template-columns: 2fr 0.8fr 0.8fr;
      row-gap: 2px;
      column-gap: 6px;
      font-size: 7.8pt;
      margin-bottom: 3px;
    }
    .info-item {
      display: flex;
      align-items: baseline;
    }
    .info-label {
      font-weight: bold;
      margin-right: 4px;
      white-space: nowrap;
    }
    .info-line {
      border-bottom: 1px solid #000;
      flex-grow: 1;
      padding-left: 3px;
      font-size: 7.8pt;
      min-height: 11pt;
      line-height: 11pt;
    }

    /* DEAR PARENTS TEXT */
    .parent-message {
      font-size: 7.0pt;
      line-height: 1.2;
      text-align: justify;
      margin-bottom: 3px;
      padding: 0 1px;
    }
    .parent-message strong {
      display: inline;
      margin-right: 2px;
    }

    /* SECTION TITLES */
    .section-title {
      font-weight: bold;
      font-size: 7.8pt;
      text-align: center;
      text-transform: uppercase;
      margin: 2px 0 2px;
      letter-spacing: 0.2px;
    }

    /* TABLES (GRADES & DESCRIPTORS) */
    .sf9-table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid var(--border-color);
      font-size: 7.3pt;
      margin-bottom: 4px;
    }
    .sf9-table th, 
    .sf9-table td {
      border: 1px solid var(--border-color);
      padding: 1.8px 3px;
      vertical-align: middle;
      height: 14.5px;
    }
    .sf9-table th {
      font-weight: bold;
      text-align: center;
      background: #f8fafc;
      font-size: 7.3pt;
      padding: 2.5px 2px;
    }
    .sf9-table .group-row {
      font-weight: bold;
      background-color: #f1f5f9;
      font-size: 7.3pt;
      height: 15px;
    }
    .sf9-table .sub-row td.subj-name {
      padding-left: 14px;
      font-style: italic;
    }
    .sf9-table td.text-center {
      text-align: center;
    }
    .sf9-table td.text-right {
      text-align: right;
    }
    .sf9-table td.subj-name {
      padding-left: 5px;
      font-size: 7.1pt;
      line-height: 1.12;
    }
    .sf9-table .gen-avg-row {
      font-weight: bold;
      background-color: #f8fafc;
      font-size: 7.6pt;
      height: 16px;
    }

    /* PERFORMANCE DESCRIPTORS TABLE */
    .desc-table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid var(--border-color);
      font-size: 7.0pt;
      margin-top: 1px;
    }
    .desc-table th, 
    .desc-table td {
      border: 1px solid var(--border-color);
      padding: 1.5px 3px;
      vertical-align: middle;
      height: 13.5px;
    }
    .desc-table th {
      font-weight: bold;
      text-align: center;
      background: #f8fafc;
      font-size: 7.0pt;
      padding: 2px 2px;
    }

    /* RIGHT PANEL SPECIFICS */
    .attendance-table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid var(--border-color);
      font-size: 7.1pt;
      margin-bottom: 6px;
    }
    .attendance-table th, 
    .attendance-table td {
      border: 1px solid var(--border-color);
      padding: 2.2px 1.5px;
      text-align: center;
      vertical-align: middle;
      height: 15.5px;
    }
    .attendance-table th {
      font-weight: bold;
      background: #f8fafc;
      font-size: 7.1pt;
    }
    .attendance-table td.row-label {
      text-align: left;
      padding-left: 4px;
      font-weight: bold;
      font-size: 7.1pt;
      white-space: nowrap;
    }

    /* PARENT SIGNATURES */
    .signatures-block {
      border: 1px solid var(--border-color);
      padding: 6px 10px;
      margin-bottom: 6px;
      font-size: 7.5pt;
    }
    .sig-row {
      display: flex;
      align-items: flex-end;
      margin-bottom: 4.5px;
    }
    .sig-row:last-child {
      margin-bottom: 0;
    }
    .sig-label {
      width: 60px;
      font-weight: bold;
      font-size: 7.5pt;
    }
    .sig-line {
      flex-grow: 1;
      border-bottom: 1px solid #000;
      height: 13px;
    }

    /* CERTIFICATE OF TRANSFER */
    .transfer-cert {
      border: 1px solid var(--border-color);
      padding: 6px 10px;
      margin-bottom: 6px;
      font-size: 7.4pt;
      line-height: 1.3;
    }
    .transfer-cert p {
      margin: 0 0 4px 0;
      text-indent: 14px;
      text-align: justify;
    }
    .transfer-field {
      display: flex;
      margin-bottom: 4px;
    }
    .transfer-field .t-line {
      border-bottom: 1px solid #000;
      flex-grow: 1;
      padding-left: 4px;
      font-weight: bold;
      font-size: 7.4pt;
    }
    .transfer-signers {
      display: grid;
      grid-template-columns: 1fr 1fr;
      column-gap: 15px;
      margin-top: 14px;
      text-align: center;
    }
    .signer-box {
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .signer-name {
      font-weight: bold;
      text-transform: uppercase;
      font-size: 8.0pt;
      border-bottom: 1px solid #000;
      width: 90%;
      padding-bottom: 1px;
    }
    .signer-role {
      font-size: 7.2pt;
      margin-top: 1px;
    }

    /* CANCELLATION BLOCK */
    .cancellation-cert {
      border: 1px solid var(--border-color);
      padding: 6px 10px;
      font-size: 7.4pt;
      margin-top: 2px;
    }
    .cancel-grid {
      display: grid;
      grid-template-columns: 1.4fr 1fr;
      column-gap: 12px;
      margin-bottom: 10px;
    }

    /* Inline Edit Styling */
    .editable-active [contenteditable="true"] {
      outline: 1px dashed #3b82f6 !important;
      background-color: rgba(59, 130, 246, 0.06);
      cursor: text;
    }
    .editable-active [contenteditable="true"]:hover {
      background-color: rgba(59, 130, 246, 0.15);
    }
    .editable-active [contenteditable="true"]:focus {
      outline: 2px solid #2563eb !important;
      background-color: #fff;
    }

    /* -------------------------------------------------------------
       PRINT MEDIA QUERY (EXACT US LETTER LANDSCAPE)
       ------------------------------------------------------------- */
    @page {
      size: letter landscape;
      margin: 0.22in 0.28in 0.18in 0.28in;
    }

    @media print {
      html, body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        height: 100% !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
      .no-print {
        display: none !important;
      }
      .workspace-wrapper {
        padding: 0 !important;
        margin: 0 !important;
        min-height: auto !important;
      }
      .sf9-sheet {
        box-shadow: none !important;
        margin: 0 !important;
        width: 100% !important;
        height: 100% !important;
        max-width: none !important;
        max-height: none !important;
        transform: none !important;
        padding: 0 !important;
        border: none !important;
      }
      .sf9-sheet::before {
        display: none !important;
      }
    }
  </style>
</head>
<body>

  <!-- =========================================================
       TOP CONTROL TOOLBAR (Interactive Sandbox for Testing)
       ========================================================= -->
  <header class="top-toolbar no-print">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
      
      <!-- Left: Title and Status -->
      <div class="d-flex align-items-center gap-2">
        <a href="../views/admin/dashboard.php" class="btn btn-outline-light btn-sm btn-toolbar-custom" title="Back to System">
          <i class="fas fa-arrow-left"></i>
        </a>
        <div class="toolbar-title">
          <i class="fas fa-file-invoice text-warning"></i>
          <span>SF9 Layout Designer</span>
          <span id="levelBadge" class="toolbar-badge jhs">Junior High (JHS)</span>
        </div>
      </div>

      <!-- Center: Grade Level Preset, DataSource, Terms, & Style -->
      <div class="d-flex flex-wrap align-items-center gap-2">
        
        <!-- Grade Level Preset Switcher -->
        <div class="d-flex align-items-center gap-1">
          <label class="text-white-50 small mb-0 me-1">Curriculum / Level:</label>
          <select id="gradeLevelSelect" class="form-select form-select-toolbar" style="width: 220px;" onchange="handleGradeLevelChange(this.value)">
            <optgroup label="Junior High School (JHS Subjects)">
              <option value="jhs-7" selected>Grade 7 (Rizal / Sampaguita)</option>
              <option value="jhs-8">Grade 8 (Bonifacio / Newton)</option>
              <option value="jhs-9">Grade 9 (Mabini / Einstein)</option>
              <option value="jhs-10">Grade 10 (Luna / Aguinaldo)</option>
            </optgroup>
            <optgroup label="Senior High School (SHS Subjects)">
              <option value="shs-11">Grade 11 (STEM / HUMSS)</option>
              <option value="shs-12">Grade 12 (TVL - ICT / PDF Sample)</option>
            </optgroup>
          </select>
        </div>

        <!-- Student Data Source -->
        <div class="d-flex align-items-center gap-1">
          <label class="text-white-50 small mb-0 me-1">Student Data:</label>
          <select id="studentSelect" class="form-select form-select-toolbar" style="width: 200px;" onchange="handleStudentChange(this.value)">
            <option value="sample" selected>Standard Sample Student</option>
            <option value="blank">Blank Template (Ready to print)</option>
            <?php if (!empty($dbStudents)): ?>
              <optgroup label="Live Database Students">
                <?php foreach ($dbStudents as $stu): ?>
                  <option value="db-<?= $stu['id'] ?>" <?= $selectedStudentId == $stu['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($stu['full_name']) ?> (Gr. <?= $stu['grade_level'] ?> - <?= htmlspecialchars($stu['section_name'] ?? '') ?>)
                  </option>
                <?php endforeach; ?>
              </optgroup>
            <?php endif; ?>
          </select>
        </div>

        <!-- Term Mode: 4 Quarters (standard DepEd) vs 3 Terms (from original PDF) -->
        <div class="d-flex align-items-center gap-1">
          <label class="text-white-50 small mb-0 me-1">Grading:</label>
          <select id="termModeSelect" class="form-select form-select-toolbar" style="width: 125px;" onchange="updateTermMode(this.value)">
            <option value="4quarters" selected>4 Quarters</option>
            <option value="3terms">3 Terms</option>
          </select>
        </div>

        <!-- Theme Preset -->
        <div class="d-flex align-items-center gap-1">
          <label class="text-white-50 small mb-0 me-1">Style:</label>
          <select id="themeSelect" class="form-select form-select-toolbar" style="width: 155px;" onchange="updateTheme(this.value)">
            <option value="theme-enhanced" selected>Enhanced Formal</option>
            <option value="theme-pdf-replica">Exact DepEd Print</option>
            <option value="theme-accent">Subtle Accent</option>
          </select>
        </div>
      </div>

      <!-- Right: Action Buttons -->
      <div class="d-flex align-items-center gap-2">
        <!-- Clean View Simulator -->
        <button id="simPrintBtn" class="btn btn-outline-secondary btn-sm btn-toolbar-custom text-white" onclick="toggleSimPrintMode()">
          <i class="fas fa-eye"></i> <span>Clean View</span>
        </button>

        <!-- Live Edit Toggle -->
        <button id="editToggleBtn" class="btn btn-outline-warning btn-sm btn-toolbar-custom" onclick="toggleEditMode()">
          <i class="fas fa-edit"></i> <span>Edit Content</span>
        </button>

        <!-- Zoom Control -->
        <div class="btn-group btn-group-sm">
          <button class="btn btn-dark text-white-50" onclick="adjustZoom(-0.1)" title="Zoom Out"><i class="fas fa-search-minus"></i></button>
          <button id="zoomDisplayBtn" class="btn btn-dark text-white px-2" onclick="resetZoom()" title="Reset Zoom">100%</button>
          <button class="btn btn-dark text-white-50" onclick="adjustZoom(0.1)" title="Zoom In"><i class="fas fa-search-plus"></i></button>
        </div>

        <!-- Print Button -->
        <button class="btn btn-success btn-sm btn-toolbar-custom" onclick="window.print()">
          <i class="fas fa-print"></i> <span>Print / Save PDF</span>
        </button>
      </div>

    </div>
  </header>

  <!-- =========================================================
       MAIN TESTING WORKSPACE
       ========================================================= -->
  <main class="workspace-wrapper">

    <!-- Screen-only Helper Banner -->
    <div class="workspace-notice no-print">
      <div>
        <i class="fas fa-info-circle me-2"></i>
        <strong>DepEd Standard SF9:</strong> Configured for <strong>US Letter Landscape (11" &times; 8.5")</strong>.
        Junior High School (Grades 7–10) displays the standard DepEd 8 learning areas + MAPEH components. Senior High School (Grades 11–12) displays Track/Strand with Core, Applied, and Specialized subjects.
      </div>
      <div>
        <span class="badge bg-primary text-white me-2">Dashed center line = Paper fold guide</span>
        <button class="btn btn-sm btn-link text-primary p-0 text-decoration-none" onclick="document.querySelector('.workspace-notice').remove()">Dismiss</button>
      </div>
    </div>

    <!-- =========================================================
         SF9 PRINTABLE SHEET (11" x 8.5" Landscape)
         ========================================================= -->
    <div id="sf9Sheet" class="sf9-sheet theme-enhanced">

      <!-- =======================================================
           LEFT PANEL: FRONT / ACADEMIC PERFORMANCE
           ======================================================= -->
      <section class="sf9-panel">
        
        <!-- Header with Official Seals -->
        <table class="header-table">
          <tr>
            <td style="width: 54px; text-align: left;">
              <img src="assets/deped_logo.png" alt="DepEd Seal" class="header-logo">
            </td>
            <td class="header-center-text">
              <div class="header-dept">Republic of the Philippines</div>
              <div class="header-dept">Department of Education</div>
              <div class="header-dept">Region 02</div>
              <div class="header-division">SCHOOLS DIVISION OF CAGAYAN</div>
              <div class="header-dept">Sto. Niño District</div>
              <div class="header-dept">Sto. Niño, Cagayan</div>
              <div class="header-school">LUBO NATIONAL HIGH SCHOOL</div>
            </td>
            <td style="width: 54px; text-align: right;">
              <img src="assets/lubo_logo.png" alt="Lubo NHS Seal" class="header-logo">
            </td>
          </tr>
        </table>

        <!-- School Year -->
        <div class="header-sy" id="field_sy" contenteditable="false">School Year 2026 - 2027</div>

        <!-- Form Title -->
        <div class="report-main-title" contenteditable="false">LEARNER’S PROGRESS REPORT CARD (SF9)</div>

        <!-- Student Personal Details -->
        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">Name:</span>
            <span id="field_name" class="info-line" contenteditable="false"><?= htmlspecialchars(strtoupper($studentData['full_name'] ?? 'DELA CRUZ, JUAN M.')) ?></span>
          </div>
          <div class="info-item">
            <span class="info-label">Age:</span>
            <span id="field_age" class="info-line text-center" contenteditable="false">13</span>
          </div>
          <div class="info-item">
            <span class="info-label">Sex:</span>
            <span id="field_sex" class="info-line text-center" contenteditable="false"><?= htmlspecialchars($studentData['gender'] ?? 'Male') ?></span>
          </div>

          <div class="info-item">
            <span class="info-label">LRN:</span>
            <span id="field_lrn" class="info-line" contenteditable="false"><?= htmlspecialchars($studentData['lrn'] ?? '454654546546') ?></span>
          </div>
          <div class="info-item">
            <span class="info-label">Grade:</span>
            <span id="field_grade" class="info-line text-center" contenteditable="false"><?= htmlspecialchars($studentData['grade_level'] ?? '7') ?></span>
          </div>
          <div class="info-item">
            <span class="info-label">Section:</span>
            <span id="field_section" class="info-line text-center" contenteditable="false"><?= htmlspecialchars($studentData['section_name'] ?? 'Rizal') ?></span>
          </div>

          <!-- Track / Strand row (shown for SHS, hidden or Curriculum for JHS) -->
          <div class="info-item" id="trackRow" style="grid-column: span 3;">
            <span class="info-label" id="trackLabel">Curriculum:</span>
            <span id="field_track" class="info-line" contenteditable="false">Junior High School (K to 12 Basic Education Curriculum)</span>
          </div>
        </div>

        <!-- Dear Parents Message -->
        <div class="parent-message">
          <strong>Dear Parents:</strong>
          This Performance Report shows the ability and progress your child has made in the different learning areas as well as his/her core values. The school welcomes you should you desire to know more about your child’s progress.
        </div>

        <!-- Learning Progress and Achievement Header -->
        <div class="section-title">LEARNING PROGRESS AND ACHIEVEMENT</div>

        <!-- Grades Table Container -->
        <table class="sf9-table" id="gradesTable">
          <thead>
            <tr>
              <th rowspan="2" style="width: 46%;">Learning Areas</th>
              <th colspan="4" id="termColHeader" style="width: 28%;">QUARTER</th>
              <th rowspan="2" style="width: 13%;">Final Grade</th>
              <th rowspan="2" style="width: 13%;">Remarks</th>
            </tr>
            <tr id="subTermRow">
              <th style="width: 7%;">1</th>
              <th style="width: 7%;">2</th>
              <th style="width: 7%;">3</th>
              <th style="width: 7%;">4</th>
            </tr>
          </thead>
          <tbody id="gradesTableBody">
            <!-- Dynamic Subjects Rows generated via JS based on Junior High vs Senior High -->
          </tbody>
        </table>

        <!-- Performance Descriptors Table -->
        <div class="section-title">PERFORMANCE DESCRIPTORS</div>
        <table class="desc-table">
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
           RIGHT PANEL: ATTENDANCE, SIGNATURES & CERTIFICATIONS
           ======================================================= -->
      <section class="sf9-panel">

        <!-- Attendance Record Title -->
        <div class="section-title">ATTENDANCE RECORD</div>

        <!-- Attendance Table -->
        <table class="attendance-table" id="attendanceTable">
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
              <td contenteditable="false">21</td>
              <td contenteditable="false">21</td>
              <td contenteditable="false">22</td>
              <td contenteditable="false">20</td>
              <td contenteditable="false">15</td>
              <td contenteditable="false">21</td>
              <td contenteditable="false">19</td>
              <td contenteditable="false">21</td>
              <td contenteditable="false">18</td>
              <td contenteditable="false">22</td>
              <td class="fw-bold" id="totalClassDays">200</td>
            </tr>
            <tr>
              <td class="row-label">No. of Days Present</td>
              <td contenteditable="false">21</td>
              <td contenteditable="false">20</td>
              <td contenteditable="false">22</td>
              <td contenteditable="false">19</td>
              <td contenteditable="false">15</td>
              <td contenteditable="false">21</td>
              <td contenteditable="false">19</td>
              <td contenteditable="false">20</td>
              <td contenteditable="false">18</td>
              <td contenteditable="false">22</td>
              <td class="fw-bold" id="totalDaysPresent">197</td>
            </tr>
            <tr>
              <td class="row-label">No. of Days Absent</td>
              <td contenteditable="false">0</td>
              <td contenteditable="false">1</td>
              <td contenteditable="false">0</td>
              <td contenteditable="false">1</td>
              <td contenteditable="false">0</td>
              <td contenteditable="false">0</td>
              <td contenteditable="false">0</td>
              <td contenteditable="false">1</td>
              <td contenteditable="false">0</td>
              <td contenteditable="false">0</td>
              <td class="fw-bold" id="totalDaysAbsent">3</td>
            </tr>
          </tbody>
        </table>

        <!-- Parent / Guardian's Signature Block -->
        <div class="section-title">PARENT / GUARDIAN'S SIGNATURE</div>
        <div class="signatures-block" id="parentSignaturesBlock">
          <div class="sig-row" id="sigQuarter1">
            <span class="sig-label" id="sigLabel1">1st Quarter:</span>
            <div class="sig-line" contenteditable="false"></div>
          </div>
          <div class="sig-row" id="sigQuarter2">
            <span class="sig-label" id="sigLabel2">2nd Quarter:</span>
            <div class="sig-line" contenteditable="false"></div>
          </div>
          <div class="sig-row" id="sigQuarter3">
            <span class="sig-label" id="sigLabel3">3rd Quarter:</span>
            <div class="sig-line" contenteditable="false"></div>
          </div>
          <div class="sig-row" id="sigQuarter4">
            <span class="sig-label" id="sigLabel4">4th Quarter:</span>
            <div class="sig-line" contenteditable="false"></div>
          </div>
        </div>

        <!-- Certificate of Transfer -->
        <div class="section-title">CERTIFICATE OF TRANSFER</div>
        <div class="transfer-cert">
          <p>
            This is to certify that the above-named learner has satisfactorily completed the requirements for the grade level indicated.
          </p>
          <div class="transfer-field">
            <span>Admitted to Grade:</span>
            <span class="t-line" id="transferAdmitted" contenteditable="false">Grade 8</span>
          </div>
          <div class="transfer-field">
            <span>Eligible for Admission to Grade:</span>
            <span class="t-line" id="transferEligible" contenteditable="false">Grade 8</span>
          </div>

          <div class="transfer-signers">
            <div class="signer-box">
              <span class="text-muted small" style="font-size: 7.0pt; margin-bottom: 12px;">Approved by:</span>
              <div class="signer-name" contenteditable="false">MARLON C. VALIENTES</div>
              <div class="signer-role">School Head / Principal</div>
            </div>
            <div class="signer-box">
              <span class="text-muted small" style="font-size: 7.0pt; margin-bottom: 12px;">Prepared by:</span>
              <div class="signer-name" id="field_adviser" contenteditable="false">JOSEPH M. BATUYONG</div>
              <div class="signer-role">Class Adviser</div>
            </div>
          </div>
        </div>

        <!-- Cancellation of Eligibility to Transfer -->
        <div class="section-title">CANCELLATION OF ELIGIBILITY TO TRANSFER</div>
        <div class="cancellation-cert">
          <div class="cancel-grid">
            <div class="transfer-field" style="margin-bottom: 0;">
              <span style="white-space: nowrap;">Admitted in:</span>
              <span class="t-line" contenteditable="false"></span>
            </div>
            <div class="transfer-field" style="margin-bottom: 0;">
              <span style="white-space: nowrap;">Date:</span>
              <span class="t-line" contenteditable="false"></span>
            </div>
          </div>

          <div class="d-flex justify-content-center mt-2">
            <div class="signer-box" style="width: 55%;">
              <div class="signer-name" contenteditable="false">&nbsp;</div>
              <div class="signer-role">School Head</div>
            </div>
          </div>
        </div>

      </section>

    </div><!-- /sf9-sheet -->

  </main>

  <!-- =========================================================
       JAVASCRIPT LOGIC & CURRICULUM SUBJECT DATA
       ========================================================= -->
  <script>
    let currentZoom = 1.0;
    let isEditMode = false;
    let isSimPrint = false;
    let currentTermMode = '4quarters'; // '4quarters' or '3terms'
    let currentGradeLevel = <?= json_encode($requestedLevel ?: 'jhs-7') ?>;   // 'jhs-7', 'jhs-8', 'jhs-9', 'jhs-10', 'shs-11', 'shs-12'

    // JUNIOR HIGH SCHOOL (JHS) STANDARD SUBJECTS (Grades 7 to 10)
    const jhsSubjects = [
      { name: "Filipino", code: "FIL", sampleGrades: [88, 89, 90, 91] },
      { name: "English", code: "ENG", sampleGrades: [90, 92, 91, 93] },
      { name: "Mathematics", code: "MATH", sampleGrades: [86, 88, 87, 89] },
      { name: "Science", code: "SCI", sampleGrades: [85, 87, 88, 90] },
      { name: "Araling Panlipunan (AP)", code: "AP", sampleGrades: [89, 90, 91, 92] },
      { name: "Edukasyon sa Pagpapakatao (EsP) / Values Education", code: "VE", sampleGrades: [92, 93, 94, 95] },
      { name: "Technology and Livelihood Education (TLE)", code: "TLE", sampleGrades: [87, 89, 90, 92] },
      { 
        name: "Music, Arts, Physical Education and Health (MAPEH)", 
        code: "MAPEH", 
        isParent: true,
        sampleGrades: [90, 91, 92, 93],
        subSubjects: [
          { name: "Music", sampleGrades: [90, 91, 92, 93] },
          { name: "Arts", sampleGrades: [89, 90, 91, 92] },
          { name: "Physical Education (PE)", sampleGrades: [92, 93, 94, 95] },
          { name: "Health", sampleGrades: [91, 92, 93, 94] }
        ]
      }
    ];

    // SENIOR HIGH SCHOOL (SHS) GRADE 12 TVL-ICT (from SF9-TVL-ICT12.pdf)
    const shsSubjectsTvl = [
      {
        group: "Core Subjects",
        subjects: [
          { name: "Media and Information Literacy", sampleGrades: [88, 90, 92, 93] },
          { name: "Contemporary Philippine Arts from the Regions", sampleGrades: [85, 87, 89, 90] },
          { name: "Earth and Life Science", sampleGrades: [84, 86, 88, 89] },
          { name: "Physical Science", sampleGrades: [86, 88, 90, 91] },
          { name: "Physical Education and Health", sampleGrades: [91, 93, 94, 95] }
        ]
      },
      {
        group: "Applied Subjects",
        subjects: [
          { name: "Filipino sa Piling Larang (Tech-Voc)", sampleGrades: [87, 89, 91, 92] },
          { name: "Practical Research II", sampleGrades: [89, 91, 93, 94] },
          { name: "Entrepreneurship", sampleGrades: [86, 88, 90, 91] },
          { name: "Inquiries, Investigations and Immersion", sampleGrades: [88, 90, 92, 93] }
        ]
      },
      {
        group: "Specialized Subjects",
        subjects: [
          { name: "Computer Systems Servicing NC II", sampleGrades: [92, 94, 96, 97] },
          { name: "Work Immersion", sampleGrades: [93, 95, 97, 98] }
        ]
      }
    ];

    // SENIOR HIGH SCHOOL (SHS) GRADE 11 STEM SAMPLE
    const shsSubjectsStem = [
      {
        group: "Core Subjects",
        subjects: [
          { name: "Oral Communication in Context", sampleGrades: [88, 90, 91, 92] },
          { name: "Komunikasyon at Pananaliksik sa Wika at Kulturang Pilipino", sampleGrades: [86, 88, 90, 91] },
          { name: "General Mathematics", sampleGrades: [89, 91, 92, 94] },
          { name: "Earth Science", sampleGrades: [87, 89, 90, 92] },
          { name: "Physical Education and Health 1", sampleGrades: [92, 94, 95, 96] }
        ]
      },
      {
        group: "Applied Subjects",
        subjects: [
          { name: "English for Academic and Professional Purposes", sampleGrades: [88, 90, 91, 93] },
          { name: "Empowerment Technologies (E-Tech)", sampleGrades: [91, 93, 94, 95] },
          { name: "Practical Research I", sampleGrades: [87, 89, 90, 92] }
        ]
      },
      {
        group: "Specialized Subjects",
        subjects: [
          { name: "Pre-Calculus", sampleGrades: [86, 88, 89, 91] },
          { name: "General Biology 1", sampleGrades: [88, 90, 92, 93] }
        ]
      }
    ];

    // Grade Level Transfer Mapping
    const transferLevels = {
      'jhs-7':  { next: 'Grade 8', track: 'Junior High School (K to 12 BEC)', sec: 'Rizal', age: '13', badge: 'Junior High (Grade 7)' },
      'jhs-8':  { next: 'Grade 9', track: 'Junior High School (K to 12 BEC)', sec: 'Bonifacio', age: '14', badge: 'Junior High (Grade 8)' },
      'jhs-9':  { next: 'Grade 10', track: 'Junior High School (K to 12 BEC)', sec: 'Mabini', age: '15', badge: 'Junior High (Grade 9)' },
      'jhs-10': { next: 'Grade 11 (Senior High School)', track: 'Junior High School Completer (K to 12 BEC)', sec: 'Luna', age: '16', badge: 'Junior High (Grade 10 Completer)' },
      'shs-11': { next: 'Grade 12', track: 'Academic Track – STEM', sec: 'STEM-A', age: '17', badge: 'Senior High (Grade 11)' },
      'shs-12': { next: 'Graduated / Higher Education (Tertiary)', track: 'Technical-Vocational-Livelihood (TVL) – ICT', sec: 'ICT', age: '18', badge: 'Senior High (Grade 12 TVL-ICT)' }
    };

    // Database Students Map
    const dbStudentsList = <?= json_encode($dbStudents) ?>;
    const dbStudentGrades = <?= json_encode($studentGrades) ?>;
    const currentSelectedId = <?= json_encode($selectedStudentId) ?>;

    document.addEventListener('DOMContentLoaded', () => {
      document.getElementById('gradeLevelSelect').value = currentGradeLevel;
      handleGradeLevelChange(currentGradeLevel);
      if (currentSelectedId) {
        document.getElementById('studentSelect').value = `db-${currentSelectedId}`;
        const match = dbStudentsList.find(s => s.id == currentSelectedId);
        if (match) {
          const gLvl = parseInt(match.grade_level) || 7;
          if (gLvl <= 10) {
            currentGradeLevel = `jhs-${gLvl}`;
          } else {
            currentGradeLevel = `shs-${gLvl}`;
          }
          document.getElementById('gradeLevelSelect').value = currentGradeLevel;
        }
      }
      renderGradeTable();
      calculateAttendanceTotals();
      setupCellListeners();
    });

    // Handle Grade Level Change
    function handleGradeLevelChange(level) {
      currentGradeLevel = level;
      const info = transferLevels[level];
      const isJhs = level.startsWith('jhs');
      
      const badge = document.getElementById('levelBadge');
      badge.textContent = info.badge;
      badge.className = `toolbar-badge ${isJhs ? 'jhs' : 'shs'}`;

      // Update student grade, section, and transfer text
      document.getElementById('field_grade').textContent = level.replace('jhs-', '').replace('shs-', '');
      document.getElementById('field_age').textContent = info.age;
      document.getElementById('transferAdmitted').textContent = info.next;
      document.getElementById('transferEligible').textContent = info.next;

      const trackRow = document.getElementById('trackRow');
      const trackLabel = document.getElementById('trackLabel');
      const fieldTrack = document.getElementById('field_track');

      if (isJhs) {
        trackLabel.textContent = "Curriculum:";
        fieldTrack.textContent = info.track;
        document.getElementById('field_section').textContent = info.sec;
      } else {
        trackLabel.textContent = "Track / Strand:";
        fieldTrack.textContent = info.track;
        document.getElementById('field_section').textContent = info.sec;
      }

      renderGradeTable();
    }

    // Handle Student Selector Change
    function handleStudentChange(val) {
      if (val === 'sample') {
        document.getElementById('field_name').textContent = "DELA CRUZ, JUAN M.";
        document.getElementById('field_lrn').textContent = "454654546546";
        document.getElementById('field_sex').textContent = "Male";
        renderGradeTable();
      } else if (val === 'blank') {
        loadBlankForm();
      } else if (val.startsWith('db-')) {
        const sid = val.replace('db-', '');
        window.location.href = `index.php?student_id=${sid}`;
      }
    }

    // Render Grade Table based on Junior High vs Senior High
    function renderGradeTable() {
      const tbody = document.getElementById('gradesTableBody');
      tbody.innerHTML = '';

      const numCols = currentTermMode === '4quarters' ? 4 : 3;
      const isBlank = document.getElementById('studentSelect').value === 'blank';

      if (currentGradeLevel.startsWith('jhs')) {
        // Render Standard Junior High Subjects
        jhsSubjects.forEach(sub => {
          if (sub.isParent) {
            // Parent MAPEH Row
            const parentTr = document.createElement('tr');
            parentTr.className = 'group-row';
            parentTr.setAttribute('data-subject', sub.name);
            parentTr.innerHTML = `
              <td class="subj-name fw-bold">${sub.name}</td>
              ${generateGradeCells(sub.sampleGrades, numCols, isBlank, sub.name)}
              <td class="text-center final-cell fw-bold"></td>
              <td class="text-center remark-cell fw-bold"></td>
            `;
            tbody.appendChild(parentTr);

            // Sub-components: Music, Arts, PE, Health
            if (sub.subSubjects) {
              sub.subSubjects.forEach(subItem => {
                const subTr = document.createElement('tr');
                subTr.className = 'sub-row';
                subTr.setAttribute('data-subject', subItem.name);
                subTr.innerHTML = `
                  <td class="subj-name">${subItem.name}</td>
                  ${generateGradeCells(subItem.sampleGrades, numCols, isBlank, subItem.name)}
                  <td class="text-center final-cell fw-bold"></td>
                  <td class="text-center remark-cell"></td>
                `;
                tbody.appendChild(subTr);
              });
            }
          } else {
            // Standard JHS subject row
            const tr = document.createElement('tr');
            tr.setAttribute('data-subject', sub.name);
            tr.innerHTML = `
              <td class="subj-name">${sub.name}</td>
              ${generateGradeCells(sub.sampleGrades, numCols, isBlank, sub.name)}
              <td class="text-center final-cell fw-bold"></td>
              <td class="text-center remark-cell"></td>
            `;
            tbody.appendChild(tr);
          }
        });

      } else {
        // Render Senior High Subjects (STEM or TVL)
        const groups = currentGradeLevel === 'shs-11' ? shsSubjectsStem : shsSubjectsTvl;
        groups.forEach(grp => {
          const grpRow = document.createElement('tr');
          grpRow.className = 'group-row';
          grpRow.innerHTML = `<td colspan="${numCols + 3}">${grp.group}</td>`;
          tbody.appendChild(grpRow);

          grp.subjects.forEach(sub => {
            const tr = document.createElement('tr');
            tr.setAttribute('data-subject', sub.name);
            tr.innerHTML = `
              <td class="subj-name">${sub.name}</td>
              ${generateGradeCells(sub.sampleGrades, numCols, isBlank, sub.name)}
              <td class="text-center final-cell fw-bold"></td>
              <td class="text-center remark-cell"></td>
            `;
            tbody.appendChild(tr);
          });
        });
      }

      // Append General Average Row
      const genAvgRow = document.createElement('tr');
      genAvgRow.className = 'gen-avg-row';
      genAvgRow.innerHTML = `
        <td colspan="${numCols + 1}" class="text-right fw-bold" style="padding-right: 8px;">General Average</td>
        <td class="text-center fw-bold" id="genAvgVal"></td>
        <td class="text-center fw-bold" id="genAvgRemark"></td>
      `;
      tbody.appendChild(genAvgRow);

      setupCellListeners();
      calculateAllGrades();
    }

    // Helper to generate Grade TD cells
    function generateGradeCells(samples, numCols, isBlank, subjectName) {
      let cells = '';
      for (let i = 1; i <= numCols; i++) {
        let val = '';
        if (!isBlank) {
          // Check if DB student has recorded grade
          if (dbStudentGrades && dbStudentGrades[subjectName] && dbStudentGrades[subjectName][i] !== undefined) {
            val = Math.round(dbStudentGrades[subjectName][i]);
          } else if (samples && samples[i - 1] !== undefined) {
            val = samples[i - 1];
          }
        }
        cells += `<td class="text-center grade-cell" contenteditable="${isEditMode ? 'true' : 'false'}">${val}</td>`;
      }
      return cells;
    }

    // Load Blank Template
    function loadBlankForm() {
      document.getElementById('field_name').textContent = '';
      document.getElementById('field_age').textContent = '';
      document.getElementById('field_sex').textContent = '';
      document.getElementById('field_lrn').textContent = '';
      document.getElementById('field_section').textContent = '';

      renderGradeTable();

      // Clear attendance
      const attCells = document.querySelectorAll('#attendanceTable tbody tr td:not(.row-label):not(#totalClassDays):not(#totalDaysPresent):not(#totalDaysAbsent)');
      attCells.forEach(c => c.textContent = '');
      document.getElementById('totalClassDays').textContent = '';
      document.getElementById('totalDaysPresent').textContent = '';
      document.getElementById('totalDaysAbsent').textContent = '';
    }

    // Term Mode Update (3 terms vs 4 quarters)
    function updateTermMode(mode) {
      currentTermMode = mode;
      const colHeader = document.getElementById('termColHeader');
      const subTermRow = document.getElementById('subTermRow');
      const sigQ4 = document.getElementById('sigQuarter4');

      if (mode === '4quarters') {
        colHeader.setAttribute('colspan', '4');
        colHeader.textContent = 'QUARTER';
        subTermRow.innerHTML = `
          <th style="width: 7%;">1</th>
          <th style="width: 7%;">2</th>
          <th style="width: 7%;">3</th>
          <th style="width: 7%;">4</th>
        `;
        document.getElementById('sigLabel1').textContent = '1st Quarter:';
        document.getElementById('sigLabel2').textContent = '2nd Quarter:';
        document.getElementById('sigLabel3').textContent = '3rd Quarter:';
        if (sigQ4) sigQ4.style.display = 'flex';
      } else {
        colHeader.setAttribute('colspan', '3');
        colHeader.textContent = 'TERM';
        subTermRow.innerHTML = `
          <th style="width: 9%;">1</th>
          <th style="width: 9%;">2</th>
          <th style="width: 9%;">3</th>
        `;
        document.getElementById('sigLabel1').textContent = 'Term 1:';
        document.getElementById('sigLabel2').textContent = 'Term 2:';
        document.getElementById('sigLabel3').textContent = 'Term 3:';
        if (sigQ4) sigQ4.style.display = 'none';
      }

      renderGradeTable();
    }

    // Grade Calculations (Auto Computes Final Grades & General Average)
    function calculateAllGrades() {
      const rows = document.querySelectorAll('#gradesTableBody tr[data-subject]');
      const finals = [];

      rows.forEach(r => {
        // Skip sub-components from general average if parent exists
        const isSubRow = r.classList.contains('sub-row');

        const cells = r.querySelectorAll('.grade-cell');
        const vals = [];
        cells.forEach(c => {
          const num = parseFloat(c.textContent.trim());
          if (!isNaN(num)) vals.push(num);
        });

        const finalCell = r.querySelector('.final-cell');
        const remarkCell = r.querySelector('.remark-cell');

        if (vals.length > 0) {
          const avg = vals.reduce((a, b) => a + b, 0) / vals.length;
          const rounded = Math.round(avg);
          finalCell.textContent = rounded;

          if (!isSubRow) finals.push(rounded);

          if (rounded >= 75) {
            remarkCell.textContent = 'Passed';
            remarkCell.style.color = '#000';
          } else {
            remarkCell.textContent = 'Failed';
            remarkCell.style.color = '#dc2626';
          }
        } else {
          finalCell.textContent = '';
          remarkCell.textContent = '';
        }
      });

      const genAvgVal = document.getElementById('genAvgVal');
      const genAvgRemark = document.getElementById('genAvgRemark');

      if (finals.length > 0) {
        const genAvg = finals.reduce((a, b) => a + b, 0) / finals.length;
        genAvgVal.textContent = genAvg.toFixed(2);
        if (genAvg >= 75) {
          genAvgRemark.textContent = 'Passed';
          genAvgRemark.style.color = '#000';
        } else {
          genAvgRemark.textContent = 'Failed';
          genAvgRemark.style.color = '#dc2626';
        }
      } else {
        genAvgVal.textContent = '';
        genAvgRemark.textContent = '';
      }
    }

    // Setup input listeners for auto calculation
    function setupCellListeners() {
      const gradeCells = document.querySelectorAll('.grade-cell');
      gradeCells.forEach(cell => {
        cell.removeEventListener('input', calculateAllGrades);
        cell.addEventListener('input', calculateAllGrades);
      });

      const attCells = document.querySelectorAll('#attendanceTable tbody td[contenteditable]');
      attCells.forEach(cell => {
        cell.removeEventListener('input', calculateAttendanceTotals);
        cell.addEventListener('input', calculateAttendanceTotals);
      });
    }

    // Attendance Calculations
    function calculateAttendanceTotals() {
      const rows = document.querySelectorAll('#attendanceTable tbody tr');
      const totalIds = ['totalClassDays', 'totalDaysPresent', 'totalDaysAbsent'];

      rows.forEach((r, idx) => {
        const cells = r.querySelectorAll('td:not(.row-label):not(#' + totalIds[idx] + ')');
        let sum = 0;
        let hasValue = false;
        cells.forEach(c => {
          const val = parseFloat(c.textContent.trim());
          if (!isNaN(val)) {
            sum += val;
            hasValue = true;
          }
        });
        const target = document.getElementById(totalIds[idx]);
        if (target) target.textContent = hasValue ? sum : '';
      });
    }

    // Toggle Sim Print Mode
    function toggleSimPrintMode() {
      isSimPrint = !isSimPrint;
      document.body.classList.toggle('sim-print-mode', isSimPrint);
      const btn = document.getElementById('simPrintBtn');
      if (isSimPrint) {
        btn.classList.remove('btn-outline-secondary', 'text-white');
        btn.classList.add('btn-secondary');
        btn.innerHTML = '<i class="fas fa-desktop"></i> <span>Normal View</span>';
      } else {
        btn.classList.remove('btn-secondary');
        btn.classList.add('btn-outline-secondary', 'text-white');
        btn.innerHTML = '<i class="fas fa-eye"></i> <span>Clean View</span>';
      }
    }

    // Zoom Controls
    function adjustZoom(delta) {
      currentZoom = Math.min(Math.max(0.6, currentZoom + delta), 1.6);
      applyZoom();
    }
    function resetZoom() {
      currentZoom = 1.0;
      applyZoom();
    }
    function applyZoom() {
      const sheet = document.getElementById('sf9Sheet');
      sheet.style.transform = `scale(${currentZoom})`;
      document.getElementById('zoomDisplayBtn').textContent = `${Math.round(currentZoom * 100)}%`;
      if (currentZoom !== 1.0) {
        sheet.style.marginBottom = `${(currentZoom - 1.0) * sheet.offsetHeight}px`;
      } else {
        sheet.style.marginBottom = '0px';
      }
    }

    // Toggle Live In-browser Edit Mode
    function toggleEditMode() {
      isEditMode = !isEditMode;
      const sheet = document.getElementById('sf9Sheet');
      const btn = document.getElementById('editToggleBtn');

      if (isEditMode) {
        sheet.classList.add('editable-active');
        btn.classList.remove('btn-outline-warning');
        btn.classList.add('btn-warning');
        btn.innerHTML = '<i class="fas fa-check"></i> <span>Done Editing</span>';
        enableEditing(true);
      } else {
        sheet.classList.remove('editable-active');
        btn.classList.remove('btn-warning');
        btn.classList.add('btn-outline-warning');
        btn.innerHTML = '<i class="fas fa-edit"></i> <span>Edit Content</span>';
        enableEditing(false);
        calculateAllGrades();
        calculateAttendanceTotals();
      }
    }

    function enableEditing(enabled) {
      const editables = document.querySelectorAll('#sf9Sheet [contenteditable]');
      editables.forEach(el => {
        el.setAttribute('contenteditable', enabled ? 'true' : 'false');
      });
    }

    // Theme selector
    function updateTheme(themeClass) {
      const sheet = document.getElementById('sf9Sheet');
      sheet.className = `sf9-sheet ${themeClass}`;
      if (isEditMode) sheet.classList.add('editable-active');
    }
  </script>
</body>
</html>