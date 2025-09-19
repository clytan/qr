/*
INSTRUCTIONS:
1. Replace {USER_ID} with an actual user ID from your user_user table
2. Replace {COMMUNITY_ID} with an actual community ID from your community table
3. Replace {MESSAGE_ID} with an actual message ID from your community_chat table
*/

-- Add moderator role type (only run this if the role doesn't exist)
INSERT INTO admin_user_role (role_name) 
VALUES ('moderator');

-- Make Debanjan (ID: 1) a moderator for community 1
INSERT INTO user_roles (user_id, community_id, role_type) 
VALUES (1, 1, 'moderator');

-- Add a message report (User 2 being reported by User 1)
INSERT INTO message_reports (message_id, reported_by, reason, status, reviewed_by, reviewed_on, action_taken) 
VALUES 
(1, 1, 'Inappropriate content', 'pending', NULL, NULL, NULL);

-- Add a penalty for User 2 (issued by moderator User 1)
INSERT INTO user_penalties (user_id, community_id, penalty_type, reason, start_time, end_time, applied_by)
VALUES 
(2, 1, 'timeout', 'Repeated spamming', NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE), 1);

-- Note: Replace the IDs (1,2,3,4) with actual user IDs from your user_user table
-- Replace community_id (1) with actual community IDs from your community table