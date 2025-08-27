<?php
require_once('./dbconfig/connection.php');

header('Content-Type: application/json');

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if ($email === '') {
    echo json_encode(['status' => false, 'message' => 'Email is required', 'data' => []]);
    exit();
}

$sql = "SELECT verified FROM user_otp WHERE user_email = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['verified'] == 1) {
            echo json_encode(['status' => true, 'message' => 'Email is verified', 'data' => ['verified' => 1]]);
            exit();
        } else {
            echo json_encode(['status' => false, 'message' => 'Email is not verified', 'data' => ['verified' => 0]]);
            exit();
        }
    } else {
        echo json_encode(['status' => false, 'message' => 'Email not found', 'data' => []]);
        exit();
    }
} else {
    echo json_encode(['status' => false, 'message' => 'Database error', 'data' => []]);
    exit();
}
