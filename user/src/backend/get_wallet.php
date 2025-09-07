<?php
session_start();
require_once('../backend/dbconfig/connection.php');
header('Content-Type: application/json');

// You may want to get user_id from session in production
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_GET['user_id']) ? intval($_GET['user_id']) : 0);

if (!$user_id) {
    echo json_encode(['status' => false, 'message' => 'User not logged in', 'data' => []]);
    exit();
}

// Get wallet balance
$sqlWallet = "SELECT balance FROM user_wallet WHERE user_id = ? AND is_deleted = 0 LIMIT 1";
$stmtWallet = $conn->prepare($sqlWallet);
$stmtWallet->bind_param('i', $user_id);
$stmtWallet->execute();
$resultWallet = $stmtWallet->get_result();
$balance = 0;
if ($resultWallet->num_rows > 0) {
    $row = $resultWallet->fetch_assoc();
    $balance = floatval($row['balance']);
}
$stmtWallet->close();

// Get wallet transactions (latest 20)
$sqlTrans = "SELECT amount, transaction_type, description, created_on FROM user_wallet_transaction WHERE user_id = ? AND is_deleted = 0 ORDER BY created_on DESC LIMIT 20";
$stmtTrans = $conn->prepare($sqlTrans);
$stmtTrans->bind_param('i', $user_id);
$stmtTrans->execute();
$resultTrans = $stmtTrans->get_result();
$transactions = [];
while ($row = $resultTrans->fetch_assoc()) {
    $transactions[] = $row;
}
$stmtTrans->close();

// Output JSON
echo json_encode([
    'status' => true,
    'data' => [
        'balance' => $balance,
        'transactions' => $transactions
    ]
]);
exit();
?>