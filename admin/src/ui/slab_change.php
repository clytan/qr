<?php 
    session_start();
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
?>
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
              <input type="hidden" id="admin_user_id" value="<?php echo htmlspecialchars($user_id); ?>">
              <h4 class="mb-4">Slab Change Request List</h4>
              <table id="userTable" class="table table-hover table-bordered table-striped align-middle">
                <thead class="table-primary">
                  <tr>
                    <th>Id</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>User QR ID</th>
                    <th>Current Slab</th>
                    <th>Requested Slab</th>
                    <th class="text-center">Options</th>
                  </tr>
                </thead>
                <tbody></tbody>
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