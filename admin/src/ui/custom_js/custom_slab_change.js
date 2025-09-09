$(document).ready(function () {
  $('#userTable').DataTable({
    responsive: true,
    lengthChange: false,
    language: {
      searchPlaceholder: "Search ...",
      paginate: {
        previous: '<i class="fas fa-angle-left"></i>',
        next: '<i class="fas fa-angle-right"></i>'
      }
    }
  });

  // Approve Button
  $(document).on('click', '.approve-btn', function () {
    const userId = $(this).data('id');

    Swal.fire({
      title: 'Confirm Approval',
      text: "Are you sure you want to approve this user's request?",
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',  // Bootstrap success color
      cancelButtonColor: '#6c757d',   // Bootstrap secondary color
      confirmButtonText: 'Yes, Approve',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        // Place AJAX call here if needed
        Swal.fire({
          icon: 'success',
          title: 'Approved!',
          text: 'The user request has been approved.',
          timer: 2000,
          showConfirmButton: false
        });
      }
    });
  });

  // Reject Button
  $(document).on('click', '.reject-btn', function () {
    const userId = $(this).data('id');

    Swal.fire({
      title: 'Confirm Rejection',
      text: "Are you sure you want to decline this user's request?",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',  // Bootstrap danger color
      cancelButtonColor: '#6c757d',   // Bootstrap secondary color
      confirmButtonText: 'Yes, Reject',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        // Place AJAX call here if needed
        Swal.fire({
          icon: 'success',
          title: 'Declined!',
          text: 'The user request has been declined.',
          timer: 2000,
          showConfirmButton: false
        });
      }
    });
  });
});
