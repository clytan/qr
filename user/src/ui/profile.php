<!DOCTYPE html>
<html lang="en">
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../backend/dbconfig/connection.php';

// Check if accessing via QR parameter (public profile view)
$viewing_qr = isset($_GET['qr']) || isset($_GET['QR']);
$viewed_qr = $viewing_qr ? (isset($_GET['qr']) ? $_GET['qr'] : $_GET['QR']) : '';

// Only require authentication if NOT viewing a public profile
if (!$viewing_qr) {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header('Location: index.php');
        exit();
    }
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
$user_qr = isset($_SESSION['user_qr_id']) ? $_SESSION['user_qr_id'] : '';
$user_user_type = isset($_SESSION['user_user_type']) ? $_SESSION['user_user_type'] : '';

// Check if logged-in user is viewing someone else's profile
$is_viewing_other_profile = $viewing_qr && !empty($user_id) && $viewed_qr !== $user_qr;
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | ZQR Connect</title>
    <link rel="icon" href="../../../logo/logo.png" type="image/gif" sizes="16x16">
    <?php include('../components/csslinks.php') ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #e67753;
            --primary-dark: #d6653f;
            --accent-color: #E2AD2A;
            --text-color: #e2e8f0;
            --text-secondary: #94a3b8;
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --card-hover: #2d3b52;
            --border-color: #334155;
            --input-bg: rgba(255, 255, 255, 0.08);
            --input-border: #475569;
            --success: #10b981;
            --danger: #ef4444;
            --gradient-1: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            --gradient-2: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
        }

        body {
            background: #0f172a;
            background-image:
                radial-gradient(at 0% 0%, rgba(102, 126, 234, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(240, 147, 251, 0.15) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(118, 75, 162, 0.1) 0px, transparent 50%);
            color: var(--text-color);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        /* Add spacing for header - transparent fixed header needs margin not padding */
        #content {
            min-height: 100vh;
            position: relative;
            z-index: 1;
            padding: 2rem 0;
        }

        /* Navigation Tabs Styling */
        .nav-tabs-container {
            display: flex;
            justify-content: center;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 2rem;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .nav-tabs {
            border-bottom: none;
            margin-bottom: 0;
            display: flex;
            width: 100%;
            justify-content: center;
            padding: 0;
        }

        .nav-tabs .nav-item {
            margin: 0;
            flex: 1;
            max-width: 200px;
            text-align: center;
        }

        .nav-tabs .nav-link {
            color: var(--text-secondary);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin: 0 0.25rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.03);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            min-height: 45px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .nav-tabs-container {
                margin: 0.5rem -0.5rem 2rem -0.0rem;
                padding: 0 0.5rem;
                justify-content: flex-start;
            }

            .nav-tabs {
                width: 100%;
                justify-content: space-between;
                gap: 8px;
                padding: 0 4px;
            }

            .nav-tabs .nav-item {
                flex: 1;
                min-width: 100px;
            }

            .nav-tabs .nav-link {
                padding: 0.625rem 0.5rem;
                font-size: 0.9rem;
                white-space: nowrap;
                margin: 0;
            }

            .nav-tabs .nav-link i {
                margin-right: 4px;
                font-size: 1rem;
            }
        }

        .nav-tabs .nav-link i {
            margin-right: 0.5rem;
        }

        .nav-tabs .nav-link:hover {
            color: var(--primary-color);
            border-color: transparent;
            background: rgba(102, 126, 234, 0.1);
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-color: var(--border-color) var(--border-color) transparent;
            background: var(--card-bg);
            font-weight: 600;
        }

        .nav-tabs .nav-link.active i {
            color: var(--primary-color);
        }

        .profile-container {
            max-width: 1400px;
            margin: 0 auto 2rem;
            padding: 2rem 1rem 8rem;
            position: relative;
            z-index: 1;
            margin-top: 120px;
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(100px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Page Title */
        .page-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .page-title p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        /* Subscription Status Styles */
        .subscription-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(15, 23, 42, 0.95) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .subscription-info { padding: 1rem 0; }
        .sub-row { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .sub-row:last-child { border-bottom: none; }
        .sub-label { color: var(--text-secondary); font-size: 0.9rem; }
        .sub-value { color: var(--text-color); font-weight: 600; }
        .sub-tier { padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; }
        .sub-tier.tier-gold { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #1f2937; }
        .sub-tier.tier-silver { background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%); color: #1f2937; }
        .sub-tier.tier-normal { background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%); color: white; }
        .sub-tier.tier-student { background: linear-gradient(135deg, #34d399 0%, #10b981 100%); color: white; }
        .subscription-info.status-ok .sub-value:last-child { color: #4ade80; }
        .subscription-info.status-warning .sub-value:last-child { color: #fbbf24; }
        .subscription-info.status-grace .sub-value:last-child { color: #f97316; }
        .subscription-info.status-expired .sub-value:last-child { color: #ef4444; }
        .sub-action { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255, 255, 255, 0.1); }
        .btn-renew { width: 100%; padding: 0.875rem; font-size: 1rem; }
        
        /* Fix modal stacking and disable backdrop */
        #subscriptionModal { z-index: 9999 !important; }
        #subscriptionModal .modal-dialog { box-shadow: 0 10px 40px rgba(0,0,0,0.5); z-index: 10000 !important; }
        #subscriptionModal .modal-content { position: relative; z-index: 10001 !important; }
        .modal-backdrop { display: none !important; }

        .profile-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
            align-items: start;
        }

        #user-name {
            text-align: center !important;
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0.75rem 0 1rem 0;
            color: var(--text-color);
            width: 100%;
            display: block;
        }

        @media (max-width: 768px) {
            .profile-container {
                margin-top: 100px;
                padding-bottom: 10rem;
            }

            .profile-header {
                grid-template-columns: 1fr;
            }

            #user-name {
                text-align: center !important;
                font-size: 1.5rem;
            }

            .page-title h1 {
                font-size: 1.5rem;
            }

            .page-title p {
                font-size: 0.85rem;
            }

            .page-title .btn-danger {
                padding: 0.4rem 0.75rem;
                font-size: 0.75rem;
                min-width: auto;
            }

            .page-title .btn-danger i {
                font-size: 0.8rem;
                margin-right: 0.2rem;
            }

            .btn-danger {
                background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%) !important;
                border: none !important;
                color: white !important;
            }

            .btn-danger:hover {
                opacity: 0.9;
                transform: translateY(-1px);
            }

            .qr-actions .btn {
                padding: 0.625rem 1rem;
                font-size: 0.85rem;
            }

            .qr-actions .btn i {
                font-size: 0.9rem;
            }
        }

        .profile-card {
            background: transparent;
            backdrop-filter: blur(10px);
            border-radius: 1.25rem;
            padding: 1.75rem;
            /* border: 1px solid rgba(255, 255, 255, 0.1); */
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            margin-bottom: 0.75rem;
            margin-top: -2.65rem;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.03), rgba(118, 75, 162, 0.03));
            opacity: 0;
            transition: opacity 0.4s ease;
            pointer-events: none;
        }

        .profile-card:hover::before {
            opacity: 1;
        }

        .profile-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        /* Profile Image Styles - Compact & Modern */
        .profile-image-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 1.25rem;
        }

        .profile-image-container::before {
            content: '';
            position: absolute;
            inset: -3px;
            background: var(--gradient-1);
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 0;
        }

        .profile-image-container:hover::before {
            opacity: 0.6;
        }

        #click_profile_img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(30, 41, 59, 0.8);
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            background: var(--gradient-1);
            position: relative;
            z-index: 1;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        #click_profile_img:hover {
            transform: scale(1.05);
            border-color: var(--primary-color);
        }

        .profile-name {
            text-align: center;
            margin-bottom: 1rem;
        }

        .profile-name h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .profile-username {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .stats-grid {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            margin: 1rem 0;
        }

        .stat-item {
            display: inline-flex;
            align-items: baseline;
            gap: 0.375rem;
            cursor: pointer;
            transition: opacity 0.2s ease;
            background: transparent;
            border: none;
            padding: 0;
        }

        .stat-item:hover {
            opacity: 0.7;
        }

        .stat-value {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-color);
        }

        .stat-label {
            font-size: 1rem;
            color: var(--text-secondary);
            font-weight: 400;
        }

        /* QR Section Enhanced */
        .qr-section {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .qr-section h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: var(--text-color);
            font-weight: 600;
        }

        .qr-container {
            background: white;
            padding: 0;
            border-radius: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 1rem auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            position: relative;
            width: 300px;
            height: 300px;
        }

        .qr-container:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
        }

        /* Custom frame overlay */
        .qr-frame-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 10;
        }

        #click_banner_img {
            position: relative;
            z-index: 5;
            width: 192px;
            height: 192px;
        }

        .qr-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            margin-top: 1rem;
            flex-wrap: wrap;
        }
        
        .btn-save-contact {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
        }
        .btn-save-contact:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(34, 197, 94, 0.6);
        }

        /* Form Sections */
        .form-section {
            margin-bottom: 1rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            margin-bottom: 1.25rem;
            padding-bottom: 0.625rem;
            border-bottom: 1px solid rgba(102, 126, 234, 0.2);
        }

        .section-header i {
            font-size: 1.25rem;
            color: white;
        }

        .section-header h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-color);
            margin: 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.25rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
            width: 100%;
        }

        .form-label {
            display: block;
            margin-bottom: 0.625rem;
            font-weight: 600;
            color: var(--text-color);
            font-size: 0.9375rem;
        }

        .form-label i {
            margin-right: 0.5rem;
            color: white;
        }

        .form-control {
            width: 100%;
            padding: 0.625rem 1rem;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 0.5rem;
            color: var(--text-color);
            transition: all 0.2s ease;
            font-size: 0.9375rem;
            font-family: inherit;
            height: 42px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 0 3px rgba(230, 119, 83, 0.08);
            color: #ffffff !important;
        }

        .form-control[readonly] {
            background: rgba(255, 255, 255, 0.03);
            border-color: var(--border-color);
            color: var(--text-secondary);
            cursor: not-allowed;
        }

        .form-control::placeholder {
            color: #64748b;
            opacity: 1;
        }

        .form-control:hover:not([readonly]):not(:focus) {
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.1);
        }

        .input-group {
            display: flex;
            align-items: center;
            width: 100%;
            min-height: 50px;
        }

        .input-group .form-control {
            flex: 1;
            min-width: 0;
            width: 100%;
        }

        .input-group-append {
            display: flex;
            align-items: center;
            padding-left: 10px;
            min-width: 100px;
            justify-content: flex-end;
        }

        /* Ensure form controls have proper sizing */
        .form-control {
            min-height: 50px;
            width: 100%;
            box-sizing: border-box;
        }

        /* Enhanced Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.625rem;
            padding: 0.875rem 1.75rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            text-decoration: none;
            font-size: 0.95rem;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(230, 119, 83, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(233, 67, 122, 0.6);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .btn-outline-danger {
            background: transparent;
            border: 2px solid var(--danger);
            color: var(--danger);
        }

        .btn-outline-danger:hover {
            background: var(--danger);
            color: white;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: var(--text-color);
        }

        .btn-secondary:hover {
            background: var(--card-hover);
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Public Toggle Switch - Modern */
        .public-toggle {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            font-size: 0.875rem;
            user-select: none;
        }

        .public-toggle-input {
            width: 3.5rem;
            height: 1.75rem;
            border-radius: 1rem;
            border: 2px solid var(--border-color);
            appearance: none;
            cursor: pointer;
            position: relative;
            background: var(--input-bg);
            transition: all 0.3s ease;
        }

        .public-toggle-input::before {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 50%;
            background: var(--text-secondary);
            transition: all 0.3s ease;
        }

        .public-toggle-input:checked {
            background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(230, 119, 83, 0.4);
        }

        .public-toggle-input:checked::before {
            left: calc(100% - 1.5rem);
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .public-toggle label {
            cursor: pointer;
            color: var(--text-color);
            font-weight: 500;
        }

        /* Color Picker Enhanced */
        .color-picker-container {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            margin-top: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .color-picker-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
        }

        .color-picker-group label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .color-picker {
            width: 60px;
            height: 60px;
            padding: 0;
            border: 3px solid var(--border-color);
            border-radius: 50%;
            cursor: pointer;
            background: none;
            transition: all 0.3s ease;
        }

        .color-picker:hover {
            border-color: var(--primary-color);
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .color-picker::-webkit-color-swatch-wrapper {
            padding: 0;
            border-radius: 50%;
        }

        .color-picker::-webkit-color-swatch {
            border: none;
            border-radius: 50%;
        }

        /* Action Buttons Container */
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 2px solid rgba(255, 255, 255, 0.1);
            flex-wrap: wrap;
        }

        .action-buttons .btn {
            min-width: 180px;
        }

        /* Social Media Grid - New Design */
        .social-media-section {
            margin-top: 2rem;
        }

        .social-media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .social-input-container {
            background: rgba(255, 255, 255, 0.02);
            border-radius: 0.75rem;
            padding: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.06);
            transition: all 0.2s ease;
        }

        .social-input-container:hover {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(102, 126, 234, 0.3);
            transform: translateY(-1px);
        }

        .social-input-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .social-input-header .form-label {
            margin: 0;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .social-input-header .form-label i {
            font-size: 1.4rem;
        }

        /* Social Media Brand Colors */
        .social-input-header .fa-instagram {
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .social-input-header .fa-facebook { color: #1877F2 !important; }
        .social-input-header .fa-youtube { color: #FF0000 !important; }
        .social-input-header .fa-whatsapp { color: #25D366 !important; }
        .social-input-header .fa-x-twitter { color: #fff !important; }
        .social-input-header .fa-linkedin { color: #0A66C2 !important; }
        .social-input-header .fa-telegram { color: #0088cc !important; }
        .social-input-header .fa-snapchat { color: #FFFC00 !important; }
        .social-input-header .fa-globe { color: #4CAF50 !important; }

        .social-input-field {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .social-input-field .form-control {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding-right: 12px;
            height: 42px;
            font-size: 0.875rem;
            color: var(--text-color);
            flex: 1;
        }

        .social-input-field .form-control:focus {
            background: rgba(0, 0, 0, 0.3);
            border-color: var(--primary-color);
            color: var(--text-color);
        }

        .social-input-field .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .btn-link {
            position: relative;
            right: auto;
            background: linear-gradient(135deg, #E9437A 0%, #E2AD2A 100%);
            border: none;
            color: white;
            padding: 10px 12px;
            border-radius: 8px;
            cursor: pointer;
            opacity: 1;
            transition: all 0.3s ease;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(233, 67, 122, 0.3);
        }

        .btn-link:hover {
            opacity: 1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(233, 67, 122, 0.5);
        }
        
        .btn-link i {
            color: white !important;
        }

        /* Public Toggle - New Design */
        .public-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .public-toggle-input {
            position: relative;
            width: 40px;
            height: 20px;
            appearance: none;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .public-toggle-input::before {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            background: white;
            transition: all 0.3s ease;
        }

        .public-toggle-input:checked {
            background: var(--primary-color);
        }

        .public-toggle-input:checked::before {
            left: 22px;
        }

        .toggle-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            user-select: none;
        }

        @media (max-width: 768px) {
            .social-media-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .social-input-container {
                padding: 14px;
            }

            .social-input-field .form-control {
                height: 52px;
                font-size: 16px;
            }

            .social-media-grid .form-control {
                min-height: 50px;
                font-size: 16px;
                padding: 12px 16px;
                width: 100%;
                min-width: 200px;
            }

            .social-media-grid .input-group-append {
                display: flex;
                align-items: center;
                margin-left: 0;
                width: 100%;
                justify-content: flex-end;
                padding-right: 10px;
            }

            .social-media-grid .public-toggle {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                width: 100%;
                padding: 5px 0;
            }

            .social-media-grid .public-toggle-input {
                width: 48px;
                height: 24px;
            }

            .social-media-grid .public-toggle-input::before {
                width: 20px;
                height: 20px;
            }

            .social-media-grid .public-toggle label {
                margin-left: 8px;
                white-space: nowrap;
                font-size: 14px;
                color: var(--text-secondary);
            }

            .social-media-grid .public-toggle-input:checked::before {
                left: calc(100% - 22px);
            }

            .action-buttons {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .action-buttons .btn {
                width: 100%;
                min-width: auto;
            }
        }

        /* Follow button container */
        #follow-btn-container {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 10;
            pointer-events: auto;
        }

        #follow-btn-container .btn {
            width: 45px;
            height: 45px;
            padding: 0;
            border-radius: 50%;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            border: none;
            font-size: 0 !important;
            overflow: hidden;
        }

        #follow-btn-container .btn i {
            margin: 0 !important;
            font-size: 18px !important;
        }

        #follow-btn-container .btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
        }

        #follow-btn-container .btn-outline-secondary {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid #E9437A;
            color: #E9437A;
        }

        #follow-btn-container .btn-outline-secondary:hover {
            background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            color: white !important;
            border-color: transparent;
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        /* Modal Styles - Fixed */
        .modal-content {
            background-color: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
        }

        .modal-body {
            max-height: 400px;
            overflow-y: auto;
        }

        .modal .close {
            color: var(--text-color);
            opacity: 1;
            font-size: 1.5rem;
            font-weight: bold;
            text-shadow: none;
        }

        .modal .close:hover {
            color: var(--primary-color);
            opacity: 1;
        }

        .modal .close:focus {
            outline: none;
        }

        /* Public View Mode Styles */
        body.public-view-mode .form-control {
            background-color: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid #444 !important;
            color: #fff !important;
            cursor: default !important;
            pointer-events: none;
        }

        body.public-view-mode .form-control[data-has-link="true"] {
            /* color: #007bff !important;
        border-color: #007bff !important; */
        }

        body.public-view-mode .public-visit-link {
            background-color: transparent !important;
            /* border: 1px solid #007bff !important; */
            /* color: #007bff !important; */
            pointer-events: auto;
        }


        .modal-backdrop {
            z-index: 1040 !important;
        }

        .modal {
            z-index: 1050 !important;
        }

        body.public-view-mode .public-visit-link:hover {
            /* background-color: #007bff !important; */
            color: #fff !important;
        }

        body.public-view-mode .input-group-append {
            pointer-events: auto;
        }

        body.public-view-mode #click_profile_img {
            cursor: default;
        }

        body.public-view-mode #click_profile_img:hover {
            transform: none;
        }



        /* Hide empty form sections */
        .form-group:empty,
        .col-md-3:has(.form-control[value=""]),
        .col-md-4:has(.form-control[value=""]) {
            display: none;
        }

        /* Utility classes */
        .hidden {
            display: none !important;
        }

        .text-center {
            text-align: center;
        }

        .mt-3 {
            margin-top: 1rem;
        }

        .mt-4 {
            margin-top: 1.5rem;
        }

        .mb-3 {
            margin-bottom: 1rem;
        }

        .mb-4 {
            margin-bottom: 1.5rem;
        }

        .d-flex {
            display: flex;
        }

        .align-items-center {
            align-items: center;
        }

        .fw-bold {
            font-weight: bold;
        }

        .text-muted {
            color: #6c757d;
        }

        .me-3 {
            margin-right: 1rem;
        }

        .border-bottom {
            border-bottom: 1px solid var(--border-color);
        }

        .rounded-circle {
            border-radius: 50%;
        }

        /* Social link icons */
        .social-link-icon {
            width: 20px;
            text-align: center;
        }

        /* Followers/Following clickable styling */
        #followers-count,
        #following-count {
            cursor: pointer;
            transition: color 0.3s ease;
        }

        #followers-count:hover,
        #following-count:hover {
            color: #5558dd !important;
            text-decoration: underline;
        }

        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .profile-container {
                max-width: 1000px;
            }
        }

        @media (max-width: 768px) {
            .profile-container {
                padding: 0 0.5rem;
            }

            .profile-card {
                padding: 1.5rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .social-media-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                justify-content: stretch;
            }

            .action-buttons .btn {
                flex: 1;
                justify-content: center;
            }

            /* Enhanced mobile input styling */
            .form-control {
                font-size: 16px;
                /* Prevents iOS zoom */
                padding: 12px 16px;
                height: 52px;
                /* Taller input on mobile */
            }

            .input-group {
                display: flex;
                flex-direction: row;
                align-items: center;
                width: 100%;
            }

            .input-group .form-control {
                flex: 1;
                min-width: 0;
                /* Prevents overflow */
            }

            .input-group-append {
                margin-left: 8px;
                white-space: nowrap;
            }

            .public-toggle {
                margin-left: 8px;
            }

            .public-toggle-input {
                width: 48px;
                /* Larger toggle switch */
                height: 24px;
            }

            .public-toggle-input::before {
                width: 20px;
                height: 20px;
            }

            .public-toggle label {
                font-size: 14px;
                padding: 0 4px;
            }

            .form-label {
                font-size: 1.1rem;
                margin-bottom: 8px;
            }
        }

        /* Profile Booster Styles */
        .profile-booster-section {
            margin-top: 2rem;
            padding: 2rem;
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1) 0%, rgba(255, 140, 0, 0.1) 100%);
            border: 2px solid #ffd700;
            border-radius: 12px;
            text-align: center;
        }

        .booster-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 1rem;
        }

        .booster-header i {
            font-size: 1.5rem;
            color: #ffd700;
        }

        .booster-header h4 {
            margin: 0;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #ffd700 0%, #ff8c00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .booster-description {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }

        .booster-benefits {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .benefit-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-color);
        }

        .benefit-item i {
            color: #10b981;
            font-size: 1.1rem;
        }

        .booster-price {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--text-color);
        }

        .price-label {
            color: var(--text-secondary);
            font-size: 1rem;
            margin-right: 10px;
        }

        .price-value {
            background: linear-gradient(135deg, #ffd700 0%, #ff8c00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-boost {
            background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            font-size: 1.2rem;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 15px rgba(230, 119, 83, 0.4);
        }

        .btn-boost:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(230, 119, 83, 0.6);
        }

        .btn-boost i {
            margin-right: 8px;
        }

        @media (max-width: 768px) {
            .profile-booster-section {
                padding: 1.5rem;
            }

            .booster-header h4 {
                font-size: 1.2rem;
            }

            .booster-price {
                font-size: 1.5rem;
            }

            .btn-boost {
                font-size: 1rem;
                padding: 0.875rem 1.5rem;
            }
        }
    </style>
