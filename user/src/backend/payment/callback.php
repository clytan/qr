<?php
require_once('../dbconfig/connection.php');
require_once('./session_config.php');
require_once('../auto_community_helper.php');
require_once(__DIR__ . '/../../mailer/send_welcome_email.php');
error_log("Payment callback received - Method: " . $_SERVER['REQUEST_METHOD']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postData = file_get_contents('php://input');
    error_log("Callback raw data: " . $postData);
    $data = json_decode($postData, true);

    if ($data && isset($data['payment']['payment_status'])) {
        error_log("Payment status: " . $data['payment']['payment_status']);

        // Get order_id from callback data
        $order_id = $data['order']['order_id'] ?? null;

        if (!$order_id) {
            error_log("Order ID not found in callback data");
            echo json_encode(['status' => false, 'message' => 'Order ID missing']);
            exit;
        }

        error_log("Order ID from callback: " . $order_id);

        // Get registration data from database (more reliable than session)
        $sqlGetReg = "SELECT registration_data, status FROM user_pending_registration WHERE order_id = ? AND status = 'pending'";
        $stmtGetReg = $conn->prepare($sqlGetReg);
        $stmtGetReg->bind_param('s', $order_id);
        $stmtGetReg->execute();
        $resultGetReg = $stmtGetReg->get_result();

        if ($resultGetReg->num_rows === 0) {
            error_log("No pending registration found for order: " . $order_id);
            echo json_encode(['status' => false, 'message' => 'Registration data not found']);
            exit;
        }

        $rowReg = $resultGetReg->fetch_assoc();
        $regData = json_decode($rowReg['registration_data'], true);
        $stmtGetReg->close();

        error_log("Registration data retrieved from database: " . print_r($regData, true));

        if ($data['payment']['payment_status'] == "SUCCESS") {
            $payment_id = $data['payment']['cf_payment_id'];
            $bank_reference = $data['payment']['bank_reference'];

            error_log("Processing registration with data: " . print_r($regData, true));

            // Process registration here by calling the registration logic
            $result = processRegistration($regData, $payment_id, $bank_reference, $order_id);

            error_log("Registration result: " . print_r($result, true));

            if ($result['status']) {
                // Update status to completed
                $sqlUpdate = "UPDATE user_pending_registration SET status = 'completed' WHERE order_id = ?";
                $stmtUpdate = $conn->prepare($sqlUpdate);
                $stmtUpdate->bind_param('s', $order_id);
                $stmtUpdate->execute();
                $stmtUpdate->close();

                echo json_encode(['status' => true, 'message' => 'Registration successful']);
            } else {
                echo json_encode(['status' => false, 'message' => $result['message']]);
            }
        } else {
            echo json_encode(['status' => false, 'message' => 'Payment failed or pending']);
        }
    } else {
        echo json_encode(['status' => false, 'message' => 'Invalid payment data']);
    }
} else {
    echo json_encode(['status' => false, 'message' => 'Invalid request method']);
}

