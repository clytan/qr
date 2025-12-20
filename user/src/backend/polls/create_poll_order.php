<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

date_default_timezone_set("Asia/Kolkata");

require_once(__DIR__ . '/../../backend/dbconfig/connection.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cashfreeBaseUrl = "https://api.cashfree.com/pg/";
// Use HTTPS for production, HTTP for localhost
$protocol = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') ? 'http' : 'https';
$baseURL = $protocol . "://" . $_SERVER['HTTP_HOST'];

// Adjust path if running in a subdirectory (like XAMPP /qr/)
$uri = $_SERVER['REQUEST_URI'];
$projectDir = '';
if (strpos($uri, '/qr/') !== false) {
    $projectDir = '/qr';
}

$notifyURL = $baseURL . $projectDir . "/user/src/backend/payment/callback_poll.php"; 
// For now, let's assume we use a generic return or poll specific return
$returnURL = $baseURL . $projectDir . "/user/src/backend/payment/return_poll.php"; 

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method is allowed');
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }

    $pollId = isset($data['poll_id']) ? intval($data['poll_id']) : 0;
    
    if ($pollId <= 0) {
        throw new Exception("Invalid Poll ID");
    }

    // Verify Poll Ownership and Status
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, title, payment_status FROM user_polls WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $pollId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $poll = $result->fetch_assoc();

    if (!$poll) {
        throw new Exception("Poll not found or you don't have permission");
    }

    if ($poll['payment_status'] === 'completed') {
        throw new Exception("Payment already completed for this poll");
    }

    // Amount is fixed ₹99
    // Amount is fixed ₹99
    $amount = 1.00; // TESTING: Set to 1
    $customerName = $_SESSION['user_name'] ?? 'User';
    $customerEmail = $_SESSION['user_email'] ?? 'user@example.com';
    $customerPhone = $_SESSION['user_phone'] ?? '9999999999';

    // Cashfree Credentials
    $clientId = "1106277eab36909b950443d4c757726011"; 
    $clientSecret = "cfsk_ma_prod_36fd9bb92f7bbb654f807b60d6b7c67c_244c3bc6"; 

    $order_id = 'POLL_' . time() . '_' . rand(1000, 9999);
    $customer_id = 'CUST_' . $userId;

    // Store order_id in poll table
    $updateStmt = $conn->prepare("UPDATE user_polls SET payment_id = ? WHERE id = ?");
    $updateStmt->bind_param("si", $order_id, $pollId);
    $updateStmt->execute();

    // Create Order
    $paymentData = createOrder(
        $clientId,
        $clientSecret,
        $order_id,
        $customer_id,
        $customerName,
        $customerEmail,
        $customerPhone,
        $amount,
        $returnURL,
        $notifyURL
    );

    echo json_encode($paymentData);

} catch (Exception $e) {
    error_log("Poll Order Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['status' => false, 'error' => $e->getMessage()]);
}

function createExpiryTime()
{
    return date_format(date_timestamp_set(new DateTime(), strtotime("+16 Minutes", strtotime(date('Y-m-d H:i:s')))), 'c');
}

function requestWithHeader($url, $headers, $data)
{
    $curl = curl_init($url);
    $jsonData = json_encode($data);

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // TODO: Enable in prod
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);

    $resp = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    if ($resp === false) {
        throw new Exception('CURL Error: ' . curl_error($curl));
    }
    
    if ($httpCode >= 400) {
        $errorDetails = json_decode($resp, true);
        $msg = isset($errorDetails['message']) ? $errorDetails['message'] : $resp;
        throw new Exception('Gateway Error (' . $httpCode . '): ' . $msg);
    }

    curl_close($curl);
    return $resp;
}

function createOrder($clientId, $clientSecret, $orderId, $customerId, $name, $email, $number, $amount, $returnURL, $notifyURL)
{
    global $cashfreeBaseUrl;

    if (strlen($number) == 10 && is_numeric($number)) {
        $number = '+91' . $number;
    }

    $headers = array(
        "accept: application/json",
        "content-type: application/json",
        "x-api-version: 2023-08-01",
        "x-client-id: $clientId",
        "x-client-secret: $clientSecret"
    );

    $postData = array(
        'order_id' => $orderId,
        'order_amount' => $amount,
        'order_currency' => 'INR',
        'customer_details' => array(
            'customer_id' => $customerId,
            'customer_name' => $name,
            'customer_email' => $email,
            'customer_phone' => $number
        ),
        'order_meta' => array(
            'return_url' => $returnURL . '?order_id={order_id}',
            'notify_url' => $notifyURL
        ),
        'order_expiry_time' => createExpiryTime()
    );

    $response = requestWithHeader($cashfreeBaseUrl . 'orders', $headers, $postData);
    $response = json_decode($response, true);

    $arr = array();
    if (isset($response['payment_session_id'])) {
        $arr['status'] = true;
        $arr['session'] = $response['payment_session_id'];
        $arr['order_id'] = $orderId;
    } else {
        $arr['status'] = false;
        $arr['error'] = isset($response['message']) ? $response['message'] : 'Unknown gateway error';
    }
    return $arr;
}
?>
