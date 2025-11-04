// Community JavaScript functions
document.addEventListener('DOMContentLoaded', function () {
    loadCommunities();
    // Regular updates for new messages
    setInterval(() => loadMessages(false), 5000);
    // Full refresh every 30 seconds to check timeout status
    setInterval(() => loadMessages(true), 30000);
});

let currentCommunityId = null;
let lastMessageTime = null;

// Load all communities
function loadCommunities() {
    fetch('../backend/get_communities.php')
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                // Store user's communities for reference
                window.userCommunities = data.communities.reduce((acc, comm) => {
                    acc[comm.id] = comm.is_user_community;
                    return acc;
                }, {});

                const tabs = document.getElementById('communityTabs');
                tabs.innerHTML = data.communities.map(community => `
                <div class="community-tab ${community.is_user_community ? 'active' : ''}" 
                     onclick="selectCommunity(${community.id}, ${community.is_user_community})">
                    ${community.name}
                    <span class="badge">${community.current_members}/10</span>
                </div>
            `).join('');

                // Load messages for user's community and set initial input visibility
                if (data.user_community_id) {
                    currentCommunityId = data.user_community_id;
                    const chatInput = document.querySelector('.chat-input');
                    if (chatInput) {
                        chatInput.style.display = 'flex';
                    }
                    loadMessages();
                    loadMembers();
                }
            }
        })
        .catch(error => console.error('Error:', error));
}

// Function to render a single message
function renderMessage(msg) {
    // Get current community membership status
    const isMember = window.userCommunities[currentCommunityId] || false;

    // Add data attribute for user ID to help with updates
    const dataAttributes = `data-message-id="${msg.id}" data-user-id="${msg.user_id}"`;

    // Function to determine if a file is an image
    const isImageFile = (filename) => {
        const imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        const ext = filename.split('.').pop().toLowerCase();
        return imageExtensions.includes(ext);
    };

    // Render attachment based on type
    const renderAttachment = () => {
        if (!msg.attachment_path) return '';

        if (isImageFile(msg.attachment_name)) {
            return `
            <div class="message-attachment">
                <img src="../${msg.attachment_path}" alt="${msg.attachment_name}">
            </div>`;
        } else {
            return `
            <div class="message-attachment file">
                <a href="../${msg.attachment_path}" target="_blank">
                    <i class="fa fa-paperclip"></i>${msg.attachment_name}
                </a>
            </div>`;
        }
    };

    return `
        <div class="message ${msg.is_own ? 'sent' : 'received'}" data-message-id="${msg.id}">
            <div style="display: flex; gap: 12px; align-items: flex-start;">
                <div style="flex-shrink: 0; width: 40px; height: 40px;">
                    <img src="assets/images/user.jpg" alt="${msg.user_name}" 
                         style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 2px solid rgba(131, 100, 226, 0.3);"
                         onerror="this.src='assets/images/user.jpg'">
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div class="message-header">
                        <div class="message-sender" style="margin-right: 10px;">
                            <span style="color: rgba(131, 100, 226, 0.8); font-size: 0.9em;">@${msg.user_qr_id}</span>
                            ${msg.is_moderator ? `
                                <span class="moderator-badge">
                                    <i class="fa fa-shield"></i> Mod
                                </span>
                            ` : ''}
                        </div>
                        <div class="timestamp">${formatDate(msg.created_on)}</div>
                    </div>
                    <div class="message-content" style="word-break: break-word; overflow-wrap: break-word; white-space: pre-wrap; max-width: 100%;">${msg.message}</div>
                    ${renderAttachment()}
                    <div class="message-footer">
                <div class="d-flex align-items-center">
                    ${isMember ? `
                        <button class="btn-reply" onclick="replyToMessage(${msg.id}, '${msg.user_qr_id}')">
                            <i class="fa fa-reply"></i>Reply
                        </button>
                    ` : ``}
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="reactions-group">
                        ${isMember ? `
                            <button class="btn-reaction ${msg.user_reaction === 'like' ? 'active' : ''}" 
                                    onclick="reactToMessage(${msg.id}, 'like')">
                                <i class="fa fa-thumbs-up"></i>
                                <span class="reaction-count">${msg.like_count || 0}</span>
                            </button>
                            <button class="btn-reaction ${msg.user_reaction === 'dislike' ? 'active' : ''}" 
                                    onclick="reactToMessage(${msg.id}, 'dislike')">
                                <i class="fa fa-thumbs-down"></i>
                                <span class="reaction-count">${msg.dislike_count || 0}</span>
                            </button>
                        ` : `
                            <div class="btn-reaction disabled">
                                <i class="fa fa-thumbs-up"></i>
                                <span class="reaction-count">${msg.like_count || 0}</span>
                            </div>
                            <div class="btn-reaction disabled">
                                <i class="fa fa-thumbs-down"></i>
                                <span class="reaction-count">${msg.dislike_count || 0}</span>
                            </div>
                        `}
                    </div>
                    ${!msg.is_own && isMember ? `
                        <button class="btn-report" onclick="openReportModal(${msg.id})">
                            <i class="fa fa-flag"></i> Report
                        </button>
                    ` : ''}
                    ${msg.is_current_user_moderator && !msg.is_own ? `
                        <div class="moderator-actions" style="display: flex !important; gap: 2px !important; margin: 2px 0 !important; flex-wrap: wrap !important;">
                            ${msg.is_banned ? `
                                <span class="user-status banned" style="display: inline-flex !important; align-items: center !important; padding: 2px 6px !important; background: #ff4444 !important; color: white !important; border-radius: 3px !important; font-size: 11px !important; justify-content: center !important; line-height: 1.2 !important;">
                                    <i class="fa fa-ban" style="margin-right: 3px !important;"></i> Banned
                                </span>
                            ` : msg.is_timed_out ? `
                                <span class="user-status timeout" style="display: inline-flex !important; align-items: center !important; padding: 2px 6px !important; background: #ffa500 !important; color: white !important; border-radius: 3px !important; font-size: 11px !important; justify-content: center !important; line-height: 1.2 !important;">
                                    <i class="fa fa-clock-o" style="margin-right: 3px !important;"></i> Timed Out
                                </span>
                            ` : `
                                <button class="btn-timeout" onclick="timeoutUser(${msg.user_id}, ${msg.id})"
                                    style="background: #ffa500 !important;">
                                    <i class="fa fa-clock-o"></i> Time out
                                </button>
                                <button class="btn-ban" onclick="banUser(${msg.user_id}, ${msg.id})"
                                    style="background: #ff4444 !important;">
                                    <i class="fa fa-ban"></i> Ban
                                </button>
                            `}
                        </div>
                    ` : ''}
                </div>
            </div>
                </div>
            </div>
        </div>
    `;
}

