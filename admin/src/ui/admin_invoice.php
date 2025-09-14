<!-- Invoice Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1" role="dialog" aria-labelledby="invoiceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="invoiceModalLabel">Invoice</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="invoice-box">
          <div class="invoice-header">
            <div class="invoice-title">Invoice</div>
            <div class="invoice-status">
              <span class="badge-paid">Paid</span>
            </div>
          </div>

          <div class="invoice-section mt-4">
            <strong>Customer Details:</strong>
            <p id="customerDetails">Loading...</p>
          </div>

          <div class="invoice-section">
            <strong>Invoice Number:</strong> <span id="invoiceNumber">#...</span><br>
            <strong>Date:</strong> <span id="invoiceDate">...</span><br>
            <strong>Payment Mode:</strong> <span id="paymentMode">...</span><br>
            <strong>Payment Reference:</strong> <span id="paymentReference">...</span>
          </div>

          <div class="order-summary mt-4">
            <table>
              <thead>
                <tr>
                  <th>Description</th>
                  <th>CGST</th>
                  <th>SGST</th>
                  <th>IGST</th>
                  <th>GST Total</th>
                  <th>Total Amount</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td id="invoiceType">...</td>
                  <td id="cgst">0</td>
                  <td id="sgst">0</td>
                  <td id="igst">0</td>
                  <td id="gstTotal">0</td>
                  <td id="totalAmount">0</td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times-circle me-1"></i> Close
        </button>
        <button type="button" class="btn btn-success" id="printInvoice">
          <i class="fas fa-print me-1"></i> Print
        </button>
      </div>

    </div>
  </div>
</div>

<style>
    .invoice-box {
        background: #fff;
        border-radius: 8px;
        padding: 32px 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
        font-size: 16px;
    }

    .invoice-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        border-bottom: 1px solid #eee;
        padding-bottom: 16px;
    }

    .invoice-title {
        font-size: 2rem;
        font-weight: 700;
        color: #333;
    }

    .invoice-status {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .badge-paid {
        background: #27ae60;
        color: #fff;
        border-radius: 4px;
        padding: 4px 12px;
        font-size: 1rem;
    }

    .invoice-section {
        margin-top: 24px;
    }

    .invoice-section strong {
        font-size: 1.1rem;
    }

    .order-summary {
        margin-top: 32px;
    }

    .order-summary table {
        width: 100%;
        border-collapse: collapse;
    }

    .order-summary th,
    .order-summary td {
        border-bottom: 1px solid #eee;
        padding: 8px 6px;
        text-align: left;
    }

    .order-summary th {
        background: #fafafa;
    }

    .order-summary tfoot td {
        font-weight: 600;
    }

    .text-right {
        text-align: right;
    }

    .text-center {
        text-align: center;
    }

    .mt-2 {
        margin-top: 12px;
    }

    .mt-4 {
        margin-top: 24px;
    }

    .btn {
        display: inline-block;
        padding: 8px 18px;
        border-radius: 4px;
        background: #27ae60;
        color: #fff;
        border: none;
        font-size: 1rem;
        cursor: pointer;
        margin-right: 8px;
    }

    .btn-secondary {
        background: #3498db;
    }
</style>
