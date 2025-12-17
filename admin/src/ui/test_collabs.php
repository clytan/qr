<?php
// Test script to check collaborations data
require_once('../components/auth_check.php');
require_once('../backend/dbconfig/connection.php');

echo "<h2>Database Connection Test</h2>";
echo "<p>Connection successful!</p>";

echo "<h3>All Collaborations in Database:</h3>";

$sql = "SELECT * FROM influencer_collabs ORDER BY created_on DESC";
$result = $conn->query($sql);

if ($result) {
    echo "<p>Total rows: " . $result->num_rows . "</p>";
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; padding: 5px;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Category</th><th>Status</th><th>Created On</th><th>Accepted By</th><th>Is Deleted</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['collab_title'] . "</td>";
            echo "<td>" . $row['category'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>" . $row['created_on'] . "</td>";
            echo "<td>" . ($row['accepted_by'] ?? 'NULL') . "</td>";
            echo "<td>" . $row['is_deleted'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No collaborations found in database.</p>";
    }
} else {
    echo "<p style='color: red;'>Query Error: " . $conn->error . "</p>";
}

echo "<h3>Test GET_COLLABS endpoint:</h3>";

// Test the get_collabs with different statuses
$test_statuses = ['all', 'pending', 'active', 'completed'];

foreach ($test_statuses as $test_status) {
    echo "<h4>Status: $test_status</h4>";
    
    $sql = "SELECT c.*, 
            u.user_full_name as influencer_name, 
            u.user_email as influencer_email
            FROM influencer_collabs c
            LEFT JOIN user_user u ON c.accepted_by = u.id
            WHERE c.is_deleted = 0";
    
    if ($test_status !== 'all') {
        $sql .= " AND c.status = '$test_status'";
    }
    
    $sql .= " ORDER BY c.created_on DESC";
    
    $result = $conn->query($sql);
    
    if ($result) {
        echo "<p>Found: " . $result->num_rows . " rows</p>";
    } else {
        echo "<p style='color: red;'>Error: " . $conn->error . "</p>";
    }
}

echo "<hr>";
echo "<a href='collaborations.php'>← Back to Collaborations</a>";
?>
