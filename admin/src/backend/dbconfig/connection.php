<?php
// Load the INI file
$config = parse_ini_file('config.ini');

// Create a connection to the database
$conn = new mysqli($config['host'], $config['username'], $config['password'], $config['dbname']);
date_default_timezone_set('Asia/Kolkata');

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to UTF-8 (optional, depending on your application requirements)
$conn->set_charset("utf8");
?>