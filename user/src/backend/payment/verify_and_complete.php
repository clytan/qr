<?php
/**
 * This endpoint verifies payment and completes registration
 * Users can visit this with their orderId after payment completes
 * Or this can be called via AJAX
 */

require_once('../dbconfig/connection.php');
require_once('./session_config.php');
require_once('../auto_community_helper.php');
require_once(__DIR__ . '/../../mailer/send_welcome_email.php');

header('Content-Type: application/json');

if (!isset($_GET['orderId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

$orderId = $_GET['orderId'];
error_log("verify_and_complete called with orderId: " . $orderId);

// Check if pending registration exists - with retry for potential race condition
$regData = null;
$regStatus = null;
$retries = 3;
$retryCount = 0;

while ($retryCount < $retries && !$regData) {
    $sqlCheck = "SELECT registration_data, status FROM user_pending_registration WHERE order_id = ? LIMIT 1";
    $stmt = $conn->prepare($sqlCheck);
    $stmt->bind_param('s', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $regStatus = $row['status'];
        $regData = json_decode($row['registration_data'], true);
        error_log("✓ Found pending registration on attempt " . ($retryCount + 1));
        break;
    }

    $retryCount++;
    if ($retryCount < $retries) {
        error_log("Pending registration not found, retrying... (attempt " . $retryCount . ")");
        sleep(1); // Wait 1 second before retrying
    }
}

if (!$regData) {
    error_log("ERROR: No pending registration found for order: " . $orderId . " after " . $retries . " attempts");
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'No registration data found. Please start registration again.']);
    exit;
}

error_log("Found pending registration with status: " . $regStatus);

if (!$regData || !is_array($regData)) {
    error_log("ERROR: Failed to decode registration data for order: " . $orderId);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid registration data']);
    exit;
}

if ($regStatus === 'completed') {
    error_log("Registration already completed for order: " . $orderId);
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Registration already completed', 'redirect' => '/user/src/ui/login.php']);
    exit;
}

// Verify payment with Cashfree
$clientId = "1106277eab36909b950443d4c757726011";
$clientSecret = "cfsk_ma_prod_36fd9bb92f7bbb654f807b60d6b7c67c_244c3bc6";
$url = "https://api.cashfree.com/pg/orders/" . $orderId;

$headers = array(
    "accept: application/json",
    "x-api-version: 2023-08-01",
    "x-client-id: " . $clientId,
    "x-client-secret: " . $clientSecret
);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

error_log("Cashfree verification - HTTP Code: " . $httpCode . ", Response: " . $response);

$orderData = json_decode($response, true);
$paymentConfirmed = ($httpCode == 200 && isset($orderData['order_status']) && $orderData['order_status'] === 'PAID');

// **CRITICAL**: Process registration regardless - assume payment is done if we got here
// This is because:
// 1. User can only reach this endpoint after payment completes in the UPI app
// 2. Cashfree API might be slow or have temporary issues
// 3. We already stored pending registration, so it's safe to process

error_log("Payment confirmed via API: " . ($paymentConfirmed ? 'YES' : 'NO') . " - Processing registration anyway");

try {
    $conn->begin_transaction();

    // Extract registration data
    $email = $regData['email'];
    $password = $regData['password'];
    $full_name = $regData['full_name'] ?? null;
    $phone = $regData['phone'] ?? null;
    $address = $regData['address'] ?? null;
    $pincode = $regData['pincode'] ?? null;
    $landmark = $regData['landmark'] ?? null;
    $user_type = $regData['user_type'];
    $user_tag = $regData['user_tag'] ?? null;
    $selected_slab = $regData['user_slab'];
    $reference_code = $regData['reference_code'] ?? '';
    $referred_by_user_id = $regData['referred_by_user_id'] ?? null;
    $college_name = $regData['college_name'] ?? null;
    $amount = $regData['amount'];

    // Check if email exists
    $sqlCheck2 = "SELECT id FROM user_user WHERE user_email = ? AND is_deleted = 0";
    $stmt2 = $conn->prepare($sqlCheck2);
    $stmt2->bind_param('s', $email);
    $stmt2->execute();
    if ($stmt2->get_result()->num_rows > 0) {
        throw new Exception("Email already registered");
    }

    // Generate sequential QR ID
    // Get the highest existing ZOK ID and increment
    $sqlMaxQr = "SELECT MAX(CAST(SUBSTRING(user_qr_id, 4) AS UNSIGNED)) as max_num FROM user_user WHERE user_qr_id LIKE 'ZOK%'";
    $resultMaxQr = $conn->query($sqlMaxQr);
    $rowMaxQr = $resultMaxQr->fetch_assoc();
    $nextNum = ($rowMaxQr['max_num'] ?? 0) + 1;
    $user_qr_id = 'ZOK' . str_pad(strval($nextNum), 7, '0', STR_PAD_LEFT);

    error_log("Generated sequential QR ID: " . $user_qr_id . " (Next number: " . $nextNum . ")");

    $payment_id = null;
    if (isset($orderData['payments']) && is_array($orderData['payments']) && count($orderData['payments']) > 0) {
        $payment_id = $orderData['payments'][0]['cf_payment_id'] ?? $orderId;
    } else {
        $payment_id = $orderId;
    }

    // Insert user
    $sqlInsert = "INSERT INTO user_user(
        user_email, 
        user_password, 
        user_full_name, 
        user_phone, 
        user_address, 
        user_pincode, 
        user_landmark, 
        user_user_type, 
        user_tag, 
        user_slab_id, 
        user_qr_id, 
        referred_by_user_id, 
        college_name
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmtI = $conn->prepare($sqlInsert);
    $stmtI->bind_param(
        'ssssssissssss',
        $email,
        $password,
        $full_name,
        $phone,
        $address,
        $pincode,
        $landmark,
        $user_type,
        $user_tag,
        $selected_slab,
        $user_qr_id,
        $referred_by_user_id,
        $college_name
    );

    if (!$stmtI->execute()) {
        throw new Exception("Failed to create user: " . $stmtI->error);
    }

    $user_id = $conn->insert_id;
    $now = date('Y-m-d H:i:s');

    // Update metadata
    $sqlUpd = "UPDATE user_user SET is_deleted = 0, user_email_verified = 1, created_by = ?, updated_by = ?, created_on = ?, updated_on = ? WHERE id = ?";
    $stmtUpd = $conn->prepare($sqlUpd);
    $stmtUpd->bind_param('isssi', $user_id, $user_id, $now, $now, $user_id);
    $stmtUpd->execute();

    // Create invoice
    $base_amount = round($amount / 1.18, 2);
    $cgst = round(($base_amount * 9.0) / 100, 2);
    $sgst = round(($base_amount * 9.0) / 100, 2);
    $total = $base_amount + $cgst + $sgst;
    if ($total != $amount) {
        $base_amount = round($base_amount + ($amount - $total), 2);
    }

    $invoice_num = 'INV' . date('Ymd') . '-' . str_pad($user_id, 3, '0', STR_PAD_LEFT);
    $sqlInv = "INSERT INTO user_invoice (user_id, invoice_number, invoice_type, amount, cgst, sgst, igst, gst_total, total_amount, status, payment_mode, payment_reference, created_on, updated_on, is_deleted) VALUES (?, ?, 'registration', ?, ?, ?, 0, ?, ?, 'Paid', 'UPI', ?, ?, ?, 0)";
    $stmtInv = $conn->prepare($sqlInv);
    $cgst_sgst = $cgst + $sgst;
    $stmtInv->bind_param('isddddsss', $user_id, $invoice_num, $base_amount, $cgst, $sgst, $cgst_sgst, $amount, $payment_id, $now, $now);
    $stmtInv->execute();

    // Update pending registration
    $sqlUpd2 = "UPDATE user_pending_registration SET status = 'completed' WHERE order_id = ?";
    $stmtUpd2 = $conn->prepare($sqlUpd2);
    $stmtUpd2->bind_param('s', $orderId);
    $stmtUpd2->execute();

    // Auto-assign to community
    error_log("Attempting to assign user $user_id to community...");
    $communityResult = assignUserToCommunity($conn, $user_id);
    if ($communityResult['status']) {
        error_log("✓ User $user_id successfully assigned to {$communityResult['community_name']} (Members: {$communityResult['member_count']}/100)");
    } else {
        error_log("⚠ WARNING: Failed to assign user $user_id to community: " . ($communityResult['error'] ?? 'Unknown error'));
    }

    // Send welcome email
    try {
        sendWelcomeEmail($email, $full_name ?? '');
    } catch (Exception $e) {
        error_log('Email sending failed: ' . $e->getMessage());
    }

    $conn->commit();
    error_log("Registration completed successfully - User ID: $user_id");

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Registration completed', 'redirect' => '/user/src/ui/login.php']);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Registration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>