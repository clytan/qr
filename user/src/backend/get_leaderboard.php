<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
require_once('dbconfig/connection.php');

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'status' => false,
        'message' => 'Invalid request method. Only GET allowed.'
    ]);
    exit;
}

// Get optional limit parameter (default: top 50)
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$limit = min(max($limit, 1), 100); // Between 1 and 100

try {
    // Query to get users and their referral counts
    // Assuming there's a 'referred_by' column in user_user table
    // If not, this needs to be adjusted based on your schema
    $sql = "SELECT 
                u.id,
                u.user_qr_id,
                u.user_full_name,
                u.user_email,
                u.created_on,
                COUNT(r.id) as referral_count
            FROM user_user u
            LEFT JOIN user_user r ON r.referred_by = u.user_qr_id AND r.is_deleted = 0
            WHERE u.is_deleted = 0
            GROUP BY u.id, u.user_qr_id, u.user_full_name, u.user_email, u.created_on
            HAVING referral_count > 0
            ORDER BY referral_count DESC, u.created_on ASC
            LIMIT ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    // Bind parameter and execute
    $stmt->bind_param('i', $limit);
    $stmt->execute();

    $result = $stmt->get_result();

    $leaderboard = [];
    $rank = 1;

    while ($row = $result->fetch_assoc()) {
        $leaderboard[] = [
            'rank' => $rank++,
            'user_id' => $row['id'],
            'user_qr_id' => $row['user_qr_id'],
            'name' => $row['user_full_name'],
            'email' => $row['user_email'],
            'referral_count' => intval($row['referral_count']),
            'member_since' => $row['created_on']
        ];
    }

    echo json_encode([
        'status' => true,
        'message' => 'Leaderboard retrieved successfully.',
        'data' => $leaderboard,
        'total_leaders' => count($leaderboard)
    ]);

    $stmt->close();

} catch (Exception $e) {
    // Handle any errors
    error_log('Leaderboard fetch error: ' . $e->getMessage());

    echo json_encode([
        'status' => false,
        'message' => 'An error occurred while fetching leaderboard data. Please try again.',
        'error' => $e->getMessage()
    ]);
}

// Close database connection
$conn->close();
?>
