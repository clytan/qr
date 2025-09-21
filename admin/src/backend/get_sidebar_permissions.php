<?php
// Usage: include this file and call getUserSidebarPermissions($user_id)
// Returns: array of allowed page names and role_name for the user
include_once __DIR__ . '/dbconfig/connection.php';

function getUserSidebarPermissions($user_id) {
    global $conn;
    $result = [
        'role_name' => '',
        'allowed_pages' => []
    ];
    // Get role_name and allowed page_names for the user
    $sql = "SELECT r.role_name, p.page_name ,p.page_urls
            FROM admin_user u
            JOIN admin_user_role r ON u.role_id = r.id
            LEFT JOIN admin_urls au ON au.user_id = u.id
            LEFT JOIN admin_pages p ON FIND_IN_SET(p.id, au.allowed_urls)
            WHERE u.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $pages = [];
    $role_name = '';
    while ($row = $res->fetch_assoc()) {
        $role_name = $row['role_name'];
        if ($row['page_name']) {
            $pages[] = $row['page_name'];
        }
    }
    $result['role_name'] = $role_name;
    $result['allowed_pages'] = $pages;
    return $result;
}
?>
