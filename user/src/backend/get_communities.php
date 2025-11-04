<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Not logged in']);
    exit();
}

include_once('./dbconfig/connection.php');
$user_id = $_SESSION['user_id'];

// Get all communities
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM community_members WHERE community_id = c.id AND is_deleted = 0) as current_members,
        EXISTS(SELECT 1 FROM community_members WHERE community_id = c.id AND user_id = ? AND is_deleted = 0) as is_user_community,
        EXISTS(SELECT 1 FROM user_roles WHERE user_id = ? AND community_id = c.id AND role_type IN ('moderator', 'admin') AND is_deleted = 0) as is_moderator
        FROM community c 
        WHERE c.is_deleted = 0 
        ORDER BY c.id ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$communities = [];
$user_community_id = null;

while ($row = $result->fetch_assoc()) {
    $communities[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'current_members' => $row['current_members'],
        'is_user_community' => $row['is_user_community'] == 1,
        'is_full' => $row['is_full'],
        'is_moderator' => $row['is_moderator'] == 1
    ];
    if ($row['is_user_community']) {
        $user_community_id = $row['id'];
    }
}

echo json_encode([
    'status' => true,
    'communities' => $communities,
    'user_community_id' => $user_community_id
]);

$stmt->close();
$conn->close();