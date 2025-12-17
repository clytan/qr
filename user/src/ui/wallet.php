<!DOCTYPE html>
<html lang="zxx">
<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>

<head>
    <title>Zokli - Wallet</title>
    <link rel="icon" href="../assets/logo2.png" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="Zokli Wallet" name="description" />
    <?php include('../components/csslinks.php') ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
            --card-bg: #1e293b;
            --border-color: #334155;
            --text-color: #e2e8f0;
            --text-secondary: #94a3b8;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        .wallet-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .wallet-balance-card {
            background: var(--primary-gradient);
            color: #fff;
            border-radius: 24px;
            box-shadow: 0 6px 32px rgba(233, 67, 122, 0.4);
            padding: 2.5rem 1.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .balance-amount {
            font-size: 3rem;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .balance-label {
            font-size: 1.2rem;
            opacity: 0.9;
            font-weight: 500;
        }

        .wallet-actions {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .action-btn {
            text-align: center;
        }

        .action-btn button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary-gradient);
            border: 2px solid rgba(255,255,255,0.2);
            box-shadow: 0 4px 12px rgba(233, 67, 122, 0.4);
            color: #fff;
            font-size: 1.4rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .action-btn button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(233, 67, 122, 0.6);
        }

        .action-btn span {
            display: block;
            margin-top: 8px;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .section-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .transaction-item:last-child {
            border-bottom: none;
        }

        .tx-info h4 {
            font-size: 0.95rem;
            color: var(--text-color);
            margin: 0;
        }

        .tx-info p {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin: 4px 0 0 0;
        }

        .tx-amount {
            font-weight: 700;
            font-size: 1rem;
        }

        .tx-amount.credit { color: var(--success); }
        .tx-amount.debit { color: var(--danger); }

        /* Withdrawal Status Badges */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background: rgba(245, 158, 11, 0.2); color: var(--warning); }
        .status-approved { background: rgba(16, 185, 129, 0.2); color: var(--success); }
        .status-rejected { background: rgba(239, 68, 68, 0.2); color: var(--danger); }
        .status-completed { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active { display: flex; }

        .modal-content {
            background: var(--card-bg);
            border-radius: 16px;
            width: 90%;
            max-width: 450px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid var(--border-color);
        }

        .modal-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            color: var(--text-color);
            font-size: 1.2rem;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .modal-body {
            padding: 1.25rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            color: var(--text-color);
            font-size: 0.9rem;
            margin-bottom: 6px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-color);
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: #e67753;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 1rem;
        }

        .payment-method {
            padding: 16px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .payment-method:hover {
            border-color: #e67753;
        }

        .payment-method.selected {
            border-color: #e67753;
            background: rgba(230, 119, 83, 0.1);
        }

        .payment-method i {
            font-size: 1.5rem;
            margin-bottom: 8px;
            display: block;
        }

        .payment-method span {
            font-size: 0.9rem;
            color: var(--text-color);
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background: var(--primary-gradient);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(233, 67, 122, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .bank-fields, .upi-fields {
            display: none;
        }

        .bank-fields.active, .upi-fields.active {
            display: block;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Withdrawal history item */
        .withdrawal-item {
            background: rgba(255,255,255,0.02);
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 10px;
        }

        .withdrawal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .withdrawal-amount {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-color);
        }

        .withdrawal-details {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .btn-cancel {
            padding: 6px 12px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--danger);
            border-radius: 6px;
            color: var(--danger);
            font-size: 0.8rem;
            cursor: pointer;
            margin-top: 8px;
        }

        .btn-cancel:hover {
            background: var(--danger);
            color: white;
        }

        @media (max-width: 768px) {
            .wallet-container {
                padding-bottom: 100px;
            }
        }
    </style>
</head>

<body class="dark-scheme de-grey">
    <div id="wrapper">
        <?php include('../components/header.php') ?>

        <div class="no-bottom no-top" id="content">
            <div id="top"></div>
            <section aria-label="section" style="padding-top: 100px;">
                <div class="wallet-container">
                    <h2 class="text-center text-white mb-4" style="font-weight: 700;">
                        <!-- <i class="fas fa-wallet"></i> My Wallet -->
                    </h2>

                    <!-- Balance Card -->
                    <div class="wallet-balance-card">
                        <div class="balance-amount" id="wallet-balance">0</div>
                        <div class="balance-label">Coins</div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="wallet-actions">
                        <div class="action-btn">
                            <button onclick="showRedeemModal()">
                                <i class="fas fa-money-bill-wave"></i>
                            </button>
                            <span>Redeem</span>
                        </div>
                        <div class="action-btn">
                            <button onclick="showHistory()">
                                <i class="fas fa-history"></i>
                            </button>
                            <span>History</span>
                        </div>
                    </div>

                    <!-- Pending Withdrawals -->
                    <div class="section-card" id="pending-section" style="display: none;">
                        <h5 class="section-title">
                            <i class="fas fa-clock"></i> Pending Withdrawals
                        </h5>
                        <div id="pending-withdrawals"></div>
                    </div>

                    <!-- Transactions -->
                    <div class="section-card">
                        <h5 class="section-title">
                            <i class="fas fa-exchange-alt"></i> Recent Transactions
                        </h5>
                        <div id="wallet-transactions">
                            <div class="empty-state">
                                <i class="fas fa-spinner fa-spin"></i>
                                <p>Loading...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Withdrawal History -->
                    <div class="section-card" id="history-section" style="display: none;">
                        <h5 class="section-title">
                            <i class="fas fa-list"></i> Withdrawal History
                        </h5>
                        <div id="withdrawal-history"></div>
                    </div>
                </div>
            </section>
        </div>

        <a href="#" id="back-to-top"></a>
        <?php include('../components/footer.php'); ?>
    </div>

    <!-- Redeem Modal -->
    <div class="modal-overlay" id="redeemModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-money-bill-wave"></i> Redeem to Bank</h3>
                <button class="modal-close" onclick="closeRedeemModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Amount to Redeem (₹)</label>
                    <input type="number" class="form-control" id="redeem-amount" placeholder="Enter amount (min ₹100)" min="100">
                    <small style="color: var(--text-secondary);">Minimum: ₹100 | Available: <span id="available-balance">0</span> coins</small>
                </div>

                <label style="color: var(--text-color); font-weight: 500; margin-bottom: 10px; display: block;">Payment Method</label>
                <div class="payment-methods">
                    <div class="payment-method selected" data-method="upi" onclick="selectPaymentMethod('upi')">
                        <i class="fas fa-mobile-alt"></i>
                        <span>UPI</span>
                    </div>
                    <div class="payment-method" data-method="bank" onclick="selectPaymentMethod('bank')">
                        <i class="fas fa-university"></i>
                        <span>Bank Transfer</span>
                    </div>
                </div>

                <!-- UPI Fields -->
                <div class="upi-fields active" id="upi-fields">
                    <div class="form-group">
                        <label>UPI ID</label>
                        <input type="text" class="form-control" id="upi-id" placeholder="yourname@upi">
                    </div>
                </div>

                <!-- Bank Fields -->
                <div class="bank-fields" id="bank-fields">
                    <div class="form-group">
                        <label>Account Holder Name</label>
                        <input type="text" class="form-control" id="account-holder" placeholder="As per bank records">
                    </div>
                    <div class="form-group">
                        <label>Bank Name</label>
                        <input type="text" class="form-control" id="bank-name" placeholder="e.g., HDFC Bank">
                    </div>
                    <div class="form-group">
                        <label>Account Number</label>
                        <input type="text" class="form-control" id="account-number" placeholder="Enter account number">
                    </div>
                    <div class="form-group">
                        <label>IFSC Code</label>
                        <input type="text" class="form-control" id="ifsc-code" placeholder="e.g., HDFC0001234" style="text-transform: uppercase;">
                    </div>
                </div>

                <button class="btn-primary" id="submit-redeem" onclick="submitRedeemRequest()">
                    <i class="fas fa-paper-plane"></i> Submit Request
                </button>
            </div>
        </div>
    </div>

    <?php include('../components/jslinks.php'); ?>

    <script>
        let currentBalance = 0;
        let selectedPaymentMethod = 'upi';

        $(document).ready(function() {
            loadWalletData();
            loadWithdrawals();
        });

        function loadWalletData() {
            $.ajax({
                url: '../backend/get_wallet.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status && response.data) {
                        currentBalance = response.data.balance || 0;
                        $('#wallet-balance').text(currentBalance.toLocaleString('en-IN'));
                        $('#available-balance').text(currentBalance.toLocaleString('en-IN'));
                        renderTransactions(response.data.transactions || []);
                    }
                },
                error: function() {
                    $('#wallet-transactions').html('<div class="empty-state"><p>Failed to load wallet data</p></div>');
                }
            });
        }

        function renderTransactions(transactions) {
            if (transactions.length === 0) {
                $('#wallet-transactions').html('<div class="empty-state"><i class="fas fa-receipt"></i><p>No transactions yet</p></div>');
                return;
            }

            let html = '';
            transactions.forEach(tx => {
                const isCredit = tx.amount > 0;
                const sign = isCredit ? '+' : '';
                const colorClass = isCredit ? 'credit' : 'debit';
                const date = tx.created_on ? new Date(tx.created_on).toLocaleDateString('en-IN') : '';

                html += `
                    <div class="transaction-item">
                        <div class="tx-info">
                            <h4>${tx.description || tx.transaction_type}</h4>
                            <p>${date}</p>
                        </div>
                        <div class="tx-amount ${colorClass}">${sign}₹${Math.abs(tx.amount).toFixed(2)}</div>
                    </div>
                `;
            });

            $('#wallet-transactions').html(html);
        }

        function loadWithdrawals() {
            $.ajax({
                url: '../backend/wallet/get_withdrawals.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status && response.data) {
                        renderWithdrawals(response.data);
                    }
                }
            });
        }

        function renderWithdrawals(withdrawals) {
            const pending = withdrawals.filter(w => w.status === 'pending');
            const completed = withdrawals.filter(w => w.status !== 'pending');

            // Pending withdrawals
            if (pending.length > 0) {
                $('#pending-section').show();
                let html = '';
                pending.forEach(w => {
                    const date = new Date(w.created_on).toLocaleDateString('en-IN');
                    const method = w.payment_method === 'upi' ? `UPI: ${w.upi_id}` : `Bank: ${w.account_number_masked}`;
                    
                    html += `
                        <div class="withdrawal-item">
                            <div class="withdrawal-header">
                                <span class="withdrawal-amount">₹${parseFloat(w.amount).toLocaleString('en-IN')}</span>
                                <span class="status-badge status-pending">Pending</span>
                            </div>
                            <div class="withdrawal-details">
                                <div>${method}</div>
                                <div>Requested: ${date}</div>
                            </div>
                            <button class="btn-cancel" onclick="cancelWithdrawal(${w.id})">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    `;
                });
                $('#pending-withdrawals').html(html);
            } else {
                $('#pending-section').hide();
            }

            // Completed withdrawals
            if (completed.length > 0) {
                let html = '';
                completed.forEach(w => {
                    const date = new Date(w.created_on).toLocaleDateString('en-IN');
                    const statusClass = `status-${w.status}`;
                    const method = w.payment_method === 'upi' ? `UPI: ${w.upi_id}` : `Bank: ${w.account_number_masked}`;
                    
                    html += `
                        <div class="withdrawal-item">
                            <div class="withdrawal-header">
                                <span class="withdrawal-amount">₹${parseFloat(w.amount).toLocaleString('en-IN')}</span>
                                <span class="status-badge ${statusClass}">${w.status}</span>
                            </div>
                            <div class="withdrawal-details">
                                <div>${method}</div>
                                <div>${date}</div>
                                ${w.rejection_reason ? `<div style="color: var(--danger);">Reason: ${w.rejection_reason}</div>` : ''}
                            </div>
                        </div>
                    `;
                });
                $('#withdrawal-history').html(html);
            } else {
                $('#withdrawal-history').html('<div class="empty-state"><i class="fas fa-inbox"></i><p>No withdrawal history</p></div>');
            }
        }

        function showRedeemModal() {
            if (currentBalance < 100) {
                alert('Minimum balance of ₹100 required for withdrawal');
                return;
            }
            $('#redeem-amount').val('');
            $('#upi-id').val('');
            $('#bank-name').val('');
            $('#account-number').val('');
            $('#ifsc-code').val('');
            $('#account-holder').val('');
            selectPaymentMethod('upi');
            $('#redeemModal').addClass('active');
        }

        function closeRedeemModal() {
            $('#redeemModal').removeClass('active');
        }

        function showHistory() {
            $('#history-section').toggle();
        }

        function selectPaymentMethod(method) {
            selectedPaymentMethod = method;
            $('.payment-method').removeClass('selected');
            $(`.payment-method[data-method="${method}"]`).addClass('selected');
            
            if (method === 'upi') {
                $('#upi-fields').addClass('active');
                $('#bank-fields').removeClass('active');
            } else {
                $('#bank-fields').addClass('active');
                $('#upi-fields').removeClass('active');
            }
        }

        function submitRedeemRequest() {
            const amount = parseFloat($('#redeem-amount').val()) || 0;
            
            if (amount < 100) {
                alert('Minimum withdrawal amount is ₹100');
                return;
            }
            
            if (amount > currentBalance) {
                alert('Insufficient balance');
                return;
            }

            const data = {
                amount: amount,
                payment_method: selectedPaymentMethod
            };

            if (selectedPaymentMethod === 'upi') {
                data.upi_id = $('#upi-id').val().trim();
                if (!data.upi_id) {
                    alert('Please enter your UPI ID');
                    return;
                }
            } else {
                data.bank_name = $('#bank-name').val().trim();
                data.account_number = $('#account-number').val().trim();
                data.ifsc_code = $('#ifsc-code').val().trim().toUpperCase();
                data.account_holder_name = $('#account-holder').val().trim();

                if (!data.bank_name || !data.account_number || !data.ifsc_code || !data.account_holder_name) {
                    alert('Please fill all bank details');
                    return;
                }
            }

            $('#submit-redeem').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

            $.ajax({
                url: '../backend/wallet/request_withdrawal.php',
                type: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    if (response.status) {
                        alert(response.message);
                        closeRedeemModal();
                        loadWalletData();
                        loadWithdrawals();
                    } else {
                        alert(response.message || 'Request failed');
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                },
                complete: function() {
                    $('#submit-redeem').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Request');
                }
            });
        }

        function cancelWithdrawal(withdrawalId) {
            if (!confirm('Are you sure you want to cancel this withdrawal? The amount will be refunded to your wallet.')) {
                return;
            }

            $.ajax({
                url: '../backend/wallet/cancel_withdrawal.php',
                type: 'POST',
                data: JSON.stringify({ withdrawal_id: withdrawalId }),
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    if (response.status) {
                        alert(response.message);
                        loadWalletData();
                        loadWithdrawals();
                    } else {
                        alert(response.message || 'Failed to cancel');
                    }
                },
                error: function() {
                    alert('Network error');
                }
            });
        }

        // Close modal on overlay click
        $('#redeemModal').on('click', function(e) {
            if (e.target === this) closeRedeemModal();
        });
    </script>
</body>

</html>