<?php
/**
 * Cron Job: Auto-delete expired polls after 7 days
 * 
 * Run this script daily via cron:
 * 0 2 * * * php /path/to/delete_expired_polls.php
 * 
 * Or call via URL with a secret key for testing
 */

// Allow CLI or authenticated web access
$isCLI = (php_sapi_name() === 'cli');
$isAuthorized = isset($_GET['key']) && $_GET['key'] === 'ZOKLI_CRON_SECRET_2024';

if (!$isCLI && !$isAuthorized) {
    http_response_code(403);
    die('Unauthorized');
}

require_once(__DIR__ . '/dbconfig/connection.php');

// Log function
function logMessage($msg) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $msg\n";
    error_log("[$timestamp] [Poll Cleanup] $msg");
}

logMessage("Starting expired polls cleanup...");

try {
    // Find polls that are expired (created_on + 7 days < NOW())
    // and have status = 'active' (paid polls that have expired)
    $sql = "SELECT id, title, created_on, user_id 
            FROM user_poll 
            WHERE is_deleted = 0 
            AND status = 'active' 
            AND DATE_ADD(created_on, INTERVAL 7 DAY) < NOW()";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $expiredCount = 0;
    
    while ($poll = $result->fetch_assoc()) {
        $pollId = $poll['id'];
        $pollTitle = $poll['title'];
        $createdOn = $poll['created_on'];
        
        // Soft delete the poll
        $updateSql = "UPDATE user_poll SET is_deleted = 1, status = 'expired', updated_on = NOW() WHERE id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param('i', $pollId);
        
        if ($stmt->execute()) {
            logMessage("Deleted expired poll ID: $pollId, Title: '$pollTitle', Created: $createdOn");
            $expiredCount++;
        } else {
            logMessage("ERROR: Failed to delete poll ID: $pollId - " . $stmt->error);
        }
        
        $stmt->close();
    }
    
    logMessage("Cleanup complete. $expiredCount expired polls deleted.");
    
    // Also clean up pending_payment polls older than 24 hours (abandoned payments)
    $abandonedSql = "UPDATE user_poll SET is_deleted = 1, status = 'abandoned', updated_on = NOW() 
                     WHERE is_deleted = 0 
                     AND status = 'pending_payment' 
                     AND DATE_ADD(created_on, INTERVAL 24 HOUR) < NOW()";
    
    if ($conn->query($abandonedSql)) {
        $abandonedCount = $conn->affected_rows;
        if ($abandonedCount > 0) {
            logMessage("Cleaned up $abandonedCount abandoned payment polls.");
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'expired_deleted' => $expiredCount,
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
