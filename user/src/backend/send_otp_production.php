<?php
// Set content type to JSON immediately
header('Content-Type: application/json');

// Disable output buffering and set reasonable timeout
ob_implicit_flush(true);
set_time_limit(30);

try {
    // Check if email is provided
    if (!isset($_POST['email']) || empty(trim($_POST['email']))) {
        echo json_encode(['status' => false, 'message' => 'Email is required']);
        exit();
    }

    $email = trim($_POST['email']);
    $resend = isset($_POST['resend']) ? intval($_POST['resend']) : 0;

    // Basic email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => false, 'message' => 'Invalid email format']);
        exit();
    }

    // Include database connection
    require_once('./dbconfig/connection.php');

    // Generate OTP
    $otp = rand(100000, 999999);

    // Database operations first (fast and reliable)
    $inserted_id = null;
    $updated_id = null;
    
    $sqlCheck = "SELECT verified, id FROM user_otp WHERE user_email = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    if (!$stmtCheck) {
        echo json_encode(['status' => false, 'message' => 'Database error']);
        exit();
    }
    
    $stmtCheck->bind_param('s', $email);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    
    if ($row = $resultCheck->fetch_assoc()) {
        // Email exists, update OTP
        $sqlUpdate = "UPDATE user_otp SET otp = ?, verified = 0 WHERE user_email = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        if ($stmtUpdate) {
            $stmtUpdate->bind_param('ss', $otp, $email);
            $stmtUpdate->execute();
            $updated_id = $row['id'];
        }
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

    $otpId = $inserted_id ? $inserted_id : ($updated_id ? $updated_id : null);
    
    if (!$otpId) {
        echo json_encode(['status' => false, 'message' => 'Failed to save OTP to database']);
        exit();
    }

    // Return success immediately - email will be sent in background
    echo json_encode([
        'status' => true, 
        'message' => 'OTP sent to your email', 
        'data' => ['id' => $otpId], 
        'otp_id' => $otpId
    ]);
    
    // Flush output to send response to client immediately
    if (ob_get_level()) {
        ob_end_flush();
    }
    flush();
    
    // Close the connection to the client
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    
    // Now try to send email in background (won't delay the response)
    try {
        if (file_exists('../mailer/PHPMailerAutoload.php')) {
            include '../mailer/PHPMailerAutoload.php';
            
            $mail = new PHPMailer();
            
            // Embed logo image
            $logoPath = __DIR__ . '/../assets/logo.png';
            if (file_exists($logoPath)) {
                $mail->AddEmbeddedImage($logoPath, 'logo', 'logo.png', 'base64', 'image/png');
            }
            
            // SMTP Configuration with shorter timeouts
            $mail->isSMTP();
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            $mail->SMTPDebug = 0;
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = "tls";
            $mail->Port = 587;
            $mail->Host = "184.168.98.206";
            $mail->Username = "support@codersdek.com";
            $mail->Password = "Cdek@2020*";
            
            // Set shorter timeouts
            $mail->Timeout = 10;
            $mail->SMTPKeepAlive = false;
            
            $mail->isHTML(true);
            $mail->setFrom("support@codersdek.com", "Zokli Support");
            $mail->addAddress($email);
            $mail->Subject = 'Email Verification OTP - Zokli';
            
            // Simple email content for faster processing
            $content = '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Email Verification OTP</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; background: #f4f6fb; }
                    .container { max-width: 400px; margin: 0 auto; padding: 30px 20px; background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
                    .logo { text-align: center; margin-bottom: 20px; }
                    .logo img { max-width: 120px; height: auto; }
                    .otp { font-size: 28px; font-weight: bold; color: #667eea; text-align: center; padding: 15px; background: linear-gradient(135deg, #f0f4ff 0%, #e8ecff 100%); border-radius: 8px; margin: 20px 0; letter-spacing: 5px; }
                    .text { text-align: center; margin: 10px 0; color: #34495e; }
                    h2 { text-align: center; color: #667eea; margin: 0 0 20px 0; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="logo">
                        <img src="cid:logo" alt="Zokli Logo" />
                    </div>
                    <h2>Email Verification</h2>
                    <div class="text">Your OTP for email verification:</div>
                    <div class="otp">' . $otp . '</div>
                    <div class="text">This OTP is valid for 10 minutes.</div>
                    <div class="text" style="font-size: 12px; color: #666; margin-top: 20px;">Zokli &copy; 2025<br><br><i>*Zokli Technologies LLP, terms & conditions apply*</i></div>
                </div>
            </body>
            </html>';
            
            $mail->Body = $content;
            
            // Try to send email (but don't worry if it fails - user already got success response)
            $mail->send();
        }
        
    } catch (Exception $mailException) {
        // Log error but don't affect the user experience
        error_log("Email sending failed: " . $mailException->getMessage());
    }

} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => 'Server error occurred']);
}
?>
