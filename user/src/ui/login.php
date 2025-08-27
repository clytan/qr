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
                                    <h1>Create, sell or collect digital items.</h1>
                                    <p class="lead">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do
                                        eiusmod tempor incididunt ut labore et dolore magna aliqua ut enim.</p>
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
        <?php include('../components/footer.php'); ?>
        <!-- footer close -->

    </div>



    <!-- Javascript Files
    ================================================== -->
    <?php include('../components/jslinks.php'); ?>
    <script src="./custom_js/custom_login.js"></script>


</body>

</html>