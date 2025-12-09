<?php
/**
 * Get Today's Draw Results
 * Returns winners for the user's community for today
 */

header('Content-Type: application/json');
require_once '../dbconfig/connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'error' => 'Not logged in', 'login_required' => true]);
    exit;
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

try {
    // Get user's community
    $sqlUser = "SELECT community_id FROM user_user WHERE id = ? AND is_deleted = 0";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param('i', $user_id);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    
    if ($resultUser->num_rows === 0) {
        echo json_encode(['status' => false, 'error' => 'User not found']);
        exit;
    }
    
    $userData = $resultUser->fetch_assoc();
    $community_id = $userData['community_id'];
    
    if (!$community_id) {
        echo json_encode(['status' => false, 'error' => 'User not assigned to any community']);
        exit;
    }
    
    // Get community member count
    $sqlMemberCount = "SELECT COUNT(*) as count FROM community_members WHERE community_id = ? AND is_deleted = 0";
    $stmtCount = $conn->prepare($sqlMemberCount);
    $stmtCount->bind_param('i', $community_id);
    $stmtCount->execute();
    $memberCount = $stmtCount->get_result()->fetch_assoc()['count'];
    
    // Check if draw exists for today
    $sqlDraw = "SELECT id, is_completed, total_winners FROM reward_draws WHERE community_id = ? AND draw_date = ?";
    $stmtDraw = $conn->prepare($sqlDraw);
    $stmtDraw->bind_param('is', $community_id, $today);
    $stmtDraw->execute();
    $resultDraw = $stmtDraw->get_result();
    
    if ($resultDraw->num_rows === 0) {
        // No draw yet for today
        echo json_encode([
            'status' => true,
            'draw_exists' => false,
            'community_id' => $community_id,
            'member_count' => $memberCount,
            'user_id' => $user_id
        ]);
        exit;
    }
    
    $drawData = $resultDraw->fetch_assoc();
    
    if (!$drawData['is_completed']) {
        // Draw exists but not completed yet
        echo json_encode([
            'status' => true,
            'draw_exists' => true,
            'is_completed' => false,
            'community_id' => $community_id,
            'member_count' => $memberCount,
            'user_id' => $user_id
        ]);
        exit;
    }
    
    // Draw is completed - get winners
    $sqlWinners = "SELECT 
                        rw.position,
                        rw.user_id,
                        u.user_qr_id,
                        u.user_full_name,
                        u.user_image_path,
                        (SELECT color_dark FROM user_qr_colors WHERE user_id = u.id AND is_deleted = 0 LIMIT 1) as qr_color_dark,
                        (SELECT color_light FROM user_qr_colors WHERE user_id = u.id AND is_deleted = 0 LIMIT 1) as qr_color_light
                    FROM reward_winners rw
                    JOIN user_user u ON rw.user_id = u.id
                    WHERE rw.draw_id = ?
                    ORDER BY rw.position ASC";
    
    $stmtWinners = $conn->prepare($sqlWinners);
    $stmtWinners->bind_param('i', $drawData['id']);
    $stmtWinners->execute();
    $resultWinners = $stmtWinners->get_result();
    
    $winners = [];
    $currentUserWon = false;
    $currentUserPosition = null;
    
    while ($row = $resultWinners->fetch_assoc()) {
        $isCurrentUser = ($row['user_id'] == $user_id);
        if ($isCurrentUser) {
            $currentUserWon = true;
            $currentUserPosition = $row['position'];
        }
        
        $winners[] = [
            'position' => $row['position'],
            'user_id' => $row['user_id'],
            'user_qr_id' => $row['user_qr_id'],
            'user_full_name' => $row['user_full_name'],
            'user_image_path' => $row['user_image_path'],
            'qr_color_dark' => $row['qr_color_dark'] ?: '#000000',
            'qr_color_light' => $row['qr_color_light'] ?: '#ffffff',
            'is_current_user' => $isCurrentUser
        ];
    }
    
    echo json_encode([
        'status' => true,
        'draw_exists' => true,
        'is_completed' => true,
        'community_id' => $community_id,
        'member_count' => $memberCount,
        'total_winners' => count($winners),
        'current_user_won' => $currentUserWon,
        'current_user_position' => $currentUserPosition,
        'user_id' => $user_id,
        'winners' => $winners
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_todays_draw: " . $e->getMessage());
    echo json_encode(['status' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
