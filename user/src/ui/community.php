<?php
include '../backend/dbconfig/connection.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$hide_header_extras = true; // Hide notification/wallet buttons on this page
?>
<!DOCTYPE html>
<html lang="zxx">

<head>
    <title>Community - ZQR</title>
    <link rel="icon" href="../assets/logo2.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="ZQR Community" name="description" />
    <?php include('../components/csslinks.php') ?>

    <link href="discord_chat_layout.css?v=<?php echo time(); ?>" rel="stylesheet" type="text/css" />
    <link href="community_mod_styles.css?v=<?php echo time(); ?>" rel="stylesheet" type="text/css" />
    <link href="timeout_styles.css?v=<?php echo time(); ?>" rel="stylesheet" type="text/css" />
</head>

<body class="dark-scheme de-grey">
    <div id="wrapper">
        <?php // Header hidden for full chat experience - include('../components/header.php') ?>
        <div class="no-bottom no-top" id="content" style="padding-top: 0 !important;">
            <div id="top"></div>
            <section id="section-community" aria-label="section">
                <div class="container">

                    <!-- Discord-Style Chat Layout -->
                    <div class="discord-chat-layout">
                        <!-- Chat Section -->
                        <div class="discord-chat-section">
                            <div class="chat-messages" id="chatMessages">
                                <!-- Messages will be loaded here -->
                            </div>
                            <div class="chat-input-wrapper">
                                <!-- Attachment Preview -->
                                <div id="attachmentPreview" class="attachment-preview" style="display: none;">
                                    <div class="preview-content">
                                        <span id="attachmentName"></span>
                                        <button class="btn-remove" onclick="removeAttachment()">×</button>
                                    </div>
                                </div>

                                <div class="chat-input">
                                    <!-- Expandable Actions Button -->
                                    <button class="input-toggle-btn" id="toggleActionsBtn"
                                        onclick="toggleInputActions()" title="Actions">
                                        <i class="fa fa-plus"></i>
                                    </button>

                                    <!-- Hidden Actions (expand on click) -->
                                    <div class="input-actions-group" id="inputActionsGroup" style="display: none;">
                                        <button class="input-action-btn" onclick="toggleEmojiPicker()" title="Emoji">
                                            <i class="fa fa-smile-o"></i>
                                        </button>

                                        <button class="input-action-btn" onclick="toggleGifPicker()" title="GIF">
                                            <i class="fa fa-gift"></i>
                                        </button>

                                        <label for="attachment" class="input-action-btn" title="Attach file">
                                            <i class="fa fa-paperclip"></i>
                                        </label>
                                        <input type="file" id="attachment" accept="image/*,.pdf,.doc,.docx,.txt"
                                            style="display: none;">
                                    </div>

                                    <input type="text" id="messageInput" placeholder="Message in Community Chat">

                                    <button class="btn-send" onclick="sendMessage()" title="Send">
                                        <i class="fa fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Emoji Picker -->
                            <div id="emojiPicker" class="emoji-picker" style="display: none;">
                                <div class="picker-header">
                                    <span class="picker-title">Emojis</span>
                                    <button class="picker-close" onclick="closeEmojiPicker()">×</button>
                                </div>
                                <div class="emoji-tabs">
                                    <button class="emoji-tab active" onclick="showEmojiCategory('smileys')">😊</button>
                                    <button class="emoji-tab" onclick="showEmojiCategory('gestures')">👋</button>
                                    <button class="emoji-tab" onclick="showEmojiCategory('hearts')">❤️</button>
                                    <button class="emoji-tab" onclick="showEmojiCategory('objects')">🎉</button>
                                </div>
                                <div class="emoji-grid" id="emojiGrid">
                                    <!-- Emojis will be populated here -->
                                </div>
                            </div>

                            <!-- GIF Picker -->
                            <div id="gifPicker" class="gif-picker" style="display: none;">
                                <div class="picker-header">
                                    <span class="picker-title">GIFs</span>
                                    <button class="picker-close" onclick="closeGifPicker()">×</button>
                                </div>
                                <div class="gif-search">
                                    <input type="text" id="gifSearchInput" placeholder="Search GIFs..."
                                        onkeyup="searchGifs(this.value)">
                                </div>
                                <div class="gif-grid" id="gifGrid">
                                    <div class="gif-loading">Loading GIFs...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="community-dropdown-wrapper">
                                <button class="community-dropdown-btn" id="communityDropdownBtn">
                                    <i class="fa fa-comments"></i>
                                    <span id="selectedCommunityName">Select Community</span>
                                    <i class="fa fa-chevron-down dropdown-arrow"></i>
                                </button>
                                <div class="community-dropdown-menu" id="communityDropdownMenu">
                                    <!-- Communities will be loaded here -->
                                    <div class="text-center" style="padding: 15px; color: #94a3b8;">Loading communities...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Members Sidebar -->
                    <div class="discord-members-section" style="display: none;">
                        <div class="members-header">Community Members</div>
                        <div class="members-list" id="membersList">
                            <!-- Members will be loaded here -->
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <?php include('../components/footer.php'); ?>

    </div>

    <!-- Report Modal (outside wrapper for proper positioning) -->
    <div id="reportModal" class="report-modal">
        <div class="report-modal-content">
            <div class="report-modal-header">
                <div class="report-modal-title">Report Message</div>
                <button class="report-modal-close" onclick="closeReportModal()">×</button>
            </div>
            <form class="report-form" onsubmit="submitReport(event)">
                <input type="hidden" id="reportMessageId">
                <textarea id="reportReason" placeholder="Please describe why you're reporting this message..."
                    required></textarea>
                <button type="submit">Submit Report</button>
            </form>
        </div>
    </div>

    <!-- Image Lightbox Modal -->
    <div id="imageLightbox" class="image-lightbox" onclick="closeLightbox()">
        <div class="lightbox-content" onclick="event.stopPropagation()">
            <button class="lightbox-close" onclick="closeLightbox()">×</button>
            <img id="lightboxImage" src="" alt="Expanded image" class="lightbox-image">
        </div>
        <div class="lightbox-hint">Click anywhere to close</div>
    </div>

    <?php include('../components/jslinks.php'); ?>
    <script src="custom_js/community.js?v=<?php echo time(); ?>"></script>
    <script src="custom_js/report.js"></script>
</body>

</html>