<?php
/**
 * Save User's Selected QR Frame
 */
session_start();
header('Content-Type: application/json');
require_once('.././dbconfig/connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'User not logged in.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get frame from request
$data = json_decode(file_get_contents('php://input'), true);
$frame = isset($data['frame']) ? trim($data['frame']) : '';

// Validate frame
if ($frame === '') {
    echo json_encode(['status' => false, 'message' => 'No frame specified.']);
    exit;
}

// Handle special cases
if ($frame === 'none') {
    $frame = null;
} elseif ($frame === 'default') {
    $frame = 'default';
}

try {
    // Update user's frame preference
    $stmt = $conn->prepare('UPDATE user_user SET qr_frame = ? WHERE id = ?');
    $stmt->bind_param('si', $frame, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => true, 
            'message' => 'Frame saved successfully.',
            'frame' => $frame
        ]);
    } else {
        echo json_encode(['status' => false, 'message' => 'Failed to save frame.']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
