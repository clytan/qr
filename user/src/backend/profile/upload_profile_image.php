<?php
require_once('../dbconfig/connection.php');
session_start();

$response = ['status' => false, 'message' => '', 'src' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Not authenticated';
    echo json_encode($response);
    exit;
}

if (!isset($_FILES['profile_image'])) {
    $response['message'] = 'No file uploaded';
    echo json_encode($response);
    exit;
}

$file = $_FILES['profile_image'];
$userId = $_SESSION['user_id'];

// Validate file
$allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
if (!in_array($file['type'], $allowedTypes)) {
    $response['message'] = 'Invalid file type. Only JPG and PNG allowed.';
    echo json_encode($response);
    exit;
}

// Create upload directory if it doesn't exist
$uploadDir = '../../uploads/profile_images/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = $userId . '_' . time() . '.' . $extension;
$filepath = $uploadDir . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    try {
        // Start transaction
        $conn->begin_transaction();

        // Set previous images as inactive
        $stmt = $conn->prepare("UPDATE user_profile_images SET is_active = 0 WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        // Insert new image record
        $stmt = $conn->prepare("INSERT INTO user_profile_images (user_id, image_path, created_by) VALUES (?, ?, ?)");
        $relativePath = 'uploads/profile_images/' . $filename;
        $stmt->bind_param("isi", $userId, $relativePath, $userId);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        $response['status'] = true;
        $response['message'] = 'Profile image uploaded successfully';
        $response['src'] = '../' . $relativePath;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $response['message'] = 'Database error: ' . $e->getMessage();
        // Delete uploaded file if database insert fails
        unlink($filepath);
    }
} else {
    $response['message'] = 'Failed to upload file';
}

echo json_encode($response);
?>