</head>

<body class="dark-scheme de-grey">
    <div id="wrapper">
        <!-- header begin -->
        <?php include('../components/header.php') ?>
        <!-- header close -->

        <!-- content begin -->
        <div class="no-bottom no-top" id="content">
            <!-- Hidden fields for JavaScript -->
            <input type="hidden" id="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
            <input type="hidden" id="user_qr" value="<?php echo htmlspecialchars($user_qr); ?>">
            <input type="hidden" id="user_type" value="<?php echo htmlspecialchars($user_user_type); ?>">

            <div class="profile-container">
                <!-- Navigation Tabs -->
                <!-- <div class="nav-tabs-container">
                    <ul class="nav nav-tabs mb-4">
                        <li class="nav-item">
                            <a class="nav-link" href="/user/src/ui/index.php">
                                <i class="fas fa-home"></i> Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/user/src/ui/community.php">
                                <i class="fas fa-users"></i> Community
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/user/src/ui/wallet.php">
                                <i class="fas fa-wallet"></i> Wallet
                            </a>
                        </li>
                    </ul>
                </div> -->

                <!-- Page Title -->
                <div class="page-title d-none">
                    <h1><i class="fas fa-user-circle"></i> My Profile</h1>
                    <p>Manage your QR profile, customize your settings, and connect with others</p>
                </div>

                <div class="profile-header" style="margin-top: 40%;">
                    <!-- Left side: Profile Info -->
                    <div class="profile-card" style="position: relative;">
                        <div id="follow-btn-container" class="<?php echo $is_viewing_other_profile ? '' : 'hidden'; ?>">
                            <!-- Follow button will be injected by JavaScript -->
                        </div>
                        <div class="profile-image-container">
                            <img src="" alt="Profile" class="profile-image" id="click_profile_img">
                            <input type="file" id="upload_profile_img" hidden accept="image/*">
                        </div>

                        <script>
                            // Robust avatar fallback: generate initials SVG and set as src on error or when default image is used.
                            (function () {
                                try {
                                    var img = document.getElementById('click_profile_img');
                                    var nameEl = document.getElementById('full_name');

                                    function generateInitialsDataUrl(name, size) {
                                        name = (name || 'User').trim();
                                        if (!name || name.length === 0) {
                                            name = 'User';
                                        }
                                        var initials = name.split(/\s+/).map(function (n) {
                                            return n.charAt(0).toUpperCase();
                                        }).slice(0, 2).join('');
                                        var r = size / 2;
                                        var stroke = Math.max(2, Math.round(size * 0.06));
                                        var innerR = r - stroke / 1.5;
                                        var fontSize = Math.round(size * 0.38);
                                        var svg = "<svg xmlns='http://www.w3.org/2000/svg' width='" + size +
                                            "' height='" + size + "' viewBox='0 0 " + size + " " + size + "'>" +
                                            "<defs><linearGradient id='g' x1='0' x2='1' y1='0' y2='1'><stop offset='0' stop-color='#667eea'/><stop offset='1' stop-color='#764ba2'/></linearGradient></defs>" +
                                            "<circle cx='" + r + "' cy='" + r + "' r='" + innerR +
                                            "' fill='url(#g)' stroke='rgba(0,0,0,0.28)' stroke-width='" + stroke +
                                            "' />" +
                                            "<text x='50%' y='50%' dy='.36em' text-anchor='middle' font-family='Inter, Arial, sans-serif' font-size='" +
                                            fontSize + "' font-weight='700' fill='rgba(255,255,255,0.95)'>" + initials +
                                            "</text></svg>";
                                        return 'data:image/svg+xml;utf8,' + encodeURIComponent(svg);
                                    }

                                    if (img) {
                                        // Set initial avatar immediately since src is empty
                                        img.src = generateInitialsDataUrl('User', 160);
                                        img.setAttribute('data-generated-initials', '1');

                                        // onerror handler for missing/404 images
                                        img.onerror = function () {
                                            try {
                                                this.onerror = null;
                                                var name = nameEl ? nameEl.value : '';
                                                this.src = generateInitialsDataUrl(name || 'User', 160);
                                                this.setAttribute('data-generated-initials', '1');
                                            } catch (e) {
                                                console.error('Error generating initials avatar:', e);
                                            }
                                        };
                                    }
                                } catch (e) {
                                    console.error('Profile image initialization error:', e);
                                }
                            })();
                        </script>

                        <h2 id="user-name">Loading</h2>
                        <p id="user-qr-id" style="text-align: center; color: var(--text-secondary); margin-top: -20px; margin-bottom: 20px; font-weight: 500; letter-spacing: 0.5px; width: 100%;">@Loading</p>

                        <div class="stats-grid" style="margin-top: -5%;">
                            <div class="stat-item" id="followers-count">
                                <span class="stat-value">0</span>
                                <span class="stat-label">followers</span>
                            </div>
                            <div class="stat-item" id="following-count">
                                <span class="stat-value">0</span>
                                <span class="stat-label">following</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right side: QR Code -->
                    <div class="profile-card" style="margin-top: -22%;">
                        <div class="qr-section">
                            <div class="qr-container" id="qr-frame-container">
                                <img src="../assets/images/frame.png" class="qr-frame-overlay" alt="Frame">
                            </div>

                            <div class="qr-actions" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <button class="btn btn-secondary" id="download-qr-btn">
                                    <i class="fas fa-download"></i> Download QR
                                </button>
                                <button class="btn btn-secondary" id="share-profile-btn">
                                    <i class="fas fa-share-alt"></i> Share Profile
                                </button>
                            </div>
                            <?php if (!$viewing_qr): ?>
                            <button class="btn btn-outline-primary mt-3" id="choose-frame-btn" style="width: 100%;">
                                <i class="fas fa-image"></i> Choose Frame
                            </button>
                            <button class="btn btn-secondary mt-3" id="subscription-btn" style="width: 100%; display: none;">
                                <i class="fas fa-credit-card"></i> <span id="subscription-btn-text">Subscription Status</span>
                            </button>
                            <?php else: ?>
                            <a href="../backend/profile_new/generate_vcard.php?qr=<?php echo htmlspecialchars($viewed_qr); ?>" class="btn btn-primary mt-3" style="width: 100%; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                <i class="fas fa-address-book"></i> Save to Contacts
                            </a>
                            <?php endif; ?>

                            <div id="qr-color-controls" class="hidden">
                                <div class="color-picker-container">
                                    <div class="color-picker-group">
                                        <label>Foreground:</label>
                                        <input type="color" id="qr-color-dark" class="color-picker" value="#000000">
                                    </div>
                                    <div class="color-picker-group">
                                        <label>Background:</label>
                                        <input type="color" id="qr-color-light" class="color-picker" value="#FFFFFF">
                                    </div>
                                </div>
                                <button class="btn btn-primary mt-3" id="save-qr-color">
                                    <i class="fas fa-save"></i> Save QR Colors
                                </button>
                        </div>
                    </div>
                </div>

                <!-- Profile Information -->
                <div class="profile-card">
                    <form id="form-create-item">
                        <!-- Basic Information Section -->
                        <div class="form-section">
                            <div class="section-header">
                                <i class="fas fa-user-edit"></i>
                                <h3>Basic Information</h3>
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="full_name">
                                        <i class="fas fa-user"></i> Full Name
                                    </label>
                                    <input type="text" id="full_name" name="full_name" class="form-control"
                                        placeholder="Enter your full name" value="">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="phone_number">
                                        <i class="fas fa-phone"></i> Phone Number
                                    </label>
                                    <input type="tel" id="phone_number" name="phone_number" class="form-control"
                                        placeholder="+1 (555) 123-4567" value="">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="email_address">
                                        <i class="fas fa-envelope"></i> Email Address
                                    </label>
                                    <input type="email" id="email_address" name="email_address" class="form-control"
                                        placeholder="your.email@example.com">
                                </div>

                                <div class="form-group address-header-group">
                                    <div class="social-input-header">
                                        <label class="form-label">
                                            <i class="fas fa-map-marker-alt"></i> Address Information
                                        </label>
                                        <div class="public-toggle">
                                            <input type="checkbox" id="public_address" class="public-toggle-input"
                                                data-field="address" checked>
                                            <span class="toggle-label">Public</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group address-field-group">
                                    <label class="form-label" for="address">
                                        <i class="fas fa-map-marker-alt"></i> Address
                                    </label>
                                    <input type="text" id="address" name="address" class="form-control"
                                        placeholder="123 Main St, City, Country" value="">
                                </div>

                                <div class="form-group address-field-group">
                                    <label class="form-label" for="landmark">
                                        <i class="fas fa-map-pin"></i> Landmark
                                    </label>
                                    <input type="text" id="landmark" name="landmark" class="form-control"
                                        placeholder="Near City Mall" value="">
                                </div>

                                <div class="form-group address-field-group">
                                    <label class="form-label" for="pincode">
                                        <i class="fas fa-map-marked-alt"></i> Pincode
                                    </label>
                                    <input type="text" id="pincode" name="pincode" class="form-control"
                                        placeholder="123456" value="" maxlength="6">
                                </div>
                            </div>
                        </div>

                        <!-- Social Media Links -->
                        <div class="form-section social-media-section" id="social-media-section">
                            <div class="section-header">
                                <i class="fas fa-share-alt"></i>
                                <h3>Social Media Links</h3>
                            </div>
                            <div class="social-media-grid">

                                <!-- Priority 2: Instagram -->
                                <div class="form-group social-input-container">
                                    <div class="social-input-header">
                                        <label class="form-label" for="instagram_username">
                                            <i class="fab fa-instagram"></i> Instagram
                                        </label>
                                        <div class="public-toggle">
                                            <input type="checkbox" id="public_instagram_username"
                                                class="public-toggle-input" data-field="instagram_username" checked>
                                            <span class="toggle-label">Public</span>
                                        </div>
                                    </div>
                                    <div class="social-input-field">
                                        <input type="text" id="instagram_username" name="instagram_username"
                                            class="form-control" placeholder="Enter your Instagram username">
                                        <button type="button" class="btn-link">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                    </div>
                                </div>
                                <!-- Priority 1: Facebook -->
                                <div class="form-group social-input-container">
                                    <div class="social-input-header">
                                        <label class="form-label" for="facebook_username">
                                            <i class="fab fa-facebook"></i> Facebook
                                        </label>
                                        <div class="public-toggle">
                                            <input type="checkbox" id="public_facebook_username"
                                                class="public-toggle-input" data-field="facebook_username" checked>
                                            <span class="toggle-label">Public</span>
                                        </div>
                                    </div>
                                    <div class="social-input-field">
                                        <input type="text" id="facebook_username" name="facebook_username"
                                            class="form-control" placeholder="Enter your Facebook username">
                                        <button type="button" class="btn-link">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                    </div>
                                </div>



                                <!-- Priority 3: YouTube -->
                                <div class="form-group social-input-container">
                                    <div class="social-input-header">
                                        <label class="form-label" for="youtube_username">
                                            <i class="fab fa-youtube"></i> YouTube
                                        </label>
                                        <div class="public-toggle">
                                            <input type="checkbox" id="public_youtube_username"
                                                class="public-toggle-input" data-field="youtube_username" checked>
                                            <span class="toggle-label">Public</span>
                                        </div>
                                    </div>
                                    <div class="social-input-field">
                                        <input type="text" id="youtube_username" name="youtube_username"
                                            class="form-control" placeholder="Enter your YouTube channel">
                                        <button type="button" class="btn-link">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- WhatsApp -->
                                <div class="form-group social-input-container">
                                    <div class="social-input-header">
                                        <label class="form-label" for="whatsapp_link">
                                            <i class="fab fa-whatsapp"></i> WhatsApp
                                        </label>
                                        <div class="public-toggle">
                                            <input type="checkbox" id="public_whatsapp_link" class="public-toggle-input"
                                                data-field="whatsapp_link" checked>
                                            <span class="toggle-label">Public</span>
                                        </div>
                                    </div>
                                    <div class="social-input-field">
                                        <input type="text" id="whatsapp_link" name="whatsapp_link" class="form-control"
                                            placeholder="Enter your WhatsApp number">
                                        <button type="button" class="btn-link">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Twitter -->
                                <div class="form-group social-input-container">
                                    <div class="social-input-header">
                                        <label class="form-label" for="twitter_username">
                                            <i class="fab fa-x-twitter"></i> X
                                        </label>
                                        <div class="public-toggle">
                                            <input type="checkbox" id="public_twitter_username"
                                                class="public-toggle-input" data-field="twitter_username">
                                            <span class="toggle-label">Public</span>
                                        </div>
                                    </div>
                                    <div class="social-input-field">
                                        <input type="text" id="twitter_username" name="twitter_username"
                                            class="form-control" placeholder="Enter your X username">
                                        <button type="button" class="btn-link">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- LinkedIn -->
                                <div class="form-group social-input-container">
                                    <div class="social-input-header">
                                        <label class="form-label" for="linkedin_username">
                                            <i class="fab fa-linkedin"></i> LinkedIn
                                        </label>
                                        <div class="public-toggle">
                                            <input type="checkbox" id="public_linkedin_username"
                                                class="public-toggle-input" data-field="linkedin_username" checked>
                                            <span class="toggle-label">Public</span>
                                        </div>
                                    </div>
                                    <div class="social-input-field">
                                        <input type="text" id="linkedin_username" name="linkedin_username"
                                            class="form-control" placeholder="Enter your LinkedIn profile URL">
                                        <button type="button" class="btn-link">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Telegram -->
                                <div class="form-group social-input-container">
                                    <div class="social-input-header">
                                        <label class="form-label" for="telegram_link">
                                            <i class="fab fa-telegram"></i> Telegram
                                        </label>
                                        <div class="public-toggle">
                                            <input type="checkbox" id="public_telegram_link" class="public-toggle-input"
                                                data-field="telegram_link" checked>
                                            <span class="toggle-label">Public</span>
                                        </div>
                                    </div>
                                    <div class="social-input-field">
                                        <input type="text" id="telegram_link" name="telegram_link" class="form-control"
                                            placeholder="Enter your Telegram username">
                                        <button type="button" class="btn-link">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Snapchat -->
                                <div class="form-group social-input-container">
                                    <div class="social-input-header">
                                        <label class="form-label" for="snapchat_username">
                                            <i class="fab fa-snapchat"></i> Snapchat
                                        </label>
                                        <div class="public-toggle">
                                            <input type="checkbox" id="public_snapchat_username"
                                                class="public-toggle-input" data-field="snapchat_username" checked>
                                            <span class="toggle-label">Public</span>
                                        </div>
                                    </div>
                                    <div class="social-input-field">
                                        <input type="text" id="snapchat_username" name="snapchat_username"
                                            class="form-control" placeholder="Enter your Snapchat username">
                                        <button type="button" class="btn-link">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Website -->
                                <div class="form-group social-input-container">
                                    <div class="social-input-header">
                                        <label class="form-label" for="website">
                                            <i class="fas fa-globe"></i> Website
                                        </label>
                                        <div class="public-toggle">
                                            <input type="checkbox" id="public_website" class="public-toggle-input"
                                                data-field="website">
                                            <span class="toggle-label">Public</span>
                                        </div>
                                    </div>
                                    <div class="social-input-field">
                                        <input type="text" id="website" name="website" class="form-control"
                                            placeholder="Enter your website URL">
                                        <button type="button" class="btn-link">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Booster Section (for Gold/Silver users) -->
                        <div id="profile-booster-section" class="profile-booster-section">
                            <div class="booster-header">
                                <i class="fas fa-rocket"></i>
                                <h4>Super Charge Your Profile</h4>
                            </div>
                            <div class="d-none">
                                <p class="booster-description">
                                    Boost your profile to appear at the top of search results and get more visibility!
                                </p>
                                <div class="booster-benefits">
                                    <div class="benefit-item">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Top of search results for 7 days</span>
                                    </div>
                                    <div class="benefit-item">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Highlighted profile badge</span>
                                    </div>
                                    <div class="benefit-item">
                                        <i class="fas fa-check-circle"></i>
                                        <span>3x more profile views</span>
                                    </div>
                                </div>
                                <div class="booster-price">
                                    <span class="price-label">Boost Cost:</span>
                                    <span class="price-value">₹199</span>
                                </div>
                            </div>

                            <!-- Existing Links List -->
                            <div id="supercharge-links-list" class="d-none" style="margin-bottom: 1rem;">
                                <h5 style="font-size: 1rem; margin-bottom: 0.75rem; color: var(--text-color);">
                                    <i class="fas fa-list"></i> Your Supercharge Links
                                </h5>
                                <div id="links-container"></div>
                            </div>

                            <!-- Add New Link Form -->
                            <div id="supercharge-form" class="d-none">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-link"></i> Add New Supercharge Link
                                    </label>
                                    <input type="url" class="form-control" id="supercharge_link"
                                        placeholder="Enter your link to supercharge your profile">
                                    <small class="form-text text-muted">Provide a link that showcases your work,
                                        portfolio, or content</small>
                                </div>
                            </div>
                            <button class="btn btn-boost" id="btn-super-charge">
                                <i class="fas fa-bolt"></i> Super Charge Now
                            </button>
                        </div>

                        <?php if (!$viewing_qr): ?>
                            <div class="action-buttons">
                                <button type="submit" class="btn btn-primary" id="update-profile-btn">
                                    <i class="fas fa-save"></i> Save
                                </button>
                                <a href="../backend/logout.php" class="btn btn-danger">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
                <div id="toast" class="toast"></div>
                
                <!-- Subscription Modal -->
                <?php if (!$viewing_qr): ?>
                <div class="modal fade" id="subscriptionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="false" data-bs-keyboard="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px;">
                            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.1); padding: 1.25rem;">
                                <h5 class="modal-title" style="color: var(--text-color); font-weight: 600;">
                                    <i class="fas fa-credit-card" style="color: var(--primary-color); margin-right: 8px;"></i>
                                    Subscription Status
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="subscription-modal-content" style="padding: 1.5rem;">
                                <div class="text-center py-4">
                                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i>
                                    <p style="margin-top: 1rem; color: var(--text-secondary);">Loading subscription info...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div>
        <!-- content close -->

        <a href="#" id="back-to-top"></a>

        <!-- footer begin -->
        <?php include('../components/footer.php'); ?>
        <!-- footer close -->

    </div>
    <!-- wrapper close -->

    <?php include('../components/jslinks.php'); ?>
    <script src="../components/qr/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
    <script src="./custom_js/custom_profile_new.js"></script>
    <script>
        // Function to handle social media redirects
        function handleSocialMediaRedirect(inputId, btnElement) {
            const input = document.getElementById(inputId);
            const value = input.value.trim();

            if (!value) return;

            let url = '';
            switch (inputId) {
                case 'website':
                    url = value.startsWith('http') ? value : 'https://' + value;
                    break;
                case 'facebook_username':
                    const fbHandle = value.replace('@', '');
                    url = `https://www.facebook.com/${fbHandle}`;
                    break;
                case 'whatsapp_link':
                    const whatsappNumber = value.replace(/[^0-9]/g, '');
                    url = `https://wa.me/${whatsappNumber}`;
                    break;
                case 'telegram_link':
                    const username = value.replace('@', '');
                    url = `https://t.me/${username}`;
                    break;
                case 'twitter_username':
                    const twitterHandle = value.replace('@', '');
                    url = `https://twitter.com/${twitterHandle}`;
                    break;
                case 'instagram_username':
                    const instaHandle = value.replace('@', '');
                    url = `https://instagram.com/${instaHandle}`;
                    break;
                case 'youtube_username':
                    if (value.includes('youtube.com') || value.includes('youtu.be')) {
                        url = value;
                    } else {
                        const ytHandle = value.replace('@', '');
                        url = `https://youtube.com/@${ytHandle}`;
                    }
                    break;
                case 'linkedin_username':
                    url = value.includes('linkedin.com') ? value : `https://linkedin.com/in/${value}`;
                    break;
                case 'snapchat_username':
                    const snapHandle = value.replace('@', '');
                    url = `https://snapchat.com/add/${snapHandle}`;
                    break;
            }

            if (url) {
                window.open(url, '_blank');
            }
        }

        // Add click handlers to all redirect buttons
        document.addEventListener('DOMContentLoaded', function () {
            const socialInputs = [
                'website', 'facebook_username', 'whatsapp_link', 'telegram_link', 'twitter_username',
                'instagram_username', 'youtube_username', 'linkedin_username', 'snapchat_username'
            ];

            socialInputs.forEach(inputId => {
                const container = document.getElementById(inputId).closest('.social-input-field');
                const redirectBtn = container.querySelector('.btn-link');
                redirectBtn.addEventListener('click', () => handleSocialMediaRedirect(inputId,
                    redirectBtn));
            });

            // Profile Booster functionality
            checkAndShowBooster();

            // Load followers count on page load
            loadFollowersCount();

            // Initialize follow button if viewing another profile
            if (isViewingOtherProfile) {
                checkFollowStatus();
            }
        });

        // Check if user is Gold or Silver and show booster
        function checkAndShowBooster() {
            const userId = '<?php echo $user_id; ?>';
            const viewingQr = <?php echo $viewing_qr ? 'true' : 'false'; ?>;
            const isOwnProfile = <?php echo (!$viewing_qr && isset($_SESSION['user_id'])) ? 'true' : 'false'; ?>;

            // Only show supercharge section if user is viewing their OWN profile (not a public view)
            if (!isOwnProfile || viewingQr || !userId) {
                console.log('Hiding supercharge section - not own profile or public view');
                document.getElementById('profile-booster-section').style.display = 'none';
                return;
            }

            // Check for test mode
            const urlParams = new URLSearchParams(window.location.search);
            const testRenewal = urlParams.get('test_renewal');
            
            $.ajax({
                url: '../backend/profile_new/get_profile_data.php',
                type: 'POST',
                data: {
                    user_id: userId,
                    test_renewal: testRenewal || ''
                },
                dataType: 'json',
                success: function (data) {
                    console.log('User data for booster:', data);
                    if (data.user) {
                        const userSlabId = parseInt(data.user.user_slab_id);
                        const userTag = data.user.user_tag ? data.user.user_tag.toLowerCase() : '';
                        console.log('User slab ID:', userSlabId, 'User tag:', userTag);
                        // Show booster for Gold (3) users only
                        if (userSlabId === 3 || userTag === 'gold') {
                            console.log('Showing supercharge section for Gold user');
                            document.getElementById('profile-booster-section').style.display = 'block';
                        } else {
                            console.log('User not eligible for supercharge. Slab ID:', userSlabId, 'Tag:', userTag);
                            document.getElementById('profile-booster-section').style.display = 'none';
                        }
                        
                        // Render subscription status
                        if (data.subscription) {
                            renderSubscriptionStatus(data.subscription);
                        }
                    } else {
                        console.log('No user data returned');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error checking slab:', error, xhr.responseText);
                }
            });
        }

        // Handle Super Charge button
        const boostBtn = document.getElementById('btn-super-charge');
        let superchargeFormVisible = false;

        if (boostBtn) {
            console.log('Boost button found, attaching click handler');

            // Check existing supercharge status on load
            checkSuperchargeStatus();

            boostBtn.addEventListener('click', function () {
                console.log('Super Charge button clicked');

                // Toggle form visibility
                const form = document.getElementById('supercharge-form');
                if (!superchargeFormVisible) {
                    form.classList.remove('d-none');
                    superchargeFormVisible = true;
                    boostBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Supercharge Request';
                } else {
                    // Submit the form
                    submitSuperchargeRequest();
                }
            });
        }

        function checkSuperchargeStatus() {
            $.ajax({
                url: '../backend/profile_new/get_supercharge_status.php',
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    if (response.success && response.has_request && response.requests.length > 0) {
                        const requests = response.requests;
                        const linksList = $('#supercharge-links-list');
                        const linksContainer = $('#links-container');

                        // Show links list
                        linksList.removeClass('d-none');
                        linksContainer.empty();

                        // Display each link with its status
                        requests.forEach(function (request) {
                            let statusBadge = '';
                            let statusClass = '';

                            if (request.status === 'pending') {
                                statusBadge = '<span class="badge" style="background: #fbbf24; color: #000;"><i class="fas fa-clock"></i> Pending</span>';
                                statusClass = 'border-warning';
                            } else if (request.status === 'approved') {
                                statusBadge = '<span class="badge" style="background: #10b981; color: #fff;"><i class="fas fa-check-circle"></i> Approved</span>';
                                statusClass = 'border-success';
                            } else if (request.status === 'rejected') {
                                statusBadge = '<span class="badge" style="background: #ef4444; color: #fff;"><i class="fas fa-times-circle"></i> Rejected</span>';
                                statusClass = 'border-danger';
                            }

                            let linkHtml = `
                                <div class="supercharge-link-item ${statusClass}" style="padding: 0.75rem; margin-bottom: 0.75rem; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; background: rgba(255,255,255,0.03);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                        <div style="flex: 1; overflow: hidden;">
                                            <a href="${request.supercharge_link}" target="_blank" style="color: #e67753; text-decoration: none; word-break: break-all;">
                                                <i class="fas fa-external-link-alt"></i> ${request.supercharge_link}
                                            </a>
                                        </div>
                                        <div style="margin-left: 0.75rem;">
                                            ${statusBadge}
                                        </div>
                                    </div>
                                    ${request.status === 'rejected' && request.admin_notes ? `<div style="font-size: 0.85rem; color: #ef4444; margin-top: 0.5rem;"><strong>Reason:</strong> ${request.admin_notes}</div>` : ''}
                                    <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.5rem;">
                                        Submitted: ${new Date(request.created_at).toLocaleDateString()}
                                    </div>
                                </div>
                            `;

                            linksContainer.append(linkHtml);
                        });
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error checking supercharge status:', error);
                }
            });
        }

        function submitSuperchargeRequest() {
            const link = $('#supercharge_link').val().trim();

            if (!link) {
                alert('Please enter a valid link');
                return;
            }

            // Validate URL format
            try {
                new URL(link);
            } catch (e) {
                alert('Please enter a valid URL (must start with http:// or https://)');
                return;
            }

            $.ajax({
                url: '../backend/profile_new/submit_supercharge.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    supercharge_link: link
                }),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        alert(response.message);
                        $('#supercharge_link').val(''); // Clear the input field
                        checkSuperchargeStatus(); // Refresh status
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error submitting supercharge:', error);
                    alert('Failed to submit supercharge request. Please try again.');
                }
            });
        }

        // Render subscription status
        let subscriptionData = null;
        
        function renderSubscriptionStatus(sub) {
            subscriptionData = sub;
            
            // Show the subscription button
            const subBtn = document.getElementById('subscription-btn');
            if (subBtn) {
                subBtn.style.display = 'block';
                
                // Update button text based on status
                const btnText = document.getElementById('subscription-btn-text');
                if (btnText) {
                    if (sub.is_expired) {
                        btnText.textContent = '⚠️ Subscription Expired';
                        subBtn.classList.add('btn-danger');
                        subBtn.classList.remove('btn-secondary');
                    } else if (sub.needs_renewal) {
                        btnText.textContent = '⏰ ' + sub.days_remaining + ' days left';
                        subBtn.classList.add('btn-warning');
                        subBtn.classList.remove('btn-secondary');
                    } else {
                        btnText.textContent = 'Subscription Status';
                    }
                }
                
                // Bind click to open modal
                subBtn.onclick = function() {
                    showSubscriptionModal();
                };
            }
        }
        
        function showSubscriptionModal() {
            const content = document.getElementById('subscription-modal-content');
            if (!content || !subscriptionData) return;
            
            const sub = subscriptionData;
            const expiryDate = new Date(sub.expires_on);
            const formattedExpiry = expiryDate.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
            const tier = (sub.tier || 'normal').charAt(0).toUpperCase() + (sub.tier || 'normal').slice(1);
            
            let statusClass = 'status-ok';
            let statusIcon = '✅';
            let statusText = sub.days_remaining + ' days remaining';
            
            if (sub.is_expired) {
                statusClass = 'status-expired';
                statusIcon = '❌';
                statusText = 'Expired';
            } else if (sub.is_in_grace) {
                statusClass = 'status-grace';
                statusIcon = '⚠️';
                statusText = 'Grace period (' + Math.abs(sub.days_remaining) + ' days overdue)';
            } else if (sub.days_remaining <= 30) {
                statusClass = 'status-warning';
                statusIcon = '⏰';
                statusText = sub.days_remaining + ' days remaining';
            }
            
            let html = `
                <div class="subscription-info ${statusClass}">
                    <div class="sub-row">
                        <span class="sub-label">Membership:</span>
                        <span class="sub-value sub-tier tier-${sub.tier}">${tier}</span>
                    </div>
                    <div class="sub-row">
                        <span class="sub-label">Expires:</span>
                        <span class="sub-value">${formattedExpiry}</span>
                    </div>
            `;
            
            if (sub.needs_renewal) {
                html += `
                    <div class="sub-action">
                        <button type="button" id="renew-btn" class="btn btn-primary btn-renew">
                            <i class="fas fa-sync-alt"></i> Renew Now - ₹${sub.renewal_price}
                        </button>
                    </div>
                `;
            }
            
            html += '</div>';
            content.innerHTML = html;
            
            // Bind click event after HTML is inserted
            const renewBtn = document.getElementById('renew-btn');
            if (renewBtn) {
                renewBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    startRenewal();
                });
            }
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('subscriptionModal'));
            modal.show();
        }
        
        // Start renewal payment
        function startRenewal() {
            const renewBtn = document.querySelector('.btn-renew');
            if (renewBtn) {
                renewBtn.disabled = true;
                renewBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }
            
            fetch('../backend/payment/renew_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status && data.session) {
                    // Redirect to renewal payment page
                    const session = encodeURIComponent(data.session);
                    const orderId = encodeURIComponent(data.order_id);
                    window.location.href = '../backend/payment/intent_renewal.php?session=' + session + '&orderId=' + orderId;
                } else {
                    alert('Error: ' + (data.error || 'Failed to create payment order'));
                    if (renewBtn) {
                        renewBtn.disabled = false;
                        renewBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Renew Now';
                    }
                }
            })
            .catch(err => {
                console.error('Renewal error:', err);
                alert('Error starting renewal. Please try again.');
                if (renewBtn) {
                    renewBtn.disabled = false;
                    renewBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Renew Now';
                }
            });
        }

        function boostProfile() {
            fetch('../backend/boost_profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    boost_duration: 7
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        alert('✨ Success! Your profile has been Super Charged!\n\n' + data.message);
                        // Reload page to reflect changes
                        location.reload();
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error boosting profile:', error);
                    alert('An error occurred while boosting your profile. Please try again.');
                });
        }

        // Follow Button Functionality
        const isViewingOtherProfile = <?php echo $is_viewing_other_profile ? 'true' : 'false'; ?>;
        const viewedQr = '<?php echo $viewed_qr; ?>';
        const currentUserQr = '<?php echo $user_qr; ?>';

        function loadFollowersCount() {
            const targetQr = viewedQr || currentUserQr;
            if (!targetQr) return;

            $.ajax({
                url: '../backend/get_followers_count.php',
                method: 'POST',
                data: JSON.stringify({
                    qr_id: targetQr,
                    follower_id: '<?php echo $user_id; ?>'
                }),
                contentType: 'application/json',
                success: function (response) {
                    updateFollowersCount(response.total_count);
                },
                error: function () {
                    console.error('Failed to load followers count');
                }
            });
        }

        function checkFollowStatus() {
            // Get the user_id for the viewed profile
            $.ajax({
                url: '../backend/get_user_id.php',
                method: 'POST',
                data: JSON.stringify({ qr_id: viewedQr }),
                contentType: 'application/json',
                success: function (userResponse) {
                    if (userResponse.status && userResponse.user_id) {
                        const targetUserId = userResponse.user_id;

                        // Now check follow status
                        $.ajax({
                            url: '../backend/get_followers_count.php',
                            method: 'POST',
                            data: JSON.stringify({
                                qr_id: viewedQr,
                                follower_id: '<?php echo $user_id; ?>'
                            }),
                            contentType: 'application/json',
                            success: function (response) {
                                renderFollowButton(response.following, targetUserId);
                                updateFollowersCount(response.total_count);
                            },
                            error: function () {
                                console.error('Failed to check follow status');
                            }
                        });
                    }
                },
                error: function () {
                    console.error('Failed to get user ID');
                }
            });
        }

        function renderFollowButton(isFollowing, targetUserId) {
            const btnClass = isFollowing
                ? 'btn btn-outline-secondary'
                : 'btn';
            const btnStyle = isFollowing
                ? ''
                : 'background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%); color: white;';
            const iconClass = isFollowing ? 'fas fa-user-check' : 'fas fa-user-plus';
            const title = isFollowing ? 'Following' : 'Follow';

            const button = $('<button>')
                .addClass(btnClass)
                .attr('style', btnStyle)
                .attr('onclick', `toggleFollow(${targetUserId})`)
                .attr('title', title)
                .html(`<i class="${iconClass}"></i>`);

            $('#follow-btn-container').html(button);
        }

        function toggleFollow(targetUserId) {
            $.ajax({
                url: '../backend/profile/toggle_follow.php',
                method: 'POST',
                data: JSON.stringify({ target_user_id: targetUserId }),
                contentType: 'application/json',
                success: function (response) {
                    if (response.success) {
                        const isFollowing = response.data.following;
                        renderFollowButton(isFollowing, targetUserId);

                        // Update followers count
                        checkFollowStatus();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function () {
                    alert('Failed to update follow status. Please try again.');
                }
            });
        }

        function updateFollowersCount(count) {
            // Update the followers count display
            $('#followers-count .stat-value').text(count);
        }

        function showNotification(message) {
            // Simple notification - you can enhance this with a toast library
            const notification = $('<div>')
                .css({
                    'position': 'fixed',
                    'top': '20px',
                    'right': '20px',
                    'background': 'linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%)',
                    'color': 'white',
                    'padding': '15px 25px',
                    'border-radius': '8px',
                    'box-shadow': '0 4px 6px rgba(0,0,0,0.1)',
                    'z-index': '9999',
                    'animation': 'slideIn 0.3s ease-out'
                })
                .text(message);

            $('body').append(notification);

            setTimeout(function () {
                notification.fadeOut(300, function () {
                    $(this).remove();
                });
            }, 3000);
        }

        // Share Profile Button
        document.getElementById('share-profile-btn')?.addEventListener('click', function () {
            const userQrId = '<?php echo $viewing_qr ? $viewed_qr : $user_qr; ?>';
            const profileUrl = window.location.origin + window.location.pathname + '?QR=' + userQrId;

            // Try using the Web Share API first (mobile devices)
            if (navigator.share) {
                navigator.share({
                    title: 'My ZQR Profile',
                    text: 'Check out my profile on ZQR Connect!',
                    url: profileUrl
                }).catch(err => {
                    // If share fails, copy to clipboard
                    copyToClipboard(profileUrl);
                });
            } else {
                // Fallback: copy to clipboard
                copyToClipboard(profileUrl);
            }
        });

        function copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    alert('✅ Profile link copied to clipboard!\n\n' + text);
                }).catch(() => {
                    fallbackCopyToClipboard(text);
                });
            } else {
                fallbackCopyToClipboard(text);
            }
        }

        function fallbackCopyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                alert('✅ Profile link copied to clipboard!\n\n' + text);
            } catch (err) {
                prompt('Copy this link manually:', text);
            }
            document.body.removeChild(textArea);
        }
    </script>

    <!-- Frame Selector Modal -->
    <div class="modal fade" id="frameModal" tabindex="-1" aria-labelledby="frameModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="background: var(--card-bg); border: 1px solid var(--border-color);">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
                    <h5 class="modal-title" id="frameModalLabel" style="color: var(--text-color);">
                        <i class="fas fa-image"></i> Choose QR Frame
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="frame-grid" class="frame-grid">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--primary-color);"></i>
                            <p class="mt-2" style="color: var(--text-secondary);">Loading frames...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Frame Selector Modal Styles */
        .frame-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
            padding: 0.5rem;
        }
        
        .frame-item {
            aspect-ratio: 1;
            border: 3px solid transparent;
            border-radius: 12px;
            cursor: pointer;
            overflow: hidden;
            transition: all 0.2s ease;
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .frame-item:hover {
            border-color: var(--primary-color);
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(230, 119, 83, 0.3);
        }
        
        .frame-item.selected {
            border-color: var(--success);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3);
        }
        
        .frame-item.selected::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 8px;
            right: 8px;
            background: var(--success);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        
        .frame-item img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .frame-item .no-frame-icon {
            font-size: 2rem;
            color: var(--text-secondary);
        }
        
        .frame-item .frame-label {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            font-size: 0.75rem;
            padding: 4px;
            text-align: center;
        }

        .btn-outline-primary {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
        }
    </style>

    <script>
        // Frame Selector Logic
        document.addEventListener('DOMContentLoaded', function() {
            const chooseFrameBtn = document.getElementById('choose-frame-btn');
            const frameGrid = document.getElementById('frame-grid');
            const frameOverlay = document.querySelector('.qr-frame-overlay');
            let currentFrame = 'default';
            let frameModal;

            // Initialize modal
            if (typeof bootstrap !== 'undefined') {
                frameModal = new bootstrap.Modal(document.getElementById('frameModal'));
            }

            // Load user's current frame
            function loadCurrentFrame() {
                const userId = document.getElementById('user_id').value;
                const viewedQr = '<?php echo $viewed_qr; ?>';
                
                let data = {};
                if (viewedQr) {
                    data.qr_id = viewedQr;
                } else if (userId) {
                    data.user_id = userId;
                } else {
                    return;
                }

                $.ajax({
                    url: '../backend/profile_new/get_frame.php',
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status) {
                            currentFrame = response.frame;
                            if (frameOverlay) {
                                if (response.frameUrl === null) {
                                    frameOverlay.style.display = 'none';
                                } else {
                                    // Fix relative paths for frames
                                    let frameUrl = response.frameUrl;
                                    if (frameUrl && !frameUrl.startsWith('http') && !frameUrl.startsWith('data:')) {
                                        // Ensure path starts correctly relative to current page location
                                        // If frameUrl starts with /user/src, it is absolute from web root
                                        // We can use it directly if server allows, or prepend .. if needed
                                        if (frameUrl.startsWith('/user/src')) {
                                             frameUrl = '..' + frameUrl.substring('/user/src'.length);
                                        }
                                    }
                                    frameOverlay.src = frameUrl || response.frameUrl;
                                    frameOverlay.style.display = 'block';
                                }
                            }
                        }
                    }
                });
            }

            // Load available frames
            function loadFrames() {
                $.ajax({
                    url: '../backend/profile_new/get_frames.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status && response.data) {
                            renderFrameGrid(response.data);
                        } else {
                            frameGrid.innerHTML = '<p class="text-center" style="color: var(--danger);">Failed to load frames</p>';
                        }
                    },
                    error: function() {
                        frameGrid.innerHTML = '<p class="text-center" style="color: var(--danger);">Error loading frames</p>';
                    }
                });
            }

            // Render frame grid
            function renderFrameGrid(frames) {
                frameGrid.innerHTML = '';
                
                frames.forEach(function(frame) {
                    const div = document.createElement('div');
                    div.className = 'frame-item' + (frame.id === currentFrame ? ' selected' : '');
                    div.dataset.frameId = frame.id;
                    div.dataset.frameUrl = frame.url || '';

                    if (frame.id === 'none') {
                        div.innerHTML = '<i class="fas fa-ban no-frame-icon"></i><span class="frame-label">No Frame</span>';
                    } else if (frame.thumbnail) {
                        div.innerHTML = '<img src="' + frame.thumbnail + '" alt="' + frame.name + '"><span class="frame-label">' + frame.name + '</span>';
                    } else {
                        div.innerHTML = '<i class="fas fa-image no-frame-icon"></i><span class="frame-label">' + frame.name + '</span>';
                    }

                    div.addEventListener('click', function() {
                        selectFrame(frame.id, frame.url);
                    });

                    frameGrid.appendChild(div);
                });
            }

            // Select frame
            function selectFrame(frameId, frameUrl) {
                // Update UI immediately
                document.querySelectorAll('.frame-item').forEach(el => el.classList.remove('selected'));
                document.querySelector('[data-frame-id="' + frameId + '"]')?.classList.add('selected');

                // Update frame overlay
                if (frameOverlay) {
                    if (frameId === 'none' || !frameUrl) {
                        frameOverlay.style.display = 'none';
                    } else {
                        frameOverlay.src = frameUrl;
                        frameOverlay.style.display = 'block';
                    }
                }

                // Save to server
                $.ajax({
                    url: '../backend/profile_new/save_frame.php',
                    type: 'POST',
                    data: JSON.stringify({ frame: frameId }),
                    contentType: 'application/json',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status) {
                            currentFrame = frameId;
                            if (frameModal) frameModal.hide();
                            showToast('Frame updated successfully!', 'success');
                        } else {
                            showToast('Failed to save frame: ' + response.message, 'error');
                        }
                    },
                    error: function() {
                        showToast('Error saving frame', 'error');
                    }
                });
            }

            // Open modal
            if (chooseFrameBtn) {
                chooseFrameBtn.addEventListener('click', function() {
                    loadFrames();
                    if (frameModal) {
                        frameModal.show();
                    } else {
                        $('#frameModal').modal('show');
                    }
                });
            }

            // Load current frame on page load
            loadCurrentFrame();
        });
    </script>
</body>

</html>