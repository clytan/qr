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

// Fetch user info for billing
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
    <title>Registration Invoice - ZQR</title>
    <link rel="icon" href="../assets/logo.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="ZQR Registration Invoice" name="description" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .invoice-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .invoice-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .company-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .company-logo {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 8px;
            padding: 5px;
        }

        .company-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .company-details h2 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .company-details p {
            font-size: 13px;
            opacity: 0.95;
        }

        .invoice-number {
            text-align: right;
        }

        .invoice-number h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .badge {
            display: inline-block;
            background: #27ae60;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .invoice-content {
            padding: 40px;
        }

        .invoice-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 12px;
            font-weight: 700;
            color: #667eea;
            text-transform: uppercase;
            margin-bottom: 12px;
            letter-spacing: 1px;
        }

        .section-content {
            font-size: 14px;
            color: #333;
            line-height: 1.8;
        }

        .section-content p {
            margin-bottom: 4px;
        }

        .divider {
            height: 1px;
            background: #eee;
            margin: 30px 0;
        }

        .table-container {
            margin: 30px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead {
            background: #f8f9fa;
            border-top: 2px solid #667eea;
            border-bottom: 2px solid #667eea;
        }

        table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        table tbody tr:hover {
            background: #f9f9f9;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .summary-box {
            width: 350px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .summary-row.total {
            border-bottom: 2px solid #667eea;
            border-top: 2px solid #667eea;
            padding: 12px 0;
            font-weight: 700;
            font-size: 16px;
            color: #667eea;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 40px;
            justify-content: flex-end;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .btn i {
            margin-right: 8px;
        }

        .footer-section {
            background: #f8f9fa;
            padding: 30px 40px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 13px;
            color: #666;
        }

        .footer-section p {
            margin: 5px 0;
        }

        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .success-message strong {
            display: block;
            margin-bottom: 5px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .invoice-container {
                box-shadow: none;
                border-radius: 0;
            }

            .button-group {
                display: none;
            }

            .success-message {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .invoice-header {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }

            .invoice-number {
                text-align: center;
                margin-top: 20px;
            }

            .invoice-row {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .summary-box {
                width: 100%;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-info">
                <div class="company-logo">
                    <img src="../assets/logo.png" alt="ZQR Logo">
                </div>
                <div class="company-details">
                    <h2>ZQR.com</h2>
                    <p>123 Main Street, Mumbai, MH 400001</p>
                    <p>support@zqr.com | +91-9876543210</p>
                </div>
            </div>
            <div class="invoice-number">
                <h3><?php echo isset($invoice) ? htmlspecialchars($invoice['invoice_number']) : 'N/A'; ?></h3>
                <span class="badge"><?php echo isset($invoice) ? htmlspecialchars($invoice['status']) : 'N/A'; ?></span>
            </div>
        </div>

        <!-- Content -->
        <div class="invoice-content">
            <!-- Success Message -->
            <?php if ($user_id && $invoice): ?>
                <div class="success-message">
                    <strong>✓ Registration Successful!</strong>
                    Your registration has been completed and payment confirmed. Your invoice is ready for download.
                </div>
            <?php endif; ?>

            <!-- Invoice Details -->
            <div class="invoice-row">
                <div>
                    <div class="section-title">Invoice Details</div>
                    <div class="section-content">
                        <p><strong>Invoice Number:</strong>
                            <?php echo isset($invoice) ? htmlspecialchars($invoice['invoice_number']) : 'N/A'; ?></p>
                        <p><strong>Invoice Date:</strong>
                            <?php echo isset($invoice) ? date('d M, Y', strtotime($invoice['created_on'])) : 'N/A'; ?>
                        </p>
                        <p><strong>Invoice Type:</strong>
                            <?php echo isset($invoice) ? ucfirst(htmlspecialchars($invoice['invoice_type'])) : 'N/A'; ?>
                        </p>
                        <p><strong>Payment Mode:</strong>
                            <?php echo isset($invoice) ? htmlspecialchars($invoice['payment_mode']) : 'N/A'; ?></p>
                        <p><strong>Order Reference:</strong>
                            <?php echo isset($invoice) ? htmlspecialchars($invoice['payment_reference']) : 'N/A'; ?></p>
                    </div>
                </div>
                <div>
                    <div class="section-title">Billed To</div>
                    <div class="section-content">
                        <p><strong><?php echo htmlspecialchars($billed_to['name']); ?></strong></p>
                        <p><?php echo htmlspecialchars($billed_to['address']); ?></p>
                        <p><?php echo htmlspecialchars($billed_to['email']); ?></p>
                        <p><?php echo htmlspecialchars($billed_to['phone']); ?></p>
                    </div>
                </div>
            </div>

            <div class="divider"></div>

            <!-- Items Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th class="text-center">No.</th>
                            <th>Description</th>
                            <th class="text-right">Price</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($invoice) && $invoice['items']): ?>
                            <?php foreach ($invoice['items'] as $index => $item): ?>
                                <tr>
                                    <td class="text-center"><?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                        <p style="color: #999; font-size: 12px; margin-top: 3px;">
                                            <?php echo htmlspecialchars($item['desc']); ?></p>
                                    </td>
                                    <td class="text-right">₹ <?php echo number_format($item['price'], 2); ?></td>
                                    <td class="text-center"><?php echo intval($item['qty']); ?></td>
                                    <td class="text-right">₹ <?php echo number_format($item['total'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Summary -->
            <div class="summary-section">
                <div class="summary-box">
                    <div class="summary-row">
                        <span>Sub Total</span>
                        <span>₹ <?php echo isset($invoice) ? number_format($invoice['amount'], 2) : '0.00'; ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Discount</span>
                        <span>- ₹
                            <?php echo isset($invoice) ? number_format($invoice['discount'], 2) : '0.00'; ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>₹ <?php echo isset($invoice) ? number_format($invoice['shipping'], 2) : '0.00'; ?></span>
                    </div>
                    <div class="summary-row">
                        <span>CGST (9%)</span>
                        <span>₹ <?php echo isset($invoice) ? number_format($invoice['cgst'], 2) : '0.00'; ?></span>
                    </div>
                    <div class="summary-row">
                        <span>SGST (9%)</span>
                        <span>₹ <?php echo isset($invoice) ? number_format($invoice['sgst'], 2) : '0.00'; ?></span>
                    </div>
                    <div class="summary-row">
                        <span>IGST (0%)</span>
                        <span>₹ <?php echo isset($invoice) ? number_format($invoice['igst'], 2) : '0.00'; ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Total GST</span>
                        <span>₹ <?php echo isset($invoice) ? number_format($invoice['gst_total'], 2) : '0.00'; ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>TOTAL AMOUNT</span>
                        <span>₹
                            <?php echo isset($invoice) ? number_format($invoice['total_amount'], 2) : '0.00'; ?></span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="button-group">
                <button class="btn btn-secondary" onclick="window.print()">
                    <i class="fa fa-print"></i> Print
                </button>
                <button class="btn btn-primary" onclick="downloadPDF()">
                    <i class="fa fa-download"></i> Download PDF
                </button>
                <button class="btn btn-primary" onclick="window.location.href = '../ui/dashboard.php'">
                    <i class="fa fa-home"></i> Go to Dashboard
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-section">
            <p><strong>Thank you for registering with ZQR!</strong></p>
            <p>If you have any questions about this invoice, please contact us at support@zqr.com</p>
            <p style="margin-top: 15px; color: #999;">This is a computer generated invoice and is valid without a
                signature or seal.</p>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.querySelector('.invoice-container');
            const invoiceNumber = '<?php echo isset($invoice) ? htmlspecialchars($invoice['invoice_number']) : 'invoice'; ?>';

            const options = {
                margin: 10,
                filename: `${invoiceNumber}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
            };

            // Hide buttons before PDF generation
            const buttonGroup = document.querySelector('.button-group');
            buttonGroup.style.display = 'none';

            html2pdf().set(options).from(element).save().then(() => {
                // Show buttons again
                buttonGroup.style.display = 'flex';
            });
        }
    </script>
</body>

</html>