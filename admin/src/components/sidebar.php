<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) session_start();
$user_id = $_SESSION['user_id'] ?? null;
$sidebar = [
    ["href" => "./index.php", "label" => "Dashboard"],
    ["href" => "./manage_users.php", "label" => "Manage Users"],
    ["href" => "./slab_change.php", "label" => "Slab Change Request"],
    ["href" => "./orders.php", "label" => "Manage Orders"],
    ["href" => "./manage_permission.php", "label" => "Manage Permission"]
];
$allowed = [];
$role_name = '';
if ($user_id) {
    include_once __DIR__ . '/../backend/get_sidebar_permissions.php';
    $perm = getUserSidebarPermissions($user_id);
    $allowed = $perm['allowed_pages'];
    $role_name = $perm['role_name'];
}
?>
<div class="deznav">
    <div class="deznav-scroll">
        <ul class="metismenu" id="menu">
            <li class="menu-title">YOUR COMPANY</li>
            <?php foreach ($sidebar as $item):
                $show = false;
                if ($role_name === 'Super Admin') {
                    $show = true;
                } else if (in_array($item['label'], $allowed)) {
                    $show = true;
                }
                if ($show): ?>
                <li><a href="<?= $item['href'] ?>" aria-expanded="false">
                    <div class="menu-icon">
                        <!-- ...existing SVG icons... -->
                    </div>
                    <span class="nav-text"><?= $item['label'] ?></span>
                </a></li>
            <?php endif; endforeach; ?>
        </ul>
    </div>
</div>