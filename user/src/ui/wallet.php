<!DOCTYPE html>
<html lang="zxx">

<head>
    <title>Gigaland - NFT Marketplace Website</title>
    <link rel="icon" href="images/icon-red.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="Gigaland - NFT Marketplace Website" name="description" />
    <meta content="" name="keywords" />
    <meta content="" name="author" />
    <!-- CSS Files
    ================================================== -->
    <?php include('../components/csslinks.php') ?>
    <!-- FontAwesome 5+ for wallet icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body class="dark-scheme de-grey">
    <div id="wrapper">

        <!-- header begin -->
        <?php include('../components/header.php') ?>
        <!-- header close -->
        <!-- content begin -->
        <div class="no-bottom no-top" id="content">
            <div id="top"></div>
            <!-- section begin -->
            <section aria-label="section">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-6">
                            <div style="display: flex; justify-content: center;">
                                <h2 class="mb-4 text-white"
                                    style="font-weight:700;letter-spacing:1px; text-align:center;">Wallet</h2>
                            </div>
                            <!-- Wallet Balance Card -->
                            <div class="card text-center mb-4"
                                style="background: linear-gradient(90deg, #4e54c8 0%, #8f94fb 100%); color: #fff; border-radius: 24px; box-shadow: 0 6px 32px rgba(0,0,0,0.25);">
                                <div class="card-body" style="padding: 2.5rem 1.5rem;">
                                    <div id="wallet-balance"
                                        style="font-size: 2.8rem; font-weight: bold; margin: 0 0 8px 0; letter-spacing:1px;">
                                        Loading...</div>
                                    <div
                                        style="font-size: 1.2rem; opacity: 0.9; font-weight: 500; letter-spacing: 1px;">
                                        Coins</div>
                                </div>
                            </div>
                            <!-- Wallet Actions -->
                            <div class="d-flex justify-content-center mb-4">
                                <div class="text-center mx-3">
                                    <button class="btn mb-2"
                                        style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#4e54c8 0%,#8f94fb 100%);box-shadow:0 2px 8px rgba(79,84,200,0.25);border:2px solid #5a5e8c;display:flex;align-items:center;justify-content:center;transition:box-shadow 0.2s;">
                                        <i class="fas fa-exchange-alt" style="color:#fff;font-size:1.3rem;"></i>
                                    </button>
                                    <div style="font-size:0.95rem;">Transfer</div>
                                </div>
                                <div class="text-center mx-3">
                                    <button class="btn mb-2"
                                        style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#8f94fb 0%,#4e54c8 100%);box-shadow:0 2px 8px rgba(79,84,200,0.25);border:2px solid #5a5e8c;display:flex;align-items:center;justify-content:center;transition:box-shadow 0.2s;">
                                        <i class="fa fa-gift" style="color:#fff;font-size:1.3rem;"></i>
                                    </button>
                                    <div style="font-size:0.95rem;">Redeem</div>
                                </div>
                            </div>
                            <hr style="border-top: 1px solid #333; margin-bottom: 2rem; opacity: 0.3;">
                            <!-- Transaction List -->
                            <div class="card"
                                style="background:#23263b; border-radius: 18px; box-shadow:0 2px 12px rgba(79,84,200,0.10);">
                                <div class="card-body">
                                    <h5 class="mb-3" style="color:#fff; font-weight:600; letter-spacing:1px;">
                                        Transactions</h5>
                                    <ul class="list-unstyled" id="wallet-transactions">
                                        <li>Loading...</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
        <!-- content close -->

        <a href="#" id="back-to-top"></a>

        <!-- footer begin -->
        <?php include('../components/footer.php'); ?>
        <!-- footer close -->

    </div>



    <!-- Javascript Files
    ================================================== -->

    <?php include('../components/jslinks.php'); ?>
    <script src="../ui/custom_js/wallet_dynamic.js"></script>


</body>

</html>