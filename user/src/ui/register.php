<!DOCTYPE html>
<html lang="zxx">

<head>
    <title>ZQR</title>
    <link rel="icon" href="../assets/logo2.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="" name="keywords" />
    <meta content="" name="author" />
    <!-- CSS Files
    ================================================== -->
    <?php include('../components/csslinks.php') ?>
    <!-- Registration Form CSS -->
    <link rel="stylesheet" href="../assets/css/register-form.css">

    <style>
        .centered-logo {
            width: 240px !important;
            height: auto !important;
            min-width: 180px !important;
            display: block !important;
            margin: 0 auto !important;
        }

        @media (max-width: 768px) {
            .centered-logo {
                width: 180px !important;
            }
        }

        .logo-container-hero {
            text-align: center;
            margin: 10px 0 20px;
        }
    </style>

    <style>
        /* College name field styling */
        .college-field {
            display: none;
            margin-top: 15px;
            animation: slideDown 0.3s ease;
        }

        .college-field.show {
            display: block;
        }

        .college-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            background: #f8f9fa;
        }

        .college-input:focus {
            outline: none;
            border-color: #74b9ff;
            background: white;
        }

        .college-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                max-height: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                max-height: 100px;
                transform: translateY(0);
            }
        }

        /* Membership tier card price styling */
        .card-price {
            display: block;
            margin-top: 8px;
            font-size: 18px;
            font-weight: 700;
            color: #27ae60;
        }

        .card-features {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            color: #7f8c8d;
            font-weight: 500;
        }

        /* Common selection card styling */
        .selection-card {
            cursor: pointer;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: white;
        }

        .selection-card input[type="radio"] {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 20px;
            height: 20px;
            margin: 0;
            cursor: pointer;
        }

        .selection-card:hover {
            border-color: #3498db;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* User type card styling */
        .type-card input:checked~.card-icon span {
            color: #3498db;
            transform: scale(1.2);
        }

        .type-card input:checked~.card-text {
            color: #3498db;
            font-weight: 700;
        }

        /* Tier card styling with specific colors */
        .tier-card[data-tier="normal"]:has(input:checked) {
            border-color: #3498db !important;
            background: #f0f8ff;
        }

        .tier-card[data-tier="silver"]:has(input:checked) {
            border-color: #c0c0c0 !important;
            background: #f8f8f8;
        }

        .tier-card[data-tier="gold"]:has(input:checked) {
            border-color: #ffd700 !important;
            background: #fffef0;
        }

        .tier-card input:checked~.card-price {
            color: #27ae60;
            font-weight: 800;
        }

        /* ==================== MOBILE RESPONSIVE STYLES ==================== */
        @media (max-width: 768px) {

            /* Adjust padding for mobile */
            .box-rounded {
                padding: 25px 15px !important;
            }

            /* Full width form on mobile */
            .col-lg-4 {
                padding: 0 15px;
            }

            /* Pincode and Landmark - Stack vertically on mobile */
            .field-set-grid {
                display: block !important;
            }

            .field-set-grid .field-set {
                margin-bottom: 15px !important;
            }

            /* User type cards - Stack vertically on mobile */
            .basic-user-types {
                grid-template-columns: 1fr !important;
                gap: 10px !important;
            }

            /* Tier cards - Stack vertically on mobile */
            .tier-cards-grid {
                grid-template-columns: 1fr !important;
                gap: 10px !important;
            }

            /* Adjust card padding on mobile */
            .selection-card {
                padding: 12px !important;
                flex-direction: row !important;
                justify-content: flex-start !important;
                gap: 12px;
            }

            .selection-card .card-icon {
                margin: 0 !important;
            }

            .selection-card .card-text {
                flex: 1;
                text-align: left !important;
            }

            /* Adjust tier card layout on mobile */
            .tier-card {
                flex-direction: row !important;
                align-items: center !important;
                text-align: left !important;
            }

            .tier-card .card-icon {
                order: 1;
                margin-right: 10px !important;
            }

            .tier-card .card-text {
                order: 2;
                flex: 1;
                text-align: left !important;
                font-size: 16px !important;
            }

            .tier-card .card-price {
                order: 3;
                margin: 0 10px 0 0 !important;
                font-size: 16px !important;
            }

            .tier-card .card-features {
                order: 4;
                font-size: 11px !important;
                margin: 0 !important;
            }

            /* Adjust reference section checkbox on mobile */
            .reference-section label {
                font-size: 15px !important;
            }

            .reference-section label span {
                font-size: 15px !important;
            }

            /* Input field adjustments */
            .form-input,
            .college-input,
            .reference-input {
                font-size: 14px !important;
                padding: 10px 12px !important;
            }

            /* Hide left column on mobile */
            .col-lg-5.text-light {
                display: none !important;
            }

            /* Center the form on mobile */
            .col-lg-4.offset-lg-2 {
                margin: 0 !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
            }

            /* Adjust section margins */
            .field-set {
                margin-bottom: 15px !important;
            }

            /* Heading adjustments */
            h3 {
                font-size: 22px !important;
            }

            /* Button full width on mobile */
            .btn-fullwidth {
                width: 100% !important;
                padding: 12px !important;
                font-size: 15px !important;
            }

            /* Policy checkboxes - more compact on mobile */
            .field-set>div[style*="background: rgba(255, 255, 255, 0.05)"] {
                padding: 15px !important;
            }

            .field-set label {
                font-size: 13px !important;
            }
        }

        /* Small phone screens */
        @media (max-width: 480px) {
            .box-rounded {
                padding: 20px 12px !important;
            }

            .selection-card {
                padding: 10px !important;
            }

            .card-price {
                font-size: 14px !important;
            }

            .card-features {
                font-size: 10px !important;
            }

            h3 {
                font-size: 20px !important;
            }
        }
    </style>
</head>

<body>
    <div id="wrapper">

        <!-- header begin -->
        <header class="transparent d-none">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="de-flex sm-pt10">
                            <div class="de-flex-col">
                                <div class="de-flex-col">
                                    <!-- logo begin -->
                                    <div id="logo">
                                        <a href="index.php">
                                            <img alt="Logo" class="centered-logo" src="../assets/logo2.png" />
                                        </a>
                                    </div>
                                    <!-- logo close -->
                                </div>
                            </div>
                            <div class="de-flex-col header-col-mid">
                                <!-- mainmenu begin -->
                                <ul id="mainmenu">
                                    <li>
                                        <a href="login.php">Login<span></span></a>
                                    </li>
                                </ul>
                                <div class="menu_side_area">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <!-- header close -->
        <!-- content begin -->
        <div class="no-bottom no-top" id="content">
            <div id="top"></div>

            <section class="full-height relative no-top no-bottom vertical-center"
                data-bgimage="url(../assets/images/background/6.jpg) top" data-stellar-background-ratio=".5">
                <div class="overlay-gradient t50">
                    <div class="center-y relative">
                        <div class="container">
                            <div class="row align-items-center">
                                <div class="col-lg-5 text-light wow fadeInRight" data-wow-delay=".5s">
                                    <div class="spacer-10"></div>
                                    <div class="logo-container-hero">
                                        <a href="index.php">
                                            <img src="../assets/logo2.png" alt="Logo" class="centered-logo"
                                                style="width:240px; min-width:180px; height:auto;" />
                                        </a>
                                    </div>
                                </div>

                                <div class="col-lg-4 offset-lg-2 wow fadeIn" data-wow-delay=".5s">
                                    <div class="box-rounded padding40" data-bgcolor="#ffffff">
                                        <h3 class="mb10">Register</h3>
                                        <p>Already have an account? <a href="login.php">Login here<span></span></a>.</p>
                                        <form name="registerForm" id='register_form' class="form-border" method="post"
                                            action=''>

                                            <!-- Full Name Field -->
                                            <div class="field-set">
                                                <input type='text' name='full_name' id='full_name'
                                                    placeholder="👤 Full Name (e.g., John D Smith)" class="form-input"
                                                    required>
                                                <span id="name-validation-status"
                                                    style="margin-top:5px; display:block; font-size:12px; font-weight:600;"></span>
                                            </div>

                                            <!-- Email Field with Cool Design -->
                                            <div class="field-set" style="position:relative; margin-bottom:20px;">
                                                <input type='text' name='email' id='email'
                                                    placeholder="✉️ Email Address" class="form-input" required>
                                                <div id="verify-email-section" style="display:none; margin-top:8px;">
                                                    <a href="#" id="verify-email-link" class="verify-email-btn">
                                                        🔐 Verify Email
                                                    </a>
                                                    <span id="email-verified-status"
                                                        style="margin-left:15px; font-weight:600; font-size:14px;"></span>
                                                </div>
                                            </div>

                                            <!-- Phone Number Field -->
                                            <div class="field-set">
                                                <input type='tel' name='phone' id='phone'
                                                    placeholder="📱 Phone Number (10 digits)" class="form-input"
                                                    maxlength="10" pattern="[0-9]{10}" required>
                                                <span id="phone-validation-status"
                                                    style="margin-top:5px; display:block; font-size:12px; font-weight:600;"></span>
                                            </div>

                                            <!-- Password Field -->
                                            <div class="field-set">
                                                <input type='password' name='password' id='password'
                                                    placeholder="🔒 Password" class="form-input" required>
                                            </div>

                                            <!-- Confirm Password Field -->
                                            <div class="field-set">
                                                <input type='password' name='confirmpassword' id='confirmpassword'
                                                    placeholder="🔐 Confirm Password" class="form-input" required>
                                                <span id="password-match-status"
                                                    style="margin-bottom:20px; display:block; font-weight:600;"></span>
                                            </div>






                                            <!-- Register As Section with Better Responsive Design -->
                                            <div class="field-set" style="margin-bottom:8%;">
                                                <label
                                                    style="margin-bottom:20px;display:block;font-weight:600;color:#2c3e50;font-size:16px;">👤
                                                    Register as</label>

                                                <!-- Basic User Types - Compact grid layout -->
                                                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:15px;"
                                                    class="basic-user-types">

                                                    <!-- Individual Card -->
                                                    <label class="selection-card type-card" data-type="individual">
                                                        <input type="radio" name="user_type" value="1"
                                                            id="type_individual" required>
                                                        <div class="card-icon">
                                                            <span style="font-size:18px;">👤</span>
                                                        </div>
                                                        <span class="card-text">Individual</span>
                                                    </label>

                                                    <!-- Creator Card -->
                                                    <label class="selection-card type-card" data-type="creator">
                                                        <input type="radio" name="user_type" value="2"
                                                            id="type_creator">
                                                        <div class="card-icon">
                                                            <span style="font-size:18px;">🎨</span>
                                                        </div>
                                                        <span class="card-text">Creator</span>
                                                    </label>

                                                    <!-- Business Card -->
                                                    <label class="selection-card type-card" data-type="business">
                                                        <input type="radio" name="user_type" value="3"
                                                            id="type_business">
                                                        <div class="card-icon">
                                                            <span style="font-size:18px;">🏢</span>
                                                        </div>
                                                        <span class="card-text">Business</span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Membership Tier Selection (User Tag: Gold/Silver/Normal) -->
                                            <div class="field-set" id="membership_tier_section"
                                                style="margin-top: -5%;">
                                                <label
                                                    style="margin-bottom:15px;display:block;font-weight:600;color:#2c3e50;font-size:16px;">💎
                                                    Select Your Membership Tier</label>


                                                <!-- Membership Tier Cards (No price/description) -->
                                                <div class="tier-cards-grid"
                                                    style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:15px;">
                                                    <label class="selection-card tier-card" data-tier="gold">
                                                        <input type="radio" name="user_tag" value="gold" id="tier_gold">
                                                        <div class="card-icon">
                                                            <span style="font-size:24px;">✨</span>
                                                        </div>
                                                        <span class="card-text">Gold</span>
                                                    </label>
                                                    <label class="selection-card tier-card" data-tier="silver">
                                                        <input type="radio" name="user_tag" value="silver"
                                                            id="tier_silver">
                                                        <div class="card-icon">
                                                            <span style="font-size:24px;">🥈</span>
                                                        </div>
                                                        <span class="card-text">Silver</span>
                                                    </label>
                                                    <!-- Normal tier removed: only Gold and Silver shown -->
                                                </div>

                                                <!-- ...existing code... -->

                                                <!-- Hidden field for slab ID (set dynamically by JS) -->
                                                <input type="hidden" name="user_slab" id="user_slab">
                                            </div>
                                            <!-- Student Leader Dropdown and College Name (above Register As) -->
                                            <div class="field-set" style="margin-bottom:15px;">
                                                <label for="student_leader"
                                                    style="font-weight:600;color:#2c3e50;font-size:15px;">🏅 Are you a
                                                    Student Leader?</label>
                                                <select name="student_leader" id="student_leader" class="form-input">
                                                    <option value="no" selected>No</option>
                                                    <option value="yes">Yes</option>
                                                </select>
                                            </div>
                                            <div id="college_field" class="college-field" style="margin-bottom:15px;">
                                                <label class="college-label">🎓 Enter your college name:</label>
                                                <input type="text" name="college_name" id="college_name"
                                                    placeholder="Enter your college/university name"
                                                    class="college-input">
                                            </div>

                                            <!-- Reference Code Section (Always Visible, 10 digits) -->
                                            <div class="field-set" style="margin-bottom:20px;">
                                                <label for="reference_code"
                                                    style="font-weight:600;color:#2c3e50;font-size:15px;">🎯 Reference
                                                    Code (if any)</label>
                                                <input type="text" name="reference_code" id="reference_code"
                                                    class="reference-input" maxlength="10" pattern="[0-9]{10}"
                                                    placeholder="🔗 Enter 10-digit reference code">
                                                <span id="reference-status"
                                                    style="margin-left:0px; margin-top:10px; display:block; font-size:14px; font-weight:600;"></span>
                                            </div>

                                            <!-- Terms and Policies Agreement -->
                                            <div class="field-set" style="margin-top:20px;">
                                                <div
                                                    style="background: rgba(255, 255, 255, 0.05); padding: 15px; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.1);">
                                                    <label
                                                        style="display: flex; align-items: center; cursor: pointer; font-size: 13px; color: #b0b0b0; line-height: 1.6;">
                                                        <input type="checkbox" id="terms-checkbox" required
                                                            style="margin-right: 10px; min-width: 20px; width: 20px; height: 20px; cursor: pointer; flex-shrink: 0; accent-color: #8364e2; -webkit-appearance: auto; appearance: auto;">
                                                        <span>I agree to the <a href="terms.php" target="_blank"
                                                                style="color: #8364e2; text-decoration: underline;"
                                                                onclick="event.stopPropagation();">Terms &
                                                                Conditions</a>, <a href="privacy.php" target="_blank"
                                                                style="color: #8364e2; text-decoration: underline;"
                                                                onclick="event.stopPropagation();">Privacy Policy</a>
                                                            and <a href="refund.php" target="_blank"
                                                                style="color: #8364e2; text-decoration: underline;"
                                                                onclick="event.stopPropagation();">Refund
                                                                Policy</a></span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Submit Button -->
                                            <div class="field-set" style="margin-top:25px;">
                                                <button type='submit' id='register_user_form' disabled
                                                    class="btn btn-main btn-fullwidth color-2">
                                                    🚀 Create Account (₹ <span id="pay-amount">999</span> )
                                                </button>
                                            </div>


                                            <div class="clearfix"></div>
                                            <!-- Email OTP Modal -->
                                            <div id="email-otp-modal" class="modal" tabindex="-1" role="dialog"
                                                style="display:none; background:rgba(0,0,0,0.5); position:fixed; top:0; left:0; width:100vw; height:100vh; z-index:9999; align-items:center; justify-content:center;">
                                                <div class="modal-dialog" role="document"
                                                    style="max-width:400px; margin:auto;">
                                                    <div class="modal-content" style="padding:30px; border-radius:8px;">
                                                        <div class="modal-header" style="border:none;">
                                                            <h5 class="modal-title">Verify Email</h5>
                                                            <button type="button" class="close" id="close-otp-modal"
                                                                style="background:none; border:none; font-size:24px;">&times;</button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>An OTP has been sent to <span
                                                                    id="otp-email-display"></span></p>
                                                            <input type="text" id="email-otp-input" maxlength="6"
                                                                class="form-control" placeholder="Enter 6-digit OTP"
                                                                style="margin-bottom:10px;">
                                                            <button type="button" id="submit-otp-btn"
                                                                class="btn btn-main btn-fullwidth color-2"
                                                                style="margin-bottom:10px;">Verify OTP</button>
                                                            <button type="button" id="resend-otp-btn"
                                                                class="btn btn-secondary btn-fullwidth">Resend
                                                                OTP</button>
                                                            <div id="otp-status-msg"
                                                                style="margin-top:10px; color:red;"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="spacer-single"></div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
        <!-- content close -->

        <!-- footer begin -->
        <?php
        //include('../components/footer.php'); 
        ?>
        <!-- footer close -->

    </div>

    <!-- Javascript Files
    ================================================== -->
    <?php include('../components/jslinks.php'); ?>
    <!-- Registration Form JavaScript -->
    <script src="../assets/js/register-form.js"></script>
    <script src="./custom_js/custom_register.js"></script>


</body>

</html>