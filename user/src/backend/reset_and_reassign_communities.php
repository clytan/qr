<?php
/**
 * Reset and Reassign All Communities
 * 
 * WARNING: This will:
 * 1. Remove all community assignments from users
 * 2. Clear all community_members entries
 * 3. Reset community member counts
 * 4. Delete all existing communities (optional)
 * 5. Reassign all users to fresh communities (100 per community)
 * 
 * Use this for:
 * - Testing the auto-assignment logic
 * - Resetting after data corruption
 * - Starting fresh with new community structure
 */

// Increase execution time for large datasets
set_time_limit(300); // 5 minutes

// Include database connection
require_once 'dbconfig/connection.php';
require_once 'auto_community_helper.php';

// Initialize variables
$resetComplete = false;
$stats = [];
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Step 1: Get statistics BEFORE reset
        $beforeStats = [
            'total_users' => 0,
            'assigned_users' => 0,
            'total_communities' => 0,
            'community_members_count' => 0
        ];
        
        $result = $conn->query("SELECT COUNT(*) as count FROM user_user WHERE is_deleted = 0");
        $beforeStats['total_users'] = $result->fetch_assoc()['count'];
        
        $result = $conn->query("SELECT COUNT(*) as count FROM user_user WHERE community_id IS NOT NULL AND is_deleted = 0");
        $beforeStats['assigned_users'] = $result->fetch_assoc()['count'];
        
        $result = $conn->query("SELECT COUNT(*) as count FROM community WHERE is_deleted = 0");
        $beforeStats['total_communities'] = $result->fetch_assoc()['count'];
        
        $result = $conn->query("SELECT COUNT(*) as count FROM community_members WHERE is_deleted = 0");
        $beforeStats['community_members_count'] = $result->fetch_assoc()['count'];
        
        // Step 2: Remove all user community assignments (keep users 1 and 2)
        $sql = "UPDATE user_user SET community_id = NULL WHERE is_deleted = 0 AND id NOT IN (1, 2)";
        if (!$conn->query($sql)) {
            throw new Exception("Failed to clear user community assignments: " . $conn->error);
        }
        $clearedUsers = $conn->affected_rows;
        
        // Step 3: HARD DELETE all community chat reactions first (to avoid FK constraint)
        $sql = "DELETE FROM community_reactions";
        if (!$conn->query($sql)) {
            throw new Exception("Failed to clear community reactions: " . $conn->error);
        }
        $clearedReactions = $conn->affected_rows;
        
        // Step 4: HARD DELETE reported messages (if exists)
        $clearedReports = 0;
        $result = $conn->query("SHOW TABLES LIKE 'reported_messages'");
        if ($result && $result->num_rows > 0) {
            $sql = "DELETE FROM reported_messages";
            if ($conn->query($sql)) {
                $clearedReports = $conn->affected_rows;
            }
        }
        
        // Step 5: HARD DELETE all community chat messages
        $sql = "DELETE FROM community_chat";
        if (!$conn->query($sql)) {
            throw new Exception("Failed to clear community chat: " . $conn->error);
        }
        $clearedChats = $conn->affected_rows;
        
        // Step 6: HARD DELETE all user_roles FIRST (has FK to community)
        $sql = "DELETE FROM user_roles WHERE community_id IS NOT NULL";
        if (!$conn->query($sql)) {
            throw new Exception("Failed to clear user roles: " . $conn->error);
        }
        $clearedRoles = $conn->affected_rows;
        
        // Step 7: HARD DELETE user penalties (has FK to community)
        $sql = "DELETE FROM user_penalties";
        if (!$conn->query($sql)) {
            throw new Exception("Failed to clear user penalties: " . $conn->error);
        }
        $clearedPenalties = $conn->affected_rows;
        
        // Step 8: HARD DELETE all community_members entries
        $sql = "DELETE FROM community_members";
        if (!$conn->query($sql)) {
            throw new Exception("Failed to clear community members: " . $conn->error);
        }
        $clearedMembers = $conn->affected_rows;
        
        // Step 9: Reset or delete all communities
        if (isset($_POST['delete_communities']) && $_POST['delete_communities'] === 'yes') {
            // HARD DELETE all communities (permanently remove from database)
            $sql = "DELETE FROM community";
            if (!$conn->query($sql)) {
                throw new Exception("Failed to delete communities: " . $conn->error);
            }
            $deletedCommunities = $conn->affected_rows;
            $stats['deleted_communities'] = $deletedCommunities;
        } else {
            // Just reset member counts
            $sql = "UPDATE community SET current_members = 0, is_full = 0, updated_on = NOW(3) WHERE is_deleted = 0";
            if (!$conn->query($sql)) {
                throw new Exception("Failed to reset community counts: " . $conn->error);
            }
            $resetCommunities = $conn->affected_rows;
            $stats['reset_communities'] = $resetCommunities;
        }
        
        // Step 10: Reassign all users using the migration function
        $reassignResult = migrateExistingUsersToCommunities($conn);
        
        // Commit transaction
        $conn->commit();
        
        // Get statistics AFTER reset
        $afterStats = getCommunityStats($conn);
        
        // Compile results
        $stats = [
            'before' => $beforeStats,
            'cleared_users' => $clearedUsers,
            'cleared_reactions' => $clearedReactions,
            'cleared_reports' => $clearedReports,
            'cleared_chats' => $clearedChats,
            'cleared_penalties' => $clearedPenalties,
            'cleared_members' => $clearedMembers,
            'cleared_roles' => $clearedRoles,
            'reassign_result' => $reassignResult,
            'after' => $afterStats
        ];
        
        $resetComplete = true;
        
    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = $e->getMessage();
    }
}

