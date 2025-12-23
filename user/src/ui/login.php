<!DOCTYPE html>
<html lang="zxx">

<head>
    <title>ZQR - Login</title>
    <link rel="icon" href="../../../logo/logo.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="" name="keywords" />
    <meta content="" name="author" />
    <!-- CSS Files
    ================================================== -->
    <?php include('../components/csslinks.php') ?>
    <style>
        /* ==================== BASE STYLES ==================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            background: #1A1A1B !important;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        #wrapper, #content, .no-bottom.no-top {
            background: transparent !important;
        }

        /* ==================== PAGE LAYOUT ==================== */
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
            background: linear-gradient(135deg, #1A1A1B 0%, #0f172a 100%);
        }

        /* ==================== FORM BOX ==================== */
        .auth-box {
            width: 100%;
            max-width: 380px;
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 30px 28px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
        }

        /* ==================== FORM LOGO ==================== */
        .auth-logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .auth-logo img {
            width: 130px;
            height: auto;
        }

        /* ==================== FORM HEADER ==================== */
        .auth-title {
            color: #f1f5f9;
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 6px;
        }

        .auth-subtitle {
            color: #94a3b8;
            font-size: 14px;
            text-align: center;
            margin-bottom: 24px;
        }

        .auth-subtitle a {
            background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 600;
            text-decoration: none;
        }

        /* ==================== FORM INPUTS ==================== */
        .auth-field {
            margin-bottom: 16px;
        }

        .auth-input {
            width: 100%;
            height: 44px;
            padding: 12px 14px;
            background: rgba(15, 23, 42, 0.6);
            border: 1.5px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            font-size: 14px;
            color: #e2e8f0;
            transition: all 0.2s ease;
        }

        .auth-input::placeholder {
            color: #64748b;
        }

        .auth-input:focus {
            outline: none;
            background: rgba(30, 41, 59, 0.8);
            border-color: #e67753;
            box-shadow: 0 0 0 3px rgba(230, 119, 83, 0.12);
        }

        /* ==================== SUBMIT BUTTON ==================== */
        .auth-btn {
            width: 100%;
            height: 44px;
            background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 8px;
        }

        .auth-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(230, 119, 83, 0.35);
        }

        /* ==================== PASSWORD TOGGLE ==================== */
        .password-toggle-container {
            position: relative;
        }

        .password-toggle-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            color: #64748b;
            z-index: 10;
            padding: 4px;
        }

        .password-toggle-btn:hover {
            color: #94a3b8;
        }

        .auth-input.with-toggle {
            padding-right: 40px !important;
        }

        /* ==================== MOBILE RESPONSIVE ==================== */
        @media (max-width: 480px) {
            .auth-box {
                padding: 25px 22px;
            }

            .auth-logo img {
                width: 130px;
            }

            .auth-title {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>
    <!-- Page Content -->
    <div class="auth-page">
        <div class="auth-box">
            <!-- Logo inside form -->
            <div class="auth-logo">
                <a href="index.php">
                    <img src="../../../logo/logo-both.png" alt="Zokli Logo" />
                </a>
            </div>

            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-subtitle">Don't have an account? <a href="register.php">Register here</a></p>

            <form name="loginForm" id="login_form" method="post">
                <div class="auth-field">
                    <input type="text" name="information" id="information" class="auth-input" placeholder="📧 Email or Phone number" required>
                </div>
                <div class="auth-field">
                    <div class="password-toggle-container">
                        <input type="password" name="password" id="password" class="auth-input with-toggle" placeholder="🔒 Password" required>
                        <button type="button" class="password-toggle-btn" id="password-toggle">👁️</button>
                    </div>
                </div>
                <button type="submit" id="send_login" class="auth-btn">Sign In</button>
            </form>
        </div>
    </div>

    <!-- Javascript Files
    ================================================== -->
    <?php include('../components/jslinks.php'); ?>
    <script src="./custom_js/custom_login.js"></script>

    <!-- Password Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordToggle = document.getElementById('password-toggle');
            const passwordInput = document.getElementById('password');

            if (passwordToggle && passwordInput) {
                passwordToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isPassword = passwordInput.type === 'password';
                    passwordInput.type = isPassword ? 'text' : 'password';
                    passwordToggle.textContent = isPassword ? '👁️‍🗨️' : '👁️';
                });
            }
        });
    </script>
</body>

</html>