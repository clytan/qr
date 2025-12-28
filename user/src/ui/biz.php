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
    
    <link rel="stylesheet" href="../css/biz.css">
</head>
<body class="dark-scheme de-grey">
    <div id="wrapper">
        <?php include('../components/header.php'); ?>
        
        <div class="no-bottom no-top" id="content">
            <div class="page-container">
                <!-- Hero Section (Polls-Style) -->
                <section class="polls-hero" style="text-align: center; padding: 30px 0 20px;">
                    <h1 class="polls-title" style="font-size: 2.5rem; font-weight: 800; background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 0.5rem;">Partner Programme</h1>

                    <!-- Prize Carousel -->
                    <div class="prize-carousel-section container" style="max-width: 900px; margin: 20px auto;">
                        <div class="owl-carousel owl-theme" id="prizeCarousel">
                            <div class="item">
                                <div class="prize-banner-item banner-1">
                                    <div class="banner-content">
                                        <h3>Partner Up</h3>
                                        <p>Team with top companies and grow together!</p>
                                    </div>
                                    <i class="fas fa-handshake banner-icon"></i>
                                </div>
                            </div>
                            <div class="item">
                                <div class="prize-banner-item banner-2">
                                    <div class="banner-content">
                                        <h3>Earn Commission</h3>
                                        <p>Get rewarded for every successful referral.</p>
                                    </div>
                                    <i class="fas fa-coins banner-icon"></i>
                                </div>
                            </div>
                            <div class="item">
                                <div class="prize-banner-item banner-3">
                                    <div class="banner-content">
                                        <h3>Simple & Transparent</h3>
                                        <p>Track your earnings in real-time.</p>
                                    </div>
                                    <i class="fas fa-chart-line banner-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Premium Nav Bar (Polls-Style) -->
                <div class="polls-nav-bar">
                    <div class="polls-tabs-group">
                        <button class="poll-tab-item active" onclick="showTab('programmes')">All</button>
                        <button class="poll-tab-item" onclick="showTab('my_referrals')">Referrals</button>
                        <?php if ($is_biz_user): ?>
                        <button class="poll-tab-item" onclick="showTab('my_collabs')">Created</button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($is_biz_user): ?>
                    <button class="create-poll-btn-premium" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> Create Programme
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
        <div class="modal" style="background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%) !important; color: #e2e8f0 !important; border: 1px solid rgba(233, 67, 122, 0.3) !important; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5) !important; display: block !important; z-index: 100000 !important; max-width: 400px !important; width: 90% !important; padding: 20px !important; border-radius: 16px !important;">
            <h3 class="modal-title" style="margin: 0 0 20px 0; font-size: 1.3rem; background: linear-gradient(135deg, #E9437A, #E2AD2A); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                <i class="fas fa-user-plus" style="-webkit-text-fill-color: #E9437A;"></i> Submit Referral
            </h3>
            <form id="referForm">
                <input type="hidden" id="programme-id">
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label" style="display: block; font-size: 0.85rem; color: #94a3b8; margin-bottom: 6px;">Client Name *</label>
                    <input type="text" class="form-input" id="client-name" required placeholder="Enter client's full name" style="width: 100%; padding: 12px; background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #e2e8f0; font-size: 14px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label" style="display: block; font-size: 0.85rem; color: #94a3b8; margin-bottom: 6px;">Client Phone *</label>
                    <input type="tel" class="form-input" id="client-phone" required placeholder="Enter client's phone number" style="width: 100%; padding: 12px; background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #e2e8f0; font-size: 14px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label" style="display: block; font-size: 0.85rem; color: #94a3b8; margin-bottom: 6px;">Client Email *</label>
                    <input type="email" class="form-input" id="client-email" required placeholder="client@example.com" style="width: 100%; padding: 12px; background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #e2e8f0; font-size: 14px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" style="display: block; font-size: 0.85rem; color: #94a3b8; margin-bottom: 6px;">Product Required *</label>
                    <input type="text" class="form-input" id="product-name" required placeholder="Which product is client interested in?" style="width: 100%; padding: 12px; background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #e2e8f0; font-size: 14px;">
                </div>
                
                <div class="modal-actions" style="display: flex; gap: 12px;">
                    <button type="button" class="btn secondary" onclick="closeModal()" style="flex: 1; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: #94a3b8; font-size: 15px; cursor: pointer;">Cancel</button>
                    <button type="submit" class="btn primary" style="flex: 2; padding: 12px; background: linear-gradient(135deg, #E9437A, #E2AD2A); border: none; border-radius: 10px; color: white; font-size: 15px; font-weight: 600; cursor: pointer;"><i class="fas fa-paper-plane"></i> Submit Referral</button>
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
    
    <?php include('../components/jslinks.php') ?>
    <script src="../js/biz.js"></script>

</body>
</html>
