CREATE TABLE user_wallet_transaction (
    id BIGINT(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT(20) NOT NULL, -- whose wallet is affected
    amount DECIMAL(18,2) NOT NULL,
    transaction_type VARCHAR(50) NOT NULL, -- e.g., 'Referral', 'Purchase', etc.
    description VARCHAR(255),
    created_by BIGINT(20),
    created_on DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_by BIGINT(20),
    updated_on DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES user_user(id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
