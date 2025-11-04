
// Enhanced JavaScript with college name functionality
var eventHandler = {
    init: () => {
        eventHandler.onChange();
        eventHandler.onInput();
        eventHandler.submitEvent();
        eventHandler.emailInputEvents();
        eventHandler.emailVerifyEvents();
        eventHandler.referenceEvents();
        eventHandler.loadUserSlabs();
        eventHandler.slabSelectionEvents();
        eventHandler.membershipTierEvents();
    },

    // Handle membership tier selection
    membershipTierEvents: () => {
        // Load slabs and store mapping
        $.ajax({
            url: '../backend/get_user_slabs.php',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                console.log('Get user slabs response:', response);
                if (response.status && response.data) {
                    // Store slab mapping
                    window.slabMapping = {};
                    response.data.forEach(function (slab) {
                        const slabName = slab.name.toLowerCase();
                        console.log('Processing slab:', slabName, 'id:', slab.id);
                        if (slabName.includes('normal') || slabName.includes('bronze') || slabName.includes('individual') || slabName.includes('default')) {
                            window.slabMapping['normal'] = slab.id;
                        } else if (slabName.includes('silver')) {
                            window.slabMapping['silver'] = slab.id;
                        } else if (slabName.includes('gold')) {
                            window.slabMapping['gold'] = slab.id;
                        }
                    });
                    console.log('Slab mapping:', window.slabMapping);
                    
                    // Set default "Normal" tier slab ID on page load
                    if (window.slabMapping['normal']) {
                        $('#user_slab').val(window.slabMapping['normal']);
                        console.log('Default Normal tier slab ID set:', window.slabMapping['normal']);
                        
                        // Trigger the change event for the pre-checked Normal tier
                        const checkedTier = $('input[name="user_tag"]:checked');
                        if (checkedTier.length > 0) {
                            checkedTier.trigger('change');
                        } else {
                            registerFunction.updateSubmitState();
                        }
                    } else {
                        console.error('ERROR: No normal tier found in slabs!');
                    }
                } else {
                    console.error('ERROR: Failed to load slabs:', response.message || 'Unknown error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX ERROR loading slabs:', status, error, xhr.responseText);
            }
        });

        // Handle tier selection (user_tag)
        $('input[name="user_tag"]').on('change', function() {
            const tier = $(this).val() || 'normal'; // Empty value = normal
            const slabId = window.slabMapping[tier];
            
            if (slabId) {
                $('#user_slab').val(slabId);
                console.log('Selected tier:', tier, 'Slab ID:', slabId);
            }
            
            registerFunction.updateSubmitText();
            registerFunction.updateSubmitState();
        });
    },

    slabSelectionEvents: () => {
        $('#user_slab').on('change', function () {
            var selectedText = $(this).find('option:selected').text().toLowerCase();
            var collegeField = $('#college_field');
            var collegeInput = $('#college_name');

            if (selectedText.includes('student leader')) {
                collegeField.addClass('show');
                collegeInput.prop('required', true);
            } else {
                collegeField.removeClass('show');
                collegeInput.prop('required', false);
                collegeInput.val(''); // Clear the field when hidden
            }

            registerFunction.updateSubmitState();
        });
    },

    loadUserSlabs: () => {
        $.ajax({
            url: '../backend/get_user_slabs.php',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.status && response.data) {
                    var options = '<option value="">Select a slab...</option>';
                    response.data.forEach(function (slab) {
                        options += '<option value="' + slab.id + '">' + slab.name +
                            '</option>';
                    });
                    $('#user_slab').html(options);
                } else {
                    console.error('Failed to load user slabs');
                }
            },
            error: function (xhr, status, error) {
                console.error('Error loading user slabs:', error);
            }
        });
    },

    referenceEvents: () => {
        $('#has_reference').on('change', function () {
            if ($(this).is(':checked')) {
                $('#reference_section').show();
                $('#reference_code').focus();
            } else {
                $('#reference_section').hide();
                $('#reference_code').val('');
                $('#reference-status').text('');
                registerFunction.setReferenceValid(true);
            }
            registerFunction.updateSubmitState();
        });

        $('#reference_code').on('input', function () {
            var code = $(this).val().trim();
            if (code === '') {
                $('#reference-status').text('');
                registerFunction.setReferenceValid(true);
                registerFunction.updateSubmitState();
                return;
            }

            clearTimeout(registerFunction.referenceTimeout);
            registerFunction.referenceTimeout = setTimeout(function () {
                registerFunction.validateReference(code);
            }, 500);
        });
    },

    emailInputEvents: () => {
        $('#email').on('input', function () {
            var email = $(this).val().trim();
            if (email !== '') {
                $('#verify-email-section').show();
                $('#email-verified-status').text('');
                $('#verify-email-link').show();
                registerFunction.setEmailVerified(false);
            } else {
                $('#verify-email-section').hide();
                registerFunction.setEmailVerified(false);
            }
            registerFunction.updateSubmitState();
        });
    },

    emailVerifyEvents: () => {
        $(document).on('click', '#verify-email-link', function (e) {
            e.preventDefault();
            var email = $('#email').val().trim();
            if (!registerFunction.validateEmail(email)) {
                $('#email-verified-status').text('Invalid email').css('color', 'red');
                return;
            }
            $('#otp-email-display').text(email);
            $('#email-otp-input').val('');
            $('#otp-status-msg').text('');
            $('#email-otp-modal').css('display', 'flex');

            $('#email-otp-modal').removeAttr('data-otp-id');

            if (typeof registerFunction !== 'undefined' && typeof registerFunction.sendOtp ===
                'function') {
                registerFunction.sendOtp(email);
            } else {
                console.error('registerFunction or sendOtp not available!');
                $('#otp-status-msg').css('color', 'red').text(
                    'Error: OTP function not available. Please refresh the page.');
            }
        });

        $('#close-otp-modal').on('click', function () {
            $('#email-otp-modal').hide();
        });

        $('#submit-otp-btn').on('click', function () {
            var email = $('#email').val().trim();
            var otp = $('#email-otp-input').val().trim();

            if (otp.length !== 6 || !/^[0-9]{6}$/.test(otp)) {
                $('#otp-status-msg').css('color', 'red').text('Please enter a valid 6-digit OTP.');
                return;
            }

            var otp_id = $('#email-otp-modal').attr('data-otp-id') || $('#email-otp-modal').data(
                'otp-id') || window.currentOtpId;

            if (otp_id) {
                registerFunction.verifyOtp(otp_id, otp);
            } else {
                $('#otp-status-msg').css('color', 'red').text(
                    'OTP session expired. Please click "Resend OTP" to get a new code.');
            }
        });

        $('#resend-otp-btn').on('click', function () {
            var email = $('#email').val().trim();
            registerFunction.sendOtp(email, true);
        });
    },

    onChange: () => {
        $('#email').on('input', function () {
            var email = $(this).val().trim();
            if (email !== '') {
                registerFunction.checkEmailVerified(email);
            }
        });

        $('input[name="user_type"]').on('change', function () {
            registerFunction.updateSubmitText();
            registerFunction.updateSubmitState();
        });

        $('#user_slab').on('change', function () {
            registerFunction.updateSubmitState();
        });

        // Policy checkbox event listener
        $('#terms-checkbox').on('change', function () {
            registerFunction.updateSubmitState();
        });
    },

    onInput: () => {
        $('#full_name').on('input', function () {
            registerFunction.validateFullName();
            registerFunction.updateSubmitState();
        });

        $('#phone').on('input', function () {
            registerFunction.validatePhone();
            registerFunction.updateSubmitState();
        });

        $('#pincode').on('input', function () {
            registerFunction.validatePincode();
            registerFunction.updateSubmitState();
        });

        $('#email').on('input', function () {
            registerFunction.updateSubmitState();
        });

        $('#confirmpassword').on('input', function () {
            registerFunction.checkPasswordMatch();
            registerFunction.updateSubmitState();
        });

        $('#password').on('input', function () {
            registerFunction.checkPasswordMatch();
            registerFunction.updateSubmitState();
        });

        $('#address').on('input', function () {
            registerFunction.updateSubmitState();
        });

        $('#landmark').on('input', function () {
            registerFunction.updateSubmitState();
        });

        // Add college name input validation
        $('#college_name').on('input', function () {
            registerFunction.updateSubmitState();
        });
    },

    submitEvent: () => {
        $('#register_form').on('submit', function (e) {
            e.preventDefault();

            if (!registerFunction.isFormComplete()) {
                alert('Please complete all required fields and verify your email.');
                return;
            }

            if ($('#password').val().trim() !== $('#confirmpassword').val().trim()) {
                alert('Passwords do not match.');
                return;
            }

            var selectedUserType = registerFunction.getSelectedUserType();
            var userTypeValue = selectedUserType[0];
            
            // Get the selected membership tier
            var userTagValue = $('input[name="user_tag"]:checked').val() || 'normal';

            var finalUserType = userTypeValue;
            var finalUserTag = userTagValue;

            if (userTypeValue === '4' || userTypeValue === '5') {
                finalUserType = '1';
            }

            var formData = {
                full_name: $('#full_name').val().trim(),
                email: $('#email').val().trim(),
                phone: $('#phone').val().trim(),
                password: $('#password').val().trim(),
                address: $('#address').val().trim(),
                pincode: $('#pincode').val().trim(),
                landmark: $('#landmark').val().trim(),
                user_type: finalUserType,
                user_tag: finalUserTag,
                user_slab: $('#user_slab').val(),
                college_name: $('#college_name').val().trim(),
                reference_code: $('#has_reference').is(':checked') ? $('#reference_code').val()
                    .trim() : '',
                referred_by_user_id: registerFunction.referredByUserId || null
            };

            // Calculate amount based on user type and membership tier
            const amount = registerFunction.calculateAmount(finalUserType, finalUserTag);
            formData.amount = amount;
            
            console.log('Form submission - User Type:', finalUserType, 'Membership Tier:', finalUserTag, 'Amount:', amount);

            // First check if user already exists
            $.ajax({
                url: '../backend/check_user_exists.php',
                type: 'POST',
                data: JSON.stringify({ email: formData.email }),
                contentType: 'application/json',
                dataType: 'json',
                success: function (checkResponse) {
                    if (checkResponse.exists) {
                        alert('This email is already registered. Please login instead.');
                        window.location.href = 'login.php';
                        return;
                    }
                    
                    // If user doesn't exist, proceed to create payment order
                    $.ajax({
                        url: '../backend/payment/order.php',
                        type: 'POST',
                        data: JSON.stringify(formData),
                        contentType: 'application/json',
                        dataType: 'json',
                        success: function (response) {
                            if (response.status && response.session) {
                                // Redirect to payment intent page
                                window.location.href = '../backend/payment/intent.php?session=' + response.session;
                            } else {
                                alert(response.error || 'Failed to initiate payment.');
                            }
                        },
                        error: function (xhr, status, error) {
                            alert('An error occurred while initiating payment: ' + error);
                        }
                    });
                },
                error: function (xhr, status, error) {
                    alert('An error occurred while checking user: ' + error);
                }
            });
        });
    }
};

