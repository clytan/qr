<?php
/**
 * Create Poll API
 * 
 * Creates a new poll with options
 * Polls go live immediately
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

$title = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$pollType = $data['poll_type'] ?? 'single';
$options = $data['options'] ?? [];
$endsAt = $data['ends_at'] ?? null;

// Validation
if (empty($title)) {
    echo json_encode(['status' => false, 'message' => 'Poll title is required']);
    exit;
}

if (strlen($title) > 255) {
    echo json_encode(['status' => false, 'message' => 'Title is too long (max 255 characters)']);
    exit;
}

if (count($options) < 2) {
    echo json_encode(['status' => false, 'message' => 'At least 2 options are required']);
    exit;
}

if (count($options) > 10) {
    echo json_encode(['status' => false, 'message' => 'Maximum 10 options allowed']);
    exit;
}

// Validate each option
foreach ($options as $option) {
    if (empty(trim($option))) {
        echo json_encode(['status' => false, 'message' => 'All options must have text']);
        exit;
    }
    if (strlen($option) > 255) {
        echo json_encode(['status' => false, 'message' => 'Option text is too long (max 255 characters)']);
        exit;
    }
}

// Validate poll type
if (!in_array($pollType, ['single', 'multiple'])) {
    $pollType = 'single';
}

try {
    $conn->begin_transaction();
    
    // Insert poll
    $sqlPoll = "INSERT INTO user_polls (user_id, title, description, poll_type, status, payment_status, ends_at, created_by, created_on) 
                VALUES (?, ?, ?, ?, 'pending_payment', 'pending', ?, ?, NOW())";
    $stmtPoll = $conn->prepare($sqlPoll);
    $stmtPoll->bind_param('issssi', $userId, $title, $description, $pollType, $endsAt, $userId);
    
    if (!$stmtPoll->execute()) {
        throw new Exception("Failed to create poll");
    }
    
    $pollId = $conn->insert_id;
    $stmtPoll->close();
    
    // Insert options
    $sqlOption = "INSERT INTO user_poll_options (poll_id, option_text, option_order) VALUES (?, ?, ?)";
    $stmtOption = $conn->prepare($sqlOption);
    
    $order = 1;
    foreach ($options as $optionText) {
        $optionText = trim($optionText);
        if (!empty($optionText)) {
            $stmtOption->bind_param('isi', $pollId, $optionText, $order);
            if (!$stmtOption->execute()) {
                throw new Exception("Failed to create poll option");
            }
            $order++;
        }
    }
    $stmtOption->close();
    
    $conn->commit();
    
    echo json_encode([
        'status' => true,
        'message' => 'Poll created successfully!',
        'poll_id' => $pollId
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Create poll error: " . $e->getMessage());
    echo json_encode(['status' => false, 'message' => 'Failed to create poll']);
}
?>
