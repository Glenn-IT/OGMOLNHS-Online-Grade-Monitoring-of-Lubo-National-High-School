# OGMS — PHP + MySQL + IoT Implementation Guide

**Lubo National High School | Online Grade Monitoring System**

> This guide converts the current static HTML prototype into a fully working PHP backend with MySQL database and IoT integration (SMS gateway). Follow every checklist item in order.

---

## Current State Summary

| Item                | Status                      |
| ------------------- | --------------------------- |
| HTML/CSS/JS Pages   | 17 pages — done             |
| PHP Backend         | None                        |
| MySQL Database      | None                        |
| Authentication      | localStorage only           |
| Data Persistence    | localStorage (mock data)    |
| SMS Notifications   | Simulated (no real gateway) |
| IoT / Hardware Link | None                        |

---

## Tech Stack (Target)

| Layer             | Technology                                  |
| ----------------- | ------------------------------------------- |
| Frontend          | HTML5, Bootstrap 5.3, Chart.js, Vanilla JS  |
| Backend           | PHP 8.x (XAMPP)                             |
| Database          | MySQL 8.x (XAMPP phpMyAdmin)                |
| Session           | PHP Sessions (`$_SESSION`)                  |
| Authentication    | PHP `password_hash()` / `password_verify()` |
| SMS / IoT Gateway | Semaphore API or Vonage (for real SMS)      |
| HTTP Client (JS)  | `fetch()` API calling PHP endpoints         |

---

## Folder Structure (After Implementation)

```
OGMS-Lubo-National-High-School/
├── index.php                  ← new login entry point (replace index.html)
├── logout.php
├── config/
│   └── db.php                 ← database connection
├── api/
│   ├── auth.php               ← login / logout / session check
│   ├── students.php           ← CRUD students
│   ├── grades.php             ← CRUD grades
│   ├── subjects.php           ← CRUD subjects
│   ├── teachers.php           ← CRUD teachers
│   ├── analytics.php          ← aggregated stats
│   ├── reports.php            ← report generation
│   └── sms.php                ← real SMS dispatch
├── assets/
│   ├── css/style.css          ← unchanged
│   ├── css/print.css          ← unchanged
│   └── js/
│       ├── app.js             ← update: replace localStorage calls with fetch()
│       └── api-client.js      ← new: centralized fetch() helper
├── components/
│   ├── admin-sidebar.php      ← convert from .html
│   └── student-sidebar.php    ← convert from .html
├── views/
│   ├── admin/                 ← convert all .html → .php
│   │   ├── dashboard.php
│   │   ├── manage-grades.php
│   │   ├── manage-students.php
│   │   ├── analytics.php
│   │   ├── reports.php
│   │   ├── sms.php
│   │   └── profile.php
│   └── student/               ← convert all .html → .php
│       ├── dashboard.php
│       ├── grades.php
│       ├── analytics.php
│       ├── reports.php
│       ├── profile.php
│       ├── signup.php
│       └── forgot-password.php
├── database/
│   └── ogms_schema.sql        ← full SQL schema + seed data
└── docs/
    └── PHP-IMPLEMENTATION-GUIDE.md  ← this file
```

---

## PHASE 1 — Database Setup

### 1.1 Create the MySQL Database

- [x] Open **phpMyAdmin** at `http://localhost/phpmyadmin`
- [x] Create a new database named `ogms_lnhs`
- [x] Set collation to `utf8mb4_unicode_ci`

### 1.2 Create the SQL Schema File

Create `database/ogms_schema.sql` with the following tables:

