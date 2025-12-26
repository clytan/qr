<?php
// Include auth check - this ensures admin is logged in
require_once('../components/auth_check.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Dashboard - Admin Panel</title>
    <link rel="icon" href="../../../logo/logo.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            background: #0f172a;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #e2e8f0;
        }

        /* Dashboard Content */
        .dashboard-content {
            padding: 30px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 8px;
        }

        .page-subtitle {
            color: #94a3b8;
            font-size: 14px;
        }

        /* Access Denied Alert */
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            opacity: 0.1;
        }

        .stat-card.pink::before { background: #E9437A; }
        .stat-card.orange::before { background: #e67753; }
        .stat-card.yellow::before { background: #E2AD2A; }
        .stat-card.blue::before { background: #3b82f6; }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 15px;
        }

        .stat-card.pink .stat-icon { background: rgba(233, 67, 122, 0.2); color: #E9437A; }
        .stat-card.orange .stat-icon { background: rgba(230, 119, 83, 0.2); color: #e67753; }
        .stat-card.yellow .stat-icon { background: rgba(226, 173, 42, 0.2); color: #E2AD2A; }
        .stat-card.blue .stat-icon { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #94a3b8;
            font-size: 14px;
        }

        /* Welcome Card */
        .welcome-card {
            background: linear-gradient(135deg, rgba(233, 67, 122, 0.15) 0%, rgba(230, 119, 83, 0.15) 50%, rgba(226, 173, 42, 0.15) 100%);
            border: 1px solid rgba(230, 119, 83, 0.3);
            border-radius: 20px;
            padding: 35px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(230, 119, 83, 0.2) 0%, transparent 70%);
        }

        .welcome-title {
            font-size: 24px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 8px;
        }

        .welcome-text {
            color: #cbd5e1;
            font-size: 15px;
            line-height: 1.6;
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            border-radius: 20px;
            color: white;
            font-size: 12px;
            font-weight: 600;
            margin-top: 15px;
        }

        /* Quick Actions */
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #f1f5f9;
            margin-bottom: 20px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 18px 20px;
            color: #e2e8f0;
            text-decoration: none;
            transition: all 0.25s ease;
        }

        .action-btn:hover {
            background: rgba(30, 41, 59, 1);
            border-color: rgba(230, 119, 83, 0.3);
            transform: translateX(4px);
        }

        .action-btn i {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(230, 119, 83, 0.15);
            color: #e67753;
            font-size: 16px;
        }

        .action-btn span {
            font-weight: 500;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <?php include('../components/sidebar.php'); ?>
    
    <main class="admin-main">
        <div class="dashboard-content">
            <?php if (isset($_GET['error']) && $_GET['error'] === 'access_denied'): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Access Denied: You don't have permission to access that page.</span>
            </div>
            <?php endif; ?>

            <!-- Welcome Card -->
            <div class="welcome-card">
                <h2 class="welcome-title">Welcome back, <?php echo htmlspecialchars($admin_name); ?>! 👋</h2>
                <p class="welcome-text">
                    You're logged into the Zokli Admin Panel. Use the sidebar to navigate through different sections.
                </p>
                <span class="role-badge">
                    <i class="fas fa-user-shield"></i>
                    <?php echo htmlspecialchars($admin_role_name); ?>
                </span>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card pink">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-value" id="stat-users">-</div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-icon"><i class="fas fa-comments"></i></div>
                    <div class="stat-value" id="stat-communities">-</div>
                    <div class="stat-label">Communities</div>
                </div>
                <div class="stat-card yellow">
                    <div class="stat-icon"><i class="fas fa-user-cog"></i></div>
                    <div class="stat-value" id="stat-admins">-</div>
                    <div class="stat-label">Admin Users</div>
                </div>
                <div class="stat-card blue">
                    <div class="stat-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="stat-value" id="stat-roles">-</div>
                    <div class="stat-label">Roles</div>
                </div>
            </div>

            <!-- Charts Section -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px; margin-bottom: 30px;">
                <div style="background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 16px; padding: 25px;">
                    <h3 style="color: #f1f5f9; font-size: 18px; margin-bottom: 20px;">User Growth (Last 30 Days)</h3>
                    <canvas id="userChart" height="250"></canvas>
                </div>
                <div style="background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 16px; padding: 25px;">
                    <h3 style="color: #f1f5f9; font-size: 18px; margin-bottom: 20px;">Community Activity</h3>
                    <canvas id="communityChart" height="250"></canvas>
                </div>
            </div>

            <!-- Quick Actions -->
            <h3 class="section-title">Quick Actions</h3>
            <div class="quick-actions">
                <a href="admin_users.php" class="action-btn">
                    <i class="fas fa-user-plus"></i>
                    <span>Manage Admin Users</span>
                </a>
                <a href="admin_roles.php" class="action-btn">
                    <i class="fas fa-user-tag"></i>
                    <span>Manage Roles</span>
                </a>
                <a href="admin_urls.php" class="action-btn">
                    <i class="fas fa-link"></i>
                    <span>URL Permissions</span>
                </a>
                <a href="users.php" class="action-btn">
                    <i class="fas fa-users"></i>
                    <span>View All Users</span>
                </a>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Fetch dashboard stats
        $(document).ready(function() {
            $.ajax({
                url: '../backend/get_dashboard_stats.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status) {
                        $('#stat-users').text(response.data.users || 0);
                        $('#stat-communities').text(response.data.communities || 0);
                        $('#stat-admins').text(response.data.admins || 0);
                        $('#stat-roles').text(response.data.roles || 0);
                    }
                },
                error: function() {
                    // Just show dashes on error
                    $('.stat-value').text('-');
                }
            });

            // Fetch chart data
            $.ajax({
                url: '../backend/get_dashboard_chart_data.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status) {
                        renderCharts(response.data);
                    }
                }
            });

            function renderCharts(data) {
                // Common chart options for dark theme
                const chartOptions = {
                    responsive: true,
                    plugins: {
                        legend: { labels: { color: '#94a3b8' } }
                    },
                    scales: {
                        y: {
                            grid: { color: 'rgba(255, 255, 255, 0.05)' },
                            ticks: { color: '#94a3b8' },
                            beginAtZero: true
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#94a3b8' }
                        }
                    }
                };

                // User Chart (Line)
                new Chart(document.getElementById('userChart'), {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'New Users',
                            data: data.users,
                            borderColor: '#E9437A',
                            backgroundColor: 'rgba(233, 67, 122, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 0
                        }]
                    },
                    options: chartOptions
                });

                // Community Chart (Bar)
                new Chart(document.getElementById('communityChart'), {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'New Communities',
                            data: data.communities,
                            backgroundColor: '#e67753',
                            borderRadius: 4
                        }]
                    },
                    options: chartOptions
                });
            }
        });
    </script>
</body>

</html>
