<?php
require_once('../dbconfig/connection.php');
require_once('./session_config.php');

error_log("Return URL accessed - Query string: " . $_SERVER['QUERY_STRING']);

function processRegistration($data, $payment_id, $bank_reference, $order_id)
{
    global $conn;

    try {
        $conn->begin_transaction();
        error_log("Starting registration process with data: " . print_r($data, true));

        $email = $data['email'];
        $password = $data['password'];
        $user_type = $data['user_type'];
        $user_tag = $data['user_tag'] ?? null;
        $selected_slab = $data['user_slab'];
        $reference_code = $data['reference_code'] ?? '';
        $referred_by_user_id = $data['referred_by_user_id'] ?? null;
        $college_name = $data['college_name'] ?? null;
        $amount = $data['amount'];

        // Check if email already exists
        $checkEmailSql = "SELECT id FROM user_user WHERE user_email = ? AND is_deleted = 0";
        $stmtCheck = $conn->prepare($checkEmailSql);
        $stmtCheck->bind_param('s', $email);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        if ($resultCheck->num_rows > 0) {
            throw new Exception("Email already registered");
        }
        $stmtCheck->close();

        // Generate user_qr_id (ZOKLI_XXXXXX format)
        do {
            $random_digits = str_pad(strval(mt_rand(0, 999999)), 6, '0', STR_PAD_LEFT);
            $user_qr_id = 'ZOKLI_' . $random_digits;
            $sqlCheckQr = "SELECT 1 FROM user_user WHERE user_qr_id = ?";
            $stmtCheckQr = $conn->prepare($sqlCheckQr);
            $stmtCheckQr->bind_param('s', $user_qr_id);
            $stmtCheckQr->execute();
            $resultCheckQr = $stmtCheckQr->get_result();
            $exists = $resultCheckQr->num_rows > 0;
            $stmtCheckQr->close();
        } while ($exists);

        error_log("Generated QR ID: " . $user_qr_id);

        // Insert user
        $sqlInsert = "INSERT INTO user_user(user_email, user_password, user_user_type, user_tag, user_slab_id, user_qr_id, referred_by_user_id, college_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        if (!$stmtInsert) {
            throw new Exception("Failed to prepare user insert statement: " . $conn->error);
        }

        $stmtInsert->bind_param('ssisssis', $email, $password, $user_type, $user_tag, $selected_slab, $user_qr_id, $referred_by_user_id, $college_name);

        if (!$stmtInsert->execute()) {
            throw new Exception("Failed to create user: " . $stmtInsert->error);
        }

        $user_id = $conn->insert_id;
        error_log("User inserted with ID: " . $user_id);

        $now = date('Y-m-d H:i:s');

        // Update user metadata
        $sqlUpdate = "UPDATE user_user SET is_deleted = 0, user_email_verified = 1, created_by = ?, updated_by = ?, created_on = ?, updated_on = ? WHERE id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param('isssi', $user_id, $user_id, $now, $now, $user_id);
        if (!$stmtUpdate->execute()) {
            throw new Exception("Failed to update user metadata: " . $stmtUpdate->error);
        }

        // Create invoice
        $cgst = $amount * 0.09;
        $sgst = $amount * 0.09;
        $igst = 0.00;
        $gst_total = $cgst + $sgst + $igst;
        $total_amount = $amount + $gst_total;

        $invoice_number = 'INV' . date('Ymd') . '-' . str_pad($user_id, 3, '0', STR_PAD_LEFT);

        $sqlInvoice = "INSERT INTO user_invoice (user_id, invoice_number, invoice_type, amount, cgst, sgst, igst, gst_total, total_amount, status, payment_mode, payment_reference, created_on, updated_on, is_deleted) VALUES (?, ?, 'registration', ?, ?, ?, ?, ?, ?, 'Paid', 'UPI', ?, ?, ?, 0)";
        $stmtInvoice = $conn->prepare($sqlInvoice);
        $stmtInvoice->bind_param('isdddddssss', $user_id, $invoice_number, $amount, $cgst, $sgst, $igst, $gst_total, $total_amount, $payment_id, $now, $now);

        if (!$stmtInvoice->execute()) {
            throw new Exception("Failed to create invoice: " . $stmtInvoice->error);
        }

        error_log("Invoice created successfully");

        // Process referral if exists
        if ($referred_by_user_id) {
            error_log("Processing referral for user: " . $referred_by_user_id);
            processReferral($conn, $referred_by_user_id, $user_id);
        }

        $conn->commit();
        error_log("Registration completed successfully for user ID: " . $user_id);
        return ['status' => true, 'message' => 'Registration successful'];

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Registration error: " . $e->getMessage());
        return ['status' => false, 'message' => $e->getMessage()];
    }
}

