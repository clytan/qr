// Community JavaScript functions
document.addEventListener('DOMContentLoaded', function () {
    loadCommunities();
    // Auto-refresh chat every 5 seconds
    setInterval(loadMessages, 5000);
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
                }                // Store user's communities for reference
                window.userCommunities = data.communities.reduce((acc, comm) => {
                    acc[comm.id] = comm.is_user_community;
                    return acc;
                }, {});
            }
        })
        .catch(error => console.error('Error:', error));
}

// Function to render a single message
function renderMessage(msg) {
    // Get current community membership status
    const isMember = window.userCommunities[currentCommunityId] || false;

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
            <div class="message-sender">${msg.user_name}</div>
            <div class="message-content">${msg.message}</div>
            ${renderAttachment()}
            <div class="message-footer">
                <div class="d-flex align-items-center">
                    ${isMember ? `
                        <button class="btn-reply" onclick="replyToMessage(${msg.id})">
                            <i class="fa fa-reply"></i>Reply
                        </button>
                    ` : ``}
                    <div class="timestamp">${formatDate(msg.created_on)}</div>
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
                </div>
            </div>
        </div>
    `;
}

// Load messages for current community
function loadMessages() {
    if (!currentCommunityId) return;

    const chatMessages = document.getElementById('chatMessages');
    // More precise bottom detection
    const isAtBottom = Math.abs(chatMessages.scrollHeight - chatMessages.scrollTop - chatMessages.clientHeight) < 10;
    const currentScrollPos = chatMessages.scrollTop;
    const previousScrollHeight = chatMessages.scrollHeight;

    const params = lastMessageTime ? `?since=${lastMessageTime}` : '';
    fetch(`../backend/get_community_messages.php?community_id=${currentCommunityId}${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                // On first load or community change
                if (!lastMessageTime) {
                    const messagesHtml = data.messages.map(msg => renderMessage(msg)).join('');
                    chatMessages.innerHTML = messagesHtml;
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                } else {
                    // For updates
                    let hasNewMessages = false;
                    data.messages.forEach(msg => {
                        if (!document.querySelector(`[data-message-id="${msg.id}"]`)) {
                            chatMessages.insertAdjacentHTML('beforeend', renderMessage(msg));
                            hasNewMessages = true;
                        } else {
                            updateMessageReactions(msg);
                        }
                    });

                    // Only auto-scroll if user was already near bottom or it's a new message from the user
                    if (hasNewMessages) {
                        if (isNearBottom) {
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        } else {
                            // Maintain scroll position when new content is loaded
                            chatMessages.scrollTop = currentScrollPos + (chatMessages.scrollHeight - previousScrollHeight);
                        }
                    }
                }

                if (data.messages.length > 0) {
                    lastMessageTime = data.messages[data.messages.length - 1].created_on;
                }
            }
        })
        .catch(error => console.error('Error:', error));
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

// Helper function to format dates
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

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleString('en-IN', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Reply functionality
function replyToMessage(messageId) {
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    if (messageElement) {
        const sender = messageElement.querySelector('.message-sender').textContent;
        const input = document.getElementById('messageInput');
        input.value = `@${sender} `;
        input.focus();
    }
}