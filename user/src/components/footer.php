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

<style>
/* Global Dark Theme for All Pages */
body {
    background: #1A1A1B !important;
    overflow-x: hidden;
}

#content {
    background: #1A1A1B !important;
}

section {
    background: transparent !important;
}

.container {
    background: transparent !important;
}

.no-bottom.no-top {
    background: #1A1A1B !important;
}

#wrapper {
    background: #1A1A1B !important;
}

/* Remove white backgrounds from all elements */
div, section, article, aside, header, nav {
    background-color: transparent;
}
</style>

<!-- Coming Soon Toast -->
<div id="coming-soon-toast" style="position:fixed;left:50%;bottom:90px;transform:translateX(-50%);background:rgba(0,0,0,0.85);color:#fff;padding:10px 16px;border-radius:8px;display:none;z-index:1100;box-shadow:0 6px 18px rgba(0,0,0,0.4);">
    <strong id="coming-soon-text">Coming soon</strong>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    function showToast(text){
        var toast = document.getElementById('coming-soon-toast');
        var txt = document.getElementById('coming-soon-text');
        txt.textContent = text + ' coming soon';
        toast.style.display = 'block';
        toast.style.opacity = 1;
        setTimeout(function(){
            toast.style.transition = 'opacity 300ms ease';
            toast.style.opacity = 0;
            setTimeout(function(){ toast.style.display = 'none'; toast.style.transition = ''; }, 300);
        }, 1500);
    }

    var coming = document.querySelectorAll('.coming-soon');
    coming.forEach(function(el){
        el.addEventListener('click', function(e){
            e.preventDefault();
            var name = el.getAttribute('data-name') || 'This feature';
            showToast(name);
        });
    });
    // More popover toggle
    var moreToggles = document.querySelectorAll('.nav-item-more');
    moreToggles.forEach(function(toggle){
        toggle.addEventListener('click', function(e){
            e.preventDefault();
            var container = toggle.closest('.more-container');
            var pop = container.querySelector('.more-popover');
            var expanded = toggle.getAttribute('aria-expanded') === 'true';
            if(!expanded) {
                pop.style.display = 'block';
                toggle.setAttribute('aria-expanded','true');
                pop.setAttribute('aria-hidden','false');
            } else {
                pop.style.display = 'none';
                toggle.setAttribute('aria-expanded','false');
                pop.setAttribute('aria-hidden','true');
            }
        });
    });

    // Close popovers when clicking outside and handle desktop "More"
    document.addEventListener('click', function(e){
        document.querySelectorAll('.more-popover').forEach(function(pop){
            var container = pop.closest('.more-container');
            if(!container) return;
            if(!container.contains(e.target)){
                pop.style.display = 'none';
                var toggle = container.querySelector('.nav-item-more');
                if(toggle) toggle.setAttribute('aria-expanded','false');
                pop.setAttribute('aria-hidden','true');
            }
        });

        // desktop more close
        document.querySelectorAll('.more-desktop').forEach(function(md){
            var wrap = md.closest('.more-desktop-wrapper');
            if(!wrap) return;
            if(!wrap.contains(e.target)){
                md.style.display = 'none';
                md.setAttribute('aria-hidden','true');
            }
        });
    });

    // desktop more toggle
    document.querySelectorAll('.desktop-more-toggle').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            var wrap = btn.closest('.more-desktop-wrapper');
            var panel = wrap.querySelector('.more-desktop');
            if(panel.style.display === 'block'){
                panel.style.display = 'none'; panel.setAttribute('aria-hidden','true');
            } else { panel.style.display = 'block'; panel.setAttribute('aria-hidden','false'); }
        });
    });
});
</script>

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
                
                <a href="/user/src/ui/privacy.php" class="more-item">Privacy Policy</a>
                <a href="/user/src/ui/terms.php" class="more-item">Terms & Conditions</a>
                <a href="/user/src/ui/refund.php" class="more-item">Refund Policy</a>
                
                <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 6px 0;"></div>
                
                <a href="../backend/logout.php" class="more-item"><i class="fas fa-sign-out-alt" style="width: 20px; color: #ef4444;"></i> Logout</a>
            </div>
        </div>
    </div>
</footer>

<!-- Mobile Footer Links (Static at bottom of content) -->
<div class="mobile-legal-footer d-md-none" style="text-align: center; padding: 20px 0 80px; opacity: 0.7;">
    <div style="margin-bottom: 10px;">
        <a href="/user/src/ui/privacy.php" style="color: #94a3b8; font-size: 12px; margin: 0 8px; text-decoration: none;">Privacy</a>
        <span style="color: #475569;">•</span>
        <a href="/user/src/ui/terms.php" style="color: #94a3b8; font-size: 12px; margin: 0 8px; text-decoration: none;">Terms</a>
        <span style="color: #475569;">•</span>
        <a href="/user/src/ui/refund.php" style="color: #94a3b8; font-size: 12px; margin: 0 8px; text-decoration: none;">Refund</a>
    </div>
    <div style="font-size: 11px; color: #475569;">&copy; <?php echo date('Y'); ?> Zokli</div>
