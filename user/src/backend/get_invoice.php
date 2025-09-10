<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
include_once('./dbconfig/connection.php');

// Fetch latest invoice for this user
$sqlInvoice = "SELECT * FROM user_invoice WHERE user_id = ? ORDER BY created_on DESC LIMIT 1";
$stmtInvoice = $conn->prepare($sqlInvoice);
$stmtInvoice->bind_param('i', $user_id);
$stmtInvoice->execute();
$resultInvoice = $stmtInvoice->get_result();

if ($resultInvoice->num_rows > 0) {
    $row = $resultInvoice->fetch_assoc();
    // Fetch user info for billing
    $sqlUser = "SELECT user_full_name, user_address, user_email, user_phone FROM user_user WHERE id = ?";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param('i', $user_id);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    $billed_to = [
        'name' => '',
        'address' => '',
        'email' => '',
        'phone' => ''
    ];
    if ($resultUser->num_rows > 0) {
        $user = $resultUser->fetch_assoc();
        $billed_to = [
            'name' => $user['user_full_name'] ?: '',
            'address' => $user['user_address'] ?: '',
            'email' => $user['user_email'] ?: '',
            'phone' => $user['user_phone'] ?: ''
        ];
    }
    $stmtUser->close();
    $invoice = [
        'invoice_number' => $row['invoice_number'],
        'order_number' => $row['invoice_number'],
        'invoice_type' => $row['invoice_type'],
        'amount' => floatval($row['amount']),
        'cgst' => floatval($row['cgst']),
        'sgst' => floatval($row['sgst']),
        'igst' => floatval($row['igst']),
        'gst_total' => floatval($row['gst_total']),
        'total_amount' => floatval($row['total_amount']),
        'status' => $row['status'],
        'payment_mode' => $row['payment_mode'],
        'payment_reference' => $row['payment_reference'],
        'created_on' => $row['created_on'],
        'discount' => 0.00,
        'shipping' => 0.00,
        'items' => [
            [
                'name' => ucfirst($row['invoice_type']) . ' Fee',
                'desc' => 'User ' . $row['invoice_type'] . ' for ZQR',
                'price' => floatval($row['amount']),
                'qty' => 1,
                'total' => floatval($row['amount'])
            ]
        ]
    ];
    echo json_encode(['status' => true, 'invoice' => $invoice, 'billed_to' => $billed_to]);
} else {
    echo json_encode(['status' => false, 'message' => 'No invoice found']);
}
$stmtInvoice->close();
$conn->close();
