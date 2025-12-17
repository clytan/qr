<!DOCTYPE html>
<html lang="en">
<?php
include '../backend/dbconfig/connection.php';
session_start();
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
?>

<head>
    <title>Zokli - Privacy Policy</title>
    <link rel="icon" href="../assets/logo2.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="Zokli Privacy Policy" name="description" />
    <?php include('../components/csslinks.php') ?>
    <style>
          /* keep minimal additional styles so page matches site */
        .policy-container{padding:80px 0;}
        #logo {
            margin-bottom: 30px;
        }
        .policy-card{
            background:rgba(255,255,255,0.03);
            padding:30px;
            border-radius:12px;
            border:1px solid rgba(255,255,255,0.06);
            margin-top: 70px;
        }
        .policy-title{
            font-size:2.2rem;
            margin-bottom:10px;
            background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
            color: #a5b4fc;
        }
        .policy-sub{color:#94a3b8;margin-bottom:20px}
    </style>
</head>

<body class="dark-scheme de-grey">
    <div id="wrapper">
        <?php include('../components/header.php') ?>

        <div class="no-bottom no-top" id="content">
            <section class="policy-container">
                <div class="container">
                    <div class="policy-card">
                        <h1 class="policy-title">Privacy Policy</h1>
                        <p class="policy-sub">Last updated: December 2025</p>

                        <p>Welcome to Zokli <strong>(Blrleaf Technology OPC Private Limited)</strong>. Your privacy matters to us. This Privacy Policy explains how Zokli ("we", "our", or "us") collects, uses, discloses, and protects information when you visit or use our website and services.</p>

                        <h3>1. Information We Collect</h3>
                        <p>We may collect information you provide directly (for example, when you register, update your profile, or contact support) such as name, email address, username, profile data, and any other information you choose to provide. We also collect data automatically when you use our services, including device information, IP address, browser type, pages visited, and usage statistics.</p>

                        <h3>2. How We Use Your Information</h3>
                        <ul>
                            <li>To provide, maintain and improve our services.</li>
                            <li>To personalize your experience and deliver content and features tailored to you.</li>
                            <li>To communicate with you about your account, transactions, and updates.</li>
                            <li>To detect, prevent and address technical issues, abuse, or fraud.</li>
                        </ul>

                        <h3>3. Sharing and Disclosure</h3>
                        <p>We do not sell your personal information. We may share information with third parties only in the following circumstances:</p>
                        <ul>
                            <li>With service providers who perform services on our behalf (hosting, analytics, payment processing).</li>
                            <li>To comply with legal obligations or respond to lawful requests.</li>
                            <li>To protect the rights, property or safety of Zokli, our users, or the public.</li>
                        </ul>

                        <h3>4. Cookies & Tracking</h3>
                        <p>We use cookies and similar tracking technologies to operate and improve our services. You can control cookies via your browser settings, but disabling certain cookies may affect functionality.</p>

                        <h3>5. Data Security</h3>
                        <p>We implement reasonable administrative, technical and physical safeguards designed to protect your information. However no method of transmission or storage is 100% secure and we cannot guarantee absolute security.</p>

                        <h3>6. Your Choices</h3>
                        <p>You can access and update certain account information through your profile. You may opt out of promotional emails by following the unsubscribe instructions in those messages.</p>

                        <h3>7. Children</h3>
                        <p>Our services are not directed to children under 13. We do not knowingly collect personal information from children under 13.</p>

                        <h3>8. Changes to this Policy</h3>
                        <p>We may update this Policy from time to time. We will post the updated policy with a revised "Last updated" date.</p>

                        <h3>9. Contact Us</h3>
                        <p>If you have questions about this Privacy Policy, please contact us at: Zokli.india@gmail.com or call us at +91 9980769600</p>
                    </div>
                </div>
            </section>
        </div>

        <?php include('../components/footer.php'); ?>
    </div>

    <?php include('../components/jslinks.php'); ?>
</body>

</html>
