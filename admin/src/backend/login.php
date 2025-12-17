<?php
// Start admin session
session_start();

// Include the database connection
require_once('./dbconfig/connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Basic input validation
    if ($username === '' || $password === '') {
        echo json_encode(['status' => false, 'message' => 'Username and password are required', 'data' => []]);
        exit();
    }

    // Login query - check admin_user table with role join
    $sqlLogin = "SELECT au.*, aur.role_name 
                 FROM admin_user au 
                 LEFT JOIN admin_user_role aur ON au.role_id = aur.id 
                 WHERE au.is_deleted = 0 
                 AND (au.user_name = ? OR au.email = ?) 
                 AND au.password = ?";
    
    $stmt = $conn->prepare($sqlLogin);
    if (!$stmt) {
        echo json_encode(['status' => false, 'message' => 'Database error', 'data' => []]);
        exit();
    }
    
    $stmt->bind_param('sss', $username, $username, $password);
    $stmt->execute();
    $resultLogin = $stmt->get_result();

    if ($resultLogin->num_rows > 0) {
        $admin = $resultLogin->fetch_assoc();

        // Set ADMIN session variables (different from user session to avoid conflicts)
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['user_name'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_role_id'] = $admin['role_id'];
        $_SESSION['admin_role_name'] = $admin['role_name'];

        // Fetch allowed URLs for this admin
        $sqlUrls = "SELECT allowed_urls FROM admin_urls WHERE user_id = ?";
        $stmtUrls = $conn->prepare($sqlUrls);
        $stmtUrls->bind_param('i', $admin['id']);
        $stmtUrls->execute();
        $resultUrls = $stmtUrls->get_result();
        
        $allowedUrls = [];
        while ($row = $resultUrls->fetch_assoc()) {
            $allowedUrls[] = $row['allowed_urls'];
        }
        $_SESSION['admin_allowed_urls'] = $allowedUrls;
        $stmtUrls->close();

        $data = ['redirect' => 'dashboard.php'];
        $stmt->close();
        echo json_encode(['status' => true, 'message' => 'Login successful', 'data' => $data]);
        exit();

    } else {
        $stmt->close();
        echo json_encode(['status' => false, 'message' => 'Invalid username or password', 'data' => []]);
        exit();
    }
}
?>
