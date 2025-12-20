-- Table to store subscription renewals
CREATE TABLE IF NOT EXISTS user_renewal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    tier VARCHAR(50) NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    payment_reference VARCHAR(255) DEFAULT NULL,
    old_expiry_date DATETIME NOT NULL,
    new_expiry_date DATETIME NOT NULL,
    created_on DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_on DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    INDEX idx_user_id (user_id)
);
