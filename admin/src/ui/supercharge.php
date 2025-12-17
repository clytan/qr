<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../ui/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Super Charge Requests - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --accent: #e67753;
            --accent-gradient: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
        }
        
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
        }

        /* Layout Override for sidebar */
        .admin-main { margin-left: 260px; transition: margin-left 0.3s ease; }
        .dashboard-content { padding: 30px; }
        @media (max-width: 768px) { .admin-main { margin-left: 0; padding-top: 60px; } }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .header h1 {
            font-size: 1.75rem;
            font-weight: 700;
        }
        
        .header h1 i {
            margin-right: 10px;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: var(--bg-secondary);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--bg-tertiary);
        }
        
        .stat-card.pending { border-left: 4px solid var(--warning); }
        .stat-card.approved { border-left: 4px solid var(--success); }
        .stat-card.rejected { border-left: 4px solid var(--danger); }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        /* Filters */
        .filters {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 10px 20px;
            background: var(--bg-secondary);
            border: 1px solid var(--bg-tertiary);
            border-radius: 8px;
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: var(--accent);
            border-color: var(--accent);
        }
        
        /* Table */
        .table-container {
            background: var(--bg-secondary);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--bg-tertiary);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--bg-tertiary);
        }
        
        th {
            background: var(--bg-tertiary);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
        }
        
        tr:hover {
            background: rgba(255,255,255,0.02);
        }
        
        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: rgba(245, 158, 11, 0.15); color: var(--warning); }
        .status-approved { background: rgba(16, 185, 129, 0.15); color: var(--success); }
        .status-rejected { background: rgba(239, 68, 68, 0.15); color: var(--danger); }
        
        /* Action buttons */
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            margin: 2px;
            transition: all 0.2s;
        }
        
        .btn-approve {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success);
            border: 1px solid var(--success);
        }
        
        .btn-approve:hover {
            background: var(--success);
            color: white;
        }
        
        .btn-reject {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            border: 1px solid var(--danger);
        }
        
        .btn-reject:hover {
            background: var(--danger);
            color: white;
        }
        
        /* Link style */
        .req-link {
            color: var(--info);
            text-decoration: none;
        }
        .req-link:hover { text-decoration: underline; }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay.active { display: flex; }
        
        .modal-content {
            background: var(--bg-secondary);
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            border: 1px solid var(--bg-tertiary);
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--bg-tertiary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 { margin: 0; }
        
        .modal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .modal-body { padding: 20px; }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 14px;
            background: var(--bg-primary);
            border: 1px solid var(--bg-tertiary);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--accent);
        }
        
        .btn-primary {
            width: 100%;
            padding: 12px;
            background: var(--accent-gradient);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }
        
        .btn-primary:hover { opacity: 0.9; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .table-container { overflow-x: auto; }
            table { min-width: 800px; }
        }
    </style>
</head>

