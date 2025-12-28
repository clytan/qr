<?php
// Hide footer if user is not logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // User not logged in - don't show footer
    return;
}
?>

<link rel="stylesheet" href="../css/footer.css">

<!-- Coming Soon Toast -->
<div id="coming-soon-toast" style="position:fixed;left:50%;bottom:90px;transform:translateX(-50%);background:rgba(0,0,0,0.85);color:#fff;padding:10px 16px;border-radius:8px;display:none;z-index:1100;box-shadow:0 6px 18px rgba(0,0,0,0.4);">
    <strong id="coming-soon-text">Coming soon</strong>
</div>

<script src="../js/footer.js"></script>

<br>
<!-- Mobile Footer - Reordered Tabs (visible on mobile) -->
<footer class="mobile-footer d-md-none">
    <div class="mobile-nav">
        <a href="/user/src/ui/index.php" class="nav-item" data-page="home">
            <div class="nav-icon-wrapper">
                <i class="fas fa-home"></i>
            </div>
            <span>Home</span>
        </a>
        <a href="/user/src/ui/profile.php" class="nav-item" data-page="profile">
            <div class="nav-icon-wrapper">
                <i class="fas fa-user"></i>
            </div>
            <span>Profile</span>
        </a>
        <a href="/user/src/ui/rewards.php" class="nav-item" data-page="rewards">
            <div class="nav-icon-wrapper">
                <i class="fas fa-gift"></i>
            </div>
            <span>Rewards</span>
        </a>
        <a href="/user/src/ui/community.php" class="nav-item" data-page="community">
            <div class="nav-icon-wrapper">
                <i class="fas fa-users"></i>
            </div>
            <span>Community</span>
        </a>
        <a href="/user/src/ui/reference.php" class="nav-item" data-page="reference">
            <div class="nav-icon-wrapper">
                <i class="fas fa-book"></i>
            </div>
            <span>Reference</span>
        </a>
        <div class="nav-item more-container">
            <a href="#" class="nav-item-more" aria-expanded="false" aria-haspopup="true">
                <div class="nav-icon-wrapper">
                    <i class="fas fa-ellipsis-h"></i>
                </div>
                <span>More</span>
            </a>
            <div class="more-popover" role="menu" aria-hidden="true">
                <a href="/user/src/ui/biz.php" class="more-item">Sell & Earn</a>
                <a href="/user/src/ui/polls.php" class="more-item">Vote now</a>
                <a href="/user/src/ui/influencer.php" class="more-item">Influencer Program</a>
                
                <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 6px 0;"></div>
                
                <a href="/user/src/ui/privacy.php" class="more-item" style="margin-top: 10px;">Privacy Policy</a>
                <a href="/user/src/ui/terms.php" class="more-item" style="margin-top: 10px;">Terms & Conditions</a>
                <a href="/user/src/ui/refund.php" class="more-item" style="margin-top: 10px;">Refund Policy</a>
                
                <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 6px 0;"></div>
                
                <a href="../backend/logout.php" class="more-item"><i class="fas fa-sign-out-alt" style="width: 20px; color: #ef4444;"></i> Logout</a>
            </div>
        </div>
    </div>
</footer>


<!-- Desktop Footer -->
<footer class="desktop-footer d-none d-md-block">
    <div class="container">
        <div class="footer-content">
            <div class="footer-nav">
                <a href="/user/src/ui/index.php">Home</a>
                <a href="/user/src/ui/profile.php">Profile</a>
                <a href="/user/src/ui/rewards.php">Rewards</a>
                <a href="/user/src/ui/community.php">Community</a>
                <a href="/user/src/ui/reference.php">Reference</a>
                <div class="more-desktop-wrapper">
                    <a href="#" class="desktop-more-toggle">More</a>
                    <div class="more-desktop" aria-hidden="true">
                        <a href="/user/src/ui/biz.php">Sell & Earn</a>
                        <a href="/user/src/ui/polls.php">Vote now</a>
                        <a href="/user/src/ui/influencer.php">Influencer Program</a>
                        <a href="../backend/logout.php" style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 4px; padding-top: 8px;"><i class="fas fa-sign-out-alt" style="margin-right: 5px; color: #ef4444;"></i> Logout</a>
                    </div>
                </div>
            </div>
            <div class="footer-links">
                <a href="/user/src/ui/privacy.php">Privacy Policy</a>
                <a href="/user/src/ui/terms.php">Terms & Conditions</a>
                <a href="/user/src/ui/refund.php">Refund Policy</a>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> BLRLEAF TECHNOLOGY (OPC) PRIVATE LIMITED. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

