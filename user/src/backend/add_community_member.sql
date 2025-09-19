-- Add your user to Community 2 (replace user_id with your actual user ID)
INSERT INTO community_members (community_id, user_id, created_by, created_on, updated_by, updated_on, is_deleted) 
VALUES (2, /* YOUR_USER_ID */, /* YOUR_USER_ID */, NOW(), /* YOUR_USER_ID */, NOW(), 0);