<!DOCTYPE html>
<html lang="en">
<?php
include '../backend/dbconfig/connection.php';
session_start();
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$user_qr = isset($_SESSION['user_qr_id']) ? $_SESSION['user_qr_id'] : '';

// Get member count for this user's community
$member_count = 0;
$community_id = null;
if ($is_logged_in && $user_id) {
    $stmt = $conn->prepare("SELECT community_id FROM user_user WHERE id = ? AND is_deleted = 0");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $community_id = $row['community_id'];
        if ($community_id) {
            $stmt2 = $conn->prepare("SELECT COUNT(*) as cnt FROM community_members WHERE community_id = ? AND is_deleted = 0");
            $stmt2->bind_param('i', $community_id);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            if ($row2 = $result2->fetch_assoc()) {
                $member_count = (int)$row2['cnt'];
            }
        }
    }
}

// Get max winners from config
$max_winners = 30; // Default
$stmt_conf = $conn->prepare("SELECT config_value FROM reward_config WHERE config_key = 'max_winners_per_draw'");
if ($stmt_conf) {
    $stmt_conf->execute();
    $res_conf = $stmt_conf->get_result();
    if ($row_conf = $res_conf->fetch_assoc()) {
        $max_winners = (int)$row_conf['config_value'];
    }
}

// Determine number of spinner slots
// Check if a completed draw exists for today - if so, use its winner count
$today = date('Y-m-d');
$completed_draw_winners = 0;
if ($community_id) {
    $stmt_draw = $conn->prepare("SELECT total_winners FROM reward_draws WHERE community_id = ? AND draw_date = ? AND is_completed = 1");
    if ($stmt_draw) {
        $stmt_draw->bind_param('is', $community_id, $today);
        $stmt_draw->execute();
        $res_draw = $stmt_draw->get_result();
        if ($row_draw = $res_draw->fetch_assoc()) {
            $completed_draw_winners = (int)$row_draw['total_winners'];
        }
    }
}

// Use completed draw's winner count if available, otherwise use current member count
if ($completed_draw_winners > 0) {
    $spinner_count = $completed_draw_winners;
} else {
    $spinner_count = min($max_winners, max(1, $member_count));
}
?>

