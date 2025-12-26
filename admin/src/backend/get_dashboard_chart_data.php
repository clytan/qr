<?php
// admin/src/backend/get_dashboard_chart_data.php

require_once './dbconfig/connection.php';
session_start();

header('Content-Type: application/json');

// Check admin auth
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // 1. Get User Registrations (Last 30 Days)
    // We want a daily count. 
    // SQL: Group by DATE(created_on)
    
    $users_sql = "
        SELECT DATE(created_on) as date, COUNT(*) as count 
        FROM user_user 
        WHERE created_on >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
        GROUP BY DATE(created_on) 
        ORDER BY date ASC
    ";
    
    $stmt = $conn->prepare($users_sql);
    $stmt->execute();
    $user_results = $stmt->get_result();
    
    $user_data = [];
    while ($row = $user_results->fetch_assoc()) {
        $user_data[$row['date']] = $row['count'];
    }

    // 2. Get Community Creations (Last 30 Days)
    $comm_sql = "
        SELECT DATE(created_on) as date, COUNT(*) as count 
        FROM community
        WHERE created_on >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
        GROUP BY DATE(created_on) 
        ORDER BY date ASC
    ";
    
    $stmt = $conn->prepare($comm_sql);
    $stmt->execute();
    $comm_results = $stmt->get_result();
    
    $comm_data = [];
    while ($row = $comm_results->fetch_assoc()) {
        $comm_data[$row['date']] = $row['count'];
    }

    // Fill in missing dates with 0
    $final_dates = [];
    $final_user_counts = [];
    $final_comm_counts = [];

    // Loop last 30 days
    for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $final_dates[] = date('d M', strtotime($date)); // Format: 01 Jan
        
        $final_user_counts[] = isset($user_data[$date]) ? (int)$user_data[$date] : 0;
        $final_comm_counts[] = isset($comm_data[$date]) ? (int)$comm_data[$date] : 0;
    }

    echo json_encode([
        'status' => true,
        'data' => [
            'labels' => $final_dates,
            'users' => $final_user_counts,
            'communities' => $final_comm_counts
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>
