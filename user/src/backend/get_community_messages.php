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

// Check if user is a member of this community
$sqlCheck = "SELECT 1 FROM community_members WHERE user_id = ? AND community_id = ? AND is_deleted = 0";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param('ii', $user_id, $community_id);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();
$is_member = ($resultCheck->num_rows > 0);
$stmtCheck->close();

// Get messages with reaction info
$sql = "SELECT cc.*, uu.user_full_name,
        (SELECT reaction_type FROM community_reactions 
         WHERE message_id = cc.id AND user_id = ? AND is_deleted = 0) as user_reaction,
        (SELECT COUNT(*) FROM community_reactions 
         WHERE message_id = cc.id AND reaction_type = 'like' AND is_deleted = 0) as like_count,
        (SELECT COUNT(*) FROM community_reactions 
         WHERE message_id = cc.id AND reaction_type = 'dislike' AND is_deleted = 0) as dislike_count
        FROM community_chat cc
        JOIN user_user uu ON cc.user_id = uu.id
        WHERE cc.community_id = ? AND cc.is_deleted = 0 ";

// If since parameter provided, only get newer messages
if (isset($_GET['since'])) {
    $sql .= "AND cc.created_on > ? ";
}

$sql .= "ORDER BY cc.created_on ASC LIMIT 100";

$stmt = $conn->prepare($sql);
if (isset($_GET['since'])) {
    $since = $_GET['since'];
    $stmt->bind_param('iis', $user_id, $community_id, $since);
} else {
    $stmt->bind_param('ii', $user_id, $community_id);
}

$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'id' => $row['id'],
        'message' => $row['message'],
        'user_name' => $row['user_full_name'],
        'created_on' => $row['created_on'],
        'is_own' => $row['user_id'] == $user_id,
        'user_reaction' => $row['user_reaction'],
        'like_count' => $row['like_count'],
        'dislike_count' => $row['dislike_count'],
        'attachment_path' => $row['attachment_path'],
        'attachment_name' => $row['attachment_name'],
        'is_member' => $is_member
    ];
}

echo json_encode([
    'status' => true,
    'messages' => $messages
]);

$stmt->close();
$conn->close();