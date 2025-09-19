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

if (!isset($data['message_id']) || !isset($data['reason'])) {
    echo json_encode(['status' => false, 'message' => 'Missing required parameters']);
    exit();
}

$message_id = $data['message_id'];
$reason = $data['reason'];

// Get message and community info
$sql = "SELECT cc.community_id, 
        EXISTS(SELECT 1 FROM community_members cm 
               WHERE cm.community_id = cc.community_id 
               AND cm.user_id = ? 
               AND cm.is_deleted = 0) as is_member
        FROM community_chat cc 
        WHERE cc.id = ? AND cc.is_deleted = 0";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $user_id, $message_id);
$stmt->execute();
$result = $stmt->get_result();
$message = $result->fetch_assoc();

if (!$message) {
    echo json_encode(['status' => false, 'message' => 'Message not found']);
    exit();
}

// Check if user is a member of the community
if (!$message['is_member']) {
    echo json_encode(['status' => false, 'message' => 'Only community members can report messages']);
    exit();
}

// Check if user has already reported this message
$sql = "SELECT 1 FROM message_reports 
        WHERE message_id = ? AND reported_by = ? 
        AND is_deleted = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $message_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['status' => false, 'message' => 'You have already reported this message']);
    exit();
}

// Insert report
$sql = "INSERT INTO message_reports (message_id, reported_by, reason) 
        VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iis', $message_id, $user_id, $reason);

if ($stmt->execute()) {
    // Success! You might want to notify moderators here
    echo json_encode(['status' => true, 'message' => 'Message reported successfully']);
} else {
    echo json_encode(['status' => false, 'message' => 'Failed to report message']);
}

$stmt->close();
$conn->close();