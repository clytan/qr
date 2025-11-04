<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once('./dbconfig/connection.php');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method is allowed');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['email']) || empty($data['email'])) {
        throw new Exception('Email is required');
    }

    $email = trim($data['email']);

    // Check if user exists in user_user table
    $sql = "SELECT id FROM user_user WHERE user_email = ? AND is_deleted = 0";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    $exists = $result->num_rows > 0;

    echo json_encode([
        'status' => true,
        'exists' => $exists
    ]);

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'error' => $e->getMessage()
    ]);
}
?>
