<?php
header('Content-Type: application/json');
require_once('.././dbconfig/connection.php');

// Get JSON POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['qr_id'])) {
    echo json_encode(['status' => 0, 'message' => 'Missing required parameters']);
    exit;
}

$qr_id = $data['qr_id'];
$follower_id = isset($data['follower_id']) ? (int) $data['follower_id'] : null;

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

// Get followers count (people following this user)
$followers_sql = "SELECT COUNT(*) as count FROM user_followers WHERE user_id = ? AND is_deleted = 0";
$followers_stmt = $conn->prepare($followers_sql);
if (!$followers_stmt) {
    echo json_encode(['status' => 0, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$followers_stmt->bind_param('i', $user_id);
$followers_stmt->execute();
$followers_stmt->bind_result($followers_count);
$followers_stmt->fetch();
$followers_stmt->close();

// Get following count (people this user is following) - using followers_id column
$following_sql = "SELECT COUNT(*) as count FROM user_followers WHERE followers_id = ? AND is_deleted = 0";
$following_stmt = $conn->prepare($following_sql);
if (!$following_stmt) {
    echo json_encode(['status' => 0, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$following_stmt->bind_param('i', $user_id);
$following_stmt->execute();
$following_stmt->bind_result($following_count);
$following_stmt->fetch();
$following_stmt->close();

$response = [
    'status' => 1,
    'total_count' => $followers_count,
    'following_count' => $following_count
];

// Check if the current user is following this profile - using followers_id column
if ($follower_id && $follower_id != $user_id) {
    $check_sql = "SELECT COUNT(*) as count FROM user_followers WHERE user_id = ? AND followers_id = ? AND is_deleted = 0";
    $check_stmt = $conn->prepare($check_sql);
    if ($check_stmt) {
        $check_stmt->bind_param('ii', $user_id, $follower_id);
        $check_stmt->execute();
        $check_stmt->bind_result($is_following);
        $check_stmt->fetch();
        $check_stmt->close();

        $response['following'] = $is_following > 0;
    }
}

echo json_encode($response);
?>