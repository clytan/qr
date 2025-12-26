<?php
// Use absolute path to ensure connectivity
include __DIR__ . '/../../../user/src/backend/dbconfig/connection.php';

try {
    // Check if columns exist
    $result = $conn->query("SHOW COLUMNS FROM user_notifications LIKE 'subject'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE user_notifications ADD COLUMN subject VARCHAR(255) AFTER user_id");
        echo "Added 'subject' column.\n";
    } else {
        echo "'subject' column already exists.\n";
    }

    $result = $conn->query("SHOW COLUMNS FROM user_notifications LIKE 'link'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE user_notifications ADD COLUMN link TEXT AFTER message");
        echo "Added 'link' column.\n";
    } else {
        echo "'link' column already exists.\n";
    }

    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
