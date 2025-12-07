<?php
/**
 * Database Setup Script
 * Run this script once to create required tables for wallet and topup
 * 
 * Usage: php setup_db.php
 */

// Load CodeIgniter bootstrap
require_once __DIR__ . '/system/Boot.php';
$paths = new \Config\Paths();
require_once $paths->systemDirectory . '/Boot.php';

// Get database connection
$db = \Config\Database::connect();

echo "Starting database setup...\n\n";

// Create wallets table
$sql = "CREATE TABLE IF NOT EXISTS wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    balance DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_wallets_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $db->query($sql);
    echo "✓ Table 'wallets' created successfully\n";
} catch (Exception $e) {
    echo "✗ Error creating 'wallets' table: " . $e->getMessage() . "\n";
}

// Create topups table
$sql = "CREATE TABLE IF NOT EXISTS topups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id VARCHAR(100) NOT NULL UNIQUE,
    amount DECIMAL(15,2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    payment_type VARCHAR(50) NULL,
    snap_token VARCHAR(255) NULL,
    midtrans_response TEXT NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_topups_user (user_id),
    INDEX idx_topups_order (order_id),
    INDEX idx_topups_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $db->query($sql);
    echo "✓ Table 'topups' created successfully\n";
} catch (Exception $e) {
    echo "✗ Error creating 'topups' table: " . $e->getMessage() . "\n";
}

// Create wallet_transactions table
$sql = "CREATE TABLE IF NOT EXISTS wallet_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(20) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description VARCHAR(255) NULL,
    reference_type VARCHAR(50) NULL,
    reference_id INT NULL,
    balance_before DECIMAL(15,2) NOT NULL DEFAULT 0,
    balance_after DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_wallet_tx_user (user_id),
    INDEX idx_wallet_tx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $db->query($sql);
    echo "✓ Table 'wallet_transactions' created successfully\n";
} catch (Exception $e) {
    echo "✗ Error creating 'wallet_transactions' table: " . $e->getMessage() . "\n";
}

// Add columns to bills table if not exists
try {
    $db->query("ALTER TABLE bills ADD COLUMN bill_type VARCHAR(50) NULL AFTER user_id");
    echo "✓ Column 'bill_type' added to bills table\n";
} catch (Exception $e) {
    echo "- Column 'bill_type' already exists or error: " . $e->getMessage() . "\n";
}

try {
    $db->query("ALTER TABLE bills ADD COLUMN bill_number VARCHAR(100) NULL AFTER bill_type");
    echo "✓ Column 'bill_number' added to bills table\n";
} catch (Exception $e) {
    echo "- Column 'bill_number' already exists or error: " . $e->getMessage() . "\n";
}

echo "\n✓ Database setup complete!\n";
