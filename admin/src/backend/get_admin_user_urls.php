<?php
// Get all user allowed urls
require_once './dbconfig/connection.php';
header('Content-Type: application/json');
$sql = "SELECT u.id as user_id, u.user_name, u.email, u.phone, r.role_name as role, au.id as url_id, au.allowed_urls FROM admin_user u LEFT JOIN admin_user_role r ON u.role_id = r.id LEFT JOIN admin_urls au ON u.id = au.user_id";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    exit;
}
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
    $stmt->close();
    exit;
}
$res = $stmt->get_result();
if ($res === false) {
    echo json_encode(['success' => false, 'error' => 'Get result failed: ' . $stmt->error]);
    $stmt->close();
    exit;
}
$data = [];
while($row = $res->fetch_assoc()) {
    $data[] = $row;
}
$stmt->close();
echo json_encode(['success'=>true, 'data'=>$data]);
?>