<!DOCTYPE html>
<html lang="zxx">

<head>
    <title>ZQR</title>
    <link rel="icon" href="../assets/images/company_logo.jpg" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="" name="keywords" />
    <meta content="" name="author" />
    <!-- CSS Files
    ================================================== -->
    <?php include('../components/csslinks.php') ?>
    <!-- Registration Form CSS -->
    <link rel="stylesheet" href="../assets/css/register-form.css">
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
                                            <img alt="" class="logo" src="../assets/images/logo-3.png" />
                                            <img alt="" class="logo-2" src="../assets/images/logo-3.png" />
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
                                    <h1>Create, sell or collect digital items.</h1>
                                </div>

                                <div class="col-lg-4 offset-lg-2 wow fadeIn" data-wow-delay=".5s">
                                    <div class="box-rounded padding40" data-bgcolor="#ffffff">
                                        <h3 class="mb10">Register</h3>
                                        <p>Already have an account? <a href="login.php">Login here<span></span></a>.</p>
                                        <form name="registerForm" id='register_form' class="form-border" method="post"
                                            action=''>

                                            <!-- Email Field with Cool Design -->
                                            <div class="field-set" style="position:relative; margin-bottom:20px;">
                                                <input type='text' name='email' id='email' 
                                                       placeholder="✉️ Email Address"
                                                       class="form-input">
                                                <div id="verify-email-section" style="display:none; margin-top:8px;">
                                                    <a href="#" id="verify-email-link" class="verify-email-btn">
                                                        🔐 Verify Email
                                                    </a>
                                                    <span id="email-verified-status" style="margin-left:15px; font-weight:600; font-size:14px;"></span>
                                                </div>
                                            </div>

                                            <!-- Password Field -->
                                            <div class="field-set">
                                                <input type='password' name='password' id='password'
                                                       placeholder="🔒 Password"
                                                       class="form-input">
                                            </div>

                                            <!-- Confirm Password Field -->
                                            <div class="field-set">
                                                <input type='password' name='confirmpassword' id='confirmpassword'
                                                       placeholder="🔐 Confirm Password"
                                                       class="form-input">
                                                <span id="password-match-status" style="margin-left:8px; margin-top:8px; display:block; font-weight:600;"></span>
                                            </div>

                                            <!-- User Slab Selection with Cool Design -->
                                            <div class="field-set" style="margin-top: -5%;">
                                                <label style="margin-bottom:12px;display:block;font-weight:600;color:#2c3e50;font-size:14px;">💎 Select Your Plan</label>
                                                <select name="user_slab" id="user_slab" required class="form-select">
                                                    <option value="" style="background:#fff;color:#666;">🎯 Select your plan...</option>
                                                </select>
                                            </div>

                                            <!-- Register As Section with Better Responsive Design -->
                                            <div class="field-set" style="margin-bottom:8%;">
                                                <label style="margin-bottom:20px;display:block;font-weight:600;color:#2c3e50;font-size:16px;">👤 Register as</label>
                                                
                                                <!-- Basic User Types - Compact grid layout -->
                                                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:15px;" class="basic-user-types">
                                                    
                                                    <!-- Individual Card -->
                                                    <label class="user-type-card" data-type="individual">
                                                        <input type="radio" name="user_type" value="1" required style="display:none;">
                                                        <div class="card-icon">
                                                            <span style="font-size:18px;">👤</span>
                                                        </div>
                                                        <span class="card-text">Individual</span>
                                                    </label>

                                                    <!-- Creator Card -->
                                                    <label class="user-type-card" data-type="creator">
                                                        <input type="radio" name="user_type" value="2" style="display:none;">
                                                        <div class="card-icon">
                                                            <span style="font-size:18px;">🎨</span>
                                                        </div>
                                                        <span class="card-text">Creator</span>
                                                    </label>

                                                    <!-- Business Card -->
                                                    <label class="user-type-card" data-type="business">
                                                        <input type="radio" name="user_type" value="3" style="display:none;">
                                                        <div class="card-icon">
                                                            <span style="font-size:18px;">🏢</span>
                                                        </div>
                                                        <span class="card-text">Business</span>
                                                    </label>
                                                </div>

                                                <!-- Premium Membership Cards -->
                                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;" class="premium-types">
                                                     
                                                    <!-- Gold Member Card -->
                                                    <label class="user-type-card" data-type="gold">
                                                        <input type="radio" name="user_type" value="1" data-tag="gold" style="display:none;">
                                                        <div class="card-icon">
                                                            <span style="font-size:16px;">✨</span>
                                                        </div>
                                                        <span class="card-text">Gold</span>
                                                    </label>

                                                    <!-- Silver Member Card -->
                                                    <label class="user-type-card" data-type="silver">
                                                        <input type="radio" name="user_type" value="1" data-tag="silver" style="display:none;">
                                                        <div class="card-icon">
                                                            <span style="font-size:16px;">🥈</span>
                                                        </div>
                                                        <span class="card-text">Silver</span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Reference Code Section with Modern Toggle -->
                                            <div class="field-set" style="margin-bottom:20px;">
                                                <div class="reference-section">
                                                    <label style="display:flex;align-items:center;font-weight:600;cursor:pointer;color:#333;margin-bottom:0;gap:15px;">
                                                        <div style="position:relative;">
                                                            <input type="checkbox" id="has_reference" name="has_reference" 
                                                                   style="width:24px;height:24px;cursor:pointer;opacity:0;position:absolute;">
                                                            <div class="reference-checkbox" id="checkbox_visual">
                                                                <span style="color:#74b9ff;font-size:16px;font-weight:bold;opacity:0;transition:opacity 0.3s ease;" id="check_mark">✓</span>
                                                            </div>
                                                        </div>
                                                        <span style="font-size:17px;font-weight:600;color:#333;">🎯 Do you have a reference code?</span>
                                                    </label>
                                                    
                                                    <div id="reference_section" style="display:none;margin-top:20px;animation:slideDown 0.3s ease;">
                                                        <input type="text" name="reference_code" id="reference_code" class="reference-input" 
                                                               placeholder="🔗 Enter your reference code">
                                                        <span id="reference-status" style="margin-left:0px; margin-top:10px; display:block; font-size:14px; font-weight:600;"></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Submit Button -->
                                            <div class="field-set" style="margin-top:25px;">
                                                <button type='submit' id='register_user_form' disabled class="btn btn-main btn-fullwidth color-2">
                                                    🚀 Create Account
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
