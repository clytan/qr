<?php
require_once __DIR__ . '/../../backend/dbconfig/connection.php';
// Add option_image column if it doesn't exist
$sql = "ALTER TABLE user_poll_options ADD COLUMN option_image VARCHAR(255) DEFAULT NULL";
if ($conn->query($sql) === TRUE) {
    echo "Table updated successfully";
} else {
    // Check if duplicate column error (1060)
    if ($conn->errno == 1060) {
        echo "Column already exists";
    } else {
        echo "Error updating table: " . $conn->error;
    }
}
?>
