<?php
header('Content-Type: application/json');
require_once('./dbconfig/connection.php');

// Get JSON POST data
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['user_id'])) {
    echo json_encode(['status' => 0, 'message' => 'Invalid data']);
    exit;
}
$user_id = (int)$data['user_id'];
$user_full_name = $data['user_full_name'] ?? '';
$phone_number = $data['phone_number'] ?? '';
$user_email = $data['user_email'] ?? '';
$user_address = $data['user_address'] ?? '';
$public_fields = $data['public_fields'] ?? [];
$links = $data['links'] ?? [];

// Update user_user table
global $conn;
$user_sql = "UPDATE user_user SET user_full_name=?, user_phone=?, user_email=?, user_address=? WHERE id=?";
$user_stmt = $conn->prepare($user_sql);
if (!$user_stmt) {
    echo json_encode(['status' => 0, 'message' => 'DB error: ' . $conn->error]);
    exit;
}
$user_stmt->bind_param('ssssi', $user_full_name, $phone_number, $user_email, $user_address, $user_id);
$user_stmt->execute();
$user_stmt->close();

// Update or insert links in user_profile_links
foreach ($links as $type => $linkData) {
    $value = is_array($linkData) && isset($linkData['value']) ? $linkData['value'] : '';
    $is_public = is_array($linkData) && isset($linkData['is_public']) ? (int)$linkData['is_public'] : 0;
    // Get link_type id from user_profile_link_type
    $type_id = null;
    $type_sql = "SELECT id FROM user_profile_link_type WHERE name = ? LIMIT 1";
    $type_stmt = $conn->prepare($type_sql);
    if ($type_stmt) {
        $type_stmt->bind_param('s', $type);
        $type_stmt->execute();
        $type_stmt->bind_result($type_id);
        $type_stmt->fetch();
        $type_stmt->close();
    }
    if (!$type_id) continue;
    // Check if link exists
    $link_id = null;
    $check_sql = "SELECT id FROM user_profile_links WHERE user_id = ? AND link_type = ?";
    $check_stmt = $conn->prepare($check_sql);
    if ($check_stmt) {
        $check_stmt->bind_param('ii', $user_id, $type_id);
        $check_stmt->execute();
        $check_stmt->bind_result($link_id);
        $check_stmt->fetch();
        $check_stmt->close();
    }
    if ($link_id) {
        // Update
        $update_sql = "UPDATE user_profile_links SET link = ?, is_public = ?, is_deleted = 0, updated_by = ?, updated_on = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        if ($update_stmt) {
            $update_stmt->bind_param('siii', $value, $is_public, $user_id, $link_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
    } else {
        // insert
        $insert_sql = "INSERT INTO user_profile_links (user_id, link_type, link, is_public, is_deleted, created_by, created_on, updated_by, updated_on) VALUES (?, ?, ?, ?, 0, ?, NOW(), ?, NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        if ($insert_stmt) {
            $insert_stmt->bind_param('iisi ii', $user_id, $type_id, $value, $is_public, $user_id, $user_id);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
    }
}

echo json_encode(['status' => 1, 'message' => 'Profile updated successfully']);
