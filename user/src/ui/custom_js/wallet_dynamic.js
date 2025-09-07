$(document).ready(function () {
    // You may want to get user_id from session or a JS variable in production
    $.ajax({
        url: '../backend/get_wallet.php',
        type: 'GET',
        dataType: 'json',
        success: function (response) {
            if (response.status && response.data) {
                // Update balance
                var balance = response.data.balance;
                $("#wallet-balance").text(balance.toLocaleString('en-US'));

                // Update transactions
                var txHtml = '';
                if (response.data.transactions.length === 0) {
                    txHtml = '<li style="color:#aaa;">No transactions found.</li>';
                } else {
                    response.data.transactions.forEach(function (tx) {
                        var sign = tx.amount > 0 ? '+' : '-';
                        var color = tx.amount > 0 ? '#00ff99' : '#ff4b5c';
                        var dateStr = '';
                        if (tx.created_on) {
                            var d = new Date(tx.created_on.replace(' ', 'T'));
                            dateStr = d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) + ' ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                        }
                        var iconClass = tx.amount > 0 ? 'fas fa-coins' : 'fas fa-arrow-down'; // FontAwesome 5+
                        var iconBg = tx.amount > 0 ? '#1dd1a1' : '#ff4b5c';
                        txHtml += '<li class="d-flex justify-content-between align-items-center mb-3" style="background:#23263b;border-radius:12px;padding:14px 18px;box-shadow:0 1px 6px rgba(79,84,200,0.07);">';
                        txHtml += '<div class="d-flex align-items-center">';
                        txHtml += '<span class="rounded-circle d-flex align-items-center justify-content-center" style="min-width:40px;width:40px;height:40px;background:' + iconBg + ';margin-right:16px;"><i class="' + iconClass + ' text-white" style="font-size:1.5rem;min-width:24px;"></i></span>';
                        txHtml += '<div>';
                        txHtml += '<div style="color:#fff; font-weight:500;">' + (tx.description || tx.transaction_type) + '</div>';
                        txHtml += '<div style="font-size:0.9rem; color:#aaa;">' + (dateStr ? dateStr : '-') + '</div>';
                        txHtml += '</div></div>';
                        txHtml += '<div style="color:' + color + '; font-weight:700; font-size:1.1rem;">' + sign + Math.abs(tx.amount).toFixed(2) + ' Coins</div>';
                        txHtml += '</li>';
                    });
                }
                $("#wallet-transactions").html(txHtml);
            }
        },
        error: function () {
            $("#wallet-balance").text('Error');
            $("#wallet-transactions").html('<li style="color:#ff4b5c;">Failed to load transactions.</li>');
        }
    });
});