// Load messages for current community
function loadMessages(forceRefresh = false) {
    if (!currentCommunityId) return;

    const chatMessages = document.getElementById('chatMessages');
    // More precise bottom detection
    const isAtBottom = Math.abs(chatMessages.scrollHeight - chatMessages.scrollTop - chatMessages.clientHeight) < 10;
    const currentScrollPos = chatMessages.scrollTop;
    const previousScrollHeight = chatMessages.scrollHeight;

    // If forcing refresh, clear the lastMessageTime to get all messages
    const params = forceRefresh ? '' : (lastMessageTime ? `?since=${lastMessageTime}` : '');

    fetch(`../backend/get_community_messages.php?community_id=${currentCommunityId}${params}`)
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            const chatInput = document.querySelector('.chat-input');

            // Handle error state
            if (!data.status) {
                chatMessages.innerHTML = `<div class="system-message error">
                    <div class="system-message-content">
                        <i class="fa fa-exclamation-circle"></i>
                        ${data.message}
                    </div>
                </div>`;
                if (chatInput) {
                    chatInput.style.display = 'none';
                }
                return;
            }

            // Handle chat input visibility based on timeout state
            if (data.isTimedOut) {
                if (chatInput) {
                    chatInput.style.display = 'none';
                }
            } else if (chatInput) {
                chatInput.style.display = 'flex';
            }

            // Handle initial load
            if (!lastMessageTime) {
                let content = '';

                // Add messages if available
                if (data.messages && data.messages.length > 0) {
                    content = data.messages.map(msg => renderMessage(msg)).join('');
                    // Update last message time
                    lastMessageTime = data.messages[data.messages.length - 1].created_on;
                }

                chatMessages.innerHTML = content;

                // Add timeout warning if user is timed out
                if (data.isTimedOut) {
                    const warningHtml = `
                        <div class="system-message warning sticky">
                            <div class="system-message-content">
                                <i class="fa fa-clock-o"></i>
                                ${data.timeoutMessage}
                            </div>
                        </div>`;
                    chatMessages.insertAdjacentHTML('afterbegin', warningHtml);
                }

                chatMessages.scrollTop = chatMessages.scrollHeight;
            } else {
                // Handle updates
                const warningElement = document.querySelector('.system-message.warning.sticky');

                // Update timeout warning status
                if (data.isTimedOut && !warningElement) {
                    const warningHtml = `
                        <div class="system-message warning sticky">
                            <div class="system-message-content">
                                <i class="fa fa-clock-o"></i>
                                ${data.timeoutMessage}
                            </div>
                        </div>`;
                    chatMessages.insertAdjacentHTML('afterbegin', warningHtml);
                } else if (!data.isTimedOut && warningElement) {
                    warningElement.remove();
                }

                // Update messages
                if (data.messages && data.messages.length > 0) {
                    let hasNewMessages = false;
                    data.messages.forEach(msg => {
                        const existingMessage = document.querySelector(`[data-message-id="${msg.id}"]`);
                        if (!existingMessage) {
                            chatMessages.insertAdjacentHTML('beforeend', renderMessage(msg));
                            hasNewMessages = true;
                        } else {
                            // Update reactions
                            updateMessageReactions(msg);

                            // Update moderator actions if present
                            const modActions = existingMessage.querySelector('.moderator-actions');
                            if (modActions && msg.is_current_user_moderator && !msg.is_own) {
                                // Determine the current status and update UI accordingly
                                if (msg.is_banned) {
                                    modActions.innerHTML = `
                                        <span class="user-status banned" style="display: inline-flex !important; align-items: center !important; padding: 2px 6px !important; background: #ff4444 !important; color: white !important; border-radius: 3px !important; font-size: 11px !important; justify-content: center !important; line-height: 1.2 !important;">
                                            <i class="fa fa-ban" style="margin-right: 3px !important;"></i> Banned
                                        </span>`;
                                } else if (msg.is_timed_out) {
                                    modActions.innerHTML = `
                                        <span class="user-status timeout" style="display: inline-flex !important; align-items: center !important; padding: 2px 6px !important; background: #ffa500 !important; color: white !important; border-radius: 3px !important; font-size: 11px !important; justify-content: center !important; line-height: 1.2 !important;">
                                            <i class="fa fa-clock-o" style="margin-right: 3px !important;"></i> Timed Out
                                        </span>`;
                                } else {
                                    // No active penalties, show mod buttons
                                    modActions.innerHTML = `
                                        <button class="btn-timeout" onclick="timeoutUser(${msg.user_id}, ${msg.id})"
                                            style="background: #ffa500 !important;">
                                            <i class="fa fa-clock-o"></i> Time out 
                                        </button>
                                        <button class="btn-ban" onclick="banUser(${msg.user_id}, ${msg.id})"
                                            style="background: #ff4444 !important;">
                                            <i class="fa fa-ban"></i> Ban
                                        </button>`;
                                }
                            }
                        }
                    });

                    // Handle scrolling
                    if (hasNewMessages) {
                        if (isAtBottom) {
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        } else {
                            // Maintain scroll position when new content is loaded
                            chatMessages.scrollTop = currentScrollPos + (chatMessages.scrollHeight - previousScrollHeight);
                        }
                    }

                    // Update last message time
                    lastMessageTime = data.messages[data.messages.length - 1].created_on;
                }
            }
        })
        .catch(function (error) {
            console.error('Error loading messages:', error);
        });
}

