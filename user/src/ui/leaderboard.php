<!DOCTYPE html>
<html lang="en">
<?php
include '../backend/dbconfig/connection.php';
session_start();
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$user_qr_id = isset($_SESSION['user_qr_id']) ? $_SESSION['user_qr_id'] : '';
?>

<head>
    <title>ZQR - Referral Leaderboard</title>
    <link rel="icon" href="../assets/images/icon-red.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="ZQR Referral Leaderboard - Top Referrers" name="description" />
    <meta content="leaderboard, referrals, top users, rankings" name="keywords" />
    <meta content="ZQR" name="author" />
    <!-- CSS Files -->
    <?php include('../components/csslinks.php') ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5568d3;
            --secondary: #764ba2;
            --accent: #f093fb;
            --dark: #0f172a;
            --darker: #0a0e27;
            --light: #f8fafc;
            --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-3: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --gold: #ffd700;
            --silver: #c0c0c0;
            --bronze: #cd7f32;
        }

        /* Hero Section */
        .leaderboard-hero {
            position: relative;
            padding: 120px 0 80px;
            background: linear-gradient(135deg, rgba(10, 14, 39, 0.95) 0%, rgba(26, 31, 58, 0.95) 100%),
                url('../assets/images/background/1.jpg') center/cover;
            text-align: center;
            color: white;
        }

        .hero-badge {
            display: inline-block;
            padding: 8px 20px;
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 50px;
            color: var(--primary);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1rem;
        }

        .gradient-text {
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: #94a3b8;
            max-width: 600px;
            margin: 0 auto 2rem;
        }

        /* Leaderboard Section */
        .leaderboard-section {
            padding: 60px 0;
            background: var(--dark);
            min-height: 60vh;
        }

        .leaderboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Top 3 Podium */
        .podium {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 60px;
            align-items: flex-end;
        }

        .podium-item {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px 20px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
        }

        .podium-item:hover {
            transform: translateY(-10px);
            border-color: var(--primary);
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.3);
        }

        .podium-item.rank-1 {
            order: 2;
            padding-top: 50px;
        }

        .podium-item.rank-2 {
            order: 1;
        }

        .podium-item.rank-3 {
            order: 3;
        }

        .podium-rank {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            border: 4px solid var(--dark);
        }

        .rank-1 .podium-rank {
            background: var(--gold);
            color: #000;
            font-size: 2rem;
        }

        .rank-2 .podium-rank {
            background: var(--silver);
            color: #000;
        }

        .rank-3 .podium-rank {
            background: var(--bronze);
            color: #fff;
        }

        .podium-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 20px auto;
            background: var(--gradient-1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
        }

        .rank-1 .podium-avatar {
            width: 120px;
            height: 120px;
            font-size: 3.5rem;
        }

        .podium-name {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: white;
        }

        .podium-referrals {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            margin: 10px 0;
        }

        .podium-label {
            font-size: 0.9rem;
            color: #94a3b8;
        }

        /* Leaderboard Table */
        .leaderboard-table-wrapper {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            overflow: hidden;
        }

        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
        }

        .leaderboard-table thead {
            background: rgba(102, 126, 234, 0.1);
        }

        .leaderboard-table th {
            padding: 20px;
            text-align: left;
            font-weight: 600;
            color: white;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .leaderboard-table td {
            padding: 20px;
            color: #94a3b8;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .leaderboard-table tbody tr {
            transition: all 0.3s ease;
        }

        .leaderboard-table tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .leaderboard-table tbody tr.current-user {
            background: rgba(102, 126, 234, 0.15);
            border-left: 4px solid var(--primary);
        }

        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            font-weight: 700;
            color: white;
            font-size: 1.1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--gradient-1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-size: 1rem;
            font-weight: 600;
            color: white;
            margin-bottom: 4px;
        }

        .user-qr-id {
            font-size: 0.85rem;
            color: #64748b;
        }

        .referral-count {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        /* Loading State */
        .loading-spinner {
            text-align: center;
            padding: 60px 20px;
            color: white;
        }

        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }

            .podium {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .podium-item {
                order: unset !important;
            }

            .leaderboard-table {
                font-size: 0.85rem;
            }

            .leaderboard-table th,
            .leaderboard-table td {
                padding: 12px 8px;
            }

            .user-avatar {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
        }
    </style>
</head>

<body class="dark-scheme de-grey">
    <div id="wrapper">
        <!-- header begin -->
        <?php include('../components/header.php') ?>
        <!-- header close -->

        <!-- content begin -->
        <div class="no-bottom no-top" id="content">

            <!-- Hero Section -->
            <section class="leaderboard-hero">
                <div class="container">
                    <?php if ($is_logged_in): ?>
                        <div class="hero-badge">
                            <i class="fas fa-user-check"></i> Welcome, <?php echo htmlspecialchars($user_name); ?>!
                        </div>
                    <?php else: ?>
                        <div class="hero-badge">
                            <i class="fas fa-trophy"></i> Top Referrers
                        </div>
                    <?php endif; ?>

                    <h1 class="hero-title">
                        <span class="gradient-text">Referral</span> Leaderboard
                    </h1>

                    <p class="hero-subtitle">
                        See who's leading the way! Share your referral code and climb the ranks.
                    </p>
                </div>
            </section>

            <!-- Leaderboard Section -->
            <section class="leaderboard-section">
                <div class="leaderboard-container">
                    
                    <!-- Top 3 Podium -->
                    <div id="podium" class="podium">
                        <!-- Will be populated by JavaScript -->
                    </div>

                    <!-- Leaderboard Table -->
                    <div class="leaderboard-table-wrapper">
                        <div id="loading" class="loading-spinner">
                            <div class="spinner"></div>
                            <p>Loading leaderboard...</p>
                        </div>

                        <table id="leaderboard-table" class="leaderboard-table" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>User</th>
                                    <th>Referrals</th>
                                    <th>Member Since</th>
                                </tr>
                            </thead>
                            <tbody id="leaderboard-body">
                                <!-- Will be populated by JavaScript -->
                            </tbody>
                        </table>

                        <div id="empty-state" class="empty-state" style="display: none;">
                            <i class="fas fa-users-slash"></i>
                            <h3>No Referral Data Yet</h3>
                            <p>Be the first to refer someone and top the leaderboard!</p>
                        </div>
                    </div>

                </div>
            </section>

        </div>
        <!-- content close -->

        <a href="#" id="back-to-top"></a>

        <!-- footer begin -->
        <?php include('../components/footer.php'); ?>
        <!-- footer close -->

    </div>

    <!-- Javascript Files -->
    <?php include('../components/jslinks.php'); ?>

    <script>
        // Current user's QR ID (if logged in)
        const currentUserQrId = '<?php echo $user_qr_id; ?>';

        // Fetch leaderboard data
        async function fetchLeaderboard() {
            try {
                const response = await fetch('../backend/get_leaderboard.php?limit=50');
                const data = await response.json();

                if (data.status && data.data.length > 0) {
                    displayPodium(data.data.slice(0, 3));
                    displayLeaderboard(data.data);
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('leaderboard-table').style.display = 'table';
                } else {
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('empty-state').style.display = 'block';
                }
            } catch (error) {
                console.error('Error fetching leaderboard:', error);
                document.getElementById('loading').style.display = 'none';
                document.getElementById('empty-state').style.display = 'block';
            }
        }

        // Display top 3 podium
        function displayPodium(topThree) {
            const podium = document.getElementById('podium');
            podium.innerHTML = '';

            topThree.forEach((user, index) => {
                const rank = index + 1;
                const rankClass = `rank-${rank}`;
                const emoji = rank === 1 ? '👑' : rank === 2 ? '🥈' : '🥉';

                const item = document.createElement('div');
                item.className = `podium-item ${rankClass}`;
                item.innerHTML = `
                    <div class="podium-rank">${rank}</div>
                    <div class="podium-avatar">${emoji}</div>
                    <div class="podium-name">${escapeHtml(user.name)}</div>
                    <div class="podium-referrals">${user.referral_count}</div>
                    <div class="podium-label">Referrals</div>
                `;
                podium.appendChild(item);
            });
        }

        // Display full leaderboard table
        function displayLeaderboard(leaders) {
            const tbody = document.getElementById('leaderboard-body');
            tbody.innerHTML = '';

            leaders.forEach((user) => {
                const row = document.createElement('tr');
                if (user.user_qr_id === currentUserQrId) {
                    row.className = 'current-user';
                }

                const initial = user.name.charAt(0).toUpperCase();
                const memberSince = new Date(user.member_since).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short'
                });

                row.innerHTML = `
                    <td>
                        <span class="rank-badge">${user.rank}</span>
                    </td>
                    <td>
                        <div class="user-info">
                            <div class="user-avatar">${initial}</div>
                            <div class="user-details">
                                <div class="user-name">${escapeHtml(user.name)}</div>
                                <div class="user-qr-id">ID: ${escapeHtml(user.user_qr_id)}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="referral-count">${user.referral_count}</span>
                    </td>
                    <td>${memberSince}</td>
                `;
                tbody.appendChild(row);
            });
        }

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            fetchLeaderboard();
        });

        // Back to top button
        const backToTop = document.getElementById('back-to-top');
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTop.style.opacity = '1';
                backToTop.style.visibility = 'visible';
            } else {
                backToTop.style.opacity = '0';
                backToTop.style.visibility = 'hidden';
            }
        });
    </script>

</body>

</html>
