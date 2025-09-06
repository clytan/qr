// Profile image upload handler
var eventHandler={
    init: function() {
        eventHandler.onClickEvent();
        eventHandler.onChangeEvent();
        eventHandler.onSubmitEvent();
    },
    onClickEvent: function() {
        var $qrColorDark = $('#qr-color-dark');
        var $qrColorLight = $('#qr-color-light');
        var $saveQrColor = $('#save-qr-color');
        var userId = $('#user_id').val();
        // Save color preferences to backend
        $saveQrColor.on('click', function() {
            var colorDark = $qrColorDark.val();
            var colorLight = $qrColorLight.val();
            $.ajax({
                url: '../backend/save_qr_color.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ user_id: userId, colorDark: colorDark, colorLight: colorLight }),
                dataType: 'json',
                success: function(res) {
                    if (res.status) {
                        profileFunction.initQRCode();
                        alert('QR color saved!');
                    } else {
                        alert(res.message || 'Failed to save QR color.');
                    }
                },
                error: function() {
                    alert('Error saving QR color.');
                }
            });
        });
        // Attach follow button click handler (delegated for dynamic button)
        $(document).on('click', '#follow-btn', function() {
            // Get qr_id from URL
            var urlParams = new URLSearchParams(window.location.search);
            var qr_id = urlParams.get('qr');
            // Get followers_id from user_id input (the logged-in user)
            var followers_id = $('#user_id').val();
            if (!qr_id || !followers_id) {
                alert('Missing user information.');
                return;
            }
            // Post to backend to follow
            $.ajax({
                url: '../backend/save_follow_user.php',
                type: 'POST',
                data: JSON.stringify({ qr_id: qr_id, followers_id: followers_id }),
                contentType: 'application/json',
                dataType: 'json',
                success: function(res) {
                    if (res.status) {
                        alert('Followed successfully!');
                        $('#follow-icon').text('✓');
                        $('#follow-btn').prop('disabled', true);
                        
                    } else {
                        alert(res.message || 'Could not follow.');
                    }
                },
                error: function() {
                    alert('Error following user.');
                }
            });
        });
    },
    onChangeEvent: function() {
        var $qrColorDark = $('#qr-color-dark');
        var $qrColorLight = $('#qr-color-light');
        $('#upload_profile_img').on('change', function(e) {
            var file = this.files[0];
            if (!file) return;
            var ext = file.name.split('.').pop().toLowerCase();
            if ($.inArray(ext, ['jpg', 'jpeg', 'png']) === -1) {
                alert('Only JPG and PNG files are allowed.');
                return;
            }
            var formData = new FormData();
            formData.append('profile_img', file);
            var userId = $('#user_id').val();
            if (userId) {
                formData.append('user_id', userId);
            }
            $.ajax({
                url: '../backend/profile_upload.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    if (res.status) {
                        $('#click_profile_img').attr('src', res.src);
                    } else {
                        alert(res.message || 'Upload failed.');
                    }
                },
                error: function() {
                    alert('Upload error.');
                }
            });
        });
        // When user changes color, update QR code preview instantly
        $qrColorDark.on('input', function() {
            // Save value locally but don't persist yet
            var colorDark = $qrColorDark.val();
            var colorLight = $qrColorLight.val();
            var userQr = $('#user_qr').val();
            var path = window.location.pathname;
            if (path.indexOf('index.php') !== -1) {
                path = path.replace('index.php', 'login.php');
            }
            var qrUrl = window.location.origin + path + '?qr=' + encodeURIComponent(userQr || '');
            profileFunction.generateQRCode(qrUrl, 'click_banner_img', colorDark, colorLight);
        });
        $qrColorLight.on('input', function() {
            var colorDark = $qrColorDark.val();
            var colorLight = $qrColorLight.val();
            var userQr = $('#user_qr').val();
            var path = window.location.pathname;
            if (path.indexOf('index.php') !== -1) {
                path = path.replace('index.php', 'login.php');
            }
            var qrUrl = window.location.origin + path + '?qr=' + encodeURIComponent(userQr || '');
            profileFunction.generateQRCode(qrUrl, 'click_banner_img', colorDark, colorLight);
        });

    },
    onSubmitEvent: function() {
        // Profile form submit handler
        $('#form-create-item').on('submit', function(e) {
            e.preventDefault();
            var formData = {};
            
            // User fields
            formData.user_id = $('#user_id').val();
            formData.user_full_name = $('#full_name').val();
            formData.phone_number = $('#phone_number').val();
            formData.user_email = $('#email_address').val();
            formData.user_address = $('#address').val();
            // Links (socials, website, etc.)
            var links = {};
            // Website
            links.website = {
                value: $('#website').val(),
                is_public: $('#public_website').is(':checked') ? 1 : 0
            };
            // WhatsApp
            links.whatsapp_link = {
                value: $('#whatsapp_link').val(),
                is_public: $('#public_whatsapp_link').is(':checked') ? 1 : 0
            };
            // Telegram
            links.telegram_link = {
                value: $('#telegram_link').val(),
                is_public: $('#public_telegram_link').is(':checked') ? 1 : 0
            };
            // Socials
            ['twitter','instagram','youtube','linkedin','snapchat'].forEach(function(type) {
                links[ type + '_username'] = {
                    value: $('#' + type + '_username').val(),
                    is_public: $('#public_' + type + '_username').is(':checked') ? 1 : 0
                };
            });
            formData.links = links;
            // Also send public/private status for each field
            var fields = [];
            $('.public-toggle').each(function() {
                var target = $(this).data('target');
                fields.push(target)
            });
            formData.fields = fields;
            // Send via AJAX
            $.ajax({
                url: '../backend/save_profile_data.php',
                type: 'POST',
                data: JSON.stringify(formData),
                contentType: 'application/json',
                dataType: 'json',
                success: function(res) {
                    if (res.status) {
                        alert('Profile updated successfully!');
                    } else {
                        alert(res.message || 'Profile update failed.');
                    }
                },
                error: function() {
                    alert('Error saving profile.');
                }
            });
        });

    }
}

