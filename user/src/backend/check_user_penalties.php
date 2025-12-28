<?php
function checkUserPenalties($user_id, $community_id, $conn)
{
    $now = date('Y-m-d H:i:s');

    // Check for active penalties (timeout or ban)
    $sql = "SELECT penalty_type, end_time 
            FROM user_penalties 
            WHERE user_id = ? 
            AND community_id = ? 
            AND (penalty_type = 'timeout' OR penalty_type = 'ban')
            AND (end_time > ? OR penalty_type = 'ban')
            AND is_deleted = 0
            ORDER BY created_on DESC 
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iis', $user_id, $community_id, $now);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $penalty = $result->fetch_assoc();

        if ($penalty['penalty_type'] === 'ban') {
            return ['status' => false, 'message' => 'You have been banned from this community'];
        } else {
            return ['status' => false, 'message' => 'You are in timeout until ' . date('M j, Y g:i A', strtotime($penalty['end_time']))];
        }
    }

    return ['status' => true];
}
?>