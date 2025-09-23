<?php
require_once('./dbconfig/connection.php');

header('Content-Type: application/json');

// Get date range parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

try {
    // Query to get daily revenue stats
    $query = "SELECT 
                DATE(created_on) as date,
                SUM(amount) as earnings,
                SUM(gst_total) as gst,
                SUM(total_amount) as total_revenue,
                COUNT(*) as order_count
            FROM user_invoice 
            WHERE DATE(created_on) BETWEEN ? AND ?
            GROUP BY DATE(created_on)
            ORDER BY date ASC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param('ss', $start_date, $end_date);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    // Calculate totals and percentage change
    $total_revenue = 0;
    $total_earnings = 0;
    $total_gst = 0;
    
    foreach ($data as $row) {
        $total_revenue += $row['total_revenue'];
        $total_earnings += $row['earnings'];
        $total_gst += $row['gst'];
    }
    
    // Get previous period stats for comparison
    $days_diff = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
    $prev_start = date('Y-m-d', strtotime($start_date . " -{$days_diff} days"));
    $prev_end = date('Y-m-d', strtotime($start_date . ' -1 day'));
    
    $prev_query = "SELECT SUM(total_amount) as prev_total FROM user_invoice 
                  WHERE DATE(created_on) BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($prev_query);
    if (!$stmt) {
        throw new Exception("Prepare failed for previous query: " . $conn->error);
    }

    $stmt->bind_param('ss', $prev_start, $prev_end);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed for previous query: " . $stmt->error);
    }

    $prev_result = $stmt->get_result();
    $prev_data = $prev_result->fetch_assoc();
    $prev_total = $prev_data['prev_total'] ?: 0;
    
    // Calculate percentage change
    $percent_change = $prev_total > 0 ? (($total_revenue - $prev_total) / $prev_total * 100) : 100;
    
    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'summary' => [
            'total_revenue' => $total_revenue,
            'total_earnings' => $total_earnings,
            'total_gst' => $total_gst,
            'percent_change' => round($percent_change, 2)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>