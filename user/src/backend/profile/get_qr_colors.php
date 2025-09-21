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

    $stmt = $conn->prepare("
        SELECT color_dark, color_light 
        FROM user_qr_colors 
        WHERE user_id = ? AND is_deleted = 0
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $response['data'] = [
            'dark' => $row['color_dark'],
            'light' => $row['color_light']
        ];
    } else {
        $response['data'] = [
            'dark' => '#000000',
            'light' => '#FFFFFF'
        ];
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);