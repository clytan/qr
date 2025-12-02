<?php
require_once __DIR__ . '/PHPMailerAutoload.php';
require_once __DIR__ . '/email_templates/welcome_email.php';

function sendWelcomeEmail($toEmail, $toName = '') {
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

    $content = getWelcomeEmailContent($toName);

    $mail->AddAddress($toEmail, $toName ?: $toEmail);
    $mail->SetFrom($mail->Username, 'Zokli');
    $mail->AddReplyTo($mail->Username, 'Zokli');
    $mail->Subject = 'Welcome to Zokli - Make Every Day Your Lucky Day';
    
    // Embed logo image
    $logoPath = __DIR__ . '/../assets/logo.png';
    if (file_exists($logoPath)) {
        $mail->AddEmbeddedImage($logoPath, 'logo', 'logo.png', 'base64', 'image/png');
    }
    
    $mail->MsgHTML($content);

    if (!$mail->Send()) {
        error_log('Welcome email failed: ' . $mail->ErrorInfo);
        return false;
    }
    return true;
}

?>
