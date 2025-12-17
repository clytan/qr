<?php
/**
 * Admin Authentication Check
 * Include this at the top of every protected admin page
 * Uses admin_id session key (separate from user_id to avoid conflicts)
 */

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get current page name
$currentPage = basename($_SERVER['PHP_SELF']);

// Get allowed URLs for this admin
$allowedUrls = isset($_SESSION['admin_allowed_urls']) ? $_SESSION['admin_allowed_urls'] : [];

// Check if current page is allowed (skip for dashboard - everyone can access)
if ($currentPage !== 'dashboard.php' && $currentPage !== 'login.php') {
    // If not super admin (role_id = 1), check URL permissions
    if ($_SESSION['admin_role_id'] != 1) {
        if (!in_array($currentPage, $allowedUrls)) {
            // Redirect to dashboard with access denied message
            header('Location: dashboard.php?error=access_denied');
            exit();
        }
    }
}

// Make admin info available to pages
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];
$admin_email = $_SESSION['admin_email'];
$admin_role_id = $_SESSION['admin_role_id'];
$admin_role_name = $_SESSION['admin_role_name'];
?>
