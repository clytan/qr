<?php
// Start the session
session_start();

// Include the database connection
require_once('./dbconfig/connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = isset($_POST['email']) ? trim($_POST['email']) : '';
	$user_type = isset($_POST['user_type']) ? $_POST['user_type'] : [];
	$password = isset($_POST['password']) ? trim($_POST['password']) : '';

	// Basic input validation
	if ($email === '' || count($user_type) == 0 || $password === '') {
		echo json_encode(['status' => false, 'message' => 'All fields are required', 'data' => []]);
		exit();
	}

	// Check if user already exists (by username or email or phone)
	$sqlCheck = "SELECT * FROM user_user WHERE user_email = ?";
	$stmtCheck = $conn->prepare($sqlCheck);
	if (!$stmtCheck) {
		echo json_encode(['status' => false, 'message' => 'Database error', 'data' => []]);
		exit();
	}
	$stmtCheck->bind_param('s',  $email);
	$stmtCheck->execute();
	$resultCheck = $stmtCheck->get_result();
	if ($resultCheck->num_rows > 0) {
		$stmtCheck->close();
		echo json_encode(['status' => false, 'message' => 'User already exists with this email', 'data' => []]);
		exit();
	}
	$stmtCheck->close();

	// Insert new user
	$sqlInsert = "INSERT INTO user_user(user_email, user_password, user_user_type, user_tag) VALUES (?, ?, ?, ?)";
	$stmtInsert = $conn->prepare($sqlInsert);
	if (!$stmtInsert) {
		echo json_encode(['status' => false, 'message' => 'Database error: ' . $conn->error, 'data' => []]);
		exit();
	}
	$user_type_0 = isset($user_type[0]) ? $user_type[0] : '';
	$user_type_1 = isset($user_type[1]) ? $user_type[1] : '';
	$stmtInsert->bind_param('ssss', $email, $password, $user_type_0, $user_type_1);
	$success = $stmtInsert->execute();
	$stmtInsert->close();

	if ($success) {
		echo json_encode(['status' => true, 'message' => 'Registration successful', 'data' => []]);
		exit();
	} else {
		echo json_encode(['status' => false, 'message' => 'Registration failed', 'data' => []]);
		exit();
	}
}
?>