</div>

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
                <p>&copy; <?php echo date('Y'); ?> Zokli. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<style>
/* Mobile Footer Styles - Jeeto Daily Inspired */
.mobile-footer {
    position: fixed !important;
    bottom: 0 !important;
    left: 0 !important;
    right: 0 !important;
    background: #0a0e1a !important; /* Fully opaque solid dark background */
    background-color: #0a0e1a !important;
    border-top: 1px solid rgba(102, 126, 234, 0.3);
    padding: 8px 0 8px;
    z-index: 9500 !important; /* High z-index to stay above other elements */
    box-shadow: 0 -8px 30px rgba(0, 0, 0, 0.6);
    opacity: 1 !important;
}

.mobile-nav {
    display: flex;
    justify-content: space-around;
    align-items: flex-end;
    max-width: 600px;
    margin: 0 auto;
    padding: 0 10px;
    background: #0a0e1a; /* Ensure solid background */
    position: relative;
    z-index: 1;
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

.more-container { position: relative; }
.nav-item-more { display:flex; flex-direction:column; align-items:center; color:#94a3b8; text-decoration:none; }
.more-popover {
    position: absolute;
    bottom: 60px;
    left: 50%;
    transform: translateX(-50%);
    background: #0a0e1a; /* Fully opaque */
    border: 1px solid #1e293b;
    border-radius: 10px;
    padding: 6px;
    display: none;
    box-shadow: 0 8px 24px rgba(0,0,0,0.8);
    min-width: 120px;
    z-index: 1200;
}
.more-popover .more-item {
    display:block; padding:8px 10px; color:#e2e8f0; text-decoration:none; font-size:0.85rem; border-radius:6px;
}
.more-popover .more-item:not(:last-child){ margin-bottom:6px; }
.more-popover .more-item:hover { background: rgba(255,255,255,0.03); color:#e67753 }

.nav-icon-wrapper {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    margin-bottom: 4px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: #1e293b; /* Fully opaque */
    border: 1px solid #334155;
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
    background: #2d3548; /* Fully opaque hover */
    border-color: #e67753;
    transform: translateY(-2px);
}

.mobile-nav .nav-item:hover,
.mobile-nav .nav-item.active {
    color: #e67753;
}

.mobile-nav .nav-item:hover .nav-icon-wrapper i,
.mobile-nav .nav-item.active .nav-icon-wrapper i {
    color: #e67753;
    transform: scale(1.1);
}

.mobile-nav .nav-item-center:hover .nav-icon-center,
.mobile-nav .nav-item-center.active .nav-icon-center {
    transform: scale(1.1);
    box-shadow: 0 10px 30px rgba(230, 119, 83, 0.6), 0 0 30px rgba(230, 119, 83, 0.4);
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

/* Desktop 'More' control */
.more-desktop-wrapper { position: relative; }
.desktop-more-toggle { color:#e2e8f0; text-decoration:none; cursor:pointer }
.more-desktop { position:absolute; top:36px; left:0; background: rgba(6,10,20,0.95); padding:8px; border-radius:8px; display:none; box-shadow:0 8px 24px rgba(0,0,0,0.6); }
.more-desktop a { display:block; padding:6px 10px; color:#e2e8f0; text-decoration:none }
.more-desktop a:hover { color:#e67753 }

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
    bottom: -4px;
    left: 0;
    width: 0;
    height: 2px;
    background: linear-gradient(90deg, #E9437A, #e67753, #E2AD2A);
    transition: width 0.3s ease;
}

.footer-nav a:hover {
    color: #e67753;
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
    color: #e67753;
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
/* Mobile-specific size overrides to make footer icons/text smaller */
@media (max-width: 767px) {
    /* Slightly smaller nav text and tighter padding */
    .mobile-nav .nav-item {
        font-size: 0.56rem; /* further reduced */
        padding: 4px 6px;
        max-width: 64px;
    }

    /* Smaller icon wrapper */
    .nav-icon-wrapper {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        margin-bottom: 2px;
    }

    .nav-icon-wrapper i {
        font-size: 1.05rem; /* reduced from 1.4rem */
    }

    /* Center featured icon smaller and less offset */
    .nav-item-center {
        transform: translateY(-6px);
    }

    .nav-icon-center {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        border: 2px solid rgba(10, 14, 39, 0.8);
    }

    .nav-icon-center i {
        font-size: 1.4rem; /* reduced from 1.8rem */
    }

    /* show popover full width on very small screens */
    .more-popover { left: 4%; bottom: 72px; transform: translateX(-50%); }

    /* Keep the back-to-top hidden on mobile */
    #back-to-top {
        display: none !important;
    }

    /* reduce bottom margin so content isn't pushed too far on small screens */
    #content {
        margin-bottom: 64px;
    }
}
</style>