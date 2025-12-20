<?php
// callback_poll.php
// Handles server-to-server webhook notifications from Cashfree for Polls

error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't output errors to the gateway

require_once(__DIR__ . '/../../backend/dbconfig/connection.php');

// Log the incoming request for debugging
$rawPost = file_get_contents('php://input');
error_log("Cashfree Poll Webhook Received: " . $rawPost);

// Decode headers/body if needed (Cashfree sends POST data)
$data = $_POST;
if (empty($data)) {
    $data = json_decode($rawPost, true);
}

$orderId = $data['order_id'] ?? $data['orderId'] ?? null;
$txStatus = $data['txStatus'] ?? $data['order_status'] ?? null;
$referenceId = $data['cf_payment_id'] ?? $data['referenceId'] ?? null;
$orderAmount = floatval($data['order_amount'] ?? $data['orderAmount'] ?? 99);

if (!$orderId) {
    error_log("Poll Webhook Error: No Order ID found");
    exit;
}

if ($txStatus === 'SUCCESS' || $txStatus === 'PAID') {
    // 1. Find the poll
    $stmt = $conn->prepare("SELECT p.id, p.user_id, p.title, p.payment_status FROM user_polls p WHERE p.payment_id = ?");
    $stmt->bind_param("s", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $poll = $result->fetch_assoc();

    if ($poll && $poll['payment_status'] !== 'completed') {
        // 2. Mark as completed and active
        $updateStmt = $conn->prepare("UPDATE user_polls SET payment_status = 'completed', status = 'active' WHERE id = ?");
        $updateStmt->bind_param("i", $poll['id']);
        $updateStmt->execute();
        error_log("Poll Webhook Success: Poll " . $poll['id'] . " activated.");
        
        // 3. Calculate GST and create invoice
        // Total amount is ₹99 (inclusive of GST)
        // Base Amount = 99 / 1.18 = 83.90
        // GST 18% = 99 - 83.90 = 15.10 (9% CGST + 9% SGST)
        $totalAmount = $orderAmount > 0 ? $orderAmount : 99.00;
        $baseAmount = round($totalAmount / 1.18, 2);
        $gstRate = 0.18;
        $gstTotal = round($totalAmount - $baseAmount, 2);
        $cgst = round($gstTotal / 2, 2);
        $sgst = round($gstTotal / 2, 2);
        
        // Generate invoice number
        $invoiceNumber = 'INV-POLL-' . date('Ymd') . '-' . str_pad($poll['id'], 4, '0', STR_PAD_LEFT);
        
        // Check if invoice already exists
        $checkInv = $conn->prepare("SELECT id FROM user_invoice WHERE order_id = ?");
        $checkInv->bind_param("s", $orderId);
        $checkInv->execute();
        $existingInv = $checkInv->get_result()->fetch_assoc();
        $checkInv->close();
        
        if (!$existingInv) {
            // Create invoice
            $invSql = "INSERT INTO user_invoice 
                       (user_id, invoice_number, invoice_type, order_id, payment_reference, 
                        amount, gst_rate, cgst, sgst, gst_total, total_amount, status, created_on, is_deleted) 
                       VALUES (?, ?, 'poll', ?, ?, ?, ?, ?, ?, ?, ?, 'Paid', NOW(), 0)";
            $invStmt = $conn->prepare($invSql);
            if ($invStmt) {
                $invStmt->bind_param('isssddddd d', 
                    $poll['user_id'], $invoiceNumber, $orderId, $referenceId,
                    $baseAmount, $gstRate, $cgst, $sgst, $gstTotal, $totalAmount
                );
                if ($invStmt->execute()) {
                    error_log("Poll Invoice created: " . $invoiceNumber);
                } else {
                    error_log("Poll Invoice creation failed: " . $invStmt->error);
                }
                $invStmt->close();
            } else {
                error_log("Poll Invoice prepare failed: " . $conn->error);
            }
        }
        
    } else {
        error_log("Poll Webhook: Poll not found or already paid. Order: $orderId");
    }
} else {
    error_log("Poll Webhook: Transaction not success. Status: " . ($txStatus ?? 'Unknown'));
}
?>
