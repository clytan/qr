<?php
/**
 * Export Database Schema and Table References
 * Access: http://localhost:8000/user/src/backend/export_database_schema.php
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('dbconfig/connection.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Schema Export</title>
    <style>
        body { font-family: 'Courier New', monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #4fc3f7; border-bottom: 2px solid #4fc3f7; padding-bottom: 10px; }
        h2 { color: #81c784; margin-top: 30px; }
        h3 { color: #ffb74d; }
        pre { background: #2d2d2d; padding: 15px; border-radius: 5px; overflow-x: auto; border-left: 4px solid #4fc3f7; }
        .table-box { background: #2d2d2d; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #81c784; }
        .success { color: #81c784; }
        .error { color: #e57373; }
        .info { color: #4fc3f7; }
        .copy-btn { background: #4fc3f7; color: #1e1e1e; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px; margin: 10px 0; }
        .copy-btn:hover { background: #81c784; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #444; }
        th { background: #333; color: #4fc3f7; }
        tr:hover { background: #333; }
    </style>
    <script>
        function copyToClipboard(elementId) {
            const text = document.getElementById(elementId).innerText;
            navigator.clipboard.writeText(text).then(() => {
                alert('✅ Copied to clipboard!');
            });
        }
    </script>
</head>
<body>
    <div class='container'>
        <h1>📊 Database Schema Export</h1>";

// Get database name
$dbName = $conn->query("SELECT DATABASE()")->fetch_row()[0];
echo "<p class='info'><strong>Database:</strong> $dbName</p>";
echo "<p class='info'><strong>Server:</strong> " . $conn->host_info . "</p>";

// Get all tables
echo "<h2>📋 All Tables in Database</h2>";
$tablesResult = $conn->query("SHOW TABLES");
$tables = [];

echo "<table>";
echo "<tr><th>#</th><th>Table Name</th><th>Rows</th></tr>";
$index = 1;
while ($row = $tablesResult->fetch_row()) {
    $tableName = $row[0];
    $tables[] = $tableName;
    
    // Get row count
    $countResult = $conn->query("SELECT COUNT(*) as count FROM `$tableName`");
    $count = $countResult->fetch_assoc()['count'];
    
    echo "<tr><td>$index</td><td><strong>$tableName</strong></td><td>$count rows</td></tr>";
    $index++;
}
echo "</table>";

echo "<p class='success'>Total Tables: " . count($tables) . "</p>";

// Export each table structure
echo "<h2>🔧 Table Structures (CREATE TABLE Statements)</h2>";
echo "<button class='copy-btn' onclick='copyToClipboard(\"all-creates\")'>📋 Copy All CREATE Statements</button>";

echo "<div id='all-creates'>";
foreach ($tables as $tableName) {
    $createResult = $conn->query("SHOW CREATE TABLE `$tableName`");
    $createRow = $createResult->fetch_assoc();
    $createStatement = $createRow['Create Table'];
    
    echo "\n-- Table: $tableName\n";
    echo $createStatement . ";\n\n";
}
echo "</div>";

echo "<pre id='all-creates-display'>";
foreach ($tables as $tableName) {
    $createResult = $conn->query("SHOW CREATE TABLE `$tableName`");
    $createRow = $createResult->fetch_assoc();
    $createStatement = $createRow['Create Table'];
    
    echo "\n-- ========================================\n";
    echo "-- Table: $tableName\n";
    echo "-- ========================================\n";
    echo htmlspecialchars($createStatement) . ";\n\n";
}
echo "</pre>";

// Show detailed structure for each table
echo "<h2>📝 Detailed Column Information</h2>";
foreach ($tables as $tableName) {
    echo "<div class='table-box'>";
    echo "<h3>Table: $tableName</h3>";
    
    // Get columns
    $columnsResult = $conn->query("SHOW COLUMNS FROM `$tableName`");
    
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($col = $columnsResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Get indexes
    $indexResult = $conn->query("SHOW INDEXES FROM `$tableName`");
    if ($indexResult->num_rows > 0) {
        echo "<h4>Indexes:</h4>";
        echo "<table>";
        echo "<tr><th>Key Name</th><th>Column</th><th>Unique</th></tr>";
        while ($idx = $indexResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($idx['Key_name']) . "</td>";
            echo "<td>" . htmlspecialchars($idx['Column_name']) . "</td>";
            echo "<td>" . ($idx['Non_unique'] == 0 ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "</div>";
}

// Show community-related tables specifically
echo "<h2>🏘️ Community-Related Tables</h2>";
$communityTables = [];
foreach ($tables as $table) {
    if (stripos($table, 'community') !== false) {
        $communityTables[] = $table;
    }
}

if (count($communityTables) > 0) {
    echo "<p class='success'>Found " . count($communityTables) . " community-related tables:</p>";
    echo "<ul>";
    foreach ($communityTables as $table) {
        echo "<li><strong>$table</strong></li>";
        
        // Show sample data
        $sampleResult = $conn->query("SELECT * FROM `$table` LIMIT 5");
        if ($sampleResult && $sampleResult->num_rows > 0) {
            echo "<p>Sample data (first 5 rows):</p>";
            echo "<table>";
            
            // Headers
            $fields = $sampleResult->fetch_fields();
            echo "<tr>";
            foreach ($fields as $field) {
                echo "<th>" . htmlspecialchars($field->name) . "</th>";
            }
            echo "</tr>";
            
            // Data
            while ($row = $sampleResult->fetch_assoc()) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='info'>Table is empty</p>";
        }
    }
    echo "</ul>";
} else {
    echo "<p class='error'>⚠️ No community tables found! You may need to create them.</p>";
}

// Check for user table
echo "<h2>👥 User Table Structure</h2>";
if (in_array('user_user', $tables)) {
    echo "<p class='success'>✓ user_user table exists</p>";
    
    // Check if community_id column exists
    $columnsResult = $conn->query("SHOW COLUMNS FROM user_user");
    $hasCommunityId = false;
    while ($col = $columnsResult->fetch_assoc()) {
        if ($col['Field'] == 'community_id') {
            $hasCommunityId = true;
            break;
        }
    }
    
    if ($hasCommunityId) {
        echo "<p class='success'>✓ community_id column exists in user_user</p>";
    } else {
        echo "<p class='error'>✗ community_id column NOT found in user_user table</p>";
        echo "<p>You may need to add it with:</p>";
        echo "<pre>ALTER TABLE user_user ADD COLUMN community_id INT NULL AFTER user_slab_id;</pre>";
    }
} else {
    echo "<p class='error'>✗ user_user table not found!</p>";
}

echo "<hr style='border-color: #444; margin: 40px 0;'>";
echo "<h2>📋 Quick Copy Sections</h2>";

echo "<h3>Community Tables Only:</h3>";
echo "<button class='copy-btn' onclick='copyToClipboard(\"community-only\")'>📋 Copy Community Table Structures</button>";
echo "<pre id='community-only'>";
foreach ($communityTables as $tableName) {
    $createResult = $conn->query("SHOW CREATE TABLE `$tableName`");
    $createRow = $createResult->fetch_assoc();
    echo htmlspecialchars($createRow['Create Table']) . ";\n\n";
}
echo "</pre>";

echo "</div></body></html>";

$conn->close();
?>
