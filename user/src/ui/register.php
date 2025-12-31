<!DOCTYPE html>
<?php
// Capture referral code from URL parameter
$ref_code = isset($_GET['ref']) ? htmlspecialchars($_GET['ref']) : '';
?>
<html lang="zxx">

<head>
    <title>ZQR - Register</title>
    <link rel="icon" href="../../../logo/logo.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="" name="keywords" />
    <meta content="" name="author" />
    <!-- CSS Files
    ================================================== -->
    <?php include('../components/csslinks.php') ?>

    <style>
        /* ==================== BASE STYLES ==================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            background: #1A1A1B !important;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        #wrapper, #content, .no-bottom.no-top {
            background: transparent !important;
        }

        /* ==================== PAGE LAYOUT ==================== */
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
            background: linear-gradient(135deg, #1A1A1B 0%, #0f172a 100%);
        }

        /* ==================== FORM BOX ==================== */
        .auth-box {
            width: 100%;
            max-width: 420px;
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 24px 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
        }

        /* ==================== FORM LOGO ==================== */
        .auth-logo {
            text-align: center;
            margin-bottom: 12px;
        }

        .auth-logo img {
            width: 130px;
            height: auto;
        }

        /* ==================== FORM HEADER ==================== */
        .auth-title {
            color: #f1f5f9;
            font-size: 20px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 3px;
        }

        .auth-subtitle {
            color: #94a3b8;
            font-size: 12px;
            text-align: center;
            margin-bottom: 16px;
        }

        .auth-subtitle a {
            background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 600;
            text-decoration: none;
        }

        /* ==================== FORM INPUTS ==================== */
        .field-set {
            margin-bottom: 10px;
        }

        .form-input,
        .college-input,
        select.form-input {
            width: 100%;
            height: 40px;
            padding: 10px 12px;
            background: rgba(15, 23, 42, 0.6) !important;
            border: 1.5px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 8px;
            font-size: 13px;
            color: #e2e8f0 !important;
            transition: all 0.2s ease;
        }

        .form-input::placeholder,
        .college-input::placeholder {
            color: #64748b !important;
            font-size: 12px;
        }

        .form-input:focus,
        .college-input:focus {
            outline: none;
            background: rgba(30, 41, 59, 0.8) !important;
            border-color: #e67753 !important;
            box-shadow: 0 0 0 2px rgba(230, 119, 83, 0.12) !important;
        }

        /* ==================== LABELS ==================== */
        label {
            color: #94a3b8 !important;
            font-weight: 600;
            font-size: 11px;
            margin-bottom: 3px;
            display: block;
        }

        .section-label {
            color: #e2e8f0 !important;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
        }

        /* ==================== VALIDATION STATUS ==================== */
        .validation-status {
            display: block;
            min-height: 12px;
            margin-top: 2px;
            font-size: 10px;
            font-weight: 600;
            line-height: 1.3;
        }

        .validation-status:empty {
            min-height: 0;
            margin-top: 0;
        }

        /* ==================== VERIFY EMAIL ==================== */
        .verify-section {
            display: none;
            margin-top: 5px;
            align-items: center;
            gap: 8px;
        }

        #verify-email-link {
            display: inline-block;
            padding: 4px 8px;
            background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            color: white !important;
            border-radius: 5px;
            font-size: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .verification-status {
            font-size: 10px;
            font-weight: 600;
        }

        /* ==================== PASSWORD TOGGLE ==================== */
        .password-toggle-container {
            position: relative;
        }

        .password-toggle-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            color: #64748b;
            z-index: 10;
        }

        .form-input.with-toggle {
            padding-right: 36px !important;
        }

        /* ==================== SELECTION CARDS ==================== */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 12px;
        }

        .cards-grid-2 {
            grid-template-columns: repeat(2, 1fr);
        }

        .selection-card {
            cursor: pointer;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 14px 10px 10px;
            border: 1.5px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            transition: all 0.2s ease;
            background: rgba(15, 23, 42, 0.4);
        }

        .selection-card:hover {
            border-color: #e67753;
        }

        .selection-card .card-text {
            color: #94a3b8;
            font-weight: 600;
            font-size: 11px;
            margin-top: 4px;
        }

        .selection-card .card-icon {
            font-size: 22px;
            line-height: 1;
        }

        .selection-card input[type="radio"] {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 14px;
            height: 14px;
            accent-color: #e67753;
        }

        .type-card:has(input:checked),
        .tier-card:has(input:checked) {
            border-color: #e67753 !important;
            background: rgba(230, 119, 83, 0.1) !important;
        }

        .type-card:has(input:checked) .card-text,
        .tier-card:has(input:checked) .card-text {
            color: #e67753 !important;
        }

        /* Tier Image Card Styles */
        .tier-card-img {
            padding: 0 !important;
            overflow: hidden;
        }

        .tier-card-img .tier-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .tier-card-img:hover .tier-img {
            transform: scale(1.02);
        }

        .tier-card-img:has(input:checked) {
            border-color: #e67753 !important;
            box-shadow: 0 0 0 3px rgba(230, 119, 83, 0.3);
        }

        .tier-card-img:has(input:checked) .tier-img {
            opacity: 0.9;
        }

        /* ==================== TERMS CHECKBOX ==================== */
        .terms-box {
            background: rgba(15, 23, 42, 0.4);
            padding: 10px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .terms-box label {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            cursor: pointer;
            font-size: 10px !important;
            color: #94a3b8 !important;
            line-height: 1.5;
        }

        .terms-box input[type="checkbox"] {
            -webkit-appearance: checkbox !important;
            -moz-appearance: checkbox !important;
            appearance: checkbox !important;
            min-width: 16px !important;
            width: 16px !important;
            height: 16px !important;
            margin: 0 !important;
            margin-top: 1px !important;
            accent-color: #e67753;
            cursor: pointer;
            opacity: 1 !important;
            visibility: visible !important;
            position: relative !important;
        }

        .terms-box a {
            color: #e67753 !important;
            text-decoration: underline;
        }

        /* ==================== SUBMIT BUTTON ==================== */
        .btn-main {
            width: 100%;
            height: 42px;
            background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%) !important;
            border: none !important;
            border-radius: 8px;
            color: white !important;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-main:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(230, 119, 83, 0.35);
        }

        .btn-main:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* ==================== COLLEGE FIELD ==================== */
        .college-field {
            display: none;
            margin-bottom: 12px;
        }

        .college-field.show {
            display: block;
        }

        /* ==================== OTP MODAL - RESPONSIVE ==================== */
        #email-otp-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            padding: 20px;
            box-sizing: border-box;
            overflow-y: auto;
            align-items: center;
            justify-content: center;
        }

        #email-otp-modal .modal-dialog {
            width: 100%;
            max-width: 360px;
            margin: auto;
        }

        #email-otp-modal .modal-content {
            background: #1e293b;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            width: 100%;
            box-sizing: border-box;
        }

        #email-otp-modal .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding: 0;
            border: none;
        }

        #email-otp-modal .modal-title {
            color: #f1f5f9;
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        #email-otp-modal .close {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: #94a3b8;
            font-size: 20px;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        #email-otp-modal .close:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #f1f5f9;
        }

        #email-otp-modal .modal-body {
            padding: 0;
        }

        #email-otp-modal .modal-body p {
            color: #94a3b8;
            margin-bottom: 16px;
            font-size: 14px;
            text-align: center;
        }

        #email-otp-modal #otp-email-display {
            color: #e67753;
            font-weight: 600;
        }

        #email-otp-modal #email-otp-input {
            width: 100%;
            height: 56px;
            font-size: 24px;
            text-align: center;
            letter-spacing: 8px;
            background: rgba(15, 23, 42, 0.6);
            border: 2px solid rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            color: #f1f5f9;
            margin-bottom: 16px;
            box-sizing: border-box;
            font-family: 'Inter', monospace;
        }

        #email-otp-modal #email-otp-input::placeholder {
            color: #64748b;
            letter-spacing: 2px;
            font-size: 14px;
        }

        #email-otp-modal #email-otp-input:focus {
            outline: none;
            border-color: #e67753;
            box-shadow: 0 0 0 3px rgba(230, 119, 83, 0.15);
        }

        #email-otp-modal .btn-main {
            width: 100%;
            height: 44px;
            margin-bottom: 10px;
        }

        #email-otp-modal .btn-secondary {
            width: 100%;
            height: 40px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #94a3b8;
            font-weight: 600;
            font-size: 13px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        #email-otp-modal .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #e2e8f0;
        }

        #email-otp-modal #otp-status-msg {
            margin-top: 12px;
            text-align: center;
            font-size: 13px;
            font-weight: 500;
        }

        /* ==================== MOBILE RESPONSIVE ==================== */
        @media (max-width: 480px) {
            .auth-box {
                padding: 20px 16px;
            }

            .auth-logo img {
                width: 130px;
            }

            .auth-title {
                font-size: 18px;
            }

            .form-input {
                height: 38px;
                font-size: 12px;
            }

            .selection-card {
                padding: 12px 8px 8px;
            }

            .selection-card .card-icon {
                font-size: 18px;
            }

            .selection-card .card-text {
                font-size: 10px;
            }

            /* Modal responsive */
            #email-otp-modal {
                padding: 16px;
            }

            #email-otp-modal .modal-dialog {
                max-width: 100%;
            }

            #email-otp-modal .modal-content {
                padding: 20px;
            }

            #email-otp-modal #email-otp-input {
                height: 50px;
                font-size: 20px;
                letter-spacing: 6px;
            }
        }
    </style>
