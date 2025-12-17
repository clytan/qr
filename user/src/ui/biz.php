<?php
session_start();
require_once('../backend/dbconfig/connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Partner';

// Get user details from database for accurate info
$stmtUser = $conn->prepare("SELECT user_email, user_user_type FROM user_user WHERE id = ?");
$stmtUser->bind_param('i', $user_id);
$stmtUser->execute();
$userResult = $stmtUser->get_result();
$userData = $userResult->fetch_assoc();
$stmtUser->close();

$user_email = $userData['user_email'] ?? '';
$user_type = intval($userData['user_user_type'] ?? 1);
$is_biz_user = ($user_type === 3); // Biz users can create collaborations

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    
    switch ($action) {
        case 'get_programmes':
            try {
                $sql = "SELECT * FROM partner_programmes 
                        WHERE status = 'active' AND is_deleted = 0 
                        ORDER BY created_on DESC";
                
                $result = $conn->query($sql);
                $programmes = [];
                
                while ($row = $result->fetch_assoc()) {
                    $programmes[] = $row;
                }
                
                echo json_encode(['status' => true, 'data' => $programmes]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'submit_referral':
            try {
                $programme_id = intval($_POST['programme_id'] ?? 0);
                $client_name = trim($_POST['client_name'] ?? '');
                $client_phone = trim($_POST['client_phone'] ?? '');
                $client_email = trim($_POST['client_email'] ?? '');
                $product_name = trim($_POST['product_name'] ?? '');
                
                if ($programme_id <= 0 || empty($client_name) || empty($client_phone) || empty($client_email)) {
                    echo json_encode(['status' => false, 'message' => 'All fields are required']);
                    exit();
                }
                
                // Get programme details
                $stmt = $conn->prepare("SELECT * FROM partner_programmes WHERE id = ? AND status = 'active' AND is_deleted = 0");
                $stmt->bind_param('i', $programme_id);
                $stmt->execute();
                $programme = $stmt->get_result()->fetch_assoc();
                
                if (!$programme) {
                    echo json_encode(['status' => false, 'message' => 'Programme not found or inactive']);
                    exit();
                }
                
                // Insert referral
                $sql = "INSERT INTO partner_referrals (
                    programme_id, referred_by, client_name, client_phone, 
                    client_email, product_name, status, created_on
                ) VALUES (?, ?, ?, ?, ?, ?, 'open', NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    'iissss',
                    $programme_id, $user_id, $client_name, $client_phone,
                    $client_email, $product_name
                );
                
                if ($stmt->execute()) {
                    // Send emails
                    sendReferralEmails($programme, $client_name, $client_phone, $client_email, $product_name, $user_name, $user_email);
                    
                    echo json_encode([
                        'status' => true,
                        'message' => 'Referral submitted successfully! The company will contact your client soon.'
                    ]);
                } else {
                    echo json_encode(['status' => false, 'message' => 'Failed to submit referral']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'get_my_referrals':
            try {
                $sql = "SELECT r.*, p.programme_header, p.company_name
                        FROM partner_referrals r
                        LEFT JOIN partner_programmes p ON r.programme_id = p.id
                        WHERE r.referred_by = ? AND r.is_deleted = 0
                        ORDER BY r.created_on DESC";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $referrals = [];
                while ($row = $result->fetch_assoc()) {
                    $referrals[] = $row;
                }
                
                echo json_encode(['status' => true, 'data' => $referrals]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'create_programme':
            // Only Biz users can create partner programmes
            if ($user_type != 3) {
                echo json_encode(['status' => false, 'message' => 'Only Business users can create partner programmes']);
                exit();
            }
            
            try {
                $programme_header = trim($_POST['programme_header'] ?? '');
                $company_name = trim($_POST['company_name'] ?? '');
                $product_link = trim($_POST['product_link'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $commission_details = trim($_POST['commission_details'] ?? '');
                $company_email = $user_email; // Use logged-in user's email
                
                if (empty($programme_header) || empty($commission_details) || empty($description)) {
                    echo json_encode(['status' => false, 'message' => 'Programme header, description and commission details are required']);
                    exit();
                }
                
                $sql = "INSERT INTO partner_programmes (
                    programme_header, company_name, product_link, description,
                    commission_details, company_email, status, created_by, created_on
                ) VALUES (?, ?, ?, ?, ?, ?, 'active', ?, NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    'ssssssi',
                    $programme_header, $company_name, $product_link, $description,
                    $commission_details, $company_email, $user_id
                );
                
                if ($stmt->execute()) {
                    echo json_encode(['status' => true, 'message' => 'Partner programme created successfully!']);
                } else {
                    echo json_encode(['status' => false, 'message' => 'Failed to create programme']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'get_my_programmes':
            try {
                $sql = "SELECT p.*, COUNT(r.id) as referral_count
                        FROM partner_programmes p
                        LEFT JOIN partner_referrals r ON p.id = r.programme_id AND r.is_deleted = 0
                        WHERE p.created_by = ? AND p.is_deleted = 0
                        GROUP BY p.id
                        ORDER BY p.created_on DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $programmes = [];
                while ($row = $result->fetch_assoc()) {
                    $programmes[] = $row;
                }
                
                echo json_encode(['status' => true, 'data' => $programmes]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'delete_programme':
            try {
                $programme_id = intval($_POST['programme_id'] ?? 0);
                
                $stmt = $conn->prepare("SELECT created_by FROM partner_programmes WHERE id = ? AND is_deleted = 0");
                $stmt->bind_param('i', $programme_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $programme = $result->fetch_assoc();
                
                if (!$programme || $programme['created_by'] != $user_id) {
                    echo json_encode(['status' => false, 'message' => 'You can only delete your own programmes']);
                    exit();
                }
                
                $stmt = $conn->prepare("UPDATE partner_programmes SET is_deleted = 1 WHERE id = ?");
                $stmt->bind_param('i', $programme_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['status' => true, 'message' => 'Programme deleted successfully']);
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
function sendReferralEmails($programme, $client_name, $client_phone, $client_email, $product_name, $user_name, $user_email) {
    $admin_email = "admin@zokli.com"; // Admin/Zokli email
    
    $subject = "New Referral: " . $programme['programme_header'];
    $headers = "From: noreply@zokli.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #E9437A, #E2AD2A); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
            .content { background: #f9f9f9; padding: 20px; border-radius: 10px; }
            .details { margin: 15px 0; }
            .label { font-weight: bold; color: #E9437A; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🎯 New Lead Referral</h2>
            </div>
            <div class='content'>
                <p><strong>Programme:</strong> {$programme['programme_header']}</p>
                
                <div class='details'>
                    <p><span class='label'>Client Name:</span> {$client_name}</p>
                    <p><span class='label'>Phone:</span> {$client_phone}</p>
                    <p><span class='label'>Email:</span> {$client_email}</p>
                    <p><span class='label'>Product Required:</span> {$product_name}</p>
                </div>
                
                <div class='details'>
                    <p><span class='label'>Referred By:</span> {$user_name} ({$user_email})</p>
                </div>
                
                <p><strong>Commission Details:</strong><br>{$programme['commission_details']}</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " Zokli Partner Programme. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Email to Company
    if (!empty($programme['company_email'])) {
        @mail($programme['company_email'], $subject, $message, $headers);
    }
    
    // Email to Admin/Zokli
    @mail($admin_email, $subject, $message, $headers);
    
    // Email to User (confirmation)
    $user_message = str_replace('New Lead Referral', 'Referral Submitted Successfully', $message);
    @mail($user_email, "Referral Confirmation: " . $programme['programme_header'], $user_message, $headers);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Biz - Partner Programme | Zokli</title>
    <link rel="icon" href="../assets/logo2.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php include('../components/csslinks.php') ?>
    
    <style>
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
        }
        
        .hero-title {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #E9437A 0%, #E2AD2A 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .hero-subtitle {
            font-size: 18px;
            color: #94a3b8;
            max-width: 700px;
            margin: 0 auto 30px;
            line-height: 1.7;
        }
        
        /* Tabs */
        .tabs-container {
            display: flex;
            gap: 15px;
            margin-bottom: 40px;
            background: rgba(30, 41, 59, 0.6);
            padding: 8px;
            border-radius: 16px;
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
        
        .tab-btn:hover { background: rgba(255, 255, 255, 0.05); color: #e2e8f0; }
        .tab-btn.active {
            background: linear-gradient(135deg, #E9437A 0%, #E2AD2A 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(233, 67, 122, 0.3);
        }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        /* Programme Cards */
        .programmes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 30px;
        }
        
        .programme-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        
        .programme-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(233, 67, 122, 0.25);
            border-color: rgba(233, 67, 122, 0.4);
        }
        
        .programme-content {
            padding: 25px;
        }
        
        .programme-header {
            font-size: 22px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 8px;
        }
        
        .company-name {
            font-size: 14px;
            color: #E9437A;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .programme-description {
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.7;
            margin-bottom: 20px;
        }
        
        .commission-box {
            padding: 15px;
            background: linear-gradient(135deg, rgba(226, 173, 42, 0.15), rgba(233, 67, 122, 0.15));
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid rgba(226, 173, 42, 0.3);
        }
        
        .commission-label {
            font-size: 12px;
            color: #94a3b8;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .commission-text {
            font-size: 14px;
            color: #E2AD2A;
            font-weight: 600;
            line-height: 1.6;
        }
        
        .product-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px;
            background: rgba(233, 67, 122, 0.1);
            border-radius: 10px;
            margin-bottom: 20px;
            text-decoration: none;
            color: #E9437A;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .product-link:hover {
            background: rgba(233, 67, 122, 0.2);
            transform: translateX(5px);
        }
        
        .refer-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #E9437A, #E2AD2A);
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
        
        .refer-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(233, 67, 122, 0.4);
        }
        
        /* Referrals Table */
        .referrals-section {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: rgba(15, 23, 42, 0.5);
            padding: 14px 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
        }
        
        td {
            padding: 14px 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 13px;
            color: #e2e8f0;
        }
        
        tr:hover td { background: rgba(255, 255, 255, 0.02); }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-badge.open {
            background: rgba(233, 67, 122, 0.2);
            color: #E9437A;
        }
        
        .status-badge.in_process {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }
        
        .status-badge.closed {
            background: rgba(226, 173, 42, 0.2);
            color: #E2AD2A;
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
            z-index: 99999 !important;
            padding: 20px;
            backdrop-filter: blur(5px);
        }
        
        .modal-overlay.show { display: flex; }
        
        .modal {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            border-radius: 20px;
            width: 100%;
            max-width: 550px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 35px;
            border: 1px solid rgba(233, 67, 122, 0.3);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 100000 !important;
        }
        
        .modal-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 25px;
            background: linear-gradient(135deg, #E9437A, #E2AD2A);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .form-group { margin-bottom: 20px; }
        
        .form-label {
            display: block;
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 8px;
            font-weight: 600;
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
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #E9437A;
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
        }
        
        .btn.primary {
            background: linear-gradient(135deg, #E9437A, #E2AD2A);
            color: white;
        }
        
        .btn.secondary {
            background: rgba(255, 255, 255, 0.05);
            color: #94a3b8;
        }
        
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 18px 28px;
            border-radius: 12px;
            font-size: 15px;
            z-index: 5000;
            transform: translateX(150%);
            transition: transform 0.4s ease;
        }
        
        .toast.show { transform: translateX(0); }
        .toast.success { background: linear-gradient(135deg, #E2AD2A, #16a34a); color: white; }
        .toast.error { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
        
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
        
        @media (max-width: 768px) {
            .hero-title { font-size: 32px; }
            .tabs-container { flex-direction: column; }
            .programmes-grid { grid-template-columns: 1fr; }
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
            margin-top: 20px;
        }
        
        .create-collab-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
        }
        
        /* Create Modal Styles */
        .create-modal {
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
        
        .create-modal.show { display: flex; }
        
        .create-modal .modal {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            border-radius: 16px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid rgba(233, 67, 122, 0.3);
            display: block !important;
            position: relative;
            z-index: 10001;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        .modal-header {
            padding: 12px 20px;
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
        
        .modal-close-btn {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .modal-body { padding: 15px 20px; }
        .collab-form-group { margin-bottom: 12px; }
        
        .collab-form-group label {
            display: block;
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 5px;
        }
        
        .collab-form-input, .collab-form-select, .collab-form-textarea {
            width: 100%;
            padding: 10px 12px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #e2e8f0;
            font-size: 13px;
        }
        
        .collab-form-input:focus, .collab-form-select:focus { outline: none; border-color: #E9437A; }
        .collab-form-textarea { min-height: 70px; resize: vertical; }

        
        .photo-uploads-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        
        .photo-upload-box {
            aspect-ratio: 1;
            border: 2px dashed rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .photo-upload-box:hover { border-color: #E9437A; }
        .photo-upload-box input { display: none; }
        .photo-upload-box i { font-size: 24px; color: #475569; }
        .photo-upload-box span { font-size: 11px; color: #475569; margin-top: 5px; }
        .photo-upload-box img { position: absolute; width: 100%; height: 100%; object-fit: cover; }
        
        .modal-footer-btns {
            padding: 20px 25px;
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            gap: 12px;
        }
        
        .btn-modal-cancel {
            flex: 1;
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #94a3b8;
            font-size: 15px;
            cursor: pointer;
        }
        
        .btn-modal-submit {
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
        
        /* Collab Card */
        .collab-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px;
        }
        
        .collab-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .collab-title { font-size: 18px; font-weight: 700; color: #f1f5f9; }
        
        .collab-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .collab-status.pending { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .collab-status.active { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
        
        .collab-desc { font-size: 14px; color: #94a3b8; margin-bottom: 15px; }
        
        .collab-delete-btn {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid #ef4444;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
        }
        
        .collab-delete-btn:hover { background: #ef4444; color: white; }
    </style>
</head>
<body class="dark-scheme de-grey">
    <div id="wrapper">
        <?php include('../components/header.php'); ?>
        
        <div class="no-bottom no-top" id="content">
            <div class="page-container" style="margin-top:10%;">
                <!-- Hero Section -->
                <div class="hero-section">
                    <div class="hero-badge">
                        <i class="fas fa-briefcase"></i> Partner Programme
                    </div>
                    <h1 class="hero-title">
                        <i class="fas fa-handshake"></i> Biz - Earn with Referrals
                    </h1>
                    <p class="hero-subtitle">
                        Partner with top companies and earn commissions by referring clients. 
                        Simple, transparent, and rewarding!
                    </p>
                    <!-- Debug: User Type = <?php echo $user_type; ?>, Is Biz = <?php echo $is_biz_user ? 'true' : 'false'; ?> -->
                    <?php if ($is_biz_user): ?>
                    <button class="create-collab-btn" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> Create Partner Programme
                    </button>
                    <?php endif; ?>
                </div>
                
                <!-- Tabs -->
                <div class="tabs-container">
                    <button class="tab-btn active" onclick="showTab('programmes')">
                        <i class="fas fa-briefcase"></i>
                        Programmes
                    </button>
                    <button class="tab-btn" onclick="showTab('my_referrals')">
                        <i class="fas fa-list"></i>
                        My Referrals
                    </button>
                    <?php if ($is_biz_user): ?>
                    <button class="tab-btn" onclick="showTab('my_collabs')">
                        <i class="fas fa-layer-group"></i>
                        My Programmes
                    </button>

                    <?php endif; ?>
                </div>
                
                <!-- Tab: Programmes -->
                <div class="tab-content active" id="tab-programmes">
                    <div class="programmes-grid" id="programmes-grid">
                        <div class="empty-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Loading programmes...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: My Referrals -->
                <div class="tab-content" id="tab-my_referrals">
                    <div class="referrals-section">
                        <div style="overflow-x: auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Programme</th>
                                        <th>Client Name</th>
                                        <th>Phone</th>
                                        <th>Product</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody id="referrals-body">
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px;">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: My Created Collabs (Biz users only) -->
                <?php if ($is_biz_user): ?>
                <div class="tab-content" id="tab-my_collabs">
                    <div class="programmes-grid" id="my-collabs-grid">
                        <div class="empty-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Loading your collaborations...</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
        
        <?php include('../components/footer.php'); ?>
    </div>
    
    <!-- Refer Modal (Outside wrapper for proper z-index) -->
    <div class="modal-overlay" id="referModal" style="z-index: 99999 !important;">
        <div class="modal" style="background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%) !important; color: #e2e8f0 !important; border: 1px solid rgba(233, 67, 122, 0.3) !important; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5) !important; display: block !important; z-index: 100000 !important;">
            <h3 class="modal-title">
                <i class="fas fa-user-plus"></i> Submit Referral
            </h3>
            <form id="referForm">
                <input type="hidden" id="programme-id">
                
                <div class="form-group">
                    <label class="form-label">Client Name *</label>
                    <input type="text" class="form-input" id="client-name" required placeholder="Enter client's full name">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Client Phone *</label>
                    <input type="tel" class="form-input" id="client-phone" required placeholder="Enter client's phone number">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Client Email *</label>
                    <input type="email" class="form-input" id="client-email" required placeholder="client@example.com">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Product Required *</label>
                    <input type="text" class="form-input" id="product-name" required placeholder="Which product is client interested in?">
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn primary"><i class="fas fa-paper-plane"></i> Submit Referral</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Create Partner Programme Modal (Biz users only) -->
    <?php if ($is_biz_user): ?>
    <div class="create-modal" id="createProgrammeModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Create Partner Programme</h3>
                <button class="modal-close-btn" onclick="closeCreateModal()">&times;</button>
            </div>
            <form id="createProgrammeForm">
                <div class="modal-body">
                    <div class="collab-form-group">
                        <label class="form-label">Programme Header *</label>
                        <input type="text" class="collab-form-input" name="programme_header" required placeholder="e.g., Sell Life Insurance">
                    </div>
                    
                    <div class="collab-form-group">
                        <label class="form-label">Company Name</label>
                        <input type="text" class="collab-form-input" name="company_name" placeholder="e.g., ABC Insurance Ltd.">
                    </div>
                    
                    <div class="collab-form-group">
                        <label class="form-label">Product/Company Link</label>
                        <input type="url" class="collab-form-input" name="product_link" placeholder="https://company.com">
                    </div>
                    
                    <div class="collab-form-group">
                        <label class="form-label">Description *</label>
                        <textarea class="collab-form-textarea" name="description" required placeholder="Describe the partner programme..."></textarea>
                    </div>
                    
                    <div class="collab-form-group">
                        <label class="form-label">Commission Details *</label>
                        <textarea class="collab-form-textarea" name="commission_details" required placeholder="e.g., 10% commission on first year premium, Paid within 30 days..."></textarea>
                    </div>
                </div>
                <div class="modal-footer-btns">
                    <button type="button" class="btn-modal-cancel" onclick="closeCreateModal()">Cancel</button>
                    <button type="submit" class="btn-modal-submit"><i class="fas fa-paper-plane"></i> Create Programme</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>


    
    <div class="toast" id="toast"></div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentTab = 'programmes';
        let currentProgramme = null;
        const isBizUser = <?php echo $is_biz_user ? 'true' : 'false'; ?>;
        
        $(document).ready(function() {
            loadProgrammes();
            <?php if ($is_biz_user): ?>
            loadMyProgrammes();
            <?php endif; ?>
        });
        
        function showTab(tab) {
            currentTab = tab;
            
            $('.tab-btn').removeClass('active');
            $(`.tab-btn`).each(function() {
                const text = $(this).text().trim().toLowerCase();
                if ((tab === 'programmes' && text.includes('programmes')) ||
                    (tab === 'my_referrals' && text.includes('referrals')) ||
                    (tab === 'my_collabs' && text.includes('collabs'))) {
                    $(this).addClass('active');
                }
            });
            
            $('.tab-content').removeClass('active');
            $(`#tab-${tab}`).addClass('active');
            
            if (tab === 'my_referrals') {
                loadMyReferrals();
            } else if (tab === 'my_collabs') {
                loadMyProgrammes();
            }
        }
        
        function loadProgrammes() {
            $.post('', { action: 'get_programmes' }, function(response) {
                if (response.status && response.data.length > 0) {
                    renderProgrammes(response.data);
                } else {
                    $('#programmes-grid').html(`
                        <div class="empty-state">
                            <i class="fas fa-briefcase"></i>
                            <h3>No Active Programmes</h3>
                            <p>Check back soon for new partner opportunities!</p>
                        </div>
                    `);
                }
            }, 'json').fail(function() {
                showToast('Failed to load programmes', 'error');
            });
        }
        
        function renderProgrammes(programmes) {
            let html = '';
            
            programmes.forEach(p => {
                html += `
                    <div class="programme-card">
                        <div class="programme-content">
                            <div class="programme-header">${escapeHtml(p.programme_header)}</div>
                            ${p.company_name ? `<div class="company-name"><i class="fas fa-building"></i> ${escapeHtml(p.company_name)}</div>` : ''}
                            
                            <div class="programme-description">${escapeHtml(p.description)}</div>
                            
                            <div class="commission-box">
                                <div class="commission-label"><i class="fas fa-dollar-sign"></i> Commission Structure</div>
                                <div class="commission-text">${escapeHtml(p.commission_details)}</div>
                            </div>
                            
                            ${p.product_link ? `
                                <a href="${p.product_link}" target="_blank" class="product-link">
                                    <i class="fas fa-external-link-alt"></i>
                                    View Company Website
                                </a>
                            ` : ''}
                            
                            <button class="refer-button" onclick="openReferModal(${p.id}, '${escapeHtml(p.programme_header)}')">
                                <i class="fas fa-user-plus"></i>
                                Refer a Client
                            </button>
                        </div>
                    </div>
                `;
            });
            
            $('#programmes-grid').html(html);
        }
        
        function loadMyReferrals() {
            $.post('', { action: 'get_my_referrals' }, function(response) {
                if (response.status && response.data.length > 0) {
                    renderMyReferrals(response.data);
                } else {
                    $('#referrals-body').html(`
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #64748b;">
                                No referrals yet. Start referring clients!
                            </td>
                        </tr>
                    `);
                }
            }, 'json').fail(function() {
                showToast('Failed to load referrals', 'error');
            });
        }
        
        function renderMyReferrals(referrals) {
            let html = '';
            
            referrals.forEach(r => {
                html += `
                    <tr>
                        <td>${escapeHtml(r.programme_header)}</td>
                        <td>${escapeHtml(r.client_name)}</td>
                        <td>${escapeHtml(r.client_phone)}</td>
                        <td>${escapeHtml(r.product_name)}</td>
                        <td><span class="status-badge ${r.status}">${r.status.replace('_', ' ')}</span></td>
                        <td>${formatDate(r.created_on)}</td>
                    </tr>
                `;
            });
            
            $('#referrals-body').html(html);
        }
        
        function openReferModal(programmeId, programmeName) {
            currentProgramme = { id: programmeId, name: programmeName };
            $('#programme-id').val(programmeId);
            $('#referForm')[0].reset();
            $('#referModal').addClass('show');
        }
        
        function closeModal() {
            $('#referModal').removeClass('show');
        }
        
        $('#referForm').on('submit', function(e) {
            e.preventDefault();
            
            // Prevent duplicate submissions
            const $submitBtn = $(this).find('button[type="submit"]');
            if ($submitBtn.prop('disabled')) {
                return false; // Already submitting
            }
            
            // Disable submit button
            $submitBtn.prop('disabled', true).text('Submitting...');
            
            $.post('', {
                action: 'submit_referral',
                programme_id: $('#programme-id').val(),
                client_name: $('#client-name').val(),
                client_phone: $('#client-phone').val(),
                client_email: $('#client-email').val(),
                product_name: $('#product-name').val()
            }, function(response) {
                // Re-enable button
                $submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Referral');
                
                if (response.status) {
                    showToast(response.message, 'success');
                    closeModal();
                    if (currentTab === 'my_referrals') {
                        loadMyReferrals();
                    }
                } else {
                    showToast(response.message, 'error');
                }
            }, 'json').fail(function() {
                // Re-enable button on error
                $submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Referral');
                showToast('Failed to submit referral. Please try again.', 'error');
            });
        });
        
        function showToast(message, type) {
            const toast = $('#toast');
            toast.removeClass('success error').addClass(type).text(message).addClass('show');
            setTimeout(() => toast.removeClass('show'), 4000);
        }
        
        function escapeHtml(text) {
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return text ? String(text).replace(/[&<>"']/g, m => map[m]) : '';
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }
        
        // ===== BIZ USER FUNCTIONS =====
        <?php if ($is_biz_user): ?>
        
        function openCreateModal() {
            $('#createProgrammeForm')[0].reset();
            $('#createProgrammeModal').addClass('show');
        }
        
        function closeCreateModal() {
            $('#createProgrammeModal').removeClass('show');
        }
        
        function loadMyProgrammes() {
            $.post('', { action: 'get_my_programmes' }, function(response) {
                if (response.status && response.data.length > 0) {
                    renderMyProgrammes(response.data);
                } else {
                    $('#my-collabs-grid').html(`
                        <div class="empty-state">
                            <i class="fas fa-briefcase"></i>
                            <h3>No Created Programmes</h3>
                            <p>Click "Create Partner Programme" to get started!</p>
                        </div>
                    `);
                }
            }, 'json');
        }
        
        function renderMyProgrammes(programmes) {
            let html = '';
            
            programmes.forEach(p => {
                html += `
                    <div class="collab-card">
                        <div class="collab-card-header">
                            <div class="collab-title">${escapeHtml(p.programme_header)}</div>
                            <span class="collab-status ${p.status}">${p.status}</span>
                        </div>
                        ${p.company_name ? `<div style="color: #E9437A; font-size: 12px; margin-bottom: 8px;"><i class="fas fa-building"></i> ${escapeHtml(p.company_name)}</div>` : ''}
                        <div class="collab-desc">${escapeHtml(p.description || '')}</div>
                        <div style="font-size: 12px; color: #E2AD2A; margin: 10px 0;">
                            <i class="fas fa-users"></i> ${p.referral_count || 0} referrals
                        </div>
                        <button class="collab-delete-btn" onclick="deleteProgramme(${p.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                `;
            });
            
            $('#my-collabs-grid').html(html);
        }
        
        function deleteProgramme(id) {
            if (!confirm('Are you sure you want to delete this programme?')) return;
            
            $.post('', { action: 'delete_programme', programme_id: id }, function(response) {
                if (response.status) {
                    showToast(response.message, 'success');
                    loadMyProgrammes();
                } else {
                    showToast(response.message, 'error');
                }
            }, 'json');
        }
        
        // Submit create programme form
        $('#createProgrammeForm').on('submit', function(e) {
            e.preventDefault();
            
            $.post('', $(this).serialize() + '&action=create_programme', function(response) {
                if (response.status) {
                    showToast(response.message, 'success');
                    closeCreateModal();
                    loadMyProgrammes();
                    loadProgrammes(); // Reload the main list too
                } else {
                    showToast(response.message, 'error');
                }
            }, 'json').fail(function() {
                showToast('Failed to create programme', 'error');
            });
        });
        
        // Close modal on overlay click
        $('#createProgrammeModal').on('click', function(e) {
            if (e.target === this) closeCreateModal();
        });
        
        <?php endif; ?>

    </script>

</body>
</html>
