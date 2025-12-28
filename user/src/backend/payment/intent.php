<?php
header('Content-Type: text/html; charset=utf-8');

$orderId = isset($_GET['orderId']) ? htmlspecialchars($_GET['orderId']) : '';
$sessionId = isset($_GET['session']) ? htmlspecialchars($_GET['session']) : '';

if (!$orderId || !$sessionId) {
    die('Invalid payment session or order ID.');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
    <title>Complete Payment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen w-screen flex flex-col items-center justify-center">
        <div class="p-6 text-center max-w-md">
            <div class="flex flex-col items-center">
                <div
                    class="logo m-auto mb-5 bg-white w-24 h-24 p-3 border rounded-full overflow-hidden flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-16 h-16">
                        <path fill="#3B82F6"
                            d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm-1.24 14.774l-3.34-3.34a.5.5 0 0 1 0-.708l.708-.707a.5.5 0 0 1 .707 0l2.279 2.279 5.526-5.526a.5.5 0 0 1 .707 0l.707.707a.5.5 0 0 1 0 .707l-6.587 6.588a.5.5 0 0 1-.707 0z" />
                    </svg>
                </div>
                <h2 class="font-bold text-xl text-center mb-3">Complete your payment</h2>
                <p class="text-sm text-center text-gray-600 px-4 mb-6">
                    Click the button below to open the payment page. After payment, you'll automatically be registered.
                </p>
            </div>
        </div>

        <div id="status" class="mx-6 text-center text-sm text-gray-600 mt-4 mb-4 max-w-md">
            <p id="status-text">Ready to pay</p>
        </div>
        <div id="Cerror" class="mx-6 text-white p-3 bg-red-500 rounded-md mt-4 hidden text-center max-w-md"></div>

        <button id="payNowBtn"
            class="mx-6 bg-green-600 text-white px-8 py-4 rounded-lg mt-4 hover:bg-green-700 max-w-md font-bold text-lg shadow-lg">
            💳 Pay Now
        </button>

        <button id="checkPaymentBtn"
            class="hidden mx-6 bg-blue-600 text-white px-6 py-2 rounded-md mt-4 hover:bg-blue-700 max-w-md">
            ✓ Check Payment Status
        </button>
        <button id="backBtn"
            class="hidden mx-6 bg-gray-400 text-white px-6 py-2 rounded-md mt-2 hover:bg-gray-500 max-w-md">
            ← Back to Registration
        </button>
    </div>

    <script>
        const orderId = '<?php echo $orderId; ?>';
        const sessionId = '<?php echo $sessionId; ?>';
        let pollInterval = null;
        let registrationCompleted = false;

        console.log('intent.php loaded - orderId:', orderId);

        // Initialize Cashfree with checkout mode (works on both desktop and mobile)
        const cashfree = new Cashfree({ mode: "production" });

        function updateStatus(msg) {
            document.getElementById("status-text").textContent = msg;
            console.log(msg);
        }

        function showError(err) {
            document.getElementById("Cerror").style.display = "block";
            document.getElementById("Cerror").innerHTML = err;
            document.getElementById("checkPaymentBtn").classList.remove("hidden");
            document.getElementById("backBtn").classList.remove("hidden");
        }

        function startPayment() {
            console.log("Starting payment via checkout...");
            updateStatus("Opening payment page...");
            
            const payBtn = document.getElementById("payNowBtn");
            payBtn.disabled = true;
            payBtn.style.opacity = '0.5';
            payBtn.innerHTML = '⏳ Processing...';

            // Use checkout method - works on all devices (desktop + mobile)
            cashfree.checkout({
                paymentSessionId: sessionId,
                returnUrl: window.location.origin + "/user/src/backend/payment/return.php?orderId=" + orderId
            }).then(function(result) {
                console.log("Checkout result:", result);
                // User completed or cancelled - check status
                if (result.error) {
                    console.error("Payment error:", result.error);
                    showError("Payment error: " + (result.error.message || "Unknown error"));
                    payBtn.disabled = false;
                    payBtn.style.opacity = '1';
                    payBtn.innerHTML = '💳 Try Again';
                } else if (result.paymentDetails) {
                    // Payment was attempted
                    updateStatus("Checking payment status...");
                    checkPaymentStatus();
                }
            }).catch(function(error) {
                console.error("Checkout error:", error);
                showError("Error: " + (error.message || "Payment failed"));
                payBtn.disabled = false;
                payBtn.style.opacity = '1';
                payBtn.innerHTML = '💳 Try Again';
            });
        }

        function checkPaymentStatus() {
            fetch('../backend/payment/check_payment_status.php?orderId=' + encodeURIComponent(orderId) + '&t=' + Date.now(), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                console.log('Payment status:', data.status);
                if (data.status === 'PAID') {
                    completeRegistration();
                } else if (data.status === 'FAILED') {
                    showError("Payment was declined. Please try again.");
                } else {
                    // Still pending - show check button
                    updateStatus("Payment status: " + data.status + ". Click below to verify.");
                    document.getElementById("checkPaymentBtn").classList.remove("hidden");
                    document.getElementById("backBtn").classList.remove("hidden");
                    
                    const payBtn = document.getElementById("payNowBtn");
                    payBtn.disabled = false;
                    payBtn.style.opacity = '1';
                    payBtn.innerHTML = '💳 Try Again';
                }
            })
            .catch(e => {
                console.error('Status check error:', e);
                showError("Error checking payment status");
            });
        }

        function completeRegistration() {
            if (registrationCompleted) {
                console.log('Registration already completed, ignoring duplicate call');
                return;
            }
            registrationCompleted = true;

            updateStatus("✓ Payment successful! Completing registration...");
            console.log('Calling verify_and_complete.php with orderId:', orderId);

            fetch('../backend/payment/verify_and_complete.php?orderId=' + encodeURIComponent(orderId), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' }
            })
                .then(r => r.json())
                .then(data => {
                    console.log('Verify response:', data);
                    if (data.success) {
                        updateStatus("✓ Registration complete! Redirecting...");
                        setTimeout(function () {
                            window.location.href = data.redirect || '/user/src/ui/login.php';
                        }, 1500);
                    } else {
                        registrationCompleted = false;
                        showError("Registration failed: " + (data.message || 'Please try again'));
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    registrationCompleted = false;
                    showError("Error completing registration. Please refresh.");
                });
        }

        // Manual check button
        document.getElementById("checkPaymentBtn").addEventListener("click", function () {
            console.log('Manual check clicked');
            updateStatus("Checking payment status...");
            checkPaymentStatus();
        });

        // Pay Now button
        document.getElementById("payNowBtn").addEventListener("click", function () {
            console.log('Pay Now button clicked');
            startPayment();
        });

        // Back button
        document.getElementById("backBtn").addEventListener("click", function () {
            window.location.href = '../ui/register.php';
        });
    </script>
</body>

</html>