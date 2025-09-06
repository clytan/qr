<?php
// get_followers_count.php
header('Content-Type: application/json');
require_once './dbconfig/connection.php';
$data = json_decode(file_get_contents('php://input'), true);
$qr_id = isset($data['qr_id']) ? $data['qr_id'] : '';
$follower_id = isset($data['follower_id']) ? $data['follower_id'] : '';
$total_count = 0;
$following = false;
if ($qr_id !== '') {
    // Get user_id from user_user table (must not be deleted)
    $stmt = $conn->prepare('SELECT id FROM user_user WHERE user_qr_id = ? AND is_deleted = 0 LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $qr_id);
        $stmt->execute();
        $stmt->bind_result($user_id);
        if ($stmt->fetch() && $user_id) {
            $stmt->close();
            // Count followers for this user_id (must not be deleted)
            $stmt2 = $conn->prepare('SELECT COUNT(*) FROM user_followers WHERE user_id = ? AND is_deleted = 0');
            if ($stmt2) {
                $stmt2->bind_param('s', $user_id);
                $stmt2->execute();
                $stmt2->bind_result($total_count);
                $stmt2->fetch();
                $stmt2->close();
            }
            // Check if follower_id is following user_id
            if ($follower_id !== '') {
                $stmt3 = $conn->prepare('SELECT 1 FROM user_followers WHERE user_id = ? AND followers_id = ? AND is_deleted = 0 LIMIT 1');
                if ($stmt3) {
                    $stmt3->bind_param('ss', $user_id, $follower_id);
                    $stmt3->execute();
                    $stmt3->store_result();
                    if ($stmt3->num_rows > 0) {
                        $following = true;
                    }
                    $stmt3->close();
                }
            }
        } else {
            $stmt->close();
        }
    }
}
echo json_encode(['total_count' => (int)$total_count, 'following' => $following]);