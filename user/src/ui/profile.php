<!DOCTYPE html>
<html lang="en">
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../backend/dbconfig/connection.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
$user_qr = isset($_SESSION['user_qr_id']) ? $_SESSION['user_qr_id'] : '';
$user_user_type = isset($_SESSION['user_user_type']) ? $_SESSION['user_user_type'] : '';
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | ZQR Connect</title>
    <link rel="icon" href="../assets/images/icon-red.png" type="image/gif" sizes="16x16">
    <?php include('../components/csslinks.php') ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
    :root {
        --primary-color: #667eea;
        --primary-dark: #5568d3;
        --accent-color: #f093fb;
        --text-color: #e2e8f0;
        --text-secondary: #94a3b8;
        --bg-color: #0f172a;
        --card-bg: #1e293b;
        --card-hover: #2d3b52;
        --border-color: #334155;
        --input-bg: rgba(255, 255, 255, 0.08);
        --input-border: #475569;
        --success: #10b981;
        --danger: #ef4444;
        --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    body {
        background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #0a0e27 100%);
        color: var(--text-color);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    /* Add spacing for header - transparent fixed header needs margin not padding */
    #content {
        min-height: 100vh;
        position: relative;
        z-index: 1;
    }

    .profile-container {
        max-width: 1400px;
        margin: 0 auto 2rem;
        padding: 2rem 1rem 4rem;
        position: relative;
        z-index: 1;
        margin-top: 120px;
        animation: fadeInUp 0.6s ease;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Page Title */
    .page-title {
        text-align: center;
        margin-bottom: 3rem;
    }

    .page-title h1 {
        font-size: 2.5rem;
        font-weight: 800;
        background: var(--gradient-1);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 0.5rem;
    }

    .page-title p {
        color: var(--text-secondary);
        font-size: 1.1rem;
    }

    .profile-header {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    @media (max-width: 768px) {
        .profile-container {
            margin-top: 100px;
        }
        
        .profile-header {
            grid-template-columns: 1fr;
        }

        .page-title h1 {
            font-size: 2rem;
        }
    }

    .profile-card {
        background: rgba(30, 41, 59, 0.8);
        backdrop-filter: blur(10px);
        border-radius: 1.5rem;
        padding: 2.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .profile-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--gradient-1);
    }

    .profile-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
        border-color: rgba(102, 126, 234, 0.3);
    }

    /* Profile Image Styles - Enhanced */
    .profile-image-container {
        position: relative;
        width: 160px;
        height: 160px;
        margin: 0 auto 1.5rem;
    }

    .profile-image-container::before {
        content: '';
        position: absolute;
        inset: -5px;
        background: var(--gradient-1);
        border-radius: 50%;
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 0;
        animation: pulse 2s infinite;
    }

    .profile-image-container:hover::before {
        opacity: 1;
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 0.5;
            transform: scale(1);
        }
        50% {
            opacity: 0.8;
            transform: scale(1.05);
        }
    }

    #click_profile_img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid var(--card-bg);
        cursor: pointer;
        transition: all 0.3s ease;
        display: block;
        background: var(--gradient-1);
        position: relative;
        z-index: 1;
    }

    #click_profile_img:hover {
        transform: scale(1.05);
        border-color: var(--primary-color);
    }

    .profile-name {
        text-align: center;
        margin-bottom: 1rem;
    }

    .profile-name h2 {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--text-color);
    }

    .profile-username {
        color: var(--text-secondary);
        font-size: 1rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin: 1.5rem 0;
        text-align: center;
    }

    .stat-item {
        padding: 1.25rem;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 0.75rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .stat-item:hover {
        background: rgba(102, 126, 234, 0.1);
        border-color: var(--primary-color);
        transform: translateY(-2px);
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 800;
        background: var(--gradient-1);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        display: block;
        margin-bottom: 0.25rem;
    }

    .stat-label {
        font-size: 0.875rem;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    /* QR Section Enhanced */
    .qr-section {
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .qr-section h3 {
        font-size: 1.3rem;
        margin-bottom: 1rem;
        color: var(--text-color);
        font-weight: 600;
    }

    .qr-container {
        background: white;
        padding: 0;
        border-radius: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 1rem auto;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
        position: relative;
        width: 300px;
        height: 300px;
    }

    .qr-container:hover {
        transform: scale(1.05);
        box-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
    }

    /* Custom frame overlay */
    .qr-frame-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 10;
    }

    #click_banner_img {
        position: relative;
        z-index: 5;
        width: 192px;
        height: 192px;
    }

    .qr-actions {
        display: flex;
        gap: 0.75rem;
        justify-content: center;
        margin-top: 1rem;
        flex-wrap: wrap;
    }

    /* Form Sections */
    .form-section {
        margin-bottom: 2.5rem;
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid rgba(102, 126, 234, 0.2);
    }

    .section-header i {
        font-size: 1.5rem;
        color: var(--primary-color);
    }

    .section-header h3 {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--text-color);
        margin: 0;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.75rem;
        font-weight: 600;
        color: var(--text-color);
        font-size: 0.95rem;
    }

    .form-label i {
        margin-right: 0.5rem;
        color: var(--primary-color);
    }

    .form-control {
        width: 100%;
        padding: 0.875rem 1.125rem;
        background: var(--input-bg);
        border: 2px solid var(--input-border);
        border-radius: 0.75rem;
        color: var(--text-color);
        transition: all 0.3s ease;
        font-size: 0.95rem;
        font-family: inherit;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        background: rgba(255, 255, 255, 0.12);
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        transform: translateY(-1px);
    }

    .form-control[readonly] {
        background: rgba(255, 255, 255, 0.03);
        border-color: var(--border-color);
        color: var(--text-secondary);
        cursor: not-allowed;
    }

    .form-control::placeholder {
        color: #64748b;
        opacity: 1;
    }

    .form-control:hover:not([readonly]):not(:focus) {
        border-color: var(--primary-color);
        background: rgba(255, 255, 255, 0.1);
    }

    .input-group {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .input-group .form-control {
        flex: 1;
    }

    .input-group-append {
        display: flex;
        gap: 0.75rem;
        align-items: center;
    }

    /* Enhanced Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.625rem;
        padding: 0.875rem 1.75rem;
        border-radius: 0.75rem;
        font-weight: 600;
        transition: all 0.3s ease;
        cursor: pointer;
        border: none;
        text-decoration: none;
        font-size: 0.95rem;
        position: relative;
        overflow: hidden;
    }

    .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }

    .btn:hover::before {
        left: 100%;
    }

    .btn-primary {
        background: var(--gradient-1);
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
    }

    .btn-primary:active {
        transform: translateY(0);
    }

    .btn-outline {
        background: transparent;
        border: 2px solid var(--primary-color);
        color: var(--primary-color);
    }

    .btn-outline:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-2px);
    }

    .btn-outline-danger {
        background: transparent;
        border: 2px solid var(--danger);
        color: var(--danger);
    }

    .btn-outline-danger:hover {
        background: var(--danger);
        color: white;
        transform: translateY(-2px);
    }

    .btn-secondary {
        background: rgba(255, 255, 255, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.2);
        color: var(--text-color);
    }

    .btn-secondary:hover {
        background: var(--card-hover);
        border-color: var(--primary-color);
        transform: translateY(-2px);
    }

    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none !important;
    }

    /* Public Toggle Switch - Modern */
    .public-toggle {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        font-size: 0.875rem;
        user-select: none;
    }

    .public-toggle-input {
        width: 2.75rem;
        height: 1.5rem;
        border-radius: 1rem;
        border: 2px solid var(--border-color);
        appearance: none;
        cursor: pointer;
        position: relative;
        background: var(--input-bg);
        transition: all 0.3s ease;
    }

    .public-toggle-input::before {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 1rem;
        height: 1rem;
        border-radius: 50%;
        background: var(--text-secondary);
        transition: all 0.3s ease;
    }

    .public-toggle-input:checked {
        background: var(--gradient-1);
        border-color: var(--primary-color);
    }

    .public-toggle-input:checked::before {
        left: calc(100% - 1.25rem);
        background: white;
    }

    .public-toggle label {
        cursor: pointer;
        color: var(--text-color);
        font-weight: 500;
    }

    /* Color Picker Enhanced */
    .color-picker-container {
        display: flex;
        gap: 1.5rem;
        align-items: center;
        margin-top: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .color-picker-group {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
    }

    .color-picker-group label {
        font-size: 0.875rem;
        color: var(--text-secondary);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .color-picker {
        width: 60px;
        height: 60px;
        padding: 0;
        border: 3px solid var(--border-color);
        border-radius: 50%;
        cursor: pointer;
        background: none;
        transition: all 0.3s ease;
    }

    .color-picker:hover {
        border-color: var(--primary-color);
        transform: scale(1.1);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    }

    .color-picker::-webkit-color-swatch-wrapper {
        padding: 0;
        border-radius: 50%;
    }

    .color-picker::-webkit-color-swatch {
        border: none;
        border-radius: 50%;
    }

    /* Action Buttons Container */
    .action-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2.5rem;
        padding-top: 2rem;
        border-top: 2px solid rgba(255, 255, 255, 0.1);
        flex-wrap: wrap;
    }

    .action-buttons .btn {
        min-width: 180px;
    }

    /* Social Media Grid */
    .social-media-section {
        margin-top: 2rem;
    }

    .social-media-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 1.5rem;
        margin-top: 1rem;
    }

    @media (max-width: 768px) {
        .social-media-grid {
            grid-template-columns: 1fr;
        }

        .action-buttons {
            flex-direction: column;
        }

        .action-buttons .btn {
            width: 100%;
        }
    }

    /* Follow button container */
    #follow-btn-container {
        margin: 1.5rem 0;
        text-align: center;
        pointer-events: auto;
    }

    #follow-btn-container .btn {
        padding: 0.875rem 2rem;
        font-size: 1rem;
        min-width: 150px;
    }
        border-radius: 25px;
        min-width: 150px;
        pointer-events: auto;
    }

    /* Action buttons */
    .action-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2rem;
        flex-wrap: wrap;
    }

    /* Modal Styles - Fixed */
    .modal-content {
        background-color: var(--card-bg);
        color: var(--text-color);
        border: 1px solid var(--border-color);
    }

    .modal-header {
        border-bottom: 1px solid var(--border-color);
    }

    .modal-footer {
        border-top: 1px solid var(--border-color);
    }

    .modal-body {
        max-height: 400px;
        overflow-y: auto;
    }

    .modal .close {
        color: var(--text-color);
        opacity: 1;
        font-size: 1.5rem;
        font-weight: bold;
        text-shadow: none;
    }

    .modal .close:hover {
        color: var(--primary-color);
        opacity: 1;
    }

    .modal .close:focus {
        outline: none;
    }

    /* Public View Mode Styles */
    body.public-view-mode .form-control {
        background-color: rgba(255, 255, 255, 0.1) !important;
        border: 1px solid #444 !important;
        color: #fff !important;
        cursor: default !important;
        pointer-events: none;
    }

    body.public-view-mode .form-control[data-has-link="true"] {
        color: #007bff !important;
        border-color: #007bff !important;
    }

    body.public-view-mode .public-visit-link {
        background-color: transparent !important;
        border: 1px solid #007bff !important;
        color: #007bff !important;
        pointer-events: auto;
    }


    .modal-backdrop {
        z-index: 1040 !important;
    }

    .modal {
        z-index: 1050 !important;
    }

    body.public-view-mode .public-visit-link:hover {
        background-color: #007bff !important;
        color: #fff !important;
    }

    body.public-view-mode .input-group-append {
        pointer-events: auto;
    }

    body.public-view-mode #click_profile_img {
        cursor: default;
    }

    body.public-view-mode #click_profile_img:hover {
        transform: none;
    }



    /* Hide empty form sections */
    .form-group:empty,
    .col-md-3:has(.form-control[value=""]),
    .col-md-4:has(.form-control[value=""]) {
        display: none;
    }

    /* Utility classes */
    .hidden {
        display: none !important;
    }

    .text-center {
        text-align: center;
    }

    .mt-3 {
        margin-top: 1rem;
    }

    .mt-4 {
        margin-top: 1.5rem;
    }

    .mb-3 {
        margin-bottom: 1rem;
    }

    .mb-4 {
        margin-bottom: 1.5rem;
    }

    .d-flex {
        display: flex;
    }

    .align-items-center {
        align-items: center;
    }

    .fw-bold {
        font-weight: bold;
    }

    .text-muted {
        color: #6c757d;
    }

    .me-3 {
        margin-right: 1rem;
    }

    .border-bottom {
        border-bottom: 1px solid var(--border-color);
    }

    .rounded-circle {
        border-radius: 50%;
    }

    /* Social link icons */
    .social-link-icon {
        width: 20px;
        text-align: center;
    }

    /* Followers/Following clickable styling */
    #followers-count,
    #following-count {
        cursor: pointer;
        transition: color 0.3s ease;
    }

    #followers-count:hover,
    #following-count:hover {
        color: #5558dd !important;
        text-decoration: underline;
    }

    /* Responsive adjustments */
    @media (max-width: 1200px) {
        .profile-container {
            max-width: 1000px;
        }
    }

    @media (max-width: 768px) {
        .profile-container {
            padding: 0 0.5rem;
        }

        .profile-card {
            padding: 1.5rem;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .social-media-grid {
            grid-template-columns: 1fr;
        }

        .action-buttons {
            justify-content: stretch;
        }

        .action-buttons .btn {
            flex: 1;
            justify-content: center;
        }
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
            <!-- Hidden fields for JavaScript -->
            <input type="hidden" id="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
            <input type="hidden" id="user_qr" value="<?php echo htmlspecialchars($user_qr); ?>">
            <input type="hidden" id="user_type" value="<?php echo htmlspecialchars($user_user_type); ?>">

            <div class="profile-container">
                <!-- Page Title -->
                <div class="page-title">
                    <h1><i class="fas fa-user-circle"></i> My Profile</h1>
                    <p>Manage your QR profile, customize your settings, and connect with others</p>
                </div>

                <div class="profile-header">
                    <!-- Left side: Profile Info -->
                    <div class="profile-card">
                        <div class="profile-image-container">
                            <img src="" alt="Profile" class="profile-image" id="click_profile_img">
                            <input type="file" id="upload_profile_img" hidden accept="image/*">
                        </div>

                <h2 class="text-center" id="user-name">Loading</h2>

                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value" id="followers-count">0</div>
                        <div class="stat-label">Followers</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="following-count">0</div>
                        <div class="stat-label">Following</div>
                    </div>
                </div>

                <div id="follow-btn-container" class="hidden">
                    <!-- Follow button will be injected by JavaScript -->
                </div>
            </div>

            <!-- Right side: QR Code -->
            <div class="profile-card">
                <div class="qr-section">
                    <h3>Profile QR Code</h3>
                    <div class="qr-container" id="qr-frame-container">
                        <img id="click_banner_img" alt="QR Code" style="width: 192px; height: 192px;"
                            src="https://via.placeholder.com/192">
                        <img src="../assets/images/frame.png" class="qr-frame-overlay" alt="Frame">
                    </div>

                    <div class="qr-actions">
                        <button class="btn btn-secondary" id="download-qr-btn">
                            <i class="fas fa-download"></i> Download QR
                        </button>
                    </div>

                    <div id="qr-color-controls" class="hidden">
                        <div class="color-picker-container">
                            <div class="color-picker-group">
                                <label>Foreground:</label>
                                <input type="color" id="qr-color-dark" class="color-picker" value="#000000">
                            </div>
                            <div class="color-picker-group">
                                <label>Background:</label>
                                <input type="color" id="qr-color-light" class="color-picker" value="#FFFFFF">
                            </div>
                        </div>
                        <button class="btn btn-primary mt-3" id="save-qr-color">
                            <i class="fas fa-save"></i> Save QR Colors
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Information -->
        <div class="profile-card">
            <form id="form-create-item">
                <!-- Basic Information Section -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fas fa-user-edit"></i>
                        <h3>Basic Information</h3>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="full_name">
                                <i class="fas fa-user"></i> Full Name
                            </label>
                            <input type="text" id="full_name" name="full_name" class="form-control" 
                                   placeholder="Enter your full name" value="">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="phone_number">
                                <i class="fas fa-phone"></i> Phone Number
                            </label>
                            <input type="tel" id="phone_number" name="phone_number" class="form-control" 
                                   placeholder="+1 (555) 123-4567" value="">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="email_address">
                                <i class="fas fa-envelope"></i> Email Address
                            </label>
                            <input type="email" id="email_address" name="email_address" class="form-control"
                                   placeholder="your.email@example.com">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="address">
                                <i class="fas fa-map-marker-alt"></i> Address
                            </label>
                            <input type="text" id="address" name="address" class="form-control" 
                                   placeholder="123 Main St, City, Country" value="">
                        </div>
                    </div>
                </div>

                <!-- Social Media Links -->
                <div class="form-section social-media-section">
                    <div class="section-header">
                        <i class="fas fa-share-alt"></i>
                        <h3>Social Media Links</h3>
                    </div>
                    <div class="social-media-grid">
                        <div class="form-group">
                            <label class="form-label" for="website">
                                <i class="fas fa-globe"></i> Website URL
                            </label>
                            <div class="input-group">
                                <input type="text" id="website" name="website" class="form-control">
                                <div class="input-group-append">
                                    <div class="public-toggle">
                                        <input type="checkbox" id="public_website" class="public-toggle-input"
                                            data-field="website">
                                        <label for="public_website">Public</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="whatsapp_link">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </label>
                            <div class="input-group">
                                <input type="text" id="whatsapp_link" name="whatsapp_link" class="form-control"
                                    placeholder="https://wa.me/1234567890" value="">
                                <div class="input-group-append">
                                    <div class="public-toggle">
                                        <input type="checkbox" id="public_whatsapp_link" class="public-toggle-input"
                                            data-field="whatsapp_link" checked>
                                        <label for="public_whatsapp_link">Public</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="telegram_link">
                                <i class="fab fa-telegram"></i> Telegram
                            </label>
                            <div class="input-group">
                                <input type="text" id="telegram_link" name="telegram_link" class="form-control"
                                    placeholder="https://t.me/username" value="">
                                <div class="input-group-append">
                                    <div class="public-toggle">
                                        <input type="checkbox" id="public_telegram_link" class="public-toggle-input"
                                            data-field="telegram_link" checked>
                                        <label for="public_telegram_link">Public</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="twitter_username">
                                <i class="fab fa-twitter"></i> Twitter
                            </label>
                            <div class="input-group">
                                <input type="text" id="twitter_username" name="twitter_username" class="form-control"
                                    placeholder="@username">
                                <div class="input-group-append">
                                    <div class="public-toggle">
                                        <input type="checkbox" id="public_twitter_username" class="public-toggle-input"
                                            data-field="twitter_username">
                                        <label for="public_twitter_username">Public</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="instagram_username">
                                <i class="fab fa-instagram"></i> Instagram
                            </label>
                            <div class="input-group">
                                <input type="text" id="instagram_username" name="instagram_username"
                                    class="form-control" placeholder="@username" value="">
                                <div class="input-group-append">
                                    <div class="public-toggle">
                                        <input type="checkbox" id="public_instagram_username"
                                            class="public-toggle-input" data-field="instagram_username" checked>
                                        <label for="public_instagram_username">Public</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="youtube_username">
                                <i class="fab fa-youtube"></i> YouTube
                            </label>
                            <div class="input-group">
                                <input type="text" id="youtube_username" name="youtube_username" class="form-control"
                                    placeholder="@channelname" value="">
                                <div class="input-group-append">
                                    <div class="public-toggle">
                                        <input type="checkbox" id="public_youtube_username" class="public-toggle-input"
                                            data-field="youtube_username" checked>
                                        <label for="public_youtube_username">Public</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="linkedin_username">
                                <i class="fab fa-linkedin"></i> LinkedIn
                            </label>
                            <div class="input-group">
                                <input type="text" id="linkedin_username" name="linkedin_username" class="form-control"
                                    placeholder="https://linkedin.com/in/username" value="">
                                <div class="input-group-append">
                                    <div class="public-toggle">
                                        <input type="checkbox" id="public_linkedin_username" class="public-toggle-input"
                                            data-field="linkedin_username" checked>
                                        <label for="public_linkedin_username">Public</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="snapchat_username">
                                <i class="fab fa-snapchat"></i> Snapchat
                            </label>
                            <div class="input-group">
                                <input type="text" id="snapchat_username" name="snapchat_username" class="form-control"
                                    placeholder="username" value="">
                                <div class="input-group-append">
                                    <div class="public-toggle">
                                        <input type="checkbox" id="public_snapchat_username" class="public-toggle-input"
                                            data-field="snapchat_username" checked>
                                        <label for="public_snapchat_username">Public</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary" id="update-profile-btn">
                        <i class="fas fa-save"></i> Save All Changes
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="location.reload()">
                        <i class="fas fa-undo"></i> Reset Form
                    </button>
                </div>
            </form>
        </div>
        <div id="toast" class="toast"></div>
    </div>
    
    </div>
    <!-- content close -->

    <a href="#" id="back-to-top"></a>

    <!-- footer begin -->
    <?php include('../components/footer.php'); ?>
    <!-- footer close -->

    </div>
    <!-- wrapper close -->
    
    <?php include('../components/jslinks.php'); ?>
    <script src="../components/qr/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
    <script src="./custom_js/custom_profile_new.js"></script>
</body>

</html>