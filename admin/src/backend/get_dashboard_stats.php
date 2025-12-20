<?php
session_start();
require_once('./dbconfig/connection.php');

// Verify admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => false, 'message' => 'Not authenticated']);
    exit();
}

header('Content-Type: application/json');

try {
    $stats = [];
    
    // Total users
    $result = $conn->query("SELECT COUNT(*) as count FROM user_user WHERE is_deleted = 0");
    $stats['users'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Total communities
    $communityResult = @$conn->query("SELECT COUNT(*) as count FROM community");
    $stats['communities'] = $communityResult ? $communityResult->fetch_assoc()['count'] : 0;
    
    // Admin users
    $result = $conn->query("SELECT COUNT(*) as count FROM admin_user WHERE is_deleted = 0");
    $stats['admins'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Roles
    $result = $conn->query("SELECT COUNT(*) as count FROM admin_user_role");
    $stats['roles'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    echo json_encode(['status' => true, 'data' => $stats]);
    
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    echo json_encode(['status' => false, 'message' => 'Error fetching stats: ' . $e->getMessage()]);
}
?>
