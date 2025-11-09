<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<header class="transparent scroll-dark">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="header-content d-flex align-items-center justify-content-between">
                    <div class="left-section">
                        <!-- Placeholder for balance -->
                    </div>
                    <div class="logo-wrapper text-center">
                        <!-- logo begin -->
                        <div id="logo">
                            <a href="index.php">
                                <img alt="Logo" class="centered-logo" src="../assets/logo.png" />
                            </a>
                        </div>
                        <!-- logo close -->
                    </div>
                    <div class="right-section">
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
                        position: relative;
                        width: 100%;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    }

                    .logo-wrapper {
                        position: absolute;
                        left: 50%;
                        top: 50%;
                        transform: translate(-50%, -50%);
                        z-index: 1;
                    }

                    .left-section,
                    .right-section {
                        position: relative;
                        z-index: 2;
                        min-width: 50px;
                    }

                    .right-section {
                        display: flex;
                        align-items: center;
                        justify-content: flex-end;
                    }

                    .notification-dropdown {
                        position: relative;
                        margin-right: 15px;
                        z-index: 9999;
                    }

                    .btn-notification {
                        display: flex !important;
                        align-items: center;
                        justify-content: center;
                        width: 40px;
                        height: 40px;
                        border-radius: 50%;
                        background: rgba(131, 100, 226, 0.1);
                        color: #ffffffff !important;
                        text-decoration: none;
                        position: relative;
                        z-index: 9999;
                    }

                    .btn-notification i {
                        font-size: 20px;
                        display: inline-block !important;
                        color: #ffffffff !important;
                    }

                    .btn-notification:hover {
                        background: rgba(131, 100, 226, 0.2);
                    }

                    .notification-content {
                        position: absolute;
                        right: 0;
                        top: 50px;
                        width: 300px;
                        background: white;
                        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
                        border-radius: 8px;
                        display: none;
                        z-index: 99999;
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

                        .btn-notification {
                            color: #ffffffff !important;
                            opacity: 1 !important;
                            visibility: visible !important;
                        }

                        .btn-notification i {
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

                        .btn-notification {
                            color: #fff !important;
                            background: rgba(255, 255, 255, 0.1);
                        }

                        .btn-notification:hover {
                            background: rgba(255, 255, 255, 0.2);
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

        // Set initial display state
        notificationContent.style.display = 'none';

        // Toggle notification panel
        notificationBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Direct toggle without checking current state
            if (notificationContent.style.display === 'block') {
                notificationContent.style.display = 'none';
            } else {
                notificationContent.style.display = 'block';
                fetchNotifications();
            }
        });

        // Close notifications when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationContent.contains(e.target) && e.target !== notificationBtn) {
                notificationContent.style.display = 'none';
            }
        });

        function fetchNotifications() {
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