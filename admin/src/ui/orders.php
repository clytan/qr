<?php 
    if (session_status() === PHP_SESSION_NONE) session_start();
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
    // Direct URL access prevention
    if ($user_id) {
        include_once __DIR__ . '/../backend/get_sidebar_permissions.php';
        $perm = getUserSidebarPermissions($user_id);
        $allowed = $perm['allowed_pages'];
        $role_name = $perm['role_name'];
        $page_label = 'Manage Orders';
        if ($role_name !== 'Super Admin' && !in_array($page_label, $allowed)) {
            header('Location: login.php');
            exit();
        }
    } else {
        header('Location: login.php');
        exit();
    }
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
            <h4 class="mb-4">Invoice List</h4>
            <div id="ordersTableControls" style="display: flex; justify-content: flex-start; align-items: center; gap: 10px; margin-bottom: 8px;"></div>
            <table id="ordersTable" class="table table-hover table-bordered table-striped align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>QR ID</th>
                        <th>Invoice Type</th>
                        <th>Invoice Number</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

            <!-- Export Columns Modal -->
            <div class="modal fade" id="exportOrdersColumnsModal" tabindex="-1" aria-labelledby="exportOrdersColumnsModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold" id="exportOrdersColumnsModalLabel">Select Columns to Export</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="exportOrdersColumnSelectorModal">
                            <!-- JS will dynamically insert checkboxes here -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="exportOrdersSelectedBtnModal">Export Selected</button>
                        </div>
                    </div>
                </div>
            </div>
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
    <script src="./custom_js/custom_orders.js"></script>

</body>

</html>