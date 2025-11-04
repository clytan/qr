<?php
// Manual Registration Processor - Use this to complete pending registrations
require_once('./backend/dbconfig/connection.php');
require_once('./backend/payment/session_config.php');
require_once('./backend/auto_community_helper.php');

// Include the processRegistration function
require_once('./backend/payment/return.php');

if (isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    
    // Get registration data
    $sqlGetReg = "SELECT registration_data, status FROM user_pending_registration WHERE order_id = ?";
    $stmtGetReg = $conn->prepare($sqlGetReg);
    $stmtGetReg->bind_param('s', $order_id);
    $stmtGetReg->execute();
    $resultGetReg = $stmtGetReg->get_result();
    
    if ($resultGetReg->num_rows === 0) {
        die("❌ Order not found: $order_id");
    }
    
    $rowReg = $resultGetReg->fetch_assoc();
    
    if ($rowReg['status'] == 'completed') {
        die("✅ This order is already processed!");
    }
    
    $regData = json_decode($rowReg['registration_data'], true);
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Manual Registration Processing</title>
        <style>
            body { font-family: Arial; padding: 20px; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #333; }
            .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0; }
            .warning { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #ffc107; }
            .success { background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #28a745; }
            .error { background: #f8d7da; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #dc3545; }
            button { background: #4CAF50; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
            button:hover { background: #45a049; }
            .cancel { background: #f44336; }
            .cancel:hover { background: #da190b; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>⚠️ Manual Registration Processing</h1>
            
            <div class="info">
                <h3>Order Details:</h3>
                <strong>Order ID:</strong> <?php echo htmlspecialchars($order_id); ?><br>
                <strong>Email:</strong> <?php echo htmlspecialchars($regData['email']); ?><br>
                <strong>Name:</strong> <?php echo htmlspecialchars($regData['full_name'] ?? 'N/A'); ?><br>
                <strong>Phone:</strong> <?php echo htmlspecialchars($regData['phone'] ?? 'N/A'); ?><br>
                <strong>Amount:</strong> ₹<?php echo htmlspecialchars($regData['amount']); ?><br>
            </div>
            
            <div class="warning">
                <h3>⚠️ Important Instructions:</h3>
                <ol>
                    <li>First, <strong>verify in Cashfree dashboard</strong> that payment was successful for this order</li>
                    <li>Only click "Process Registration" if payment is confirmed</li>
                    <li>This will create the user account and complete registration</li>
                </ol>
            </div>
            
            <form method="POST" onsubmit="return confirm('Have you verified payment in Cashfree dashboard?');">
                <input type="hidden" name="confirm_process" value="1">
                <button type="submit">✅ Process Registration (Payment Confirmed)</button>
                <button type="button" class="cancel" onclick="window.location.href='check_pending.php'">Cancel</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    
    // Process if confirmed
    if (isset($_POST['confirm_process'])) {
        echo "<div class='container'><div class='info'><h3>Processing...</h3>";
        
        $result = processRegistration($regData, 'MANUAL_PAYMENT_' . $order_id, $order_id, $order_id);
        
        if ($result['status']) {
            // Update status
            $sqlUpdate = "UPDATE user_pending_registration SET status = 'completed' WHERE order_id = ?";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bind_param('s', $order_id);
            $stmtUpdate->execute();
            
            echo "</div><div class='success'>";
            echo "<h2>✅ Registration Successful!</h2>";
            echo "<p>User account created successfully.</p>";
            echo "<p><a href='check_pending.php'>Back to Pending List</a> | <a href='ui/login.php'>Go to Login</a></p>";
            echo "</div></div>";
        } else {
            echo "</div><div class='error'>";
            echo "<h2>❌ Registration Failed</h2>";
            echo "<p>Error: " . htmlspecialchars($result['message']) . "</p>";
            echo "<p><a href='?order_id=$order_id'>Try Again</a></p>";
            echo "</div></div>";
        }
    }
    
} else {
    echo "<h1>❌ No Order ID Provided</h1>";
    echo "<p><a href='check_pending.php'>View Pending Registrations</a></p>";
}

$conn->close();
?>
