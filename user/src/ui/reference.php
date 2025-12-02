<!DOCTYPE html>
<html lang="en">
<?php
include '../backend/dbconfig/connection.php';
session_start();
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Redirect to index if not logged in
if (!$is_logged_in) {
    header('Location: index.php');
    exit();
}
?>

<head>
    <title>Zokli - Referral & Leaderboard</title>
    <link rel="icon" href="../assets/logo2.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="Zokli Referral Program & Leaderboard - Earn rewards by referring friends" name="description" />
    <?php include('../components/csslinks.php') ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/referral_leaderboard.css">
</head>

<body class="dark-scheme de-grey">
    <div id="wrapper">
        <!-- header begin -->
        <?php include('../components/header.php') ?>
        <!-- header close -->

        <!-- content begin -->
        <div class="no-bottom no-top" id="content">

            <!-- Hero Section -->
            <section class="hero-section">
                <div class="container">
                    <div class="hero-content">
                        <h1 class="hero-title wow fadeInUp">
                            <i class="fas fa-trophy"></i> Referral Program
                        </h1>
                        <p class="hero-subtitle wow fadeInUp" data-wow-delay="0.2s">
                            Share your referral code and earn rewards when your friends join Zokli
                        </p>
                    </div>

                    <!-- Referral Card -->
                    <div style="margin-top:10px;"class="referral-card wow fadeInUp" data-wow-delay="0.4s">
                        <div class="referral-card-content">
                            <div class="referral-code-section">
                                <span class="referral-code-label">Your Referral Code</span>
                                <div class="referral-code-box">
                                    <span class="referral-code-text" id="referral-code">Loading...</span>
                                    <button class="copy-btn" id="copy-code-btn" title="Copy Code">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>

                                <div class="share-buttons">
                                    <a href="#" class="share-btn whatsapp" id="share-whatsapp"
                                        title="Share on WhatsApp">
                                        <i class="fab fa-whatsapp"></i>
                                    </a>
                                    <a href="#" class="share-btn telegram" id="share-telegram"
                                        title="Share on Telegram">
                                        <i class="fab fa-telegram"></i>
                                    </a>
                                    <a href="#" class="share-btn twitter" id="share-twitter" title="Share on Twitter">
                                        <i class="fab fa-twitter"></i>
                                    </a>
                                    <a href="#" class="share-btn messenger" id="share-messenger" title="Share on Messenger">
                                        <i class="fab fa-facebook-messenger"></i>
                                    </a>
                                    <a href="#" class="share-btn instagram" id="share-instagram" title="Share on Instagram">
                                        <i class="fab fa-instagram"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- Stats Grid -->
                            <div class="stats-grid">
                                <div class="stat-card wow fadeInUp" data-wow-delay="0.1s">
                                    <div class="stat-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="stat-number" id="total-referrals">0</div>
                                    <div class="stat-label">Total Referrals</div>
                                </div>

                                <div class="stat-card wow fadeInUp" data-wow-delay="0.2s">
                                    <div class="stat-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="stat-number" id="active-referrals">0</div>
                                    <div class="stat-label">Active Referrals</div>
                                </div>

                                <div class="stat-card wow fadeInUp coming-soon" data-name="Rewards" data-wow-delay="0.3s" style="cursor: pointer;">
                                    <div class="stat-icon">
                                        <i class="fas fa-coins"></i>
                                    </div>
                                    <div class="stat-number" id="total-earnings">₹0</div>
                                    <div class="stat-label">Total Earnings</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Leaderboard Section -->
            <section class="leaderboard-section">
                <div class="container">
                    <div class="section-header">
                        <h2 class="section-title wow fadeInUp">
                            <i class="fas fa-medal"></i> Top Referrers
                        </h2>
                        <p class="section-subtitle wow fadeInUp" data-wow-delay="0.2s">
                            See who's leading the referral race this month
                        </p>
                    </div>

                    <div class="leaderboard-table wow fadeInUp" data-wow-delay="0.4s">
                        <div class="leaderboard-header">
                            <div>Rank</div>
                            <div>User</div>
                            <div>Referrals</div>
                            <div class="earning-header">Earnings</div>
                        </div>
                        <div id="leaderboard-content">
                            <div class="loading">
                                <i class="fas fa-spinner"></i>
                                <p>Loading leaderboard...</p>
                            </div>
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
        // Load user referral stats
        function loadReferralStats() {
            $.ajax({
                url: '../backend/get_referral_stats.php',
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    if (response.status && response.data) {
                        const data = response.data;
                        $('#referral-code').text(data.referral_code || 'N/A');
                        $('#total-referrals').text(data.total_referrals || 0);
                        $('#active-referrals').text(data.active_referrals || 0);
                        $('#total-earnings').text('₹' + (data.total_earnings || 0));

                        // Setup share links - using profile QR link format
                        const shareText = `Join Zokli using my referral code: ${data.referral_code}`;
                        const shareUrl = window.location.origin + '/user/src/ui/profile.php?QR=' + encodeURIComponent(data.referral_code);
                        const registerUrl = window.location.origin + '/user/src/ui/register.php?ref=' + data.referral_code;

                        $('#share-whatsapp').attr('href', `https://wa.me/?text=${encodeURIComponent(shareText + '\nView Profile: ' + shareUrl + '\nRegister: ' + registerUrl)}`);
                        $('#share-telegram').attr('href', `https://t.me/share/url?url=${encodeURIComponent(shareUrl)}&text=${encodeURIComponent(shareText)}`);
                        $('#share-twitter').attr('href', `https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText)}&url=${encodeURIComponent(shareUrl)}`);

                        // Facebook Messenger: use mobile deep link and fallback to Facebook sharer (no app_id required)
                        const messengerDeep = `fb-messenger://share?link=${encodeURIComponent(shareUrl)}`;
                        const fbSharer = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}&quote=${encodeURIComponent(shareText)}`;
                        $('#share-messenger').attr('href', messengerDeep);
                        // Try deep link first; if it fails (desktop browsers), open Facebook sharer as fallback
                        $('#share-messenger').off('click').on('click', function (e) {
                            e.preventDefault();
                            // Attempt to open the messenger app via deep link
                            const start = Date.now();
                            // For some browsers, assigning window.location will attempt to open the app
                            window.location = messengerDeep;
                            // After a short delay open the fallback sharer in a new tab
                            setTimeout(function () {
                                // If still on the page, open fallback
                                if (Date.now() - start > 50) {
                                    window.open(fbSharer, '_blank');
                                }
                            }, 700);
                        });

                        // Instagram: Instagram web doesn't support direct URL sharing via intent.
                        // We'll make the button copy the message to clipboard and then open Instagram web profile.
                        $('#share-instagram').attr('href', '#');
                        $('#share-instagram').on('click', function (e) {
                            e.preventDefault();
                            const fullMessage = `${shareText}\nView Profile: ${shareUrl}\nRegister: ${registerUrl}`;
                            navigator.clipboard.writeText(fullMessage).then(() => {
                                window.open('https://www.instagram.com', '_blank');
                                alert('Share text copied to clipboard. Paste it in Instagram to share.');
                            }).catch(() => {
                                window.open('https://www.instagram.com', '_blank');
                                alert('Unable to copy automatically. Please paste your referral message manually in Instagram.');
                            });
                        });
                    }
                },
                error: function () {
                    $('#referral-code').text('Error loading');
                }
            });
        }

        // Load leaderboard
        function loadLeaderboard() {
            $.ajax({
                url: '../backend/get_referral_stats.php?leaderboard=1',
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    if (response.status && response.leaderboard) {
                        const leaderboard = response.leaderboard;
                        let html = '';

                        if (leaderboard.length === 0) {
                            html = '<div class="loading"><p>No data yet. Be the first to refer!</p></div>';
                        } else {
                            leaderboard.forEach((user, index) => {
                                const rank = index + 1;
                                const rankClass = rank === 1 ? 'rank-1' : rank === 2 ? 'rank-2' : rank === 3 ? 'rank-3' : '';
                                const badgeClass = user.user_tag === 'gold' ? 'badge-gold' : user.user_tag === 'silver' ? 'badge-silver' : 'badge-normal';
                                const badgeText = user.user_tag === 'gold' ? 'Gold' : user.user_tag === 'silver' ? 'Silver' : 'Member';
                                const initial = (user.user_name || 'U').charAt(0).toUpperCase();

                                html += `
                                    <div class="leaderboard-row">
                                        <div class="rank ${rankClass}">#${rank}</div>
                                        <div class="user-info">
                                            <div class="user-avatar">${initial}</div>
                                            <div class="user-details">
                                                <div class="user-name">${user.user_name || 'User'}</div>
                                                <span class="user-badge ${badgeClass}">${badgeText}</span>
                                            </div>
                                        </div>
                                        <div class="referral-count">${user.referral_count || 0}</div>
                                        <div class="earning-amount">₹${user.total_earnings || 0}</div>
                                    </div>
                                `;
                            });
                        }

                        $('#leaderboard-content').html(html);
                    }
                },
                error: function () {
                    $('#leaderboard-content').html('<div class="loading"><p>Error loading leaderboard</p></div>');
                }
            });
        }

        // Copy referral code
        $('#copy-code-btn').on('click', function () {
            const code = $('#referral-code').text();
            if (code && code !== 'Loading...' && code !== 'Error loading') {
                navigator.clipboard.writeText(code).then(() => {
                    const btn = $(this);
                    const originalIcon = btn.html();
                    btn.html('<i class="fas fa-check"></i>');
                    btn.css('background', 'linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%)');
                    setTimeout(() => {
                        btn.html(originalIcon);
                        btn.css('background', '');
                    }, 2000);
                });
            }
        });

        // Load data on page load
        $(document).ready(function () {
            loadReferralStats();
            loadLeaderboard();
        });
    </script>

</body>

</html>