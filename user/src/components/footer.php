<br>
<footer class="mobile-footer d-md-none">
    <div class="mobile-nav">
        <a href="/qr/user/src/ui/index.php" class="nav-item">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="/qr/user/src/ui/community.php" class="nav-item">
            <i class="fas fa-users"></i>
            <span>Community</span>
        </a>
        <a href="/qr/user/src/ui/profile.php" class="nav-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </div>
</footer>

<footer class="desktop-footer d-none d-md-block">
    <div class="container">
        <div class="footer-content">
            <div class="footer-nav">
                <a href="/qr/user/src/ui/index.php">Home</a>
                <a href="/qr/user/src/ui/community.php">Community</a>
                <a href="/qr/user/src/ui/profile.php">Profile</a>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> ZQKLI. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<style>
    /* Mobile Footer Styles */
    .mobile-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background-color: #212428;
        border-top: 1px solid var(--border-color);
        padding: 8px 0;
        z-index: 1000;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    .mobile-nav {
        display: flex;
        justify-content: space-around;
        align-items: center;
    }

    .mobile-nav .nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        color: var(--text-secondary);
        text-decoration: none;
        font-size: 0.8rem;
        padding: 4px 0;
        transition: all 0.3s ease;
    }

    .mobile-nav .nav-item i {
        font-size: 1.4rem;
        margin-bottom: 4px;
    }

    .mobile-nav .nav-item:hover,
    .mobile-nav .nav-item.active {
        color: var(--primary-color);
    }

    .mobile-nav .logo-item {
        transform: translateY(-15px);
    }

    .footer-logo img {
        width: 40px;
        height: auto;
    }

    /* Desktop Footer Styles */
    .desktop-footer {
        background: var(--card-bg);
        padding: 2rem 0;
        border-top: 1px solid var(--border-color);
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
    }

    .footer-nav {
        display: flex;
        gap: 2rem;
    }

    .footer-nav a {
        color: var(--text-color);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .footer-nav a:hover {
        color: var(--primary-color);
    }

    .footer-bottom {
        text-align: center;
        color: var(--text-secondary);
        font-size: 0.9rem;
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