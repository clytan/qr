<?php
// Include auth check
require_once('../components/auth_check.php');
require_once('../backend/dbconfig/connection.php');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'get_polls':
            try {
                $page = intval($_POST['page'] ?? 1);
                $limit = 20;
                $offset = ($page - 1) * $limit;
                $status_filter = trim($_POST['status_filter'] ?? '');
                $search = trim($_POST['search'] ?? '');
                
                $where = "WHERE p.is_deleted = 0";
                $params = [];
                $types = '';
                
                if (!empty($status_filter)) {
                    $where .= " AND p.status = ?";
                    $params[] = $status_filter;
                    $types .= 's';
                }
                
                if (!empty($search)) {
                    $where .= " AND (p.title LIKE ? OR u.user_full_name LIKE ?)";
                    $searchTerm = "%$search%";
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                    $types .= 'ss';
                }
                
                // Get total
                $countSql = "SELECT COUNT(*) as total FROM user_polls p LEFT JOIN user_user u ON p.user_id = u.id $where";
                $total = 0;
                if (!empty($params)) {
                    $countStmt = $conn->prepare($countSql);
                    $countStmt->bind_param($types, ...$params);
                    $countStmt->execute();
                    $total = $countStmt->get_result()->fetch_assoc()['total'];
                } else {
                    $total = $conn->query($countSql)->fetch_assoc()['total'];
                }
                
                // Get polls
                $sql = "SELECT p.*, 
                               u.user_full_name as creator_name, 
                               u.user_qr_id as creator_qr,
                               (SELECT COUNT(*) FROM user_poll_votes WHERE poll_id = p.id) as total_votes,
                               (SELECT COUNT(*) FROM user_poll_options WHERE poll_id = p.id) as options_count
                        FROM user_polls p
                        LEFT JOIN user_user u ON p.user_id = u.id
                        $where
                        ORDER BY p.created_on DESC
                        LIMIT $limit OFFSET $offset";
                
                $polls = [];
                if (!empty($params)) {
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                } else {
                    $result = $conn->query($sql);
                }
                
                while ($row = $result->fetch_assoc()) {
                    $polls[] = $row;
                }
                
                echo json_encode([
                    'status' => true,
                    'data' => $polls,
                    'pagination' => [
                        'total' => $total,
                        'page' => $page,
                        'pages' => $total > 0 ? ceil($total / $limit) : 0
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'get_poll_details':
            try {
                $poll_id = intval($_POST['poll_id'] ?? 0);
                
                // Get poll
                $stmt = $conn->prepare("SELECT p.*, u.user_full_name as creator_name, u.user_qr_id 
                                        FROM user_polls p 
                                        LEFT JOIN user_user u ON p.user_id = u.id 
                                        WHERE p.id = ?");
                $stmt->bind_param('i', $poll_id);
                $stmt->execute();
                $poll = $stmt->get_result()->fetch_assoc();
                
                // Get options with votes
                $optStmt = $conn->prepare("SELECT o.*, 
                                            (SELECT COUNT(*) FROM user_poll_votes WHERE option_id = o.id) as votes
                                           FROM user_poll_options o 
                                           WHERE o.poll_id = ? 
                                           ORDER BY o.option_order");
                $optStmt->bind_param('i', $poll_id);
                $optStmt->execute();
                $options = [];
                $optResult = $optStmt->get_result();
                while ($opt = $optResult->fetch_assoc()) {
                    $options[] = $opt;
                }
                
                echo json_encode(['status' => true, 'poll' => $poll, 'options' => $options]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'delete_poll':
            try {
                $poll_id = intval($_POST['poll_id'] ?? 0);
                $stmt = $conn->prepare("UPDATE user_polls SET is_deleted = 1, updated_on = NOW() WHERE id = ?");
                $stmt->bind_param('i', $poll_id);
                $stmt->execute();
                echo json_encode(['status' => true, 'message' => 'Poll deleted successfully']);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'close_poll':
            try {
                $poll_id = intval($_POST['poll_id'] ?? 0);
                $stmt = $conn->prepare("UPDATE user_polls SET status = 'closed', updated_on = NOW() WHERE id = ?");
                $stmt->bind_param('i', $poll_id);
                $stmt->execute();
                echo json_encode(['status' => true, 'message' => 'Poll closed successfully']);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'reopen_poll':
            try {
                $poll_id = intval($_POST['poll_id'] ?? 0);
                $stmt = $conn->prepare("UPDATE user_polls SET status = 'active', updated_on = NOW() WHERE id = ?");
                $stmt->bind_param('i', $poll_id);
                $stmt->execute();
                echo json_encode(['status' => true, 'message' => 'Poll reopened successfully']);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'create_poll':
            try {
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $options = json_decode($_POST['options'] ?? '[]', true);
                $admin_id = $_SESSION['admin_id'] ?? 0;
                
                if (empty($title) || count($options) < 2) {
                    echo json_encode(['status' => false, 'message' => 'Title and at least 2 options required']);
                    exit();
                }
                
                $conn->begin_transaction();
                
                $stmt = $conn->prepare("INSERT INTO user_polls (user_id, title, description, poll_type, status, is_admin_poll, created_by, created_on) VALUES (0, ?, ?, 'single', 'active', 1, ?, NOW())");
                $stmt->bind_param('ssi', $title, $description, $admin_id);
                $stmt->execute();
                $poll_id = $conn->insert_id;
                
                $optStmt = $conn->prepare("INSERT INTO user_poll_options (poll_id, option_text, option_order) VALUES (?, ?, ?)");
                $order = 1;
                foreach ($options as $opt) {
                    $opt = trim($opt);
                    if (!empty($opt)) {
                        $optStmt->bind_param('isi', $poll_id, $opt, $order);
                        $optStmt->execute();
                        $order++;
                    }
                }
                
                $conn->commit();
                echo json_encode(['status' => true, 'message' => 'Poll created successfully']);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'get_stats':
            try {
                $totalPolls = $conn->query("SELECT COUNT(*) as cnt FROM user_polls WHERE is_deleted = 0")->fetch_assoc()['cnt'];
                $activePolls = $conn->query("SELECT COUNT(*) as cnt FROM user_polls WHERE is_deleted = 0 AND status = 'active'")->fetch_assoc()['cnt'];
                $totalVotes = $conn->query("SELECT COUNT(*) as cnt FROM user_poll_votes")->fetch_assoc()['cnt'];
                $adminPolls = $conn->query("SELECT COUNT(*) as cnt FROM user_polls WHERE is_deleted = 0 AND is_admin_poll = 1")->fetch_assoc()['cnt'];
                
                echo json_encode([
                    'status' => true,
                    'data' => [
                        'total_polls' => $totalPolls,
                        'active_polls' => $activePolls,
                        'total_votes' => $totalVotes,
                        'admin_polls' => $adminPolls
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => true, 'data' => ['total_polls' => 0, 'active_polls' => 0, 'total_votes' => 0, 'admin_polls' => 0]]);
            }
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Polls - Admin Panel</title>
    <link rel="icon" href="../../assets/logo2.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html { background: #0f172a; min-height: 100vh; font-family: 'Inter', sans-serif; color: #e2e8f0; }
        
        .page-content { padding: 30px; }
        .page-header { margin-bottom: 25px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px; }
        .page-title { font-size: 28px; font-weight: 700; color: #f1f5f9; margin-bottom: 5px; }
        .page-subtitle { color: #64748b; font-size: 14px; }
        
        .btn-create { padding: 10px 20px; background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%); border: none; border-radius: 10px; color: white; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; }
        .btn-create:hover { opacity: 0.9; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 14px; padding: 20px; }
        .stat-icon { width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 15px; }
        .stat-icon.purple { background: rgba(168, 85, 247, 0.15); color: #a855f7; }
        .stat-icon.green { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
        .stat-icon.blue { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .stat-icon.gold { background: rgba(251, 191, 36, 0.15); color: #fbbf24; }
        .stat-value { font-size: 28px; font-weight: 700; color: #f1f5f9; }
        .stat-label { font-size: 13px; color: #64748b; margin-top: 5px; }
        
        .filters { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-input { padding: 10px 14px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #e2e8f0; font-size: 14px; }
        .filter-input:focus { outline: none; border-color: #e67753; }
        
        .table-container { background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 14px; overflow: hidden; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th { background: rgba(15, 23, 42, 0.5); padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #94a3b8; text-transform: uppercase; }
        td { padding: 14px 16px; border-top: 1px solid rgba(255, 255, 255, 0.05); font-size: 13px; }
        tr:hover td { background: rgba(255, 255, 255, 0.02); }
        
        .poll-title-cell { max-width: 250px; }
        .poll-title-text { font-weight: 600; color: #f1f5f9; margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .poll-creator { font-size: 12px; color: #64748b; }
        
        .badge { display: inline-flex; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
        .badge.active { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
        .badge.closed { background: rgba(148, 163, 184, 0.15); color: #94a3b8; }
        .badge.admin { background: rgba(168, 85, 247, 0.15); color: #a855f7; }
        
        .action-btns { display: flex; gap: 8px; }
        .btn-action { padding: 6px 10px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; transition: all 0.2s; }
        .btn-view { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .btn-close { background: rgba(234, 179, 8, 0.15); color: #fbbf24; }
        .btn-open { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
        .btn-delete { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .btn-action:hover { opacity: 0.8; }
        
        .pagination { display: flex; justify-content: center; gap: 8px; padding: 20px; }
        .page-btn { padding: 8px 14px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #94a3b8; cursor: pointer; }
        .page-btn.active, .page-btn:hover { background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%); color: white; border-color: transparent; }
        
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); display: none; align-items: center; justify-content: center; z-index: 2000; padding: 20px; }
        .modal-overlay.show { display: flex; }
        .modal { background: #1e293b; border-radius: 16px; width: 100%; max-width: 550px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
        .modal-title { font-size: 18px; font-weight: 600; color: #f1f5f9; }
        .modal-close { width: 36px; height: 36px; border-radius: 8px; border: none; background: rgba(255, 255, 255, 0.05); color: #94a3b8; cursor: pointer; font-size: 18px; }
        .modal-body { padding: 20px; overflow-y: auto; flex: 1; }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: #e2e8f0; margin-bottom: 8px; }
        .form-input, .form-textarea { width: 100%; padding: 12px 14px; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #e2e8f0; font-size: 14px; }
        .form-textarea { min-height: 80px; resize: vertical; }
        .form-input:focus, .form-textarea:focus { outline: none; border-color: #e67753; }
        
        .options-list { display: flex; flex-direction: column; gap: 10px; }
        .option-row { display: flex; gap: 10px; }
        .option-row .form-input { flex: 1; }
        .btn-remove-opt { padding: 10px 12px; background: rgba(239, 68, 68, 0.15); border: none; border-radius: 8px; color: #f87171; cursor: pointer; }
        .btn-add-opt { padding: 10px; background: rgba(59, 130, 246, 0.15); border: 2px dashed rgba(59, 130, 246, 0.3); border-radius: 8px; color: #60a5fa; cursor: pointer; text-align: center; margin-top: 10px; }
        
        .btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%); border: none; border-radius: 10px; color: white; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .btn-submit:hover { opacity: 0.9; }
        
        .poll-details { }
        .poll-meta { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px; }
        .poll-meta-item { background: rgba(15, 23, 42, 0.5); padding: 15px; border-radius: 10px; }
        .poll-meta-label { font-size: 11px; color: #64748b; margin-bottom: 4px; }
        .poll-meta-value { font-size: 15px; font-weight: 600; color: #f1f5f9; }
        
        .poll-options-list { }
        .poll-option-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: rgba(15, 23, 42, 0.5); border-radius: 10px; margin-bottom: 10px; }
        .poll-option-text { font-weight: 500; }
        .poll-option-votes { font-size: 13px; color: #fbbf24; font-weight: 600; }
        
        .toast { position: fixed; bottom: 30px; right: 30px; padding: 16px 24px; border-radius: 10px; font-size: 14px; font-weight: 500; z-index: 3000; transform: translateX(150%); transition: transform 0.3s ease; }
        .toast.show { transform: translateX(0); }
        .toast.success { background: rgba(34, 197, 94, 0.9); color: white; }
        .toast.error { background: rgba(239, 68, 68, 0.9); color: white; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
        
        @media (max-width: 768px) {
            .page-content { padding: 20px 15px; }
            .poll-meta { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include('../components/sidebar.php'); ?>
    
    <main class="admin-main">
        <div class="page-content">
            <div class="page-header">
                <div>
                    <h1 class="page-title">📊 Polls Management</h1>
                    <p class="page-subtitle">Manage community polls, view results, and create admin polls</p>
                </div>
                <button class="btn-create" onclick="showCreateModal()">
                    <i class="fas fa-plus"></i> Create Poll
                </button>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-poll"></i></div>
                    <div class="stat-value" id="stat-total">-</div>
                    <div class="stat-label">Total Polls</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-value" id="stat-active">-</div>
                    <div class="stat-label">Active Polls</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-vote-yea"></i></div>
                    <div class="stat-value" id="stat-votes">-</div>
                    <div class="stat-label">Total Votes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="fas fa-shield-alt"></i></div>
                    <div class="stat-value" id="stat-admin">-</div>
                    <div class="stat-label">Admin Polls</div>
                </div>
            </div>
            
            <div class="filters">
                <input type="text" class="filter-input" id="searchInput" placeholder="Search polls..." style="flex: 1; min-width: 200px;">
                <select class="filter-input" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            
            <div class="table-container">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Poll</th>
                                <th>Options</th>
                                <th>Votes</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="polls-table-body">
                            <tr><td colspan="6" class="empty-state">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="pagination" id="polls-pagination"></div>
            </div>
        </div>
    </main>
    
    <!-- Create Poll Modal -->
    <div class="modal-overlay" id="createModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Create Admin Poll</h3>
                <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="createPollForm" onsubmit="createPoll(event)">
                    <div class="form-group">
                        <label class="form-label">Question *</label>
                        <input type="text" class="form-input" id="pollTitle" placeholder="What do you want to ask?" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-textarea" id="pollDescription" placeholder="Add more context..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Options *</label>
                        <div class="options-list" id="optionsList">
                            <div class="option-row"><input type="text" class="form-input" placeholder="Option 1" required></div>
                            <div class="option-row"><input type="text" class="form-input" placeholder="Option 2" required></div>
                        </div>
                        <button type="button" class="btn-add-opt" onclick="addOption()"><i class="fas fa-plus"></i> Add Option</button>
                    </div>
                    <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Create Poll</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- View Poll Modal -->
    <div class="modal-overlay" id="viewModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="viewModalTitle">Poll Details</h3>
                <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
            </div>
            <div class="modal-body" id="viewModalBody">Loading...</div>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            loadStats();
            loadPolls(1);
            
            $('#searchInput').on('keyup', debounce(function() { loadPolls(1); }, 300));
            $('#statusFilter').on('change', function() { loadPolls(1); });
        });
        
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }
        
        function loadStats() {
            $.post('', { action: 'get_stats' }, function(response) {
                if (response.status) {
                    $('#stat-total').text(response.data.total_polls);
                    $('#stat-active').text(response.data.active_polls);
                    $('#stat-votes').text(response.data.total_votes);
                    $('#stat-admin').text(response.data.admin_polls);
                }
            }, 'json');
        }
        
        function loadPolls(page) {
            $.post('', {
                action: 'get_polls',
                page: page,
                search: $('#searchInput').val(),
                status_filter: $('#statusFilter').val()
            }, function(response) {
                if (response.status && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(poll => {
                        const statusBadge = poll.status === 'active' 
                            ? '<span class="badge active">Active</span>' 
                            : '<span class="badge closed">Closed</span>';
                        const adminBadge = poll.is_admin_poll == 1 ? ' <span class="badge admin">Admin</span>' : '';
                        
                        html += `
                            <tr>
                                <td class="poll-title-cell">
                                    <div class="poll-title-text">${escapeHtml(poll.title)}</div>
                                    <div class="poll-creator">by ${escapeHtml(poll.creator_name || 'Admin')} ${poll.creator_qr ? '(' + poll.creator_qr + ')' : ''}</div>
                                </td>
                                <td>${poll.options_count} options</td>
                                <td><strong style="color: #fbbf24;">${poll.total_votes}</strong></td>
                                <td>${statusBadge}${adminBadge}</td>
                                <td>${formatDate(poll.created_on)}</td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-action btn-view" onclick="viewPoll(${poll.id})"><i class="fas fa-eye"></i></button>
                                        ${poll.status === 'active' 
                                            ? `<button class="btn-action btn-close" onclick="closePoll(${poll.id})" title="Close Poll"><i class="fas fa-lock"></i></button>` 
                                            : `<button class="btn-action btn-open" onclick="reopenPoll(${poll.id})" title="Reopen Poll"><i class="fas fa-unlock"></i></button>`}
                                        <button class="btn-action btn-delete" onclick="deletePoll(${poll.id})"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    $('#polls-table-body').html(html);
                    renderPagination(response.pagination);
                } else {
                    $('#polls-table-body').html('<tr><td colspan="6" class="empty-state"><i class="fas fa-poll" style="font-size: 32px; opacity: 0.5;"></i><br>No polls found</td></tr>');
                    $('#polls-pagination').html('');
                }
            }, 'json');
        }
        
        function renderPagination(p) {
            if (p.pages <= 1) { $('#polls-pagination').html(''); return; }
            let html = `<button class="page-btn" onclick="loadPolls(${p.page - 1})" ${p.page <= 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`;
            for (let i = 1; i <= p.pages; i++) {
                if (i === 1 || i === p.pages || (i >= p.page - 2 && i <= p.page + 2)) {
                    html += `<button class="page-btn ${i === p.page ? 'active' : ''}" onclick="loadPolls(${i})">${i}</button>`;
                }
            }
            html += `<button class="page-btn" onclick="loadPolls(${p.page + 1})" ${p.page >= p.pages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`;
            $('#polls-pagination').html(html);
        }
        
        function viewPoll(id) {
            $('#viewModalBody').html('<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 24px;"></i></div>');
            $('#viewModal').addClass('show');
            
            $.post('', { action: 'get_poll_details', poll_id: id }, function(response) {
                if (response.status) {
                    const poll = response.poll;
                    const options = response.options;
                    const totalVotes = options.reduce((sum, o) => sum + parseInt(o.votes || 0), 0);
                    
                    let html = `
                        <div class="poll-details">
                            <h3 style="margin-bottom: 15px; color: #f1f5f9;">${escapeHtml(poll.title)}</h3>
                            ${poll.description ? `<p style="color: #94a3b8; margin-bottom: 20px;">${escapeHtml(poll.description)}</p>` : ''}
                            
                            <div class="poll-meta">
                                <div class="poll-meta-item">
                                    <div class="poll-meta-label">Creator</div>
                                    <div class="poll-meta-value">${escapeHtml(poll.creator_name || 'Admin')}</div>
                                </div>
                                <div class="poll-meta-item">
                                    <div class="poll-meta-label">Total Votes</div>
                                    <div class="poll-meta-value" style="color: #fbbf24;">${totalVotes}</div>
                                </div>
                                <div class="poll-meta-item">
                                    <div class="poll-meta-label">Status</div>
                                    <div class="poll-meta-value">${poll.status === 'active' ? '🟢 Active' : '⚫ Closed'}</div>
                                </div>
                                <div class="poll-meta-item">
                                    <div class="poll-meta-label">Created</div>
                                    <div class="poll-meta-value">${formatDate(poll.created_on)}</div>
                                </div>
                            </div>
                            
                            <h4 style="margin-bottom: 15px; color: #e2e8f0;">Results</h4>
                            <div class="poll-options-list">
                    `;
                    
                    options.forEach(opt => {
                        const pct = totalVotes > 0 ? Math.round((opt.votes / totalVotes) * 100) : 0;
                        html += `
                            <div class="poll-option-item">
                                <span class="poll-option-text">${escapeHtml(opt.option_text)}</span>
                                <span class="poll-option-votes">${opt.votes} votes (${pct}%)</span>
                            </div>
                        `;
                    });
                    
                    html += '</div></div>';
                    
                    $('#viewModalTitle').text('Poll Details');
                    $('#viewModalBody').html(html);
                }
            }, 'json');
        }
        
        function showCreateModal() {
            $('#createModal').addClass('show');
            $('#createPollForm')[0].reset();
            $('#optionsList').html(`
                <div class="option-row"><input type="text" class="form-input" placeholder="Option 1" required></div>
                <div class="option-row"><input type="text" class="form-input" placeholder="Option 2" required></div>
            `);
        }
        
        function closeModal(id) {
            $('#' + id).removeClass('show');
        }
        
        function addOption() {
            const count = $('#optionsList .option-row').length + 1;
            if (count > 10) { showToast('Maximum 10 options allowed', 'error'); return; }
            $('#optionsList').append(`
                <div class="option-row">
                    <input type="text" class="form-input" placeholder="Option ${count}" required>
                    <button type="button" class="btn-remove-opt" onclick="$(this).parent().remove()"><i class="fas fa-times"></i></button>
                </div>
            `);
        }
        
        function createPoll(e) {
            e.preventDefault();
            const title = $('#pollTitle').val().trim();
            const description = $('#pollDescription').val().trim();
            const options = [];
            $('#optionsList .form-input').each(function() {
                const v = $(this).val().trim();
                if (v) options.push(v);
            });
            
            if (!title || options.length < 2) {
                showToast('Title and at least 2 options required', 'error');
                return;
            }
            
            $.post('', {
                action: 'create_poll',
                title: title,
                description: description,
                options: JSON.stringify(options)
            }, function(response) {
                showToast(response.message, response.status ? 'success' : 'error');
                if (response.status) {
                    closeModal('createModal');
                    loadPolls(1);
                    loadStats();
                }
            }, 'json');
        }
        
        function closePoll(id) {
            if (!confirm('Close this poll? Users will no longer be able to vote.')) return;
            $.post('', { action: 'close_poll', poll_id: id }, function(response) {
                showToast(response.message, response.status ? 'success' : 'error');
                if (response.status) { loadPolls(1); loadStats(); }
            }, 'json');
        }
        
        function reopenPoll(id) {
            if (!confirm('Reopen this poll?')) return;
            $.post('', { action: 'reopen_poll', poll_id: id }, function(response) {
                showToast(response.message, response.status ? 'success' : 'error');
                if (response.status) { loadPolls(1); loadStats(); }
            }, 'json');
        }
        
        function deletePoll(id) {
            if (!confirm('Are you sure you want to delete this poll? This cannot be undone.')) return;
            $.post('', { action: 'delete_poll', poll_id: id }, function(response) {
                showToast(response.message, response.status ? 'success' : 'error');
                if (response.status) { loadPolls(1); loadStats(); }
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
