<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<?php
// Check if user is logged in
$header_is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
?>

<header class="transparent scroll-dark">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="header-content d-flex align-items-center justify-content-between">
                    <div class="left-section">
                        <!-- logo begin -->
                        <div id="logo">
                            <a href="index.php">
                                <img alt="Logo" class="logo-image" src="../assets/logo.png" />
                            </a>
                        </div>
                        <!-- logo close -->
                    </div>
                    <div class="right-section">
                        <?php if ($header_is_logged_in): ?>
                        <!-- Notifications Dropdown -->
                        <div class="notification-dropdown">
                            <a href="javascript:void(0)" class="btn-main btn-notification" id="notificationBtn">
                                <i class="fas fa-bell"></i>
                                <span class="notification-count" style="display: none;">0</span>
                            </a>
                            <div class="notification-content">
                                <div class="notification-header">
                                    <h4>Notifications</h4>
                                </div>
                                <div class="notification-list">
                                    <!-- Notifications will be inserted here via JavaScript -->
                                </div>
                            </div>
                        </div>
                        <!-- Wallet Button -->
                        <a href="wallet.php" class="btn-main btn-wallet" id="walletBtn" title="Wallet">
                            <i class="fas fa-wallet"></i>
                            <span class="wallet-balance" style="display:none;"></span>
                        </a>
                        <?php endif; ?>
                    </div>
                    <style>
                    header {
                        padding: 10px 0;
                        min-height: 80px;
                        display: flex;
                        align-items: center;
                    }

                    .header-content {
                        min-height: 80px;
                        width: 100%;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    }

                    .left-section {
                        display: flex;
                        align-items: center;
                        gap: 15px;
                    }

    .right-section {
        display: flex;
        align-items: center;
        gap: 10px;
    }                    #logo {
                        display: flex;
                        align-items: center;
                    }

                    .logo-image {
                        height: 50px;
                        width: auto;
                    }

                    .notification-dropdown {
                        position: relative;
                        z-index: 9999;
                    }

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
        width: 38px !important;
        height: 38px !important;
        border-radius: 12px !important;
        background: transparent !important;
        border: 1.5px solid rgba(255, 255, 255, 0.8) !important;
        color: #ffffff !important;
        text-decoration: none !important;
        position: relative;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        backdrop-filter: blur(10px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
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
        background: rgba(255, 255, 255, 0.15) !important;
        border-color: rgba(255, 255, 255, 1) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
    }                    .notification-content {
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

                    .notification-list {
                        max-height: 300px;
                        overflow-y: auto;
                        padding: 10px;
                    }

                    #logo {
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

                        header {
                            min-height: 60px;
                        }

                        .header-content {
                            min-height: 60px;
                        }

                        .btn-notification,
                        .btn-wallet {
                            border-radius: 12px !important;
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
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationContent = document.querySelector('.notification-content');
        const notificationCount = document.querySelector('.notification-count');
        const notificationList = document.querySelector('.notification-list');

        // Safety guards and diagnostics
        if (!notificationBtn) console.warn('notificationBtn element not found');
        if (!notificationContent) console.warn('notificationContent element not found');
        if (!notificationList) console.warn('notificationList element not found');

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
                            notificationItem.innerHTML = `
                                <div class="notification-content-wrapper">
                                    <div class="notification-main">
                                        <div class="message">${notification.message}</div>
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