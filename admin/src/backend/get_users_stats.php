<?php
require_once('./dbconfig/connection.php');
header('Content-Type: application/json');

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

try {
    $query = "SELECT DATE(created_on) as date, COUNT(*) as user_count
              FROM user_user
              WHERE DATE(created_on) BETWEEN ? AND ?
              GROUP BY DATE(created_on)
              ORDER BY date ASC";
    $stmt = $conn->prepare($query);
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

    $stmt->bind_param('ss', $start_date, $end_date);
    if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);

    $result = $stmt->get_result();
    $data = [];
    $total_users = 0;
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $total_users += $row['user_count'];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'total_users' => $total_users
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>