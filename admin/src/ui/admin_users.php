<?php
// Include auth check
require_once('../components/auth_check.php');

// Include DB connection
require_once('../backend/dbconfig/connection.php');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'get_all':
            $sql = "SELECT au.*, aur.role_name 
                    FROM admin_user au 
                    LEFT JOIN admin_user_role aur ON au.role_id = aur.id 
                    WHERE au.is_deleted = 0 
                    ORDER BY au.created_on DESC";
            $result = $conn->query($sql);
            $users = [];
            while ($row = $result->fetch_assoc()) {
                unset($row['password']); // Don't send password
                $users[] = $row;
            }
            echo json_encode(['status' => true, 'data' => $users]);
            exit();
            
        case 'get_roles':
            $result = $conn->query("SELECT * FROM admin_user_role ORDER BY id");
            $roles = [];
            while ($row = $result->fetch_assoc()) {
                $roles[] = $row;
            }
            echo json_encode(['status' => true, 'data' => $roles]);
            exit();
            
        case 'create':
            $user_name = trim($_POST['user_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $role_id = intval($_POST['role_id'] ?? 0);
            
            if (empty($user_name) || empty($email) || empty($password) || $role_id <= 0) {
                echo json_encode(['status' => false, 'message' => 'All fields are required']);
                exit();
            }
            
            // Check if username or email already exists
            $check = $conn->prepare("SELECT id FROM admin_user WHERE (user_name = ? OR email = ?) AND is_deleted = 0");
            $check->bind_param('ss', $user_name, $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                echo json_encode(['status' => false, 'message' => 'Username or email already exists']);
                exit();
            }
            
            $stmt = $conn->prepare("INSERT INTO admin_user (user_name, password, phone, email, role_id, created_by, created_on, updated_by, updated_on, is_deleted) VALUES (?, ?, ?, ?, ?, ?, NOW(3), ?, NOW(3), 0)");
            $stmt->bind_param('ssssiii', $user_name, $password, $phone, $email, $role_id, $admin_id, $admin_id);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => true, 'message' => 'Admin user created successfully']);
            } else {
                echo json_encode(['status' => false, 'message' => 'Failed to create user']);
            }
            exit();
            
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $user_name = trim($_POST['user_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $role_id = intval($_POST['role_id'] ?? 0);
            $password = trim($_POST['password'] ?? '');
            
            if ($id <= 0 || empty($user_name) || empty($email) || $role_id <= 0) {
                echo json_encode(['status' => false, 'message' => 'Required fields missing']);
                exit();
            }
            
            if (!empty($password)) {
                $stmt = $conn->prepare("UPDATE admin_user SET user_name = ?, email = ?, phone = ?, role_id = ?, password = ?, updated_by = ?, updated_on = NOW(3) WHERE id = ?");
                $stmt->bind_param('sssissi', $user_name, $email, $phone, $role_id, $password, $admin_id, $id);
            } else {
                $stmt = $conn->prepare("UPDATE admin_user SET user_name = ?, email = ?, phone = ?, role_id = ?, updated_by = ?, updated_on = NOW(3) WHERE id = ?");
                $stmt->bind_param('sssiii', $user_name, $email, $phone, $role_id, $admin_id, $id);
            }
            
            if ($stmt->execute()) {
                echo json_encode(['status' => true, 'message' => 'Admin user updated successfully']);
            } else {
                echo json_encode(['status' => false, 'message' => 'Failed to update user']);
            }
            exit();
            
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => false, 'message' => 'Invalid ID']);
                exit();
            }
            
            // Prevent deleting yourself
            if ($id == $admin_id) {
                echo json_encode(['status' => false, 'message' => 'You cannot delete your own account']);
                exit();
            }
            
            $stmt = $conn->prepare("UPDATE admin_user SET is_deleted = 1, updated_by = ?, updated_on = NOW(3) WHERE id = ?");
            $stmt->bind_param('ii', $admin_id, $id);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => true, 'message' => 'Admin user deleted successfully']);
            } else {
                echo json_encode(['status' => false, 'message' => 'Failed to delete user']);
            }
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Admin Users - Admin Panel</title>
    <link rel="icon" href="../../assets/logo2.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html { background: #0f172a; min-height: 100vh; font-family: 'Inter', sans-serif; color: #e2e8f0; }
        
        .page-content { padding: 30px; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .page-title { font-size: 28px; font-weight: 700; color: #f1f5f9; }
        
        .btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px; background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            border: none; border-radius: 10px; color: white; font-size: 14px; font-weight: 600;
            cursor: pointer; transition: all 0.3s ease; text-decoration: none;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(230, 119, 83, 0.4); }
        
        /* Table Styles */
        .table-container {
            background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px; overflow: hidden;
        }
        
        table { width: 100%; border-collapse: collapse; }
        
        th { background: rgba(15, 23, 42, 0.5); padding: 16px 20px; text-align: left;
            font-size: 12px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
        
        td { padding: 16px 20px; border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 14px; color: #e2e8f0; }
        
        tr:hover td { background: rgba(255, 255, 255, 0.02); }
        
        .role-badge {
            display: inline-flex; padding: 5px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 500;
        }
        .role-badge.super-admin { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .role-badge.admin { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .role-badge.employee { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
        
        .action-btns { display: flex; gap: 8px; }
        .btn-icon {
            width: 36px; height: 36px; border-radius: 8px; border: none;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.2s ease; font-size: 14px;
        }
        .btn-edit { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .btn-edit:hover { background: rgba(59, 130, 246, 0.25); }
        .btn-delete { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .btn-delete:hover { background: rgba(239, 68, 68, 0.25); }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.7); display: none; align-items: center;
            justify-content: center; z-index: 2000; padding: 20px;
        }
        .modal-overlay.show { display: flex; }
        
        .modal {
            background: #1e293b; border-radius: 16px; width: 100%; max-width: 500px;
            max-height: 90vh; overflow-y: auto;
        }
        
        .modal-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 20px 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .modal-title { font-size: 18px; font-weight: 600; color: #f1f5f9; }
        .modal-close {
            width: 36px; height: 36px; border-radius: 8px; border: none;
            background: rgba(255, 255, 255, 0.05); color: #94a3b8;
            cursor: pointer; font-size: 18px; transition: all 0.2s ease;
        }
        .modal-close:hover { background: rgba(255, 255, 255, 0.1); color: #f1f5f9; }
        
        .modal-body { padding: 25px; }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; color: #94a3b8; font-size: 13px; font-weight: 500; margin-bottom: 8px; }
        .form-input, .form-select {
            width: 100%; padding: 12px 16px; background: rgba(15, 23, 42, 0.6);
            border: 1.5px solid rgba(255, 255, 255, 0.1); border-radius: 10px;
            font-size: 14px; color: #e2e8f0; transition: all 0.2s ease;
        }
        .form-input:focus, .form-select:focus {
            outline: none; border-color: #e67753;
            box-shadow: 0 0 0 3px rgba(230, 119, 83, 0.12);
        }
        .form-select { cursor: pointer; }
        .form-select option { background: #1e293b; color: #e2e8f0; }
        
        .modal-footer {
            display: flex; justify-content: flex-end; gap: 12px;
            padding: 20px 25px; border-top: 1px solid rgba(255, 255, 255, 0.08);
        }
        .btn-cancel {
            padding: 10px 20px; background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px;
            color: #94a3b8; font-size: 14px; cursor: pointer; transition: all 0.2s ease;
        }
        .btn-cancel:hover { background: rgba(255, 255, 255, 0.1); color: #f1f5f9; }
        
        .btn-submit {
            padding: 10px 24px; background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            border: none; border-radius: 8px; color: white; font-size: 14px; font-weight: 600;
            cursor: pointer; transition: all 0.2s ease;
        }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(230, 119, 83, 0.4); }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
        .empty-state i { font-size: 48px; margin-bottom: 15px; opacity: 0.5; }
        
        /* Toast notification */
        .toast {
            position: fixed; bottom: 30px; right: 30px;
            padding: 16px 24px; border-radius: 10px;
            font-size: 14px; font-weight: 500; z-index: 3000;
            transform: translateX(150%); transition: transform 0.3s ease;
        }
        .toast.show { transform: translateX(0); }
        .toast.success { background: rgba(34, 197, 94, 0.9); color: white; }
        .toast.error { background: rgba(239, 68, 68, 0.9); color: white; }
        
        /* Responsive table */
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .table-responsive table { min-width: 650px; }
        
        /* Mobile adjustments */
        @media (max-width: 768px) {
            .page-content { padding: 20px 15px; }
            .page-header { flex-direction: column; align-items: flex-start; }
            .page-title { font-size: 22px; }
            .btn-primary { width: 100%; justify-content: center; }
        }
    </style>
</head>

<body>
    <?php include('../components/sidebar.php'); ?>
    
    <main class="admin-main">
        <div class="page-content">
            <div class="page-header">
                <h1 class="page-title">Admin Users</h1>
                <button class="btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i> Add Admin User
                </button>
            </div>
            
            <div class="table-container">
                <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="users-table-body">
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
    
    <!-- Add/Edit Modal -->
    <div class="modal-overlay" id="userModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Add Admin User</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="userForm">
                <div class="modal-body">
                    <input type="hidden" id="userId" name="id">
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-input" id="userName" name="user_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-input" id="userEmail" name="email" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-input" id="userPhone" name="phone">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password <span id="passwordHint">(required)</span></label>
                        <input type="password" class="form-input" id="userPassword" name="password">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select class="form-select" id="userRole" name="role_id" required>
                            <option value="">Select Role</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit" id="submitBtn">Save User</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Delete</h3>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="color: #94a3b8;">Are you sure you want to delete this admin user? This action cannot be undone.</p>
                <input type="hidden" id="deleteUserId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button type="button" class="btn-submit" style="background: #ef4444;" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>
    
    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let roles = [];
        
        $(document).ready(function() {
            loadRoles();
            loadUsers();
        });
        
        function loadRoles() {
            $.post('', { action: 'get_roles' }, function(response) {
                if (response.status) {
                    roles = response.data;
                    let options = '<option value="">Select Role</option>';
                    roles.forEach(role => {
                        options += `<option value="${role.id}">${role.role_name}</option>`;
                    });
                    $('#userRole').html(options);
                }
            }, 'json');
        }
        
        function loadUsers() {
            $.post('', { action: 'get_all' }, function(response) {
                if (response.status && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(user => {
                        let roleClass = user.role_name?.toLowerCase().replace(' ', '-') || 'admin';
                        html += `
                            <tr>
                                <td><strong>${escapeHtml(user.user_name)}</strong></td>
                                <td>${escapeHtml(user.email)}</td>
                                <td>${escapeHtml(user.phone || '-')}</td>
                                <td><span class="role-badge ${roleClass}">${escapeHtml(user.role_name || 'N/A')}</span></td>
                                <td>${formatDate(user.created_on)}</td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-icon btn-edit" onclick='editUser(${JSON.stringify(user)})'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-icon btn-delete" onclick="deleteUser(${user.id})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    $('#users-table-body').html(html);
                } else {
                    $('#users-table-body').html(`
                        <tr>
                            <td colspan="6" class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                <p>No admin users found</p>
                            </td>
                        </tr>
                    `);
                }
            }, 'json');
        }
        
        function openModal() {
            $('#modalTitle').text('Add Admin User');
            $('#userForm')[0].reset();
            $('#userId').val('');
            $('#passwordHint').text('(required)');
            $('#userPassword').prop('required', true);
            $('#userModal').addClass('show');
        }
        
        function closeModal() {
            $('#userModal').removeClass('show');
        }
        
        function editUser(user) {
            $('#modalTitle').text('Edit Admin User');
            $('#userId').val(user.id);
            $('#userName').val(user.user_name);
            $('#userEmail').val(user.email);
            $('#userPhone').val(user.phone);
            $('#userRole').val(user.role_id);
            $('#userPassword').val('');
            $('#passwordHint').text('(leave blank to keep current)');
            $('#userPassword').prop('required', false);
            $('#userModal').addClass('show');
        }
        
        function deleteUser(id) {
            $('#deleteUserId').val(id);
            $('#deleteModal').addClass('show');
        }
        
        function closeDeleteModal() {
            $('#deleteModal').removeClass('show');
        }
        
        function confirmDelete() {
            const id = $('#deleteUserId').val();
            $.post('', { action: 'delete', id: id }, function(response) {
                closeDeleteModal();
                if (response.status) {
                    showToast(response.message, 'success');
                    loadUsers();
                } else {
                    showToast(response.message, 'error');
                }
            }, 'json');
        }
        
        $('#userForm').on('submit', function(e) {
            e.preventDefault();
            const isEdit = $('#userId').val() !== '';
            const action = isEdit ? 'update' : 'create';
            
            $.post('', { action: action, ...Object.fromEntries(new FormData(this)) }, function(response) {
                if (response.status) {
                    closeModal();
                    showToast(response.message, 'success');
                    loadUsers();
                } else {
                    showToast(response.message, 'error');
                }
            }, 'json');
        });
        
        function showToast(message, type) {
            const toast = $('#toast');
            toast.text(message).removeClass('success error').addClass(type + ' show');
            setTimeout(() => toast.removeClass('show'), 3000);
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        
        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        }
    </script>
</body>

</html>
