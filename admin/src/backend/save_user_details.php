<?php
header('Content-Type: application/json');
require_once('./dbconfig/connection.php');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $id = $_POST['id'] ?? null;
    if (!$id) {
        throw new Exception('User ID missing.');
    }

    // Prepare the update statement
    $sql = "UPDATE user_user SET 
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
        user_tag = ? 
        updated_on = NOW()
        WHERE id = ? AND is_deleted=0" ;

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('SQL Prepare Failed: ' . $conn->error);
    }

    // Bind parameters
    $bind = $stmt->bind_param(
        'sssssssssssi',
        $_POST['user_full_name'],
        $_POST['user_phone'],
        $_POST['user_email'],
        $_POST['user_address'],
        $_POST['user_user_type'],
        $_POST['user_qr_id'],
        $_POST['user_slab_id'],
        $_POST['user_email_verified'],
        $_POST['sub_end_date'],
        $_POST['referred_by_user_id'],
        $_POST['user_tag'],
        $id
    );

    if ($bind === false) {
        throw new Exception('Parameter binding failed: ' . $stmt->error);
    }

    // Execute statement
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
    $conn->close();
}
