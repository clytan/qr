<?php
/**
 * Generate vCard (.vcf) file for user profile
 * Downloads contact information to phone
 */

// Prevent any output before headers
ob_start();

require_once('../dbconfig/connection.php');

$qr_id = $_GET['qr'] ?? $_GET['QR'] ?? null;

if (!$qr_id) {
    header('Content-Type: text/plain');
    echo 'Error: QR ID required';
    exit();
}

// Get user data - removed profile_image as it may not exist
$sql = "SELECT user_full_name, user_email, user_phone, user_address, user_tag, user_qr_id
        FROM user_user 
        WHERE user_qr_id = ? AND is_deleted = 0";
$stmt = @$conn->prepare($sql);
if (!$stmt) {
    header('Content-Type: text/plain');
    echo 'Database error';
    exit();
}
$stmt->bind_param('s', $qr_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Content-Type: text/plain');
    echo 'Error: User not found';
    exit();
}

// Get social links (optional - may not exist on all setups)
$links = [];
$sql_links = "SELECT plt.link as link_value, lkt.name as link_type_name 
              FROM user_profile_links plt 
              JOIN user_profile_links_type lkt ON plt.link_type = lkt.id 
              WHERE plt.user_id = (SELECT id FROM user_user WHERE user_qr_id = ?) 
              AND plt.is_deleted = 0 AND plt.is_public = 1";
$stmt_links = @$conn->prepare($sql_links);
if ($stmt_links) {
    $stmt_links->bind_param('s', $qr_id);
    $stmt_links->execute();
    $links_result = $stmt_links->get_result();
    while ($row = $links_result->fetch_assoc()) {
        $links[$row['link_type_name']] = $row['link_value'];
    }
    $stmt_links->close();
}

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

// Clear any output buffer and set download headers
ob_end_clean();
header('Content-Type: text/vcard; charset=utf-8');
$filename = preg_replace('/[^a-zA-Z0-9]/', '_', $name) . '_Zokli.vcf';
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($vcard));

echo $vcard;
exit();
?>
