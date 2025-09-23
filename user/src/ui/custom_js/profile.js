class ProfileManager {
    constructor() {
        this.init();
        this.attachEventListeners();
    }

    init() {
        this.loadProfile();
        this.loadFollowStats();
        this.initializeQRCode();
        this.checkViewMode();
    }

    checkViewMode() {
        // Check if viewing someone else's profile
        const urlParams = new URLSearchParams(window.location.search);
        this.viewingQr = urlParams.get('qr');
        this.isOwnProfile = !this.viewingQr || this.viewingQr === $('#user_qr').val();

        if (!this.isOwnProfile) {
            this.setReadOnlyMode();
        }
    }

    setReadOnlyMode() {
        $('input, select').prop('readonly', true);
        $('.public-toggle, #profile-upload, .save-buttons').hide();
        this.loadFollowButton();
    }

    loadProfile() {
        const endpoint = this.viewingQr ?
            `${ProfileConfig.api.profile.get}?qr=${this.viewingQr}` :
            ProfileConfig.api.profile.get;

        $.ajax({
            url: endpoint,
            type: 'GET',
            success: (response) => {
                if (response.success) {
                    this.updateProfileUI(response.data);
                }
            }
        });
    }

    updateProfileUI(data) {
        // Update basic info
        $('#full_name').val(data.full_name);
        $('#phone_number').val(data.phone_number);
        $('#email_address').val(data.email_address);
        $('#address').val(data.address);
        $('#user-name').text(data.full_name);

        // Update profile image
        if (data.profile_image) {
            $('#profile-image').attr('src', data.profile_image);
        }

        // Update social links
        if (data.social_links) {
            this.updateSocialLinks(data.social_links);
        }
    }

    updateSocialLinks(links) {
        ProfileConfig.socialPlatforms.forEach(platform => {
            const link = links[platform.id];
            if (link) {
                $(`#${platform.id}`).val(link.value);
                $(`#public_${platform.id}`).prop('checked', link.is_public);
            }
        });
    }

    loadFollowStats() {
        const userId = this.viewingQr || $('#user_qr').val();

        $.ajax({
            url: ProfileConfig.api.follow.count,
            type: 'POST',
            data: { user_id: userId },
            success: (response) => {
                if (response.success) {
                    $('#followers-count').text(response.data.followers);
                    $('#following-count').text(response.data.following);
                }
            }
        });
    }

    loadFollowButton() {
        const followerId = $('#user_id').val();
        const followingId = this.viewingQr;

        if (!followerId || !followingId) return;

        $.ajax({
            url: ProfileConfig.api.follow.status,
            type: 'POST',
            data: { follower_id: followerId, following_id: followingId },
            success: (response) => {
                const btn = $('#follow-btn');
                btn.text(response.following ? 'Following' : 'Follow');
                btn.toggleClass('following', response.following);
            }
        });
    }

    attachEventListeners() {
        // Profile image upload
        $('#profile-upload').on('change', (e) => this.handleImageUpload(e));

        // Form submission
        $('#profile-form').on('submit', (e) => this.handleFormSubmit(e));

        // QR color changes
        $('#qr-color-dark, #qr-color-light').on('change', () => this.updateQRCode());

        // Follow button
        $('#follow-btn').on('click', () => this.handleFollow());

        // Save QR colors
        $('#save-qr-color').on('click', () => this.saveQRColors());
    }

    handleImageUpload(event) {
        const file = event.target.files[0];
        if (!file) return;

        if (!ProfileConfig.upload.image.allowedTypes.includes(file.type)) {
            this.showToast('Only JPG and PNG files are allowed', 'error');
            return;
        }

        if (file.size > ProfileConfig.upload.image.maxSize) {
            this.showToast('File size exceeds 5MB limit', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('profile_image', file);

        $.ajax({
            url: ProfileConfig.api.profile.upload,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (response) => {
                if (response.success) {
                    $('#profile-image').attr('src', response.data.url);
                    this.showToast('Profile image updated successfully');
                }
            }
        });
    }

    handleFormSubmit(event) {
        event.preventDefault();

        const formData = {
            basic: {
                full_name: $('#full_name').val(),
                phone_number: $('#phone_number').val(),
                email_address: $('#email_address').val(),
                address: $('#address').val()
            },
            social: {}
        };

        // Collect social media data
        ProfileConfig.socialPlatforms.forEach(platform => {
            formData.social[platform.id] = {
                value: $(`#${platform.id}`).val(),
                is_public: $(`#public_${platform.id}`).is(':checked')
            };
        });

        $.ajax({
            url: ProfileConfig.api.profile.update,
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: (response) => {
                if (response.success) {
                    this.showToast('Profile updated successfully');
                }
            }
        });
    }

    initializeQRCode() {
        const userId = this.viewingQr || $('#user_qr').val();

        $.ajax({
            url: ProfileConfig.api.qr.colors.get,
            type: 'POST',
            data: { user_id: userId },
            success: (response) => {
                if (response.success) {
                    this.updateQRCode(response.data.dark, response.data.light);
                } else {
                    this.updateQRCode();
                }
            }
        });
    }

    updateQRCode(dark = null, light = null) {
        const darkColor = dark || $('#qr-color-dark').val() || ProfileConfig.qrCode.defaultDark;
        const lightColor = light || $('#qr-color-light').val() || ProfileConfig.qrCode.defaultLight;

        $('#qr-code').empty();
        new QRCode(document.getElementById('qr-code'), {
            text: window.location.origin + window.location.pathname + '?qr=' + (this.viewingQr || $('#user_qr').val()),
            width: ProfileConfig.qrCode.size,
            height: ProfileConfig.qrCode.size,
            colorDark: darkColor,
            colorLight: lightColor,
            correctLevel: QRCode.CorrectLevel[ProfileConfig.qrCode.level]
        });
    }

    saveQRColors() {
        $.ajax({
            url: ProfileConfig.api.qr.colors.save,
            type: 'POST',
            data: {
                dark: $('#qr-color-dark').val(),
                light: $('#qr-color-light').val()
            },
            success: (response) => {
                if (response.success) {
                    this.showToast('QR colors saved successfully');
                }
            }
        });
    }

    handleFollow() {
        const btn = $('#follow-btn');
        const following = btn.hasClass('following');

        $.ajax({
            url: ProfileConfig.api.follow.toggle,
            type: 'POST',
            data: {
                follower_id: $('#user_id').val(),
                following_id: this.viewingQr,
                action: following ? 'unfollow' : 'follow'
            },
            success: (response) => {
                if (response.success) {
                    btn.text(following ? 'Follow' : 'Following');
                    btn.toggleClass('following');
                    this.loadFollowStats();
                }
            }
        });
    }

    showToast(message, type = 'success') {
        const toast = $('#toast');
        toast.text(message)
            .removeClass()
            .addClass(`toast ${type}`)
            .addClass('show');

        setTimeout(() => {
            toast.removeClass('show');
        }, 3000);
    }
}

// Initialize on document ready
$(document).ready(() => {
    new ProfileManager();
});