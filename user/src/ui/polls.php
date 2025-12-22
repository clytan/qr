<!DOCTYPE html>
<html lang="en">
<?php
include '../backend/dbconfig/connection.php';
session_start();
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
?>

<head>
    <title>Zokli - Community Polls</title>
    <link rel="icon" href="../assets/logo2.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="Zokli Community Polls - Create and vote on polls" name="description" />
    
    <?php include('../components/csslinks.php') ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
    :root {
        --primary: #667eea;
        --primary-dark: #5568d3;
        --secondary: #764ba2;
        --accent: #f093fb;
        --dark: #0f172a;
        --darker: #0a0e27;
        --light: #f8fafc;
        --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --gradient-2: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
    }

    body, html {
        min-height: 100vh;
        background: var(--darker) !important;
    }

    #wrapper {
        background: linear-gradient(135deg, rgba(10, 14, 39, 0.98) 0%, rgba(26, 31, 58, 0.95) 100%);
    }

    #content {
        padding-top: 80px !important;
        padding-bottom: 100px;
    }

    @media (max-width: 768px) {
        #content {
           padding-top: 60px !important;
        }
    }

    /* Page Header */
    .polls-hero {
        text-align: center;
        padding: 30px 0 20px;
        margin-top:10%;
    }

    .polls-title {
        font-size: 2.5rem;
        font-weight: 800;
        background: var(--gradient-2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 0.5rem;
    }

    .polls-subtitle {
        color: #94a3b8;
        font-size: 1rem;
    }

    /* Tabs */
    .polls-tabs {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin: 20px 0 30px;
        flex-wrap: wrap;
    }

    .poll-tab {
        padding: 10px 24px;
        border-radius: 50px;
        border: 2px solid rgba(255,255,255,0.1);
        background: rgba(255,255,255,0.05);
        color: #94a3b8;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .poll-tab:hover {
        border-color: var(--primary);
        color: #fff;
    }

    .poll-tab.active {
        background: var(--gradient-1);
        border-color: transparent;
        color: #fff;
    }

    /* Create Poll Button */
    .create-poll-btn {
        position: fixed;
        bottom: 120px;
        right: 20px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: var(--gradient-2);
        color: white;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        box-shadow: 0 8px 25px rgba(233, 67, 122, 0.4);
        z-index: 100;
        transition: all 0.3s ease;
    }

    .create-poll-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 12px 35px rgba(233, 67, 122, 0.5);
    }

    /* Polls Container */
    .polls-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 0 15px;
    }

    /* Poll Card */
    .poll-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
        transition: all 0.3s ease;
    }

    .poll-card:hover {
        border-color: rgba(102, 126, 234, 0.3);
        transform: translateY(-2px);
    }

    .poll-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }

    .poll-creator {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.85rem;
        color: #94a3b8;
    }

    .poll-creator-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--gradient-1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.8rem;
    }

    .poll-status {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .poll-status.active {
        background: rgba(16, 185, 129, 0.2);
        color: #10b981;
    }

    .poll-status.pending_payment {
        background: rgba(245, 158, 11, 0.2);
        color: #f59e0b;
    }

    .poll-status.closed {
        background: rgba(148, 163, 184, 0.2);
        color: #94a3b8;
    }

    .poll-timer {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        background: rgba(102, 126, 234, 0.2);
        color: #667eea;
        margin-left: 20%;
    }

    .poll-timer.expired {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
    }

    .poll-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 8px;
    }

    .poll-description {
        color: #94a3b8;
        font-size: 0.9rem;
        margin-bottom: 20px;
    }

    /* Poll Options */
    .poll-options {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .poll-option {
        position: relative;
        padding: 14px 18px;
        border-radius: 10px;
        border: 2px solid rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.03);
        cursor: pointer;
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .poll-option:hover:not(.disabled) {
        border-color: var(--primary);
        background: rgba(102, 126, 234, 0.1);
    }

    .poll-option.selected {
        border-color: var(--primary);
        background: rgba(102, 126, 234, 0.15);
    }

    .poll-option.voted {
        cursor: default;
    }

    .poll-option-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        z-index: 2;
    }

    .poll-option-text {
        color: #e2e8f0;
        font-weight: 500;
    }

    .poll-option-stats {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #94a3b8;
        font-size: 0.85rem;
    }

    .poll-option-bar {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        background: linear-gradient(90deg, rgba(102, 126, 234, 0.2) 0%, rgba(118, 75, 162, 0.1) 100%);
        border-radius: 8px;
        transition: width 0.5s ease;
        z-index: 1;
    }

    .poll-option.user-voted .poll-option-bar {
        background: linear-gradient(90deg, rgba(102, 126, 234, 0.4) 0%, rgba(118, 75, 162, 0.3) 100%);
    }

    /* Poll Footer */
    .poll-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }

    .poll-votes {
        color: #64748b;
        font-size: 0.85rem;
    }

    .poll-actions {
        display: flex;
        gap: 10px;
    }

    .poll-action-btn {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.8rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .poll-action-btn.edit {
        background: rgba(102, 126, 234, 0.2);
        color: var(--primary);
    }

    .poll-action-btn.delete {
        background: rgba(239, 68, 68, 0.2);
        color: var(--danger);
    }

    .poll-action-btn:hover {
        opacity: 0.8;
    }

    /* Modal */
    .poll-modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.8);
        z-index: 2000;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .poll-modal.show {
        display: flex;
    }

    .poll-modal-content {
        background: #1a1f3e;
        border-radius: 20px;
        width: 100%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .poll-modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .poll-modal-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #fff;
    }

    .poll-modal-close {
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0;
        line-height: 1;
    }

    .poll-modal-body {
        padding: 24px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        color: #e2e8f0;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .form-input, .form-textarea {
        width: 100%;
        padding: 12px 16px;
        border-radius: 10px;
        border: 2px solid rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.05);
        color: #fff;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-input:focus, .form-textarea:focus {
        outline: none;
        border-color: var(--primary);
        background: rgba(102, 126, 234, 0.1);
    }

    .form-textarea {
        resize: vertical;
        min-height: 80px;
    }

    .options-container {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .option-input-wrapper {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .option-input {
        flex: 1;
    }

    .remove-option-btn {
        padding: 8px 12px;
        background: rgba(239, 68, 68, 0.2);
        color: var(--danger);
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }

    .add-option-btn {
        padding: 10px;
        background: rgba(102, 126, 234, 0.2);
        color: var(--primary);
        border: 2px dashed rgba(102, 126, 234, 0.4);
        border-radius: 10px;
        cursor: pointer;
        text-align: center;
        margin-top: 10px;
        transition: all 0.3s ease;
    }

    .add-option-btn:hover {
        background: rgba(102, 126, 234, 0.3);
        border-color: var(--primary);
    }

    .submit-poll-btn {
        width: 100%;
        padding: 14px 20px;
        background: var(--gradient-2);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        margin-top: 10px;
        transition: all 0.3s ease;
    }

    .submit-poll-btn:hover {
        opacity: 0.9;
        transform: translateY(-2px);
    }

    .submit-poll-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #64748b;
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .empty-state h3 {
        color: #94a3b8;
        margin-bottom: 10px;
    }

    /* Login Prompt */
    .login-prompt {
        text-align: center;
        padding: 100px 20px;
        min-height: 60vh;
    }

    .login-prompt h2 {
        color: #fff;
        font-size: 2rem;
        margin-bottom: 1rem;
    }

    .login-prompt p {
        color: #94a3b8;
        margin-bottom: 2rem;
    }

    .btn-login {
        padding: 15px 40px;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 50px;
        background: var(--gradient-2);
        color: white;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }

    .btn-login:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(233, 67, 122, 0.4);
        color: white;
    }

    /* Loading */
    .loading-spinner {
        text-align: center;
        padding: 40px;
        color: #94a3b8;
    }

    .loading-spinner i {
        font-size: 2rem;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Toast */
    .toast {
        position: fixed;
        bottom: 120px;
        left: 50%;
        transform: translateX(-50%);
        padding: 12px 24px;
        border-radius: 10px;
        color: white;
        font-weight: 500;
        z-index: 3000;
        display: none;
        animation: slideUp 0.3s ease;
    }

    .toast.success { background: var(--success); }
    .toast.error { background: var(--danger); }

    .toast.show { display: block; }

    @keyframes slideUp {
        from { transform: translate(-50%, 20px); opacity: 0; }
        to { transform: translate(-50%, 0); opacity: 1; }
    }

    @media (max-width: 768px) {
        .polls-title { font-size: 1.8rem; }
        .poll-card { padding: 16px; }
        .poll-title { font-size: 1.1rem; }
        .create-poll-btn { bottom: 120px; right: 15px; width: 55px; height: 55px; }
    }

    /* Option Image Upload Styles */
    .option-image-upload {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px dashed rgba(255, 255, 255, 0.2);
        border-radius: 10px;
        cursor: pointer;
        color: #94a3b8;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .option-image-upload:hover {
        background: rgba(102, 126, 234, 0.1);
        border-color: var(--primary);
        color: var(--primary);
    }

    .option-image-upload.has-file {
        background: rgba(16, 185, 129, 0.1);
        border-color: var(--success);
        color: var(--success);
        background-size: cover;
        background-position: center;
        border-style: solid;
    }

    .option-image-upload.has-file i {
        display: none;
    }

    /* Premium Nav Bar */
    .polls-nav-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: rgba(30, 41, 59, 0.6);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 6px 8px 6px 8px; /* Balanced padding */
        margin: 0 auto 30px;
        max-width: 800px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .polls-tabs-group {
        display: flex;
        gap: 4px;
        background: rgba(0,0,0,0.2);
        padding: 4px;
        border-radius: 12px;
    }

    .poll-tab-item {
        background: transparent;
        color: #94a3b8;
        padding: 8px 20px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
    }

    .poll-tab-item:hover {
        color: #e2e8f0;
    }

    .poll-tab-item.active {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .create-poll-btn-premium {
        background: var(--gradient-2);
        color: white;
        padding: 10px 20px;
        border-radius: 10px;
        border: none;
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(233, 67, 122, 0.2);
    }
    
    .create-poll-btn-premium:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(233, 67, 122, 0.3);
        filter: brightness(1.1);
    }

    /* Mobile Responsive Nav */
    @media (max-width: 600px) {
        .polls-nav-bar {
            flex-direction: column;
            gap: 15px;
            padding: 15px;
            background: transparent;
            border: none;
            box-shadow: none;
        }
        .polls-tabs-group {
            width: 100%;
            justify-content: center;
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            padding: 6px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .poll-tab-item { flex: 1; }
        .create-poll-btn-premium {
            width: 100%;
            justify-content: center;
        }
    }
    </style>
</head>

<body class="dark-scheme de-grey">
    <div id="wrapper">
        <?php include('../components/header.php') ?>

        <div class="no-bottom no-top" id="content">
            <?php if (!$is_logged_in): ?>
            <section class="login-prompt">
                <i class="fas fa-poll" style="font-size: 4rem; color: var(--primary); margin-bottom: 1rem;"></i>
                <h2>Login Required</h2>
                <p>Please login to view and participate in community polls!</p>
                <a href="login.php?return=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login Now
                </a>
            </section>
            <?php else: ?>
            
            <section class="polls-hero">
                <h1 class="polls-title">Community Polls</h1>
                <p class="polls-subtitle">Vote on polls and share your opinion with the community!</p>
            </section>

            <div class="polls-nav-bar">
                <div class="polls-tabs-group">
                    <button class="poll-tab-item active" data-filter="all">All Polls</button>
                    <button class="poll-tab-item" data-filter="my">My Polls</button>
                </div>
                
                <button class="create-poll-btn-premium" id="createPollBtn" title="Create Poll">
                    <i class="fas fa-plus"></i> Create Poll
                </button>
            </div>

            <div class="polls-container" id="pollsContainer">
                <div class="loading-spinner">
                    <i class="fas fa-spinner"></i>
                    <p>Loading polls...</p>
                </div>
            </div>



            <?php endif; ?>
        </div>

        <?php include('../components/footer.php') ?>
    </div>

    <!-- Create Poll Modal with Payment -->
    <div class="poll-modal" id="createPollModal">
        <div class="poll-modal-content">
            <div class="poll-modal-header">
                <h3 class="poll-modal-title">Create New Poll</h3>
                <button class="poll-modal-close" id="closeModal">&times;</button>
            </div>
            <div class="poll-modal-body">
                <form id="createPollForm">
                    <div class="form-group">
                        <label class="form-label">Question *</label>
                        <input type="text" class="form-input" id="pollTitle" placeholder="What do you want to ask?" required maxlength="255">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description (optional)</label>
                        <textarea class="form-textarea" id="pollDescription" placeholder="Add more context..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Options *</label>
                        <div class="options-container" id="optionsContainer">
                            <div class="option-input-wrapper">
                                <input type="text" class="form-input option-input" placeholder="Option 1" required maxlength="255">
                                <label class="option-image-upload" title="Upload Image (Optional)">
                                    <i class="fas fa-image"></i>
                                    <input type="file" class="option-image-file" accept="image/*" style="display:none" onchange="PollsApp.handleFileSelect(this)">
                                </label>
                            </div>
                            <div class="option-input-wrapper">
                                <input type="text" class="form-input option-input" placeholder="Option 2" required maxlength="255">
                                <label class="option-image-upload" title="Upload Image (Optional)">
                                    <i class="fas fa-image"></i>
                                    <input type="file" class="option-image-file" accept="image/*" style="display:none" onchange="PollsApp.handleFileSelect(this)">
                                </label>
                            </div>
                        </div>
                        <button type="button" class="add-option-btn" id="addOptionBtn">
                            <i class="fas fa-plus"></i> Add Option
                        </button>
                    </div>

                    <div class="payment-info" style="background: rgba(16, 185, 129, 0.1); padding: 15px; border-radius: 10px; margin-top: 20px; border: 1px solid rgba(16, 185, 129, 0.3);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #e2e8f0; font-weight: 600;">Poll Creation Fee</span>
                            <span style="color: #10b981; font-weight: 700; font-size: 1.2rem;">₹99</span>
                        </div>
                        <p style="color: #94a3b8; font-size: 0.85rem; margin-top: 5px;">Polls are active for 7 days.</p>
                    </div>
                    
                    <button type="submit" class="submit-poll-btn" id="submitPollBtn">
                        <i class="fas fa-bolt"></i> Pay ₹99 & Create Poll
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <?php include('../components/jslinks.php') ?>
    
    <?php if ($is_logged_in): ?>
    <script>
    const PollsApp = {
        currentFilter: 'all',
        polls: [],
        
        init() {
            this.bindEvents();
            this.checkPaymentVerification();
            this.loadPolls();
        },

        async checkPaymentVerification() {
            const urlParams = new URLSearchParams(window.location.search);
            const orderId = urlParams.get('order_id');
            
            if (orderId) {
                // Clear URL param without reload
                window.history.replaceState({}, document.title, window.location.pathname);
                
                this.showToast('Verifying payment...', 'info');
                
                try {
                    const response = await fetch('../backend/polls/verify_poll_payment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ order_id: orderId })
                    });
                    const data = await response.json();
                    
                    if (data.status) {
                        this.showToast('Payment verified! Poll is now active.', 'success');
                        this.loadPolls(); // Refresh list immediately
                    } else {
                        this.showToast(data.error || 'Payment verification failed', 'error');
                    }
                } catch (error) {
                    console.error('Payment verification error:', error);
                    this.showToast('Error verifying payment', 'error');
                }
            }
        },
        
        bindEvents() {
            // Tab switching
            document.querySelectorAll('.poll-tab-item').forEach(tab => {
                tab.addEventListener('click', () => {
                    document.querySelectorAll('.poll-tab-item').forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    this.currentFilter = tab.dataset.filter;
                    this.loadPolls();
                });
            });
            
            // Create poll button
            document.getElementById('createPollBtn').addEventListener('click', () => {
                document.getElementById('createPollModal').classList.add('show');
            });
            
            // Close modal
            document.getElementById('closeModal').addEventListener('click', () => {
                document.getElementById('createPollModal').classList.remove('show');
            });
            
            // Close modal on backdrop click
            document.getElementById('createPollModal').addEventListener('click', (e) => {
                if (e.target === document.getElementById('createPollModal')) {
                    document.getElementById('createPollModal').classList.remove('show');
                }
            });
            
            // Add option
            document.getElementById('addOptionBtn').addEventListener('click', () => {
                this.addOptionInput();
            });
            
            // Submit poll form
            document.getElementById('createPollForm').addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleCreatePollFlow();
            });
            
            // Global click handler for voting logic (delegation)
            document.addEventListener('click', (e) => {
                // Select Option
                if (e.target.closest('.poll-option') && !e.target.closest('.poll-option').classList.contains('disabled')) {
                    this.handleOptionSelect(e.target.closest('.poll-option'));
                }
                
                // Confirm Vote
                if (e.target.closest('.confirm-vote-btn')) {
                    this.handleVoteConfirm(e.target.closest('.confirm-vote-btn'));
                }
            });
        },
        
        // Handle Option Selection (Visual only first)
        handleOptionSelect(optionEl) {
            const pollCard = optionEl.closest('.poll-card');
            
            // Remove selected from siblings
            pollCard.querySelectorAll('.poll-option').forEach(opt => opt.classList.remove('selected'));
            
            // Add selected to clicked
            optionEl.classList.add('selected');
            
            // Show confirm button
            let confirmBtn = pollCard.querySelector('.confirm-vote-container');
            if (!confirmBtn) {
                const footer = pollCard.querySelector('.poll-footer');
                const btnHtml = `
                    <div class="confirm-vote-container" style="width: 100%; margin-top: 10px; text-align: right; animation: fadeIn 0.3s ease;">
                        <button class="confirm-vote-btn" style="background: var(--success); color: white; border: none; padding: 8px 20px; border-radius: 20px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);">
                            Confirm Vote <i class="fas fa-check"></i>
                        </button>
                    </div>
                `;
                // Insert before footer or inside footer's first child? 
                // Let's allow voting logic to inject clearly
                // We'll insert it after options container
                pollCard.querySelector('.poll-options').insertAdjacentHTML('afterend', btnHtml);
            }
        },
        
        // Handle Vote Confirmation
        handleVoteConfirm(btn) {
            const pollCard = btn.closest('.poll-card');
            const selectedOption = pollCard.querySelector('.poll-option.selected');
            
            if (selectedOption) {
                const pollId = selectedOption.dataset.pollId;
                const optionId = selectedOption.dataset.optionId;
                this.vote(pollId, optionId);
                
                // Hide button immediately to prevent double clicks
                btn.closest('.confirm-vote-container').remove();
            }
        },

        async handleCreatePollFlow() {
            const btn = document.getElementById('submitPollBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            btn.disabled = true;

            try {
                // 1. Create Poll (Pending Payment)
                // 1. Create Poll (Pending Payment)
                const formData = this.getPollFormData();
                if (!formData) {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    return; // Stop if validation failed (toast already shown)
                }
                
                // Log for debugging
                console.log('Creating poll with images...');

                const createResp = await fetch('../backend/polls/create_poll.php', {
                    method: 'POST',
                    // HEADERS REMOVED to let browser set boundary for FormData
                    body: formData 
                });
                const createData = await createResp.json();
                
                if (!createData.status) throw new Error(createData.message || 'Failed to initialize poll');
                
                const pollId = createData.poll_id;
                
                // 2. Create Payment Order
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Initiating Payment...';
                
                const orderResp = await fetch('../backend/polls/create_poll_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ poll_id: pollId })
                });
                const orderData = await orderResp.json();
                
                if (!orderData.status) throw new Error(orderData.error || 'Failed to create payment order');
                
                // 3. Open Cashfree Checkout
                // We need Cashfree JS SDK. Assuming it's loaded or we redirect. 
                // Since this is generic, let's look at `custom_register.js` approach.
                // It redirected to intent.php. Does user have Cashfree JS?
                // The implementation in `order.php` suggests an intent flow or JS flow.
                // Let's assume we use the session_id to open checkout or redirect.
                
                // Important: Using the same redirect approach as register for consistency if SDK not present
                // But wait, user might stay on page. Let's try to bundle Cashfree SDK if not here.
                // Or better, redirect to a payment page that returns here.
                
                // Strategy: Redirect to intent logic like registration
                // But we need to come back to polls.php
                
                // Let's use the `payment_session_id`
                if (typeof Cashfree !== 'undefined') {
                    const cashfree = new Cashfree({ mode: "production" }); // or sandbox
                    cashfree.checkout({
                        paymentSessionId: orderData.session,
                        returnUrl: window.location.href // We'll handle verification on load
                    }).then(function() {
                        // This might not be hit if returnUrl handles it
                    });
                } else {
                    // Fallback to loading script or redirecting
                    // Let's load script dynamically if needed
                     const script = document.createElement("script");
                     script.src = "https://sdk.cashfree.com/js/v3/cashfree.js";
                     script.onload = () => {
                         const cashfree = new Cashfree({ mode: "production" });
                         cashfree.checkout({
                             paymentSessionId: orderData.session,
                             returnUrl: window.location.href // We will just reload page and check
                         });
                     };
                     document.head.appendChild(script);
                }

            } catch (error) {
                console.error(error);
                this.showToast(error.message, 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        },
        
        getPollFormData() {
            const title = document.getElementById('pollTitle').value.trim();
            const desc = document.getElementById('pollDescription').value.trim();
            
            // Get Option Texts and Files
            const wrappers = document.querySelectorAll('.option-input-wrapper');
            let validOptionsCount = 0;
            const formData = new FormData();

            formData.append('title', title);
            formData.append('description', desc);
            formData.append('poll_type', 'single'); // Default to single
            
            wrappers.forEach((wrapper, index) => {
                const textInput = wrapper.querySelector('.option-input');
                const fileInput = wrapper.querySelector('.option-image-file');
                const text = textInput.value.trim();

                if (text) {
                    formData.append('option_texts[]', text);
                    if (fileInput.files.length > 0) {
                        // Keyed by index to match backend logic
                        formData.append(`option_images[${validOptionsCount}]`, fileInput.files[0]);
                    }
                    validOptionsCount++;
                }
            });
            
            if (!title) { this.showToast('Question is required', 'error'); return null; }
            if (validOptionsCount < 2) { this.showToast('At least 2 options are required', 'error'); return null; }
            
            return formData;
        },
        


        async verifyPayment(orderId) {
            if (!orderId) return;
            
            const btn = document.querySelector(`button[data-order="${orderId}"]`);
            if(btn) {
                btn.disabled = true;
                btn.textContent = 'Checking...';
            }

            this.showToast('Checking payment status...', 'info');

            try {
                const response = await fetch('../backend/polls/verify_poll_payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId })
                });
                const data = await response.json();
                
                if (data.status) {
                    this.showToast('Payment confirmed! Poll activated.', 'success');
                    this.loadPolls();
                } else {
                    this.showToast(data.error || 'Payment not completed or failed', 'error');
                    if(btn) {
                        btn.disabled = false;
                        btn.textContent = 'Check Payment';
                    }
                }
            } catch (error) {
                console.error('Verification error:', error);
                this.showToast('Network error verifying payment', 'error');
                if(btn) {
                    btn.disabled = false;
                    btn.textContent = 'Check Payment';
                }
            }
        },

        async loadPolls() {
            const container = document.getElementById('pollsContainer');
            container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner"></i><p>Loading polls...</p></div>';
            
            try {
                const response = await fetch(`../backend/polls/get_polls.php?filter=${this.currentFilter}`);
                const data = await response.json();
                
                if (data.status && data.polls.length > 0) {
                    container.innerHTML = data.polls.map(poll => this.renderPollCard(poll)).join('');
                    this.bindPollEvents();
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-poll"></i>
                            <h3>No polls found</h3>
                            <p>Be the first to create a poll!</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading polls:', error);
                container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><h3>Error loading polls</h3></div>';
            }
        },
        
        renderPollCard(poll) {
            const totalVotes = poll.total_votes || 0;
            const creatorInitial = poll.creator_name ? poll.creator_name.charAt(0).toUpperCase() : '?';
            
            let optionsHtml = poll.options.map(opt => {
                const percentage = totalVotes > 0 ? Math.round((opt.votes / totalVotes) * 100) : 0;
                const isUserVoted = poll.user_voted_option === opt.id;
                const votedClass = poll.user_voted ? 'voted' : '';
                const userVotedClass = isUserVoted ? 'user-voted selected' : '';
                
                const imageHtml = opt.image ? 
                    `<div class="poll-option-image" style="width: 40px; height: 40px; border-radius: 6px; overflow: hidden; margin-right: 12px; flex-shrink: 0; background-image: url('${opt.image}'); background-size: cover; background-position: center; border: 1px solid rgba(255,255,255,0.1);"></div>` 
                    : '';

                return `
                    <div class="poll-option ${votedClass} ${userVotedClass}" 
                         data-poll-id="${poll.id}" 
                         data-option-id="${opt.id}"
                         ${poll.user_voted || poll.status === 'closed' ? 'disabled' : ''}>
                        <div class="poll-option-bar" style="width: ${poll.user_voted || poll.status === 'closed' ? percentage : 0}%"></div>
                        <div class="poll-option-content">
                            <div style="display:flex; align-items:center;">
                                ${imageHtml}
                                <span class="poll-option-text">${this.escapeHtml(opt.text)}</span>
                            </div>
                            <span class="poll-option-stats">
                                ${poll.user_voted || poll.status === 'closed' ? `<span>${percentage}%</span>` : ''}
                                ${isUserVoted ? '<i class="fas fa-check-circle" style="color: var(--primary)"></i>' : ''}
                            </span>
                        </div>
                    </div>
                `;
            }).join('');
            
            let actionsHtml = ''; // ... kept existing logic ...
            if (poll.is_owner) {
                actionsHtml = `
                    <div class="poll-actions">
                        <button class="poll-action-btn delete" data-poll-id="${poll.id}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                `;
            }
            
            return `
                <div class="poll-card" data-poll-id="${poll.id}">
                    <div class="poll-header">
                        <div class="poll-creator">
                            <div class="poll-creator-avatar">${creatorInitial}</div>
                            <span>${this.escapeHtml(poll.creator_name)}</span>
                            ${poll.is_admin_poll ? '<span style="color: var(--primary);"><i class="fas fa-shield-alt"></i> Admin</span>' : ''}
                        </div>
                        <span class="poll-status ${poll.status}">
                            ${poll.status === 'active' ? 'Active' : (poll.status === 'pending_payment' ? 'Pending Payment' : 'Closed')}
                        </span>
                    </div>
                     ${poll.status === 'pending_payment' && poll.is_owner ? 
                        `<div style="margin-bottom: 15px;">
                            <button onclick="PollsApp.verifyPayment('${poll.payment_id}')" data-order="${poll.payment_id}" class="poll-action-btn edit" style="width:100%">
                                <i class="fas fa-sync"></i> Check Payment Status
                            </button>
                         </div>` 
                    : ''}
                    
                    <h3 class="poll-title">${this.escapeHtml(poll.title)}</h3>
                    ${poll.description ? `<p class="poll-description">${this.escapeHtml(poll.description)}</p>` : ''}
                    
                    <div class="poll-options">
                        ${optionsHtml}
                    </div>
                    
                    <div class="poll-footer">
                        <span class="poll-votes">
                            <i class="fas fa-vote-yea"></i> ${totalVotes} vote${totalVotes !== 1 ? 's' : ''}
                        </span>
                        ${poll.is_owner && poll.status === 'active' ? this.getExpiryTimerHtml(poll.created_on) : ''}
                        ${actionsHtml}
                    </div>
                </div>
            `;
        },
        
        bindPollEvents() {
            // Delete poll
            document.querySelectorAll('.poll-action-btn.delete').forEach(btn => {
                btn.addEventListener('click', async () => {
                    if (confirm('Are you sure you want to delete this poll?')) {
                        await this.deletePoll(btn.dataset.pollId);
                    }
                });
            });
        },
        
        async vote(pollId, optionId) {
            try {
                const response = await fetch('../backend/polls/vote_poll.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ poll_id: pollId, option_id: optionId })
                });
                
                const data = await response.json();
                
                if (data.status) {
                    this.showToast('Vote recorded!', 'success');
                    this.loadPolls();
                } else {
                    this.showToast(data.message || 'Failed to vote', 'error');
                }
            } catch (error) {
                console.error('Vote error:', error);
                this.showToast('Error recording vote', 'error');
            }
        },
        
        async deletePoll(pollId) {
            try {
                const response = await fetch('../backend/polls/delete_poll.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ poll_id: pollId })
                });
                
                const data = await response.json();
                
                if (data.status) {
                    this.showToast('Poll deleted!', 'success');
                    this.loadPolls();
                } else {
                    this.showToast(data.message || 'Failed to delete', 'error');
                }
            } catch (error) {
                console.error('Delete error:', error);
                this.showToast('Error deleting poll', 'error');
            }
        },
        
        async createPoll() {
            const title = document.getElementById('pollTitle').value.trim();
            const description = document.getElementById('pollDescription').value.trim();
            // Checking logic moved to getPollFormData
            const formData = this.getPollFormData();
            if (!formData) return; // Validation failed
            

            
            const submitBtn = document.getElementById('submitPollBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
            
            try {
                const response = await fetch('../backend/polls/create_poll.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status) {
                    this.showToast('Poll created!', 'success');
                    document.getElementById('createPollModal').classList.remove('show');
                    document.getElementById('createPollForm').reset();
                    this.resetOptionsContainer();
                    this.loadPolls();
                } else {
                    this.showToast(data.message || 'Failed to create poll', 'error');
                }
            } catch (error) {
                console.error('Create poll error:', error);
                this.showToast('Error creating poll', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Create Poll';
            }
        },
        
        addOptionInput() {
            const container = document.getElementById('optionsContainer');
            const count = container.querySelectorAll('.option-input-wrapper').length;
            
            if (count >= 10) {
                this.showToast('Maximum 10 options allowed', 'error');
                return;
            }
            
            const wrapper = document.createElement('div');
            wrapper.className = 'option-input-wrapper';
            wrapper.innerHTML = `
                <input type="text" class="form-input option-input" placeholder="Option ${count + 1}" required maxlength="255">
                <label class="option-image-upload" title="Upload Image (Optional)">
                    <i class="fas fa-image"></i>
                    <input type="file" class="option-image-file" accept="image/*" style="display:none" onchange="PollsApp.handleFileSelect(this)">
                </label>
                <button type="button" class="remove-option-btn"><i class="fas fa-times"></i></button>
            `;
            
            wrapper.querySelector('.remove-option-btn').addEventListener('click', () => {
                wrapper.remove();
            });
            
            container.appendChild(wrapper);
        },

        handleFileSelect(input) {
            const label = input.closest('.option-image-upload');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    label.style.backgroundImage = `url('${e.target.result}')`;
                    label.classList.add('has-file');
                    label.title = input.files[0].name;
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                label.style.backgroundImage = '';
                label.classList.remove('has-file');
                label.title = 'Upload Image (Optional)';
            }
        },

        getExpiryTimerHtml(createdOn) {
            const createdDate = new Date(createdOn);
            const expiryDate = new Date(createdDate.getTime() + (7 * 24 * 60 * 60 * 1000)); // 7 days
            const now = new Date();
            const diff = expiryDate - now;
            
            if (diff <= 0) {
                return '<span class="poll-timer expired"><i class="fas fa-clock"></i> Expired</span>';
            }
            
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            
            let timeStr = '';
            if (days > 0) timeStr += `${days}d `;
            if (hours > 0) timeStr += `${hours}h `;
            if (days === 0) timeStr += `${mins}m`;
            
            return `<span class="poll-timer"><i class="fas fa-hourglass-half"></i> ${timeStr.trim()} left</span>`;
        },
        
        resetOptionsContainer() {
            const container = document.getElementById('optionsContainer');
            container.innerHTML = `
                <div class="option-input-wrapper">
                    <input type="text" class="form-input option-input" placeholder="Option 1" required maxlength="255">
                    <label class="option-image-upload" title="Upload Image (Optional)">
                        <i class="fas fa-image"></i>
                        <input type="file" class="option-image-file" accept="image/*" style="display:none" onchange="PollsApp.handleFileSelect(this)">
                    </label>
                </div>
                <div class="option-input-wrapper">
                    <input type="text" class="form-input option-input" placeholder="Option 2" required maxlength="255">
                    <label class="option-image-upload" title="Upload Image (Optional)">
                        <i class="fas fa-image"></i>
                        <input type="file" class="option-image-file" accept="image/*" style="display:none" onchange="PollsApp.handleFileSelect(this)">
                    </label>
                </div>
            `;
        },
        
        showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast ${type} show`;
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        },
        
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
    document.addEventListener('DOMContentLoaded', () => {
        PollsApp.init();
    });
    </script>
    <?php endif; ?>
</body>
</html>
