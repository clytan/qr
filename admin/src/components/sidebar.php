<?php
/**
 * Admin Sidebar Component
 * Collapsible on mobile, dynamic menu based on permissions
 */

require_once(__DIR__ . '/../backend/dbconfig/connection.php');

$currentPage = basename($_SERVER['PHP_SELF']);

// Menu items with icons
$menuItems = [
    ['url' => 'dashboard.php', 'name' => 'Dashboard', 'icon' => 'fas fa-home'],
    ['url' => 'admin_users.php', 'name' => 'Admin Users', 'icon' => 'fas fa-users-cog'],
    ['url' => 'admin_roles.php', 'name' => 'Roles', 'icon' => 'fas fa-user-tag'],
    ['url' => 'admin_urls.php', 'name' => 'URL Permissions', 'icon' => 'fas fa-link'],
    ['url' => 'db_explorer.php', 'name' => 'DB Explorer', 'icon' => 'fas fa-database'],
    ['url' => 'communities.php', 'name' => 'Communities', 'icon' => 'fas fa-comments'],
    ['url' => 'polls.php', 'name' => 'Polls', 'icon' => 'fas fa-poll'],
    ['url' => 'collaborations.php', 'name' => 'Influencer Collabs', 'icon' => 'fas fa-handshake'],
    ['url' => 'partner_programmes.php', 'name' => 'Partner Programmes', 'icon' => 'fas fa-briefcase'],
    ['url' => 'withdrawals.php', 'name' => 'Wallet Requests', 'icon' => 'fas fa-money-bill-wave'],
    ['url' => 'supercharge.php', 'name' => 'Super Charge', 'icon' => 'fas fa-bolt'],
    ['url' => 'rewards.php', 'name' => 'Rewards', 'icon' => 'fas fa-gift'],
    ['url' => 'users.php', 'name' => 'Users', 'icon' => 'fas fa-users'],
    ['url' => 'renewals.php', 'name' => 'Renewals', 'icon' => 'fas fa-sync-alt'],
    ['url' => 'invoices.php', 'name' => 'Invoices', 'icon' => 'fas fa-file-invoice'],
    ['url' => 'analytics.php', 'name' => 'Analytics', 'icon' => 'fas fa-chart-line'],
    ['url' => 'notifications.php', 'name' => 'Notifications', 'icon' => 'fas fa-bell'],
];

$allowedUrls = isset($_SESSION['admin_allowed_urls']) ? $_SESSION['admin_allowed_urls'] : [];
$isSuperAdmin = (isset($_SESSION['admin_role_id']) && $_SESSION['admin_role_id'] == 1);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
/* ==================== SIDEBAR STYLES ==================== */
/* ==================== SIDEBAR STYLES ==================== */
.admin-sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 260px;
    height: 100vh;
    background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
    display: flex;
    flex-direction: column;
    z-index: 1000;
    box-shadow: 4px 0 15px rgba(0, 0, 0, 0.3);
    transition: transform 0.3s ease;
}

.sidebar-header {
    padding: 20px 25px 30px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 0; /* Changed from 20px */
    flex-shrink: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}

.sidebar-logo img {
    width: 40px;
    height: 40px;
    border-radius: 10px;
}

.sidebar-logo-text {
    font-size: 18px;
    font-weight: 700;
    background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Close button for mobile */
.sidebar-close {
    display: none;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: none;
    background: rgba(255, 255, 255, 0.1);
    color: #94a3b8;
    font-size: 18px;
    cursor: pointer;
    align-items: center;
    justify-content: center;
}

.sidebar-menu {
    list-style: none;
    padding: 20px 0;
    margin: 0;
    flex: 1;
    overflow-y: auto;
    min-height: 0; /* Important for flex child scrolling */
}

/* Custom Scrollbar for menu */
.sidebar-menu::-webkit-scrollbar {
    width: 4px;
}
.sidebar-menu::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.02);
}
.sidebar-menu::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
}

.sidebar-menu-item {
    margin: 4px 12px;
}

.sidebar-menu-link {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 20px;
    color: #a0aec0;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.25s ease;
    font-size: 14px;
    font-weight: 500;
}

.sidebar-menu-link:hover {
    background: rgba(255, 255, 255, 0.08);
    color: #ffffff;
    transform: translateX(4px);
}

.sidebar-menu-link.active {
    background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
    color: #ffffff;
    box-shadow: 0 4px 15px rgba(230, 119, 83, 0.4);
}

.sidebar-menu-link i {
    width: 22px;
    font-size: 16px;
    text-align: center;
}

/* User info at bottom */
.sidebar-user {
    position: relative; /* Changed from absolute */
    bottom: auto; /* Reset */
    left: auto; /* Reset */
    right: auto; /* Reset */
    padding: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.2);
    flex-shrink: 0; /* Don't shrink */
}

