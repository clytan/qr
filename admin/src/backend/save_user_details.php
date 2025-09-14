<?php
header('Content-Type: application/json');
require_once('./dbconfig/connection.php');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    // Validate required fields
    $requiredFields = [
        'id', 'user_full_name', 'user_phone', 'user_email',
        'user_address', 'user_user_type', 'user_qr_id',
        'user_slab_id', 'user_email_verified',
        'sub_end_date', 'referred_by_user_id',
        'user_tag', 'admin_id'
    ];

    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Sanitize & assign values
    $id = intval($_POST['id']);
    $fullName = trim($_POST['user_full_name']);
    $phone = trim($_POST['user_phone']);
    $email = trim($_POST['user_email']);
    $address = trim($_POST['user_address']);
    $userType = intval($_POST['user_user_type']);
    $qrId = trim($_POST['user_qr_id']);
    $slabId = intval($_POST['user_slab_id']);
    $emailVerified = ($_POST['user_email_verified'] == 'true' || $_POST['user_email_verified'] == 1) ? 1 : 0;
    $subEndDate = !empty($_POST['sub_end_date']) ? date('Y-m-d', strtotime($_POST['sub_end_date'])) : null;
    $referredBy = !empty($_POST['referred_by_user_id']) ? intval($_POST['referred_by_user_id']) : null;
    $tag = trim($_POST['user_tag']);
    $adminId = intval($_POST['admin_id']);

    // Prepare update query
    $sql = "
        UPDATE user_user SET 
            user_full_name = ?, 
            user_phone = ?, 
            user_email = ?, 
            user_address = ?, 
            user_user_type = ?, 
            user_qr_id = ?, 
            user_slab_id = ?, 
            user_email_verified = ?, 
            sub_end_date = ?, 
            referred_by_user_id = ?, 
            user_tag = ?, 
            updated_on = NOW(), 
            updated_by = ?, 
            updated_by_admin = 1 
        WHERE id = ? AND is_deleted = 0
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('SQL Prepare Failed: ' . $conn->error);
    }

    $stmt->bind_param(
        'sssssisisssii',
        $fullName,
        $phone,
        $email,
        $address,
        $userType,
        $qrId,
        $slabId,
        $emailVerified,
        $subEndDate,
        $referredBy,
        $tag,
        $adminId,
        $id
    );

    if (!$stmt->execute()) {
        throw new Exception('Database update failed: ' . $stmt->error);
    }

    echo json_encode([
        'status' => true,
        'message' => 'User updated successfully'
    ]);

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);

    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }

    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
