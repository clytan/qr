<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
require_once('dbconfig/connection.php');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => false,
        'message' => 'Invalid request method. Only POST allowed.'
    ]);
    exit;
}

// Get the reference code from POST data
$user_qr_id = isset($_POST['user_qr_id']) ? trim($_POST['user_qr_id']) : '';
// Debug: log the received user_qr_id
error_log('validate_reference.php received user_qr_id: ' . $user_qr_id);

// Validate input
if (empty($user_qr_id)) {
    echo json_encode([
        'status' => false,
        'message' => 'Reference code is required.'
    ]);
    exit;
}

try {
    // Prepare SQL query to check if user_qr_id exists in user_user table
    $sql = "SELECT id, user_qr_id, user_email, user_full_name, created_on 
            FROM user_user 
            WHERE user_qr_id = ? AND is_deleted = 0 
            LIMIT 1";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    // Bind parameter and execute
    $stmt->bind_param('s', $user_qr_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Reference code is valid
        $user_data = $result->fetch_assoc();

        echo json_encode([
            'status' => true,
            'message' => 'Valid reference code.',
            'data' => [
                'referred_by_user_id' => $user_data['id'],
                'referred_by_email' => $user_data['user_email'],
                'referred_by_name' => $user_data['user_full_name'],
                'user_qr_id' => $user_data['user_qr_id'],
                'member_since' => $user_data['created_on']
            ]
        ]);
    } else {
        // Reference code not found
        echo json_encode([
            'status' => false,
            'message' => 'Invalid reference code. Please check and try again.'
        ]);
    }

    $stmt->close();

} catch (Exception $e) {
    // Handle any errors
    error_log('Reference validation error: ' . $e->getMessage());

    echo json_encode([
        'status' => false,
        'message' => 'An error occurred while validating the reference code. Please try again.'
    ]);
}

// Close database connection
$conn->close();
?>