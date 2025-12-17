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
            $sql = "SELECT au_urls.*, au.user_name, aur.role_name 
                    FROM admin_urls au_urls
                    LEFT JOIN admin_user au ON au_urls.user_id = au.id
                    LEFT JOIN admin_user_role aur ON au.role_id = aur.id
                    ORDER BY au.user_name, au_urls.allowed_urls";
            $result = $conn->query($sql);
            $urls = [];
            while ($row = $result->fetch_assoc()) {
                $urls[] = $row;
            }
            echo json_encode(['status' => true, 'data' => $urls]);
            exit();
            
        case 'get_users':
            $sql = "SELECT au.id, au.user_name, aur.role_name 
                    FROM admin_user au 
                    LEFT JOIN admin_user_role aur ON au.role_id = aur.id 
                    WHERE au.is_deleted = 0 
                    ORDER BY au.user_name";
            $result = $conn->query($sql);
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            echo json_encode(['status' => true, 'data' => $users]);
            exit();
            
        case 'get_pages':
            $result = $conn->query("SELECT * FROM admin_pages ORDER BY page_name");
            $pages = [];
            while ($row = $result->fetch_assoc()) {
                $pages[] = $row;
            }
            echo json_encode(['status' => true, 'data' => $pages]);
            exit();
            
        case 'get_user_urls':
            $user_id = intval($_POST['user_id'] ?? 0);
            $stmt = $conn->prepare("SELECT allowed_urls FROM admin_urls WHERE user_id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $urls = [];
            while ($row = $result->fetch_assoc()) {
                $urls[] = $row['allowed_urls'];
            }
            echo json_encode(['status' => true, 'data' => $urls]);
            exit();
            
        case 'save_urls':
            $user_id = intval($_POST['user_id'] ?? 0);
            $urls = isset($_POST['urls']) ? $_POST['urls'] : [];
            
            if ($user_id <= 0) {
                echo json_encode(['status' => false, 'message' => 'User is required']);
                exit();
            }
            
            // Delete existing URLs for user
            $stmt = $conn->prepare("DELETE FROM admin_urls WHERE user_id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            
            // Insert new URLs
            if (!empty($urls) && is_array($urls)) {
                $stmt = $conn->prepare("INSERT INTO admin_urls (user_id, allowed_urls) VALUES (?, ?)");
                foreach ($urls as $url) {
                    $url = trim($url);
                    if (!empty($url)) {
                        $stmt->bind_param('is', $user_id, $url);
                        $stmt->execute();
                    }
                }
            }
            
            echo json_encode(['status' => true, 'message' => 'URL permissions updated successfully']);
            exit();
            
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => false, 'message' => 'Invalid ID']);
                exit();
            }
            
            $stmt = $conn->prepare("DELETE FROM admin_urls WHERE id = ?");
            $stmt->bind_param('i', $id);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => true, 'message' => 'URL permission deleted']);
            } else {
                echo json_encode(['status' => false, 'message' => 'Failed to delete']);
            }
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>URL Permissions - Admin Panel</title>
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
        
        /* User Cards */
        .users-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
        
        .user-card {
            background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px; overflow: hidden; transition: all 0.3s ease;
        }
        .user-card:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3); }
        
        .user-card-header {
            padding: 20px; display: flex; justify-content: space-between; align-items: center;
            background: rgba(15, 23, 42, 0.5); border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .user-info { display: flex; align-items: center; gap: 12px; }
        .user-avatar {
            width: 45px; height: 45px; border-radius: 10px;
            background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 600; font-size: 18px;
        }
        .user-name { font-size: 16px; font-weight: 600; color: #f1f5f9; }
        .user-role { font-size: 12px; color: #64748b; }
        
        .url-count {
            padding: 6px 12px; background: rgba(59, 130, 246, 0.15);
            border-radius: 20px; font-size: 12px; color: #60a5fa; font-weight: 500;
        }
        
        .user-card-body { padding: 20px; }
        .url-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .url-tag {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 14px; background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px; font-size: 13px; color: #94a3b8;
        }
        .url-tag i { color: #4ade80; font-size: 10px; }
        .no-urls { color: #64748b; font-size: 13px; font-style: italic; }
        
        .user-card-footer {
            padding: 15px 20px; background: rgba(15, 23, 42, 0.3);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        .btn-edit-urls {
            width: 100%; padding: 10px; background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px;
            color: #60a5fa; font-size: 13px; font-weight: 500; cursor: pointer;
            transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-edit-urls:hover { background: rgba(59, 130, 246, 0.25); }
        
        /* Modal Styles */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); display: none; align-items: center; justify-content: center; z-index: 2000; padding: 20px; }
        .modal-overlay.show { display: flex; }
        .modal { background: #1e293b; border-radius: 16px; width: 100%; max-width: 500px; max-height: 80vh; overflow: hidden; display: flex; flex-direction: column; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
        .modal-title { font-size: 18px; font-weight: 600; color: #f1f5f9; }
        .modal-close { width: 36px; height: 36px; border-radius: 8px; border: none; background: rgba(255, 255, 255, 0.05); color: #94a3b8; cursor: pointer; font-size: 18px; }
        .modal-body { padding: 25px; overflow-y: auto; flex: 1; }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; color: #94a3b8; font-size: 13px; font-weight: 500; margin-bottom: 8px; }
        .form-select { width: 100%; padding: 12px 16px; background: rgba(15, 23, 42, 0.6); border: 1.5px solid rgba(255, 255, 255, 0.1); border-radius: 10px; font-size: 14px; color: #e2e8f0; cursor: pointer; }
        .form-select option { background: #1e293b; }
        .form-select:focus { outline: none; border-color: #e67753; }
        
        .checkbox-group { display: flex; flex-direction: column; gap: 10px; max-height: 300px; overflow-y: auto; padding: 10px; background: rgba(15, 23, 42, 0.4); border-radius: 10px; }
        .checkbox-item { display: flex; align-items: center; gap: 10px; padding: 10px; background: rgba(30, 41, 59, 0.5); border-radius: 8px; cursor: pointer; transition: all 0.2s ease; }
        .checkbox-item:hover { background: rgba(30, 41, 59, 0.8); }
        .checkbox-item input { width: 18px; height: 18px; cursor: pointer; accent-color: #e67753; }
        .checkbox-item label { cursor: pointer; font-size: 14px; color: #e2e8f0; }
        
        .modal-footer { display: flex; justify-content: flex-end; gap: 12px; padding: 20px 25px; border-top: 1px solid rgba(255, 255, 255, 0.08); }
        .btn-cancel { padding: 10px 20px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #94a3b8; font-size: 14px; cursor: pointer; }
        .btn-submit { padding: 10px 24px; background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%); border: none; border-radius: 8px; color: white; font-size: 14px; font-weight: 600; cursor: pointer; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; grid-column: 1 / -1; }
        
        .toast { position: fixed; bottom: 30px; right: 30px; padding: 16px 24px; border-radius: 10px; font-size: 14px; font-weight: 500; z-index: 3000; transform: translateX(150%); transition: transform 0.3s ease; }
        .toast.show { transform: translateX(0); }
        .toast.success { background: rgba(34, 197, 94, 0.9); color: white; }
        .toast.error { background: rgba(239, 68, 68, 0.9); color: white; }
        
        /* Available pages list */
        .available-pages { margin-top: 30px; }
        .section-title { font-size: 18px; font-weight: 600; color: #f1f5f9; margin-bottom: 15px; }
        .pages-table { background: rgba(30, 41, 59, 0.8); border-radius: 12px; overflow: hidden; }
        .pages-table table { width: 100%; border-collapse: collapse; }
        .pages-table th { background: rgba(15, 23, 42, 0.5); padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #94a3b8; text-transform: uppercase; }
        .pages-table td { padding: 14px 20px; border-top: 1px solid rgba(255, 255, 255, 0.05); font-size: 14px; }
    </style>
</head>
<body>
    <?php include('../components/sidebar.php'); ?>
    
    <main class="admin-main">
        <div class="page-content">
            <div class="page-header">
                <h1 class="page-title">URL Permissions</h1>
                <button class="btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Assign URLs</button>
            </div>
            
            <div class="users-grid" id="users-container">
                <div class="empty-state"><i class="fas fa-spinner fa-spin" style="font-size: 32px;"></i><p>Loading...</p></div>
            </div>
            
            <!-- Available Pages Reference -->
            <div class="available-pages">
                <h3 class="section-title">Available Pages</h3>
                <div class="pages-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Page Name</th>
                                <th>URL</th>
                            </tr>
                        </thead>
                        <tbody id="pages-table-body">
                            <tr><td colspan="2" style="text-align: center; color: #64748b;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Assign URLs Modal -->
    <div class="modal-overlay" id="urlModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Assign URL Permissions</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="urlForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Select Admin User *</label>
                        <select class="form-select" id="selectUser" required>
                            <option value="">Choose user...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Allowed Pages</label>
                        <div class="checkbox-group" id="pagesCheckboxes">
                            <p style="color: #64748b; font-size: 13px;">Select a user first</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Save Permissions</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let allPages = [];
        let allUsers = [];
        
        $(document).ready(function() {
            loadPages();
            loadUsers();
            loadUserUrls();
        });
        
        function loadPages() {
            $.post('', { action: 'get_pages' }, function(response) {
                if (response.status) {
                    allPages = response.data;
                    let html = '';
                    if (allPages.length > 0) {
                        allPages.forEach(page => {
                            html += `<tr><td>${escapeHtml(page.page_name)}</td><td><code style="background: rgba(15,23,42,0.6); padding: 4px 8px; border-radius: 4px; color: #60a5fa;">${escapeHtml(page.page_urls)}</code></td></tr>`;
                        });
                    } else {
                        html = '<tr><td colspan="2" style="text-align: center; color: #64748b;">No pages configured</td></tr>';
                    }
                    $('#pages-table-body').html(html);
                }
            }, 'json');
        }
        
        function loadUsers() {
            $.post('', { action: 'get_users' }, function(response) {
                if (response.status) {
                    allUsers = response.data;
                    let options = '<option value="">Choose user...</option>';
                    allUsers.forEach(user => {
                        options += `<option value="${user.id}">${escapeHtml(user.user_name)} (${escapeHtml(user.role_name || 'No role')})</option>`;
                    });
                    $('#selectUser').html(options);
                }
            }, 'json');
        }
        
        function loadUserUrls() {
            $.post('', { action: 'get_all' }, function(response) {
                if (response.status) {
                    // Group by user
                    const userUrls = {};
                    response.data.forEach(item => {
                        if (!userUrls[item.user_id]) {
                            userUrls[item.user_id] = {
                                user_name: item.user_name,
                                role_name: item.role_name,
                                urls: []
                            };
                        }
                        userUrls[item.user_id].urls.push(item.allowed_urls);
                    });
                    
                    // Also add users without any URLs
                    allUsers.forEach(user => {
                        if (!userUrls[user.id]) {
                            userUrls[user.id] = {
                                user_name: user.user_name,
                                role_name: user.role_name,
                                urls: []
                            };
                        }
                    });
                    
                    let html = '';
                    Object.keys(userUrls).forEach(userId => {
                        const user = userUrls[userId];
                        const initial = user.user_name ? user.user_name.charAt(0).toUpperCase() : 'A';
                        const urlTags = user.urls.length > 0 
                            ? user.urls.map(url => `<span class="url-tag"><i class="fas fa-circle"></i>${escapeHtml(url)}</span>`).join('')
                            : '<span class="no-urls">No URLs assigned</span>';
                        
                        html += `
                            <div class="user-card">
                                <div class="user-card-header">
                                    <div class="user-info">
                                        <div class="user-avatar">${initial}</div>
                                        <div>
                                            <div class="user-name">${escapeHtml(user.user_name || 'Unknown')}</div>
                                            <div class="user-role">${escapeHtml(user.role_name || 'No role')}</div>
                                        </div>
                                    </div>
                                    <span class="url-count">${user.urls.length} URL${user.urls.length !== 1 ? 's' : ''}</span>
                                </div>
                                <div class="user-card-body">
                                    <div class="url-list">${urlTags}</div>
                                </div>
                                <div class="user-card-footer">
                                    <button class="btn-edit-urls" onclick="editUserUrls(${userId})">
                                        <i class="fas fa-edit"></i> Edit Permissions
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    
                    if (html === '') {
                        html = '<div class="empty-state"><i class="fas fa-link" style="font-size: 48px; opacity: 0.5;"></i><p>No admin users found</p></div>';
                    }
                    
                    $('#users-container').html(html);
                }
            }, 'json');
        }
        
        function openModal() {
            $('#urlForm')[0].reset();
            $('#selectUser').val('');
            $('#pagesCheckboxes').html('<p style="color: #64748b; font-size: 13px;">Select a user first</p>');
            $('#urlModal').addClass('show');
        }
        
        function closeModal() {
            $('#urlModal').removeClass('show');
        }
        
        function editUserUrls(userId) {
            $('#selectUser').val(userId).trigger('change');
            $('#urlModal').addClass('show');
        }
        
        // When user is selected, load their current URLs and show checkboxes
        $('#selectUser').on('change', function() {
            const userId = $(this).val();
            if (!userId) {
                $('#pagesCheckboxes').html('<p style="color: #64748b; font-size: 13px;">Select a user first</p>');
                return;
            }
            
            // Get user's current URLs
            $.post('', { action: 'get_user_urls', user_id: userId }, function(response) {
                const userUrls = response.status ? response.data : [];
                
                let html = '';
                if (allPages.length > 0) {
                    allPages.forEach(page => {
                        const isChecked = userUrls.includes(page.page_urls) ? 'checked' : '';
                        html += `
                            <div class="checkbox-item">
                                <input type="checkbox" name="urls[]" value="${escapeHtml(page.page_urls)}" id="page_${page.id}" ${isChecked}>
                                <label for="page_${page.id}">${escapeHtml(page.page_name)} <small style="color: #64748b;">(${escapeHtml(page.page_urls)})</small></label>
                            </div>
                        `;
                    });
                } else {
                    html = '<p style="color: #64748b; font-size: 13px;">No pages configured in admin_pages table</p>';
                }
                $('#pagesCheckboxes').html(html);
            }, 'json');
        });
        
        $('#urlForm').on('submit', function(e) {
            e.preventDefault();
            const userId = $('#selectUser').val();
            const urls = $('input[name="urls[]"]:checked').map(function() { return $(this).val(); }).get();
            
            $.post('', { action: 'save_urls', user_id: userId, urls: urls }, function(response) {
                if (response.status) {
                    closeModal();
                    loadUserUrls();
                }
                showToast(response.message, response.status ? 'success' : 'error');
            }, 'json');
        });
        
        function showToast(msg, type) {
            const t = $('#toast');
            t.text(msg).removeClass('success error').addClass(type + ' show');
            setTimeout(() => t.removeClass('show'), 3000);
        }
        
        function escapeHtml(text) {
            return text ? text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') : '';
        }
    </script>
</body>
</html>
