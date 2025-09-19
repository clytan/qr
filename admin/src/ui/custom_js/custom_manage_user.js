var userManager = {
  slabOptions: [],
  userTypeOptions: [],
  currentUser: null,
  isEditing: false,
  table: null,
  fieldMap: [
    { display: "#modalFullNameDisplay", input: "#modalFullNameInput", key: "user_full_name", label: "Full Name" },
    { display: "#modalPhoneDisplay", input: "#modalPhoneInput", key: "user_phone", label: "Phone" },
    { display: "#modalEmailDisplay", input: "#modalEmailInput", key: "user_email", label: "Email" },
    { display: "#modalAddressDisplay", input: "#modalAddressInput", key: "user_address", label: "Address" },
    { display: "#modalUserTypeDisplay", input: "#modalUserTypeInput", key: "user_user_type", label: "User Type" },
    { display: "#modalQrIdDisplay", input: "#modalQrIdInput", key: "user_qr_id", label: "QR ID" },
    { display: null, input: null, key: "created_by", label: "Created By" },
    { display: null, input: null, key: "created_on", label: "Created On" },
    { display: "#modalEndDateDisplay", input: "#modalEndDateInput", key: "sub_end_date", label: "Subscription End Date" },
    { display: "#modalSlabIdDisplay", input: "#modalSlabIdInput", key: "user_slab_id", label: "Slab ID" },
    { display: "#modalEmailVerifiedDisplay", input: "#modalEmailVerified", key: "user_email_verified", label: "Email Verified" },
    { display: "#modalReferredByDisplay", input: "#modalReferredByInput", key: "referred_by_user_id", label: "Referred By" },
    { display: "#modalTagDisplay", input: "#modalTagInput", key: "user_tag", label: "Tag" },
    { display: null, input: null, key: "updated_by", label: "Updated By" },
    { display: null, input: null, key: "updated_on", label: "Updated On" }
  ],

  init: function () {
    this.loadDropdownOptions();
    this.initDataTable();
    this.renderExportColumnSelectorModal();
    this.bindEvents();
  },

  loadDropdownOptions: function () {
    var self = this;
    $.getJSON('../backend/get_user_form_options.php')
      .done(function (data) {
        if (data.status) {
          self.slabOptions = data.slabs || [];
          self.userTypeOptions = data.types || [];
        } else {
          alert('Failed to load options: ' + (data.message || 'Unknown error'));
        }
      })
      .fail(function (jqxhr, textStatus, error) {
        alert('Error loading options: ' + error);
      });
  },

  initDataTable: function () {
    var self = this;
    let columns = [
      {
        data: 'id',
        render: function (data, type, row) {
          return `<a href="#" class="user-id">${data}</a>`;
        },
        title: "ID"
      }
    ];

    this.fieldMap.forEach(field => {
      let col = {
        data: field.key,
        title: field.label,
        render: function (data, type, row) {
          if (field.key === 'user_user_type') {
            let typeOption = self.userTypeOptions.find(o => o.id == data || o.user_type_name === data);
            return typeOption ? typeOption.user_type_name : data || '';
          } else if (field.key === 'user_slab_id') {
            let slabOption = self.slabOptions.find(o => o.id == data || o.name === data);
            return slabOption ? slabOption.name : data || '';
          } else if (field.key === 'user_email_verified') {
            return (data == 1 || data === true || data === 'true') ? 'Yes' : 'No';
          } else if (field.key === 'sub_end_date') {
            if (data && data !== '0000-00-00') {
              let date = new Date(data);
              if (!isNaN(date.getTime())) {
                return date.toISOString().split('T')[0];
              }
            }
            return '';
          } else if (field.key === 'created_on' || field.key === 'updated_on') {
            if (data && data !== '0000-00-00 00:00:00') {
              let date = new Date(data);
              if (!isNaN(date.getTime())) {
                return date.toISOString().replace('T', ' ').substring(0, 19);
              }
            }
            return '';
          }
          return data || '';
        }
      };
      columns.push(col);
    });

    this.table = $('#userTable').DataTable({
      ajax: {
        url: '../backend/get_users_details.php',
        dataSrc: function (json) {
          if (json.status) {
            return json.data || [];
          } else {
            alert(json.message || 'Failed to load user data');
            return [];
          }
        }
      },
      columns: columns,
      responsive: true,
      pageLength: 5,
      lengthChange: false,
      language: {
        search: "_INPUT_",
        searchPlaceholder: "Search users...",
        paginate: {
          previous: '<i class="fas fa-angle-left"></i>',
          next: '<i class="fas fa-angle-right"></i>'
        }
      },
      columnDefs: [{ className: "align-middle", targets: "_all" }]
    });

    // Append Export button next to the DataTable search input
    const exportBtnHtml = `
      <button class="btn btn-sm btn-primary ms-2" id="exportSelectedBtn" type="button">
          <i class="fas fa-file-export me-1"></i> Export
      </button>
    `;
    $('#userTable_filter').appendTo('#userTableControls');
    $('#userTable_filter').append(exportBtnHtml);

    // Bind Export button click event to open the export modal
    $('#exportSelectedBtn').on('click', function () {
      $('#exportColumnsModal').modal('show');
    });
  },

  renderExportColumnSelectorModal: function () {
    let container = $('#exportColumnSelectorModal');
    container.empty();

    // Add "Select All" checkbox at the top
    container.append(`
      <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" id="exportCol_all" checked>
        <label class="form-check-label" for="exportCol_all"><strong>Select All</strong></label>
      </div>
    `);

    this.fieldMap.forEach((field, idx) => {
      container.append(`
        <div class="form-check">
          <input class="form-check-input export-column-checkbox" type="checkbox" id="exportCol_${idx}" value="${field.key}" checked>
          <label class="form-check-label" for="exportCol_${idx}">${field.label}</label>
        </div>
      `);
    });

    // Bind event for Select All checkbox
    $('#exportCol_all').on('change', function () {
      const checked = $(this).prop('checked');
      $('.export-column-checkbox').prop('checked', checked);
    });

    // Uncheck "Select All" if any individual checkbox unchecked, else check if all checked
    container.on('change', '.export-column-checkbox', function () {
      if (!$(this).prop('checked')) {
        $('#exportCol_all').prop('checked', false);
      } else {
        const allChecked = $('.export-column-checkbox').length === $('.export-column-checkbox:checked').length;
        $('#exportCol_all').prop('checked', allChecked);
      }
    });
  },

  bindEvents: function () {
    var self = this;

    // Open modal on user id click
    $('#userTable tbody').on('click', '.user-id', function (e) {
      e.preventDefault();
      const rowData = self.table.row($(this).parents('tr')).data();
      if (!rowData) return;
      self.currentUser = rowData;
      self.fillModalFields();
      $('#userModal').modal('show');
    });

    $('#editProfileBtn').on('click', function () {
      self.toggleEditMode();
    });

    $('#saveProfileBtn').on('click', function () {
      self.saveProfile();
    });

    // Export modal toggle
    $('#exportSelectedBtn').on('click', function () {
      $('#exportColumnsModal').modal('show');
    });

    // Export CSV on modal confirm button click
    $('#exportSelectedBtnModal').on('click', function () {
      self.exportSelectedColumns();
      $('#exportColumnsModal').modal('hide');
    });
  },

  fillModalFields: function () {
    var self = this;
    this.fieldMap.forEach(({ display, input, key }) => {
      let value = self.currentUser[key] || '';
      if (key === 'user_user_type') {
        $(input).empty();
        self.userTypeOptions.forEach(opt => {
          const isSelected = opt.user_type_name === value || opt.id == value;
          $(input).append(`<option value="${opt.id}" ${isSelected ? 'selected' : ''}>${opt.user_type_name}</option>`);
        });
        $(display).text($(input).find('option:selected').text());
      } else if (key === 'user_slab_id') {
        $(input).empty();
        self.slabOptions.forEach(opt => {
          const isSelected = opt.name === value || opt.id == value;
          $(input).append(`<option value="${opt.id}" ${isSelected ? 'selected' : ''}>${opt.name}</option>`);
        });
        $(display).text($(input).find('option:selected').text());
      } else if (key === 'user_email_verified') {
        const isVerified = self.currentUser[key] == 1 || self.currentUser[key] === true || self.currentUser[key] === 'true';
        $(input).prop('checked', isVerified);
        $(display).text(isVerified ? 'Yes' : 'No');
      } else if (key === 'sub_end_date') {
        if (value && value !== '0000-00-00') {
          const date = new Date(value);
          if (!isNaN(date.getTime())) {
            const formattedDate = date.toISOString().split('T')[0];
            $(input).val(formattedDate);
            $(display).text(formattedDate);
          } else {
            $(input).val('');
            $(display).text('');
          }
        } else {
          $(input).val('');
          $(display).text('');
        }
      } else {
        $(input).val(value);
        $(display).text(value);
      }
    });

    const imagePath = self.currentUser.user_image_path || 'https://via.placeholder.com/100';
    $('#profileImage').attr('src', imagePath);
  },

  toggleEditMode: function () {
    this.isEditing = !this.isEditing;
    this.fieldMap.forEach(({ display, input }) => {
      $(display).toggleClass('d-none', this.isEditing);
      $(input).toggleClass('d-none', !this.isEditing);
    });
    $('#saveProfileBtn').toggleClass('d-none', !this.isEditing);
    $('#editProfileBtn').html(
      this.isEditing
        ? '<i class="fas fa-times me-1"></i> Cancel'
        : '<i class="fas fa-edit me-1"></i> Edit Profile'
    );
  },

  saveProfile: function () {
    var self = this;
    this.fieldMap.forEach(({ display, input, key }) => {
      let displayValue = '';
      if (key === 'user_user_type' || key === 'user_slab_id') {
        displayValue = $(input).find('option:selected').text();
      } else if (key === 'user_email_verified') {
        displayValue = $(input).prop('checked') ? 'Yes' : 'No';
      } else if (key === 'sub_end_date') {
        const rawDate = $(input).val();
        const date = new Date(rawDate);
        if (rawDate && !isNaN(date.getTime())) {
          displayValue = date.toISOString().split('T')[0];
        } else {
          displayValue = '';
        }
      } else {
        displayValue = $(input).val();
      }
      $(display).text(displayValue);
    });

    this.toggleEditMode();

    const adminId = $('#admin_user_id').val();
    const requestData = Object.assign(
      { id: this.currentUser.id, admin_id: adminId },
      this.getUpdatedData()
    );

    $.ajax({
      url: '../backend/save_user_details.php',
      method: 'POST',
      data: requestData,
      dataType: 'json',
      success: function (res) {
        if (res.status) {
          alert(res.message || 'User updated successfully');
          self.table.ajax.reload(null, false);
        } else {
          alert('Update failed: ' + (res.message || 'Unknown error'));
        }
      },
      error: function (xhr, status, error) {
        alert('An error occurred: ' + error);
      }
    });
  },

  getUpdatedData: function () {
    var data = {};
    this.fieldMap.forEach(({ input, key }) => {
      if (key === 'user_user_type' || key === 'user_slab_id') {
        data[key] = $(input).find('option:selected').val();
      } else if (key === 'user_email_verified') {
        data[key] = $(input).prop('checked') ? 1 : 0;
      } else if (key === 'sub_end_date') {
        const rawDate = $(input).val();
        const date = new Date(rawDate);
        data[key] = (rawDate && !isNaN(date.getTime())) ? date.toISOString().split('T')[0] : '';
      } else {
        data[key] = $(input).val();
      }
    });
    return data;
  },

  exportSelectedColumns: function () {
    let self = this;

    // Get checked columns from modal (excluding ID)
    let selectedKeys = [];
    $('.export-column-checkbox:checked').each(function () {
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
      let field = self.fieldMap.find(f => f.key === key);
      if (field) headerLabels.push(field.label);
    });

    // Build CSV rows
    let csvRows = [];
    csvRows.push(headerLabels.join(','));

    data.forEach(row => {
      let rowData = [];
      selectedKeys.forEach(key => {
        let val = row[key];

        // Format certain fields as in render
        if (key === 'user_user_type') {
          let typeOption = self.userTypeOptions.find(o => o.id == val || o.user_type_name === val);
          val = typeOption ? typeOption.user_type_name : val || '';
        } else if (key === 'user_slab_id') {
          let slabOption = self.slabOptions.find(o => o.id == val || o.name === val);
          val = slabOption ? slabOption.name : val || '';
        } else if (key === 'user_email_verified') {
          val = (val == 1 || val === true || val === 'true') ? 'Yes' : 'No';
        } else if (key === 'sub_end_date') {
          if (val && val !== '0000-00-00') {
            let d = new Date(val);
            val = !isNaN(d.getTime()) ? d.toISOString().split('T')[0] : '';
          } else {
            val = '';
          }
        }

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
    a.download = 'users_export.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }
};

$(document).ready(function () {
  userManager.init();
});
