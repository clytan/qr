<?php
// Include auth check
require_once('../components/auth_check.php');
require_once('../backend/dbconfig/connection.php');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'get_stats':
            $stats = [];
            
            // Monthly revenue (last 12 months)
            $revenueResult = $conn->query("
                SELECT 
                    DATE_FORMAT(created_on, '%Y-%m') as month,
                    SUM(total_amount) as revenue,
                    COUNT(*) as count
                FROM user_invoice 
                WHERE is_deleted = 0 AND status = 'Paid' 
                    AND created_on >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_on, '%Y-%m')
                ORDER BY month ASC
            ");
            $stats['monthly_revenue'] = [];
            if ($revenueResult) {
                while ($row = $revenueResult->fetch_assoc()) {
                    $stats['monthly_revenue'][] = $row;
                }
            }
            
            // Monthly user registrations (last 12 months)
            $usersResult = $conn->query("
                SELECT 
                    DATE_FORMAT(created_on, '%Y-%m') as month,
                    COUNT(*) as count
                FROM user_user 
                WHERE is_deleted = 0 
                    AND created_on >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_on, '%Y-%m')
                ORDER BY month ASC
            ");
            $stats['monthly_users'] = [];
            if ($usersResult) {
                while ($row = $usersResult->fetch_assoc()) {
                    $stats['monthly_users'][] = $row;
                }
            }
            
            // User tier distribution
            $tierResult = $conn->query("
                SELECT user_tag as tier, COUNT(*) as count
                FROM user_user 
                WHERE is_deleted = 0
                GROUP BY user_tag
            ");
            $stats['tier_distribution'] = [];
            if ($tierResult) {
                while ($row = $tierResult->fetch_assoc()) {
                    $stats['tier_distribution'][] = $row;
                }
            }
            
            // Revenue by type
            $typeResult = $conn->query("
                SELECT 
                    COALESCE(invoice_type, 'registration') as type,
                    SUM(total_amount) as revenue,
                    COUNT(*) as count
                FROM user_invoice 
                WHERE is_deleted = 0 AND status = 'Paid'
                GROUP BY invoice_type
            ");
            $stats['revenue_by_type'] = [];
            if ($typeResult) {
                while ($row = $typeResult->fetch_assoc()) {
                    $stats['revenue_by_type'][] = $row;
                }
            }
            
            // Summary stats
            $summaryResult = $conn->query("
                SELECT 
                    (SELECT COUNT(*) FROM user_user WHERE is_deleted = 0) as total_users,
                    (SELECT SUM(total_amount) FROM user_invoice WHERE is_deleted = 0 AND status = 'Paid') as total_revenue,
                    (SELECT COUNT(*) FROM user_invoice WHERE is_deleted = 0 AND status = 'Paid') as total_invoices,
                    (SELECT COUNT(*) FROM user_user WHERE is_deleted = 0 AND created_on >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as new_users_30d
            ");
            $stats['summary'] = $summaryResult ? $summaryResult->fetch_assoc() : [];
            
            echo json_encode(['status' => true, 'data' => $stats]);
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; }
        .main-content { margin-left: 260px; padding: 2rem; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 1rem; padding-top: 70px; } }
        
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-size: 1.75rem; color: #f8fafc; margin-bottom: 0.5rem; }
        .page-header p { color: #94a3b8; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); border-radius: 12px; padding: 1.25rem; border: 1px solid rgba(255,255,255,0.1); }
        .stat-card h3 { color: #94a3b8; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; }
        .stat-card .value { font-size: 1.75rem; font-weight: 700; color: #f8fafc; }
        .stat-card.users .value { color: #3b82f6; }
        .stat-card.revenue .value { color: #10b981; }
        .stat-card.invoices .value { color: #f59e0b; }
        .stat-card.new .value { color: #8b5cf6; }
        
        .charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; }
        .chart-card { background: #1e293b; border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(255,255,255,0.1); }
        .chart-card h3 { color: #f8fafc; font-size: 1rem; margin-bottom: 1rem; }
        .chart-container { position: relative; height: 300px; }
        
        @media (max-width: 768px) {
            .charts-grid { grid-template-columns: 1fr; }
            .chart-container { height: 250px; }
        }
    </style>
</head>
<body>
    <?php include('../components/sidebar.php'); ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-chart-line"></i> Analytics</h1>
            <p>Revenue and user growth insights</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card users">
                <h3>Total Users</h3>
                <div class="value" id="stat-users">0</div>
            </div>
            <div class="stat-card revenue">
                <h3>Total Revenue</h3>
                <div class="value" id="stat-revenue">₹0</div>
            </div>
            <div class="stat-card invoices">
                <h3>Total Invoices</h3>
                <div class="value" id="stat-invoices">0</div>
            </div>
            <div class="stat-card new">
                <h3>New Users (30d)</h3>
                <div class="value" id="stat-new">0</div>
            </div>
        </div>
        
        <div class="charts-grid">
            <div class="chart-card">
                <h3><i class="fas fa-rupee-sign"></i> Monthly Revenue</h3>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <h3><i class="fas fa-user-plus"></i> Monthly User Registrations</h3>
                <div class="chart-container">
                    <canvas id="usersChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> User Tier Distribution</h3>
                <div class="chart-container">
                    <canvas id="tierChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <h3><i class="fas fa-money-bill-wave"></i> Revenue by Type</h3>
                <div class="chart-container">
                    <canvas id="typeChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let revenueChart, usersChart, tierChart, typeChart;
        
        function loadAnalytics() {
            $.post('', { action: 'get_stats' }, function(response) {
                if (response.status) {
                    renderStats(response.data.summary);
                    renderRevenueChart(response.data.monthly_revenue);
                    renderUsersChart(response.data.monthly_users);
                    renderTierChart(response.data.tier_distribution);
                    renderTypeChart(response.data.revenue_by_type);
                }
            }, 'json');
        }
        
        function renderStats(summary) {
            $('#stat-users').text(parseInt(summary.total_users || 0).toLocaleString());
            $('#stat-revenue').text('₹' + parseFloat(summary.total_revenue || 0).toLocaleString());
            $('#stat-invoices').text(parseInt(summary.total_invoices || 0).toLocaleString());
            $('#stat-new').text(parseInt(summary.new_users_30d || 0).toLocaleString());
        }
        
        function renderRevenueChart(data) {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            const labels = data.map(d => formatMonth(d.month));
            const values = data.map(d => parseFloat(d.revenue));
            
            if (revenueChart) revenueChart.destroy();
            revenueChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue (₹)',
                        data: values,
                        backgroundColor: 'rgba(16, 185, 129, 0.6)',
                        borderColor: '#10b981',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#94a3b8' } }, x: { grid: { display: false }, ticks: { color: '#94a3b8' } } }
                }
            });
        }
        
        function renderUsersChart(data) {
            const ctx = document.getElementById('usersChart').getContext('2d');
            const labels = data.map(d => formatMonth(d.month));
            const values = data.map(d => parseInt(d.count));
            
            if (usersChart) usersChart.destroy();
            usersChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Users',
                        data: values,
                        backgroundColor: 'rgba(59, 130, 246, 0.3)',
                        borderColor: '#3b82f6',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#94a3b8' } }, x: { grid: { display: false }, ticks: { color: '#94a3b8' } } }
                }
            });
        }
        
        function renderTierChart(data) {
            const ctx = document.getElementById('tierChart').getContext('2d');
            const labels = data.map(d => (d.tier || 'Normal').charAt(0).toUpperCase() + (d.tier || 'Normal').slice(1));
            const values = data.map(d => parseInt(d.count));
            const colors = ['#fbbf24', '#9ca3af', '#3b82f6', '#8b5cf6', '#10b981'];
            
            if (tierChart) tierChart.destroy();
            tierChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors.slice(0, labels.length),
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8' } } }
                }
            });
        }
        
        function renderTypeChart(data) {
            const ctx = document.getElementById('typeChart').getContext('2d');
            const labels = data.map(d => (d.type || 'Registration').charAt(0).toUpperCase() + (d.type || 'Registration').slice(1));
            const values = data.map(d => parseFloat(d.revenue));
            
            if (typeChart) typeChart.destroy();
            typeChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: ['#3b82f6', '#8b5cf6', '#10b981'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8' } } }
                }
            });
        }
        
        function formatMonth(monthStr) {
            const [year, month] = monthStr.split('-');
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return months[parseInt(month) - 1] + " '" + year.slice(2);
        }
        
        $(document).ready(function() {
            loadAnalytics();
        });
    </script>
</body>
</html>
