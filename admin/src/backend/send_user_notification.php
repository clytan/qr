<?php
session_start();
require_once './dbconfig/connection.php';

header('Content-Type: application/json');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/notification_errors.log');

// Ensure the user is an admin
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || !isset($data['message'])) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$user_id = $data['user_id'];
$message = $data['message'];
$admin_id = $_SESSION['admin_id'];
$current_time = date('Y-m-d H:i:s');

$query = "INSERT INTO user_notifications (user_id, message, created_by, created_on, is_read, is_deleted) 
          VALUES (?, ?, ?, ?, 0, 0)";

$stmt = $conn->prepare($query);
$stmt->bind_param("isss", $user_id, $message, $admin_id, $current_time);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Notification sent successfully']);
} else {
    echo json_encode(['error' => 'Failed to send notification']);
}
?>