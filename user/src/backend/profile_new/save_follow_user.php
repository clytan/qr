<?php
// follow_user.php
header('Content-Type: application/json');
require_once('.././dbconfig/connection.php');
$data = json_decode(file_get_contents('php://input'), true);
$qr_id = isset($data['qr_id']) ? $data['qr_id'] : '';
$followers_id = isset($data['followers_id']) ? $data['followers_id'] : '';

if ($qr_id === '' || $followers_id === '') {
    echo json_encode(['status' => false, 'message' => 'Missing parameters.']);
    exit;
}

// Get user_id from qr_id using user_user table
$stmt = $conn->prepare('SELECT id FROM user_user WHERE user_qr_id = ? AND is_deleted = 0 LIMIT 1');
if (!$stmt) {
    echo json_encode(['status' => false, 'message' => 'DB error.']);
    exit;
}
$stmt->bind_param('s', $qr_id);
$stmt->execute();
$stmt->bind_result($user_id);
if ($stmt->fetch() && $user_id) {
    $stmt->close();

    // Prevent self-following
    if ($user_id == $followers_id) {
        echo json_encode(['status' => false, 'message' => 'Cannot follow yourself.']);
        exit;
    }

    // Insert into user_followers - using correct column name followers_id
    $stmt2 = $conn->prepare('INSERT INTO user_followers (user_id, followers_id, created_on, updated_on, created_by, updated_by) VALUES (?, ?, NOW(), NOW(), ?, ?)');
    if (!$stmt2) {
        echo json_encode(['status' => false, 'message' => 'DB error.']);
        exit;
    }
    $stmt2->bind_param('iiii', $user_id, $followers_id, $followers_id, $followers_id);
    if ($stmt2->execute()) {
        echo json_encode(['status' => true, 'message' => 'Followed successfully.']);
    } else {
        echo json_encode(['status' => false, 'message' => 'Insert failed.', 'error' => $stmt2->error]);
    }
    $stmt2->close();
} else {
    $stmt->close();
    echo json_encode(['status' => false, 'message' => 'User not found.']);
}
?>