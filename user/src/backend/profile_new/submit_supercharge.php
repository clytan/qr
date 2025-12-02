<?php
include '../dbconfig/connection.php';
header('Content-Type: application/json');

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if user is Gold (user_tag = 'gold' or user_slab_id = 3)
$check_sql = "SELECT user_tag, user_slab_id FROM user_user WHERE id = ? AND is_deleted = 0";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('i', $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

// Check if user is gold (adjust condition based on your database structure)
$is_gold = (strtolower($user['user_tag']) === 'gold') || ($user['user_slab_id'] == 3);

if (!$is_gold) {
    echo json_encode(['success' => false, 'message' => 'Only Gold members can submit supercharge requests']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$supercharge_link = isset($data['supercharge_link']) ? trim($data['supercharge_link']) : '';

// Validate link
if (empty($supercharge_link)) {
    echo json_encode(['success' => false, 'message' => 'Supercharge link is required']);
    exit();
}

// Validate URL format
if (!filter_var($supercharge_link, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid URL']);
    exit();
}

// Check total number of links for this user (including all statuses)
$count_sql = "SELECT COUNT(*) as total FROM supercharge_requests WHERE user_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param('i', $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_data = $count_result->fetch_assoc();

// Optional: Set a limit on total links (e.g., 10)
if ($count_data['total'] >= 10) {
    echo json_encode(['success' => false, 'message' => 'You have reached the maximum limit of 10 supercharge links']);
    exit();
}
$count_stmt->close();


// Insert supercharge request
$insert_sql = "INSERT INTO supercharge_requests (user_id, supercharge_link, status) VALUES (?, ?, 'pending')";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param('is', $user_id, $supercharge_link);

if ($insert_stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Supercharge link submitted successfully! Admin will review it soon.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit supercharge link. Please try again.']);
}

$insert_stmt->close();
$check_stmt->close();
$conn->close();
?>
