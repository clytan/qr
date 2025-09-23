<?php
session_start();
header('Content-Type: application/json');

$response = [
    'logged_in' => false,
    'user_id' => null
];

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $response['logged_in'] = true;
    $response['user_id'] = $_SESSION['user_id'];
}

echo json_encode($response);
?>