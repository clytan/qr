var userManager = {
    slabOptions: [],
    userTypeOptions: [],
    currentUser: null,
    isEditing: false,
    table: null,
    fieldMap: [
        { display: "#modalFullNameDisplay", input: "#modalFullNameInput", key: "user_full_name" },
        { display: "#modalPhoneDisplay", input: "#modalPhoneInput", key: "user_phone" },
        { display: "#modalEmailDisplay", input: "#modalEmailInput", key: "user_email" },
        { display: "#modalAddressDisplay", input: "#modalAddressInput", key: "user_address" },
        { display: "#modalUserTypeDisplay", input: "#modalUserTypeInput", key: "user_user_type" },
        { display: "#modalQrIdDisplay", input: "#modalQrIdInput", key: "user_qr_id" },
        { display: "#modalSlabIdDisplay", input: "#modalSlabIdInput", key: "user_slab_id" },
        { display: "#modalEmailVerifiedDisplay", input: "#modalEmailVerified", key: "user_email_verified" },
        { display: "#modalEndDateDisplay", input: "#modalEndDateInput", key: "sub_end_date" },
        { display: "#modalReferredByDisplay", input: "#modalReferredByInput", key: "referred_by_user_id" },
        { display: "#modalTagDisplay", input: "#modalTagInput", key: "user_tag" }
    ],

    init: function() {
        this.loadDropdownOptions();
        this.initDataTable();
        this.bindEvents();
    },

    loadDropdownOptions: function() {
      var self = this;
      $.getJSON('../backend/get_user_form_options.php', function(data) {
          if (data.status) {
              self.slabOptions = data.slabs;
              self.userTypeOptions = data.types;
          } else {
              alert('Failed to load options: ' + (data.message || 'Unknown error'));
          }
      }).fail(function(jqxhr, textStatus, error) {
          alert('Error loading options: ' + error);
      });
    },

    initDataTable: function() {
        var self = this;
        this.table = $('#userTable').DataTable({
            ajax: {
                url: '../backend/get_users_details.php',
                dataSrc: function(json) {
                    if (json.status) {
                        return json.data; // return the user array if successful
                    } else {
                        alert(json.message || 'Failed to load user data');
                        return []; // return empty array to show no data
                    }
                }
            },
            columns: [
                {
                    data: 'id',
                    render: function(data, type, row) {
                        return `<a href="#" class="user-id" data-user='${JSON.stringify(row)}'>${data}</a>`;
                    }
                },
                { data: 'user_full_name' },
                { data: 'user_email' },
                { data: 'user_phone' }
            ],
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
    },

    bindEvents: function() {
        var self = this;

        $('#userTable tbody').on('click', '.user-id', function(e) {
            e.preventDefault();
            self.currentUser = $(this).data('user');
            self.fillModalFields();
            $('#userModal').modal('show');
        });

        $('#editProfileBtn').on('click', function() {
            self.toggleEditMode();
        });

        $('#saveProfileBtn').on('click', function() {
            self.saveProfile();
        });
    },

    fillModalFields: function() {
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

            } else {
                if (key === 'sub_end_date' && value) {
                    const formattedDate = new Date(value).toISOString().split('T')[0];
                    $(input).val(formattedDate);
                } else {
                    $(input).val(value);
                }
                $(display).text(value);
            }
        });

        const imagePath = self.currentUser.user_image_path || 'https://via.placeholder.com/100';
        $('#profileImage').attr('src', imagePath);
    },

    toggleEditMode: function() {
        this.isEditing = !this.isEditing;

        this.fieldMap.forEach(({ display, input }) => {
            $(display).toggleClass('d-none', this.isEditing);
            $(input).toggleClass('d-none', !this.isEditing);
        });

        $('#saveProfileBtn').toggleClass('d-none', !this.isEditing);

        $('#editProfileBtn').html(this.isEditing
            ? '<i class="fas fa-times me-1"></i> Cancel'
            : '<i class="fas fa-edit me-1"></i> Edit Profile');
    },

    saveProfile: function() {
        var self = this;

        // Update display with input values
        this.fieldMap.forEach(({ display, input, key }) => {
            let displayValue = '';

            if (key === 'user_user_type' || key === 'user_slab_id') {
                displayValue = $(input).find('option:selected').text();
            } else if (key === 'user_email_verified') {
                displayValue = $(input).prop('checked') ? 'Yes' : 'No';
            } else {
                displayValue = $(input).val();
            }

            $(display).text(displayValue);
        });

        // Switch off edit mode
        this.isEditing = false;
        this.fieldMap.forEach(({ display, input }) => {
            $(display).removeClass('d-none');
            $(input).addClass('d-none');
        });

        $('#saveProfileBtn').addClass('d-none');
        $('#editProfileBtn').html('<i class="fas fa-edit me-1"></i> Edit Profile');
        
        const adminId = $('#admin_user_id').val();

        // 👇 Prepare data to send including admin ID
        const requestData = Object.assign(
            { id: this.currentUser.id, admin_id: adminId },
            this.getUpdatedData()
        );

        // AJAX call to save updated data
        $.ajax({
          url: '../backend/save_user_details.php',
          method: 'POST',
          data: requestData,
          dataType: 'json',  // explicitly expect JSON response
          success: (res) => {
              if (res.status) {
                  alert(res.message || 'User updated successfully');
                  this.table.ajax.reload();
              } else {
                  alert('Update failed: ' + (res.message || 'Unknown error'));
              }
          },
          error: (xhr, status, error) => {
              alert('An error occurred: ' + error);
          }
      });

    },

    getUpdatedData: function() {
        var data = {};

        this.fieldMap.forEach(({ input, key }) => {
            if (key === 'user_user_type' || key === 'user_slab_id') {
                data[key] = $(input).find('option:selected').val();
            } else if (key === 'user_email_verified') {
                data[key] = $(input).prop('checked') ? 1 : 0;
            } else {
                data[key] = $(input).val();
            }
        });

        return data;
    }
};


$(document).ready(function() {
  userManager.init();
});
