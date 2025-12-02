<?php
// get_user_id.php - Get user_id from qr_id
header('Content-Type: application/json');
require_once './dbconfig/connection.php';

$data = json_decode(file_get_contents('php://input'), true);
$qr_id = isset($data['qr_id']) ? $data['qr_id'] : '';

if ($qr_id === '') {
    echo json_encode(['status' => false, 'message' => 'QR ID is required']);
    exit;
}

// Get user_id from user_user table (must not be deleted)
$stmt = $conn->prepare('SELECT id FROM user_user WHERE user_qr_id = ? AND is_deleted = 0 LIMIT 1');
if (!$stmt) {
    echo json_encode(['status' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param('s', $qr_id);
$stmt->execute();
$stmt->bind_result($user_id);

if ($stmt->fetch() && $user_id) {
    echo json_encode(['status' => true, 'user_id' => $user_id]);
} else {
    echo json_encode(['status' => false, 'message' => 'User not found']);
}

$stmt->close();
$conn->close();
