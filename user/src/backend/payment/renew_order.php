<?php
/**
 * Create Renewal Payment Order
 * Creates a Cashfree payment order for subscription renewal
 */

header('Content-Type: application/json');
session_start();
require_once('../dbconfig/connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'error' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data
$sql = "SELECT id, user_email, user_full_name, user_phone, user_tag, user_qr_id, created_on FROM user_user WHERE id = ? AND is_deleted = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['status' => false, 'error' => 'User not found']);
    exit();
}

// Tier prices (must match get_profile_data.php)
$TIER_PRICES = [
    'gold' => 9999,
    'silver' => 5555,
    'normal' => 999,
    'student' => 999
];

$user_tier = strtolower($user['user_tag'] ?? 'normal');
$amount = $TIER_PRICES[$user_tier] ?? 999;

// Cashfree credentials
$clientId = "1106277eab36909b950443d4c757726011";
$clientSecret = "cfsk_ma_prod_36fd9bb92f7bbb654f807b60d6b7c67c_244c3bc6";

$order_id = 'RENEW_' . time() . '_' . rand(1000, 9999);
$customer_id = 'CUST_' . $user_id . '_' . time();

// Build return URL dynamically
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$subdirectory = '/qr';
$returnURL = $protocol . '://' . $host . $subdirectory . '/user/src/backend/payment/return_renewal.php?order_id=' . $order_id;

// Create Cashfree order
$orderData = [
    "order_id" => $order_id,
    "order_amount" => $amount,
    "order_currency" => "INR",
    "customer_details" => [
        "customer_id" => $customer_id,
        "customer_name" => $user['user_full_name'] ?? 'User',
        "customer_email" => $user['user_email'],
        "customer_phone" => $user['user_phone'] ?? '9999999999'
    ],
    "order_meta" => [
        "return_url" => $returnURL
    ],
    "order_note" => "Subscription Renewal - " . ucfirst($user_tier) . " Tier"
];

$ch = curl_init('https://api.cashfree.com/pg/orders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-api-version: 2023-08-01',
    'x-client-id: ' . $clientId,
    'x-client-secret: ' . $clientSecret
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$orderResponse = json_decode($response, true);

if ($httpCode !== 200 || !isset($orderResponse['payment_session_id'])) {
    error_log("Cashfree renewal order error: " . $response);
    // Return detailed error for debugging
    $errorMessage = 'Failed to create payment order';
    if (isset($orderResponse['message'])) {
        $errorMessage = $orderResponse['message'];
    } elseif (isset($orderResponse['error'])) {
        $errorMessage = is_array($orderResponse['error']) ? json_encode($orderResponse['error']) : $orderResponse['error'];
    }
    echo json_encode([
        'status' => false, 
        'error' => $errorMessage,
        'debug' => [
            'http_code' => $httpCode,
            'response' => $orderResponse
        ]
    ]);
    exit();
}

// Store renewal data in session for processing after payment
$_SESSION['renewal_data'] = [
    'user_id' => $user_id,
    'order_id' => $order_id,
    'amount' => $amount,
    'tier' => $user_tier
];

echo json_encode([
    'status' => true,
    'session' => $orderResponse['payment_session_id'],
    'order_id' => $order_id,
    'amount' => $amount,
    'tier' => ucfirst($user_tier)
]);
?>
