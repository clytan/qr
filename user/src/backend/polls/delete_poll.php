<?php
/**
 * Delete Poll API
 * 
 * Soft deletes a poll (owner or admin)
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../dbconfig/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Invalid request method']);
    exit;
}

$userId = intval($_SESSION['user_id']);

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$pollId = intval($data['poll_id'] ?? 0);

if ($pollId <= 0) {
    echo json_encode(['status' => false, 'message' => 'Invalid poll ID']);
    exit;
}

try {
    // Check if poll exists and user is the owner
    $checkPoll = "SELECT id, user_id FROM user_polls WHERE id = ? AND is_deleted = 0";
    $stmtCheck = $conn->prepare($checkPoll);
    $stmtCheck->bind_param('i', $pollId);
    $stmtCheck->execute();
    $pollResult = $stmtCheck->get_result();
    
    if ($pollResult->num_rows === 0) {
        echo json_encode(['status' => false, 'message' => 'Poll not found']);
        exit;
    }
    
    $poll = $pollResult->fetch_assoc();
    $stmtCheck->close();
    
    // Only owner can delete
    if (intval($poll['user_id']) !== $userId) {
        echo json_encode(['status' => false, 'message' => 'You can only delete your own polls']);
        exit;
    }
    
    // Soft delete the poll
    $deleteSql = "UPDATE user_polls SET is_deleted = 1, updated_on = NOW() WHERE id = ?";
    $stmtDelete = $conn->prepare($deleteSql);
    $stmtDelete->bind_param('i', $pollId);
    
    if (!$stmtDelete->execute()) {
        throw new Exception("Failed to delete poll");
    }
    $stmtDelete->close();
    
    echo json_encode([
        'status' => true,
        'message' => 'Poll deleted successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Delete poll error: " . $e->getMessage());
    echo json_encode(['status' => false, 'message' => 'Failed to delete poll']);
}
?>
