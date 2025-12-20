<?php
require_once __DIR__ . '/../../backend/dbconfig/connection.php';
// Add 'pending_payment' to the status ENUM
$sql = "ALTER TABLE user_polls MODIFY COLUMN status ENUM('active', 'closed', 'pending_payment') DEFAULT 'pending_payment'";
if ($conn->query($sql) === TRUE) {
    echo "Table updated successfully";
} else {
    echo "Error updating table: " . $conn->error;
}
?>
