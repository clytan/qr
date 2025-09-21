<?php
// Get all users with roles
require_once './dbconfig/connection.php';
header('Content-Type: application/json');
$users = [];
$pages = [];
$userUrls = [];

// Users query (no user input, but use prepared statement for consistency)
$stmt_users = $conn->prepare("SELECT a.id, a.user_name, a.phone, a.email, a.role_id, r.role_name as role FROM admin_user a LEFT JOIN admin_user_role r ON a.role_id = r.id");
if (!$stmt_users) {
    echo json_encode(['success' => false, 'error' => 'Prepare users failed: ' . $conn->error]);
    exit;
}
if (!$stmt_users->execute()) {
    echo json_encode(['success' => false, 'error' => 'Execute users failed: ' . $stmt_users->error]);
    $stmt_users->close();
    exit;
}
$res_users = $stmt_users->get_result();
if ($res_users === false) {
    echo json_encode(['success' => false, 'error' => 'Get result users failed: ' . $stmt_users->error]);
    $stmt_users->close();
    exit;
}
while($row = $res_users->fetch_assoc()) {
    $users[] = $row;
}
$stmt_users->close();

// Pages query (no user input, but use prepared statement for consistency)
$stmt_pages = $conn->prepare("SELECT id, page_name FROM admin_pages");
if (!$stmt_pages) {
    echo json_encode(['success' => false, 'error' => 'Prepare pages failed: ' . $conn->error]);
    exit;
}
if (!$stmt_pages->execute()) {
    echo json_encode(['success' => false, 'error' => 'Execute pages failed: ' . $stmt_pages->error]);
    $stmt_pages->close();
    exit;
}
$res_pages = $stmt_pages->get_result();
if ($res_pages === false) {
    echo json_encode(['success' => false, 'error' => 'Get result pages failed: ' . $stmt_pages->error]);
    $stmt_pages->close();
    exit;
}
while($row = $res_pages->fetch_assoc()) {
    $pages[] = $row;
}
$stmt_pages->close();

// UserUrls query (no user input, but use prepared statement for consistency)
$stmt_user_urls = $conn->prepare("SELECT u.id as user_id, u.user_name, u.email, u.phone, r.role_name as role, au.id as url_id, au.allowed_urls FROM admin_user u LEFT JOIN admin_user_role r ON u.role_id = r.id LEFT JOIN admin_urls au ON u.id = au.user_id");
if (!$stmt_user_urls) {
    echo json_encode(['success' => false, 'error' => 'Prepare userUrls failed: ' . $conn->error]);
    exit;
}
if (!$stmt_user_urls->execute()) {
    echo json_encode(['success' => false, 'error' => 'Execute userUrls failed: ' . $stmt_user_urls->error]);
    $stmt_user_urls->close();
    exit;
}
$res_user_urls = $stmt_user_urls->get_result();
if ($res_user_urls === false) {
    echo json_encode(['success' => false, 'error' => 'Get result userUrls failed: ' . $stmt_user_urls->error]);
    $stmt_user_urls->close();
    exit;
}
while($row = $res_user_urls->fetch_assoc()) {
    $userUrls[] = $row;
}
$stmt_user_urls->close();

echo json_encode([
    'success' => true,
    'users' => $users,
    'pages' => $pages,
    'userUrls' => $userUrls
]);
?>