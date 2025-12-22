<?php
/**
 * Cashfree Payment Webhook/Callback Handler for Polls
 * This is called asynchronously by Cashfree server when payment status changes
 * 
 * IMPORTANT: This runs independently of user session
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

// Log all incoming requests
$logFile = __DIR__ . '/../../logs/callback_poll.log';
$logDir = dirname($logFile);
if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
}

function logCallback($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Get raw POST data
$rawData = file_get_contents('php://input');
logCallback("Callback received: " . $rawData);

require_once(__DIR__ . '/../../backend/dbconfig/connection.php');

// Cashfree sends JSON data
$data = json_decode($rawData, true);

if (!$data) {
    logCallback("ERROR: Invalid JSON data");
    http_response_code(400);
    exit;
}

// Cashfree webhook structure
$orderId = $data['data']['order']['order_id'] ?? null;
$orderStatus = $data['data']['order']['order_status'] ?? null;
$orderAmount = $data['data']['order']['order_amount'] ?? 0;

if (!$orderId) {
    logCallback("ERROR: No order_id in callback data");
    http_response_code(400);
    exit;
}

logCallback("Processing callback for Order: $orderId, Status: $orderStatus");

try {
    // Find the poll
    $stmt = $conn->prepare("SELECT id, user_id, title, payment_status FROM user_polls WHERE payment_id = ?");
    $stmt->bind_param("s", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $poll = $result->fetch_assoc();
    $stmt->close();

    if (!$poll) {
        logCallback("ERROR: Poll not found for order $orderId");
        http_response_code(404);
        exit;
    }

    $pollId = $poll['id'];
    $userId = $poll['user_id'];

    // If already processed, skip
    if ($poll['payment_status'] === 'completed') {
        logCallback("INFO: Payment already completed for poll $pollId");
        http_response_code(200);
        echo json_encode(['status' => 'already_processed']);
        exit;
    }

    // Process based on status
    if ($orderStatus === 'PAID') {
        logCallback("SUCCESS: Payment PAID for order $orderId, activating poll $pollId");

        $conn->begin_transaction();

        // Update poll status
        $updateStmt = $conn->prepare("UPDATE user_polls SET payment_status = 'completed', status = 'active', updated_on = NOW() WHERE id = ?");
        $updateStmt->bind_param("i", $pollId);
        
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update poll status: " . $updateStmt->error);
        }
        $updateStmt->close();

        // Create invoice with GST
        $totalAmount = floatval($orderAmount);
        $baseAmount = round($totalAmount / 1.18, 2);
        $gstTotal = round($totalAmount - $baseAmount, 2);
        $cgst = round($gstTotal / 2, 2);
        $sgst = round($gstTotal / 2, 2);
        $gstRate = 0.18;
        $referenceId = $data['data']['payment']['cf_payment_id'] ?? '';
        $invoiceNumber = 'INV-POLL-' . date('Ymd') . '-' . str_pad($pollId, 4, '0', STR_PAD_LEFT);

        // Check if invoice exists
        $checkInv = $conn->prepare("SELECT id FROM user_invoice WHERE invoice_number = ?");
        $checkInv->bind_param("s", $invoiceNumber);
        $checkInv->execute();
        $existingInv = $checkInv->get_result()->fetch_assoc();
        $checkInv->close();

        if (!$existingInv) {
            $invSql = "INSERT INTO user_invoice 
                       (user_id, invoice_number, payment_reference, 
                        amount, gst_rate, cgst, sgst, gst_total, total_amount, status, created_on, is_deleted) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Paid', NOW(), 0)";
            $invStmt = $conn->prepare($invSql);
            $invStmt->bind_param('issdddddd', 
                $userId, $invoiceNumber, $referenceId,
                $baseAmount, $gstRate, $cgst, $sgst, $gstTotal, $totalAmount
            );
            
            if ($invStmt->execute()) {
                logCallback("SUCCESS: Invoice created for poll $pollId");
            } else {
                logCallback("WARNING: Invoice creation failed: " . $invStmt->error);
            }
            $invStmt->close();
        }

        $conn->commit();
        logCallback("SUCCESS: Poll $pollId activated successfully");

        http_response_code(200);
        echo json_encode(['status' => 'success', 'poll_id' => $pollId]);

    } elseif (in_array($orderStatus, ['FAILED', 'CANCELLED', 'USER_DROPPED'])) {
        logCallback("INFO: Payment $orderStatus for order $orderId");
        
        // Mark as failed
        $updateStmt = $conn->prepare("UPDATE user_polls SET payment_status = 'failed', status = 'pending_payment', updated_on = NOW() WHERE id = ?");
        $updateStmt->bind_param("i", $pollId);
        $updateStmt->execute();
        $updateStmt->close();

        http_response_code(200);
        echo json_encode(['status' => 'payment_failed']);

    } else {
        logCallback("INFO: Payment status $orderStatus for order $orderId - no action taken");
        http_response_code(200);
        echo json_encode(['status' => 'pending']);
    }

} catch (Exception $e) {
    if ($conn->in_transaction) {
        $conn->rollback();
    }
    logCallback("EXCEPTION: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

logCallback("Callback processing completed for $orderId");
?>