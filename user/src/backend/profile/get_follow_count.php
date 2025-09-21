<?php
require_once('../../backend/dbconfig/connection.php');
session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $userId = $input['user_id'] ?? null;
    if (!$userId) {
        throw new Exception('User ID required');
    }

    $stmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM user_followers WHERE followers_id = ? AND is_deleted = 0) as following_count,
            (SELECT COUNT(*) FROM user_followers WHERE user_id = ? AND is_deleted = 0) as followers_count
    ");
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();

    $response['success'] = true;
    $response['data'] = [
        'followers' => (int) $stats['followers_count'],
        'following' => (int) $stats['following_count']
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);