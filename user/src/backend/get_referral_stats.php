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
    // Get user's referral code (user_qr_id)
    $sqlUser = "SELECT user_qr_id, user_full_name, user_tag, user_slab_id FROM user_user WHERE id = ? AND is_deleted = 0";
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
    $user_slab_id = $userData['user_slab_id'];

    // Get total referrals count
    $sqlTotalReferrals = "SELECT COUNT(*) as total FROM user_user WHERE referred_by_user_id = ? AND is_deleted = 0";
    $stmtTotalReferrals = $conn->prepare($sqlTotalReferrals);
    $stmtTotalReferrals->bind_param('s', $referral_code);
    $stmtTotalReferrals->execute();
    $resultTotalReferrals = $stmtTotalReferrals->get_result();
    $totalReferrals = $resultTotalReferrals->fetch_assoc()['total'];

    // Get active referrals (users who have completed payment/registration)
    $sqlActiveReferrals = "SELECT COUNT(*) as active FROM user_user WHERE referred_by_user_id = ? AND user_email_verified = 1 AND is_deleted = 0";
    $stmtActiveReferrals = $conn->prepare($sqlActiveReferrals);
    $stmtActiveReferrals->bind_param('s', $referral_code);
    $stmtActiveReferrals->execute();
    $resultActiveReferrals = $stmtActiveReferrals->get_result();
    $activeReferrals = $resultActiveReferrals->fetch_assoc()['active'];

    // Calculate total earnings based on referral commission
    // Get commission percentage from user_slab
    $sqlSlab = "SELECT ref_commission FROM user_slab WHERE id = ?";
    $stmtSlab = $conn->prepare($sqlSlab);
    $stmtSlab->bind_param('i', $user_slab_id);
    $stmtSlab->execute();
    $resultSlab = $stmtSlab->get_result();
    $refCommission = 0;
    if ($resultSlab->num_rows > 0) {
        $slabData = $resultSlab->fetch_assoc();
        $refCommission = $slabData['ref_commission'] ?? 0;
    }

    // Get total earnings based on ACTUAL wallet credit transactions
    // This fixes the issue where earnings were calculated as percentage of invoice amount (e.g. 93.22)
    // instead of the actual credited amount (100 or 200)
    
    $sqlEarnings = "SELECT COALESCE(SUM(amount), 0) as total_earnings 
                    FROM user_wallet_transaction 
                    WHERE user_id = ? 
                    AND transaction_type = 'Referral' 
                    AND is_deleted = 0";
                    
    $stmtEarnings = $conn->prepare($sqlEarnings);
    $stmtEarnings->bind_param('i', $user_id); // Use user_id (referrer's ID), not referral code
    $stmtEarnings->execute();
    $resultEarnings = $stmtEarnings->get_result();
    $earningsData = $resultEarnings->fetch_assoc();
    $totalEarnings = $earningsData['total_earnings'];

    $responseData = [
        'status' => true,
        'data' => [
            'referral_code' => $referral_code,
            'total_referrals' => $totalReferrals,
            'active_referrals' => $activeReferrals,
            'total_earnings' => round($totalEarnings, 2),
            'commission_percentage' => $refCommission
        ]
    ];

    // If leaderboard is requested
    if (isset($_GET['leaderboard'])) {
        // Get period filter
        $period = isset($_GET['period']) ? $_GET['period'] : 'all';
        
        // Build date filter based on period
        $dateFilter = '';
        if ($period === 'month') {
            $dateFilter = 'AND r.created_on >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
        } else if ($period === 'week') {
            $dateFilter = 'AND r.created_on >= DATE_SUB(NOW(), INTERVAL 1 WEEK)';
        }
        
        // Get top referrers with their stats
        $sqlLeaderboard = "SELECT 
            u.id,
            u.user_full_name,
            u.user_email,
            u.user_qr_id,
            u.user_tag,
            COUNT(r.id) as referral_count,
            COALESCE(SUM(i.amount), 0) as total_amount,
            us.ref_commission
        FROM user_user u
        LEFT JOIN user_user r ON r.referred_by_user_id = u.user_qr_id AND r.is_deleted = 0 $dateFilter
        LEFT JOIN user_invoice i ON r.id = i.user_id AND i.invoice_type = 'registration' AND i.status = 'Paid'
        LEFT JOIN user_slab us ON u.user_slab_id = us.id
        WHERE u.is_deleted = 0
        GROUP BY u.id
        HAVING referral_count > 0
        ORDER BY referral_count DESC, total_amount DESC
        LIMIT 20";

        $resultLeaderboard = $conn->query($sqlLeaderboard);
        $leaderboard = [];

        if ($resultLeaderboard && $resultLeaderboard->num_rows > 0) {
            while ($row = $resultLeaderboard->fetch_assoc()) {
                $commission = $row['ref_commission'] ?? 0;
                $earnings = ($row['total_amount'] * $commission) / 100;
                
                $leaderboard[] = [
                    'user_id' => $row['id'],
                    'user_name' => $row['user_full_name'] ?? 'User',
                    'user_qr_id' => $row['user_qr_id'],
                    'user_tag' => $row['user_tag'],
                    'referral_count' => $row['referral_count'],
                    'total_earnings' => round($earnings, 2)
                ];
            }
        }

        $responseData['leaderboard'] = $leaderboard;
    }

    echo json_encode($responseData);

} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
