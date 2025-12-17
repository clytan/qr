<?php
require_once('../dbconfig/connection.php');

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['request_id']) || !isset($input['action'])) {
    echo json_encode(['status' => false, 'message' => 'Missing parameters']);
    exit();
}

$request_id = (int) $input['request_id'];
$action = $input['action']; // 'approve' or 'reject'
$admin_notes = isset($input['admin_notes']) ? $input['admin_notes'] : '';

if (!in_array($action, ['approve', 'reject'])) {
    echo json_encode(['status' => false, 'message' => 'Invalid action']);
    exit();
}

$new_status = ($action === 'approve') ? 'approved' : 'rejected';

$sql = "UPDATE supercharge_requests SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssi', $new_status, $admin_notes, $request_id);

if ($stmt->execute()) {
    echo json_encode(['status' => true, 'message' => 'Request ' . $new_status . ' successfully']);
} else {
    echo json_encode(['status' => false, 'message' => 'Failed to update request']);
}

$stmt->close();
$conn->close();
?>
