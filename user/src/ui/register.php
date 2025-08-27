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
</head>

<body>
    <div id="wrapper">

        <!-- header begin -->
        <header class="transparent">
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

                                            <div class="field-set">
                                                <input type='text' name='email' id='email' class="form-control"
                                                    placeholder="Email Address">
                                                <span id="verify-email-section" style="display:none; margin-left:10px;">
                                                    <a href="#" id="verify-email-link">Verify Email</a>
                                                    <span id="email-verified-status" style="margin-left:8px;"></span>
                                                </span>
                                            </div>

                                            <div class="field-set">
                                                <input type='password' name='password' id='password'
                                                    class="form-control" placeholder="Password">
                                            </div>

                                            <div class="field-set">
                                                <input type='password' name='confirm_password' id='confirm_password'
                                                    class="form-control" placeholder="Confirm Password">
                                                <span id="password-match-status" style="margin-left:8px;"></span>
                                            </div>

                                            <div class="field-set" style="margin-bottom:10%;">
                                                <label style="margin-bottom:8px;display:block;font-weight:500;">Register
                                                    as:</label>
                                                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                                    <label><input type="radio" name="user_type" value="1" required>
                                                        Individual</label>
                                                    <label><input type="radio" name="user_type" value="2">
                                                        Creator</label>
                                                    <label><input type="radio" name="user_type" value="3">
                                                        Business</label>
                                                    <label><input type="radio" name="user_type" value="1"
                                                            data-tag="gold"> <span
                                                            style="color:gold;font-weight:bold;">Gold
                                                            Member</span></label>
                                                    <label><input type="radio" name="user_type" value="1"
                                                            data-tag="silver"><span
                                                            style="color:silver;font-weight:bold;">Silver
                                                            Member</span></label>
                                                </div>
                                            </div>



                                            <div class="field-set">
                                                <input type='submit' id='register_user_form' value='Submit'
                                                    class="btn btn-main btn-fullwidth color-2" disabled>
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
        <?php include('../components/footer.php'); ?>
        <!-- footer close -->

    </div>



    <!-- Javascript Files
    ================================================== -->
    <?php include('../components/jslinks.php'); ?>
    <script src="./custom_js/custom_register.js"></script>



</body>

</html>