<?php
// Include auth check
require_once('../components/auth_check.php');
require_once('../backend/dbconfig/connection.php');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'create_programme':
            try {
                $programme_header = trim($_POST['programme_header'] ?? '');
                $company_name = trim($_POST['company_name'] ?? '');
                $product_link = trim($_POST['product_link'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $commission_details = trim($_POST['commission_details'] ?? '');
                $company_email = trim($_POST['company_email'] ?? '');
                $admin_id = $_SESSION['admin_id'] ?? 1;
                
                if (empty($programme_header) || empty($commission_details) || empty($company_email)) {
                    echo json_encode(['status' => false, 'message' => 'Header, commission details, and company email are required']);
                    exit();
                }
                
                $sql = "INSERT INTO partner_programmes (
                    programme_header, company_name, product_link, description,
                    commission_details, company_email, status, created_by, created_on
                ) VALUES (?, ?, ?, ?, ?, ?, 'active', ?, NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    'ssssssi',
                    $programme_header, $company_name, $product_link, $description,
                    $commission_details, $company_email, $admin_id
                );
                
                if ($stmt->execute()) {
                    echo json_encode(['status' => true, 'message' => 'Partner programme created successfully']);
                } else {
                    echo json_encode(['status' => false, 'message' => 'Failed to create programme: ' . $stmt->error]);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'get_programmes':
            try {
                $sql = "SELECT p.*, COUNT(r.id) as referral_count
                        FROM partner_programmes p
                        LEFT JOIN partner_referrals r ON p.id = r.programme_id AND r.is_deleted = 0
                        WHERE p.is_deleted = 0
                        GROUP BY p.id
                        ORDER BY p.created_on DESC";
                
                $result = $conn->query($sql);
                $programmes = [];
                
                while ($row = $result->fetch_assoc()) {
                    $programmes[] = $row;
                }
                
                echo json_encode(['status' => true, 'data' => $programmes]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'get_referrals':
            try {
                $programme_id = intval($_POST['programme_id'] ?? 0);
                $status_filter = $_POST['status'] ?? 'all';
                
                $sql = "SELECT r.*, p.programme_header, p.company_name, u.user_full_name, u.user_email as user_email
                        FROM partner_referrals r
                        LEFT JOIN partner_programmes p ON r.programme_id = p.id
                        LEFT JOIN user_user u ON r.referred_by = u.id
                        WHERE r.is_deleted = 0";
                
                if ($programme_id > 0) {
                    $sql .= " AND r.programme_id = $programme_id";
                }
                
                if ($status_filter !== 'all') {
                    $sql .= " AND r.status = '$status_filter'";
                }
                
                $sql .= " ORDER BY r.created_on DESC";
                
                $result = $conn->query($sql);
                $referrals = [];
                
                while ($row = $result->fetch_assoc()) {
                    $referrals[] = $row;
                }
                
                echo json_encode(['status' => true, 'data' => $referrals]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'update_referral_status':
            try {
                $referral_id = intval($_POST['referral_id'] ?? 0);
                $new_status = $_POST['new_status'] ?? '';
                $notes = trim($_POST['notes'] ?? '');
                
                if ($referral_id <= 0 || !in_array($new_status, ['open', 'in_process', 'closed'])) {
                    echo json_encode(['status' => false, 'message' => 'Invalid referral ID or status']);
                    exit();
                }
                
                $sql = "UPDATE partner_referrals SET status = ?, notes = ?, updated_on = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ssi', $new_status, $notes, $referral_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['status' => true, 'message' => 'Referral status updated successfully']);
                } else {
                    echo json_encode(['status' => false, 'message' => 'Failed to update status']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'toggle_programme':
            try {
                $programme_id = intval($_POST['programme_id'] ?? 0);
                $new_status = $_POST['new_status'] ?? '';
                
                if ($programme_id <= 0 || !in_array($new_status, ['active', 'inactive'])) {
                    echo json_encode(['status' => false, 'message' => 'Invalid programme or status']);
                    exit();
                }
                
                $stmt = $conn->prepare("UPDATE partner_programmes SET status = ? WHERE id = ?");
                $stmt->bind_param('si', $new_status, $programme_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['status' => true, 'message' => 'Programme status updated']);
                } else {
                    echo json_encode(['status' => false, 'message' => 'Failed to update programme']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'delete_programme':
            try {
                $programme_id = intval($_POST['programme_id'] ?? 0);
                
                if ($programme_id <= 0) {
                    echo json_encode(['status' => false, 'message' => 'Invalid programme ID']);
                    exit();
                }
                
                $stmt = $conn->prepare("UPDATE partner_programmes SET is_deleted = 1 WHERE id = ?");
                $stmt->bind_param('i', $programme_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['status' => true, 'message' => 'Programme deleted successfully']);
                } else {
                    echo json_encode(['status' => false, 'message' => 'Failed to delete programme']);
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
    <title>Partner Programmes - Admin Panel</title>
    <link rel="icon" href="../../assets/logo2.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html { background: #0f172a; min-height: 100vh; font-family: 'Inter', sans-serif; color: #e2e8f0; }
        
        .page-content { padding: 30px; }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 25px;
            background: linear-gradient(135deg, rgba(233, 67, 122, 0.1) 0%, rgba(226, 173, 42, 0.1) 100%);
            border-radius: 16px;
            border: 1px solid rgba(233, 67, 122, 0.2);
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #E9437A 0%, #E2AD2A 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .create-btn {
            padding: 14px 28px;
            background: linear-gradient(135deg, #E9437A 0%, #E2AD2A 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 20px rgba(233, 67, 122, 0.3);
            transition: all 0.3s ease;
        }
        
        .create-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(233, 67, 122, 0.4);
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            background: rgba(30, 41, 59, 0.6);
            padding: 8px;
            border-radius: 14px;
        }
        
        .tab-btn {
            flex: 1;
            padding: 14px 24px;
            background: transparent;
            border: none;
            border-radius: 10px;
            color: #94a3b8;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .tab-btn:hover { background: rgba(255, 255, 255, 0.05); color: #e2e8f0; }
        .tab-btn.active {
            background: linear-gradient(135deg, #E9437A 0%, #E2AD2A 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(233, 67, 122, 0.3);
        }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        /* Programme Cards */
        .programmes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
        }
        
        .programme-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 25px;
            transition: all 0.3s ease;
        }
        
        .programme-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(233, 67, 122, 0.2);
            border-color: rgba(233, 67, 122, 0.4);
        }
        
        .programme-header {
            font-size: 20px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 8px;
        }
        
        .company-name {
            font-size: 14px;
            color: #E9437A;
            margin-bottom: 15px;
        }
        
        .programme-description {
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .commission-box {
            padding: 12px;
            background: linear-gradient(135deg, rgba(226, 173, 42, 0.15), rgba(233, 67, 122, 0.15));
            border-radius: 10px;
            margin-bottom: 15px;
            border: 1px solid rgba(226, 173, 42, 0.3);
        }
        
        .commission-label {
            font-size: 12px;
            color: #94a3b8;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .commission-text {
            font-size: 14px;
            color: #E2AD2A;
            font-weight: 600;
        }
        
        .programme-stats {
            display: flex;
            gap: 20px;
            padding: 15px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 15px;
        }
        
        .stat-item {
            text-align: center;
            flex: 1;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #E9437A;
        }
        
        .stat-label {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
        }
        
        .programme-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            flex: 1;
            min-width: 100px;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }
        
        .action-btn.view {
            background: rgba(233, 67, 122, 0.2);
            color: #E9437A;
        }
        
        .action-btn.toggle {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }
        
        .action-btn.delete {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
        
        .action-btn:hover { opacity: 0.8; }
        
        /* Referrals Table */
        .referrals-section {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            overflow: hidden;
        }
        
        .section-header {
            padding: 20px;
            background: rgba(15, 23, 42, 0.5);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #f1f5f9;
        }
        
        .filter-group {
            display: flex;
            gap: 10px;
        }
        
        .filter-select {
            padding: 8px 16px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #e2e8f0;
            font-size: 14px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: rgba(15, 23, 42, 0.5);
            padding: 14px 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
        }
        
        td {
            padding: 14px 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 13px;
            color: #e2e8f0;
        }
        
        tr:hover td { background: rgba(255, 255, 255, 0.02); }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-badge.open {
            background: rgba(233, 67, 122, 0.2);
            color: #E9437A;
        }
        
        .status-badge.in_process {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }
        
        .status-badge.closed {
            background: rgba(226, 173, 42, 0.2);
            color: #E2AD2A;
        }
        
        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 3000;
            padding: 20px;
            backdrop-filter: blur(5px);
        }
        
        .modal-overlay.show { display: flex; }
        
        .modal {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            border-radius: 20px;
            width: 100%;
            max-width: 650px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 35px;
            border: 1px solid rgba(233, 67, 122, 0.3);
        }
        
        .modal-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 25px;
            background: linear-gradient(135deg, #E9437A, #E2AD2A);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .form-group { margin-bottom: 20px; }
        
        .form-label {
            display: block;
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 8px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 13px 16px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #e2e8f0;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #E9437A;
            box-shadow: 0 0 0 3px rgba(233, 67, 122, 0.1);
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 14px 24px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }
        
        .btn.primary {
            background: linear-gradient(135deg, #E9437A, #E2AD2A);
            color: white;
        }
        
        .btn.secondary {
            background: rgba(255, 255, 255, 0.05);
            color: #94a3b8;
        }
        
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 18px 28px;
            border-radius: 12px;
            font-size: 14px;
            z-index: 4000;
            transform: translateX(150%);
            transition: transform 0.4s ease;
        }
        
        .toast.show { transform: translateX(0); }
        .toast.success { background: linear-gradient(135deg, #E2AD2A, #16a34a); color: white; }
        .toast.error { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }
        
        .empty-state i {
            font-size: 64px;
            opacity: 0.3;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .programmes-grid { grid-template-columns: 1fr; }
            .page-header { flex-direction: column; gap: 15px; }
            table { font-size: 12px; }
        }
    </style>
</head>
<body>
    <?php include('../components/sidebar.php'); ?>
    
    <main class="admin-main">
        <div class="page-content">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-handshake"></i>
                    Partner Programmes
                </h1>
                <button class="create-btn" onclick="showCreateModal()">
                    <i class="fas fa-plus"></i>
                    Create Programme
                </button>
            </div>
            
            <div class="tabs">
                <button class="tab-btn active" onclick="showTab('programmes')">
                    <i class="fas fa-briefcase"></i>
                    Programmes
                </button>
                <button class="tab-btn" onclick="showTab('referrals')">
                    <i class="fas fa-users"></i>
                    All Referrals
                </button>
            </div>
            
            <!-- Tab: Programmes -->
            <div class="tab-content active" id="tab-programmes">
                <div class="programmes-grid" id="programmes-grid">
                    <div class="empty-state">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading programmes...</p>
                    </div>
                </div>
            </div>
            
            <!-- Tab: Referrals -->
            <div class="tab-content" id="tab-referrals">
                <div class="referrals-section">
                    <div class="section-header">
                        <h3 class="section-title">All Referrals</h3>
                        <div class="filter-group">
                            <select class="filter-select" id="status-filter" onchange="loadReferrals()">
                                <option value="all">All Status</option>
                                <option value="open">Open</option>
                                <option value="in_process">In Process</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                    </div>
                    <div style="overflow-x: auto;">
                        <table id="referrals-table">
                            <thead>
                                <tr>
                                    <th>Programme</th>
                                    <th>Client Name</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Product</th>
                                    <th>Referred By</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="referrals-body">
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px;">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Create Programme Modal -->
    <div class="modal-overlay" id="createModal">
        <div class="modal">
            <h3 class="modal-title">
                <i class="fas fa-plus-circle"></i>
                Create Partner Programme
            </h3>
            <form id="createProgrammeForm">
                <div class="form-group">
                    <label class="form-label">Programme Header *</label>
                    <input type="text" class="form-input" name="programme_header" required placeholder="e.g., Sell Life Insurance">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Company Name</label>
                    <input type="text" class="form-input" name="company_name" placeholder="e.g., ABC Insurance Ltd.">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Product/Company Link</label>
                    <input type="url" class="form-input" name="product_link" placeholder="https://company.com">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description *</label>
                    <textarea class="form-textarea" name="description" required placeholder="Describe the partner programme..."></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Commission Details *</label>
                    <textarea class="form-textarea" name="commission_details" required placeholder="e.g., 10% commission on first year premium, Paid within 30 days..."></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Company Email *</label>
                    <input type="email" class="form-input" name="company_email" required placeholder="company@example.com">
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn secondary" onclick="closeModal('createModal')">Cancel</button>
                    <button type="submit" class="btn primary">Create Programme</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div class="modal-overlay" id="statusModal">
        <div class="modal">
            <h3 class="modal-title">
                <i class="fas fa-edit"></i>
                Update Referral Status
            </h3>
            <form id="updateStatusForm">
                <input type="hidden" id="referral-id">
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="new-status">
                        <option value="open">Open</option>
                        <option value="in_process">In Process</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Notes (Optional)</label>
                    <textarea class="form-textarea" id="status-notes" placeholder="Add any notes about this referral..."></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn secondary" onclick="closeModal('statusModal')">Cancel</button>
                    <button type="submit" class="btn primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentTab = 'programmes';
        
        $(document).ready(function() {
            loadProgrammes();
        });
        
        function showTab(tab) {
            currentTab = tab;
            $('.tab-btn').removeClass('active');
            $(`.tab-btn:contains('${tab === 'programmes' ? 'Programmes' : 'Referrals'}')`).addClass('active');
            $('.tab-content').removeClass('active');
            $(`#tab-${tab}`).addClass('active');
            
            if (tab === 'referrals') {
                loadReferrals();
            }
        }
        
        function loadProgrammes() {
            $.post('', { action: 'get_programmes' }, function(response) {
                if (response.status && response.data.length > 0) {
                    renderProgrammes(response.data);
                } else {
                    $('#programmes-grid').html(`
                        <div class="empty-state">
                            <i class="fas fa-briefcase"></i>
                            <h3>No Programmes</h3>
                            <p>Create your first partner programme to get started!</p>
                        </div>
                    `);
                }
            }, 'json').fail(function() {
                showToast('Failed to load programmes', 'error');
            });
        }
        
        function renderProgrammes(programmes) {
            let html = '';
            
            programmes.forEach(p => {
                const statusClass = p.status === 'active' ? 'success' : 'warning';
                html += `
                    <div class="programme-card">
                        <div class="programme-header">${escapeHtml(p.programme_header)}</div>
                        ${p.company_name ? `<div class="company-name">${escapeHtml(p.company_name)}</div>` : ''}
                        <div class="programme-description">${escapeHtml(p.description)}</div>
                        
                        <div class="commission-box">
                            <div class="commission-label">💰 Commission Structure</div>
                            <div class="commission-text">${escapeHtml(p.commission_details)}</div>
                        </div>
                        
                        <div class="programme-stats">
                            <div class="stat-item">
                                <div class="stat-value">${p.referral_count || 0}</div>
                                <div class="stat-label">Referrals</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value status-badge ${statusClass}">${p.status}</div>
                                <div class="stat-label">Status</div>
                            </div>
                        </div>
                        
                        <div class="programme-actions">
                            <button class="action-btn view" onclick="viewProgrammeReferrals(${p.id})">
                                <i class="fas fa-eye"></i> View Referrals
                            </button>
                            <button class="action-btn toggle" onclick="toggleProgramme(${p.id}, '${p.status === 'active' ? 'inactive' : 'active'}')">
                                <i class="fas fa-toggle-${p.status === 'active' ? 'on' : 'off'}"></i> ${p.status === 'active' ? 'Deactivate' : 'Activate'}
                            </button>
                            <button class="action-btn delete" onclick="deleteProgramme(${p.id})">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                `;
            });
            
            $('#programmes-grid').html(html);
        }
        
        function loadReferrals(programmeId = 0) {
            const status = $('#status-filter').val();
            
            $.post('', {
                action: 'get_referrals',
                programme_id: programmeId,
                status: status
            }, function(response) {
                if (response.status && response.data.length > 0) {
                    renderReferrals(response.data);
                } else {
                    $('#referrals-body').html(`
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: #64748b;">
                                No referrals found
                            </td>
                        </tr>
                    `);
                }
            }, 'json').fail(function() {
                showToast('Failed to load referrals', 'error');
            });
        }
        
        function renderReferrals(referrals) {
            let html = '';
            
            referrals.forEach(r => {
                html += `
                    <tr>
                        <td>${escapeHtml(r.programme_header)}</td>
                        <td>${escapeHtml(r.client_name)}</td>
                        <td>${escapeHtml(r.client_phone)}</td>
                        <td>${escapeHtml(r.client_email)}</td>
                        <td>${escapeHtml(r.product_name)}</td>
                        <td>${escapeHtml(r.user_full_name || 'N/A')}</td>
                        <td><span class="status-badge ${r.status}">${r.status.replace('_', ' ')}</span></td>
                        <td>${formatDate(r.created_on)}</td>
                        <td>
                            <button class="action-btn view" style="min-width: auto; padding: 6px 12px;" onclick="updateReferralStatus(${r.id}, '${r.status}')">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            $('#referrals-body').html(html);
        }
        
        function viewProgrammeReferrals(programmeId) {
            showTab('referrals');
            loadReferrals(programmeId);
        }
        
        function showCreateModal() {
            $('#createModal').addClass('show');
        }
        
        function closeModal(modalId) {
            $(`#${modalId}`).removeClass('show');
        }
        
        $('#createProgrammeForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = $(this).serialize() + '&action=create_programme';
            
            $.post('', formData, function(response) {
                if (response.status) {
                    showToast(response.message, 'success');
                    closeModal('createModal');
                    $('#createProgrammeForm')[0].reset();
                    loadProgrammes();
                } else {
                    showToast(response.message, 'error');
                }
            }, 'json');
        });
        
        function updateReferralStatus(id, currentStatus) {
            $('#referral-id').val(id);
            $('#new-status').val(currentStatus);
            $('#statusModal').addClass('show');
        }
        
        $('#updateStatusForm').on('submit', function(e) {
            e.preventDefault();
            
            $.post('', {
                action: 'update_referral_status',
                referral_id: $('#referral-id').val(),
                new_status: $('#new-status').val(),
                notes: $('#status-notes').val()
            }, function(response) {
                if (response.status) {
                    showToast(response.message, 'success');
                    closeModal('statusModal');
                    loadReferrals();
                } else {
                    showToast(response.message, 'error');
                }
            }, 'json');
        });
        
        function toggleProgramme(id, newStatus) {
            if (!confirm(`Are you sure you want to ${newStatus} this programme?`)) return;
            
            $.post('', {
                action: 'toggle_programme',
                programme_id: id,
                new_status: newStatus
            }, function(response) {
                if (response.status) {
                    showToast(response.message, 'success');
                    loadProgrammes();
                } else {
                    showToast(response.message, 'error');
                }
            }, 'json');
        }
        
        function deleteProgramme(id) {
            if (!confirm('Are you sure you want to delete this programme?')) return;
            
            $.post('', {
                action: 'delete_programme',
                programme_id: id
            }, function(response) {
                if (response.status) {
                    showToast(response.message, 'success');
                    loadProgrammes();
                } else {
                    showToast(response.message, 'error');
                }
            }, 'json');
        }
        
        function showToast(message, type) {
            const toast = $('#toast');
            toast.removeClass('success error').addClass(type).text(message).addClass('show');
            setTimeout(() => toast.removeClass('show'), 3000);
        }
        
        function escapeHtml(text) {
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return text ? String(text).replace(/[&<>"']/g, m => map[m]) : '';
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }
    </script>
</body>
</html>
