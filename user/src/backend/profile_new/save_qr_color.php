<?php
// save_qr_color.php - Simple approach
session_start();
header('Content-Type: application/json');
require_once('.././dbconfig/connection.php');

// Get user_id from session
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'User not logged in.']);
    exit;
}

$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$colorDark = isset($data['colorDark']) ? $data['colorDark'] : '';
$colorLight = isset($data['colorLight']) ? $data['colorLight'] : '';

if ($colorDark === '' || $colorLight === '') {
    echo json_encode(['status' => false, 'message' => 'Missing color parameters.']);
    exit;
}

// Try to UPDATE first
$updateStmt = $conn->prepare('
    UPDATE user_qr_colors 
    SET color_dark = ?, color_light = ?, updated_on = NOW(), updated_by = ?
    WHERE user_id = ?
');
$updateStmt->bind_param('ssss', $colorDark, $colorLight, $user_id, $user_id);
$updateStmt->execute();

// Check if any row was updated
if ($updateStmt->affected_rows > 0) {
    echo json_encode(['status' => true, 'message' => 'QR colors updated successfully.']);
} else {
    // No rows updated means user doesn't exist, so INSERT
    $insertStmt = $conn->prepare('
        INSERT INTO user_qr_colors (user_id, color_dark, color_light, created_on, updated_on, created_by, updated_by)
        VALUES (?, ?, ?, NOW(), NOW(), ?, ?)
    ');
    $insertStmt->bind_param('sssss', $user_id, $colorDark, $colorLight, $user_id, $user_id);

    if ($insertStmt->execute()) {
        echo json_encode(['status' => true, 'message' => 'QR colors saved successfully.']);
    } else {
        echo json_encode(['status' => false, 'message' => 'Failed to save colors.']);
    }
    $insertStmt->close();
}

$updateStmt->close();
?>