```sql
-- database/ogms_schema.sql

CREATE DATABASE IF NOT EXISTS ogms_lnhs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ogms_lnhs;

CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    lrn         VARCHAR(12) UNIQUE,                 -- LRN for students, NULL for admin
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(100) UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,              -- bcrypt hash
    role        ENUM('admin','teacher','student') NOT NULL DEFAULT 'student',
    phone       VARCHAR(20),
    address     TEXT,
    birthdate   DATE,
    gender      ENUM('Male','Female','Other'),
    avatar_url  VARCHAR(255),
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE school_years (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    label       VARCHAR(20) NOT NULL,              -- e.g. "2024-2025"
    is_active   TINYINT(1) DEFAULT 0
);

CREATE TABLE sections (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(50) NOT NULL,              -- e.g. "Grade 10 - Sampaguita"
    grade_level TINYINT NOT NULL,
    school_year_id INT,
    FOREIGN KEY (school_year_id) REFERENCES school_years(id)
);

CREATE TABLE subjects (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    code        VARCHAR(20),
    teacher_id  INT,
    section_id  INT,
    FOREIGN KEY (teacher_id) REFERENCES users(id),
    FOREIGN KEY (section_id) REFERENCES sections(id)
);

CREATE TABLE enrollments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    student_id  INT NOT NULL,
    section_id  INT NOT NULL,
    school_year_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (section_id) REFERENCES sections(id),
    FOREIGN KEY (school_year_id) REFERENCES school_years(id),
    UNIQUE KEY uq_enrollment (student_id, school_year_id)
);

CREATE TABLE grades (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    student_id  INT NOT NULL,
    subject_id  INT NOT NULL,
    quarter     TINYINT NOT NULL CHECK (quarter BETWEEN 1 AND 4),
    written_works   DECIMAL(5,2),
    performance_tasks DECIMAL(5,2),
    quarterly_exam  DECIMAL(5,2),
    final_grade DECIMAL(5,2),                     -- computed: 20% WW + 60% PT + 20% QE
    remarks     ENUM('Passed','Failed','Incomplete') DEFAULT NULL,
    encoded_by  INT,
    school_year_id INT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (encoded_by) REFERENCES users(id),
    FOREIGN KEY (school_year_id) REFERENCES school_years(id),
    UNIQUE KEY uq_grade (student_id, subject_id, quarter, school_year_id)
);

CREATE TABLE sms_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    recipient_phone VARCHAR(20) NOT NULL,
    recipient_name  VARCHAR(100),
    message     TEXT NOT NULL,
    status      ENUM('pending','sent','failed') DEFAULT 'pending',
    sent_at     TIMESTAMP NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE password_resets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    token       VARCHAR(64) NOT NULL,
    expires_at  DATETIME NOT NULL,
    used        TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

- [x] Run the SQL file in phpMyAdmin (`Import` tab) or via CLI:
  ```bash
  mysql -u root -p < database/ogms_schema.sql
  ```

### 1.3 Seed Initial Data

- [x] Insert default admin account:
  ```sql
  INSERT INTO users (full_name, email, password, role)
  VALUES ('Administrator', 'admin@lnhs.edu.ph', '$2y$10$REPLACE_WITH_BCRYPT_HASH', 'admin');
  ```
  Generate the hash in PHP:
  ```php
  echo password_hash('admin123', PASSWORD_BCRYPT);
  ```
- [x] Insert school year:
  ```sql
  INSERT INTO school_years (label, is_active) VALUES ('2024-2025', 1);
  ```
- [x] Insert at least one section and subjects (copy from `data/mock-data.js`)
- [x] Insert demo student accounts (copy from mock data, hash passwords)

---

## PHASE 2 — PHP Configuration & Connection

### 2.1 Create Database Config

Create `config/db.php`:

```php
<?php
// config/db.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // change if you set a MySQL password
define('DB_NAME', 'ogms_lnhs');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
```

- [x] `config/db.php` created
- [x] Database credentials are correct for your XAMPP setup
- [x] Tested connection (create a test file, delete after confirming)

### 2.2 Create Session Helper

Create `config/session.php`:

```php
<?php
// config/session.php
session_start();

function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: /OGMS-Lubo-National-High-School/index.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: /OGMS-Lubo-National-High-School/views/student/dashboard.php');
        exit;
    }
}

