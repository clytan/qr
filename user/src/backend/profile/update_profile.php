<?php
require_once('../../backend/dbconfig/connection.php');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $_SESSION['user_id'];

    $conn->begin_transaction();

    // Update basic info
    $stmt = $conn->prepare("
        UPDATE user_user 
        SET 
            user_full_name = ?,
            user_phone = ?,
            user_email = ?,
            user_address = ?,
            updated_by = ?,
            updated_on = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->bind_param(
        "ssssii",
        $input['basic']['full_name'],
        $input['basic']['phone_number'],
        $input['basic']['email_address'],
        $input['basic']['address'],
        $userId,
        $userId
    );
    $stmt->execute();

    // Update social links
    foreach ($input['social'] as $platform => $data) {
        // First check if link exists
        $stmt = $conn->prepare("
            SELECT id FROM user_profile_links_type 
            WHERE user_id = ? AND name = ? AND is_deleted = 0
        ");
        $stmt->bind_param("is", $userId, $platform);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update existing link
            $linkId = $result->fetch_assoc()['id'];
            $stmt = $conn->prepare("
                UPDATE user_profile_links_type 
                SET 
                    value = ?,
                    is_public = ?,
                    updated_by = ?,
                    updated_on = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->bind_param(
                "siii",
                $data['value'],
                $data['is_public'],
                $userId,
                $linkId
            );
        } else {
            // Insert new link
            $stmt = $conn->prepare("
                INSERT INTO user_profile_links_type 
                (user_id, name, value, is_public, created_by) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "issii",
                $userId,
                $platform,
                $data['value'],
                $data['is_public'],
                $userId
            );
        }
        $stmt->execute();
    }

    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Profile updated successfully';

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);