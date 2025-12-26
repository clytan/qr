<?php
// Enable error logging to file
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/notification_errors.log');

session_start();

// Set JSON header first before any possible error occurs
header('Content-Type: application/json');

// Log the current directory and included file path
error_log("Current Directory: " . __DIR__);
error_log("Attempting to include: ./dbconfig/connection.php");

try {
    // Include database configuration
    require_once './dbconfig/connection.php';

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not logged in');
    }

    $user_id = $_SESSION['user_id'];

    // Verify database connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    $query = "SELECT n.*, u.user_full_name as sender_name 
              FROM user_notifications n 
              LEFT JOIN user_user u ON n.created_by = u.id 
              WHERE n.user_id = ? AND n.is_deleted = 0 
              ORDER BY n.created_on DESC 
              LIMIT 5";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception('Query preparation failed: ' . $conn->error);
    }

    $stmt->bind_param("i", $user_id);

    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => $row['id'],
            'subject' => $row['subject'] ?? '',
            'message' => $row['message'],
            'link' => $row['link'] ?? '',
            'is_read' => $row['is_read'],
            'created_on' => $row['created_on'],
            'sender_name' => $row['sender_name']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $notifications
    ]);

} catch (Exception $e) {
    // Return a proper JSON error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>