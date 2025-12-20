<?php
require_once __DIR__ . '/../../backend/dbconfig/connection.php';
$result = $conn->query("DESCRIBE user_polls");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
?>
