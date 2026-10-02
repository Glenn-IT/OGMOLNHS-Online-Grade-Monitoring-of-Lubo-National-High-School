-- Migration: Add approval_status to users table for teacher registration workflow
-- Date: 2026-10-02

USE ogms_lnhs;

-- Add approval_status column if it doesn't already exist
SET @colExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'users' 
      AND COLUMN_NAME = 'approval_status'
);

SET @sql = IF(@colExists = 0,
    "ALTER TABLE users ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved' AFTER is_active",
    "SELECT 'Column approval_status already exists' AS info"
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure all existing users are marked as approved
UPDATE users SET approval_status = 'approved' WHERE approval_status IS NULL OR approval_status = '';