<body id="page-top">
    <?php include('../components/sidebar.php'); ?>
    
    <main class="admin-main">
        <div class="dashboard-content">
            <div class="container">
                <div class="header">
                    <h1><i class="fas fa-bolt"></i> Super Charge Requests</h1>
                </div>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card pending">
                        <div class="stat-value" id="stat-pending">0</div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                    <div class="stat-card approved">
                        <div class="stat-value" id="stat-approved">0</div>
                        <div class="stat-label">Approved</div>
                    </div>
                    <div class="stat-card rejected">
                        <div class="stat-value" id="stat-rejected">0</div>
                        <div class="stat-label">Rejected</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters">
                    <button class="filter-btn active" data-status="all">All</button>
                    <button class="filter-btn" data-status="pending">Pending</button>
                    <button class="filter-btn" data-status="approved">Approved</button>
                    <button class="filter-btn" data-status="rejected">Rejected</button>
                </div>

                <!-- Table -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Link</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="requestsList">
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <p>Loading...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Reject Modal -->
    <div class="modal-overlay" id="rejectModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reject Request</h3>
                <button class="modal-close" onclick="closeRejectModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rejectRequestId">
                <div class="form-group">
                    <label>Reason for Rejection</label>
                    <textarea class="form-control" id="rejectReason" rows="3" placeholder="Enter reason..."></textarea>
                </div>
                <button class="btn-primary" style="background: var(--danger);" onclick="submitReject()">Reject Request</button>
            </div>
        </div>
    </div>

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        let currentFilter = 'all';
        let requestsMap = {};

        $(document).ready(function() {
            loadRequests();

            $('.filter-btn').click(function() {
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');
                currentFilter = $(this).data('status');
                loadRequests();
            });
        });

        function loadRequests() {
            $.ajax({
                url: '../backend/supercharge/get_requests.php',
                type: 'GET',
                data: { status: currentFilter },
                success: function(response) {
                    if (response.status) {
                        displayRequests(response.data);
                        updateStats(response.stats);
                    } else {
                        alert('Error loading data');
                    }
                },
                error: function() {
                    console.error('Error connecting to server');
                }
            });
        }

        function updateStats(stats) {
            if (!stats) return;
            $('#stat-pending').text(stats.pending || 0);
            $('#stat-approved').text(stats.approved || 0);
            $('#stat-rejected').text(stats.rejected || 0);
        }

        function displayRequests(data) {
            const tbody = $('#requestsList');
            tbody.empty();
            requestsMap = {};

            if (data.length === 0) {
                tbody.html(`
                    <tr>
                        <td colspan="6" class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No requests found</p>
                        </td>
                    </tr>
                `);
                return;
            }

            data.forEach(req => {
                requestsMap[req.id] = req;
                
                let actions = '';
                if (req.status === 'pending') {
                    actions = `
                        <button class="action-btn btn-approve" onclick="processRequest(${req.id}, 'approve')">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="action-btn btn-reject" onclick="openRejectModal(${req.id})">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    `;
                } else if (req.status === 'rejected' && req.admin_notes) {
                    actions = `<small style="color: var(--text-secondary);">Reason: ${req.admin_notes}</small>`;
                }

                tbody.append(`
                    <tr>
                        <td>#${req.id}</td>
                        <td>
                            <strong>${req.user_full_name}</strong><br>
                            <small style="color: var(--text-secondary);">${req.user_email}</small><br>
                            <small style="color: var(--accent);">@${req.user_qr_id}</small>
                        </td>
                        <td>
                            <a href="${req.supercharge_link}" target="_blank" class="req-link">
                                ${req.supercharge_link} <i class="fas fa-external-link-alt" style="font-size: 0.8em;"></i>
                            </a>
                        </td>
                        <td><span class="status-badge status-${req.status}">${req.status}</span></td>
                        <td><small>${new Date(req.created_at).toLocaleDateString()}</small></td>
                        <td>${actions}</td>
                    </tr>
                `);
            });
        }

        function processRequest(id, action, notes = '') {
            if (action === 'approve' && !confirm('Are you sure you want to approve this request?')) return;

            $.ajax({
                url: '../backend/supercharge/process_request.php',
                type: 'POST',
                data: JSON.stringify({
                    request_id: id,
                    action: action,
                    admin_notes: notes
                }),
                contentType: 'application/json',
                success: function(response) {
                    if (response.status) {
                        loadRequests();
                        if (action === 'reject') closeRejectModal();
                    } else {
                        alert(response.message);
                    }
                }
            });
        }

        function openRejectModal(id) {
            $('#rejectRequestId').val(id);
            $('#rejectReason').val('');
            $('#rejectModal').addClass('active');
        }

        function closeRejectModal() {
            $('#rejectModal').removeClass('active');
        }

        function submitReject() {
            const id = $('#rejectRequestId').val();
            const reason = $('#rejectReason').val();
            if (!reason) {
                alert('Please provide a reason');
                return;
            }
            processRequest(id, 'reject', reason);
        }

        // Close modal on outside click
        $('#rejectModal').click(function(e) {
            if (e.target === this) closeRejectModal();
        });
    </script>
</body>
</html>
