-- Wallet Withdrawals Feature - Database Schema
-- Run this to create the withdrawals table

CREATE TABLE IF NOT EXISTS user_wallet_withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    
    -- Payment method: 'upi' or 'bank'
    payment_method ENUM('upi', 'bank') NOT NULL DEFAULT 'upi',
    
    -- UPI details
    upi_id VARCHAR(100),
    
    -- Bank details
    bank_name VARCHAR(100),
    branch_name VARCHAR(100),
    account_number VARCHAR(50),
    ifsc_code VARCHAR(20),
    account_holder_name VARCHAR(100),
    
    -- Status tracking
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    admin_notes TEXT,
    rejection_reason TEXT,
    
    -- Processing info
    processed_by INT,
    processed_on DATETIME,
    transaction_reference VARCHAR(100),
    
    -- Standard fields
    created_on DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_on DATETIME ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_on (created_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Note: Foreign key constraint removed to avoid data type issues
-- The user_id column references user_user.id

-- Run this if the table already exists (to add branch_name column):
ALTER TABLE user_wallet_withdrawals ADD COLUMN branch_name VARCHAR(100) AFTER bank_name;

