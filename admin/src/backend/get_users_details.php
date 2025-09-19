<?php
session_start();
header('Content-Type: application/json');
require_once('./dbconfig/connection.php'); // adjust path as needed

try {
    // Since no user input is used here, SQL injection risk is minimal,
    // but we use prepared statements for consistency and safety.
    $user_sql = "
        SELECT 
            uu.id,
            uu.user_full_name,
            uu.user_email,
            uu.user_phone,
            uut.user_type_name AS user_user_type, 
            uu.user_email_verified,
            uu.user_qr_id,
            uu.user_tag,
            uu.user_image_path,
            uu.user_address,
            us.name AS user_slab_id, 
            uu.sub_end_date,
            uu.referred_by_user_id,
            uuc.user_full_name AS created_by,
            uu.created_on,
            uup.user_full_name AS updated_by,
            uu.updated_on
        FROM user_user uu
        LEFT JOIN user_user_type uut ON uu.user_user_type = uut.id
        LEFT JOIN user_slab us ON uu.user_slab_id = us.id
        LEFT JOIN user_user uuc ON uu.created_by = uuc.id
        LEFT JOIN user_user uup ON uu.updated_by = uup.id
        WHERE uu.is_deleted=0
    ";

    $stmt = $conn->prepare($user_sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Normalize email_verified as boolean string "true"/"false"
            $row['user_email_verified'] = ($row['user_email_verified'] == 1) ? "true" : "false";
            $users[] = $row;
        }

        echo json_encode([
            'status' => true,
            'data' => $users
        ]);
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Failed to fetch user data'
        ]);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>
