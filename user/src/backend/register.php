<?php
// Start the session
session_start();

// Include the database connection
require_once('./dbconfig/connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = isset($_POST['email']) ? trim($_POST['email']) : '';
	$user_type = isset($_POST['user_type']) ? $_POST['user_type'] : '';
	$user_tag = isset($_POST['user_tag']) ? trim($_POST['user_tag']) : '';
	$password = isset($_POST['password']) ? trim($_POST['password']) : '';
	$user_slab = isset($_POST['user_slab']) ? $_POST['user_slab'] : '';
	$reference_code = isset($_POST['reference_code']) ? trim($_POST['reference_code']) : '';
	$referred_by_user_id = isset($_POST['referred_by_user_id']) ? $_POST['referred_by_user_id'] : null;
	
	// Convert empty user_tag to NULL for database
	$user_tag = empty($user_tag) ? null : $user_tag;

	// Basic input validation
	if ($email === '' || $user_type === '' || $password === '' || $user_slab === '') {
		echo json_encode(['status' => false, 'message' => 'All required fields must be filled', 'data' => []]);
		exit();
	}

	// Validate reference code if provided
	if (!empty($reference_code)) {
		if (empty($referred_by_user_id)) {
			echo json_encode(['status' => false, 'message' => 'Invalid reference code', 'data' => []]);
			exit();
		}
		
		// Double-check the reference exists
		$sqlCheckRef = "SELECT id FROM user_user WHERE id = ? AND user_qr_id = ? AND is_deleted = 0";
		$stmtCheckRef = $conn->prepare($sqlCheckRef);
		$stmtCheckRef->bind_param('is', $referred_by_user_id, $reference_code);
		$stmtCheckRef->execute();
		$resultCheckRef = $stmtCheckRef->get_result();
		if ($resultCheckRef->num_rows === 0) {
			$stmtCheckRef->close();
			echo json_encode(['status' => false, 'message' => 'Invalid reference code', 'data' => []]);
			exit();
		}
		$stmtCheckRef->close();
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


		// Generate a 10-digit unique id for user_qr_id
		do {
			$user_qr_id = strval(mt_rand(1000000000, 9999999999));
			$sqlCheckQr = "SELECT 1 FROM user_user WHERE user_qr_id = ?";
			$stmtCheckQr = $conn->prepare($sqlCheckQr);
			$stmtCheckQr->bind_param('s', $user_qr_id);
			$stmtCheckQr->execute();
			$resultCheckQr = $stmtCheckQr->get_result();
			$exists = $resultCheckQr->num_rows > 0;
			$stmtCheckQr->close();
		} while ($exists);

		// Insert new user with user_qr_id and new fields
		$sqlInsert = "INSERT INTO user_user(user_email, user_password, user_user_type, user_tag, user_slab_id, user_qr_id, referred_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
		$stmtInsert = $conn->prepare($sqlInsert);
		if (!$stmtInsert) {
			echo json_encode(['status' => false, 'message' => 'Database error: ' . $conn->error, 'data' => []]);
			exit();
		}
		$stmtInsert->bind_param('ssisssi', $email, $password, $user_type, $user_tag, $user_slab, $user_qr_id, $referred_by_user_id);
		$success = $stmtInsert->execute();
		$stmtInsert->close();

		if ($success) {
			// Get the inserted user's ID (assuming auto_increment primary key 'id')
			$user_id = $conn->insert_id;
			$now = date('Y-m-d H:i:s');
			// Set created_by and updated_by to the user's own id, and timestamps
			$sqlUpdate = "UPDATE user_user SET is_deleted = 0, user_email_verified = 1, created_by = ?, updated_by = ?, created_on = ?, updated_on = ? WHERE id = ?";
			$stmtUpdate = $conn->prepare($sqlUpdate);
			if ($stmtUpdate) {
				$stmtUpdate->bind_param('sssss', $user_id, $user_id, $now, $now, $user_id);
				$stmtUpdate->execute();
				$stmtUpdate->close();
			}
		}

	if ($success) {
		echo json_encode(['status' => true, 'message' => 'Registration successful', 'data' => []]);
		exit();
	} else {
		echo json_encode(['status' => false, 'message' => 'Registration failed', 'data' => []]);
		exit();
	}
}
?>
