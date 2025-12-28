// Robust avatar fallback: generate initials SVG and set as src on error or when default image is used.
(function () {
    try {
        var img = document.getElementById('click_profile_img');
        var nameEl = document.getElementById('full_name');

        function generateInitialsDataUrl(name, size) {
            name = (name || 'User').trim();
            if (!name || name.length === 0) {
                name = 'User';
            }
            var initials = name.split(/\s+/).map(function (n) {
                return n.charAt(0).toUpperCase();
            }).slice(0, 2).join('');
            var r = size / 2;
            var stroke = Math.max(2, Math.round(size * 0.06));
            var innerR = r - stroke / 1.5;
            var fontSize = Math.round(size * 0.38);
            var svg = "<svg xmlns='http://www.w3.org/2000/svg' width='" + size +
                "' height='" + size + "' viewBox='0 0 " + size + " " + size + "'>" +
                "<defs><linearGradient id='g' x1='0' x2='1' y1='0' y2='1'><stop offset='0' stop-color='#667eea'/><stop offset='1' stop-color='#764ba2'/></linearGradient></defs>" +
                "<circle cx='" + r + "' cy='" + r + "' r='" + innerR +
                "' fill='url(#g)' stroke='rgba(0,0,0,0.28)' stroke-width='" + stroke +
                "' />" +
                "<text x='50%' y='50%' dy='.36em' text-anchor='middle' font-family='Inter, Arial, sans-serif' font-size='" +
                fontSize + "' font-weight='700' fill='rgba(255,255,255,0.95)'>" + initials +
                "</text></svg>";
            return 'data:image/svg+xml;utf8,' + encodeURIComponent(svg);
        }

        if (img) {
            // Set initial avatar immediately since src is empty
            img.src = generateInitialsDataUrl('User', 160);
            img.setAttribute('data-generated-initials', '1');

            // onerror handler for missing/404 images
            img.onerror = function () {
                try {
                    this.onerror = null;
                    var name = nameEl ? nameEl.value : '';
                    this.src = generateInitialsDataUrl(name || 'User', 160);
                    this.setAttribute('data-generated-initials', '1');
                } catch (e) {
                    console.error('Error generating initials avatar:', e);
                }
            };
        }
    } catch (e) {
        console.error('Profile image initialization error:', e);
    }
})();

// QR Menu Action Toggle
document.addEventListener('DOMContentLoaded', function () {
    const qrMenuBtn = document.getElementById('qr-menu-btn');
    if (qrMenuBtn) {
        qrMenuBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const dropdown = document.getElementById('qr-dropdown');
            const btn = this;

            // Toggle visibility using style to override CSS if needed, but class is better
            if (dropdown.style.display === 'block' || dropdown.classList.contains('show')) {
                dropdown.style.display = 'none';
                dropdown.classList.remove('show');
                btn.classList.remove('active');
                btn.querySelector('.fa-chevron-down').style.transform = 'rotate(0deg)';
            } else {
                dropdown.style.display = 'block';
                dropdown.classList.add('show');
                btn.classList.add('active');
                btn.querySelector('.fa-chevron-down').style.transform = 'rotate(180deg)';
            }
        });
    }

    // Prevent dropdown close when clicking inside color controls or menu items
    const qrDropdown = document.getElementById('qr-dropdown');
    if (qrDropdown) {
        qrDropdown.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
        const dropdown = document.getElementById('qr-dropdown');
        const btn = document.getElementById('qr-menu-btn');
        if (btn && dropdown && !btn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
            dropdown.classList.remove('show');
            btn.classList.remove('active');
            btn.querySelector('.fa-chevron-down').style.transform = 'rotate(0deg)';
        }
    });

    // Profile Booster functionality
    checkAndShowBooster();

    // Load followers count on page load
    loadFollowersCount();
});

