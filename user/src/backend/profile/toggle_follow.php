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

    // Check if already following
    $stmt = $conn->prepare("SELECT id FROM user_followers WHERE followers_id = ? AND user_id = ? AND is_deleted = 0");
    $stmt->bind_param("ii", $userId, $targetUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Unfollow
        $stmt = $conn->prepare("UPDATE user_followers SET is_deleted = 1, updated_by = ? WHERE followers_id = ? AND user_id = ?");
        $stmt->bind_param("iii", $userId, $userId, $targetUserId);
        $stmt->execute();
        $response['data'] = ['following' => false];
    } else {
        // Follow
        $stmt = $conn->prepare("INSERT INTO user_followers (followers_id, user_id, created_by) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $userId, $targetUserId, $userId);
        $stmt->execute();
        $response['data'] = ['following' => true];
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);