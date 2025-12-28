<?php
include '../PHPMailerAutoload.php';

require_once('../../backend/dbconfig/connection.php');

$email = trim($_POST['email']);

$mail = new PHPMailer();
$mail->IsSMTP();
$mail->SMTPOptions = array(
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    )
);

$mail->Mailer = "smtp";
$mail->SMTPDebug = 0;
$mail->SMTPAuth = TRUE;
$mail->SMTPSecure = "tls";
$mail->Port = 587;
$mail->Host = "184.168.98.206";
$mail->Username = "support@codersdek.com";
$mail->Password = "Cdek@2020*";
$mail->IsHTML(true);


$otp = rand(100000, 999999);

// OTP logic for user_otp table
$inserted_id = null;
$updated_id = null;
$sqlCheck = "SELECT verified, id FROM user_otp WHERE user_email = ?";
$stmtCheck = $conn->prepare($sqlCheck);
if ($stmtCheck) {
    $stmtCheck->bind_param('s', $email);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    if ($row = $resultCheck->fetch_assoc()) {
        // Email exists
        if ($row['verified'] == 0) {
            // Not verified, update OTP
            $sqlUpdate = "UPDATE user_otp SET otp = ?, verified = 0 WHERE user_email = ?";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            if ($stmtUpdate) {
                $stmtUpdate->bind_param('ss', $otp, $email);
                $stmtUpdate->execute();
                $updated_id = $row['id'];
            }
        }
        // If verified, do nothing (or you can handle as needed)
    } else {
        // Email not present, insert new record
        $sqlInsert = "INSERT INTO user_otp (user_email, otp, verified) VALUES (?, ?, 0)";
        $stmtInsert = $conn->prepare($sqlInsert);
        if ($stmtInsert) {
            $stmtInsert->bind_param('ss', $email, $otp);
            $stmtInsert->execute();
            $inserted_id = $conn->insert_id;
        }
    }
}

// Embed logo image
$logoPath = __DIR__ . '/../../assets/logo.png';
if (file_exists($logoPath)) {
    $mail->AddEmbeddedImage($logoPath, 'logo', 'logo.png', 'base64', 'image/png');
}

$content = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification OTP</title>
    <style>
        body { background: #f4f6fb; font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .container {
            max-width: 480px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 32px 24px 24px 24px;
        }
        .logo {
            text-align: center;
            margin-bottom: 24px;
        }
        .logo img {
            max-width: 140px;
            height: auto;
        }
        h2 {
            color: #002E5B;
            text-align: center;
            margin-bottom: 12px;
        }
        .otp-box {
            background: #f0f4ff;
            color: #002E5B;
            font-size: 2.2em;
            font-weight: bold;
            letter-spacing: 8px;
            text-align: center;
            border-radius: 8px;
            padding: 18px 0;
            margin: 24px 0 16px 0;
        }
        .info {
            color: #333;
            text-align: center;
            font-size: 1.08em;
            margin-bottom: 18px;
        }
        .footer {
            text-align: center;
            color: #aaa;
            font-size: 0.95em;
            margin-top: 32px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="cid:logo" alt="Zokli Logo" />
        </div>
        <h2>Email Verification</h2>
        <div class="info">Your One Time Password (OTP) for email verification is:</div>
        <div class="otp-box">' . $otp . '</div>
        <div class="info">This OTP is valid for 10 minutes. Please do not share it with anyone.</div>
        <div class="footer">If you did not request this, please ignore this email.<br>Zokli &copy; 2025<br><br><i>*Zokli Technologies LLP, terms & conditions apply*</i></div>
    </div>
</body>
</html>';

$mail->AddAddress($email, $email);
// $mail->AddAddress("walter.pinto@pennpetchem.com", "walter.pinto@pennpetchem.com");
$mail->SetFrom("support@codersdek.com", "Zokli Support");
$mail->AddReplyTo($email, $email);
$mail->Subject = 'OTP for Email Verification';
$mail->MsgHTML($content);
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= 'From: support@codersdek.com';

// send the email
if (!$mail->Send()) {
    $otpId = $inserted_id ? $inserted_id : ($updated_id ? $updated_id : null);
    $sentmail = ['status' => false, 'message' => 'OTP sending failed', 'data' => ['id' => $otpId], 'otp_id' => $otpId];
    error_log($mail->ErrorInfo);
} else {
    $otpId = $inserted_id ? $inserted_id : ($updated_id ? $updated_id : null);
    $sentmail = ['status' => true, 'message' => 'OTP sent successfully', 'data' => ['id' => $otpId], 'otp_id' => $otpId];
}

echo json_encode($sentmail);
