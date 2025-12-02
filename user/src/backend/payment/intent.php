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
                    Select your payment method and complete the payment. After payment, you'll automatically be
                    registered.
                </p>
            </div>
        </div>

        <div id="intent" class="w-full max-w-md px-6"></div>
        <div id="status" class="mx-6 text-center text-sm text-gray-600 mt-4 mb-4 max-w-md">
            <p id="status-text">Initializing payment...</p>
        </div>
        <div id="Cerror" class="mx-6 text-white p-3 bg-red-500 rounded-md mt-4 hidden text-center max-w-md"></div>

        <button id="payNowBtn"
            class="mx-6 bg-green-600 text-white px-6 py-3 rounded-md mt-4 hover:bg-green-700 max-w-md font-bold text-lg">
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

    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <script>
        const orderId = '<?php echo $orderId; ?>';
        const sessionId = '<?php echo $sessionId; ?>';
        let pollInterval = null;
        let registrationCompleted = false;

        // Prevent accidental reuse of old intent.php pages
        // Store current attempt in sessionStorage
        const currentAttempt = Date.now() + '_' + Math.random();
        sessionStorage.setItem('current_payment_attempt', currentAttempt);

        console.log('intent.php loaded - orderId:', orderId, 'sessionId:', sessionId.substring(0, 50) + '...');
        console.log('Payment attempt ID:', currentAttempt);

        const cashfree = Cashfree({
            mode: "production",
        });

        const upiApp = cashfree.create("upiApp", {
            values: {
                upiApp: "web",
                buttonIcon: false,
            },
        });

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

        upiApp.on("loaderror", function (data) {
            console.error("Load error:", data);
            showError("Payment load error: " + (data.error?.message || "Unknown"));
        });

        upiApp.mount("#intent");

        upiApp.on("ready", function () {
            console.log("Payment ready");
            updateStatus("Payment form ready. Click 'Pay Now' to continue...");
            // Don't auto-start on mobile - let user click the button
        });

        function startPayment() {
            console.log("Starting payment...");
            updateStatus("Opening payment app...");

            cashfree.pay({
                paymentMethod: upiApp,
                paymentSessionId: sessionId,
            })
                .then(function (result) {
                    console.log("Payment initiated:", result);
                    updateStatus("Payment app opened. Please complete payment...");
                    // Start polling IMMEDIATELY after payment is initiated
                    startPollingForPayment();
                })
                .catch(function (error) {
                    console.error("Payment error:", error);
                    showError("Error: " + (error.message || "Unknown"));
                });
        }

        // Listen for success callback (works if browser stays active)
        upiApp.on("payment_success", function (data) {
            console.log("✓ Payment success callback:", data);
            updateStatus("✓ Payment successful!");
            completeRegistration();
        });

        upiApp.on("payment_failed", function (data) {
            console.error("✗ Payment failed:", data);
            showError("Payment failed: " + (data.message || "Unknown"));
            stopPolling();
        });

        // AGGRESSIVE POLLING - keeps checking even if user left the tab
        function startPollingForPayment() {
            console.log('Starting payment status polling...');
            let pollCount = 0;
            const maxPolls = 300; // 5 minutes

            pollInterval = setInterval(function () {
                pollCount++;

                fetch('../backend/payment/check_payment_status.php?orderId=' + encodeURIComponent(orderId) + '&t=' + Date.now(), {
                    method: 'GET',
                    headers: { 'Content-Type': 'application/json' }
                })
                    .then(r => r.json())
                    .then(data => {
                        console.log('Poll #' + pollCount + ' - Status:', data.status);

                        if (data.status === 'PAID') {
                            console.log('✓ Payment confirmed via polling!');
                            stopPolling();
                            completeRegistration();
                        } else if (data.status === 'FAILED') {
                            console.log('✗ Payment failed');
                            stopPolling();
                            showError("Payment was declined. Please try again.");
                        } else if (pollCount % 30 === 0) {
                            // Update status every 30 seconds
                            updateStatus("Checking payment status... (" + Math.floor(pollCount / 60) + "m)");
                        }
                    })
                    .catch(e => console.log('Poll error:', e));

                if (pollCount >= maxPolls) {
                    console.log('Max polling reached');
                    stopPolling();
                    showError("Payment check timed out. Click button below to verify manually.");
                }
            }, 1000); // Poll every second
        }

        function stopPolling() {
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
        }

        function completeRegistration() {
            if (registrationCompleted) {
                console.log('Registration already completed, ignoring duplicate call');
                return;
            }
            registrationCompleted = true;

            stopPolling();
            updateStatus("✓ Completing registration...");
            console.log('Calling verify_and_complete.php with orderId:', orderId);

            fetch('../backend/payment/verify_and_complete.php?orderId=' + encodeURIComponent(orderId), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' }
            })
                .then(r => r.json())
                .then(data => {
                    console.log('Verify response:', data);
                    if (data.success) {
                        updateStatus("✓ Registration complete!");
                        setTimeout(function () {
                            window.location.href = data.redirect || '/user/src/ui/login.php';
                        }, 1500);
                    } else {
                        registrationCompleted = false; // Allow retry on error
                        showError("Registration failed: " + (data.message || 'Please try again'));
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    registrationCompleted = false; // Allow retry on error
                    showError("Error completing registration. Please refresh.");
                });
        }

        // Manual check button
        document.getElementById("checkPaymentBtn").addEventListener("click", function () {
            console.log('Manual check clicked');
            updateStatus("Checking payment status...");

            fetch('../backend/payment/check_payment_status.php?orderId=' + encodeURIComponent(orderId) + '&t=' + Date.now(), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' }
            })
                .then(r => r.json())
                .then(data => {
                    console.log('Manual check result:', data);
                    if (data.status === 'PAID') {
                        updateStatus("✓ Payment confirmed!");
                        completeRegistration();
                    } else {
                        showError("Status: " + data.status + ". Try again in a moment.");
                    }
                })
                .catch(e => {
                    console.error('Check error:', e);
                    showError("Error checking payment.");
                });
        });

        // Pay Now button - user clicks this to start payment
        document.getElementById("payNowBtn").addEventListener("click", function () {
            console.log('Pay Now button clicked');
            this.disabled = true;
            this.style.opacity = '0.5';
            startPayment();
        });

        // Back button
        document.getElementById("backBtn").addEventListener("click", function () {
            // Clear all payment-related session data when going back
            sessionStorage.removeItem('current_payment_attempt');
            sessionStorage.removeItem('payment_orderId');
            sessionStorage.removeItem('payment_sessionId');
            window.location.href = '../ui/register.php';
        });

        // IMPORTANT: Check payment status every 10 seconds even if user is not looking
        // This ensures registration happens even if user switches to payment app
        window.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                console.log('Page hidden - continuing to poll for payment');
            } else {
                console.log('Page visible - polling continues');
            }
        });

        // Check on page load/focus
        window.addEventListener('focus', function () {
            console.log('Page focused - checking payment status');
            if (pollInterval === null) {
                startPollingForPayment();
            }
        });
    </script>
</body>

</html>