<?php
// Start the session
session_start();

// Include the database connection
require_once('./dbconfig/connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['information']) ? trim($_POST['information']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Basic input validation (optional, but good practice)
    if ($username === '' || $password === '') {
        echo json_encode(['status' => false, 'message' => 'Username and password are required', 'data' => []]);
        exit();
    }

    // Login query with prepared statement to allow login with username or email
    $sqlLogin = "SELECT * FROM admin_user WHERE (user_name = ? OR email = ? OR phone = ?) AND password = ?";
    $stmt = $conn->prepare($sqlLogin);
    if (!$stmt) {
        echo json_encode(['status' => false, 'message' => 'Database error', 'data' => []]);
        exit();
    }
    $stmt->bind_param('ssss', $username, $username, $username, $password);
    $stmt->execute();
    $resultLogin = $stmt->get_result();

    if ($resultLogin->num_rows > 0) {
        $user = $resultLogin->fetch_assoc();
        // Do not print user or any debug output here

        // Set session as 'admin' if id is 1, else 'employee', and store user id separately
        if ($user['id'] == 1) {
            $_SESSION['user'] = 'admin';
            $redirectUrl = 'index.php';
        } else {
            $_SESSION['user'] = 'employee';
            $redirectUrl = 'login.php'; ## change the redirect URL here
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role_id'] = $user['role_id'];

        $data = ['redirect' => $redirectUrl, 'sessionID' => $user['role_id']];
        echo json_encode(['status' => true, 'message' => '', 'data' => $data]);
        exit();
        
    } else {
        // Provide JSON response for AJAX
        echo json_encode(['status' => false, 'message' => 'Invalid username or password' ,'data' => []]);
        exit();
    }
}
?>