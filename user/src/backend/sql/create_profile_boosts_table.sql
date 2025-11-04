-- Create profile_boosts table to track user profile boosts
CREATE TABLE IF NOT EXISTS `profile_boosts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `start_date` DATETIME NOT NULL,
  `end_date` DATETIME NOT NULL,
  `boost_amount_paid` DECIMAL(10,2) NOT NULL DEFAULT 199.00,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_on` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_end_date` (`end_date`, `is_deleted`),
  KEY `idx_active_boosts` (`user_id`, `end_date`, `is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create wallet_transactions table if it doesn't exist (for tracking boost payments)
CREATE TABLE IF NOT EXISTS `wallet_transactions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `transaction_type` ENUM('credit', 'debit') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `balance_after` DECIMAL(10,2) NOT NULL,
  `reference_id` VARCHAR(100) NULL,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_on` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_on` (`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