</head>

<body>
    <!-- Page Content -->
    <div class="auth-page">
        <div class="auth-box">
            <!-- Logo inside form -->
            <div class="auth-logo">
                <a href="index.php">
                    <img src="../../../logo/logo-both.png" alt="Zokli Logo" />
                </a>
            </div>

            <h1 class="auth-title">Create Account</h1>
            <p class="auth-subtitle">Already have an account? <a href="login.php">Login here</a></p>

            <form name="registerForm" id="register_form" method="post" action="">

                <!-- Full Name Field -->
                <div class="field-set">
                    <input type="text" name="full_name" id="full_name" placeholder="👤 Full Name" class="form-input" required>
                    <span id="name-validation-status" class="validation-status"></span>
                </div>

                <!-- Email Field -->
                <div class="field-set">
                    <input type="text" name="email" id="email" placeholder="✉️ Email Address" class="form-input" required>
                    <div id="verify-email-section" class="verify-section">
                        <a href="#" id="verify-email-link">🔐 Verify</a>
                        <span id="email-verified-status" class="verification-status"></span>
                    </div>
                </div>

                <!-- Phone Number Field -->
                <div class="field-set">
                    <input type="tel" name="phone" id="phone" placeholder="📱 Phone (10 digits)" class="form-input" maxlength="10" pattern="[0-9]{10}" required>
                    <span id="phone-validation-status" class="validation-status"></span>
                </div>

                <!-- Password Field -->
                <div class="field-set">
                    <div class="password-toggle-container">
                        <input type="password" name="password" id="password" placeholder="🔒 Password" class="form-input with-toggle" required>
                        <button type="button" class="password-toggle-btn" id="password-toggle">👁️</button>
                    </div>
                </div>

                <!-- Confirm Password Field -->
                <div class="field-set">
                    <div class="password-toggle-container">
                        <input type="password" name="confirmpassword" id="confirmpassword" placeholder="🔐 Confirm Password" class="form-input with-toggle" required>
                        <button type="button" class="password-toggle-btn" id="confirmpassword-toggle">👁️</button>
                    </div>
                    <span id="password-match-status" class="validation-status"></span>
                </div>

                <!-- Register As Section -->
                <div class="field-set">
                    <label class="section-label">👤 Register as</label>
                    <div class="cards-grid">
                        <label class="selection-card type-card">
                            <input type="radio" name="user_type" value="1" id="type_individual" required>
                            <div class="card-icon">👤</div>
                            <span class="card-text">Individual</span>
                        </label>
                        <label class="selection-card type-card">
                            <input type="radio" name="user_type" value="2" id="type_creator">
                            <div class="card-icon">🎨</div>
                            <span class="card-text">Creator</span>
                        </label>
                        <label class="selection-card type-card">
                            <input type="radio" name="user_type" value="3" id="type_business">
                            <div class="card-icon">🏢</div>
                            <span class="card-text">Business</span>
                        </label>
                    </div>
                </div>

                <!-- Membership Tier Selection -->
                <div class="field-set" id="membership_tier_section">
                    <label class="section-label">💎 Membership Tier</label>
                    <div class="cards-grid cards-grid-2">
                        <label class="selection-card tier-card tier-card-img" data-tier="gold">
                            <input type="radio" name="user_tag" value="gold" id="tier_gold">
                            <img src="../btnimg/gold.jpeg" alt="Gold" class="tier-img">
                        </label>
                        <label class="selection-card tier-card tier-card-img" data-tier="silver">
                            <input type="radio" name="user_tag" value="silver" id="tier_silver">
                            <img src="../btnimg/silver.jpeg" alt="Silver" class="tier-img">
                        </label>
                    </div>
                    <input type="hidden" name="user_slab" id="user_slab">
                </div>

                <!-- Student Leader Dropdown -->
                <div class="field-set">
                    <label for="student_leader">🏅 Student Leader?</label>
                    <select name="student_leader" id="student_leader" class="form-input">
                        <option value="no" selected>No</option>
                        <option value="yes">Yes</option>
                    </select>
                </div>

                <!-- College Field (hidden by default) -->
                <div id="college_field" class="college-field">
                    <label>🎓 College Name</label>
                    <input type="text" name="college_name" id="college_name" placeholder="Enter your college name" class="form-input">
                </div>

                <!-- Promo Code Section -->
                <div class="field-set">
                    <label for="promo_code">🎁 Promo Code</label>
                    <input type="text" name="promo_code" id="promo_code" class="form-input" placeholder="Enter promo code" style="text-transform: uppercase;">
                    <span id="promo-status" class="validation-status"></span>
                </div>

                <!-- Reference Code Section -->
                <div class="field-set">
                    <label for="reference_code">🎯 Reference Code</label>
                    <input type="text" name="reference_code" id="reference_code" class="form-input" maxlength="10" placeholder="10-character code" value="<?php echo $ref_code; ?>">
                    <span id="reference-status" class="validation-status"></span>
                </div>

                <!-- Terms and Policies Agreement -->
                <div class="field-set">
                    <div class="terms-box">
                        <label>
                            <input type="checkbox" id="terms-checkbox" required>
                            <span>I agree to the <a href="terms.php" target="_blank" onclick="event.stopPropagation();">Terms</a>, <a href="privacy.php" target="_blank" onclick="event.stopPropagation();">Privacy</a> and <a href="refund.php" target="_blank" onclick="event.stopPropagation();">Refund</a> policies</span>
                        </label>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="field-set" style="margin-top: 16px;">
                    <button type="submit" id="register_user_form" disabled class="btn-main">
                        🚀 Create Account (₹ <span id="pay-amount">999</span>)
                    </button>
                </div>

                <!-- Email OTP Modal -->
                <div id="email-otp-modal">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Verify Email</h5>
                                <button type="button" class="close" id="close-otp-modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <p>OTP sent to <span id="otp-email-display"></span></p>
                                <input type="text" id="email-otp-input" maxlength="6" placeholder="Enter 6-digit OTP">
                                <button type="button" id="submit-otp-btn" class="btn-main">Verify OTP</button>
                                <button type="button" id="resend-otp-btn" class="btn-secondary">Resend OTP</button>
                                <div id="otp-status-msg"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- Javascript Files
    ================================================== -->
    <?php include('../components/jslinks.php'); ?>
    <script src="./custom_js/toast.js"></script>
    <script src="./custom_js/custom_register.js"></script>

    <!-- Password Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordToggle = document.getElementById('password-toggle');
            const passwordInput = document.getElementById('password');
            const confirmPasswordToggle = document.getElementById('confirmpassword-toggle');
            const confirmPasswordInput = document.getElementById('confirmpassword');

            function setupPasswordToggle(toggleBtn, inputField) {
                toggleBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const isPassword = inputField.type === 'password';
                    inputField.type = isPassword ? 'text' : 'password';
                    toggleBtn.textContent = isPassword ? '👁️‍🗨️' : '👁️';
                });
            }

            setupPasswordToggle(passwordToggle, passwordInput);
            setupPasswordToggle(confirmPasswordToggle, confirmPasswordInput);
        });
    </script>

</body>

</html>