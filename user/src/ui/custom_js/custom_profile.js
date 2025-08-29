// Profile image upload handler
var eventHandler={
    init: function() {
        eventHandler.onChangeEvent();
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
    }
}


var profileFunction={ 
    init: function(){
        profileFunction.checkForProfileImage();
        profileFunction.generateQRCode('https://google.com ','click_banner_img')
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
                colorDark : "#5c1f1fff",
                colorLight : "#ae3a3aff",
                correctLevel : QRCode.CorrectLevel.H
            });
        }
    }
}


$(document).ready(function() {
    
    // Initial state
    eventHandler.init();
    profileFunction.init();
});