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
    
    <link rel="stylesheet" href="../css/influencer.css">
</head>
<body class="dark-scheme de-grey">
    <div id="wrapper">
        <!-- Header -->
        <?php include('../components/header.php'); ?>
        
        <!-- Content -->
        <div class="no-bottom no-top" id="content">
            <div class="page-container">
                <!-- Hero Section (Polls-Style) -->
                <section class="polls-hero">
                    <h1 class="polls-title">Influencer Program</h1>

                    <!-- Prize Carousel -->
                    <div class="prize-carousel-section container" style="max-width: 900px; margin: 20px auto;">
                        <div class="owl-carousel owl-theme" id="prizeCarousel">
                            <div class="item">
                                <div class="prize-banner-item banner-1">
                                    <div class="banner-content">
                                        <h3>Brand Collabs</h3>
                                        <p>Partner with top brands and create amazing content!</p>
                                    </div>
                                    <i class="fas fa-star banner-icon"></i>
                                </div>
                            </div>
                            <div class="item">
                                <div class="prize-banner-item banner-2">
                                    <div class="banner-content">
                                        <h3>Get Rewarded</h3>
                                        <p>Earn for your influence and creativity.</p>
                                    </div>
                                    <i class="fas fa-gift banner-icon"></i>
                                </div>
                            </div>
                            <div class="item">
                                <div class="prize-banner-item banner-3">
                                    <div class="banner-content">
                                        <h3>Build Your Brand</h3>
                                        <p>Join thousands of influencers growing with Zokli.</p>
                                    </div>
                                    <i class="fas fa-rocket banner-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Premium Nav Bar (Polls-Style) -->
                <div class="polls-nav-bar">
                    <div class="polls-tabs-group">
                        <button class="poll-tab-item active" onclick="showTab('available')">Available</button>
                        <button class="poll-tab-item" onclick="showTab('my_collabs')">My Accepted</button>
                        <?php if ($is_biz_user): ?>
                        <button class="poll-tab-item" onclick="showTab('my_created')">My Created</button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($is_biz_user): ?>
                    <button class="create-poll-btn-premium" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> Create Collab
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
    
    <?php include('../components/jslinks.php') ?>
    <script>
        const userId = <?php echo $user_id; ?>;
        const isBizUser = <?php echo $is_biz_user ? 'true' : 'false'; ?>;
    </script>
    <script src="../js/influencer.js"></script>
</body>
</html>
