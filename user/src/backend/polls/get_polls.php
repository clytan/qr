<?php
/**
 * Get Polls API
 * 
 * Returns all active polls for the community feed,
 * plus user's own polls if requested
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

$userId = intval($_SESSION['user_id']);
$filter = $_GET['filter'] ?? 'all'; // 'all', 'my', 'active', 'closed'

// Cleanup: Mark polls older than 7 days as closed
// This ensures they don't show up in 'active' feeds
$cleanupSql = "UPDATE user_polls SET status = 'closed' 
               WHERE created_on < DATE_SUB(NOW(), INTERVAL 7 DAY) 
               AND status = 'active'";
$conn->query($cleanupSql);

try {
    $polls = [];
    
    // Build query based on filter
    $whereConditions = ['p.is_deleted = 0'];
    $params = [];
    $types = '';
    
    if ($filter === 'my') {
        $whereConditions[] = 'p.user_id = ?';
        $params[] = $userId;
        $types .= 'i';
    } elseif ($filter === 'active') {
        $whereConditions[] = 'p.status = ?';
        $params[] = 'active';
        $types .= 's';
    } elseif ($filter === 'closed') {
        $whereConditions[] = 'p.status = ?';
        $params[] = 'closed';
        $types .= 's';
    } else {
        // All polls - only show active for community, show all for user's own
        $whereConditions[] = "(p.status = 'active' OR p.user_id = ?)";
        $params[] = $userId;
        $types .= 'i';
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    $sql = "SELECT 
                p.id,
                p.user_id,
                p.title,
                p.description,
                p.poll_type,
                p.status,
                p.is_admin_poll,
                p.ends_at,
                p.ends_at,
                p.created_on,
                p.payment_id,
                u.user_full_name as creator_name,
                u.user_qr_id as creator_qr_id,
                (SELECT COUNT(*) FROM user_poll_votes WHERE poll_id = p.id) as total_votes
            FROM user_polls p
            LEFT JOIN user_user u ON p.user_id = u.id
            WHERE $whereClause
            ORDER BY p.created_on DESC
            LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $pollId = $row['id'];
        
        // Get options for this poll
        $optionsSql = "SELECT 
                            o.id,
                            o.option_text,
                            o.option_image,
                            o.option_order,
                            (SELECT COUNT(*) FROM user_poll_votes WHERE option_id = o.id) as vote_count
                       FROM user_poll_options o
                       WHERE o.poll_id = ?
                       ORDER BY o.option_order ASC";
        $optionsStmt = $conn->prepare($optionsSql);
        $optionsStmt->bind_param('i', $pollId);
        $optionsStmt->execute();
        $optionsResult = $optionsStmt->get_result();
        
        $options = [];
        while ($opt = $optionsResult->fetch_assoc()) {
            $options[] = [
                'id' => intval($opt['id']),
                'text' => $opt['option_text'],
                'image' => $opt['option_image'] ? '/' . $opt['option_image'] : null,
                'order' => intval($opt['option_order']),
                'votes' => intval($opt['vote_count'])
            ];
        }
        $optionsStmt->close();
        
        // Check if current user has voted on this poll
        $votedSql = "SELECT option_id FROM user_poll_votes WHERE poll_id = ? AND user_id = ?";
        $votedStmt = $conn->prepare($votedSql);
        $votedStmt->bind_param('ii', $pollId, $userId);
        $votedStmt->execute();
        $votedResult = $votedStmt->get_result();
        $userVotedOptionId = null;
        if ($votedRow = $votedResult->fetch_assoc()) {
            $userVotedOptionId = intval($votedRow['option_id']);
        }
        $votedStmt->close();
        
        $polls[] = [
            'id' => intval($row['id']),
            'user_id' => intval($row['user_id']),
            'title' => $row['title'],
            'description' => $row['description'],
            'poll_type' => $row['poll_type'],
            'status' => $row['status'],
            'is_admin_poll' => (bool)$row['is_admin_poll'],
            'ends_at' => $row['ends_at'],
            'created_on' => $row['created_on'],
            'payment_id' => $row['payment_id'],
            'creator_name' => $row['creator_name'] ?? 'Anonymous',
            'creator_qr_id' => $row['creator_qr_id'],
            'total_votes' => intval($row['total_votes']),
            'options' => $options,
            'user_voted' => $userVotedOptionId !== null,
            'user_voted_option' => $userVotedOptionId,
            'is_owner' => intval($row['user_id']) === $userId
        ];
    }
    $stmt->close();
    
    echo json_encode([
        'status' => true,
        'polls' => $polls,
        'count' => count($polls)
    ]);
    
} catch (Exception $e) {
    error_log("Get polls error: " . $e->getMessage());
    echo json_encode(['status' => false, 'message' => 'Failed to fetch polls']);
}
?>
