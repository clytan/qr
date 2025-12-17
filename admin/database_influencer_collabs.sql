-- Influencer Collaborations Table
CREATE TABLE IF NOT EXISTS `influencer_collabs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collab_title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL COMMENT 'lifestyle, skincare, haircare, fashion, fitness, food, tech, other',
  `product_description` text NOT NULL,
  `product_link` varchar(500) DEFAULT NULL,
  `photo_1` varchar(500) DEFAULT NULL,
  `photo_2` varchar(500) DEFAULT NULL,
  `photo_3` varchar(500) DEFAULT NULL,
  `financial_type` enum('barter','paid') DEFAULT 'barter',
  `financial_amount` decimal(10,2) DEFAULT 0.00,
  `detailed_summary` text NOT NULL,
  `brand_email` varchar(255) NOT NULL,
  `status` enum('pending','active','completed','cancelled') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL COMMENT 'Admin user who created this',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `accepted_by` int(11) DEFAULT NULL COMMENT 'Influencer user ID who accepted',
  `accepted_on` datetime DEFAULT NULL,
  `completed_on` datetime DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_accepted_by` (`accepted_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance
ALTER TABLE `influencer_collabs` 
  ADD INDEX `idx_is_deleted` (`is_deleted`),
  ADD INDEX `idx_created_on` (`created_on`);
