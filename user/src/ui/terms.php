<!DOCTYPE html>
<html lang="en">
<?php
include '../backend/dbconfig/connection.php';
session_start();
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
?>

<head>
    <title>Zokli - Terms & Agreement</title>
    <link rel="icon" href="../assets/logo2.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="Zokli Terms & Agreement" name="description" />
    <?php include('../components/csslinks.php') ?>
    <style>
        /* keep minimal additional styles so page matches site */
        .policy-container {
            padding: 80px 0;
        }

        #logo {
            margin-bottom: 30px;
        }

        .policy-card {
            background: rgba(255, 255, 255, 0.03);
            padding: 30px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            margin-top: 70px;
        }

        .policy-title {
            font-size: 2.2rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            color: #a5b4fc;
        }

        .policy-sub {
            color: #94a3b8;
            margin-bottom: 20px
        }
    </style>
</head>

<body class="dark-scheme de-grey">
    <div id="wrapper">
        <?php include('../components/header.php') ?>

        <div class="no-bottom no-top" id="content">
            <section class="policy-container">
                <div class="container">
                    <div class="policy-card">
                        <h1 class="policy-title">Terms & Agreement</h1>
                        <p class="policy-sub">Last updated: October 2025</p>

                        <p>Welcome to Zokli <strong>(Blr Leaf Technologies Pvt Ltd.)</strong>. These Terms & Agreement
                            ("Terms") govern your access to and use of our website and services. By accessing or using
                            Zokli, you agree to be bound by these Terms.</p>

                        <h3>1. Using Zokli</h3>
                        <p>You must be at least 13 years old to use our services. You agree to provide accurate
                            information when creating an account and to keep your account credentials secure.</p>

                        <h3>2. Acceptable Use</h3>
                        <p>When using Zokli you must not:</p>
                        <ul>
                            <li>Violate any law or regulation;</li>
                            <li>Infringe others' rights, including intellectual property or privacy;</li>
                            <li>Post abusive, obscene, or harassing content;</li>
                            <li>Attempt to interfere with the service or access another user's account.</li>
                        </ul>

                        <h3>3. Content</h3>
                        <p>Users retain ownership of the content they post. By posting content you grant Zokli a license
                            to host, use, distribute and display that content as necessary to provide the service.</p>

                        <h3>4. Termination</h3>
                        <p>We may suspend or terminate accounts that violate these Terms or when required by law. Upon
                            termination, your right to use the service will end.</p>

                        <h3>5. Disclaimers</h3>
                        <p>The service is provided "as is" and we disclaim warranties to the fullest extent permitted by
                            law.</p>

                        <h3>6. Limitation of Liability</h3>
                        <p>To the extent permitted by law, Zokli and its affiliates will not be liable for indirect,
                            incidental, special or consequential damages arising out of or related to your use of the
                            service.</p>

                        <h3>7. Governing Law</h3>
                        <p>These Terms are governed by the laws of the jurisdiction where Zokli operates unless
                            otherwise required by applicable law.</p>

                        <h3>8. Changes to Terms</h3>
                        <p>We may modify these Terms. Continued use after changes indicates acceptance of the updated
                            Terms.</p>

                        <h3>9. Contact</h3>
                        <p>Questions about these Terms can be sent to: Zokli.india@gmail.com</p>
                    </div>
                </div>
            </section>
        </div>

        <?php include('../components/footer.php'); ?>
    </div>

    <?php include('../components/jslinks.php'); ?>
</body>

</html>