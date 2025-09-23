<?php
require_once('../../backend/dbconfig/connection.php');
session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        throw new Exception('Not authenticated');
    }

    // Check if colors already exist
    $stmt = $conn->prepare("
        SELECT id FROM user_qr_colors 
        WHERE user_id = ? AND is_deleted = 0
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing colors
        $stmt = $conn->prepare("
            UPDATE user_qr_colors 
            SET 
                color_dark = ?,
                color_light = ?,
                updated_by = ?,
                updated_on = CURRENT_TIMESTAMP
            WHERE user_id = ? AND is_deleted = 0
        ");
        $stmt->bind_param(
            "ssii",
            $input['dark'],
            $input['light'],
            $userId,
            $userId
        );
    } else {
        // Insert new colors
        $stmt = $conn->prepare("
            INSERT INTO user_qr_colors 
            (user_id, color_dark, color_light, created_by) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "issi",
            $userId,
            $input['dark'],
            $input['light'],
            $userId
        );
    }

    $stmt->execute();
    $response['success'] = true;
    $response['message'] = 'QR colors saved successfully';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);