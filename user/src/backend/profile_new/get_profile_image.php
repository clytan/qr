<?php
require_once('.././dbconfig/connection.php');

$response = ['status' => false, 'src' => ''];

if (isset($_POST['qr'])) {
    // Fetch image by QR code
    $qr = $_POST['qr'];
    $sql = "SELECT user_image_path FROM user_user WHERE user_qr_id = ? AND is_deleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $qr);
} else if (isset($_POST['user_id'])) {
    // Fetch image by user ID (existing logic)
    $userId = intval($_POST['user_id']);
    $sql = "SELECT user_image_path FROM user_user WHERE id = ? AND is_deleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
} else {
    echo json_encode($response);
    exit;
}

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['user_image_path'])) {
            $response['status'] = true;
            $response['src'] = $row['user_image_path'];
        }
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($response);
?>