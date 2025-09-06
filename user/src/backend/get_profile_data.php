<?php
require_once('./dbconfig/connection.php');
header('Content-Type: application/json');


$user_id = null;
if (isset($_POST['user_id']) && is_numeric($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
} elseif (isset($_POST['qr']) && !empty($_POST['qr'])) {
    // Lookup user_id by unique user_qr_id
    $qr = $_POST['qr'];
    global $conn;
    $qr_sql = "SELECT id FROM user_user WHERE user_qr_id = ?";
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
        $user_id = (int)$qr_row['id'];
    } else {
        echo json_encode(['error' => 'Invalid QR code or user not found']);
        exit;
    }
} else {
    echo json_encode(['error' => 'User not logged in or invalid user id/qr']);
    exit;
}

global $conn;
// Fetch user data from user_user
$user_sql = "SELECT * FROM user_user WHERE id = ?";
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



// Map DB type names to frontend keys
$typeMap = [
    'website' => 'Website',
    'twitter' => 'Twitter',
    'instagram' => 'Instagram',
    'youtube' => 'Youtube',
    'linkedin' => 'LinkedIn',
    'snapchat' => 'SnapChat',
    'whatsapp_link' => 'WhatsApp',
    'telegram_link' => 'Telegram',
];
$reverseTypeMap = array_flip($typeMap); // e.g., 'LinkedIn' => 'linkedin_username'

// Fetch profile links joined with link type, filter is_deleted=0, include is_public
$links_sql = "SELECT plt.link as link_value, plt.is_public, lkt.name as link_type_name FROM user_profile_links plt JOIN user_profile_links_type lkt ON plt.link_type = lkt.id WHERE plt.user_id = ? AND plt.is_deleted = 0";
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
        $frontendKey = isset($reverseTypeMap[$row['link_type_name']]) ? $reverseTypeMap[$row['link_type_name']] : $row['link_type_name'];
        $links[$frontendKey] = [
            'value' => $row['link_value'],
            'is_public' => isset($row['is_public']) ? (int)$row['is_public'] : 0
        ];
    }
}
$links_stmt->close();

$response = [
    'user' => $user_data,
    'links' => $links
];

echo json_encode($response);