// Function to handle social media redirects
function handleSocialMediaRedirect(inputId, btnElement) {
    const input = document.getElementById(inputId);
    const value = input.value.trim();

    if (!value) return;

    let url = '';
    switch (inputId) {
        case 'website':
            url = value.startsWith('http') ? value : 'https://' + value;
            break;
        case 'facebook_username':
            const fbHandle = value.replace('@', '');
            url = `https://www.facebook.com/${fbHandle}`;
            break;
        case 'whatsapp_link':
            const whatsappNumber = value.replace(/[^0-9]/g, '');
            url = `https://wa.me/${whatsappNumber}`;
            break;
        case 'telegram_link':
            const username = value.replace('@', '');
            url = `https://t.me/${username}`;
            break;
        case 'twitter_username':
            const twitterHandle = value.replace('@', '');
            url = `https://twitter.com/${twitterHandle}`;
            break;
        case 'instagram_username':
            const instaHandle = value.replace('@', '');
            url = `https://instagram.com/${instaHandle}`;
            break;
        case 'youtube_username':
            if (value.includes('youtube.com') || value.includes('youtu.be')) {
                url = value;
            } else {
                const ytHandle = value.replace('@', '');
                url = `https://youtube.com/@${ytHandle}`;
            }
            break;
        case 'linkedin_username':
            url = value.includes('linkedin.com') ? value : `https://linkedin.com/in/${value}`;
            break;
        case 'snapchat_username':
            const snapHandle = value.replace('@', '');
            url = `https://snapchat.com/add/${snapHandle}`;
            break;
    }

    if (url) {
        window.open(url, '_blank');
    }
}

// Add click handlers to all redirect buttons
document.addEventListener('DOMContentLoaded', function () {
    const socialInputs = [
        'website', 'facebook_username', 'whatsapp_link', 'telegram_link', 'twitter_username',
        'instagram_username', 'youtube_username', 'linkedin_username', 'snapchat_username'
    ];

    socialInputs.forEach(inputId => {
        const inputElement = document.getElementById(inputId);
        if (inputElement) {
            const container = inputElement.closest('.social-input-field');
            if (container) {
                const redirectBtn = container.querySelector('.btn-link');
                if (redirectBtn) {
                    redirectBtn.addEventListener('click', () => handleSocialMediaRedirect(inputId, redirectBtn));
                }
            }
        }
    });

    // Initialize follow button if viewing another profile
    if (typeof isViewingOtherProfile !== 'undefined' && isViewingOtherProfile) {
        checkFollowStatus();
    }
});

// Check if user is Gold or Silver and show booster
function checkAndShowBooster() {
    const userId = document.getElementById('user_id')?.value;
    // We need to get these values from the HTML or global variables if defined
    // Assuming they are available or we might need to pass them

    // For now, we rely on the PHP echo injection similar to before, BUT since this is external JS
    // we need to read from hidden inputs or data attributes.
    // Let's assume hidden inputs are present as added in the PHP file.

    const viewingQrVal = document.getElementById('user_qr')?.value;
    // We need to know if we are viewing our own profile or not.
    // This logic relies on PHP session which is not directly available here.
    // However, the original code used PHP echo.
    // We should look element existence or data attributes.

    const boosterSection = document.getElementById('profile-booster-section');
    if (!boosterSection) return;

    // If section exists, we proceed with AJAX check
    if (!userId) {
        boosterSection.style.display = 'none';
        return;
    }

    const urlParams = new URLSearchParams(window.location.search);
    const testRenewal = urlParams.get('test_renewal');

    // We need to fix the path if we are in /user/src/ui
    const backendPath = '../backend/profile_new/get_profile_data.php';

    // Use fetch or jQuery if available. Original code used jQuery.
    if (typeof $ !== 'undefined') {
        $.ajax({
            url: backendPath,
            type: 'POST',
            data: {
                user_id: userId,
                test_renewal: testRenewal || ''
            },
            dataType: 'json',
            success: function (data) {
                console.log('User data for booster:', data);
                if (data.user) {
                    const userSlabId = parseInt(data.user.user_slab_id);
                    const userTag = data.user.user_tag ? data.user.user_tag.toLowerCase() : '';

                    // Show booster for Gold (3) users only
                    if (userSlabId === 3 || userTag === 'gold') {
                        boosterSection.style.display = 'block';
                    } else {
                        boosterSection.style.display = 'none';
                    }

                    // Render subscription status
                    if (data.subscription) {
                        try {
                            renderSubscriptionStatus(data.subscription);
                        } catch (e) { console.error('Render sub status error', e); }
                    }
                }
            },
            error: function (xhr, status, error) {
                console.error('Error checking slab:', error);
            }
        });
    }
}