function requireStudent(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'student') {
        header('Location: /OGMS-Lubo-National-High-School/views/admin/dashboard.php');
        exit;
    }
}

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
```

- [x] `config/session.php` created

---

## PHASE 3 — Authentication (Login / Signup / Logout)

### 3.1 Convert `index.html` → `index.php`

- [x] Rename `index.html` to `index.php`
- [x] Add at the top:
  ```php
  <?php
  require_once 'config/session.php';
  if (!empty($_SESSION['user_id'])) {
      $redirect = $_SESSION['role'] === 'admin'
          ? 'views/admin/dashboard.php'
          : 'views/student/dashboard.php';
      header("Location: $redirect");
      exit;
  }
  ?>
  ```
- [x] Remove all `<script>` blocks that reference `localStorage` for login

### 3.2 Create `api/auth.php`

```php
<?php
// api/auth.php
require_once '../config/db.php';
require_once '../config/session.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'login') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        jsonResponse(['success' => false, 'message' => 'Email and password required.'], 400);
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid credentials.'], 401);
    }

    $_SESSION['user_id']   = $user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['email']     = $user['email'];

    jsonResponse([
        'success'  => true,
        'role'     => $user['role'],
        'redirect' => $user['role'] === 'admin'
            ? '../views/admin/dashboard.php'
            : '../views/student/dashboard.php',
    ]);
}

if ($action === 'logout') {
    session_destroy();
    jsonResponse(['success' => true]);
}

