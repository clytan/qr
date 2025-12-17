<?php
/**
 * Invoice PDF Generator Helper
 * 
 * Generates a PDF invoice for user registration
 * Uses a simple inline PDF generator that works without external dependencies
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
        
        // Create temp directory if needed
        $tempDir = __DIR__ . '/../../temp';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        
        // Generate PDF using simple PDF class
        $pdf = new SimplePDF();
        $pdf->setTitle('Invoice - ' . $invoice['invoice_number']);
        
        // Add content
        $pdf->addPage();
        
        // Header
        $pdf->setFont('Helvetica', 'B', 24);
        $pdf->setColor(102, 126, 234); // Purple gradient color
        $pdf->addText(50, 50, 'ZOKLI');
        
        $pdf->setFont('Helvetica', '', 10);
        $pdf->setColor(100, 100, 100);
        $pdf->addText(50, 70, 'Blrleaf Technology OPC Private Limited');
        $pdf->addText(50, 82, 'Email: Zokli.india@gmail.com');
        $pdf->addText(50, 94, 'Phone: +91 9980769600');
        
        // Invoice details on right
        $pdf->setFont('Helvetica', 'B', 16);
        $pdf->setColor(0, 0, 0);
        $pdf->addText(400, 50, 'INVOICE');
        
        $pdf->setFont('Helvetica', '', 10);
        $pdf->addText(400, 70, 'Invoice #: ' . $invoice['invoice_number']);
        $pdf->addText(400, 82, 'Date: ' . date('d M, Y', strtotime($invoice['created_on'])));
        $pdf->addText(400, 94, 'Status: ' . $invoice['status']);
        
        // Horizontal line
        $pdf->addLine(50, 120, 550, 120);
        
        // Bill To section
        $pdf->setFont('Helvetica', 'B', 12);
        $pdf->setColor(102, 126, 234);
        $pdf->addText(50, 145, 'BILL TO');
        
        $pdf->setFont('Helvetica', '', 10);
        $pdf->setColor(0, 0, 0);
        $userName = $user['user_full_name'] ?: 'N/A';
        $userEmail = $user['user_email'] ?: 'N/A';
        $userPhone = $user['user_phone'] ?: 'N/A';
        $userQrId = $user['user_qr_id'] ?: 'N/A';
        
        $pdf->addText(50, 165, $userName);
        $pdf->addText(50, 177, 'QR ID: ' . $userQrId);
        $pdf->addText(50, 189, 'Email: ' . $userEmail);
        $pdf->addText(50, 201, 'Phone: ' . $userPhone);
        
        // Payment details on right
        $pdf->setFont('Helvetica', 'B', 12);
        $pdf->setColor(102, 126, 234);
        $pdf->addText(350, 145, 'PAYMENT DETAILS');
        
        $pdf->setFont('Helvetica', '', 10);
        $pdf->setColor(0, 0, 0);
        $pdf->addText(350, 165, 'Payment Mode: ' . ($invoice['payment_mode'] ?: 'Online'));
        $pdf->addText(350, 177, 'Reference: ' . ($invoice['payment_reference'] ?: 'N/A'));
        $pdf->addText(350, 189, 'Type: ' . ucfirst($invoice['invoice_type']));
        
        // Items table header
        $pdf->setFont('Helvetica', 'B', 10);
        $pdf->setColor(255, 255, 255);
        $pdf->addRect(50, 230, 500, 25, [102, 126, 234]);
        $pdf->addText(60, 247, 'Description');
        $pdf->addText(350, 247, 'Qty');
        $pdf->addText(420, 247, 'Price');
        $pdf->addText(490, 247, 'Total');
        
        // Item row
        $pdf->setFont('Helvetica', '', 10);
        $pdf->setColor(0, 0, 0);
        $invoiceType = ucfirst($invoice['invoice_type']);
        $amount = number_format($invoice['amount'], 2);
        
        $pdf->addRect(50, 255, 500, 30, [248, 249, 250], false);
        $pdf->addText(60, 275, $invoiceType . ' Fee - Zokli Membership');
        $pdf->addText(360, 275, '1');
        $pdf->addText(410, 275, 'Rs. ' . $amount);
        $pdf->addText(480, 275, 'Rs. ' . $amount);
        
        // Summary
        $summaryY = 310;
        $pdf->setFont('Helvetica', '', 10);
        
        $pdf->addText(380, $summaryY, 'Sub Total:');
        $pdf->addText(480, $summaryY, 'Rs. ' . $amount);
        
        $cgst = number_format($invoice['cgst'], 2);
        $sgst = number_format($invoice['sgst'], 2);
        $gstTotal = number_format($invoice['gst_total'], 2);
        $totalAmount = number_format($invoice['total_amount'], 2);
        
        $pdf->addText(380, $summaryY + 15, 'CGST (9%):');
        $pdf->addText(480, $summaryY + 15, 'Rs. ' . $cgst);
        
        $pdf->addText(380, $summaryY + 30, 'SGST (9%):');
        $pdf->addText(480, $summaryY + 30, 'Rs. ' . $sgst);
        
        $pdf->addLine(380, $summaryY + 45, 550, $summaryY + 45);
        
        $pdf->setFont('Helvetica', 'B', 12);
        $pdf->setColor(102, 126, 234);
        $pdf->addText(380, $summaryY + 60, 'TOTAL:');
        $pdf->addText(470, $summaryY + 60, 'Rs. ' . $totalAmount);
        
        // Footer
        $pdf->setFont('Helvetica', '', 9);
        $pdf->setColor(100, 100, 100);
        $pdf->addText(50, 500, 'Thank you for joining Zokli!');
        $pdf->addText(50, 515, 'For questions, contact: Zokli.india@gmail.com');
        $pdf->addText(50, 530, 'This is a computer generated invoice and is valid without signature.');
        
        // Save PDF
        $outputPath = $tempDir . '/invoice_' . $invoice['invoice_number'] . '_' . time() . '.pdf';
        $pdf->save($outputPath);
        
        if (file_exists($outputPath)) {
            error_log("Invoice PDF generated successfully: " . $outputPath);
            return $outputPath;
        } else {
            error_log("Invoice PDF generation failed: file not created");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Invoice generation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Simple PDF Generator Class
 * A lightweight PDF generator without external dependencies
 */
