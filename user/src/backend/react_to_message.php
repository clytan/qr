<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Not logged in']);
    exit();
}

include_once('./dbconfig/connection.php');
$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['message_id']) || !isset($data['reaction_type'])) {
    echo json_encode(['status' => false, 'message' => 'Missing required fields']);
    exit();
}

$message_id = intval($data['message_id']);
$reaction_type = $data['reaction_type'];

if (!in_array($reaction_type, ['like', 'dislike'])) {
    echo json_encode(['status' => false, 'message' => 'Invalid reaction type']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Check if user already reacted
    $sql = "SELECT id, reaction_type FROM community_reactions 
            WHERE message_id = ? AND user_id = ? AND is_deleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $message_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();

    $now = date('Y-m-d H:i:s');

    if ($existing) {
        if ($existing['reaction_type'] === $reaction_type) {
            // Remove reaction if clicking same button
            $sql = "UPDATE community_reactions SET is_deleted = 1, updated_by = ?, updated_on = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('isi', $user_id, $now, $existing['id']);
            $stmt->execute();
            $stmt->close();

            // Decrease count
            $countField = $reaction_type . 's_count';
            $sql = "UPDATE community_chat SET $countField = GREATEST($countField - 1, 0) WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $message_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Change reaction type
            $sql = "UPDATE community_reactions 
                    SET reaction_type = ?, updated_by = ?, updated_on = ?, is_deleted = 0 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sisi', $reaction_type, $user_id, $now, $existing['id']);
            $stmt->execute();
            $stmt->close();

            // Update counts
            $oldField = $existing['reaction_type'] . 's_count';
            $newField = $reaction_type . 's_count';
            $sql = "UPDATE community_chat 
                    SET $oldField = GREATEST($oldField - 1, 0),
                        $newField = $newField + 1
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $message_id);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        // Add new reaction
        $sql = "INSERT INTO community_reactions (message_id, user_id, reaction_type, created_by, created_on, updated_by, updated_on) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iisisss', $message_id, $user_id, $reaction_type, $user_id, $now, $user_id, $now);
        $stmt->execute();
        $stmt->close();

        // Increase count
        $countField = $reaction_type . 's_count';
        $sql = "UPDATE community_chat SET $countField = $countField + 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $message_id);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();

    // Get updated counts
    $sql = "SELECT likes_count, dislikes_count FROM community_chat WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $counts = $result->fetch_assoc();

    echo json_encode([
        'status' => true,
        'likes_count' => $counts['likes_count'],
        'dislikes_count' => $counts['dislikes_count']
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => false, 'message' => 'Error processing reaction']);
}

$conn->close();