// Handle Super Charge button
document.addEventListener('DOMContentLoaded', function () {
    const boostBtn = document.getElementById('btn-super-charge');
    let superchargeFormVisible = false;

    if (boostBtn) {
        // Check existing supercharge status on load
        checkSuperchargeStatus();

        boostBtn.addEventListener('click', function () {
            // Toggle form visibility
            const form = document.getElementById('supercharge-form');
            if (form) {
                if (!superchargeFormVisible) {
                    form.classList.remove('d-none');
                    superchargeFormVisible = true;
                    boostBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Supercharge Request';
                } else {
                    // Submit the form
                    submitSuperchargeRequest();
                }
            }
        });
    }
});

function checkSuperchargeStatus() {
    if (typeof $ === 'undefined') return;

    $.ajax({
        url: '../backend/profile_new/get_supercharge_status.php',
        type: 'GET',
        dataType: 'json',
        success: function (response) {
            if (response.success && response.has_request && response.requests.length > 0) {
                const requests = response.requests;
                const linksList = $('#supercharge-links-list');
                const linksContainer = $('#links-container');

                if (linksList.length && linksContainer.length) {
                    // Show links list
                    linksList.removeClass('d-none');
                    linksContainer.empty();

                    // Display each link with its status
                    requests.forEach(function (request) {
                        let statusBadge = '';
                        let statusClass = '';

                        if (request.status === 'pending') {
                            statusBadge = '<span class="badge" style="background: #fbbf24; color: #000;"><i class="fas fa-clock"></i> Pending</span>';
                            statusClass = 'border-warning';
                        } else if (request.status === 'approved') {
                            statusBadge = '<span class="badge" style="background: #10b981; color: #fff;"><i class="fas fa-check-circle"></i> Approved</span>';
                            statusClass = 'border-success';
                        } else if (request.status === 'rejected') {
                            statusBadge = '<span class="badge" style="background: #ef4444; color: #fff;"><i class="fas fa-times-circle"></i> Rejected</span>';
                            statusClass = 'border-danger';
                        }

                        let linkHtml = `
                            <div class="supercharge-link-item ${statusClass}" style="padding: 0.75rem; margin-bottom: 0.75rem; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; background: rgba(255,255,255,0.03);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <div style="flex: 1; overflow: hidden;">
                                        <a href="${request.supercharge_link}" target="_blank" style="color: #e67753; text-decoration: none; word-break: break-all;">
                                            <i class="fas fa-external-link-alt"></i> ${request.supercharge_link}
                                        </a>
                                    </div>
                                    <div style="margin-left: 0.75rem;">
                                        ${statusBadge}
                                    </div>
                                </div>
                                ${request.status === 'rejected' && request.admin_notes ? `<div style="font-size: 0.85rem; color: #ef4444; margin-top: 0.5rem;"><strong>Reason:</strong> ${request.admin_notes}</div>` : ''}
                                <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.5rem;">
                                    Submitted: ${new Date(request.created_at).toLocaleDateString()}
                                </div>
                            </div>
                        `;

                        linksContainer.append(linkHtml);
                    });
                }
            }
        },
        error: function (xhr, status, error) {
            console.error('Error checking supercharge status:', error);
        }
    });
}

function submitSuperchargeRequest() {
    if (typeof $ === 'undefined') return;

    const linkInput = $('#supercharge_link');
    const link = linkInput.val() ? linkInput.val().trim() : '';

    if (!link) {
        alert('Please enter a valid link');
        return;
    }

    // Validate URL format
    try {
        new URL(link);
    } catch (e) {
        alert('Please enter a valid URL (must start with http:// or https://)');
        return;
    }

    $.ajax({
        url: '../backend/profile_new/submit_supercharge.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            supercharge_link: link
        }),
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                alert(response.message);
                linkInput.val(''); // Clear the input field
                checkSuperchargeStatus(); // Refresh status
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error submitting supercharge:', error);
            alert('Failed to submit supercharge request. Please try again.');
        }
    });
}