// Load members of current community
function loadMembers() {
    if (!currentCommunityId) return;

    fetch(`../backend/get_community_members.php?community_id=${currentCommunityId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                const membersList = document.getElementById('membersList');
                membersList.innerHTML = data.members.map(member => `
                    <div class="member-item">
                        <img src="${member.user_image_path || '../assets/images/user.jpg'}" 
                             class="member-avatar" alt="${member.user_full_name}">
                        <div>
                            <div class="member-name">${member.user_full_name}</div>
                            <div class="member-joined">Joined: ${formatDate(member.created_on)}</div>
                        </div>
                    </div>
                `).join('');
            }
        })
        .catch(error => console.error('Error:', error));
}

// Handle file selection
document.getElementById('attachment').addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (file) {
        document.getElementById('attachmentName').textContent = file.name;
        document.getElementById('attachmentPreview').style.display = 'block';
    }
});

// Remove attachment
function removeAttachment() {
    document.getElementById('attachment').value = '';
    document.getElementById('attachmentPreview').style.display = 'none';
}

// Send a new message
function sendMessage() {
    const input = document.getElementById('messageInput');
    const fileInput = document.getElementById('attachment');
    const message = input.value.trim();
    const chatMessages = document.getElementById('chatMessages');

    if ((!message && !fileInput.files[0]) || !currentCommunityId) return;

    const formData = new FormData();
    formData.append('community_id', currentCommunityId);
    formData.append('message', message);

    if (fileInput.files[0]) {
        formData.append('attachment', fileInput.files[0]);
    }

    fetch('../backend/send_community_message.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                input.value = '';
                // Clear file input and preview
                fileInput.value = '';
                document.getElementById('attachmentPreview').style.display = 'none';

                // Update without forcing scroll
                lastMessageTime = new Date().toISOString();
                const wasAtBottom = chatMessages.scrollHeight - chatMessages.scrollTop === chatMessages.clientHeight;
                loadMessages();

                // Only scroll to bottom if user was already at bottom or sending a new message
                if (wasAtBottom) {
                    setTimeout(() => {
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }, 100);
                }
            } else {
                alert(data.message || 'Failed to send message');
            }
        })
        .catch(error => console.error('Error:', error));
}

// Select a community to view
function selectCommunity(communityId, isUserCommunity) {
    currentCommunityId = communityId;
    lastMessageTime = null; // Reset last message time for new community

    // Update active tab
    document.querySelectorAll('.community-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`.community-tab[onclick="selectCommunity(${communityId}, ${isUserCommunity})"]`).classList.add('active');

    // Show/hide input area based on community membership
    const chatInput = document.querySelector('.chat-input');
    if (chatInput) {
        chatInput.style.display = isUserCommunity ? 'flex' : 'none';
    }

    // Load community data
    loadMessages();
    loadMembers();
}

// Function to update existing message reactions
function updateMessageReactions(msg) {
    const messageElement = document.querySelector(`[data-message-id="${msg.id}"]`);
    if (messageElement) {
        const likeButton = messageElement.querySelector('.btn-reaction:first-child');
        const dislikeButton = messageElement.querySelector('.btn-reaction:last-child');

        // Update counts
        likeButton.querySelector('.reaction-count').textContent = msg.like_count || 0;
        dislikeButton.querySelector('.reaction-count').textContent = msg.dislike_count || 0;

        // Update active states
        likeButton.classList.toggle('active', msg.user_reaction === 'like');
        dislikeButton.classList.toggle('active', msg.user_reaction === 'dislike');
    }
}

// Handle message reactions
function reactToMessage(messageId, reactionType) {
    fetch('../backend/react_to_message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            message_id: messageId,
            reaction_type: reactionType
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
                if (messageElement) {
                    const likeButton = messageElement.querySelector('.btn-reaction:first-child');
                    const dislikeButton = messageElement.querySelector('.btn-reaction:last-child');

                    // Update counts
                    likeButton.querySelector('.reaction-count').textContent = data.likes_count;
                    dislikeButton.querySelector('.reaction-count').textContent = data.dislikes_count;

                    // Update active states
                    if (reactionType === 'like') {
                        likeButton.classList.toggle('active');
                        dislikeButton.classList.remove('active');
                    } else {
                        dislikeButton.classList.toggle('active');
                        likeButton.classList.remove('active');
                    }
                }
            }
        })
        .catch(error => console.error('Error:', error));
}

// Helper function to format dates
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleString('en-IN', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Moderator Actions
function timeoutUser(userId, messageId) {
    const duration = prompt('Enter timeout duration in minutes:', '30');
    if (duration === null) return; // User cancelled

    fetch('../backend/timeout_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            user_id: userId,
            message_id: messageId,
            duration: parseInt(duration)
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                // Immediately update UI for all messages from this user
                const userMessages = document.querySelectorAll('.message');
                userMessages.forEach(messageEl => {
                    const modActions = messageEl.querySelector('.moderator-actions');
                    if (modActions) {
                        // Get the onclick attribute of any timeout/ban button to extract user ID
                        const timeoutBtn = modActions.querySelector('.btn-timeout');
                        if (timeoutBtn && timeoutBtn.getAttribute('onclick').includes(`timeoutUser(${userId},`)) {
                            modActions.innerHTML = `
                                <span class="user-status timeout" style="display: inline-flex; align-items: center; padding: 6px 12px; background: #ffa500; color: white; border-radius: 4px; font-size: 14px;">
                                    <i class="fa fa-clock-o" style="margin-right: 6px;"></i> Timed Out
                                </span>`;
                        }
                    }
                });
                alert('User has been timed out');
            } else {
                alert(data.message || 'Failed to timeout user');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to timeout user. Please try again.');
        });
}

function banUser(userId, messageId) {
    if (!confirm('Are you sure you want to ban this user from the community?')) return;

    fetch('../backend/ban_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            user_id: userId,
            message_id: messageId
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                // Immediately update UI for all messages from this user
                const userMessages = document.querySelectorAll('.message');
                userMessages.forEach(messageEl => {
                    const modActions = messageEl.querySelector('.moderator-actions');
                    if (modActions) {
                        // Get the onclick attribute of any timeout/ban button to extract user ID
                        const banBtn = modActions.querySelector('.btn-ban');
                        if (banBtn && banBtn.getAttribute('onclick').includes(`banUser(${userId},`)) {
                            modActions.innerHTML = `
                                <span class="user-status banned" style="display: inline-flex; align-items: center; padding: 6px 12px; background: #ff4444; color: white; border-radius: 4px; font-size: 14px;">
                                    <i class="fa fa-ban" style="margin-right: 6px;"></i> Banned
                                </span>`;
                        }
                    }
                });
                alert('User has been banned');
            } else {
                alert(data.message || 'Failed to ban user');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to ban user. Please try again.');
        });
}

// Reply functionality
function replyToMessage(messageId, userQrId) {
    const input = document.getElementById('messageInput');
    input.value = `@${userQrId} `;
    input.focus();
}