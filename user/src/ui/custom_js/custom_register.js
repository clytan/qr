var eventHandler = {
    init:()=>{
        eventHandler.onChange();
        eventHandler.onInput();
        eventHandler.submitEvent();
        eventHandler.emailInputEvents();
        eventHandler.emailVerifyEvents();
        eventHandler.referenceEvents();
        eventHandler.loadUserSlabs();
    },
    loadUserSlabs:()=>{
        $.ajax({
            url: '../backend/get_user_slabs.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status && response.data) {
                    var options = '<option value="">Select a slab...</option>';
                    response.data.forEach(function(slab) {
                        options += '<option value="' + slab.id + '">' + slab.name + '</option>';
                    });
                    $('#user_slab').html(options);
                } else {
                    console.error('Failed to load user slabs');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading user slabs:', error);
            }
        });
    },
    referenceEvents:()=>{
        $('#has_reference').on('change', function() {
            if ($(this).is(':checked')) {
                $('#reference_section').show();
                $('#reference_code').focus();
            } else {
                $('#reference_section').hide();
                $('#reference_code').val('');
                $('#reference-status').text('');
                registerFunction.setReferenceValid(true); // Reset validation when hidden
            }
            registerFunction.updateSubmitState();
        });
        
        $('#reference_code').on('input', function() {
            var code = $(this).val().trim();
            if (code === '') {
                $('#reference-status').text('');
                registerFunction.setReferenceValid(true);
                registerFunction.updateSubmitState();
                return;
            }
            
            // Debounce validation
            clearTimeout(registerFunction.referenceTimeout);
            registerFunction.referenceTimeout = setTimeout(function() {
                registerFunction.validateReference(code);
            }, 500);
        });
    },
    emailInputEvents:()=>{
        // Show verify link if email is not empty
        $('#email').on('input', function() {
            var email = $(this).val().trim();
            if(email !== '') {
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
    emailVerifyEvents:()=>{
        // Open modal and send OTP
        $(document).on('click', '#verify-email-link', function(e){
            e.preventDefault();
            var email = $('#email').val().trim();
            if(!registerFunction.validateEmail(email)){
                $('#email-verified-status').text('Invalid email').css('color','red');
                return;
            }
            $('#otp-email-display').text(email);
            $('#email-otp-input').val('');
            $('#otp-status-msg').text('');
            $('#email-otp-modal').css('display','flex');
            
            // Clear any existing OTP ID and start fresh
            $('#email-otp-modal').removeAttr('data-otp-id');
            console.log('Starting OTP process for email:', email);
            
            // Debug: Check if registerFunction exists
            console.log('registerFunction exists:', typeof registerFunction);
            console.log('sendOtp function exists:', typeof registerFunction.sendOtp);
            
            if (typeof registerFunction !== 'undefined' && typeof registerFunction.sendOtp === 'function') {
                console.log('Calling registerFunction.sendOtp...');
                registerFunction.sendOtp(email);
            } else {
                console.error('registerFunction or sendOtp not available!');
                $('#otp-status-msg').css('color','red').text('Error: OTP function not available. Please refresh the page.');
            }
        });
        // Close modal
        $('#close-otp-modal').on('click', function(){
            $('#email-otp-modal').hide();
        });
        // Submit OTP
        $('#submit-otp-btn').on('click', function(){
            var email = $('#email').val().trim();
            var otp = $('#email-otp-input').val().trim();
            
            // Validate OTP format
            if(otp.length !== 6 || !/^[0-9]{6}$/.test(otp)){
                $('#otp-status-msg').css('color','red').text('Please enter a valid 6-digit OTP.');
                return;
            }
            
            console.log('=== OTP VERIFICATION ATTEMPT ===');
            console.log('Email:', email);
            console.log('OTP entered:', otp);
            
            // Get OTP ID with multiple fallback methods
            var otp_id_attr = $('#email-otp-modal').attr('data-otp-id');
            var otp_id_data = $('#email-otp-modal').data('otp-id');
            var otp_id_window = window.currentOtpId;
            
            console.log('OTP ID from attr:', otp_id_attr, 'Type:', typeof otp_id_attr);
            console.log('OTP ID from data:', otp_id_data, 'Type:', typeof otp_id_data);
            console.log('OTP ID from window:', otp_id_window, 'Type:', typeof otp_id_window);
            console.log('Modal element exists:', $('#email-otp-modal').length > 0);
            console.log('Modal attributes:', $('#email-otp-modal')[0] ? $('#email-otp-modal')[0].attributes : 'no element');
            
            // Choose the first valid OTP ID
            var otp_id = otp_id_attr || otp_id_data || otp_id_window || '';
            
            console.log('Final selected OTP ID:', otp_id, 'Type:', typeof otp_id);
            console.log('OTP ID is valid?', otp_id !== '' && otp_id !== undefined && otp_id !== 'undefined' && otp_id !== null);
            console.log('================================');
            
            if(otp_id !== '' && otp_id !== undefined && otp_id !== 'undefined' && otp_id !== null){
                console.log('Proceeding with OTP verification for ID:', otp_id);
                registerFunction.verifyOtp(otp_id, otp);
            }
            else{
                console.log('OTP ID is missing, expired, or invalid. All methods returned:', {
                    attr: otp_id_attr,
                    data: otp_id_data,
                    window: otp_id_window
                });
                $('#otp-status-msg').css('color','red').text('OTP session expired. Please click "Resend OTP" to get a new code.');
            }
        });
        // Resend OTP
        $('#resend-otp-btn').on('click', function(){
            var email = $('#email').val().trim();
            registerFunction.sendOtp(email, true);
        });
    },
    onChange:()=>{
        // Use input event instead of change for better UX and reliability
        $('#email').on('input', function() {
            var email = $(this).val().trim();
            if(email !== ''){
                registerFunction.checkEmailVerified(email);
            }
        });
        $('input[name="user_type"]').on('change', function() {
            registerFunction.updateSubmitText();
            registerFunction.updateSubmitState();
        });
        
        $('#user_slab').on('change', function() {
            registerFunction.updateSubmitState();
        });
    },
    onInput:()=>{
        $('#email').on('input', function() {
            registerFunction.updateSubmitState();
        });
        // Password match check
        $('#confirm_password').on('input', function() {
            registerFunction.checkPasswordMatch();
            registerFunction.updateSubmitState();
        });
        $('#password').on('input', function() {
            registerFunction.checkPasswordMatch();
            registerFunction.updateSubmitState();
        });
    },
    submitEvent:() =>{
        $('#register_form').on('submit', function(e) {
            e.preventDefault();
            
            // Final validation before submit
            if (!registerFunction.isFormComplete()) {
                alert('Please complete all required fields and verify your email.');
                return;
            }
            
            // Check password match
            if ($('#password').val().trim() !== $('#confirm_password').val().trim()) {
                alert('Passwords do not match.');
                return;
            }
            
            // Collect form data
            var formData = {
                email: $('#email').val().trim(),
                password: $('#password').val().trim(),
                user_type: registerFunction.getSelectedUserType()[0],
                user_slab: $('#user_slab').val(),
                reference_code: $('#has_reference').is(':checked') ? $('#reference_code').val().trim() : '',
                referred_by_user_id: registerFunction.referredByUserId || null
            };
            
            $.ajax({
                url: '../backend/register.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.status) {
                        // Registration successful, redirect to login
                        window.location.href = 'login.php';
                    } else {
                        alert(response.message || 'Registration failed.');
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred: ' + error);
                }
            });
        });
    }
};

var registerFunction = {
    // Add debug test function
    debugTest: function() {
        console.log('=== DEBUG TEST FUNCTION ===');
        console.log('jQuery version:', $.fn.jquery);
        console.log('Modal element exists:', $('#email-otp-modal').length);
        console.log('Modal HTML:', $('#email-otp-modal').length > 0 ? $('#email-otp-modal')[0].outerHTML.substring(0, 200) + '...' : 'No modal found');
        console.log('Window object has currentOtpId property:', 'currentOtpId' in window);
        console.log('Current window.currentOtpId value:', window.currentOtpId);
        
        // Test setting and getting attributes
        $('#email-otp-modal').attr('data-test', 'test123');
        var testResult = $('#email-otp-modal').attr('data-test');
        console.log('Test attribute set/get result:', testResult);
        $('#email-otp-modal').removeAttr('data-test');
        console.log('========================');
    },
    emailVerified: false,
    referenceValid: true,
    referredByUserId: null,
    referenceTimeout: null,
    setEmailVerified: function(val){
        registerFunction.emailVerified = val;
        if(val){
            $('#email-verified-status').text('Verified').css('color','green');
            $('#verify-email-link').hide();
        }else{
            $('#email-verified-status').text('');
            $('#verify-email-link').show();
        }
        registerFunction.updateSubmitState();
    },
    setReferenceValid: function(val) {
        registerFunction.referenceValid = val;
        registerFunction.updateSubmitState();
    },
    validateReference: function(code) {
        $('#reference-status').text('Validating...').css('color', 'orange');
        
        $.ajax({
            url: '../backend/validate_reference.php',
            type: 'POST',
            data: { user_qr_id: code },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    $('#reference-status').text('Valid reference').css('color', 'green');
                    registerFunction.referredByUserId = response.data.referred_by_user_id;
                    registerFunction.setReferenceValid(true);
                } else {
                    $('#reference-status').text(response.message || 'Invalid reference').css('color', 'red');
                    registerFunction.referredByUserId = null;
                    registerFunction.setReferenceValid(false);
                }
            },
            error: function() {
                $('#reference-status').text('Error validating reference').css('color', 'red');
                registerFunction.referredByUserId = null;
                registerFunction.setReferenceValid(false);
            }
        });
    },
    validateEmail: function(email){
        // Simple email regex
        return /^\S+@\S+\.\S+$/.test(email);
    },
    sendOtp: function(email, resend=false){
        $('#otp-status-msg').css('color','black').text(resend ? 'Resending OTP...' : 'Sending OTP...');
        
        // Clear previous OTP ID from all storage methods
        $('#email-otp-modal').removeAttr('data-otp-id');
        $('#email-otp-modal').removeData('otp-id');
        window.currentOtpId = null;
        
        $.ajax({
            url: '../backend/send_otp_production.php',
            type: 'POST',
            data: { email: email, resend: resend ? 1 : 0 },
            dataType: 'json',
            timeout: 15000,
            success: function(response){
                if(response.status){
                    // Extract OTP ID from response
                    var otp_id = response.data?.id || response.otp_id || null;
                    
                    if(otp_id){
                        // Store OTP ID in multiple ways for reliability
                        $('#email-otp-modal').attr('data-otp-id', String(otp_id));
                        $('#email-otp-modal').data('otp-id', String(otp_id));
                        window.currentOtpId = String(otp_id);
                        
                        $('#otp-status-msg').css('color','green').text('OTP sent to your email. Please check your inbox.');
                    } else {
                        $('#otp-status-msg').css('color','red').text('Invalid OTP response. Please try again.');
                    }
                }else{
                    $('#otp-status-msg').css('color','red').text(response.message || 'Failed to send OTP.');
                }
            },
            error: function(xhr, status, error){
                if (xhr.status === 0) {
                    $('#otp-status-msg').css('color','red').text('Network error. Please check your connection.');
                } else if (xhr.status === 404) {
                    $('#otp-status-msg').css('color','red').text('Server file not found. Please contact support.');
                } else if (xhr.status === 500) {
                    $('#otp-status-msg').css('color','red').text('Server error. Please try again.');
                } else {
                    $('#otp-status-msg').css('color','red').text('Error sending OTP. Please try again.');
                }
            }
        });
    },
    verifyOtp: function(otp_id, otp){
        $('#otp-status-msg').css('color','black').text('Verifying...');
        console.log('Verifying OTP with ID:', otp_id, 'OTP:', otp); // Debug log
        $.ajax({
            url: '../backend/verify_email_otp.php',
            type: 'POST',
            data: { otp_id: otp_id, otp: otp },
            dataType: 'json',
            success: function(response){
                console.log('OTP Verify Response:', response); // Debug log
                if(response.status){
                    $('#otp-status-msg').css('color','green').text('Email verified successfully!');
                    setTimeout(function() {
                        $('#email-otp-modal').hide();
                    }, 1500);
                    registerFunction.setEmailVerified(true);
                }else{
                    $('#otp-status-msg').css('color','red').text(response.message || 'Invalid OTP.');
                }
            },
            error: function(xhr, status, error){
                console.log('OTP Verify Error:', error, xhr.responseText); // Debug log
                $('#otp-status-msg').css('color','red').text('Error verifying OTP. Please try again.');
            }
        });
    },
    checkEmailVerified: function(email, cb){
        // Check if email is already verified in DB
        $.ajax({
            url: '../backend/check_email_verified.php',
            type: 'POST',
            data: { email: email },
            dataType: 'json',
            success: function(response){
                if(response.status && response.data.verified == 1){
                    registerFunction.setEmailVerified(true);
                    if(cb) cb(true);
                }else{
                    registerFunction.setEmailVerified(false);
                    if(cb) cb(false);
                }
            },
            error: function(){
                registerFunction.setEmailVerified(false);
                if(cb) cb(false);
            }
        });
    },
    init:()=>{
        registerFunction.updateSubmitText(true);
	    registerFunction.updateSubmitState();
    },
    getSelectedUserType:()=>{
        return [$('input[name="user_type"]').filter(':checked').val() || null,$('input[name="user_type"]').filter(':checked').attr('data-tag') || '']; // [value, tag]
    },
    updateSubmitText:(init=false)=>{
        var userType = registerFunction.getSelectedUserType()[0];
        if (userType === 'Gold Member') {
            $('#register_user_form').val('Pay 5000');
        } else if (userType === 'Silver Member') {
            $('#register_user_form').val('Pay 3000');
        } else if(init){
            $('#register_user_form').val('Pay');
        }else {
            $('#register_user_form').val('Pay 2000');
        }
    },
    isFormComplete:()=>{
        var email = $.trim($('#email').val());
        var password = $.trim($('#password').val());
        var confirmPassword = $.trim($('#confirm_password').val());
        var userType = registerFunction.getSelectedUserType()[0];
        var userSlab = $('#user_slab').val();
        var hasReference = $('#has_reference').is(':checked');
        var referenceCode = $.trim($('#reference_code').val());
        
        // Basic validation
        var isValid = (
            email !== '' &&
            password !== '' &&
            confirmPassword !== '' &&
            password === confirmPassword &&
            registerFunction.emailVerified &&
            userType !== null &&
            userSlab !== ''
        );
        
        // Reference validation
        if (hasReference) {
            isValid = isValid && referenceCode !== '' && registerFunction.referenceValid;
        }
        
        return isValid;
    },
    updateSubmitState:()=>{
        var $submit = $('#register_user_form');
        $submit.prop('disabled', !registerFunction.isFormComplete());
    },
    checkPasswordMatch: function() {
        var password = $('#password').val().trim();
        var confirmPassword = $('#confirm_password').val().trim();
        if (password !== '' && confirmPassword !== '') {
            if (password !== confirmPassword) {
                $('#password-match-status').text('Passwords do not match').css('color', 'red');
            }
            else{
                $('#password-match-status').text('')
            }
        } else {
            $('#password-match-status').text('');
        }
    },
}

// Registration form logic in jQuery
$(document).ready(function() {
	// Initial state
    eventHandler.init();
    registerFunction.init();
    // On page load, check if email is already verified (for autofill or browser restore)
    var email = $('#email').val().trim();
    if(email !== ''){
        registerFunction.checkEmailVerified(email);
    }
});
