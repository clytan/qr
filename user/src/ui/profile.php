<?php // Start the session to access session variables (must be first line, before any output)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
$user_qr = isset($_SESSION['user_qr_id']) ? $_SESSION['user_qr_id'] : '' ;
$user_user_type = isset($_SESSION['user_user_type']) ? $_SESSION['user_user_type'] : '' ;
?>
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

</head>

<body class="dark-scheme de-grey">
    <div id="wrapper">


        <!-- content begin -->
        <div class="no-bottom no-top" id="content">
            <div id="top"></div>


            <!-- section begin -->
            <section id="section-main" aria-label="section">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-8 offset-lg-2">
                            <form id="form-create-item" class="form-border" method="post">
                                <input type="hidden" id="user_id" name="user_id"
                                    value="<?php echo htmlspecialchars($user_id); ?>">
                                <input type="hidden" id="user_qr" name="user_qr"
                                    value="<?php echo htmlspecialchars($user_qr); ?>">
                                <div class="de_tab tab_simple">

                                    <div class="de_tab_content">
                                        <div class="tab-1">
                                            <div class="row wow fadeIn">
                                                <div class="col-lg-8 mb-sm-20">
                                                    <div class="field-set">

                                                        <!-- Follow button -->
                                                        <div id="follow-btn-container" style="margin-bottom:10px;">
                                                        </div>
                                                        <input type="text" name="full_name" id="full_name"
                                                            class="form-control" placeholder="Enter Full Name" />

                                                        <div class="spacer-20"></div>


                                                        <input type="text" name="phone_number" id="phone_number"
                                                            class="form-control"
                                                            placeholder="Enter your Phone Number" />



                                                        <div class="spacer-20"></div>



                                                        <input type="text" name="email_address" id="email_address"
                                                            class="form-control" placeholder="Enter email" />



                                                        <div class="spacer-20"></div>



                                                        <input type="text" name="address" id="address"
                                                            class="form-control" placeholder="Enter Address" />



                                                        <div class="spacer-20"></div>


                                                        <div class="input-group mb-2">
                                                            <input type="text" name="website" id="website"
                                                                class="form-control" placeholder="Enter Website URL" />
                                                            <div class="input-group-append ms-2">
                                                                <input type="checkbox" id="public_website"
                                                                    class="public-toggle" data-target="website"
                                                                    name="public_website">
                                                                <label for="public_website"
                                                                    class="public-label ms-1">Public</label>
                                                            </div>
                                                        </div>
                                                        <div class="spacer-20"></div>
                                                        <div class="input-group mb-2">
                                                            <input type="text" name="whatsapp_link" id="whatsapp_link"
                                                                class="form-control"
                                                                placeholder="Enter WhatsApp Number or link " />
                                                            <div class="input-group-append ms-2">
                                                                <input type="checkbox" id="public_whatsapp_link"
                                                                    class="public-toggle" data-target="whatsapp_link"
                                                                    name="public_whatsapp_link">
                                                                <label for="public_whatsapp_link"
                                                                    class="public-label ms-1">Public</label>
                                                            </div>
                                                        </div>
                                                        <div class="spacer-20"></div>
                                                        <div class="input-group mb-2">
                                                            <input type="text" name="telegram_link" id="telegram_link"
                                                                class="form-control"
                                                                placeholder="Enter Telegram Link " />
                                                            <div class="input-group-append ms-2">
                                                                <input type="checkbox" id="public_telegram_link"
                                                                    class="public-toggle" data-target="telegram_link"
                                                                    name="public_telegram_link">
                                                                <label for="public_telegram_link"
                                                                    class="public-label ms-1">Public</label>
                                                            </div>
                                                        </div>

                                                        <div class="spacer-20"></div>


                                                        <div class="input-group mb-2">
                                                            <input type="text" name="twitter_username"
                                                                id="twitter_username" class="form-control"
                                                                placeholder="Enter Twitter username" />
                                                            <div class="input-group-append ms-2">
                                                                <input type="checkbox" id="public_twitter_username"
                                                                    class="public-toggle" data-target="twitter_username"
                                                                    name="public_twitter_username">
                                                                <label for="public_twitter_username"
                                                                    class="public-label ms-1">Public</label>
                                                            </div>
                                                        </div>

                                                        <div class="spacer-20"></div>



                                                        <div class="input-group mb-2">
                                                            <input type="text" name="instagram_username"
                                                                id="instagram_username" class="form-control"
                                                                placeholder="Enter Instagram username" />
                                                            <div class="input-group-append ms-2">
                                                                <input type="checkbox" id="public_instagram_username"
                                                                    class="public-toggle"
                                                                    data-target="instagram_username"
                                                                    name="public_instagram_username">
                                                                <label for="public_instagram_username"
                                                                    class="public-label ms-1">Public</label>
                                                            </div>
                                                        </div>

                                                        <div class="spacer-20"></div>


                                                        <div class="input-group mb-2">
                                                            <input type="text" name="youtube_username"
                                                                id="youtube_username" class="form-control"
                                                                placeholder="Enter YouTube Channel" />
                                                            <div class="input-group-append ms-2">
                                                                <input type="checkbox" id="public_youtube_username"
                                                                    class="public-toggle" data-target="youtube_username"
                                                                    name="public_youtube_username">
                                                                <label for="public_youtube_username"
                                                                    class="public-label ms-1">Public</label>
                                                            </div>
                                                        </div>

                                                        <div class="spacer-20"></div>


                                                        <div class="input-group mb-2">
                                                            <input type="text" name="linkedin_username"
                                                                id="linkedin_username" class="form-control"
                                                                placeholder="Enter LinkedIn URL" />
                                                            <div class="input-group-append ms-2">
                                                                <input type="checkbox" id="public_linkedin_username"
                                                                    class="public-toggle"
                                                                    data-target="linkedin_username"
                                                                    name="public_linkedin_username">
                                                                <label for="public_linkedin_username"
                                                                    class="public-label ms-1">Public</label>
                                                            </div>
                                                        </div>

                                                        <div class="spacer-20"></div>


                                                        <div class="input-group mb-2">
                                                            <input type="text" name="snapchat_username"
                                                                id="snapchat_username" class="form-control"
                                                                placeholder="Enter Snapchat username" />
                                                            <div class="input-group-append ms-2">
                                                                <input type="checkbox" id="public_snapchat_username"
                                                                    class="public-toggle"
                                                                    data-target="snapchat_username"
                                                                    name="public_snapchat_username">
                                                                <label for="public_snapchat_username"
                                                                    class="public-label ms-1">Public</label>
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>

                                                <div id="sidebar" class="col-lg-4">
                                                    <h5>Profile image <i class="fa fa-info-circle id-color-2"
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            title="Recommend 400 x 400. Max size: 50MB. Click the image to upload."></i>
                                                    </h5>

                                                    <img src="../assets/images/author_single/author_thumbnail.jpg"
                                                        id="click_profile_img" class="d-profile-img-edit img-fluid"
                                                        alt="">
                                                    <input type="file" id="upload_profile_img">

                                                    <div class="spacer-30"></div>

                                                    <h5>Profile banner </h5>
                                                    <img src="" id="click_banner_img"
                                                        class="d-banner-img-edit img-fluid" alt="">
                                                    <?php
                                                        $qr_id_display = '';
                                                        if (isset($_GET['qr']) && $_GET['qr'] !== '') {
                                                                $qr_id_display = $_GET['qr'];
                                                        } elseif (!empty($user_qr)) {
                                                                $qr_id_display = $user_qr;
                                                        }
                                                        if ($qr_id_display !== ''):
                                                    ?>
                                                    <div id="public-qr-id"
                                                        style="margin-top:10px; font-weight:bold; word-break:break-all;">
                                                        QR ID: <?php echo htmlspecialchars($qr_id_display); ?></div>
                                                    <?php endif; ?>

                                                    <?php if (empty($_GET['qr'])): ?>
                                                    <div id="qr-color-controls">
                                                        <div class="mt-3 mb-2">
                                                            <label for="qr-color-dark" style="font-weight:600;">QR
                                                                Foreground Color:</label>
                                                            <input type="color" id="qr-color-dark" name="qr-color-dark"
                                                                value="#000000"
                                                                style="width:36px; height:36px; border:none; border-radius:6px; box-shadow:0 2px 8px rgba(0,0,0,0.10); cursor:pointer; outline:none; transition:box-shadow 0.2s;">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="qr-color-light" style="font-weight:600;">QR
                                                                Background Color:</label>
                                                            <input type="color" id="qr-color-light"
                                                                name="qr-color-light" value="#ffffff"
                                                                style="width:36px; height:36px; border:none; border-radius:6px; box-shadow:0 2px 8px rgba(0,0,0,0.10); cursor:pointer; outline:none; transition:box-shadow 0.2s;">
                                                        </div>
                                                        <button type="button"
                                                            class="btn btn-gradient d-flex align-items-center gap-2 shadow-sm px-4 py-2"
                                                            id="save-qr-color"
                                                            style="background: linear-gradient(90deg, #6a11cb 0%, #2575fc 100%); color: #fff; border: none; border-radius: 30px; font-weight: 600; font-size: 1rem; transition: box-shadow 0.2s;">
                                                            <span>Save QR Colors</span>
                                                        </button>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>





                                    </div>
                                </div>

                                <div class="spacer-30"></div>
                                <input type="submit" id="submit" class="btn-main" value="Update profile">
                            </form>
                        </div>
                    </div>
                </div>
            </section>

        </div>
        <!-- content close -->

        <a href="#" id="back-to-top"></a>


    </div>



    <!-- Javascript Files
    ================================================== -->
    <?php include('../components/jslinks.php'); ?>
    <script src="../components/qr/qrcode.min.js"></script>
    <script src="./custom_js/custom_profile.js"></script>


</body>

</html>