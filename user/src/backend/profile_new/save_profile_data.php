<?php
header('Content-Type: application/json');
require_once('.././dbconfig/connection.php');

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
$user_pincode = $data['user_pincode'] ?? '';
$user_landmark = $data['user_landmark'] ?? '';
$is_public_address = isset($data['is_public_address']) ? (int)$data['is_public_address'] : 1;
$fields = $data['fields'] ?? [];
$links = $data['links'] ?? [];

// Helper function to clean social media values - extract username from full URLs
function cleanSocialValue($value, $type) {
    if (empty($value)) return '';
    $value = trim($value);
    
    // URL patterns for social platforms to extract username
    $patterns = [
        'instagram_username' => '/(?:https?:\/\/)?(?:www\.)?instagram\.com\/([^\/?#]+)/i',
        'facebook_username' => '/(?:https?:\/\/)?(?:www\.)?facebook\.com\/([^\/?#]+)/i',
        'twitter_username' => '/(?:https?:\/\/)?(?:www\.)?(?:twitter|x)\.com\/([^\/?#]+)/i',
        'youtube_username' => '/(?:https?:\/\/)?(?:www\.)?youtube\.com\/(?:@|channel\/|c\/)?([^\/?#]+)/i',
        'linkedin_username' => '/(?:https?:\/\/)?(?:www\.)?linkedin\.com\/in\/([^\/?#]+)/i',
        'snapchat_username' => '/(?:https?:\/\/)?(?:www\.)?snapchat\.com\/add\/([^\/?#]+)/i',
        'telegram_link' => '/(?:https?:\/\/)?(?:www\.)?t\.me\/([^\/?#]+)/i',
        'whatsapp_link' => '/(?:https?:\/\/)?(?:wa\.me\/)?(\+?\d+)/i',
    ];
    
    // For website, validate it's a proper URL
    if ($type === 'website') {
        // If it doesn't start with http, add https://
        if (!preg_match('/^https?:\/\//i', $value)) {
            // Check if it looks like a valid domain
            if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}/', $value)) {
                $value = 'https://' . $value;
            } else {
                // Invalid website - reject values like "IIII"
                return '';
            }
        }
        return $value;
    }
    
    // For social platforms, try to extract username from URL
    if (isset($patterns[$type])) {
        if (preg_match($patterns[$type], $value, $matches)) {
            return $matches[1]; // Return just the username
        }
    }
    
    // If not a URL, remove @ prefix and return as username
    $value = ltrim($value, '@');
    
    // Basic validation - username should be alphanumeric with underscores/dots
    // Allow more characters for WhatsApp (phone numbers with +)
    if ($type === 'whatsapp_link') {
        return preg_replace('/[^\d+]/', '', $value);
    }
    
    return $value;
}

// Update user_user table
global $conn;
$user_sql = "UPDATE user_user SET user_full_name=?, user_phone=?, user_email=?, user_address=?, user_pincode=?, user_landmark=?, is_public_address=? WHERE id=?";
$user_stmt = $conn->prepare($user_sql);
if (!$user_stmt) {
    echo json_encode(['status' => 0, 'message' => 'DB error: ' . $conn->error]);
    exit;
}
$user_stmt->bind_param('ssssssii', $user_full_name, $phone_number, $user_email, $user_address, $user_pincode, $user_landmark, $is_public_address, $user_id);
$user_stmt->execute();
$user_stmt->close();



// Map frontend keys to DB type names
$typeMap = [
    'website' => 'Website',
    'facebook_username' => 'Facebook',
    'twitter_username' => 'Twitter',
    'instagram_username' => 'Instagram',
    'youtube_username' => 'Youtube',
    'linkedin_username' => 'LinkedIn',
    'snapchat_username' => 'Snapchat',
    'whatsapp_link' => 'WhatsApp',
    'telegram_link' => 'Telegram',
    'google_maps_link' => 'GoogleMaps',
];

foreach ($fields as $type) {
    $dbType = isset($typeMap[$type]) ? $typeMap[$type] : $type;
    $value = '';
    $is_public = 0;
    if (isset($links[$type]) && is_array($links[$type])) {
        $linkData = $links[$type];
    } elseif (isset($links[$type]) && is_object($links[$type])) {
        $linkData = get_object_vars($links[$type]);
    } else {
        continue;
    }
    if (isset($linkData['value']) && is_string($linkData['value'])) {
        $value = trim($linkData['value']);
    }
    if ($value === '') continue;
    if (isset($linkData['is_public'])) {
        $is_public = (int)$linkData['is_public'];
    }
    
    // Clean up social media value - extract username from full URLs
    $value = cleanSocialValue($value, $type);

    // Get link_type id from user_profile_links_type
    $type_id = null;
    $type_sql = "SELECT id FROM user_profile_links_type WHERE name = ? AND is_deleted=0";
    $type_stmt = $conn->prepare($type_sql);
    if ($type_stmt) {
        $type_stmt->bind_param('s', $dbType);
        $type_stmt->execute();
        $type_stmt->bind_result($type_id);
        $type_stmt->fetch();
        $type_stmt->close();
    }
    if (!$type_id) {
        continue;
    }

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
        $update_sql = "UPDATE user_profile_links SET link = ?, is_public = ?, is_deleted = 0, updated_by = ?, updated_on = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        if ($update_stmt) {
            $update_stmt->bind_param('siii', $value, $is_public, $user_id, $link_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
    } else {
        $insert_sql = "INSERT INTO user_profile_links (user_id, link_type, link, is_public, is_deleted, created_by, created_on, updated_by, updated_on) VALUES (?, ?, ?, ?, 0, ?, NOW(), ?, NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        if ($insert_stmt) {
            $insert_stmt->bind_param('iisiii', $user_id, $type_id, $value, $is_public, $user_id, $user_id);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
    }
}

echo json_encode(['status' => 1, 'message' => 'Profile updated successfully']);