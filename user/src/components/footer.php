<br>
<!-- Mobile Footer - Jeeto Daily Inspired -->
<footer class="mobile-footer d-md-none">
    <div class="mobile-nav">
        <a href="/user/src/ui/index.php" class="nav-item" data-page="home">
            <div class="nav-icon-wrapper">
                <i class="fas fa-home"></i>
            </div>
            <span>Home</span>
        </a>
        <a href="/user/src/ui/wallet.php" class="nav-item" data-page="spin">
            <div class="nav-icon-wrapper">
                <i class="fas fa-coins"></i>
            </div>
            <span>Wallet</span>
        </a>
        <a href="/user/src/ui/referral_leaderboard.php" class="nav-item nav-item-center" data-page="referral">
            <div class="nav-icon-wrapper nav-icon-center">
                <i class="fas fa-trophy"></i>
            </div>
            <span>Rewards</span>
        </a>
        <a href="/user/src/ui/community.php" class="nav-item" data-page="community">
            <div class="nav-icon-wrapper">
                <i class="fas fa-users"></i>
            </div>
            <span>Community</span>
        </a>
        <a href="/user/src/ui/profile.php" class="nav-item" data-page="profile">
            <div class="nav-icon-wrapper">
                <i class="fas fa-user"></i>
            </div>
            <span>Profile</span>
        </a>
    </div>
</footer>

<!-- Desktop Footer -->
<footer class="desktop-footer d-none d-md-block">
    <div class="container">
        <div class="footer-content">
            <div class="footer-nav">
                <a href="/user/src/ui/index.php">Home</a>
                <a href="/user/src/ui/community.php">Community</a>
                <a href="/user/src/ui/referral_leaderboard.php">Referral Program</a>
                <a href="/user/src/ui/profile.php">Profile</a>
                <a href="/user/src/ui/wallet.php">Wallet</a>
            </div>
            <div class="footer-links">
                <a href="/user/src/ui/privacy.php">Privacy Policy</a>
                <a href="/user/src/ui/terms.php">Terms & Conditions</a>
                <a href="/user/src/ui/refund.php">Refund Policy</a>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Zokli. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<style>
/* Mobile Footer Styles - Jeeto Daily Inspired */
.mobile-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.95) 0%, rgba(10, 14, 39, 0.98) 100%);
    border-top: 2px solid rgba(102, 126, 234, 0.3);
    padding: 8px 0 8px;
    z-index: 1000;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    box-shadow: 0 -5px 25px rgba(0, 0, 0, 0.3);
}

.mobile-nav {
    display: flex;
    justify-content: space-around;
    align-items: flex-end;
    max-width: 600px;
    margin: 0 auto;
    padding: 0 10px;
}

.mobile-nav .nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: #94a3b8;
    text-decoration: none;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 8px 10px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    flex: 1;
    max-width: 80px;
}

.nav-icon-wrapper {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    margin-bottom: 4px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.nav-icon-wrapper i {
    font-size: 1.4rem;
    transition: all 0.3s ease;
}

/* Center Item - Featured (Rewards) */
.nav-item-center {
    transform: translateY(-10px);
}

.nav-icon-center {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4), 0 0 20px rgba(102, 126, 234, 0.3);
    border: 3px solid rgba(10, 14, 39, 0.8);
    position: relative;
}

.nav-icon-center::before {
    content: '';
    position: absolute;
    inset: -3px;
    border-radius: 50%;
    padding: 3px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    animation: glow 2s ease-in-out infinite;
}

@keyframes glow {
    0%, 100% {
        opacity: 0.5;
    }
    50% {
        opacity: 1;
    }
}

.nav-icon-center i {
    font-size: 1.8rem;
    color: white;
}

/* Hover and Active States */
.mobile-nav .nav-item:hover .nav-icon-wrapper,
.mobile-nav .nav-item.active .nav-icon-wrapper {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.2) 0%, rgba(118, 75, 162, 0.2) 100%);
    border-color: rgba(102, 126, 234, 0.5);
    transform: translateY(-2px);
}

.mobile-nav .nav-item:hover,
.mobile-nav .nav-item.active {
    color: #667eea;
}

.mobile-nav .nav-item:hover .nav-icon-wrapper i,
.mobile-nav .nav-item.active .nav-icon-wrapper i {
    color: #667eea;
    transform: scale(1.1);
}

.mobile-nav .nav-item-center:hover .nav-icon-center,
.mobile-nav .nav-item-center.active .nav-icon-center {
    transform: scale(1.1);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.6), 0 0 30px rgba(102, 126, 234, 0.4);
}

.mobile-nav .nav-item span {
    margin-top: 2px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.footer-logo img {
    width: 40px;
    height: auto;
}

/* Desktop Footer Styles */
.desktop-footer {
    background: linear-gradient(180deg, #0a0e27 0%, #0f172a 100%);
    padding: 3rem 0 1.5rem;
    border-top: 2px solid rgba(102, 126, 234, 0.2);
    margin-top: 80px;
}

.footer-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2rem;
}

.desktop-footer .footer-logo img {
    width: 120px;
    height: auto;
    margin-bottom: 1rem;
}

.footer-nav {
    display: flex;
    gap: 2.5rem;
    flex-wrap: wrap;
    justify-content: center;
}

.footer-nav a {
    color: #e2e8f0;
    text-decoration: none;
    transition: all 0.3s ease;
    font-weight: 500;
    font-size: 1rem;
    position: relative;
}

.footer-nav a::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 0;
    height: 2px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    transition: width 0.3s ease;
}

.footer-nav a:hover {
    color: #667eea;
}

.footer-nav a:hover::after {
    width: 100%;
}

.footer-links {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
    justify-content: center;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-links a {
    color: #94a3b8;
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.3s ease;
}

.footer-links a:hover {
    color: #667eea;
}

.footer-bottom {
    text-align: center;
    color: #64748b;
    font-size: 0.9rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    margin-top: 1.5rem;
}

/* Add margin to main content to prevent footer overlap */
#content {
    margin-bottom: 70px;
}

@media (min-width: 768px) {
    #content {
        margin-bottom: 0;
    }
}

/* Hide back-to-top button on mobile */
@media (max-width: 767px) {
    #back-to-top {
        display: none !important;
    }
}
</style>