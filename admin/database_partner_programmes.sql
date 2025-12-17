-- Partner Programmes Table
CREATE TABLE IF NOT EXISTS `partner_programmes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `programme_header` varchar(255) NOT NULL COMMENT 'e.g., Sell Life Insurance',
  `company_name` varchar(255) DEFAULT NULL,
  `product_link` varchar(500) DEFAULT NULL COMMENT 'Link to product/company',
  `description` text NOT NULL COMMENT 'Description of partner programme',
  `commission_details` text NOT NULL COMMENT 'Financial/commission structure',
  `company_email` varchar(255) NOT NULL COMMENT 'Email to receive referrals',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL COMMENT 'Admin user who created',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `is_deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_is_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Partner Programme Referrals/Leads Table
CREATE TABLE IF NOT EXISTS `partner_referrals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `programme_id` int(11) NOT NULL,
  `referred_by` int(11) NOT NULL COMMENT 'User ID who made the referral',
  `client_name` varchar(255) NOT NULL,
  `client_phone` varchar(20) NOT NULL,
  `client_email` varchar(255) NOT NULL,
  `product_name` varchar(255) NOT NULL COMMENT 'Which product client requires',
  `status` enum('open','in_process','closed') DEFAULT 'open',
  `notes` text DEFAULT NULL COMMENT 'Admin notes about the lead',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_on` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `is_deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_programme` (`programme_id`),
  KEY `idx_referred_by` (`referred_by`),
  KEY `idx_status` (`status`),
  KEY `idx_is_deleted` (`is_deleted`),
  FOREIGN KEY (`programme_id`) REFERENCES `partner_programmes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for better performance
ALTER TABLE `partner_programmes` 
  ADD INDEX `idx_created_on` (`created_on`);
  
ALTER TABLE `partner_referrals` 
  ADD INDEX `idx_created_on` (`created_on`);
