-- database/ogms_schema.sql
-- OGMS Lubo National High School
-- Run via phpMyAdmin Import tab or: mysql -u root -p < database/ogms_schema.sql

CREATE DATABASE IF NOT EXISTS ogms_lnhs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ogms_lnhs;

-- ─── USERS ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    lrn         VARCHAR(12) UNIQUE,
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(100) UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,
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

-- ─── SCHOOL YEARS ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS school_years (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    label       VARCHAR(20) NOT NULL,
    is_active   TINYINT(1) DEFAULT 0
);

-- ─── SECTIONS ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sections (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(50) NOT NULL,
    grade_level     TINYINT NOT NULL,
    school_year_id  INT,
    FOREIGN KEY (school_year_id) REFERENCES school_years(id)
);

-- ─── SUBJECTS ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS subjects (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    code        VARCHAR(20),
    teacher_id  INT,
    section_id  INT,
    FOREIGN KEY (teacher_id) REFERENCES users(id),
    FOREIGN KEY (section_id) REFERENCES sections(id)
);

-- ─── ENROLLMENTS ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS enrollments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    student_id      INT NOT NULL,
    section_id      INT NOT NULL,
    school_year_id  INT NOT NULL,
    enrolled_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id)     REFERENCES users(id),
    FOREIGN KEY (section_id)     REFERENCES sections(id),
    FOREIGN KEY (school_year_id) REFERENCES school_years(id),
    UNIQUE KEY uq_enrollment (student_id, school_year_id)
);

-- ─── GRADES ───────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS grades (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    student_id          INT NOT NULL,
    subject_id          INT NOT NULL,
    quarter             TINYINT NOT NULL CHECK (quarter BETWEEN 1 AND 4),
    written_works       DECIMAL(5,2),
    performance_tasks   DECIMAL(5,2),
    quarterly_exam      DECIMAL(5,2),
    final_grade         DECIMAL(5,2),
    remarks             ENUM('Passed','Failed','Incomplete') DEFAULT NULL,
    encoded_by          INT,
    school_year_id      INT NOT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id)     REFERENCES users(id),
    FOREIGN KEY (subject_id)     REFERENCES subjects(id),
    FOREIGN KEY (encoded_by)     REFERENCES users(id),
    FOREIGN KEY (school_year_id) REFERENCES school_years(id),
    UNIQUE KEY uq_grade (student_id, subject_id, quarter, school_year_id)
);

-- ─── SMS LOGS ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sms_logs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    recipient_phone VARCHAR(20) NOT NULL,
    recipient_name  VARCHAR(100),
    message         TEXT NOT NULL,
    status          ENUM('pending','sent','failed') DEFAULT 'pending',
    sent_at         TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── PASSWORD RESETS ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS password_resets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    token       VARCHAR(64) NOT NULL,
    expires_at  DATETIME NOT NULL,
    used        TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
