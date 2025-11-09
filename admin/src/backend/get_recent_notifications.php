<?php
session_start();
require_once './dbconfig/connection.php';

header('Content-Type: application/json');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/notification_errors.log');

try {
    // Check if admin is logged in
    if (!isset($_SESSION['admin_id'])) {
        throw new Exception('Unauthorized access');
    }

    // Get recent notifications with user details
    $query = "SELECT n.*, u.user_full_name 
              FROM user_notifications n 
              JOIN user_user u ON n.user_id = u.id 
              WHERE n.is_deleted = 0 
              ORDER BY n.created_on DESC 
              LIMIT 50";

    $result = $conn->query($query);

    if (!$result) {
        throw new Exception('Failed to fetch notifications: ' . $conn->error);
    }

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => $row['id'],
            'user_full_name' => $row['user_full_name'],
            'message' => $row['message'],
            'is_read' => $row['is_read'],
            'created_on' => $row['created_on']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $notifications
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>