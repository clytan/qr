var orderManger = {
    table: null,

    init: function () {
        this.initDataTable();
        this.bindEvents();
    },

    initDataTable: function () {
        var self = this;

        this.table = $('#ordersTable').DataTable({
            ajax: {
                url: '../backend/get_user_orders.php',
                dataSrc: function (json) {
                    if (json.status) {
                        return json.data;
                    } else {
                        alert(json.message || 'Failed to load orders data');
                        return [];
                    }
                },
                error: function (xhr, status, error) {
                    console.error('DataTable AJAX error:', error);
                    alert('Error loading order data. Please try again later.');
                }
            },
            columns: [
                {
                    data: 'id',
                    render: function (data, type, row) {
                        return `
                            <a href="#"
                                class="open-invoice-modal"
                                data-id="${data}"
                                data-user-id="${row.user_id}"
                                data-invoice-number="${row.invoice_number}"
                                data-invoice-type="${row.invoice_type}"
                                data-amount="${row.amount}"
                                data-cgst="${row.cgst}"
                                data-sgst="${row.sgst}"
                                data-igst="${row.igst}"
                                data-gst-total="${row.gst_total}"
                                data-total-amount="${row.total_amount}"
                                data-status="${row.status}"
                                data-payment-mode="${row.payment_mode}"
                                data-payment-reference="${row.payment_reference}"
                                data-full-name="${row.full_name}"
                                data-email="${row.email}"
                                data-phone="${row.phone}"
                                data-invoice-date="${row.created_on || ''}"
                            >
                                ${data}
                            </a>`;
                    }
                },
                { data: 'full_name' },
                { data: 'email' },
                { data: 'phone' },
                { data: 'qr_id' },
                { data: 'invoice_type' },
                { data: 'invoice_number' },
                { data: 'total_amount' },
                { data: 'status' }
            ],
            responsive: true,
            pageLength: 8,
            lengthChange: false,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search users...",
                paginate: {
                    previous: '<i class="fas fa-angle-left"></i>',
                    next: '<i class="fas fa-angle-right"></i>'
                }
            },
            columnDefs: [
                { className: "align-middle", targets: "_all" }
            ]
        });
    },

    bindEvents: function () {
        var self = this;

        // Open modal on ID click
        $('#ordersTable tbody').on('click', 'a.open-invoice-modal', function (e) {
            e.preventDefault();

            const modalData = $(this).data();

            $.ajax({
                url: '../ui/admin_invoice.php',
                type: 'POST',
                data: modalData,
                success: function (response) {
                    try {
                        const modalContainer = $('<div/>').html(response);
                        $('body').append(modalContainer);

                        const modal = new bootstrap.Modal(modalContainer.find('.modal'));
                        modal.show();

                        // Fill modal data
                        modalContainer.find('#invoiceNumber').text(modalData.invoiceNumber);
                        modalContainer.find('#invoiceType').text(modalData.invoiceType);
                        modalContainer.find('#cgst').text(modalData.cgst);
                        modalContainer.find('#sgst').text(modalData.sgst);
                        modalContainer.find('#igst').text(modalData.igst);
                        modalContainer.find('#gstTotal').text(modalData.gstTotal);
                        modalContainer.find('#totalAmount').text(modalData.totalAmount);
                        modalContainer.find('#paymentMode').text(modalData.paymentMode);
                        modalContainer.find('#paymentReference').text(modalData.paymentReference);
                        modalContainer.find('#invoiceDate').text(modalData.invoiceDate || 'N/A');

                        modalContainer.find('#customerDetails').html(`
                            <strong>Name:</strong> ${modalData.fullName || ''}<br>
                            <strong>Email:</strong> ${modalData.email || ''}<br>
                            <strong>Phone:</strong> ${modalData.phone || ''}
                        `);

                        if (modalData.status === 'Paid') {
                            modalContainer.find('.invoice-status').html('<span class="badge-paid">Paid</span>');
                        } else {
                            modalContainer.find('.invoice-status').html('<span class="badge bg-danger">Unpaid</span>');
                        }

                        modalContainer.on('hidden.bs.modal', function () {
                            modalContainer.remove();
                        });

                    } catch (err) {
                        console.error('Error processing invoice modal:', err);
                        alert('Something went wrong while opening the invoice. Please try again.');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error loading invoice modal:', error);
                    alert('Unable to load invoice details. Please check your network and try again.');
                }
            });
        });

        // Print Invoice
        $(document).on('click', '#printInvoice', function () {
            try {
                const printContents = document.querySelector('.invoice-box').outerHTML;
                const printWindow = window.open('', '', 'height=600,width=800');

                if (!printWindow) throw new Error('Pop-up blocked or failed to open print window.');

                printWindow.document.head.innerHTML = `
                    <title>Invoice</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .invoice-box {
                            background: #fff;
                            border-radius: 8px;
                            padding: 32px 24px;
                            margin: 32px 0;
                            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
                            font-size: 16px;
                        }
                    </style>
                `;

                printWindow.document.body.innerHTML = printContents;
                printWindow.focus();

                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                }, 500);

            } catch (err) {
                console.error('Print error:', err);
                alert('Failed to print invoice. Please try again or check browser settings.');
            }
        });
    }
};

$(document).ready(function () {
    orderManger.init();
});
