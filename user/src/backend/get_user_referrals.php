<?php
session_start();
header('Content-Type: application/json');
require_once('./dbconfig/connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get user's referral code
    $sqlUser = "SELECT user_qr_id FROM user_user WHERE id = ? AND is_deleted = 0";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param('i', $user_id);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();

    if ($resultUser->num_rows === 0) {
        echo json_encode(['status' => false, 'message' => 'User not found']);
        exit();
    }

    $userData = $resultUser->fetch_assoc();
    $referral_code = $userData['user_qr_id'];

    // Get all users referred by this user
    $sqlReferrals = "SELECT 
        u.id,
        u.user_name,
        u.user_email,
        u.user_qr_id,
        u.user_tag,
        u.user_email_verified,
        u.created_on,
        i.amount as paid_amount,
        i.status as payment_status,
        i.created_on as payment_date
    FROM user_user u
    LEFT JOIN user_invoice i ON u.id = i.user_id AND i.invoice_type = 'registration'
    WHERE u.referred_by_user_id = ? AND u.is_deleted = 0
    ORDER BY u.created_on DESC";

    $stmtReferrals = $conn->prepare($sqlReferrals);
    $stmtReferrals->bind_param('s', $referral_code);
    $stmtReferrals->execute();
    $resultReferrals = $stmtReferrals->get_result();

    $referrals = [];
    while ($row = $resultReferrals->fetch_assoc()) {
        $status = 'Pending';
        if ($row['user_email_verified'] == 1 && $row['payment_status'] == 'Paid') {
            $status = 'Active';
        } elseif ($row['user_email_verified'] == 1) {
            $status = 'Verified';
        }

        $referrals[] = [
            'user_id' => $row['id'],
            'user_name' => $row['user_name'] ?? 'User',
            'user_email' => $row['user_email'],
            'user_qr_id' => $row['user_qr_id'],
            'user_tag' => $row['user_tag'],
            'status' => $status,
            'paid_amount' => $row['paid_amount'] ?? 0,
            'joined_date' => $row['created_on'],
            'payment_date' => $row['payment_date']
        ];
    }

    echo json_encode([
        'status' => true,
        'data' => $referrals,
        'total_count' => count($referrals)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
