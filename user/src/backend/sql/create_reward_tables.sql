-- ========================================
-- Reward System Tables
-- Created: 2025-12-08
-- ========================================

-- Reward Configuration Table (for admin settings)
CREATE TABLE IF NOT EXISTS `reward_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(50) NOT NULL,
  `config_value` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_on` datetime(3) NOT NULL,
  `updated_on` datetime(3) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key_UNIQUE` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default configuration values
INSERT INTO `reward_config` (`config_key`, `config_value`, `description`, `created_on`, `updated_on`) VALUES
('spin_start_time', '00:00:00', 'Time when spinner starts (HH:MM:SS)', NOW(3), NOW(3)),
('spin_end_time', '21:00:00', 'Time when spinner stops and winners are revealed (HH:MM:SS)', NOW(3), NOW(3)),
('max_winners_per_draw', '30', 'Maximum winners per community per draw', NOW(3), NOW(3)),
('spin_duration_seconds', '10', 'Animation duration for spinner in seconds', NOW(3), NOW(3))
ON DUPLICATE KEY UPDATE `updated_on` = NOW(3);

-- Reward Draws Table (one record per community per day)
CREATE TABLE IF NOT EXISTS `reward_draws` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL,
  `draw_date` date NOT NULL,
  `total_participants` int(11) DEFAULT 0,
  `total_winners` int(11) DEFAULT 0,
  `is_completed` tinyint(1) DEFAULT 0 COMMENT '1 = draw completed, winners selected',
  `created_on` datetime(3) NOT NULL,
  `updated_on` datetime(3) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_draw_per_day` (`community_id`, `draw_date`),
  KEY `idx_draw_date` (`draw_date`),
  KEY `idx_community_id` (`community_id`),
  CONSTRAINT `fk_draw_community` FOREIGN KEY (`community_id`) REFERENCES `community` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reward Winners Table (stores each winner)
CREATE TABLE IF NOT EXISTS `reward_winners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `draw_id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `community_id` int(11) NOT NULL,
  `position` int(11) NOT NULL COMMENT 'Winner position 1-30',
  `won_at` datetime(3) NOT NULL,
  `created_on` datetime(3) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_winner_per_draw` (`draw_id`, `user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_draw_id` (`draw_id`),
  KEY `idx_won_at` (`won_at`),
  CONSTRAINT `fk_winner_draw` FOREIGN KEY (`draw_id`) REFERENCES `reward_draws` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_winner_user` FOREIGN KEY (`user_id`) REFERENCES `user_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_winner_community` FOREIGN KEY (`community_id`) REFERENCES `community` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index for win counting (for fair probability calculation)
CREATE INDEX IF NOT EXISTS `idx_user_wins` ON `reward_winners` (`user_id`, `won_at`);
