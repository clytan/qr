<!DOCTYPE html>
<html lang="en">
<?php include('../components/head.php') ?>

<body>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

    <?php include('../components/loader.php'); ?>

    <div id="main-wrapper">
        <?php include('../components/navbar.php'); ?>
        <?php include('../components/chatbox.php'); ?>
        <?php include('../components/header.php'); ?>
        <?php include('../components/sidebar.php'); ?>

        <!-- Content body start -->
        <div class="content-body">
            <div class="container-fluid">
                <div class="row">
                    <!-- Revenue Card -->
                    <div class="col-xl-6">
                        <div class="card bg-primary text-white" style="min-height: 300px;">
                            <div class="card-header d-flex justify-content-between align-items-center py-2">
                                <div>
                                    <h4 class="card-title mb-2 bg-primary text-white">Revenue Analytics</h4>
                                </div>
                                <div class="d-flex align-items-center">
                                    <input type="text" class="form-control form-control-sm bg-white"
                                        id="revenueDateRange"
                                        style="min-width: 220px;"
                                        readonly 
                                        placeholder="Select date range">
                                </div>
                            </div>
                            <div class="card-body py-2">
                                <!-- Summary Cards -->
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="p-2 bg-light rounded">
                                            <h6 class="mb-2">Total Revenue</h6>
                                            <h6 class="mb-0" id="totalRevenueLabel">₹0.00</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-2 bg-light rounded">
                                            <h6 class="mb-2">Total Earnings</h6>
                                            <h6 class="mb-0" id="totalEarningsLabel">₹0.00</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-2 bg-light rounded">
                                            <h6 class="mb-2">Total GST</h6>
                                            <h6 class="mb-0" id="totalGSTLabel">₹0.00</h3>
                                        </div>
                                    </div>
                                </div>

                                <!-- Chart Container -->
                                <div id="revenueStackedBarChart" style="min-height: 365px; width:100%; margin-top: 20px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include('../components/footer.php'); ?>
    </div>

    <!-- Scripts -->
    <?php include('../components/scripts.php') ?>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="./custom_js/custom_dashboard.js"></script>

</body>
</html>
