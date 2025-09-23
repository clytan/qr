const PROFILE_CONFIG = {
    // API Endpoints
    endpoints: {
        uploadImage: '../backend/profile/upload_profile_image.php',
        getProfile: '../backend/profile/get_profile_data.php',
        updateProfile: '../backend/profile/update_profile.php',
        getFollowers: '../backend/profile/get_followers.php',
        followUser: '../backend/profile/follow_user.php',
        saveQrColors: '../backend/profile/save_qr_colors.php',
        getQrColors: '../backend/profile/get_qr_colors.php'
    },

    // Social Media Configurations
    socialMedia: [
        {
            id: 'website',
            label: 'Website URL',
            icon: 'fa-globe',
            placeholder: 'Enter website URL',
            urlPrefix: 'https://'
        },
        {
            id: 'whatsapp_link',
            label: 'WhatsApp',
            icon: 'fa-whatsapp',
            placeholder: 'Enter WhatsApp number',
            urlPrefix: 'https://wa.me/'
        },
        {
            id: 'telegram_link',
            label: 'Telegram',
            icon: 'fa-telegram',
            placeholder: 'Enter Telegram username',
            urlPrefix: 'https://t.me/'
        },
        {
            id: 'twitter_username',
            label: 'Twitter',
            icon: 'fa-twitter',
            placeholder: 'Enter Twitter username',
            urlPrefix: 'https://twitter.com/'
        },
        {
            id: 'instagram_username',
            label: 'Instagram',
            icon: 'fa-instagram',
            placeholder: 'Enter Instagram username',
            urlPrefix: 'https://instagram.com/'
        },
        {
            id: 'youtube_username',
            label: 'YouTube',
            icon: 'fa-youtube',
            placeholder: 'Enter YouTube channel',
            urlPrefix: 'https://youtube.com/'
        },
        {
            id: 'linkedin_username',
            label: 'LinkedIn',
            icon: 'fa-linkedin',
            placeholder: 'Enter LinkedIn profile',
            urlPrefix: 'https://linkedin.com/in/'
        },
        {
            id: 'snapchat_username',
            label: 'Snapchat',
            icon: 'fa-snapchat',
            placeholder: 'Enter Snapchat username',
            urlPrefix: 'https://snapchat.com/add/'
        }
    ],

    // Default QR Code settings
    qrCode: {
        defaultColors: {
            dark: '#000000',
            light: '#FFFFFF'
        },
        size: 192,
        correctLevel: 'H'
    },

    // UI text and messages
    ui: {
        toastDuration: 3000,
        messages: {
            profileUpdated: 'Profile updated successfully!',
            imageUploaded: 'Profile image uploaded successfully!',
            qrColorsSaved: 'QR colors saved successfully!',
            copySuccess: 'Copied to clipboard!',
            error: {
                imageUpload: 'Failed to upload image',
                profileUpdate: 'Failed to update profile',
                qrColors: 'Failed to save QR colors',
                network: 'Network error occurred'
            }
        }
    }
};