<?php
/**
 * Get User's Selected QR Frame
 */
header('Content-Type: application/json');
require_once('.././dbconfig/connection.php');

$user_id = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$qr_id = isset($_POST['qr_id']) ? $_POST['qr_id'] : '';

$response = [
    'status' => true,
    'frame' => 'default',
    'frameUrl' => '/user/src/assets/images/frame.png'
];

try {
    $targetUserId = null;
    
    // Get user ID
    if ($user_id !== '') {
        $targetUserId = $user_id;
    } elseif ($qr_id !== '') {
        // Look up user by QR ID
        $stmt = $conn->prepare('SELECT id FROM user_user WHERE user_qr_id = ? AND is_deleted = 0 LIMIT 1');
        $stmt->bind_param('s', $qr_id);
        $stmt->execute();
        $stmt->bind_result($foundId);
        if ($stmt->fetch()) {
            $targetUserId = $foundId;
        }
        $stmt->close();
    }
    
    if ($targetUserId) {
        // Get user's frame preference
        $stmt = $conn->prepare('SELECT qr_frame FROM user_user WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $targetUserId);
        $stmt->execute();
        $stmt->bind_result($frame);
        
        if ($stmt->fetch() && $frame) {
            $response['frame'] = $frame;
            
            // Determine frame URL
            if ($frame === 'none' || $frame === null) {
                $response['frameUrl'] = null;
            } elseif ($frame === 'default') {
                $response['frameUrl'] = '/user/src/assets/images/frame.png';
            } else {
                $response['frameUrl'] = '/user/src/frames/' . $frame;
            }
        }
        $stmt->close();
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'frame' => 'default',
        'frameUrl' => '/user/src/assets/images/frame.png'
    ]);
}
?>
