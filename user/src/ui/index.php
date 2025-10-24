<!DOCTYPE html>
<html lang="en">
<?php
    include '../backend/dbconfig/connection.php';
    session_start();
    $is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    $user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
?>

<head>
    <title>ZQR - Connect Through QR Codes</title>
    <link rel="icon" href="../assets/images/icon-red.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="ZQR - Create your QR profile, connect instantly, join communities and manage your digital wallet" name="description" />
    <meta content="QR code, digital profile, community, chat, wallet, instant connection" name="keywords" />
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
        }

        /* Hero Section with Parallax */
        .hero-parallax {
            position: relative;
            height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .parallax-bg {
            position: absolute;
            top: -50px;
            left: 0;
            width: 100%;
            height: calc(100% + 100px);
            background: linear-gradient(135deg, rgba(10, 14, 39, 0.95) 0%, rgba(26, 31, 58, 0.95) 100%),
                        url('../assets/images/background/1.jpg') center/cover;
            transform: translateZ(-1px) scale(1.5);
            z-index: -1;
        }

        .hero-content-wrapper {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 0 20px;
            max-width: 1200px;
            margin: 0 auto;
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
            margin-bottom: 2rem;
            animation: fadeInDown 1s ease;
        }

        .hero-title {
            font-size: 4.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            animation: fadeInUp 1s ease 0.2s both;
        }

        .hero-title .gradient-text {
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.5rem;
            color: #94a3b8;
            max-width: 700px;
            margin: 0 auto 3rem;
            line-height: 1.7;
            animation: fadeInUp 1s ease 0.4s both;
        }

        .hero-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1s ease 0.6s both;
        }

        .btn-hero {
            padding: 18px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: var(--gradient-1);
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
        }

        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: rgba(102, 126, 234, 0.1);
            transform: translateY(-3px);
        }

        /* Floating Elements */
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            overflow: hidden;
            z-index: 1;
        }

        .shape {
            position: absolute;
            opacity: 0.1;
            animation: float 20s infinite ease-in-out;
        }

        .shape-1 { top: 10%; left: 10%; width: 100px; height: 100px; background: var(--gradient-1); border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
        .shape-2 { top: 60%; right: 15%; width: 150px; height: 150px; background: var(--gradient-2); border-radius: 63% 37% 54% 46% / 55% 48% 52% 45%; animation-delay: -5s; }
        .shape-3 { bottom: 20%; left: 20%; width: 80px; height: 80px; background: var(--gradient-3); border-radius: 41% 59% 58% 42% / 45% 60% 40% 55%; animation-delay: -10s; }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(30px, -30px) rotate(90deg); }
            50% { transform: translate(-20px, 20px) rotate(180deg); }
            75% { transform: translate(40px, 10px) rotate(270deg); }
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Enhanced Carousel Section */
        .carousel-section {
            padding: 100px 0;
            background: var(--darker);
            position: relative;
            overflow: hidden;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: #94a3b8;
            max-width: 600px;
            margin: 0 auto;
        }

        .enhanced-carousel {
            position: relative;
            padding: 20px;
        }

        .carousel-item-enhanced {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s ease;
            margin: 10px;
        }

        .carousel-item-enhanced:hover {
            transform: translateY(-10px);
        }

        .carousel-item-enhanced img {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 20px;
        }

        .carousel-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, transparent 100%);
            padding: 30px;
            color: white;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.4s ease;
        }

        .carousel-item-enhanced:hover .carousel-overlay {
            transform: translateY(0);
            opacity: 1;
        }

        .carousel-overlay h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .carousel-overlay p {
            color: #94a3b8;
        }

        /* Features Grid with Parallax */
        .features-parallax {
            padding: 100px 0;
            background: var(--dark);
            position: relative;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 60px;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px 30px;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient-1);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px) scale(1.02);
            border-color: var(--primary);
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.3);
        }

        .feature-card:hover::before {
            opacity: 0.05;
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: var(--gradient-1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .feature-card:nth-child(2) .feature-icon { background: var(--gradient-2); }
        .feature-card:nth-child(3) .feature-icon { background: var(--gradient-3); }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }

        .feature-description {
            color: #94a3b8;
            line-height: 1.7;
            position: relative;
            z-index: 1;
        }

        /* Stats Counter */
        .stats-section {
            padding: 80px 0;
            background: var(--gradient-1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            text-align: center;
        }

        .stat-item {
            color: white;
        }

        .stat-number {
            font-size: 3.5rem;
            font-weight: 800;
            display: block;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* CTA Section with Image */
        .cta-parallax {
            position: relative;
            padding: 150px 0;
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.95) 0%, rgba(245, 87, 108, 0.95) 100%),
                        url('../assets/images/background/5.jpg') center/cover fixed;
            text-align: center;
            color: white;
        }

        .cta-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .cta-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .cta-text {
            font-size: 1.3rem;
            margin-bottom: 40px;
            opacity: 0.95;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title { font-size: 2.5rem; }
            .hero-subtitle { font-size: 1.1rem; }
            .section-title { font-size: 2rem; }
            .cta-title { font-size: 2rem; }
            .stat-number { font-size: 2.5rem; }
            .hero-buttons { flex-direction: column; align-items: center; }
            .btn-hero { width: 100%; max-width: 300px; justify-content: center; }
            .carousel-item-enhanced img { height: 300px; }
        }

        /* Smooth Scroll */
        html { scroll-behavior: smooth; }
    </style>
</head>

<body class="dark-scheme de-grey">
    <div id="wrapper">
        <!-- header begin -->
        <?php include('../components/header.php') ?>
        <!-- header close -->
        
        <!-- content begin -->
        <div class="no-bottom no-top" id="content">
            
            <!-- Hero Section with Parallax -->
            <section class="hero-parallax" data-parallax="scroll">
                <div class="parallax-bg"></div>
                
                <div class="floating-shapes">
                    <div class="shape shape-1"></div>
                    <div class="shape shape-2"></div>
                    <div class="shape shape-3"></div>
                </div>
                
                <div class="hero-content-wrapper">
                    <?php if ($is_logged_in): ?>
                        <div class="hero-badge">
                            <i class="fas fa-check-circle"></i> Welcome back, <?php echo htmlspecialchars($user_name); ?>!
                        </div>
                    <?php else: ?>
                        <div class="hero-badge">
                            <i class="fas fa-star"></i> The Future of Digital Connection
                        </div>
                    <?php endif; ?>
                    
                    <h1 class="hero-title">
                        Connect Through<br>
                        <span class="gradient-text">QR Codes</span>
                    </h1>
                    
                    <p class="hero-subtitle">
                        Create your unique QR profile, share instantly with anyone, join vibrant communities,
                        and manage your digital wallet — all in one powerful platform.
                    </p>
                    
                    <div class="hero-buttons">
                        <?php if (!$is_logged_in): ?>
                            <a href="register.php" class="btn-hero btn-primary">
                                <i class="fas fa-rocket"></i> Get Started Free
                            </a>
                            <a href="login.php" class="btn-hero btn-outline">
                                <i class="fas fa-sign-in-alt"></i> Sign In
                            </a>
                        <?php else: ?>
                            <a href="profile.php" class="btn-hero btn-primary">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                            <a href="community.php" class="btn-hero btn-outline">
                                <i class="fas fa-comments"></i> Join Community
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Enhanced Carousel Section -->
            <section class="carousel-section">
                <div class="container">
                    <div class="section-header">
                        <h2 class="section-title">Discover Amazing Features</h2>
                        <p class="section-subtitle">
                            Explore the powerful capabilities that make ZQR your ultimate digital connection platform
                        </p>
                    </div>
                    
                    <div class="enhanced-carousel">
                        <div id="feature-carousel" class="owl-carousel owl-theme">
                            <div class="carousel-item-enhanced">
                                <img src="../assets/images/carousel/crs-1.jpg" alt="QR Profile Creation">
                                <div class="carousel-overlay">
                                    <h3><i class="fas fa-qrcode"></i> Instant QR Profiles</h3>
                                    <p>Create your personalized QR code profile in seconds and share with anyone</p>
                                </div>
                            </div>
                            
                            <div class="carousel-item-enhanced">
                                <img src="../assets/images/carousel/crs-2.jpg" alt="Community Chat">
                                <div class="carousel-overlay">
                                    <h3><i class="fas fa-users"></i> Community Connections</h3>
                                    <p>Join communities, engage in conversations, and build meaningful relationships</p>
                                </div>
                            </div>
                            
                            <div class="carousel-item-enhanced">
                                <img src="../assets/images/carousel/crs-3.jpg" alt="Digital Wallet">
                                <div class="carousel-overlay">
                                    <h3><i class="fas fa-wallet"></i> Secure Wallet</h3>
                                    <p>Manage your digital assets with our secure and easy-to-use wallet system</p>
                                </div>
                            </div>
                            
                            <div class="carousel-item-enhanced">
                                <img src="../assets/images/carousel/crs-4.jpg" alt="Customization">
                                <div class="carousel-overlay">
                                    <h3><i class="fas fa-palette"></i> Custom Themes</h3>
                                    <p>Personalize your QR code colors and profile theme to match your style</p>
                                </div>
                            </div>
                            
                            <div class="carousel-item-enhanced">
                                <img src="../assets/images/carousel/crs-5.jpg" alt="Analytics">
                                <div class="carousel-overlay">
                                    <h3><i class="fas fa-chart-line"></i> Smart Analytics</h3>
                                    <p>Track your profile views, connections, and engagement with detailed insights</p>
                                </div>
                            </div>
                            
                            <div class="carousel-item-enhanced">
                                <img src="../assets/images/carousel/crs-6.jpg" alt="Security">
                                <div class="carousel-overlay">
                                    <h3><i class="fas fa-shield-alt"></i> Top Security</h3>
                                    <p>Your data is protected with enterprise-level security and privacy controls</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Features with Parallax -->
            <section class="features-parallax">
                <div class="container">
                    <div class="section-header">
                        <h2 class="section-title">Why Choose ZQR?</h2>
                        <p class="section-subtitle">
                            Everything you need to manage your digital presence in one place
                        </p>
                    </div>
                    
                    <div class="features-grid">
                        <div class="feature-card wow fadeInUp" data-wow-delay="0.1s">
                            <div class="feature-icon">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <h3 class="feature-title">Lightning Fast</h3>
                            <p class="feature-description">
                                Create and share your QR profile in seconds. No complicated setup, just instant connections.
                            </p>
                        </div>
                        
                        <div class="feature-card wow fadeInUp" data-wow-delay="0.2s">
                            <div class="feature-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <h3 class="feature-title">Mobile First</h3>
                            <p class="feature-description">
                                Optimized for mobile devices. Access your profile anywhere, anytime from any device.
                            </p>
                        </div>
                        
                        <div class="feature-card wow fadeInUp" data-wow-delay="0.3s">
                            <div class="feature-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <h3 class="feature-title">Secure & Private</h3>
                            <p class="feature-description">
                                Your privacy matters. Control who sees your information and keep your data secure.
                            </p>
                        </div>
                        
                        <div class="feature-card wow fadeInUp" data-wow-delay="0.4s">
                            <div class="feature-icon">
                                <i class="fas fa-infinity"></i>
                            </div>
                            <h3 class="feature-title">Unlimited Sharing</h3>
                            <p class="feature-description">
                                Share your QR code unlimited times. No restrictions on how many people you connect with.
                            </p>
                        </div>
                        
                        <div class="feature-card wow fadeInUp" data-wow-delay="0.5s">
                            <div class="feature-icon">
                                <i class="fas fa-paint-brush"></i>
                            </div>
                            <h3 class="feature-title">Full Customization</h3>
                            <p class="feature-description">
                                Make it yours! Customize colors, themes, and design to reflect your unique personality.
                            </p>
                        </div>
                        
                        <div class="feature-card wow fadeInUp" data-wow-delay="0.6s">
                            <div class="feature-icon">
                                <i class="fas fa-headset"></i>
                            </div>
                            <h3 class="feature-title">24/7 Support</h3>
                            <p class="feature-description">
                                Need help? Our support team is always here to assist you with any questions.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Stats Counter -->
            <section class="stats-section">
                <div class="container">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number counter" data-count="10000">0</span>
                            <span class="stat-label">Active Users</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number counter" data-count="50000">0</span>
                            <span class="stat-label">Connections Made</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number counter" data-count="100">0</span>
                            <span class="stat-label">Communities</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">99.9%</span>
                            <span class="stat-label">Uptime</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA Section with Parallax -->
            <section class="cta-parallax">
                <div class="cta-content">
                    <h2 class="cta-title">Ready to Get Started?</h2>
                    <p class="cta-text">
                        Join thousands of users who are already connecting smarter with ZQR.
                        Create your profile today and experience the future of networking.
                    </p>
                    <?php if (!$is_logged_in): ?>
                        <a href="register.php" class="btn-hero btn-primary" style="background: white; color: #f5576c;">
                            <i class="fas fa-user-plus"></i> Create Free Account
                        </a>
                    <?php else: ?>
                        <a href="profile.php" class="btn-hero btn-primary" style="background: white; color: #f5576c;">
                            <i class="fas fa-arrow-right"></i> Go to My Profile
                        </a>
                    <?php endif; ?>
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
        // Parallax Effect
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const parallaxBg = document.querySelector('.parallax-bg');
            if (parallaxBg) {
                parallaxBg.style.transform = `translateY(${scrolled * 0.5}px)`;
            }
        });

        // Counter Animation
        function animateCounter(element, target) {
            let current = 0;
            const increment = target / 100;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = target.toLocaleString();
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current).toLocaleString();
                }
            }, 20);
        }

        // Intersection Observer for Counter
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counters = entry.target.querySelectorAll('.counter');
                    counters.forEach(counter => {
                        const target = parseInt(counter.getAttribute('data-count'));
                        animateCounter(counter, target);
                    });
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        const statsSection = document.querySelector('.stats-section');
        if (statsSection) {
            counterObserver.observe(statsSection);
        }

        // Initialize Enhanced Carousel
        $(document).ready(function() {
            $('#feature-carousel').owlCarousel({
                loop: true,
                margin: 20,
                nav: true,
                dots: true,
                autoplay: true,
                autoplayTimeout: 5000,
                autoplayHoverPause: true,
                navText: ['<i class="fa fa-chevron-left"></i>', '<i class="fa fa-chevron-right"></i>'],
                responsive: {
                    0: {
                        items: 1
                    },
                    768: {
                        items: 2
                    },
                    1024: {
                        items: 3
                    }
                }
            });

            // Smooth scroll for anchor links
            $('a[href^="#"]').on('click', function(e) {
                const target = $(this.getAttribute('href'));
                if (target.length) {
                    e.preventDefault();
                    $('html, body').stop().animate({
                        scrollTop: target.offset().top - 100
                    }, 1000);
                }
            });
        });

        // Floating shapes animation enhancement
        const shapes = document.querySelectorAll('.shape');
        shapes.forEach((shape, index) => {
            shape.style.animationDuration = `${20 + index * 5}s`;
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

        // Add smooth reveal animation on scroll
        const revealElements = document.querySelectorAll('.feature-card, .carousel-item-enhanced');
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        revealElements.forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            revealObserver.observe(el);
        });
    </script>

</body>

</html>