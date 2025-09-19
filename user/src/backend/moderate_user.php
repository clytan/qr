<?php
require_once('moderator_utils.php');

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || !isset($data['community_id']) || !isset($data['action'])) {
    echo json_encode(['status' => false, 'message' => 'Missing required parameters']);
    exit();
}

$target_user_id = $data['user_id'];
$community_id = $data['community_id'];
$action = $data['action'];

// Check if user is moderator
if (!isUserModerator($user_id, $community_id)) {
    echo json_encode(['status' => false, 'message' => 'Unauthorized action']);
    exit();
}

switch ($action) {
    case 'timeout':
        if (!isset($data['duration']) || !isset($data['reason'])) {
            echo json_encode(['status' => false, 'message' => 'Missing timeout parameters']);
            exit();
        }

        $duration = intval($data['duration']); // duration in minutes
        $reason = $data['reason'];
        $end_time = date('Y-m-d H:i:s', strtotime("+{$duration} minutes"));

        $sql = "INSERT INTO user_penalties (user_id, community_id, penalty_type, reason, end_time, applied_by) 
                VALUES (?, ?, 'timeout', ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iissi', $target_user_id, $community_id, $reason, $end_time, $user_id);
        break;

    case 'ban':
        if (!isset($data['reason'])) {
            echo json_encode(['status' => false, 'message' => 'Missing ban reason']);
            exit();
        }

        $reason = $data['reason'];

        $sql = "INSERT INTO user_penalties (user_id, community_id, penalty_type, reason, applied_by) 
                VALUES (?, ?, 'ban', ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iisi', $target_user_id, $community_id, $reason, $user_id);
        break;

    case 'unban':
        $sql = "UPDATE user_penalties 
                SET is_active = 0 
                WHERE user_id = ? AND community_id = ? 
                AND is_deleted = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $target_user_id, $community_id);
        break;

    default:
        echo json_encode(['status' => false, 'message' => 'Invalid action']);
        exit();
}

if ($stmt->execute()) {
    echo json_encode(['status' => true, 'message' => 'Action applied successfully']);
} else {
    echo json_encode(['status' => false, 'message' => 'Failed to apply action']);
}

$stmt->close();
$conn->close();