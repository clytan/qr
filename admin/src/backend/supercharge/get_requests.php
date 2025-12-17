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

    // Get stats
    $sqlStats = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                 FROM supercharge_requests";
    $statsResult = $conn->query($sqlStats);
    $stats = $statsResult->fetch_assoc();

    echo json_encode(['status' => true, 'data' => $requests, 'stats' => $stats]);

} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
