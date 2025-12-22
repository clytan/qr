<!DOCTYPE html>
<html lang="en">

<head>
    <title>Admin Login - Zokli</title>
    <link rel="icon" href="/qr/logo/logo-both.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ==================== BASE STYLES ==================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            background: #0f172a !important;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* ==================== PAGE LAYOUT ==================== */
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            position: relative;
            overflow: hidden;
        }

        /* Animated background effect */
        .auth-page::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(233, 67, 122, 0.15) 0%, transparent 70%);
            top: -100px;
            right: -100px;
            animation: pulse 8s ease-in-out infinite;
        }

        .auth-page::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(226, 173, 42, 0.1) 0%, transparent 70%);
            bottom: -100px;
            left: -100px;
            animation: pulse 10s ease-in-out infinite reverse;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        /* ==================== FORM BOX ==================== */
        .auth-box {
            width: 100%;
            max-width: 420px;
            background: rgba(30, 41, 59, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 40px 35px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 10;
        }

        /* ==================== ADMIN BADGE ==================== */
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: linear-gradient(135deg, rgba(233, 67, 122, 0.2), rgba(226, 173, 42, 0.2));
            border: 1px solid rgba(230, 119, 83, 0.3);
            border-radius: 30px;
            color: #e67753;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .admin-badge i {
            font-size: 14px;
        }

        /* ==================== FORM LOGO ==================== */
        .auth-logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .auth-logo img {
            width: 180px;
            height: auto;
            margin-bottom: 15px;
        }

        /* ==================== FORM HEADER ==================== */
        .auth-title {
            color: #f1f5f9;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 8px;
        }

        .auth-subtitle {
            color: #94a3b8;
            font-size: 14px;
            text-align: center;
            margin-bottom: 30px;
        }

        /* ==================== FORM INPUTS ==================== */
        .auth-field {
            margin-bottom: 20px;
            position: relative;
        }

        .auth-label {
            display: block;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .auth-input-wrapper {
            position: relative;
        }

        .auth-input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 16px;
            transition: color 0.2s ease;
        }

        .auth-input {
            width: 100%;
            height: 50px;
            padding: 12px 16px 12px 48px;
            background: rgba(15, 23, 42, 0.6);
            border: 1.5px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
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

        .auth-input:focus + i,
        .auth-input:focus ~ i {
            color: #e67753;
        }

        /* ==================== SUBMIT BUTTON ==================== */
        .auth-btn {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }

        .auth-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .auth-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(230, 119, 83, 0.4);
        }

        .auth-btn:hover::before {
            left: 100%;
        }

        .auth-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* ==================== ERROR MESSAGE ==================== */
        .auth-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            display: none;
        }

        .auth-error.show {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ==================== MOBILE RESPONSIVE ==================== */
        @media (max-width: 480px) {
            .auth-box {
                padding: 30px 25px;
            }

            .auth-logo img {
                width: 70px;
            }

            .auth-title {
                font-size: 24px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="auth-page">
        <div class="auth-box">
            <!-- Admin Badge -->
            <div style="text-align: center;">
                <span class="admin-badge">
                    <i class="fas fa-shield-alt"></i>
                    Admin Access Only
                </span>
            </div>

            <!-- Logo -->
            <div class="auth-logo">
                <img src="/qr/logo/logo-both.png" alt="Zokli Logo" onerror="this.style.display='none'" />
            </div>

            <h1 class="auth-title">Admin Login</h1>
            <p class="auth-subtitle">Sign in to access the admin dashboard</p>

            <!-- Error Message -->
            <div class="auth-error" id="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span id="error-text"></span>
            </div>

            <form name="loginForm" id="login_form" method="post">
                <div class="auth-field">
                    <label class="auth-label">Username or Email</label>
                    <div class="auth-input-wrapper">
                        <input type="text" name="username" id="username" class="auth-input" placeholder="Enter your username" required>
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                <div class="auth-field">
                    <label class="auth-label">Password</label>
                    <div class="auth-input-wrapper">
                        <input type="password" name="password" id="password" class="auth-input" placeholder="Enter your password" required>
                        <i class="fas fa-lock"></i>
                    </div>
                </div>
                <button type="submit" id="submit_btn" class="auth-btn">
                    <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>
                    Sign In
                </button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#login_form').on('submit', function(e) {
                e.preventDefault();
                
                var $btn = $('#submit_btn');
                var originalText = $btn.html();
                
                // Disable button and show loading
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Signing in...');
                
                // Hide any previous errors
                $('#error-message').removeClass('show');
                
                $.ajax({
                    url: '../backend/login.php',
                    type: 'POST',
                    data: {
                        username: $('#username').val().trim(),
                        password: $('#password').val().trim()
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status) {
                            // Success - redirect to dashboard
                            window.location.href = response.data.redirect;
                        } else {
                            // Show error
                            $('#error-text').text(response.message || 'Login failed');
                            $('#error-message').addClass('show');
                            $btn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#error-text').text('An error occurred. Please try again.');
                        $('#error-message').addClass('show');
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        });
    </script>
</body>

</html>
