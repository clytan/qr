// Profile image upload handler
var eventHandler={
    init: function() {
        eventHandler.onChangeEvent();
        eventHandler.onSubmitEvent();
    },
    onChangeEvent: function() {
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
            // Socials
            ['twitter','instagram','youtube','linkedin','snapchat'].forEach(function(type) {
                links[type] = {
                    value: $('#' + type + '_username').val(),
                    is_public: $('#public_' + type + '_username').is(':checked') ? 1 : 0
                };
            });
            formData.links = links;
            // Also send public/private status for each field
            var publicFields = {};
            $('.public-toggle').each(function() {
                var target = $(this).data('target');
                publicFields[target] = $(this).is(':checked') ? 1 : 0;
            });
            formData.public_fields = publicFields;
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
            profileFunction.setAllFieldsReadOnly();
            profileFunction.showOnlyPublicFields();   
        }
        else{
            profileFunction.getProfileData();
        }
        profileFunction.checkForProfileImage();
        profileFunction.initQRCode();

    },
    showOnlyPublicFields:function(){
        $('.public-toggle').each(function() {
            var target = $(this).data('target');
            var isPublic = $(this).is(':checked');
            var $input = $('#' + target);
            var $group = $input.closest('.input-group.mb-2');
            if (isPublic) {
                $group.show();
            } else {
                $group.hide();
            }
        });
    },
    initQRCode:function(){
        // Generate QR code with current location and user_qr from hidden input
        var userQr = $('#user_qr').val();
        var path = window.location.pathname;
        if (path.indexOf('index.php') !== -1) {
            path = path.replace('index.php', 'login.php');
        }
        var qrUrl = window.location.origin + path + '?qr=' + encodeURIComponent(userQr || '');
        profileFunction.generateQRCode(qrUrl, 'click_banner_img');
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
                    // res.links is now a dict: {instagram: {value, is_public}, ...}
                    Object.keys(res.links).forEach(function(key) {
                        var linkObj = res.links[key];
                        if (typeof linkObj === 'object' && linkObj !== null) {
                            // Set value
                            if (key === 'website') {
                                $('#website').val(linkObj.value || '');
                                $('#public_website').prop('checked', linkObj.is_public == 1);
                            } else {
                                $('#' + key + '_username').val(linkObj.value || '');
                                $('#public_' + key + '_username').prop('checked', linkObj.is_public == 1);
                            }
                        }
                    });
                }
            }
        });

    },
    setAllFieldsReadOnly:function(){
        // Change the profile title to 'Public Profile'
        var $title = $('#profile-title');
        if ($title.length) {
            $title.text('Public Profile');
        }
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
    generateQRCode: function(data, elementId) {
        var el = document.getElementById(elementId);
        // If the element is an <img>, set its src to the QR code data URL
        if (el && el.tagName.toLowerCase() === 'img') {
            // Create a temporary div to generate the QR code
            var tempDiv = document.createElement('div');
            new QRCode(tempDiv, {
                text: data,
                width: 128,
                height: 128,
                colorDark : "#000000",
                colorLight : "#ffffff",
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
                width: 128,
                height: 128,
                colorDark : "#000000",
                colorLight : "#ffffff",
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