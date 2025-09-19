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
    <style>
    /* Reddit-like styles */
    :root {
        --reddit-bg: #1A1A1B;
        --reddit-card: #272729;
        --reddit-accent: #8364E2;
        --reddit-text: #D7DADC;
        --reddit-border: #343536;
        --reddit-hover: #2D2D2E;
    }

    .btn-attachment {
        padding: 8px;
        cursor: pointer;
        color: var(--reddit-text);
        transition: color 0.2s ease;
        opacity: 0.8;
    }

    .btn-attachment:hover {
        opacity: 1;
    }

    .attachment-preview {
        padding: 8px 12px;
        background: var(--reddit-hover);
        border: 1px solid var(--reddit-border);
        border-radius: 4px;
        margin-top: 8px;
    }

    .preview-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.9em;
    }

    .btn-remove {
        background: none;
        border: none;
        color: rgba(255, 255, 255, 0.7);
        cursor: pointer;
        font-size: 1.2em;
        padding: 0 5px;
    }

    .btn-remove:hover {
        color: white;
    }

    .message-attachment {
        margin-top: 8px;
        width: 100%;
        max-width: 450px;
        border-radius: 4px;
        overflow: hidden;
    }

    .message-attachment.file {
        padding: 8px;
        background: var(--reddit-hover);
        border: 1px solid var(--reddit-border);
        border-radius: 4px;
        width: auto;
    }

    .message-attachment img {
        width: 100%;
        height: 250px;
        /* Fixed height */
        object-fit: contain;
        /* Maintain aspect ratio */
        background: var(--reddit-bg);
        border-radius: 4px;
        border: 1px solid var(--reddit-border);
    }

    @media (max-width: 768px) {
        .message-attachment {
            max-width: 100%;
        }

        .message-attachment img {
            height: 200px;
            /* Slightly smaller height on mobile */
        }
    }

    .message-attachment a {
        color: #8364E2;
        text-decoration: none;
        display: flex;
        align-items: center;
        font-size: 0.9em;
    }

    .message-attachment i {
        margin-right: 5px;
    }

    .message-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 0.9em;
    }

    .reactions-group {
        display: flex;
        gap: 6px;
    }

    .btn-reaction {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 6px 12px;
        border-radius: 20px;
        color: rgba(255, 255, 255, 0.7);
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
        font-size: 0.9em;
        backdrop-filter: blur(5px);
    }

    .btn-reaction:hover {
        background: rgba(255, 255, 255, 0.15);
        color: white;
        transform: translateY(-1px);
    }

    .btn-reaction.active {
        background: linear-gradient(135deg, #8364E2 0%, #6F4ED4 100%);
        color: white;
        border-color: transparent;
        box-shadow: 0 2px 4px rgba(131, 100, 226, 0.3);
    }

    .btn-reaction i {
        font-size: 0.9em;
    }

    .reaction-count {
        font-weight: 600;
        min-width: 20px;
        text-align: center;
    }

    .timestamp {
        margin-top: 8px;
        margin-left: 15px;
        font-size: 0.8em;
        color: rgba(255, 255, 255, 0.5);
    }

    .community-tabs {
        overflow-x: auto;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        -ms-overflow-style: auto;
        padding: 15px 0;
        margin-bottom: -5px;
        /* Compensate for scrollbar space */
    }

    .community-tabs::-webkit-scrollbar {
        height: 4px;
        display: block;
    }

    .community-tabs::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 2px;
    }

    .community-tabs::-webkit-scrollbar-thumb {
        background: rgba(131, 100, 226, 0.3);
        border-radius: 2px;
        transition: background 0.2s ease;
    }

    .community-tabs::-webkit-scrollbar-thumb:hover {
        background: rgba(131, 100, 226, 0.5);
    }

    .community-tab {
        display: inline-block;
        padding: 10px 20px;
        margin: 0 5px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .community-tab.active {
        background: #8364E2;
        color: white;
    }

    .chat-container {
        height: calc(100vh - 200px);
        min-height: 400px;
        background: var(--reddit-bg);
        border: 1px solid var(--reddit-border);
        border-radius: 4px;
        margin: 20px 0;
        display: flex;
        flex-direction: column;
    }

    .chat-messages {
        flex-grow: 1;
        overflow-y: auto;
        padding: 16px;
        scrollbar-width: thin;
        scrollbar-color: rgba(131, 100, 226, 0.3) transparent;
        -ms-overflow-style: auto;
    }

    /* Webkit scrollbar styling (Chrome, Safari, newer Edge) */
    .chat-messages::-webkit-scrollbar {
        width: 4px;
        display: block;
    }

    .chat-messages::-webkit-scrollbar-track {
        background: transparent;
    }

    .chat-messages::-webkit-scrollbar-thumb {
        background: rgba(131, 100, 226, 0.3);
        border-radius: 4px;
        transition: background 0.2s ease;
    }

    .chat-messages::-webkit-scrollbar-thumb:hover {
        background: rgba(131, 100, 226, 0.5);
    }

    /* Firefox scrollbar styling */
    @supports (scrollbar-color: auto) {
        .chat-messages {
            scrollbar-width: thin;
            scrollbar-color: rgba(131, 100, 226, 0.3) transparent;
        }
    }

    /* For Edge support */
    @supports (-ms-overflow-style: none) {
        .chat-messages {
            -ms-overflow-style: -ms-autohiding-scrollbar;
        }
    }

    .message {
        margin: 8px 0;
        padding: 12px;
        border-radius: 4px;
        width: fit-content;
        max-width: min(600px, 80%);
        background: var(--reddit-card);
        border: 1px solid var(--reddit-border);
        transition: background 0.2s ease;
    }

    .message:hover {
        background: var(--reddit-hover);
    }

    .message.sent {
        margin-left: auto;
        background: var(--reddit-card);
    }

    .message.received {
        margin-right: auto;
        background: var(--reddit-card);
    }

    @media (max-width: 768px) {
        .message {
            max-width: 95%;
        }

        .chat-container {
            height: calc(100vh - 180px);
            margin: 10px 0;
        }
    }

    .message-sender {
        font-weight: 600;
        margin-bottom: 5px;
        color: rgba(255, 255, 255, 0.9);
    }

    .message-content {
        line-height: 1.4;
        color: rgba(255, 255, 255, 0.8);
    }

    .chat-input {
        padding: 12px;
        border-top: 1px solid var(--reddit-border);
        display: flex;
        gap: 8px;
        background: var(--reddit-card);
        align-items: center;
    }

    .chat-input input {
        flex-grow: 1;
        background: var(--reddit-bg);
        border: 1px solid var(--reddit-border);
        padding: 8px 12px;
        border-radius: 4px;
        color: var(--reddit-text);
        font-size: 14px;
        min-height: 38px;
    }

    .chat-input input:focus {
        border-color: var(--reddit-accent);
        outline: none;
    }

    .btn-main {
        background: var(--reddit-accent);
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        color: white;
        font-weight: 600;
        cursor: pointer;
        transition: opacity 0.2s ease;
        height: 38px;
    }

    .btn-main:hover {
        opacity: 0.9;
    }

    @media (max-width: 768px) {
        .chat-input {
            padding: 8px;
        }

        .btn-main {
            padding: 8px 12px;
        }
    }

    .members-list {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        padding: 20px;
        margin-left: 10px;
        margin-top: 4.5%;
        overflow-y: auto;
        max-height: calc(100vh - 200px);
        scrollbar-width: thin;
        scrollbar-color: rgba(131, 100, 226, 0.3) transparent;
    }

    .members-list::-webkit-scrollbar {
        width: 4px;
        display: block;
    }

    .members-list::-webkit-scrollbar-track {
        background: transparent;
    }

    .members-list::-webkit-scrollbar-thumb {
        background: rgba(131, 100, 226, 0.3);
        border-radius: 4px;
        transition: background 0.2s ease;
    }

    .members-list::-webkit-scrollbar-thumb:hover {
        background: rgba(131, 100, 226, 0.5);
    }

    .member-item {
        display: flex;
        align-items: center;
        padding: 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .member-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        margin-right: 15px;
    }

    .timestamp {
        font-size: 0.8em;
        opacity: 0.6;
    }

    /* Image Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.9);
        justify-content: center;
        align-items: center;
    }

    .modal.active {
        display: flex;
    }

    .modal img {
        max-width: 90%;
        max-height: 90vh;
        object-fit: contain;
        border-radius: 8px;
    }

    .modal .close {
        position: absolute;
        top: 20px;
        right: 20px;
        color: white;
        font-size: 28px;
        cursor: pointer;
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .modal .close:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: rotate(90deg);
    }

    /* Reply Button Style */
    .btn-reply {
        color: rgba(255, 255, 255, 0.7);
        border: none;
        background: none;
        padding: 4px 8px;
        font-size: 0.85em;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 4px;
        transition: all 0.2s ease;
        border-radius: 4px;
    }

    .btn-reply:hover {
        color: white;
        background: rgba(255, 255, 255, 0.1);
    }

    .btn-reply i {
        font-size: 0.9em;
    }

    /* Make reactions more compact */
    .btn-reaction {
        padding: 4px 8px;
        font-size: 0.8em;
    }

    .reaction-count {
        min-width: 16px;
        font-size: 0.9em;
    }

    /* Report button styling */
    .btn-report {
        color: rgba(255, 255, 255, 0.5);
        background: none;
        border: none;
        font-size: 0.8em;
        padding: 4px 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 4px;
        opacity: 0;
        transition: all 0.2s ease;
    }

    .message:hover .btn-report {
        opacity: 1;
    }

    .btn-report:hover {
        color: #ff4444;
        background: rgba(255, 68, 68, 0.1);
        border-radius: 4px;
    }

    /* Report Modal */
    .report-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }

    .report-modal.active {
        display: flex;
    }

    .report-modal-content {
        background: var(--reddit-card);
        border: 1px solid var(--reddit-border);
        border-radius: 8px;
        padding: 20px;
        width: 90%;
        max-width: 500px;
    }

    .report-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .report-modal-title {
        font-size: 1.2em;
        font-weight: bold;
        color: #fff;
    }

    .report-modal-close {
        background: none;
        border: none;
        color: rgba(255, 255, 255, 0.5);
        font-size: 1.5em;
        cursor: pointer;
    }

    .report-modal-close:hover {
        color: #fff;
    }

    .report-form textarea {
        width: 100%;
        background: var(--reddit-bg);
        border: 1px solid var(--reddit-border);
        color: #fff;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 15px;
        min-height: 100px;
        resize: vertical;
    }

    .report-form textarea:focus {
        border-color: #8364E2;
        outline: none;
    }

    .report-form button {
        background: #ff4444;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
    }

    .report-form button:hover {
        background: #ff2020;
    }
    </style>
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