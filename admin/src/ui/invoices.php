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
            $where = "WHERE i.is_deleted = 0";
            $params = [];
            $types = '';
            
            if (!empty($search)) {
                $where .= " AND (u.user_full_name LIKE ? OR u.user_email LIKE ? OR i.invoice_number LIKE ?)";
                $searchTerm = "%$search%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
                $types .= 'sss';
            }
            
            // Type filter
            $type_filter = isset($_POST['type_filter']) ? trim($_POST['type_filter']) : '';
            if (!empty($type_filter)) {
                $where .= " AND i.invoice_type = ?";
                $params[] = $type_filter;
                $types .= 's';
            }
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM user_invoice i 
                         LEFT JOIN user_user u ON i.user_id = u.id $where";
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
            
            // Get invoices
            $sql = "SELECT i.*, u.user_full_name, u.user_email, u.user_phone, u.user_qr_id, u.user_tag
                    FROM user_invoice i
                    LEFT JOIN user_user u ON i.user_id = u.id
                    $where 
                    ORDER BY i.created_on DESC
                    LIMIT $limit OFFSET $offset";
            
            if (!empty($params)) {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $conn->query($sql);
            }
            
            $invoices = [];
            while ($row = $result->fetch_assoc()) {
                $invoices[] = $row;
            }
            
            // Get stats
            $statsResult = @$conn->query("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN invoice_type = 'registration' THEN 1 ELSE 0 END) as registration_count,
                SUM(CASE WHEN invoice_type = 'renewal' THEN 1 ELSE 0 END) as renewal_count,
                SUM(CASE WHEN invoice_type = 'poll' THEN 1 ELSE 0 END) as poll_count,
                SUM(total_amount) as total_revenue,
                SUM(gst_total) as total_gst
                FROM user_invoice WHERE is_deleted = 0 AND status = 'Paid'");
            $stats = $statsResult ? $statsResult->fetch_assoc() : ['total' => 0, 'registration_count' => 0, 'renewal_count' => 0, 'poll_count' => 0, 'total_revenue' => 0, 'total_gst' => 0];
            
            echo json_encode([
                'status' => true, 
                'data' => $invoices,
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
    <title>Invoices - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; }
        .main-content { margin-left: 260px; padding: 2rem; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 1rem; padding-top: 70px; } }
        
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-size: 1.75rem; color: #f8fafc; margin-bottom: 0.5rem; }
        .page-header p { color: #94a3b8; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); border-radius: 12px; padding: 1.25rem; border: 1px solid rgba(255,255,255,0.1); }
        .stat-card h3 { color: #94a3b8; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; }
        .stat-card .value { font-size: 1.5rem; font-weight: 700; color: #f8fafc; }
        .stat-card.revenue .value { color: #10b981; }
        .stat-card.registration .value { color: #3b82f6; }
        .stat-card.renewal .value { color: #f59e0b; }
        .stat-card.gst .value { color: #8b5cf6; }
        
        .toolbar { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center; }
        .search-box { flex: 1; min-width: 200px; }
        .search-box input { width: 100%; padding: 0.75rem 1rem; border: 1px solid #475569; border-radius: 8px; background: #1e293b; color: #e2e8f0; font-size: 0.875rem; }
        .search-box input:focus { outline: none; border-color: #3b82f6; }
        
        .filter-select { padding: 0.75rem 1rem; border: 1px solid #475569; border-radius: 8px; background: #1e293b; color: #e2e8f0; font-size: 0.875rem; cursor: pointer; }
        
        .table-container { background: #1e293b; border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #334155; }
        th { background: #334155; color: #94a3b8; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }
        td { font-size: 0.875rem; }
        tr:hover { background: rgba(255,255,255,0.03); }
        
        .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .badge.paid { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .badge.pending { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .badge.registration { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .badge.renewal { background: rgba(139, 92, 246, 0.2); color: #a78bfa; }
        .badge.poll { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .stat-card.poll .value { color: #34d399; }
        
        .user-info { display: flex; flex-direction: column; }
        .user-name { font-weight: 600; color: #f8fafc; }
        .user-email { color: #64748b; font-size: 0.75rem; }
        
        .amount { font-weight: 600; color: #10b981; }
        .gst { color: #8b5cf6; font-size: 0.75rem; }
        
        .pagination { display: flex; gap: 0.5rem; justify-content: center; padding: 1rem; }
        .pagination button { padding: 0.5rem 1rem; border: 1px solid #475569; border-radius: 6px; background: #1e293b; color: #e2e8f0; cursor: pointer; }
        .pagination button:hover { background: #334155; }
        .pagination button.active { background: #3b82f6; border-color: #3b82f6; }
        
        .empty-state { text-align: center; padding: 3rem; color: #64748b; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; }
    </style>
</head>
<body>
    <?php include('../components/sidebar.php'); ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-file-invoice"></i> Invoices</h1>
            <p>View all registration and renewal invoices</p>
        </div>
        
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <h3>Total Invoices</h3>
                <div class="value" id="stat-total">0</div>
            </div>
            <div class="stat-card registration">
                <h3>Registrations</h3>
                <div class="value" id="stat-reg">0</div>
            </div>
            <div class="stat-card renewal">
                <h3>Renewals</h3>
                <div class="value" id="stat-renew">0</div>
            </div>
            <div class="stat-card poll">
                <h3>Polls</h3>
                <div class="value" id="stat-poll">0</div>
            </div>
            <div class="stat-card revenue">
                <h3>Total Revenue</h3>
                <div class="value" id="stat-revenue">₹0</div>
            </div>
            <div class="stat-card gst">
                <h3>GST Collected</h3>
                <div class="value" id="stat-gst">₹0</div>
            </div>
        </div>
        
        <div class="toolbar">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search by name, email, or invoice number...">
            </div>
            <select class="filter-select" id="typeFilter">
                <option value="">All Types</option>
                <option value="registration">Registration</option>
                <option value="renewal">Renewal</option>
                <option value="poll">Poll</option>
            </select>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>GST</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody id="invoicesTable">
                    <tr><td colspan="8" class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Loading...</p></td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="pagination" id="pagination"></div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentPage = 1;
        
        function loadInvoices(page = 1) {
            currentPage = page;
            const search = $('#searchInput').val().trim();
            const typeFilter = $('#typeFilter').val();
            
            $.post('', { action: 'get_all', page: page, search: search, type_filter: typeFilter }, function(response) {
                if (response.status) {
                    renderTable(response.data);
                    renderPagination(response.pagination);
                    renderStats(response.stats);
                }
            }, 'json');
        }
        
        function renderTable(data) {
            if (data.length === 0) {
                $('#invoicesTable').html('<tr><td colspan="8" class="empty-state"><i class="fas fa-inbox"></i><p>No invoices found</p></td></tr>');
                return;
            }
            
            let html = '';
            data.forEach(inv => {
                const typeClass = inv.invoice_type || 'registration';
                const statusClass = (inv.status || 'pending').toLowerCase();
                html += `<tr>
                    <td><strong>${inv.invoice_number || 'N/A'}</strong></td>
                    <td>
                        <div class="user-info">
                            <span class="user-name">${inv.user_full_name || 'N/A'}</span>
                            <span class="user-email">${inv.user_email || ''}</span>
                        </div>
                    </td>
                    <td><span class="badge ${typeClass}">${inv.invoice_type || 'registration'}</span></td>
                    <td class="amount">₹${parseFloat(inv.amount || 0).toLocaleString()}</td>
                    <td class="gst">₹${parseFloat(inv.gst_total || 0).toLocaleString()}</td>
                    <td class="amount">₹${parseFloat(inv.total_amount || 0).toLocaleString()}</td>
                    <td><span class="badge ${statusClass}">${inv.status || 'Pending'}</span></td>
                    <td>${formatDate(inv.created_on)}</td>
                </tr>`;
            });
            $('#invoicesTable').html(html);
        }
        
        function renderStats(stats) {
            $('#stat-total').text(stats.total || 0);
            $('#stat-reg').text(stats.registration_count || 0);
            $('#stat-renew').text(stats.renewal_count || 0);
            $('#stat-poll').text(stats.poll_count || 0);
            $('#stat-revenue').text('₹' + parseFloat(stats.total_revenue || 0).toLocaleString());
            $('#stat-gst').text('₹' + parseFloat(stats.total_gst || 0).toLocaleString());
        }
        
        function renderPagination(pagination) {
            let html = '';
            for (let i = 1; i <= pagination.pages; i++) {
                html += `<button onclick="loadInvoices(${i})" class="${i === pagination.page ? 'active' : ''}">${i}</button>`;
            }
            $('#pagination').html(html);
        }
        
        function formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        }
        
        $(document).ready(function() {
            loadInvoices();
            
            let searchTimer;
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => loadInvoices(1), 300);
            });
            
            $('#typeFilter').on('change', function() {
                loadInvoices(1);
            });
        });
    </script>
</body>
</html>
