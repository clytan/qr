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
    $sqlLogin = "SELECT * FROM user WHERE (user_name = ? OR email = ? OR phone = ?) AND password = ?";
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

        // Set session as 'individual' if user_type_id is 1, 'business' if user_type_id is 2, 'Contributor' if user_type_id is 3 , 'Gold Member' if user_type_id is 4, 'Silver Member' if user_type_id is 5
        if ($user['user_type_id'] == 1) { // 
            $_SESSION['user'] = 'individual';
            $redirectUrl = 'index.php';
        } else if($user['user_type_id'] == 2){
            $_SESSION['user'] = 'business';
            $redirectUrl = 'index.php'; 
        }
        else if($user['user_type_id'] == 3){
            $_SESSION['user'] = 'contributor';
            $redirectUrl = 'index.php'; 
        }else if($user['user_type_id'] == 4){
            $_SESSION['user'] = 'gold_member';
            $redirectUrl = 'index.php'; 
        }else if($user['user_type_id'] == 5){
            $_SESSION['user'] = 'silver_member';
            $redirectUrl = 'index.php'; 
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