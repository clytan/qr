<?php
// Prevent any output before JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Load the INI file
$config = parse_ini_file('config.ini');

if (!$config) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'message' => 'Database configuration not found']);
    exit();
}

// Create a connection to the database
$conn = new mysqli($config['host'], $config['username'], $config['password'], $config['dbname']);
date_default_timezone_set('Asia/Kolkata');

// Check the connection
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'message' => 'Database connection failed']);
    exit();
}

// Set character set to UTF-8
$conn->set_charset("utf8");
?>