function processRegistration($data, $payment_id, $bank_reference, $order_id)
{
    global $conn;

    try {
        $conn->begin_transaction();

        $full_name = $data['full_name'] ?? null;
        $email = $data['email'];
        $phone = $data['phone'] ?? null;
        $password = $data['password'];
        $address = $data['address'] ?? null;
        $pincode = $data['pincode'] ?? null;
        $landmark = $data['landmark'] ?? null;
        $user_type = $data['user_type'];
        $user_tag = $data['user_tag'] ?? null;
        $selected_slab = $data['user_slab'];
        $reference_code = $data['reference_code'] ?? '';
        $referred_by_user_id = $data['referred_by_user_id'] ?? null;
        $college_name = $data['college_name'] ?? null;
        $amount = $data['amount'];

        // Generate sequential QR ID
        // Get the highest existing ZOK ID and increment
        $sqlMaxQr = "SELECT MAX(CAST(SUBSTRING(user_qr_id, 4) AS UNSIGNED)) as max_num FROM user_user WHERE user_qr_id LIKE 'ZOK%'";
        $resultMaxQr = $conn->query($sqlMaxQr);
        $rowMaxQr = $resultMaxQr->fetch_assoc();
        $nextNum = ($rowMaxQr['max_num'] ?? 0) + 1;
        $user_qr_id = 'ZOK' . str_pad(strval($nextNum), 7, '0', STR_PAD_LEFT);
        error_log("Generated sequential QR ID: " . $user_qr_id . " (Next number: " . $nextNum . ")");

        // Build INSERT query using actual column names from database
        $columns = ['user_email', 'user_password', 'user_user_type', 'user_tag', 'user_slab_id', 'user_qr_id', 'referred_by_user_id'];
        $values = [$email, $password, $user_type, $user_tag, $selected_slab, $user_qr_id, $referred_by_user_id];
        $types = 'ssisssi';

        // Add user_full_name (column exists in database)
        if ($full_name !== null) {
            $columns[] = 'user_full_name';
            $values[] = $full_name;
            $types .= 's';
        }

        // Add user_phone (column exists in database)
        if ($phone !== null) {
            $columns[] = 'user_phone';
            $values[] = $phone;
            $types .= 's';
        }

        // Add user_address (column exists in database)
        if ($address !== null) {
            $columns[] = 'user_address';
            $values[] = $address;
            $types .= 's';
        }

        // Add user_pincode (column exists in database)
        if ($pincode !== null) {
            $columns[] = 'user_pincode';
            $values[] = $pincode;
            $types .= 's';
        }

        // Add user_landmark (column exists in database)
        if ($landmark !== null) {
            $columns[] = 'user_landmark';
            $values[] = $landmark;
            $types .= 's';
        }

        // Add college_name (column exists in database)
        if ($college_name !== null) {
            $columns[] = 'college_name';
            $values[] = $college_name;
            $types .= 's';
        }

        // Build SQL query
        $columnsList = implode(', ', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sqlInsert = "INSERT INTO user_user($columnsList) VALUES ($placeholders)";

        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param($types, ...$values);

        if (!$stmtInsert->execute()) {
            throw new Exception("Failed to create user: " . $conn->error);
        }

        $user_id = $conn->insert_id;
        $now = date('Y-m-d H:i:s');

        // Update user metadata
        $sqlUpdate = "UPDATE user_user SET is_deleted = 0, user_email_verified = 1, created_by = ?, updated_by = ?, created_on = ?, updated_on = ? WHERE id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param('isssi', $user_id, $user_id, $now, $now, $user_id);
        if (!$stmtUpdate->execute()) {
            throw new Exception("Failed to update user metadata");
        }

        // ========================================
        // GST CALCULATION (Indian Tax Structure - GST INCLUSIVE)
        // ========================================
        // Amount from UI is the TOTAL amount (GST inclusive)
        // We need to extract the base amount and GST components
        $total_amount = $amount; // e.g., ₹999 (this is what user pays)

        // Calculate base amount (reverse calculation)
        // Total = Base + (Base × 18%)
        // Total = Base × 1.18
        // Base = Total ÷ 1.18
        $base_amount = round($total_amount / 1.18, 2);

        // Calculate GST: CGST 9% + SGST 9% = 18% total
        $cgst_rate = 9.0; // 9%
        $sgst_rate = 9.0; // 9%

        $cgst = round(($base_amount * $cgst_rate) / 100, 2);
        $sgst = round(($base_amount * $sgst_rate) / 100, 2);
        $igst = 0.00; // IGST not applicable for intra-state transactions

        $gst_total = $cgst + $sgst + $igst;

        // Adjust for rounding differences to ensure total matches exactly
        $calculated_total = $base_amount + $gst_total;
        if ($calculated_total != $total_amount) {
            $difference = $total_amount - $calculated_total;
            $base_amount = round($base_amount + $difference, 2);
        }

        // Log GST calculation for debugging
        error_log("GST Calculation (Inclusive) - Total: ₹{$total_amount}, Base: ₹{$base_amount}, CGST (9%): ₹{$cgst}, SGST (9%): ₹{$sgst}, Total GST: ₹{$gst_total}");

        $invoice_number = 'INV' . date('Ymd') . '-' . str_pad($user_id, 3, '0', STR_PAD_LEFT);

        $sqlInvoice = "INSERT INTO user_invoice (user_id, invoice_number, invoice_type, amount, cgst, sgst, igst, gst_total, total_amount, status, payment_mode, payment_reference, created_on, updated_on, is_deleted) VALUES (?, ?, 'registration', ?, ?, ?, ?, ?, ?, 'Paid', 'UPI', ?, ?, ?, 0)";
        $stmtInvoice = $conn->prepare($sqlInvoice);
        $stmtInvoice->bind_param('isdddddssss', $user_id, $invoice_number, $base_amount, $cgst, $sgst, $igst, $gst_total, $total_amount, $payment_id, $now, $now);

        if (!$stmtInvoice->execute()) {
            throw new Exception("Failed to create invoice");
        }

        // Process referral if exists
        if ($referred_by_user_id) {
            processReferral($conn, $referred_by_user_id, $user_id);
        }

        $conn->commit();

        // AUTO-ASSIGN USER TO COMMUNITY (100 users per community)
        $communityResult = assignUserToCommunity($conn, $user_id);
        if ($communityResult['status']) {
            error_log("User $user_id auto-assigned to {$communityResult['community_name']} (Members: {$communityResult['member_count']}/100)");
        } else {
            error_log("Failed to auto-assign community for user $user_id: " . $communityResult['error']);
        }

        // Send welcome email with QR code and invoice attachments (best effort)
        try {
            sendWelcomeEmail($email, $full_name ?? '', $user_id, $user_qr_id);
        } catch (Exception $e) {
            error_log('Failed to send welcome email: ' . $e->getMessage());
        }


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
        // Get referrer details - check both by numeric id and by user_qr_id
        // since referred_by_user_id might be stored as either
        $sqlReferrer = "SELECT id, user_slab_id, user_tag, user_user_type, college_name FROM user_user WHERE (id = ? OR user_qr_id = ?) AND is_deleted = 0 LIMIT 1";
        $stmtReferrer = $conn->prepare($sqlReferrer);
        $stmtReferrer->bind_param('ss', $referred_by_user_id, $referred_by_user_id);
        $stmtReferrer->execute();
        $resultReferrer = $stmtReferrer->get_result();

        if ($resultReferrer->num_rows > 0) {
            $referrer = $resultReferrer->fetch_assoc();
            $referrer_id = $referrer['id'];
            $referrer_slab_id = $referrer['user_slab_id'];

            // Get referrer's details directly to determine commission
            // We use user_tag (gold/silver) and user_user_type (2=Creator, 3=Business)
            // Logic: Gold, Silver, Creator, Business, Student Leader -> ₹200, Others -> ₹100
            
            $referrer_tag = strtolower(trim($referrer['user_tag'] ?? ''));
            $referrer_type = (string)$referrer['user_user_type'];
            
            error_log("Processing Referral - Referrer ID: $referrer_id, Type: '$referrer_type', Tag: '$referrer_tag', College: '{$referrer['college_name']}'");

            $is_privileged = false;
            $privileged_reason = "";

            // Check Tags (substring match)
            $privileged_tags = ['gold', 'silver', 'creator', 'student leader', 'business'];
            foreach ($privileged_tags as $tag) {
                if (strpos($referrer_tag, $tag) !== false) {
                    $is_privileged = true;
                    $privileged_reason = "Tag match: $tag";
                    break;
                }
            }

            // Check Type (2=Creator, 3=Business)
            if (!$is_privileged && ($referrer_type === '2' || $referrer_type === '3')) {
                $is_privileged = true;
                $privileged_reason = "User Type: $referrer_type";
            }

            // Check College (Student Leader indicator)
            if (!$is_privileged && !empty($referrer['college_name']) && $referrer['college_name'] !== '[NULL]') {
                $is_privileged = true;
                $privileged_reason = "Has College Name";
            }

            if ($is_privileged) {
                $commission_amount = 200.0;
                error_log(">> Awarding ₹200 (Reason: $privileged_reason)");
            } else {
                $commission_amount = 100.0;
                error_log(">> Awarding ₹100 (Standard User)");
            }

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
                
                error_log("Referral commission of {$commission_amount} processed for referrer ID: {$referrer_id}");
            }
        
    } catch (Exception $e) {
        error_log("Error processing referral: " . $e->getMessage());
        // Don't throw - we don't want to fail the registration if referral processing fails
    }
}
?>