<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';

// Company info (static)
$company = [
    'name' => 'ZQR.com',
    'address' => '123 Main Street, Mumbai, MH 400001',
    'email' => 'support@zqr.com',
    'phone' => '+91-9876543210',
];

// Connect to DB
include_once('../backend/dbconfig/connection.php');

// Fetch user info for billing (if available)
$billed_to = [
    'name' => '',
    'address' => '',
    'email' => '',
    'phone' => '',
];
if ($user_id) {
    $sqlUser = "SELECT user_full_name, user_address, user_email, user_phone FROM user_user WHERE id = ?";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param('i', $user_id);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    if ($resultUser->num_rows > 0) {
        $rowUser = $resultUser->fetch_assoc();
        $billed_to['name'] = $rowUser['user_full_name'] ?: '';
        $billed_to['address'] = $rowUser['user_address'] ?: '';
        $billed_to['email'] = $rowUser['user_email'] ?: '';
        $billed_to['phone'] = $rowUser['user_phone'] ?: '';
    }
    $stmtUser->close();
}

// Fetch latest invoice for this user
$invoice = null;
if ($user_id) {
    $sqlInvoice = "SELECT * FROM user_invoice WHERE user_id = ? ORDER BY created_on DESC LIMIT 1";
    $stmtInvoice = $conn->prepare($sqlInvoice);
    $stmtInvoice->bind_param('i', $user_id);
    $stmtInvoice->execute();
    $resultInvoice = $stmtInvoice->get_result();
    if ($resultInvoice->num_rows > 0) {
        $row = $resultInvoice->fetch_assoc();
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
    }
    $stmtInvoice->close();
}
?>
<!DOCTYPE html>
<html lang="zxx">

<head>
    <title>Invoice - ZQR</title>
    <link rel="icon" href="../assets/images/icon-red.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="ZQR Invoice" name="description" />
    <style>
        .invoice-box {
            background: #fff;
            border-radius: 8px;
            padding: 32px 24px;
            margin: 32px 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
            font-size: 16px;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid #eee;
            padding-bottom: 16px;
        }

        .invoice-title {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
        }

        .invoice-status {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .badge-paid {
            background: #27ae60;
            color: #fff;
            border-radius: 4px;
            padding: 4px 12px;
            font-size: 1rem;
        }

        .invoice-section {
            margin-top: 24px;
        }

        .invoice-section strong {
            font-size: 1.1rem;
        }

        .order-summary {
            margin-top: 32px;
        }

        .order-summary table {
            width: 100%;
            border-collapse: collapse;
        }

        .order-summary th,
        .order-summary td {
            border-bottom: 1px solid #eee;
            padding: 8px 6px;
            text-align: left;
        }

        .order-summary th {
            background: #fafafa;
        }

        .order-summary tfoot td {
            font-weight: 600;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .mt-2 {
            margin-top: 12px;
        }

        .mt-4 {
            margin-top: 24px;
        }

        .btn {
            display: inline-block;
            padding: 8px 18px;
            border-radius: 4px;
            background: #27ae60;
            color: #fff;
            border: none;
            font-size: 1rem;
            cursor: pointer;
            margin-right: 8px;
        }

        .btn-secondary {
            background: #3498db;
        }
    </style>
    <?php include('../components/csslinks.php') ?>
</head>

<body class="dark-scheme de-grey">
    <div id="wrapper">
        <?php include('../components/header.php') ?>
        <div class="no-bottom no-top" id="content">
            <div id="top"></div>
            <div class="container">
                <div id="invoice-box" class="invoice-box">
                    <div style='padding:40px;text-align:center;'>
                        <h3>Loading invoice...</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('../components/footer.php'); ?>
    </div>
    <?php include('../components/jslinks.php'); ?>
    <script src="custom_js/custom_invoice.js"></script>
</body>

</html>