<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

date_default_timezone_set("Asia/Kolkata");

error_log("========== order.php called ==========");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Raw input: " . file_get_contents('php://input'));

require_once('./session_config.php');
require_once('../dbconfig/connection.php');

// ============================================
// CONFIGURATION: Choose your testing method
// ============================================

// Option A: Using ngrok (RECOMMENDED - works with Cashfree sandbox)
// 1. Download ngrok from https://ngrok.com/download
// 2. Run: ngrok http 8000
// 3. Copy the HTTPS URL (e.g., https://abc123.ngrok.io)
// 4. Uncomment line below and paste your ngrok URL
// $NGROK_URL = "https://YOUR_NGROK_URL.ngrok.io"; // PASTE YOUR NGROK URL HERE

// Option B: Using localhost (may cause 403 on Cashfree payment page)
// Keep $NGROK_URL commented to use localhost
// ============================================

$cashfreeBaseUrl = "https://api.cashfree.com/pg/";

// Automatically use ngrok URL if set, otherwise use localhost
if (isset($NGROK_URL) && !empty($NGROK_URL)) {
    $baseURL = $NGROK_URL;
    error_log("Using ngrok URL: " . $baseURL);
} else {
    // Use HTTPS for production, HTTP for localhost
    $protocol = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') ? 'http' : 'https';
    $baseURL = $protocol . "://" . $_SERVER['HTTP_HOST'];
    error_log("Using URL: " . $baseURL);
}

