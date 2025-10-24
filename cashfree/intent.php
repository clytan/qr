<?php
if (!isset($_GET['session'])) {
    echo "Unauthorized access";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, userScalable=no" />
    <title>Pay Now</title>
    <script src="./tailwind.js"></script>
</head>

<body>
    <div class="h-screen w-screen md:hidden ">
        <div class="rounded-none py-3">
            <div class="flex flex-col items-center">
                <div class="logo m-auto mb-5 bg-white w-[100px] h-[100px] p-3 border rounded-full overflow-hidden">
                    <!-- Add an image or logo -->
                    <img src="/assets/upi.png" alt="">
                </div>
                <h2 class="font-bold text-md text-center mb-2">
                    Complete your payment
                </h2>
                <p class="text-[13px] text-center px-3">
                    Please select your preferred payment app from the options provided
                    in the pop-up, proceed with your payment, and return here once
                    your transaction is complete.
                </p>
            </div>
        </div>
        <div id="intent" class="invisible absolute"></div>
        <div class="p-3">
            <button id="pay" class="w-full dark:bg-zinc-900 border border-white text-white py-3 rounded-md hidden">Pay
                Now</button>
        </div>
        <div id="Cerror" class="mx-3 text-white p-2 bg-red-500 rounded-md hidden"></div>
    </div>

    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <script>
        let cashfree = Cashfree({
            mode: "production",
        });

        let upiApp = cashfree.create("upiApp", {
            values: {
                upiApp: "web",
                buttonIcon: false,
            },
        });

        upiApp.on("loaderror", function (data) {
            Cerror.style.display = "block";
            pay.style.display = "none";
            Cerror.innerHTML = data.error.message;
        });

        upiApp.mount("#intent");

        upiApp.on("ready", function (d) {
            pay.style.display = "block";
            pay.click();
        });

        document.getElementById("pay").addEventListener("click", function () {
            let paymentPromise = cashfree.pay({
                paymentMethod: upiApp,
                paymentSessionId: "<?= $_GET['session']; ?>",
            });
            paymentPromise.then(function (result) {
                window.parent.postMessage("Payment triggered", "*");
            });
        });
    </script>
</body>

</html>