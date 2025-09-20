<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Not logged in']);
    exit();
}

include_once('./dbconfig/connection.php');
require_once('check_user_penalties.php');
$user_id = $_SESSION['user_id'];

// Get POST data and file
$community_id = isset($_POST['community_id']) ? intval($_POST['community_id']) : null;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (!$community_id) {
    echo json_encode(['status' => false, 'message' => 'Community ID required']);
    exit();
}

// Check if there's either a message or an attachment
if (empty($message) && !isset($_FILES['attachment'])) {
    echo json_encode(['status' => false, 'message' => 'Message or attachment required']);
    exit();
}

// Handle file upload
$attachment_path = null;
$attachment_name = null;

if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/community/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file = $_FILES['attachment'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];

    if (!in_array($ext, $allowed_types)) {
        echo json_encode(['status' => false, 'message' => 'Invalid file type']);
        exit();
    }

    $attachment_name = $file['name'];
    $unique_name = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $attachment_path = 'uploads/community/' . $unique_name;

    if (!move_uploaded_file($file['tmp_name'], $upload_dir . $unique_name)) {
        echo json_encode(['status' => false, 'message' => 'Failed to upload file']);
        exit();
    }
}

// Verify user belongs to this community and isn't banned/timed out
$sqlCheck = "SELECT 1 FROM community_members WHERE user_id = ? AND community_id = ? AND is_deleted = 0";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param('ii', $user_id, $community_id);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck->num_rows === 0) {
    echo json_encode(['status' => false, 'message' => 'Access denied']);
    $stmtCheck->close();
    exit();
}
$stmtCheck->close();

// Check if user is banned or in timeout
$penaltyCheck = checkUserPenalties($user_id, $community_id, $conn);
if (!$penaltyCheck['status']) {
    echo json_encode(['status' => false, 'message' => $penaltyCheck['message']]);
    exit();
}

// Insert message
$now = date('Y-m-d H:i:s');
$sql = "INSERT INTO community_chat (community_id, user_id, message, attachment_path, attachment_name, created_by, created_on, updated_by, updated_on, is_deleted) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";

$stmt = $conn->prepare($sql);
$stmt->bind_param('iisssissi', $community_id, $user_id, $message, $attachment_path, $attachment_name, $user_id, $now, $user_id, $now);

if ($stmt->execute()) {
    echo json_encode([
        'status' => true,
        'message' => 'Message sent successfully'
    ]);
} else {
    echo json_encode([
        'status' => false,
        'message' => 'Failed to send message'
    ]);
}

$stmt->close();
$conn->close();