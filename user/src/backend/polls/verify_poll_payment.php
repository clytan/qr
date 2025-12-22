<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once(__DIR__ . '/../../backend/dbconfig/connection.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cashfreeBaseUrl = "https://api.cashfree.com/pg/";
// Credentials
$clientId = "1106277eab36909b950443d4c757726011";
$clientSecret = "cfsk_ma_prod_36fd9bb92f7bbb654f807b60d6b7c67c_244c3bc6";

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method is allowed');
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['order_id'])) {
        throw new Exception('Order ID is required');
    }

    $orderId = $data['order_id'];
    $userId = $_SESSION['user_id'];

    // Verify order in our DB first
    $stmt = $conn->prepare("SELECT id, payment_status FROM user_polls WHERE payment_id = ? AND user_id = ?");
    $stmt->bind_param("si", $orderId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $poll = $result->fetch_assoc();

    if (!$poll) {
        throw new Exception("Poll not found for this order");
    }

    if ($poll['payment_status'] === 'completed') {
        echo json_encode(['status' => true, 'message' => 'Payment already verified', 'poll_id' => $poll['id']]);
        exit;
    }

    // Call Cashfree to verify
    $headers = array(
        "accept: application/json",
        "x-api-version: 2023-08-01",
        "x-client-id: $clientId",
        "x-client-secret: $clientSecret"
    );

    $cfResponse = requestWithHeader($cashfreeBaseUrl . "orders/$orderId", $headers);
    error_log("Cashfree Verification Response for $orderId: " . $cfResponse); // DEBUG LOG
    $cfData = json_decode($cfResponse, true);

    if (isset($cfData['order_status']) && $cfData['order_status'] === 'PAID') {
        error_log("Poll Verify: Status is PAID. Updating DB...");
        // Update DB
        $updateStmt = $conn->prepare("UPDATE user_polls SET payment_status = 'completed', status = 'active' WHERE id = ?");
        $updateStmt->bind_param("i", $poll['id']);
        
        if ($updateStmt->execute()) {
            error_log("Poll Verify: DB Updated for Poll ID " . $poll['id']);
            // Create invoice with GST if not exists
            $totalAmount = floatval($cfData['order_amount'] ?? 99.00);
            $baseAmount = round($totalAmount / 1.18, 2);
            $gstTotal = round($totalAmount - $baseAmount, 2);
            $cgst = round($gstTotal / 2, 2);
            $sgst = round($gstTotal / 2, 2);
            $gstRate = 0.18;
            $referenceId = $cfData['cf_order_id'] ?? '';
            
            $invoiceNumber = 'INV-POLL-' . date('Ymd') . '-' . str_pad($poll['id'], 4, '0', STR_PAD_LEFT);
            
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
                $invStmt = @$conn->prepare($invSql);
                if ($invStmt) {
                    $invStmt->bind_param('issdddddd', 
                        $userId, $invoiceNumber, $referenceId,
                        $baseAmount, $gstRate, $cgst, $sgst, $gstTotal, $totalAmount
                    );
                    if ($invStmt->execute()) {
                         error_log("Poll Verify: Invoice Created.");
                    } else {
                         error_log("Poll Verify: Invoice Insert Failed: " . $invStmt->error);
                    }
                    $invStmt->close();
                } else {
                    error_log("Poll Verify: Invoice Prepare Failed: " . $conn->error);
                }
            } else {
                error_log("Poll Verify: Invoice Already Exists.");
            }
            
            echo json_encode(['status' => true, 'message' => 'Payment verified and poll activated!', 'poll_id' => $poll['id']]);
        } else {
            error_log("Poll Verify: DB Update Failed: " . $updateStmt->error);
            throw new Exception("Database update failed");
        }
    } else {
        error_log("Poll Verify: Payment Status is " . ($cfData['order_status'] ?? 'Unknown'));
        throw new Exception("Payment not completed. Status: " . ($cfData['order_status'] ?? 'Unknown'));
    }

} catch (Exception $e) {
    error_log("Poll Verify Exception: " . $e->getMessage());
    echo json_encode(['status' => false, 'error' => $e->getMessage()]);
}

function requestWithHeader($url, $headers)
{
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);

    $resp = curl_exec($curl);
    
    // Check HTTP status code
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($httpCode >= 400) {
         curl_close($curl);
         throw new Exception("Gateway returned error code: $httpCode");
    }

    curl_close($curl);
    return $resp;
}
?>
