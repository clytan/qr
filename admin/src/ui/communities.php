<?php
// Include auth check
require_once('../components/auth_check.php');
require_once('../backend/dbconfig/connection.php');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'get_communities':
            try {
                // Simple query first - avoid complex subqueries that may fail
                $sql = "SELECT c.*, 
                        (SELECT COUNT(*) FROM community_members WHERE community_id = c.id AND is_deleted = 0) as member_count,
                        (SELECT COUNT(*) FROM community_chat WHERE community_id = c.id AND is_deleted = 0) as message_count
                        FROM community c 
                        WHERE c.is_deleted = 0 
                        ORDER BY c.name";
                $result = $conn->query($sql);
                $communities = [];
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $communities[] = $row;
                    }
                }
                echo json_encode(['status' => true, 'data' => $communities, 'debug' => $conn->error]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'get_chat':
            try {
                $community_id = intval($_POST['community_id'] ?? 0);
                $since = $_POST['since'] ?? null;
                
                if ($community_id <= 0) {
                    echo json_encode(['status' => false, 'message' => 'Community ID required']);
                    exit();
                }
                
                // Simplified query - avoid message_reports subquery that may fail
                $sql = "SELECT cc.*, u.user_full_name, u.user_qr_id, u.user_image_path,
                        (SELECT COUNT(*) FROM community_reactions WHERE message_id = cc.id AND reaction_type = 'like' AND is_deleted = 0) as likes,
                        (SELECT COUNT(*) FROM community_reactions WHERE message_id = cc.id AND reaction_type = 'dislike' AND is_deleted = 0) as dislikes
                        FROM community_chat cc
                        JOIN user_user u ON cc.user_id = u.id
                        WHERE cc.community_id = ? AND cc.is_deleted = 0";
                
                if ($since) {
                    $sql .= " AND cc.created_on > ?";
                }
                $sql .= " ORDER BY cc.created_on DESC LIMIT 100";
                
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    echo json_encode(['status' => false, 'message' => 'Query error: ' . $conn->error]);
                    exit();
                }
                if ($since) {
                    $stmt->bind_param('is', $community_id, $since);
                } else {
                    $stmt->bind_param('i', $community_id);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                
                $messages = [];
                while ($row = $result->fetch_assoc()) {
                    $messages[] = $row;
                }
                
                // Reverse to show oldest first
                $messages = array_reverse($messages);
                
                echo json_encode(['status' => true, 'data' => $messages, 'debug' => ['count' => count($messages), 'sql_error' => $conn->error]]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'get_members':
            try {
                $community_id = intval($_POST['community_id'] ?? 0);
                
                if ($community_id <= 0) {
                    echo json_encode(['status' => false, 'message' => 'Community ID required']);
                    exit();
                }
                
                $sql = "SELECT cm.*, u.user_full_name, u.user_qr_id, u.user_email, u.user_image_path,
                        (SELECT role_type FROM user_roles WHERE user_id = cm.user_id AND community_id = cm.community_id AND is_deleted = 0 LIMIT 1) as role,
                        (SELECT penalty_type FROM user_penalties WHERE user_id = cm.user_id AND community_id = cm.community_id AND is_deleted = 0 AND (penalty_type = 'ban' OR (penalty_type = 'timeout' AND end_time > NOW())) ORDER BY created_on DESC LIMIT 1) as active_penalty,
                        (SELECT end_time FROM user_penalties WHERE user_id = cm.user_id AND community_id = cm.community_id AND is_deleted = 0 AND penalty_type = 'timeout' AND end_time > NOW() ORDER BY created_on DESC LIMIT 1) as timeout_end
                        FROM community_members cm
                        JOIN user_user u ON cm.user_id = u.id
                        WHERE cm.community_id = ? AND cm.is_deleted = 0
                        ORDER BY u.user_full_name";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $community_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $members = [];
                while ($row = $result->fetch_assoc()) {
                    $members[] = $row;
                }
                
                echo json_encode(['status' => true, 'data' => $members]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'ban_user':
            try {
                $user_id = intval($_POST['user_id'] ?? 0);
                $community_id = intval($_POST['community_id'] ?? 0);
                $reason = trim($_POST['reason'] ?? 'Banned by admin');
                
                if ($user_id <= 0 || $community_id <= 0) {
                    echo json_encode(['status' => false, 'message' => 'User and Community ID required']);
                    exit();
                }
                
                // Note: applied_by is optional (NULL allowed) since we're using admin panel
                $sql = "INSERT INTO user_penalties (user_id, community_id, penalty_type, reason, start_time) VALUES (?, ?, 'ban', ?, NOW())";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    echo json_encode(['status' => false, 'message' => 'Prepare failed: ' . $conn->error, 'sql' => $sql]);
                    exit();
                }
                $stmt->bind_param('iis', $user_id, $community_id, $reason);
                
                if ($stmt->execute()) {
                    echo json_encode(['status' => true, 'message' => 'User banned successfully']);
                } else {
                    echo json_encode(['status' => false, 'message' => 'Execute failed: ' . $stmt->error]);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => 'Exception: ' . $e->getMessage()]);
            }
            exit();
            
        case 'timeout_user':
            try {
                $user_id = intval($_POST['user_id'] ?? 0);
                $community_id = intval($_POST['community_id'] ?? 0);
                $duration = intval($_POST['duration'] ?? 30); // minutes
                $reason = trim($_POST['reason'] ?? 'Timed out by admin');
                
                if ($user_id <= 0 || $community_id <= 0) {
                    echo json_encode(['status' => false, 'message' => 'User and Community ID required']);
                    exit();
                }
                
                $end_time = date('Y-m-d H:i:s', strtotime("+{$duration} minutes"));
                
                // Note: applied_by is optional (NULL allowed) since we're using admin panel
                $sql = "INSERT INTO user_penalties (user_id, community_id, penalty_type, reason, start_time, end_time) VALUES (?, ?, 'timeout', ?, NOW(), ?)";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    echo json_encode(['status' => false, 'message' => 'Prepare failed: ' . $conn->error, 'sql' => $sql]);
                    exit();
                }
                $stmt->bind_param('iiss', $user_id, $community_id, $reason, $end_time);
                
                if ($stmt->execute()) {
                    echo json_encode(['status' => true, 'message' => "User timed out for $duration minutes"]);
                } else {
                    echo json_encode(['status' => false, 'message' => 'Execute failed: ' . $stmt->error]);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => 'Exception: ' . $e->getMessage()]);
            }
            exit();
            
        case 'unban_user':
            try {
                $user_id = intval($_POST['user_id'] ?? 0);
                $community_id = intval($_POST['community_id'] ?? 0);
                
                if ($user_id <= 0 || $community_id <= 0) {
                    echo json_encode(['status' => false, 'message' => 'User and Community ID required']);
                    exit();
                }
                
                $stmt = $conn->prepare("UPDATE user_penalties SET is_deleted = 1 WHERE user_id = ? AND community_id = ? AND is_deleted = 0");
                $stmt->bind_param('ii', $user_id, $community_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['status' => true, 'message' => 'User unbanned']);
                } else {
                    echo json_encode(['status' => false, 'message' => 'Failed to unban user']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'toggle_moderator':
            try {
                $user_id = intval($_POST['user_id'] ?? 0);
                $community_id = intval($_POST['community_id'] ?? 0);
                $make_mod = ($_POST['make_mod'] ?? '') === 'true';
                
                if ($user_id <= 0 || $community_id <= 0) {
                    echo json_encode(['status' => false, 'message' => 'User and Community ID required']);
                    exit();
                }
                
                if ($make_mod) {
                    // Check if any role exists (active or deleted)
                    $check = $conn->prepare("SELECT id FROM user_roles WHERE user_id = ? AND community_id = ?");
                    if (!$check) {
                        echo json_encode(['status' => false, 'message' => 'Prepare check failed: ' . $conn->error]);
                        exit();
                    }
                    $check->bind_param('ii', $user_id, $community_id);
                    $check->execute();
                    $result = $check->get_result();
                    
                    if ($result->num_rows > 0) {
                        // Update existing role to moderator
                        $stmt = $conn->prepare("UPDATE user_roles SET role_type = 'moderator', is_deleted = 0 WHERE user_id = ? AND community_id = ?");
                        if (!$stmt) {
                            echo json_encode(['status' => false, 'message' => 'Prepare update failed: ' . $conn->error]);
                            exit();
                        }
                        $stmt->bind_param('ii', $user_id, $community_id);
                        $msg = 'User promoted to moderator';
                    } else {
                        // Insert new moderator role
                        $stmt = $conn->prepare("INSERT INTO user_roles (user_id, community_id, role_type) VALUES (?, ?, 'moderator')");
                        if (!$stmt) {
                            echo json_encode(['status' => false, 'message' => 'Prepare insert failed: ' . $conn->error]);
                            exit();
                        }
                        $stmt->bind_param('ii', $user_id, $community_id);
                        $msg = 'User promoted to moderator';
                    }
                } else {
                    // Start of remove mod logic
                    // We should probably strip the role back to 'member' or soft delete if only mods are in this table.
                    // Assuming we demote to member:
                    $stmt = $conn->prepare("UPDATE user_roles SET role_type = 'member' WHERE user_id = ? AND community_id = ? AND is_deleted = 0");
                    if (!$stmt) {
                        echo json_encode(['status' => false, 'message' => 'Prepare update failed: ' . $conn->error]);
                        exit();
                    }
                    $stmt->bind_param('ii', $user_id, $community_id);
                    $msg = 'Moderator role removed';
                }
                
                if ($stmt->execute()) {
                    echo json_encode(['status' => true, 'message' => $msg]);
                } else {
                    echo json_encode(['status' => false, 'message' => 'Execute failed: ' . $stmt->error]);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => 'Exception: ' . $e->getMessage()]);
            }
            exit();
            
        case 'get_reports':
            try {
                $community_id = intval($_POST['community_id'] ?? 0);
                
                // Fetch reports (pending first, then others) including reviewer info
                $sql = "SELECT mr.*, cc.message, cc.user_id as sender_id, u.user_full_name as sender_name, 
                        r.user_full_name as reporter_name, cc.created_on as message_time, cc.community_id,
                        reviewer.user_full_name as reviewer_name
                        FROM message_reports mr
                        JOIN community_chat cc ON mr.message_id = cc.id
                        JOIN user_user u ON cc.user_id = u.id
                        JOIN user_user r ON mr.reported_by = r.id
                        LEFT JOIN user_user reviewer ON mr.reviewed_by = reviewer.id
                        WHERE mr.is_deleted = 0";
                
                if ($community_id > 0) {
                    $sql .= " AND cc.community_id = ?";
                }
                // Order by status (pending first) then date
                $sql .= " ORDER BY (CASE WHEN mr.status = 'pending' THEN 0 ELSE 1 END), mr.created_on DESC LIMIT 50";
                
                if ($community_id > 0) {
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        echo json_encode(['status' => false, 'message' => 'Prepare failed: ' . $conn->error, 'sql' => $sql]);
                        exit();
                    }
                    $stmt->bind_param('i', $community_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                } else {
                    $result = $conn->query($sql);
                    if (!$result) {
                        echo json_encode(['status' => false, 'message' => 'Query failed: ' . $conn->error, 'sql' => $sql]);
                        exit();
                    }
                }
                
                $reports = [];
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $reports[] = $row;
                    }
                }
                
                echo json_encode(['status' => true, 'data' => $reports, 'count' => count($reports)]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'delete_message':
            try {
                $message_id = intval($_POST['message_id'] ?? 0);
                $admin_id = $_SESSION['admin_id'] ?? 0;
                
                if ($message_id <= 0) {
                    echo json_encode(['status' => false, 'message' => 'Message ID required']);
                    exit();
                }
                
                $stmt = $conn->prepare("UPDATE community_chat SET is_deleted = 1 WHERE id = ?");
                $stmt->bind_param('i', $message_id);
                
                if ($stmt->execute()) {
                    // Resolve reports: status='actioned', action='message_deleted'
                    $resolve = $conn->prepare("UPDATE message_reports SET status = 'actioned', action_taken = 'message_deleted', reviewed_by = ?, reviewed_on = NOW() WHERE message_id = ? AND status = 'pending'");
                    $resolve->bind_param('ii', $admin_id, $message_id);
                    $resolve->execute();
                    
                    echo json_encode(['status' => true, 'message' => 'Message deleted and reports resolved']);
                } else {
                    echo json_encode(['status' => false, 'message' => 'Failed to delete message']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'dismiss_report':
            try {
                $report_id = intval($_POST['report_id'] ?? 0);
                $admin_id = $_SESSION['admin_id'] ?? 0;
                
                if ($report_id <= 0) {
                    echo json_encode(['status' => false, 'message' => 'Report ID required']);
                    exit();
                }
                
                // Dismiss report: status='dismissed', action='dismissed'
                $stmt = $conn->prepare("UPDATE message_reports SET status = 'dismissed', action_taken = 'dismissed', reviewed_by = ?, reviewed_on = NOW() WHERE id = ?");
                $stmt->bind_param('ii', $admin_id, $report_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['status' => true, 'message' => 'Report dismissed associated']);
                } else {
                    echo json_encode(['status' => false, 'message' => 'Failed to dismiss report']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Communities - Admin Panel</title>
    <link rel="icon" href="../../assets/logo2.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html { background: #0f172a; min-height: 100vh; font-family: 'Inter', sans-serif; color: #e2e8f0; }
        
        .page-content { padding: 20px; display: flex; gap: 20px; height: calc(100vh - 40px); }
        
        /* Left Panel - Community List */
        .community-panel { width: 300px; flex-shrink: 0; display: flex; flex-direction: column; }
        .panel-header { padding: 15px; background: rgba(30, 41, 59, 0.9); border-radius: 12px 12px 0 0; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .panel-title { font-size: 16px; font-weight: 600; color: #f1f5f9; display: flex; align-items: center; gap: 8px; }
        .community-list { flex: 1; overflow-y: auto; background: rgba(30, 41, 59, 0.6); border-radius: 0 0 12px 12px; }
        .community-item { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); cursor: pointer; transition: all 0.2s; }
        .community-item:hover, .community-item.active { background: rgba(233, 67, 122, 0.1); }
        .community-item.active { border-left: 3px solid #E9437A; }
        .community-name { font-weight: 600; font-size: 14px; margin-bottom: 5px; }
        .community-stats { font-size: 12px; color: #64748b; display: flex; gap: 12px; }
        .community-stat { display: flex; align-items: center; gap: 4px; }
        .report-badge { background: #ef4444; color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px; margin-left: 8px; }
        
        /* Right Panel - Main Content */
        .main-panel { flex: 1; display: flex; flex-direction: column; gap: 15px; }
        
        /* Tabs */
        .tabs { display: flex; gap: 10px; }
        .tab-btn { padding: 10px 18px; background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; color: #94a3b8; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 8px; }
        .tab-btn:hover { background: rgba(255,255,255,0.05); }
        .tab-btn.active { background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%); color: white; border-color: transparent; }
        
        .tab-content { display: none; flex: 1; overflow: hidden; }
        .tab-content.active { display: flex; flex-direction: column; }
        
        /* Chat View */
        .chat-header { display: flex; justify-content: space-between; align-items: center; padding: 15px; background: rgba(30, 41, 59, 0.8); border-radius: 12px 12px 0 0; }
        .realtime-toggle { display: flex; align-items: center; gap: 10px; }
        .toggle-btn { padding: 8px 16px; background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; color: #f87171; font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 6px; }
        .toggle-btn.active { background: rgba(34, 197, 94, 0.2); border-color: rgba(34, 197, 94, 0.3); color: #4ade80; }
        .toggle-btn .dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
        .toggle-btn.active .dot { animation: pulse 1s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        
        .chat-messages { flex: 1; overflow-y: auto; padding: 15px; background: rgba(15, 23, 42, 0.5); display: flex; flex-direction: column; gap: 12px; }
        .message { display: flex; gap: 12px; padding: 12px; background: rgba(30, 41, 59, 0.6); border-radius: 10px; }
        .message.reported { border-left: 3px solid #f59e0b; background: rgba(245, 158, 11, 0.1); }
        .message-avatar { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%); display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0; overflow: hidden; }
        .message-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .message-content { flex: 1; min-width: 0; }
        .message-header { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; }
        .message-user { font-weight: 600; font-size: 13px; color: #f1f5f9; }
        .message-qr { font-size: 11px; color: #60a5fa; }
        .message-time { font-size: 11px; color: #64748b; margin-left: auto; }
        .message-text { font-size: 13px; line-height: 1.5; word-break: break-word; }
        .message-actions { display: flex; gap: 8px; margin-top: 8px; }
        .msg-btn { padding: 4px 10px; border-radius: 6px; font-size: 11px; cursor: pointer; border: none; }
        .msg-btn.delete { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .msg-btn.delete:hover { background: rgba(239, 68, 68, 0.25); }
        .message-reactions { display: flex; gap: 10px; margin-top: 6px; font-size: 11px; color: #64748b; }
        
        /* Members View */
        .members-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; padding: 15px; overflow-y: auto; }
        .member-card { background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 15px; }
        .member-card.banned { border-color: rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.05); }
        .member-card.timeout { border-color: rgba(245, 158, 11, 0.3); background: rgba(245, 158, 11, 0.05); }
        .member-card.moderator { border-color: rgba(168, 85, 247, 0.3); background: rgba(168, 85, 247, 0.05); }
        .member-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .member-avatar { width: 45px; height: 45px; border-radius: 12px; background: linear-gradient(135deg, #E9437A, #E2AD2A); display: flex; align-items: center; justify-content: center; font-weight: 600; overflow: hidden; }
        .member-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .member-info { flex: 1; }
        .member-name { font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .member-qr { font-size: 12px; color: #60a5fa; }
        .badge { padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: 500; }
        .badge.mod { background: rgba(168, 85, 247, 0.2); color: #a855f7; }
        .badge.banned { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .badge.timeout { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .member-actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .action-btn { padding: 6px 12px; border-radius: 6px; font-size: 11px; cursor: pointer; border: none; display: flex; align-items: center; gap: 5px; }
        .action-btn.ban { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .action-btn.timeout { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .action-btn.unban { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
        .action-btn.mod { background: rgba(168, 85, 247, 0.15); color: #a855f7; }
        .action-btn:hover { opacity: 0.8; }
        
        /* Reports View */
        .reports-list { padding: 15px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; }
        .report-card { background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 12px; padding: 15px; }
        .report-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .report-info { font-size: 12px; color: #64748b; }
        .report-message { background: rgba(15, 23, 42, 0.5); padding: 12px; border-radius: 8px; margin-bottom: 12px; font-size: 13px; }
        .report-reason { font-size: 12px; color: #fbbf24; margin-bottom: 10px; }
        .report-actions { display: flex; gap: 10px; }
        
        .report-names { display: flex; flex-direction: column; }
        .sub-text { font-size: 11px; color: #64748b; margin-top: 2px; }
        .resolved-footer { margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.05); font-size: 11px; color: #059669; display: flex; align-items: center; gap: 6px; }
        
        /* Modal */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); display: none; align-items: center; justify-content: center; z-index: 2000; padding: 20px; }
        .modal-overlay.show { display: flex; }
        .modal { background: #1e293b; border-radius: 16px; width: 100%; max-width: 400px; padding: 25px; }
        .modal-title { font-size: 18px; font-weight: 600; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; font-size: 13px; color: #94a3b8; margin-bottom: 6px; }
        .form-input, .form-select { width: 100%; padding: 12px; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #e2e8f0; font-size: 14px; }
        .form-input:focus, .form-select:focus { outline: none; border-color: #E9437A; }
        .modal-actions { display: flex; gap: 10px; margin-top: 20px; }
        .btn { padding: 12px 20px; border-radius: 8px; font-size: 14px; cursor: pointer; border: none; flex: 1; }
        .btn.primary { background: linear-gradient(135deg, #E9437A, #E2AD2A); color: white; }
        .btn.secondary { background: rgba(255,255,255,0.05); color: #94a3b8; }
        
        .toast { position: fixed; bottom: 30px; right: 30px; padding: 16px 24px; border-radius: 10px; font-size: 14px; z-index: 3000; transform: translateX(150%); transition: transform 0.3s ease; }
        .toast.show { transform: translateX(0); }
        .toast.success { background: rgba(34, 197, 94, 0.9); color: white; }
        .toast.error { background: rgba(239, 68, 68, 0.9); color: white; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
        .empty-state i { font-size: 48px; margin-bottom: 15px; opacity: 0.5; }
        
        @media (max-width: 900px) {
            .page-content { flex-direction: column; height: auto; }
            .community-panel { width: 100%; max-height: 300px; }
            .main-panel { min-height: 500px; }
        }
    </style>
</head>
<body>
    <?php include('../components/sidebar.php'); ?>
    
    <main class="admin-main">
        <div class="page-content">
            <!-- Left Panel - Community List -->
            <div class="community-panel">
                <div class="panel-header">
                    <h3 class="panel-title"><i class="fas fa-users"></i> Communities</h3>
                </div>
                <div class="community-list" id="community-list">
                    <div class="empty-state">Loading...</div>
                </div>
            </div>
            
            <!-- Right Panel - Main Content -->
            <div class="main-panel" id="main-panel" style="display: none;">
                <div class="tabs">
                    <button class="tab-btn active" onclick="showTab('chat')"><i class="fas fa-comments"></i> Chat</button>
                    <button class="tab-btn" onclick="showTab('members')"><i class="fas fa-user-friends"></i> Members</button>
                    <button class="tab-btn" onclick="showTab('reports')"><i class="fas fa-flag"></i> Reports</button>
                </div>
                
                <!-- Chat Tab -->
                <div class="tab-content active" id="tab-chat">
                    <div class="chat-header">
                        <span id="chat-community-name">Community Chat</span>
                        <div class="realtime-toggle">
                            <button class="toggle-btn" id="realtime-toggle" onclick="toggleRealtime()">
                                <span class="dot"></span> Real-time
                            </button>
                        </div>
                    </div>
                    <div class="chat-messages" id="chat-messages">
                        <div class="empty-state"><i class="fas fa-comments"></i><br>Select a community to view chat</div>
                    </div>
                </div>
                
                <!-- Members Tab -->
                <div class="tab-content" id="tab-members">
                    <div class="members-grid" id="members-grid">
                        <div class="empty-state"><i class="fas fa-users"></i><br>Select a community to view members</div>
                    </div>
                </div>
                
                <!-- Reports Tab -->
                <div class="tab-content" id="tab-reports">
                    <div class="reports-list" id="reports-list">
                        <div class="empty-state"><i class="fas fa-flag"></i><br>No pending reports</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Timeout Modal -->
    <div class="modal-overlay" id="timeoutModal">
        <div class="modal">
            <h3 class="modal-title"><i class="fas fa-clock"></i> Timeout User</h3>
            <input type="hidden" id="timeout-user-id">
            <div class="form-group">
                <label class="form-label">Duration</label>
                <select class="form-select" id="timeout-duration">
                    <option value="5">5 minutes</option>
                    <option value="15">15 minutes</option>
                    <option value="30" selected>30 minutes</option>
                    <option value="60">1 hour</option>
                    <option value="1440">24 hours</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Reason</label>
                <input type="text" class="form-input" id="timeout-reason" placeholder="Enter reason...">
            </div>
            <div class="modal-actions">
                <button class="btn secondary" onclick="closeModal('timeoutModal')">Cancel</button>
                <button class="btn primary" onclick="applyTimeout()">Apply Timeout</button>
            </div>
        </div>
    </div>
    
    <!-- Ban Modal -->
    <div class="modal-overlay" id="banModal">
        <div class="modal">
            <h3 class="modal-title"><i class="fas fa-ban"></i> Ban User</h3>
            <input type="hidden" id="ban-user-id">
            <div class="form-group">
                <label class="form-label">Reason</label>
                <input type="text" class="form-input" id="ban-reason" placeholder="Enter reason for ban...">
            </div>
            <div class="modal-actions">
                <button class="btn secondary" onclick="closeModal('banModal')">Cancel</button>
                <button class="btn primary" onclick="applyBan()">Ban User</button>
            </div>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentCommunityId = null;
        let realtimeInterval = null;
        let isRealtime = false;
        
        $(document).ready(function() {
            loadCommunities();
        });
        
        function loadCommunities() {
            $.post('', { action: 'get_communities' }, function(response) {
                if (response.status && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(c => {
                        html += `
                            <div class="community-item" onclick="selectCommunity(${c.id}, '${escapeHtml(c.name)}')" data-id="${c.id}">
                                <div class="community-name">
                                    ${escapeHtml(c.name)}
                                    ${c.pending_reports > 0 ? `<span class="report-badge">${c.pending_reports}</span>` : ''}
                                </div>
                                <div class="community-stats">
                                    <span class="community-stat"><i class="fas fa-user"></i> ${c.member_count || 0}</span>
                                    <span class="community-stat"><i class="fas fa-comment"></i> ${c.message_count || 0}</span>
                                </div>
                            </div>
                        `;
                    });
                    $('#community-list').html(html);
                } else {
                    $('#community-list').html('<div class="empty-state"><i class="fas fa-users-slash"></i><br>No communities found</div>');
                }
            }, 'json');
        }
        
        function selectCommunity(id, name) {
            currentCommunityId = id;
            $('.community-item').removeClass('active');
            $(`.community-item[data-id="${id}"]`).addClass('active');
            $('#main-panel').show();
            $('#chat-community-name').text(name);
            
            loadChat();
            loadMembers();
            loadReports();
        }
        
        function loadChat() {
            if (!currentCommunityId) return;
            
            $.post('', { action: 'get_chat', community_id: currentCommunityId }, function(response) {
                if (response.status && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(msg => {
                        const avatar = msg.user_image_path 
                            ? `<img src="../../../user/src/${msg.user_image_path}" alt="">` 
                            : (msg.user_full_name ? msg.user_full_name.charAt(0).toUpperCase() : '?');
                        
                        html += `
                            <div class="message ${msg.is_reported ? 'reported' : ''}">
                                <div class="message-avatar">${avatar}</div>
                                <div class="message-content">
                                    <div class="message-header">
                                        <span class="message-user">${escapeHtml(msg.user_full_name)}</span>
                                        <span class="message-qr">${escapeHtml(msg.user_qr_id)}</span>
                                        <span class="message-time">${formatTime(msg.created_on)}</span>
                                    </div>
                                    <div class="message-text">${escapeHtml(msg.message)}</div>
                                    <div class="message-reactions">
                                        <span><i class="fas fa-thumbs-up"></i> ${msg.likes || 0}</span>
                                        <span><i class="fas fa-thumbs-down"></i> ${msg.dislikes || 0}</span>
                                    </div>
                                    <div class="message-actions">
                                        <button class="msg-btn delete" onclick="deleteMessage(${msg.id})"><i class="fas fa-trash"></i> Delete</button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    $('#chat-messages').html(html);
                    // Scroll to bottom
                    $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
                } else {
                    $('#chat-messages').html('<div class="empty-state"><i class="fas fa-comments"></i><br>No messages yet</div>');
                }
            }, 'json');
        }
        
        function loadMembers() {
            if (!currentCommunityId) return;
            
            $.post('', { action: 'get_members', community_id: currentCommunityId }, function(response) {
                if (response.status && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(m => {
                        const avatar = m.user_image_path 
                            ? `<img src="../../../user/src/${m.user_image_path}" alt="">` 
                            : (m.user_full_name ? m.user_full_name.charAt(0).toUpperCase() : '?');
                        
                        let cardClass = '';
                        let badges = '';
                        let actions = '';
                        
                        if (m.role === 'moderator' || m.role === 'admin') {
                            cardClass = 'moderator';
                            badges += `<span class="badge mod"><i class="fas fa-shield-alt"></i> ${m.role}</span>`;
                        }
                        
                        if (m.active_penalty === 'ban') {
                            cardClass = 'banned';
                            badges += '<span class="badge banned"><i class="fas fa-ban"></i> Banned</span>';
                            actions = `<button class="action-btn unban" onclick="unbanUser(${m.user_id})"><i class="fas fa-unlock"></i> Unban</button>`;
                        } else if (m.active_penalty === 'timeout') {
                            cardClass = 'timeout';
                            badges += '<span class="badge timeout"><i class="fas fa-clock"></i> Timeout</span>';
                            actions = `<button class="action-btn unban" onclick="unbanUser(${m.user_id})"><i class="fas fa-unlock"></i> Remove Timeout</button>`;
                        } else {
                            actions = `
                                <button class="action-btn timeout" onclick="showTimeoutModal(${m.user_id})"><i class="fas fa-clock"></i> Timeout</button>
                                <button class="action-btn ban" onclick="showBanModal(${m.user_id})"><i class="fas fa-ban"></i> Ban</button>
                            `;
                        }
                        
                        // Mod toggle
                        if (m.role === 'moderator') {
                            actions += `<button class="action-btn mod" onclick="toggleMod(${m.user_id}, false)"><i class="fas fa-user-minus"></i> Remove Mod</button>`;
                        } else if (!m.active_penalty) {
                            actions += `<button class="action-btn mod" onclick="toggleMod(${m.user_id}, true)"><i class="fas fa-user-shield"></i> Make Mod</button>`;
                        }
                        
                        html += `
                            <div class="member-card ${cardClass}">
                                <div class="member-header">
                                    <div class="member-avatar">${avatar}</div>
                                    <div class="member-info">
                                        <div class="member-name">${escapeHtml(m.user_full_name)} ${badges}</div>
                                        <div class="member-qr">${escapeHtml(m.user_qr_id)}</div>
                                    </div>
                                </div>
                                <div class="member-actions">${actions}</div>
                            </div>
                        `;
                    });
                    $('#members-grid').html(html);
                } else {
                    $('#members-grid').html('<div class="empty-state"><i class="fas fa-users"></i><br>No members found</div>');
                }
            }, 'json');
        }
        
        function loadReports() {
            $.post('', { action: 'get_reports', community_id: currentCommunityId || 0 }, function(response) {
                if (response.status && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(r => {
                        let actions = '';
                        let headerBadge = '';
                        let footerInfo = '';
                        let cardClass = 'report-card';
                        
                        if (r.status === 'pending') {
                            actions = `
                                <div class="report-actions">
                                    <button class="action-btn ban" onclick="deleteMessage(${r.message_id});"><i class="fas fa-trash"></i> Delete</button>
                                    <button class="action-btn unban" onclick="dismissReport(${r.id})"><i class="fas fa-check"></i> Dismiss</button>
                                </div>
                            `;
                            headerBadge = '<span class="status-badge pending">Pending</span>';
                        } else {
                            const action = r.action_taken ? r.action_taken.replace('_', ' ') : r.status;
                            const reviewer = r.reviewer_name ? r.reviewer_name : 'Admin';
                            
                            // Footer info for resolved items instead of squeezing into header
                            footerInfo = `
                                <div class="resolved-footer">
                                    <i class="fas fa-info-circle"></i> ${action} by ${reviewer}
                                </div>
                            `;
                            headerBadge = `<span class="status-badge resolved">${r.status}</span>`;
                            cardClass += ' resolved';
                        }
                        
                        html += `
                            <div class="${cardClass}">
                                <div class="report-header">
                                    <div class="report-names">
                                        <strong>${escapeHtml(r.sender_name)}</strong>
                                        <span class="sub-text">reported by ${escapeHtml(r.reporter_name)}</span>
                                    </div>
                                    <div class="report-meta">
                                        ${headerBadge}
                                        <span class="report-time">${formatTime(r.created_on)}</span>
                                    </div>
                                </div>
                                <div class="report-content">
                                    <div class="report-message">"${escapeHtml(r.message)}"</div>
                                    <div class="report-reason"><i class="fas fa-exclamation-triangle"></i> ${escapeHtml(r.reason || 'No reason')}</div>
                                </div>
                                ${actions}
                                ${footerInfo}
                            </div>
                        `;
                    });
                    $('#reports-list').html(html);
                } else {
                    $('#reports-list').html('<div class="empty-state"><i class="fas fa-check-circle"></i><br>No reports found</div>');
                }
            }, 'json');
        }
        
        function toggleRealtime() {
            isRealtime = !isRealtime;
            const btn = $('#realtime-toggle');
            
            if (isRealtime) {
                btn.addClass('active');
                realtimeInterval = setInterval(loadChat, 5000);
                showToast('Real-time chat enabled', 'success');
            } else {
                btn.removeClass('active');
                clearInterval(realtimeInterval);
                showToast('Real-time chat disabled', 'success');
            }
        }
        
        function showTab(tab) {
            $('.tab-btn').removeClass('active');
            $(`.tab-btn:contains('${tab === 'chat' ? 'Chat' : tab === 'members' ? 'Members' : 'Reports'}')`).addClass('active');
            $('.tab-content').removeClass('active');
            $(`#tab-${tab}`).addClass('active');
        }
        
        function showTimeoutModal(userId) {
            $('#timeout-user-id').val(userId);
            $('#timeout-reason').val('');
            $('#timeoutModal').addClass('show');
        }
        
        function showBanModal(userId) {
            $('#ban-user-id').val(userId);
            $('#ban-reason').val('');
            $('#banModal').addClass('show');
        }
        
        function closeModal(id) {
            $(`#${id}`).removeClass('show');
        }
        
        function applyTimeout() {
            const userId = $('#timeout-user-id').val();
            const duration = $('#timeout-duration').val();
            const reason = $('#timeout-reason').val() || 'Timed out by admin';
            
            $.post('', { action: 'timeout_user', user_id: userId, community_id: currentCommunityId, duration: duration, reason: reason }, function(response) {
                showToast(response.message, response.status ? 'success' : 'error');
                if (response.status) {
                    closeModal('timeoutModal');
                    loadMembers();
                }
            }, 'json');
        }
        
        function applyBan() {
            const userId = $('#ban-user-id').val();
            const reason = $('#ban-reason').val() || 'Banned by admin';
            
            $.post('', { action: 'ban_user', user_id: userId, community_id: currentCommunityId, reason: reason }, function(response) {
                showToast(response.message, response.status ? 'success' : 'error');
                if (response.status) {
                    closeModal('banModal');
                    loadMembers();
                }
            }, 'json');
        }
        
        function unbanUser(userId) {
            console.log('unbanUser called', userId);
            
            $.post('', { action: 'unban_user', user_id: userId, community_id: currentCommunityId }, function(response) {
                console.log('Unban response:', response);
                showToast(response.message, response.status ? 'success' : 'error');
                if (response.status) loadMembers();
            }, 'json')
            .fail(function(xhr) { console.error(xhr.responseText); });
        }
        
        function toggleMod(userId, makeMod) {
            console.log('toggleMod called', userId, makeMod);
            
            $.post('', { action: 'toggle_moderator', user_id: userId, community_id: currentCommunityId, make_mod: makeMod }, function(response) {
                console.log('Response received:', response);
                showToast(response.message, response.status ? 'success' : 'error');
                if (response.status) loadMembers();
            }, 'json')
            .fail(function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                showToast('Network error occurred', 'error');
            });
        }
        
        function deleteMessage(messageId) {
            console.log('deleteMessage called', messageId);
            
            $.post('', { action: 'delete_message', message_id: messageId }, function(response) {
                console.log('Delete message response:', response);
                showToast(response.message, response.status ? 'success' : 'error');
                if (response.status) {
                    loadChat();
                    loadReports(); // Also update reports list
                }
            }, 'json')
            .fail(function(xhr) { console.error(xhr.responseText); });
        }
        
        function dismissReport(reportId) {
            console.log('dismissReport called', reportId);
            $.post('', { action: 'dismiss_report', report_id: reportId }, function(response) {
                console.log('Dismiss report response:', response);
                if (response.status) {
                    loadReports();
                    loadCommunities(); // Update report badges
                } else {
                    showToast(response.message, 'error');
                }
            }, 'json')
            .fail(function(xhr) { console.error(xhr.responseText); });
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
        
        function formatTime(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleString('en-IN', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
        }
    </script>
</body>
</html>
