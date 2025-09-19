-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS=0;

-- Delete all data from tables
DELETE FROM community_reactions;
DELETE FROM community_chat;

-- Reset auto-increment values
ALTER TABLE community_reactions AUTO_INCREMENT = 1;
ALTER TABLE community_chat AUTO_INCREMENT = 1;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;