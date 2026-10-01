-- Migration: Add school_posts table, email_verifications table, adviser_id to sections, and seed demo teacher and school posts
-- Date: 2026-10-01

CREATE TABLE IF NOT EXISTS school_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('announcement', 'event', 'highlight') NOT NULL DEFAULT 'announcement',
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    event_date DATE NULL,
    author_name VARCHAR(100) DEFAULT 'School Administration',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(191) NOT NULL,
    otp VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_otp (email, otp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add adviser_id to sections if it does not exist
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
                   WHERE table_schema = DATABASE() AND table_name = 'sections' AND column_name = 'adviser_id');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE sections ADD COLUMN adviser_id INT NULL AFTER grade_level, ADD CONSTRAINT fk_sections_adviser FOREIGN KEY (adviser_id) REFERENCES users(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Seed default teacher Maria Santos if not exists
INSERT INTO users (full_name, email, password, role, is_active)
SELECT 'Maria Santos', 'teacher@lnhs.edu.ph', '$2y$10$iWmcqN7uu3ZXMwxlWvEiR.6HT5psArkliCh2Q6b872DvjAVlU87vy', 'teacher', 1
WHERE NOT EXISTS (SELECT id FROM users WHERE email = 'teacher@lnhs.edu.ph');

-- Assign Maria Santos as adviser for Section 52 (Rizal) if currently NULL
UPDATE sections SET adviser_id = (SELECT id FROM users WHERE email = 'teacher@lnhs.edu.ph' LIMIT 1)
WHERE id = 52 AND (adviser_id IS NULL OR adviser_id = 0);

-- Seed initial school posts if table is empty
INSERT INTO school_posts (type, title, content, event_date, author_name, is_active)
SELECT 'announcement', 'Welcome to School Year 2025–2026', 'Lubo National High School warmly welcomes all Junior and Senior High School learners, parents, and stakeholders to another fruitful year of quality basic education.', '2026-09-01', 'Office of the Principal', 1
WHERE NOT EXISTS (SELECT id FROM school_posts WHERE title = 'Welcome to School Year 2025–2026');

INSERT INTO school_posts (type, title, content, event_date, author_name, is_active)
SELECT 'event', '1st Quarter General Parents-Teachers Association (GPTA) Meeting', 'All parents and guardians are cordially invited to attend the 1st Quarter GPTA Assembly at the LNHS Gymnasium to discuss school programs and student welfare.', '2026-10-15', 'GPTA Board', 1
WHERE NOT EXISTS (SELECT id FROM school_posts WHERE title = '1st Quarter General Parents-Teachers Association (GPTA) Meeting');

INSERT INTO school_posts (type, title, content, event_date, author_name, is_active)
SELECT 'highlight', 'LNHS Mathletes Triumph in Regional Science & Math Fair', 'Congratulations to our LNHS learners who bagged top honors in the Regional Science, Technology, and Math Fair. Your LNHS family is truly proud of you!', '2026-09-20', 'Math Department', 1
WHERE NOT EXISTS (SELECT id FROM school_posts WHERE title = 'LNHS Mathletes Triumph in Regional Science & Math Fair');
