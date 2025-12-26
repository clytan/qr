<?php
// admin/src/backend/global_search.php

require_once './dbconfig/connection.php';
session_start();

header('Content-Type: application/json');

// Check admin auth
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit();
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode(['status' => false, 'message' => 'Search query too short']);
    exit();
}

try {
    $results = [
        'users' => [],
        'communities' => []
    ];

    $searchTerm = "%$query%";

    // 1. Search Users (Name, Email, Phone, QR ID)
    $user_sql = "
        SELECT id, user_full_name, user_email, user_phone, user_qr_id, user_image_path, created_on 
        FROM user_user 
        WHERE user_full_name LIKE ? 
           OR user_email LIKE ? 
           OR user_phone LIKE ? 
           OR user_qr_id LIKE ? 
        ORDER BY created_on DESC 
        LIMIT 5
    ";
    
    $stmt = $conn->prepare($user_sql);
    $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results['users'][] = $row;
    }

    // 2. Search Communities (Name only)
    $comm_sql = "
        SELECT id, name as community_name, created_on as created_at 
        FROM community 
        WHERE name LIKE ? 
        ORDER BY created_on DESC 
        LIMIT 5
    ";

    $stmt = $conn->prepare($comm_sql);
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results['communities'][] = $row;
    }

    echo json_encode(['status' => true, 'data' => $results]);

} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>
