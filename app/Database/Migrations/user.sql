-- ============================================
-- BillPay - Billing System Database
-- Complete SQL Schema
-- ============================================

-- ============================================
-- 1. USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user' COMMENT 'admin, user, merchant',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. TRANSACTIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL COMMENT 'electricity, water, internet, phone, topup, transfer',
    description VARCHAR(255) NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, success, failed',
    reference VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_transactions_user (user_id),
    INDEX idx_transactions_status (status),
    INDEX idx_transactions_type (type),
    INDEX idx_transactions_date (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. BILLS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bill_type VARCHAR(50) NOT NULL COMMENT 'electricity, water, internet, phone',
    bill_number VARCHAR(100) NULL,
    description VARCHAR(255) NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    due_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, paid, overdue',
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_bills_user (user_id),
    INDEX idx_bills_status (status),
    INDEX idx_bills_due (due_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. WALLETS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    balance DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_wallets_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. TOPUPS TABLE (Midtrans Transactions)
-- ============================================
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

-- ============================================
-- 6. WALLET TRANSACTIONS TABLE (History)
-- ============================================
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

-- ============================================
-- 7. SAMPLE DATA - USERS (password: password123)
-- ============================================
INSERT INTO users (id, name, email, password, role) VALUES
(1, 'Admin Demo', 'admin@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'User Demo', 'user@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
(3, 'Merchant Demo', 'merchant@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'merchant'),
(4, 'John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
(5, 'Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user')
ON DUPLICATE KEY UPDATE name = VALUES(name), role = VALUES(role);

-- ============================================
-- 8. SAMPLE DATA - WALLETS
-- ============================================
INSERT INTO wallets (user_id, balance) VALUES
(2, 500000),
(4, 1000000),
(5, 250000)
ON DUPLICATE KEY UPDATE balance = VALUES(balance);

-- ============================================
-- 9. SAMPLE DATA - TRANSACTIONS
-- ============================================
INSERT IGNORE INTO transactions (user_id, type, description, amount, status, reference, created_at) VALUES
(2, 'electricity', 'PLN Token - January 2025', 350000, 'success', 'TRX001234', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'water', 'PDAM - January 2025', 150000, 'success', 'TRX001235', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'internet', 'IndiHome - January 2025', 500000, 'pending', 'TRX001236', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(4, 'electricity', 'PLN Token - January 2025', 280000, 'success', 'TRX001237', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(4, 'topup', 'E-Wallet Top Up', 1000000, 'success', 'TRX001238', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(5, 'phone', 'Telkomsel - January 2025', 200000, 'success', 'TRX001239', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(5, 'internet', 'Biznet - January 2025', 450000, 'failed', 'TRX001240', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(2, 'electricity', 'PLN Token - December 2024', 320000, 'success', 'TRX001241', DATE_SUB(NOW(), INTERVAL 30 DAY)),
(4, 'water', 'PDAM - December 2024', 180000, 'success', 'TRX001242', DATE_SUB(NOW(), INTERVAL 35 DAY)),
(5, 'topup', 'GoPay Top Up', 500000, 'success', 'TRX001243', DATE_SUB(NOW(), INTERVAL 40 DAY));

-- ============================================
-- 10. SAMPLE DATA - BILLS
-- ============================================
INSERT IGNORE INTO bills (user_id, bill_type, bill_number, description, amount, due_date, status, created_at) VALUES
(2, 'electricity', 'PLN-123456789', 'PLN - February 2025', 350000, DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'pending', NOW()),
(2, 'water', 'PDAM-987654321', 'PDAM - February 2025', 150000, DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'pending', NOW()),
(2, 'internet', 'INET-456789123', 'IndiHome - February 2025', 500000, DATE_ADD(CURDATE(), INTERVAL 21 DAY), 'pending', NOW()),
(4, 'electricity', 'PLN-111222333', 'PLN - February 2025', 280000, DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'pending', NOW()),
(4, 'phone', 'TEL-444555666', 'Telkomsel Halo - February 2025', 250000, DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'pending', NOW()),
(5, 'internet', 'INET-777888999', 'Biznet - February 2025', 450000, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'pending', NOW()),
(2, 'phone', 'TEL-000111222', 'XL Postpaid - January 2025', 200000, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'paid', DATE_SUB(NOW(), INTERVAL 15 DAY));

-- ============================================
-- 11. SAMPLE DATA - WALLET TRANSACTIONS
-- ============================================
INSERT IGNORE INTO wallet_transactions (user_id, type, amount, description, reference_type, balance_before, balance_after, created_at) VALUES
(2, 'credit', 500000, 'Top Up Saldo', 'topup', 0, 500000, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(4, 'credit', 1000000, 'Top Up Saldo', 'topup', 0, 1000000, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(5, 'credit', 500000, 'Top Up Saldo', 'topup', 0, 500000, DATE_SUB(NOW(), INTERVAL 7 DAY)),
(5, 'debit', 250000, 'Pembayaran Tagihan Listrik', 'bill_payment', 500000, 250000, DATE_SUB(NOW(), INTERVAL 3 DAY));
