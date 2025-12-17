<?php
session_start();

// Clear only admin session variables (preserve user session if any)
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_role_id']);
unset($_SESSION['admin_role_name']);
unset($_SESSION['admin_allowed_urls']);

// Redirect to login page
header('Location: ../ui/login.php');
exit();
?>
