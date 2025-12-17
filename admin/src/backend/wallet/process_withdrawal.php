<?php
/**
 * Admin - Process Withdrawal Request (Approve/Reject/Complete)
 */
session_start();
header('Content-Type: application/json');
require_once('../dbconfig/connection.php');

// Check admin auth
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    echo json_encode(['status' => false, 'message' => 'Admin authentication required']);
    exit;
}

$admin_id = $_SESSION['admin_id'];
$data = json_decode(file_get_contents('php://input'), true);

$withdrawal_id = isset($data['withdrawal_id']) ? intval($data['withdrawal_id']) : 0;
$action = isset($data['action']) ? trim($data['action']) : '';
$admin_notes = isset($data['admin_notes']) ? trim($data['admin_notes']) : '';
$rejection_reason = isset($data['rejection_reason']) ? trim($data['rejection_reason']) : '';
$transaction_reference = isset($data['transaction_reference']) ? trim($data['transaction_reference']) : '';

if ($withdrawal_id <= 0) {
    echo json_encode(['status' => false, 'message' => 'Invalid withdrawal ID']);
    exit;
}

if (!in_array($action, ['approve', 'reject', 'complete'])) {
    echo json_encode(['status' => false, 'message' => 'Invalid action']);
    exit;
}

try {
    $conn->begin_transaction();
    
    // Get withdrawal details
    $sqlGet = "SELECT * FROM user_wallet_withdrawals WHERE id = ? AND is_deleted = 0";
    $stmtGet = $conn->prepare($sqlGet);
    $stmtGet->bind_param('i', $withdrawal_id);
    $stmtGet->execute();
    $result = $stmtGet->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Withdrawal request not found');
    }
    
    $withdrawal = $result->fetch_assoc();
    $stmtGet->close();
    
    $user_id = $withdrawal['user_id'];
    $amount = floatval($withdrawal['amount']);
    $current_status = $withdrawal['status'];
    
    // Validate status transitions
    if ($action === 'approve' && $current_status !== 'pending') {
        throw new Exception('Only pending requests can be approved');
    }
    
    if ($action === 'reject' && !in_array($current_status, ['pending', 'approved'])) {
        throw new Exception('Only pending or approved requests can be rejected');
    }
    
    if ($action === 'complete' && $current_status !== 'approved') {
        throw new Exception('Only approved requests can be marked as completed');
    }
    
    // Process action
    $new_status = '';
    switch ($action) {
        case 'approve':
            $new_status = 'approved';
            break;
            
        case 'reject':
            $new_status = 'rejected';
            if (empty($rejection_reason)) {
                throw new Exception('Please provide a rejection reason');
            }
            
            // Refund amount to user's wallet
            $sqlRefund = "UPDATE user_wallet SET balance = balance + ?, updated_on = NOW() WHERE user_id = ? AND is_deleted = 0";
            $stmtRefund = $conn->prepare($sqlRefund);
            $stmtRefund->bind_param('di', $amount, $user_id);
            $stmtRefund->execute();
            $stmtRefund->close();
            
            // Record refund transaction
            $description = "Withdrawal rejected - Refund for request #" . $withdrawal_id;
            $sqlTrans = "INSERT INTO user_wallet_transaction 
                         (user_id, amount, transaction_type, description, created_on, updated_on, is_deleted) 
                         VALUES (?, ?, 'Refund', ?, NOW(), NOW(), 0)";
            $stmtTrans = $conn->prepare($sqlTrans);
            $stmtTrans->bind_param('ids', $user_id, $amount, $description);
            $stmtTrans->execute();
            $stmtTrans->close();
            break;
            
        case 'complete':
            $new_status = 'completed';
            break;
    }
    
    // Update withdrawal record
    $sqlUpdate = "UPDATE user_wallet_withdrawals 
                  SET status = ?, 
                      admin_notes = ?,
                      rejection_reason = ?,
                      transaction_reference = ?,
                      processed_by = ?,
                      processed_on = NOW(),
                      updated_on = NOW()
                  WHERE id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param('ssssii', $new_status, $admin_notes, $rejection_reason, $transaction_reference, $admin_id, $withdrawal_id);
    
    if (!$stmtUpdate->execute()) {
        throw new Exception('Failed to update withdrawal status');
    }
    $stmtUpdate->close();
    
    $conn->commit();
    
    $messages = [
        'approve' => 'Withdrawal approved successfully. Please proceed with the bank transfer.',
        'reject' => 'Withdrawal rejected. Amount refunded to user wallet.',
        'complete' => 'Withdrawal marked as completed.'
    ];
    
    echo json_encode([
        'status' => true,
        'message' => $messages[$action]
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>
