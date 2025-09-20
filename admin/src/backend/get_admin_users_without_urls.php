<?php
// Get users without allowed_urls
require_once './dbconfig/connection.php';
header('Content-Type: application/json');
$sql = "SELECT u.id as user_id, u.user_name as user_name, r.role_name FROM admin_user u LEFT JOIN admin_user_role r ON u.role_id = r.id WHERE u.role_id != ? AND u.id NOT IN (SELECT user_id FROM admin_urls)";
$role_exclude = 1;
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param('i', $role_exclude);
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
$users = [];
while($row = $res->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();
echo json_encode(['success'=>true, 'data'=>$users]);
?>