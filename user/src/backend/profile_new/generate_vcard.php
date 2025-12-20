<?php
/**
 * Generate vCard (.vcf) file for user profile
 * Downloads contact information to phone
 */
header('Content-Type: text/vcard; charset=utf-8');

require_once('../dbconfig/connection.php');

$qr_id = $_GET['qr'] ?? $_GET['QR'] ?? null;

if (!$qr_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'QR ID required']);
    exit();
}

// Get user data
$sql = "SELECT u.user_full_name, u.user_email, u.user_phone, u.user_address, u.user_tag, u.user_qr_id,
               u.profile_image
        FROM user_user u 
        WHERE u.user_qr_id = ? AND u.is_deleted = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $qr_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not found']);
    exit();
}

// Get social links
$sql_links = "SELECT plt.link as link_value, lkt.name as link_type_name 
              FROM user_profile_links plt 
              JOIN user_profile_links_type lkt ON plt.link_type = lkt.id 
              WHERE plt.user_id = (SELECT id FROM user_user WHERE user_qr_id = ?) 
              AND plt.is_deleted = 0 AND plt.is_public = 1";
$stmt_links = $conn->prepare($sql_links);
$stmt_links->bind_param('s', $qr_id);
$stmt_links->execute();
$links_result = $stmt_links->get_result();
$links = [];
while ($row = $links_result->fetch_assoc()) {
    $links[$row['link_type_name']] = $row['link_value'];
}
$stmt_links->close();

// Generate vCard
$name = $user['user_full_name'] ?? 'Contact';
$nameParts = explode(' ', $name, 2);
$firstName = $nameParts[0] ?? '';
$lastName = $nameParts[1] ?? '';

$vcard = "BEGIN:VCARD\r\n";
$vcard .= "VERSION:3.0\r\n";
$vcard .= "N:" . $lastName . ";" . $firstName . ";;;\r\n";
$vcard .= "FN:" . $name . "\r\n";

if (!empty($user['user_phone'])) {
    $vcard .= "TEL;TYPE=CELL:" . $user['user_phone'] . "\r\n";
}

if (!empty($user['user_email'])) {
    $vcard .= "EMAIL:" . $user['user_email'] . "\r\n";
}

if (!empty($user['user_address'])) {
    $vcard .= "ADR;TYPE=HOME:;;" . str_replace("\n", ", ", $user['user_address']) . ";;;;\r\n";
}

// Organization/Title based on tier
$tier = ucfirst($user['user_tag'] ?? 'Member');
$vcard .= "TITLE:Zokli " . $tier . " Member\r\n";
$vcard .= "ORG:Zokli\r\n";

// Add website/social links as URLs
if (!empty($links['Website'])) {
    $vcard .= "URL:" . $links['Website'] . "\r\n";
}

// Add profile URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$subdirectory = (strpos($host, 'localhost') !== false) ? '/qr' : '';
$profileUrl = $protocol . '://' . $host . $subdirectory . '/user/src/ui/profile.php?qr=' . $user['user_qr_id'];
$vcard .= "URL;TYPE=WORK:" . $profileUrl . "\r\n";

// Add note with social links
$socialNote = "Zokli Profile: " . $profileUrl;
if (!empty($links['Instagram'])) {
    $socialNote .= " | Instagram: @" . $links['Instagram'];
}
if (!empty($links['WhatsApp'])) {
    $socialNote .= " | WhatsApp: " . $links['WhatsApp'];
}
$vcard .= "NOTE:" . $socialNote . "\r\n";

$vcard .= "END:VCARD\r\n";

// Set download headers
$filename = preg_replace('/[^a-zA-Z0-9]/', '_', $name) . '_Zokli.vcf';
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($vcard));

echo $vcard;
?>
