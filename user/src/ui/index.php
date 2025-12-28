<!DOCTYPE html>
<html lang="en">
<?php
include '../backend/dbconfig/connection.php';
session_start();
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
?>

<head>
    <title>Zokli - Digital Social Community for Dynamic Generation</title>
    <link rel="icon" href="../../../logo/logo.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta
        content="Zokli - Join 10 million Zoks in the hyper-connected knowledge nexus. A digital community for learning, creating, and collective action."
        name="description" />
    <meta content="Zokli, digital community, social media, Zoks, knowledge sharing, networking, skill development"
        name="keywords" />
    <meta content="Zokli" name="author" />
    <!-- CSS Files -->
    <?php include('../components/csslinks.php') ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="../css/index.css">
</head>

<body class="dark-scheme de-grey">
    <div id="wrapper">
        <!-- header begin -->
        <?php include('../components/header.php') ?>
        <!-- header close -->

        <!-- content begin -->
        <div class="no-bottom no-top" id="content">
            <section class="carousel-section" style="">
                <div class="hero-content-wrapper welcome-margin">
                    <?php if ($is_logged_in): ?>
                    <div class="hero-badge">
                        <i class="fas fa-check-circle"></i> Welcome back, <?php echo htmlspecialchars($user_name); ?>!
                    </div>
                    <?php else: ?>
                    <div class="hero-badge">
                        <i class="fas fa-star"></i> The Future of Digital Connection
                    </div>
                    <?php endif; ?>
                </div>
                <div class="container">
                    <div class="enhanced-carousel">
                        <div id="feature-carousel" class="owl-carousel owl-theme">
                            <div class="carousel-item-enhanced">
                                <div class="banner-blur-bg" style="background-image: url('../banners/2.jpg');"></div>
                                <img src="../banners/2.jpg" alt="Banner 2" class="banner-img-main">
                            </div>

                            <div class="carousel-item-enhanced">
                                <div class="banner-blur-bg" style="background-image: url('../banners/3.jpg');"></div>
                                <img src="../banners/3.jpg" alt="Banner 3" class="banner-img-main">
                            </div>

                            <div class="carousel-item-enhanced">
                                <div class="banner-blur-bg" style="background-image: url('../banners/4.jpg');"></div>
                                <img src="../banners/4.jpg" alt="Banner 4" class="banner-img-main">
                            </div>

                            <div class="carousel-item-enhanced">
                                <div class="banner-blur-bg" style="background-image: url('../banners/5.jpg');"></div>
                                <img src="../banners/5.jpg" alt="Banner 5" class="banner-img-main">
                            </div>
                            <div class="carousel-item-enhanced">
                                <div class="banner-blur-bg" style="background-image: url('../banners/6.jpg');"></div>
                                <img src="../banners/6.jpg" alt="Banner 6" class="banner-img-main">
                            </div>
                            <!-- <div class="carousel-item-enhanced">
                                <div class="banner-blur-bg" style="background-image: url('../banners/7.jpeg');"></div>
                                <img src="../banners/7.jpeg" alt="Banner 7" class="banner-img-main">
                            </div>
                            <div class="carousel-item-enhanced">
                                <div class="banner-blur-bg" style="background-image: url('../banners/8.jpeg');"></div>
                                <img src="../banners/8.jpeg" alt="Banner 8" class="banner-img-main">
                            </div>
                            <div class="carousel-item-enhanced">
                                <div class="banner-blur-bg" style="background-image: url('../banners/9.jpeg');"></div>
                                <img src="../banners/9.jpeg" alt="Banner 9" class="banner-img-main">
                            </div> -->
                            <div class="carousel-item-enhanced">
                                <div class="banner-blur-bg" style="background-image: url('../banners/10.jpeg');"></div>
                                <img src="../banners/10.jpeg" alt="Banner 10" class="banner-img-main">
                            </div>
                            <div class="carousel-item-enhanced">
                                <div class="banner-blur-bg" style="background-image: url('../banners/11.jpeg');"></div>
                                <img src="../banners/11.jpeg" alt="Banner 11" class="banner-img-main">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Hero Section with Parallax -->
            <section class="hero-parallax" data-parallax="scroll" style="margin-top: 20px;">
                <div class="parallax-bg"></div>

                <div class="floating-shapes">
                    <div class="shape shape-1"></div>
                    <div class="shape shape-2"></div>
                    <div class="shape shape-3"></div>
                </div>

                <div class="hero-content-wrapper">

                    <h1 class="hero-title">
                        Welcome to<br>
                        <span class="gradient-text">Community Zokli</span>
                    </h1>

                    <p class="hero-subtitle">
                        A digital social community for the young, pro-tech & dynamic generation.
                        Join 10 million Zoks to express, connect, learn, create, and grow together.
                    </p>

                    <div class="hero-buttons">
                        <?php if (!$is_logged_in): ?>
                        <a href="register.php" class="btn-hero btn-primary">
                            <i class="fas fa-rocket"></i> Register Now
                        </a>
                        <a href="login.php" class="btn-hero btn-outline">
                            <i class="fas fa-sign-in-alt"></i> Sign In
                        </a>
                        <?php else: ?>
                        <a href="profile.php" class="btn-hero btn-primary">
                            <i class="fas fa-user"></i> My Social Profile
                        </a>
                        <a href="community.php" class="btn-hero btn-outline">
                            <i class="fas fa-comments"></i> Community Chat
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Enhanced Carousel Section -->


            <!-- Features with Parallax -->
            <section class="features-parallax">
                <div class="container">
                    <div class="section-header">
                        <h2 class="section-title">Community Zokli Goals</h2>
                        <p class="section-subtitle">
                            Building a hyper-connected knowledge nexus for 10 million members across the nation
                        </p>
                    </div>

                    <div class="features-grid">
                        <div class="feature-card wow fadeInUp" data-wow-delay="0.1s">
                            <div class="feature-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h3 class="feature-title">Knowledge Sharing</h3>
                            <p class="feature-description">
                                Access curated learning paths, expert-led workshops, and peer-to-peer tutoring on any
                                subject. Get help from seasoned professionals and native speakers.
                            </p>
                        </div>

                        <div class="feature-card wow fadeInUp" data-wow-delay="0.2s">
                            <div class="feature-icon">
                                <i class="fas fa-palette"></i>
                            </div>
                            <h3 class="feature-title">Creative Collaboration</h3>
                            <p class="feature-description">
                                Artists collaborate on digital canvases, musicians jam across regions, writers co-author
                                novels, and innovators pitch concepts to potential partners.
                            </p>
                        </div>

                        <div class="feature-card wow fadeInUp" data-wow-delay="0.3s">
                            <div class="feature-icon">
                                <i class="fas fa-lightbulb"></i>
                            </div>
                            <h3 class="feature-title">Collective Intelligence</h3>
                            <p class="feature-description">
                                When challenges arise, 10 million minds mobilize together, brainstorming solutions,
                                crowdsourcing data, and driving real-world impact.
                            </p>
                        </div>

                        <div class="feature-card wow fadeInUp" data-wow-delay="0.4s">
                            <div class="feature-icon">
                                <i class="fas fa-coins"></i>
                            </div>
                            <h3 class="feature-title">Economic Empowerment</h3>
                            <p class="feature-description">
                                A transparent, reward-based economy where members are rewarded for contributions,
                                fostering sustainable talent recognition and compensation.
                            </p>
                        </div>

                        <div class="feature-card wow fadeInUp" data-wow-delay="0.5s">
                            <div class="feature-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="feature-title">Decentralized Governance</h3>
                            <p class="feature-description">
                                Participate in transparent, democratic processes to shape the community's future,
                                allocate resources, and influence policy.
                            </p>
                        </div>

                        <div class="feature-card wow fadeInUp" data-wow-delay="0.6s">
                            <div class="feature-icon">
                                <i class="fas fa-hands-helping"></i>
                            </div>
                            <h3 class="feature-title">Culture of Generosity</h3>
                            <p class="feature-description">
                                Thrive in a community built on sharing expertise, offering support, and celebrating each
                                other's successes. A digital sanctuary fostering belonging.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Vision Section -->
            <section class="features-parallax vision-section">
                <div class="container">
                    <div class="section-header">
                        <h2 class="section-title">Our Vision</h2>
                        <p class="section-subtitle">
                            The Hyper-Connected Knowledge Nexus for 10 Million Zoks
                        </p>
                    </div>

                    <div class="features-grid">
                        <div class="feature-card wow fadeInUp" data-wow-delay="0.1s">
                            <div class="feature-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <h3 class="feature-title">For Learners</h3>
                            <p class="feature-description">
                                Access curated learning paths, expert-led workshops, and peer-to-peer tutoring on any
                                subject imaginable. Get help from seasoned developers, master new languages with native
                                speakers, and grow your skills continuously.
                            </p>
                        </div>

                        <div class="feature-card wow fadeInUp" data-wow-delay="0.2s">
                            <div class="feature-icon">
                                <i class="fas fa-paint-brush"></i>
                            </div>
                            <h3 class="feature-title">For Creators</h3>
                            <p class="feature-description">
                                A curated marketplace for ideas and talent. Artists collaborate on digital canvases,
                                musicians jam across regions, writers co-author novels, and innovators pitch
                                groundbreaking concepts to potential investors and partners.
                            </p>
                        </div>

                        <div class="feature-card wow fadeInUp" data-wow-delay="0.3s">
                            <div class="feature-icon">
                                <i class="fas fa-puzzle-piece"></i>
                            </div>
                            <h3 class="feature-title">For Problem Solvers</h3>
                            <p class="feature-description">
                                A powerful collective intelligence engine. When significant challenges arise, 10 million
                                minds mobilize, brainstorming solutions, crowdsourcing data, and driving real-world
                                impact on social issues and community needs.
                            </p>
                        </div>

                        <div class="feature-card wow fadeInUp" data-wow-delay="0.4s">
                            <div class="feature-icon">
                                <i class="fas fa-compass"></i>
                            </div>
                            <h3 class="feature-title">Personalized Discovery</h3>
                            <p class="feature-description">
                                Sophisticated algorithms curate content, connections, and opportunities tailored to each
                                individual's evolving needs and interests, ensuring every member feels seen, valued, and
                                consistently engaged.
                            </p>
                        </div>

                        <div class="feature-card wow fadeInUp" data-wow-delay="0.5s">
                            <div class="feature-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <h3 class="feature-title">The Spirit of Generosity</h3>
                            <p class="feature-description">
                                A culture where sharing expertise, offering support, and celebrating each other's
                                successes are the norm. A digital sanctuary that fosters belonging and amplifies
                                individual potential through collective goodwill.
                            </p>
                        </div>

                        <div class="feature-card wow fadeInUp" data-wow-delay="0.6s">
                            <div class="feature-icon">
                                <i class="fas fa-globe-americas"></i>
                            </div>
                            <h3 class="feature-title">Power of Collective Action</h3>
                            <p class="feature-description">
                                By pooling resources, knowledge, and intent, this community becomes a formidable force
                                for positive change, driving innovation, promoting social justice, and fostering a more
                                equitable and sustainable world.
                            </p>
                        </div>
                    </div>

                    <div class="section-header" style="margin-top: 60px;">
                        <h3 class="section-subtitle"
                            style="font-size: 1.3rem; color: var(--text-color); max-width: 900px; margin: 0 auto; line-height: 1.8;">
                            Built on principles of <strong style="color: #e67753;">open access</strong>, <strong
                                style="color: #e67753;">intellectual curiosity</strong>, <strong
                                style="color: #e67753;">mutual respect</strong>, and a shared commitment to <strong
                                style="color: #e67753;">positive contribution</strong>. A testament to the power of
                            collective human ingenuity when amplified by technology.
                        </h3>
                    </div>
                </div>
            </section>

            <!-- Stats Counter -->
            <section class="stats-section">
                <div class="container">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number">10M</span>
                            <span class="stat-label">Target Members</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number counter" data-count="50000">0</span>
                            <span class="stat-label">Active Collaborations</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number counter" data-count="1000">0</span>
                            <span class="stat-label">Learning Workshops</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">100%</span>
                            <span class="stat-label">Community Driven</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA Section with Parallax -->
            <section class="cta-parallax" style="">
                <div class="cta-content">
                    <h2 class="cta-title">Become a Proud Zok Today!</h2>
                    <p class="cta-text">
                        Join the hyper-connected knowledge nexus where 10 million members from across the nation
                        converge to learn, create, and drive positive change.
                        Be part of Community Zokli and amplify your potential.
                    </p>
                    <?php if (!$is_logged_in): ?>
                    <a href="register.php" class="btn-hero btn-primary" style="background: white; color: #e67753;">
                        <i class="fas fa-user-plus"></i> Join Community Zokli
                    </a>
                    <?php else: ?>
                    <div class="hero-buttons">
                        <a href="profile.php" class="btn-hero btn-primary" style="background: white; color: #e67753;">
                            <i class="fas fa-arrow-right"></i> My Social Profile
                        </a>
                        <a href="../backend/logout.php" class="btn-hero" style="background: rgba(255,255,255,0.15); border: 2px solid rgba(255,255,255,0.8); color: white;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
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

    <script src="../js/index.js"></script>

</body>

</html>
