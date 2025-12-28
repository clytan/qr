// Community JavaScript functions
let pollingInterval = 5000; // Start with 5 seconds
let isUserActive = true;
let lastActivityTime = Date.now();
let pollingTimer = null;

document.addEventListener('DOMContentLoaded', function () {
    loadCommunities();

    // Track user activity
    ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
        document.addEventListener(event, () => {
            isUserActive = true;
            lastActivityTime = Date.now();
            // Speed up polling when user is active
            if (pollingInterval > 5000) {
                pollingInterval = 5000;
                resetPolling();
            }
        }, true);
    });

    // Check for inactivity every 30 seconds
    setInterval(() => {
        const inactiveTime = Date.now() - lastActivityTime;
        if (inactiveTime > 60000) { // 1 minute inactive
            isUserActive = false;
            pollingInterval = 30000; // Slow down to 30 seconds
        } else if (inactiveTime > 30000) { // 30 seconds inactive
            pollingInterval = 15000; // Medium speed
        } else {
            pollingInterval = 5000; // Active - 5 seconds
        }
    }, 30000);

    // Smart polling function
    function smartPoll() {
        loadMessages(false).then(() => {
            pollingTimer = setTimeout(smartPoll, pollingInterval);
        });
    }

    // Start smart polling
    smartPoll();

    // Full refresh every 30 seconds for timeout/ban status
    setInterval(() => loadMessages(true), 30000);

    // Add keyboard support for lightbox
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeLightbox();
        }
    });
});

function resetPolling() {
    if (pollingTimer) {
        clearTimeout(pollingTimer);
        pollingTimer = setTimeout(() => loadMessages(false), pollingInterval);
    }
}

