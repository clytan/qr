<?php
// manual_verify_poll.php
// Manually verify a specific poll order to debug the issue

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../backend/dbconfig/connection.php';

// Hardcoded values from user request
$orderId = 'POLL_1766225804_5419';
$userId = 58;

echo "Starting manual verification for Order: $orderId, User: $userId\n";

// 1. Check DB for Poll
$stmt = $conn->prepare("SELECT id, payment_status, status FROM user_polls WHERE payment_id = ? AND user_id = ?");
$stmt->bind_param("si", $orderId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$poll = $result->fetch_assoc();

if (!$poll) {
    die("ERROR: Poll not found in DB.\n");
}

echo "Found Poll ID: " . $poll['id'] . "\n";
echo "Current DB Status: " . $poll['status'] . "\n";
echo "Current Payment Status: " . $poll['payment_status'] . "\n";

// 2. Call Cashfree API
$clientId = "1106277eab36909b950443d4c757726011";
$clientSecret = "cfsk_ma_prod_36fd9bb92f7bbb654f807b60d6b7c67c_244c3bc6";
$cashfreeBaseUrl = "https://api.cashfree.com/pg/";

$headers = array(
    "accept: application/json",
    "x-api-version: 2023-08-01",
    "x-client-id: $clientId",
    "x-client-secret: $clientSecret"
);

$url = $cashfreeBaseUrl . "orders/$orderId";
echo "Calling Cashfree API: $url\n";

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_TIMEOUT, 30);

$resp = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "API Response Code: $httpCode\n";
echo "API Response Body: $resp\n";

$cfData = json_decode($resp, true);

if (isset($cfData['order_status']) && $cfData['order_status'] === 'PAID') {
    echo "Cashfree says: PAID\n";
    
    // 3. Update DB
    $updateStmt = $conn->prepare("UPDATE user_polls SET payment_status = 'completed', status = 'active' WHERE id = ?");
    $updateStmt->bind_param("i", $poll['id']);
    
    if ($updateStmt->execute()) {
        echo "SUCCESS: Database updated to 'active' and 'completed'.\n";
    } else {
        echo "ERROR: Database update failed: " . $conn->error . "\n";
    }
} else {
    echo "FAILURE: Cashfree status is " . ($cfData['order_status'] ?? 'Unknown') . "\n";
}
?>
