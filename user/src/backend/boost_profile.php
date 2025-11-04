<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Not logged in']);
    exit();
}

include_once('./dbconfig/connection.php');

$user_id = $_SESSION['user_id'];

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$boost_duration = isset($data['boost_duration']) ? intval($data['boost_duration']) : 7;
$boost_cost = 199; // ₹199 for profile boost

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if user is Gold or Silver
    $sqlCheckSlab = "SELECT user_slab_id, user_full_name FROM user_user WHERE id = ? AND is_deleted = 0";
    $stmtCheck = $conn->prepare($sqlCheckSlab);
    $stmtCheck->bind_param('i', $user_id);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    
    if ($resultCheck->num_rows === 0) {
        throw new Exception('User not found');
    }
    
    $userData = $resultCheck->fetch_assoc();
    $user_slab_id = $userData['user_slab_id'];
    $user_name = $userData['user_full_name'];
    
    // Check if user is eligible (Gold=3 or Silver=2)
    // Adjust these IDs based on your actual database
    if ($user_slab_id != 2 && $user_slab_id != 3) {
        throw new Exception('Profile boosting is only available for Gold and Silver members');
    }
    
    $stmtCheck->close();

    // Check wallet balance
    $sqlWallet = "SELECT balance FROM user_wallet WHERE user_id = ? AND is_deleted = 0";
    $stmtWallet = $conn->prepare($sqlWallet);
    $stmtWallet->bind_param('i', $user_id);
    $stmtWallet->execute();
    $resultWallet = $stmtWallet->get_result();
    
    if ($resultWallet->num_rows === 0) {
        throw new Exception('Wallet not found');
    }
    
    $walletData = $resultWallet->fetch_assoc();
    $current_balance = floatval($walletData['balance']);
    
    if ($current_balance < $boost_cost) {
        throw new Exception('Insufficient wallet balance. Please add funds to your wallet.');
    }
    
    $stmtWallet->close();

    // Deduct amount from wallet
    $new_balance = $current_balance - $boost_cost;
    $sqlUpdateWallet = "UPDATE user_wallet SET balance = ? WHERE user_id = ? AND is_deleted = 0";
    $stmtUpdateWallet = $conn->prepare($sqlUpdateWallet);
    $stmtUpdateWallet->bind_param('di', $new_balance, $user_id);
    
    if (!$stmtUpdateWallet->execute()) {
        throw new Exception('Failed to update wallet balance');
    }
    
    $stmtUpdateWallet->close();

    // Record wallet transaction
    $transaction_type = 'debit';
    $description = 'Profile Super Charge - ' . $boost_duration . ' days boost';
    $sqlTransaction = "INSERT INTO wallet_transactions (user_id, transaction_type, amount, description, balance_after, created_on) 
                       VALUES (?, ?, ?, ?, ?, NOW())";
    $stmtTransaction = $conn->prepare($sqlTransaction);
    $stmtTransaction->bind_param('isdsd', $user_id, $transaction_type, $boost_cost, $description, $new_balance);
    
    if (!$stmtTransaction->execute()) {
        throw new Exception('Failed to record transaction');
    }
    
    $stmtTransaction->close();

    // Add or update profile boost record
    $boost_end_date = date('Y-m-d H:i:s', strtotime("+{$boost_duration} days"));
    
    // Check if there's an existing active boost
    $sqlCheckBoost = "SELECT id FROM profile_boosts WHERE user_id = ? AND end_date > NOW() AND is_deleted = 0";
    $stmtCheckBoost = $conn->prepare($sqlCheckBoost);
    $stmtCheckBoost->bind_param('i', $user_id);
    $stmtCheckBoost->execute();
    $resultBoost = $stmtCheckBoost->get_result();
    
    if ($resultBoost->num_rows > 0) {
        // Extend existing boost
        $sqlUpdateBoost = "UPDATE profile_boosts SET end_date = DATE_ADD(end_date, INTERVAL ? DAY), updated_on = NOW() 
                           WHERE user_id = ? AND end_date > NOW() AND is_deleted = 0";
        $stmtUpdateBoost = $conn->prepare($sqlUpdateBoost);
        $stmtUpdateBoost->bind_param('ii', $boost_duration, $user_id);
        $stmtUpdateBoost->execute();
        $stmtUpdateBoost->close();
        $message = "Your profile boost has been extended by {$boost_duration} days!";
    } else {
        // Create new boost
        $sqlInsertBoost = "INSERT INTO profile_boosts (user_id, start_date, end_date, boost_amount_paid, created_on) 
                           VALUES (?, NOW(), ?, ?, NOW())";
        $stmtInsertBoost = $conn->prepare($sqlInsertBoost);
        $stmtInsertBoost->bind_param('isd', $user_id, $boost_end_date, $boost_cost);
        $stmtInsertBoost->execute();
        $stmtInsertBoost->close();
        $message = "Your profile has been Super Charged for {$boost_duration} days!";
    }
    
    $stmtCheckBoost->close();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'status' => true,
        'message' => $message,
        'new_balance' => $new_balance,
        'boost_end_date' => $boost_end_date
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
