document.addEventListener('DOMContentLoaded', function() {
    const invoiceBox = document.getElementById('invoice-box');
    if (!invoiceBox) return;

    fetch('../backend/get_invoice.php')
        .then(response => response.json())
        .then(data => {
            if (!data.status) {
                invoiceBox.innerHTML = `<div style='padding:40px;text-align:center;'><h3>No invoice found for your account.</h3></div>`;
                return;
            }
            const invoice = data.invoice;
            const billed = data.billed_to;
            invoiceBox.innerHTML = `
                <div class="invoice-header">
                    <div>
                        <div class="invoice-title">ZQR.com</div>
                        <div>123 Main Street, Mumbai, MH 400001</div>
                        <div>support@zqr.com</div>
                        <div>+91-9876543210</div>
                    </div>
                    <div class="text-right">
                        <div style="font-size:1.2rem;font-weight:600;">Invoice #${invoice.invoice_number}
                            <span class="badge-paid">${invoice.status}</span>
                        </div>
                        <div class="mt-2">Invoice Date:<br><span style="font-weight:400;">${formatDate(invoice.created_on)}</span></div>
                        <div class="mt-2">Order No:<br><span style="font-weight:400;">#${invoice.order_number}</span></div>
                    </div>
                </div>
                <div class="invoice-section">
                    <div style="font-weight:600;">Billed To:</div>
                    <div style="font-size:1.1rem;">${billed.name || ''}</div>
                    <div>${billed.address || ''}</div>
                    <div>${billed.email || ''}</div>
                    <div>${billed.phone || ''}</div>
                </div>
                <div class="order-summary">
                    <div style="font-weight:600;font-size:1.1rem;margin-bottom:8px;">Order Summary</div>
                    <table>
                        <thead>
                            <tr>
                                <th class="text-center">No.</th>
                                <th>Item</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${invoice.items.map((item, i) => `
                                <tr>
                                    <td class="text-center">${String(i+1).padStart(2, '0')}</td>
                                    <td><strong>${item.name}</strong><br><span style="font-size:0.95em;color:#888;">${item.desc}</span></td>
                                    <td>₹ ${item.price.toFixed(2)}</td>
                                    <td class="text-center">${item.qty}</td>
                                    <td>₹ ${item.total.toFixed(2)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                        <tfoot>
                            <tr><td colspan="4" class="text-right">Sub Total</td><td>₹ ${invoice.amount.toFixed(2)}</td></tr>
                            <tr><td colspan="4" class="text-right">Discount</td><td>- ₹ ${invoice.discount.toFixed(2)}</td></tr>
                            <tr><td colspan="4" class="text-right">Shipping Charge</td><td>₹ ${invoice.shipping.toFixed(2)}</td></tr>
                            <tr><td colspan="4" class="text-right">CGST (9%)</td><td>₹ ${invoice.cgst.toFixed(2)}</td></tr>
                            <tr><td colspan="4" class="text-right">SGST (9%)</td><td>₹ ${invoice.sgst.toFixed(2)}</td></tr>
                            <tr><td colspan="4" class="text-right">IGST (0%)</td><td>₹ ${invoice.igst.toFixed(2)}</td></tr>
                            <tr><td colspan="4" class="text-right">Total GST</td><td>₹ ${invoice.gst_total.toFixed(2)}</td></tr>
                            <tr><td colspan="4" class="text-right" style="font-size:1.2rem;">Total</td><td style="font-size:1.2rem;">₹ ${invoice.total_amount.toFixed(2)}</td></tr>
                        </tfoot>
                    </table>
                </div>
                <div class="mt-4 text-right">
                    <button class="btn btn-secondary">Send</button>
                    <button class="btn"><i class="fa fa-download"></i> Download</button>
                </div>
            `;
        })
        .catch(() => {
            invoiceBox.innerHTML = `<div style='padding:40px;text-align:center;'><h3>Error loading invoice.</h3></div>`;
        });

    function formatDate(dateStr) {
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
    }
});
