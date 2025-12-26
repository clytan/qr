<?php
// Include auth check
require_once('../components/auth_check.php');
require_once('../backend/dbconfig/connection.php');
require_once('../backend/log_admin_action.php');

$admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 0; // For logging

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'get_all':
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;
            $search = isset($_POST['search']) ? trim($_POST['search']) : '';
            
            // Build search condition
            $where = "WHERE is_deleted = 0";
            $params = [];
            $types = '';
            
            if (!empty($search)) {
                $where .= " AND (user_full_name LIKE ? OR user_email LIKE ? OR user_phone LIKE ? OR user_qr_id LIKE ?)";
                $searchTerm = "%$search%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
                $types .= 'ssss';
            }
            
            // Tier filter
            $tier_filter = isset($_POST['tier_filter']) ? trim($_POST['tier_filter']) : '';
            if (!empty($tier_filter)) {
                $where .= " AND user_tag = ?";
                $params[] = $tier_filter;
                $types .= 's';
            }
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM user_user $where";
            if (!empty($params)) {
                $countStmt = $conn->prepare($countSql);
                $countStmt->bind_param($types, ...$params);
                $countStmt->execute();
                $countResult = $countStmt->get_result()->fetch_assoc();
                $total = $countResult['total'];
                $countStmt->close();
            } else {
                $countResult = $conn->query($countSql)->fetch_assoc();
                $total = $countResult['total'];
            }
            
            // Get users
            $sql = "SELECT id, user_full_name, user_email, user_phone, user_qr_id, user_user_type, 
                           user_tag, community_id, user_image_path, user_email_verified, 
                           user_address, user_pincode, user_landmark,
                           created_on, updated_on
                    FROM user_user 
                    $where 
                    ORDER BY created_on DESC
                    LIMIT $limit OFFSET $offset";
            
            if (!empty($params)) {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $conn->query($sql);
            }
            
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            
            echo json_encode([
                'status' => true, 
                'data' => $users,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'pages' => ceil($total / $limit),
                    'limit' => $limit
                ]
            ]);
            exit();
            
        case 'get_user':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => false, 'message' => 'Invalid user ID']);
                exit();
            }
            
            // Get user details
            $stmt = $conn->prepare("SELECT * FROM user_user WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$user) {
                echo json_encode(['status' => false, 'message' => 'User not found']);
                exit();
            }
            
            // Get penalties
            $stmtPen = $conn->prepare("SELECT * FROM user_penalties WHERE user_id = ? ORDER BY start_time DESC");
            $stmtPen->bind_param('i', $id);
            $stmtPen->execute();
            $penResult = $stmtPen->get_result();
            $penalties = [];
            while ($row = $penResult->fetch_assoc()) {
                $penalties[] = $row;
            }
            $stmtPen->close();
            
            // Remove password from response
            unset($user['user_password']);
            
            echo json_encode(['status' => true, 'data' => ['user' => $user, 'penalties' => $penalties]]);
            exit();
            
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $user_full_name = trim($_POST['user_full_name'] ?? '');
            $user_email = trim($_POST['user_email'] ?? '');
            $user_phone = trim($_POST['user_phone'] ?? '');
            
            if ($id <= 0) {
                echo json_encode(['status' => false, 'message' => 'Invalid user ID']);
                exit();
            }
            
            $stmt = $conn->prepare("UPDATE user_user SET user_full_name = ?, user_email = ?, user_phone = ?, updated_by = ?, updated_on = NOW() WHERE id = ?");
            $stmt->bind_param('sssii', $user_full_name, $user_email, $user_phone, $admin_id, $id);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => true, 'message' => 'User updated successfully']);
            } else {
                echo json_encode(['status' => false, 'message' => 'Failed to update user']);
            }
            exit();
            
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => false, 'message' => 'Invalid user ID']);
                exit();
            }
            
            $stmt = $conn->prepare("UPDATE user_user SET is_deleted = 1, updated_by = ?, updated_on = NOW() WHERE id = ?");
            $stmt->bind_param('ii', $admin_id, $id);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => true, 'message' => 'User deleted successfully']);
            } else {
                echo json_encode(['status' => false, 'message' => 'Failed to delete user']);
            }
            exit();
            
        case 'ban':
            $userId = intval($_POST['user_id'] ?? 0);
            $reason = trim($_POST['reason'] ?? 'Banned by admin');
            
            if ($userId <= 0) {
                echo json_encode(['status' => false, 'message' => 'Invalid user ID']);
                exit();
            }
            
            // Global ban (community_id = NULL means global)
            $stmt = $conn->prepare("INSERT INTO user_penalties (user_id, community_id, penalty_type, reason, applied_by, start_time) VALUES (?, NULL, 'ban', ?, ?, NOW())");
            $stmt->bind_param('isi', $userId, $reason, $admin_id);
            
            if ($stmt->execute()) {
                // Log action
                logAdminAction($admin_id, 'BAN_USER', "Banned user ID: $user_id. Reason: $reason", $conn);
                
                echo json_encode(['status' => true, 'message' => 'User banned successfully']);
            } else {
                echo json_encode(['status' => false, 'message' => 'Failed to ban user']);
            }
            exit();
            
        case 'unban':
            $userId = intval($_POST['user_id'] ?? 0);
            
            if ($userId <= 0) {
                echo json_encode(['status' => false, 'message' => 'Invalid user ID']);
                exit();
            }
            
            // Remove all active bans
            $stmt = $conn->prepare("UPDATE user_penalties SET is_active = 0, is_deleted = 1 WHERE user_id = ? AND penalty_type = 'ban' AND (is_active = 1 OR is_deleted = 0)");
            $stmt->bind_param('i', $userId);
            
            if ($stmt->execute()) {
                // Log action
                logAdminAction($admin_id, 'UNBAN_USER', "Unbanned user ID: $user_id", $conn);

                echo json_encode(['status' => true, 'message' => 'User unbanned successfully']);
            } else {
                echo json_encode(['status' => false, 'message' => 'Failed to unban user']);
            }
            exit();
            
        case 'verify_email':
            $userId = intval($_POST['user_id'] ?? 0);
            
            if ($userId <= 0) {
                echo json_encode(['status' => false, 'message' => 'Invalid user ID']);
                exit();
            }
            
            $stmt = $conn->prepare("UPDATE user_user SET user_email_verified = 1, updated_by = ?, updated_on = NOW() WHERE id = ?");
            $stmt->bind_param('ii', $admin_id, $userId);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => true, 'message' => 'Email verified successfully']);
            } else {
                echo json_encode(['status' => false, 'message' => 'Failed to verify email']);
            }
            exit();
            
        case 'export':
            $search = isset($_POST['search']) ? trim($_POST['search']) : '';
            $tier_filter = isset($_POST['tier_filter']) ? trim($_POST['tier_filter']) : '';
            $columns = isset($_POST['columns']) ? $_POST['columns'] : [];
            
            $where = "WHERE is_deleted = 0";
            $params = [];
            $types = '';
            
            if (!empty($search)) {
                $where .= " AND (user_full_name LIKE ? OR user_email LIKE ? OR user_phone LIKE ? OR user_qr_id LIKE ?)";
                $searchTerm = "%$search%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
                $types .= 'ssss';
            }
            
            if (!empty($tier_filter)) {
                $where .= " AND user_tag = ?";
                $params[] = $tier_filter;
                $types .= 's';
            }

            // Map frontend column keys to DB columns
            $fieldMap = [
                'name' => 'user_full_name',
                'email' => 'user_email',
                'phone' => 'user_phone',
                'address' => 'user_address',
                'pincode' => 'user_pincode',
                'landmark' => 'user_landmark',
                'qr_id' => 'user_qr_id',
                'type' => 'user_user_type',
                'tier' => 'user_tag',
                'joined' => 'created_on'
            ];

            $selectFields = [];
            if (!empty($columns)) {
                foreach ($columns as $col) {
                    if (isset($fieldMap[$col])) {
                        $selectFields[] = $fieldMap[$col];
                    }
                }
            }

            // Fallback if no valid columns selected
            if (empty($selectFields)) {
                $selectFields = ['user_full_name', 'user_phone', 'user_address'];
            }
            
            $sql = "SELECT " . implode(', ', $selectFields) . " FROM user_user $where ORDER BY created_on DESC";
            
            if (!empty($params)) {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $conn->query($sql);
            }
            
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            
            // 4. Add logging to 'export' case
            logAdminAction($admin_id, 'EXPORT_USERS', "Exported " . count($users) . " users. Format: CSV/Txt", $conn);
            
            echo json_encode(['status' => true, 'data' => $users]);
            exit();
            
        case 'extend_subscription':
            try {
                $userId = intval($_POST['user_id'] ?? 0);
                $months = intval($_POST['months'] ?? 12);
                $reason = trim($_POST['reason'] ?? 'Extended by admin');
                
                if ($userId <= 0) {
                    echo json_encode(['status' => false, 'message' => 'Invalid user ID']);
                    exit();
                }
                
                if ($months < 1 || $months > 60) {
                    echo json_encode(['status' => false, 'message' => 'Months should be between 1 and 60']);
                    exit();
                }
                
                // Create log table if not exists
                @$conn->query("CREATE TABLE IF NOT EXISTS subscription_extensions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    months_extended INT NOT NULL,
                    reason VARCHAR(255),
                    admin_id INT,
                    created_on DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                
                // Extend subscription by adding months to created_on
                $stmt = $conn->prepare("UPDATE user_user SET created_on = DATE_ADD(created_on, INTERVAL ? MONTH), updated_by = ?, updated_on = NOW() WHERE id = ?");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param('iii', $months, $admin_id, $userId);
                
                if ($stmt->execute()) {
                    // Log the extension (optional)
                    $logStmt = @$conn->prepare("INSERT INTO subscription_extensions (user_id, months_extended, reason, admin_id, created_on) VALUES (?, ?, ?, ?, NOW())");
                    if ($logStmt) {
                        $logStmt->bind_param('iisi', $userId, $months, $reason, $admin_id);
                        @$logStmt->execute();
                        @$logStmt->close();
                    }
                    echo json_encode(['status' => true, 'message' => "Subscription extended by $months months"]);
                } else {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
            } catch (Exception $e) {
                error_log("Extend Subscription Error: " . $e->getMessage());
                echo json_encode(['status' => false, 'message' => 'Failed to extend: ' . $e->getMessage()]);
            }
            exit();
            
        case 'change_tier':
            try {
                $userId = intval($_POST['user_id'] ?? 0);
                $newTier = trim($_POST['tier'] ?? '');
                $reason = trim($_POST['reason'] ?? 'Changed by admin');
                
                if ($userId <= 0) {
                    echo json_encode(['status' => false, 'message' => 'Invalid user ID']);
                    exit();
                }
                
                $validTiers = ['gold', 'silver', 'normal', 'student'];
                if (!in_array(strtolower($newTier), $validTiers)) {
                    echo json_encode(['status' => false, 'message' => 'Invalid tier']);
                    exit();
                }
                
                // Create log table if not exists
                @$conn->query("CREATE TABLE IF NOT EXISTS tier_changes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    old_tier VARCHAR(50),
                    new_tier VARCHAR(50),
                    reason VARCHAR(255),
                    admin_id INT,
                    created_on DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                
                // Get current tier for logging
                $currentStmt = $conn->prepare("SELECT user_tag FROM user_user WHERE id = ?");
                if (!$currentStmt) {
                    throw new Exception("Prepare failed");
                }
                $currentStmt->bind_param('i', $userId);
                $currentStmt->execute();
                $currentResult = $currentStmt->get_result()->fetch_assoc();
                $oldTier = $currentResult['user_tag'] ?? 'unknown';
                $currentStmt->close();
                
                // Update tier
                $stmt = $conn->prepare("UPDATE user_user SET user_tag = ?, updated_by = ?, updated_on = NOW() WHERE id = ?");
                if (!$stmt) {
                    throw new Exception("Prepare failed");
                }
                $stmt->bind_param('sii', $newTier, $admin_id, $userId);
                
                if ($stmt->execute()) {
                    // Log the tier change (optional)
                    $logStmt = @$conn->prepare("INSERT INTO tier_changes (user_id, old_tier, new_tier, reason, admin_id, created_on) VALUES (?, ?, ?, ?, ?, NOW())");
                    if ($logStmt) {
                        $logStmt->bind_param('isssi', $userId, $oldTier, $newTier, $reason, $admin_id);
                        @$logStmt->execute();
                        @$logStmt->close();
                    }
                    echo json_encode(['status' => true, 'message' => "Tier changed from $oldTier to $newTier"]);
                } else {
                    throw new Exception("Update failed");
                }
            } catch (Exception $e) {
                error_log("Change Tier Error: " . $e->getMessage());
                echo json_encode(['status' => false, 'message' => 'Failed to change tier']);
            }
            exit();
            
        case 'get_subscription':
            $userId = intval($_POST['user_id'] ?? 0);
            
            if ($userId <= 0) {
                echo json_encode(['status' => false, 'message' => 'Invalid user ID']);
                exit();
            }
            
            $stmt = $conn->prepare("SELECT u.id, u.user_full_name, u.user_tag, u.created_on,
                                           DATE_ADD(u.created_on, INTERVAL 1 YEAR) as expiry_date,
                                           DATEDIFF(DATE_ADD(u.created_on, INTERVAL 1 YEAR), NOW()) as days_remaining
                                    FROM user_user u WHERE u.id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $subData = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($subData) {
                echo json_encode(['status' => true, 'data' => $subData]);
            } else {
                echo json_encode(['status' => false, 'message' => 'User not found']);
            }
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Users - Admin Panel</title>
    <link rel="icon" href="../../assets/logo2.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html { background: #0f172a; min-height: 100vh; font-family: 'Inter', sans-serif; color: #e2e8f0; }
        
        .page-content { padding: 30px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .page-title { font-size: 28px; font-weight: 700; color: #f1f5f9; }
        
        /* Search bar */
        .search-bar { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 25px; }
        .search-input {
            flex: 1; min-width: 250px; padding: 12px 16px; padding-left: 44px;
            background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px; color: #e2e8f0; font-size: 14px;
        }
        .search-input:focus { outline: none; border-color: #e67753; }
        .search-wrapper { position: relative; flex: 1; min-width: 250px; }
        .search-wrapper i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #64748b; }
        
        /* Stats row */
        .stats-row { display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap; }
        .stat-mini { background: rgba(30, 41, 59, 0.8); padding: 15px 20px; border-radius: 10px; border: 1px solid rgba(255, 255, 255, 0.08); }
        .stat-mini-value { font-size: 22px; font-weight: 700; color: #f1f5f9; }
        .stat-mini-label { font-size: 12px; color: #64748b; margin-top: 3px; }
        
        .filter-select { padding: 12px 16px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 10px; color: #e2e8f0; font-size: 14px; min-width: 150px; }
        .filter-select:focus { outline: none; border-color: #e67753; }
        .btn-export { padding: 12px 20px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; border-radius: 10px; color: white; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; }
        .btn-export:hover { opacity: 0.9; }
        
        /* Table */
        .table-container { background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 16px; overflow: hidden; }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th { background: rgba(15, 23, 42, 0.5); padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #94a3b8; text-transform: uppercase; white-space: nowrap; }
        td { padding: 14px 16px; border-top: 1px solid rgba(255, 255, 255, 0.05); font-size: 13px; color: #e2e8f0; }
        tr:hover td { background: rgba(255, 255, 255, 0.02); }
        
        .user-cell { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px; overflow: hidden; }
        .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-info .name { font-weight: 600; color: #f1f5f9; }
        .user-info .email { font-size: 12px; color: #64748b; }
        
        .badge { display: inline-flex; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
        .badge.verified { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
        .badge.unverified { background: rgba(234, 179, 8, 0.15); color: #fbbf24; }
        .badge.pro { background: rgba(168, 85, 247, 0.15); color: #a855f7; }
        .badge.free { background: rgba(148, 163, 184, 0.15); color: #94a3b8; }
        .badge.banned { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        
        .action-btns { display: flex; gap: 8px; }
        .btn-icon { width: 34px; height: 34px; border-radius: 8px; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; font-size: 13px; }
        .btn-view { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .btn-edit { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
        .btn-ban { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .btn-icon:hover { transform: scale(1.1); }
        
        /* Pagination */
        .pagination { display: flex; justify-content: center; gap: 8px; padding: 20px; }
        .page-btn { padding: 8px 14px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #94a3b8; cursor: pointer; transition: all 0.2s ease; }
        .page-btn:hover, .page-btn.active { background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%); color: white; border-color: transparent; }
        .page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        
        /* Modal */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); display: none; align-items: center; justify-content: center; z-index: 2000; padding: 20px; overflow-y: auto; }
        .modal-overlay.show { display: flex; }
        .modal { background: #1e293b; border-radius: 16px; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.08); position: sticky; top: 0; background: #1e293b; z-index: 10; }
        .modal-title { font-size: 18px; font-weight: 600; color: #f1f5f9; }
        .modal-close { width: 36px; height: 36px; border-radius: 8px; border: none; background: rgba(255, 255, 255, 0.05); color: #94a3b8; cursor: pointer; font-size: 18px; }
        .modal-body { padding: 25px; }
        
        /* User detail card */
        .user-detail-header { display: flex; align-items: center; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; }
        .user-detail-avatar { width: 80px; height: 80px; border-radius: 16px; background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 28px; overflow: hidden; }
        .user-detail-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-detail-info h3 { font-size: 20px; color: #f1f5f9; margin-bottom: 5px; }
        .user-detail-info p { color: #64748b; font-size: 13px; }
        
        .detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px; }
        .detail-item { background: rgba(15, 23, 42, 0.5); padding: 15px; border-radius: 10px; }
        .detail-label { font-size: 11px; color: #64748b; text-transform: uppercase; margin-bottom: 5px; }
        .detail-value { font-size: 14px; color: #f1f5f9; word-break: break-all; }
        
        .section-title { font-size: 14px; font-weight: 600; color: #f1f5f9; margin: 25px 0 15px; display: flex; align-items: center; gap: 8px; }
        .section-title i { color: #e67753; }
        
        .penalty-item { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 10px; padding: 12px 15px; margin-bottom: 10px; }
        .penalty-item.inactive { opacity: 0.5; }
        .penalty-type { display: inline-flex; padding: 3px 8px; background: rgba(239, 68, 68, 0.2); color: #f87171; border-radius: 5px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .penalty-reason { color: #e2e8f0; font-size: 13px; margin-top: 8px; }
        .penalty-meta { color: #64748b; font-size: 11px; margin-top: 5px; }
        
        .action-buttons { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.08); }
        .btn-action { padding: 10px 20px; border-radius: 8px; border: none; font-size: 13px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s ease; }
        .btn-action:hover { transform: translateY(-2px); }
        .btn-danger { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .btn-success { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
        .btn-primary-action { background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%); color: white; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
        .toast { position: fixed; bottom: 30px; right: 30px; padding: 16px 24px; border-radius: 10px; font-size: 14px; font-weight: 500; z-index: 3000; transform: translateX(150%); transition: transform 0.3s ease; }
        .toast.show { transform: translateX(0); }
        .toast.success { background: rgba(34, 197, 94, 0.9); color: white; }
        .toast.error { background: rgba(239, 68, 68, 0.9); color: white; }
        
        @media (max-width: 768px) {
            .page-content { padding: 20px 15px; }
            .detail-grid { grid-template-columns: 1fr; }
            .user-detail-header { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
    <?php include('../components/sidebar.php'); ?>
    
    <main class="admin-main">
        <div class="page-content">
            <div class="page-header">
                <h1 class="page-title">User Management</h1>
            </div>
            
            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-mini">
                    <div class="stat-mini-value" id="total-users">-</div>
                    <div class="stat-mini-label">Total Users</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value" id="verified-users">-</div>
                    <div class="stat-mini-label">Verified</div>
                </div>
            </div>
            
            <!-- Search + Filters -->
            <div class="search-bar">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" class="search-input" id="searchInput" placeholder="Search by name, email, phone, or QR ID...">
                </div>
                <select class="filter-select" id="tierFilter">
                    <option value="">All Tiers</option>
                    <option value="gold">Gold</option>
                    <option value="silver">Silver</option>
                    <option value="normal">Normal</option>
                    <option value="student">Student</option>
                </select>
                <button class="btn-export" onclick="openExportModal()">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
            
            <!-- Users Table -->
            <div class="table-container">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Phone</th>
                                <th>QR ID</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body">
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <i class="fas fa-spinner fa-spin" style="font-size: 24px;"></i><br>Loading...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="pagination" id="pagination"></div>
            </div>
        </div>
    </main>
    
    <!-- Export Modal -->
    <div class="modal-overlay" id="exportModal">
        <div class="modal" style="max-width: 450px;">
            <div class="modal-header">
                <h3 class="modal-title">Export Users</h3>
                <button class="modal-close" onclick="closeExportModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="color: #94a3b8; margin-bottom: 15px;">Select the columns you want to include in the CSV export.</p>
                
                <div style="margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px;">
                    <label style="color: #94a3b8; font-size: 13px; display: block; margin-bottom: 10px;">Export Format</label>
                    <div style="display: flex; gap: 20px;">
                        <label style="display: flex; align-items: center; gap: 8px; color: #e2e8f0; cursor: pointer;">
                            <input type="radio" name="exportFormat" value="csv" checked> CSV (Excel)
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; color: #e2e8f0; cursor: pointer;">
                            <input type="radio" name="exportFormat" value="txt"> Text File (Print Friendly)
                        </label>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; gap: 10px; color: #e2e8f0; cursor: pointer;">
                        <input type="checkbox" class="export-col" value="name" checked> Name
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; color: #e2e8f0; cursor: pointer;">
                        <input type="checkbox" class="export-col" value="address" checked> Address
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; color: #e2e8f0; cursor: pointer;">
                        <input type="checkbox" class="export-col" value="pincode" checked> Pincode
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; color: #e2e8f0; cursor: pointer;">
                        <input type="checkbox" class="export-col" value="landmark" checked> Landmark
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; color: #e2e8f0; cursor: pointer;">
                        <input type="checkbox" class="export-col" value="phone" checked> Phone
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; color: #e2e8f0; cursor: pointer;">
                        <input type="checkbox" class="export-col" value="email"> Email
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; color: #e2e8f0; cursor: pointer;">
                        <input type="checkbox" class="export-col" value="qr_id" checked> QR ID
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; color: #e2e8f0; cursor: pointer;">
                        <input type="checkbox" class="export-col" value="type"> Type
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; color: #e2e8f0; cursor: pointer;">
                        <input type="checkbox" class="export-col" value="tier"> Tier
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; color: #e2e8f0; cursor: pointer;">
                        <input type="checkbox" class="export-col" value="joined"> Joined Date
                    </label>
                </div>

                <div class="action-buttons" style="border: none; padding-top: 5px;">
                    <button class="btn-action" style="background: rgba(255,255,255,0.05); color: #94a3b8;" onclick="closeExportModal()">Cancel</button>
                    <button class="btn-action btn-primary-action" onclick="confirmExport()"><i class="fas fa-file-export"></i> Download</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- User Detail Modal -->
    <div class="modal-overlay" id="userModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">User Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="userModalBody">
                Loading...
            </div>
        </div>
    </div>
    
    <!-- Ban Modal -->
    <div class="modal-overlay" id="banModal">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h3 class="modal-title">Ban User</h3>
                <button class="modal-close" onclick="closeBanModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="color: #94a3b8; margin-bottom: 15px;">This will globally ban the user from all communities.</p>
                <label style="color: #94a3b8; font-size: 13px; display: block; margin-bottom: 8px;">Reason</label>
                <textarea id="banReason" rows="3" style="width: 100%; padding: 12px; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #e2e8f0; resize: none;">Banned by admin</textarea>
                <input type="hidden" id="banUserId">
                <div class="action-buttons" style="border: none; padding-top: 15px; margin-top: 10px;">
                    <button class="btn-action" style="background: rgba(255,255,255,0.05); color: #94a3b8;" onclick="closeBanModal()">Cancel</button>
                    <button class="btn-action btn-danger" onclick="confirmBan()"><i class="fas fa-ban"></i> Ban User</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Subscription Management Modal -->
    <div class="modal-overlay" id="subscriptionModal">
        <div class="modal" style="max-width: 450px;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-calendar-alt"></i> Manage Subscription</h3>
                <button class="modal-close" onclick="closeSubscriptionModal()">&times;</button>
            </div>
            <div class="modal-body" id="subscriptionModalBody">
                Loading...
            </div>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentPage = 1;
        let searchTimeout;
        
        $(document).ready(function() {
            loadUsers(1);
            
            // Search with debounce
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentPage = 1;
                    loadUsers(1);
                }, 300);
            });
            
            // Tier filter change
            $('#tierFilter').on('change', function() {
                currentPage = 1;
                loadUsers(1);
            });
        });
        
        function loadUsers(page) {
            currentPage = page;
            const search = $('#searchInput').val().trim();
            const tierFilter = $('#tierFilter').val();
            
            $.post('', { action: 'get_all', page: page, search: search, tier_filter: tierFilter }, function(response) {
                if (response.status && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(user => {
                        const avatar = user.user_image_path 
                            ? `<img src="../../..${user.user_image_path}" alt="">` 
                            : (user.user_full_name ? user.user_full_name.charAt(0).toUpperCase() : '?');
                        const name = user.user_full_name || 'No Name';
                        const email = user.user_email || '-';
                        const phone = user.user_phone || '-';
                        const qrId = user.user_qr_id || '-';
                        const type = user.user_user_type || 'free';
                        const verified = user.user_email_verified ? '<span class="badge verified"><i class="fas fa-check"></i> Verified</span>' : '<span class="badge unverified">Unverified</span>';
                        const joined = formatDate(user.created_on);
                        
                        html += `
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar">${avatar}</div>
                                        <div class="user-info">
                                            <div class="name">${escapeHtml(name)}</div>
                                            <div class="email">${escapeHtml(email)}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>${escapeHtml(phone)}</td>
                                <td><code style="background: rgba(59,130,246,0.1); padding: 3px 8px; border-radius: 4px; color: #60a5fa; font-size: 12px;">${escapeHtml(qrId)}</code></td>
                                <td><span class="badge ${type === 'pro' ? 'pro' : 'free'}">${escapeHtml(type)}</span></td>
                                <td>${verified}</td>
                                <td>${joined}</td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-icon btn-view" onclick="viewUser(${user.id})" title="View"><i class="fas fa-eye"></i></button>
                                        <button class="btn-icon" onclick="openSubscriptionModal(${user.id})" title="Subscription" style="background: rgba(16, 185, 129, 0.2); color: #34d399;"><i class="fas fa-calendar-alt"></i></button>
                                        <button class="btn-icon btn-ban" onclick="openBanModal(${user.id})" title="Ban"><i class="fas fa-ban"></i></button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    $('#users-table-body').html(html);
                    
                    // Update stats
                    $('#total-users').text(response.pagination.total);
                    
                    // Render pagination
                    renderPagination(response.pagination);
                } else {
                    $('#users-table-body').html('<tr><td colspan="7" class="empty-state"><i class="fas fa-users-slash" style="font-size: 32px; opacity: 0.5;"></i><br>No users found</td></tr>');
                    $('#pagination').html('');
                }
            }, 'json');
        }
        
        function renderPagination(pagination) {
            if (pagination.pages <= 1) {
                $('#pagination').html('');
                return;
            }
            
            let html = '';
            html += `<button class="page-btn" onclick="loadUsers(${pagination.page - 1})" ${pagination.page <= 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`;
            
            for (let i = 1; i <= pagination.pages; i++) {
                if (i === 1 || i === pagination.pages || (i >= pagination.page - 2 && i <= pagination.page + 2)) {
                    html += `<button class="page-btn ${i === pagination.page ? 'active' : ''}" onclick="loadUsers(${i})">${i}</button>`;
                } else if (i === pagination.page - 3 || i === pagination.page + 3) {
                    html += '<span style="color: #64748b;">...</span>';
                }
            }
            
            html += `<button class="page-btn" onclick="loadUsers(${pagination.page + 1})" ${pagination.page >= pagination.pages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`;
            $('#pagination').html(html);
        }
        
        function viewUser(id) {
            $('#userModalBody').html('<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 24px;"></i></div>');
            $('#userModal').addClass('show');
            
            $.post('', { action: 'get_user', id: id }, function(response) {
                if (response.status) {
                    const user = response.data.user;
                    const penalties = response.data.penalties;
                    
                    const avatar = user.user_image_path 
                        ? `<img src="../../..${user.user_image_path}" alt="">` 
                        : (user.user_full_name ? user.user_full_name.charAt(0).toUpperCase() : '?');
                    
                    let penaltiesHtml = '';
                    if (penalties.length > 0) {
                        penalties.forEach(p => {
                            penaltiesHtml += `
                                <div class="penalty-item ${p.is_active == 0 ? 'inactive' : ''}">
                                    <span class="penalty-type">${escapeHtml(p.penalty_type)}</span>
                                    <div class="penalty-reason">${escapeHtml(p.reason || 'No reason provided')}</div>
                                    <div class="penalty-meta">Applied: ${formatDate(p.start_time)} ${p.end_time ? ' | Ends: ' + formatDate(p.end_time) : ''}</div>
                                </div>
                            `;
                        });
                    } else {
                        penaltiesHtml = '<p style="color: #64748b; font-size: 13px;">No penalties on record</p>';
                    }
                    
                    const hasActiveBan = penalties.some(p => p.penalty_type === 'ban' && p.is_active == 1 && p.is_deleted == 0);
                    
                    const html = `
                        <div class="user-detail-header">
                            <div class="user-detail-avatar">${avatar}</div>
                            <div class="user-detail-info">
                                <h3>${escapeHtml(user.user_full_name || 'No Name')}</h3>
                                <p>${escapeHtml(user.user_email || 'No email')}</p>
                            </div>
                        </div>
                        
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Phone</div>
                                <div class="detail-value">${escapeHtml(user.user_phone || '-')}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">QR ID</div>
                                <div class="detail-value">${escapeHtml(user.user_qr_id || '-')}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">User Type</div>
                                <div class="detail-value">${escapeHtml(user.user_user_type || 'free')}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Email Verified</div>
                                <div class="detail-value">${user.user_email_verified ? '<span class="badge verified">Yes</span>' : '<span class="badge unverified">No</span>'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Community ID</div>
                                <div class="detail-value">${user.community_id || 'None'}</div>
                            </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Joined</div>
                                <div class="detail-value">${formatDate(user.created_on)}</div>
                            </div>
                            <div class="detail-item" style="grid-column: span 2;">
                                <div class="detail-label">Address</div>
                                <div class="detail-value">
                                    ${escapeHtml(user.user_address || '')}
                                    ${user.user_landmark ? '<br><small>Landmark: ' + escapeHtml(user.user_landmark) + '</small>' : ''}
                                    ${user.user_pincode ? '<br><small>Pincode: ' + escapeHtml(user.user_pincode) + '</small>' : ''}
                                    ${!user.user_address && !user.user_landmark && !user.user_pincode ? '<span style="color: #64748b; font-style: italic;">No address details</span>' : ''}
                                </div>
                            </div>
                        </div>
                        
                        <div class="section-title"><i class="fas fa-exclamation-triangle"></i> Penalties & Bans</div>
                        ${penaltiesHtml}
                        
                        <div class="action-buttons">
                            ${!user.user_email_verified ? `<button class="btn-action btn-success" onclick="verifyEmail(${user.id})"><i class="fas fa-check"></i> Verify Email</button>` : ''}
                            ${hasActiveBan 
                                ? `<button class="btn-action btn-success" onclick="unbanUser(${user.id})"><i class="fas fa-unlock"></i> Unban User</button>`
                                : `<button class="btn-action btn-danger" onclick="closeModal(); openBanModal(${user.id})"><i class="fas fa-ban"></i> Ban User</button>`
                            }
                            <button class="btn-action btn-danger" onclick="deleteUser(${user.id})"><i class="fas fa-trash"></i> Delete</button>
                        </div>
                    `;
                    
                    $('#userModalBody').html(html);
                } else {
                    $('#userModalBody').html('<p style="color: #f87171;">Failed to load user details</p>');
                }
            }, 'json');
        }
        
        function closeModal() {
            $('#userModal').removeClass('show');
        }
        
        function openBanModal(userId) {
            $('#banUserId').val(userId);
            $('#banReason').val('Banned by admin');
            $('#banModal').addClass('show');
        }
        
        function closeBanModal() {
            $('#banModal').removeClass('show');
        }
        
        function confirmBan() {
            const userId = $('#banUserId').val();
            const reason = $('#banReason').val();
            
            $.post('', { action: 'ban', user_id: userId, reason: reason }, function(response) {
                closeBanModal();
                showToast(response.message, response.status ? 'success' : 'error');
                if (response.status) loadUsers(currentPage);
            }, 'json');
        }
        
        function unbanUser(userId) {
            if (!confirm('Are you sure you want to unban this user?')) return;
            
            $.post('', { action: 'unban', user_id: userId }, function(response) {
                showToast(response.message, response.status ? 'success' : 'error');
                if (response.status) {
                    closeModal();
                    loadUsers(currentPage);
                }
            }, 'json');
        }
        
        function verifyEmail(userId) {
            $.post('', { action: 'verify_email', user_id: userId }, function(response) {
                showToast(response.message, response.status ? 'success' : 'error');
                if (response.status) viewUser(userId);
            }, 'json');
        }
        
        function deleteUser(userId) {
            if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) return;
            
            $.post('', { action: 'delete', id: userId }, function(response) {
                closeModal();
                showToast(response.message, response.status ? 'success' : 'error');
                if (response.status) loadUsers(currentPage);
            }, 'json');
        }
        
        function showToast(msg, type) {
            const t = $('#toast');
            t.text(msg).removeClass('success error').addClass(type + ' show');
            setTimeout(() => t.removeClass('show'), 3000);
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
        
        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        }
        
        function openExportModal() {
            $('#exportModal').addClass('show');
        }

        function closeExportModal() {
            $('#exportModal').removeClass('show');
        }

        function confirmExport() {
            const search = $('#searchInput').val().trim();
            const tierFilter = $('#tierFilter').val();
            const format = $('input[name="exportFormat"]:checked').val();
            
            // Get selected columns
            const columns = [];
            $('.export-col:checked').each(function() {
                columns.push($(this).val());
            });

            if (columns.length === 0) {
                showToast('Please select at least one column', 'error');
                return;
            }

            showToast('Preparing export...', 'success');
            
            $.post('', { action: 'export', search: search, tier_filter: tierFilter, columns: columns }, function(response) {
                if (response.status && response.data.length > 0) {
                    closeExportModal();
                    
                    if (format === 'txt') {
                        // Text File Export
                        let txtContent = '';
                        
                        response.data.forEach(row => {
                            // Construct address block if address components are selected
                            let fullAddress = [];
                            if (row.user_address) fullAddress.push(row.user_address);
                            if (row.user_landmark) fullAddress.push(row.user_landmark);
                            if (row.user_pincode) fullAddress.push(row.user_pincode);
                            
                            // Build the block based on selected columns
                            if (columns.includes('name')) txtContent += (row.user_full_name || 'No Name') + '\r\n';
                            if (columns.includes('qr_id')) txtContent += (row.user_qr_id || '-') + '\r\n';
                            if (columns.includes('phone')) txtContent += (row.user_phone || '-') + '\r\n';
                            if (columns.includes('email')) txtContent += (row.user_email || '-') + '\r\n';
                            
                            // Combine address fields if any are selected
                            if (columns.includes('address') || columns.includes('pincode') || columns.includes('landmark')) {
                                if (fullAddress.length > 0) {
                                    txtContent += fullAddress.join(', ') + '\r\n';
                                } else {
                                    txtContent += 'No Address\r\n';
                                }
                            }
                            
                            if (columns.includes('type')) txtContent += 'Type: ' + (row.user_user_type || '-') + '\r\n';
                            if (columns.includes('tier')) txtContent += 'Tier: ' + (row.user_tag || '-') + '\r\n';
                            if (columns.includes('joined')) txtContent += 'Joined: ' + (row.created_on || '-') + '\r\n';
                            
                            txtContent += '----------------------------------------\r\n';
                        });

                        const blob = new Blob([txtContent], { type: 'text/plain;charset=utf-8;' });
                        const link = document.createElement('a');
                        link.href = URL.createObjectURL(blob);
                        link.download = 'users_export_' + new Date().toISOString().slice(0,10) + '.txt';
                        link.click();
                        
                    } else {
                        // CSV Export (Existing logic)
                        const headerMap = {
                            'name': 'Name',
                            'email': 'Email',
                            'phone': 'Phone',
                            'address': 'Address',
                            'pincode': 'Pincode',
                            'landmark': 'Landmark',
                            'qr_id': 'QR ID',
                            'type': 'Type',
                            'tier': 'Tier',
                            'joined': 'Joined'
                        };

                        const dbKeyMap = {
                            'name': 'user_full_name',
                            'email': 'user_email',
                            'phone': 'user_phone',
                            'address': 'user_address',
                            'pincode': 'user_pincode',
                            'landmark': 'user_landmark',
                            'qr_id': 'user_qr_id',
                            'type': 'user_user_type',
                            'tier': 'user_tag',
                            'joined': 'created_on'
                        };

                        // Build headers
                        const selectedHeaders = columns.map(col => headerMap[col] || col);
                        let csv = selectedHeaders.join(',') + '\n';
                        
                        // Build rows
                        response.data.forEach(row => {
                            const csvRow = columns.map(col => {
                                const dbKey = dbKeyMap[col];
                                let val = row[dbKey] || '';
                                val = String(val).replace(/"/g, '""');
                                return '"' + val + '"';
                            });
                            csv += csvRow.join(',') + '\n';
                        });
                        
                        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                        const link = document.createElement('a');
                        link.href = URL.createObjectURL(blob);
                        link.download = 'users_export_' + new Date().toISOString().slice(0,10) + '.csv';
                        link.click();
                    }
                    
                    showToast('Export complete! ' + response.data.length + ' users', 'success');
                } else {
                    showToast('No data to export', 'error');
                }
            }, 'json');
        }
        
        // Subscription Management Functions
        let currentSubUserId = null;
        
        function openSubscriptionModal(userId) {
            currentSubUserId = userId;
            $('#subscriptionModal').addClass('show');
            $('#subscriptionModalBody').html('<div style="text-align:center;padding:20px;"><i class="fas fa-spinner fa-spin" style="font-size:24px;"></i></div>');
            
            $.post('', { action: 'get_subscription', user_id: userId }, function(response) {
                if (response.status) {
                    const d = response.data;
                    const daysRemaining = parseInt(d.days_remaining);
                    const statusColor = daysRemaining > 30 ? '#10b981' : (daysRemaining > 7 ? '#f59e0b' : '#ef4444');
                    const tier = (d.user_tag || 'normal').charAt(0).toUpperCase() + (d.user_tag || 'normal').slice(1);
                    
                    let html = `
                        <div style="background: rgba(15,23,42,0.6); padding: 15px; border-radius: 10px; margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <span style="color: #94a3b8;">User</span>
                                <span style="color: #f8fafc; font-weight: 600;">${escapeHtml(d.user_full_name)}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <span style="color: #94a3b8;">Current Tier</span>
                                <span style="color: #fbbf24; font-weight: 600;">${tier}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <span style="color: #94a3b8;">Expiry Date</span>
                                <span style="color: #f8fafc;">${formatDate(d.expiry_date)}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                                <span style="color: #94a3b8;">Days Remaining</span>
                                <span style="color: ${statusColor}; font-weight: 700;">${daysRemaining > 0 ? daysRemaining + ' days' : 'EXPIRED'}</span>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="color: #94a3b8; font-size: 13px; display: block; margin-bottom: 8px;">Extend Subscription</label>
                            <div style="display: flex; gap: 10px;">
                                <select id="extendMonths" style="flex: 1; padding: 10px; background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #e2e8f0;">
                                    <option value="1">1 Month</option>
                                    <option value="3">3 Months</option>
                                    <option value="6">6 Months</option>
                                    <option value="12" selected>12 Months (1 Year)</option>
                                    <option value="24">24 Months (2 Years)</option>
                                </select>
                                <button onclick="extendSubscription()" style="padding: 10px 15px; background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                                    <i class="fas fa-plus"></i> Extend
                                </button>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="color: #94a3b8; font-size: 13px; display: block; margin-bottom: 8px;">Change Tier</label>
                            <div style="display: flex; gap: 10px;">
                                <select id="changeTierSelect" style="flex: 1; padding: 10px; background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #e2e8f0;">
                                    <option value="gold" ${d.user_tag === 'gold' ? 'selected' : ''}>Gold</option>
                                    <option value="silver" ${d.user_tag === 'silver' ? 'selected' : ''}>Silver</option>
                                    <option value="normal" ${d.user_tag === 'normal' ? 'selected' : ''}>Normal</option>
                                    <option value="student" ${d.user_tag === 'student' ? 'selected' : ''}>Student</option>
                                </select>
                                <button onclick="changeTier()" style="padding: 10px 15px; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                                    <i class="fas fa-exchange-alt"></i> Change
                                </button>
                            </div>
                        </div>
                        
                        <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px; text-align: right;">
                            <button onclick="closeSubscriptionModal()" style="padding: 10px 20px; background: rgba(255,255,255,0.1); color: #94a3b8; border: none; border-radius: 8px; cursor: pointer;">Close</button>
                        </div>
                    `;
                    
                    $('#subscriptionModalBody').html(html);
                } else {
                    $('#subscriptionModalBody').html('<p style="color:#ef4444;">Failed to load subscription data.</p>');
                }
            }, 'json');
        }
        
        function closeSubscriptionModal() {
            $('#subscriptionModal').removeClass('show');
            currentSubUserId = null;
        }
        
        function extendSubscription() {
            if (!currentSubUserId) return;
            const months = $('#extendMonths').val();
            
            $.post('', { action: 'extend_subscription', user_id: currentSubUserId, months: months, reason: 'Extended by admin' }, function(response) {
                if (response.status) {
                    showToast(response.message, 'success');
                    openSubscriptionModal(currentSubUserId); // Refresh modal
                } else {
                    showToast(response.message, 'error');
                }
            }, 'json');
        }
        
        function changeTier() {
            if (!currentSubUserId) return;
            const newTier = $('#changeTierSelect').val();
            
            $.post('', { action: 'change_tier', user_id: currentSubUserId, tier: newTier, reason: 'Changed by admin' }, function(response) {
                if (response.status) {
                    showToast(response.message, 'success');
                    openSubscriptionModal(currentSubUserId); // Refresh modal
                    loadUsers(currentPage); // Refresh table
                } else {
                    showToast(response.message, 'error');
                }
            }, 'json');
        }
    </script>
</body>
</html>
