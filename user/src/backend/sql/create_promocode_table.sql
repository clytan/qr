-- Promocode table for discount management
CREATE TABLE IF NOT EXISTS promo_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    discount_type ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
    discount_value DECIMAL(10, 2) NOT NULL,
    max_uses INT NOT NULL DEFAULT 100,
    current_uses INT NOT NULL DEFAULT 0,
    valid_from DATETIME DEFAULT CURRENT_TIMESTAMP,
    valid_until DATETIME,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    min_amount DECIMAL(10, 2) DEFAULT 0,
    max_discount DECIMAL(10, 2) DEFAULT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_active (is_active),
    INDEX idx_valid_dates (valid_from, valid_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Track which users have used which promocodes
CREATE TABLE IF NOT EXISTS promo_code_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    promo_code_id INT NOT NULL,
    user_id INT,
    email VARCHAR(255),
    order_id VARCHAR(100),
    discount_amount DECIMAL(10, 2) NOT NULL,
    original_amount DECIMAL(10, 2) NOT NULL,
    final_amount DECIMAL(10, 2) NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (promo_code_id) REFERENCES promo_codes(id),
    INDEX idx_user_id (user_id),
    INDEX idx_email (email),
    INDEX idx_order_id (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add promocode columns to user_pending_registration
ALTER TABLE user_pending_registration 
ADD COLUMN promo_code VARCHAR(50) DEFAULT NULL,
ADD COLUMN discount_amount DECIMAL(10, 2) DEFAULT 0,
ADD COLUMN original_amount DECIMAL(10, 2) DEFAULT NULL;

-- Add promocode columns to user_invoice
ALTER TABLE user_invoice 
ADD COLUMN promo_code VARCHAR(50) DEFAULT NULL,
ADD COLUMN discount_amount DECIMAL(10, 2) DEFAULT 0,
ADD COLUMN original_amount DECIMAL(10, 2) DEFAULT NULL;

-- Sample promocodes
INSERT INTO promo_codes (code, discount_type, discount_value, max_uses, description, valid_until, min_amount, max_discount) VALUES
('WELCOME10', 'percentage', 10.00, 1000, 'Welcome discount - 10% off', DATE_ADD(NOW(), INTERVAL 1 YEAR), 0, 100),
('SAVE50', 'fixed', 50.00, 500, 'Flat ₹50 off', DATE_ADD(NOW(), INTERVAL 6 MONTH), 200, NULL),
('EARLYBIRD', 'percentage', 15.00, 200, 'Early bird special - 15% off', DATE_ADD(NOW(), INTERVAL 3 MONTH), 500, 200),
('GOLD20', 'percentage', 20.00, 100, 'Gold membership special - 20% off', DATE_ADD(NOW(), INTERVAL 1 YEAR), 0, NULL);
