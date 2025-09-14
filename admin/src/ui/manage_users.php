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
                <h4 class="mb-4">User List</h4>
                <table id="userTable" class="table table-hover table-bordered table-striped align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>


        <!-- User Modal -->
        <div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

            <!-- Header Section: Profile + Edit Button -->
            <div class="modal-body border-bottom bg-light py-4 px-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap">
                
                <!-- Profile Image and Full Name -->
                <div class="d-flex align-items-center flex-grow-1 mb-3 mb-md-0" style="min-width: 250px;">
                    <img src="https://via.placeholder.com/100" class="rounded-circle shadow-sm me-3" alt="Profile Image" id="profileImage" width="100" height="100">
                    <div>
                    <h4 class="fw-bold mb-0" id="modalFullNameDisplay">John Doe</h4>
                    <input type="text" class="form-control form-control-sm d-none" id="modalFullNameInput" value="John Doe" />
                    </div>
                </div>

                <!-- Edit Profile Button -->
                <div>
                    <button type="button" class="btn btn-primary btn-md" id="editProfileBtn">
                    <i class="fas fa-edit me-1"></i> Edit Profile
                    </button>
                </div>

                </div>
            </div>

            <!-- Details Section -->
            <div class="modal-body px-4 py-4">
                <div class="row g-3">

                <!-- Phone -->
                <div class="col-md-6">
                    <label class="form-label text-muted"><i class="fas fa-phone-alt me-1"></i> Phone</label>
                    <p class="fw-semibold mb-0" id="modalPhoneDisplay"></p>
                    <input type="text" class="form-control d-none" id="modalPhoneInput" value="">
                </div>

                <!-- Email -->
                <div class="col-md-6">
                    <label class="form-label text-muted"><i class="fa-solid fa-envelope me-1"></i>Email</label>
                    <p class="fw-semibold mb-0" id="modalEmailDisplay"></p>
                    <input type="text" class="form-control d-none" id="modalEmailInput" value="">
                </div>


                <!-- User Type -->
                <div class="col-md-6">
                    <label class="form-label text-muted"><i class="fas fa-user-tag me-1"></i> User Type</label>
                    <p class="fw-semibold mb-0" id="modalUserTypeDisplay"></p>
                    <select class="form-select d-none" id="modalUserTypeInput"></select>
                </div>

                <!-- QR ID -->
                <div class="col-md-6">
                    <label class="form-label text-muted"><i class="fas fa-qrcode me-1"></i> QR ID</label>
                    <p class="fw-semibold mb-0" id="modalQrIdDisplay">QR123456</p>
                    <input type="text" class="form-control d-none" id="modalQrIdInput" value="">
                </div>
                
                   <!-- Slab ID -->
                <div class="col-md-6">
                <label class="form-label text-muted"><i class="fas fa-layer-group me-1"></i> Slab ID</label>
                <p class="fw-semibold mb-0" id="modalSlabIdDisplay"></p>
                <select class="form-select d-none" id="modalSlabIdInput"></select>
                </div>
                
                <!-- Address -->
                <div class="col-md-6">
                    <label class="form-label text-muted"><i class="fa-solid fa-address-card me-1"></i>Address</label>
                    <p class="fw-semibold mb-0" id="modalAddressDisplay"></p>
                    <input type="text" class="form-control d-none" id="modalAddressInput" value="">
                </div>

                <!-- Email Verified -->
                <div class="col-md-6">
                    <label class="form-label text-muted d-flex align-items-center">
                        <i class="fas fa-box me-1"></i> Email Verified
                    </label>
                    <p class="fw-semibold mb-0" id="modalEmailVerifiedDisplay"></p>
                    <input type="checkbox" class="form-check-input d-none" id="modalEmailVerified" />
                </div>

                <!-- End Date -->
                <div class="col-md-6">
                <label class="form-label text-muted"><i class="fas fa-calendar-alt me-1"></i> Subscription End Date</label>
                <p class="fw-semibold mb-0" id="modalEndDateDisplay"></p>
                <input type="date" class="form-control d-none" id="modalEndDateInput" value="">
                </div>

                <!-- Referred By -->
                <div class="col-md-6">
                <label class="form-label text-muted"><i class="fas fa-user-friends me-1"></i> Referred By User QR ID</label>
                <p class="fw-semibold mb-0" id="modalReferredByDisplay"></p>
                <input type="text" class="form-control d-none" id="modalReferredByInput" value="">
                </div>
                
                <!-- Tag -->
                <div class="col-md-6">
                    <label class="form-label text-muted"><i class="fas fa-tag me-1"></i> Tag</label>
                    <p class="fw-semibold mb-0" id="modalTagDisplay"></p>
                    <input type="text" class="form-control d-none" id="modalTagInput" value="">
                </div>

                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer bg-light px-4">
                <div class="w-100 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times-circle me-1"></i> Close
                </button>
                <button type="button" class="btn btn-success d-none" id="saveProfileBtn">
                    <i class="fas fa-save me-1"></i> Save
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

    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
    <?php include('../components/scripts.php') ?>
    <script src="./custom_js/custom_manage_user.js"></script>

</body>

</html>