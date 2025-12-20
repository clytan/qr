<?php
/**
 * Return handler for subscription renewal
 * Verifies payment and updates user's created_on date
 */

session_start();
require_once('../dbconfig/connection.php');

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    header('Location: ../../ui/profile.php?renewal=error&msg=missing_order');
    exit();
}

// Cashfree credentials
$clientId = "1106277eab36909b950443d4c757726011";
$clientSecret = "cfsk_ma_prod_36fd9bb92f7bbb654f807b60d6b7c67c_244c3bc6";

// Verify payment with Cashfree
$ch = curl_init("https://api.cashfree.com/pg/orders/$order_id");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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

$orderData = json_decode($response, true);

if ($httpCode !== 200 || !isset($orderData['order_status'])) {
    error_log("Cashfree renewal verify error: " . $response);
    header('Location: ../../ui/profile.php?renewal=error&msg=verify_failed');
    exit();
}

if ($orderData['order_status'] !== 'PAID') {
    header('Location: ../../ui/profile.php?renewal=error&msg=not_paid');
    exit();
}

// Get renewal data from session
$renewal_data = $_SESSION['renewal_data'] ?? null;
if (!$renewal_data || $renewal_data['order_id'] !== $order_id) {
    error_log("Renewal data mismatch for order: $order_id");
    header('Location: ../../ui/profile.php?renewal=error&msg=session_expired');
    exit();
}

$user_id = $renewal_data['user_id'];
$amount = $renewal_data['amount'];
$tier = $renewal_data['tier'];

try {
    $conn->begin_transaction();
    
    // Get current expiry date
    $sqlUser = "SELECT created_on FROM user_user WHERE id = ?";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param('i', $user_id);
    $stmtUser->execute();
    $userData = $stmtUser->get_result()->fetch_assoc();
    $stmtUser->close();
    
    // Calculate new expiry: original expiry + 1 year
    $old_created = new DateTime($userData['created_on']);
    $old_expiry = clone $old_created;
    $old_expiry->add(new DateInterval('P1Y'));
    
    $now = new DateTime();
    
    // If expired, new subscription starts from now
    // If not expired, extend from old expiry
    if ($now > $old_expiry) {
        $new_created = $now;
    } else {
        // User renewed early - calculate new created_on such that new expiry = old_expiry + 1 year
        $new_created = clone $old_expiry;
    }
    
    // Update created_on to extend subscription
    $sqlUpdate = "UPDATE user_user SET created_on = ?, updated_on = NOW() WHERE id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $new_created_str = $new_created->format('Y-m-d H:i:s');
    $stmtUpdate->bind_param('si', $new_created_str, $user_id);
    $stmtUpdate->execute();
    $stmtUpdate->close();
    
    // Create invoice
    $base_amount = round($amount / 1.18, 2);
    $cgst = round(($base_amount * 9.0) / 100, 2);
    $sgst = round(($base_amount * 9.0) / 100, 2);
    $gst_total = $cgst + $sgst;
    
    $invoice_number = 'RINV' . date('Ymd') . '-' . str_pad($user_id, 3, '0', STR_PAD_LEFT);
    $payment_id = $orderData['payments'][0]['cf_payment_id'] ?? $order_id;
    
    $sqlInvoice = "INSERT INTO user_invoice (user_id, invoice_number, invoice_type, amount, cgst, sgst, igst, gst_total, total_amount, status, payment_mode, payment_reference, created_on, updated_on, is_deleted) 
                   VALUES (?, ?, 'renewal', ?, ?, ?, 0, ?, ?, 'Paid', 'UPI', ?, NOW(), NOW(), 0)";
    $stmtInvoice = $conn->prepare($sqlInvoice);
    $stmtInvoice->bind_param('isddddds', $user_id, $invoice_number, $base_amount, $cgst, $sgst, $gst_total, $amount, $payment_id);
    $stmtInvoice->execute();
    $stmtInvoice->close();
    
    // Insert into user_renewal table
    $old_expiry_str = $old_expiry->format('Y-m-d H:i:s');
    $new_expiry = clone $new_created;
    $new_expiry->add(new DateInterval('P1Y'));
    $new_expiry_str = $new_expiry->format('Y-m-d H:i:s');
    
    $sqlRenewal = "INSERT INTO user_renewal (user_id, order_id, amount, tier, payment_status, payment_reference, old_expiry_date, new_expiry_date, created_on, updated_on, is_deleted) 
                   VALUES (?, ?, ?, ?, 'paid', ?, ?, ?, NOW(), NOW(), 0)";
    $stmtRenewal = $conn->prepare($sqlRenewal);
    if ($stmtRenewal) {
        $stmtRenewal->bind_param('isdssss', $user_id, $order_id, $amount, $tier, $payment_id, $old_expiry_str, $new_expiry_str);
        $stmtRenewal->execute();
        $stmtRenewal->close();
    }
    
    $conn->commit();
    
    // Clear session data
    unset($_SESSION['renewal_data']);
    
    error_log("Subscription renewed successfully for user $user_id, new created_on: $new_created_str, new expiry: $new_expiry_str");
    
    header('Location: ../../ui/profile.php?renewal=success');
    exit();
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Renewal error: " . $e->getMessage());
    header('Location: ../../ui/profile.php?renewal=error&msg=db_error');
    exit();
}
?>
