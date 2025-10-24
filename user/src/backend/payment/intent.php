<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
    <title>Complete Payment</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen w-screen md:hidden">
        <div class="p-6">
            <div class="flex flex-col items-center">
                <div
                    class="logo m-auto mb-5 bg-white w-24 h-24 p-3 border rounded-full overflow-hidden flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-16 h-16">
                        <path fill="#3B82F6"
                            d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm-1.24 14.774l-3.34-3.34a.5.5 0 0 1 0-.708l.708-.707a.5.5 0 0 1 .707 0l2.279 2.279 5.526-5.526a.5.5 0 0 1 .707 0l.707.707a.5.5 0 0 1 0 .707l-6.587 6.588a.5.5 0 0 1-.707 0z" />
                    </svg>
                </div>
                <h2 class="font-bold text-xl text-center mb-3">
                    Complete your payment
                </h2>
                <p class="text-sm text-center text-gray-600 px-4">
                    Please select your preferred payment app from the options provided
                    in the pop-up, proceed with your payment, and return here once
                    your transaction is complete.
                </p>
            </div>
        </div>

        <div id="intent" class="invisible absolute"></div>

        <div class="px-6">
            <button id="pay"
                class="w-full bg-blue-600 text-white py-3 rounded-md hidden hover:bg-blue-700 transition-colors">
                Pay Now
            </button>
        </div>

        <div id="Cerror" class="mx-6 text-white p-3 bg-red-500 rounded-md mt-4 hidden"></div>
    </div>

    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <script>
        const cashfree = Cashfree({
            mode: "sandbox", // Change to "production" for live environment
        });

        const upiApp = cashfree.create("upiApp", {
            values: {
                upiApp: "web",
                buttonIcon: false,
            },
        });

        upiApp.on("loaderror", function (data) {
            console.error("Payment load error:", data);
            document.getElementById("Cerror").style.display = "block";
            document.getElementById("pay").style.display = "none";
            document.getElementById("Cerror").innerHTML = data.error.message;
        });

        upiApp.mount("#intent");

        upiApp.on("ready", function () {
            console.log("Payment form ready");
            document.getElementById("pay").style.display = "block";
            document.getElementById("pay").click();
        });

        // Handle payment events using the UPI app instance
        upiApp.on("payment_success", function (data) {
            console.log('Payment success:', data);
            const urlParams = new URLSearchParams(window.location.search);
            const orderId = data.order.orderId || urlParams.get('order_id');
            window.location.href = '../backend/payment/return.php?orderId=' + orderId;
        });

        upiApp.on("payment_failed", function (data) {
            console.error('Payment failed:', data);
            document.getElementById("Cerror").style.display = "block";
            document.getElementById("Cerror").innerHTML = "Payment failed: " + (data.message || "Unknown error");
        });

        document.getElementById("pay").addEventListener("click", function () {
            const urlParams = new URLSearchParams(window.location.search);
            const sessionId = urlParams.get('session');

            if (!sessionId) {
                document.getElementById("Cerror").style.display = "block";
                document.getElementById("Cerror").innerHTML = "Invalid payment session";
                return;
            }

            console.log("Initiating payment with session:", sessionId);
            cashfree.pay({
                paymentMethod: upiApp,
                paymentSessionId: sessionId,
            }).then(function (result) {
                console.log("Payment initiated:", result);
                window.parent.postMessage("Payment triggered", "*");
            }).catch(function (error) {
                console.error("Payment error:", error);
                document.getElementById("Cerror").style.display = "block";
                document.getElementById("Cerror").innerHTML = "Payment failed: " + error.message;
            });
        });
    </script>
</body>

</html>