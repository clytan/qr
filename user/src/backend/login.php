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
    $sqlLogin = "SELECT * FROM user_user WHERE is_deleted = 0 AND (user_email = ? OR user_phone = ?) AND user_password = ?";
    $stmt = $conn->prepare($sqlLogin);
    if (!$stmt) {
        echo json_encode(['status' => false, 'message' => 'Database error', 'data' => []]);
        exit();
    }
    $stmt->bind_param('sss', $username, $username, $password);
    $stmt->execute();
    $resultLogin = $stmt->get_result();

    if ($resultLogin->num_rows > 0) {
        $user = $resultLogin->fetch_assoc();

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_user_type'] = $user['user_user_type'];
        $_SESSION['user_qr_id'] = $user['user_qr_id'];

        // Check if there's a return URL parameter from POST data
        $redirectUrl = 'index.php'; // Default redirect

        if (isset($_POST['return_url']) && !empty($_POST['return_url'])) {
            // Decode the return URL and use it
            $returnUrl = urldecode($_POST['return_url']);

            // Basic security check - ensure it's a local URL
            $parsedUrl = parse_url($returnUrl);
            if (
                !isset($parsedUrl['host']) ||
                $parsedUrl['host'] === $_SERVER['HTTP_HOST'] ||
                $parsedUrl['host'] === 'localhost'
            ) {
                $redirectUrl = $returnUrl;
            }
        }

        $data = ['redirect' => $redirectUrl];
        $stmt->close();
        echo json_encode(['status' => true, 'message' => '', 'data' => $data]);
        exit();

    } else {
        $stmt->close();
        // Provide JSON response for AJAX
        echo json_encode(['status' => false, 'message' => 'Invalid username or password', 'data' => []]);
        exit();
    }
}
?>