-- Migration: Add sender_id to sms_logs table to track teacher/admin dispatches
-- Date: 2026-10-01

ALTER TABLE sms_logs 
ADD COLUMN sender_id INT NULL DEFAULT NULL AFTER message,
ADD CONSTRAINT fk_sms_logs_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL,
ADD INDEX idx_sms_logs_sender (sender_id);
