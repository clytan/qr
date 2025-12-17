<?php
/**
 * Cancel Pending Withdrawal Request
 */
session_start();
header('Content-Type: application/json');
require_once('../dbconfig/connection.php');

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Please login to continue']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$withdrawal_id = isset($data['withdrawal_id']) ? intval($data['withdrawal_id']) : 0;

if ($withdrawal_id <= 0) {
    echo json_encode(['status' => false, 'message' => 'Invalid withdrawal ID']);
    exit;
}

try {
    $conn->begin_transaction();
    
    // Check withdrawal exists and belongs to user
    $sqlCheck = "SELECT id, amount, status FROM user_wallet_withdrawals 
                 WHERE id = ? AND user_id = ? AND is_deleted = 0";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param('ii', $withdrawal_id, $user_id);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Withdrawal request not found');
    }
    
    $withdrawal = $result->fetch_assoc();
    $stmtCheck->close();
    
    if ($withdrawal['status'] !== 'pending') {
        throw new Exception('Only pending withdrawals can be cancelled');
    }
    
    $amount = floatval($withdrawal['amount']);
    
    // Mark withdrawal as deleted/cancelled
    $sqlUpdate = "UPDATE user_wallet_withdrawals SET status = 'rejected', rejection_reason = 'Cancelled by user', updated_on = NOW() WHERE id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param('i', $withdrawal_id);
    $stmtUpdate->execute();
    $stmtUpdate->close();
    
    // Refund to wallet
    $sqlWallet = "UPDATE user_wallet SET balance = balance + ?, updated_on = NOW() WHERE user_id = ? AND is_deleted = 0";
    $stmtWallet = $conn->prepare($sqlWallet);
    $stmtWallet->bind_param('di', $amount, $user_id);
    $stmtWallet->execute();
    $stmtWallet->close();
    
    // Record refund transaction
    $description = "Withdrawal cancelled - Refund for request #" . $withdrawal_id;
    $sqlTrans = "INSERT INTO user_wallet_transaction 
                 (user_id, amount, transaction_type, description, created_on, updated_on, is_deleted) 
                 VALUES (?, ?, 'Refund', ?, NOW(), NOW(), 0)";
    $stmtTrans = $conn->prepare($sqlTrans);
    $stmtTrans->bind_param('ids', $user_id, $amount, $description);
    $stmtTrans->execute();
    $stmtTrans->close();
    
    $conn->commit();
    
    echo json_encode([
        'status' => true,
        'message' => 'Withdrawal cancelled and amount refunded to your wallet'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>