class SimplePDF {
    private $objects = [];
    private $objectCount = 0;
    private $pages = [];
    private $currentPage = null;
    private $pageContent = '';
    private $fonts = [];
    private $title = 'Invoice';
    private $currentFont = 'Helvetica';
    private $currentFontSize = 12;
    private $currentColor = [0, 0, 0];
    
    public function __construct() {
        $this->addFont('Helvetica', 'Helvetica');
        $this->addFont('Helvetica-Bold', 'Helvetica-Bold');
    }
    
    public function setTitle($title) {
        $this->title = $title;
    }
    
    private function addFont($name, $baseFont) {
        $this->fonts[$name] = $baseFont;
    }
    
    public function addPage() {
        if ($this->currentPage !== null) {
            $this->pages[] = $this->pageContent;
        }
        $this->currentPage = count($this->pages);
        $this->pageContent = '';
    }
    
    public function setFont($family, $style = '', $size = 12) {
        $fontName = $family;
        if ($style === 'B') {
            $fontName = $family . '-Bold';
        }
        $this->currentFont = $fontName;
        $this->currentFontSize = $size;
    }
    
    public function setColor($r, $g, $b) {
        $this->currentColor = [$r, $g, $b];
    }
    
    public function addText($x, $y, $text) {
        $r = $this->currentColor[0] / 255;
        $g = $this->currentColor[1] / 255;
        $b = $this->currentColor[2] / 255;
        
        // Escape special characters
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        
        $this->pageContent .= sprintf("BT\n");
        $this->pageContent .= sprintf("%.3f %.3f %.3f rg\n", $r, $g, $b);
        $this->pageContent .= sprintf("/F1 %d Tf\n", $this->currentFontSize);
        $this->pageContent .= sprintf("%.2f %.2f Td\n", $x, 842 - $y); // A4 height - y (flip y-axis)
        $this->pageContent .= sprintf("(%s) Tj\n", $text);
        $this->pageContent .= "ET\n";
    }
    
    public function addLine($x1, $y1, $x2, $y2) {
        $this->pageContent .= sprintf("%.2f %.2f m\n", $x1, 842 - $y1);
        $this->pageContent .= sprintf("%.2f %.2f l\n", $x2, 842 - $y2);
        $this->pageContent .= "0.8 0.8 0.8 RG\n";
        $this->pageContent .= "1 w\n";
        $this->pageContent .= "S\n";
    }
    
    public function addRect($x, $y, $w, $h, $color, $fill = true) {
        $r = $color[0] / 255;
        $g = $color[1] / 255;
        $b = $color[2] / 255;
        
        if ($fill) {
            $this->pageContent .= sprintf("%.3f %.3f %.3f rg\n", $r, $g, $b);
            $this->pageContent .= sprintf("%.2f %.2f %.2f %.2f re f\n", $x, 842 - $y - $h, $w, $h);
        } else {
            $this->pageContent .= sprintf("%.3f %.3f %.3f RG\n", $r, $g, $b);
            $this->pageContent .= sprintf("%.2f %.2f %.2f %.2f re S\n", $x, 842 - $y - $h, $w, $h);
        }
    }
    
    public function save($filename) {
        // Finalize current page
        if (!empty($this->pageContent)) {
            $this->pages[] = $this->pageContent;
        }
        
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        
        // Object 1: Catalog
        $offsets[] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        
        // Object 2: Pages
        $offsets[] = strlen($pdf);
        $pageRefs = '';
        for ($i = 0; $i < count($this->pages); $i++) {
            $pageRefs .= (4 + $i * 2) . ' 0 R ';
        }
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [" . trim($pageRefs) . "] /Count " . count($this->pages) . " >>\nendobj\n";
        
        // Object 3: Font
        $offsets[] = strlen($pdf);
        $pdf .= "3 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        
        // Pages and their content streams
        $objNum = 4;
        foreach ($this->pages as $pageContent) {
            // Page object
            $offsets[] = strlen($pdf);
            $contentObjNum = $objNum + 1;
            $pdf .= "$objNum 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents $contentObjNum 0 R /Resources << /Font << /F1 3 0 R >> >> >>\nendobj\n";
            $objNum++;
            
            // Content stream
            $offsets[] = strlen($pdf);
            $streamLength = strlen($pageContent);
            $pdf .= "$objNum 0 obj\n<< /Length $streamLength >>\nstream\n$pageContent\nendstream\nendobj\n";
            $objNum++;
        }
        
        // Cross-reference table
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 " . ($objNum) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        
        // Trailer
        $pdf .= "trailer\n<< /Size $objNum /Root 1 0 R >>\n";
        $pdf .= "startxref\n$xrefOffset\n%%EOF";
        
        file_put_contents($filename, $pdf);
    }
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
