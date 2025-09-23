<?php
header('Content-Type: application/json');
require_once('.././dbconfig/connection.php');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['qr_id']) || !isset($data['type'])) {
    echo json_encode(['status' => 0, 'message' => 'Missing required parameters']);
    exit;
}

$qr_id = $data['qr_id'];
$type = $data['type']; // 'followers' or 'following'

// Get the user_id from user_user table
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

// Get followers or following based on type
if ($type === 'followers') {
    // Get people who follow this user
    $sql = "SELECT u.id, u.user_full_name, u.user_qr_id, u.user_image_path 
            FROM user_followers uf 
            JOIN user_user u ON uf.followers_id = u.id 
            WHERE uf.user_id = ? AND uf.is_deleted = 0 AND u.is_deleted = 0
            ORDER BY uf.created_on DESC";
} else {
    // Get people this user follows
    $sql = "SELECT u.id, u.user_full_name, u.user_qr_id, u.user_image_path 
            FROM user_followers uf 
            JOIN user_user u ON uf.user_id = u.id 
            WHERE uf.followers_id = ? AND uf.is_deleted = 0 AND u.is_deleted = 0
            ORDER BY uf.created_on DESC";
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 0, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();

echo json_encode(['status' => 1, 'data' => $users]);
?>