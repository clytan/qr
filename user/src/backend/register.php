
<?php
// Start the session
session_start();

// Include the database connection
require_once('./dbconfig/connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$name = isset($_POST['name']) ? trim($_POST['name']) : '';
	$email = isset($_POST['email']) ? trim($_POST['email']) : '';
	$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
	$user_type = isset($_POST['user_type']) ? trim($_POST['user_type']) : '';
	// $username = isset($_POST['username']) ? trim($_POST['username']) : '';
	// $password = isset($_POST['password']) ? trim($_POST['password']) : '';

	// Basic input validation
	if ($name === '' || $email === '' || $phone === '' || $user_type === '' || $username === '' || $password === '') {
		echo json_encode(['status' => false, 'message' => 'All fields are required', 'data' => []]);
		exit();
	}

	// Check if user already exists (by username or email or phone)
	$sqlCheck = "SELECT * FROM users WHERE name = ? AND email = ? AND phone = ?";
	$stmtCheck = $conn->prepare($sqlCheck);
	if (!$stmtCheck) {
		echo json_encode(['status' => false, 'message' => 'Database error', 'data' => []]);
		exit();
	}
	$stmtCheck->bind_param('sss', $name, $email, $phone);
	$stmtCheck->execute();
	$resultCheck = $stmtCheck->get_result();
	if ($resultCheck->num_rows > 0) {
		echo json_encode(['status' => false, 'message' => 'User already exists with this username, email, or phone', 'data' => []]);
		exit();
	}

	// Insert new user
	$sqlInsert = "INSERT INTO users (name, email, phone, user_type, username, password) VALUES (?, ?, ?, ?, ?, ?)";
	$stmtInsert = $conn->prepare($sqlInsert);
	if (!$stmtInsert) {
		echo json_encode(['status' => false, 'message' => 'Database error', 'data' => []]);
		exit();
	}
	$stmtInsert->bind_param('ssssss', $name, $email, $phone, $user_type, $username, $password);
	$success = $stmtInsert->execute();

	if ($success) {
		echo json_encode(['status' => true, 'message' => 'Registration successful', 'data' => []]);
		exit();
	} else {
		echo json_encode(['status' => false, 'message' => 'Registration failed', 'data' => []]);
		exit();
	}
}
?>
