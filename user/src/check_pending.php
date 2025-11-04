<?php
// Check pending registrations - WEB VERSION
require_once('./backend/dbconfig/connection.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Pending Registrations</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .box { background: #2d2d2d; padding: 15px; margin: 10px 0; border-left: 3px solid #4CAF50; }
        .error { border-left-color: #f44336; }
        .warning { border-left-color: #ff9800; }
        h2 { color: #4CAF50; }
        hr { border: 1px solid #444; }
    </style>
</head>
<body>
<h1>📊 Pending Registrations Check</h1>

<?php
echo "<h2>Recent Registrations (Last 5)</h2>";

$result = $conn->query("SELECT * FROM user_pending_registration ORDER BY id DESC LIMIT 5");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $statusClass = $row['status'] == 'pending' ? 'warning' : ($row['status'] == 'completed' ? 'box' : 'error');
        echo "<div class='$statusClass'>";
        echo "<strong>Order ID:</strong> {$row['order_id']}<br>";
        echo "<strong>Status:</strong> <span style='color: " . ($row['status']=='pending'?'#ff9800':'#4CAF50') . "'>{$row['status']}</span><br>";
        echo "<strong>Created:</strong> {$row['created_at']}<br>";
        
        $data = json_decode($row['registration_data'], true);
        if ($data) {
            echo "<strong>Email:</strong> {$data['email']}<br>";
            echo "<strong>Name:</strong> " . ($data['full_name'] ?? 'N/A') . "<br>";
            echo "<strong>Phone:</strong> " . ($data['phone'] ?? 'N/A') . "<br>";
        }
        echo "</div>";
    }
} else {
    echo "<div class='error'>❌ No pending registrations found.</div>";
}

// Check specific order
echo "<hr><h2>Specific Order Check</h2>";
$order_id = 'REG_1762269985_3181';
$stmt = $conn->prepare("SELECT * FROM user_pending_registration WHERE order_id = ?");
$stmt->bind_param('s', $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<div class='box'>";
    echo "✅ <strong>Found registration for order: $order_id</strong><br>";
    echo "Status: {$row['status']}<br>";
    echo "</div>";
    
    // Now manually process this registration
    echo "<hr><h2>🔄 Manual Processing Available</h2>";
    echo "<div class='warning'>";
    echo "<p>To manually complete this registration, visit:<br>";
    echo "<a href='manual_process.php?order_id=$order_id' style='color: #4CAF50;'>Process Registration Manually</a></p>";
    echo "</div>";
} else {
    echo "<div class='error'>❌ No registration found for order: $order_id</div>";
}

$conn->close();
?>

</body>
</html>
