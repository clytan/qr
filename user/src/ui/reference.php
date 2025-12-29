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
    <link rel="icon" href="../../../logo/logo.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="Zokli Referral Program & Leaderboard - Earn rewards by referring friends" name="description" />
    <?php include('../components/csslinks.php') ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/reference.css">

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

                    <!-- Prize Carousel -->
                    <div class="prize-carousel-section container">
                        <div class="owl-carousel owl-theme" id="prizeCarousel">
                            <div class="item">
                                <div class="prize-banner-item banner-1">
                                    <div class="banner-content">
                                        <h3>Grand Prize</h3>
                                        <p>Spin to win exclusive community rewards!</p>
                                    </div>
                                    <i class="fas fa-trophy banner-icon"></i>
                                </div>
                            </div>
                            <div class="item">
                                <div class="prize-banner-item banner-2">
                                    <div class="banner-content">
                                        <h3>Daily Winners</h3>
                                        <p>30 winners selected every single day.</p>
                                    </div>
                                    <i class="fas fa-medal banner-icon"></i>
                                </div>
                            </div>
                            <div class="item">
                                <div class="prize-banner-item banner-3">
                                    <div class="banner-content">
                                        <h3>Join the Fun</h3>
                                        <p>Be active in your community to win next.</p>
                                    </div>
                                    <i class="fas fa-gift banner-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- Referral Card -->
                    <div class="referral-card wow fadeInUp" data-wow-delay="0.4s">
                        <div class="referral-card-content">
                            <div class="referral-code-section">
                                <span class="referral-code-label text-white">Your Referral Code</span>
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
                                    <a href="#" class="share-btn twitter" id="share-twitter" title="Share on X">
                                        <i class="fab fa-x-twitter"></i>
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
                            Community leaders making an impact
                        </p>
                    </div>

                    <!-- Premium Nav Bar with Tabs -->
                    <div class="leaderboard-nav-bar">
                        <div class="leaderboard-tabs-group">
                            <button class="leaderboard-tab active" data-period="all">All Time</button>
                            <button class="leaderboard-tab" data-period="month">This Month</button>
                            <button class="leaderboard-tab" data-period="week">This Week</button>
                        </div>
                    </div>

                    <div class="leaderboard-table wow fadeInUp" data-wow-delay="0.3s">
                        <div class="leaderboard-header">
                            <div>Rank</div>
                            <div>User</div>
                            <div class="text-right">Referrals</div>
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
    <script src="custom_js/toast.js"></script>
    <script src="../js/reference.js"></script>

</body>


</html>
