<?php
// Add or update allowed_urls for a user
require_once './dbconfig/connection.php';
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;
$allowed_urls = isset($input['allowed_urls']) ? $input['allowed_urls'] : '';

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user_id']);
    exit;
}

// Check if entry exists (prepared statement)
$stmt = $conn->prepare("SELECT id FROM admin_urls WHERE user_id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param('i', $user_id);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
    $stmt->close();
    exit;
}
$result = $stmt->get_result();
if ($result === false) {
    echo json_encode(['success' => false, 'error' => 'Get result failed: ' . $stmt->error]);
    $stmt->close();
    exit;
}

if($result->num_rows > 0) {
    // Update
    $stmt->close();
    $stmt2 = $conn->prepare("UPDATE admin_urls SET allowed_urls=? WHERE user_id=?");
    if (!$stmt2) {
        echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    $stmt2->bind_param('si', $allowed_urls, $user_id);
    $success = $stmt2->execute();
    if (!$success) {
        echo json_encode(['success' => false, 'error' => 'Update failed: ' . $stmt2->error]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Permissions updated']);
    }
    $stmt2->close();
} else {
    // Insert
    $stmt->close();
    $stmt2 = $conn->prepare("INSERT INTO admin_urls (user_id, allowed_urls) VALUES (?, ?)");
    if (!$stmt2) {
        echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    $stmt2->bind_param('is', $user_id, $allowed_urls);
    $success = $stmt2->execute();
    if (!$success) {
        echo json_encode(['success' => false, 'error' => 'Insert failed: ' . $stmt2->error]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Permissions added']);
    }
    $stmt2->close();
}
?>