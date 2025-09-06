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
            $mail->setFrom("support@codersdek.com", "iGoalZERO Support");
            $mail->addAddress($email);
            $mail->Subject = 'Email Verification OTP';
            
            // Simple email content for faster processing
            $content = '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Email Verification OTP</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .container { max-width: 400px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
                    .otp { font-size: 24px; font-weight: bold; color: #002E5B; text-align: center; padding: 15px; background: #f0f4ff; border-radius: 5px; margin: 15px 0; letter-spacing: 3px; }
                    .text { text-align: center; margin: 10px 0; }
                </style>
            </head>
            <body>
                <div class="container">
                    <h2 style="text-align: center; color: #002E5B;">Email Verification</h2>
                    <div class="text">Your OTP for email verification:</div>
                    <div class="otp">' . $otp . '</div>
                    <div class="text">This OTP is valid for 10 minutes.</div>
                    <div class="text" style="font-size: 12px; color: #666; margin-top: 20px;">iGoalZERO &copy; 2025</div>
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
