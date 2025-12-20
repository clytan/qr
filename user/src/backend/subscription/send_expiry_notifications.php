<?php
/**
 * Subscription Expiry Notification Script
 * Sends email notifications to users whose subscriptions are expiring
 * 
 * Run this script daily via cron:
 * 0 9 * * * php /path/to/send_expiry_notifications.php
 */

// Allow CLI or authenticated web access
$isCLI = (php_sapi_name() === 'cli');
$isAuthorized = isset($_GET['key']) && $_GET['key'] === 'ZOKLI_CRON_SECRET_2024';

if (!$isCLI && !$isAuthorized) {
    http_response_code(403);
    die('Unauthorized');
}

require_once(__DIR__ . '/../dbconfig/connection.php');

// Log function
function logMessage($msg) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $msg\n";
    error_log("[$timestamp] [Expiry Notification] $msg");
}

// Email sending function
function sendExpiryEmail($user, $daysRemaining, $conn) {
    $to = $user['user_email'];
    $name = $user['user_full_name'];
    $tier = ucfirst($user['user_tag'] ?? 'Normal');
    $expiry_date = date('F j, Y', strtotime($user['expiry_date']));
    $renewal_price = getTierPrice($user['user_tag']);
    
    // Build renewal URL
    $renewUrl = "https://zokli.in/user/src/ui/profile.php";
    
    // Determine email subject and urgency
    if ($daysRemaining <= 0) {
        $subject = "⚠️ Your Zokli $tier Subscription Has Expired";
        $urgency = "expired";
        $urgencyColor = "#ef4444";
        $message = "Your subscription has expired. Please renew now to continue enjoying all features.";
    } elseif ($daysRemaining <= 7) {
        $subject = "🔔 Urgent: Your Zokli Subscription Expires in $daysRemaining Days";
        $urgency = "urgent";
        $urgencyColor = "#f59e0b";
        $message = "Your subscription is expiring very soon! Renew now to avoid any service interruption.";
    } else {
        $subject = "📅 Reminder: Your Zokli Subscription Expires on $expiry_date";
        $urgency = "reminder";
        $urgencyColor = "#3b82f6";
        $message = "This is a friendly reminder that your subscription will expire soon.";
    }
    
    $headers = "From: Zokli <noreply@zokli.in>\r\n";
    $headers .= "Reply-To: support@zokli.in\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $emailBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #0f172a; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, #1e293b 0%, #334155 100%); border-radius: 16px; overflow: hidden; }
            .header { padding: 30px; text-align: center; background: linear-gradient(135deg, #e67753, #d6653f); }
            .header h1 { color: white; margin: 0; font-size: 24px; }
            .content { padding: 30px; color: #e2e8f0; }
            .urgency-badge { display: inline-block; padding: 8px 16px; background: {$urgencyColor}; color: white; border-radius: 20px; font-weight: bold; margin-bottom: 20px; }
            .subscription-box { background: rgba(255,255,255,0.1); padding: 20px; border-radius: 12px; margin: 20px 0; }
            .subscription-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
            .subscription-row:last-child { border-bottom: none; }
            .label { color: #94a3b8; }
            .value { color: #f8fafc; font-weight: 600; }
            .renew-btn { display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #10b981, #059669); color: white; text-decoration: none; border-radius: 10px; font-weight: bold; font-size: 16px; margin-top: 20px; }
            .footer { padding: 20px 30px; background: rgba(0,0,0,0.2); text-align: center; color: #64748b; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🌟 Zokli Subscription</h1>
            </div>
            <div class='content'>
                <p>Hi <strong>$name</strong>,</p>
                
                <span class='urgency-badge'>
                    " . ($daysRemaining <= 0 ? 'EXPIRED' : ($daysRemaining <= 7 ? 'EXPIRES SOON' : 'EXPIRY REMINDER')) . "
                </span>
                
                <p>$message</p>
                
                <div class='subscription-box'>
                    <div class='subscription-row'>
                        <span class='label'>Your Tier</span>
                        <span class='value'>$tier</span>
                    </div>
                    <div class='subscription-row'>
                        <span class='label'>Expiry Date</span>
                        <span class='value'>$expiry_date</span>
                    </div>
                    <div class='subscription-row'>
                        <span class='label'>Days Remaining</span>
                        <span class='value' style='color: {$urgencyColor};'>" . ($daysRemaining <= 0 ? 'Expired' : "$daysRemaining days") . "</span>
                    </div>
                    <div class='subscription-row'>
                        <span class='label'>Renewal Price</span>
                        <span class='value' style='color: #10b981;'>₹$renewal_price</span>
                    </div>
                </div>
                
                <center>
                    <a href='$renewUrl' class='renew-btn'>Renew Now →</a>
                </center>
                
                <p style='margin-top: 30px; color: #94a3b8; font-size: 14px;'>
                    If you have any questions, please contact us at support@zokli.in
                </p>
            </div>
            <div class='footer'>
                © " . date('Y') . " Zokli. All rights reserved.<br>
                You are receiving this email because you have a subscription with us.
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Try to send email
    $sent = @mail($to, $subject, $emailBody, $headers);
    
    if ($sent) {
        // Log notification sent
        $logSql = "INSERT INTO subscription_notifications (user_id, notification_type, days_remaining, sent_on) VALUES (?, ?, ?, NOW())";
        $logStmt = @$conn->prepare($logSql);
        if ($logStmt) {
            $logStmt->bind_param('isi', $user['id'], $urgency, $daysRemaining);
            $logStmt->execute();
            $logStmt->close();
        }
    }
    
    return $sent;
}

function getTierPrice($tier) {
    $prices = [
        'gold' => 9999,
        'silver' => 5555,
        'normal' => 999,
        'student' => 999
    ];
    return $prices[strtolower($tier ?? 'normal')] ?? 999;
}

logMessage("Starting subscription expiry notifications...");

try {
    // Create notifications log table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS subscription_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        notification_type VARCHAR(50) NOT NULL,
        days_remaining INT NOT NULL,
        sent_on DATETIME NOT NULL,
        INDEX idx_user_date (user_id, sent_on)
    )");
    
    // Get users with expiring subscriptions (30 days, 7 days, 1 day, expired)
    $notifications = [
        ['days' => 30, 'type' => '30_day'],
        ['days' => 7, 'type' => '7_day'],
        ['days' => 1, 'type' => '1_day'],
        ['days' => 0, 'type' => 'expired']
    ];
    
    $totalSent = 0;
    
    foreach ($notifications as $notif) {
        $days = $notif['days'];
        $type = $notif['type'];
        
        // Query for users expiring in exactly X days (or expired for 0)
        if ($days == 0) {
            $sql = "SELECT u.id, u.user_full_name, u.user_email, u.user_tag, 
                           DATE_ADD(u.created_on, INTERVAL 1 YEAR) as expiry_date,
                           DATEDIFF(DATE_ADD(u.created_on, INTERVAL 1 YEAR), NOW()) as days_remaining
                    FROM user_user u
                    WHERE u.is_deleted = 0 
                    AND u.user_email IS NOT NULL 
                    AND u.user_email != ''
                    AND DATE_ADD(u.created_on, INTERVAL 1 YEAR) < NOW()
                    AND DATE_ADD(u.created_on, INTERVAL 1 YEAR) >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    AND u.id NOT IN (
                        SELECT user_id FROM subscription_notifications 
                        WHERE notification_type = 'expired' 
                        AND sent_on > DATE_SUB(NOW(), INTERVAL 7 DAY)
                    )";
        } else {
            $sql = "SELECT u.id, u.user_full_name, u.user_email, u.user_tag, 
                           DATE_ADD(u.created_on, INTERVAL 1 YEAR) as expiry_date,
                           DATEDIFF(DATE_ADD(u.created_on, INTERVAL 1 YEAR), NOW()) as days_remaining
                    FROM user_user u
                    WHERE u.is_deleted = 0 
                    AND u.user_email IS NOT NULL 
                    AND u.user_email != ''
                    AND DATEDIFF(DATE_ADD(u.created_on, INTERVAL 1 YEAR), NOW()) = ?
                    AND u.id NOT IN (
                        SELECT user_id FROM subscription_notifications 
                        WHERE notification_type = ? 
                        AND sent_on > DATE_SUB(NOW(), INTERVAL 1 DAY)
                    )";
        }
        
        if ($days == 0) {
            $result = $conn->query($sql);
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('is', $days, $type);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        
        while ($user = $result->fetch_assoc()) {
            $daysRemaining = intval($user['days_remaining']);
            if (sendExpiryEmail($user, $daysRemaining, $conn)) {
                logMessage("Sent $type notification to: " . $user['user_email']);
                $totalSent++;
            } else {
                logMessage("Failed to send $type notification to: " . $user['user_email']);
            }
        }
    }
    
    logMessage("Completed. Total emails sent: $totalSent");
    
    echo json_encode([
        'status' => 'success',
        'emails_sent' => $totalSent,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
