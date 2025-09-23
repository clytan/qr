<?php
header('Content-Type: application/json');
require_once('.././dbconfig/connection.php');

// Get JSON POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['qr_id']) || !isset($data['followers_id'])) {
    echo json_encode(['status' => 0, 'message' => 'Missing required parameters']);
    exit;
}

$qr_id = $data['qr_id'];
$follower_id = (int) $data['followers_id'];

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

// Prevent self-unfollowing
if ($user_id == $follower_id) {
    echo json_encode(['status' => 0, 'message' => 'Cannot unfollow yourself']);
    exit;
}

// Check if follow relationship exists - using followers_id column
$check_sql = "SELECT id FROM user_followers WHERE user_id = ? AND followers_id = ? AND is_deleted = 0";
$check_stmt = $conn->prepare($check_sql);
if (!$check_stmt) {
    echo json_encode(['status' => 0, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$check_stmt->bind_param('ii', $user_id, $follower_id);
$check_stmt->execute();
$check_stmt->bind_result($follow_id);
if (!$check_stmt->fetch()) {
    $check_stmt->close();
    echo json_encode(['status' => 0, 'message' => 'Follow relationship not found']);
    exit;
}
$check_stmt->close();

// Mark the follow relationship as deleted (unfollow)
$unfollow_sql = "UPDATE user_followers SET is_deleted = 1, updated_by = ?, updated_on = NOW() WHERE id = ?";
$unfollow_stmt = $conn->prepare($unfollow_sql);
if (!$unfollow_stmt) {
    echo json_encode(['status' => 0, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$unfollow_stmt->bind_param('ii', $follower_id, $follow_id);
if ($unfollow_stmt->execute()) {
    echo json_encode(['status' => 1, 'message' => 'Successfully unfollowed']);
} else {
    echo json_encode(['status' => 0, 'message' => 'Failed to unfollow']);
}
$unfollow_stmt->close();
?>