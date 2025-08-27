<?php
require_once('./dbconfig/connection.php');

header('Content-Type: application/json');

$otp_id = isset($_POST['otp_id']) ? intval($_POST['otp_id']) : 0;
$otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';

if ($otp_id === 0 || $otp === '') {
    echo json_encode(['status' => false, 'message' => 'OTP is required', 'data' => []]);
    exit();
}

$sql = "SELECT otp, verified FROM user_otp WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param('i', $otp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['otp'] === $otp) {
            // Mark as verified
            $sqlUpdate = "UPDATE user_otp SET verified = 1 WHERE id = ?";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            if ($stmtUpdate) {
                $stmtUpdate->bind_param('i', $otp_id);
                $stmtUpdate->execute();
            }
            echo json_encode(['status' => true, 'message' => 'OTP verified successfully', 'data' => []]);
            exit();
        } else {
            echo json_encode(['status' => false, 'message' => 'Invalid OTP', 'data' => []]);
            exit();
        }
    } else {
        echo json_encode(['status' => false, 'message' => 'OTP record not found', 'data' => []]);
        exit();
    }
} else {
    echo json_encode(['status' => false, 'message' => 'Database error', 'data' => []]);
    exit();
}
