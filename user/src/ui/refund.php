<!DOCTYPE html>
<html lang="en">
<?php
include '../backend/dbconfig/connection.php';
session_start();
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
?>

<head>
    <title>Zokli - Refund Policy</title>
    <link rel="icon" href="../assets/logo2.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="Zokli Refund Policy" name="description" />
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
                        <h1 class="policy-title">Refund Policy</h1>
                        <p class="policy-sub">Last updated: December 2025</p>

                        <p>At Zokli <strong>(Blrleaf Technology OPC Private Limited)</strong>, we aim to provide clear, fair
                            policies for subscriptions, purchases, and payments. This Refund Policy explains when
                            refunds are available, how to request one, and how we process refunds.</p>

                        <h3>1. General</h3>
                        <p>Refund eligibility depends on the product or service purchased. Free features and basic
                            accounts do not require payments and thus are not subject to refunds. For paid subscriptions
                            or one-time purchases, see the sections below.</p>

                        <h3>2. Subscriptions</h3>
                        <p>Recurring subscriptions may be canceled at any time. Refunds for subscription fees are
                            generally not provided for partial billing periods. In special circumstances (e.g., billing
                            errors) we may issue prorated or full refunds at our discretion.</p>

                        <h3>3. One-time Purchases</h3>
                        <p>One-time purchases (e.g., credits or premium assets) may be eligible for refund within 14
                            days of purchase if unused. Digital goods that have been accessed or consumed are typically
                            non-refundable.</p>

                        <h3>4. How to Request a Refund</h3>
                        <ol>
                            <li>Email Zokli.india@gmail.com with your order details and reason for the refund request.
                            </li>
                            <li>Provide any supporting information (transaction ID, date, account email).</li>
                            <li>Our support team will review and respond within 7 business days.</li>
                        </ol>

                        <h3>5. Processing Refunds</h3>
                        <p>Approved refunds will be issued to the original payment method. It may take up to 7 business
                            days for refunded amounts to appear depending on your bank or payment provider.</p>

                        <h3>6. Chargebacks</h3>
                        <p>If you file a chargeback with your payment provider, we may suspend your account while
                            investigating. If a chargeback is found to be invalid, we reserve the right to recover funds
                            and suspend the account.</p>

                        <h3>7. Contact</h3>
                        <p>For refund requests and questions, contact: Zokli.india@gmail.com or call us at +91 9980769600</p>
                    </div>
                </div>
            </section>
        </div>

        <?php include('../components/footer.php'); ?>
    </div>

    <?php include('../components/jslinks.php'); ?>
</body>

</html>