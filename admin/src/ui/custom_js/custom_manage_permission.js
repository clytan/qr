var permissionManager = {
    users: [],
    pages: [],
    userUrls: [],
    permissionFieldMap: [
        { key: 'user_name', label: 'User' },
        { key: 'email', label: 'Email' },
        { key: 'phone', label: 'Phone' },
        { key: 'role', label: 'Role' },
        { key: 'allowed', label: 'Allowed Pages' }
    ],

    init: function () {
        this.fetchInitialData();
        this.initSelect2();
        this.bindEvents();
    },

    fetchInitialData: function () {
        var self = this;

        $.getJSON('../backend/get_admin_users.php', function (res) {
            if (res.success) {
                self.users = res.users || [];
                self.pages = res.pages || [];
                self.userUrls = res.userUrls || [];

                $('#hidden_users').val(JSON.stringify(self.users));
                $('#hidden_pages').val(JSON.stringify(self.pages));
                $('#hidden_user_urls').val(JSON.stringify(self.userUrls));

                self.renderTable();
            } else {
                alert('Failed to load data: ' + (res.error || 'Unknown error'));
            }
        });
    },

    renderTable: function () {
        var self = this;

        var tableData = this.userUrls.map(function (row) {
            var allowed = row.allowed_urls
                ? row.allowed_urls.split(',').map(function (id) {
                    var page = self.pages.find(p => p.id == id);
                    return page ? page.page_name : id;
                }).join(', ')
                : '';

            return {
                user_name: row.user_name || row.use_name,
                email: row.email,
                phone: row.phone,
                role: row.role,
                allowed: allowed,
                actions: `<button class='btn btn-sm btn-primary edit-btn' data-id='${row.user_id}'>Edit</button>`
            };
        });

        if ($.fn.DataTable.isDataTable('#permissionTable')) {
            var table = $('#permissionTable').DataTable();
            table.clear().rows.add(tableData).draw();
        } else {
            $('#permissionTable').DataTable({
                data: tableData,
                columns: [
                    { title: 'User', data: 'user_name' },
                    { title: 'Email', data: 'email' },
                    { title: 'Phone', data: 'phone' },
                    { title: 'Role', data: 'role' },
                    { title: 'Allowed Pages', data: 'allowed' },
                    { title: 'Actions', data: 'actions' }
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

            // Append search and export button
            const exportBtnHtml = `
                <button class="btn btn-sm btn-primary ms-2" id="exportPermissionsSelectedBtn" type="button">
                    <i class="fas fa-file-export me-1"></i> Export
                </button>
            `;
            $('#permissionTable_filter').appendTo('#permissionTableControls');
            $('#permissionTable_filter').append(exportBtnHtml);

            $('#exportPermissionsSelectedBtn').on('click', function () {
                permissionManager.renderExportColumnSelectorModal();
                $('#exportPermissionsColumnsModal').modal('show');
            });

            $(document).off('click', '#exportPermissionsSelectedBtnModal').on('click', '#exportPermissionsSelectedBtnModal', function () {
                permissionManager.exportSelectedPermissionsColumns();
                $('#exportPermissionsColumnsModal').modal('hide');
            });
        }
    },

    renderExportColumnSelectorModal: function () {
        var container = $('#exportPermissionsColumnSelectorModal');
        container.empty();

        container.append(`
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="exportPermissionsCol_all" checked>
                <label class="form-check-label" for="exportPermissionsCol_all"><strong>Select All</strong></label>
            </div>
        `);

        this.permissionFieldMap.forEach((field, idx) => {
            container.append(`
                <div class="form-check">
                    <input class="form-check-input export-permissions-column-checkbox" type="checkbox" id="exportPermissionsCol_${idx}" value="${field.key}" checked>
                    <label class="form-check-label" for="exportPermissionsCol_${idx}">${field.label}</label>
                </div>
            `);
        });

        $('#exportPermissionsCol_all').on('change', function () {
            const checked = $(this).prop('checked');
            $('.export-permissions-column-checkbox').prop('checked', checked);
        });

        container.on('change', '.export-permissions-column-checkbox', function () {
            const allChecked = $('.export-permissions-column-checkbox').length === $('.export-permissions-column-checkbox:checked').length;
            $('#exportPermissionsCol_all').prop('checked', allChecked);
        });
    },

    exportSelectedPermissionsColumns: function () {
        var selectedKeys = [];
        $('.export-permissions-column-checkbox:checked').each(function () {
            selectedKeys.push($(this).val());
        });

        if (selectedKeys.length === 0) {
            alert('Please select at least one column to export.');
            return;
        }

        var data = $('#permissionTable').DataTable().rows({ search: 'applied' }).data().toArray();
        var headerLabels = selectedKeys.map(key => {
            var field = permissionManager.permissionFieldMap.find(f => f.key === key);
            return field ? field.label : key;
        });

        var csvRows = [headerLabels.join(',')];
        data.forEach(row => {
            var rowData = selectedKeys.map(key => {
                var val = row[key];
                if (typeof val === 'string') {
                    val = val.replace(/"/g, '""');
                    if (val.search(/("|,|\n)/g) >= 0) {
                        val = `"${val}"`;
                    }
                }
                return val;
            });
            csvRows.push(rowData.join(','));
        });

        var csvString = csvRows.join('\n');
        var blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'permissions_export.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    },

    bindEvents: function () {
        var self = this;

        $('#addUserBtn').on('click', function () {
            $('#userModalLabel').text('Add User Permissions');
            $('#user_select').empty();

            $.getJSON('../backend/get_admin_users_without_urls.php', function (res) {
                if (res.data && res.data.length > 0) {
                    res.data.forEach(function (u) {
                        $('#user_select').append(`<option value='${u.id}'>${u.user_name}</option>`);
                    });
                }
                $('#user_select').val(null).trigger('change');
            });

            $('#allowed_urls_select').empty();
            self.pages.forEach(function (p) {
                $('#allowed_urls_select').append(`<option value='${p.id}'>${p.page_name}</option>`);
            });

            $('#allowed_urls_select').val(null).trigger('change');
            $('#user_select').prop('disabled', false);
            $('#userModal').modal('show');
            $('#saveUserPerms').data('edit', false);
        });

        $('#permissionTable').on('click', '.edit-btn', function () {
            var userId = $(this).data('id');
            var row = self.userUrls.find(u => u.user_id == userId);
            var userName = row.user_name || row.use_name || '';

            $('#userModalLabel').text('Edit User Permissions');
            $('#user_select').empty().append(`<option value='${row.user_id}'>${userName}</option>`).val(row.user_id).trigger('change');
            $('#user_select').prop('disabled', true);

            $('#allowed_urls_select').empty();
            self.pages.forEach(function (p) {
                $('#allowed_urls_select').append(`<option value='${p.id}'>${p.page_name}</option>`);
            });

            if (row.allowed_urls) {
                var arr = row.allowed_urls.split(',');
                $('#allowed_urls_select').val(arr).trigger('change');
            } else {
                $('#allowed_urls_select').val(null).trigger('change');
            }

            $('#userModal').modal('show');
            $('#saveUserPerms').data('edit', row.user_id);
        });

        $('#saveUserPerms').on('click', function () {
            var user_id = $('#user_select').val();
            var allowed_urls = $('#allowed_urls_select').val() || [];

            $.ajax({
                url: '../backend/save_admin_user_urls.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ user_id, allowed_urls: allowed_urls.join(',') }),
                success: function (res) {
                    if (res.success) {
                        $.getJSON('../backend/get_admin_user_urls.php', function (userUrlsRes) {
                            self.userUrls = userUrlsRes.data;
                            $('#hidden_user_urls').val(JSON.stringify(self.userUrls));
                            self.renderTable();
                            $('#userModal').modal('hide');
                            self.showStatusMsg(res.message || 'Permissions saved successfully!', 'success');
                        });
                    } else {
                        self.showStatusMsg(res.error || 'Failed to save!', 'error');
                    }
                },
                error: function () {
                    self.showStatusMsg('Server error. Please try again.', 'error');
                }
            });
        });
    },

    showStatusMsg: function (msg, type) {
        if (type === 'success') {
            alert(msg);
        } else {
            alert('Error: ' + msg);
        }
    },

    initSelect2: function () {
        $('#allowed_urls_select').select2({
            width: '100%',
            placeholder: 'Select allowed pages',
            dropdownParent: $('#userModal')
        });

        $('#user_select').select2({
            width: '100%',
            placeholder: 'Select user',
            dropdownParent: $('#userModal')
        });
    }
};

$(document).ready(function () {
    permissionManager.init();
});
