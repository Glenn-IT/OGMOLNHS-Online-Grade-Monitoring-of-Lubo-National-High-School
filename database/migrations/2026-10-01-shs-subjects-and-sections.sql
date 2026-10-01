-- database/migrations/2026-10-01-shs-subjects-and-sections.sql
-- Add level (JHS/SHS) column to subjects and seed standard Senior High School subjects and sections

-- 1. Add level column to subjects
SET @col_exists := (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'subjects' 
      AND COLUMN_NAME = 'level'
);

SET @stmt := IF(@col_exists = 0, 
    'ALTER TABLE `subjects` ADD COLUMN `level` ENUM(\'JHS\', \'SHS\') NOT NULL DEFAULT \'JHS\' AFTER `code`', 
    'SELECT "Column level already exists in subjects"'
);
PREPARE add_level_col FROM @stmt;
EXECUTE add_level_col;
DEALLOCATE PREPARE add_level_col;

-- 2. Mark existing core subjects as JHS
UPDATE `subjects` SET `level` = 'JHS' WHERE `level` IS NULL OR `level` = '';

-- 3. Insert standard DepEd Senior High School (SHS) Subjects
INSERT IGNORE INTO `subjects` (`name`, `code`, `level`) VALUES
('Oral Communication in Context', 'OCC', 'SHS'),
('Reading and Writing Skills', 'RWS', 'SHS'),
('Komunikasyon at Pananaliksik sa Wika at Kulturang Pilipino', 'KPWKP', 'SHS'),
('21st Century Literature from the Philippines and the World', '21CLPW', 'SHS'),
('Contemporary Philippine Arts from the Regions', 'CPAR', 'SHS'),
('Media and Information Literacy', 'MIL', 'SHS'),
('General Mathematics', 'GENMATH', 'SHS'),
('Statistics and Probability', 'STATPROB', 'SHS'),
('Earth and Life Science', 'ELS', 'SHS'),
('Physical Science', 'PHYSCI', 'SHS'),
('Personal Development', 'PERDEV', 'SHS'),
('Understanding Culture, Society, and Politics', 'UCSP', 'SHS'),
('Physical Education and Health', 'PEH', 'SHS'),
('English for Academic and Professional Purposes', 'EAPP', 'SHS'),
('Practical Research 1', 'PR1', 'SHS'),
('Practical Research 2', 'PR2', 'SHS'),
('Empowerment Technologies', 'EMPTECH', 'SHS'),
('Entrepreneurship', 'ENTREP', 'SHS'),
('Inquiries, Investigations and Immersion', 'III', 'SHS');

-- 4. Seed Senior High School sections in active school year if needed
SET @active_sy := (SELECT `id` FROM `school_years` WHERE `is_active` = 1 LIMIT 1);
SET @active_sy := IFNULL(@active_sy, 5);

INSERT IGNORE INTO `sections` (`name`, `grade_level`, `school_year_id`) VALUES
('STEM-A', 11, @active_sy),
('HUMSS-A', 11, @active_sy),
('TVL-11', 11, @active_sy),
('STEM-B', 12, @active_sy),
('HUMSS-B', 12, @active_sy),
('TVL-12', 12, @active_sy);
