<?php
// manual_verify_poll.php
// Manually verify a payment ID against Cashfree

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../../backend/dbconfig/connection.php');

$orderId = $_GET['order_id'] ?? '';

echo "<h1>Manual Poll Payment Verification</h1>";
echo "<form method='GET'>
    <input type='text' name='order_id' placeholder='Enter Order ID (e.g. POLL_...)' value='" . htmlspecialchars($orderId) . "' style='width:300px;padding:5px;'>
    <button type='submit' style='padding:5px 10px;'>Verify</button>
</form><hr>";

if (empty($orderId)) {
    exit("Please enter an Order ID.");
}

$cashfreeBaseUrl = "https://api.cashfree.com/pg/";
// Credentials (HARDCODED FOR NOW AS IN OTHER FILES)
$clientId = "1106277eab36909b950443d4c757726011";
$clientSecret = "cfsk_ma_prod_36fd9bb92f7bbb654f807b60d6b7c67c_244c3bc6";

// 1. Check DB
$stmt = $conn->prepare("SELECT * FROM user_polls WHERE payment_id = ?");
$stmt->bind_param("s", $orderId);
$stmt->execute();
$poll = $stmt->get_result()->fetch_assoc();

if (!$poll) {
    echo "<p style='color:red'>Poll not found in Database with Order ID: $orderId</p>";
    exit;
}

echo "<h3>Database Status</h3>";
echo "Poll ID: " . $poll['id'] . "<br>";
echo "Current Payment Status: <b>" . $poll['payment_status'] . "</b><br>";
echo "Current Status: <b>" . $poll['status'] . "</b><br>";

// 2. Check Cashfree
echo "<h3>Cashfree API Check</h3>";
$headers = array(
    "accept: application/json",
    "x-api-version: 2023-08-01",
    "x-client-id: $clientId",
    "x-client-secret: $clientSecret"
);

function requestCashfree($url, $headers) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $resp = curl_exec($curl);
    if(curl_errno($curl)) return 'Error:'.curl_error($curl);
    curl_close($curl);
    return $resp;
}

$cfResponse = requestCashfree($cashfreeBaseUrl . "orders/$orderId", $headers);
$cfData = json_decode($cfResponse, true);

if (!isset($cfData['order_status'])) {
    echo "<p style='color:red'>Failed to fetch status from Cashfree or Invalid Order ID.</p>";
    echo "<pre>" . print_r($cfData, true) . "</pre>";
    exit;
}

echo "Cashfree Status: <b>" . $cfData['order_status'] . "</b><br>";
echo "Amount: " . ($cfData['order_amount'] ?? 'N/A') . "<br>";

// 3. Update if PAID
if ($cfData['order_status'] === 'PAID') {
    if ($poll['payment_status'] !== 'completed') {
        echo "<br><b>Attempting to update database...</b><br>";
        
        $updateStmt = $conn->prepare("UPDATE user_polls SET payment_status = 'completed', status = 'active' WHERE id = ?");
        $updateStmt->bind_param("i", $poll['id']);
        
        if ($updateStmt->execute()) {
            echo "<p style='color:green'>SUCCESS: Database updated to 'completed' and 'active'.</p>";
            
            // Try Invoice (Simplified)
            // Just basic insert if missing, assume schema is fixed now
             // Generate invoice number
            $invoiceNumber = 'INV-POLL-' . date('Ymd') . '-' . str_pad($poll['id'], 4, '0', STR_PAD_LEFT);
            $userId =$poll['user_id'];
            $totalAmount = $cfData['order_amount'];
            $baseAmount = round($totalAmount / 1.18, 2);
            $gstRate = 0.18;
            $gstTotal = round($totalAmount - $baseAmount, 2);
            $cgst = round($gstTotal / 2, 2);
            $sgst = round($gstTotal / 2, 2);
            $referenceId = $cfData['cf_order_id'] ?? '';
            
            // Check
            $check = $conn->query("SELECT id FROM user_invoice WHERE order_id = '$orderId'");
            if ($check->num_rows == 0) {
                 $invSql = "INSERT INTO user_invoice 
                           (user_id, invoice_number, order_id, payment_reference, 
                            amount, gst_rate, cgst, sgst, gst_total, total_amount, status, created_on, is_deleted) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Paid', NOW(), 0)";
                 $istmt = $conn->prepare($invSql);
                 $istmt->bind_param('isssdddddd', $userId, $invoiceNumber, $orderId, $referenceId, $baseAmount, $gstRate, $cgst, $sgst, $gstTotal, $totalAmount);
                 if($istmt->execute()){
                     echo "Invoice created.<br>";
                 } else {
                     echo "Invoice creation failed: " . $istmt->error . "<br>";
                 }
            } else {
                echo "Invoice already exists.<br>";
            }

        } else {
            echo "<p style='color:red'>FAILED: Could not update database.</p>";
        }
    } else {
        echo "<p style='color:blue'>Database is already up to date.</p>";
    }
} else {
    echo "<p style='color:orange'>Payment not successful at gateway. No action taken.</p>";
}
?>
