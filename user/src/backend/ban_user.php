<?php
// Prevent any output before JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Not logged in']);
    exit();
}

try {
    require_once('./dbconfig/connection.php');
    require_once('moderator_utils.php');
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => 'Server configuration error']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['user_id']) || !isset($data['message_id'])) {
    echo json_encode(['status' => false, 'message' => 'Invalid request data']);
    exit();
}

$target_user_id = $data['user_id'];
$message_id = $data['message_id'];

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

try {
    // First check if user is already banned
    $checkSql = "SELECT 1 FROM user_penalties 
                 WHERE user_id = ? AND community_id = ? 
                 AND penalty_type = 'ban' 
                 AND is_deleted = 0";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param('ii', $target_user_id, $community_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        echo json_encode(['status' => false, 'message' => 'User is already banned']);
        $checkStmt->close();
        $conn->close();
        exit();
    }
    $checkStmt->close();

    // Add ban penalty (end_time is NULL for permanent ban)
    $sql = "INSERT INTO user_penalties (user_id, community_id, penalty_type, reason, applied_by, start_time, end_time) 
            VALUES (?, ?, 'ban', 'User banned by moderator', ?, NOW(), NULL)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }

    $stmt->bind_param('iii', $target_user_id, $community_id, $user_id);

    if (!$stmt->execute()) {
        throw new Exception('Failed to execute statement: ' . $stmt->error);
    }

    $stmt->close();
    $conn->close();

    echo json_encode(['status' => true, 'message' => 'User has been banned']);

} catch (Exception $e) {
    error_log('Ban user error: ' . $e->getMessage());
    echo json_encode([
        'status' => false,
        'message' => 'Failed to ban user'
    ]);
}
?>