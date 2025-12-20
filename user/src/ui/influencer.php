<?php
session_start();
require_once('../backend/dbconfig/connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Influencer';

// Get user details from database for accurate info
$stmtUser = $conn->prepare("SELECT user_email, user_user_type FROM user_user WHERE id = ?");
$stmtUser->bind_param('i', $user_id);
$stmtUser->execute();
$userResult = $stmtUser->get_result();
$userData = $userResult->fetch_assoc();
$stmtUser->close();

$user_email = $userData['user_email'] ?? $_SESSION['user_email'] ?? '';
$user_type = intval($userData['user_user_type'] ?? $_SESSION['user_user_type'] ?? 1);
$is_biz_user = ($user_type === 3); // Biz users can create collaborations

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    
    switch ($action) {
        case 'get_collabs':
            try {
                $status_filter = $_POST['status'] ?? 'all';
                
                $sql = "SELECT c.* FROM influencer_collabs c
                        WHERE c.is_deleted = 0";
                
                if ($status_filter === 'available') {
                    $sql .= " AND c.status = 'pending'";
                } elseif ($status_filter === 'my_collabs') {
                    $sql .= " AND c.accepted_by = ?";
                }
                
                $sql .= " ORDER BY c.created_on DESC";
                
                if ($status_filter === 'my_collabs') {
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('i', $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                } else {
                    $result = $conn->query($sql);
                }
                
                $collabs = [];
                while ($row = $result->fetch_assoc()) {
                    $collabs[] = $row;
                }
                
                echo json_encode(['status' => true, 'data' => $collabs]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'accept_collab':
            try {
                $collab_id = intval($_POST['collab_id'] ?? 0);
                
                if ($collab_id <= 0) {
                    echo json_encode(['status' => false, 'message' => 'Invalid collaboration ID']);
                    exit();
                }
                
                // Get collab details
                $stmt = $conn->prepare("SELECT * FROM influencer_collabs WHERE id = ? AND status = 'pending' AND is_deleted = 0");
                $stmt->bind_param('i', $collab_id);
                $stmt->execute();
                $collab = $stmt->get_result()->fetch_assoc();
                
                if (!$collab) {
                    echo json_encode(['status' => false, 'message' => 'Collaboration not found or already accepted']);
                    exit();
                }
                
                // Update collab status
                $stmt = $conn->prepare("UPDATE influencer_collabs SET status = 'active', accepted_by = ?, accepted_on = NOW() WHERE id = ?");
                $stmt->bind_param('ii', $user_id, $collab_id);
                
                if ($stmt->execute()) {
                    // Fetch QR ID for email
                    $qrStmt = $conn->prepare("SELECT user_qr_id FROM user_user WHERE id = ?");
                    $qrStmt->bind_param('i', $user_id);
                    $qrStmt->execute();
                    $qrRes = $qrStmt->get_result()->fetch_assoc();
                    $user_qr_id = $qrRes['user_qr_id'] ?? 'N/A';

                    // Send emails
                    sendCollabAcceptanceEmails($conn, $collab, $user_id, $user_name, $user_email, $user_qr_id);
                    
                    echo json_encode([
                        'status' => true,
                        'message' => 'Collaboration accepted successfully! Check your email for details.'
                    ]);
                } else {
                    echo json_encode(['status' => false, 'message' => 'Failed to accept collaboration']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'create_collab':
            // Only Biz users can create collaborations
            if ($user_type != 3) {
                echo json_encode(['status' => false, 'message' => 'Only Business users can create collaborations']);
                exit();
            }
            
            try {
                $collab_title = trim($_POST['collab_title'] ?? '');
                $category = trim($_POST['category'] ?? '');
                if ($category === 'other') {
                    $other_cat = trim($_POST['other_category'] ?? '');
                    if (!empty($other_cat)) {
                        $category = "Other: " . $other_cat;
                    }
                }

                $product_description = trim($_POST['product_description'] ?? '');
                $product_link = trim($_POST['product_link'] ?? '');
                $financial_type = trim($_POST['financial_type'] ?? 'barter');
                $financial_amount = floatval($_POST['financial_amount'] ?? 0);
                $detailed_summary = trim($_POST['detailed_summary'] ?? '');
                $brand_email = $user_email; 
                
                if (empty($collab_title) || empty($category) || empty($product_description)) {
                    echo json_encode(['status' => false, 'message' => 'Title, category and description are required']);
                    exit();
                }
                
                // Handle file uploads
                $uploaded_photos = [];
                $upload_dir = '../../../uploads/collabs/';
                
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                for ($i = 1; $i <= 3; $i++) {
                    $field = "photo_$i";
                    if (isset($_FILES[$field]) && $_FILES[$field]['error'] == 0) {
                        $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
                        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        
                        if (in_array($ext, $allowed)) {
                            $filename = 'collab_' . time() . '_' . $i . '.' . $ext;
                            $filepath = $upload_dir . $filename;
                            
                            if (move_uploaded_file($_FILES[$field]['tmp_name'], $filepath)) {
                                $uploaded_photos[$field] = 'uploads/collabs/' . $filename;
                            }
                        }
                    }
                }
                
                $photo_1 = $uploaded_photos['photo_1'] ?? null;
                $photo_2 = $uploaded_photos['photo_2'] ?? null;
                $photo_3 = $uploaded_photos['photo_3'] ?? null;
                
                $sql = "INSERT INTO influencer_collabs 
                        (collab_title, category, product_description, product_link, 
                         photo_1, photo_2, photo_3, financial_type, financial_amount, 
                         detailed_summary, brand_email, status, created_by, created_on, is_deleted) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), 0)";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ssssssssdssi',
                    $collab_title, $category, $product_description, $product_link,
                    $photo_1, $photo_2, $photo_3, $financial_type, $financial_amount,
                    $detailed_summary, $brand_email, $user_id
                );
                
                if ($stmt->execute()) {
                    echo json_encode(['status' => true, 'message' => 'Collaboration created successfully!']);
                } else {
                    echo json_encode(['status' => false, 'message' => 'Failed to create collaboration']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'get_my_created':
            // Get collaborations created by this Biz user
            try {
                $sql = "SELECT * FROM influencer_collabs WHERE created_by = ? AND is_deleted = 0 ORDER BY created_on DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $collabs = [];
                while ($row = $result->fetch_assoc()) {
                    $collabs[] = $row;
                }
                
                echo json_encode(['status' => true, 'data' => $collabs]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'delete_collab':
            // Only creator can delete their collab
            try {
                $collab_id = intval($_POST['collab_id'] ?? 0);
                
                // Check ownership
                $stmt = $conn->prepare("SELECT created_by FROM influencer_collabs WHERE id = ? AND is_deleted = 0");
                $stmt->bind_param('i', $collab_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $collab = $result->fetch_assoc();
                
                if (!$collab || $collab['created_by'] != $user_id) {
                    echo json_encode(['status' => false, 'message' => 'You can only delete your own collaborations']);
                    exit();
                }
                
                $stmt = $conn->prepare("UPDATE influencer_collabs SET is_deleted = 1 WHERE id = ?");
                $stmt->bind_param('i', $collab_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['status' => true, 'message' => 'Collaboration deleted']);
                } else {
                    echo json_encode(['status' => false, 'message' => 'Failed to delete']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
    }
}

// Email function
function sendCollabAcceptanceEmails($conn, $collab, $influencer_id, $influencer_name, $influencer_email, $influencer_qr_id) {
    $admin_email = "admin@yourcompany.com"; // Configure this
    
    $subject = "🎉 Collaboration Accepted: " . $collab['collab_title'];
    $headers = "From: noreply@yourcompany.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $financial_text = $collab['financial_type'] === 'paid' ? '₹' . number_format($collab['financial_amount'], 2) : 'Barter (Product Exchange)';
    
    // Email content
    $message = "
    <html>
    <head>
        <style>
            body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; background: #f5f5f5; }
            .container { max-width: 600px; margin: 30px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #E9437A, #E2AD2A); color: white; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { padding: 30px; }
            .details { background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .detail-row { display: flex; margin-bottom: 12px; }
            .label { font-weight: bold; color: #E9437A; min-width: 120px; }
            .value { color: #555; }
            .summary { background: #fff3e0; padding: 15px; border-left: 4px solid #E2AD2A; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; background: #f9f9f9; color: #666; font-size: 12px; }
            .button { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #E9437A, #E2AD2A); color: white; text-decoration: none; border-radius: 25px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Collaboration Accepted!</h1>
                <p style='margin: 10px 0 0; font-size: 14px; opcity: 0.9;'>Great news for your collaboration request</p>
            </div>
            <div class='content'>
                <p>Exciting update! An influencer has accepted your collaboration request.</p>
                
                <div class='details'>
                    <div class='detail-row'>
                        <span class='label'>Collaboration:</span>
                        <span class='value'>{$collab['collab_title']}</span>
                    </div>
                    <div class='detail-row'>
                        <span class='label'>Category:</span>
                        <span class='value'>{$collab['category']}</span>
                    </div>
                    <div class='detail-row'>
                        <span class='label'>Influencer:</span>
                        <span class='value'>{$influencer_name}</span>
                    </div>
                    <div class='detail-row'>
                        <span class='label'>Email:</span>
                        <span class='value'>{$influencer_email}</span>
                    </div>
                    <div class='detail-row'>
                        <span class='label'>Reference ID:</span>
                        <span class='value'>{$influencer_qr_id}</span>
                    </div>
                    <div class='detail-row'>
                        <span class='label'>Financial:</span>
                        <span class='value'>{$financial_text}</span>
                    </div>
                </div>
                
                <div class='summary'>
                    <strong>📋 Collaboration Summary:</strong><br>
                    {$collab['detailed_summary']}
                </div>
                
                <p><strong>Next Steps:</strong></p>
                <ol>
                    <li>Reach out to the influencer at the email above</li>
                    <li>Share product details and campaign materials</li>
                    <li>Discuss timeline and deliverables</li>
                    <li>Track progress and provide support</li>
                </ol>
                
                <p style='margin-top: 20px;'>Thank you for using our Influencer Collaboration platform!</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " Zokli. All rights reserved.</p>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Send to Brand
    if (!empty($collab['brand_email'])) {
        @mail($collab['brand_email'], $subject, $message, $headers);
    }
    
    // Send to Influencer
    if (!empty($influencer_email)) {
        $influencer_message = str_replace(
            'An influencer has accepted your collaboration request',
            'You have successfully accepted a collaboration opportunity',
            $message
        );
        @mail($influencer_email, $subject, $influencer_message, $headers);
    }
    
    // Send to Admin
    @mail($admin_email, $subject, $message, $headers);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Influencer Program - Zokli</title>
    <link rel="icon" href="../assets/logo2.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php include('../components/csslinks.php') ?>
    
    <style>
        .copy-btn {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            margin-left: 8px;
            font-size: 0.9rem;
            transition: color 0.2s;
        }
        .copy-btn:hover { color: #E9437A; }
        
        .copy-btn-sm {
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            margin-left: 5px;
            font-size: 0.8rem;
        }
        .copy-btn-sm:hover { color: #E9437A; }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html { background: #0f172a; min-height: 100vh; font-family: 'Inter', sans-serif; color: #e2e8f0; }
        
        .page-container { padding: 40px 20px 100px; max-width: 1400px; margin: 0 auto; }
        
        /* Hero Section */
        .hero-section {
            text-align: center;
            padding: 60px 20px;
            background: linear-gradient(135deg, rgba(233, 67, 122, 0.1) 0%, rgba(226, 173, 42, 0.1) 100%);
            border-radius: 24px;
            margin-bottom: 50px;
            border: 1px solid rgba(233, 67, 122, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(233, 67, 122, 0.08) 0%, transparent 70%);
            animation: pulse-rotate 15s linear infinite;
        }
        
        @keyframes pulse-rotate {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(180deg); }
        }
        
        .hero-badge {
            display: inline-block;
            padding: 8px 20px;
            background: rgba(233, 67, 122, 0.2);
            border: 1px solid rgba(233, 67, 122, 0.4);
            border-radius: 50px;
            color: #E9437A;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .hero-title {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            z-index: 1;
        }
        
        .hero-subtitle {
            font-size: 18px;
            color: #94a3b8;
            max-width: 700px;
            margin: 0 auto 30px;
            line-height: 1.7;
            position: relative;
            z-index: 1;
        }
        
        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 30px;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: 800;
            background: linear-gradient(135deg, #E9437A, #E2AD2A);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: block;
        }
        
        .stat-label {
            font-size: 14px;
            color: #64748b;
            margin-top: 5px;
        }
        
        /* Tabs */
        .tabs-container {
            display: flex;
            gap: 15px;
            margin-bottom: 40px;
            background: rgba(30, 41, 59, 0.6);
            padding: 8px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .tab-btn {
            flex: 1;
            padding: 16px 24px;
            background: transparent;
            border: none;
            border-radius: 12px;
            color: #94a3b8;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .tab-btn:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #e2e8f0;
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(233, 67, 122, 0.3);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Collab Cards Grid */
        .collabs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 30px;
        }
        
        .collab-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        
        .collab-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(233, 67, 122, 0.25);
            border-color: rgba(233, 67, 122, 0.4);
        }
        
        /* Product Photos Grid */
        .product-photos {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            padding: 15px;
            background: rgba(15, 23, 42, 0.6);
        }
        
        .product-photo {
            aspect-ratio: 1;
            border-radius: 12px;
            overflow: hidden;
            background: rgba(30, 41, 59, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .product-photo:hover {
            transform: scale(1.05);
        }
        
        .product-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-placeholder {
            color: #475569;
            font-size: 28px;
        }
        
        .download-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .product-photo:hover .download-overlay {
            opacity: 1;
        }
        
        .download-btn {
            color: white;
            font-size: 24px;
        }
        
        /* Card Content */
        .collab-content {
            padding: 25px;
        }
        
        .collab-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            gap: 15px;
        }
        
        .collab-title {
            font-size: 20px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 8px;
        }
        
        .category-badge {
            display: inline-block;
            padding: 5px 14px;
            background: rgba(233, 67, 122, 0.2);
            color: #E9437A;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-badge {
            padding: 7px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
        }
        
        .status-badge.pending {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }
        
        .status-badge.active {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
        }
        
        .status-badge.completed {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }
        
        .collab-description {
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.7;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 10px;
            margin-bottom: 15px;
            text-decoration: none;
            color: #60a5fa;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .product-link:hover {
            background: rgba(59, 130, 246, 0.2);
            transform: translateX(5px);
        }
        
        .financial-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: linear-gradient(135deg, rgba(226, 173, 42, 0.15), rgba(233, 67, 122, 0.15));
            border-radius: 12px;
            margin-bottom: 15px;
            border: 1px solid rgba(226, 173, 42, 0.3);
        }
        
        .financial-label {
            font-size: 13px;
            color: #94a3b8;
            text-transform: uppercase;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .financial-value {
            font-size: 20px;
            font-weight: 800;
            background: linear-gradient(135deg, #E2AD2A, #E9437A);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .collab-summary {
            font-size: 13px;
            color: #cbd5e1;
            line-height: 1.6;
            margin-bottom: 15px;
            padding: 15px;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 10px;
            max-height: 100px;
            overflow-y: auto;
        }
        
        .collab-summary::-webkit-scrollbar {
            width: 6px;
        }
        
        .collab-summary::-webkit-scrollbar-thumb {
            background: rgba(233, 67, 122, 0.5);
            border-radius: 3px;
        }
        
        .accept-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .accept-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
        }
        
        .accepted-badge {
            width: 100%;
            padding: 14px;
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
            border-radius: 12px;
            text-align: center;
            font-weight: 600;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #64748b;
        }
        
        .empty-state i {
            font-size: 72px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
   color: #94a3b8;
        }
        
        .empty-state p {
            font-size: 16px;
        }
        
        /* Toast */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 18px 28px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 500;
            z-index: 5000;
            transform: translateX(150%);
            transition: transform 0.4s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .toast.show {
            transform: translateX(0);
        }
        
        .toast.success {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }
        
        .toast.error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 32px;
            }
            
            .hero-subtitle {
                font-size: 16px;
            }
            
            .tabs-container {
                flex-direction: column;
            }
            
            .collabs-grid {
                grid-template-columns: 1fr;
            }
            
            .hero-stats {
                gap: 20px;
            }
            
            .stat-number {
                font-size: 28px;
            }
        }
        
        /* Create Collaboration Button */
        .create-collab-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .create-collab-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
        }
        
        /* Create Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal-overlay.show { display: flex; }
        
        .modal {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            border-radius: 16px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid rgba(233, 67, 122, 0.3);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            display: block !important;
            position: relative;
            z-index: 10001;
        }
        
        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
            background: linear-gradient(135deg, #E9437A, #E2AD2A);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .modal-body { padding: 25px; }
        
        .form-group { margin-bottom: 18px; }
        
        .form-group label {
            display: block;
            font-size: 0.9rem;
            color: #94a3b8;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px 14px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #e2e8f0;
            font-size: 14px;
            font-family: inherit;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #E9437A;
        }
        
        .form-textarea { min-height: 100px; resize: vertical; }
        
        .photo-uploads {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        
        .photo-upload {
            aspect-ratio: 1;
            border: 2px dashed rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }
        
        .photo-upload:hover { border-color: #E9437A; }
        
        .photo-upload input { display: none; }
        
        .photo-upload i { font-size: 24px; color: #475569; }
        .photo-upload span { font-size: 11px; color: #475569; margin-top: 5px; }
        
        .photo-upload img {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            gap: 12px;
        }
        
        .btn-cancel {
            flex: 1;
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #94a3b8;
            font-size: 15px;
            cursor: pointer;
        }
        
        .btn-submit {
            flex: 2;
            padding: 12px;
            background: linear-gradient(135deg, #E9437A, #E2AD2A);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .btn-submit:hover { opacity: 0.9; }
        
        .delete-btn {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid #ef4444;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            margin-top: 10px;
        }
        
        .delete-btn:hover {
            background: #ef4444;
            color: white;
        }
    </style>
</head>
<body class="dark-scheme de-grey">
    <div id="wrapper">
        <!-- Header -->
        <?php include('../components/header.php'); ?>
        
        <!-- Content -->
        <div class="no-bottom no-top" id="content">
            <div class="page-container" style="margin-top:10%;">
                <!-- Hero Section -->
                <div class="hero-section">
                    <div class="hero-badge">
                        <i class="fas fa-star"></i> Exclusive Opportunities
                    </div>
                    <h1 class="hero-title">
                        <i class="fas fa-handshake"></i> Influencer Program
                    </h1>
                    <p class="hero-subtitle">
                        Partner with top brands, create amazing content, and get rewarded for your influence. 
                        Join thousands of influencers who are building their personal brand with Zokli.
                    </p>
                    <!-- Debug: User Type = <?php echo $user_type; ?>, Is Biz = <?php echo $is_biz_user ? 'true' : 'false'; ?> -->
                    <?php if ($is_biz_user): ?>
                    <button class="create-collab-btn" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> Create Collaboration
                    </button>
                    <?php endif; ?>
                    <div class="hero-stats">
                        <div class="stat-item">
                            <span class="stat-number" id="total-collabs">0</span>
                            <span class="stat-label">Available Collaborations</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number" id="your-collabs">0</span>
                            <span class="stat-label">Your Active Collabs</span>
                        </div>
                        <?php if ($is_biz_user): ?>
                        <div class="stat-item">
                            <span class="stat-number" id="created-collabs">0</span>
                            <span class="stat-label">Your Created Collabs</span>
                        </div>
                        <?php else: ?>
                        <div class="stat-item">
                            <span class="stat-number">₹50K+</span>
                            <span class="stat-label">Potential Earnings</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Tabs -->
                <div class="tabs-container">
                    <button class="tab-btn active" onclick="showTab('available')">
                        <i class="fas fa-store"></i>
                        Available
                    </button>
                    <button class="tab-btn" onclick="showTab('my_collabs')">
                        <i class="fas fa-briefcase"></i>
                        My Accepted
                    </button>
                    <?php if ($is_biz_user): ?>
                    <button class="tab-btn" onclick="showTab('my_created')">
                        <i class="fas fa-edit"></i>
                        My Created
                    </button>
                    <?php endif; ?>
                </div>
                
                <!-- Tab Content: Available -->
                <div class="tab-content active" id="tab-available">
                    <div class="collabs-grid" id="available-grid">
                        <div class="empty-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            <h3>Loading opportunities...</h3>
                        </div>
                    </div>
                </div>
                
                <!-- Tab Content: My Collabs -->
                <div class="tab-content" id="tab-my_collabs">
                    <div class="collabs-grid" id="my-collabs-grid">
                        <div class="empty-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            <h3>Loading your collaborations...</h3>
                        </div>
                    </div>
                </div>
                
                <!-- Tab Content: My Created (Biz users only) -->
                <?php if ($is_biz_user): ?>
                <div class="tab-content" id="tab-my_created">
                    <div class="collabs-grid" id="my-created-grid">
                        <div class="empty-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            <h3>Loading your created collaborations...</h3>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
        
        <!-- Footer -->
        <?php include('../components/footer.php'); ?>
    </div>
    
    <!-- Create Collaboration Modal (Biz users only) -->
    <?php if ($is_biz_user): ?>
    <div class="modal-overlay" id="createModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Create Collaboration</h3>
                <button class="modal-close" onclick="closeCreateModal()">&times;</button>
            </div>
            <form id="createCollabForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Collaboration Title *</label>
                        <input type="text" class="form-input" name="collab_title" required placeholder="e.g., Summer Skincare Campaign">
                    </div>
                    
                    <div class="form-group">
                        <label>Category *</label>
                        <select class="form-select" name="category" id="categorySelect" required onchange="toggleOtherCategory(this)">
                            <option value="">Select Category</option>
                            <option value="lifestyle">Lifestyle</option>
                            <option value="skincare">Skincare</option>
                            <option value="haircare">Haircare</option>
                            <option value="fashion">Fashion</option>
                            <option value="fitness">Fitness</option>
                            <option value="food">Food & Beverage</option>
                            <option value="tech">Technology</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="otherCategoryGroup" style="display: none;">
                        <label>Specify Category *</label>
                        <input type="text" class="form-input" name="other_category" placeholder="Enter category name">
                    </div>
                    
                    <div class="form-group">
                        <label>Product Description *</label>
                        <textarea class="form-textarea" name="product_description" required placeholder="Describe your product..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Product Link (optional)</label>
                        <input type="url" class="form-input" name="product_link" placeholder="https://example.com/product">
                    </div>
                    
                    <div class="form-group">
                        <label>Product Photos (up to 3)</label>
                        <div class="photo-uploads">
                            <label class="photo-upload" id="upload1">
                                <input type="file" name="photo_1" accept="image/*" onchange="previewPhoto(this, 'upload1')">
                                <i class="fas fa-camera"></i>
                                <span>Photo 1</span>
                            </label>
                            <label class="photo-upload" id="upload2">
                                <input type="file" name="photo_2" accept="image/*" onchange="previewPhoto(this, 'upload2')">
                                <i class="fas fa-camera"></i>
                                <span>Photo 2</span>
                            </label>
                            <label class="photo-upload" id="upload3">
                                <input type="file" name="photo_3" accept="image/*" onchange="previewPhoto(this, 'upload3')">
                                <i class="fas fa-camera"></i>
                                <span>Photo 3</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Financial Type</label>
                        <select class="form-select" name="financial_type" onchange="toggleAmount(this)">
                            <option value="barter">Barter (Product Exchange)</option>
                            <option value="paid">Paid Collaboration</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="amountGroup" style="display: none;">
                        <label>Payment Amount (₹)</label>
                        <input type="number" class="form-input" name="financial_amount" placeholder="Enter amount">
                    </div>
                    
                    <div class="form-group">
                        <label>Detailed Requirements *</label>
                        <textarea class="form-textarea" name="detailed_summary" required placeholder="Deliverables, timeline, expectations..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeCreateModal()">Cancel</button>
                    <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Create</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="toast" id="toast"></div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentTab = 'available';
        const userId = <?php echo $user_id; ?>;
        const isBizUser = <?php echo $is_biz_user ? 'true' : 'false'; ?>;
        
        $(document).ready(function() {
            loadCollabs('available');
            <?php if ($is_biz_user): ?>
            loadMyCreated();
            <?php endif; ?>
        });
        
        // Tab Management
        function showTab(tab) {
            currentTab = tab;
            
            // Update buttons
            $('.tab-btn').removeClass('active');
            $(`.tab-btn`).each(function() {
                const text = $(this).text().trim().toLowerCase();
                if ((tab === 'available' && text.includes('available')) ||
                    (tab === 'my_collabs' && text.includes('accepted')) ||
                    (tab === 'my_created' && text.includes('created'))) {
                    $(this).addClass('active');
                }
            });
            
            // Update content
            $('.tab-content').removeClass('active');
            $(`#tab-${tab}`).addClass('active');
            
            // Load data
            if (tab === 'my_created') {
                loadMyCreated();
            } else {
                loadCollabs(tab);
            }
        }
        
        // Load Collaborations
        function loadCollabs(status) {
            $.post('', {
                action: 'get_collabs',
                status: status
            }, function(response) {
                if (response.status && response.data.length > 0) {
                    renderCollabs(response.data, status);
                    updateStats(response.data, status);
                } else {
                    const emptyMessage = status === 'available' 
                        ? 'No collaborations available at the moment. Check back soon!' 
                        : "You haven't accepted any collaborations yet. Browse available opportunities!";
                    
                    $(`#${status === 'available' ? 'available' : 'my-collabs'}-grid`).html(`
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>No Collaborations</h3>
                            <p>${emptyMessage}</p>
                        </div>
                    `);
                }
            }, 'json').fail(function() {
                showToast('Failed to load collaborations', 'error');
            });
        }
        
        // Render Collaborations
        function renderCollabs(collabs, status) {
            let html = '';
            
            collabs.forEach(c => {
                const photo1 = c.photo_1 ? `<img src="../../../${c.photo_1}" alt="Product">` : '<i class="fas fa-image photo-placeholder"></i>';
                const photo2 = c.photo_2 ? `<img src="../../../${c.photo_2}" alt="Product">` : '<i class="fas fa-image photo-placeholder"></i>';
                const photo3 = c.photo_3 ? `<img src="../../../${c.photo_3}" alt="Product">` : '<i class="fas fa-image photo-placeholder"></i>';
                
                const financialText = c.financial_type === 'paid' 
                    ? `₹${parseFloat(c.financial_amount).toLocaleString()}` 
                    : 'Barter';
                
                const financialIcon = c.financial_type === 'paid' ? 'money-bill-wave' : 'handshake';
                
                const isAccepted = status === 'my_collabs';
                
                html += `
                    <div class="collab-card">
                        <div class="product-photos">
                            <div class="product-photo" onclick="downloadPhoto('../../../${c.photo_1}')">
                                ${photo1}
                                ${c.photo_1 ? '<div class="download-overlay"><i class="fas fa-download download-btn"></i></div>' : ''}
                            </div>
                            <div class="product-photo" onclick="downloadPhoto('../../../${c.photo_2}')">
                                ${photo2}
                                ${c.photo_2 ? '<div class="download-overlay"><i class="fas fa-download download-btn"></i></div>' : ''}
                            </div>
                            <div class="product-photo" onclick="downloadPhoto('../../../${c.photo_3}')">
                                ${photo3}
                                ${c.photo_3 ? '<div class="download-overlay"><i class="fas fa-download download-btn"></i></div>' : ''}
                            </div>
                        </div>
                        
                        <div class="collab-content">
                            <div class="collab-header">
                                <div>
                                    <div class="collab-title">
                                        ${escapeHtml(c.collab_title)}
                                        <button class="copy-btn" onclick="copyToClipboard('${escapeHtml(c.collab_title.replace(/'/g, "\\'"))}')" title="Copy Title">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <span class="category-badge">${c.category === 'other' ? (c.other_category || 'Other') : c.category}</span>
                                </div>
                                <span class="status-badge ${c.status}">${c.status}</span>
                            </div>
                            
                            <div class="collab-description">
                                ${escapeHtml(c.product_description)}
                                <button class="copy-btn-sm" onclick="copyToClipboard('${escapeHtml(c.product_description.replace(/'/g, "\\'"))}')" title="Copy Description">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            
                            ${c.product_link ? `
                                <a href="${c.product_link}" target="_blank" class="product-link">
                                    <i class="fas fa-external-link-alt"></i>
                                    View Product Page
                                </a>
                            ` : ''}
                            
                            <div class="financial-box">
                                <span class="financial-label">
                                    <i class="fas fa-${financialIcon}"></i>
                                    ${c.financial_type === 'paid' ? 'Payment' : 'Barter Deal'}
                                </span>
                                <span class="financial-value">${financialText}</span>
                            </div>
                            
                            <div class="collab-summary">
                                <strong>📋 Requirements:</strong><br>
                                ${escapeHtml(c.detailed_summary)}
                            </div>
                            
                            ${isAccepted ? `
                                <div class="accepted-badge">
                                    <i class="fas fa-check-circle"></i> You accepted this collaboration
                                </div>
                            ` : `
                                <button class="accept-button" onclick="acceptCollab(${c.id})">
                                    <i class="fas fa-handshake"></i>
                                    Accept Collaboration
                                </button>
                            `}
                        </div>
                    </div>
                `;
            });
            
            $(`#${status === 'available' ? 'available' : 'my-collabs'}-grid`).html(html);
        }
        
        // Update Stats
        function updateStats(collabs, status) {
            if (status === 'available') {
                $('#total-collabs').text(collabs.length);
            } else {
                $('#your-collabs').text(collabs.length);
            }
        }
        
        // Accept Collaboration
        function acceptCollab(id) {
            if (!confirm('Are you sure you want to accept this collaboration? You will be committed to delivering the requirements.')) {
                return;
            }
            
            $.post('', {
                action: 'accept_collab',
                collab_id: id
            }, function(response) {
                if (response.status) {
                    showToast(response.message, 'success');
                    loadCollabs(currentTab);
                    // Reload the other tab's data too
                    if (currentTab === 'available') {
                        $.post('', { action: 'get_collabs', status: 'my_collabs' }, function(r) {
                            if (r.status) updateStats(r.data, 'my_collabs');
                        }, 'json');
                    }
                } else {
                    showToast(response.message, 'error');
                }
            }, 'json').fail(function() {
                showToast('Failed to accept collaboration', 'error');
            });
        }
        
        // Download Photo
        function downloadPhoto(url) {
            if (!url || url.includes('null') || url.includes('undefined')) return;
            window.open(url, '_blank');
        }
        
        // Copy to Clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('Copied to clipboard!', 'success');
            }).catch(err => {
                showToast('Failed to copy', 'error');
            });
        }

        // Toast Notification
        function showToast(message, type) {
            const toast = $('#toast');
            toast.removeClass('success error').addClass(type).text(message).addClass('show');
            setTimeout(() => toast.removeClass('show'), 4000);
        }
        
        // Utility Functions
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
        }
        
        // ===== BIZ USER FUNCTIONS =====
        <?php if ($is_biz_user): ?>
        
        // Open Create Modal
        function openCreateModal() {
            $('#createCollabForm')[0].reset();
            $('.photo-upload img').remove();
            $('#amountGroup').hide();
            $('#createModal').addClass('show');
        }
        
        function closeCreateModal() {
            $('#createModal').removeClass('show');
        }
        
        // Toggle Amount Field
        function toggleAmount(select) {
            $('#amountGroup').toggle(select.value === 'paid');
        }

        // Toggle Other Category
        function toggleOtherCategory(select) {
            if (select.value === 'other') {
                $('#otherCategoryGroup').show();
                $('input[name="other_category"]').prop('required', true);
            } else {
                $('#otherCategoryGroup').hide();
                $('input[name="other_category"]').prop('required', false);
            }
        }
        
        // Preview Photo
        function previewPhoto(input, uploadId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const container = $('#' + uploadId);
                    container.find('img').remove();
                    container.prepend(`<img src="${e.target.result}">`);
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Load My Created Collabs
        function loadMyCreated() {
            $.post('', { action: 'get_my_created' }, function(response) {
                if (response.status && response.data.length > 0) {
                    renderMyCreated(response.data);
                    $('#created-collabs').text(response.data.length);
                } else {
                    $('#my-created-grid').html(`
                        <div class="empty-state">
                            <i class="fas fa-edit"></i>
                            <h3>No Created Collaborations</h3>
                            <p>Click "Create Collaboration" to get started!</p>
                        </div>
                    `);
                    $('#created-collabs').text('0');
                }
            }, 'json');
        }
        
        // Render My Created Collabs
        function renderMyCreated(collabs) {
            let html = '';
            
            collabs.forEach(c => {
                const photo1 = c.photo_1 ? `<img src="../../../${c.photo_1}" alt="Product">` : '<i class="fas fa-image photo-placeholder"></i>';
                const photo2 = c.photo_2 ? `<img src="../../../${c.photo_2}" alt="Product">` : '<i class="fas fa-image photo-placeholder"></i>';
                const photo3 = c.photo_3 ? `<img src="../../../${c.photo_3}" alt="Product">` : '<i class="fas fa-image photo-placeholder"></i>';
                
                const financialText = c.financial_type === 'paid' 
                    ? `₹${parseFloat(c.financial_amount).toLocaleString()}` 
                    : 'Barter';
                
                const financialIcon = c.financial_type === 'paid' ? 'money-bill-wave' : 'handshake';
                
                html += `
                    <div class="collab-card">
                        <div class="product-photos">
                            <div class="product-photo">${photo1}</div>
                            <div class="product-photo">${photo2}</div>
                            <div class="product-photo">${photo3}</div>
                        </div>
                        
                        <div class="collab-content">
                            <div class="collab-header">
                                <div>
                                    <div class="collab-title">${escapeHtml(c.collab_title)}</div>
                                    <span class="category-badge">${c.category}</span>
                                </div>
                                <span class="status-badge ${c.status}">${c.status}</span>
                            </div>
                            
                            <div class="collab-description">${escapeHtml(c.product_description)}</div>
                            
                            <div class="financial-box">
                                <span class="financial-label">
                                    <i class="fas fa-${financialIcon}"></i>
                                    ${c.financial_type === 'paid' ? 'Payment' : 'Barter Deal'}
                                </span>
                                <span class="financial-value">${financialText}</span>
                            </div>
                            
                            ${c.status === 'pending' ? `
                                <button class="delete-btn" onclick="deleteCollab(${c.id})">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            ` : `
                                <div style="color: #4ade80; font-size: 13px;">
                                    <i class="fas fa-check-circle"></i> Accepted by influencer
                                </div>
                            `}
                        </div>
                    </div>
                `;
            });
            
            $('#my-created-grid').html(html);
        }
        
        // Delete Collaboration
        function deleteCollab(id) {
            if (!confirm('Are you sure you want to delete this collaboration?')) return;
            
            $.post('', { action: 'delete_collab', collab_id: id }, function(response) {
                if (response.status) {
                    showToast(response.message, 'success');
                    loadMyCreated();
                    loadCollabs('available');
                } else {
                    showToast(response.message, 'error');
                }
            }, 'json');
        }
        
        // Submit Create Form
        $('#createCollabForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create_collab');
            
            $.ajax({
                url: '',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status) {
                        showToast(response.message, 'success');
                        closeCreateModal();
                        loadMyCreated();
                        loadCollabs('available');
                    } else {
                        showToast(response.message, 'error');
                    }
                },
                error: function() {
                    showToast('Failed to create collaboration', 'error');
                }
            });
        });
        
        // Close modal on overlay click
        $('#createModal').on('click', function(e) {
            if (e.target === this) closeCreateModal();
        });
        
        <?php endif; ?>
    </script>
</body>
</html>
