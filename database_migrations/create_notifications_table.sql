-- Create notifications table
CREATE TABLE user_notifications (
    id BIGINT(20) NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) NOT NULL,
    message VARCHAR(500) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_by BIGINT(20) NOT NULL,
    created_on DATETIME(3) NOT NULL,
    is_deleted TINYINT(2) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES user_user(id),
    FOREIGN KEY (created_by) REFERENCES user_user(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;