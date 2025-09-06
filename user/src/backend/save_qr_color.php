<?php
// save_qr_color.php
header('Content-Type: application/json');
require_once './dbconfig/connection.php'; // adjust path as needed

$data = json_decode(file_get_contents('php://input'), true);
$user_id = isset($data['user_id']) ? $data['user_id'] : '';
$colorDark = isset($data['colorDark']) ? $data['colorDark'] : '';
$colorLight = isset($data['colorLight']) ? $data['colorLight'] : '';

if ($user_id === '' || $colorDark === '' || $colorLight === '') {
    echo json_encode(['status' => false, 'message' => 'Missing parameters.']);
    exit;
}

// Upsert logic
$stmt = $conn->prepare('
    INSERT INTO user_qr_colors (user_id, color_dark, color_light, created_on, updated_on, created_by, updated_by)
    VALUES (?, ?, ?, NOW(), NOW(), ?, ?)
    ON DUPLICATE KEY UPDATE 
        color_dark = VALUES(color_dark), 
        color_light = VALUES(color_light),
        updated_on = NOW(),
        updated_by = VALUES(updated_by)
');
$stmt->bind_param('ssssss', $user_id, $colorDark, $colorLight, $user_id, $user_id);
if ($stmt->execute()) {
    echo json_encode(['status' => true, 'message' => 'QR color saved.']);
} else {
    echo json_encode(['status' => false, 'message' => 'Database error.']);
}
$stmt->close();
