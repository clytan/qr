<?php
// Set session cookie parameters
session_set_cookie_params([
    'lifetime' => 0,                     // Session cookie expires when browser closes
    'path' => '/',                       // Available across entire domain
    'domain' => '',                      // Current domain only
    'secure' => false,                   // Set to true if using HTTPS
    'httponly' => true,                  // Prevent JavaScript access to session cookie
    'samesite' => 'Lax'                 // Protect against CSRF
]);

// Set session garbage collection probability to 1%
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);

// Set session lifetime to 30 minutes
ini_set('session.gc_maxlifetime', 1800);

// Use database or files for session storage
ini_set('session.save_handler', 'files');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}