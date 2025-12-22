<?php
/**
 * Create Poll API
 * 
 * Creates a new poll with options
 * Polls go live immediately
 */

header('Content-Type: application/json');


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../dbconfig/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Invalid request method']);
    exit;
}

$userId = intval($_SESSION['user_id']);

// Get POST data (FormData)
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$pollType = $_POST['poll_type'] ?? 'single';
$encodedOptions = $_POST['options'] ?? null; 
// Frontend might send options as JSON string or array depending on implementation
// But with FormData, we can use array convention options[]
// Let's assume frontend sends specialized arrays: "option_texts[]" and "option_images_indices[]" or mapped by index.
// Strategy: use "option_texts[]" array

$optionTexts = $_POST['option_texts'] ?? [];
$endsAt = $_POST['ends_at'] ?? null;

// Validation
if (empty($title)) {
    echo json_encode(['status' => false, 'message' => 'Poll title is required']);
    exit;
}

if (strlen($title) > 255) {
    echo json_encode(['status' => false, 'message' => 'Title is too long (max 255 characters)']);
    exit;
}

if (count($optionTexts) < 2) {
    echo json_encode(['status' => false, 'message' => 'At least 2 options are required']);
    exit;
}

if (count($optionTexts) > 10) {
    echo json_encode(['status' => false, 'message' => 'Maximum 10 options allowed']);
    exit;
}

// Validate each option
foreach ($optionTexts as $text) {
    if (empty(trim($text))) {
        echo json_encode(['status' => false, 'message' => 'All options must have text']);
        exit;
    }
    if (strlen($text) > 255) {
        echo json_encode(['status' => false, 'message' => 'Option text is too long (max 255 characters)']);
        exit;
    }
}

// Validate poll type
if (!in_array($pollType, ['single', 'multiple'])) {
    $pollType = 'single';
}

// Image Handling Helper
function uploadPollImage($fileIndex) {
    if (!isset($_FILES['option_images']['name'][$fileIndex]) || empty($_FILES['option_images']['name'][$fileIndex])) {
        return null;
    }

    $file = [
        'name' => $_FILES['option_images']['name'][$fileIndex],
        'type' => $_FILES['option_images']['type'][$fileIndex],
        'tmp_name' => $_FILES['option_images']['tmp_name'][$fileIndex],
        'error' => $_FILES['option_images']['error'][$fileIndex],
        'size' => $_FILES['option_images']['size'][$fileIndex]
    ];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null; // or throw exception
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        throw new Exception("Invalid file type for option " . ($fileIndex + 1));
    }

    // 2MB Limit
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception("File too large for option " . ($fileIndex + 1));
    }

    // Adjust path for XAMPP root 'user/src/uploads/polls/'
    // We are in 'user/src/backend/polls/'
    $uploadDir = __DIR__ . '/../../uploads/polls/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filename = uniqid('poll_opt_') . '.' . $ext;
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'user/src/uploads/polls/' . $filename;
    }
    
    return null;
}

try {
    $conn->begin_transaction();
    
    // Insert poll
    $sqlPoll = "INSERT INTO user_polls (user_id, title, description, poll_type, status, payment_status, ends_at, created_by, created_on) 
                VALUES (?, ?, ?, ?, 'pending_payment', 'pending', ?, ?, NOW())";
    $stmtPoll = $conn->prepare($sqlPoll);
    $stmtPoll->bind_param('issssi', $userId, $title, $description, $pollType, $endsAt, $userId);
    
    if (!$stmtPoll->execute()) {
        throw new Exception("Failed to create poll");
    }
    
    $pollId = $conn->insert_id;
    $stmtPoll->close();
    
    // Insert options
    // Assuming 'option_image' column exists (added via schema update)
    $sqlOption = "INSERT INTO user_poll_options (poll_id, option_text, option_image, option_order) VALUES (?, ?, ?, ?)";
    $stmtOption = $conn->prepare($sqlOption);
    
    $order = 1;
    // Iterate through option texts
    foreach ($optionTexts as $index => $optionText) {
        $optionText = trim($optionText);
        if (!empty($optionText)) {
            // Handle Image Upload
            $imagePath = null;
            try {
                // $_FILES['option_images'] is structured as ['name'=>[0=>...], 'tmp_name'=>[0=>...]] 
                // matched by index to optionTexts if array works out
                $imagePath = uploadPollImage($index);
            } catch (Exception $imgErr) {
                // Fail the whole poll if image validation fails? Yes, better UX to be strict.
                throw $imgErr;
            }

            $stmtOption->bind_param('issi', $pollId, $optionText, $imagePath, $order);
            if (!$stmtOption->execute()) {
                throw new Exception("Failed to create poll option");
            }
            $order++;
        }
    }
    $stmtOption->close();
    
    $conn->commit();
    
    echo json_encode([
        'status' => true,
        'message' => 'Poll created successfully!',
        'poll_id' => $pollId
    ]);
    
} catch (Exception $e) {
    if ($conn->in_transaction) {
        $conn->rollback();
    }
    error_log("Create poll error: " . $e->getMessage());
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>
