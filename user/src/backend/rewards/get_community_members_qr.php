<?php
/**
 * Get Community Members for Spinner Display
 * Returns all members with their QR info for the spinning animation
 */

header('Content-Type: application/json');
require_once '../dbconfig/connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'error' => 'Not logged in', 'login_required' => true]);
    exit;
}

$user_id = $_SESSION['user_id'];

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
    
    // Get all community members with QR info
    // We need this for the spinner to show random QRs while spinning
    $sqlMembers = "SELECT 
                        u.id as user_id,
                        u.user_qr_id,
                        u.user_full_name,
                        u.user_image_path,
                        COALESCE(qc.color_dark, '#000000') as qr_color_dark,
                        COALESCE(qc.color_light, '#ffffff') as qr_color_light
                    FROM community_members cm
                    JOIN user_user u ON cm.user_id = u.id AND u.is_deleted = 0
                    LEFT JOIN user_qr_colors qc ON u.id = qc.user_id AND qc.is_deleted = 0
                    WHERE cm.community_id = ? 
                    AND cm.is_deleted = 0
                    ORDER BY RAND()
                    LIMIT 100"; // Limit for performance
    
    $stmtMembers = $conn->prepare($sqlMembers);
    $stmtMembers->bind_param('i', $community_id);
    $stmtMembers->execute();
    $resultMembers = $stmtMembers->get_result();
    
    $members = [];
    while ($row = $resultMembers->fetch_assoc()) {
        $members[] = [
            'user_id' => $row['user_id'],
            'user_qr_id' => $row['user_qr_id'],
            'user_full_name' => $row['user_full_name'],
            'user_image_path' => $row['user_image_path'],
            'qr_color_dark' => $row['qr_color_dark'],
            'qr_color_light' => $row['qr_color_light'],
            'is_current_user' => ($row['user_id'] == $user_id)
        ];
    }
    
    echo json_encode([
        'status' => true,
        'community_id' => $community_id,
        'total_members' => count($members),
        'current_user_id' => $user_id,
        'members' => $members
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_community_members_qr: " . $e->getMessage());
    echo json_encode(['status' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
