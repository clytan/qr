<?php
/**
 * Notifications Management
 * Allows admins to send notifications to all users, specific communities, or individual users.
 */

include '../components/auth_check.php';
include '../backend/dbconfig/connection.php';

// Handle AJAX actions
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $conn = $GLOBALS['conn']; // Use connection from included file
    
    switch ($_POST['action']) {
        case 'get_communities':
            try {
                $result = $conn->query("SELECT id, name FROM community WHERE is_deleted = 0 ORDER BY name");
                $communities = [];
                while ($row = $result->fetch_assoc()) {
                    $communities[] = $row;
                }
                echo json_encode(['status' => true, 'data' => $communities]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();

        case 'search_users':
            try {
                $query = $_POST['query'] ?? '';
                $sql = "SELECT id, user_full_name, user_qr_id FROM user_user WHERE is_deleted = 0";
                
                if (strlen($query) >= 1) {
                    $sql .= " AND (user_full_name LIKE ? OR user_qr_id LIKE ?)";
                    $sql .= " LIMIT 20";
                    $stmt = $conn->prepare($sql);
                    $term = "%$query%";
                    $stmt->bind_param('ss', $term, $term);
                } else {
                    $sql .= " ORDER BY id DESC LIMIT 20"; // Recent users
                    $stmt = $conn->prepare($sql);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                
                $users = [];
                while ($row = $result->fetch_assoc()) {
                    $users[] = $row;
                }
                echo json_encode(['status' => true, 'data' => $users]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();

        case 'send_notification':
            try {
                $target_type = $_POST['target_type'] ?? '';
                $subject = trim($_POST['subject'] ?? '');
                $message = trim($_POST['message'] ?? '');
                $link = trim($_POST['link'] ?? '');
                $admin_id = $_SESSION['admin_id'] ?? 0;

                if (empty($message)) {
                    echo json_encode(['status' => false, 'message' => 'Message cannot be empty']);
                    exit();
                }

                // Default subject if empty
                if (empty($subject)) {
                    $subject = 'New Notification';
                }

                $count = 0;

                if ($target_type === 'all') {
                    $stmt = $conn->prepare("INSERT INTO user_notifications (user_id, subject, message, link, created_by, created_on, is_read, is_deleted) 
                                          SELECT id, ?, ?, ?, ?, NOW(), 0, 0 FROM user_user WHERE is_deleted = 0");
                    $stmt->bind_param('sssi', $subject, $message, $link, $admin_id);
                    $stmt->execute();
                    $count = $stmt->affected_rows;
                    
                } else if ($target_type === 'community') {
                    $target_id = intval($_POST['target_id'] ?? 0);
                    if ($target_id <= 0) {
                        echo json_encode(['status' => false, 'message' => 'Community ID required']);
                        exit();
                    }
                    $stmt = $conn->prepare("INSERT INTO user_notifications (user_id, subject, message, link, created_by, created_on, is_read, is_deleted) 
                                          SELECT DISTINCT user_id, ?, ?, ?, ?, NOW(), 0, 0 FROM community_members WHERE community_id = ? AND is_deleted = 0");
                    $stmt->bind_param('sssii', $subject, $message, $link, $admin_id, $target_id);
                    $stmt->execute();
                    $count = $stmt->affected_rows;
                    
                } else if ($target_type === 'user') {
                    // Handle Multiple Users
                    $target_ids = $_POST['target_ids'] ?? []; // Expecting array
                    if (empty($target_ids)) {
                        echo json_encode(['status' => false, 'message' => 'No users selected']);
                        exit();
                    }
                    
                    if (!is_array($target_ids)) $target_ids = [$target_ids]; // Fallback
                    
                    $stmt = $conn->prepare("INSERT INTO user_notifications (user_id, subject, message, link, created_by, created_on, is_read, is_deleted) VALUES (?, ?, ?, ?, ?, NOW(), 0, 0)");
                    foreach ($target_ids as $uid) {
                        $uid = intval($uid);
                        if ($uid > 0) {
                            $stmt->bind_param('isssi', $uid, $subject, $message, $link, $admin_id);
                            $stmt->execute();
                            $count++;
                        }
                    }
                    
                } else {
                    echo json_encode(['status' => false, 'message' => 'Invalid target type']);
                    exit();
                }

                if ($count > 0) {
                    echo json_encode(['status' => true, 'message' => "Notification sent to $count user(s)"]);
                } else {
                    echo json_encode(['status' => false, 'message' => 'No users found to notify']);
                }

            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Notifications - Admin Panel</title>
    <link rel="icon" href="../../assets/logo2.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html { background: #0f172a; min-height: 100vh; font-family: 'Inter', sans-serif; color: #e2e8f0; }
        
        .page-content { padding: 30px; display: flex; gap: 30px; max-width: 1200px; margin: 0 auto; }
        .main-panel { flex: 1; display: flex; flex-direction: column; gap: 20px; }
        
        .card { background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 25px; }
        .card-header { margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; }
        .card-title { font-size: 18px; font-weight: 600; color: #f1f5f9; display: flex; align-items: center; gap: 10px; }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 14px; color: #94a3b8; margin-bottom: 8px; font-weight: 500; }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 12px 15px; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #e2e8f0; font-size: 14px; transition: all 0.2s; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: #E9437A; background: rgba(15, 23, 42, 0.8); }
        .form-textarea { min-height: 120px; resize: vertical; }
        
        .btn { padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(135deg, #E9437A 0%, #e67753 100%); color: white; }
        .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        
        .search-results { position: absolute; top: 100%; left: 0; right: 0; background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; max-height: 200px; overflow-y: auto; z-index: 100; display: none; margin-top: 5px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        .search-result-item { padding: 10px 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .search-result-item:hover { background: rgba(233, 67, 122, 0.1); }
        .search-result-item:last-child { border-bottom: none; }
        .user-name { font-weight: 600; color: #f1f5f9; }
        .user-qr { font-size: 12px; color: #64748b; }
        
        .selected-user { display: inline-flex; align-items: center; gap: 10px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2); padding: 8px 15px; border-radius: 50px; margin-top: 10px; }
        .selected-user-info { display: flex; flex-direction: column; }
        .selected-name { font-weight: 600; font-size: 13px; color: #4ade80; }
        .selected-qr { font-size: 11px; color: #86efac; }
        .remove-user { color: #86efac; cursor: pointer; padding: 5px; border-radius: 50%; }
        .remove-user:hover { background: rgba(34, 197, 94, 0.2); }

        .toast { position: fixed; bottom: 30px; right: 30px; padding: 16px 24px; border-radius: 10px; font-size: 14px; z-index: 3000; transform: translateX(150%); transition: transform 0.3s ease; }
        .toast.show { transform: translateX(0); }
        .toast.success { background: rgba(34, 197, 94, 0.9); color: white; }
        .toast.error { background: rgba(239, 68, 68, 0.9); color: white; }

        @media (max-width: 768px) {
            .page-content { padding: 15px; }
        }
    </style>
</head>
<body>
    <?php include('../components/sidebar.php'); ?>
    
    <main class="admin-main">
        <div class="page-content">
            <div class="main-panel">
                <!-- Header -->
                <div class="header" style="margin-bottom: 20px;">
                    <h1 style="font-size: 24px; font-weight: 700; color: #fff;">Notifications Center</h1>
                    <p style="color: #94a3b8; font-size: 14px; margin-top: 5px;">Send alerts and messages to users instantly</p>
                </div>

                <!-- Broadcast Card -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-bullhorn" style="color: #E9437A;"></i> Send Notification
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Target Audience</label>
                        <select class="form-select" id="target-type" onchange="handleTargetChange()">
                            <option value="all">Every User (Global Broadcast)</option>
                            <option value="community">Specific Community</option>
                            <option value="user">Individual User</option>
                        </select>
                    </div>

                    <!-- Community Selector -->
                    <div class="form-group" id="community-select-group" style="display: none;">
                        <label class="form-label">Select Community</label>
                        <select class="form-select" id="community-select">
                            <option value="">Loading communities...</option>
                        </select>
                    </div>

                    <!-- User Search -->
                    <div class="form-group" id="user-search-group" style="display: none; position: relative;">
                        <label class="form-label">Search Users (or click to see recent)</label>
                        <input type="text" class="form-input" id="user-search" placeholder="Type Name or QR ID..." autocomplete="off">
                        <div class="search-results" id="search-results"></div>
                        
                        <div id="selected-users-container" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <input type="text" class="form-input" id="notification-subject" placeholder="Enter notification subject..." maxlength="100">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Message</label>
                        <textarea class="form-textarea" id="notification-message" placeholder="Type your message here..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Link (Optional)</label>
                        <input type="url" class="form-input" id="notification-link" placeholder="https://example.com/page">
                        <p style="font-size: 12px; color: #64748b; margin-top: 5px;">Users can click this button to open the link directly</p>
                    </div>

                    <button class="btn btn-primary" id="send-btn" onclick="sendNotification()">
                        <i class="fas fa-paper-plane"></i> Send Notification
                    </button>
                </div>
            </div>
        </div>
    </main>

    <div class="toast" id="toast"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let selectedUsers = [];

        $(document).ready(function() {
            loadCommunities();
            
            // Search Debounce
            let searchTimeout;
            $('#user-search').on('input focus', function() {
                clearTimeout(searchTimeout);
                const query = $(this).val();
                
                searchTimeout = setTimeout(() => {
                    $.post('', { action: 'search_users', query: query }, function(res) {
                        if (res.status && res.data.length > 0) {
                            let html = '';
                            res.data.forEach(u => {
                                // Don't show if already selected
                                if (selectedUsers.some(su => su.id == u.id)) return;
                                
                                html += `
                                    <div class="search-result-item" onclick="selectUser(${u.id}, '${escapeHtml(u.user_full_name)}', '${escapeHtml(u.user_qr_id)}')">
                                        <div>
                                            <div class="user-name">${escapeHtml(u.user_full_name)}</div>
                                            <div class="user-qr">${escapeHtml(u.user_qr_id)}</div>
                                        </div>
                                        <i class="fas fa-plus" style="color: #4ade80;"></i>
                                    </div>
                                `;
                            });
                            $('#search-results').html(html).show();
                        } else {
                            $('#search-results').hide();
                        }
                    }, 'json');
                }, 300);
            });

            // Close search on click outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#user-search-group').length) {
                    $('#search-results').hide();
                }
            });
        });

        function loadCommunities() {
            $.post('', { action: 'get_communities' }, function(res) {
                if (res.status) {
                    let html = '<option value="">Select a community...</option>';
                    res.data.forEach(c => {
                        html += `<option value="${c.id}">${escapeHtml(c.name)}</option>`;
                    });
                    $('#community-select').html(html);
                }
            }, 'json');
        }

        function handleTargetChange() {
            const type = $('#target-type').val();
            $('#community-select-group').hide();
            $('#user-search-group').hide();
            
            if (type === 'community') $('#community-select-group').show();
            if (type === 'user') $('#user-search-group').show();
        }

        function selectUser(id, name, qr) {
            // Add to selected array
            if (!selectedUsers.some(u => u.id == id)) {
                selectedUsers.push({ id, name, qr });
            }
            renderSelectedUsers();
            $('#user-search').val('').focus(); // Keep focus for picking more
        }

        function removeUser(id) {
            selectedUsers = selectedUsers.filter(u => u.id != id);
            renderSelectedUsers();
        }

        function renderSelectedUsers() {
            const container = $('#selected-users-container');
            container.empty();
            selectedUsers.forEach(u => {
                container.append(`
                    <div class="selected-user" style="display:inline-flex;">
                        <div class="selected-user-info">
                            <span class="selected-name">${u.name}</span>
                            <span class="selected-qr">${u.qr}</span>
                        </div>
                        <i class="fas fa-times remove-user" onclick="removeUser(${u.id})"></i>
                    </div>
                `);
            });
            // Update search results to remove selected ones
            $('#user-search').trigger('input');
        }

        function sendNotification() {
            const type = $('#target-type').val();
            const message = $('#notification-message').val().trim();
            const subject = $('#notification-subject').val().trim();
            const link = $('#notification-link').val().trim();
            const btn = $('#send-btn');
            
            if (!message) {
                showToast('Please enter a message', 'error');
                return;
            }

            let data = { 
                action: 'send_notification', 
                target_type: type, 
                message: message,
                subject: subject,
                link: link
            };

            if (type === 'community') {
                const targetId = $('#community-select').val();
                if (!targetId) {
                    showToast('Please select a community', 'error');
                    return;
                }
                data.target_id = targetId;
            } else if (type === 'user') {
                if (selectedUsers.length === 0) {
                    showToast('Please select at least one user', 'error');
                    return;
                }
                data.target_ids = selectedUsers.map(u => u.id);
            }

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');

            $.post('', data, function(res) {
                if (res.status) {
                    showToast(res.message, 'success');
                    $('#notification-message').val(''); // Clear message
                    $('#notification-subject').val(''); // Clear subject
                    $('#notification-link').val(''); // Clear link
                    // Reset selection
                    if (type === 'user') {
                        selectedUsers = [];
                        renderSelectedUsers();
                    }
                } else {
                    showToast(res.message, 'error');
                }
            }, 'json')
            .fail(function(xhr) {
                console.error(xhr.responseText);
                showToast('Server error', 'error');
            })
            .always(function() {
                btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Send Notification');
            });
        }

        function showToast(msg, type) {
            const t = $('#toast');
            t.text(msg).removeClass('success error').addClass(type + ' show');
            setTimeout(() => t.removeClass('show'), 3000);
        }

        function escapeHtml(text) {
            return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    </script>
</body>
</html>