<head>
    <title>Zokli - Daily Rewards Spinner</title>
    <link rel="icon" href="../assets/logo2.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="Zokli Daily Rewards - Spin to win! Winners selected every day from your community." name="description" />
    
    <!-- CSS Files -->
    <?php include('../components/csslinks.php') ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

    <style>
    :root {
        --primary: #667eea;
        --primary-dark: #5568d3;
        --secondary: #764ba2;
        --accent: #f093fb;
        --dark: #0f172a;
        --darker: #0a0e27;
        --light: #f8fafc;
        --gold: #FFD700;
        --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --gradient-3: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --gradient-gold: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
    }

    /* Full Page Background */
    body, html {
        min-height: 100vh;
        background: var(--darker) !important;
        height: auto !important;
        display: block !important;
        position: relative !important;
        overflow-x: hidden !important;
    }

    #wrapper {
        background: linear-gradient(135deg, rgba(10, 14, 39, 0.98) 0%, rgba(26, 31, 58, 0.95) 100%),
                    url('../assets/images/background/1.jpg') center/cover fixed;
        display: block !important;
        height: auto !important;
        overflow-y: visible !important;
        position: relative !important;
    }

    /* Ensure no flex centering on page content */
    #content, .no-bottom, .no-top {
        background: transparent !important;
        display: block !important;
        vertical-align: top !important;
        margin-top: 0 !important;
        padding-top: 0 !important; /* No padding needed if header is relative */
    }

    #content.no-top {
        padding-top: 0 !important;
    }

    /* Override header spacing */
    header {
        margin-bottom: 0 !important;
        position: relative !important;
        z-index: 1000 !important;
        display: flex !important;
        width: 100% !important;
        min-height: 80px;
    }

    header + #content,
    header ~ #content {
        margin-top: 0 !important;
    }

    /* Page Header */
    .rewards-hero {
        position: relative;
        padding: 5px 0 10px; /* Minimal padding */
        margin-top: 0;
        overflow: hidden;
        min-height: auto; /* Ensure no forced height */
    }

    /* Force content to top */
    .no-bottom.no-top {
        display: block !important;
        vertical-align: top;
    }
    
    #content {
        padding-top: 60px !important; /* Offset for fixed header without gap */
    }
    
    @media (max-width: 768px) {
        #content {
           padding-top: 40px !important;
        }
    }

    .rewards-hero-content {
        position: relative;
        z-index: 2;
        text-align: center;
    }

    .rewards-title {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 1rem;
        background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: shimmer 3s infinite;
    }

    @keyframes shimmer {
        0%, 100% { filter: brightness(1); }
        50% { filter: brightness(1.2); }
    }

    .rewards-subtitle {
        font-size: 1.1rem;
        color: #94a3b8;
        max-width: 600px;
        margin: 0 auto 1.5rem;
    }

    /* Timer Section */
    .timer-container {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 20px 40px;
        display: inline-block;
        margin-bottom: 1.5rem;
    }

    .timer-label {
        color: #94a3b8;
        font-size: 0.9rem;
        margin-bottom: 8px;
    }

    .timer-display {
        font-size: 2.5rem;
        font-weight: 700;
        color: #fff;
        font-family: 'Courier New', monospace;
        letter-spacing: 4px;
    }

    .timer-display.spinning {
        color: var(--gold);
        animation: pulse 1s infinite;
    }

    .timer-display.ended {
        color: #10b981;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    .status-badge {
        display: inline-block;
        padding: 8px 20px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.85rem;
        margin-top: 12px;
    }

    .status-spinning {
        background: var(--gradient-gold);
        color: #000;
        animation: glow 2s infinite;
    }

    .status-waiting {
        background: rgba(148, 163, 184, 0.2);
        color: #94a3b8;
    }

    .status-ended {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #fff;
    }

    @keyframes glow {
        0%, 100% { box-shadow: 0 0 20px rgba(255, 215, 0, 0.5); }
        50% { box-shadow: 0 0 40px rgba(255, 215, 0, 0.8); }
    }

    /* Spinners Section - Full Width */
    .spinners-section {
        padding: 30px 0 100px;
    }

    .spinners-grid {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 20px;
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 15px;
    }

    /* Individual Spinner */
    .spinner-slot {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 15px;
        text-align: center;
        position: relative;
        overflow: visible; /* Allow crown to pop out */
        transition: all 0.4s ease;
        width: 160px;
        flex-shrink: 0;
    }

    .spinner-slot:hover {
        transform: translateY(-5px);
        border-color: var(--primary);
    }

    .spinner-slot.winner-current-user {
        border: 3px solid var(--gold) !important;
        box-shadow: 0 0 30px rgba(255, 215, 0, 0.5),
                    0 0 60px rgba(255, 215, 0, 0.3);
        animation: winnerGlow 2s infinite;
    }

    @keyframes winnerGlow {
        0%, 100% { 
            box-shadow: 0 0 30px rgba(255, 215, 0, 0.5),
                        0 0 60px rgba(255, 215, 0, 0.3);
        }
        50% { 
            box-shadow: 0 0 50px rgba(255, 215, 0, 0.8),
                        0 0 80px rgba(255, 215, 0, 0.5);
        }
    }

    .spinner-number {
        position: absolute;
        top: 8px;
        left: 8px;
        background: linear-gradient(135deg, #E9437A 0%, #E2AD2A 100%);
        color: white;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
        z-index: 10;
    }

    .spinner-container {
        height: 130px;
        overflow: hidden;
        position: relative;
        margin-bottom: 8px;
        border-radius: 10px;
        background: rgba(0, 0, 0, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .spinner-reel {
        position: absolute;
        top: 0; /* Anchor to top */
        left: 0;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        transition: transform 0.1s linear;
    }

    .spinner-reel.stopping {
        transition: transform 3s cubic-bezier(0.17, 0.67, 0.12, 0.99);
    }

    .spinner-item {
        height: 130px;
        min-height: 130px; /* Enforce height */
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
        width: 100%;
        flex-shrink: 0; /* Prevent collapsing in flex column */
    }

    .spinner-item .qr-wrapper {
        background: white;
        padding: 6px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .spinner-item .qr-code {
        width: 90px;
        height: 90px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .spinner-item .qr-code img,
    .spinner-item .qr-code canvas {
        max-width: 100%;
        max-height: 100%;
    }

    /* Removed winner-name - only show QR ID */
    .winner-name {
        display: none;
    }

    .winner-name.visible {
        opacity: 1;
    }

    .winner-qr-id {
        font-size: 0.7rem;
        color: var(--primary);
        margin-top: 2px;
        opacity: 1;
    }

    /* Crown for current user winner */
    .winner-crown {
        position: absolute;
        top: -15px;
        right: -10px;
        font-size: 1.8rem;
        animation: bounce 1s infinite;
        z-index: 20;
        filter: drop-shadow(0 2px 5px rgba(0,0,0,0.5));
    }

    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-8px); }
    }

    /* Login Prompt */
    .login-prompt {
        text-align: center;
        padding: 100px 20px;
        min-height: 80vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .login-prompt h2 {
        color: #fff;
        font-size: 2rem;
        margin-bottom: 1rem;
    }

    .login-prompt p {
        color: #94a3b8;
        margin-bottom: 2rem;
    }

    .btn-login {
        padding: 15px 40px;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 50px;
        background: linear-gradient(135deg, #E9437A 0%, #E2AD2A 100%);
        color: white;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }

    .btn-login:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(233, 67, 122, 0.4);
        color: white;
    }

    /* Winning Banner */
    .winning-banner {
        background: var(--gradient-gold);
        color: #000;
        padding: 15px;
        text-align: center;
        font-size: 1.3rem;
        font-weight: 700;
        display: none;
        position: fixed;
        top: 80px;
        left: 0;
        right: 0;
        z-index: 1000;
    }

    .winning-banner.show {
        display: block;
        animation: slideDown 0.5s ease;
    }

    @keyframes slideDown {
        from { transform: translateY(-100%); }
        to { transform: translateY(0); }
    }

    /* Floating Elements */
    .floating-elements {
        position: fixed;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        overflow: hidden;
        z-index: 0;
        pointer-events: none;
    }

    .floating-coin {
        position: absolute;
        font-size: 2rem;
        opacity: 0.2;
        animation: floatCoin 15s infinite ease-in-out;
    }

    @keyframes floatCoin {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-30px) rotate(180deg); }
    }

    /* Prize Carousel */
    .prize-carousel-section {
        margin-bottom: 30px;
        position: relative;
    }

    .prize-banner-item {
        border-radius: 15px;
        overflow: hidden;
        position: relative;
        height: 160px;
        display: flex;
        align-items: center;
        padding: 0 40px;
        margin: 0 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }

    .banner-1 { background: linear-gradient(135deg, #FFD700 0%, #FDB931 100%); }
    .banner-2 { background: linear-gradient(135deg, #E0E0E0 0%, #BDBDBD 100%); }
    .banner-3 { background: linear-gradient(135deg, #cd7f32 0%, #a0522d 100%); }

    .banner-content h3 {
        color: #000;
        font-size: 1.8rem;
        font-weight: 800;
        margin-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .banner-content p {
        color: rgba(0,0,0,0.7);
        font-weight: 600;
        font-size: 1rem;
        margin: 0;
    }

    .banner-icon {
        position: absolute;
        right: 30px;
        font-size: 5rem;
        opacity: 0.2;
        color: #000;
        transform: rotate(-15deg);
    }

    /* Spinning State - Hide QR ID */
    .spinning-active .winner-qr-id,
    .state-waiting .winner-qr-id {
        opacity: 0 !important;
        transition: opacity 0.2s;
    }
    
    /* Waiting State - Dim QR codes slightly to indicate inactive */
    .state-waiting .spinner-item .qr-code {
        opacity: 0.6;
        filter: grayscale(100%);
        transition: all 0.3s;
    }

    @media (max-width: 768px) {
        .prize-banner-item {
            height: 120px;
            padding: 0 20px;
        }
        .banner-content h3 {
            font-size: 1.4rem;
        }
        .banner-icon {
            font-size: 3.5rem;
        }
        /* Mobile adjustment to bring content up */
        .rewards-hero {
             padding-top: 10px !important;
        }
        
        /* 3 Columns for Spinners - Strict sizing */
        .spinners-grid {
            gap: 4px;
            padding: 0 2px;
            justify-content: center;
            display: flex;
        }
        .spinner-slot { 
            width: 31%;
            flex: 0 0 31%;
            max-width: 31%;
            padding: 3px;
            margin: 0 !important;
        } 
        .spinner-container {
            height: 80px; /* Smaller height */
            margin-bottom: 4px;
        }
        .spinner-item {
            height: 80px;
            min-height: 80px;
        }
        .spinner-item .qr-code {
            width: 50px;
            height: 50px;
        }
        .spinner-number {
            width: 20px;
            height: 20px;
            font-size: 0.6rem;
            left: 4px;
            top: 4px;
        }
        .winner-qr-id {
            font-size: 0.55rem;
            margin-top: 0;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    }

    @media (max-width: 480px) {
        .rewards-title {
            font-size: 1.6rem;
        }
        .timer-container {
            padding: 15px 25px;
        }
        .spinners-grid {
            gap: 12px;
        }
        .spinner-slot {
            width: 130px;
            padding: 10px;
        }
        .spinner-container {
            height: 100px;
        }
        .spinner-item {
            height: 100px;
            min-height: 100px;
        }
        .spinner-item .qr-code {
            width: 65px;
            height: 65px;
        }
    }
    </style>
</head>

<body class="dark-scheme de-grey">
    <div id="wrapper">
        <!-- Floating Elements (Background) -->
        <div class="floating-elements">
            <span class="floating-coin" style="top: 10%; left: 5%;">🪙</span>
            <span class="floating-coin" style="top: 20%; right: 10%; animation-delay: -3s;">⭐</span>
            <span class="floating-coin" style="top: 60%; left: 15%; animation-delay: -7s;">💎</span>
            <span class="floating-coin" style="top: 70%; right: 5%; animation-delay: -5s;">🎁</span>
            <span class="floating-coin" style="top: 40%; left: 80%; animation-delay: -10s;">🏆</span>
        </div>

        <!-- Header -->
        <?php include('../components/header.php') ?>

        <!-- Content -->
        <div class="no-bottom no-top" id="content">
            <?php if (!$is_logged_in): ?>
            <!-- Login Required -->
            <section class="login-prompt">
                <i class="fas fa-lock" style="font-size: 4rem; color: var(--primary); margin-bottom: 1rem;"></i>
                <h2>Login Required</h2>
                <p>Please login to participate in the daily rewards spinner!</p>
                <a href="login.php?return=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login Now
                </a>
            </section>
            <?php else: ?>
            
            <!-- Hero Section -->
            <section class="rewards-hero">
                <div class="rewards-hero-content">
                    <h1 class="rewards-title">Daily Rewards Spinner</h1>
                    
                    <!-- Prize Banner Carousel -->
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

                    <p class="rewards-subtitle">
                        <?php echo $spinner_count; ?> lucky winner<?php echo $spinner_count > 1 ? 's' : ''; ?> selected every day from your community!
                    </p>
                    
                    <div class="timer-container">
                        <div class="timer-label" id="timerLabel">Time until draw</div>
                        <div class="timer-display" id="timerDisplay">--:--:--</div>
                        <div class="status-badge status-waiting" id="statusBadge">
                            <i class="fas fa-hourglass-half"></i> Loading...
                        </div>
                    </div>
                </div>
            </section>

            <!-- Spinners Section -->
            <section class="spinners-section">
                <div class="spinners-grid" id="spinnersGrid">
                    <!-- Dynamic spinner slots based on member count -->
                    <?php for ($i = 1; $i <= $spinner_count; $i++): ?>
                    <div class="spinner-slot" id="spinner-<?php echo $i; ?>">
                        <div class="spinner-number"><?php echo $i; ?></div>
                        <div class="spinner-container">
                            <div class="spinner-reel" id="reel-<?php echo $i; ?>">
                                <!-- QR items will be added here by JavaScript -->
                            </div>
                        </div>
                        <div class="winner-qr-id" id="winner-qr-<?php echo $i; ?>"></div>
                    </div>
                    <?php endfor; ?>
                </div>
            </section>

            <?php endif; ?>
        </div>

        <!-- Footer -->
        <?php include('../components/footer.php') ?>
    </div>

    <!-- JS Files -->
    <?php include('../components/jslinks.php') ?>
    
    <?php if ($is_logged_in): ?>
    <script>
    // Rewards Spinner Logic
    const RewardsSpinner = {
        config: null,
        members: [],
        winners: [],
        spinnerCount: <?php echo $spinner_count; ?>,
        currentUserId: <?php echo json_encode($user_id); ?>,
        currentUserQr: <?php echo json_encode($user_qr); ?>,
        isSpinning: false,
        hasEnded: false,
        spinIntervals: [],
        
        init: async function() {
            console.log('Initializing Rewards Spinner with', this.spinnerCount, 'slots');
            
            // Initialize Carousel
            if (jQuery().owlCarousel) {
                jQuery('#prizeCarousel').owlCarousel({
                    loop: true,
                    margin: 10,
                    nav: false,
                    dots: false,
                    autoplay: true,
                    autoplayTimeout: 3000,
                    responsive: {
                        0: { items: 1 },
                        600: { items: 2 },
                        1000: { items: 3 }
                    }
                });
            }
            
            // Load configuration
            await this.loadConfig();
            
            // Load community members for spinner display
            await this.loadCommunityMembers();
            
            // Check if draw already completed
            await this.checkTodaysDraw();
            
            // Start timer updates
            this.startTimerUpdates();
        },
        
        loadConfig: async function() {
            try {
                const response = await fetch('../backend/rewards/get_reward_config.php');
                const data = await response.json();
                
                if (data.status) {
                    this.config = data.config;
                    this.isSpinning = data.state.is_spinning;
                    this.hasEnded = data.state.has_ended;
                    this.timeRemaining = data.state.time_remaining_seconds;
                    
                    this.updateTimerDisplay();
                }
            } catch (error) {
                console.error('Error loading config:', error);
            }
        },
        
        loadCommunityMembers: async function() {
            try {
                const response = await fetch('../backend/rewards/get_community_members_qr.php');
                const data = await response.json();
                
                if (data.status) {
                    this.members = data.members;
                    this.populateSpinners();
                }
            } catch (error) {
                console.error('Error loading members:', error);
            }
        },
        
        checkTodaysDraw: async function() {
            try {
                const response = await fetch('../backend/rewards/get_todays_draw.php');
                const data = await response.json();
                
                if (data.status && data.draw_exists && data.is_completed) {
                    this.winners = data.winners;
                    this.hasEnded = true;
                    this.showWinners();
                    
                    if (data.current_user_won) {
                        // Banner removed
                    }
                } else if (this.isSpinning) {
                    this.startSpinning();
                }
            } catch (error) {
                console.error('Error checking draw:', error);
            }
        },
        
        populateSpinners: function() {
            if (this.members.length === 0) {
                console.log('No members to populate spinners');
                return;
            }
            
            // Create a global shuffle for static display to minimize duplicates
            const staticShuffled = [...this.members].sort(() => Math.random() - 0.5);
            
            for (let i = 1; i <= this.spinnerCount; i++) {
                const reel = document.getElementById(`reel-${i}`);
                if (!reel) continue;
                
                reel.innerHTML = '';
                reel.style.transform = 'translateY(0px)';
                
                const shuffled = [...this.members].sort(() => Math.random() - 0.5);
                
                // Duplicate content for seamless infinite scroll
                const renderList = [...shuffled, ...shuffled, ...shuffled, ...shuffled];
                
                // Initial static view: Use global unique shuffle if possible
                // If member count < slot count, duplicates are unavoidable but we spread them out.
                const firstMember = staticShuffled[(i - 1) % staticShuffled.length];
                
                // We actually need to ensure the FIRST item in the reel matches this member
                // So we replace renderList[0] with this specific member for the static view
                renderList[0] = firstMember;
                
                const qrEl = document.getElementById(`winner-qr-${i}`);
                const slotEl = document.getElementById(`spinner-${i}`);
                
                // Reset Highlight (Remove it initially)
                if (slotEl) slotEl.classList.remove('winner-current-user');
                
                if (qrEl && firstMember) {
                    qrEl.textContent = '@' + firstMember.user_qr_id;
                }
                
                // Limit DOM nodes
                const maxRender = Math.min(renderList.length, 30);
                
                for (let j = 0; j < maxRender; j++) {
                    const member = renderList[j];
                    // Only render REAL QR for the first item (visible static one)
                    // Use placeholder for the rest to save massive performance
                    const type = (j === 0) ? 'real' : 'placeholder';
                    const item = this.createSpinnerItem(member, type);
                    reel.appendChild(item);
                }
            }
        },
        
        createSpinnerItem: function(member, renderType = 'real') {
            const item = document.createElement('div');
            item.className = 'spinner-item';
            item.dataset.userId = member.user_id;
            item.dataset.qrId = member.user_qr_id;
            item.dataset.name = member.user_full_name;
            
            const wrapper = document.createElement('div');
            wrapper.className = 'qr-wrapper';
            
            const qrDiv = document.createElement('div');
            qrDiv.className = 'qr-code';
            
            if (renderType === 'placeholder') {
                // Lightweight placeholder for spinning elements
                qrDiv.innerHTML = '<i class="fas fa-qrcode" style="font-size: 50px; opacity: 0.3; color: #888;"></i>';
            } else {
                // Real QR Code for static/winner elements
                const qrUrl = window.location.origin + '/user/src/ui/profile.php?qr=' + encodeURIComponent(member.user_qr_id);
                
                try {
                    new QRCode(qrDiv, {
                        text: qrUrl,
                        width: 90,
                        height: 90,
                        colorDark: member.qr_color_dark || '#000000',
                        colorLight: member.qr_color_light || '#ffffff',
                        correctLevel: QRCode.CorrectLevel.L
                    });
                } catch (e) {
                    console.error('QR Gen Error', e);
                    qrDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                }
            }
            
            wrapper.appendChild(qrDiv);
            item.appendChild(wrapper);
            
            return item;
        },
        
        startSpinning: function() {
            if (this.isSpinning && this.animationId) return;
            this.isSpinning = true;
            document.getElementById('spinnersGrid').classList.add('spinning-active');
            
            // Initialize Animation State
            this.reelStates = [];

            for (let i = 1; i <= this.spinnerCount; i++) {
                const reel = document.getElementById(`reel-${i}`);
                if (!reel || !reel.children.length) continue;
                
                const itemHeight = reel.children[0]?.offsetHeight || 130;
                
                this.reelStates.push({
                    reel: reel,
                    offset: 0,
                    speed: 25 + Math.random() * 10,
                    totalHeight: reel.children.length * itemHeight,
                    itemHeight: itemHeight // Store for loop usage
                });
            }
            
            this.animateFrame();
        },
        
        animateFrame: function() {
            if (!this.isSpinning) return;
            
            this.reelStates.forEach(state => {
                state.offset += state.speed;
                // Seamless Loop
                if (state.offset >= (state.totalHeight / 2)) {
                    state.offset = state.offset % (state.totalHeight / 2);
                }
                state.reel.style.transform = `translateY(-${state.offset}px)`;
            });
            
            this.animationId = requestAnimationFrame(() => this.animateFrame());
        },
        
        stopSpinning: function() {
            this.isSpinning = false;
            if (this.animationId) {
                cancelAnimationFrame(this.animationId);
                this.animationId = null;
            }
            document.getElementById('spinnersGrid').classList.remove('spinning-active');
            this.reelStates = [];
        },
        
        updateTimerDisplay: function() {
            const timerDisplay = document.getElementById('timerDisplay');
            const timerLabel = document.getElementById('timerLabel');
            const statusBadge = document.getElementById('statusBadge');
            
            if (this.hasEnded) {
                timerDisplay.textContent = 'COMPLETED';
                timerDisplay.className = 'timer-display ended';
                timerLabel.textContent = "Today's Draw";
                statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> Winners Selected!';
                statusBadge.className = 'status-badge status-ended';
            } else if (this.isSpinning) {
                document.getElementById('spinnersGrid').classList.remove('state-waiting', 'state-ended');
                document.getElementById('spinnersGrid').classList.add('spinning-active');
                
                const hours = Math.floor(this.timeRemaining / 3600);
                const minutes = Math.floor((this.timeRemaining % 3600) / 60);
                const seconds = this.timeRemaining % 60;
                
                timerDisplay.textContent = 
                    String(hours).padStart(2, '0') + ':' +
                    String(minutes).padStart(2, '0') + ':' +
                    String(seconds).padStart(2, '0');
                timerDisplay.className = 'timer-display spinning';
                timerLabel.textContent = 'Draw ends in';
                statusBadge.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Spinning!';
                statusBadge.className = 'status-badge status-spinning';
            } else {
                document.getElementById('spinnersGrid').classList.remove('spinning-active', 'state-ended');
                document.getElementById('spinnersGrid').classList.add('state-waiting');
                
                let displayTime = '--:--:--';
                if (this.config?.spin_end_time) {
                    try {
                        // Convert 24h string "HH:MM:SS" to 12h "HH:MM:SS AM/PM"
                        const [hours, mins, secs] = this.config.spin_end_time.split(':');
                        const date = new Date();
                        date.setHours(hours, mins, secs || 0);
                        displayTime = date.toLocaleTimeString('en-US', { hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    } catch (e) {
                        displayTime = this.config.spin_end_time;
                    }
                }
                
                timerDisplay.textContent = displayTime;
                timerDisplay.className = 'timer-display';
                timerLabel.textContent = 'Next draw at';
                statusBadge.innerHTML = '<i class="fas fa-clock"></i> Waiting for draw...';
                statusBadge.className = 'status-badge status-waiting';
            }
        },
        
        startTimerUpdates: function() {
            setInterval(async () => {
                if (this.timeRemaining > 0) {
                    this.timeRemaining--;
                    this.updateTimerDisplay();
                }
                
                if (this.timeRemaining === 0 && this.isSpinning && !this.hasEnded && !this.isExecutingDraw) {
                    this.isExecutingDraw = true;
                    await this.executeDraw();
                }
            }, 1000);
        },
        
        executeDraw: async function() {
            console.log('Executing draw...');
            
            try {
                const response = await fetch('../backend/rewards/execute_draw.php');
                const data = await response.json();
                
                if (data.status) {
                    // Reload page to show winners robustly
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error executing draw:', error);
            }
        },
        
        animateToWinners: function() {
            // Stop and animate each reel to its winning position
            this.winners.forEach((winner, index) => {
                const slotNum = index + 1;
                if (slotNum > this.spinnerCount) return;
                
                const reel = document.getElementById(`reel-${slotNum}`);
                const slot = document.getElementById(`spinner-${slotNum}`);
                
                if (!reel || !slot) return;
                
                setTimeout(() => {
                    reel.innerHTML = '';
                    
                    // Add more items before winner for longer deceleration
                    const shuffled = [...this.members].sort(() => Math.random() - 0.5).slice(0, 20); 
                    shuffled.forEach(m => {
                        const item = this.createSpinnerItem(m, 'placeholder');
                        reel.appendChild(item);
                    });
                    
                    // Add winner at the end
                    const winnerItem = this.createSpinnerItem({
                        user_id: winner.user_id,
                        user_qr_id: winner.user_qr_id,
                        user_full_name: winner.user_full_name,
                        qr_color_dark: winner.qr_color_dark,
                        qr_color_light: winner.qr_color_light
                    }, 'real');
                    reel.appendChild(winnerItem);
                    
                    // FORCE RESET TRANSFORM FIRST (Important for transition to work)
                    reel.classList.remove('stopping');
                    reel.style.transition = 'none';
                    reel.style.transform = 'translateY(0)';
                    reel.offsetHeight; // Force reflow
                    
                    // Animate to winner
                    requestAnimationFrame(() => {
                        reel.style.transition = ''; // Restore CSS transition
                        reel.classList.add('stopping');
                        
                        // Dynamic height calculation for mobile support
                        const itemHeight = reel.children[0]?.offsetHeight || 130;
                        const targetOffset = shuffled.length * itemHeight; 
                        
                        reel.style.transform = `translateY(-${targetOffset}px)`;
                    });
                    
                    setTimeout(() => {
                        const qrEl = document.getElementById(`winner-qr-${slotNum}`);
                        if (qrEl) {
                            qrEl.textContent = '@' + winner.user_qr_id;
                        }
                        
                        if (winner.is_current_user) {
                            slot.classList.add('winner-current-user');
                            const crown = document.createElement('div');
                            crown.className = 'winner-crown';
                            crown.textContent = '👑';
                            slot.appendChild(crown);
                        }
                    }, 3000);
                    
                }, index * 100);
            });
            
            this.updateTimerDisplay();
        },
        
        showWinners: function() {
            this.winners.forEach((winner, index) => {
                const slotNum = index + 1;
                if (slotNum > this.spinnerCount) return;
                
                const reel = document.getElementById(`reel-${slotNum}`);
                const slot = document.getElementById(`spinner-${slotNum}`);
                
                if (!reel || !slot) return;
                
                reel.innerHTML = '';
                
                const winnerItem = this.createSpinnerItem({
                    user_id: winner.user_id,
                    user_qr_id: winner.user_qr_id,
                    user_full_name: winner.user_full_name,
                    qr_color_dark: winner.qr_color_dark,
                    qr_color_light: winner.qr_color_light
                }, 'real');
                reel.appendChild(winnerItem);
                
                const nameEl = document.getElementById(`winner-name-${slotNum}`);
                const qrEl = document.getElementById(`winner-qr-${slotNum}`);
                if (nameEl) {
                    nameEl.textContent = winner.user_full_name || 'Anonymous';
                    nameEl.classList.add('visible');
                }
                if (qrEl) {
                    qrEl.textContent = '@' + winner.user_qr_id;
                }
                
                if (winner.is_current_user) {
                    slot.classList.add('winner-current-user');
                    const crown = document.createElement('div');
                    crown.className = 'winner-crown';
                    crown.textContent = '👑';
                    slot.appendChild(crown);
                }
            });
            
            this.updateTimerDisplay();
        },
        
        // Banner removed
        showWinningBanner: function() {
            // Function removed
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        RewardsSpinner.init();
    });
    </script>
    <?php endif; ?>
</body>
</html>
