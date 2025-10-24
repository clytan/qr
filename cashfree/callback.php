<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postData = file_get_contents('php://input');
    $data = json_decode($postData, true);

    if ($data['payment']['payment_status'] == "SUCCESS") {
        $payment_id = $data['payment']['cf_payment_id'];
        $bank_reference = $data['payment']['bank_reference'];
        echo "Payment Successful";
    } else {
        echo "Payment Pending or Failed";
    }
} else {
    echo 'This script only accepts POST requests.';
}
?>