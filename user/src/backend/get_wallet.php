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

// Get wallet balance (Sum of all active wallets to handle duplicates)
$sqlWallet = "SELECT SUM(balance) as total_balance, COUNT(*) as count FROM user_wallet WHERE user_id = ? AND is_deleted = 0";
$stmtWallet = $conn->prepare($sqlWallet);
$stmtWallet->bind_param('i', $user_id);
$stmtWallet->execute();
$resultWallet = $stmtWallet->get_result();
$balance = 0;
$wallet_exists = false;

if ($resultWallet->num_rows > 0) {
    $row = $resultWallet->fetch_assoc();
    if ($row['count'] > 0) {
        $balance = floatval($row['total_balance']);
        $wallet_exists = true;
    }
}
$stmtWallet->close();

// SELF-HEALING: If wallet row is missing but user exists, check transactions and create wallet
if (!$wallet_exists) {
    // Calculate balance from transaction history
    $sqlCalc = "SELECT SUM(amount) as real_balance FROM user_wallet_transaction WHERE user_id = ? AND is_deleted = 0";
    $stmtCalc = $conn->prepare($sqlCalc);
    $stmtCalc->bind_param('i', $user_id);
    $stmtCalc->execute();
    $resCalc = $stmtCalc->get_result();
    $rowCalc = $resCalc->fetch_assoc();
    $real_balance = $rowCalc['real_balance'] ? floatval($rowCalc['real_balance']) : 0.00;
    $stmtCalc->close();

    // Create the missing wallet row
    $now = date('Y-m-d H:i:s');
    $sqlFix = "INSERT INTO user_wallet (user_id, balance, created_by, created_on, updated_by, updated_on, is_deleted) VALUES (?, ?, ?, ?, ?, ?, 0)";
    $stmtFix = $conn->prepare($sqlFix);
    $stmtFix->bind_param('idisis', $user_id, $real_balance, $user_id, $now, $user_id, $now);
    
    if ($stmtFix->execute()) {
        $balance = $real_balance;
        error_log("Self-healing: Created missing wallet for User ID $user_id with balance $balance");
    }
    $stmtFix->close();
}

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