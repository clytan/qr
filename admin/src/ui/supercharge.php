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
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Super Charge Requests - Admin</title>
    <title>Super Charge Requests - Admin</title>
    <!-- Custom fonts for this template-->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            background: #0f172a;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #e2e8f0;
        }

        /* Dashboard Content */
        .dashboard-content {
            padding: 30px;
        }

        /* Card Styles */
        .card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .text-primary { color: #E9437A !important; }
        .text-gray-800 { color: #f1f5f9 !important; }

        .card-body {
            padding: 25px;
        }

        /* Table Styles */
        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: #e2e8f0;
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: 1rem;
            vertical-align: top;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid rgba(255, 255, 255, 0.08);
            border-top: none;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
        }

        .badge {
            display: inline-block;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }
        .badge-pending { background-color: #f6c23e; color: #fff; }
        .badge-approved { background-color: #1cc88a; color: #fff; }
        .badge-rejected { background-color: #e74a3b; color: #fff; }

        /* Buttons */
        .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.35rem;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            cursor: pointer;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: 0.2rem;
        }
        .btn-success { background-color: #1cc88a; border-color: #1cc88a; color: white; }
        .btn-danger { background-color: #e74a3b; border-color: #e74a3b; color: white; }
        .btn-outline-primary { color: #4e73df; border-color: #4e73df; background: transparent; }
        .btn-outline-primary:hover, .btn-outline-primary.active { color: #fff; background-color: #4e73df; }
        
        /* Modal */
        .modal { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; overflow: hidden; outline: 0; background: rgba(0,0,0,0.5); }
        .modal.show { display: block; overflow-y: auto; }
        .modal-dialog { position: relative; width: auto; margin: 0.5rem; pointer-events: none; max-width: 500px; margin: 1.75rem auto; }
        .modal-content { position: relative; display: flex; flex-direction: column; width: 100%; pointer-events: auto; background-color: #1a1c23; background-clip: padding-box; border: 1px solid rgba(255,255,255,0.1); border-radius: 0.3rem; outline: 0; color: #e2e8f0; }
        .modal-header { display: flex; align-items: flex-start; justify-content: space-between; padding: 1rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .modal-body { position: relative; flex: 1 1 auto; padding: 1rem; }
        .modal-footer { display: flex; align-items: center; justify-content: flex-end; padding: 1rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .close { float: right; font-size: 1.5rem; font-weight: 700; line-height: 1; color: #fff; text-shadow: 0 1px 0 #fff; opacity: .5; background: none; border: none; cursor: pointer; }
        
        .form-control {
            display: block;
            width: 100%;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #e2e8f0;
            background-color: #2d3748;
            background-clip: padding-box;
            border: 1px solid #4a5568;
            border-radius: 0.25rem;
            transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
        }

        /* Layout Override for sidebar */
        .admin-main { margin-left: 260px; transition: margin-left 0.3s ease; }
        @media (max-width: 768px) { .admin-main { margin-left: 0; padding-top: 60px; } }
    </style>
</head>

<body id="page-top">
    <?php include('../components/sidebar.php'); ?>
    
    <main class="admin-main">
        <div class="dashboard-content">

                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800" style="font-size: 1.75rem; margin-bottom: 0.5rem;">Super Charge Requests</h1>
                    <p class="mb-4" style="color: #94a3b8;">Manage promotion requests from Gold users.</p>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Requests List</h6>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary active" onclick="filterRequests('all')">All</button>
                                <button type="button" class="btn btn-sm btn-outline-warning" style="color: #f6c23e; border-color: #f6c23e;" onclick="filterRequests('pending')">Pending</button>
                                <button type="button" class="btn btn-sm btn-outline-success" style="color: #1cc88a; border-color: #1cc88a;" onclick="filterRequests('approved')">Approved</button>
                                <button type="button" class="btn btn-sm btn-outline-danger" style="color: #e74a3b; border-color: #e74a3b;" onclick="filterRequests('rejected')">Rejected</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="requestsTable" width="100%" cellspacing="0">
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
                                        <!-- Data populated by JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </main>

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Request</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="rejectForm">
                        <input type="hidden" id="rejectRequestId">
                        <div class="form-group">
                            <label>Reason for Rejection</label>
                            <textarea class="form-control" id="rejectReason" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger" type="button" onclick="submitReject()">Reject</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let currentFilter = 'all';

        $(document).ready(function() {
            loadRequests();
        });

        function filterRequests(status) {
            currentFilter = status;
            $('.btn-group .btn').removeClass('active');
            $(`.btn-group .btn-outline-${status === 'all' ? 'primary' : (status === 'pending' ? 'warning' : (status === 'approved' ? 'success' : 'danger'))}`).addClass('active');
            loadRequests();
        }

        function loadRequests() {
            $.ajax({
                url: '../backend/supercharge/get_requests.php',
                type: 'GET',
                data: { status: currentFilter },
                success: function(response) {
                    if (response.status) {
                        displayRequests(response.data);
                    } else {
                        alert('Error loading data');
                    }
                },
                error: function() {
                    alert('Error connecting to server');
                }
            });
        }

        function displayRequests(data) {
            const tbody = $('#requestsList');
            tbody.empty();

            if (data.length === 0) {
                tbody.html('<tr><td colspan="6" class="text-center">No requests found</td></tr>');
                return;
            }

            data.forEach(req => {
                let statusBadge = `<span class="badge badge-${req.status}">${req.status.toUpperCase()}</span>`;
                
                let actions = '';
                if (req.status === 'pending') {
                    actions = `
                        <button class="btn btn-sm btn-success" onclick="processRequest(${req.id}, 'approve')">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="openRejectModal(${req.id})">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                } else if (req.status === 'rejected' && req.admin_notes) {
                    actions = `<small class="text-muted">Reason: ${req.admin_notes}</small>`;
                }

                tbody.append(`
                    <tr>
                        <td>${req.id}</td>
                        <td>
                            <div><strong>${req.user_full_name}</strong></div>
                            <small class="text-muted">${req.user_email}</small><br>
                            <small class="text-muted">@${req.user_qr_id}</small>
                        </td>
                        <td>
                            <a href="${req.supercharge_link}" target="_blank">${req.supercharge_link}</a>
                        </td>
                        <td>${statusBadge}</td>
                        <td>${new Date(req.created_at).toLocaleDateString()}</td>
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
                        if (action === 'reject') $('#rejectModal').modal('hide');
                    } else {
                        alert(response.message);
                    }
                }
            });
        }

        function openRejectModal(id) {
            $('#rejectRequestId').val(id);
            $('#rejectReason').val('');
            $('#rejectModal').modal('show');
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
    </script>
</body>
</html>
