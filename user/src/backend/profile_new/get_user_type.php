<?php
// get_user_type.php
header('Content-Type: application/json');
require_once('.././dbconfig/connection.php');
$qr_id = isset($_POST['qr_id']) ? $_POST['qr_id'] : '';
$result = [ 'user_user_type' => null ];
if ($qr_id !== '') {
    $stmt = $conn->prepare('SELECT user_user_type FROM user_user WHERE user_qr_id = ? AND is_deleted = 0 LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $qr_id);
        $stmt->execute();
        $stmt->bind_result($user_user_type);
        if ($stmt->fetch()) {
            $result['user_user_type'] = $user_user_type;
        }
        $stmt->close();
    }
}
echo json_encode($result);