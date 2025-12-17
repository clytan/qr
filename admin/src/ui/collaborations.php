<?php
// Include auth check
require_once('../components/auth_check.php');
require_once('../backend/dbconfig/connection.php');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'create_collab':
            try {
                $collab_title = trim($_POST['collab_title'] ?? '');
                $category = trim($_POST['category'] ?? '');
                $product_description = trim($_POST['product_description'] ?? '');
                $product_link = trim($_POST['product_link'] ?? '');
                $financial_type = trim($_POST['financial_type'] ?? 'barter'); // barter or paid
                $financial_amount = trim($_POST['financial_amount'] ?? '0');
                $detailed_summary = trim($_POST['detailed_summary'] ?? '');
                $brand_email = trim($_POST['brand_email'] ?? '');
                $admin_id = $_SESSION['admin_id'] ?? 1;
                
                if (empty($collab_title) || empty($category)) {
                    echo json_encode(['status' => false, 'message' => 'Title and category are required']);
                    exit();
                }
                
                // Handle file uploads (up to 3 product photos)
                $uploaded_photos = [];
                $upload_dir = '../../../uploads/collabs/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                for ($i = 1; $i <= 3; $i++) {
                    if (isset($_FILES["product_photo_$i"]) && $_FILES["product_photo_$i"]['error'] === UPLOAD_ERR_OK) {
                        $file_tmp = $_FILES["product_photo_$i"]['tmp_name'];
                        $file_name = time() . "_$i_" . basename($_FILES["product_photo_$i"]['name']);
                        $file_path = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($file_tmp, $file_path)) {
                            $uploaded_photos[] = 'uploads/collabs/' . $file_name;
                        }
                    }
                }
                
                $photo_1 = $uploaded_photos[0] ?? null;
                $photo_2 = $uploaded_photos[1] ?? null;
                $photo_3 = $uploaded_photos[2] ?? null;
                
                $sql = "INSERT INTO influencer_collabs (
                    collab_title, category, product_description, product_link,
                    photo_1, photo_2, photo_3,
                    financial_type, financial_amount, detailed_summary,
                    brand_email, status, created_by, created_on
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    'ssssssssdssi',
                    $collab_title, $category, $product_description, $product_link,
                    $photo_1, $photo_2, $photo_3,
                    $financial_type, $financial_amount, $detailed_summary,
                    $brand_email, $admin_id
                );
                
                if ($stmt->execute()) {
                    echo json_encode(['status' => true, 'message' => 'Collaboration request created successfully']);
                } else {
                    echo json_encode(['status' => false, 'message' => 'Failed to create collaboration: ' . $stmt->error]);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'get_collabs':
            try {
                $status_filter = $_POST['status'] ?? 'all';
                
                $sql = "SELECT c.*, 
                        u.user_full_name as influencer_name, 
                        u.user_email as influencer_email
                        FROM influencer_collabs c
                        LEFT JOIN user_user u ON c.accepted_by = u.id
                        WHERE c.is_deleted = 0";
                
                if ($status_filter !== 'all') {
                    $sql .= " AND c.status = ?";
                }
                
                $sql .= " ORDER BY c.created_on DESC";
                
                if ($status_filter !== 'all') {
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('s', $status_filter);
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
                $influencer_id = intval($_POST['influencer_id'] ?? 0);
                
                if ($collab_id <= 0 || $influencer_id <= 0) {
                    echo json_encode(['status' => false, 'message' => 'Invalid collaboration or influencer ID']);
                    exit();
                }
                
                // Get collab details
                $stmt = $conn->prepare("SELECT * FROM influencer_collabs WHERE id = ? AND is_deleted = 0");
                $stmt->bind_param('i', $collab_id);
                $stmt->execute();
                $collab = $stmt->get_result()->fetch_assoc();
                
                if (!$collab) {
                    echo json_encode(['status' => false, 'message' => 'Collaboration not found']);
                    exit();
                }
                
                // Get influencer details
                $stmt = $conn->prepare("SELECT * FROM user_user WHERE id = ?");
                $stmt->bind_param('i', $influencer_id);
                $stmt->execute();
                $influencer = $stmt->get_result()->fetch_assoc();
                
                // Update collab status
                $stmt = $conn->prepare("UPDATE influencer_collabs SET status = 'active', accepted_by = ?, accepted_on = NOW() WHERE id = ?");
                $stmt->bind_param('ii', $influencer_id, $collab_id);
                
                if ($stmt->execute()) {
                    // Send emails to all 3 parties
                    $emails_sent = sendCollabAcceptanceEmails($collab, $influencer);
                    
                    echo json_encode([
                        'status' => true, 
                        'message' => 'Collaboration accepted successfully',
                        'emails_sent' => $emails_sent
                    ]);
                } else {
                    echo json_encode(['status' => false, 'message' => 'Failed to accept collaboration']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'complete_collab':
            try {
                $collab_id = intval($_POST['collab_id'] ?? 0);
                
                if ($collab_id <= 0) {
                    echo json_encode(['status' => false, 'message' => 'Invalid collaboration ID']);
                    exit();
                }
                
                $stmt = $conn->prepare("UPDATE influencer_collabs SET status = 'completed', completed_on = NOW() WHERE id = ?");
                $stmt->bind_param('i', $collab_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['status' => true, 'message' => 'Collaboration marked as completed']);
                } else {
                    echo json_encode(['status' => false, 'message' => 'Failed to complete collaboration']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'delete_collab':
            try {
                $collab_id = intval($_POST['collab_id'] ?? 0);
                
                if ($collab_id <= 0) {
                    echo json_encode(['status' => false, 'message' => 'Invalid collaboration ID']);
                    exit();
                }
                
                $stmt = $conn->prepare("UPDATE influencer_collabs SET is_deleted = 1 WHERE id = ?");
                $stmt->bind_param('i', $collab_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['status' => true, 'message' => 'Collaboration deleted successfully']);
                } else {
                    echo json_encode(['status' => false, 'message' => 'Failed to delete collaboration']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
    }
}

// Email function
function sendCollabAcceptanceEmails($collab, $influencer) {
    $admin_email = "admin@yourcompany.com"; // Change this
    
    $subject = "Collaboration Accepted: " . $collab['collab_title'];
    $headers = "From: noreply@yourcompany.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // Email content
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #E9437A, #E2AD2A); color: white; padding: 20px; border-radius: 10px; }
            .content { background: #f9f9f9; padding: 20px; margin-top: 20px; border-radius: 10px; }
            .details { margin: 15px 0; }
            .label { font-weight: bold; color: #E9437A; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🎉 Collaboration Accepted!</h2>
            </div>
            <div class='content'>
                <p>Great news! The collaboration has been accepted.</p>
                <div class='details'>
                    <p><span class='label'>Collaboration:</span> {$collab['collab_title']}</p>
                    <p><span class='label'>Category:</span> {$collab['category']}</p>
                    <p><span class='label'>Influencer:</span> {$influencer['user_full_name']} ({$influencer['user_email']})</p>
                    <p><span class='label'>Financial:</span> " . ($collab['financial_type'] === 'paid' ? '₹' . $collab['financial_amount'] : 'Barter') . "</p>
                    <p><span class='label'>Product Link:</span> <a href='{$collab['product_link']}'>{$collab['product_link']}</a></p>
                </div>
                <p><strong>Summary:</strong><br>{$collab['detailed_summary']}</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $emails_sent = 0;
    
    // Email to Brand
    if (!empty($collab['brand_email'])) {
        if (mail($collab['brand_email'], $subject, $message, $headers)) {
            $emails_sent++;
        }
    }
    
    // Email to Influencer
    if (!empty($influencer['user_email'])) {
        if (mail($influencer['user_email'], $subject, $message, $headers)) {
            $emails_sent++;
        }
    }
    
    // Email to Admin
    if (mail($admin_email, $subject, $message, $headers)) {
        $emails_sent++;
    }
    
    return $emails_sent;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Influencer Collaborations - Admin Panel</title>
    <link rel="icon" href="../../assets/logo2.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html { background: #0f172a; min-height: 100vh; font-family: 'Inter', sans-serif; color: #e2e8f0; }
        
        .page-content { padding: 30px; }
        
        /* Header Section */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 25px;
            background: linear-gradient(135deg, rgba(233, 67, 122, 0.1) 0%, rgba(226, 173, 42, 0.1) 100%);
            border-radius: 16px;
            border: 1px solid rgba(233, 67, 122, 0.2);
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .create-btn {
            padding: 14px 28px;
            background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 20px rgba(233, 67, 122, 0.3);
            transition: all 0.3s ease;
        }
        
        .create-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(233, 67, 122, 0.4);
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            background: rgba(30, 41, 59, 0.6);
            padding: 8px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .tab-btn {
            flex: 1;
            padding: 14px 24px;
            background: transparent;
            border: none;
            border-radius: 10px;
            color: #94a3b8;
            font-size: 15px;
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
        
        /* Banners Section */
        .banners-section {
            margin-bottom: 35px;
        }
        
        .banners-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .banner-card {
            background: linear-gradient(135deg, rgba(233, 67, 122, 0.15) 0%, rgba(226, 173, 42, 0.15) 100%);
            border: 2px solid rgba(233, 67, 122, 0.3);
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.4s ease;
        }
        
        .banner-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(233, 67, 122, 0.1) 0%, transparent 70%);
            animation: pulse-glow 4s ease-in-out infinite;
        }
        
        @keyframes pulse-glow {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        .banner-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(233, 67, 122, 0.3);
            border-color: rgba(233, 67, 122, 0.5);
        }
        
        .banner-icon {
            font-size: 48px;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #E9437A, #E2AD2A);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            z-index: 1;
        }
        
        .banner-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #f1f5f9;
            position: relative;
            z-index: 1;
        }
        
        .banner-description {
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }
        
        .banner-number {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 60px;
            font-weight: 900;
            background: linear-gradient(135deg, rgba(233, 67, 122, 0.2), rgba(226, 173, 42, 0.2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            opacity: 0.3;
        }
        
        /* Collabs Grid */
        .collabs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
        }
        
        .collab-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .collab-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(233, 67, 122, 0.2);
            border-color: rgba(233, 67, 122, 0.4);
        }
        
        /* Product Photos */
        .product-photos {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            padding: 15px;
            background: rgba(15, 23, 42, 0.5);
        }
        
        .product-photo {
            aspect-ratio: 1;
            border-radius: 10px;
            overflow: hidden;
            background: rgba(30, 41, 59, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .product-photo:hover {
            transform: scale(1.05);
        }
        
        .product-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-photo .placeholder {
            color: #475569;
            font-size: 24px;
        }
        
        .download-icon {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .product-photo:hover .download-icon {
            opacity: 1;
        }
        
        /* Card Content */
        .collab-content {
            padding: 20px;
        }
        
        .collab-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .collab-title {
            font-size: 18px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 5px;
        }
        
        .collab-category {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(233, 67, 122, 0.2);
            color: #E9437A;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
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
            line-height: 1.6;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 8px;
            margin-bottom: 15px;
            text-decoration: none;
            color: #60a5fa;
            font-size: 13px;
            transition: all 0.3s ease;
        }
        
        .product-link:hover {
            background: rgba(59, 130, 246, 0.2);
            transform: translateX(5px);
        }
        
        .financial-details {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            background: rgba(226, 173, 42, 0.1);
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .financial-label {
            font-size: 12px;
            color: #94a3b8;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .financial-value {
            font-size: 18px;
            font-weight: 700;
            color: #E2AD2A;
        }
        
        .collab-summary {
            font-size: 13px;
            color: #cbd5e1;
            line-height: 1.6;
            margin-bottom: 15px;
            padding: 12px;
            background: rgba(15, 23, 42, 0.5);
            border-radius: 8px;
            max-height: 80px;
            overflow-y: auto;
        }
        
        .collab-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 12px;
            color: #64748b;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        /* Action Buttons */
        .collab-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            flex: 1;
            min-width: 120px;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .action-btn.accept {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }
        
        .action-btn.accept:hover {
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4);
            transform: translateY(-2px);
        }
        
        .action-btn.complete {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }
        
        .action-btn.complete:hover {
            background: rgba(59, 130, 246, 0.3);
        }
        
        .action-btn.delete {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
        
        .action-btn.delete:hover {
            background: rgba(239, 68, 68, 0.3);
        }
        
        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 3000;
            padding: 20px;
            backdrop-filter: blur(5px);
        }
        
        .modal-overlay.show {
            display: flex;
        }
        
        .modal {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            border-radius: 20px;
            width: 100%;
            max-width: 650px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 35px;
            border: 1px solid rgba(233, 67, 122, 0.3);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        .modal-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 25px;
            background: linear-gradient(135deg, #E9437A, #E2AD2A);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 13px 16px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #e2e8f0;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #E9437A;
            box-shadow: 0 0 0 3px rgba(233, 67, 122, 0.1);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .file-upload-area {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        
        .file-upload-box {
            aspect-ratio: 1;
            border: 2px dashed rgba(233, 67, 122, 0.4);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(15, 23, 42, 0.5);
            position: relative;
            overflow: hidden;
        }
        
        .file-upload-box:hover {
            border-color: #E9437A;
            background: rgba(233, 67, 122, 0.1);
        }
        
        .file-upload-box input[type="file"] {
            display: none;
        }
        
        .file-upload-box .upload-icon {
            font-size: 32px;
            color: #E9437A;
            margin-bottom: 8px;
        }
        
        .file-upload-box .upload-text {
            font-size: 11px;
            color: #64748b;
            text-align: center;
        }
        
        .preview-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 14px 24px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn.primary {
            background: linear-gradient(135deg, #E9437A, #E2AD2A);
            color: white;
            box-shadow: 0 4px 15px rgba(233, 67, 122, 0.3);
        }
        
        .btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(233, 67, 122, 0.4);
        }
        
        .btn.secondary {
            background: rgba(255, 255, 255, 0.05);
            color: #94a3b8;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .btn.secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        /* Toast */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 18px 28px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            z-index: 4000;
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
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #64748b;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #94a3b8;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .banners-grid {
                grid-template-columns: 1fr;
            }
            
            .collabs-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include('../components/sidebar.php'); ?>
    
    <main class="admin-main">
        <div class="page-content">
            <!-- Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-handshake"></i>
                    Influencer Collaborations
                </h1>
                <button class="create-btn" onclick="showCreateModal()">
                    <i class="fas fa-plus"></i>
                    Create New Collab
                </button>
            </div>
            
            <!-- Tabs -->
            <div class="tabs">
                <button class="tab-btn active" onclick="showTab('pending')">
                    <i class="fas fa-clock"></i>
                    Pending Requests
                </button>
                <button class="tab-btn" onclick="showTab('active')">
                    <i class="fas fa-fire"></i>
                    Active Collabs
                </button>
                <button class="tab-btn" onclick="showTab('completed')">
                    <i class="fas fa-check-circle"></i>
                    Completed
                </button>
            </div>
            
            <!-- Tab Content: Pending -->
            <div class="tab-content active" id="tab-pending">
                <!-- Banners -->
                <div class="banners-section">
                    <div class="banners-grid">
                        <div class="banner-card">
                            <div class="banner-number">01</div>
                            <div class="banner-icon">
                                <i class="fas fa-rocket"></i>
                            </div>
                            <div class="banner-title">Launch Your Campaign</div>
                            <div class="banner-description">
                                Create compelling collaboration requests with detailed product information and attractive financial offers.
                            </div>
                        </div>
                        <div class="banner-card">
                            <div class="banner-number">02</div>
                            <div class="banner-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="banner-title">Connect with Influencers</div>
                            <div class="banner-description">
                                Top influencers review your requests and choose collaborations that align with their brand and audience.
                            </div>
                        </div>
                        <div class="banner-card">
                            <div class="banner-number">03</div>
                            <div class="banner-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="banner-title">Grow Together</div>
                            <div class="banner-description">
                                Track active collaborations, measure results, and build long-term partnerships that drive real growth.
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Collabs Grid -->
                <div class="collabs-grid" id="pending-collabs">
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h3>Loading...</h3>
                    </div>
                </div>
            </div>
            
            <!-- Tab Content: Active -->
            <div class="tab-content" id="tab-active">
                <!-- Banners -->
                <div class="banners-section">
                    <div class="banners-grid">
                        <div class="banner-card">
                            <div class="banner-number">01</div>
                            <div class="banner-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="banner-title">Premium Partnerships</div>
                            <div class="banner-description">
                                Your active collaborations represent genuine partnerships with influential content creators.
                            </div>
                        </div>
                        <div class="banner-card">
                            <div class="banner-number">02</div>
                            <div class="banner-icon">
                                <i class="fas fa-bullseye"></i>
                            </div>
                            <div class="banner-title">Monitor Progress</div>
                            <div class="banner-description">
                                Stay updated on campaign progress and ensure deliverables meet your brand standards.
                            </div>
                        </div>
                        <div class="banner-card">
                            <div class="banner-number">03</div>
                            <div class="banner-icon">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <div class="banner-title">Build Relationships</div>
                            <div class="banner-description">
                                Foster strong relationships with influencers for future campaigns and brand advocacy.
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Collabs Grid -->
                <div class="collabs-grid" id="active-collabs">
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h3>Loading...</h3>
                    </div>
                </div>
            </div>
            
            <!-- Tab Content: Completed -->
            <div class="tab-content" id="tab-completed">
                <!-- Banners -->
                <div class="banners-section">
                    <div class="banners-grid">
                        <div class="banner-card">
                            <div class="banner-number">01</div>
                            <div class="banner-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="banner-title">Success Stories</div>
                            <div class="banner-description">
                                Review your completed collaborations and celebrate successful campaigns that drove results.
                            </div>
                        </div>
                        <div class="banner-card">
                            <div class="banner-number">02</div>
                            <div class="banner-icon">
                                <i class="fas fa-analytics"></i>
                            </div>
                            <div class="banner-title">Analyze Results</div>
                            <div class="banner-description">
                                Evaluate campaign performance and gather insights for future collaboration strategies.
                            </div>
                        </div>
                        <div class="banner-card">
                            <div class="banner-number">03</div>
                            <div class="banner-icon">
                                <i class="fas fa-infinity"></i>
                            </div>
                            <div class="banner-title">Continuous Growth</div>
                            <div class="banner-description">
                                Use learnings from past collaborations to refine your approach and maximize ROI.
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Collabs Grid -->
                <div class="collabs-grid" id="completed-collabs">
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h3>Loading...</h3>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Create Collaboration Modal -->
    <div class="modal-overlay" id="createModal">
        <div class="modal">
            <h3 class="modal-title">
                <i class="fas fa-plus-circle"></i>
                Create New Collaboration
            </h3>
            <form id="createCollabForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Collaboration Title *</label>
                    <input type="text" class="form-input" name="collab_title" required placeholder="e.g., Summer Skincare Campaign">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <select class="form-select" name="category" required>
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
                
                <div class="form-group">
                    <label class="form-label">Product Description *</label>
                    <textarea class="form-textarea" name="product_description" required placeholder="Describe your product and what makes it special..."></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Product/Campaign Link</label>
                    <input type="url" class="form-input" name="product_link" placeholder="https://yourwebsite.com/product">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Product Photos (Upload up to 3) *</label>
                    <div class="file-upload-area">
                        <label class="file-upload-box" for="photo1">
                            <i class="fas fa-cloud-upload-alt upload-icon"></i>
                            <span class="upload-text">Photo 1</span>
                            <input type="file" id="photo1" name="product_photo_1" accept="image/*" onchange="previewImage(this, 1)">
                            <img class="preview-image" id="preview1" style="display: none;">
                        </label>
                        <label class="file-upload-box" for="photo2">
                            <i class="fas fa-cloud-upload-alt upload-icon"></i>
                            <span class="upload-text">Photo 2</span>
                            <input type="file" id="photo2" name="product_photo_2" accept="image/*" onchange="previewImage(this, 2)">
                            <img class="preview-image" id="preview2" style="display: none;">
                        </label>
                        <label class="file-upload-box" for="photo3">
                            <i class="fas fa-cloud-upload-alt upload-icon"></i>
                            <span class="upload-text">Photo 3</span>
                            <input type="file" id="photo3" name="product_photo_3" accept="image/*" onchange="previewImage(this, 3)">
                            <img class="preview-image" id="preview3" style="display: none;">
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Financial Details</label>
                    <select class="form-select" name="financial_type" onchange="toggleFinancialAmount(this)">
                        <option value="barter">Barter (Product Exchange)</option>
                        <option value="paid">Paid Collaboration</option>
                    </select>
                </div>
                
                <div class="form-group" id="amountGroup" style="display: none;">
                    <label class="form-label">Payment Amount (₹)</label>
                    <input type="number" class="form-input" name="financial_amount" placeholder="Enter amount">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Detailed Collaboration Summary *</label>
                    <textarea class="form-textarea" name="detailed_summary" required placeholder="Provide detailed requirements, deliverables, timeline, and any other important information..."></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Brand Email *</label>
                    <input type="email" class="form-input" name="brand_email" required placeholder="brand@company.com">
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn secondary" onclick="closeModal('createModal')">Cancel</button>
                    <button type="submit" class="btn primary">Create Collaboration</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentTab = 'pending';
        
        $(document).ready(function() {
            loadCollabs('pending');
        });
        
        // Tab Management
        function showTab(tab) {
            currentTab = tab;
            
            // Update tab buttons
            $('.tab-btn').removeClass('active');
            $(`.tab-btn:contains('${tab === 'pending' ? 'Pending' : tab === 'active' ? 'Active' : 'Completed'}')`).addClass('active');
            
            // Update tab content
            $('.tab-content').removeClass('active');
            $(`#tab-${tab}`).addClass('active');
            
            // Load collabs for this tab
            loadCollabs(tab);
        }
        
        // Load Collaborations
        function loadCollabs(status) {
            console.log('Loading collabs for status:', status);
            
            $.post('', { action: 'get_collabs', status: status }, function(response) {
                console.log('Response:', response);
                
                if (response.status && response.data.length > 0) {
                    renderCollabs(response.data, status);
                } else {
                    $(`#${status}-collabs`).html(`
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>No ${status} collaborations</h3>
                            <p>There are no ${status} collaborations at the moment.</p>
                        </div>
                    `);
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Response:', xhr.responseText);
                showToast('Failed to load collaborations: ' + error, 'error');
            });
        }
        
        // Render Collaborations
        function renderCollabs(collabs, status) {
            let html = '';
            
            collabs.forEach(c => {
                const photo1 = c.photo_1 ? `<img src="../../../${c.photo_1}" alt="Product">` : '<i class="fas fa-image placeholder"></i>';
                const photo2 = c.photo_2 ? `<img src="../../../${c.photo_2}" alt="Product">` : '<i class="fas fa-image placeholder"></i>';
                const photo3 = c.photo_3 ? `<img src="../../../${c.photo_3}" alt="Product">` : '<i class="fas fa-image placeholder"></i>';
                
                const financialText = c.financial_type === 'paid' ? `₹${parseFloat(c.financial_amount).toLocaleString()}` : 'Barter';
                
                let actions = '';
                if (c.status === 'pending') {
                    actions = `
                        <button class="action-btn delete" onclick="deleteCollab(${c.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    `;
                } else if (c.status === 'active') {
                    actions = `
                        <button class="action-btn complete" onclick="completeCollab(${c.id})">
                            <i class="fas fa-check"></i> Mark Complete
                        </button>
                    `;
                }
                
                html += `
                    <div class="collab-card">
                        <div class="product-photos">
                            <div class="product-photo" onclick="downloadPhoto('../../../${c.photo_1}')">
                                ${photo1}
                                ${c.photo_1 ? '<div class="download-icon"><i class="fas fa-download"></i></div>' : ''}
                            </div>
                            <div class="product-photo" onclick="downloadPhoto('../../../${c.photo_2}')">
                                ${photo2}
                                ${c.photo_2 ? '<div class="download-icon"><i class="fas fa-download"></i></div>' : ''}
                            </div>
                            <div class="product-photo" onclick="downloadPhoto('../../../${c.photo_3}')">
                                ${photo3}
                                ${c.photo_3 ? '<div class="download-icon"><i class="fas fa-download"></i></div>' : ''}
                            </div>
                        </div>
                        <div class="collab-content">
                            <div class="collab-header">
                                <div>
                                    <div class="collab-title">${escapeHtml(c.collab_title)}</div>
                                    <span class="collab-category">${c.category}</span>
                                </div>
                                <span class="status-badge ${c.status}">${c.status}</span>
                            </div>
                            
                            <div class="collab-description">${escapeHtml(c.product_description)}</div>
                            
                            ${c.product_link ? `
                                <a href="${c.product_link}" target="_blank" class="product-link">
                                    <i class="fas fa-external-link-alt"></i>
                                    View Product Page
                                </a>
                            ` : ''}
                            
                            <div class="financial-details">
                                <span class="financial-label">
                                    <i class="fas fa-${c.financial_type === 'paid' ? 'money-bill-wave' : 'handshake'}"></i>
                                    ${c.financial_type === 'paid' ? 'Payment' : 'Barter'}
                                </span>
                                <span class="financial-value">${financialText}</span>
                            </div>
                            
                            <div class="collab-summary">
                                <strong>Summary:</strong><br>
                                ${escapeHtml(c.detailed_summary)}
                            </div>
                            
                            <div class="collab-meta">
                                <div class="meta-item">
                                    <i class="fas fa-envelope"></i>
                                    ${c.brand_email}
                                </div>
                                ${c.influencer_name ? `
                                    <div class="meta-item">
                                        <i class="fas fa-user"></i>
                                        ${c.influencer_name}
                                    </div>
                                ` : ''}
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    ${formatDate(c.created_on)}
                                </div>
                            </div>
                            
                            <div class="collab-actions">
                                ${actions}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            $(`#${status}-collabs`).html(html);
        }
        
        // Show Create Modal
        function showCreateModal() {
            $('#createModal').addClass('show');
        }
        
        // Close Modal
        function closeModal(modalId) {
            $(`#${modalId}`).removeClass('show');
            if (modalId === 'createModal') {
                $('#createCollabForm')[0].reset();
                $('.preview-image').hide();
            }
        }
        
        // Toggle Financial Amount Field
        function toggleFinancialAmount(select) {
            if (select.value === 'paid') {
                $('#amountGroup').show();
            } else {
                $('#amountGroup').hide();
            }
        }
        
        // Preview Image
        function previewImage(input, index) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $(`#preview${index}`).attr('src', e.target.result).show();
                    $(`#preview${index}`).siblings().hide();
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Create Collaboration Form Submit
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
                        closeModal('createModal');
                        loadCollabs(currentTab);
                    } else {
                        showToast(response.message, 'error');
                    }
                },
                error: function() {
                    showToast('An error occurred. Please try again.', 'error');
                }
            });
        });
        
        // Complete Collaboration
        function completeCollab(id) {
            if (!confirm('Mark this collaboration as completed?')) return;
            
            $.post('', { action: 'complete_collab', collab_id: id }, function(response) {
                if (response.status) {
                    showToast(response.message, 'success');
                    loadCollabs(currentTab);
                } else {
                    showToast(response.message, 'error');
                }
            }, 'json');
        }
        
        // Delete Collaboration
        function deleteCollab(id) {
            if (!confirm('Are you sure you want to delete this collaboration?')) return;
            
            $.post('', { action: 'delete_collab', collab_id: id }, function(response) {
                if (response.status) {
                    showToast(response.message, 'success');
                    loadCollabs(currentTab);
                } else {
                    showToast(response.message, 'error');
                }
            }, 'json');
        }
        
        // Download Photo
        function downloadPhoto(url) {
            if (!url || url.includes('null') || url.includes('undefined')) return;
            window.open(url, '_blank');
        }
        
        // Toast Notification
        function showToast(message, type) {
            const toast = $('#toast');
            toast.removeClass('success error').addClass(type).text(message).addClass('show');
            setTimeout(() => toast.removeClass('show'), 3000);
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
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return date.toLocaleDateString('en-US', options);
        }
        
        // Close modal on outside click
        $('.modal-overlay').on('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    </script>
</body>
</html>