// Get current statistics for display
$currentStats = [
    'total_users' => 0,
    'assigned_users' => 0,
    'unassigned_users' => 0,
    'total_communities' => 0,
    'community_members_count' => 0
];

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM user_user WHERE is_deleted = 0");
    $currentStats['total_users'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM user_user WHERE community_id IS NOT NULL AND is_deleted = 0");
    $currentStats['assigned_users'] = $result->fetch_assoc()['count'];
    
    $currentStats['unassigned_users'] = $currentStats['total_users'] - $currentStats['assigned_users'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM community WHERE is_deleted = 0");
    $currentStats['total_communities'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM community_members WHERE is_deleted = 0");
    $currentStats['community_members_count'] = $result->fetch_assoc()['count'];
    
    $communityDetails = getCommunityStats($conn);
} catch (Exception $e) {
    $errors[] = "Failed to fetch current statistics: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset & Reassign Communities</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 5px;
        }
        
        .warning-box h3 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .warning-box ul {
            color: #856404;
            margin-left: 20px;
        }
        
        .warning-box li {
            margin: 5px 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 36px;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .community-list {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .community-item {
            background: white;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .community-item:last-child {
            margin-bottom: 0;
        }
        
        .community-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-full {
            background: #dc3545;
            color: white;
        }
        
        .badge-available {
            background: #28a745;
            color: white;
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #333;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(245, 87, 108, 0.3);
        }
        
        .btn-danger:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .success-box h3 {
            color: #155724;
            margin-bottom: 15px;
        }
        
        .error-box {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .error-box h3 {
            color: #721c24;
            margin-bottom: 10px;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .results-table th,
        .results-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .results-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .results-table tr:hover {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 Reset & Reassign Communities</h1>
            <p>Clear all community assignments and reassign all users from scratch</p>
        </div>
        
        <div class="content">
            <?php if (!empty($errors)): ?>
                <div class="error-box">
                    <h3>❌ Errors Occurred</h3>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($resetComplete): ?>
                <div class="success-box">
                    <h3>✅ Reset & Reassignment Complete!</h3>
                    
                    <h4 style="margin-top: 20px; margin-bottom: 10px; color: #155724;">Summary:</h4>
                    <table class="results-table">
                        <tr>
                            <th>Operation</th>
                            <th>Count</th>
                        </tr>
                        <tr>
                            <td>Users Cleared</td>
                            <td><?php echo $stats['cleared_users']; ?></td>
                        </tr>
                        <tr>
                            <td>Chat Reactions Removed</td>
                            <td><?php echo $stats['cleared_reactions']; ?></td>
                        </tr>
                        <tr>
                            <td>Reported Messages Removed</td>
                            <td><?php echo $stats['cleared_reports']; ?></td>
                        </tr>
                        <tr>
                            <td>Chat Messages Removed</td>
                            <td><?php echo $stats['cleared_chats']; ?></td>
                        </tr>
                        <tr>
                            <td>User Penalties Removed</td>
                            <td><?php echo $stats['cleared_penalties']; ?></td>
                        </tr>
                        <tr>
                            <td>Community Members Removed</td>
                            <td><?php echo $stats['cleared_members']; ?></td>
                        </tr>
                        <tr>
                            <td>User Roles Cleared</td>
                            <td><?php echo $stats['cleared_roles']; ?></td>
                        </tr>
                        <?php if (isset($stats['deleted_communities'])): ?>
                        <tr>
                            <td>Communities Deleted</td>
                            <td><?php echo $stats['deleted_communities']; ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (isset($stats['reset_communities'])): ?>
                        <tr>
                            <td>Communities Reset</td>
                            <td><?php echo $stats['reset_communities']; ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr style="background: #d4edda; font-weight: bold;">
                            <td>Users Reassigned</td>
                            <td><?php echo $stats['reassign_result']['migrated']; ?></td>
                        </tr>
                    </table>
                    
                    <?php if (!empty($stats['after'])): ?>
                        <h4 style="margin-top: 20px; margin-bottom: 10px; color: #155724;">New Community Distribution:</h4>
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>Community</th>
                                    <th>Members</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['after'] as $community): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($community['name']); ?></td>
                                        <td><?php echo $community['actual_member_count']; ?> / 100</td>
                                        <td>
                                            <span class="community-badge <?php echo $community['is_full'] ? 'badge-full' : 'badge-available'; ?>">
                                                <?php echo $community['is_full'] ? 'FULL' : 'AVAILABLE'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                    
                    <p style="margin-top: 20px; color: #155724;">
                        <strong>✅ All users have been reassigned to communities based on their registration order!</strong>
                    </p>
                    
                    <button onclick="location.reload()" class="btn btn-danger" style="margin-top: 20px; background: #28a745;">
                        🔄 Refresh Page
                    </button>
                </div>
            <?php else: ?>
                <div class="warning-box">
                    <h3>⚠️ WARNING: This is a destructive operation!</h3>
                    <p style="margin: 10px 0;"><strong>This will:</strong></p>
                    <ul>
                        <li>Remove all community assignments from users</li>
                        <li>Clear all community chat messages and reactions</li>
                        <li>Clear all community_members entries</li>
                        <li>Clear all member roles from user_roles</li>
                        <li>Reset or delete all communities (your choice)</li>
                        <li>Reassign all users to fresh communities (100 per community)</li>
                    </ul>
                    <p style="margin-top: 15px; font-weight: bold;">
                        ⚡ This operation cannot be undone! Make sure you have a database backup.
                    </p>
                </div>
                
                <h3 style="margin-bottom: 15px;">Current Statistics:</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo $currentStats['total_users']; ?></h3>
                        <p>Total Users</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $currentStats['assigned_users']; ?></h3>
                        <p>Assigned to Communities</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $currentStats['unassigned_users']; ?></h3>
                        <p>Unassigned Users</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $currentStats['total_communities']; ?></h3>
                        <p>Total Communities</p>
                    </div>
                </div>
                
                <?php if (!empty($communityDetails)): ?>
                    <h3 style="margin-bottom: 15px;">Current Communities:</h3>
                    <div class="community-list">
                        <?php foreach ($communityDetails as $community): ?>
                            <div class="community-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($community['name']); ?></strong>
                                    <br>
                                    <small><?php echo $community['actual_member_count']; ?> members</small>
                                </div>
                                <span class="community-badge <?php echo $community['is_full'] ? 'badge-full' : 'badge-available'; ?>">
                                    <?php echo $community['is_full'] ? 'FULL' : 'AVAILABLE'; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" onsubmit="return confirm('⚠️ Are you ABSOLUTELY SURE? This will reset ALL community assignments!\n\nThis action CANNOT be undone!\n\nClick OK to proceed or Cancel to abort.');">
                    <div class="form-section">
                        <h3 style="margin-bottom: 20px;">Reset Options:</h3>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="delete_communities" name="delete_communities" value="yes" checked>
                                <label for="delete_communities" style="margin-bottom: 0;">
                                    <strong>Delete all communities</strong> (creates fresh ones during reassignment)
                                    <br>
                                    <small style="color: #666;">✅ Recommended: This will remove all 12 existing communities and auto-create Community 1, Community 2, etc.</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="confirm_checkbox" required>
                                <label for="confirm_checkbox" style="margin-bottom: 0;">
                                    <strong style="color: #dc3545;">I understand this cannot be undone</strong>
                                </label>
                            </div>
                        </div>
                        
                        <input type="hidden" name="confirm_reset" value="1">
                        <button type="submit" class="btn btn-danger" id="resetBtn" disabled>
                            🗑️ RESET & REASSIGN ALL COMMUNITIES
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Enable button only when confirmation checkbox is checked
        const confirmCheckbox = document.getElementById('confirm_checkbox');
        const resetBtn = document.getElementById('resetBtn');
        
        if (confirmCheckbox && resetBtn) {
            confirmCheckbox.addEventListener('change', function() {
                resetBtn.disabled = !this.checked;
            });
        }
    </script>
</body>
</html>
