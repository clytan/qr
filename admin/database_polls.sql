-- ============================================
-- Poll System Database Schema
-- ============================================
-- Run this SQL to create the poll tables
-- ============================================

-- Main polls table
CREATE TABLE IF NOT EXISTS `user_polls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'Creator user ID',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `poll_type` enum('single','multiple') DEFAULT 'single' COMMENT 'single=one choice, multiple=multi-select',
  `status` enum('active','closed') DEFAULT 'active',
  `is_admin_poll` tinyint(1) DEFAULT 0 COMMENT 'Created by admin',
  `ends_at` datetime DEFAULT NULL COMMENT 'Optional end date',
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_on` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_is_deleted` (`is_deleted`),
  KEY `idx_created_on` (`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Poll options/choices
CREATE TABLE IF NOT EXISTS `user_poll_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `poll_id` int(11) NOT NULL,
  `option_text` varchar(255) NOT NULL,
  `option_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_poll_id` (`poll_id`),
  CONSTRAINT `fk_poll_options_poll` FOREIGN KEY (`poll_id`) REFERENCES `user_polls`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User votes
CREATE TABLE IF NOT EXISTS `user_poll_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `poll_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_single_vote` (`poll_id`,`user_id`) COMMENT 'One vote per poll per user for single-choice polls',
  KEY `idx_poll_id` (`poll_id`),
  KEY `idx_option_id` (`option_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_votes_poll` FOREIGN KEY (`poll_id`) REFERENCES `user_polls`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_votes_option` FOREIGN KEY (`option_id`) REFERENCES `user_poll_options`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Sample Data (Optional - for testing)
-- ============================================
-- INSERT INTO `user_polls` (`user_id`, `title`, `description`, `status`) VALUES
-- (1, 'What is your favorite programming language?', 'Vote for your favorite!', 'active');

-- INSERT INTO `user_poll_options` (`poll_id`, `option_text`, `option_order`) VALUES
-- (1, 'JavaScript', 1),
-- (1, 'Python', 2),
-- (1, 'PHP', 3),
-- (1, 'Java', 4);
