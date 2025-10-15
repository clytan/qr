<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
$user_qr = isset($_SESSION['user_qr_id']) ? $_SESSION['user_qr_id'] : '';
$user_user_type = isset($_SESSION['user_user_type']) ? $_SESSION['user_user_type'] : '';
?>
<!-- Hidden fields for JavaScript -->
<input type="hidden" id="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
<input type="hidden" id="user_qr" value="<?php echo htmlspecialchars($user_qr); ?>">
<input type="hidden" id="user_type" value="<?php echo htmlspecialchars($user_user_type); ?>">
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | QR Connect</title>
    <?php include('../components/csslinks.php') ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
    :root {
        --primary-color: #6366f1;
        --text-color: #e2e8f0;
        --bg-color: #0f172a;
        --card-bg: #1e293b;
        --border-color: #334155;
        --input-bg: rgba(255, 255, 255, 0.1);
        --input-border: #475569;
    }

    body {
        background-color: var(--bg-color);
        color: var(--text-color);
        font-family: 'Inter', sans-serif;
    }

    .profile-container {
        max-width: 1400px;
        margin: 2rem auto;
        padding: 0 1rem;
    }

    .profile-header {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    @media (max-width: 768px) {
        .profile-header {
            grid-template-columns: 1fr;
        }
    }

    .profile-card {
        background: var(--card-bg);
        border-radius: 1rem;
        padding: 2rem;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        border: 1px solid var(--border-color);
    }

    /* Profile Image Styles - Fixed */
    .profile-image-container {
        position: relative;
        width: 150px;
        height: 150px;
        margin: 0 auto 1.5rem;
    }

    #click_profile_img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--primary-color);
        cursor: pointer;
        transition: all 0.3s ease;
        display: block;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    /* Show initials when no image or placeholder */
    #click_profile_img::before {
        content: attr(data-initials);
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        font-weight: bold;
        color: white;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        z-index: 1;
    }

    /* Hide initials when actual image loads */
    #click_profile_img[src]:not([src=""]):not([src*="placeholder"]):not([src*="via.placeholder"])::before {
        display: none !important;
    }

    #click_profile_img:hover {
        transform: scale(1.05);
        border-color: #5558dd;
    }

    .profile-image {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--primary-color);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin: 1.5rem 0;
        text-align: center;
    }

    .stat-item {
        padding: 1rem;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 0.5rem;
        border: 1px solid var(--border-color);
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-color);
        cursor: pointer;
        transition: color 0.3s ease;
    }

    .stat-value:hover {
        color: #5558dd;
        text-decoration: underline;
    }

    .stat-label {
        font-size: 0.875rem;
        opacity: 0.8;
    }

    .qr-section {
        text-align: center;
    }

    .qr-container {
        background: white;
        padding: 1rem;
        border-radius: 0.5rem;
        display: inline-block;
        margin-top: 1rem;
    }

    /* Form Grid Layout */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--text-color);
    }

    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        background: var(--input-bg);
        border: 2px solid var(--input-border);
        border-radius: 0.5rem;
        color: var(--text-color);
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        background: rgba(255, 255, 255, 0.15);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .form-control[readonly] {
        background: rgba(255, 255, 255, 0.05);
        border-color: var(--border-color);
        color: #cbd5e1;
    }

    .form-control::placeholder {
        color: #64748b;
        opacity: 1;
    }

    .form-control:hover:not([readonly]) {
        border-color: var(--primary-color);
    }

    .input-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .input-group .form-control {
        flex: 1;
    }

    .input-group-append {
        display: flex;
        gap: 0.5rem;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
        cursor: pointer;
        border: none;
        text-decoration: none;
        font-size: 0.95rem;
    }

    .btn-primary {
        background: var(--primary-color);
        color: white;
    }

    .btn-primary:hover {
        background: #5558dd;
        transform: translateY(-1px);
    }

    .btn-outline {
        background: transparent;
        border: 2px solid var(--primary-color);
        color: var(--primary-color);
    }

    .btn-outline:hover {
        background: var(--primary-color);
        color: white;
    }

    .btn-outline-danger {
        background: transparent;
        border: 2px solid #dc3545;
        color: #dc3545;
    }

    .btn-outline-danger:hover {
        background: #dc3545;
        color: white;
    }

    .btn-secondary {
        background-color: var(--border-color);
        border-color: var(--border-color);
        color: var(--text-color);
    }

    .btn-secondary:hover {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .public-toggle {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
    }

    .public-toggle-input {
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 0.25rem;
        border: 2px solid var(--border-color);
        appearance: none;
        cursor: pointer;
        position: relative;
        background: var(--input-bg);
    }

    .public-toggle-input:checked {
        background: var(--primary-color);
        border-color: var(--primary-color);
    }

    .public-toggle-input:checked::after {
        content: '✓';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: white;
        font-size: 0.875rem;
    }

    .color-picker-container {
        display: flex;
        gap: 1rem;
        align-items: center;
        margin-top: 1rem;
        justify-content: center;
    }

    .color-picker-group {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }

    .color-picker {
        width: 40px;
        height: 40px;
        padding: 0;
        border: 2px solid var(--border-color);
        border-radius: 0.5rem;
        cursor: pointer;
        background: none;
    }

    .color-picker::-webkit-color-swatch-wrapper {
        padding: 0;
    }

    .color-picker::-webkit-color-swatch {
        border: none;
        border-radius: 0.4rem;
    }

    /* Social Media Grid */
    .social-media-section {
        margin-top: 2rem;
    }

    .social-media-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 1.5rem;
        margin-top: 1rem;
    }

    @media (max-width: 768px) {
        .social-media-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Follow button container */
    #follow-btn-container {
        margin: 20px 0;
        text-align: center;
        pointer-events: auto;
    }

    #follow-btn-container .btn {
        padding: 10px 25px;
        font-size: 16px;
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