// Generate an SVG data URL avatar with initials and gradient
function generateInitialsAvatar(name, size = 64) {
    const initials = (name || 'U').split(' ').map(n => n.charAt(0).toUpperCase()).slice(0, 2).join('');
    const colors = [['#667eea', '#764ba2'], ['#f093fb', '#f5576c'], ['#10b981', '#06b6d4'], ['#f59e0b', '#ef4444']];
    const pick = colors[(initials.charCodeAt(0) + (initials.charCodeAt(1) || 0)) % colors.length];
    const r = size / 2;
    const stroke = Math.max(2, Math.round(size * 0.06));
    const innerR = r - stroke / 1.5;
    const fontSize = Math.round(size * 0.38);
    const svg = `<svg xmlns='http://www.w3.org/2000/svg' width='${size}' height='${size}' viewBox='0 0 ${size} ${size}'>
            <defs>
                <linearGradient id='g' x1='0' x2='1' y1='0' y2='1'>
                    <stop offset='0' stop-color='${pick[0]}' />
                    <stop offset='1' stop-color='${pick[1]}' />
                </linearGradient>
            </defs>
            <circle cx='${r}' cy='${r}' r='${innerR}' fill='url(#g)' stroke='rgba(0,0,0,0.28)' stroke-width='${stroke}' />
            <text x='50%' y='50%' dy='.36em' text-anchor='middle' font-family='Inter, Arial, sans-serif' font-size='${fontSize}' font-weight='700' fill='rgba(255,255,255,0.95)'>${initials}</text>
        </svg>`;
    return 'data:image/svg+xml;utf8,' + encodeURIComponent(svg).replace(/'/g, '%27');
}

let currentCommunityId = null;
let lastMessageTime = null;
let isSendingMessage = false;

// Normalize image path returned from backend:
// - If path is absolute (starts with http(s)://) or starts with '/', or already relative ('../'), return as-is.
// - Otherwise prefix with '../' to point to the correct directory relative to the JS file.
function resolveImagePath(p) {
    if (!p) return null;
    try {
        p = p.toString();
        if (/^https?:\/\//i.test(p)) return p;
        // If backend returned an absolute project path like '/user/src/backend/...',
        // normalize it to a relative path from this UI file: '../backend/...'
        if (p.startsWith('/user/src/')) {
            return '..' + p.replace(/^\/user\/src/, '');
        }
        if (p.startsWith('/')) return p;
        if (p.startsWith('../') || p.startsWith('./')) return p;
        // otherwise assume it's a relative path from project root under user/src
        return '../' + p;
    } catch (e) {
        return p;
    }
}

// Lightbox functions for image expansion
function openLightbox(imageUrl) {
    const lightbox = document.getElementById('imageLightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    if (lightbox && lightboxImage) {
        lightboxImage.src = imageUrl;
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeLightbox() {
    const lightbox = document.getElementById('imageLightbox');
    if (lightbox) {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Toggle input actions (emoji, gif, attachment buttons)
function toggleInputActions() {
    const actionsGroup = document.getElementById('inputActionsGroup');
    const toggleBtn = document.getElementById('toggleActionsBtn');

    if (actionsGroup.style.display === 'none' || actionsGroup.style.display === '') {
        actionsGroup.style.display = 'flex';
        toggleBtn.classList.add('active');
    } else {
        actionsGroup.style.display = 'none';
        toggleBtn.classList.remove('active');
    }
}

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

                const dropdownMenu = document.getElementById('communityDropdownMenu');
                const dropdownBtn = document.getElementById('communityDropdownBtn');
                const selectedName = document.getElementById('selectedCommunityName');

                // Populate dropdown menu
                dropdownMenu.innerHTML = data.communities.map(community => `
                    <div class="community-dropdown-item ${community.is_user_community ? 'active' : ''}" 
                         data-community-id="${community.id}"
                         data-is-user-community="${community.is_user_community}"
                         onclick="selectCommunityFromDropdown(${community.id}, ${community.is_user_community}, '${community.name.replace(/'/g, "\\'")}')">
                        <div class="community-name">
                            <i class="fa fa-comments"></i>
                            ${community.name}
                            ${community.is_moderator ? '<span class="mod-badge"><i class="fa fa-shield"></i></span>' : ''}
                        </div>
                        <div class="community-meta">
                            <span class="member-count">${community.current_members}/1M members</span>
                        </div>
                    </div>
                `).join('');

                // Set initial selected community name
                const userCommunity = data.communities.find(c => c.is_user_community);
                if (userCommunity) {
                    selectedName.textContent = userCommunity.name;
                    currentCommunityId = data.user_community_id;
                    const chatInput = document.querySelector('.chat-input');
                    if (chatInput) {
                        chatInput.style.display = 'flex';
                    }
                    loadMessages();
                    loadMembers();
                } else if (data.communities.length > 0) {
                    selectedName.textContent = 'Select Community';
                }

                // Dropdown toggle
                dropdownBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    dropdownBtn.classList.toggle('open');
                    dropdownMenu.classList.toggle('open');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function (e) {
                    if (!dropdownBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                        dropdownBtn.classList.remove('open');
                        dropdownMenu.classList.remove('open');
                    }
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

// Select community from dropdown
function selectCommunityFromDropdown(communityId, isUserCommunity, communityName) {
    currentCommunityId = communityId;
    lastMessageTime = null; // Reset last message time for new community

    // Update selected name in button
    document.getElementById('selectedCommunityName').textContent = communityName;

    // Update active state in dropdown
    document.querySelectorAll('.community-dropdown-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`.community-dropdown-item[data-community-id="${communityId}"]`).classList.add('active');

    // Close dropdown
    document.getElementById('communityDropdownBtn').classList.remove('open');
    document.getElementById('communityDropdownMenu').classList.remove('open');

    // Show/hide input area based on community membership
    const chatInput = document.querySelector('.chat-input');
    if (chatInput) {
        chatInput.style.display = isUserCommunity ? 'flex' : 'none';
    }

    // Load community data
    loadMessages();
    loadMembers();
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

        // Use absolute path from site root for production compatibility
        // attachment_path is stored as 'uploads/community/...'
        const attachmentUrl = '/user/src/' + msg.attachment_path;
        
        if (isImageFile(msg.attachment_name || msg.attachment_path)) {
            return `
            <div class="message-attachment">
                <img src="${attachmentUrl}" alt="${msg.attachment_name || 'Image'}" onclick="openLightbox('${attachmentUrl}')">
            </div>`;
        } else {
            return `
            <div class="message-attachment file">
                <a href="${attachmentUrl}" target="_blank">
                    <i class="fa fa-paperclip"></i>${msg.attachment_name || 'Attachment'}
                </a>
            </div>`;
        }
    };

    // Format timestamp
    const formatTime = (dateStr) => {
        const date = new Date(dateStr);
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const messageDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());

        if (messageDate.getTime() === today.getTime()) {
            return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        } else if (messageDate.getTime() === today.getTime() - 86400000) {
            return 'Yesterday ' + date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        } else {
            return date.toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' }) + ' ' +
                date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        }
    };

    // Format message content - detect GIF URLs and render as images
    const formatMessageContent = (message) => {
        if (!message) return '';

        // Check for [GIF] prefix (from admin panel)
        if (message.startsWith('[GIF]')) {
            const gifUrl = message.replace('[GIF]', '').trim();
            return `<div class="message-gif"><img src="${gifUrl}" alt="GIF" class="gif-content" onclick="openLightbox('${gifUrl}')"></div>`;
        }

        // Check if message contains HTML img tag (from GIF picker)
        if (message.includes('<img') && message.includes('class="gif-message"')) {
            // Extract the src URL
            const srcMatch = message.match(/src="([^"]+)"/);
            if (srcMatch && srcMatch[1]) {
                return `<div class="message-gif"><img src="${srcMatch[1]}" alt="GIF" class="gif-content" onclick="openLightbox('${srcMatch[1]}')"></div>`;
            }
        }

        // Check if message is a direct GIF URL (from Giphy/Tenor) - with or without file extension
        const gifUrlPattern = /^https?:\/\/(media\d*\.giphy\.com|media\.tenor\.com|i\.giphy\.com|c\.tenor\.com)\/.*/i;
        if (gifUrlPattern.test(message.trim())) {
            return `<div class="message-gif"><img src="${message.trim()}" alt="GIF" class="gif-content" onclick="openLightbox('${message.trim()}')"></div>`;
        }

        // Regular text message - escape HTML to prevent XSS
        return message.replace(/</g, '&lt;').replace(/>/g, '&gt;');
    };

    // Handle admin messages differently
    const isAdminMessage = msg.admin_sent || msg.user_qr_id === 'ADMIN';
    const avatarSrc = isAdminMessage 
        ? null 
        : (msg.user_image_path ? resolveImagePath(msg.user_image_path) : generateInitialsAvatar(msg.user_name, 56));
    const avatarHtml = isAdminMessage
        ? `<div class="message-avatar admin-avatar"><i class="fa fa-shield"></i></div>`
        : `<img src="${avatarSrc}" alt="${msg.user_qr_id}" class="message-avatar" onerror="this.onerror=null;this.src='${generateInitialsAvatar(msg.user_name, 56)}'">`;
    const adminBadge = isAdminMessage ? '<span class="admin-badge" style="background: linear-gradient(135deg, #ec4899, #f59e0b); color: white; font-size: 10px; padding: 2px 6px; border-radius: 4px; margin-left: 6px;">ADMIN</span>' : '';
    
    return `
        <div class="message ${isAdminMessage ? 'admin-message' : ''}" data-message-id="${msg.id}">
            ${avatarHtml}
            <div class="message-content">
                <div class="message-header">
                    ${isAdminMessage 
                        ? `<span class="message-username" style="color: #ec4899; font-weight: 600;">Admin</span>${adminBadge}`
                        : `<a href="profile.php?qr=${msg.user_qr_id}" class="message-username">@${msg.user_qr_id}</a>`}
                    ${msg.is_moderator && !isAdminMessage ? `
                        <span class="moderator-badge"><i class="fa fa-shield"></i></span>
                    ` : ''}
                    <span class="message-timestamp">${formatTime(msg.created_on)}</span>
                </div>
                <div class="message-text">${formatMessageContent(msg.message)}</div>
                ${renderAttachment()}
                <div class="message-actions">
                    ${isMember ? `
                        <button class="btn-reply" onclick="replyToMessage(${msg.id}, '${msg.user_qr_id}')">
                            <i class="fa fa-reply"></i> Reply
                        </button>
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
                    ${!msg.is_own && isMember ? `
                        <button class="btn-report" onclick="openReportModal(${msg.id})">
                            <i class="fa fa-flag"></i> Report
                        </button>
                    ` : ''}
                    ${msg.is_current_user_moderator && !msg.is_own ? `
                        <div class="moderator-actions">
                            ${msg.is_banned ? `
                                <span class="user-status banned">
                                    <i class="fa fa-ban"></i> Banned
                                </span>
                            ` : msg.is_timed_out ? `
                                <span class="user-status timeout">
                                    <i class="fa fa-clock-o"></i> Timed Out
                                </span>
                            ` : `
                                <button class="btn-timeout" onclick="timeoutUser(${msg.user_id}, ${msg.id})">
                                    <i class="fa fa-clock-o"></i> Timeout
                                </button>
                                <button class="btn-ban" onclick="banUser(${msg.user_id}, ${msg.id})">
                                    <i class="fa fa-ban"></i> Ban
                                </button>
                            `}
                        </div>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

// Load messages for current community
function loadMessages(forceRefresh = false) {
    if (!currentCommunityId) return Promise.resolve();

    const chatMessages = document.getElementById('chatMessages');
    // More precise bottom detection
    const isAtBottom = Math.abs(chatMessages.scrollHeight - chatMessages.scrollTop - chatMessages.clientHeight) < 10;
    const currentScrollPos = chatMessages.scrollTop;
    const previousScrollHeight = chatMessages.scrollHeight;

    // If forcing refresh, clear the lastMessageTime to get all messages
    const params = forceRefresh ? '' : (lastMessageTime ? `?since=${lastMessageTime}` : '');

    return fetch(`../backend/get_community_messages.php?community_id=${currentCommunityId}${params}`)
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
                        if (!msg || !msg.id) {
                            return;
                        }

                        const existingMessage = document.querySelector(`[data-message-id="${msg.id}"]`);
                        if (!existingMessage) {
                            chatMessages.insertAdjacentHTML('beforeend', renderMessage(msg));
                            hasNewMessages = true;
                        } else {
                            // Update reactions
                            try {
                                updateMessageReactions(msg);
                            } catch (error) {
                                console.warn('Error updating reactions:', error);
                            }

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
                if (!membersList) return; // Element not found, skip
                membersList.innerHTML = data.members.map(member => {
                    const memberAvatar = member.user_image_path ? resolveImagePath(member.user_image_path) : generateInitialsAvatar(member.user_full_name || member.user_qr_id, 48);
                    return `<div class="member-item" title="${member.user_full_name || member.user_qr_id}">
                                <img src="${memberAvatar}" 
                                     class="member-avatar" 
                                     alt="${member.user_qr_id}"
                                     onerror="this.onerror=null;this.src='${generateInitialsAvatar(member.user_full_name || member.user_qr_id, 48)}'">
                                <div class="member-info">
                                    <div class="member-qr-id">@${member.user_qr_id}</div>
                                </div>
                            </div>`;
                }).join('');
            }
        })
        .catch(error => console.error('Error:', error));
}

// Handle file selection with image preview
document.getElementById('attachment').addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (file) {
        const preview = document.getElementById('attachmentPreview');
        const previewContent = preview.querySelector('.preview-content');

        // Check if it's an image
        if (file.type.startsWith('image/')) {
            // Create image preview
            const reader = new FileReader();
            reader.onload = function (e) {
                previewContent.innerHTML = `
                    <div class="preview-image-wrapper">
                        <img src="${e.target.result}" alt="${file.name}" class="preview-image">
                        <div class="preview-image-name">${file.name}</div>
                    </div>
                    <button class="btn-remove" onclick="removeAttachment()">×</button>
                `;
            };
            reader.readAsDataURL(file);
        } else {
            // Show file name for non-images
            previewContent.innerHTML = `
                <span><i class="fa fa-paperclip"></i> ${file.name}</span>
                <button class="btn-remove" onclick="removeAttachment()">×</button>
            `;
        }

        preview.style.display = 'block';

        // Close the actions group after selecting file
        const actionsGroup = document.getElementById('inputActionsGroup');
        const toggleBtn = document.getElementById('toggleActionsBtn');
        if (actionsGroup && toggleBtn) {
            actionsGroup.style.display = 'none';
            toggleBtn.classList.remove('active');
        }
    }
});

// Remove attachment
function removeAttachment() {
    document.getElementById('attachment').value = '';
    document.getElementById('attachmentPreview').style.display = 'none';
    const previewContent = document.getElementById('attachmentPreview').querySelector('.preview-content');
    if (previewContent) {
        previewContent.innerHTML = '';
    }
}

// Send a new message
function sendMessage() {
    // Prevent duplicate sends
    if (isSendingMessage) return;

    const input = document.getElementById('messageInput');
    const fileInput = document.getElementById('attachment');
    const message = input.value.trim();
    const chatMessages = document.getElementById('chatMessages');
    const sendBtn = document.querySelector('.btn-send');

    if ((!message && !fileInput.files[0]) || !currentCommunityId) return;

    // Set sending flag and disable button
    isSendingMessage = true;
    if (sendBtn) {
        sendBtn.disabled = true;
        sendBtn.style.opacity = '0.6';
        sendBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    }

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
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to send message. Please try again.');
        })
        .finally(() => {
            // Re-enable button after send completes
            isSendingMessage = false;
            if (sendBtn) {
                sendBtn.disabled = false;
                sendBtn.style.opacity = '1';
                sendBtn.innerHTML = '<i class="fa fa-paper-plane"></i>';
            }
        });
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
    if (!messageElement) {
        return;
    }

    const reactionButtons = messageElement.querySelectorAll('.btn-reaction');
    if (reactionButtons.length < 2) {
        return;
    }

    const likeButton = reactionButtons[0];
    const dislikeButton = reactionButtons[1];

    if (!likeButton || !dislikeButton) {
        return;
    }

    // Update counts safely
    const likeCount = likeButton.querySelector('.reaction-count');
    const dislikeCount = dislikeButton.querySelector('.reaction-count');

    if (likeCount) {
        likeCount.textContent = msg.like_count || 0;
    }

    if (dislikeCount) {
        dislikeCount.textContent = msg.dislike_count || 0;
    }

    // Update active states
    likeButton.classList.toggle('active', msg.user_reaction === 'like');
    dislikeButton.classList.toggle('active', msg.user_reaction === 'dislike');
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
                    const reactionButtons = messageElement.querySelectorAll('.btn-reaction');
                    if (reactionButtons.length >= 2) {
                        const likeButton = reactionButtons[0];
                        const dislikeButton = reactionButtons[1];

                        // Update counts
                        const likeCount = likeButton.querySelector('.reaction-count');
                        const dislikeCount = dislikeButton.querySelector('.reaction-count');

                        if (likeCount) likeCount.textContent = data.likes_count;
                        if (dislikeCount) dislikeCount.textContent = data.dislikes_count;

                        // Update active states (only if buttons are clickable)
                        if (!likeButton.classList.contains('disabled')) {
                            if (reactionType === 'like') {
                                likeButton.classList.toggle('active');
                                dislikeButton.classList.remove('active');
                            } else {
                                dislikeButton.classList.toggle('active');
                                likeButton.classList.remove('active');
                            }
                        }
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

// Emoji Picker Functionality
const emojiCategories = {
    smileys: ['😊', '😂', '🤣', '😍', '🥰', '😘', '😁', '😅', '😆', '😉', '😎', '🤗', '🤔', '🤨', '😐', '😑', '😶', '🙄', '😏', '😬', '😮', '😯', '😴', '😪', '😌', '😔', '😕', '🙁', '😣', '😖', '😫', '😤', '😠', '😡', '🤯', '😳', '😱', '😨', '😰', '😥', '😢', '😭', '😓'],
    gestures: ['👋', '🤚', '✋', '🖐', '👌', '🤌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👍', '👎', '✊', '👊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🤝', '🙏', '💪', '🦾', '🦿', '🦵', '🦶'],
    hearts: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '♥️', '💌', '💋', '💏', '💑', '🥰', '😍', '😘', '😻', '💍', '💐', '🌹', '🌺', '🌸', '🌼', '🌻'],
    objects: ['🎉', '🎊', '🎈', '🎁', '🎂', '🎄', '🎃', '🎆', '🎇', '✨', '🎯', '🎮', '🎲', '🎰', '🎱', '🎳', '🎺', '🎸', '🎹', '🎤', '🎧', '🎬', '📱', '💻', '⌚', '📷', '📺', '📻', '☎️', '📞', '📡', '🔋', '💡', '🔦', '🕯️', '🔥', '💧', '⭐', '🌟', '💫']
};

let currentCategory = 'smileys';

function toggleEmojiPicker() {
    const emojiPicker = document.getElementById('emojiPicker');
    const gifPicker = document.getElementById('gifPicker');

    // Close GIF picker if open
    if (gifPicker) gifPicker.style.display = 'none';

    if (emojiPicker.style.display === 'none' || !emojiPicker.style.display) {
        emojiPicker.style.display = 'flex';
        showEmojiCategory(currentCategory);
    } else {
        emojiPicker.style.display = 'none';
    }
}

function closeEmojiPicker() {
    document.getElementById('emojiPicker').style.display = 'none';
}

function showEmojiCategory(category) {
    currentCategory = category;
    const grid = document.getElementById('emojiGrid');
    const tabs = document.querySelectorAll('.emoji-tab');

    // Update active tab
    tabs.forEach(tab => tab.classList.remove('active'));
    if (event && event.target) {
        event.target.classList.add('active');
    }

    // Populate emoji grid
    grid.innerHTML = emojiCategories[category].map(emoji =>
        `<div class="emoji-item" onclick="insertEmoji('${emoji}')">${emoji}</div>`
    ).join('');
}

function insertEmoji(emoji) {
    const input = document.getElementById('messageInput');
    input.value += emoji;
    input.focus();
}

// GIF Picker Functions (Using Giphy API - Free and reliable)
const GIPHY_API_KEY = 'sXpGFDGZs0Dv1mmNFvYaGUvYwKX0PWIh'; // Giphy SDK public beta key
let gifSearchTimeout;

function toggleGifPicker() {
    const gifPicker = document.getElementById('gifPicker');
    const emojiPicker = document.getElementById('emojiPicker');

    // Close emoji picker if open
    if (emojiPicker) emojiPicker.style.display = 'none';

    if (gifPicker.style.display === 'none' || !gifPicker.style.display) {
        gifPicker.style.display = 'flex';
        loadTrendingGifs();
    } else {
        gifPicker.style.display = 'none';
    }
}

function closeGifPicker() {
    document.getElementById('gifPicker').style.display = 'none';
}

function loadTrendingGifs() {
    const grid = document.getElementById('gifGrid');
    grid.innerHTML = '<div class="gif-loading">Loading trending GIFs...</div>';

    // Using Giphy API - trending endpoint
    fetch(`https://api.giphy.com/v1/gifs/trending?api_key=${GIPHY_API_KEY}&limit=25&rating=g`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Giphy trending response:', data);
            if (data.data && data.data.length > 0) {
                displayGifs(data.data);
            } else {
                grid.innerHTML = '<div class="gif-loading">No trending GIFs found</div>';
            }
        })
        .catch(error => {
            console.error('Error loading GIFs:', error);
            grid.innerHTML = `<div class="gif-loading">Failed to load GIFs<br><small style="font-size: 11px; opacity: 0.7;">${error.message}</small></div>`;
        });
}

function searchGifs(query) {
    clearTimeout(gifSearchTimeout);

    if (!query.trim()) {
        loadTrendingGifs();
        return;
    }

    gifSearchTimeout = setTimeout(() => {
        const grid = document.getElementById('gifGrid');
        grid.innerHTML = '<div class="gif-loading">Searching...</div>';

        // Using Giphy API search
        fetch(`https://api.giphy.com/v1/gifs/search?api_key=${GIPHY_API_KEY}&q=${encodeURIComponent(query)}&limit=25&rating=g`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Giphy search response:', data);
                if (data.data && data.data.length > 0) {
                    displayGifs(data.data);
                } else {
                    grid.innerHTML = '<div class="gif-loading">No GIFs found for "' + query + '"</div>';
                }
            })
            .catch(error => {
                console.error('Error searching GIFs:', error);
                grid.innerHTML = `<div class="gif-loading">Search failed<br><small style="font-size: 11px; opacity: 0.7;">${error.message}</small></div>`;
            });
    }, 500);
}

function displayGifs(gifs) {
    const grid = document.getElementById('gifGrid');

    if (!gifs || gifs.length === 0) {
        grid.innerHTML = '<div class="gif-loading">No GIFs available</div>';
        return;
    }

    grid.innerHTML = gifs.map(gif => {
        try {
            // Giphy API format: gif.images.fixed_height_small.url (preview) and gif.images.downsized.url (full)
            if (!gif.images) {
                console.warn('Missing images object:', gif);
                return '';
            }

            // Get URLs - Giphy has multiple sizes
            const gifUrl = gif.images.downsized?.url || gif.images.original?.url;
            const preview = gif.images.fixed_height_small?.url || gif.images.preview_gif?.url || gifUrl;

            if (!gifUrl || !preview) {
                console.warn('Missing URLs:', gif.images);
                return '';
            }

            return `
                <div class="gif-item" onclick="insertGif('${gifUrl.replace(/'/g, "\\'")}')">
                    <img src="${preview}" loading="lazy" alt="${gif.title || 'GIF'}" onerror="this.parentElement.style.display='none'">
                </div>
            `;
        } catch (error) {
            console.warn('Error processing GIF:', error, gif);
            return '';
        }
    }).filter(html => html !== '').join('');

    if (grid.innerHTML === '') {
        grid.innerHTML = '<div class="gif-loading">Failed to load GIF images</div>';
    }
}

function insertGif(gifUrl) {
    // Send GIF as an embedded image in the message
    const input = document.getElementById('messageInput');
    // Use HTML img tag so it displays as image
    input.value = `<img src="${gifUrl}" alt="GIF" class="gif-message">`;
    closeGifPicker();

    // Close action buttons
    const actionsGroup = document.getElementById('inputActionsGroup');
    const toggleBtn = document.getElementById('toggleActionsBtn');
    if (actionsGroup && toggleBtn) {
        actionsGroup.style.display = 'none';
        toggleBtn.classList.remove('active');
    }

    sendMessage();
}

// Close pickers when clicking outside
document.addEventListener('click', function (event) {
    const emojiPicker = document.getElementById('emojiPicker');
    const gifPicker = document.getElementById('gifPicker');
    const emojiBtn = document.querySelector('.input-action-btn[onclick*="toggleEmojiPicker"]');
    const gifBtn = document.querySelector('.input-action-btn[onclick*="toggleGifPicker"]');

    if (emojiPicker && emojiBtn &&
        !emojiPicker.contains(event.target) &&
        !emojiBtn.contains(event.target)) {
        emojiPicker.style.display = 'none';
    }

    if (gifPicker && gifBtn &&
        !gifPicker.contains(event.target) &&
        !gifBtn.contains(event.target)) {
        gifPicker.style.display = 'none';
    }
});