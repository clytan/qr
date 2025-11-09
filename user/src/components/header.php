<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<header class="transparent scroll-dark">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="de-flex sm-pt10 justify-content-center">
                    <div class="de-flex-col text-center">
                        <!-- logo begin -->
                        <div id="logo" style="margin: 8px 0 20px;">
                            <a href="index.php">
                                <img alt="Logo" class="centered-logo" src="../assets/logo.png" style="width:240px; min-width:180px; height:auto;" />
                            </a>
                        </div>
                        <!-- logo close -->
                    </div>
                    <style>
                        .centered-logo {
                            width: 240px !important; /* desktop size - increased */
                            height: auto !important;
                            min-width: 180px !important; /* prevent excessive shrinking */
                            display: block !important;
                            margin: 0 auto !important;
                            max-width: none !important; /* use fixed size unless viewport smaller */
                        }

                        .justify-content-center {
                            justify-content: center !important;
                        }

                        .text-center {
                            text-align: center !important;
                        }

                        header .container {
                            display: flex;
                            justify-content: center;
                        }

                        @media (max-width: 768px) {
                            .centered-logo {
                                width: 180px; /* mobile size - increased */
                                height: auto;
                            }
                        }
                    </style>
                    <!-- <div class="de-flex-col">
                            <input id="quick_search" class="xs-hide style-2" name="quick_search"
                                placeholder="search item here..." type="text" />
                        </div> -->
                </div>
                <div class="de-flex-col header-col-mid d-none">
                    <!-- mainmenu begin -->
                    <ul id="mainmenu">
                        <li>
                            <a href="index.php">HOME<span></span></a>
                        </li>
                        <li>
                            <a href="community.php">CHAT<span></span></a>
                        </li>
                        <li>
                            <a href="login.php">LOGIN<span></span></a>
                        </li>
                        <li>
                            <a href="register.php">REGISTER<span></span></a>
                        </li>
                        <li>
                            <a href="admin\src\ui\login.php">admin<span></span></a>
                        </li>
                    </ul>
                    <!-- mainmenu close -->
                    <div class="menu_side_area">
                        <a href="wallet.php" class="btn-main btn-wallet"><i
                                class="icon_wallet_alt"></i><span>Wallet</span></a>
                        <span id="menu-btn"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</header>