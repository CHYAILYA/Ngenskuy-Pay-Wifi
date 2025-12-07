-- Add missing columns to users table
-- Run this SQL to add card_number and updated_at columns

-- Add card_number column if not exists
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS card_number VARCHAR(20) NULL AFTER role,
ADD INDEX IF NOT EXISTS idx_users_card (card_number);

-- Add updated_at column if not exists  
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Verify columns
DESCRIBE users;
