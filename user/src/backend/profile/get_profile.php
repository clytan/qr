<?php
require_once('../../backend/dbconfig/connection.php');
session_start();

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    $userId = $_SESSION['user_id'];

    // Get basic profile info from user_user table
    $stmt = $conn->prepare("
        SELECT user_full_name, user_phone, user_email, user_address, user_qr_id 
        FROM user_user 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $basicInfo = $result->fetch_assoc();

    // Get social media links
    $stmt = $conn->prepare("
        SELECT l.name, l.value, l.is_public 
        FROM user_profile_links_type l
        WHERE l.user_id = ? AND l.is_deleted = 0
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $socialLinks = [];
    while ($row = $result->fetch_assoc()) {
        $socialLinks[$row['name']] = [
            'value' => $row['value'],
            'is_public' => $row['is_public']
        ];
    }

    // Get followers/following count
    $stmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM user_followers WHERE followers_id = ? AND is_deleted = 0) as following_count,
            (SELECT COUNT(*) FROM user_followers WHERE user_id = ? AND is_deleted = 0) as followers_count
    ");
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();

    $response['success'] = true;
    $response['data'] = [
        'basic' => $basicInfo,
        'social_links' => $socialLinks,
        'stats' => $stats
    ];

} catch (Exception $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);