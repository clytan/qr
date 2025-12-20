<?php
// return_poll.php
// Handles return from Cashfree for Poll Payments

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$orderId = $_GET['order_id'] ?? '';

if (empty($orderId)) {
    // Fallback if no order ID
    header("Location: ../../ui/polls.php?error=missing_order");
    exit;
}

// Redirect back to polls UI with order_id to trigger client-side verification
header("Location: ../../ui/polls.php?order_id=" . urlencode($orderId));
exit;
?>
