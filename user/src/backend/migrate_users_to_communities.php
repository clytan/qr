<?php
/**
 * Migrate Existing Users to Communities
 * Run this ONCE to assign existing users to communities
 * Access: http://localhost:8000/user/src/backend/migrate_users_to_communities.php
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('dbconfig/connection.php');
require_once('auto_community_helper.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Migrate Users to Communities</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #4CAF50; color: white; }
        tr:hover { background: #f5f5f5; }
        .btn { background: #4CAF50; color: white; border: none; padding: 15px 30px; cursor: pointer; border-radius: 5px; font-size: 16px; }
        .btn:hover { background: #45a049; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🏘️ Migrate Existing Users to Communities</h1>";

// Check if community_id column exists
$checkColumn = $conn->query("SHOW COLUMNS FROM user_user LIKE 'community_id'");
if ($checkColumn->num_rows == 0) {
    echo "<div class='error'>";
    echo "<h3>❌ Setup Required!</h3>";
    echo "<p>The <code>community_id</code> column doesn't exist in <code>user_user</code> table.</p>";
    echo "<p><strong>Run this SQL first:</strong></p>";
    echo "<pre>ALTER TABLE user_user 
ADD COLUMN community_id INT NULL AFTER user_slab_id,
ADD KEY idx_community_id (community_id),
ADD CONSTRAINT fk_user_community FOREIGN KEY (community_id) 
  REFERENCES community(id) ON DELETE SET NULL ON UPDATE CASCADE;</pre>";
    echo "</div></div></body></html>";
    exit;
}

echo "<div class='success'>✓ Database structure is ready</div>";

// Get stats before migration
$sqlStats = "SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN community_id IS NULL THEN 1 ELSE 0 END) as unassigned_users,
    SUM(CASE WHEN community_id IS NOT NULL THEN 1 ELSE 0 END) as assigned_users
FROM user_user WHERE is_deleted = 0";
$resultStats = $conn->query($sqlStats);
$stats = $resultStats->fetch_assoc();

echo "<h2>📊 Current Status</h2>";
echo "<table>";
echo "<tr><th>Metric</th><th>Count</th></tr>";
echo "<tr><td><strong>Total Active Users</strong></td><td>{$stats['total_users']}</td></tr>";
echo "<tr><td>Users Already Assigned</td><td>{$stats['assigned_users']}</td></tr>";
echo "<tr><td><strong>Users Need Assignment</strong></td><td style='color: #d9534f;'><strong>{$stats['unassigned_users']}</strong></td></tr>";
echo "</table>";

// Show community stats
echo "<h2>🏘️ Current Communities</h2>";
$communityStats = getCommunityStats($conn);

if (count($communityStats) > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Community Name</th><th>Current Members</th><th>Status</th></tr>";
    foreach ($communityStats as $community) {
        $status = $community['is_full'] ? '<span style="color: red;">FULL</span>' : '<span style="color: green;">Open</span>';
        echo "<tr>";
        echo "<td>{$community['id']}</td>";
        echo "<td><strong>{$community['name']}</strong></td>";
        echo "<td>{$community['actual_member_count']} / 100</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='info'>No communities found. They will be created automatically during migration.</div>";
}

// Migration button
if ($stats['unassigned_users'] > 0) {
    echo "<div class='info'>";
    echo "<h3>⚠️ Ready to Migrate</h3>";
    echo "<p>This will assign <strong>{$stats['unassigned_users']}</strong> users to communities (100 users per community).</p>";
    echo "<p>Users will be assigned based on their registration order (oldest first).</p>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='migrate' class='btn'>🚀 Start Migration</button>";
    echo "</form>";
    echo "</div>";
}

// Perform migration if requested
if (isset($_POST['migrate'])) {
    echo "<h2>🔄 Migration in Progress...</h2>";
    echo "<pre>";
    
    $result = migrateExistingUsersToCommunities($conn);
    
    if ($result['status']) {
        echo "✅ Migration completed!\n\n";
        echo "Users migrated: {$result['migrated_count']}\n";
        
        if (count($result['errors']) > 0) {
            echo "\n⚠️ Some errors occurred:\n";
            foreach ($result['errors'] as $error) {
                echo "  - $error\n";
            }
        }
    } else {
        echo "❌ Migration failed: {$result['error']}\n";
    }
    
    echo "</pre>";
    
    // Show updated stats
    echo "<h2>📊 Updated Status</h2>";
    $resultStats = $conn->query($sqlStats);
    $stats = $resultStats->fetch_assoc();
    
    echo "<table>";
    echo "<tr><th>Metric</th><th>Count</th></tr>";
    echo "<tr><td><strong>Total Active Users</strong></td><td>{$stats['total_users']}</td></tr>";
    echo "<tr><td>Users Assigned</td><td style='color: green;'><strong>{$stats['assigned_users']}</strong></td></tr>";
    echo "<tr><td>Users Still Unassigned</td><td>{$stats['unassigned_users']}</td></tr>";
    echo "</table>";
    
    // Show updated community stats
    echo "<h2>🏘️ Updated Communities</h2>";
    $communityStats = getCommunityStats($conn);
    echo "<table>";
    echo "<tr><th>ID</th><th>Community Name</th><th>Members</th><th>Status</th></tr>";
    foreach ($communityStats as $community) {
        $status = $community['is_full'] ? '<span style="color: red;">FULL (100/100)</span>' : '<span style="color: green;">Open</span>';
        echo "<tr>";
        echo "<td>{$community['id']}</td>";
        echo "<td><strong>{$community['name']}</strong></td>";
        echo "<td>{$community['actual_member_count']} / 100</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='success'>";
    echo "<h3>✅ Migration Complete!</h3>";
    echo "<p>All existing users have been assigned to communities.</p>";
    echo "<p><strong>From now on, new registrations will automatically be assigned to communities.</strong></p>";
    echo "</div>";
}

echo "<h2>📋 How It Works</h2>";
echo "<div class='info'>";
echo "<ul>";
echo "<li><strong>First 100 users</strong> → Community 1</li>";
echo "<li><strong>Users 101-200</strong> → Community 2</li>";
echo "<li><strong>Users 201-300</strong> → Community 3</li>";
echo "<li>And so on...</li>";
echo "</ul>";
echo "<p><strong>New registrations</strong> will automatically be assigned to the appropriate community based on total user count.</p>";
echo "</div>";

echo "</div></body></html>";

$conn->close();
?>
