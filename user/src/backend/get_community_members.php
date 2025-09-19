<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Not logged in']);
    exit();
}

include_once('./dbconfig/connection.php');
$user_id = $_SESSION['user_id'];

if (!isset($_GET['community_id'])) {
    echo json_encode(['status' => false, 'message' => 'Community ID required']);
    exit();
}

$community_id = intval($_GET['community_id']);

// Get members
$sql = "SELECT cm.*, uu.user_full_name, uu.user_image_path 
        FROM community_members cm
        JOIN user_user uu ON cm.user_id = uu.id
        WHERE cm.community_id = ? AND cm.is_deleted = 0
        ORDER BY cm.created_on ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $community_id);
$stmt->execute();
$result = $stmt->get_result();

$members = [];
while ($row = $result->fetch_assoc()) {
    $members[] = [
        'id' => $row['id'],
        'user_id' => $row['user_id'],
        'user_full_name' => $row['user_full_name'],
        'user_image_path' => $row['user_image_path'],
        'created_on' => $row['created_on']
    ];
}

echo json_encode([
    'status' => true,
    'members' => $members
]);

$stmt->close();
$conn->close();