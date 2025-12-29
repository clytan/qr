const profileFunction = {
    init: function () {
        const urlParams = new URLSearchParams(window.location.search);
        // Handle both lowercase 'qr' and uppercase 'QR' parameters
        const viewingQr = urlParams.get('qr') || urlParams.get('QR');
        const userQr = $('#user_qr').val();
        const userId = $('#user_id').val();

        if (viewingQr) {
            // Viewing someone else's profile or own profile via QR link
            window.PUBLIC_PROFILE = true;
            this.getProfileData(viewingQr);
            this.initQRCode(viewingQr);
            this.checkLoginAndSetupView(viewingQr, userQr);
            this.setAllFieldsReadOnly();
            $('#qr-color-controls').addClass('hidden');
            $('#update-profile-btn').addClass('hidden');
        } else {
            // Own profile edit mode
            window.PUBLIC_PROFILE = false;
            this.getProfileData();
            this.initQRCode();
            this.getFollowersCount(userQr, userId);
            $('#qr-color-controls').removeClass('hidden');
            $('#update-profile-btn').removeClass('hidden');
            $('#follow-btn-container').addClass('hidden');
        }

        this.checkForProfileImage();
        this.setupEventHandlers();
    },

    checkLoginAndSetupView: function (viewingQr, userQr) {
        console.log('Checking login for QR:', viewingQr, 'vs user QR:', userQr);

        // First, make sure follow button container is visible
        $('#follow-btn-container').removeClass('hidden');

        // Check if user is logged in
        $.ajax({
            url: '../backend/profile_new/check_login_status.php',
            type: 'GET',
            dataType: 'json',
            success: function (loginRes) {
                console.log('Login status:', loginRes);

                if (loginRes.logged_in) {
                    const userId = loginRes.user_id;

                    if (viewingQr === userQr) {
                        // User is viewing their own profile via QR link
                        $('#follow-btn-container').addClass('hidden');
                        profileFunction.getFollowersCount(viewingQr, userId);
                    } else {
                        // Logged in user viewing someone else's profile
                        console.log('Setting up follow button for different user');
                        profileFunction.getFollowButtonDetails(viewingQr, userId);
                        profileFunction.getFollowersCount(viewingQr, userId);
                    }
                } else {
                    // Not logged in - show login prompt button
                    console.log('User not logged in, showing login button');
                    $('#follow-btn-container').html(`
                        <button class="btn btn-primary" id="login-to-follow-btn" title="Login to Follow">
                            <i class="fas fa-sign-in-alt"></i>
                        </button>
                    `).removeClass('hidden');

                    // Get followers count without user context
                    profileFunction.getFollowersCount(viewingQr, null);
                }
            },
            error: function () {
                console.log('Error checking login status, assuming not logged in');
                // Assume not logged in on error
                $('#follow-btn-container').html(`
                    <button class="btn btn-primary" id="login-to-follow-btn" title="Login to Follow">
                        <i class="fas fa-sign-in-alt"></i>
                    </button>
                `).removeClass('hidden');
                profileFunction.getFollowersCount(viewingQr, null);
            }
        });
    },

    setupEventHandlers: function () {
        // Name formatting handler
        $('#full_name').on('input', function () {
            const name = $(this).val();
            if (name) {
                const properName = name.toLowerCase().split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');
                $(this).val(properName);
                $('#user-name').text(properName);
            }
        });

        // Profile image upload (only in edit mode)
        if (!window.PUBLIC_PROFILE) {
            // Remove any existing handlers first
            $('#click_profile_img').off('click');
            $('#upload_profile_img').off('change');

            $('#click_profile_img').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $('#upload_profile_img').click();
            });

            $('#upload_profile_img').on('change', this.handleProfileImageUpload);
        }

        // QR Color changes (only in edit mode)
        if (!window.PUBLIC_PROFILE) {
            $('#qr-color-dark, #qr-color-light').on('input', this.handleQRColorChange);
            $('#qr-color-dark, #qr-color-light').on('change', this.handleSaveQRColor);
            $('#save-qr-color').on('click', this.handleSaveQRColor);
        }

        // QR Download button
        $('#download-qr-btn').on('click', this.handleDownloadQR);

        // Follow/Unfollow buttons
        $(document).on('click', '#follow-btn', this.handleFollowClick);
        $(document).on('click', '#unfollow-btn', this.handleUnfollowClick);
        $(document).on('click', '#login-to-follow-btn', this.handleLoginRedirect);

        // Followers/Following modal clicks
        $(document).on('click', '#followers-count, .followers-link', this.showFollowersModal);
        $(document).on('click', '#following-count, .following-link', this.showFollowingModal);

        // Profile form submission (only in edit mode)
        if (!window.PUBLIC_PROFILE) {
            $('#form-create-item').on('submit', this.handleProfileSubmit);
        }

        // Set up visit links
        this.setupSocialLinks();
    },
    showFollowersModal: function () {
        const qrId = window.PUBLIC_PROFILE ?
            new URLSearchParams(window.location.search).get('qr') :
            $('#user_qr').val();

        profileFunction.loadFollowersModal(qrId, 'followers');
    },

    showFollowingModal: function () {
        const qrId = window.PUBLIC_PROFILE ?
            new URLSearchParams(window.location.search).get('qr') :
            $('#user_qr').val();

        profileFunction.loadFollowersModal(qrId, 'following');
    },

    loadFollowersModal: function (qrId, type) {
        $.ajax({
            url: '../backend/profile_new/get_followers_list.php',
            type: 'POST',
            data: JSON.stringify({ qr_id: qrId, type: type }),
            contentType: 'application/json',
            dataType: 'json',
            success: function (res) {
                if (res.status) {
                    profileFunction.displayFollowersModal(res.data, type);
                } else {
                    showToast('Failed to load ' + type, 'error');
                }
            },
            error: function () {
                showToast('Error loading ' + type, 'error');
            }
        });
    },

    displayFollowersModal: function (data, type) {
        const title = type === 'followers' ? 'Followers' : 'Following';
        let content = `<div class="modal fade" id="followersModal" tabindex="-1" role="dialog" aria-labelledby="followersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="followersModalLabel">${title}</h5>

                </div>
                <div class="modal-body">`;

        if (data.length === 0) {
            content += `<p>No ${type} found.</p>`;
        } else {
            data.forEach(user => {
                const userImage = user.user_image_path || 'https://via.placeholder.com/40x40/667eea/ffffff?text=' + (user.user_full_name ? user.user_full_name.charAt(0) : 'A');
                content += `
                <div class="d-flex align-items-center mb-3 p-2 border-bottom">
                    <img src="${userImage}" 
                         class="rounded-circle me-3" width="40" height="40" 
                         style="object-fit: cover; border: 2px solid #007bff;">
                    <div>
                        <div class="fw-bold" style="color: #fff;">${user.user_full_name || 'Anonymous'}</div>
                        <small class="text-muted">@${user.user_qr_id}</small>
                    </div>
                </div>`;
            });
        }

        content += `</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>`;

        // Remove existing modal and add new one
        $('#followersModal').remove();
        $('body').append(content);

        // Initialize the modal manually and show it
        $('#followersModal').modal({
            backdrop: true,
            keyboard: true,
            focus: true
        }).modal('show');

        // Add explicit close handlers
        $('#followersModal .close, #followersModal [data-dismiss="modal"]').on('click', function () {
            $('#followersModal').modal('hide');
        });
    },
    handleLoginRedirect: function () {
        // Redirect to login page with return URL
        const currentUrl = window.location.href;
        window.location.href = '../ui/login.php?return=' + encodeURIComponent(currentUrl);
    },

    setupSocialLinks: function () {
        const socialLinksConfig = {
            website: { field: 'website', base: '' },
            facebook_username: { field: 'facebook_username', base: 'https://www.facebook.com/' },
            whatsapp_link: { field: 'whatsapp_link', base: 'https://wa.me/' },
            telegram_link: { field: 'telegram_link', base: 'https://t.me/' },
            twitter_username: { field: 'twitter_username', base: 'https://twitter.com/' },
            instagram_username: { field: 'instagram_username', base: 'https://instagram.com/' },
            youtube_username: { field: 'youtube_username', base: 'https://youtube.com/@' },
            linkedin_username: { field: 'linkedin_username', base: 'https://linkedin.com/in/' },
            snapchat_username: { field: 'snapchat_username', base: 'https://snapchat.com/add/' }
        };

        // Helper function to properly build social URL
        // If value is already a full URL, use it directly
        // If it's just a username, prepend the base URL
        const buildSocialUrl = (value, baseUrl, field) => {
            if (!value) return '';
            value = value.trim();
            
            // For website field, just use the value directly
            if (field === 'website') {
                return value.startsWith('http') ? value : 'https://' + value;
            }
            
            // If value is already a full URL (starts with http:// or https://)
            if (value.startsWith('http://') || value.startsWith('https://')) {
                return value;
            }
            
            // If value starts with www., add https://
            if (value.startsWith('www.')) {
                return 'https://' + value;
            }
            
            // Otherwise, it's a username - prepend base URL
            // Remove @ symbol if present (common for Instagram/Twitter usernames)
            const cleanValue = value.replace(/^@/, '');
            return baseUrl + cleanValue;
        };

        if (!window.PUBLIC_PROFILE) {
            // Edit mode - add visit links as before
            Object.entries(socialLinksConfig).forEach(([field, config]) => {
                const inputGroup = $(`#${field}`).closest('.input-group');
                if (!inputGroup.find('.visit-link').length) {
                    inputGroup.append(`
                    <div class="input-group-append">
                        <button class="btn btn-outline visit-link" data-field="${field}" data-base="${config.base}">
                            <i class="fas fa-external-link-alt"></i>
                        </button>
                    </div>
                `);
                }
            });

            $(document).on('click', '.visit-link', function (e) {
                e.preventDefault();
                const field = $(this).data('field');
                const baseUrl = $(this).data('base');
                const value = $(`#${field}`).val();
                if (value) {
                    const url = buildSocialUrl(value, baseUrl, field);
                    if (url) window.open(url, '_blank');
                }
            });
        } else {
            // Public view - make individual fields clickable with visit button
            Object.entries(socialLinksConfig).forEach(([field, config]) => {
                const inputField = $(`#${field}`);
                const value = inputField.val();

                if (value && value.trim() !== '') {
                    const finalUrl = buildSocialUrl(value, config.base, field);

                    // Add a visit button to the input group
                    const inputGroup = inputField.closest('.input-group');
                    if (!inputGroup.find('.public-visit-link').length) {
                        inputGroup.append(`
                        <div class="input-group-append">
                            <button class="btn btn-outline-primary public-visit-link" data-url="${finalUrl}">
                                <i class="fas fa-external-link-alt"></i>
                            </button>
                        </div>
                    `);
                    }

                    // Style the input field to look readonly but not clickable
                    inputField.css({
                        'background-color': 'rgba(255, 255, 255, 0.1)',
                        'border': '1px solid #444',
                        'color': '#007bff',
                        'cursor': 'default'
                    });
                }
            });

            // Handle public visit link clicks
            $(document).on('click', '.public-visit-link', function (e) {
                e.preventDefault();
                const url = $(this).data('url');
                window.open(url, '_blank');
            });
        }
        
        // Add real-time validation for website and social fields (non-public mode only)
        if (!window.PUBLIC_PROFILE) {
            // Validate website URL format
            const isValidWebsite = (value) => {
                if (!value || value.trim() === '') return true; // Empty is OK
                value = value.trim();
                // Must have at least one dot and valid TLD pattern
                return /^(https?:\/\/)?[a-zA-Z0-9][a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}/.test(value);
            };
            
            // Add validation to website field
            $(document).on('blur', '#website', function() {
                const value = $(this).val();
                const inputGroup = $(this).closest('.input-group');
                let errorEl = inputGroup.find('.validation-error');
                
                if (!isValidWebsite(value)) {
                    if (!errorEl.length) {
                        inputGroup.after('<div class="validation-error" style="color: #ff6b6b; font-size: 12px; margin-top: 4px;">Please enter a valid website URL (e.g., example.com or https://example.com)</div>');
                    }
                    $(this).css('border-color', '#ff6b6b');
                } else {
                    inputGroup.next('.validation-error').remove();
                    $(this).css('border-color', '');
                }
            });
            
            // Also validate on input to clear error when corrected
            $(document).on('input', '#website', function() {
                if (isValidWebsite($(this).val())) {
                    $(this).closest('.input-group').next('.validation-error').remove();
                    $(this).css('border-color', '');
                }
            });
            
            // Validate LinkedIn URL format
            const isValidLinkedIn = (value) => {
                if (!value || value.trim() === '') return true;
                const val = value.trim();
                // If it looks like a URL (has http, www, or .com)
                if (val.includes('http') || val.includes('www.') || val.includes('.com')) {
                   // Must be a valid LinkedIn profile URL
                   return /(?:https?:\/\/)?(?:www\.)?linkedin\.com\/in\/([^\/?#]+)/i.test(val);
                }
                return true; // Simple usernames are allowed
            };

            // Add validation to LinkedIn field
            $(document).on('blur', '#linkedin_username', function() {
                const value = $(this).val();
                const inputGroup = $(this).closest('.input-group');
                let errorEl = inputGroup.find('.validation-error');
                
                if (!isValidLinkedIn(value)) {
                    if (!errorEl.length) {
                        inputGroup.after('<div class="validation-error" style="color: #ff6b6b; font-size: 12px; margin-top: 4px;">Please enter a valid LinkedIn profile URL (e.g., linkedin.com/in/username)</div>');
                    }
                    $(this).css('border-color', '#ff6b6b');
                } else {
                    inputGroup.next('.validation-error').remove();
                    $(this).css('border-color', '');
                }
            });

            // Also validate on input (LinkedIn)
            $(document).on('input', '#linkedin_username', function() {
                if (isValidLinkedIn($(this).val())) {
                    $(this).closest('.input-group').next('.validation-error').remove();
                    $(this).css('border-color', '');
                }
            });
        }
    },


    getFollowersCount: function (qrId, followerId) {
        if (!qrId) return;

        const data = { qr_id: qrId };
        if (followerId) {
            data.follower_id = followerId;
        }

        $.ajax({
            url: '../backend/profile_new/get_followers_count.php',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            dataType: 'json',
            success: function (resp) {
                if (typeof resp.total_count !== 'undefined') {
                    $('#followers-count .stat-value').text(resp.total_count);
                }
                if (typeof resp.following_count !== 'undefined') {
                    $('#following-count .stat-value').text(resp.following_count);
                }
            }
        });
    },

    getFollowButtonDetails: function (qrId, followerId) {
        console.log('Getting follow button details for:', qrId, 'follower:', followerId);

        if (!qrId || !followerId) {
            console.log('Missing qrId or followerId');
            return;
        }

        $.ajax({
            url: '../backend/profile_new/get_user_type.php',
            type: 'POST',
            data: { qr_id: qrId },
            dataType: 'json',
            success: function (res) {
                console.log('User type response:', res);

                if (res && res.user_user_type != 1) {
                    // Check if already following
                    $.ajax({
                        url: '../backend/profile_new/check_follow_status.php',
                        type: 'POST',
                        data: JSON.stringify({ qr_id: qrId, follower_id: followerId }),
                        contentType: 'application/json',
                        dataType: 'json',
                        success: function (followRes) {
                            console.log('Follow status response:', followRes);

                            let btnHtml;
                            if (followRes.is_following) {
                                btnHtml = `
                                    <button class="btn btn-outline-danger" id="unfollow-btn">
                                        <i class="fas fa-user-minus"></i> Unfollow
                                    </button>
                                `;
                            } else {
                                btnHtml = `
                                    <button class="btn btn-primary" id="follow-btn">
                                        <i class="fas fa-user-plus"></i> Follow
                                    </button>
                                `;
                            }

                            $('#follow-btn-container').html(btnHtml).removeClass('hidden').show();
                            console.log('Follow button set and should be visible now');
                        },
                        error: function (xhr, status, error) {
                            console.error('Error checking follow status:', error, xhr.responseText);
                        }
                    });
                } else {
                    console.log('User type is 1, hiding follow button');
                    $('#follow-btn-container').addClass('hidden');
                }
            },
            error: function (xhr, status, error) {
                console.error('Error getting user type:', error, xhr.responseText);
            }
        });
    },

    setAllFieldsReadOnly: function () {
        // Make all form inputs readonly
        $('input, select, textarea').not('[type="hidden"]').prop('readonly', true);

        // Hide all privacy toggles and controls - but keep the structure
        $('.public-toggle, .public-toggle-input, .form-check').hide();

        // Disable file uploads
        $('#upload_profile_img').prop('disabled', true);

        // Remove click handlers for profile image
        $('#click_profile_img').off('click');
        $('.profile-image-container').css('cursor', 'default');

        // Hide edit-only elements
        $('.edit-only').hide();

        // Add public view styling
        $('body').addClass('public-view-mode');

        // Style basic info fields consistently
        $('#full_name, #phone_number, #email_address, #address, #landmark, #pincode').css({
            'background-color': 'rgba(255, 255, 255, 0.1)',
            'border': '1px solid #444',
            'color': '#fff',
            'cursor': 'default'
        });

        // Remove any unwanted click events from the entire form
        $('.form-control').off('click');
    }
    ,
    handleProfileImageUpload: function (e) {
        const file = this.files[0];
        if (!file) return;

        const ext = file.name.split('.').pop().toLowerCase();
        if (!['jpg', 'jpeg', 'png'].includes(ext)) {
            showToast('Only JPG and PNG files are allowed.', 'error');
            this.value = ''; // Clear the input
            return;
        }

        // Check file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            showToast('File size must be less than 5MB.', 'error');
            this.value = '';
            return;
        }

        const formData = new FormData();
        formData.append('profile_img', file);
        formData.append('user_id', $('#user_id').val());

        // Show loading state
        const $profileImg = $('#click_profile_img');
        const originalSrc = $profileImg.attr('src');
        $profileImg.css('opacity', '0.5');

        $.ajax({
            url: '../backend/profile_new/profile_upload.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                if (res.status) {
                    $profileImg.attr('src', res.src + '?t=' + Date.now()).css('opacity', '1');
                    showToast('Profile image updated successfully!');
                } else {
                    $profileImg.css('opacity', '1');
                    showToast(res.message || 'Upload failed.', 'error');
                }
            },
            error: function (xhr) {
                $profileImg.css('opacity', '1');
                console.error('Upload error:', xhr.responseText);
                showToast('Upload error.', 'error');
            }
        });

        // Clear the input
        this.value = '';
    },

    checkColorContrast: function (hexFg, hexBg) {
        // Helper to get brightness
        const getBrightness = (hex) => {
            const rgb = parseInt(hex.slice(1), 16);
            const r = (rgb >> 16) & 0xff;
            const g = (rgb >> 8) & 0xff;
            const b = (rgb >> 0) & 0xff;
            return (r * 299 + g * 587 + b * 114) / 1000;
        };

        const bFg = getBrightness(hexFg);
        const bBg = getBrightness(hexBg);

        // 1. Check if Background is darker than Foreground (Inverted)
        if (bBg < bFg) {
            return { valid: false, message: 'Bad Contrast: Background must be lighter than the QR dots.' };
        }

        // 2. Check difference (Threshold 100 is a safe bet for scanners)
        if ((bBg - bFg) < 100) {
            return { valid: false, message: 'Low Contrast: Please choose lighter background or darker dots.' };
        }

        return { valid: true };
    },

    handleQRColorChange: function () {
        const colorDark = $('#qr-color-dark').val();
        const colorLight = $('#qr-color-light').val();
        const userQr = $('#user_qr').val();

        // Check contrast live
        const check = profileFunction.checkColorContrast(colorDark, colorLight);
        if (!check.valid) {
            // Show toast only if it's a manual change (to avoid spamming on load if needed, but here it's fine)
            // But we don't want to stop the preview entirely, OR we might want to warn
            // Let's show a toast and MAYBE revert? Reverting live is annoying.
            // Let's just warn for now.
            showToast(check.message, 'error');
            // return; // Uncomment to freeze preview on bad colors
        }

        profileFunction.generateQRCode(userQr, colorDark, colorLight);
    },

    handleSaveQRColor: function () {
        const userId = $('#user_id').val();
        const userQr = $('#user_qr').val();
        const colorDark = $('#qr-color-dark').val();
        const colorLight = $('#qr-color-light').val();

        // Validate Contrast before saving
        const check = profileFunction.checkColorContrast(colorDark, colorLight);
        if (!check.valid) {
            showToast(check.message, 'error');
            // Reset to previous valid colors (optional, but good UX: reload from server)
            // For now, just stop saving. 
            // Better: Trigger init to fetch last saved valid colors? 
            // profileFunction.initQRCode(); 
            // Actually, simply returning prevents the bad save.
            return;
        }

        $.ajax({
            url: '../backend/profile_new/save_qr_color.php',
            type: 'POST',
            data: JSON.stringify({
                user_id: userId,
                colorDark: colorDark,
                colorLight: colorLight
            }),
            contentType: 'application/json',
            dataType: 'json',
            success: function (res) {
                if (res.status) {
                    showToast('QR colors saved successfully!');
                    // Regenerate QR code with saved colors and proper styling
                    const currentPath = window.location.pathname;
                    const basePath = currentPath.substring(0, currentPath.lastIndexOf('/') + 1);
                    const qrUrl = window.location.origin + basePath + 'profile.php?qr=' + encodeURIComponent(userQr || '');
                    profileFunction.generateQRCode(qrUrl, colorDark, colorLight);
                } else {
                    showToast(res.message || 'Failed to save QR colors.', 'error');
                }
            },
            error: function () {
                showToast('Error saving QR colors.', 'error');
            }
        });
    },

    handleDownloadQR: function () {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        // Instagram story size: 1080x1920 (9:16 ratio)
        canvas.width = 1080;
        canvas.height = 1920;

        // Background gradient
        const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
        gradient.addColorStop(0, '#667eea');
        gradient.addColorStop(1, '#764ba2');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Square white background for QR (no rounded corners)
        const whiteBoxSize = 850;
        const whiteBoxX = (canvas.width - whiteBoxSize) / 2;
        const whiteBoxY = (canvas.height - whiteBoxSize) / 2;

        ctx.fillStyle = 'white';
        ctx.shadowColor = 'rgba(0, 0, 0, 0.3)';
        ctx.shadowBlur = 40;
        ctx.shadowOffsetY = 20;
        ctx.fillRect(whiteBoxX, whiteBoxY, whiteBoxSize, whiteBoxSize);
        ctx.shadowColor = 'transparent';

        // Get QR image and frame image
        const qrImg = document.getElementById('click_banner_img');
        const frameOverlay = document.querySelector('.qr-frame-overlay');
        const frameImg = new Image();
        let useFrame = false;

        // Check if frame exists and is visible (not 'none')
        // Using getComputedStyle to ensure we catch hidden state even if not inline style
        if (frameOverlay && frameOverlay.src) {
            const style = window.getComputedStyle(frameOverlay);
            if (style.display !== 'none' && frameOverlay.src.indexOf('frame.png') === -1) { // Assuming 'frame.png' is just a placeholder name or check if real frame
                // Better check: if the src is not empty and display is not none
                // In profile.php, if no frame is selected, display is set to none
            }

            if (style.display !== 'none') {
                frameImg.src = frameOverlay.src;
                frameImg.crossOrigin = "Anonymous"; // Handle potential CORS issues with frame images
                useFrame = true;
            }
        }

        // Wait for both images to load
        if (qrImg && qrImg.src && qrImg.complete) {
            if (useFrame) {
                frameImg.onload = function () {
                    profileFunction.drawQROnCanvas(ctx, qrImg, frameImg, canvas);
                };
                frameImg.onerror = function () {
                    // Fallback if frame fails to load
                    profileFunction.drawQROnCanvas(ctx, qrImg, null, canvas);
                };
                // If frame already loaded
                if (frameImg.complete) {
                    profileFunction.drawQROnCanvas(ctx, qrImg, frameImg, canvas);
                }
            } else {
                profileFunction.drawQROnCanvas(ctx, qrImg, null, canvas);
            }
        } else {
            showToast('QR code not loaded yet. Please try again.', 'error');
        }
    },

    drawQROnCanvas: function (ctx, qrImg, frameImg, canvas) {
        // Calculate square white box dimensions
        const whiteBoxSize = 850;
        const whiteBoxX = (canvas.width - whiteBoxSize) / 2;
        const whiteBoxY = (canvas.height - whiteBoxSize) / 2;

        // Title
        ctx.fillStyle = '#2d3748';
        ctx.font = 'bold 60px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('', canvas.width / 2, whiteBoxY + 100);

        // QR Code size and position (centered in white box)
        const qrSize = 550;
        const qrX = (canvas.width - qrSize) / 2;
        const qrY = whiteBoxY + 160;

        // Draw QR code first
        try {
            ctx.drawImage(qrImg, qrX, qrY, qrSize, qrSize);
        } catch (e) {
            console.error('Error drawing QR code:', e);
        }

        // Draw frame overlay to match QR size
        if (frameImg) {
            const frameSize = 850;
            const frameX = (canvas.width - frameSize) / 2;
            const frameY = whiteBoxY + 10;
            try {
                ctx.drawImage(frameImg, frameX, frameY, frameSize, frameSize);
            } catch (e) {
                console.error('Error drawing frame:', e);
            }
        }

        // Bottom text
        ctx.fillStyle = '#718096';
        ctx.font = '40px Arial';
        ctx.fillText('', canvas.width / 2, whiteBoxY + whiteBoxSize - 60);

        // Convert to blob and download
        canvas.toBlob(function (blob) {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'QR-Code-Story.png';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showToast('QR code downloaded! Perfect for Instagram stories!', 'success');
        }, 'image/png');
    },

    handleFollowClick: function () {
        const qrId = new URLSearchParams(window.location.search).get('qr');

        // Check if user is logged in first
        $.ajax({
            url: '../backend/profile_new/check_login_status.php',
            type: 'GET',
            dataType: 'json',
            success: function (loginRes) {
                if (!loginRes.logged_in) {
                    profileFunction.handleLoginRedirect();
                    return;
                }

                const followerId = loginRes.user_id;

                $.ajax({
                    url: '../backend/profile_new/save_follow_user.php',
                    type: 'POST',
                    data: JSON.stringify({ qr_id: qrId, followers_id: followerId }),
                    contentType: 'application/json',
                    dataType: 'json',
                    success: function (res) {
                        if (res.status) {
                            showToast('Followed successfully!');
                            $('#follow-btn').replaceWith(`
                                <button class="btn btn-outline" id="unfollow-btn">
                                    <i class="fas fa-user-minus"></i> Unfollow
                                </button>
                            `);
                            profileFunction.getFollowersCount(qrId, followerId);
                        } else {
                            showToast(res.message || 'Could not follow.', 'error');
                        }
                    },
                    error: function () {
                        showToast('Error following user.', 'error');
                    }
                });
            }
        });
    },

    handleUnfollowClick: function () {
        const qrId = new URLSearchParams(window.location.search).get('qr');

        $.ajax({
            url: '../backend/profile_new/check_login_status.php',
            type: 'GET',
            dataType: 'json',
            success: function (loginRes) {
                if (!loginRes.logged_in) {
                    profileFunction.handleLoginRedirect();
                    return;
                }

                const followerId = loginRes.user_id;

                $.ajax({
                    url: '../backend/profile_new/unfollow_user.php',
                    type: 'POST',
                    data: JSON.stringify({ qr_id: qrId, followers_id: followerId }),
                    contentType: 'application/json',
                    dataType: 'json',
                    success: function (res) {
                        if (res.status) {
                            showToast('Unfollowed successfully!');
                            $('#unfollow-btn').replaceWith(`
                                <button class="btn btn-primary" id="follow-btn">
                                    <i class="fas fa-user-plus"></i> Follow
                                </button>
                            `);
                            profileFunction.getFollowersCount(qrId, followerId);
                        } else {
                            showToast(res.message || 'Could not unfollow.', 'error');
                        }
                    },
                    error: function () {
                        showToast('Error unfollowing user.', 'error');
                    }
                });
            }
        });
    },

    handleProfileSubmit: function (e) {
        e.preventDefault();
        console.log('Profile submit triggered'); // Debug log

        // Validate website field before saving
        const websiteValue = $('#website').val();
        if (websiteValue && websiteValue.trim() !== '') {
            const isValidWebsite = /^(https?:\/\/)?[a-zA-Z0-9][a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}/.test(websiteValue.trim());
            if (!isValidWebsite) {
                showToast('Please enter a valid website URL (e.g., example.com)', 'error');
                $('#website').focus().css('border-color', '#ff6b6b');
                return; // Stop form submission
            }
        }

        // Validate LinkedIn URL
        const linkedinVal = $('#linkedin_username').val();
        if (linkedinVal && linkedinVal.trim() !== '') {
            const val = linkedinVal.trim();
            // If it looks like a URL (has http, www, or .com)
            if (val.includes('http') || val.includes('www.') || val.includes('.com')) {
                // Must be a valid LinkedIn profile URL
                if (!/(?:https?:\/\/)?(?:www\.)?linkedin\.com\/in\/([^\/?#]+)/i.test(val)) {
                    showToast('Please enter a valid LinkedIn profile URL (e.g., linkedin.com/in/username)', 'error');
                    $('#linkedin_username').focus().css('border-color', '#ff6b6b');
                    return;
                }
            }
        }

        const formData = {
            user_id: $('#user_id').val(),
            user_full_name: $('#full_name').val(),
            phone_number: $('#phone_number').val(),
            user_email: $('#email_address').val(),
            user_address: $('#address').val(),
            user_pincode: $('#pincode').val(),
            user_landmark: $('#landmark').val(),
            is_public_address: $('#public_address').is(':checked') ? 1 : 0,
            links: {},
            fields: []
        };

        // Collect social media links
        const fields = [
            'website',
            'facebook_username',
            'whatsapp_link',
            'telegram_link',
            'twitter_username',
            'instagram_username',
            'youtube_username',
            'linkedin_username',
            'snapchat_username',
            'google_maps_link'
        ];

        fields.forEach(field => {
            formData.fields.push(field);
            // Google Maps link uses the address public toggle, not its own
            const isPublic = field === 'google_maps_link' 
                ? ($('#public_address').is(':checked') ? 1 : 0)
                : ($(`#public_${field}`).is(':checked') ? 1 : 0);
            formData.links[field] = {
                value: $(`#${field}`).val(),
                is_public: isPublic
            };
        });

        console.log('Sending form data:', formData); // Debug log

        $.ajax({
            url: '../backend/profile_new/save_profile_data.php',
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            dataType: 'json',
            success: function (res) {
                console.log('Profile update response:', res); // Debug log
                if (res.status) {
                    showToast('Profile updated successfully!', 'success');
                } else {
                    showToast(res.message || 'Profile update failed.', 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error('Profile update error:', error, xhr.responseText); // Debug log
                showToast('Error saving profile.', 'error');
            }
        });
    },


    getProfileData: function (qrId = "") {
        const data = qrId ? { qr: qrId } : { user_id: $('#user_id').val() };

        $.ajax({
            url: '../backend/profile_new/get_profile_data.php',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (res) {
                console.log('Profile data received:', res);

                if (res.user) {
                    const user = res.user;
                    let fullName = user.user_full_name || 'Anonymous';

                    // Proper case formatting function
                    const toProperCase = (str) => {
                        return str.toLowerCase().split(' ')
                            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                            .join(' ');
                    };

                    // Format the full name in proper case
                    fullName = toProperCase(fullName);

                    // Set initials
                    const initials = fullName.split(' ')
                        .map(word => word.charAt(0).toUpperCase())
                        .slice(0, 2)
                        .join('');
                    $('#click_profile_img').attr('data-initials', initials);

                    // Populate fields with properly formatted name
                    $('#full_name').val(fullName);
                    $('#phone_number').val(user.user_phone || '');
                    $('#email_address').val(user.user_email || '');
                    $('#address').val(user.user_address || '');
                    $('#landmark').val(user.user_landmark || '');
                    $('#pincode').val(user.user_pincode || '');
                    $('#user-name').text(fullName);

                    // Set QR ID
                    if (user.user_qr_id) {
                        $('#user-qr-id').text('@' + user.user_qr_id);
                    } else {
                        $('#user-qr-id').hide();
                    }

                    // Set public toggle for address
                    if (!window.PUBLIC_PROFILE) {
                        $('#public_address').prop('checked', user.is_public_address == 1);
                    }

                    // Show Google Maps field for business users (type 3)
                    // In public view, only show if address is public
                    if (user.user_user_type == 3) {
                        if (window.PUBLIC_PROFILE) {
                            // Public view: only show if address is public
                            if (user.is_public_address == 1) {
                                $('#google-maps-field').show();
                            }
                        } else {
                            // Own profile: always show for business users
                            $('#google-maps-field').show();
                        }
                    }

                    // Update the profile image with correct initials based on loaded name
                    profileFunction.updateProfileImageWithInitials(fullName);

                    // Hide empty fields in public view
                    if (window.PUBLIC_PROFILE) {
                        profileFunction.hideEmptyFields(user);
                    }
                }

                if (res.links) {
                    Object.entries(res.links).forEach(([fieldId, value]) => {
                        if (value && typeof value === 'object') {
                            const fieldValue = value.value || '';
                            const isPublic = value.is_public == 1;

                            $(`#${fieldId}`).val(fieldValue);
                            if (!window.PUBLIC_PROFILE) {
                                $(`#public_${fieldId}`).prop('checked', isPublic);
                            }
                        }
                    });

                    // Hide empty social media fields in public view
                    if (window.PUBLIC_PROFILE) {
                        profileFunction.hideEmptySocialFields(res.links);
                    }
                }

                // Setup social links after data is loaded
                profileFunction.setupSocialLinks();
            },
            error: function (xhr, status, error) {
                console.error('Error fetching profile data:', error);
            }
        });
    },

    updateProfileImageWithInitials: function (fullName) {
        const profileImg = $('#click_profile_img');
        const currentSrc = profileImg.attr('src') || '';
        const isGenerated = profileImg.attr('data-generated-initials') === '1';

        // If no image or already showing generated initials, update with the correct name
        if (!currentSrc || currentSrc.startsWith('data:image/svg') || isGenerated) {
            profileImg.attr('src', profileFunction.generateInitialsAvatar(fullName, 160));
            profileImg.attr('data-generated-initials', '1');
        }
    },

    hideEmptyFields: function (user) {
        // Hide basic info fields that are empty OR not public in public view
        const isAddressPublic = user.is_public_address == 1;

        const basicFields = {
            'full_name': { value: user.user_full_name, isPublic: true }, // Always show name
            'phone_number': { value: user.user_phone, isPublic: true }, // Always show phone
            'email_address': { value: user.user_email, isPublic: true }, // Always show email
            'address': { value: user.user_address, isPublic: isAddressPublic },
            'landmark': { value: user.user_landmark, isPublic: isAddressPublic },
            'pincode': { value: user.user_pincode, isPublic: isAddressPublic }
        };

        Object.entries(basicFields).forEach(([fieldId, field]) => {
            // Hide if empty OR if not public in public view
            if (!field.value || field.value.trim() === '' || (window.PUBLIC_PROFILE && !field.isPublic)) {
                $(`#${fieldId}`).closest('.col-md-3, .form-group').hide();
            }
        });

        // Hide "Address Information" header if address is not public in public view
        if (window.PUBLIC_PROFILE && !isAddressPublic) {
            $('.address-header-group').hide();
        }
    },
    hideEmptySocialFields: function (links) {
        // Define all possible social media fields
        const allSocialFields = [
            'website',
            'facebook_username',
            'whatsapp_link',
            'telegram_link',
            'twitter_username',
            'instagram_username',
            'youtube_username',
            'linkedin_username',
            'snapchat_username'
        ];

        let hasAnyLinks = false;

        // Hide fields that don't have values or aren't in the links data
        allSocialFields.forEach(fieldId => {
            const hasValue = links[fieldId] && links[fieldId].value && links[fieldId].value.trim() !== '';
            if (!hasValue) {
                // Hide the entire column/form group containing this field
                $(`#${fieldId}`).closest('.col-md-4, .col-lg-4, .form-group, .col').hide();
            } else {
                // Mark fields with values for different styling
                $(`#${fieldId}`).attr('data-has-link', 'true');
                hasAnyLinks = true;
            }
        });

        // Hide the entire Social Media Links section if no links are present
        if (!hasAnyLinks) {
            $('#social-media-section').hide();
        }
    },


    initQRCode: function (qrId = '') {
        console.log('Initializing QR code');
        const userId = $('#user_id').val();
        const userQr = qrId || $('#user_qr').val();

        // Ensure QR container and image exist
        const container = document.getElementById('qr-frame-container');
        if (!container) {
            console.error('QR container not found');
            return;
        }

        // Create or get QR image element
        let qrImg = document.getElementById('click_banner_img');
        if (!qrImg) {
            qrImg = document.createElement('img');
            qrImg.id = 'click_banner_img';
            qrImg.alt = 'QR Code';
            container.appendChild(qrImg);
        }

        const currentPath = window.location.pathname;
        const basePath = currentPath.substring(0, currentPath.lastIndexOf('/') + 1);
        const qrUrl = window.location.origin + basePath + 'profile.php?qr=' + encodeURIComponent(userQr || '');

        const data = {};
        if (userId) data.user_id = userId;
        if (qrId) data.qr_id = qrId;

        $.ajax({
            url: '../backend/profile_new/get_qr_color.php',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (res) {
                const colorDark = res.colorDark || '#000000';
                const colorLight = res.colorLight || '#ffffff';
                $('#qr-color-dark').val(colorDark);
                $('#qr-color-light').val(colorLight);

                // Add delay for mobile
                setTimeout(() => {
                    profileFunction.generateQRCode(qrUrl, colorDark, colorLight);
                }, 100);
            },
            error: function (xhr, status, error) {
                console.error('Error fetching QR colors:', error);
                setTimeout(() => {
                    profileFunction.generateQRCode(qrUrl, '#000000', '#ffffff');
                }, 100);
            }
        });
    },

    generateQRCode: function (data, colorDark = '#000000', colorLight = '#ffffff') {
        console.log('Generating QR code for:', data);

        // Create a temporary container for QR generation
        const tempDiv = document.createElement('div');
        tempDiv.style.position = 'absolute';
        tempDiv.style.left = '-9999px';
        document.body.appendChild(tempDiv);

        try {
            // Generate QR code
            new QRCode(tempDiv, {
                text: data,
                width: 192,
                height: 192,
                colorDark: colorDark,
                colorLight: colorLight,
                correctLevel: QRCode.CorrectLevel.H
            });

            // Wait for QR code to be generated
            setTimeout(() => {
                const qrCanvas = tempDiv.querySelector('canvas');
                const qrImg = tempDiv.querySelector('img');

                if (qrCanvas) {
                    // Use canvas if available (more reliable)
                    const dataURL = qrCanvas.toDataURL('image/png');
                    profileFunction.setQRImage(dataURL);
                    console.log('QR generated from canvas');
                } else if (qrImg && qrImg.complete) {
                    // Use image if canvas not available
                    profileFunction.setQRImage(qrImg.src);
                    console.log('QR generated from img');
                } else if (qrImg) {
                    // Wait for image to load
                    qrImg.onload = function () {
                        profileFunction.setQRImage(qrImg.src);
                        console.log('QR generated from img (delayed)');
                    };
                }

                // Cleanup
                setTimeout(() => {
                    if (tempDiv && tempDiv.parentNode) {
                        document.body.removeChild(tempDiv);
                    }
                }, 500);
            }, 200); // Increased timeout for mobile

        } catch (error) {
            console.error('QR generation error:', error);
            document.body.removeChild(tempDiv);
        }
    },
    setQRImage: function (src) {
        const element = document.getElementById('click_banner_img');

        if (!element) {
            console.error('QR image element not found');
            return;
        }

        // Create new image to ensure proper loading
        const img = new Image();
        img.onload = function () {
            element.src = src;
            element.style.display = 'block';
            element.style.width = '192px';
            element.style.height = '192px';
            element.style.position = 'relative';
            element.style.zIndex = '5';
            console.log('QR image set successfully');
        };
        img.onerror = function () {
            console.error('Failed to load QR image');
        };
        img.src = src;
    },

    checkForProfileImage: function () {
        // Check if we're viewing via QR parameter (handle both 'qr' and 'QR')
        const urlParams = new URLSearchParams(window.location.search);
        const viewingQr = urlParams.get('qr') || urlParams.get('QR');

        let requestData = {};

        if (viewingQr) {
            // Viewing someone's profile via QR - fetch THEIR image
            requestData = { qr: viewingQr };
        } else {
            // Viewing own profile - fetch logged-in user's image
            const userId = $('#user_id').val();
            if (!userId) return;
            requestData = { user_id: userId };
        }

        $.ajax({
            url: '../backend/profile_new/get_profile_image.php',
            type: 'POST',
            data: requestData,
            dataType: 'json',
            success: function (res) {
                if (res.status && res.src) {
                    // Fix the image path - ensure it points to the correct directory
                    let imagePath = res.src;

                    // Convert absolute path to relative if needed
                    if (imagePath.startsWith('/user/src/')) {
                        imagePath = '..' + imagePath.substring('/user/src'.length);
                    } else if (!imagePath.includes('backend') && !imagePath.startsWith('http')) {
                        imagePath = '../backend/ui/profile/' + imagePath.split('/').pop();
                    }

                    const img = new Image();
                    img.onload = function () {
                        // Image loaded successfully
                        $('#click_profile_img').attr('src', imagePath).removeAttr('data-generated-initials');
                    };
                    img.onerror = function () {
                        // Image failed to load, show initials instead
                        console.log('No profile image found, showing initials');
                        profileFunction.showInitialsAvatar();
                    };
                    img.src = imagePath;
                } else {
                    console.log('No profile image found, showing initials');
                    profileFunction.showInitialsAvatar();
                }
            },
            error: function () {
                console.log('Error fetching profile image, showing initials');
                profileFunction.showInitialsAvatar();
            }
        });
    },

    showInitialsAvatar: function () {
        const fullName = $('#full_name').val() || $('#user_qr').val() || 'User';
        const initials = fullName.split(' ')
            .map(word => word.charAt(0).toUpperCase())
            .slice(0, 2)
            .join('');

        const profileImg = $('#click_profile_img');
        profileImg.attr('src', profileFunction.generateInitialsAvatar(fullName, 160));
        profileImg.attr('data-generated-initials', '1');
    },

    generateInitialsAvatar: function (name, size = 64) {
        const initials = (name || 'User').split(' ')
            .map(n => n.charAt(0).toUpperCase())
            .slice(0, 2)
            .join('');

        const colors = [
            ['#667eea', '#764ba2'],
            ['#f093fb', '#f5576c'],
            ['#10b981', '#06b6d4'],
            ['#f59e0b', '#ef4444']
        ];

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

        return 'data:image/svg+xml;utf8,' + encodeURIComponent(svg);
    }
};

function showToast(message, type = 'success') {
    // Remove any existing toasts
    document.querySelectorAll('.toast-notification').forEach(toast => toast.remove());

    // Create new toast
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.textContent = message;

    // Apply styles directly to ensure they work
    Object.assign(toast.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        padding: '15px 20px',
        background: type === 'success'
            ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)'
            : 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
        color: 'white',
        borderRadius: '8px',
        boxShadow: '0 8px 25px rgba(0, 0, 0, 0.4)',
        zIndex: '2147483647', // Maximum z-index
        maxWidth: '350px',
        fontWeight: '500',
        opacity: '0',
        transform: 'translateX(400px)', // Start completely off-screen
        transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
        border: '1px solid rgba(255, 255, 255, 0.2)',
        pointerEvents: 'none',
        display: 'block',
        visibility: 'visible'
    });

    // Append to body
    document.body.appendChild(toast);

    // Force reflow and show
    toast.offsetHeight; // Force reflow
    toast.style.opacity = '1';
    toast.style.transform = 'translateX(0)';

    // Auto-hide after 4 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(400px)';
        setTimeout(() => {
            if (toast && toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 4000);
}

$(document).ready(function () {
    profileFunction.init();
});