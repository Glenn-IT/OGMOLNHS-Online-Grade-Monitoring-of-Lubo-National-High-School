-- Migration: Add teacher_subjects table to support subject + grade level teacher assignments
-- Date: 2026-10-01

CREATE TABLE IF NOT EXISTS teacher_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    grade_level TINYINT NOT NULL,
    school_year_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (school_year_id) REFERENCES school_years(id) ON DELETE CASCADE,
    UNIQUE KEY uq_teacher_sub_grade (teacher_id, subject_id, grade_level, school_year_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assign Maria Santos (id 87) to teach Filipino (id 21) for Grade 7 only
INSERT INTO teacher_subjects (teacher_id, subject_id, grade_level, school_year_id)
SELECT 87, 21, 7, 5
WHERE NOT EXISTS (
    SELECT 1 FROM teacher_subjects WHERE teacher_id = 87 AND subject_id = 21 AND grade_level = 7 AND school_year_id = 5
);

-- If Alucard Sigo (id 90) exists, assign to Math (id 18) for Grade 8
INSERT INTO teacher_subjects (teacher_id, subject_id, grade_level, school_year_id)
SELECT 90, 18, 8, 5
FROM users WHERE id = 90
AND NOT EXISTS (
    SELECT 1 FROM teacher_subjects WHERE teacher_id = 90 AND subject_id = 18 AND grade_level = 8 AND school_year_id = 5
);
