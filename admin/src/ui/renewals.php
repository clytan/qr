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
            $where = "WHERE r.is_deleted = 0";
            $params = [];
            $types = '';
            
            if (!empty($search)) {
                $where .= " AND (u.user_full_name LIKE ? OR u.user_email LIKE ? OR r.order_id LIKE ?)";
                $searchTerm = "%$search%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
                $types .= 'sss';
            }
            
            // Status filter
            $status_filter = isset($_POST['status_filter']) ? trim($_POST['status_filter']) : '';
            if (!empty($status_filter)) {
                $where .= " AND r.payment_status = ?";
                $params[] = $status_filter;
                $types .= 's';
            }
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM user_renewal r 
                         LEFT JOIN user_user u ON r.user_id = u.id $where";
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
            
            // Get renewals
            $sql = "SELECT r.*, u.user_full_name, u.user_email, u.user_phone, u.user_qr_id
                    FROM user_renewal r
                    LEFT JOIN user_user u ON r.user_id = u.id
                    $where 
                    ORDER BY r.created_on DESC
                    LIMIT $limit OFFSET $offset";
            
            if (!empty($params)) {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $conn->query($sql);
            }
            
            $renewals = [];
            while ($row = $result->fetch_assoc()) {
                $renewals[] = $row;
            }
            
            // Get stats
            $statsResult = @$conn->query("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) as total_revenue
                FROM user_renewal WHERE is_deleted = 0");
            $stats = $statsResult ? $statsResult->fetch_assoc() : ['total' => 0, 'paid_count' => 0, 'pending_count' => 0, 'total_revenue' => 0];
            
            echo json_encode([
                'status' => true, 
                'data' => $renewals,
                'stats' => $stats,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'pages' => ceil($total / $limit),
                    'limit' => $limit
                ]
            ]);
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renewals - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; }
        .main-content { margin-left: 260px; padding: 2rem; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 1rem; padding-top: 70px; } }
        
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-size: 1.75rem; color: #f8fafc; margin-bottom: 0.5rem; }
        .page-header p { color: #94a3b8; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); border-radius: 12px; padding: 1.25rem; border: 1px solid rgba(255,255,255,0.1); }
        .stat-card h3 { color: #94a3b8; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; }
        .stat-card .value { font-size: 1.75rem; font-weight: 700; color: #f8fafc; }
        .stat-card.revenue .value { color: #10b981; }
        .stat-card.paid .value { color: #3b82f6; }
        .stat-card.pending .value { color: #f59e0b; }
        
        .toolbar { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center; }
        .search-box { flex: 1; min-width: 200px; }
        .search-box input { width: 100%; padding: 0.75rem 1rem; border: 1px solid #475569; border-radius: 8px; background: #1e293b; color: #e2e8f0; font-size: 0.875rem; }
        .search-box input:focus { outline: none; border-color: #3b82f6; }
        
        .filter-select { padding: 0.75rem 1rem; border: 1px solid #475569; border-radius: 8px; background: #1e293b; color: #e2e8f0; font-size: 0.875rem; cursor: pointer; }
        
        .table-container { background: #1e293b; border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #334155; }
        th { background: #334155; color: #94a3b8; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }
        td { font-size: 0.875rem; }
        tr:hover { background: rgba(255,255,255,0.03); }
        
        .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .badge.paid { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .badge.pending { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .badge.failed { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        
        .user-info { display: flex; flex-direction: column; }
        .user-name { font-weight: 600; color: #f8fafc; }
        .user-email { color: #64748b; font-size: 0.75rem; }
        
        .tier-badge { padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; }
        .tier-badge.gold { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #1f2937; }
        .tier-badge.silver { background: linear-gradient(135deg, #9ca3af, #6b7280); color: #1f2937; }
        .tier-badge.normal { background: linear-gradient(135deg, #60a5fa, #3b82f6); color: white; }
        
        .pagination { display: flex; gap: 0.5rem; justify-content: center; padding: 1rem; }
        .pagination button { padding: 0.5rem 1rem; border: 1px solid #475569; border-radius: 6px; background: #1e293b; color: #e2e8f0; cursor: pointer; }
        .pagination button:hover { background: #334155; }
        .pagination button.active { background: #3b82f6; border-color: #3b82f6; }
        .pagination button:disabled { opacity: 0.5; cursor: not-allowed; }
        
        .empty-state { text-align: center; padding: 3rem; color: #64748b; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; }
    </style>
</head>
<body>
    <?php include('../components/sidebar.php'); ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-sync-alt"></i> Subscription Renewals</h1>
            <p>Manage subscription renewal transactions</p>
        </div>
        
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <h3>Total Renewals</h3>
                <div class="value" id="stat-total">0</div>
            </div>
            <div class="stat-card paid">
                <h3>Paid</h3>
                <div class="value" id="stat-paid">0</div>
            </div>
            <div class="stat-card pending">
                <h3>Pending</h3>
                <div class="value" id="stat-pending">0</div>
            </div>
            <div class="stat-card revenue">
                <h3>Total Revenue</h3>
                <div class="value" id="stat-revenue">₹0</div>
            </div>
        </div>
        
        <div class="toolbar">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search by name, email, or order ID...">
            </div>
            <select class="filter-select" id="statusFilter">
                <option value="">All Status</option>
                <option value="paid">Paid</option>
                <option value="pending">Pending</option>
                <option value="failed">Failed</option>
            </select>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Tier</th>
                        <th>Amount</th>
                        <th>Old Expiry</th>
                        <th>New Expiry</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody id="renewalsTable">
                    <tr><td colspan="7" class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Loading...</p></td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="pagination" id="pagination"></div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentPage = 1;
        
        function loadRenewals(page = 1) {
            currentPage = page;
            const search = $('#searchInput').val().trim();
            const statusFilter = $('#statusFilter').val();
            
            $.post('', { action: 'get_all', page: page, search: search, status_filter: statusFilter }, function(response) {
                if (response.status) {
                    renderTable(response.data);
                    renderPagination(response.pagination);
                    renderStats(response.stats);
                }
            }, 'json');
        }
        
        function renderTable(data) {
            if (data.length === 0) {
                $('#renewalsTable').html('<tr><td colspan="7" class="empty-state"><i class="fas fa-inbox"></i><p>No renewals found</p></td></tr>');
                return;
            }
            
            let html = '';
            data.forEach(r => {
                const tierClass = (r.tier || 'normal').toLowerCase();
                const statusClass = r.payment_status || 'pending';
                html += `<tr>
                    <td>
                        <div class="user-info">
                            <span class="user-name">${r.user_full_name || 'N/A'}</span>
                            <span class="user-email">${r.user_email || ''}</span>
                        </div>
                    </td>
                    <td><span class="tier-badge ${tierClass}">${r.tier || 'Normal'}</span></td>
                    <td>₹${parseFloat(r.amount || 0).toLocaleString()}</td>
                    <td>${formatDate(r.old_expiry_date)}</td>
                    <td>${formatDate(r.new_expiry_date)}</td>
                    <td><span class="badge ${statusClass}">${r.payment_status || 'unknown'}</span></td>
                    <td>${formatDate(r.created_on)}</td>
                </tr>`;
            });
            $('#renewalsTable').html(html);
        }
        
        function renderStats(stats) {
            $('#stat-total').text(stats.total || 0);
            $('#stat-paid').text(stats.paid_count || 0);
            $('#stat-pending').text(stats.pending_count || 0);
            $('#stat-revenue').text('₹' + parseFloat(stats.total_revenue || 0).toLocaleString());
        }
        
        function renderPagination(pagination) {
            let html = '';
            for (let i = 1; i <= pagination.pages; i++) {
                html += `<button onclick="loadRenewals(${i})" class="${i === pagination.page ? 'active' : ''}">${i}</button>`;
            }
            $('#pagination').html(html);
        }
        
        function formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        }
        
        $(document).ready(function() {
            loadRenewals();
            
            let searchTimer;
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => loadRenewals(1), 300);
            });
            
            $('#statusFilter').on('change', function() {
                loadRenewals(1);
            });
        });
    </script>
</body>
</html>