if ($action === 'check') {
    if (!empty($_SESSION['user_id'])) {
        jsonResponse(['logged_in' => true, 'role' => $_SESSION['role'], 'name' => $_SESSION['full_name']]);
    }
    jsonResponse(['logged_in' => false]);
}
```

- [x] `api/auth.php` created
- [x] Login form `action` attribute updated to post to `api/auth.php`
- [x] JS `fetch()` call handles `success: true/false` and redirects

### 3.3 Create `logout.php`

```php
<?php
require_once 'config/session.php';
session_destroy();
header('Location: index.php');
exit;
```

- [x] `logout.php` created
- [x] All sidebar logout buttons point to `logout.php`

### 3.4 Create `api/students.php` (Signup endpoint)

- [x] Handles `POST action=register` with LRN, full name, email, password
- [x] Validates LRN format (12 digits)
- [x] Hashes password with `password_hash()`
- [x] Inserts into `users` table with `role = 'student'`
- [x] Returns JSON `{success, message}`

---

## PHASE 4 — Convert HTML Pages to PHP

### 4.1 Conversion Template

At the top of every `.php` view file, add:

```php
<?php
require_once '../../config/session.php';
requireAdmin();   // or requireStudent() for student pages
?>
```

Then replace the static `<script>` data blocks with `fetch()` calls to the API.

### 4.2 Admin Pages

- [x] `views/admin/dashboard.php` — add session guard + remove localStorage stats
- [x] `views/admin/manage-students.php` — table loads from `api/students.php`
- [x] `views/admin/manage-grades.php` — table loads from `api/grades.php`
- [x] `views/admin/analytics.php` — chart data from `api/analytics.php`
- [x] `views/admin/reports.php` — report data from `api/reports.php`
- [x] `views/admin/sms.php` — SMS dispatch via `api/sms.php`
- [x] `views/admin/profile.php` — profile load/update via `api/students.php`

### 4.3 Student Pages

- [x] `views/student/dashboard.php` — session guard + grades summary from API
- [x] `views/student/grades.php` — grades table from `api/grades.php?student_id=`
- [x] `views/student/analytics.php` — chart data from `api/analytics.php`
- [x] `views/student/reports.php` — printable data from `api/reports.php`
- [x] `views/student/profile.php` — profile from `api/students.php`
- [x] `views/student/signup.php` — posts to `api/auth.php?action=register`
- [x] `views/student/forgot-password.php` — posts to `api/auth.php?action=reset_request`

### 4.4 Convert Sidebar Components

- [x] Rename `components/admin-sidebar.html` → `components/admin-sidebar.php`
- [x] Rename `components/student-sidebar.html` → `components/student-sidebar.php`
- [x] Each sidebar `<?php include ?>` can output the logged-in user's name from `$_SESSION`

---

## PHASE 5 — API Endpoints (PHP)

### 5.1 Students API (`api/students.php`)

| Method | Action            | Description                         |
| ------ | ----------------- | ----------------------------------- |
| GET    | `?action=list`    | Return all students (admin only)    |
| GET    | `?action=get&id=` | Return one student                  |
| POST   | `action=register` | Create new student account          |
| POST   | `action=update`   | Update profile fields               |
| POST   | `action=delete`   | Soft-delete student (`is_active=0`) |

- [x] `api/students.php` implemented
- [x] All endpoints use prepared statements (no raw SQL concatenation)
- [x] Admin-only endpoints check `$_SESSION['role'] === 'admin'`

### 5.2 Grades API (`api/grades.php`)

| Method | Action                     | Description                        |
| ------ | -------------------------- | ---------------------------------- |
| GET    | `?action=list&student_id=` | Grades for one student             |
| GET    | `?action=list&subject_id=` | Grades for one subject             |
| POST   | `action=save`              | Insert or update a grade record    |
| POST   | `action=delete`            | Remove a grade record (admin only) |

- [x] `api/grades.php` implemented
- [x] `final_grade` computed on save: `(written_works * 0.20) + (performance_tasks * 0.60) + (quarterly_exam * 0.20)`
- [x] `remarks` set automatically: ≥ 75 = Passed, < 75 = Failed

### 5.3 Analytics API (`api/analytics.php`)

- [x] Returns class average per subject
- [x] Returns student trend per quarter
- [x] Returns pass/fail counts
- [x] Returns top/bottom performers (admin)
- [x] Data format matches existing Chart.js `labels` / `datasets` structure

### 5.4 Reports API (`api/reports.php`)

- [x] Class summary report (all students, all subjects, all quarters)
- [x] Subject performance report (one subject, all students)
- [x] Individual student report card (one student, all subjects, all quarters)
- [x] Returns JSON or triggers PHP-based PDF generation (optional: use mPDF library)

### 5.5 SMS API (`api/sms.php`) ⏳ Pending — Semaphore setup

- [ ] Receives `recipient_phone`, `message` via POST
- [ ] Logs to `sms_logs` table with `status = 'pending'`
- [ ] Calls Semaphore API (or Vonage):

  ```php
  // Semaphore SMS (Philippine carrier-compatible)
  $apiKey   = 'YOUR_SEMAPHORE_API_KEY';
  $response = file_get_contents('https://api.semaphore.co/api/v4/messages', false,
      stream_context_create(['http' => [
          'method'  => 'POST',
          'header'  => 'Content-Type: application/x-www-form-urlencoded',
          'content' => http_build_query([
              'apikey'     => $apiKey,
              'number'     => $phone,
              'message'    => $message,
              'sendername' => 'LNHS_OGMS',
          ]),
      ]])
  );
  ```

- [ ] Updates `sms_logs` row with `status = 'sent'` or `'failed'`
- [ ] Admin-only endpoint

---

## PHASE 6 — JavaScript Update (Replace localStorage with fetch)

### 6.1 Create `assets/js/api-client.js`

```javascript
// assets/js/api-client.js — centralized API helper
const API = {
  BASE: "/OGMS-Lubo-National-High-School/api/",

  async get(endpoint, params = {}) {
    const url = new URL(this.BASE + endpoint, window.location.origin);
    Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
    const res = await fetch(url);
    if (!res.ok) throw new Error(`API error ${res.status}`);
    return res.json();
  },

  async post(endpoint, data = {}) {
    const body = new FormData();
    Object.entries(data).forEach(([k, v]) => body.append(k, v));
    const res = await fetch(this.BASE + endpoint, { method: "POST", body });
    if (!res.ok) throw new Error(`API error ${res.status}`);
    return res.json();
  },
};
```

### 6.2 Update `assets/js/app.js`

- [x] Remove all `localStorage.getItem / setItem` calls
- [x] Remove `initMockData()` call
- [x] Replace login logic with `API.post('auth.php', {action:'login', email, password})`
- [x] Replace auth check with `API.get('auth.php', {action:'check'})`
- [x] Keep toast notifications and loading overlay helpers (they are UI-only, no change needed)

### 6.3 Update Each Page's Inline Script

For every page that loaded data from localStorage, replace with `fetch()`:

```javascript
// Before (localStorage)
const grades = JSON.parse(localStorage.getItem("grades")) || [];

