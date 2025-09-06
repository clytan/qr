<?php
// get_qr_color.php
header('Content-Type: application/json');
require_once './dbconfig/connection.php'; // adjust path as needed

$user_id = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$qr_id = isset($_POST['qr_id']) ? $_POST['qr_id'] : '';
$response = [
    'colorDark' => '#000000', // default
    'colorLight' => '#ffffff' // default
];
if ($user_id !== '') {
    $stmt = $conn->prepare('SELECT color_dark, color_light FROM user_qr_colors WHERE user_id = ? AND is_deleted = 0 LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $user_id);
        $stmt->execute();
        $stmt->bind_result($colorDark, $colorLight);
        if ($stmt->fetch()) {
            if ($colorDark) $response['colorDark'] = $colorDark;
            if ($colorLight) $response['colorLight'] = $colorLight;
        }
        $stmt->close();
    } else {
        echo json_encode(['error' => 'DB prepare failed (user_id)', 'sqlstate' => $conn->error]);
        exit;
    }
} elseif ($qr_id !== '') {
    // Get user_id from qr_id
    $stmt = $conn->prepare('SELECT id FROM user_user WHERE user_qr_id = ? AND is_deleted = 0 LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $qr_id);
        $stmt->execute();
        $stmt->bind_result($found_user_id);
        if ($stmt->fetch() && $found_user_id) {
            $stmt->close();
            $stmt2 = $conn->prepare('SELECT color_dark, color_light FROM user_qr_colors WHERE user_id = ? AND is_deleted = 0 LIMIT 1');
            if ($stmt2) {
                $stmt2->bind_param('s', $found_user_id);
                $stmt2->execute();
                $stmt2->bind_result($colorDark, $colorLight);
                if ($stmt2->fetch()) {
                    if ($colorDark) $response['colorDark'] = $colorDark;
                    if ($colorLight) $response['colorLight'] = $colorLight;
                }
                $stmt2->close();
            } else {
                echo json_encode(['error' => 'DB prepare failed (color lookup)', 'sqlstate' => $conn->error]);
                exit;
            }
        } else {
            $stmt->close();
        }
    } else {
        echo json_encode(['error' => 'DB prepare failed (qr_id)', 'sqlstate' => $conn->error]);
        exit;
    }
}
echo json_encode($response);
