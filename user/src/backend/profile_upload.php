<?php
require_once('.././dbconfig/connection.php');

// CORRECTED: Set target directory to match your desired path
$targetDir = __DIR__ . "/../ui/profile/";
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$response = ['status' => false, 'message' => '', 'src' => ''];

if (isset($_FILES['profile_img']) && isset($_POST['user_id'])) {
    $file = $_FILES['profile_img'];
    $fileName = basename($file['name']);
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png'];

    if (!in_array($fileType, $allowed)) {
        $response['message'] = 'Only JPG and PNG files are allowed.';
    } else if ($file['error'] !== 0) {
        $response['message'] = 'File upload error.';
    } else {
        // Generate unique filename to avoid issues with spaces and duplicates
        $userId = intval($_POST['user_id']);
        $newName = 'user_' . $userId . '_' . time() . '.' . $fileType;
        $targetFile = $targetDir . $newName;

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            // CORRECTED: Path now matches where file is actually saved
            $src = '/user/src/ui/profile/' . $newName;

            // Update user_user table with new image path
            $sql = "UPDATE user_user SET user_image_path = ?, updated_on = NOW(), updated_by = ? WHERE id = ? AND is_deleted = 0";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('sii', $src, $userId, $userId);
                if ($stmt->execute()) {
                    $response['status'] = true;
                    $response['src'] = $src;
                    $response['message'] = 'Upload and DB update successful.';
                } else {
                    $response['message'] = 'DB update failed: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $response['message'] = 'DB prepare failed: ' . $conn->error;
            }
        } else {
            $response['message'] = 'Failed to move uploaded file.';
        }
    }
} else {
    $response['message'] = 'No file uploaded or user_id missing.';
}

header('Content-Type: application/json');
echo json_encode($response);
?>