// After (fetch API)
const grades = await API.get("grades.php", {
  action: "list",
  student_id: SESSION_USER_ID,
});
```

- [x] `app.js` updated
- [x] `api-client.js` created and included in all pages
- [x] Each admin page fetches from its matching API endpoint
- [x] Each student page fetches from its matching API endpoint

---

## PHASE 7 — IoT Integration (SMS Gateway)

The "IoT" component of this system is the real-time SMS notification to parents/guardians when grades are updated or report cards are finalized.

### 7.1 Semaphore API Setup (Recommended for Philippines)

- [ ] Create a free account at [semaphore.co](https://semaphore.co)
- [ ] Get your API key from the dashboard
- [ ] Store it in `config/db.php` as a constant:
  ```php
  define('SMS_API_KEY', 'your_semaphore_api_key_here');
  define('SMS_SENDER',  'LNHS_OGMS');
  ```
- [ ] Test sending a message to your own phone number

### 7.2 SMS Trigger Points

Configure `api/sms.php` to be called automatically when:

- [ ] A grade is saved/updated (`api/grades.php` calls `sms.php` internally)
- [ ] Admin manually sends a bulk SMS from `views/admin/sms.php`
- [ ] Quarter report is finalized (trigger on all students in a section)

### 7.3 SMS Log Viewer (Admin)

The `views/admin/sms.php` page already has a UI for this. Wire it up:

- [ ] Load `sms_logs` table via `api/sms.php?action=logs`
- [ ] Show `recipient_name`, `message`, `status`, `sent_at`
- [ ] Add retry button for `failed` messages

### 7.4 Optional — RFID / Attendance IoT (Future Extension)

If the school has RFID card readers for attendance:

- [ ] Add `attendance` table to database schema
- [ ] Create `api/attendance.php` with a POST endpoint that accepts `rfid_tag` + timestamp
- [ ] RFID reader device posts to `http://localhost/.../api/attendance.php` over local network
- [ ] Link attendance records to student profile page

---

## PHASE 8 — Security Hardening

- [x] All SQL queries use **PDO prepared statements** (no string concatenation)
- [x] All `$_POST` / `$_GET` inputs are validated and sanitized before use
- [x] Passwords stored as `password_hash($pass, PASSWORD_BCRYPT)` — never plaintext
- [x] Session tokens regenerated on login: `session_regenerate_id(true)`
- [x] API endpoints check `$_SESSION['role']` before processing (no client-side role trust)
- [x] File uploads (avatars) validate MIME type and store outside webroot or in a controlled path (no uploads currently — avatar_url is a text field only)
- [x] CORS headers set if API is called cross-origin (not needed for same-origin XAMPP setup)
- [x] `config/db.php` is not directly accessible from the browser (`config/.htaccess` — Deny from all)
- [x] `database/` protected by `database/.htaccess` — Deny from all
- [x] Root `.htaccess` added — blocks directory listing, protects config/database/data paths
- [x] Security headers added to all API responses via `jsonResponse()`: X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Cache-Control
- [x] Brute-force login guard added: 10 failed attempts per session triggers 429
- [x] Password minimum length enforced at 8 characters across all endpoints
- [x] Remove `data/mock-data.js` from production — deleted

---

## PHASE 9 — Testing Checklist

### Authentication

- [x] Admin can log in with correct credentials
- [x] Student can log in with correct credentials
- [x] Wrong password shows error message
- [x] Logged-in admin redirects to `views/admin/dashboard.php`
- [x] Logged-in student redirects to `views/student/dashboard.php`
- [x] Accessing a protected page while logged out redirects to login
- [x] Logout clears session and redirects to login

