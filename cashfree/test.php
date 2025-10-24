<?php
$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/qr/cashfree";
?>
<!DOCTYPE html>
<html>

<head>
    <title>Cashfree Payment Test</title>
    <script src="https://sdk.cashfree.com/js/ui/2.0.0/cashfree.sandbox.js"></script>
</head>

<body>
    <h2>Test Cashfree Payment</h2>
    <form id="paymentForm">
        <div>
            <label>Order ID:</label>
            <input type="text" id="order_id" name="order_id" value="TEST_<?php echo time(); ?>" readonly>
        </div>
        <div>
            <label>Customer ID:</label>
            <input type="text" id="customer_id" name="customer_id" value="CUST_<?php echo time(); ?>" readonly>
        </div>
        <div>
            <label>Name:</label>
            <input type="text" id="user_name" name="user_name" value="Test User">
        </div>
        <div>
            <label>Email:</label>
            <input type="email" id="user_email" name="user_email" value="test@example.com">
        </div>
        <div>
            <label>Phone:</label>
            <input type="text" id="user_number" name="user_number" value="9999999999">
        </div>
        <div>
            <label>Amount (INR):</label>
            <input type="number" id="amount" name="amount" value="1">
        </div>
        <button type="button" onclick="initiatePayment()">Pay Now</button>
    </form>

    <script>
        async function initiatePayment() {
            try {
                const form = document.getElementById('paymentForm');
                const formData = new FormData(form);
                const searchParams = new URLSearchParams();

                for (const [key, value] of formData.entries()) {
                    if (!value) {
                        throw new Error(`${key} cannot be empty`);
                    }
                    searchParams.append(key, value);
                }

                const response = await fetch('order.php?' + searchParams.toString());
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || `HTTP error! status: ${response.status}`);
                }

                if (data.status && data.session) {
                    const cf = new Cashfree(data.session);
                    // Initialize payment
                    cf.redirect();
                } else {
                    throw new Error(data.error || 'Failed to create payment session');
                }
            } catch (error) {
                console.error('Payment Error:', error);
                alert('Error: ' + error.message);
            }
        }
    </script>
</body>

</html>