<?php
// admin/src/ui/audit_logs.php
require_once '../components/auth_check.php';
require_once '../backend/dbconfig/connection.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';

$whereClause = "WHERE 1=1";
if ($typeFilter) {
    $whereClause .= " AND action_type = '" . $conn->real_escape_string($typeFilter) . "'";
}

// Get Total Count
$countSql = "SELECT COUNT(*) as total FROM admin_audit_logs $whereClause";
$countResult = $conn->query($countSql);
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Get Logs
$sql = "SELECT al.*, au.user_name 
        FROM admin_audit_logs al 
        LEFT JOIN admin_user au ON al.admin_id = au.id 
        $whereClause 
        ORDER BY al.created_on DESC 
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Audit Logs - Admin Panel</title>
    <link rel="icon" href="../../../logo/logo.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body, html {
            background: #0f172a;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            color: #e2e8f0;
            margin: 0;
            padding: 0;
        }
        
        .page-content { padding: 30px; }
        .page-header { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .page-title { font-size: 28px; font-weight: 700; color: #f1f5f9; }
        .page-subtitle { color: #94a3b8; font-size: 14px; margin-top: 5px; }

        .log-table-wrapper {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; }
        th { 
            background: rgba(30, 41, 59, 0.8);
            color: #94a3b8;
            font-weight: 600;
            text-align: left;
            padding: 15px 20px;
            font-size: 13px;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        td {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 14px;
            color: #cbd5e1;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,0.02); }

        .badge-admin {
            background: rgba(233, 67, 122, 0.15);
            color: #E9437A;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-action {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        .pagination {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        .page-link {
            padding: 8px 12px;
            background: rgba(255,255,255,0.05);
            border-radius: 6px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 13px;
        }
        .page-link.active {
            background: #E9437A;
            color: white;
        }

        .filter-select {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255,255,255,0.1);
            color: #e2e8f0;
            padding: 8px 15px;
            border-radius: 8px;
            outline: none;
        }
    </style>
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="page-content">
            <div class="page-header">
                <div>
                    <h1 class="page-title"><i class="fas fa-shield-alt" style="margin-right: 10px; color: #E9437A;"></i> Security Audit Logs</h1>
                    <p class="page-subtitle">Track sensitive actions performed by admins</p>
                </div>
                <div>
                    <!-- Filter form could go here -->
                </div>
            </div>

            <div class="log-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Admin</th>
                            <th>Action Type</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="badge-admin">
                                            <i class="fas fa-user-shield" style="margin-right: 5px;"></i>
                                            <?php echo htmlspecialchars($row['user_name'] ?? 'System/Deleted'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-action"><?php echo htmlspecialchars($row['action_type']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td style="font-family: monospace; color: #94a3b8;"><?php echo htmlspecialchars($row['ip_address'] ?? '-'); ?></td>
                                    <td style="color: #94a3b8;"><?php echo date('d M Y, h:i A', strtotime($row['created_on'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: #64748b;">
                                    <i class="fas fa-folder-open" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                                    No audit logs found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Simple Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