### Grades

- [x] Admin can add a new grade record
- [x] Admin can edit an existing grade
- [x] Admin can delete a grade
- [x] `final_grade` is computed correctly on save
- [x] `remarks` (Passed/Failed) is set correctly
- [x] Student can view their own grades (cannot see other students)

### SMS

- [ ] Test SMS sends to a real phone number via Semaphore
- [ ] SMS log shows the correct status after sending
- [ ] Failed SMS shows in log with `failed` status

### Analytics

- [ ] Charts render with real data from the database
- [ ] Class average chart reflects actual grades
- [ ] Student trend chart shows quarterly progression

### Reports

- [ ] Individual report card shows correct data
- [ ] Print view renders cleanly
- [ ] Class summary report shows all students

---

## PHASE 10 — Deployment Checklist (XAMPP Local)

- [ ] XAMPP Apache and MySQL services are running
- [ ] Database `ogms_lnhs` is created and seeded
- [ ] All files are under `C:\xampp\htdocs\OGMS-Lubo-National-High-School\`
- [ ] `http://localhost/OGMS-Lubo-National-High-School/` loads `index.php` (not `index.html`)
- [ ] No PHP errors in browser or in `C:\xampp\logs\php_error_log`
- [ ] All pages load without JavaScript console errors
- [ ] SMS API key is configured and tested

---

## Implementation Priority Order

| Priority     | Phase                        | Effort    |
| ------------ | ---------------------------- | --------- |
| 1 (Do First) | Phase 1 — Database Setup     | 1–2 hours |
| 2            | Phase 2 — PHP Config         | 30 min    |
| 3            | Phase 3 — Authentication     | 1–2 hours |
| 4            | Phase 5 — API Endpoints      | 3–4 hours |
| 5            | Phase 4 — Convert HTML → PHP | 2–3 hours |
| 6            | Phase 6 — JS Update          | 2 hours   |
| 7            | Phase 7 — IoT / SMS          | 1–2 hours |
| 8            | Phase 8 — Security           | 1 hour    |
| 9            | Phase 9 — Testing            | 1–2 hours |

**Total Estimated Effort: 12–18 hours**

---

## Quick Reference — File Mapping (HTML → PHP)

| Old File                             | New File                            | Notes                            |
| ------------------------------------ | ----------------------------------- | -------------------------------- |
| `index.html`                         | `index.php`                         | Add session redirect             |
| `views/admin/dashboard.html`         | `views/admin/dashboard.php`         | requireAdmin() guard             |
| `views/admin/manage-students.html`   | `views/admin/manage-students.php`   | CRUD via api/students.php        |
| `views/admin/manage-grades.html`     | `views/admin/manage-grades.php`     | CRUD via api/grades.php          |
| `views/admin/analytics.html`         | `views/admin/analytics.php`         | Chart data via api/analytics.php |
| `views/admin/reports.html`           | `views/admin/reports.php`           | Data via api/reports.php         |
| `views/admin/sms.html`               | `views/admin/sms.php`               | Real SMS via api/sms.php         |
| `views/admin/profile.html`           | `views/admin/profile.php`           | Update via api/students.php      |
| `views/student/dashboard.html`       | `views/student/dashboard.php`       | requireStudent() guard           |
| `views/student/grades.html`          | `views/student/grades.php`          | Own grades only                  |
| `views/student/analytics.html`       | `views/student/analytics.php`       | Own data only                    |
| `views/student/reports.html`         | `views/student/reports.php`         | Own report card                  |
| `views/student/profile.html`         | `views/student/profile.php`         | Own profile only                 |
| `views/student/signup.html`          | `views/student/signup.php`          | POST to api/auth.php             |
| `views/student/forgot-password.html` | `views/student/forgot-password.php` | Password reset flow              |
| `components/admin-sidebar.html`      | `components/admin-sidebar.php`      | Session user name                |
| `components/student-sidebar.html`    | `components/student-sidebar.php`    | Session user name                |

---

_Last updated: 2026-06-06 | Version: 1.0_
