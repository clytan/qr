// Load user referral stats
function loadReferralStats() {
    $.ajax({
        url: '../backend/get_referral_stats.php',
        type: 'GET',
        dataType: 'json',
        success: function (response) {
            if (response.status && response.data) {
                const data = response.data;
                $('#referral-code').text(data.referral_code || 'N/A');
                $('#total-referrals').text(data.total_referrals || 0);
                $('#active-referrals').text(data.active_referrals || 0);
                $('#total-earnings').text('₹' + (data.total_earnings || 0));

                // Setup share links - using production zokli.in URLs
                const shareText = `Join Zokli community, using my referral code: ${data.referral_code}`;
                const profileUrl = `https://www.zokli.in/profile?QR=${encodeURIComponent(data.referral_code)}`;
                const registerUrl = `https://www.zokli.in/register?ref=${data.referral_code}`;

                // Display URLs (cleaner for text)
                const displayProfileUrl = `zokli.in/profile?QR=${encodeURIComponent(data.referral_code)}`;
                const displayRegisterUrl = `zokli.in/register?ref=${data.referral_code}`;

                const tagline = `Be a part of this wonderful, digital social media community.`;
                const fullMessage = `${shareText}\nView Profile: ${displayProfileUrl}\nRegister: ${displayRegisterUrl}\n${tagline}`;

                $('#share-whatsapp').attr('href', `https://wa.me/?text=${encodeURIComponent(fullMessage)}`);
                $('#share-telegram').attr('href', `https://t.me/share/url?url=${encodeURIComponent(registerUrl)}&text=${encodeURIComponent(shareText + '\n' + tagline)}`);
                $('#share-twitter').attr('href', `https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText + ' ' + tagline)}&url=${encodeURIComponent(registerUrl)}`);

                // Facebook Messenger: use mobile deep link and fallback to Facebook sharer (no app_id required)
                const messengerDeep = `fb-messenger://share?link=${encodeURIComponent(registerUrl)}`;
                const fbSharer = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(registerUrl)}&quote=${encodeURIComponent(shareText)}`;
                $('#share-messenger').attr('href', messengerDeep);
                // Try deep link first; if it fails (desktop browsers), open Facebook sharer as fallback
                $('#share-messenger').off('click').on('click', function (e) {
                    e.preventDefault();
                    // Attempt to open the messenger app via deep link
                    const start = Date.now();
                    // For some browsers, assigning window.location will attempt to open the app
                    window.location = messengerDeep;
                    // After a short delay open the fallback sharer in a new tab
                    setTimeout(function () {
                        // If still on the page, open fallback
                        if (Date.now() - start > 50) {
                            window.open(fbSharer, '_blank');
                        }
                    }, 700);
                });

                // Instagram: Instagram web doesn't support direct URL sharing via intent.
                // We'll make the button copy the message to clipboard and then open Instagram web profile.
                $('#share-instagram').attr('href', '#');
                $('#share-instagram').on('click', function (e) {
                    e.preventDefault();
                    navigator.clipboard.writeText(fullMessage).then(() => {
                        window.open('https://www.instagram.com', '_blank');
                        showToast('Share text copied to clipboard. Opening Instagram...', 'success');
                    }).catch(() => {
                        window.open('https://www.instagram.com', '_blank');
                        showToast('Unable to copy automatically. Please paste your referral message manually in Instagram.', 'warning');
                    });
                });
            }
        },
        error: function () {
            $('#referral-code').text('Error loading');
        }
    });
}

// Load leaderboard with optional period filter
function loadLeaderboard(period = 'all') {
    // Show loading state
    $('#leaderboard-content').html(`
        <div class="loading">
            <i class="fas fa-spinner"></i>
            <p>Loading leaderboard...</p>
        </div>
    `);

    $.ajax({
        url: '../backend/get_referral_stats.php?leaderboard=1&period=' + period,
        type: 'GET',
        dataType: 'json',
        success: function (response) {
            if (response.status && response.leaderboard) {
                const leaderboard = response.leaderboard;
                let html = '';

                if (leaderboard.length === 0) {
                    let periodText = period === 'week' ? 'this week' : (period === 'month' ? 'this month' : '');
                    html = `
                        <div class="empty-leaderboard">
                            <i class="fas fa-trophy"></i>
                            <p>No referrals ${periodText ? periodText : 'yet'}. Be the first to climb the leaderboard!</p>
                        </div>
                    `;
                } else {
                    // Render clean professional rows
                    leaderboard.forEach((user, index) => {
                        const rank = index + 1;
                        const initial = (user.user_qr_id || 'U').charAt(0).toUpperCase();
                        const displayName = user.user_qr_id || 'Anonymous';

                        // Top row class
                        let topClass = '';
                        let rankIcon = '';
                        let rankClass = '';

                        if (rank === 1) {
                            topClass = 'top-1';
                            rankClass = 'rank-1';
                            rankIcon = '<i class=""></i>';
                        } else if (rank === 2) {
                            topClass = 'top-2';
                            rankClass = 'rank-2';
                            rankIcon = '<i class=""></i>';
                        } else if (rank === 3) {
                            topClass = 'top-3';
                            rankClass = 'rank-3';
                            rankIcon = '<i class=""></i>';
                        }

                        html += `
                            <div class="leaderboard-row ${topClass}">
                                <div class="rank ${rankClass}">${rankIcon}#${rank}</div>
                                <div class="user-info">
                                    <div class="user-avatar d-none">${initial}</div>
                                    <div class="user-name">${displayName}</div>
                                </div>
                                <div class="referral-count">
                                    <i class="fas fa-users"></i>
                                    <span>${user.referral_count || 0}</span>
                                </div>
                            </div>
                        `;
                    });
                }

                $('#leaderboard-content').html(html);
            }
        },
        error: function () {
            $('#leaderboard-content').html(`
                <div class="empty-leaderboard">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Error loading leaderboard. Please try again later.</p>
                </div>
            `);
        }
    });
}

// Copy referral code
$('#copy-code-btn').on('click', function () {
    const code = $('#referral-code').text();
    if (code && code !== 'Loading...' && code !== 'Error loading') {
        navigator.clipboard.writeText(code).then(() => {
            const btn = $(this);
            const originalIcon = btn.html();
            btn.html('<i class="fas fa-check"></i>');
            btn.css('background', 'linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%)');
            setTimeout(() => {
                btn.html(originalIcon);
                btn.css('background', '');
            }, 2000);
        });
    }
});

// Tab switching with period filtering
$('.leaderboard-tab').on('click', function () {
    $('.leaderboard-tab').removeClass('active');
    $(this).addClass('active');
    const period = $(this).data('period');
    loadLeaderboard(period);
});

// Load data on page load
$(document).ready(function () {
    loadReferralStats();
    loadLeaderboard();

    // Initialize Prize Carousel
    if (jQuery().owlCarousel) {
        $('#prizeCarousel').owlCarousel({
            loop: true,
            margin: 10,
            nav: false,
            dots: false,
            autoplay: true,
            autoplayTimeout: 3000,
            responsive: {
                0: { items: 1 },
                600: { items: 2 },
                1000: { items: 3 }
            }
        });
    }

});
