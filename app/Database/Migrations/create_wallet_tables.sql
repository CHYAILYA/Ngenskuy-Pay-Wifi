-- ============================================
-- Create Wallets and Topups Tables
-- Run this SQL to create the required tables
-- ============================================

-- 1. WALLETS TABLE
CREATE TABLE IF NOT EXISTS wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    balance DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_wallets_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TOPUPS TABLE (Midtrans Transactions)
CREATE TABLE IF NOT EXISTS topups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id VARCHAR(100) NOT NULL UNIQUE,
    amount DECIMAL(15,2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, success, failed, expired',
    payment_type VARCHAR(50) NULL,
    snap_token VARCHAR(255) NULL,
    midtrans_response TEXT NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_topups_user (user_id),
    INDEX idx_topups_order (order_id),
    INDEX idx_topups_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. WALLET TRANSACTIONS TABLE (History)
CREATE TABLE IF NOT EXISTS wallet_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(20) NOT NULL COMMENT 'credit, debit',
    amount DECIMAL(15,2) NOT NULL,
    description VARCHAR(255) NULL,
    reference_type VARCHAR(50) NULL COMMENT 'topup, bill_payment, transfer',
    reference_id INT NULL,
    balance_before DECIMAL(15,2) NOT NULL DEFAULT 0,
    balance_after DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_wallet_tx_user (user_id),
    INDEX idx_wallet_tx_type (type),
    INDEX idx_wallet_tx_date (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Add bill_type and bill_number columns to bills if not exists
ALTER TABLE bills 
ADD COLUMN IF NOT EXISTS bill_type VARCHAR(50) NULL AFTER user_id,
ADD COLUMN IF NOT EXISTS bill_number VARCHAR(100) NULL AFTER bill_type;

-- 5. Insert sample wallets
INSERT INTO wallets (user_id, balance) VALUES
(1, 0),
(2, 500000),
(3, 0)
ON DUPLICATE KEY UPDATE balance = balance;

SELECT 'Tables created successfully!' AS result;
