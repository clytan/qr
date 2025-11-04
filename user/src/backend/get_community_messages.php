<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Not logged in']);
    exit();
}

include_once('./dbconfig/connection.php');
require_once('check_user_penalties.php');
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

// Check if user is banned or in timeout
$penaltyCheck = checkUserPenalties($user_id, $community_id, $conn);
$isTimedOut = !$penaltyCheck['status'];
$timeoutMessage = $isTimedOut ? $penaltyCheck['message'] : null;

// Get messages with reaction info, moderator status, QR ID, and profile image
$sql = "SELECT cc.*, uu.user_full_name, uu.user_qr_id, uu.user_image_path,
        (SELECT reaction_type FROM community_reactions 
         WHERE message_id = cc.id AND user_id = ? AND is_deleted = 0) as user_reaction,
        (SELECT COUNT(*) FROM community_reactions 
         WHERE message_id = cc.id AND reaction_type = 'like' AND is_deleted = 0) as like_count,
        (SELECT COUNT(*) FROM community_reactions 
         WHERE message_id = cc.id AND reaction_type = 'dislike' AND is_deleted = 0) as dislike_count,
        (SELECT 1 FROM user_roles 
         WHERE user_id = cc.user_id AND community_id = cc.community_id 
         AND role_type IN ('moderator', 'admin') AND is_deleted = 0) as is_moderator,
        (SELECT 1 FROM user_roles 
         WHERE user_id = ? AND community_id = cc.community_id 
         AND role_type IN ('moderator', 'admin') AND is_deleted = 0) as is_current_user_moderator
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
    $stmt->bind_param('iiis', $user_id, $user_id, $community_id, $since);
} else {
    $stmt->bind_param('iii', $user_id, $user_id, $community_id);
}

$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    // Check user penalties (timeout and ban status)
    $sql_penalties = "SELECT penalty_type, end_time 
                     FROM user_penalties 
                     WHERE user_id = ? 
                     AND community_id = ? 
                     AND (penalty_type = 'timeout' OR penalty_type = 'ban')
                     AND is_deleted = 0
                     ORDER BY created_on DESC 
                     LIMIT 1";

    $stmt_penalties = $conn->prepare($sql_penalties);
    $stmt_penalties->bind_param('ii', $row['user_id'], $community_id);
    $stmt_penalties->execute();
    $result_penalties = $stmt_penalties->get_result();
    $penalty = $result_penalties->fetch_assoc();
    $stmt_penalties->close();

    $is_banned = false;
    $is_timed_out = false;
    $now = date('Y-m-d H:i:s');

    if ($penalty) {
        if ($penalty['penalty_type'] === 'ban') {
            $is_banned = true;
        } elseif ($penalty['penalty_type'] === 'timeout' && $penalty['end_time'] > $now) {
            $is_timed_out = true;
        }
    }

    $messages[] = [
        'id' => $row['id'],
        'message' => $row['message'],
        'user_name' => $row['user_full_name'],
        'user_qr_id' => $row['user_qr_id'],
        'user_image_path' => $row['user_image_path'],
        'created_on' => $row['created_on'],
        'is_own' => $row['user_id'] == $user_id,
        'user_reaction' => $row['user_reaction'],
        'like_count' => $row['like_count'],
        'dislike_count' => $row['dislike_count'],
        'attachment_path' => $row['attachment_path'],
        'attachment_name' => $row['attachment_name'],
        'is_member' => $is_member,
        'is_moderator' => (bool) $row['is_moderator'],
        'is_current_user_moderator' => (bool) $row['is_current_user_moderator'],
        'user_id' => $row['user_id'],
        'is_timed_out' => $is_timed_out,
        'is_banned' => $is_banned
    ];
}

echo json_encode([
    'status' => true,
    'messages' => $messages,
    'isTimedOut' => $isTimedOut,
    'timeoutMessage' => $timeoutMessage
]);

$stmt->close();
$conn->close();