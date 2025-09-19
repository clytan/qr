
var userSlabRequestManager = {
    table: null,
    slabFieldMap: [
        { key: 'id', label: 'Id' },
        { key: 'full_name', label: 'Full Name' },
        { key: 'email', label: 'Email' },
        { key: 'phone', label: 'Phone' },
        { key: 'qr_id', label: 'User QR ID' },
        { key: 'current_slab', label: 'Current Slab' },
        { key: 'requested_slab', label: 'Requested Slab' }
    ],

    init: function () {
        this.initDataTable();
        this.renderExportColumnSelectorModal();
        this.bindEvents();
    },

    initDataTable: function () {
        var self = this;

        this.table = $('#userTable').DataTable({
            responsive: true,
            lengthChange: false,
            ajax: {
                url: '../backend/get_user_slab_change_request.php',
                dataSrc: function (json) {
                    if (json.data) {
                        return json.data;
                    } else {
                        alert(json.message || 'Failed to load request data');
                        return [];
                    }
                }
            },
            columns: [
                { data: 'id' },
                { data: 'full_name' },
                { data: 'email' },
                { data: 'phone' },
                { data: 'qr_id' },
                { data: 'current_slab' },
                { data: 'requested_slab' },
                {
                    data: 'id',
                    className: 'text-center',
                    render: function (data, type, row) {
                        return `
                            <button class='btn btn-success btn-sm me-1 action-btn'
                                data-id='${data}'
                                data-user-id='${row.user_id}'
                                data-action='approve'
                                title='Approve'>
                                <i class='fas fa-check'></i>
                            </button>
                            <button class='btn btn-danger btn-sm action-btn'
                                data-id='${data}'
                                data-user-id='${row.user_id}'
                                data-action='reject'
                                title='Reject'>
                                <i class='fas fa-times'></i>
                            </button>`;
                    }
                }
            ],
                        language: {
                                search: "_INPUT_",
                                searchPlaceholder: "Search ...",
                                paginate: {
                                        previous: '<i class="fas fa-angle-left"></i>',
                                        next: '<i class="fas fa-angle-right"></i>'
                                }
                        }
                });

                // Append Export button next to the DataTable search input
                const exportBtnHtml = `
                    <button class="btn btn-sm btn-primary ms-2" id="exportSlabChangeSelectedBtn" type="button">
                            <i class="fas fa-file-export me-1"></i> Export
                    </button>
                `;
                $('#userTable_filter').appendTo('#slabChangeTableControls');
                $('#userTable_filter').append(exportBtnHtml);

                // Bind Export button click event to open the export modal
                $('#exportSlabChangeSelectedBtn').on('click', function () {
                    $('#exportSlabChangeColumnsModal').modal('show');
                });
        },

        renderExportColumnSelectorModal: function () {
                let container = $('#exportSlabChangeColumnSelectorModal');
                container.empty();

                // Add "Select All" checkbox at the top
                container.append(`
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="exportSlabChangeCol_all" checked>
                        <label class="form-check-label" for="exportSlabChangeCol_all"><strong>Select All</strong></label>
                    </div>
                `);

                this.slabFieldMap.forEach((field, idx) => {
                    container.append(`
                        <div class="form-check">
                            <input class="form-check-input export-slabchange-column-checkbox" type="checkbox" id="exportSlabChangeCol_${idx}" value="${field.key}" checked>
                            <label class="form-check-label" for="exportSlabChangeCol_${idx}">${field.label}</label>
                        </div>
                    `);
                });

                // Bind event for Select All checkbox
                $('#exportSlabChangeCol_all').on('change', function () {
                    const checked = $(this).prop('checked');
                    $('.export-slabchange-column-checkbox').prop('checked', checked);
                });

                // Uncheck "Select All" if any individual checkbox unchecked, else check if all checked
                container.on('change', '.export-slabchange-column-checkbox', function () {
                    if (!$(this).prop('checked')) {
                        $('#exportSlabChangeCol_all').prop('checked', false);
                    } else {
                        const allChecked = $('.export-slabchange-column-checkbox').length === $('.export-slabchange-column-checkbox:checked').length;
                        $('#exportSlabChangeCol_all').prop('checked', allChecked);
                    }
        });
    },

    bindEvents: function () {
        var self = this;

        // Export modal toggle
        $('#exportSlabChangeSelectedBtn').on('click', function () {
            $('#exportSlabChangeColumnsModal').modal('show');
        });

        // Export CSV on modal confirm button click
        $('#exportSlabChangeSelectedBtnModal').on('click', function () {
            self.exportSelectedColumns();
            $('#exportSlabChangeColumnsModal').modal('hide');
        });

        $('#userTable tbody').on('click', '.action-btn', function () {
            const requestId = $(this).data('id');
            const userId = $(this).data('user-id');
            const action = $(this).data('action');
            const statusText = action === 'approve' ? 'Approve' : 'Reject';
            const swalIcon = action === 'approve' ? 'question' : 'warning';
            const adminId = $('#admin_user_id').val();

            Swal.fire({
                title: `Confirm ${statusText}`,
                input: 'textarea',
                inputLabel: 'Comment',
                inputPlaceholder: 'Enter your comment here...',
                inputAttributes: {
                    'aria-label': 'Comment'
                },
                inputValidator: (value) => {
                    if (!value.trim()) {
                        return 'Comment is required!';
                    }
                },
                showCancelButton: true,
                confirmButtonText: `Yes, ${statusText}`,
                cancelButtonText: 'Cancel',
                icon: swalIcon,
                preConfirm: (comment) => {
                    return fetch('../backend/save_updated_user_slab.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: requestId,
                            user_id: userId,
                            status: action === 'approve' ? 'Approved' : 'Rejected',
                            comment: comment,
                            admin_id:adminId
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (!data.success) {
                                throw new Error(data.message || 'Something went wrong');
                            }
                            return data;
                        })
                        .catch(error => {
                            Swal.showValidationMessage(error.message);
                        });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: `${statusText}d!`,
                        text: `The request has been ${statusText.toLowerCase()}d.`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    self.table.ajax.reload(null, false);
                }
            });
        });
    },

    exportSelectedColumns: function () {
        let self = this;

        // Get checked columns from modal
        let selectedKeys = [];
        $('.export-slabchange-column-checkbox:checked').each(function () {
            selectedKeys.push($(this).val());
        });

        if (selectedKeys.length === 0) {
            alert('Please select at least one column to export.');
            return;
        }

        // Get filtered table data (currently visible rows)
        let data = this.table.rows({ search: 'applied' }).data().toArray();

        // Prepare CSV headers
        let headerLabels = [];
        selectedKeys.forEach(key => {
            let field = self.slabFieldMap.find(f => f.key === key);
            if (field) headerLabels.push(field.label);
        });

        // Build CSV rows
        let csvRows = [];
        csvRows.push(headerLabels.join(','));

        data.forEach(row => {
            let rowData = [];
            selectedKeys.forEach(key => {
                let val = row[key];
                if (typeof val === 'string') {
                    val = val.replace(/"/g, '""'); // Escape quotes
                    if (val.search(/("|,|\n)/g) >= 0) {
                        val = `"${val}"`; // Wrap in quotes if contains comma, newline or quote
                    }
                }
                rowData.push(val);
            });
            csvRows.push(rowData.join(','));
        });

        // Create CSV file and trigger download
        let csvString = csvRows.join('\n');
        let blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
        let url = URL.createObjectURL(blob);
        let a = document.createElement('a');
        a.href = url;
        a.download = 'slab_change_export.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
};

$(document).ready(function () {
    userSlabRequestManager.init();
});
