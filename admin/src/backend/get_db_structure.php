<?php
/**
 * API to fetch database structure for all tables
 * Returns table names, columns, and their properties
 */
session_start();
require_once('./dbconfig/connection.php');

// Verify admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'message' => 'Not authenticated']);
    exit();
}

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : 'get_tables');

try {
    switch ($action) {
        case 'get_tables':
            // Get all tables in the database
            $result = $conn->query("SHOW TABLES");
            $tables = [];
            while ($row = $result->fetch_array()) {
                $tableName = $row[0];
                
                // Get row count for each table
                $countResult = $conn->query("SELECT COUNT(*) as count FROM `$tableName`");
                $count = $countResult ? $countResult->fetch_assoc()['count'] : 0;
                
                $tables[] = [
                    'name' => $tableName,
                    'row_count' => $count
                ];
            }
            echo json_encode(['status' => true, 'data' => $tables]);
            break;
            
        case 'get_structure':
            $table = isset($_GET['table']) ? $_GET['table'] : (isset($_POST['table']) ? $_POST['table'] : '');
            if (empty($table)) {
                echo json_encode(['status' => false, 'message' => 'Table name required']);
                exit();
            }
            
            // Sanitize table name
            $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
            
            // Get table structure
            $result = $conn->query("DESCRIBE `$table`");
            $columns = [];
            while ($row = $result->fetch_assoc()) {
                $columns[] = [
                    'field' => $row['Field'],
                    'type' => $row['Type'],
                    'null' => $row['Null'],
                    'key' => $row['Key'],
                    'default' => $row['Default'],
                    'extra' => $row['Extra']
                ];
            }
            
            // Get sample data (first 10 rows)
            $dataResult = $conn->query("SELECT * FROM `$table` LIMIT 10");
            $sampleData = [];
            if ($dataResult) {
                while ($row = $dataResult->fetch_assoc()) {
                    $sampleData[] = $row;
                }
            }
            
            echo json_encode([
                'status' => true, 
                'data' => [
                    'table' => $table,
                    'columns' => $columns,
                    'sample_data' => $sampleData
                ]
            ]);
            break;
            
        case 'get_all_structures':
            // Get complete database structure
            $tablesResult = $conn->query("SHOW TABLES");
            $allStructures = [];
            
            while ($row = $tablesResult->fetch_array()) {
                $tableName = $row[0];
                
                // Get columns
                $columnsResult = $conn->query("DESCRIBE `$tableName`");
                $columns = [];
                while ($col = $columnsResult->fetch_assoc()) {
                    $columns[] = [
                        'field' => $col['Field'],
                        'type' => $col['Type'],
                        'null' => $col['Null'],
                        'key' => $col['Key'],
                        'default' => $col['Default'],
                        'extra' => $col['Extra']
                    ];
                }
                
                // Get row count
                $countResult = $conn->query("SELECT COUNT(*) as count FROM `$tableName`");
                $count = $countResult ? $countResult->fetch_assoc()['count'] : 0;
                
                $allStructures[] = [
                    'table' => $tableName,
                    'row_count' => $count,
                    'columns' => $columns
                ];
            }
            
            echo json_encode(['status' => true, 'data' => $allStructures]);
            break;
            
        default:
            echo json_encode(['status' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
