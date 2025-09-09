<!DOCTYPE html>
<html lang="en">
<?php include('../components/head.php') ?>

<body>

    <!--*******************
        Preloader start
    ********************-->
    <?php include('../components/loader.php'); ?>
    <!--*******************
        Preloader end
    ********************-->

    <!--**********************************
        Main wrapper start
    ***********************************-->
    <div id="main-wrapper">
        <!--**********************************
            Nav header start
        ***********************************-->
        <?php include('../components/navbar.php'); ?>
        <!--**********************************
            Nav header end
        ***********************************-->

        <!--**********************************
            Chat box start
        ***********************************-->
        <?php include('../components/chatbox.php'); ?>
        <!--**********************************
            Chat box End
        ***********************************-->

        <!--**********************************
            Header start
        ***********************************-->

        <?php include('../components/header.php'); ?>
        <!--**********************************
            Header end ti-comment-alt
        ***********************************-->

        <!--**********************************
            Sidebar start
        ***********************************-->
        <?php include('../components/sidebar.php'); ?>

        <!--**********************************
            Sidebar end
        ***********************************-->

        <!--**********************************
            Content body start
        ***********************************-->
        <div class="content-body">
            <div class="container-fluid">
              <h4 class="mb-4">Slab Change Request List</h4>
              <table id="userTable" class="table table-hover table-bordered table-striped align-middle">
                <thead class="table-primary">
                  <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>User QR ID</th>
                    <th>Current Slab</th>
                    <th>Requested Slab</th>
                    <th class="text-center">Options</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  // Sample data — replace with DB query as needed
                  $users = [
                    [
                      'id' => 1,
                      'user_full' => 'Debanjan Roy',
                      'user_email' => 'debanjan@example.com',
                      'user_phone' => '9876543210',
                      'user_user_type' => 'Admin',
                      'user_qr_id' => 'QR12345',
                      'user_tag' => 'Tech',
                      'current_slab' => 'Basic',
                      'requested_slab' => 'Premium'
                    ],
                    [
                      'id' => 2,
                      'user_full' => 'Amit Sharma',
                      'user_email' => 'amit@example.com',
                      'user_phone' => '9123456780',
                      'user_user_type' => 'User',
                      'user_qr_id' => 'QR67890',
                      'user_tag' => 'Sales',
                      'current_slab' => 'Premium',
                      'requested_slab' => 'Enterprise'
                    ]
                  ];

                  foreach ($users as $user) {
                    echo "<tr>
                      <td>{$user['user_full']}</td>
                      <td>{$user['user_email']}</td>
                      <td>{$user['user_phone']}</td>
                      <td>{$user['user_qr_id']}</td>
                      <td>{$user['current_slab']}</td>
                      <td>{$user['requested_slab']}</td>
                      <td class='text-center'>
                        <button class='btn btn-success btn-sm me-1 approve-btn' data-id='{$user['id']}' title='Approve'>
                          <i class='fas fa-check'></i>
                        </button>
                        <button class='btn btn-danger btn-sm reject-btn' data-id='{$user['id']}' title='Reject'>
                          <i class='fas fa-times'></i>
                        </button>
                      </td>
                    </tr>";
                  }
                  ?>
                </tbody>
              </table>
            </div>

        </div>

        <!--**********************************
            Content body end
        ***********************************-->
        <!--**********************************
            Footer start
        ***********************************-->
        <?php include('../components/footer.php'); ?>
        <!--**********************************
            Footer end
        ***********************************-->

        <!--**********************************
           Support ticket button start
        ***********************************-->

        <!--**********************************
           Support ticket button end
        ***********************************-->

    </div>
    <!--**********************************
        Main wrapper end
    ***********************************-->

    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
    <?php include('../components/scripts.php') ?>
    <script src="./custom_js/custom_slab_change.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


</body>

</html>