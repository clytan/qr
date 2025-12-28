<?php
session_start();
require_once('../backend/dbconfig/connection.php');

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$user_id = $_SESSION['user_id'];
echo "<h1>Debug Info for User ID: $user_id</h1>";

// 1. Check User Table
$sqlUser = "SELECT id, user_email, user_qr_id FROM user_user WHERE id = $user_id";
$resUser = $conn->query($sqlUser);
echo "<h2>User Table</h2>";
if ($resUser->num_rows > 0) {
    echo "<pre>" . print_r($resUser->fetch_assoc(), true) . "</pre>";
} else {
    echo "User not found in DB!";
}

// 2. Check Wallet Table (All rows, including deleted)
$sqlWallet = "SELECT * FROM user_wallet WHERE user_id = $user_id";
$resWallet = $conn->query($sqlWallet);
echo "<h2>User Wallet Table (All rows)</h2>";
if ($resWallet->num_rows > 0) {
    while($row = $resWallet->fetch_assoc()) {
        echo "<pre>" . print_r($row, true) . "</pre>";
    }
} else {
    echo "NO WALLET ROWS FOUND!";
}

// 3. Check Transactions (All rows)
$sqlTrans = "SELECT * FROM user_wallet_transaction WHERE user_id = $user_id ORDER BY id DESC";
$resTrans = $conn->query($sqlTrans);
echo "<h2>Transactions (All rows)</h2>";
if ($resTrans->num_rows > 0) {
    while($row = $resTrans->fetch_assoc()) {
        echo "<pre>" . print_r($row, true) . "</pre>";
    }
} else {
    echo "NO TRANSACTIONS FOUND!";
}

// 4. Check Raw Sum
$sqlSum = "SELECT SUM(amount) as calc_balance FROM user_wallet_transaction WHERE user_id = $user_id AND is_deleted = 0";
$resSum = $conn->query($sqlSum);
$rowSum = $resSum->fetch_assoc();
echo "<h2>Calculated Balance from Transactions</h2>";
echo "Calculated Sum: " . $rowSum['calc_balance'];
?>
