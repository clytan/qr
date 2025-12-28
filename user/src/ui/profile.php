<!DOCTYPE html>
<html lang="en">
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../backend/dbconfig/connection.php';

// Check if accessing via QR parameter (public profile view)
$viewing_qr = isset($_GET['qr']) || isset($_GET['QR']);
$viewed_qr = $viewing_qr ? (isset($_GET['qr']) ? $_GET['qr'] : $_GET['QR']) : '';

// Only require authentication if NOT viewing a public profile
if (!$viewing_qr) {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header('Location: index.php');
        exit();
    }
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
$user_qr = isset($_SESSION['user_qr_id']) ? $_SESSION['user_qr_id'] : '';
$user_user_type = isset($_SESSION['user_user_type']) ? $_SESSION['user_user_type'] : '';

// Check if logged-in user is viewing someone else's profile
$is_viewing_other_profile = $viewing_qr && !empty($user_id) && $viewed_qr !== $user_qr;
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | ZQR Connect</title>
    <link rel="icon" href="../../../logo/logo.png" type="image/gif" sizes="16x16">
    <?php include('../components/csslinks.php') ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom CSS -->
<link rel="stylesheet" href="../css/profile.css">
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
                <!-- Navigation Tabs -->
                <!-- <div class="nav-tabs-container">
                    <ul class="nav nav-tabs mb-4">
                        <li class="nav-item">
                            <a class="nav-link" href="/user/src/ui/index.php">
                                <i class="fas fa-home"></i> Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/user/src/ui/community.php">
                                <i class="fas fa-users"></i> Community
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/user/src/ui/wallet.php">
                                <i class="fas fa-wallet"></i> Wallet
                            </a>
                        </li>
                    </ul>
                </div> -->

                <!-- Page Title -->
                <div class="page-title d-none">
                    <h1><i class="fas fa-user-circle"></i> My Profile</h1>
                    <p>Manage your QR profile, customize your settings, and connect with others</p>
                </div>

                <div class="profile-header" style="margin-top: 40%;">
                    <!-- Left side: Profile Info -->
                    <div class="profile-card" style="position: relative;">
                        <div id="follow-btn-container" class="<?php echo $is_viewing_other_profile ? '' : 'hidden'; ?>">
                            <!-- Follow button will be injected by JavaScript -->
                        </div>
                        <div class="profile-image-container">
                            <img src="" alt="Profile" class="profile-image" id="click_profile_img">
                            <input type="file" id="upload_profile_img" hidden accept="image/*">
                        </div>


                        <h2 id="user-name">Loading</h2>
                        <p id="user-qr-id" style="text-align: center; color: var(--text-secondary); margin-top: -20px; margin-bottom: 20px; font-weight: 500; letter-spacing: 0.5px; width: 100%;">@Loading</p>

                        <div class="stats-grid" style="margin-top: -4%;">
                            <div class="stat-item" id="followers-count">
                                <span class="stat-value">0</span>
                                <span class="stat-label">followers</span>
                            </div>
                            <div class="stat-item" id="following-count">
                                <span class="stat-value">0</span>
                                <span class="stat-label">following</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right side: QR Code -->
                    <div class="profile-card" style="margin-top: -22%;">
                        <div class="qr-section">
                            <div class="qr-container" id="qr-frame-container">
                                <img src="../assets/images/frame.png" class="qr-frame-overlay" alt="Frame">
                            </div>

                            <!-- QR Actions Dropdown -->
                            <div class="qr-action-menu-container" style="width: 100%; margin-top: 20px; position: relative;">
                                <button class="btn btn-secondary" id="qr-menu-btn" style="width: 100%; justify-content: space-between; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px 16px;">
                                    <span style="font-weight: 600; color: #fff;"><i class="fas fa-sliders-h" style="margin-right: 8px; color: var(--primary-color);"></i> Profile Actions</span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="qr-dropdown-menu" id="qr-dropdown" style="display: none;">
                                    <button class="qr-menu-item" id="download-qr-btn">
                                        <i class="fas fa-download"></i> Download QR
                                    </button>
                                    <button class="qr-menu-item" id="share-profile-btn">
                                        <i class="fas fa-share-alt"></i> Share Profile
                                    </button>
                                    
                                    <?php if (!$viewing_qr): ?>
                                        <button class="qr-menu-item" id="choose-frame-btn">
                                            <i class="fas fa-image"></i> Choose Frame
                                        </button>
                                        <button class="qr-menu-item" id="subscription-btn" style="display: none;">
                                            <i class="fas fa-credit-card"></i> <span id="subscription-btn-text">Subscription Status</span>
                                        </button>
                                        <button class="qr-menu-item" id="toggle-colors-btn" onclick="document.getElementById('qr-color-controls').classList.toggle('d-none');">
                                            <i class="fas fa-palette"></i> Customize Colors
                                        </button>
                                        
                                        <!-- Color Controls Nested Inside Dropdown -->
                                        <div id="qr-color-controls" class="d-none" style="padding: 15px; background: rgba(0,0,0,0.3); border-radius: 8px; margin-top: 5px; margin-bottom: 5px;">
                                            <div class="color-picker-container">
                                                <div class="color-picker-group">
                                                    <label class="form-label text-muted small mb-1">FOREGROUND</label>
                                                    <input type="color" id="qr-color-dark" class="color-picker" value="#000000" style="width: 50px; height: 50px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.2); cursor: pointer; padding: 0;">
                                                </div>
                                                <div class="color-picker-group">
                                                    <label class="form-label text-muted small mb-1">BACKGROUND</label>
                                                    <input type="color" id="qr-color-light" class="color-picker" value="#FFFFFF" style="width: 50px; height: 50px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.2); cursor: pointer; padding: 0;">
                                                </div>
                                            </div>
                                            <button class="btn btn-primary mt-3 w-100 d-none" id="save-qr-color">
                                                <i class="fas fa-save"></i> Save QR Colors
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <a href="../backend/profile_new/generate_vcard.php?qr=<?php echo htmlspecialchars($viewed_qr); ?>" class="qr-menu-item">
                                            <i class="fas fa-address-book"></i> Save to Contacts
                                        </a>
                                    <?php endif; ?>
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

                                <div class="form-group address-header-group">
                                    <div class="social-input-header">
                                        <label class="form-label">
                                            <i class="fas fa-map-marker-alt"></i> Address Information
                                        </label>
                                        <div class="public-toggle">
                                            <input type="checkbox" id="public_address" class="public-toggle-input"
                                                data-field="address" checked>
                                            <span class="toggle-label">Public</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group address-field-group">
                                    <label class="form-label" for="address">
                                        <i class="fas fa-map-marker-alt"></i> Address
                                    </label>
                                    <input type="text" id="address" name="address" class="form-control"
                                        placeholder="123 Main St, City, Country" value="">
                                </div>

                                <div class="form-group address-field-group">
                                    <label class="form-label" for="landmark">
                                        <i class="fas fa-map-pin"></i> Landmark
                                    </label>
                                    <input type="text" id="landmark" name="landmark" class="form-control"
                                        placeholder="Near City Mall" value="">
                                </div>

                                <div class="form-group address-field-group">
                                    <label class="form-label" for="pincode">
                                        <i class="fas fa-map-marked-alt"></i> Pincode
                                    </label>
                                    <input type="text" id="pincode" name="pincode" class="form-control"
                                        placeholder="123456" value="" maxlength="6">
                                </div>

                                <!-- Google Maps Link (Business only) -->
                                <div class="form-group address-field-group business-only-field" id="google-maps-field" style="display: none;">
                                    <label class="form-label" for="google_maps_link">
                                        <i class="fas fa-map-marker-alt" style="color: #EA4335;"></i> Google Maps Link
                                    </label>
                                    <div class="social-input-field">
                                        <input type="url" id="google_maps_link" name="google_maps_link" class="form-control"
                                            placeholder="Paste your Google Maps location link">
                                        <button type="button" class="btn-link" onclick="if($('#google_maps_link').val()) window.open($('#google_maps_link').val(), '_blank');">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Social Media Links -->
                        <div class="form-section social-media-section" id="social-media-section">
                            <div class="section-header">
                                <i class="fas fa-share-alt"></i>
                                <h3>Social Media Links</h3>
                            </div>
                            <div class="social-media-grid">

                                <!-- Priority 2: Instagram -->
                                <div class="form-group social-input-container">
                                    <div class="social-input-header">
                                        <label class="form-label" for="instagram_username">
                                            <i class="fab fa-instagram"></i> Instagram
                                        </label>
                                        <div class="public-toggle">
                                            <input type="checkbox" id="public_instagram_username"
                                                class="public-toggle-input" data-field="instagram_username" checked>
                                            <span class="toggle-label">Public</span>
                                        </div>
                                    </div>
                                    <div class="social-input-field">
                                        <input type="text" id="instagram_username" name="instagram_username"
                                            class="form-control" placeholder="Enter your Instagram username">
                                        <button type="button" class="btn-link">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                    </div>
                                </div>
                                <!-- Priority 1: Facebook -->
                                <div class="form-group social-input-container">
                                    <div class="social-input-header">
                                        <label class="form-label" for="facebook_username">
                                            <i class="fab fa-facebook"></i> Facebook
                                        </label>
                                        <div class="public-toggle">
                                            <input type="checkbox" id="public_facebook_username"
                                                class="public-toggle-input" data-field="facebook_username" checked>
                                            <span class="toggle-label">Public</span>
                                        </div>
                                    </div>
                                    <div class="social-input-field">
                                        <input type="text" id="facebook_username" name="facebook_username"
                                            class="form-control" placeholder="Enter your Facebook username">
                                        <button type="button" class="btn-link">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                    </div>
                                </div>



                                <!-- Priority 3: YouTube -->
                                <div class="form-group social-input-container">
                                    <div class="social-input-header">
                                        <label class="form-label" for="youtube_username">
                                            <i class="fab fa-youtube"></i> YouTube
                                        </label>
                                        <div class="public-toggle">
                                            <input type="checkbox" id="public_youtube_username"
                                                class="public-toggle-input" data-field="youtube_username" checked>
                                            <span class="toggle-label">Public</span>
                                        </div>
                                    </div>
                                    <div class="social-input-field">
                                        <input type="text" id="youtube_username" name="youtube_username"
                                            class="form-control" placeholder="Enter your YouTube channel">
                                        <button type="button" class="btn-link">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- WhatsApp -->
                                <div class="form-group social-input-container">
                                    <div class="social-input-header">
                                        <label class="form-label" for="whatsapp_link">
                                            <i class="fab fa-whatsapp"></i> WhatsApp
                                        </label>
                                        <div class="public-toggle">
                                            <input type="checkbox" id="public_whatsapp_link" class="public-toggle-input"
                                                data-field="whatsapp_link" checked>
                                            <span class="toggle-label">Public</span>
                                        </div>
                                    </div>
                                    <div class="social-input-field">
                                        <input type="text" id="whatsapp_link" name="whatsapp_link" class="form-control"
                                            placeholder="Enter your WhatsApp number">
                                        <button type="button" class="btn-link">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Twitter -->
                                <div class="form-group social-input-container">
                                    <div class="social-input-header">
                                        <label class="form-label" for="twitter_username">
                                            <i class="fab fa-x-twitter"></i> X
                                        </label>
                                        <div class="public-toggle">
                                            <input type="checkbox" id="public_twitter_username"
                                                class="public-toggle-input" data-field="twitter_username">
                                            <span class="toggle-label">Public</span>
                                        </div>
                                    </div>
                                    <div class="social-input-field">
                                        <input type="text" id="twitter_username" name="twitter_username"
                                            class="form-control" placeholder="Enter your X username">
                                        <button type="button" class="btn-link">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- LinkedIn -->
                                <div class="form-group social-input-container">
                                    <div class="social-input-header">
                                        <label class="form-label" for="linkedin_username">
                                            <i class="fab fa-linkedin"></i> LinkedIn
                                        </label>
                                        <div class="public-toggle">
                                            <input type="checkbox" id="public_linkedin_username"
                                                class="public-toggle-input" data-field="linkedin_username" checked>
                                            <span class="toggle-label">Public</span>
                                        </div>
                                    </div>
                                    <div class="social-input-field">
                                        <input type="text" id="linkedin_username" name="linkedin_username"
                                            class="form-control" placeholder="Enter your LinkedIn profile URL">
                                        <button type="button" class="btn-link">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Telegram -->
                                <div class="form-group social-input-container">
                                    <div class="social-input-header">
                                        <label class="form-label" for="telegram_link">
                                            <i class="fab fa-telegram"></i> Telegram
                                        </label>
                                        <div class="public-toggle">
                                            <input type="checkbox" id="public_telegram_link" class="public-toggle-input"
                                                data-field="telegram_link" checked>
                                            <span class="toggle-label">Public</span>
                                        </div>
                                    </div>
                                    <div class="social-input-field">
                                        <input type="text" id="telegram_link" name="telegram_link" class="form-control"
                                            placeholder="Enter your Telegram username">
                                        <button type="button" class="btn-link">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Snapchat -->
                                <div class="form-group social-input-container">
                                    <div class="social-input-header">
                                        <label class="form-label" for="snapchat_username">
                                            <i class="fab fa-snapchat"></i> Snapchat
                                        </label>
                                        <div class="public-toggle">
                                            <input type="checkbox" id="public_snapchat_username"
                                                class="public-toggle-input" data-field="snapchat_username" checked>
                                            <span class="toggle-label">Public</span>
                                        </div>
                                    </div>
                                    <div class="social-input-field">
                                        <input type="text" id="snapchat_username" name="snapchat_username"
                                            class="form-control" placeholder="Enter your Snapchat username">
                                        <button type="button" class="btn-link">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Website -->
                                <div class="form-group social-input-container">
                                    <div class="social-input-header">
                                        <label class="form-label" for="website">
                                            <i class="fas fa-globe"></i> Website
                                        </label>
                                        <div class="public-toggle">
                                            <input type="checkbox" id="public_website" class="public-toggle-input"
                                                data-field="website">
                                            <span class="toggle-label">Public</span>
                                        </div>
                                    </div>
                                    <div class="social-input-field">
                                        <input type="text" id="website" name="website" class="form-control"
                                            placeholder="Enter your website URL">
                                        <button type="button" class="btn-link">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Booster Section (for Gold/Silver users) -->
                        <div id="profile-booster-section" class="profile-booster-section">
                            <div class="booster-header">
                                <i class="fas fa-rocket"></i>
                                <h4>Super Charge Your Profile</h4>
                            </div>
                            <div class="d-none">
                                <p class="booster-description">
                                    Boost your profile to appear at the top of search results and get more visibility!
                                </p>
                                <div class="booster-benefits">
                                    <div class="benefit-item">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Top of search results for 7 days</span>
                                    </div>
                                    <div class="benefit-item">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Highlighted profile badge</span>
                                    </div>
                                    <div class="benefit-item">
                                        <i class="fas fa-check-circle"></i>
                                        <span>3x more profile views</span>
                                    </div>
                                </div>
                                <div class="booster-price">
                                    <span class="price-label">Boost Cost:</span>
                                    <span class="price-value">₹199</span>
                                </div>
                            </div>

                            <!-- Existing Links List -->
                            <div id="supercharge-links-list" class="d-none" style="margin-bottom: 1rem;">
                                <h5 style="font-size: 1rem; margin-bottom: 0.75rem; color: var(--text-color);">
                                    <i class="fas fa-list"></i> Your Supercharge Links
                                </h5>
                                <div id="links-container"></div>
                            </div>

                            <!-- Add New Link Form -->
                            <div id="supercharge-form" class="d-none">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-link"></i> Add New Supercharge Link
                                    </label>
                                    <input type="url" class="form-control" id="supercharge_link"
                                        placeholder="Enter your link to supercharge your profile">
                                    <small class="form-text text-muted">Provide a link that showcases your work,
                                        portfolio, or content</small>
                                </div>
                            </div>
                            <button class="btn btn-boost" id="btn-super-charge">
                                <i class="fas fa-bolt"></i> Super Charge Now
                            </button>
                        </div>

                        <?php if (!$viewing_qr): ?>
                            <div class="action-buttons">
                                <button type="submit" class="btn btn-primary" id="update-profile-btn">
                                    <i class="fas fa-save"></i> Save
                                </button>
                                <a href="../backend/logout.php" class="btn btn-danger">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
                <div id="toast" class="toast"></div>
                
                <!-- Subscription Modal -->
                <?php if (!$viewing_qr): ?>
                <div class="modal fade" id="subscriptionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="false" data-bs-keyboard="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px;">
                            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.1); padding: 1.25rem;">
                                <h5 class="modal-title" style="color: var(--text-color); font-weight: 600;">
                                    <i class="fas fa-credit-card" style="color: var(--primary-color); margin-right: 8px;"></i>
                                    Subscription Status
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="subscription-modal-content" style="padding: 1.5rem;">
                                <div class="text-center py-4">
                                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i>
                                    <p style="margin-top: 1rem; color: var(--text-secondary);">Loading subscription info...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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

    <!-- Frame Selector Modal -->
    <div class="modal fade" id="frameModal" tabindex="-1" aria-labelledby="frameModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="background: var(--card-bg); border: 1px solid var(--border-color);">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
                    <h5 class="modal-title" id="frameModalLabel" style="color: var(--text-color);">
                        <i class="fas fa-image"></i> Choose QR Frame
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="frame-grid" class="frame-grid">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--primary-color);"></i>
                            <p class="mt-2" style="color: var(--text-secondary);">Loading frames...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="../js/profile.js"></script>
</body>

</html>
