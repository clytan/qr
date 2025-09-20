<?php 
    if (session_status() === PHP_SESSION_NONE) session_start();
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
    // Direct URL access prevention
    if ($user_id) {
        include_once __DIR__ . '/../backend/get_sidebar_permissions.php';
        $perm = getUserSidebarPermissions($user_id);
        $role_name = $perm['role_name'];
        if ($role_name !== 'Super Admin') {
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
                <h4 class="mb-4">User Permissions</h4>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div id="permissionTableControls" style="display: flex; justify-content: flex-end; align-items: center; gap: 10px;"></div>
                    <button class="btn btn-success" id="addUserBtn">+ Add new user</button>
                </div>
                <table id="permissionTable" class="table table-hover table-bordered table-striped align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Allowed Pages</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <input type="hidden" id="hidden_users">
                <input type="hidden" id="hidden_pages">
                <input type="hidden" id="hidden_user_urls">
            </div>
            <!-- Modal -->
            <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg"> <!-- Changed to modal-lg for bigger modal -->
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title fw-bold" id="userModalLabel">Add User Permissions</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="userPermForm">
                                <div class="mb-4">
                                    <label class="form-label fw-semibold" for="user_select">
                                        <i class="fa fa-user me-1 text-primary"></i> User
                                    </label>
                                    <select id="user_select" class="form-select form-select-lg" style="width:100%"></select>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-semibold" for="allowed_urls_select">
                                        <i class="fa fa-link me-1 text-success"></i> Allowed Pages
                                    </label>
                                    <select id="allowed_urls_select" class="form-select form-select-lg" multiple style="width:100%"></select>
                                    <div class="form-text">Hold Ctrl (Windows) or Cmd (Mac) to select multiple pages.</div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fa fa-times me-1"></i> Cancel
                            </button>
                            <button type="button" class="btn btn-primary" id="saveUserPerms">
                                <i class="fa fa-save me-1"></i> Save
                            </button>
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


    <!-- Export Columns Modal for Permissions -->
    <div class="modal fade" id="exportPermissionsColumnsModal" tabindex="-1" aria-labelledby="exportPermissionsColumnsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="exportPermissionsColumnsModalLabel">Select Columns to Export</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="exportPermissionsColumnSelectorModal">
                    <!-- JS will dynamically insert checkboxes here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="exportPermissionsSelectedBtnModal">Export Selected</button>
                </div>
            </div>
        </div>
    </div>

    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
    <?php include('../components/scripts.php') ?>
    <!-- DataTables & Select2 -->
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="./custom_js/custom_manage_permission.js"></script>

</body>

</html>