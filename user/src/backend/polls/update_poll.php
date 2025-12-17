<?php
/**
 * Update Poll API
 * 
 * Updates a poll title/description (only if no votes yet)
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
$title = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$status = $data['status'] ?? null;

if ($pollId <= 0) {
    echo json_encode(['status' => false, 'message' => 'Invalid poll ID']);
    exit;
}

if (empty($title)) {
    echo json_encode(['status' => false, 'message' => 'Poll title is required']);
    exit;
}

try {
    // Check if poll exists, user is owner, and no votes yet
    $checkPoll = "SELECT p.id, p.user_id, 
                         (SELECT COUNT(*) FROM user_poll_votes WHERE poll_id = p.id) as vote_count
                  FROM user_polls p 
                  WHERE p.id = ? AND p.is_deleted = 0";
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
    
    // Only owner can edit
    if (intval($poll['user_id']) !== $userId) {
        echo json_encode(['status' => false, 'message' => 'You can only edit your own polls']);
        exit;
    }
    
    // Can only edit title/description if no votes yet
    if (intval($poll['vote_count']) > 0) {
        // Can only close/reopen, not edit content
        if ($status && in_array($status, ['active', 'closed'])) {
            $updateSql = "UPDATE user_polls SET status = ?, updated_on = NOW() WHERE id = ?";
            $stmtUpdate = $conn->prepare($updateSql);
            $stmtUpdate->bind_param('si', $status, $pollId);
        } else {
            echo json_encode(['status' => false, 'message' => 'Cannot edit poll after people have voted. You can only close it.']);
            exit;
        }
    } else {
        // No votes yet - can edit everything
        $updateSql = "UPDATE user_polls SET title = ?, description = ?, updated_on = NOW() WHERE id = ?";
        $stmtUpdate = $conn->prepare($updateSql);
        $stmtUpdate->bind_param('ssi', $title, $description, $pollId);
    }
    
    if (!$stmtUpdate->execute()) {
        throw new Exception("Failed to update poll");
    }
    $stmtUpdate->close();
    
    echo json_encode([
        'status' => true,
        'message' => 'Poll updated successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Update poll error: " . $e->getMessage());
    echo json_encode(['status' => false, 'message' => 'Failed to update poll']);
}
?>