$notifyURL = $baseURL . "/user/src/backend/payment/callback.php";
$returnURL = $baseURL . "/user/src/backend/payment/return.php";

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method is allowed');
    }

    // CLEAR any old session data first
    $_SESSION['registration_data'] = null;
    $_SESSION['order_id'] = null;
    unset($_SESSION['registration_data']);
    unset($_SESSION['order_id']);

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }

    $required_params = ['full_name', 'email', 'phone', 'password', 'user_type', 'user_slab', 'amount'];
    foreach ($required_params as $param) {
        if (!isset($data[$param]) || empty($data[$param])) {
            throw new Exception("Missing or empty parameter: $param");
        }
    }

    // ============================================
    // SECURITY: Hardcoded tier prices - NEVER trust frontend amounts
    // ============================================
    // TESTING: All prices set to 1 (Original: normal=999, silver=5555, gold=9999, student_leader=999)
    $TIER_PRICES = [
        'normal' => 999,          // Original: 999
        'silver' => 5555,          // Original: 5555
        'gold' => 9999,            // Original: 9999
        'student_leader' => 999   // Original: 999
    ];

    // Get user_tag from frontend (gold/silver/normal)
    $userTag = isset($data['user_tag']) ? strtolower(trim($data['user_tag'])) : 'normal';
    
    // Check if student leader (overrides tier selection)
    $isStudentLeader = isset($data['student_leader']) && $data['student_leader'] === 'yes';
    
    // Determine the correct tier for pricing
    if ($isStudentLeader) {
        $pricingTier = 'student_leader';
    } else if (in_array($userTag, ['gold', 'silver', 'normal'])) {
        $pricingTier = $userTag;
    } else {
        $pricingTier = 'normal'; // Default fallback
    }
    
    // Get the HARDCODED price for this tier (NEVER use frontend amount directly)
    $originalAmount = $TIER_PRICES[$pricingTier];
    
    // Validate that frontend sent the correct amount (detect tampering)
    $frontendAmount = floatval($data['amount']);
    if ($frontendAmount != $originalAmount) {
        error_log("⚠️ SECURITY WARNING: Frontend amount ($frontendAmount) doesn't match tier price ($originalAmount) for tier: $pricingTier");
        // We'll use our hardcoded amount anyway, but log the discrepancy
    }
    
    error_log("✓ Tier: $pricingTier, Hardcoded Price: ₹$originalAmount");

    // Your Cashfree credentials - replace with actual credentials
    $clientId = "1106277eab36909b950443d4c757726011"; // Replace with your client ID
    $clientSecret = "cfsk_ma_prod_36fd9bb92f7bbb654f807b60d6b7c67c_244c3bc6"; // Replace with your client secret

    $order_id = 'REG_' . time() . '_' . rand(1000, 9999);
    $customer_id = 'CUST_' . time() . '_' . rand(1000, 9999);

    // Handle promo code if provided
    $promoCode = isset($data['promo_code']) ? strtoupper(trim($data['promo_code'])) : null;
    $discountAmount = 0;
    $finalAmount = $originalAmount;

    if ($promoCode) {
        // Validate and apply promo code
        $sqlPromo = "SELECT * FROM promo_codes 
                     WHERE code = ? 
                     AND is_active = 1 
                     AND (valid_from IS NULL OR valid_from <= NOW()) 
                     AND (valid_until IS NULL OR valid_until >= NOW())
                     LIMIT 1";
        
        $stmtPromo = $conn->prepare($sqlPromo);
        $stmtPromo->bind_param('s', $promoCode);
        $stmtPromo->execute();
        $promoResult = $stmtPromo->get_result();
        
        if ($promoResult->num_rows > 0) {
            $promo = $promoResult->fetch_assoc();
            
            // Check usage limit
            if ($promo['current_uses'] < $promo['max_uses']) {
                // Check minimum amount
                if ($originalAmount >= $promo['min_amount']) {
                    // Calculate discount
                    if ($promo['discount_type'] === 'percentage') {
                        $discountAmount = ($originalAmount * $promo['discount_value']) / 100;
                        if ($promo['max_discount'] !== null && $discountAmount > $promo['max_discount']) {
                            $discountAmount = $promo['max_discount'];
                        }
                    } else {
                        $discountAmount = $promo['discount_value'];
                        if ($discountAmount > $originalAmount) {
                            $discountAmount = $originalAmount;
                        }
                    }
                    
                    $discountAmount = round($discountAmount, 2);
                    $finalAmount = round($originalAmount - $discountAmount, 2);
                    
                    error_log("✓ Promo code applied: $promoCode - Discount: ₹$discountAmount");
                }
            }
        }
        $stmtPromo->close();
    }

    // Store registration data with promo code info in DATABASE
    $registrationJson = json_encode($data);
    $sqlStore = "INSERT INTO user_pending_registration (order_id, registration_data, status, promo_code, discount_amount, original_amount) VALUES (?, ?, 'pending', ?, ?, ?)";
    $stmtStore = $conn->prepare($sqlStore);

    if (!$stmtStore) {
        error_log("Failed to prepare statement: " . $conn->error);
        throw new Exception('Database error: Unable to store registration data');
    }

    $stmtStore->bind_param('sssdd', $order_id, $registrationJson, $promoCode, $discountAmount, $originalAmount);

    if (!$stmtStore->execute()) {
        error_log("Failed to store registration data: " . $stmtStore->error);
        throw new Exception('Database error: Unable to save registration data - ' . $stmtStore->error);
    }

    error_log("✓ Registration data stored in database for order: " . $order_id);
    error_log("  Email: " . $data['email']);
    error_log("  Original Amount: ₹" . $originalAmount);
    error_log("  Discount: ₹" . $discountAmount);
    error_log("  Final Amount: ₹" . $finalAmount);

    $stmtStore->close();

    // Also store in session as backup (but don't rely on it)
    $_SESSION['registration_data'] = $data;
    $_SESSION['order_id'] = $order_id;

    $data = createOrder(
        $clientId,
        $clientSecret,
        $order_id,
        $customer_id,
        $data['full_name'],
        $data['email'],
        $data['phone'],
        $finalAmount  // Use discounted amount for payment
    );

    echo json_encode($data);
} catch (Exception $e) {
    error_log("❌ Exception in order.php: " . $e->getMessage());
    error_log("Exception trace: " . $e->getTraceAsString());
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

        // Properly encode JSON
        $jsonData = json_encode($data);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);

        // SSL and timeout settings
        // For local development, disable SSL verification
        // TODO: Enable SSL verification in production!
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);

        // Follow redirects
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 3);

        // Add user agent to avoid CloudFront blocks
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        error_log('Cashfree API Request - URL: ' . $url);
        error_log('Cashfree API Request - Headers: ' . json_encode($headers));
        error_log('Cashfree API Request - Data: ' . $jsonData);

        $resp = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        $curlErrno = curl_errno($curl);

        error_log('Cashfree API Response - HTTP Code: ' . $httpCode);
        error_log('Cashfree API Response - Body: ' . $resp);

        if ($resp === false) {
            curl_close($curl);
            throw new Exception('CURL Error (' . $curlErrno . '): ' . $curlError);
        }

        if ($httpCode == 403) {
            curl_close($curl);
            throw new Exception('Access denied by payment gateway (403). Please verify API credentials and ensure your IP/domain is whitelisted in Cashfree dashboard.');
        }

        if ($httpCode >= 400) {
            curl_close($curl);
            $errorDetails = json_decode($resp, true);
            $errorMsg = isset($errorDetails['message']) ? $errorDetails['message'] : $resp;
            throw new Exception('Payment gateway error (' . $httpCode . '): ' . $errorMsg);
        }

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

    // Ensure phone number has country code (Cashfree requirement)
    if (strlen($number) == 10 && is_numeric($number)) {
        $number = '+91' . $number; // Add India country code
    } elseif (strlen($number) == 10) {
        $number = '91' . $number;
    }

    // Ensure amount is numeric and formatted correctly
    $amount = floatval($amount);

    error_log("Creating Cashfree order - ID: $orderId, Amount: $amount, Phone: $number");

    $headers = array(
        "accept: application/json",
        "content-type: application/json",
        "x-api-version: 2023-08-01",
        "x-client-id: $clientId",
        "x-client-secret: $clientSecret"
    );

    // Build proper nested array structure for Cashfree API
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
            'return_url' => $returnURL . '?orderId={order_id}',
            'notify_url' => $notifyURL,
            'payment_methods' => 'upi,cc,dc,nb,app'
        ),
        'order_expiry_time' => createExpiryTime()
    );

    error_log("Cashfree request data: " . json_encode($postData));

    try {
        $response = requestWithHeader($cashfreeBaseUrl . 'orders', $headers, $postData);
        $response = json_decode($response, true);

        error_log("Cashfree response: " . json_encode($response));

        $arr = array();
        if (isset($response['payment_session_id'])) {
            $arr['status'] = true;
            $arr['session'] = $response['payment_session_id'];
            $arr['order_id'] = $orderId;
            error_log("Cashfree order created successfully: " . $response['payment_session_id']);
        } else {
            $arr['status'] = false;
            $arr['error'] = isset($response['message']) ? $response['message'] : 'Unknown error from payment gateway';
            $arr['cashfree_response'] = $response;
            error_log('Cashfree Error Response: ' . json_encode($response));
        }
        return $arr;
    } catch (Exception $e) {
        error_log('Exception in createOrder: ' . $e->getMessage());
        return array(
            'status' => false,
            'error' => 'Payment gateway error: ' . $e->getMessage()
        );
    }
}
?>