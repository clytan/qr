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

// Get all user's supercharge requests
$sql = "SELECT sr.id, sr.supercharge_link, sr.status, sr.admin_notes, sr.created_at, sr.updated_at 
        FROM supercharge_requests sr 
        WHERE sr.user_id = ? 
        ORDER BY sr.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

if (count($requests) > 0) {
    echo json_encode([
        'success' => true,
        'has_request' => true,
        'requests' => $requests
    ]);
} else {
    echo json_encode([
        'success' => true,
        'has_request' => false,
        'requests' => []
    ]);
}

$stmt->close();
$conn->close();
?>
