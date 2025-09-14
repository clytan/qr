<?php
require('./dbconfig/connection.php'); // DB connection

header('Content-Type: application/json');

// Initialize response
$response = ['data' => [], 'error' => null];

// Prepared SQL with JOIN
$sql = "
SELECT 
    ur.id AS id,
    ur.user_id AS user_id,
    u.user_full_name AS full_name,
    u.user_email AS email,
    u.user_phone AS phone,
    u.user_qr_id AS qr_id,
    u.user_slab_id AS current_slab_id,
    slab.name AS current_slab_name,
    ur.requested_slab_id AS requested_slab_id,
    requested_slab.name AS requested_slab_name,
    u.user_user_type AS user_type_id,
    user_type.user_type_name AS user_type_name
FROM user_request ur
JOIN user_user u ON ur.user_id = u.id
LEFT JOIN user_slab slab ON u.user_slab_id = slab.id
LEFT JOIN user_slab requested_slab ON ur.requested_slab_id = requested_slab.id
LEFT JOIN user_user_type user_type ON u.user_user_type = user_type.id
WHERE ur.is_deleted = 0 AND ur.status = ?
ORDER BY ur.id DESC
";
// Prepare the statement
if ($stmt = $conn->prepare($sql)) {
    $status = 'Pending';
    $stmt->bind_param("s", $status);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $response['data'][] = [
                'id' => $row['id'],
                'user_id' => $row['user_id'],
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'qr_id' => $row['qr_id'],
                'current_slab' => $row['current_slab_name'],
                'requested_slab' => $row['requested_slab_name'],
            ];
        }

    } else {
        http_response_code(500); // Internal Server Error
        $response['error'] = 'Database execution failed: ' . $stmt->error;
    }

    $stmt->close();

} else {
    http_response_code(500); // Internal Server Error
    $response['error'] = 'Failed to prepare SQL statement: ' . $conn->error;
}

echo json_encode($response);
?>
