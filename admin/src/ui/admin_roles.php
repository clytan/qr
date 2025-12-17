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
            $result = $conn->query("SELECT * FROM admin_user_role ORDER BY id");
            $roles = [];
            while ($row = $result->fetch_assoc()) {
                $roles[] = $row;
            }
            echo json_encode(['status' => true, 'data' => $roles]);
            exit();
            
        case 'create':
            $role_name = trim($_POST['role_name'] ?? '');
            if (empty($role_name)) {
                echo json_encode(['status' => false, 'message' => 'Role name is required']);
                exit();
            }
            
            $stmt = $conn->prepare("INSERT INTO admin_user_role (role_name) VALUES (?)");
            $stmt->bind_param('s', $role_name);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => true, 'message' => 'Role created successfully']);
            } else {
                echo json_encode(['status' => false, 'message' => 'Failed to create role']);
            }
            exit();
            
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $role_name = trim($_POST['role_name'] ?? '');
            
            if ($id <= 0 || empty($role_name)) {
                echo json_encode(['status' => false, 'message' => 'Role ID and name are required']);
                exit();
            }
            
            $stmt = $conn->prepare("UPDATE admin_user_role SET role_name = ? WHERE id = ?");
            $stmt->bind_param('si', $role_name, $id);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => true, 'message' => 'Role updated successfully']);
            } else {
                echo json_encode(['status' => false, 'message' => 'Failed to update role']);
            }
            exit();
            
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => false, 'message' => 'Invalid ID']);
                exit();
            }
            
            // Check if role is in use
            $check = $conn->prepare("SELECT COUNT(*) as count FROM admin_user WHERE role_id = ? AND is_deleted = 0");
            $check->bind_param('i', $id);
            $check->execute();
            $result = $check->get_result()->fetch_assoc();
            if ($result['count'] > 0) {
                echo json_encode(['status' => false, 'message' => 'Cannot delete role - it is assigned to ' . $result['count'] . ' user(s)']);
                exit();
            }
            
            $stmt = $conn->prepare("DELETE FROM admin_user_role WHERE id = ?");
            $stmt->bind_param('i', $id);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => true, 'message' => 'Role deleted successfully']);
            } else {
                echo json_encode(['status' => false, 'message' => 'Failed to delete role']);
            }
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Roles - Admin Panel</title>
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
            cursor: pointer; transition: all 0.3s ease;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(230, 119, 83, 0.4); }
        
        .roles-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        
        .role-card {
            background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px; padding: 25px; transition: all 0.3s ease;
        }
        .role-card:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3); }
        
        .role-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .role-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .role-icon.super { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .role-icon.admin { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .role-icon.default { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
        
        .role-name { font-size: 18px; font-weight: 600; color: #f1f5f9; margin-bottom: 5px; }
        .role-id { font-size: 12px; color: #64748b; }
        
        .role-actions { display: flex; gap: 8px; }
        .btn-icon { width: 36px; height: 36px; border-radius: 8px; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; font-size: 14px; }
        .btn-edit { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .btn-edit:hover { background: rgba(59, 130, 246, 0.25); }
        .btn-delete { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .btn-delete:hover { background: rgba(239, 68, 68, 0.25); }
        
        /* Modal Styles */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); display: none; align-items: center; justify-content: center; z-index: 2000; padding: 20px; }
        .modal-overlay.show { display: flex; }
        .modal { background: #1e293b; border-radius: 16px; width: 100%; max-width: 400px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
        .modal-title { font-size: 18px; font-weight: 600; color: #f1f5f9; }
        .modal-close { width: 36px; height: 36px; border-radius: 8px; border: none; background: rgba(255, 255, 255, 0.05); color: #94a3b8; cursor: pointer; font-size: 18px; }
        .modal-body { padding: 25px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; color: #94a3b8; font-size: 13px; font-weight: 500; margin-bottom: 8px; }
        .form-input { width: 100%; padding: 12px 16px; background: rgba(15, 23, 42, 0.6); border: 1.5px solid rgba(255, 255, 255, 0.1); border-radius: 10px; font-size: 14px; color: #e2e8f0; }
        .form-input:focus { outline: none; border-color: #e67753; box-shadow: 0 0 0 3px rgba(230, 119, 83, 0.12); }
        .modal-footer { display: flex; justify-content: flex-end; gap: 12px; padding: 20px 25px; border-top: 1px solid rgba(255, 255, 255, 0.08); }
        .btn-cancel { padding: 10px 20px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #94a3b8; font-size: 14px; cursor: pointer; }
        .btn-submit { padding: 10px 24px; background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%); border: none; border-radius: 8px; color: white; font-size: 14px; font-weight: 600; cursor: pointer; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; grid-column: 1 / -1; }
        
        .toast { position: fixed; bottom: 30px; right: 30px; padding: 16px 24px; border-radius: 10px; font-size: 14px; font-weight: 500; z-index: 3000; transform: translateX(150%); transition: transform 0.3s ease; }
        .toast.show { transform: translateX(0); }
        .toast.success { background: rgba(34, 197, 94, 0.9); color: white; }
        .toast.error { background: rgba(239, 68, 68, 0.9); color: white; }
    </style>
</head>
<body>
    <?php include('../components/sidebar.php'); ?>
    
    <main class="admin-main">
        <div class="page-content">
            <div class="page-header">
                <h1 class="page-title">Admin Roles</h1>
                <button class="btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Add Role</button>
            </div>
            
            <div class="roles-grid" id="roles-container">
                <div class="empty-state"><i class="fas fa-spinner fa-spin" style="font-size: 32px;"></i><p>Loading...</p></div>
            </div>
        </div>
    </main>
    
    <div class="modal-overlay" id="roleModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Add Role</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="roleForm">
                <div class="modal-body">
                    <input type="hidden" id="roleId" name="id">
                    <div class="form-group">
                        <label class="form-label">Role Name *</label>
                        <input type="text" class="form-input" id="roleName" name="role_name" required placeholder="e.g., Manager, Editor">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Save Role</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Delete</h3>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body"><p style="color: #94a3b8;">Are you sure you want to delete this role?</p><input type="hidden" id="deleteRoleId"></div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button type="button" class="btn-submit" style="background: #ef4444;" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() { loadRoles(); });
        
        function loadRoles() {
            $.post('', { action: 'get_all' }, function(response) {
                if (response.status && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(role => {
                        let iconClass = role.id == 1 ? 'super' : (role.id == 2 ? 'admin' : 'default');
                        let icon = role.id == 1 ? 'fa-crown' : (role.id == 2 ? 'fa-user-shield' : 'fa-user');
                        html += `
                            <div class="role-card">
                                <div class="role-header">
                                    <div class="role-icon ${iconClass}"><i class="fas ${icon}"></i></div>
                                    <div class="role-actions">
                                        <button class="btn-icon btn-edit" onclick='editRole(${JSON.stringify(role)})'><i class="fas fa-edit"></i></button>
                                        <button class="btn-icon btn-delete" onclick="deleteRole(${role.id})"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                                <div class="role-name">${escapeHtml(role.role_name)}</div>
                                <div class="role-id">ID: ${role.id}</div>
                            </div>
                        `;
                    });
                    $('#roles-container').html(html);
                } else {
                    $('#roles-container').html('<div class="empty-state"><i class="fas fa-user-tag" style="font-size: 48px; opacity: 0.5;"></i><p>No roles found</p></div>');
                }
            }, 'json');
        }
        
        function openModal() { $('#modalTitle').text('Add Role'); $('#roleForm')[0].reset(); $('#roleId').val(''); $('#roleModal').addClass('show'); }
        function closeModal() { $('#roleModal').removeClass('show'); }
        function editRole(role) { $('#modalTitle').text('Edit Role'); $('#roleId').val(role.id); $('#roleName').val(role.role_name); $('#roleModal').addClass('show'); }
        function deleteRole(id) { $('#deleteRoleId').val(id); $('#deleteModal').addClass('show'); }
        function closeDeleteModal() { $('#deleteModal').removeClass('show'); }
        
        function confirmDelete() {
            $.post('', { action: 'delete', id: $('#deleteRoleId').val() }, function(response) {
                closeDeleteModal();
                showToast(response.message, response.status ? 'success' : 'error');
                if (response.status) loadRoles();
            }, 'json');
        }
        
        $('#roleForm').on('submit', function(e) {
            e.preventDefault();
            const action = $('#roleId').val() ? 'update' : 'create';
            $.post('', { action: action, ...Object.fromEntries(new FormData(this)) }, function(response) {
                if (response.status) { closeModal(); loadRoles(); }
                showToast(response.message, response.status ? 'success' : 'error');
            }, 'json');
        });
        
        function showToast(msg, type) { const t = $('#toast'); t.text(msg).removeClass('success error').addClass(type + ' show'); setTimeout(() => t.removeClass('show'), 3000); }
        function escapeHtml(text) { return text ? text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') : ''; }
    </script>
</body>
</html>