<body>
    <!-- Hidden fields for JavaScript -->
    <input type="hidden" id="user_id" value="123">
    <input type="hidden" id="user_qr" value="1234567890">
    <input type="hidden" id="user_type" value="2">

    <div class="profile-container">
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
                    <div class="qr-container">
                        <img id="click_banner_img" alt="QR Code" style="width: 192px; height: 192px;"
                            src="https://via.placeholder.com/192">
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
                <h3>Basic Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" value="">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone_number">Phone Number</label>
                        <input type="tel" id="phone_number" name="phone_number" class="form-control" value="">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email_address">Email Address</label>
                        <input type="email" id="email_address" name="email_address" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="address">Address</label>
                        <input type="text" id="address" name="address" class="form-control" value="">
                    </div>
                </div>

                <!-- Social Media Links -->
                <div class="social-media-section">
                    <h3>Social Media Links</h3>
                    <div class="social-media-grid">
                        <div class="form-group">
                            <label class="form-label" for="website">Website URL</label>
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
                            <label class="form-label" for="whatsapp_link">WhatsApp</label>
                            <div class="input-group">
                                <input type="text" id="whatsapp_link" name="whatsapp_link" class="form-control"
                                    value="">
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
                            <label class="form-label" for="telegram_link">Telegram</label>
                            <div class="input-group">
                                <input type="text" id="telegram_link" name="telegram_link" class="form-control"
                                    value="">
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
                            <label class="form-label" for="twitter_username">Twitter</label>
                            <div class="input-group">
                                <input type="text" id="twitter_username" name="twitter_username" class="form-control">
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
                            <label class="form-label" for="instagram_username">Instagram</label>
                            <div class="input-group">
                                <input type="text" id="instagram_username" name="instagram_username"
                                    class="form-control" value="">
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
                            <label class="form-label" for="youtube_username">YouTube</label>
                            <div class="input-group">
                                <input type="text" id="youtube_username" name="youtube_username" class="form-control"
                                    value="">
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
                            <label class="form-label" for="linkedin_username">LinkedIn</label>
                            <div class="input-group">
                                <input type="text" id="linkedin_username" name="linkedin_username" class="form-control"
                                    value="">
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
                            <label class="form-label" for="snapchat_username">Snapchat</label>
                            <div class="input-group">
                                <input type="text" id="snapchat_username" name="snapchat_username" class="form-control"
                                    value="">
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
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </div>
            </form>
        </div>
        <div id="toast" class="toast"></div>
    </div>
    <?php include('../components/jslinks.php'); ?>
    <script src="../components/qr/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
    <script src="./custom_js/custom_profile_new.js"></script>
</body>

</html>