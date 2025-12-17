// Enhanced JavaScript with college name functionality
var appliedPromoCode = null;
var promoDiscount = 0;
var originalAmount = 0;

var eventHandler = {
    init: () => {
        eventHandler.onChange();
        eventHandler.onInput();
        eventHandler.submitEvent();
        eventHandler.emailInputEvents();
        eventHandler.emailVerifyEvents();
        eventHandler.referenceEvents();
        eventHandler.membershipTierEvents();
        eventHandler.studentLeaderEvents();
        eventHandler.promoCodeEvents();
    },

    promoCodeEvents: () => {
        let promoTimeout = null;

        $('#promo_code').on('input', function () {
            const promoCode = $(this).val().trim().toUpperCase();
            const statusEl = $('#promo-status');

            // Clear previous timeout
            clearTimeout(promoTimeout);

            // Reset if empty
            if (!promoCode) {
                if (appliedPromoCode) {
                    appliedPromoCode = null;
                    promoDiscount = 0;
                    originalAmount = 0;
                    $('#promo_code').prop('readonly', false);
                    statusEl.html('');
                    eventHandler.updatePayAmount();
                }
                return;
            }

            // Show checking status
            statusEl.html('<span style="color: #94a3b8;">⏳ Checking...</span>');

            // Debounce the API call
            promoTimeout = setTimeout(function () {
                // IMPORTANT: Always calculate original tier amount from tier selection,
                // not from the displayed #pay-amount (which could already be discounted)
                let checkedTier = $('input[name="user_tag"]:checked').val();
                // TESTING: All prices set to 1 (Original: Normal=999, Silver=5555, Gold=9999)
                let amount = 1; // Default
                if (checkedTier === 'gold') amount = 1; // Original: 9999
                else if (checkedTier === 'silver') amount = 1; // Original: 5555
                if ($('#student_leader').val() === 'yes') amount = 1; // Original: 999

                $.ajax({
                    url: '../backend/payment/validate_promo.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        code: promoCode,
                        amount: amount
                    }),
                    success: function (response) {
                        if (response.success) {
                            appliedPromoCode = response.promo_code;
                            promoDiscount = response.discount_amount;
                            originalAmount = response.original_amount;

                            $('#pay-amount').text(response.final_amount.toFixed(0));

                            let discountText = response.discount_type === 'percentage'
                                ? response.discount_value + '% off'
                                : '₹' + response.discount_value + ' off';

                            statusEl.html(`<span style="color: #10b981;">✓ ${discountText} applied! You save ₹${promoDiscount.toFixed(2)}</span>`);
                        } else {
                            appliedPromoCode = null;
                            promoDiscount = 0;
                            originalAmount = 0;
                            statusEl.html(`<span style="color: #ef4444;">✗ ${response.message}</span>`);
                            eventHandler.updatePayAmount();
                        }
                    },
                    error: function () {
                        appliedPromoCode = null;
                        promoDiscount = 0;
                        originalAmount = 0;
                        statusEl.html('<span style="color: #ef4444;">✗ Error validating promo code</span>');
                        eventHandler.updatePayAmount();
                    }
                });
            }, 800); // Wait 800ms after user stops typing
        });
    },

    updatePayAmount: () => {
        let checkedTier = $('input[name="user_tag"]:checked').val();
        // TESTING: All prices set to 1 (Original: Normal=999, Silver=5555, Gold=9999)
        let amount = 1;
        if (checkedTier === 'gold') amount = 1; // Original: 9999
        else if (checkedTier === 'silver') amount = 1; // Original: 5555
        if ($('#student_leader').val() === 'yes') amount = 1; // Original: 999

        // Reset promo if amount changes
        if (appliedPromoCode && originalAmount !== amount) {
            appliedPromoCode = null;
            promoDiscount = 0;
            originalAmount = 0;
            $('#promo_code').val('').prop('readonly', false);
            $('#apply-promo-btn').prop('disabled', false).text('Apply').css('background', '');
            $('#promo-status').html('');
        }

        $('#pay-amount').text(amount);
    },

    studentLeaderEvents: () => {
        $('#student_leader').on('change', function () {
            if ($(this).val() === 'yes') {
                $('#college_field').addClass('show');
                $('#college_name').prop('required', true);
                $('#membership_tier_section').hide();
                eventHandler.updatePayAmount();
            } else {
                $('#college_field').removeClass('show');
                $('#college_name').prop('required', false);
                $('#college_name').val('');
                $('#membership_tier_section').show();
                eventHandler.updatePayAmount();
            }
            registerFunction.updateSubmitState();
        });
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
            error: function (xhr, status, error) {
                console.error('AJAX ERROR loading slabs:', status, error, xhr.responseText);
            }
        });

        // Make Gold/Silver toggleable
        $('input[name="user_tag"]').on('click', function () {
            if ($(this).prop('checked')) {
                // If already checked, uncheck
                if ($(this).data('waschecked')) {
                    $(this).prop('checked', false);
                    $(this).data('waschecked', false);
                } else {
                    $(this).data('waschecked', true);
                }
            } else {
                $(this).data('waschecked', false);
            }
            // Update price - TESTING: All prices set to 1
            let checked = $('input[name="user_tag"]:checked').val();
            let amount = 1; // Original: 999
            if (checked === 'gold') amount = 1; // Original: 9999
            else if (checked === 'silver') amount = 1; // Original: 5555
            if ($('#student_leader').val() === 'yes') amount = 1; // Original: 999
            $('#pay-amount').text(amount);
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
        // Reference code is always visible, validate for 10 alphanumeric characters if filled
        $('#reference_code').on('input', function () {
            var code = $(this).val().trim();
            if (code === '') {
                $('#reference-status').text('');
                registerFunction.setReferenceValid(true);
                registerFunction.updateSubmitState();
                return;
            }
            if (!/^[A-Za-z0-9]{10}$/.test(code)) {
                $('#reference-status').text('Reference code must be 10 characters (letters and numbers)').css('color', 'red');
                registerFunction.setReferenceValid(false);
                registerFunction.referredByUserId = null;
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
                $('#verify-email-section').css('display', 'flex');
                // Only reset verification if the email actually changed from the verified one
                if (email !== registerFunction.lastVerifiedEmail) {
                    $('#email-verified-status').text('');
                    $('#verify-email-link').show();
                    registerFunction.setEmailVerified(false);
                }
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

        // Always update price on load and on membership/student leader change
        eventHandler.updatePayAmount();
        $('input[name="user_tag"], #student_leader').on('change click', eventHandler.updatePayAmount);
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
                user_type: finalUserType,
                user_slab: $('#user_slab').val(),
                user_tag: finalUserTag, // Send tier for backend validation
                student_leader: $('#student_leader').val(), // Send student leader status
                college_name: $('#college_name').val().trim(),
                reference_code: $('#reference_code').val().trim(),
                referred_by_user_id: registerFunction.referredByUserId || null,
                promo_code: appliedPromoCode || null
            };
            // Calculate amount based on user type and membership tier
            // ALWAYS send the original tier amount - backend will validate and use hardcoded prices
            const amount = registerFunction.calculateAmount(finalUserType, finalUserTag);
            formData.amount = amount; // Backend will verify this matches the tier
            console.log('Form submission - User Type:', finalUserType, 'Membership Tier:', finalUserTag, 'Amount:', formData.amount);
            if (appliedPromoCode) {
                console.log('Promo code applied:', appliedPromoCode, 'Discount:', promoDiscount);
            }
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
                            console.log('Order.php response:', response);
                            if (response.status && response.session) {
                                console.log('✓ Payment order created successfully');
                                console.log('  Session:', response.session.substring(0, 50) + '...');
                                console.log('  Order ID:', response.order_id);

                                // IMPORTANT: Redirect IMMEDIATELY, don't wait
                                const session = encodeURIComponent(response.session);
                                const orderId = encodeURIComponent(response.order_id);
                                const timestamp = Date.now();
                                const redirectUrl = '../backend/payment/intent.php?session=' + session + '&orderId=' + orderId + '&t=' + timestamp;

                                console.log('Redirecting to:', redirectUrl);
                                window.location.href = redirectUrl;

                                // Prevent any other code from executing
                                return false;
                            } else {
                                console.error('❌ Payment order failed:', response);
                                alert(response.error || 'Failed to initiate payment.');
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('❌ AJAX error:', status, error);
                            console.error('Response text:', xhr.responseText);
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
    lastVerifiedEmail: '', // Track which email was verified to avoid resetting unnecessarily
    referenceValid: true,
    referredByUserId: null,
    referenceTimeout: null,

    calculateAmount: function (userType, userTag) {
        // TESTING: All prices set to 1
        // Original prices: Normal=999, Silver=5555, Gold=9999
        let amount = 1; // Default Normal tier (Original: 999)

        // Adjust amount based on membership tier
        if (userTag === 'gold') {
            amount = 1; // Original: 9999
        } else if (userTag === 'silver') {
            amount = 1; // Original: 5555
        } else {
            amount = 1; // Original: 999
        }

        return amount;
    },

    setEmailVerified: function (val, email) {
        registerFunction.emailVerified = val;
        if (val) {
            registerFunction.lastVerifiedEmail = email || $('#email').val().trim();
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
                    // Pass the email that was verified
                    registerFunction.setEmailVerified(true, $('#email').val().trim());
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
                    registerFunction.setEmailVerified(true, email);
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
        var userType = registerFunction.getSelectedUserType()[0];
        var userSlab = $('#user_slab').val();
        // College field logic
        var collegeRequired = $('#college_name').prop('required');
        var collegeName = $.trim($('#college_name').val());
        var collegeValid = !collegeRequired || (collegeRequired && collegeName !== '');
        // Validate name pattern
        var namePattern = /^[A-Z][a-z]+(\s[A-Z][a-z]*)+$/;
        var nameValid = namePattern.test(fullName);
        // Validate phone pattern
        var phonePattern = /^[0-9]{10}$/;
        var phoneValid = phonePattern.test(phone);
        // Reference code validation
        var referenceCode = $.trim($('#reference_code').val());
        var referenceValid = referenceCode === '' || (/^[A-Za-z0-9]{10}$/.test(referenceCode) && registerFunction.referenceValid);
        // Policy checkbox
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
            registerFunction.emailVerified &&
            userType !== null &&
            userSlab !== '' &&
            collegeValid &&
            referenceValid &&
            termsChecked
        );
        return isValid;
    },

    updateSubmitState: () => {
        const $submit = $('#register_user_form');
        const isComplete = registerFunction.isFormComplete();
        // Also require terms checkbox to be checked
        const termsChecked = $('#terms-checkbox').is(':checked');
        $submit.prop('disabled', !(isComplete && termsChecked));
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

    // Check if email already has value (e.g., returning from payment gateway or browser back)
    var email = $('#email').val().trim();
    if (email !== '') {
        // Show the verify email section
        $('#verify-email-section').css('display', 'flex');

        // Check if this email was previously verified
        registerFunction.checkEmailVerified(email);
    }

    // Also re-check on page visibility change (user returning from payment app)
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            var currentEmail = $('#email').val().trim();
            if (currentEmail !== '' && !registerFunction.emailVerified) {
                registerFunction.checkEmailVerified(currentEmail);
            }
        }
    });
});
