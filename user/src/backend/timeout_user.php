<?php
// Prevent any output before JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

// Check session first
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Not logged in']);
    exit();
}

// Include required files
try {
    require_once('./dbconfig/connection.php');
    require_once('moderator_utils.php');
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => 'Server configuration error']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['user_id']) || !isset($data['message_id']) || !isset($data['duration'])) {
    echo json_encode(['status' => false, 'message' => 'Invalid request data']);
    exit();
}

$target_user_id = $data['user_id'];
$message_id = $data['message_id'];
$duration = intval($data['duration']);

// Get community ID from message
$sql = "SELECT community_id FROM community_chat WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $message_id);
$stmt->execute();
$result = $stmt->get_result();
$message = $result->fetch_assoc();
$stmt->close();

if (!$message) {
    echo json_encode(['status' => false, 'message' => 'Message not found']);
    exit();
}

$community_id = $message['community_id'];

// Check if user is moderator
if (!isUserModerator($user_id, $community_id)) {
    echo json_encode(['status' => false, 'message' => 'Not authorized']);
    exit();
}

// Calculate end time
$end_time = date('Y-m-d H:i:s', strtotime("+{$duration} minutes"));

// Add timeout penalty
$sql = "INSERT INTO user_penalties (user_id, community_id, penalty_type, reason, applied_by, start_time, end_time) 
        VALUES (?, ?, 'timeout', 'User timeout by moderator', ?, NOW(), ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iiis', $target_user_id, $community_id, $user_id, $end_time);

if ($stmt->execute()) {
    echo json_encode(['status' => true, 'message' => 'User has been timed out']);
} else {
    echo json_encode(['status' => false, 'message' => 'Failed to timeout user']);
}

$stmt->close();
$conn->close();
?>