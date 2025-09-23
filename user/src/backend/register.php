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
	$selected_slab = isset($_POST['user_slab']) ? $_POST['user_slab'] : '';
	$reference_code = isset($_POST['reference_code']) ? trim($_POST['reference_code']) : '';
	$referred_by_user_id = isset($_POST['referred_by_user_id']) ? $_POST['referred_by_user_id'] : null;
	$college_name = isset($_POST['college_name']) ? trim($_POST['college_name']) : ''; // Add college name
	$default_slab_id = 1;

	// Convert empty user_tag to NULL for database
	$user_tag = empty($user_tag) ? null : $user_tag;

	// Convert empty college_name to NULL for database
	$college_name = empty($college_name) ? null : $college_name;

	// Basic input validation
	if ($email === '' || $user_type === '' || $password === '' || $selected_slab === '') {
		echo json_encode(['status' => false, 'message' => 'All required fields must be filled', 'data' => []]);
		exit();
	}

	// Check if selected slab requires college name (assuming "Student Leader" slab name contains "student leader")
	if (!empty($selected_slab)) {
		$sqlSlabCheck = "SELECT name FROM user_slab WHERE id = ?";
		$stmtSlabCheck = $conn->prepare($sqlSlabCheck);
		$stmtSlabCheck->bind_param('i', $selected_slab);
		$stmtSlabCheck->execute();
		$resultSlabCheck = $stmtSlabCheck->get_result();

		if ($resultSlabCheck->num_rows > 0) {
			$slabData = $resultSlabCheck->fetch_assoc();
			$slabName = strtolower($slabData['name']);

			// If slab name contains "student leader", college name is required
			if (strpos($slabName, 'student leader') !== false && empty($college_name)) {
				$stmtSlabCheck->close();
				echo json_encode(['status' => false, 'message' => 'College name is required for Student Leader plan', 'data' => []]);
				exit();
			}
		}
		$stmtSlabCheck->close();
	}

	// Validate reference code if provided
	if (!empty($reference_code)) {
		// Double-check the reference exists by user_qr_id only
		$sqlCheckRef = "SELECT id FROM user_user WHERE user_qr_id = ? AND is_deleted = 0";
		$stmtCheckRef = $conn->prepare($sqlCheckRef);
		$stmtCheckRef->bind_param('s', $reference_code);
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
	$stmtCheck->bind_param('s', $email);
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

	// First check if college_name column exists in user_user table
	$sqlCheckColumn = "SHOW COLUMNS FROM user_user LIKE 'college_name'";
	$resultCheckColumn = $conn->query($sqlCheckColumn);
	$columnExists = $resultCheckColumn && $resultCheckColumn->num_rows > 0;

	if ($columnExists) {
		// Insert new user with college_name if column exists
		$sqlInsert = "INSERT INTO user_user(user_email, user_password, user_user_type, user_tag, user_slab_id, user_qr_id, referred_by_user_id, college_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
		$stmtInsert = $conn->prepare($sqlInsert);
		if (!$stmtInsert) {
			echo json_encode(['status' => false, 'message' => 'Database error: ' . $conn->error, 'data' => []]);
			exit();
		}
		$stmtInsert->bind_param('ssisssis', $email, $password, $user_type, $user_tag, $default_slab_id, $user_qr_id, $referred_by_user_id, $college_name);
	} else {
		// Insert without college_name if column doesn't exist (fallback for existing systems)
		$sqlInsert = "INSERT INTO user_user(user_email, user_password, user_user_type, user_tag, user_slab_id, user_qr_id, referred_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
		$stmtInsert = $conn->prepare($sqlInsert);
		if (!$stmtInsert) {
			echo json_encode(['status' => false, 'message' => 'Database error: ' . $conn->error, 'data' => []]);
			exit();
		}
		$stmtInsert->bind_param('ssisssi', $email, $password, $user_type, $user_tag, $default_slab_id, $user_qr_id, $referred_by_user_id);

		// Log the college name if provided but column doesn't exist
		if (!empty($college_name)) {
			error_log("College name '$college_name' provided for user '$email' but college_name column doesn't exist in user_user table");
		}
	}

	$success = $stmtInsert->execute();
	$stmtInsert->close();

	if ($success) {
		// If user selected a slab other than default, create a user_request for admin approval
		$user_id = $conn->insert_id; // Ensure user_id is set before using
		$now = date('Y-m-d H:i:s'); // Set timestamp before using in user_request
		if ($selected_slab != $default_slab_id) {
			$sqlRequest = "INSERT INTO user_request (user_id, requested_slab_id, status, created_by, updated_by, created_on, updated_on, is_deleted) VALUES (?, ?, 'pending', ?, ?, ?, ?, 0)";
			$stmtRequest = $conn->prepare($sqlRequest);
			if ($stmtRequest) {
				$stmtRequest->bind_param('iissss', $user_id, $selected_slab, $user_id, $user_id, $now, $now);
				$stmtRequest->execute();
				$stmtRequest->close();
			}
		}
		// Set created_by and updated_by to the user's own id, and timestamps
		$sqlUpdate = "UPDATE user_user SET is_deleted = 0, user_email_verified = 1, created_by = ?, updated_by = ?, created_on = ?, updated_on = ? WHERE id = ?";
		$stmtUpdate = $conn->prepare($sqlUpdate);
		if ($stmtUpdate) {
			$stmtUpdate->bind_param('isssi', $user_id, $user_id, $now, $now, $user_id);
			$stmtUpdate->execute();
			$stmtUpdate->close();
		}

		// Referral commission logic with debug output
		if (!empty($referred_by_user_id)) {
			// 1. Find referrer by user_qr_id (use referred_by_user_id as user_qr_id)
			$sqlReferrer = "SELECT id, user_slab_id, user_full_name FROM user_user WHERE user_qr_id = ? AND is_deleted = 0";
			$stmtReferrer = $conn->prepare($sqlReferrer);
			$stmtReferrer->bind_param('s', $referred_by_user_id);
			$stmtReferrer->execute();
			$resultReferrer = $stmtReferrer->get_result();
			if ($resultReferrer->num_rows > 0) {
				$referrer = $resultReferrer->fetch_assoc();
				$referrer_id = $referrer['id'];
				$referrer_slab_id = $referrer['user_slab_id'];
				$referrer_name = $referrer['user_full_name'];
				// 2. Get commission percentage
				$sqlSlab = "SELECT ref_commission FROM user_slab WHERE id = ?";
				$stmtSlab = $conn->prepare($sqlSlab);
				$stmtSlab->bind_param('i', $referrer_slab_id);
				$stmtSlab->execute();
				$resultSlab = $stmtSlab->get_result();
				$commission_percent = 0;
				if ($resultSlab->num_rows > 0) {
					$rowSlab = $resultSlab->fetch_assoc();
					$commission_percent = floatval($rowSlab['ref_commission']);
				}
				$stmtSlab->close();
				// 3. Calculate commission (dummy base amount 100)
				$base_amount = 100;
				$commission_amount = $base_amount * ($commission_percent / 100);
				// Debug output
				error_log("Referral debug: referrer_id=$referrer_id, slab_id=$referrer_slab_id, commission_percent=$commission_percent, commission_amount=$commission_amount");
				// 4. Update or insert into user_wallet
				$sqlWallet = "SELECT id, balance FROM user_wallet WHERE user_id = ? AND is_deleted = 0";
				$stmtWallet = $conn->prepare($sqlWallet);
				$stmtWallet->bind_param('i', $referrer_id);
				$stmtWallet->execute();
				$resultWallet = $stmtWallet->get_result();
				if ($resultWallet->num_rows > 0) {
					$rowWallet = $resultWallet->fetch_assoc();
					$new_balance = $rowWallet['balance'] + $commission_amount;
					$wallet_id = $rowWallet['id'];
					$sqlUpdateWallet = "UPDATE user_wallet SET balance = ?, updated_by = ?, updated_on = ? WHERE id = ?";
					$stmtUpdateWallet = $conn->prepare($sqlUpdateWallet);
					$stmtUpdateWallet->bind_param('disi', $new_balance, $referrer_id, $now, $wallet_id);
					$stmtUpdateWallet->execute();
					$stmtUpdateWallet->close();
					error_log("Referral debug: Updated wallet for referrer_id=$referrer_id, new_balance=$new_balance");
				} else {
					$sqlInsertWallet = "INSERT INTO user_wallet (user_id, balance, created_by, created_on, updated_by, updated_on, is_deleted) VALUES (?, ?, ?, ?, ?, ?, 0)";
					$stmtInsertWallet = $conn->prepare($sqlInsertWallet);
					$stmtInsertWallet->bind_param('idisis', $referrer_id, $commission_amount, $referrer_id, $now, $referrer_id, $now);
					$stmtInsertWallet->execute();
					$stmtInsertWallet->close();
					error_log("Referral debug: Created wallet for referrer_id=$referrer_id, balance=$commission_amount, created_on=$now");
				}
				$stmtWallet->close();
				// 5. Insert into user_wallet_transaction
				$description = "You referred a new user and earned a commission.";
				$sqlTrans = "INSERT INTO user_wallet_transaction (user_id, amount, transaction_type, description, created_by, updated_by, updated_on, is_deleted, created_on) VALUES (?, ?, 'Referral', ?, ?, ?, ?, 0, ?)";
				$stmtTrans = $conn->prepare($sqlTrans);
				$stmtTrans->bind_param('idssiiss', $referrer_id, $commission_amount, $description, $referrer_id, $referrer_id, $now, $now);
				$stmtTrans->execute();
				$stmtTrans->close();
				error_log("Referral debug: Inserted transaction for referrer_id=$referrer_id, amount=$commission_amount, created_on=$now");
			} else {
				error_log("Referral debug: No referrer found for user_qr_id=$referred_by_user_id");
			}
			$stmtReferrer->close();
		}
	}

	if ($success) {
		// Invoice creation logic - FIXED: Use a default amount since subscription_amount column doesn't exist
		$slab_id = $selected_slab !== '' ? $selected_slab : $default_slab_id;

		// Option 1: Use a default amount
		$amount = 100.00; // Set your default registration amount here

		// 2. Calculate GST (example: 18% split as 9% CGST, 9% SGST)
		$cgst = $amount * 0.09;
		$sgst = $amount * 0.09;
		$igst = 0.00;
		$gst_total = $cgst + $sgst + $igst;
		$total_amount = $amount + $gst_total;
		// 3. Generate invoice number (simple example)
		$invoice_number = 'INV' . date('Ymd') . '-' . str_pad($user_id, 3, '0', STR_PAD_LEFT);
		$order_number = 'ORD' . date('Ymd') . '-' . str_pad($user_id, 3, '0', STR_PAD_LEFT);
		$status = 'Paid'; // or 'Pending' if you want to mark as unpaid
		$now = date('Y-m-d H:i:s');
		$sqlInvoice = "INSERT INTO user_invoice (user_id, invoice_number, invoice_type, amount, cgst, sgst, igst, gst_total, total_amount, status, payment_mode, payment_reference, created_on, updated_on, is_deleted) VALUES (?, ?, 'registration', ?, ?, ?, ?, ?, ?, ?, '', '', ?, ?, 0)";
		$stmtInvoice = $conn->prepare($sqlInvoice);
		$stmtInvoice->bind_param('isddddddsss', $user_id, $invoice_number, $amount, $cgst, $sgst, $igst, $gst_total, $total_amount, $status, $now, $now);
		$stmtInvoice->execute();
		$stmtInvoice->close();
		echo json_encode(['status' => true, 'message' => 'Registration successful', 'data' => []]);
		exit();
	} else {
		echo json_encode(['status' => false, 'message' => 'Registration failed', 'data' => []]);
		exit();
	}
}
?>