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
        margin-top: -40px;
    }

    .hero-badge {
        display: inline-block;
        padding: 8px 20px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 50px;
        color: white;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 0    ;
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
        background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
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
        background: linear-gradient(135deg, #E9437A 0%, #E2AD2A 100%);
        color: white;
        box-shadow: 0 10px 30px rgba(233, 67, 122, 0.4);
        border: none;
    }

    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(233, 67, 122, 0.6);
    }

    .btn-outline {
        background: transparent;
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .btn-outline:hover {
        background: linear-gradient(135deg, #E9437A 0%, #E2AD2A 100%);
        border-color: transparent;
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(233, 67, 122, 0.4);
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

    .shape-1 {
        top: 10%;
        left: 10%;
        width: 100px;
        height: 100px;
        background: var(--gradient-1);
        border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
    }

    .shape-2 {
        top: 60%;
        right: 15%;
        width: 150px;
        height: 150px;
        background: var(--gradient-2);
        border-radius: 63% 37% 54% 46% / 55% 48% 52% 45%;
        animation-delay: -5s;
    }

    .shape-3 {
        bottom: 20%;
        left: 20%;
        width: 80px;
        height: 80px;
        background: var(--gradient-3);
        border-radius: 41% 59% 58% 42% / 45% 60% 40% 55%;
        animation-delay: -10s;
    }

    @keyframes float {

        0%,
        100% {
            transform: translate(0, 0) rotate(0deg);
        }

        25% {
            transform: translate(30px, -30px) rotate(90deg);
        }

        50% {
            transform: translate(-20px, 20px) rotate(180deg);
        }

        75% {
            transform: translate(40px, 10px) rotate(270deg);
        }
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Enhanced Carousel Section */
    .welcome-margin {
        margin-top: 5%;
    }

    .carousel-section {
        padding: 30px 0;
        background: var(--darker);
        position: relative;
        overflow: hidden;
    }

    .section-header {
        text-align: center;
        margin-bottom: 20px;
    }

    .section-title {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 1rem;
        background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
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
        padding: 0; /* Removed padding to reduce gaps */
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
        height: auto;
        display: block;
        border-radius: 20px;
        /* Removed fixed height and black background */
    }

    .carousel-overlay {
        display: none;
    }

    /* Features Grid with Parallax */
    .features-parallax {
        padding: 60px 0;
        background: var(--dark);
        position: relative;
    }

    .vision-section {
        background: transparent;
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
        padding: 25px 20px;
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

    .feature-card:nth-child(2) .feature-icon {
        background: var(--gradient-2);
    }

    .feature-card:nth-child(3) .feature-icon {
        background: var(--gradient-3);
    }

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
        padding: 30px 0;
        background: var(--gradient-1);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
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
        padding: 40px 0;
        background: linear-gradient(135deg, rgba(240, 147, 251, 0.95) 0%, rgba(245, 87, 108, 0.95) 100%),
            url('../assets/images/background/5.jpg') center/cover fixed;
        text-align: center;
        color: white;
    }

    .cta-content {
        max-width: 800px;
        margin: 0 auto;
        padding: 0 20px;
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

    /* Responsive - Tablet/iPad */
    @media (min-width: 769px) and (max-width: 1024px) {
        .welcome-margin {
            margin-top: 10% !important;
        }

        .hero-parallax {
            margin-top: 30px !important;
            height: auto;
            min-height: 60vh;
        }

        .hero-title {
            font-size: 3rem;
        }

        .hero-subtitle {
            font-size: 1.2rem;
        }

        .carousel-item-enhanced img {
            height: auto;
        }

        .section-title {
            font-size: 2.5rem;
        }

        .features-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 40px;
        }

        .features-parallax {
            padding: 40px 0;
            margin-top: 0 !important;
        }

        .stat-number {
            font-size: 3rem;
        }

        .cta-title {
            font-size: 2.5rem;
        }
    }

    /* Responsive - Mobile */
    @media (max-width: 768px) {
        .welcome-margin {
            margin-top: 100px !important; /* Fixed value to clear header */
        }

        .hero-parallax {
            margin-top: -80px !important;
            height: auto;
            min-height: 70vh;
        }

        .hero-title {
            font-size: 2.5rem;
        }

        .hero-subtitle {
            font-size: 1.1rem;
        }

        .section-title {
            font-size: 2rem;
        }

        .cta-title {
            font-size: 2rem;
        }

        .stat-number {
            font-size: 2.5rem;
        }

        .hero-buttons {
            flex-direction: column;
            align-items: center;
        }

        .btn-hero {
            width: 100%;
            max-width: 300px;
            justify-content: center;
        }

        .carousel-item-enhanced img {
            height: auto;
        }

        .features-parallax {
            padding: 30px 0;
            margin-top: 0 !important;
        }

        .features-grid {
            grid-template-columns: 1fr;
            gap: 15px;
            margin-top: 30px;
        }

        .feature-card {
            padding: 20px 15px;
        }

        .stats-section {
            padding: 20px 0;
        }

        .cta-parallax {
            padding: 30px 0;
        }
    }

    /* Large Desktop */
    @media (min-width: 1025px) {
        .hero-parallax {
            margin-top: 30px !important;
        }

        .features-parallax {
            margin-top: 0 !important;
        }
    }

    /* Smooth Scroll */
    html {
        scroll-behavior: smooth;
    }
    </style>
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
                                <img src="../banners/2.jpg" alt="Banner 2">
                            </div>

                            <div class="carousel-item-enhanced">
                                <img src="../banners/3.jpg" alt="Banner 3">
                            </div>

                            <div class="carousel-item-enhanced">
                                <img src="../banners/4.jpg" alt="Banner 4">
                            </div>

                            <div class="carousel-item-enhanced">
                                <img src="../banners/5.jpg" alt="Banner 5">
                            </div>
                            <div class="carousel-item-enhanced">
                                <img src="../banners/6.jpg" alt="Banner 6">
                            </div>
                            <!-- <div class="carousel-item-enhanced">
                                <img src="../banners/7.jpeg" alt="Banner 7">
                            </div>
                            <div class="carousel-item-enhanced">
                                <img src="../banners/8.jpeg" alt="Banner 8">
                            </div>
                            <div class="carousel-item-enhanced">
                                <img src="../banners/9.jpeg" alt="Banner 9">
                            </div> -->
                            <div class="carousel-item-enhanced">
                                <img src="../banners/10.jpeg" alt="Banner 10">
                            </div>
                            <div class="carousel-item-enhanced">
                                <img src="../banners/11.jpeg" alt="Banner 11">
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
                            <i class="fas fa-user"></i> My Zoks Profile
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
                            <i class="fas fa-arrow-right"></i> My Zoks Profile
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
    }, {
        threshold: 0.5
    });

    const statsSection = document.querySelector('.stats-section');
    if (statsSection) {
        counterObserver.observe(statsSection);
    }

    // Initialize Enhanced Carousel
    $(document).ready(function() {
        $('#feature-carousel').owlCarousel({
            loop: true,
            margin: 20,
            nav: false,
            dots: false,
            autoplay: true,
            autoplayTimeout: 4000,
            autoplayHoverPause: true,
            smartSpeed: 600,
            fluidSpeed: true,
            navText: ['<i class="fa fa-chevron-left"></i>', '<i class="fa fa-chevron-right"></i>'],
            responsive: {
                0: {
                    items: 1
                },
                768: {
                    items: 1
                },
                1200: {
                    items: 1
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
    }, {
        threshold: 0.1
    });

    revealElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        revealObserver.observe(el);
    });
    </script>

</body>

</html>