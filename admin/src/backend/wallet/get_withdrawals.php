<?php
/**
 * Admin - Get All Withdrawal Requests
 */
session_start();
header('Content-Type: application/json');
require_once('../dbconfig/connection.php');

// Check admin auth
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    echo json_encode(['status' => false, 'message' => 'Admin authentication required']);
    exit;
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

try {
    $sql = "SELECT 
                w.id,
                w.user_id,
                w.amount,
                w.payment_method,
                w.upi_id,
                w.bank_name,
                w.account_number,
                w.ifsc_code,
                w.account_holder_name,
                w.status,
                w.admin_notes,
                w.rejection_reason,
                w.transaction_reference,
                w.created_on,
                w.processed_on,
                u.user_full_name,
                u.user_email,
                u.user_phone,
                u.user_qr_id,
                uw.balance as current_balance
            FROM user_wallet_withdrawals w
            LEFT JOIN user_user u ON w.user_id = u.id
            LEFT JOIN user_wallet uw ON w.user_id = uw.user_id AND uw.is_deleted = 0
            WHERE w.is_deleted = 0";
    
    if ($status_filter !== 'all') {
        $sql .= " AND w.status = ?";
    }
    
    $sql .= " ORDER BY 
                CASE w.status 
                    WHEN 'pending' THEN 1 
                    WHEN 'approved' THEN 2 
                    WHEN 'completed' THEN 3 
                    WHEN 'rejected' THEN 4 
                END, 
                w.created_on DESC";
    
    $stmt = $conn->prepare($sql);
    
    if ($status_filter !== 'all') {
        $stmt->bind_param('s', $status_filter);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $withdrawals = [];
    while ($row = $result->fetch_assoc()) {
        $withdrawals[] = $row;
    }
    
    // Get summary stats
    $sqlStats = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
                    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount
                 FROM user_wallet_withdrawals WHERE is_deleted = 0";
    $statsResult = $conn->query($sqlStats);
    $stats = $statsResult->fetch_assoc();
    
    echo json_encode([
        'status' => true,
        'data' => $withdrawals,
        'stats' => $stats,
        'count' => count($withdrawals)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
