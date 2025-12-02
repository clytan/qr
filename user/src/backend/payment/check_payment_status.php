<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

date_default_timezone_set("Asia/Kolkata");

require_once('../dbconfig/connection.php');

if (!isset($_GET['orderId'])) {
    echo json_encode(['status' => 'error', 'message' => 'Order ID missing']);
    exit;
}

$order_id = $_GET['orderId'];
error_log("Payment status check for order: " . $order_id);

// Cashfree credentials
$clientId = "1106277eab36909b950443d4c757726011";
$clientSecret = "cfsk_ma_prod_36fd9bb92f7bbb654f807b60d6b7c67c_244c3bc6";
$cashfreeBaseUrl = "https://api.cashfree.com/pg/";

$headers = array(
    "accept: application/json",
    "x-api-version: 2023-08-01",
    "x-client-id: " . $clientId,
    "x-client-secret: " . $clientSecret
);

try {
    $ch = curl_init($cashfreeBaseUrl . "orders/" . $order_id);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    error_log("Cashfree payment status response - HTTP Code: " . $httpCode);
    error_log("Cashfree payment status response - Body: " . $response);

    if ($httpCode == 200) {
        $orderData = json_decode($response, true);

        if (isset($orderData['order_status'])) {
            error_log("Order status: " . $orderData['order_status']);

            if ($orderData['order_status'] === 'PAID') {
                echo json_encode([
                    'status' => 'PAID',
                    'message' => 'Payment successful',
                    'order_id' => $order_id,
                    'order_data' => $orderData
                ]);
            } else {
                echo json_encode([
                    'status' => $orderData['order_status'],
                    'message' => 'Payment status: ' . $orderData['order_status'],
                    'order_id' => $order_id
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'unknown',
                'message' => 'Could not determine payment status',
                'order_id' => $order_id
            ]);
        }
    } else {
        error_log("Cashfree API error. HTTP Code: " . $httpCode . ", Error: " . $curlError);
        echo json_encode([
            'status' => 'error',
            'message' => 'Could not verify payment status',
            'http_code' => $httpCode
        ]);
    }
} catch (Exception $e) {
    error_log("Exception in check_payment_status: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>