-- User Roles Table
CREATE TABLE user_roles (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT(20) NOT NULL,
    community_id INT(11) NOT NULL,
    role_type ENUM('member', 'moderator', 'admin') NOT NULL DEFAULT 'member',
    created_on DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
    created_by BIGINT(20),
    is_deleted TINYINT(2) DEFAULT 0,
    UNIQUE KEY unique_user_community_role (user_id, community_id),
    CONSTRAINT fk_roles_user FOREIGN KEY (user_id) REFERENCES user_user(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_roles_community FOREIGN KEY (community_id) REFERENCES community(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_roles_creator FOREIGN KEY (created_by) REFERENCES user_user(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- User Penalties Table
CREATE TABLE user_penalties (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT(20) NOT NULL,
    community_id INT(11) NOT NULL,
    penalty_type ENUM('timeout', 'ban') NOT NULL,
    reason TEXT,
    start_time DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
    end_time DATETIME(3),
    applied_by BIGINT(20) NOT NULL,
    is_active TINYINT(2) DEFAULT 1,
    created_on DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
    is_deleted TINYINT(2) DEFAULT 0,
    CONSTRAINT fk_penalties_user FOREIGN KEY (user_id) REFERENCES user_user(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_penalties_community FOREIGN KEY (community_id) REFERENCES community(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_penalties_moderator FOREIGN KEY (applied_by) REFERENCES user_user(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Message Reports Table
CREATE TABLE message_reports (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    message_id INT(11) NOT NULL,
    reported_by BIGINT(20) NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'reviewed', 'actioned', 'dismissed') DEFAULT 'pending',
    reviewed_by BIGINT(20),
    action_taken TEXT,
    created_on DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
    reviewed_on DATETIME(3),
    is_deleted TINYINT(2) DEFAULT 0,
    CONSTRAINT fk_reports_message FOREIGN KEY (message_id) REFERENCES community_chat(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_reports_reporter FOREIGN KEY (reported_by) REFERENCES user_user(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_reports_reviewer FOREIGN KEY (reviewed_by) REFERENCES user_user(id) ON DELETE RESTRICT ON UPDATE CASCADE
);