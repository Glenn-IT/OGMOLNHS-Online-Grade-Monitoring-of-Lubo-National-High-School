-- Migration: Add guardian_phone column to users table
ALTER TABLE users ADD COLUMN guardian_phone VARCHAR(20) DEFAULT NULL AFTER guardian_name;
