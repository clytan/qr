<?php
require_once('.././dbconfig/connection.php');
header('Content-Type: application/json');

$user_id = null;
if (isset($_POST['user_id']) && is_numeric($_POST['user_id'])) {
    $user_id = (int) $_POST['user_id'];
} elseif (isset($_POST['qr']) && !empty($_POST['qr'])) {
    // Lookup user_id by QR code from user_user table
    $qr = $_POST['qr'];
    global $conn;

    // Get user_id from user_user table using user_qr_id column
    $qr_sql = "SELECT id FROM user_user WHERE user_qr_id = ? AND is_deleted = 0";
    $qr_stmt = $conn->prepare($qr_sql);
    if (!$qr_stmt) {
        echo json_encode(['error' => 'DB error: ' . $conn->error]);
        exit;
    }
    $qr_stmt->bind_param('s', $qr);
    $qr_stmt->execute();
    $qr_result = $qr_stmt->get_result();
    $qr_row = $qr_result->fetch_assoc();
    $qr_stmt->close();

    if ($qr_row && isset($qr_row['id'])) {
        $user_id = (int) $qr_row['id'];
    } else {
        echo json_encode(['error' => 'Invalid QR code or user not found', 'qr_searched' => $qr]);
        exit;
    }
} else {
    echo json_encode(['error' => 'User not logged in or invalid user id/qr']);
    exit;
}

global $conn;
// Fetch user data from user_user using the correct user_id
$user_sql = "SELECT * FROM user_user WHERE id = ? AND is_deleted = 0";
$user_stmt = $conn->prepare($user_sql);
if (!$user_stmt) {
    echo json_encode(['error' => 'DB error: ' . $conn->error]);
    exit;
}
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_stmt->close();

if (!$user_data) {
    echo json_encode(['error' => 'User data not found', 'user_id_searched' => $user_id]);
    exit;
}

// Map DB type names to frontend keys
$typeMap = [
    'Website' => 'website',
    'Facebook' => 'facebook_username',
    'Twitter' => 'twitter_username',
    'Instagram' => 'instagram_username',
    'Youtube' => 'youtube_username',
    'LinkedIn' => 'linkedin_username',
    'SnapChat' => 'snapchat_username',
    'Snapchat' => 'snapchat_username',
    'WhatsApp' => 'whatsapp_link',
    'Telegram' => 'telegram_link',
    'GoogleMaps' => 'google_maps_link',
];

// Check if this is a public view (QR parameter exists)
$is_public_view = isset($_POST['qr']);

// Fetch profile links - only public ones if it's a public view
$links_sql = "SELECT plt.link as link_value, plt.is_public, lkt.name as link_type_name 
              FROM user_profile_links plt 
              JOIN user_profile_links_type lkt ON plt.link_type = lkt.id 
              WHERE plt.user_id = ? AND plt.is_deleted = 0";

if ($is_public_view) {
    $links_sql .= " AND plt.is_public = 1";
}

$links_stmt = $conn->prepare($links_sql);
if (!$links_stmt) {
    echo json_encode(['error' => 'DB error: ' . $conn->error]);
    exit;
}
$links_stmt->bind_param('i', $user_id);
$links_stmt->execute();
$links_result = $links_stmt->get_result();
$links = [];

while ($row = $links_result->fetch_assoc()) {
    if (!empty($row['link_type_name'])) {
        $frontendKey = isset($typeMap[$row['link_type_name']]) ? $typeMap[$row['link_type_name']] : strtolower($row['link_type_name']);
        $links[$frontendKey] = [
            'value' => $row['link_value'],
            'is_public' => isset($row['is_public']) ? (int) $row['is_public'] : 0
        ];
    }
}
$links_stmt->close();

// Calculate subscription status
$subscription = null;
if (!empty($user_data['created_on'])) {
    $created = new DateTime($user_data['created_on']);
    $expiry = clone $created;
    $expiry->add(new DateInterval('P1Y')); // Add 1 year
    
    $now = new DateTime();
    $diff = $now->diff($expiry);
    $days_remaining = $diff->invert ? -$diff->days : $diff->days;
    
    // Grace period: 2 days after expiry
    $is_expired = $days_remaining < -2;
    $is_in_grace = $days_remaining >= -2 && $days_remaining < 0;
    
    // TEST MODE: Add ?test_renewal=1 to profile.php URL to force show renew button
    $test_mode = isset($_POST['test_renewal']) && $_POST['test_renewal'] == '1';
    $needs_renewal = $test_mode || $days_remaining <= 30; // Show renewal button if 30 days or less
    
    // Renewal prices based on tier
    $tier_prices = [
        'gold' => 9999,
        'silver' => 5555,
        'normal' => 999,
        'student' => 999
    ];
    $user_tier = strtolower($user_data['user_tag'] ?? 'normal');
    $renewal_price = $tier_prices[$user_tier] ?? 999;
    
    $subscription = [
        'tier' => $user_data['user_tag'] ?? 'normal',
        'registered_on' => $user_data['created_on'],
        'expires_on' => $expiry->format('Y-m-d H:i:s'),
        'days_remaining' => $days_remaining,
        'is_expired' => $is_expired,
        'is_in_grace' => $is_in_grace,
        'needs_renewal' => $needs_renewal,
        'renewal_price' => $renewal_price
    ];
}

$response = [
    'user' => $user_data,
    'links' => $links,
    'subscription' => $subscription,
    'is_public_view' => $is_public_view,
    'debug' => [
        'user_id' => $user_id,
        'qr_id' => isset($_POST['qr']) ? $_POST['qr'] : 'not_provided',
        'user_found' => !empty($user_data),
        'links_count' => count($links)
    ]
];

echo json_encode($response);
?>