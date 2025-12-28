<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<?php
// Check if user is logged in
$header_is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
?>

<header id="zokli-header" class="transparent scroll-dark">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="header-content d-flex align-items-center justify-content-between">
                    <!-- Left Section: Logo -->
                    <div class="left-section">
                        <div id="zokli-logo">
                            <a href="index.php">
                                <img alt="Logo" class="logo-image" src="../../../logo/logo.png" />
                            </a>
                        </div>
                    </div>
                    
                    <!-- Center Section: Logo Word (Zokli) -->
                    <div class="center-section">
                        <a href="index.php">
                            <img alt="Zokli" class="logo-word-image" src="../../../logo/logo-word.png" />
                        </a>
                    </div>
                    
                    <!-- Right Section: Notification & Wallet -->
                    <div class="right-section">
                        <?php if ($header_is_logged_in): ?>
                        <!-- Notifications Dropdown -->
                        <div class="notification-dropdown" id="zokli-notification-dropdown">
                            <a href="javascript:void(0)" class="btn-main btn-notification" id="zokli-notification-btn">
                                <i class="fas fa-bell"></i>
                                <span class="notification-count" id="zokli-notification-count" style="display: none;">0</span>
                            </a>
                            <div class="notification-content" id="zokli-notification-content">
                                <div class="notification-header">
                                    <h4>Notifications</h4>
                                </div>
                                <div class="notification-list" id="zokli-notification-list">
                                    <!-- Notifications will be inserted here via JavaScript -->
                                </div>
                            </div>
                        </div>
                        <!-- Wallet Button -->
                        <a href="wallet.php" class="btn-main btn-wallet" id="zokli-wallet-btn" title="Wallet">
                            <i class="fas fa-wallet"></i>
                            <span class="wallet-balance" style="display:none;"></span>
                        </a>
                        <?php endif; ?>
                    </div>
                    <link rel="stylesheet" href="../css/header.css">
                    <!-- <div class="de-flex-col">
                            <input id="quick_search" class="xs-hide style-2" name="quick_search"
                                placeholder="search item here..." type="text" />
                        </div> -->
                </div>
                <div class="de-flex-col header-col-mid d-none">
                    <!-- mainmenu begin -->
                    <ul id="mainmenu">
                        <li>
                            <a href="index.php">HOME<span></span></a>
                        </li>
                        <li>
                            <a href="community.php">CHAT<span></span></a>
                        </li>
                        <li>
                            <a href="login.php">LOGIN<span></span></a>
                        </li>
                        <li>
                            <a href="register.php">REGISTER<span></span></a>
                        </li>
                        <li>
                            <a href="admin\src\ui\login.php">admin<span></span></a>
                        </li>
                    </ul>
                    <!-- mainmenu close -->
                    <div class="menu_side_area">
                        <a href="wallet.php" class="btn-main btn-wallet"><i
                                class="icon_wallet_alt"></i><span>Wallet</span></a>
                        <span id="menu-btn"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/header.js"></script>
</header>
<br>
<div class="mobile-only-dev">
</div>
