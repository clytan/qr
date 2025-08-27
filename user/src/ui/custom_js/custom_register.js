var eventHandler = {
    init:()=>{
        eventHandler.onChange();
        eventHandler.onInput();
        eventHandler.submitEvent();
        eventHandler.emailInputEvents();
        eventHandler.emailVerifyEvents();
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
            registerFunction.sendOtp(email);
        });
        // Close modal
        $('#close-otp-modal').on('click', function(){
            $('#email-otp-modal').hide();
        });
        // Submit OTP
        $('#submit-otp-btn').on('click', function(){
            var email = $('#email').val().trim();
            var otp = $('#email-otp-input').val().trim();
            if(otp.length !== 6 || !/^[0-9]{6}$/.test(otp)){
                $('#otp-status-msg').text('Enter a valid 6-digit OTP.');
                return;
            }
            var otp_id = $('#email-otp-modal').attr('data-otp-id') || '';
            if(otp_id !== ''){
                registerFunction.verifyOtp(otp_id, otp);
            }
            else{
                $('#otp-status-msg').text('OTP missing. Please resend OTP.');
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
            registerFunction.updateSubmitState($('#name'), $('#email'), $('#phone'), $('#register_user_form'));
        });
    },
    onInput:()=>{
        $('#name, #email, #phone').on('input', function() {
            registerFunction.updateSubmitState($('#name'), $('#email'), $('#phone'), $('#register_user_form'));
        });
        // Password match check
        $('#confirm_password').on('input', function() {
            registerFunction.checkPasswordMatch();
        });
        $('#password').on('input', function() {
            registerFunction.checkPasswordMatch();
        });
    },
    submitEvent:() =>{
        $('#register_form').on('submit', function(e) {
            e.preventDefault();
            // Collect form data
            var formData = {
                email: $('#email').val().trim(),
                password: $('#password').val().trim(),
                user_type: registerFunction.getSelectedUserType()
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
    emailVerified: false,
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
    validateEmail: function(email){
        // Simple email regex
        return /^\S+@\S+\.\S+$/.test(email);
    },
    sendOtp: function(email, resend=false){
        $('#otp-status-msg').css('color','black').text(resend ? 'Resending OTP...' : 'Sending OTP...');
        $.ajax({
            url: '../mailer/email_templates/sendemailotp.php',
            type: 'POST',
            data: { email: email, resend: resend ? 1 : 0 },
            dataType: 'json',
            success: function(response){
                if(response.status){
                    $('#email-otp-modal').attr('data-otp-id', response.data.id || '');
                    $('#otp-status-msg').css('color','green').text('OTP sent to your email.');
                }else{
                    $('#otp-status-msg').css('color','red').text(response.message || 'Failed to send OTP.');
                }
            },
            error: function(){
                $('#otp-status-msg').css('color','red').text('Error sending OTP.');
            }
        });
    },
    verifyOtp: function(otp_id, otp){
        $('#otp-status-msg').css('color','black').text('Verifying...');
        $.ajax({
            url: '../backend/verify_email_otp.php',
            type: 'POST',
            data: { otp_id: otp_id, otp: otp },
            dataType: 'json',
            success: function(response){
                if(response.status){
                    $('#otp-status-msg').css('color','green').text('Email verified successfully!');
                    $('#email-otp-modal').hide();
                    registerFunction.setEmailVerified(true);
                }else{
                    $('#otp-status-msg').css('color','red').text(response.message || 'Invalid OTP.');
                }
            },
            error: function(){
                $('#otp-status-msg').css('color','red').text('Error verifying OTP.');
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
    isFormComplete:($name, $email, $phone)=>{
        // Only require email verification for registration
        return (
            $.trim($email.val()) !== '' &&
            registerFunction.emailVerified &&
            registerFunction.getSelectedUserType()[0] !== null
        );
    },
    updateSubmitState:($name, $email, $phone, $submit)=>{
        // Use default selectors if arguments are not provided
        $name = $name || $('#name');
        $email = $email || $('#email');
        $phone = $phone || $('#phone');
        $submit = $submit || $('#register_user_form');
        $submit.prop('disabled', !registerFunction.isFormComplete($name, $email, $phone));
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