var profileFunction={ 
    init: function(){
        // If PUBLIC_PROFILE is set, make all fields readonly/disabled
        if (window.PUBLIC_PROFILE) {
            qrId = (new URLSearchParams(window.location.search)).get('qr');
            profileFunction.getProfileData(qrId)  
            profileFunction.initQRCode(qrId);
            profileFunction.getFollowButtonDetails(qrId);
        }
        else{
            profileFunction.getProfileData();
            profileFunction.initQRCode();
        }
        profileFunction.checkForProfileImage();

    },
    getFollowButtonDetails: function(qrId) {
        var followerId = $('#user_id').val();
        if (qrId) {
            $.ajax({
                url: '../backend/get_user_type.php',
                type: 'POST',
                data: { qr_id: qrId },
                dataType: 'json',
                success: function(res) {
                    if (res && typeof res.user_user_type !== 'undefined' && res.user_user_type != 1) {
                        // Add follow button and followers count
                        var countSpan = $('<span>', {
                            id: 'followers-count',
                            style: 'margin-right:10px;font-weight:900;color:#fff;vertical-align:middle;background:none;padding:4px 18px;padding-left:0px;min-width:54px;min-height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;font-size:1.18em;letter-spacing:0.5px;line-height:1.2;box-sizing:border-box;',
                            text: 'Followers: ...'
                        });
                        var btn = $('<button>', {
                            id: 'follow-btn',
                            class: 'btn btn-primary',
                            type: 'button',
                            style: 'display:inline-flex;align-items:center;margin-bottom:2px;padding:4px 12px;font-size:0.92em;min-height:28px;',
                            html: '<span id="follow-icon" style="font-size:1em;line-height:1;">+</span> <span style="margin-left:3px;">Follow</span>'
                        });
                       
                        var $container = $('#follow-btn-container');
                        $container.append(countSpan).append(btn);
                        // Fetch followers count
                        $.ajax({
                            url: '../backend/get_followers_count.php',
                            type: 'POST',
                            data: JSON.stringify({ qr_id: qrId, follower_id: followerId }),
                            contentType: 'application/json',
                            dataType: 'json',
                            success: function(resp) {
                                if (typeof resp.total_count !== 'undefined') {
                                    $('#followers-count').text('Followers: ' + resp.total_count);
                                    if(resp.following){
                                        $('#follow-icon').text('✓');
                                        $('#follow-btn').prop('disabled', true);
                                    }
                                } else {
                                    $('#followers-count').text('Followers: 0');
                                }
                            },
                            error: function() {
                                $('#followers-count').text('Followers: 0');
                            }
                        });
                        if ($('#follow-btn-style').length === 0) {
                            var style = $('<style>', { id: 'follow-btn-style', type: 'text/css' });
                            style.html(
                                '#follow-btn {' +
                                    'border: 2px solid #007bff;' +
                                    'background: #fff;' +
                                    'color: #212428;' +
                                    'transition: background 0.2s, color 0.2s;' +
                                    'font-weight: 600;' +
                                    'min-height: 28px;' +
                                    'padding: 4px 12px;' +
                                    'font-size: 0.92em;' +
                                    'display: inline-flex;' +
                                    'align-items: center;' +
                                '}' +
                                '#follow-btn:hover, #follow-btn:focus {' +
                                    'background: #212428;' +
                                    'color: #fff;' +
                                    'border-color: #007bff;' +
                                '}' +
                                '#follow-btn span:first-child {' +
                                    'font-size: 1em;' +
                                    'line-height: 1;' +
                                '}' +
                                '#followers-count {' +
                                    'font-weight: 900;' +
                                    'color: #fff;' +
                                    'background: none;' +
                                    'padding: 4px 18px;' +
                                    'min-width: 54px;' +
                                    'min-height: 36px;' +
                                    'display: inline-flex;' +
                                    'align-items: center;' +
                                    'justify-content: center;' +
                                    'border-radius: 8px;' +
                                    'font-size: 1.18em;' +
                                    'margin-right: 15px;' +
                                    'letter-spacing: 0.5px;' +
                                    'line-height: 1.2;' +
                                    'box-sizing: border-box;' +
                                '}'
                            );
                            $('head').append(style);
                        }
                    }
                }
            });
        }
    },
    showOnlyPublicFields:function(){
        // Hide all input groups initially
        $('.input-group.mb-2').hide();
        // Remove any previously created dynamic container
        $('#public-profile-links').remove();

        // Create a new container for public fields
        var $container = $('<div id="public-profile-links"></div>');

        // For each public-toggle, if checked, clone its group and append to container with a spacer before each
        $('.public-toggle').each(function() {
            var isPublic = $(this).is(':checked');
            var target = $(this).data('target');
            var $input = $('#' + target);
            var $group = $input.closest('.input-group.mb-2');
            if (isPublic && $group.length) {
                var $clonedGroup = $group.clone().show();
                // Remove any previous button
                $clonedGroup.find('.public-link-btn').remove();
                // Add a button if in PUBLIC_PROFILE and the input has a value
                if (window.PUBLIC_PROFILE) {
                    var val = $input.val();
                    if (val) {
                        // Determine icon and link for each type
                        var icon = '';
                        var href = val;
                        if (target === 'website') {
                            icon = '<i class="fa fa-globe"></i>';
                            if (!/^https?:\/\//i.test(href)) href = 'https://' + href;
                        } else if (target === 'twitter_username') {
                            icon = '<i class="fa fa-twitter"></i>';
                            if (!/^https?:\/\//i.test(href)) href = 'https://twitter.com/' + val.replace(/^@/, '');
                        } else if (target === 'instagram_username') {
                            icon = '<i class="fa fa-instagram"></i>';
                            if (!/^https?:\/\//i.test(href)) href = 'https://instagram.com/' + val.replace(/^@/, '');
                        } else if (target === 'youtube_username') {
                            icon = '<i class="fa fa-youtube"></i>';
                            if (!/^https?:\/\//i.test(href)) href = 'https://youtube.com/' + val;
                        } else if (target === 'linkedin_username') {
                            icon = '<i class="fa fa-linkedin"></i>';
                            if (!/^https?:\/\//i.test(href)) href = 'https://linkedin.com/in/' + val.replace(/^@/, '');
                        } else if (target === 'snapchat_username') {
                            icon = '<i class="fa fa-snapchat"></i>';
                            if (!/^https?:\/\//i.test(href)) href = 'https://snapchat.com/add/' + val.replace(/^@/, '');
                        } else if (target === 'whatsapp_link') {
                            icon = '<i class="fa fa-whatsapp"></i>';
                            // Accept both wa.me links and numbers
                            if (/^\d{10,}$/.test(val)) {
                                href = 'https://wa.me/' + val;
                            } else if (!/^https?:\/\//i.test(href)) {
                                href = 'https://' + val;
                            }
                        } else if (target === 'telegram_link') {
                            icon = '<i class="fa fa-telegram"></i>';
                            if (!/^https?:\/\//i.test(href)) href = 'https://t.me/' + val.replace(/^@/, '');
                        }
                        var $btn = $('<a class="btn btn-sm btn-primary ms-2 public-link-btn" href="' + href + '" target="_blank" title="Open Link">' + icon + '</a>');
                        // Insert after the input
                        $clonedGroup.find('input.form-control').after($btn);
                    }
                }
                $container.append('<div class="spacer-20"></div>');
                $container.append($clonedGroup);
            }
        });

        // Insert the container after the email address field's spacer
        var $emailSpacer = $('#email_address').closest('.spacer-20');
        if ($emailSpacer.length) {
            $emailSpacer.after($container);
        } else {
            // fallback: after email field
            $('#email_address').after($container);
        }

        // Hide the address field if not public, else show it
        var $address = $('#address');
        var addressPublic = $('#public_address').is(':checked');
        if (!addressPublic) {
            $address.hide();
            $address.next('div').hide();
        } else {
            $address.show();
            $address.next('div').show();
        }
        // Inject CSS for icon-only hover effect (once) using jQuery
        if ($('#public-link-icon-hover-style').length === 0) {
            var style = $('<style>', { id: 'public-link-icon-hover-style', type: 'text/css' });
            style.html(`
                a.public-link-btn:hover, a.public-link-btn:focus {
                    background-color: inherit !important;
                    color: inherit !important;
                    box-shadow: none !important;
                    border-color: inherit !important;
                }
                a.public-link-btn i {
                    font-size: 2em !important;
                    transition: color 0.2s, transform 0.2s;
                }
                a.public-link-btn i:hover,
                a.public-link-btn:focus i {
                    color: #ffd700 !important;
                    transform: scale(1.2);
                }
            `);
            $('head').append(style);
        }
    },
    initQRCode:function(qrId=''){
        var userId = '';
        var userQr = '';
        if(qrId && qrId !== ''){
            userQr = qrId;
        } else {
            userId = $('#user_id').val();
            userQr = $('#user_qr').val();
        }
        var path = window.location.pathname;
        if (path.indexOf('index.php') !== -1) {
            path = path.replace('index.php', 'login.php');
        }
        var qrUrl = window.location.origin + path + '?qr=' + encodeURIComponent(userQr || '');
        // Fetch QR color from backend
        var ajaxData = {};
        if (userId) ajaxData.user_id = userId;
        if (qrId) ajaxData.qr_id = qrId;
        $.ajax({
            url: '../backend/get_qr_color.php',
            type: 'POST',
            data: ajaxData,
            dataType: 'json',
            success: function(res) {
                var colorDark = res.colorDark || '#000000';
                var colorLight = res.colorLight || '#ffffff';
                profileFunction.generateQRCode(qrUrl, 'click_banner_img', colorDark, colorLight);
            },
            error: function() {
                // fallback to default colors
                profileFunction.generateQRCode(qrUrl, 'click_banner_img', '#000000', '#ffffff');
            }
        });
    },
    getProfileData: function(qrId = "") {
        // Prefill all profile fields from backend, supports both user_id and qrId
        let data = {};
        if (qrId) {
            data = { qr: qrId };
        } else {
            const userId = $('#user_id').val();
            if (!userId) return;
            data = { user_id: userId };
        }

        $.ajax({
            url: '../backend/get_profile_data.php',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(res) {
                if (res.user) {
                    var d = res.user;
                    $('#full_name').val(d.user_full_name || '');
                    $('#phone_number').val(d.user_phone || '');
                    $('#email_address').val(d.user_email || '');
                    $('#address').val(d.user_address || '');
                }
                if (res.links) {
                    Object.keys(res.links).forEach(function(key) {
                        var linkObj = res.links[key];
                        if (typeof linkObj === 'object' && linkObj !== null) {
                            if (key === 'website') {
                                $('#website').val(linkObj.value || '');
                                $('#public_website').prop('checked', linkObj.is_public == 1);
                            } else if (key === 'whatsapp_link') {
                                $('#whatsapp_link').val(linkObj.value || '');
                                $('#public_whatsapp_link').prop('checked', linkObj.is_public == 1);
                            } else if (key === 'telegram_link') {
                                $('#telegram_link').val(linkObj.value || '');
                                $('#public_telegram_link').prop('checked', linkObj.is_public == 1);
                            } else {
                                $('#' + key + '_username').val(linkObj.value || '');
                                $('#public_' + key + '_username').prop('checked', linkObj.is_public == 1);
                            }
                        }
                    });
                }
                if (window.PUBLIC_PROFILE) {
                    profileFunction.setAllFieldsReadOnly();
                    profileFunction.showOnlyPublicFields(); 
                }

            }
        });

    },
    setAllFieldsReadOnly:function(){
        $('input, select').each(function() {
            var $el = $(this);
            var type = $el.attr('type');
            if (type === 'file' || type === 'hidden' || type === 'button' || type === 'submit') {
                $el.prop('disabled', true);
            } else {
                $el.prop('readonly', true);
            }
        });
        // Hide all public-toggle checkboxes and their labels
        $('.public-toggle').each(function() {
            $(this).hide();
            var label = $("label[for='" + $(this).attr('id') + "']");
            label.hide();
        });
        // Hide the submit button in public profile mode
        $('#form-create-item input[type="submit"], #form-create-item button[type="submit"]').hide();
    },
    checkForProfileImage: function(userId) {
        // Check for existing profile image
        var userId = $('#user_id').val();
        if (userId) {
            $.ajax({
                url: '../backend/get_profile_image.php',
                type: 'POST',
                data: { user_id: userId },
                dataType: 'json',
                success: function(res) {
                    if (res.status && res.src) {
                        $('#click_profile_img').attr('src', res.src);
                    }
                }
            });
        }
    },
    generateQRCode: function(data, elementId, colorDark, colorLight) {
        colorDark = colorDark || '#000000';
        colorLight = colorLight || '#ffffff';
        var el = document.getElementById(elementId);
        // If the element is an <img>, set its src to the QR code data URL
        if (el && el.tagName.toLowerCase() === 'img') {
            // Create a temporary div to generate the QR code
            var tempDiv = document.createElement('div');
            new QRCode(tempDiv, {
                text: data,
                width: 192,
                height: 192,
                colorDark : colorDark,
                colorLight : colorLight,
                correctLevel : QRCode.CorrectLevel.H
            });
            // Wait for QR code to render, then set img src
            setTimeout(function() {
                var qrImg = tempDiv.querySelector('img');
                if (qrImg) {
                    el.src = qrImg.src;
                }
            }, 100);
        } else {
            // Otherwise, treat as a container and render QR code inside
            $('#' + elementId).empty();
            new QRCode(el, {
                text: data,
                width: 192,
                height: 192,
                colorDark : colorDark,
                colorLight : colorLight,
                correctLevel : QRCode.CorrectLevel.H
            });
        }
    }
}


// Set PUBLIC_PROFILE based on URL
window.PUBLIC_PROFILE = window.location.href.indexOf('?qr') !== -1;

$(document).ready(function() {
    // Initial state    
    eventHandler.init();
    profileFunction.init();
});