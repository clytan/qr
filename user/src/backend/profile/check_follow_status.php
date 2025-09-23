<?php
require_once('../dbconfig/connection.php');
session_start();

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not authenticated');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $_SESSION['user_id'];
    $targetUserId = $input['target_user_id'];

    // Check if following
    $stmt = $conn->prepare("
        SELECT id 
        FROM user_followers 
        WHERE followers_id = ? AND user_id = ? AND is_deleted = 0
    ");
    $stmt->bind_param("ii", $userId, $targetUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    $response['success'] = true;
    $response['data'] = [
        'following' => $result->num_rows > 0
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);