<?php
// callback_poll.php
// Handles server-to-server webhook notifications from Cashfree

error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't output errors to the gateway

require_once(__DIR__ . '/../../backend/dbconfig/connection.php');

// Log the incoming request for debugging
$rawPost = file_get_contents('php://input');
error_log("Cashfree Webhook Received: " . $rawPost);

// Decode headers/body if needed (Cashfree sends POST data)
$data = $_POST;
if (empty($data)) {
    $data = json_decode($rawPost, true);
}

// Cashfree Notify Parameters usually include:
// orderId, orderAmount, referenceId, txStatus, paymentMode, msg, signature...
// Note: Variable names depend on the API version. Old API used txStatus, New API uses webhooks with different payload.
// Since we used createOrder (PG), the notify_url receives the POST parameters.

$orderId = $data['order_id'] ?? $data['orderId'] ?? null;
$txStatus = $data['txStatus'] ?? $data['order_status'] ?? null;

if (!$orderId) {
    error_log("Webhook Error: No Order ID found");
    exit;
}

if ($txStatus === 'SUCCESS' || $txStatus === 'PAID') {
    // 1. Find the poll
    $stmt = $conn->prepare("SELECT id, payment_status FROM user_polls WHERE payment_id = ?");
    $stmt->bind_param("s", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $poll = $result->fetch_assoc();

    if ($poll && $poll['payment_status'] !== 'completed') {
        // 2. Mark as completed and active
        $updateStmt = $conn->prepare("UPDATE user_polls SET payment_status = 'completed', status = 'active' WHERE id = ?");
        $updateStmt->bind_param("i", $poll['id']);
        $updateStmt->execute();
        error_log("Webhook Success: Poll " . $poll['id'] . " activated.");
    } else {
        error_log("Webhook: Poll not found or already paid. Order: $orderId");
    }
} else {
    error_log("Webhook: Transaction not success. Status: " . ($txStatus ?? 'Unknown'));
}
?>
