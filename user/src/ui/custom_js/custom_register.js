
var eventHandler = {
    init:()=>{
        eventHandler.onChange();
        eventHandler.onInput();
        eventHandler.submitEvent();
    },
    onChange:()=>{
        $('input[name="user_type"]').on('change', function() {
            registerFunction.updateSubmitText();
            registerFunction.updateSubmitState($('#name'), $('#email'), $('#phone'), $('#register_user_form'));
        });
    },
    onInput:()=>{
        $('#name, #email, #phone').on('input', function() {
            registerFunction.updateSubmitState($('#name'), $('#email'), $('#phone'), $('#register_user_form'));
        });
    },
    submitEvent:() =>{
        $('#register_form').on('submit', function(e) {
            e.preventDefault();

            // Collect form data
            var name = $('#name').val();
            var email = $('#email').val();
            var phone = $('#phone').val();
            var userType = registerFunction.getSelectedUserType();
            var amount = 2000;
            if (userType === 'Gold Member') {
                amount = 5000;
            } else if (userType === 'Silver Member') {
                amount = 3000;
            }

            // Example: Replace with your payment gateway integration
            // For demonstration, using a fake payment process
            // You can replace this with Razorpay, PayPal, Stripe, etc.

            alert('Redirecting to payment gateway for ' + userType + ' (Amount: ' + amount + ')');

            // Simulate payment success callback
            setTimeout(function() {
                // On payment success, redirect to index.php
                window.location.href = 'index.php';
                // registerFunction.postRegisterData()
            }, 2000);
        });
    }
};

var registerFunction = {
    init:()=>{
        registerFunction.updateSubmitText();
	    registerFunction.updateSubmitState();
    },
    getSelectedUserType:()=>{
        return $('input[name="user_type"]').filter(':checked').val() || null;
    },
    updateSubmitText:()=>{
        var userType = registerFunction.getSelectedUserType();
        if (userType === 'Gold Member') {
            $('#register_user_form').val('Pay 5000');
        } else if (userType === 'Silver Member') {
            $('#register_user_form').val('Pay 3000');
        } else {
            $('#register_user_form').val('Pay 2000');
        }
    },
    isFormComplete:($name, $email, $phone)=>{
        return (
            $.trim($name.val()) !== '' &&
            $.trim($email.val()) !== '' &&
            $.trim($phone.val()) !== '' &&
            registerFunction.getSelectedUserType() !== null
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
    postRegisterData:()=>{
        var formData = {
            name: $('#name').val().trim(),
            email: $('#email').val().trim(),
            phone: $('#phone').val().trim(),
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
                    window.location.href = 'index.php';
                } else {
                    alert(response.message || 'Registration failed.');
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred: ' + error);
            }
        });
    }
}

// Registration form logic in jQuery
$(document).ready(function() {
	// Initial state
    eventHandler.init();
    registerFunction.init();
});