var registerFunction = {
    emailVerified: false,
    referenceValid: true,
    referredByUserId: null,
    referenceTimeout: null,

    calculateAmount: function (userType, userTag) {
        // Registration amount based on membership tier
        let amount = 999; // Default Normal tier

        // Adjust amount based on membership tier
        if (userTag === 'gold') {
            amount = 9999;
        } else if (userTag === 'silver') {
            amount = 5555;
        } else {
            amount = 999; // Normal tier
        }

        return amount;
    },

    setEmailVerified: function (val) {
        registerFunction.emailVerified = val;
        if (val) {
            $('#email-verified-status').text('Verified').css('color', 'green');
            $('#verify-email-link').hide();
        } else {
            $('#email-verified-status').text('');
            $('#verify-email-link').show();
        }
        registerFunction.updateSubmitState();
    },

    setReferenceValid: function (val) {
        registerFunction.referenceValid = val;
        registerFunction.updateSubmitState();
    },

    validateReference: function (code) {
        $('#reference-status').text('Validating...').css('color', 'orange');

        $.ajax({
            url: '../backend/validate_reference.php',
            type: 'POST',
            data: {
                user_qr_id: code
            },
            dataType: 'json',
            success: function (response) {
                if (response.status) {
                    $('#reference-status').text('Valid reference').css('color', 'green');
                    registerFunction.referredByUserId = response.data.user_qr_id;
                    registerFunction.setReferenceValid(true);
                } else {
                    $('#reference-status').text(response.message || 'Invalid reference').css(
                        'color', 'red');
                    registerFunction.referredByUserId = null;
                    registerFunction.setReferenceValid(false);
                }
            },
            error: function () {
                $('#reference-status').text('Error validating reference').css('color', 'red');
                registerFunction.referredByUserId = null;
                registerFunction.setReferenceValid(false);
            }
        });
    },

    validateEmail: function (email) {
        return /^\S+@\S+\.\S+$/.test(email);
    },

    validateFullName: function () {
        const name = $('#full_name').val().trim();
        const namePattern = /^[A-Z][a-z]+(\s[A-Z][a-z]*)+$/;
        
        if (name === '') {
            $('#name-validation-status').text('').css('color', '');
            return false;
        }
        
        if (!namePattern.test(name)) {
            $('#name-validation-status').text('Name must start with capital letter, followed by space and another capital letter (e.g., John D Smith)').css('color', 'red');
            return false;
        }
        
        $('#name-validation-status').text('✓ Valid name format').css('color', 'green');
        return true;
    },

    validatePhone: function () {
        const phone = $('#phone').val().trim();
        const phonePattern = /^[0-9]{10}$/;
        
        if (phone === '') {
            $('#phone-validation-status').text('').css('color', '');
            return false;
        }
        
        if (!phonePattern.test(phone)) {
            $('#phone-validation-status').text('Phone must be exactly 10 digits').css('color', 'red');
            return false;
        }
        
        $('#phone-validation-status').text('✓ Valid phone number').css('color', 'green');
        return true;
    },

    validatePincode: function () {
        const pincode = $('#pincode').val().trim();
        const pincodePattern = /^[0-9]{6}$/;
        
        if (pincode === '' || pincodePattern.test(pincode)) {
            return true;
        }
        
        return false;
    },

    sendOtp: function (email, resend = false) {
        $('#otp-status-msg').css('color', 'black').text(resend ? 'Resending OTP...' : 'Sending OTP...');

        $('#email-otp-modal').removeAttr('data-otp-id');
        $('#email-otp-modal').removeData('otp-id');
        window.currentOtpId = null;

        $.ajax({
            url: '../backend/send_otp_production.php',
            type: 'POST',
            data: {
                email: email,
                resend: resend ? 1 : 0
            },
            dataType: 'json',
            timeout: 15000,
            success: function (response) {
                if (response.status) {
                    var otp_id = response.data?.id || response.otp_id || null;

                    if (otp_id) {
                        $('#email-otp-modal').attr('data-otp-id', String(otp_id));
                        $('#email-otp-modal').data('otp-id', String(otp_id));
                        window.currentOtpId = String(otp_id);

                        $('#otp-status-msg').css('color', 'green').text(
                            'OTP sent to your email. Please check your inbox.');
                    } else {
                        $('#otp-status-msg').css('color', 'red').text(
                            'Invalid OTP response. Please try again.');
                    }
                } else {
                    $('#otp-status-msg').css('color', 'red').text(response.message ||
                        'Failed to send OTP.');
                }
            },
            error: function (xhr, status, error) {
                if (xhr.status === 0) {
                    $('#otp-status-msg').css('color', 'red').text(
                        'Network error. Please check your connection.');
                } else if (xhr.status === 404) {
                    $('#otp-status-msg').css('color', 'red').text(
                        'Server file not found. Please contact support.');
                } else if (xhr.status === 500) {
                    $('#otp-status-msg').css('color', 'red').text(
                        'Server error. Please try again.');
                } else {
                    $('#otp-status-msg').css('color', 'red').text(
                        'Error sending OTP. Please try again.');
                }
            }
        });
    },

    verifyOtp: function (otp_id, otp) {
        $('#otp-status-msg').css('color', 'black').text('Verifying...');

        $.ajax({
            url: '../backend/verify_email_otp.php',
            type: 'POST',
            data: {
                otp_id: otp_id,
                otp: otp
            },
            dataType: 'json',
            success: function (response) {
                if (response.status) {
                    $('#otp-status-msg').css('color', 'green').text(
                        'Email verified successfully!');
                    setTimeout(function () {
                        $('#email-otp-modal').hide();
                    }, 1500);
                    registerFunction.setEmailVerified(true);
                } else {
                    $('#otp-status-msg').css('color', 'red').text(response.message ||
                        'Invalid OTP.');
                }
            },
            error: function (xhr, status, error) {
                $('#otp-status-msg').css('color', 'red').text(
                    'Error verifying OTP. Please try again.');
            }
        });
    },

    checkEmailVerified: function (email, cb) {
        $.ajax({
            url: '../backend/check_email_verified.php',
            type: 'POST',
            data: {
                email: email
            },
            dataType: 'json',
            success: function (response) {
                if (response.status && response.data.verified == 1) {
                    registerFunction.setEmailVerified(true);
                    if (cb) cb(true);
                } else {
                    registerFunction.setEmailVerified(false);
                    if (cb) cb(false);
                }
            },
            error: function () {
                registerFunction.setEmailVerified(false);
                if (cb) cb(false);
            }
        });
    },

    init: () => {
        registerFunction.updateSubmitText(true);
        registerFunction.updateSubmitState();
    },

    getSelectedUserType: () => {
        return [$('input[name="user_type"]').filter(':checked').val() || null, $('input[name="user_type"]')
            .filter(':checked').attr('data-tag') || ''
        ];
    },

    updateSubmitText: (init = false) => {
        const selectedUserType = $('input[name="user_type"]:checked');
        const userType = selectedUserType.val();
        
        // Get membership tier from the tier radio buttons
        const userTag = $('input[name="user_tag"]:checked').val() || 'normal';
        
        const amount = registerFunction.calculateAmount(userType, userTag);

        if (init) {
            $('#register_user_form').val('Pay');
        } else {
            $('#register_user_form').val('Pay ₹' + amount);
        }
    },

    isFormComplete: () => {
        var fullName = $.trim($('#full_name').val());
        var email = $.trim($('#email').val());
        var phone = $.trim($('#phone').val());
        var password = $.trim($('#password').val());
        var confirmPassword = $.trim($('#confirmpassword').val());
        var address = $.trim($('#address').val());
        var pincode = $.trim($('#pincode').val());
        var landmark = $.trim($('#landmark').val());
        var userType = registerFunction.getSelectedUserType()[0];
        var userSlab = $('#user_slab').val();
        
        console.log('Form validation check - userSlab value:', userSlab, 'emailVerified:', registerFunction.emailVerified);

        // Check if college field is required and filled
        var collegeRequired = $('#college_name').prop('required');
        var collegeName = $.trim($('#college_name').val());
        var collegeValid = !collegeRequired || (collegeRequired && collegeName !== '');

        // Validate name pattern
        var namePattern = /^[A-Z][a-z]+(\s[A-Z][a-z]*)+$/;
        var nameValid = namePattern.test(fullName);

        // Validate phone pattern
        var phonePattern = /^[0-9]{10}$/;
        var phoneValid = phonePattern.test(phone);

        // Validate pincode pattern
        var pincodePattern = /^[0-9]{6}$/;
        var pincodeValid = pincodePattern.test(pincode);

        // Check if policy checkbox is checked
        var termsChecked = $('#terms-checkbox').is(':checked');

        var isValid = (
            fullName !== '' &&
            nameValid &&
            email !== '' &&
            phone !== '' &&
            phoneValid &&
            password !== '' &&
            confirmPassword !== '' &&
            password === confirmPassword &&
            address !== '' &&
            pincode !== '' &&
            pincodeValid &&
            landmark !== '' &&
            registerFunction.emailVerified &&
            userType !== null &&
            userSlab !== '' &&
            collegeValid &&
            termsChecked
        );

        return isValid;
    },

    updateSubmitState: () => {
        const $submit = $('#register_user_form');
        const isComplete = registerFunction.isFormComplete();
        console.log('Update submit state - Form complete:', isComplete);
        $submit.prop('disabled', !isComplete);
    },

    checkPasswordMatch: function () {
        var password = $('#password').val().trim();
        var confirmPassword = $('#confirmpassword').val().trim();
        if (password !== '' && confirmPassword !== '') {
            if (password !== confirmPassword) {
                $('#password-match-status').text('Passwords do not match').css('color', 'red');
            } else {
                $('#password-match-status').text('');
            }
        } else {
            $('#password-match-status').text('');
        }
    }
};

$(document).ready(function () {
    eventHandler.init();
    registerFunction.init();

    var email = $('#email').val().trim();
    if (email !== '') {
        registerFunction.checkEmailVerified(email);
    }
});
