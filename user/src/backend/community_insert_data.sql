-- Insert into community table
INSERT INTO community (id, name, created_by, created_on, updated_by, updated_on, is_deleted) VALUES 
(1, 'General Discussion', 1, NOW(), 1, NOW(), 0),
(2, 'Tech Talk', 1, NOW(), 1, NOW(), 0),
(3, 'Creative Corner', 1, NOW(), 1, NOW(), 0),
(4, 'Help & Support', 1, NOW(), 1, NOW(), 0),
(5, 'Random Chat', 1, NOW(), 1, NOW(), 0),
(6, 'News & Updates', 1, NOW(), 1, NOW(), 0);

-- Insert into community_members
INSERT INTO community_members (id, community_id, user_id, created_by, created_on, updated_by, updated_on, is_deleted) VALUES 
(1, 1, 1, 1, NOW(), 1, NOW(), 0),
(2, 1, 2, 1, NOW(), 1, NOW(), 0),
(3, 2, 1, 1, NOW(), 1, NOW(), 0),
(4, 2, 2, 1, NOW(), 1, NOW(), 0),
(5, 3, 1, 1, NOW(), 1, NOW(), 0),
(6, 3, 2, 1, NOW(), 1, NOW(), 0),
(7, 4, 1, 1, NOW(), 1, NOW(), 0),
(8, 5, 2, 1, NOW(), 1, NOW(), 0),
(9, 6, 1, 1, NOW(), 1, NOW(), 0);

-- Insert into community_chat
INSERT INTO community_chat (id, community_id, user_id, message, attachment_path, attachment_type, attachment_name, likes_count, dislikes_count, created_by, created_on, updated_by, updated_on, is_deleted) VALUES 
(1, 1, 1, 'Welcome to the General Discussion community!', NULL, NULL, NULL, 0, 0, 1, NOW(), 1, NOW(), 0),
(2, 1, 2, 'Hey everyone, excited to be here!', NULL, NULL, NULL, 0, 0, 2, NOW(), 2, NOW(), 0),
(3, 2, 1, 'Any developers working with React here?', NULL, NULL, NULL, 0, 0, 1, NOW(), 1, NOW(), 0),
(4, 2, 2, 'I mainly work with Vue.js but interested in React too', NULL, NULL, NULL, 0, 0, 2, NOW(), 2, NOW(), 0),
(5, 3, 1, 'Check out this amazing design I made!', NULL, NULL, NULL, 0, 0, 1, NOW(), 1, NOW(), 0),
(6, 3, 2, 'That looks fantastic! How did you make it?', NULL, NULL, NULL, 0, 0, 2, NOW(), 2, NOW(), 0);

-- Insert into community_reactions
INSERT INTO community_reactions (id, message_id, user_id, reaction_type, created_by, created_on, updated_by, updated_on, is_deleted) VALUES 
(1, 1, 2, 'like', 2, NOW(), 2, NOW(), 0),
(2, 1, 3, 'like', 3, NOW(), 3, NOW(), 0),
(3, 2, 1, 'like', 1, NOW(), 1, NOW(), 0),
(4, 3, 2, 'like', 2, NOW(), 2, NOW(), 0),
(5, 4, 1, 'like', 1, NOW(), 1, NOW(), 0),
(6, 5, 2, 'dislike', 2, NOW(), 2, NOW(), 0);