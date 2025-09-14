<?php
require('./dbconfig/connection.php');

header('Content-Type: application/json');

// Decode and sanitize incoming JSON
$data = json_decode(file_get_contents("php://input"), true);

$requestId = isset($data['id']) ? intval($data['id']) : 0;
$userId = isset($data['user_id']) ? intval($data['user_id']) : 0;
$status = isset($data['status']) ? trim($data['status']) : '';
$comment = isset($data['comment']) ? trim($data['comment']) : '';
$adminId = isset($data['admin_id']) ? intval($data['admin_id']) : '';

// Validate input
if (!in_array($status, ['Approved', 'Rejected']) || empty($comment) || !$requestId || !$userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}
if($adminId === '') {
    echo json_encode(['success'=> false, 'message'=> 'AdminID not present']);
    exit;
}

// Step 1: Update user_request table with status and comment
$updateRequestSQL = "
    UPDATE user_request 
    SET status = ?, admin_comment = ?, updated_on = NOW() , updated_by = ? , updated_by_admin = 1 
    WHERE id = ? AND is_deleted = 0
";

if ($stmt = $conn->prepare($updateRequestSQL)) {
    $stmt->bind_param("ssii", $status, $comment, $adminId,$requestId);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to update user request']);
        $stmt->close();
        exit;
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Database prepare error (request update): ' . $conn->error]);
    exit;
}

// Step 2: If Approved, update user_user slab
if ($status === 'Approved') {
    // Get requested_slab_id
    $getSlabSQL = "
        SELECT requested_slab_id 
        FROM user_request 
        WHERE id = ? AND is_deleted = 0
    ";

    if ($stmt = $conn->prepare($getSlabSQL)) {
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Request not found']);
            $stmt->close();
            exit;
        }

        $row = $result->fetch_assoc();
        $requestedSlabId = intval($row['requested_slab_id']);
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Database prepare error (get slab): ' . $conn->error]);
        exit;
    }

    // Update user slab in user_user table
    $updateUserSQL = "
        UPDATE user_user 
        SET user_slab_id = ?, updated_on = NOW(), updated_by = ? , updated_by_admin = 1 
        WHERE id = ? AND is_deleted = 0
    ";

    if ($stmt = $conn->prepare($updateUserSQL)) {
        $stmt->bind_param("iii", $requestedSlabId, $adminId, $userId);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Failed to update user slab']);
            $stmt->close();
            exit;
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Database prepare error (user update): ' . $conn->error]);
        exit;
    }
}

// Success response
echo json_encode(['success' => true]);
