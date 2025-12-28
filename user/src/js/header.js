document.addEventListener('DOMContentLoaded', function () {
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

    notificationBtn.addEventListener('click', function (e) {
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
    window.addEventListener('resize', function () {
        if (notificationContent.classList.contains('open')) positionNotificationPanel();
    });
    window.addEventListener('scroll', function () {
        if (notificationContent.classList.contains('open')) positionNotificationPanel();
    }, true);

    // Close notifications when clicking outside
    document.addEventListener('click', function (e) {
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
                            // Show the actual link URL so users know where they're going - make it clickable
                            contentHtml += `<a href="${notification.link}" target="_blank" class="notification-link-preview" style="display: block; margin-top: 6px; font-size: 11px; color: #667eea; word-break: break-all; background: rgba(102, 126, 234, 0.1); padding: 6px 8px; border-radius: 4px; border-left: 3px solid #667eea; text-decoration: none;">${notification.link}</a>`;
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
                            notificationItem.addEventListener('click', function () {
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
