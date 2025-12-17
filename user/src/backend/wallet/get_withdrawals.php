<?php
/**
 * Get User's Withdrawal History
 */
session_start();
header('Content-Type: application/json');
require_once('../dbconfig/connection.php');

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Please login to continue']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $sql = "SELECT 
                id,
                amount,
                payment_method,
                upi_id,
                bank_name,
                account_number,
                ifsc_code,
                account_holder_name,
                status,
                rejection_reason,
                transaction_reference,
                created_on,
                processed_on
            FROM user_wallet_withdrawals 
            WHERE user_id = ? AND is_deleted = 0 
            ORDER BY created_on DESC 
            LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $withdrawals = [];
    while ($row = $result->fetch_assoc()) {
        // Mask account number for security
        if ($row['account_number']) {
            $row['account_number_masked'] = '****' . substr($row['account_number'], -4);
        }
        $withdrawals[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode([
        'status' => true,
        'data' => $withdrawals,
        'count' => count($withdrawals)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
