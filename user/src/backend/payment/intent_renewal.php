<?php
/**
 * Intent page for subscription renewal payments
 * Uses Cashfree checkout (works on both desktop and mobile)
 */
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
    <title>Renew Subscription</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen w-screen flex flex-col items-center justify-center">
        <div class="p-6 text-center max-w-md">
            <div class="flex flex-col items-center">
                <div class="logo m-auto mb-5 bg-white w-24 h-24 p-3 border rounded-full overflow-hidden flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-16 h-16">
                        <path fill="#10B981"
                            d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm-1.24 14.774l-3.34-3.34a.5.5 0 0 1 0-.708l.708-.707a.5.5 0 0 1 .707 0l2.279 2.279 5.526-5.526a.5.5 0 0 1 .707 0l.707.707a.5.5 0 0 1 0 .707l-6.587 6.588a.5.5 0 0 1-.707 0z" />
                    </svg>
                </div>
                <h2 class="font-bold text-xl text-center mb-3">Renew Your Subscription</h2>
                <p class="text-sm text-center text-gray-600 px-4 mb-6">
                    Complete the payment to extend your subscription by one year.
                </p>
            </div>
        </div>

        <div id="status" class="mx-6 text-center text-sm text-gray-600 mt-4 mb-4 max-w-md">
            <p id="status-text">Initializing payment...</p>
        </div>
        <div id="Cerror" class="mx-6 text-white p-3 bg-red-500 rounded-md mt-4 hidden text-center max-w-md"></div>

        <button id="payNowBtn"
            class="mx-6 bg-green-600 text-white px-6 py-3 rounded-md mt-4 hover:bg-green-700 max-w-md font-bold text-lg">
            🔄 Pay & Renew Now
        </button>

        <button id="backBtn"
            class="mx-6 bg-gray-400 text-white px-6 py-2 rounded-md mt-2 hover:bg-gray-500 max-w-md">
            ← Back to Profile
        </button>
    </div>

    <script>
        const orderId = '<?php echo $orderId; ?>';
        const sessionId = '<?php echo $sessionId; ?>';

        console.log('intent_renewal.php loaded - orderId:', orderId);

        function updateStatus(msg) {
            document.getElementById("status-text").textContent = msg;
            console.log(msg);
        }

        function showError(err) {
            document.getElementById("Cerror").style.display = "block";
            document.getElementById("Cerror").innerHTML = err;
        }

        // Check URL for payment return
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('order_id')) {
            // Returned from payment - redirect to verification
            updateStatus("Verifying payment...");
            window.location.href = 'return_renewal.php?order_id=' + encodeURIComponent(urlParams.get('order_id'));
        }

        // Pay Now button - opens Cashfree checkout
        document.getElementById("payNowBtn").addEventListener("click", function () {
            console.log('Pay Now button clicked');
            this.disabled = true;
            this.style.opacity = '0.5';
            this.textContent = 'Opening payment...';
            updateStatus("Opening payment gateway...");

            const cashfree = new Cashfree({ mode: "production" });
            // Detect if localhost or production
            const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
            const subdirectory = isLocalhost ? '/qr' : '';
            cashfree.checkout({
                paymentSessionId: sessionId,
                returnUrl: window.location.origin + subdirectory + '/user/src/backend/payment/return_renewal.php?order_id=' + orderId
            }).then(function(result) {
                console.log('Checkout result:', result);
                if (result.error) {
                    showError(result.error.message || 'Payment failed');
                    document.getElementById("payNowBtn").disabled = false;
                    document.getElementById("payNowBtn").style.opacity = '1';
                    document.getElementById("payNowBtn").textContent = '🔄 Pay & Renew Now';
                }
            }).catch(function(error) {
                console.error('Checkout error:', error);
                showError(error.message || 'Payment error');
                document.getElementById("payNowBtn").disabled = false;
                document.getElementById("payNowBtn").style.opacity = '1';
                document.getElementById("payNowBtn").textContent = '🔄 Pay & Renew Now';
            });
        });

        // Back button
        document.getElementById("backBtn").addEventListener("click", function () {
            window.location.href = '../../ui/profile.php';
        });

        updateStatus("Ready. Click 'Pay & Renew Now' to continue.");
    </script>
</body>

</html>
