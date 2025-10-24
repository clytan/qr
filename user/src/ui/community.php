<?php
include '../backend/dbconfig/connection.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="zxx">

<head>
    <title>Community - ZQR</title>
    <link rel="icon" href="../assets/images/icon-red.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="ZQR Community" name="description" />
    <?php include('../components/csslinks.php') ?>

    <link href="community_additional_styles.css" rel="stylesheet" type="text/css" />
    <link href="community_mod_styles.css" rel="stylesheet" type="text/css" />
    <link href="timeout_styles.css" rel="stylesheet" type="text/css" />
</head>

<body class="dark-scheme de-grey">
    <div id="wrapper">
        <?php include('../components/header.php') ?>
        <div class="no-bottom no-top" id="content">
            <div id="top"></div>
            <section id="section-community" aria-label="section">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="community-tabs" id="communityTabs">
                                <!-- Communities will be loaded here -->
                                <div class="text-center">Loading communities...</div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="chat-container">
                                <div class="chat-messages" id="chatMessages">
                                    <!-- Messages will be loaded here -->
                                </div>
                                <div class="chat-input" style="display: none;">
                                    <label for="attachment" class="btn-attachment">
                                        <i class="fa fa-paperclip"></i>
                                    </label>
                                    <input type="file" id="attachment" style="display: none;">
                                    <input type="text" id="messageInput" placeholder="Type your message...">
                                    <button class="btn-main" onclick="sendMessage()">Send</button>
                                </div>
                                <div id="attachmentPreview" class="attachment-preview" style="display: none;">
                                    <div class="preview-content">
                                        <span id="attachmentName"></span>
                                        <button class="btn-remove" onclick="removeAttachment()">×</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="members-list">
                                <h4>Community Members</h4>
                                <div id="membersList">
                                    <!-- Members will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <?php include('../components/footer.php'); ?>

    </div>

    <!-- Report Modal -->
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

    <?php include('../components/jslinks.php'); ?>
    <script src="custom_js/community.js"></script>
    <script src="custom_js/report.js"></script>
</body>

</html>