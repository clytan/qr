<?php
// Include auth check
require_once('../components/auth_check.php');
require_once('../backend/dbconfig/connection.php');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'get_config':
            try {
                $result = $conn->query("SELECT * FROM reward_config ORDER BY id");
                $config = [];
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $config[$row['config_key']] = $row;
                    }
                }
                echo json_encode(['status' => true, 'data' => $config]);
            } catch (Exception $e) {
                echo json_encode(['status' => true, 'data' => []]);
            }
            exit();
            
        case 'update_config':
            try {
                $config_key = trim($_POST['config_key'] ?? '');
                $config_value = trim($_POST['config_value'] ?? '');
                
                if (empty($config_key)) {
                    echo json_encode(['status' => false, 'message' => 'Config key required']);
                    exit();
                }
                
                $stmt = $conn->prepare("UPDATE reward_config SET config_value = ?, updated_on = NOW() WHERE config_key = ?");
                if ($stmt) {
                    $stmt->bind_param('ss', $config_value, $config_key);
                    if ($stmt->execute()) {
                        echo json_encode(['status' => true, 'message' => 'Configuration updated']);
                    } else {
                        echo json_encode(['status' => false, 'message' => 'Failed to update']);
                    }
                } else {
                    echo json_encode(['status' => false, 'message' => 'Config table not available']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => 'Error updating config']);
            }
            exit();
            
        case 'get_draws':
            try {
                $page = intval($_POST['page'] ?? 1);
                $limit = 20;
                $offset = ($page - 1) * $limit;
                $date_filter = trim($_POST['date_filter'] ?? '');
                $community_filter = intval($_POST['community_filter'] ?? 0);
                
                $where = "WHERE 1=1";
                $params = [];
                $types = '';
                
                if (!empty($date_filter)) {
                    $where .= " AND rd.draw_date = ?";
                    $params[] = $date_filter;
                    $types .= 's';
                }
                
                if ($community_filter > 0) {
                    $where .= " AND rd.community_id = ?";
                    $params[] = $community_filter;
                    $types .= 'i';
                }
                
                // Get total
                $total = 0;
                $countSql = "SELECT COUNT(*) as total FROM reward_draws rd $where";
                if (!empty($params)) {
                    $countStmt = $conn->prepare($countSql);
                    if ($countStmt) {
                        $countStmt->bind_param($types, ...$params);
                        $countStmt->execute();
                        $total = $countStmt->get_result()->fetch_assoc()['total'];
                    }
                } else {
                    $countResult = $conn->query($countSql);
                    if ($countResult) {
                        $total = $countResult->fetch_assoc()['total'];
                    }
                }
                
                // Get draws
                $draws = [];
                $sql = "SELECT rd.*, c.name
                        FROM reward_draws rd
                        LEFT JOIN community c ON rd.community_id = c.id
                        $where
                        ORDER BY rd.draw_date DESC, rd.community_id
                        LIMIT $limit OFFSET $offset";
                
                if (!empty($params)) {
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param($types, ...$params);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()) {
                            $draws[] = $row;
                        }
                    }
                } else {
                    $result = $conn->query($sql);
                    if ($result) {
                        while ($row = $result->fetch_assoc()) {
                            $draws[] = $row;
                        }
                    }
                }
                
                echo json_encode([
                    'status' => true,
                    'data' => $draws,
                    'pagination' => [
                        'total' => $total,
                        'page' => $page,
                        'pages' => $total > 0 ? ceil($total / $limit) : 0
                    ],
                    'debug' => [
                        'total_found' => $total,
                        'draws_count' => count($draws),
                        'sql' => $countSql,
                        'mysql_error' => $conn->error
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'status' => true,
                    'data' => [],
                    'pagination' => ['total' => 0, 'page' => 1, 'pages' => 0],
                    'debug_error' => $e->getMessage()
                ]);
            }
            exit();
            
        case 'get_winners':
            try {
                $draw_id = intval($_POST['draw_id'] ?? 0);
                
                if ($draw_id <= 0) {
                    echo json_encode(['status' => false, 'message' => 'Draw ID required']);
                    exit();
                }
                
                $draw = null;
                $winners = [];
                
                // Get draw info
                $stmt = $conn->prepare("SELECT rd.*, c.name 
                                        FROM reward_draws rd 
                                        LEFT JOIN community c ON rd.community_id = c.id
                                        WHERE rd.id = ?");
                if ($stmt) {
                    $stmt->bind_param('i', $draw_id);
                    $stmt->execute();
                    $draw = $stmt->get_result()->fetch_assoc();
                }
                
                // Get winners
                $stmtWin = $conn->prepare("SELECT rw.*, u.user_full_name, u.user_email, u.user_qr_id, u.user_image_path
                                           FROM reward_winners rw
                                           JOIN user_user u ON rw.user_id = u.id
                                           WHERE rw.draw_id = ?
                                           ORDER BY rw.position");
                if ($stmtWin) {
                    $stmtWin->bind_param('i', $draw_id);
                    $stmtWin->execute();
                    $resultWin = $stmtWin->get_result();
                    while ($row = $resultWin->fetch_assoc()) {
                        $winners[] = $row;
                    }
                }
                
                echo json_encode(['status' => true, 'data' => ['draw' => $draw, 'winners' => $winners]]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => 'Error loading winners']);
            }
            exit();
            
        case 'get_communities':
            try {
                $result = $conn->query("SELECT id, name FROM community WHERE is_deleted = 0 ORDER BY name");
                $communities = [];
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $communities[] = ['id' => $row['id'], 'community_name' => $row['name']];
                    }
                }
                echo json_encode(['status' => true, 'data' => $communities]);
            } catch (Exception $e) {
                echo json_encode(['status' => true, 'data' => []]);
            }
            exit();
            
        case 'get_stats':
            try {
                // Total draws
                $totalDrawsResult = $conn->query("SELECT COUNT(*) as cnt FROM reward_draws");
                $totalDraws = $totalDrawsResult ? $totalDrawsResult->fetch_assoc()['cnt'] : 0;
                
                // Total winners all time
                $totalWinnersResult = $conn->query("SELECT COUNT(*) as cnt FROM reward_winners");
                $totalWinners = $totalWinnersResult ? $totalWinnersResult->fetch_assoc()['cnt'] : 0;
                
                // Today's completed draws
                $today = date('Y-m-d');
                $todayCompleted = 0;
                $todayCommunities = 0;
                
                $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM reward_draws WHERE draw_date = ? AND is_completed = 1");
                if ($stmt) {
                    $stmt->bind_param('s', $today);
                    $stmt->execute();
                    $todayCompleted = $stmt->get_result()->fetch_assoc()['cnt'];
                }
                
                // Communities with draws today
                $stmt2 = $conn->prepare("SELECT COUNT(DISTINCT community_id) as cnt FROM reward_draws WHERE draw_date = ?");
                if ($stmt2) {
                    $stmt2->bind_param('s', $today);
                    $stmt2->execute();
                    $todayCommunities = $stmt2->get_result()->fetch_assoc()['cnt'];
                }
                
                echo json_encode([
                    'status' => true,
                    'data' => [
                        'total_draws' => $totalDraws,
                        'total_winners' => $totalWinners,
                        'today_completed' => $todayCompleted,
                        'today_communities' => $todayCommunities
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'status' => true,
                    'data' => [
                        'total_draws' => 0,
                        'total_winners' => 0,
                        'today_completed' => 0,
                        'today_communities' => 0
                    ]
                ]);
            }
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Rewards - Admin Panel</title>
    <link rel="icon" href="../../assets/logo2.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html { background: #0f172a; min-height: 100vh; font-family: 'Inter', sans-serif; color: #e2e8f0; }
        
        .page-content { padding: 30px; }
        .page-header { margin-bottom: 25px; }
        .page-title { font-size: 28px; font-weight: 700; color: #f1f5f9; margin-bottom: 5px; }
        .page-subtitle { color: #64748b; font-size: 14px; }
        
        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 14px; padding: 20px; }
        .stat-icon { width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 15px; }
        .stat-icon.gold { background: rgba(251, 191, 36, 0.15); color: #fbbf24; }
        .stat-icon.green { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
        .stat-icon.blue { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .stat-icon.purple { background: rgba(168, 85, 247, 0.15); color: #a855f7; }
        .stat-value { font-size: 28px; font-weight: 700; color: #f1f5f9; }
        .stat-label { font-size: 13px; color: #64748b; margin-top: 5px; }
        
        /* Tabs */
        .tabs { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .tab-btn { padding: 10px 20px; border-radius: 10px; border: 1px solid rgba(255, 255, 255, 0.1); background: transparent; color: #94a3b8; font-size: 14px; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 8px; }
        .tab-btn:hover { background: rgba(255, 255, 255, 0.05); }
        .tab-btn.active { background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%); color: white; border-color: transparent; }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        /* Config Section */
        .config-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .config-card { background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 14px; padding: 20px; }
        .config-title { font-size: 14px; font-weight: 600; color: #f1f5f9; margin-bottom: 5px; }
        .config-desc { font-size: 12px; color: #64748b; margin-bottom: 15px; }
        .config-input-group { display: flex; gap: 10px; }
        .config-input { flex: 1; padding: 10px 14px; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #e2e8f0; font-size: 14px; }
        .config-input:focus { outline: none; border-color: #e67753; }
        .btn-save { padding: 10px 16px; background: rgba(34, 197, 94, 0.2); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 8px; color: #4ade80; cursor: pointer; font-size: 13px; }
        .btn-save:hover { background: rgba(34, 197, 94, 0.3); }
        
        /* Filters */
        .filters { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-input { padding: 10px 14px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #e2e8f0; font-size: 14px; }
        .filter-input:focus { outline: none; border-color: #e67753; }
        
        /* Table */
        .table-container { background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 14px; overflow: hidden; }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th { background: rgba(15, 23, 42, 0.5); padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #94a3b8; text-transform: uppercase; }
        td { padding: 14px 16px; border-top: 1px solid rgba(255, 255, 255, 0.05); font-size: 13px; }
        tr:hover td { background: rgba(255, 255, 255, 0.02); }
        
        .badge { display: inline-flex; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
        .badge.completed { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
        .badge.pending { background: rgba(234, 179, 8, 0.15); color: #fbbf24; }
        
        .btn-view { padding: 6px 12px; background: rgba(59, 130, 246, 0.15); border: none; border-radius: 6px; color: #60a5fa; font-size: 12px; cursor: pointer; transition: all 0.2s ease; }
        .btn-view:hover { background: rgba(59, 130, 246, 0.25); }
        
        /* Modal */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); display: none; align-items: center; justify-content: center; z-index: 2000; padding: 20px; }
        .modal-overlay.show { display: flex; }
        .modal { background: #1e293b; border-radius: 16px; width: 100%; max-width: 700px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
        .modal-title { font-size: 18px; font-weight: 600; color: #f1f5f9; }
        .modal-close { width: 36px; height: 36px; border-radius: 8px; border: none; background: rgba(255, 255, 255, 0.05); color: #94a3b8; cursor: pointer; font-size: 18px; }
        .modal-body { padding: 20px; overflow-y: auto; flex: 1; }
        
        .draw-info { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
        .draw-info-item { background: rgba(15, 23, 42, 0.5); padding: 15px; border-radius: 10px; text-align: center; }
        .draw-info-value { font-size: 20px; font-weight: 700; color: #f1f5f9; }
        .draw-info-label { font-size: 11px; color: #64748b; margin-top: 3px; }
        
        .winners-list { display: flex; flex-direction: column; gap: 10px; }
        .winner-item { display: flex; align-items: center; gap: 15px; background: rgba(251, 191, 36, 0.05); border: 1px solid rgba(251, 191, 36, 0.1); border-radius: 10px; padding: 12px 15px; }
        .winner-position { width: 35px; height: 35px; border-radius: 50%; background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #000; font-weight: 700; font-size: 14px; display: flex; align-items: center; justify-content: center; }
        .winner-avatar { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; overflow: hidden; }
        .winner-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .winner-info { flex: 1; }
        .winner-name { font-weight: 600; color: #f1f5f9; font-size: 14px; }
        .winner-qr { font-size: 12px; color: #60a5fa; }
        .winner-time { font-size: 11px; color: #64748b; }
        
        .pagination { display: flex; justify-content: center; gap: 8px; padding: 20px; }
        .page-btn { padding: 8px 14px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #94a3b8; cursor: pointer; }
        .page-btn.active, .page-btn:hover { background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%); color: white; border-color: transparent; }
        .page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        
        .toast { position: fixed; bottom: 30px; right: 30px; padding: 16px 24px; border-radius: 10px; font-size: 14px; font-weight: 500; z-index: 3000; transform: translateX(150%); transition: transform 0.3s ease; }
        .toast.show { transform: translateX(0); }
        .toast.success { background: rgba(34, 197, 94, 0.9); color: white; }
        .toast.error { background: rgba(239, 68, 68, 0.9); color: white; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
        
        @media (max-width: 768px) {
            .page-content { padding: 20px 15px; }
            .draw-info { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include('../components/sidebar.php'); ?>
    
    <main class="admin-main">
        <div class="page-content">
            <div class="page-header">
                <h1 class="page-title">🎰 Rewards Management</h1>
                <p class="page-subtitle">Monitor daily draws, winners, and configure reward settings</p>
            </div>
            
            <!-- Stats -->
            <div class="stats-grid" id="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="fas fa-trophy"></i></div>
                    <div class="stat-value" id="stat-total-draws">-</div>
                    <div class="stat-label">Total Draws</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-medal"></i></div>
                    <div class="stat-value" id="stat-total-winners">-</div>
                    <div class="stat-label">Total Winners</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-value" id="stat-today-completed">-</div>
                    <div class="stat-label">Today's Completed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-users"></i></div>
                    <div class="stat-value" id="stat-today-communities">-</div>
                    <div class="stat-label">Communities Today</div>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="tabs">
                <button class="tab-btn active" onclick="showTab('draws')"><i class="fas fa-list"></i> Draws History</button>
                <button class="tab-btn" onclick="showTab('config')"><i class="fas fa-cog"></i> Configuration</button>
            </div>
            
            <!-- Draws Tab -->
            <div class="tab-content active" id="tab-draws">
                <div class="filters">
                    <input type="date" class="filter-input" id="dateFilter" onchange="loadDraws(1)">
                    <select class="filter-input" id="communityFilter" onchange="loadDraws(1)">
                        <option value="">All Communities</option>
                    </select>
                </div>
                
                <div class="table-container">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Community</th>
                                    <th>Participants</th>
                                    <th>Winners</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="draws-table-body">
                                <tr><td colspan="6" class="empty-state">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination" id="draws-pagination"></div>
                </div>
            </div>
            
            <!-- Config Tab -->
            <div class="tab-content" id="tab-config">
                <div class="config-grid" id="config-grid">
                    <div class="config-card">
                        <div class="config-title">Spin Start Time</div>
                        <div class="config-desc">When the spin period begins each day (HH:MM:SS format, e.g., 00:00:00)</div>
                        <div class="config-input-group">
                            <input type="text" class="config-input" id="config-spin_start_time" placeholder="00:00:00">
                            <button class="btn-save" onclick="saveConfig('spin_start_time')"><i class="fas fa-save"></i></button>
                        </div>
                    </div>
                    <div class="config-card">
                        <div class="config-title">Spin End Time</div>
                        <div class="config-desc">When the spin period ends and draw happens (HH:MM:SS format, e.g., 21:00:00)</div>
                        <div class="config-input-group">
                            <input type="text" class="config-input" id="config-spin_end_time" placeholder="21:00:00">
                            <button class="btn-save" onclick="saveConfig('spin_end_time')"><i class="fas fa-save"></i></button>
                        </div>
                    </div>
                    <div class="config-card">
                        <div class="config-title">Max Winners Per Draw</div>
                        <div class="config-desc">Maximum number of winners selected in each daily draw</div>
                        <div class="config-input-group">
                            <input type="number" class="config-input" id="config-max_winners_per_draw" placeholder="30">
                            <button class="btn-save" onclick="saveConfig('max_winners_per_draw')"><i class="fas fa-save"></i></button>
                        </div>
                    </div>
                    <div class="config-card">
                        <div class="config-title">Spin Duration (seconds)</div>
                        <div class="config-desc">How long the spinner animation runs</div>
                        <div class="config-input-group">
                            <input type="number" class="config-input" id="config-spin_duration_seconds" placeholder="30">
                            <button class="btn-save" onclick="saveConfig('spin_duration_seconds')"><i class="fas fa-save"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Winners Modal -->
    <div class="modal-overlay" id="winnersModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-title">Draw Winners</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modal-body">
                Loading...
            </div>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            loadStats();
            loadConfig();
            loadCommunities();
            loadDraws(1);
        });
        
        function loadStats() {
            $.post('', { action: 'get_stats' }, function(response) {
                if (response.status) {
                    $('#stat-total-draws').text(response.data.total_draws);
                    $('#stat-total-winners').text(response.data.total_winners);
                    $('#stat-today-completed').text(response.data.today_completed);
                    $('#stat-today-communities').text(response.data.today_communities);
                }
            }, 'json');
        }
        
        function loadConfig() {
            $.post('', { action: 'get_config' }, function(response) {
                if (response.status) {
                    Object.keys(response.data).forEach(key => {
                        $(`#config-${key}`).val(response.data[key].config_value);
                    });
                }
            }, 'json');
        }
        
        function saveConfig(key) {
            const value = $(`#config-${key}`).val();
            $.post('', { action: 'update_config', config_key: key, config_value: value }, function(response) {
                showToast(response.message, response.status ? 'success' : 'error');
            }, 'json');
        }
        
        function loadCommunities() {
            $.post('', { action: 'get_communities' }, function(response) {
                console.log('get_communities response:', response);
                if (response.status) {
                    let options = '<option value="">All Communities</option>';
                    response.data.forEach(c => {
                        options += `<option value="${c.id}">${escapeHtml(c.community_name)}</option>`;
                    });
                    $('#communityFilter').html(options);
                }
            }, 'json');
        }
        
        function loadDraws(page) {
            const dateFilter = $('#dateFilter').val();
            const communityFilter = $('#communityFilter').val();
            
            $.post('', { 
                action: 'get_draws', 
                page: page, 
                date_filter: dateFilter,
                community_filter: communityFilter
            }, function(response) {
                console.log('get_draws response:', response);
                if (response.status && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(draw => {
                        const status = draw.is_completed 
                            ? '<span class="badge completed"><i class="fas fa-check"></i> Completed</span>'
                            : '<span class="badge pending"><i class="fas fa-clock"></i> Pending</span>';
                        
                        html += `
                            <tr>
                                <td><strong>${formatDate(draw.draw_date)}</strong></td>
                                <td>${escapeHtml(draw.name || 'Community #' + draw.community_id)}</td>
                                <td>${draw.total_participants || 0}</td>
                                <td><span style="color: #fbbf24; font-weight: 600;">${draw.total_winners || 0}</span></td>
                                <td>${status}</td>
                                <td>
                                    ${draw.is_completed ? `<button class="btn-view" onclick="viewWinners(${draw.id})"><i class="fas fa-trophy"></i> View Winners</button>` : '-'}
                                </td>
                            </tr>
                        `;
                    });
                    $('#draws-table-body').html(html);
                    renderPagination(response.pagination);
                } else {
                    $('#draws-table-body').html('<tr><td colspan="6" class="empty-state"><i class="fas fa-calendar-times" style="font-size: 32px; opacity: 0.5;"></i><br>No draws found</td></tr>');
                    $('#draws-pagination').html('');
                }
            }, 'json');
        }
        
        function renderPagination(pagination) {
            if (pagination.pages <= 1) {
                $('#draws-pagination').html('');
                return;
            }
            let html = '';
            html += `<button class="page-btn" onclick="loadDraws(${pagination.page - 1})" ${pagination.page <= 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`;
            for (let i = 1; i <= pagination.pages; i++) {
                if (i === 1 || i === pagination.pages || (i >= pagination.page - 2 && i <= pagination.page + 2)) {
                    html += `<button class="page-btn ${i === pagination.page ? 'active' : ''}" onclick="loadDraws(${i})">${i}</button>`;
                }
            }
            html += `<button class="page-btn" onclick="loadDraws(${pagination.page + 1})" ${pagination.page >= pagination.pages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`;
            $('#draws-pagination').html(html);
        }
        
        function viewWinners(drawId) {
            $('#modal-body').html('<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 24px;"></i></div>');
            $('#winnersModal').addClass('show');
            
            $.post('', { action: 'get_winners', draw_id: drawId }, function(response) {
                if (response.status) {
                    const draw = response.data.draw;
                    const winners = response.data.winners;
                    
                    let html = `
                        <div class="draw-info">
                            <div class="draw-info-item">
                                <div class="draw-info-value">${formatDate(draw.draw_date)}</div>
                                <div class="draw-info-label">Draw Date</div>
                            </div>
                            <div class="draw-info-item">
                                <div class="draw-info-value">${escapeHtml(draw.name || 'Community #' + draw.community_id)}</div>
                                <div class="draw-info-label">Community</div>
                            </div>
                            <div class="draw-info-item">
                                <div class="draw-info-value" style="color: #fbbf24;">${winners.length}</div>
                                <div class="draw-info-label">Winners</div>
                            </div>
                        </div>
                        <div class="winners-list">
                    `;
                    
                    if (winners.length > 0) {
                        winners.forEach(w => {
                            const avatar = w.user_image_path 
                                ? `<img src="../../../user/src/${w.user_image_path}" alt="">` 
                                : (w.user_full_name ? w.user_full_name.charAt(0).toUpperCase() : '?');
                            
                            html += `
                                <div class="winner-item">
                                    <div class="winner-position">${w.position}</div>
                                    <div class="winner-avatar">${avatar}</div>
                                    <div class="winner-info">
                                        <div class="winner-name">${escapeHtml(w.user_full_name || 'No Name')}</div>
                                        <div class="winner-qr">${escapeHtml(w.user_qr_id)}</div>
                                    </div>
                                    <div class="winner-time">${formatDateTime(w.won_at)}</div>
                                </div>
                            `;
                        });
                    } else {
                        html += '<div class="empty-state">No winners recorded</div>';
                    }
                    
                    html += '</div>';
                    
                    $('#modal-title').text(`Draw Winners - ${formatDate(draw.draw_date)}`);
                    $('#modal-body').html(html);
                }
            }, 'json');
        }
        
        function closeModal() {
            $('#winnersModal').removeClass('show');
        }
        
        function showTab(tab) {
            $('.tab-btn').removeClass('active');
            $(`.tab-btn:contains('${tab === 'draws' ? 'Draws' : 'Configuration'}')`).addClass('active');
            $('.tab-content').removeClass('active');
            $(`#tab-${tab}`).addClass('active');
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
        
        function formatDateTime(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleString('en-IN', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
        }
    </script>
</body>
</html>
