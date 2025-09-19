-- Create communities table
CREATE TABLE IF NOT EXISTS communities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    created_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    max_members INT DEFAULT 10
);

-- Create community_members table
CREATE TABLE IF NOT EXISTS community_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    community_id INT,
    user_id INT,
    created_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create community_messages table
CREATE TABLE IF NOT EXISTS community_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    community_id INT,
    user_id INT,
    message TEXT,
    attachment_path VARCHAR(255),
    attachment_name VARCHAR(255),
    created_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create message_reactions table
CREATE TABLE IF NOT EXISTS message_reactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    message_id INT,
    user_id INT,
    reaction_type ENUM('like', 'dislike'),
    created_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES community_messages(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert dummy communities
INSERT INTO communities (name) VALUES 
('General Discussion'),
('Tech Talk'),
('Creative Corner'),
('Help & Support'),
('Random Chat'),
('News & Updates');

-- Insert dummy members (adjust user_id values based on your existing users)
INSERT INTO community_members (community_id, user_id) VALUES 
(1, 1), (1, 2), (1, 3),
(2, 1), (2, 3), (2, 4),
(3, 2), (3, 3), (3, 4),
(4, 1), (4, 4), (4, 5),
(5, 2), (5, 3), (5, 5),
(6, 1), (6, 2), (6, 5);

-- Insert dummy messages
INSERT INTO community_messages (community_id, user_id, message) VALUES 
(1, 1, 'Welcome to the General Discussion community!'),
(1, 2, 'Hey everyone, excited to be here!'),
(1, 3, 'This is a great place to chat!'),
(2, 1, 'Any developers working with React here?'),
(2, 3, 'I mainly work with Vue.js but interested in React too'),
(3, 2, 'Check out this amazing design I made!'),
(3, 4, 'That looks fantastic! How did you make it?'),
(4, 1, 'If anyone needs help, feel free to ask'),
(4, 5, 'Thanks! I might need some assistance later'),
(5, 2, 'Did you see the latest tech news?'),
(5, 3, 'Yes! The new developments are amazing'),
(6, 1, 'Important update: New features coming soon!'),
(6, 2, 'Can\'t wait to try them out!');

-- Insert some dummy reactions
INSERT INTO message_reactions (message_id, user_id, reaction_type) VALUES 
(1, 2, 'like'),
(1, 3, 'like'),
(2, 1, 'like'),
(2, 3, 'like'),
(3, 1, 'like'),
(3, 2, 'like'),
(4, 2, 'like'),
(4, 3, 'dislike'),
(5, 1, 'like'),
(5, 4, 'like'),
(6, 3, 'like'),
(6, 4, 'like');