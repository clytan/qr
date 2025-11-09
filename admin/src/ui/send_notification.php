<?php
include '../components/head.php';
?>

<body>
    <?php
    include '../components/header.php';
    include '../components/sidebar.php';
    ?>

    <div class="content-body">
        <div class="container-fluid">
            <div class="row page-titles mx-0">
                <div class="col-sm-6 p-md-0">
                    <div class="welcome-text">
                        <h4>Send Notifications</h4>
                        <span>Send notifications to users</span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Send New Notification</h4>
                        </div>
                        <div class="card-body">
                            <div class="form-validation">
                                <form class="form-valide" id="notification-form">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="col-form-label" for="user-select">Select User <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-control" id="user-select" name="user_id" required>
                                                    <option value="">Select User</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-form-label" for="message">Notification Message <span
                                                        class="text-danger">*</span></label>
                                                <textarea class="form-control" id="message" name="message" rows="4"
                                                    placeholder="Enter notification message" required></textarea>
                                            </div>
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-primary">Send Notification</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sent Notifications Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Recent Notifications</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="notifications-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>User</th>
                                            <th>Message</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../components/footer.php'; ?>
    <?php include '../components/scripts.php'; ?>

    <script>
        $(document).ready(function () {
            // Load users into select dropdown
            function loadUsers() {
                $.ajax({
                    url: '../backend/get_users_details.php',
                    type: 'GET',
                    success: function (response) {
                        if (response.success) {
                            const select = $('#user-select');
                            response.data.forEach(user => {
                                select.append(`<option value="${user.id}">${user.user_full_name} (${user.user_email})</option>`);
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error loading users:', error);
                        toastr.error('Failed to load users');
                    }
                });
            }

            // Handle form submission
            $('#notification-form').on('submit', function (e) {
                e.preventDefault();

                const formData = {
                    user_id: $('#user-select').val(),
                    message: $('#message').val()
                };

                $.ajax({
                    url: '../backend/send_user_notification.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(formData),
                    success: function (response) {
                        if (response.success) {
                            toastr.success('Notification sent successfully');
                            $('#message').val(''); // Clear message field
                            loadRecentNotifications(); // Refresh notifications table
                        } else {
                            toastr.error(response.error || 'Failed to send notification');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error sending notification:', error);
                        toastr.error('Failed to send notification');
                    }
                });
            });

            // Load recent notifications
            function loadRecentNotifications() {
                $.ajax({
                    url: '../backend/get_recent_notifications.php',
                    type: 'GET',
                    success: function (response) {
                        if (response.success) {
                            const tbody = $('#notifications-table tbody');
                            tbody.empty();

                            response.data.forEach(notification => {
                                tbody.append(`
                                    <tr>
                                        <td>${new Date(notification.created_on).toLocaleString()}</td>
                                        <td>${notification.user_full_name}</td>
                                        <td>${notification.message}</td>
                                        <td>${notification.is_read ? '<span class="badge badge-success">Read</span>' : '<span class="badge badge-warning">Unread</span>'}</td>
                                    </tr>
                                `);
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error loading notifications:', error);
                    }
                });
            }

            // Initial load
            loadUsers();
            loadRecentNotifications();

            // Refresh notifications every 30 seconds
            setInterval(loadRecentNotifications, 30000);
        });
    </script>
</body>

</html>