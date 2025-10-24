<?php
if (isset($_GET['order_id'])) {
    $orderId = $_GET['order_id'];
    $apiUrl = "https://api.cashfree.com/pg/orders/{$orderId}";

    $headers = array(
        'x-api-version: 2023-08-01',
        'x-client-id: ' . 'YOUR_CLIENT_ID',
        'x-client-secret: ' . 'YOUR_CLIENT_SECRET',
        'Content-Type: application/json'
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    echo $response;
}
?>