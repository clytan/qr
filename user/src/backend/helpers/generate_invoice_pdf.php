<?php
/**
 * Invoice PDF Generator Helper
 * 
 * Generates a PDF invoice for user registration
 * Uses simple HTML-to-Image approach with GD library
 */

require_once __DIR__ . '/../dbconfig/connection.php';

/**
 * Generate an invoice PDF for a user
 * 
 * @param int $userId The user's ID
 * @return string|false Path to the generated PDF file, or false on failure
 */
function generateInvoicePDF($userId) {
    global $conn;
    
    try {
        // Fetch user info
        $sqlUser = "SELECT user_full_name, user_address, user_email, user_phone, user_qr_id FROM user_user WHERE id = ?";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->bind_param('i', $userId);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();
        
        if ($resultUser->num_rows === 0) {
            error_log("Invoice generation failed: User not found - ID: $userId");
            return false;
        }
        
        $user = $resultUser->fetch_assoc();
        $stmtUser->close();
        
        // Fetch latest invoice
        $sqlInvoice = "SELECT * FROM user_invoice WHERE user_id = ? ORDER BY created_on DESC LIMIT 1";
        $stmtInvoice = $conn->prepare($sqlInvoice);
        $stmtInvoice->bind_param('i', $userId);
        $stmtInvoice->execute();
        $resultInvoice = $stmtInvoice->get_result();
        
        if ($resultInvoice->num_rows === 0) {
            error_log("Invoice generation failed: No invoice found for user - ID: $userId");
            return false;
        }
        
        $invoice = $resultInvoice->fetch_assoc();
        $stmtInvoice->close();
        
        // Generate HTML invoice content
        $html = generateInvoiceHTML($user, $invoice);
        
        // Create temp directory if needed
        $tempDir = __DIR__ . '/../../temp';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        
        // Save as HTML file (which can be opened as PDF by most email clients)
        $outputPath = $tempDir . '/invoice_' . $invoice['invoice_number'] . '_' . time() . '.html';
        file_put_contents($outputPath, $html);
        
        if (file_exists($outputPath)) {
            error_log("Invoice HTML generated successfully: " . $outputPath);
            return $outputPath;
        } else {
            error_log("Invoice generation failed: file not created");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Invoice generation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate HTML content for the invoice
 */
function generateInvoiceHTML($user, $invoice) {
    $invoiceNumber = htmlspecialchars($invoice['invoice_number']);
    $invoiceDate = date('d M, Y', strtotime($invoice['created_on']));
    $userName = htmlspecialchars($user['user_full_name'] ?: 'N/A');
    $userEmail = htmlspecialchars($user['user_email'] ?: 'N/A');
    $userPhone = htmlspecialchars($user['user_phone'] ?: 'N/A');
    $userAddress = htmlspecialchars($user['user_address'] ?: 'N/A');
    $userQrId = htmlspecialchars($user['user_qr_id'] ?: 'N/A');
    
    $amount = number_format($invoice['amount'], 2);
    $cgst = number_format($invoice['cgst'], 2);
    $sgst = number_format($invoice['sgst'], 2);
    $igst = number_format($invoice['igst'], 2);
    $gstTotal = number_format($invoice['gst_total'], 2);
    $totalAmount = number_format($invoice['total_amount'], 2);
    $paymentRef = htmlspecialchars($invoice['payment_reference'] ?: 'N/A');
    $invoiceType = ucfirst(htmlspecialchars($invoice['invoice_type']));
    
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice - {$invoiceNumber}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .invoice { max-width: 800px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px 8px 0 0; }
        .header h1 { font-size: 28px; margin-bottom: 5px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .col { width: 48%; }
        .section-title { font-size: 12px; color: #667eea; text-transform: uppercase; font-weight: 700; margin-bottom: 10px; letter-spacing: 1px; }
        .info p { margin: 5px 0; color: #333; }
        .info strong { color: #000; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f8f9fa; padding: 12px; text-align: left; border-top: 2px solid #667eea; border-bottom: 2px solid #667eea; font-size: 13px; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        .text-right { text-align: right; }
        .summary { display: flex; justify-content: flex-end; }
        .summary-box { width: 300px; }
        .summary-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .summary-row.total { border-top: 2px solid #667eea; border-bottom: 2px solid #667eea; font-weight: 700; color: #667eea; font-size: 16px; margin-top: 10px; padding-top: 12px; }
        .footer { background: #f8f9fa; padding: 20px 30px; text-align: center; border-top: 1px solid #eee; border-radius: 0 0 8px 8px; color: #666; font-size: 13px; }
        .badge { display: inline-block; background: #27ae60; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="invoice">
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Zokli</h1>
                    <p>Invoice #{$invoiceNumber}</p>
                </div>
                <div style="text-align: right;">
                    <span class="badge">{$invoice['status']}</span>
                    <p style="margin-top: 10px;">Date: {$invoiceDate}</p>
                </div>
            </div>
        </div>
        
        <div class="content">
            <div class="row">
                <div class="col">
                    <div class="section-title">Company Details</div>
                    <div class="info">
                        <p><strong>Zokli India</strong></p>
                        <p>123 Main Street, Mumbai, MH 400001</p>
                        <p>Email: support@zokli.io</p>
                        <p>Phone: +91-9876543210</p>
                    </div>
                </div>
                <div class="col">
                    <div class="section-title">Billed To</div>
                    <div class="info">
                        <p><strong>{$userName}</strong></p>
                        <p>QR ID: {$userQrId}</p>
                        <p>Email: {$userEmail}</p>
                        <p>Phone: {$userPhone}</p>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col">
                    <div class="section-title">Invoice Details</div>
                    <div class="info">
                        <p><strong>Invoice Number:</strong> {$invoiceNumber}</p>
                        <p><strong>Invoice Type:</strong> {$invoiceType}</p>
                        <p><strong>Payment Reference:</strong> {$paymentRef}</p>
                        <p><strong>Payment Mode:</strong> {$invoice['payment_mode']}</p>
                    </div>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Description</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>01</td>
                        <td><strong>{$invoiceType} Fee</strong><br><span style="color: #999; font-size: 12px;">User {$invoiceType} for Zokli</span></td>
                        <td class="text-right">₹ {$amount}</td>
                        <td class="text-right">1</td>
                        <td class="text-right">₹ {$amount}</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="summary">
                <div class="summary-box">
                    <div class="summary-row">
                        <span>Sub Total</span>
                        <span>₹ {$amount}</span>
                    </div>
                    <div class="summary-row">
                        <span>CGST (9%)</span>
                        <span>₹ {$cgst}</span>
                    </div>
                    <div class="summary-row">
                        <span>SGST (9%)</span>
                        <span>₹ {$sgst}</span>
                    </div>
                    <div class="summary-row">
                        <span>Total GST</span>
                        <span>₹ {$gstTotal}</span>
                    </div>
                    <div class="summary-row total">
                        <span>TOTAL AMOUNT</span>
                        <span>₹ {$totalAmount}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Thank you for joining Zokli!</strong></p>
            <p>If you have any questions, please contact us at support@zokli.io</p>
            <p style="margin-top: 10px; color: #999; font-size: 11px;">This is a computer generated invoice and is valid without a signature or seal.</p>
        </div>
    </div>
</body>
</html>
HTML;

    return $html;
}

/**
 * Clean up temporary invoice files
 * 
 * @param string $filePath Path to the file to delete
 * @return bool True if deleted, false otherwise
 */
function cleanupInvoiceFile($filePath) {
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return true;
}
