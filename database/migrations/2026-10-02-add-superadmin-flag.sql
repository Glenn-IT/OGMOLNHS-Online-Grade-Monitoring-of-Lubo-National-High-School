-- Migration: Add is_superadmin flag to users table and designate current admin as superadmin
-- Date: 2026-10-02

USE ogms_lnhs;

-- Add is_superadmin column if not exists
SET @colExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'users' 
      AND COLUMN_NAME = 'is_superadmin'
);

SET @sql = IF(@colExists = 0,
    "ALTER TABLE users ADD COLUMN is_superadmin TINYINT(1) NOT NULL DEFAULT 0 AFTER role",
    "SELECT 'Column is_superadmin already exists' AS info"
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Designate the existing administrator as Superadmin
UPDATE users 
SET is_superadmin = 1, is_active = 1, approval_status = 'approved' 
WHERE role = 'admin' 
ORDER BY id ASC 
LIMIT 1;