function processReferral($conn, $referred_by_user_id, $new_user_id)
{
    $now = date('Y-m-d H:i:s');

    try {
        // Get referrer details
        $sqlReferrer = "SELECT id, user_slab_id FROM user_user WHERE user_qr_id = ? AND is_deleted = 0";
        $stmtReferrer = $conn->prepare($sqlReferrer);
        $stmtReferrer->bind_param('s', $referred_by_user_id);
        $stmtReferrer->execute();
        $resultReferrer = $stmtReferrer->get_result();

        if ($resultReferrer->num_rows > 0) {
            $referrer = $resultReferrer->fetch_assoc();
            $referrer_id = $referrer['id'];
            $referrer_slab_id = $referrer['user_slab_id'];

            // Get commission percentage
            $sqlSlab = "SELECT ref_commission FROM user_slab WHERE id = ?";
            $stmtSlab = $conn->prepare($sqlSlab);
            $stmtSlab->bind_param('i', $referrer_slab_id);
            $stmtSlab->execute();
            $resultSlab = $stmtSlab->get_result();

            if ($resultSlab->num_rows > 0) {
                $rowSlab = $resultSlab->fetch_assoc();
                $commission_percent = floatval($rowSlab['ref_commission']);

                // Calculate commission
                $base_amount = 100;
                $commission_amount = $base_amount * ($commission_percent / 100);

                // Update or create wallet
                $sqlWallet = "SELECT id, balance FROM user_wallet WHERE user_id = ? AND is_deleted = 0";
                $stmtWallet = $conn->prepare($sqlWallet);
                $stmtWallet->bind_param('i', $referrer_id);
                $stmtWallet->execute();
                $resultWallet = $stmtWallet->get_result();

                if ($resultWallet->num_rows > 0) {
                    $rowWallet = $resultWallet->fetch_assoc();
                    $new_balance = $rowWallet['balance'] + $commission_amount;
                    $wallet_id = $rowWallet['id'];

                    $sqlUpdateWallet = "UPDATE user_wallet SET balance = ?, updated_by = ?, updated_on = ? WHERE id = ?";
                    $stmtUpdateWallet = $conn->prepare($sqlUpdateWallet);
                    $stmtUpdateWallet->bind_param('disi', $new_balance, $referrer_id, $now, $wallet_id);
                    $stmtUpdateWallet->execute();
                } else {
                    $sqlInsertWallet = "INSERT INTO user_wallet (user_id, balance, created_by, created_on, updated_by, updated_on, is_deleted) VALUES (?, ?, ?, ?, ?, ?, 0)";
                    $stmtInsertWallet = $conn->prepare($sqlInsertWallet);
                    $stmtInsertWallet->bind_param('idisis', $referrer_id, $commission_amount, $referrer_id, $now, $referrer_id, $now);
                    $stmtInsertWallet->execute();
                }

                // Record transaction
                $description = "Referral commission for new user registration";
                $sqlTrans = "INSERT INTO user_wallet_transaction (user_id, amount, transaction_type, description, created_by, updated_by, created_on, updated_on, is_deleted) VALUES (?, ?, 'Referral', ?, ?, ?, ?, ?, 0)";
                $stmtTrans = $conn->prepare($sqlTrans);
                $stmtTrans->bind_param('idsiiss', $referrer_id, $commission_amount, $description, $referrer_id, $referrer_id, $now, $now);
                $stmtTrans->execute();

                error_log("Referral commission processed successfully for referrer ID: " . $referrer_id);
            }
        }
    } catch (Exception $e) {
        error_log("Error processing referral: " . $e->getMessage());
        // Don't throw the exception - we don't want to fail the registration if referral processing fails
    }
}

if (isset($_GET['orderId'])) {
    $order_id = $_GET['orderId'];
    error_log("Order ID from return URL: " . $order_id);

    // Verify payment status with Cashfree
    $clientId = "TEST10846745c5a8303d342dc718d3fd54764801";
    $clientSecret = "cfsk_ma_test_0f8d48d6e963a3ff6c8005964e961bab_f925b695";
    $cashfreeBaseUrl = "https://sandbox.cashfree.com/pg/";

    $headers = array(
        "accept: application/json",
        "x-api-version: 2022-09-01",
        "x-client-id: " . $clientId,
        "x-client-secret: " . $clientSecret
    );

    $ch = curl_init($cashfreeBaseUrl . "orders/" . $order_id);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("Cashfree order status response: " . $response);

    $orderData = json_decode($response, true);

    if ($httpCode == 200 && isset($orderData['order_status'])) {
        error_log("Order status: " . $orderData['order_status']);

        if ($orderData['order_status'] === 'PAID') {
            error_log("Payment successful, processing registration");
            error_log("Session data available: " . print_r($_SESSION, true));

            if (isset($_SESSION['registration_data'])) {
                $regData = $_SESSION['registration_data'];
                $result = processRegistration($regData, $orderData['payments']['payment_id'] ?? null, $order_id, $order_id);

                if ($result['status']) {
                    error_log("Registration successful");
                    $message = "Registration successful! Redirecting to login...";
                    $redirect = "/qr/user/src/ui/login.php";
                } else {
                    error_log("Registration failed: " . $result['message']);
                    $message = "Registration failed: " . $result['message'];
                    $redirect = "/qr/user/src/ui/register.php";
                }
            } else {
                error_log("No registration data found in session");
                $message = "Registration data not found. Please try again.";
                $redirect = "/qr/user/src/ui/register.php";
            }
        } else {
            $message = "Payment not completed. Please try again.";
            $redirect = "/qr/user/src/ui/register.php";
        }
    } else {
        $message = "Could not verify payment status. Please contact support.";
        $redirect = "/qr/user/src/ui/register.php";
    }
} else {
    $message = "Invalid request. Please try again.";
    $redirect = "/qr/user/src/ui/register.php";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Registration...</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .spin {
            animation: spin 1s linear infinite;
        }
    </style>
</head>

<body class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="flex flex-col items-center p-8 bg-white rounded-lg shadow-md">
        <div class="loader border-t-4 border-blue-500 rounded-full w-16 h-16 spin mb-4"></div>
        <p class="text-lg text-gray-700 mb-2"><?php echo htmlspecialchars($message); ?></p>
        <p class="text-sm text-gray-500">Please do not close this window</p>
    </div>
    <script>
        setTimeout(() => {
            window.location.href = '<?php echo $redirect; ?>';
        }, 3000);
    </script>
</body>

</html>