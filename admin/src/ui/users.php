<?php
// Include auth check
require_once('../components/auth_check.php');
require_once('../backend/dbconfig/connection.php');

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
                $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
                $types = 'ssss';
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
            
            <!-- Search -->
            <div class="search-bar">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" class="search-input" id="searchInput" placeholder="Search by name, email, phone, or QR ID...">
                </div>
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
        });
        
        function loadUsers(page) {
            currentPage = page;
            const search = $('#searchInput').val().trim();
            
            $.post('', { action: 'get_all', page: page, search: search }, function(response) {
                if (response.status && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(user => {
                        const avatar = user.user_image_path 
                            ? `<img src="../../../user/src/${user.user_image_path}" alt="">` 
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
                        ? `<img src="../../../user/src/${user.user_image_path}" alt="">` 
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
                            <div class="detail-item">
                                <div class="detail-label">Joined</div>
                                <div class="detail-value">${formatDate(user.created_on)}</div>
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
    </script>
</body>
</html>
