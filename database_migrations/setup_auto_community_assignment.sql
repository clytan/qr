-- ============================================
-- Auto-Community Assignment Setup
-- Creates communities automatically every 100 users
-- ============================================

-- Step 1: Add community_id to user_user table (if not exists)
ALTER TABLE user_user 
ADD COLUMN community_id INT NULL AFTER user_slab_id,
ADD KEY idx_community_id (community_id),
ADD CONSTRAINT fk_user_community FOREIGN KEY (community_id) 
  REFERENCES community(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- Step 2: Create the first community if it doesn't exist
INSERT INTO community (name, current_members, is_full, created_by, created_on, updated_by, updated_on, is_deleted)
SELECT 'Community 1', 0, 0, 1, NOW(3), 1, NOW(3), 0
WHERE NOT EXISTS (SELECT 1 FROM community WHERE name = 'Community 1');

-- Optional: Create first few communities in advance
INSERT INTO community (name, current_members, is_full, created_by, created_on, updated_by, updated_on, is_deleted)
VALUES 
  ('Community 2', 0, 0, 1, NOW(3), 1, NOW(3), 0),
  ('Community 3', 0, 0, 1, NOW(3), 1, NOW(3), 0),
  ('Community 4', 0, 0, 1, NOW(3), 1, NOW(3), 0),
  ('Community 5', 0, 0, 1, NOW(3), 1, NOW(3), 0)
ON DUPLICATE KEY UPDATE name=name; -- Ignore if already exists
