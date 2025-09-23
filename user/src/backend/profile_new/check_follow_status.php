<?php
header('Content-Type: application/json');
require_once('.././dbconfig/connection.php');

// Get JSON POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['qr_id']) || !isset($data['follower_id'])) {
    echo json_encode(['status' => 0, 'message' => 'Missing required parameters']);
    exit;
}

$qr_id = $data['qr_id'];
$follower_id = (int) $data['follower_id'];

// Get the user_id from user_user table using user_qr_id column
global $conn;
$user_sql = "SELECT id FROM user_user WHERE user_qr_id = ? AND is_deleted = 0";
$user_stmt = $conn->prepare($user_sql);
if (!$user_stmt) {
    echo json_encode(['status' => 0, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$user_stmt->bind_param('s', $qr_id);
$user_stmt->execute();
$user_stmt->bind_result($user_id);
if (!$user_stmt->fetch()) {
    $user_stmt->close();
    echo json_encode(['status' => 0, 'message' => 'User not found']);
    exit;
}
$user_stmt->close();

// Check if follow relationship exists - using followers_id column
$check_sql = "SELECT COUNT(*) as count FROM user_followers WHERE user_id = ? AND followers_id = ? AND is_deleted = 0";
$check_stmt = $conn->prepare($check_sql);
if (!$check_stmt) {
    echo json_encode(['status' => 0, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$check_stmt->bind_param('ii', $user_id, $follower_id);
$check_stmt->execute();
$check_stmt->bind_result($count);
$check_stmt->fetch();
$check_stmt->close();

echo json_encode([
    'status' => 1,
    'is_following' => $count > 0
]);
?>