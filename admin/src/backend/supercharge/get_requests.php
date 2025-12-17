<?php
require_once('../dbconfig/connection.php');

header('Content-Type: application/json');

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit();
}

$status = isset($_GET['status']) ? $_GET['status'] : 'all';

$sql = "SELECT sr.*, u.user_full_name, u.user_email, u.user_qr_id 
        FROM supercharge_requests sr 
        JOIN user_user u ON sr.user_id = u.id ";

if ($status !== 'all') {
    $sql .= "WHERE sr.status = ? ";
}

$sql .= "ORDER BY sr.created_at DESC";

try {
    if ($status !== 'all') {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $status);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }

    echo json_encode(['status' => true, 'data' => $requests]);

} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
