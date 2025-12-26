<?php
// admin/src/backend/log_admin_action.php

/**
 * Logs an admin action to the database
 * 
 * @param int $admin_id The ID of the admin performing the action
 * @param string $action_type Short code/name for action (e.g., 'BAN_USER', 'UPDATE_SETTINGS')
 * @param string $description Human readable description
 * @param mysqli $conn Database connection object
 * @return bool
 */
function logAdminAction($admin_id, $action_type, $description, $conn) {
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $sql = "INSERT INTO admin_audit_logs (admin_id, action_type, description, ip_address) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("isss", $admin_id, $action_type, $description, $ip_address);
            $stmt->execute();
            return true;
        }
    } catch (Exception $e) {
        // Silently fail logging to not disrupt main flow
        error_log("Audit Log Error: " . $e->getMessage());
    }
    return false;
}
?>
