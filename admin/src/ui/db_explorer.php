<?php
// Include auth check
require_once('../components/auth_check.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>DB Explorer - Admin Panel</title>
    <link rel="icon" href="../../assets/logo2.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html { background: #0f172a; min-height: 100vh; font-family: 'Inter', sans-serif; color: #e2e8f0; }
        
        .page-content { padding: 30px; }
        .page-header { margin-bottom: 30px; }
        .page-title { font-size: 28px; font-weight: 700; color: #f1f5f9; margin-bottom: 8px; }
        .page-subtitle { color: #94a3b8; font-size: 14px; }
        
        /* Stats row */
        .stats-row { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat-box {
            background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px; padding: 20px; min-width: 150px;
        }
        .stat-value { font-size: 28px; font-weight: 700; color: #f1f5f9; }
        .stat-label { font-size: 13px; color: #64748b; margin-top: 5px; }
        
        /* Tables grid */
        .tables-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-bottom: 30px; }
        
        .table-card {
            background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px; padding: 20px; cursor: pointer; transition: all 0.3s ease;
        }
        .table-card:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3); border-color: rgba(230, 119, 83, 0.3); }
        .table-card.active { border-color: #e67753; background: rgba(230, 119, 83, 0.1); }
        
        .table-card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
        .table-icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(59, 130, 246, 0.15); color: #60a5fa; display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .table-name { font-size: 15px; font-weight: 600; color: #f1f5f9; word-break: break-all; }
        .table-rows { font-size: 12px; color: #64748b; margin-top: 8px; }
        .table-rows span { color: #4ade80; font-weight: 600; }
        
        /* Structure panel */
        .structure-panel {
            background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px; overflow: hidden;
        }
        
        .panel-header {
            padding: 20px; background: rgba(15, 23, 42, 0.5);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
        }
        .panel-title { font-size: 18px; font-weight: 600; color: #f1f5f9; display: flex; align-items: center; gap: 10px; }
        .panel-title i { color: #e67753; }
        
        .panel-body { padding: 20px; }
        
        /* Tabs */
        .tabs { display: flex; gap: 10px; }
        .tab-btn {
            padding: 8px 16px; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.1);
            background: transparent; color: #94a3b8; font-size: 13px; cursor: pointer; transition: all 0.2s ease;
        }
        .tab-btn:hover { background: rgba(255, 255, 255, 0.05); }
        .tab-btn.active { background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%); color: white; border-color: transparent; }
        
        /* Table styles */
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        
        table { width: 100%; border-collapse: collapse; min-width: 500px; }
        th { background: rgba(15, 23, 42, 0.5); padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
        td { padding: 14px 16px; border-top: 1px solid rgba(255, 255, 255, 0.05); font-size: 13px; color: #e2e8f0; }
        tr:hover td { background: rgba(255, 255, 255, 0.02); }
        
        .badge { display: inline-flex; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
        .badge.primary { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .badge.success { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
        .badge.warning { background: rgba(234, 179, 8, 0.15); color: #fbbf24; }
        .badge.secondary { background: rgba(148, 163, 184, 0.15); color: #94a3b8; }
        
        .type-tag { font-family: 'Fira Code', monospace; font-size: 12px; color: #f472b6; background: rgba(244, 114, 182, 0.1); padding: 3px 8px; border-radius: 4px; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
        .empty-state i { font-size: 48px; opacity: 0.5; margin-bottom: 15px; display: block; }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        /* Sample data scrollable */
        .sample-data-wrapper { max-height: 400px; overflow: auto; }
        
        /* Truncate long values */
        .truncate { max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        /* Mobile adjustments */
        @media (max-width: 768px) {
            .page-content { padding: 20px 15px; }
            .tables-grid { grid-template-columns: 1fr; }
            .stats-row { flex-direction: column; }
            .stat-box { width: 100%; }
            .panel-header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <?php include('../components/sidebar.php'); ?>
    
    <main class="admin-main">
        <div class="page-content">
            <div class="page-header">
                <h1 class="page-title">Database Explorer</h1>
                <p class="page-subtitle">Browse and explore the database structure</p>
            </div>
            
            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-value" id="total-tables">-</div>
                    <div class="stat-label">Total Tables</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value" id="total-rows">-</div>
                    <div class="stat-label">Total Rows</div>
                </div>
            </div>
            
            <!-- Tables Grid -->
            <h3 style="font-size: 16px; color: #f1f5f9; margin-bottom: 15px;">Select a Table</h3>
            <div class="tables-grid" id="tables-grid">
                <div class="empty-state"><i class="fas fa-spinner fa-spin"></i>Loading tables...</div>
            </div>
            
            <!-- Structure Panel -->
            <div class="structure-panel" id="structure-panel" style="display: none;">
                <div class="panel-header">
                    <div class="panel-title">
                        <i class="fas fa-table"></i>
                        <span id="selected-table-name">Table Structure</span>
                    </div>
                    <div class="tabs">
                        <button class="tab-btn active" onclick="showTab('columns')">Columns</button>
                        <button class="tab-btn" onclick="showTab('data')">Sample Data</button>
                    </div>
                </div>
                <div class="panel-body">
                    <!-- Columns Tab -->
                    <div class="tab-content active" id="tab-columns">
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Type</th>
                                        <th>Null</th>
                                        <th>Key</th>
                                        <th>Default</th>
                                        <th>Extra</th>
                                    </tr>
                                </thead>
                                <tbody id="columns-body"></tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Sample Data Tab -->
                    <div class="tab-content" id="tab-data">
                        <div class="sample-data-wrapper">
                            <div class="table-responsive">
                                <table id="data-table">
                                    <thead id="data-head"></thead>
                                    <tbody id="data-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentTable = null;
        
        $(document).ready(function() {
            loadTables();
        });
        
        function loadTables() {
            $.get('../backend/get_db_structure.php', { action: 'get_tables' }, function(response) {
                if (response.status && response.data.length > 0) {
                    let html = '';
                    let totalRows = 0;
                    
                    response.data.forEach(table => {
                        totalRows += parseInt(table.row_count);
                        html += `
                            <div class="table-card" onclick="loadStructure('${escapeHtml(table.name)}', this)">
                                <div class="table-card-header">
                                    <div class="table-icon"><i class="fas fa-table"></i></div>
                                    <div class="table-name">${escapeHtml(table.name)}</div>
                                </div>
                                <div class="table-rows"><span>${table.row_count}</span> rows</div>
                            </div>
                        `;
                    });
                    
                    $('#tables-grid').html(html);
                    $('#total-tables').text(response.data.length);
                    $('#total-rows').text(totalRows.toLocaleString());
                } else {
                    $('#tables-grid').html('<div class="empty-state"><i class="fas fa-database"></i>No tables found</div>');
                }
            }, 'json');
        }
        
        function loadStructure(tableName, element) {
            // Update active state
            $('.table-card').removeClass('active');
            $(element).addClass('active');
            currentTable = tableName;
            
            $('#selected-table-name').text(tableName);
            $('#structure-panel').show();
            
            // Reset tabs
            showTab('columns');
            
            $.get('../backend/get_db_structure.php', { action: 'get_structure', table: tableName }, function(response) {
                if (response.status) {
                    // Populate columns
                    let columnsHtml = '';
                    response.data.columns.forEach(col => {
                        let keyBadge = '';
                        if (col.key === 'PRI') keyBadge = '<span class="badge primary">PRI</span>';
                        else if (col.key === 'MUL') keyBadge = '<span class="badge warning">MUL</span>';
                        else if (col.key === 'UNI') keyBadge = '<span class="badge success">UNI</span>';
                        
                        columnsHtml += `
                            <tr>
                                <td><strong>${escapeHtml(col.field)}</strong></td>
                                <td><span class="type-tag">${escapeHtml(col.type)}</span></td>
                                <td>${col.null === 'YES' ? '<span class="badge secondary">YES</span>' : 'NO'}</td>
                                <td>${keyBadge}</td>
                                <td>${col.default !== null ? escapeHtml(col.default) : '<span style="color:#64748b">NULL</span>'}</td>
                                <td>${col.extra ? escapeHtml(col.extra) : '-'}</td>
                            </tr>
                        `;
                    });
                    $('#columns-body').html(columnsHtml);
                    
                    // Populate sample data
                    if (response.data.sample_data.length > 0) {
                        let headHtml = '<tr>';
                        Object.keys(response.data.sample_data[0]).forEach(key => {
                            headHtml += `<th>${escapeHtml(key)}</th>`;
                        });
                        headHtml += '</tr>';
                        $('#data-head').html(headHtml);
                        
                        let bodyHtml = '';
                        response.data.sample_data.forEach(row => {
                            bodyHtml += '<tr>';
                            Object.values(row).forEach(val => {
                                let displayVal = val !== null ? String(val) : '<span style="color:#64748b">NULL</span>';
                                if (displayVal.length > 50) displayVal = displayVal.substring(0, 50) + '...';
                                bodyHtml += `<td class="truncate">${escapeHtml(displayVal)}</td>`;
                            });
                            bodyHtml += '</tr>';
                        });
                        $('#data-body').html(bodyHtml);
                    } else {
                        $('#data-head').html('');
                        $('#data-body').html('<tr><td colspan="10" style="text-align:center;color:#64748b;">No data in table</td></tr>');
                    }
                }
            }, 'json');
        }
        
        function showTab(tabName) {
            $('.tab-btn').removeClass('active');
            $('.tab-content').removeClass('active');
            $(`.tab-btn:contains('${tabName === 'columns' ? 'Columns' : 'Sample Data'}')`).addClass('active');
            $(`#tab-${tabName}`).addClass('active');
        }
        
        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    </script>
</body>
</html>
