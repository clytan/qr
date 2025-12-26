<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<?php
// Check if user is logged in
$header_is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
?>

<header id="zokli-header" class="transparent scroll-dark">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="header-content d-flex align-items-center justify-content-between">
                    <!-- Left Section: Logo -->
                    <div class="left-section">
                        <div id="zokli-logo">
                            <a href="index.php">
                                <img alt="Logo" class="logo-image" src="../../../logo/logo.png" />
                            </a>
                        </div>
                    </div>
                    
                    <!-- Center Section: Logo Word (Zokli) -->
                    <div class="center-section">
                        <a href="index.php">
                            <img alt="Zokli" class="logo-word-image" src="../../../logo/logo-word.png" />
                        </a>
                    </div>
                    
                    <!-- Right Section: Notification & Wallet -->
                    <div class="right-section">
                        <?php if ($header_is_logged_in): ?>
                        <!-- Notifications Dropdown -->
                        <div class="notification-dropdown" id="zokli-notification-dropdown">
                            <a href="javascript:void(0)" class="btn-main btn-notification" id="zokli-notification-btn">
                                <i class="fas fa-bell"></i>
                                <span class="notification-count" id="zokli-notification-count" style="display: none;">0</span>
                            </a>
                            <div class="notification-content" id="zokli-notification-content">
                                <div class="notification-header">
                                    <h4>Notifications</h4>
                                </div>
                                <div class="notification-list" id="zokli-notification-list">
                                    <!-- Notifications will be inserted here via JavaScript -->
                                </div>
                            </div>
                        </div>
                        <!-- Wallet Button -->
                        <a href="wallet.php" class="btn-main btn-wallet" id="zokli-wallet-btn" title="Wallet">
                            <i class="fas fa-wallet"></i>
                            <span class="wallet-balance" style="display:none;"></span>
                        </a>
                        <?php endif; ?>
                    </div>
                    <style>
                    /* Header Base Styles */
                    #zokli-header {
                        padding: 10px 0;
                        min-height: 70px;
                        display: flex;
                        align-items: center;
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        z-index: 9000;
                        background: rgba(15, 23, 42, 0.95);
                        backdrop-filter: blur(10px);
                        -webkit-backdrop-filter: blur(10px);
                    }

                    .header-content {
                        min-height: 70px;
                        width: 100%;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    }

                    .left-section {
                        display: flex;
                        align-items: center;
                        flex: 0 0 auto;
                    }
                    
                    .center-section {
                        position: absolute;
                        left: 50%;
                        transform: translateX(-50%);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    
                    .center-section a {
                        display: flex;
                        align-items: center;
                    }
                    
                    .logo-word-image {
                        height: 28px;
                        width: auto;
                    }

                    .right-section {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        flex: 0 0 auto;
                    }
                    
                    #zokli-logo {
                        display: flex;
                        align-items: center;
                    }

                    .logo-image {
                        height: 45px;
                        width: auto;
                    }

                    #zokli-notification-dropdown {
                        position: relative;
                        z-index: 9999;
                    }

    /* Gradient styled notification & wallet buttons */
    header a.btn-notification,
    header a.btn-wallet,
    header a.btn-rewards,
    header .btn-notification,
    header .btn-wallet,
    header .btn-rewards,
    a.btn-notification,
    a.btn-wallet,
    a.btn-rewards,
    .btn-notification,
    .btn-wallet,
    .btn-rewards {
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 28px !important;
        height: 28px !important;
        border-radius: 5px !important;
        background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%) !important;
        border: none !important;
        color: #ffffff !important;
        text-decoration: none !important;
        position: relative;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-shadow: 0 4px 15px rgba(233, 67, 122, 0.3) !important;
        padding: 0 !important;
        min-width: auto !important;
    }

    header a.btn-notification i,
    header a.btn-wallet i,
    header a.btn-rewards i,
    header .btn-notification i,
    header .btn-wallet i,
    header .btn-rewards i,
    a.btn-notification i,
    a.btn-wallet i,
    a.btn-rewards i,
    .btn-notification i,
    .btn-wallet i,
    .btn-rewards i {
        font-size: 17px !important;
        display: inline-block !important;
        color: #ffffff !important;
    }

    header a.btn-notification:hover,
    header a.btn-wallet:hover,
    header a.btn-rewards:hover,
    header .btn-notification:hover,
    header .btn-wallet:hover,
    header .btn-rewards:hover,
    a.btn-notification:hover,
    a.btn-wallet:hover,
    a.btn-rewards:hover,
    .btn-notification:hover,
    .btn-wallet:hover,
    .btn-rewards:hover {
        background: linear-gradient(135deg, #d43668 0%, #d5623f 50%, #d49a20 100%) !important;
        transform: translateY(-2px) scale(1.05) !important;
        box-shadow: 0 6px 20px rgba(233, 67, 122, 0.5) !important;
    }
    
                    .notification-content {
                        position: absolute;
                        right: 0;
                        top: 50px;
                        width: 300px;
                                        min-width: 220px;
                        background: white;
                        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
                        border-radius: 8px;
                        display: none;
                        z-index: 999999;
                        opacity: 0;
                        transition: opacity 0.12s ease-in-out;
                    }

                    .notification-content.open {
                        display: block !important;
                        opacity: 1 !important;
                    }

                    @media screen and (min-width: 769px) {
                        .notification-dropdown {
                            position: relative;
                            display: flex !important;
                            align-items: center;
                        }

                        .notification-content {
                            position: absolute;
                            right: 0;
                            top: 50px;
                            width: 300px;
                        }

                        .btn-notification,
                        .btn-wallet {
                            color: #ffffffff !important;
                            opacity: 1 !important;
                            visibility: visible !important;
                        }

                        .btn-notification i,
                        .btn-wallet i {
                            color: #ffffffff !important;
                            opacity: 1 !important;
                            visibility: visible !important;
                        }

                        .right-section {
                            opacity: 1 !important;
                            visibility: visible !important;
                        }
                    }

                    @media screen and (max-width: 768px) {
                        .notification-content {
                            position: fixed;
                            width: calc(100vw - 20px);
                            right: 10px;
                            left: 10px;
                            top: 70px;
                        }

                        .btn-notification,
                        .btn-wallet {
                            color: #fff !important;
                            background: transparent !important;
                            border-radius: 12px !important;
                        }

                        .btn-notification:hover,
                        .btn-wallet:hover {
                            background: rgba(255, 255, 255, 0.15) !important;
                        }
                    }

                    .notification-header {
                        padding: 15px;
                        border-bottom: 1px solid #eee;
                    }

                    .notification-header h4 {
                        margin: 0;
                        color: #333;
                        font-size: 16px;
                    }

                    #zokli-notification-list {
                        max-height: 300px;
                        overflow-y: auto;
                        padding: 10px;
                    }

                    #zokli-logo {
                        margin: 0;
                        padding: 0;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                    }

                    .centered-logo {
                        width: 240px !important;
                        height: auto !important;
                        min-width: 180px !important;
                        display: block !important;
                        margin: 0 auto !important;
                        max-width: none !important;
                    }

                    @media (max-width: 768px) {
                        .centered-logo {
                            width: 180px !important;
                            height: auto;
                        }

                        #zokli-header {
                            min-height: 55px !important;
                            height: 58px !important; /* Force explicit height */
                            padding: 0 !important;
                            overflow: hidden !important;
                            display: flex !important;
                            align-items: center !important;
                        }

                        .header-content {
                            min-height: 60px !important;
                            height: 100% !important;
                            padding: 0 6px !important; /* Reduced side padding to move elements to edges */
                        }
                        
                        .logo-image {
                            height: 30px;
                            margin-left: -4px; /* Move logo closer to left edge */
                        }
                        
                        .logo-word-image {
                            height: 20px;
                        }

                        .btn-notification,
                        .btn-wallet {
                            border-radius: 8px !important;
                            width: 30px !important;
                            height: 30px !important;
                            margin: 0 !important; /* Remove any default margins */
                        }
                        
                        .btn-notification i,
                        .btn-wallet i {
                            font-size: 13px !important;
                        }
                        
                        .right-section {
                            gap: 5px;
                            margin-right: -10px; /* Move buttons closer to right edge */
                        }
                        
                        .notification-count {
                            top: -5px !important;
                            right: -5px !important;
                            font-size: 9px !important;
                            min-width: 14px !important;
                            height: 14px !important;
                            padding: 1px 4px !important;
                        }
                    }
                    </style>
                    <!-- <div class="de-flex-col">
                            <input id="quick_search" class="xs-hide style-2" name="quick_search"
                                placeholder="search item here..." type="text" />
                        </div> -->
                </div>
                <div class="de-flex-col header-col-mid d-none">
                    <!-- mainmenu begin -->
                    <ul id="mainmenu">
                        <li>
                            <a href="index.php">HOME<span></span></a>
                        </li>
                        <li>
                            <a href="community.php">CHAT<span></span></a>
                        </li>
                        <li>
                            <a href="login.php">LOGIN<span></span></a>
                        </li>
                        <li>
                            <a href="register.php">REGISTER<span></span></a>
                        </li>
                        <li>
                            <a href="admin\src\ui\login.php">admin<span></span></a>
                        </li>
                    </ul>
                    <!-- mainmenu close -->
                    <div class="menu_side_area">
                        <a href="wallet.php" class="btn-main btn-wallet"><i
                                class="icon_wallet_alt"></i><span>Wallet</span></a>
                        <span id="menu-btn"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .notification-count {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #FF0000;
        color: white;
        border-radius: 12px;
        padding: 2px 6px;
        min-width: 18px;
        height: 18px;
        font-size: 11px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        line-height: 1;
        box-shadow: 0 0 0 2px #1a1a1a;
    }

    .notification-content {
        background: white;
        border-radius: 5px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .notification-item {
        padding: 12px 16px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .notification-item:hover {
        background: #f8f9fa;
    }

    .notification-item.unread {
        background: #f0f7ff;
    }

    .notification-content-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
    }

    .notification-main {
        flex: 1;
        min-width: 0;
        /* Prevents flex item from overflowing */
    }

    .notification-item .message {
        font-size: 13px;
        color: #1a1a1a;
        line-height: 1.4;
        margin: 0;
        word-wrap: break-word;
        font-weight: 400;
    }

    .notification-time {
        font-size: 12px;
        color: #8e8e8e;
        white-space: nowrap;
        font-weight: 400;
        margin-left: 8px;
    }

    .notification-item.unread .notification-sender {
        color: #0066cc;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const notificationBtn = document.getElementById('zokli-notification-btn');
        const notificationContent = document.getElementById('zokli-notification-content');
        const notificationCount = document.getElementById('zokli-notification-count');
        const notificationList = document.getElementById('zokli-notification-list');

        // Safety guards and diagnostics
        if (!notificationBtn) console.warn('zokli-notification-btn element not found');
        if (!notificationContent) console.warn('zokli-notification-content element not found');
        if (!notificationList) console.warn('zokli-notification-list element not found');

        if (!notificationBtn || !notificationContent || !notificationList) {
            console.error('Notification UI elements missing — aborting notification setup');
            return;
        }

        function formatTimeAgo(dateString) {
            const now = new Date();
            const date = new Date(dateString);
            const secondsPast = (now.getTime() - date.getTime()) / 1000;
            const minutesPast = Math.floor(secondsPast / 60);
            const hoursPast = Math.floor(minutesPast / 60);
            const daysPast = Math.floor(hoursPast / 24);
            const weeksPast = Math.floor(daysPast / 7);

            if (secondsPast < 60) {
                return 'just now';
            } else if (minutesPast < 60) {
                return `${minutesPast}m ago`;
            } else if (hoursPast < 24) {
                return `${hoursPast}h ago`;
            } else if (daysPast < 7) {
                return `${daysPast}d ago`;
            } else if (weeksPast < 4) {
                return `${weeksPast}w ago`;
            } else {
                // If more than a month, show the date
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov',
                    'Dec'
                ];
                return `${months[date.getMonth()]} ${date.getDate()}`;
            }
        }

        // Ensure panel starts closed
        notificationContent.classList.remove('open');

        // Toggle notification panel using class for reliability
        let notificationAppendedToBody = false;

        function positionNotificationPanel() {
            const rect = notificationBtn.getBoundingClientRect();
            const panelWidth = 300;
            let left = rect.left;
            // keep panel on-screen
            if (left + panelWidth > window.innerWidth - 8) {
                left = window.innerWidth - panelWidth - 8;
            }
            notificationContent.style.position = 'fixed';
            notificationContent.style.top = (rect.bottom + 8) + 'px';
            notificationContent.style.left = left + 'px';
            notificationContent.style.right = 'auto';
            notificationContent.style.width = panelWidth + 'px';
        }

        notificationBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const isOpen = notificationContent.classList.contains('open');
            console.debug('notificationBtn clicked, isOpen=', isOpen);
            if (isOpen) {
                notificationContent.classList.remove('open');
                if (notificationAppendedToBody) {
                    document.body.appendChild(notificationContent); // ensure it's still in body
                }
            } else {
                // Move panel into body to avoid clipping by parent containers
                if (!notificationAppendedToBody) {
                    document.body.appendChild(notificationContent);
                    notificationAppendedToBody = true;
                }
                positionNotificationPanel();
                notificationContent.classList.add('open');
                // show a small loading placeholder
                if (notificationList) notificationList.innerHTML = '<div class="notification-item">Loading...</div>';
                fetchNotifications();
            }
        });

        // Reposition on resize/scroll while open
        window.addEventListener('resize', function() {
            if (notificationContent.classList.contains('open')) positionNotificationPanel();
        });
        window.addEventListener('scroll', function() {
            if (notificationContent.classList.contains('open')) positionNotificationPanel();
        }, true);

        // Close notifications when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationContent.contains(e.target) && e.target !== notificationBtn) {
                notificationContent.classList.remove('open');
                // optional: move panel back to original container if desired
                try {
                    notificationContent.style.position = '';
                    notificationContent.style.top = '';
                    notificationContent.style.left = '';
                    notificationContent.style.width = '';
                } catch (err) {
                    console.debug('Error resetting notification content styles', err);
                }
            }
        });

        function fetchNotifications() {
            console.debug('fetchNotifications called');
            fetch('../backend/get_user_notifications.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(response => {
                    if (!response.success) {
                        throw new Error(response.error || 'Failed to fetch notifications');
                    }

                    const data = response.data;
                    notificationList.innerHTML = '';
                    let unreadCount = 0;

                    if (data && data.length > 0) {
                        data.forEach(notification => {
                            if (!notification.is_read) unreadCount++;

                            const notificationItem = document.createElement('div');
                            notificationItem.className =
                                `notification-item ${notification.is_read ? '' : 'unread'}`;
                            notificationItem.setAttribute('data-notification-id', notification.id);
                            let contentHtml = '';
                            if (notification.subject) {
                                contentHtml += `<div class="notification-subject" style="font-weight: 600; margin-bottom: 4px; color: #1a1a1a; font-size: 14px;">${notification.subject}</div>`;
                            }
                            contentHtml += `<div class="message">${notification.message}</div>`;
                            if (notification.link) {
                                contentHtml += `<a href="${notification.link}" target="_blank" class="btn-notification-action" style="display: inline-block; margin-top: 8px; font-size: 11px; padding: 6px 12px; background: linear-gradient(135deg, #E9437A 0%, #e67753 100%); color: white; border-radius: 6px; text-decoration: none; font-weight: 500;">Open Link</a>`;
                            }

                            notificationItem.innerHTML = `
                                <div class="notification-content-wrapper">
                                    <div class="notification-main">
                                        ${contentHtml}
                                    </div>
                                    <div class="notification-time">${formatTimeAgo(notification.created_on)}</div>
                                </div>
                            `;

                            // Add click handler to mark as read
                            if (!notification.is_read) {
                                notificationItem.addEventListener('click', function() {
                                    markNotificationAsRead(notification.id, this);
                                });
                            }
                            notificationList.appendChild(notificationItem);
                        });
                    } else {
                        notificationList.innerHTML =
                            '<div class="notification-item">No notifications</div>';
                    }

                    if (unreadCount > 0) {
                        notificationCount.style.display = 'flex';
                        notificationCount.textContent = unreadCount >= 10 ? '10+' : unreadCount;
                    } else {
                        notificationCount.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error fetching notifications:', error);
                    notificationList.innerHTML =
                        '<div class="notification-item error">Failed to load notifications</div>';
                });
        }

        // Function to mark notification as read
        function markNotificationAsRead(notificationId, element) {
            fetch('../backend/mark_notification_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `notification_id=${notificationId}`
                })
                .then(response => response.json())
                .then(response => {
                    if (response.success) {
                        // Update UI
                        element.classList.remove('unread');
                        // Update notification count
                        let currentText = notificationCount.textContent;
                        let count;
                        if (currentText === '10+') {
                            // Refresh notifications to get accurate count
                            fetchNotifications();
                        } else {
                            count = parseInt(currentText || '0');
                            if (count > 0) {
                                count--;
                                if (count === 0) {
                                    notificationCount.style.display = 'none';
                                } else {
                                    notificationCount.style.display = 'flex';
                                    notificationCount.textContent = count >= 10 ? '10+' : count;
                                }
                            }
                        }
                    }
                })
                .catch(error => console.error('Error marking notification as read:', error));
        }

        // Fetch notifications periodically
        setInterval(fetchNotifications, 30000); // Check every 30 seconds
        fetchNotifications(); // Initial fetch
    });
    </script>
    </div>
</header>
<br>
<style>
    @media only screen and (max-width: 768px) {
        .mobile-only-dev {
            margin-bottom: 10%;
        }
    }
</style>
<div class="mobile-only-dev">
</div>