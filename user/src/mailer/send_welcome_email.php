<?php
require_once __DIR__ . '/PHPMailerAutoload.php';
require_once __DIR__ . '/email_templates/welcome_email.php';
require_once __DIR__ . '/../backend/helpers/generate_qr_image.php';
require_once __DIR__ . '/../backend/helpers/generate_invoice_pdf.php';

/**
 * Send welcome email with QR code and invoice attachments
 * 
 * @param string $toEmail Recipient email address
 * @param string $toName Recipient name
 * @param int|null $userId User ID for generating QR and invoice attachments
 * @param string|null $userQrId User's QR ID (e.g., ZOK0000001)
 * @return bool True if email sent successfully
 */
function sendWelcomeEmail($toEmail, $toName = '', $userId = null, $userQrId = null)
{
    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    // These SMTP settings should be adjusted to your SMTP provider
    $mail->Mailer = "smtp";
    $mail->SMTPDebug = 0;
    $mail->SMTPAuth = TRUE;
    $mail->SMTPSecure = "tls";
    $mail->Port = 587;
    $mail->Host = getenv('SMTP_HOST') ?: '184.168.98.206';
    $mail->Username = getenv('SMTP_USER') ?: 'support@codersdek.com';
    $mail->Password = getenv('SMTP_PASS') ?: 'Cdek@2020*';
    $mail->IsHTML(true);

    // Set UTF-8 charset to properly handle Unicode characters and emoji
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';

    // Track files to cleanup after sending
    $filesToCleanup = array();

    // Generate and attach QR code if userQrId is provided
    $qrImagePath = null;
    if ($userQrId) {
        try {
            $qrImagePath = generateUserQRImage($userQrId);
            if ($qrImagePath && file_exists($qrImagePath)) {
                $mail->AddAttachment($qrImagePath, 'Your_Zokli_QR_Code.png', 'base64', 'image/png');
                $filesToCleanup[] = $qrImagePath;
                error_log("QR code attached to welcome email: " . $qrImagePath);
            }
        } catch (Exception $e) {
            error_log("Failed to generate QR code for email: " . $e->getMessage());
        }
    }

    // Generate and attach invoice if userId is provided
    $invoicePath = null;
    if ($userId) {
        try {
            $invoicePath = generateInvoicePDF($userId);
            if ($invoicePath && file_exists($invoicePath)) {
                $mail->AddAttachment($invoicePath, 'Zokli_Registration_Invoice.html', 'base64', 'text/html');
                $filesToCleanup[] = $invoicePath;
                error_log("Invoice attached to welcome email: " . $invoicePath);
            }
        } catch (Exception $e) {
            error_log("Failed to generate invoice for email: " . $e->getMessage());
        }
    }

    // Get email content with attachment info
    $hasAttachments = ($qrImagePath || $invoicePath);
    $content = getWelcomeEmailContent($toName, $hasAttachments);

    $mail->AddAddress($toEmail, $toName ?: $toEmail);
    $mail->SetFrom($mail->Username, 'Zokli');
    $mail->AddReplyTo($mail->Username, 'Zokli');
    
    // Update subject to mention attachments
    if ($hasAttachments) {
        $mail->Subject = 'Welcome to Zokli! 🎉 Your QR Code & Invoice Attached';
    } else {
        $mail->Subject = 'Welcome to Zokli - Make Every Day Your Lucky Day';
    }

    // Embed logo image
    $logoPath = __DIR__ . '/../assets/logo.png';
    if (file_exists($logoPath)) {
        $mail->AddEmbeddedImage($logoPath, 'logo', 'logo.png', 'base64', 'image/png');
    }

    $mail->MsgHTML($content);

    $result = $mail->Send();
    
    if (!$result) {
        error_log('Welcome email failed: ' . $mail->ErrorInfo);
    } else {
        error_log('Welcome email sent successfully to: ' . $toEmail);
    }

    // Cleanup temporary files
    foreach ($filesToCleanup as $file) {
        if (file_exists($file)) {
            unlink($file);
            error_log("Cleaned up temp file: " . $file);
        }
    }

    return $result;
}

?>