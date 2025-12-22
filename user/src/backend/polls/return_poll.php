<?php
/**
 * Cashfree Payment Return Handler for Polls
 * This page receives the redirect from Cashfree after payment
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once(__DIR__ . '/../../backend/dbconfig/connection.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get order_id from URL (Cashfree adds this automatically)
$orderId = $_GET['order_id'] ?? '';

if (empty($orderId)) {
    header("Location: /user/src/ui/polls.php?error=invalid_order");
    exit;
}

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /user/src/ui/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Log for debugging
error_log("Return Handler: Processing order $orderId for user $userId");

// Cashfree API Configuration
$cashfreeBaseUrl = "https://api.cashfree.com/pg/";
$clientId = "1106277eab36909b950443d4c757726011";
$clientSecret = "cfsk_ma_prod_36fd9bb92f7bbb654f807b60d6b7c67c_244c3bc6";

try {
    // 1. Verify this order belongs to this user
    $stmt = $conn->prepare("SELECT id, user_id, title, payment_status FROM user_polls WHERE payment_id = ?");
    $stmt->bind_param("s", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $poll = $result->fetch_assoc();
    $stmt->close();

    if (!$poll) {
        error_log("Return Handler: Poll not found for order $orderId");
        header("Location: /user/src/ui/polls.php?error=poll_not_found");
        exit;
    }

    if ($poll['user_id'] != $userId) {
        error_log("Return Handler: User mismatch for order $orderId");
        header("Location: /user/src/ui/polls.php?error=unauthorized");
        exit;
    }

    // If already completed, just redirect
    if ($poll['payment_status'] === 'completed') {
        error_log("Return Handler: Payment already completed for order $orderId");
        header("Location: /user/src/ui/polls.php?success=already_verified");
        exit;
    }

    // 2. Verify payment with Cashfree
    $headers = array(
        "accept: application/json",
        "x-api-version: 2023-08-01",
        "x-client-id: $clientId",
        "x-client-secret: $clientSecret"
    );

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $cashfreeBaseUrl . "orders/$orderId");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($httpCode !== 200) {
        error_log("Return Handler: Cashfree API error for order $orderId - HTTP $httpCode");
        throw new Exception("Payment verification failed");
    }

    $cfData = json_decode($response, true);
    error_log("Return Handler: Cashfree response for $orderId: " . json_encode($cfData));

    // 3. Check payment status
    if (!isset($cfData['order_status'])) {
        throw new Exception("Invalid response from payment gateway");
    }

    $paymentStatus = $cfData['order_status'];

    if ($paymentStatus === 'PAID') {
        // 4. Update poll status
        $conn->begin_transaction();

        $updateStmt = $conn->prepare("UPDATE user_polls SET payment_status = 'completed', status = 'active', updated_on = NOW() WHERE id = ?");
        $updateStmt->bind_param("i", $poll['id']);
        
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update poll status");
        }
        $updateStmt->close();

        // 5. Create invoice with GST
        $totalAmount = floatval($cfData['order_amount'] ?? 99.00);
        $baseAmount = round($totalAmount / 1.18, 2);
        $gstTotal = round($totalAmount - $baseAmount, 2);
        $cgst = round($gstTotal / 2, 2);
        $sgst = round($gstTotal / 2, 2);
        $gstRate = 0.18;
        $referenceId = $cfData['cf_order_id'] ?? '';
        $invoiceNumber = 'INV-POLL-' . date('Ymd') . '-' . str_pad($poll['id'], 4, '0', STR_PAD_LEFT);

        // Check if invoice already exists
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
            
            if (!$invStmt->execute()) {
                error_log("Return Handler: Invoice creation failed for order $orderId: " . $invStmt->error);
            }
            $invStmt->close();
        }

        $conn->commit();

        error_log("Return Handler: Successfully verified and activated poll {$poll['id']} for order $orderId");
        
        // Redirect to polls page with success message
        header("Location: /user/src/ui/polls.php?verified=success&poll_id=" . $poll['id']);
        exit;

    } elseif (in_array($paymentStatus, ['FAILED', 'CANCELLED', 'USER_DROPPED'])) {
        error_log("Return Handler: Payment $paymentStatus for order $orderId");
        header("Location: /user/src/ui/polls.php?error=payment_failed&status=$paymentStatus");
        exit;
    } else {
        // PENDING or other status
        error_log("Return Handler: Payment still PENDING for order $orderId");
        header("Location: /user/src/ui/polls.php?pending=true&order_id=$orderId");
        exit;
    }

} catch (Exception $e) {
    if ($conn->in_transaction) {
        $conn->rollback();
    }
    error_log("Return Handler Exception: " . $e->getMessage());
    header("Location: /user/src/ui/polls.php?error=verification_failed");
    exit;
}
?>