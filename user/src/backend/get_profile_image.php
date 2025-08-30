<?php
// get_profile_image.php
require_once('./dbconfig/connection.php');

$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$response = ['status' => false, 'src' => ''];
if ($user_id > 0) {
    $sql = "SELECT user_image_path FROM user_user WHERE id = ? AND is_deleted = 0";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->bind_result($imgPath);
        if ($stmt->fetch() && $imgPath) {
            $response['status'] = true;
            $response['src'] = $imgPath;
        }
        $stmt->close();
    }
}
header('Content-Type: application/json');
echo json_encode($response);
