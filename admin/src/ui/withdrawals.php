<?php
// Admin - Wallet Withdrawals Management
require_once('../components/auth_check.php');
require_once('../backend/dbconfig/connection.php');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'get_withdrawals':
            $status_filter = isset($_POST['status']) ? $_POST['status'] : 'all';
            
            $sql = "SELECT 
                        w.id,
                        w.user_id,
                        w.amount,
                        w.payment_method,
                        w.upi_id,
                        w.bank_name,
                        w.branch_name,
                        w.account_number,
                        w.ifsc_code,
                        w.account_holder_name,
                        w.status,
                        w.admin_notes,
                        w.rejection_reason,
                        w.transaction_reference,
                        w.created_on,
                        w.processed_on,
                        u.user_full_name,
                        u.user_email,
                        u.user_phone,
                        u.user_qr_id,
                        uw.balance as current_balance
                    FROM user_wallet_withdrawals w
                    LEFT JOIN user_user u ON w.user_id = u.id
                    LEFT JOIN user_wallet uw ON w.user_id = uw.user_id AND uw.is_deleted = 0
                    WHERE w.is_deleted = 0";
            
            if ($status_filter !== 'all') {
                $sql .= " AND w.status = '" . $conn->real_escape_string($status_filter) . "'";
            }
            
            $sql .= " ORDER BY 
                        CASE w.status 
                            WHEN 'pending' THEN 1 
                            WHEN 'approved' THEN 2 
                            WHEN 'completed' THEN 3 
                            WHEN 'rejected' THEN 4 
                        END, 
                        w.created_on DESC";
            
            $result = $conn->query($sql);
            $withdrawals = [];
            while ($row = $result->fetch_assoc()) {
                $withdrawals[] = $row;
            }
            
            // Get stats
            $sqlStats = "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                            SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
                            SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount
                         FROM user_wallet_withdrawals WHERE is_deleted = 0";
            $statsResult = $conn->query($sqlStats);
            $stats = $statsResult->fetch_assoc();
            
            echo json_encode(['status' => true, 'data' => $withdrawals, 'stats' => $stats]);
            exit();
            
        case 'process_withdrawal':
            $withdrawal_id = intval($_POST['withdrawal_id'] ?? 0);
            $process_action = $_POST['process_action'] ?? '';
            $admin_notes = $_POST['admin_notes'] ?? '';
            $rejection_reason = $_POST['rejection_reason'] ?? '';
            $transaction_reference = $_POST['transaction_reference'] ?? '';
            
            if ($withdrawal_id <= 0 || !in_array($process_action, ['approve', 'reject', 'complete'])) {
                echo json_encode(['status' => false, 'message' => 'Invalid parameters']);
                exit();
            }
            
            // Get withdrawal details
            $stmt = $conn->prepare("SELECT * FROM user_wallet_withdrawals WHERE id = ? AND is_deleted = 0");
            $stmt->bind_param('i', $withdrawal_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode(['status' => false, 'message' => 'Withdrawal not found']);
                exit();
            }
            
            $withdrawal = $result->fetch_assoc();
            $user_id = $withdrawal['user_id'];
            $amount = floatval($withdrawal['amount']);
            
            $conn->begin_transaction();
            try {
                $new_status = '';
                switch ($process_action) {
                    case 'approve':
                        $new_status = 'approved';
                        break;
                    case 'reject':
                        $new_status = 'rejected';
                        // Refund to wallet
                        $conn->query("UPDATE user_wallet SET balance = balance + $amount, updated_on = NOW() WHERE user_id = $user_id");
                        // Record refund transaction
                        $desc = "Withdrawal rejected - Refund for request #$withdrawal_id";
                        $stmtTrans = $conn->prepare("INSERT INTO user_wallet_transaction (user_id, amount, transaction_type, description, created_on, updated_on, is_deleted) VALUES (?, ?, 'Refund', ?, NOW(), NOW(), 0)");
                        $stmtTrans->bind_param('ids', $user_id, $amount, $desc);
                        $stmtTrans->execute();
                        break;
                    case 'complete':
                        $new_status = 'completed';
                        break;
                }
                
                $admin_id = $_SESSION['admin_id'];
                $stmtUpdate = $conn->prepare("UPDATE user_wallet_withdrawals SET status = ?, admin_notes = ?, rejection_reason = ?, transaction_reference = ?, processed_by = ?, processed_on = NOW(), updated_on = NOW() WHERE id = ?");
                $stmtUpdate->bind_param('ssssii', $new_status, $admin_notes, $rejection_reason, $transaction_reference, $admin_id, $withdrawal_id);
                $stmtUpdate->execute();
                
                $conn->commit();
                echo json_encode(['status' => true, 'message' => 'Withdrawal ' . $process_action . 'd successfully']);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet Withdrawals - Admin</title>
    <link rel="icon" href="../../assets/logo2.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            /* padding: 20px; Removed padding as it's handled by dashboard-content */
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
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--bg-secondary);
            border: 1px solid var(--bg-tertiary);
            border-radius: 8px;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .back-btn:hover {
            background: var(--bg-tertiary);
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
        .stat-card.completed { border-left: 4px solid var(--info); }
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
        .status-completed { background: rgba(59, 130, 246, 0.15); color: var(--info); }
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
        
        .btn-complete {
            background: rgba(59, 130, 246, 0.15);
            color: var(--info);
            border: 1px solid var(--info);
        }
        
        .btn-complete:hover {
            background: var(--info);
            color: white;
        }
        
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
        
        .btn-primary:hover {
            opacity: 0.9;
        }
        
        .payment-info {
            background: var(--bg-primary);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 0.9rem;
        }
        
        .payment-info p {
            margin: 4px 0;
        }
        
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
                    <h1><i class="fas fa-money-bill-wave"></i> Wallet Withdrawals</h1>
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
                    <div class="stat-card completed">
                        <div class="stat-value" id="stat-completed">0</div>
                        <div class="stat-label">Completed</div>
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
                    <button class="filter-btn" data-status="completed">Completed</button>
                    <button class="filter-btn" data-status="rejected">Rejected</button>
                </div>
                
                <!-- Table -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Payment Details</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="withdrawals-table">
                            <tr>
                                <td colspan="8" class="empty-state">
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

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    
    <!-- Process Modal -->
    <div class="modal-overlay" id="processModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Process Withdrawal</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal-withdrawal-id">
                <input type="hidden" id="modal-action">
                
                <div class="payment-info" id="modal-payment-info"></div>
                
                <div class="form-group" id="rejection-reason-group" style="display: none;">
                    <label>Rejection Reason *</label>
                    <textarea class="form-control" id="rejection-reason" rows="3" placeholder="Enter reason for rejection"></textarea>
                </div>
                
                <div class="form-group" id="transaction-ref-group" style="display: none;">
                    <label>Transaction Reference</label>
                    <input type="text" class="form-control" id="transaction-reference" placeholder="Enter bank transaction reference">
                </div>
                
                <div class="form-group">
                    <label>Admin Notes (Optional)</label>
                    <textarea class="form-control" id="admin-notes" rows="2" placeholder="Internal notes"></textarea>
                </div>
                
                <button class="btn-primary" id="confirm-btn" onclick="confirmProcess()">Confirm</button>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentFilter = 'all';
        let withdrawalsMap = {}; // Global map to store withdrawal data
        
        $(document).ready(function() {
            loadWithdrawals();
            
            $('.filter-btn').click(function() {
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');
                currentFilter = $(this).data('status');
                loadWithdrawals();
            });
        });
        
        function loadWithdrawals() {
            $.post('', { action: 'get_withdrawals', status: currentFilter }, function(response) {
                if (response.status) {
                    renderWithdrawals(response.data);
                    updateStats(response.stats);
                }
            }, 'json');
        }
        
        function updateStats(stats) {
            $('#stat-pending').text(stats.pending || 0);
            $('#stat-approved').text(stats.approved || 0);
            $('#stat-completed').text(stats.completed || 0);
            $('#stat-rejected').text(stats.rejected || 0);
        }
        
        function renderWithdrawals(withdrawals) {
            withdrawalsMap = {}; // Reset map
            
            if (withdrawals.length === 0) {
                $('#withdrawals-table').html(`
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No withdrawal requests found</p>
                        </td>
                    </tr>
                `);
                return;
            }
            
            let html = '';
            withdrawals.forEach(w => {
                withdrawalsMap[w.id] = w; // Store in map
                
                const date = new Date(w.created_on).toLocaleString('en-IN');
                const paymentDetails = w.payment_method === 'upi' 
                    ? w.upi_id 
                    : `${w.bank_name}<br>${w.branch_name ? w.branch_name + '<br>' : ''}${w.account_number}<br>IFSC: ${w.ifsc_code}`;
                
                let actions = '';
                if (w.status === 'pending') {
                    actions = `
                        <button class="action-btn btn-approve" onclick="showProcessModal(${w.id}, 'approve')">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="action-btn btn-reject" onclick="showProcessModal(${w.id}, 'reject')">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    `;
                } else if (w.status === 'approved') {
                    actions = `
                        <button class="action-btn btn-complete" onclick="showProcessModal(${w.id}, 'complete')">
                            <i class="fas fa-check-double"></i> Complete
                        </button>
                        <button class="action-btn btn-reject" onclick="showProcessModal(${w.id}, 'reject')">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    `;
                }
                
                html += `
                    <tr>
                        <td>#${w.id}</td>
                        <td>
                            <strong>${w.user_full_name || 'N/A'}</strong><br>
                            <small>${w.user_email || ''}</small><br>
                            <small>${w.user_phone || ''}</small>
                        </td>
                        <td><strong>₹${parseFloat(w.amount).toLocaleString('en-IN')}</strong></td>
                        <td>${w.payment_method.toUpperCase()}</td>
                        <td><small>${paymentDetails}</small></td>
                        <td><span class="status-badge status-${w.status}">${w.status}</span></td>
                        <td><small>${date}</small></td>
                        <td>${actions}</td>
                    </tr>
                `;
            });
            
            $('#withdrawals-table').html(html);
        }
        
        function showProcessModal(id, action) {
            const data = withdrawalsMap[id]; // Retrieve from map
            if (!data) return;
            
            $('#modal-withdrawal-id').val(id);
            $('#modal-action').val(action);
            
            const titles = {
                approve: 'Approve Withdrawal',
                reject: 'Reject Withdrawal',
                complete: 'Complete Withdrawal'
            };
            $('#modal-title').text(titles[action]);
            
            let paymentInfo = `
                <p><strong>Amount:</strong> ₹${parseFloat(data.amount).toLocaleString('en-IN')}</p>
                <p><strong>Method:</strong> ${data.payment_method.toUpperCase()}</p>
            `;
            
            if (data.payment_method === 'upi') {
                paymentInfo += `<p><strong>UPI ID:</strong> ${data.upi_id}</p>`;
            } else {
                paymentInfo += `
                    <p><strong>Account Holder:</strong> ${data.account_holder_name}</p>
                    <p><strong>Bank:</strong> ${data.bank_name}</p>
                    <p><strong>Branch:</strong> ${data.branch_name || 'N/A'}</p>
                    <p><strong>Account:</strong> ${data.account_number}</p>
                    <p><strong>IFSC:</strong> ${data.ifsc_code}</p>
                `;
            }
            
            $('#modal-payment-info').html(paymentInfo);
            
            $('#rejection-reason-group').toggle(action === 'reject');
            $('#transaction-ref-group').toggle(action === 'complete');
            
            const btnColors = {
                approve: 'background: var(--success)',
                reject: 'background: var(--danger)',
                complete: 'background: var(--info)'
            };
            $('#confirm-btn').attr('style', btnColors[action]).text('Confirm ' + action.charAt(0).toUpperCase() + action.slice(1));
            
            $('#processModal').addClass('active');
        }
        
        function closeModal() {
            $('#processModal').removeClass('active');
            $('#rejection-reason').val('');
            $('#transaction-reference').val('');
            $('#admin-notes').val('');
        }
        
        function confirmProcess() {
            const id = $('#modal-withdrawal-id').val();
            const action = $('#modal-action').val();
            const rejection_reason = $('#rejection-reason').val();
            const transaction_reference = $('#transaction-reference').val();
            const admin_notes = $('#admin-notes').val();
            
            if (action === 'reject' && !rejection_reason.trim()) {
                alert('Please provide a rejection reason');
                return;
            }
            
            $('#confirm-btn').prop('disabled', true).text('Processing...');
            
            $.post('', {
                action: 'process_withdrawal',
                withdrawal_id: id,
                process_action: action,
                rejection_reason: rejection_reason,
                transaction_reference: transaction_reference,
                admin_notes: admin_notes
            }, function(response) {
                if (response.status) {
                    alert(response.message);
                    closeModal();
                    loadWithdrawals();
                } else {
                    alert(response.message || 'Operation failed');
                }
                $('#confirm-btn').prop('disabled', false);
            }, 'json').fail(function() {
                alert('Network error');
                $('#confirm-btn').prop('disabled', false);
            });
        }
        
        $('#processModal').click(function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
