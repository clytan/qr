<?php
/**
 * Vote on Poll API
 * 
 * Submits a user's vote on a poll
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
$optionId = intval($data['option_id'] ?? 0);

if ($pollId <= 0 || $optionId <= 0) {
    echo json_encode(['status' => false, 'message' => 'Invalid poll or option']);
    exit;
}

try {
    // Check if poll exists and is active
    $checkPoll = "SELECT id, status, poll_type FROM user_polls WHERE id = ? AND is_deleted = 0";
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
    
    if ($poll['status'] !== 'active') {
        echo json_encode(['status' => false, 'message' => 'This poll is closed']);
        exit;
    }
    
    // Check if option belongs to this poll
    $checkOption = "SELECT id FROM user_poll_options WHERE id = ? AND poll_id = ?";
    $stmtOpt = $conn->prepare($checkOption);
    $stmtOpt->bind_param('ii', $optionId, $pollId);
    $stmtOpt->execute();
    if ($stmtOpt->get_result()->num_rows === 0) {
        echo json_encode(['status' => false, 'message' => 'Invalid option for this poll']);
        exit;
    }
    $stmtOpt->close();
    
    // Check if user already voted (for single-choice polls)
    $checkVote = "SELECT id FROM user_poll_votes WHERE poll_id = ? AND user_id = ?";
    $stmtVote = $conn->prepare($checkVote);
    $stmtVote->bind_param('ii', $pollId, $userId);
    $stmtVote->execute();
    $voteResult = $stmtVote->get_result();
    
    if ($voteResult->num_rows > 0) {
        echo json_encode(['status' => false, 'message' => 'You have already voted on this poll']);
        exit;
    }
    $stmtVote->close();
    
    // Insert vote
    $insertVote = "INSERT INTO user_poll_votes (poll_id, option_id, user_id, created_on) VALUES (?, ?, ?, NOW())";
    $stmtInsert = $conn->prepare($insertVote);
    $stmtInsert->bind_param('iii', $pollId, $optionId, $userId);
    
    if (!$stmtInsert->execute()) {
        throw new Exception("Failed to record vote");
    }
    $stmtInsert->close();
    
    // Get updated vote counts
    $countSql = "SELECT o.id, o.option_text, 
                        (SELECT COUNT(*) FROM user_poll_votes WHERE option_id = o.id) as vote_count
                 FROM user_poll_options o
                 WHERE o.poll_id = ?
                 ORDER BY o.option_order ASC";
    $stmtCount = $conn->prepare($countSql);
    $stmtCount->bind_param('i', $pollId);
    $stmtCount->execute();
    $countResult = $stmtCount->get_result();
    
    $options = [];
    $totalVotes = 0;
    while ($row = $countResult->fetch_assoc()) {
        $options[] = [
            'id' => intval($row['id']),
            'text' => $row['option_text'],
            'votes' => intval($row['vote_count'])
        ];
        $totalVotes += intval($row['vote_count']);
    }
    $stmtCount->close();
    
    echo json_encode([
        'status' => true,
        'message' => 'Vote recorded successfully!',
        'options' => $options,
        'total_votes' => $totalVotes,
        'voted_option' => $optionId
    ]);
    
} catch (Exception $e) {
    error_log("Vote poll error: " . $e->getMessage());
    echo json_encode(['status' => false, 'message' => 'Failed to record vote']);
}
?>
