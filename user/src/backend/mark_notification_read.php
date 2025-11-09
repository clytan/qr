<?php
session_start();
require_once './dbconfig/connection.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not logged in');
    }

    if (!isset($_POST['notification_id'])) {
        throw new Exception('Notification ID is required');
    }

    $user_id = $_SESSION['user_id'];
    $notification_id = $_POST['notification_id'];

    // Update notification to mark as read
    $query = "UPDATE user_notifications 
              SET is_read = 1 
              WHERE id = ? AND user_id = ?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement');
    }

    $stmt->bind_param('ii', $notification_id, $user_id);

    if (!$stmt->execute()) {
        throw new Exception('Failed to mark notification as read');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Notification marked as read'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}