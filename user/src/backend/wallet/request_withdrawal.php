<?php
/**
 * Request Wallet Withdrawal API
 * 
 * Creates a new withdrawal request from user's wallet balance
 */
session_start();
header('Content-Type: application/json');
require_once('../dbconfig/connection.php');

// Minimum withdrawal amount
define('MIN_WITHDRAWAL_AMOUNT', 100);

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Please login to continue']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

$amount = isset($data['amount']) ? floatval($data['amount']) : 0;
$payment_method = isset($data['payment_method']) ? trim($data['payment_method']) : '';

// UPI details
$upi_id = isset($data['upi_id']) ? trim($data['upi_id']) : null;

// Bank details
$bank_name = isset($data['bank_name']) ? trim($data['bank_name']) : null;
$branch_name = isset($data['branch_name']) ? trim($data['branch_name']) : null;
$account_number = isset($data['account_number']) ? trim($data['account_number']) : null;
$ifsc_code = isset($data['ifsc_code']) ? trim($data['ifsc_code']) : null;
$account_holder_name = isset($data['account_holder_name']) ? trim($data['account_holder_name']) : null;

// Validate amount
if ($amount <= 0) {
    echo json_encode(['status' => false, 'message' => 'Please enter a valid amount']);
    exit;
}

if ($amount < MIN_WITHDRAWAL_AMOUNT) {
    echo json_encode(['status' => false, 'message' => 'Minimum withdrawal amount is ₹' . MIN_WITHDRAWAL_AMOUNT]);
    exit;
}

// Validate payment method
if (!in_array($payment_method, ['upi', 'bank'])) {
    echo json_encode(['status' => false, 'message' => 'Please select a valid payment method']);
    exit;
}

// Validate payment details based on method
if ($payment_method === 'upi') {
    if (empty($upi_id)) {
        echo json_encode(['status' => false, 'message' => 'Please enter your UPI ID']);
        exit;
    }
    // Basic UPI ID validation
    if (!preg_match('/^[\w.-]+@[\w.-]+$/', $upi_id)) {
        echo json_encode(['status' => false, 'message' => 'Please enter a valid UPI ID (e.g., name@upi)']);
        exit;
    }
} else {
    // Bank transfer validation
    if (empty($bank_name)) {
        echo json_encode(['status' => false, 'message' => 'Please enter bank name']);
        exit;
    }
    if (empty($branch_name)) {
        echo json_encode(['status' => false, 'message' => 'Please enter branch name']);
        exit;
    }
    if (empty($account_number)) {
        echo json_encode(['status' => false, 'message' => 'Please enter account number']);
        exit;
    }
    if (empty($ifsc_code)) {
        echo json_encode(['status' => false, 'message' => 'Please enter IFSC code']);
        exit;
    }
    if (empty($account_holder_name)) {
        echo json_encode(['status' => false, 'message' => 'Please enter account holder name']);
        exit;
    }
    // Basic IFSC validation
    if (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', strtoupper($ifsc_code))) {
        echo json_encode(['status' => false, 'message' => 'Please enter a valid IFSC code']);
        exit;
    }
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Check wallet balance
    // Check wallet balance (Sum of all active wallets)
    $sqlBalance = "SELECT SUM(balance) as total_balance FROM user_wallet WHERE user_id = ? AND is_deleted = 0";
    $stmtBalance = $conn->prepare($sqlBalance);
    $stmtBalance->bind_param('i', $user_id);
    $stmtBalance->execute();
    $resultBalance = $stmtBalance->get_result();
    $walletData = $resultBalance->fetch_assoc();
    
    // If null (no rows), treat as 0
    $current_balance = $walletData['total_balance'] === null ? 0.00 : floatval($walletData['total_balance']);
    
    if ($current_balance < $amount) {
        throw new Exception('Insufficient wallet balance. Available: ₹' . number_format($current_balance, 2));
    }
    
    // Check for pending withdrawals
    $sqlPending = "SELECT COUNT(*) as pending_count FROM user_wallet_withdrawals WHERE user_id = ? AND status = 'pending' AND is_deleted = 0";
    $stmtPending = $conn->prepare($sqlPending);
    $stmtPending->bind_param('i', $user_id);
    $stmtPending->execute();
    $resultPending = $stmtPending->get_result();
    $pendingData = $resultPending->fetch_assoc();
    $stmtPending->close();
    
    if ($pendingData['pending_count'] > 0) {
        throw new Exception('You already have a pending withdrawal request. Please wait for it to be processed.');
    }
    
    // Create withdrawal request
    $sqlInsert = "INSERT INTO user_wallet_withdrawals 
                  (user_id, amount, payment_method, upi_id, bank_name, branch_name, account_number, ifsc_code, account_holder_name, status, created_on) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param('idsssssss', 
        $user_id, 
        $amount, 
        $payment_method, 
        $upi_id, 
        $bank_name, 
        $branch_name,
        $account_number, 
        strtoupper($ifsc_code), 
        $account_holder_name
    );
    
    if (!$stmtInsert->execute()) {
        throw new Exception('Failed to create withdrawal request');
    }
    
    $withdrawal_id = $conn->insert_id;
    $stmtInsert->close();
    
    // Deduct amount from wallet (mark as pending)
    $new_balance = $current_balance - $amount;
    $sqlUpdateWallet = "UPDATE user_wallet SET balance = ?, updated_on = NOW() WHERE user_id = ? AND is_deleted = 0";
    $stmtUpdateWallet = $conn->prepare($sqlUpdateWallet);
    $stmtUpdateWallet->bind_param('di', $new_balance, $user_id);
    
    if (!$stmtUpdateWallet->execute()) {
        throw new Exception('Failed to update wallet balance');
    }
    $stmtUpdateWallet->close();
    
    // Record transaction
    $description = "Withdrawal request #" . $withdrawal_id . " - " . ucfirst($payment_method);
    $sqlTrans = "INSERT INTO user_wallet_transaction 
                 (user_id, amount, transaction_type, description, created_on, updated_on, is_deleted) 
                 VALUES (?, ?, 'Withdrawal', ?, NOW(), NOW(), 0)";
    $stmtTrans = $conn->prepare($sqlTrans);
    $negative_amount = -$amount; // Negative for deduction
    $stmtTrans->bind_param('ids', $user_id, $negative_amount, $description);
    $stmtTrans->execute();
    $stmtTrans->close();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'status' => true,
        'message' => 'Withdrawal request submitted successfully! Your request is pending admin approval.',
        'data' => [
            'withdrawal_id' => $withdrawal_id,
            'amount' => $amount,
            'new_balance' => $new_balance
        ]
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>
