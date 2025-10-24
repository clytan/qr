<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

date_default_timezone_set("Asia/Kolkata");

require_once('./session_config.php');

// Change these URLs to match your setup
$cashfreeBaseUrl = "https://sandbox.cashfree.com/pg/";
$notifyURL = "http://" . $_SERVER['HTTP_HOST'] . "/qr/user/src/backend/payment/callback.php";
$returnURL = "http://" . $_SERVER['HTTP_HOST'] . "/qr/user/src/backend/payment/return.php";

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method is allowed');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }

    $required_params = ['email', 'password', 'user_type', 'user_slab', 'amount'];
    foreach ($required_params as $param) {
        if (!isset($data[$param]) || empty($data[$param])) {
            throw new Exception("Missing or empty parameter: $param");
        }
    }

    // Your Cashfree credentials - replace with actual credentials
    $clientId = "TEST10846745c5a8303d342dc718d3fd54764801"; // Replace with your client ID
    $clientSecret = "cfsk_ma_test_0f8d48d6e963a3ff6c8005964e961bab_f925b695"; // Replace with your client secret

    $order_id = 'REG_' . time() . '_' . rand(1000, 9999);
    $customer_id = 'CUST_' . time() . '_' . rand(1000, 9999);

    // Store registration data in session for later use
    session_start();

    // Store the data in session
    $_SESSION['registration_data'] = $data;
    $_SESSION['order_id'] = $order_id;

    error_log("Storing registration data in session: " . print_r($data, true));
    error_log("Session ID: " . session_id());

    // Ensure session data is written immediately
    session_write_close();

    $data = createOrder(
        $clientId,
        $clientSecret,
        $order_id,
        $customer_id,
        $data['email'], // Using email as name since we don't have name field
        $data['email'],
        $data['phone'] ?? '0000000000', // Phone is optional in our form
        $data['amount']
    );

    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => false, 'error' => $e->getMessage()]);
}

function createExpiryTime()
{
    return date_format(date_timestamp_set(new DateTime(), strtotime("+16 Minutes", strtotime(date('Y-m-d H:i:s')))), 'c');
}

function requestWithHeader($url, $headers, $data)
{
    try {
        $curl = curl_init($url);
        if ($curl === false) {
            throw new Exception('Failed to initialize CURL');
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // For testing only
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, true));

        $resp = curl_exec($curl);

        if ($resp === false) {
            throw new Exception('CURL Error: ' . curl_error($curl));
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($httpCode >= 400) {
            throw new Exception('HTTP Error: ' . $httpCode . ' - ' . $resp);
        }

        error_log('Request URL: ' . $url);
        error_log('Request Headers: ' . json_encode($headers));
        error_log('Request Data: ' . json_encode($data));
        error_log('Response: ' . $resp);

        curl_close($curl);
        return $resp;
    } catch (Exception $e) {
        error_log('Error in requestWithHeader: ' . $e->getMessage());
        throw $e;
    }
}

function createOrder($clientId, $clientSecret, $orderId, $customerId, $name, $email, $number, $amount)
{
    global $cashfreeBaseUrl, $notifyURL, $returnURL;
    $headers = array(
        "accept: application/json",
        "content-type: application/json",
        "x-api-version: 2022-09-01",
        "x-client-id: $clientId",
        "x-client-secret: $clientSecret"
    );

    $postData = array();
    $postData['customer_details']['customer_id'] = $customerId;
    $postData['customer_details']['customer_name'] = $name;
    $postData['customer_details']['customer_email'] = $email;
    $postData['customer_details']['customer_phone'] = $number;
    $postData['order_meta']['return_url'] = $returnURL . '?orderId={order_id}';
    $postData['order_meta']['notify_url'] = $notifyURL;
    $postData['order_meta']['payment_methods'] = "upi";
    $postData['order_amount'] = $amount;
    $postData['order_id'] = $orderId;
    $postData['order_currency'] = "INR";
    $postData['order_expiry_time'] = createExpiryTime();
    $response = requestWithHeader($cashfreeBaseUrl . 'orders', $headers, $postData);
    $response = json_decode($response, true);

    $arr = array();
    if (isset($response['payment_session_id'])) {
        $arr['status'] = true;
        $arr['session'] = $response['payment_session_id'];
    } else {
        $arr['status'] = false;
        $arr['error'] = isset($response['message']) ? $response['message'] : 'Unknown error';
        error_log('Cashfree Error: ' . json_encode($response));
    }
    return $arr;
}
?>