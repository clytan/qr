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
    <style>
        body, #wrapper, #content, .no-bottom.no-top {
            background: #1A1A1B !important;
        }
        
        html {
            background: #1A1A1B !important;
        }
        
        .centered-logo { width: 240px !important; height: auto !important; min-width: 180px !important; display:block !important; margin:0 auto !important; }
        @media (max-width: 768px) { .centered-logo { width:180px !important; } }
        .logo-container-hero { text-align:center; margin: 10px 0 20px; }
        
        /* Form styling to match index.php */
        .box-rounded {
            background: rgba(30, 41, 59, 0.8) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .box-rounded h3 {
            color: #e2e8f0 !important;
        }
        
        .box-rounded p {
            color: #94a3b8 !important;
        }
        
        .box-rounded p a {
            background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 600;
        }
        
        .form-control {
            background: rgba(15, 23, 42, 0.6) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #e2e8f0 !important;
        }
        
        .form-control::placeholder {
            color: #64748b !important;
        }
        
        .form-control:focus {
            background: rgba(30, 41, 59, 0.8) !important;
            border-color: #e67753 !important;
            box-shadow: 0 0 0 3px rgba(230, 119, 83, 0.1) !important;
        }
        
        /* Gradient button */
        .btn-main {
            background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%) !important;
            border: none !important;
            color: white !important;
            font-weight: 600 !important;
            transition: transform 0.2s, box-shadow 0.2s !important;
        }
        
        .btn-main:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(230, 119, 83, 0.4) !important;
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
                                        <a href="register.php">Register<span></span></a>
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
                                            <img src="../assets/logo2.png" alt="Logo" class="centered-logo" style="width:240px; min-width:180px; height:auto;" />
                                        </a>
                                    </div>
                                </div>

                                <div class="col-lg-4 offset-lg-2 wow fadeIn" data-wow-delay=".5s">
                                    <div class="box-rounded padding40" data-bgcolor="#ffffff">
                                        <h3 class="mb10">Sign In</h3>
                                        <p>Login using an existing account or create a new account <a
                                                href="register.php">here<span></span></a>.</p>
                                        <form name="loginForm" id='login_form' class="form-border" method="post">

                                            <div class="field-set">
                                                <input type='text' name='information' id='information'
                                                    class="form-control" placeholder="Email / Phone number">
                                            </div>
                                            <div class="field-set">
                                                <input type='password' name='password' id='password'
                                                    class="form-control" placeholder="Password">
                                            </div>
                                            <div class="field-set">
                                                <input type='submit' id='send_login' value='Submit'
                                                    class="btn btn-main btn-fullwidth color-2">
                                            </div>
                                            <div class="clearfix"></div>

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
        <?php //include('../components/footer.php'); ?>
        <!-- footer close -->

    </div>



    <!-- Javascript Files
    ================================================== -->
    <?php include('../components/jslinks.php'); ?>
    <script src="./custom_js/custom_login.js"></script>


</body>

</html>