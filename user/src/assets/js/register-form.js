/* ======================================
   REGISTRATION FORM - JAVASCRIPT FUNCTIONALITY
   ====================================== */

document.addEventListener('DOMContentLoaded', function() {
    // Enhanced user type selection with visual feedback
    const userTypeCards = document.querySelectorAll('.user-type-card');
    
    userTypeCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove selected class from all cards
            userTypeCards.forEach(c => c.classList.remove('selected'));
            
            // Add selected class to clicked card
            this.classList.add('selected');
            
            // Check the radio button
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                // Trigger change event for form validation
                radio.dispatchEvent(new Event('change'));
            }
        });
    });

    // Custom checkbox animation for reference code
    const hasReferenceCheckbox = document.getElementById('has_reference');
    if (hasReferenceCheckbox) {
        hasReferenceCheckbox.addEventListener('change', function() {
            const visual = document.getElementById('checkbox_visual');
            const checkMark = document.getElementById('check_mark');
            const referenceSection = document.getElementById('reference_section');
            
            if (this.checked) {
                visual.classList.add('checked');
                referenceSection.style.display = 'block';
                setTimeout(() => {
                    referenceSection.style.maxHeight = '200px';
                    referenceSection.style.opacity = '1';
                }, 10);
            } else {
                visual.classList.remove('checked');
                referenceSection.style.maxHeight = '0';
                referenceSection.style.opacity = '0';
                setTimeout(() => {
                    referenceSection.style.display = 'none';
                }, 300);
                
                // Clear reference code input
                const referenceInput = document.getElementById('reference_code');
                if (referenceInput) {
                    referenceInput.value = '';
                }
            }
        });
    }

    // Form validation and submission
    const form = document.getElementById('register_form');
    const submitBtn = document.getElementById('register_user_form');
    
    if (form && submitBtn) {
        // Enable/disable submit button based on form validation
        function validateForm() {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmpassword').value;
            const userSlab = document.getElementById('user_slab').value;
            const userType = document.querySelector('input[name="user_type"]:checked');
            
            const isValid = email && password && confirmPassword && 
                           password === confirmPassword && userSlab && userType;
            
            submitBtn.disabled = !isValid;
        }
        
        // Add event listeners for form validation
        ['input', 'change'].forEach(eventType => {
            form.addEventListener(eventType, validateForm);
        });
    }

    // Password match validation
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirmpassword');
    const passwordStatus = document.getElementById('password-match-status');
    
    if (passwordField && confirmPasswordField && passwordStatus) {
        function checkPasswordMatch() {
            const password = passwordField.value;
            const confirmPassword = confirmPasswordField.value;
            
            if (confirmPassword) {
                if (password === confirmPassword) {
                    passwordStatus.textContent = '✅ Passwords match';
                    passwordStatus.style.color = '#00b894';
                } else {
                    passwordStatus.textContent = '❌ Passwords do not match';
                    passwordStatus.style.color = '#e74c3c';
                }
            } else {
                passwordStatus.textContent = '';
            }
        }
        
        confirmPasswordField.addEventListener('input', checkPasswordMatch);
        passwordField.addEventListener('input', checkPasswordMatch);
    }
});
