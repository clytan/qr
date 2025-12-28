<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'dbconfig/connection.php';

echo "<h2>Diagnostic User Data Dump</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
echo "<tr style='background:#ddd;'>
        <th>ID</th>
        <th>Name</th>
        <th>Tag</th>
        <th>Type</th>
        <th>Slab ID</th>
        <th>College Name</th>
        <th>Referral Code (QR ID)</th>
      </tr>";

$sql = "SELECT id, user_full_name, user_tag, user_user_type, user_slab_id, college_name, user_qr_id FROM user_user ORDER BY id DESC LIMIT 20";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['user_full_name'] . "</td>";
        echo "<td>" . ($row['user_tag'] ?: '[NULL]') . "</td>";
        echo "<td>" . $row['user_user_type'] . " (1=Ind, 2=Creator, 3=Biz)</td>";
        echo "<td>" . $row['user_slab_id'] . "</td>";
        echo "<td>" . ($row['college_name'] ?: '[NULL/Empty]') . "</td>";
        echo "<td>" . $row['user_qr_id'] . "</td>";
        echo "</tr>";
    }
} else {
    echo "Query failed: " . $conn->error;
}
echo "</table>";
?>