.sidebar-user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.sidebar-user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 16px;
}

.sidebar-user-details {
    flex: 1;
    min-width: 0;
}

.sidebar-user-name {
    color: #ffffff;
    font-size: 14px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-user-role {
    color: #a0aec0;
    font-size: 12px;
}

.sidebar-logout {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px;
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
    border: none;
    border-radius: 8px;
    width: 100%;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s ease;
    text-decoration: none;
}

.sidebar-logout:hover {
    background: rgba(239, 68, 68, 0.25);
    color: #f87171;
}

/* ==================== MAIN CONTENT ==================== */
.admin-main {
    margin-left: 260px;
    min-height: 100vh;
    background: #0f172a;
    transition: margin-left 0.3s ease;
}

/* Mobile header with hamburger */
.mobile-header {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 60px;
    background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
    z-index: 999;
    padding: 0 20px;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.hamburger-btn {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    border: none;
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    font-size: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.hamburger-btn:hover {
    background: rgba(255, 255, 255, 0.15);
}

.mobile-logo {
    font-size: 18px;
    font-weight: 700;
    background: linear-gradient(135deg, #E9437A 0%, #e67753 50%, #E2AD2A 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Overlay for mobile */
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 999;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.sidebar-overlay.show {
    display: block;
    opacity: 1;
}

/* ==================== MOBILE RESPONSIVE ==================== */
@media (max-width: 768px) {
    .admin-sidebar {
        transform: translateX(-100%);
        width: 280px;
        z-index: 1001;
    }
    
    .admin-sidebar.open {
        transform: translateX(0);
    }
    
    .sidebar-close {
        display: flex;
    }
    
    .admin-main {
        margin-left: 0;
        padding-top: 60px;
    }
    
    .mobile-header {
        display: flex;
    }
}

/* ==================== RESPONSIVE TABLE STYLES ==================== */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

@media (max-width: 768px) {
    /* Stack table cells on mobile */
    .table-responsive table {
        min-width: 600px;
    }
    
    /* Card-style table for mobile */
    .table-card-mobile thead {
        display: none;
    }
    
    .table-card-mobile tbody tr {
        display: block;
        background: rgba(30, 41, 59, 0.8);
        border-radius: 12px;
        margin-bottom: 12px;
        padding: 15px;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }
    
    .table-card-mobile tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border: none;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    .table-card-mobile tbody td:last-child {
        border-bottom: none;
    }
    
    .table-card-mobile tbody td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #94a3b8;
        font-size: 12px;
        text-transform: uppercase;
    }
}
</style>

<!-- Mobile Header -->
<div class="mobile-header">
    <button class="hamburger-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <span class="mobile-logo">Admin Panel</span>
    <div style="width: 44px;"></div>
</div>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-logo">
            <img src="../../../logo/logo.png" alt="Logo" onerror="this.style.display='none'">
            <span class="sidebar-logo-text">Admin Panel</span>
        </a>
        <button class="sidebar-close" onclick="closeSidebar()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <ul class="sidebar-menu">
        <?php foreach ($menuItems as $item): ?>
            <?php 
            $hasAccess = $isSuperAdmin || $item['url'] === 'dashboard.php' || in_array($item['url'], $allowedUrls);
            if (!$hasAccess) continue;
            
            $isActive = ($currentPage === $item['url']) ? 'active' : '';
            ?>
            <li class="sidebar-menu-item">
                <a href="<?php echo $item['url']; ?>" class="sidebar-menu-link <?php echo $isActive; ?>">
                    <i class="<?php echo $item['icon']; ?>"></i>
                    <span><?php echo $item['name']; ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    
    <div class="sidebar-user">
        <div class="sidebar-user-info">
            <div class="sidebar-user-avatar">
                <?php echo strtoupper(substr($admin_name ?? 'A', 0, 1)); ?>
            </div>
            <div class="sidebar-user-details">
                <div class="sidebar-user-name"><?php echo htmlspecialchars($admin_name ?? 'Admin'); ?></div>
                <div class="sidebar-user-role"><?php echo htmlspecialchars($admin_role_name ?? 'Admin'); ?></div>
            </div>
        </div>
        <a href="../backend/logout.php" class="sidebar-logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>

<script>
function toggleSidebar() {
    document.getElementById('adminSidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
    document.body.style.overflow = document.getElementById('adminSidebar').classList.contains('open') ? 'hidden' : '';
}

function closeSidebar() {
    document.getElementById('adminSidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
    document.body.style.overflow = '';
}

// Close sidebar when clicking a link on mobile
document.querySelectorAll('.sidebar-menu-link').forEach(link => {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            closeSidebar();
        }
    });
});
</script>
