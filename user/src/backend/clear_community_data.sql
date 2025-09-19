-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS=0;

-- Truncate tables to remove all data and reset auto-increment
TRUNCATE TABLE community_reactions;
TRUNCATE TABLE community_chat;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;