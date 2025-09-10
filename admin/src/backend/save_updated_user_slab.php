<?php
require('./dbconfig/connection.php');

header('Content-Type: application/json');

// Decode incoming JSON
$data = json_decode(file_get_contents("php://input"), true);

// Sanitize and validate inputs
$requestId = isset($data['id']) ? intval($data['id']) : 0;
$userId = isset($data['user_id']) ? intval($data['user_id']) : 0;
$status = isset($data['status']) ? trim($data['status']) : '';
$comment = isset($data['comment']) ? trim($data['comment']) : '';

if (!in_array($status, ['Approved', 'Rejected']) || empty($comment) || !$requestId || !$userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Step 1: Update the user_request table
$updateRequestQuery = "
    UPDATE user_request 
    SET status = ?, admin_comment = ?, updated_on = NOW()
    WHERE id = ? AND is_deleted = 0
";
if ($stmt = $conn->prepare($updateRequestQuery)) {
    $stmt->bind_param("ssi", $status, $comment, $requestId);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to update request']);
        $stmt->close();
        exit;
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

// Step 2: If approved, update user_user slab
if ($status === 'Approved') {
    // Get requested slab ID
    $slabQuery = "
        SELECT requested_slab_id 
        FROM user_request 
        WHERE id = ? AND is_deleted = 0
    ";
    if ($stmt = $conn->prepare($slabQuery)) {
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Request not found']);
            $stmt->close();
            exit;
        }

        $row = $result->fetch_assoc();
        $requestedSlabId = $row['requested_slab_id'];
        $stmt->close();

        // Update user slab
        $updateUserQuery = "
            UPDATE user_user 
            SET user_slab_id = ?, updated_on = NOW() 
            WHERE id = ? AND is_deleted = 0
        ";
        if ($stmt = $conn->prepare($updateUserQuery)) {
            $stmt->bind_param("ii", $requestedSlabId, $userId);
            if (!$stmt->execute()) {
                echo json_encode(['success' => false, 'message' => 'Failed to update user slab']);
                $stmt->close();
                exit;
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit;
        }

    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }
}

// Final response
echo json_encode(['success' => true]);
