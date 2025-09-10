var userSlabRequestManager = {
    table: null,

    init: function () {
        this.initDataTable();
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
                searchPlaceholder: "Search ...",
                paginate: {
                    previous: '<i class="fas fa-angle-left"></i>',
                    next: '<i class="fas fa-angle-right"></i>'
                }
            }
        });
    },

    bindEvents: function () {
        var self = this;

        $('#userTable tbody').on('click', '.action-btn', function () {
            const requestId = $(this).data('id');
            const userId = $(this).data('user-id');
            const action = $(this).data('action');
            const statusText = action === 'approve' ? 'Approve' : 'Reject';
            const swalIcon = action === 'approve' ? 'question' : 'warning';

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
                            comment: comment
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
    }
};

$(document).ready(function () {
    userSlabRequestManager.init();
});