// Render subscription status
let subscriptionData = null;

function renderSubscriptionStatus(sub) {
    subscriptionData = sub;

    // Show the subscription button
    const subBtn = document.getElementById('subscription-btn');
    if (subBtn) {
        subBtn.style.display = 'block';

        // Update button text based on status
        const btnText = document.getElementById('subscription-btn-text');
        if (btnText) {
            if (sub.is_expired) {
                btnText.textContent = '⚠️ Subscription Expired';
                subBtn.classList.add('btn-danger');
                subBtn.classList.remove('btn-secondary');
            } else if (sub.needs_renewal) {
                btnText.textContent = '⏰ ' + sub.days_remaining + ' days left';
                subBtn.classList.add('btn-warning');
                subBtn.classList.remove('btn-secondary');
            } else {
                btnText.textContent = 'Subscription Status';
            }
        }

        // Bind click to open modal
        subBtn.onclick = function () {
            showSubscriptionModal();
        };
    }
}

function showSubscriptionModal() {
    const content = document.getElementById('subscription-modal-content');
    if (!content || !subscriptionData) return;

    const sub = subscriptionData;
    const expiryDate = new Date(sub.expires_on);
    const formattedExpiry = expiryDate.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
    const tier = (sub.tier || 'normal').charAt(0).toUpperCase() + (sub.tier || 'normal').slice(1);

    let statusClass = 'status-ok';
    let statusIcon = '✅';
    let statusText = sub.days_remaining + ' days remaining';

    if (sub.is_expired) {
        statusClass = 'status-expired';
        statusIcon = '❌';
        statusText = 'Expired';
    } else if (sub.is_in_grace) {
        statusClass = 'status-grace';
        statusIcon = '⚠️';
        statusText = 'Grace period (' + Math.abs(sub.days_remaining) + ' days overdue)';
    } else if (sub.days_remaining <= 30) {
        statusClass = 'status-warning';
        statusIcon = '⏰';
        statusText = sub.days_remaining + ' days remaining';
    }

    let html = `
        <div class="subscription-info ${statusClass}">
            <div class="sub-row">
                <span class="sub-label">Membership:</span>
                <span class="sub-value sub-tier tier-${sub.tier}">${tier}</span>
            </div>
            <div class="sub-row">
                <span class="sub-label">Expires:</span>
                <span class="sub-value">${formattedExpiry}</span>
            </div>
    `;

    if (sub.needs_renewal) {
        html += `
            <div class="sub-action">
                <button type="button" id="renew-btn" class="btn btn-primary btn-renew">
                    <i class="fas fa-sync-alt"></i> Renew Now - ₹${sub.renewal_price}
                </button>
            </div>
        `;
    }

    html += '</div>';
    content.innerHTML = html;

    // Bind click event after HTML is inserted
    const renewBtn = document.getElementById('renew-btn');
    if (renewBtn) {
        renewBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            startRenewal();
        });
    }

    // Show modal
    if (typeof bootstrap !== 'undefined') {
        const modalEl = document.getElementById('subscriptionModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }
}

// Start renewal payment
function startRenewal() {
    const renewBtn = document.querySelector('.btn-renew');
    if (renewBtn) {
        renewBtn.disabled = true;
        renewBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    }

    fetch('../backend/payment/renew_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
        .then(response => response.json())
        .then(data => {
            if (data.status && data.session) {
                // Redirect to renewal payment page
                const session = encodeURIComponent(data.session);
                const orderId = encodeURIComponent(data.order_id);
                window.location.href = '../backend/payment/intent_renewal.php?session=' + session + '&orderId=' + orderId;
            } else {
                alert('Error: ' + (data.error || 'Failed to create payment order'));
                if (renewBtn) {
                    renewBtn.disabled = false;
                    renewBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Renew Now';
                }
            }
        })
        .catch(err => {
            console.error('Renewal error:', err);
            alert('Error starting renewal. Please try again.');
            if (renewBtn) {
                renewBtn.disabled = false;
                renewBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Renew Now';
            }
        });
}

function boostProfile() {
    fetch('../backend/boost_profile.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            boost_duration: 7
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                alert('✨ Success! Your profile has been Super Charged!\n\n' + data.message);
                // Reload page to reflect changes
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error boosting profile:', error);
            alert('An error occurred while boosting your profile. Please try again.');
        });
}

function loadFollowersCount() {
    // We need targetQr and user_id. These were variables in PHP.
    // We can rely on hidden inputs if they exist, or global variables if defined in PHP script block (which we removed).
    // Let's look for hidden inputs which we added in PHP.
    const userIdInput = document.getElementById('user_id');
    const userQrInput = document.getElementById('user_qr');
    // For viewed_qr and is_viewing_other_profile, we might need a way to pass them.
    // Assuming they are available as global vars or we add hidden inputs for them.

    // If not available, we return.
    if (!userIdInput) return;

    // Fallback if globals are used (common in PHP-JS mix)
    // But we should use safe access.
    let targetQr = null;
    if (typeof viewedQr !== 'undefined' && viewedQr) targetQr = viewedQr;
    else if (userQrInput) targetQr = userQrInput.value;
    else if (typeof currentUserQr !== 'undefined') targetQr = currentUserQr;

    if (!targetQr) return;

    if (typeof $ !== 'undefined') {
        $.ajax({
            url: '../backend/get_followers_count.php',
            method: 'POST',
            data: JSON.stringify({
                qr_id: targetQr,
                follower_id: userIdInput.value
            }),
            contentType: 'application/json',
            success: function (response) {
                updateFollowersCount(response.total_count);
            },
            error: function () {
                console.error('Failed to load followers count');
            }
        });
    }
}

function checkFollowStatus() {
    const userIdInput = document.getElementById('user_id');
    if (!userIdInput) return;

    // Assume viewedQr is globally defined or available
    if (typeof viewedQr === 'undefined' || !viewedQr) return;

    if (typeof $ !== 'undefined') {
        // Get the user_id for the viewed profile
        $.ajax({
            url: '../backend/get_user_id.php',
            method: 'POST',
            data: JSON.stringify({ qr_id: viewedQr }),
            contentType: 'application/json',
            success: function (userResponse) {
                if (userResponse.status && userResponse.user_id) {
                    const targetUserId = userResponse.user_id;

                    // Now check follow status
                    $.ajax({
                        url: '../backend/get_followers_count.php',
                        method: 'POST',
                        data: JSON.stringify({
                            qr_id: viewedQr,
                            follower_id: userIdInput.value
                        }),
                        contentType: 'application/json',
                        success: function (response) {
                            renderFollowButton(response.following, targetUserId);
                            updateFollowersCount(response.total_count);
                        },
                        error: function () {
                            console.error('Failed to check follow status');
                        }
                    });
                }
            },
            error: function () {
                console.error('Failed to get user ID');
            }
        });
    }
}

function renderFollowButton(isFollowing, targetUserId) {
    if (typeof $ === 'undefined') return;

    const btnClass = isFollowing
        ? 'btn btn-outline-secondary'
        : 'btn';
    const btnStyle = isFollowing
        ? ''
        : 'background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%); color: white;';
    const iconClass = isFollowing ? 'fas fa-user-check' : 'fas fa-user-plus';
    const title = isFollowing ? 'Following' : 'Follow';

    const button = $('<button>')
        .addClass(btnClass)
        .attr('style', btnStyle)
        .attr('onclick', `toggleFollow(${targetUserId})`)
        .attr('title', title)
        .html(`<i class="${iconClass}"></i>`);

    $('#follow-btn-container').html(button);
}

function toggleFollow(targetUserId) {
    if (typeof $ === 'undefined') return;

    $.ajax({
        url: '../backend/profile/toggle_follow.php',
        method: 'POST',
        data: JSON.stringify({ target_user_id: targetUserId }),
        contentType: 'application/json',
        success: function (response) {
            if (response.success) {
                const isFollowing = response.data.following;
                renderFollowButton(isFollowing, targetUserId);

                // Update followers count
                checkFollowStatus();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function () {
            alert('Failed to update follow status. Please try again.');
        }
    });
}

function updateFollowersCount(count) {
    if (typeof $ === 'undefined') return;
    // Update the followers count display
    $('#followers-count .stat-value').text(count);
}

function showNotification(message) {
    if (typeof $ === 'undefined') return;
    // Simple notification
    const notification = $('<div>')
        .css({
            'position': 'fixed',
            'top': '20px',
            'right': '20px',
            'background': 'linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%)',
            'color': 'white',
            'padding': '15px 25px',
            'border-radius': '8px',
            'box-shadow': '0 4px 6px rgba(0,0,0,0.1)',
            'z-index': '9999',
            'animation': 'slideIn 0.3s ease-out'
        })
        .text(message);

    $('body').append(notification);

    setTimeout(function () {
        notification.fadeOut(300, function () {
            $(this).remove();
        });
    }, 3000);
}

// Share Profile Button
document.addEventListener('DOMContentLoaded', function () {
    const shareBtn = document.getElementById('share-profile-btn');
    if (shareBtn) {
        shareBtn.addEventListener('click', function () {
            // Need to get QrId dynamically
            let userQrId = '';
            // Try to find it from hidden inputs or globals
            const viewedQr = (typeof window.viewedQr !== 'undefined') ? window.viewedQr : '';
            const userQr = document.getElementById('user_qr')?.value || '';
            const viewingQr = (typeof window.viewingQr !== 'undefined') ? window.viewingQr : false;

            userQrId = viewingQr ? viewedQr : userQr;

            // If still empty (because PHP vars not passed properly to this context), try URL param
            if (!userQrId) {
                const urlParams = new URLSearchParams(window.location.search);
                userQrId = urlParams.get('QR');
            }

            const profileUrl = window.location.origin + window.location.pathname + '?QR=' + userQrId;

            // Try using the Web Share API first (mobile devices)
            if (navigator.share) {
                navigator.share({
                    title: 'My ZQR Profile',
                    text: 'Check out my profile on ZQR Connect!',
                    url: profileUrl
                }).catch(err => {
                    // If share fails, copy to clipboard
                    copyToClipboard(profileUrl);
                });
            } else {
                // Fallback: copy to clipboard
                copyToClipboard(profileUrl);
            }
        });
    }
});

function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            alert('✅ Profile link copied to clipboard!\n\n' + text);
        }).catch(() => {
            fallbackCopyToClipboard(text);
        });
    } else {
        fallbackCopyToClipboard(text);
    }
}

function fallbackCopyToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    document.body.appendChild(textArea);
    textArea.select();
    try {
        document.execCommand('copy');
        alert('✅ Profile link copied to clipboard!\n\n' + text);
    } catch (err) {
        prompt('Copy this link manually:', text);
    }
    document.body.removeChild(textArea);
}

// Frame Selector Logic
document.addEventListener('DOMContentLoaded', function () {
    const chooseFrameBtn = document.getElementById('choose-frame-btn');
    const frameGrid = document.getElementById('frame-grid');
    const frameOverlay = document.querySelector('.qr-frame-overlay');
    let currentFrame = 'default';
    let frameModal;

    // Initialize modal
    if (typeof bootstrap !== 'undefined') {
        const modalEl = document.getElementById('frameModal');
        if (modalEl) {
            frameModal = new bootstrap.Modal(modalEl);
        }
    }

    // Load user's current frame
    function loadCurrentFrame() {
        const userId = document.getElementById('user_id')?.value;
        // Check for viewedQr global
        const viewedQr = (typeof window.viewedQr !== 'undefined') ? window.viewedQr : '';

        let data = {};
        if (viewedQr) {
            data.qr_id = viewedQr;
        } else if (userId) {
            data.user_id = userId;
        } else {
            return;
        }

        if (typeof $ !== 'undefined') {
            $.ajax({
                url: '../backend/profile_new/get_frame.php',
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function (response) {
                    if (response.status) {
                        currentFrame = response.frame;
                        if (frameOverlay) {
                            if (response.frameUrl === null) {
                                frameOverlay.style.display = 'none';
                            } else {
                                // Fix relative paths for frames
                                let frameUrl = response.frameUrl;
                                if (frameUrl && !frameUrl.startsWith('http') && !frameUrl.startsWith('data:')) {
                                    // Ensure path starts correctly relative to current page location
                                    if (frameUrl.startsWith('/user/src')) {
                                        frameUrl = '..' + frameUrl.substring('/user/src'.length);
                                    }
                                }
                                frameOverlay.src = frameUrl || response.frameUrl;
                                frameOverlay.style.display = 'block';
                            }
                        }
                    }
                }
            });
        }
    }

    // Load available frames
    function loadFrames() {
        if (typeof $ !== 'undefined') {
            $.ajax({
                url: '../backend/profile_new/get_frames.php',
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    if (response.status && response.data) {
                        renderFrameGrid(response.data);
                    } else {
                        if (frameGrid) frameGrid.innerHTML = '<p class="text-center" style="color: var(--danger);">Failed to load frames</p>';
                    }
                },
                error: function () {
                    if (frameGrid) frameGrid.innerHTML = '<p class="text-center" style="color: var(--danger);">Error loading frames</p>';
                }
            });
        }
    }

    // Render frame grid
    function renderFrameGrid(frames) {
        if (!frameGrid) return;
        frameGrid.innerHTML = '';

        frames.forEach(function (frame) {
            const div = document.createElement('div');
            div.className = 'frame-item' + (frame.id === currentFrame ? ' selected' : '');
            div.dataset.frameId = frame.id;
            div.dataset.frameUrl = frame.url || '';

            if (frame.id === 'none') {
                div.innerHTML = '<i class="fas fa-ban no-frame-icon"></i><span class="frame-label">No Frame</span>';
            } else if (frame.thumbnail) {
                div.innerHTML = '<img src="' + frame.thumbnail + '" alt="' + frame.name + '"><span class="frame-label">' + frame.name + '</span>';
            } else {
                div.innerHTML = '<i class="fas fa-image no-frame-icon"></i><span class="frame-label">' + frame.name + '</span>';
            }

            div.addEventListener('click', function () {
                selectFrame(frame.id, frame.url);
            });

            frameGrid.appendChild(div);
        });
    }

    // Select frame
    function selectFrame(frameId, frameUrl) {
        // Update UI immediately
        document.querySelectorAll('.frame-item').forEach(el => el.classList.remove('selected'));
        const selected = document.querySelector('[data-frame-id="' + frameId + '"]');
        if (selected) selected.classList.add('selected');

        // Update frame overlay
        if (frameOverlay) {
            if (frameId === 'none' || !frameUrl) {
                frameOverlay.style.display = 'none';
            } else {
                frameOverlay.src = frameUrl;
                frameOverlay.style.display = 'block';
            }
        }

        // Save to server
        if (typeof $ !== 'undefined') {
            $.ajax({
                url: '../backend/profile_new/save_frame.php',
                type: 'POST',
                data: JSON.stringify({ frame: frameId }),
                contentType: 'application/json',
                dataType: 'json',
                success: function (response) {
                    if (response.status) {
                        currentFrame = frameId;
                        if (frameModal) frameModal.hide();
                        // showToast is assumed global from footer.js
                        if (typeof showToast === 'function') showToast('Frame updated successfully!', 'success');
                    } else {
                        if (typeof showToast === 'function') showToast('Failed to save frame: ' + response.message, 'error');
                    }
                },
                error: function () {
                    if (typeof showToast === 'function') showToast('Error saving frame', 'error');
                }
            });
        }
    }

    // Open modal
    if (chooseFrameBtn) {
        chooseFrameBtn.addEventListener('click', function () {
            loadFrames();
            if (frameModal) {
                frameModal.show();
            } else if (typeof $ !== 'undefined') {
                $('#frameModal').modal('show');
            }
        });
    }

    // Load current frame on page load
    loadCurrentFrame();
});
