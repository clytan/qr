-- Create table to store pending registration data during payment process
-- This ensures registration data persists across payment gateway redirects

CREATE TABLE IF NOT EXISTS `user_pending_registration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(100) NOT NULL UNIQUE,
  `registration_data` TEXT NOT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add index for faster lookup
CREATE INDEX idx_order_status ON user_pending_registration(order_id, status);
