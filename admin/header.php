<?php
// admin/header.php - Backstage Sidebar, Topbar & Session Guard
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce admin login and 30-minute idle session timeout
require_admin_login();

$current_admin_page = basename($_SERVER['PHP_SELF']);
$admin_user = $_SESSION['admin_username'] ?? 'Staff';
$admin_role = $_SESSION['admin_role'] ?? 'staff';

// Live Pending Orders count for the sidebar badge
$pending_count_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE order_status = 'Pending'");
$pending_count = mysqli_fetch_assoc($pending_count_res)['total'] ?? 0;

$page_heading = $page_title ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_heading); ?> | A1 Backstage</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<div class="admin-layout">
    <!-- Fixed Black Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <a href="dashboard.php" class="sidebar-brand">
            <span class="sidebar-logo-flame">🔥</span>
            <div>
                <div class="sidebar-brand-title">A1 <span>Peshawari</span></div>
                <div class="sidebar-brand-sub">Staff Backstage</div>
            </div>
        </a>

        <ul class="sidebar-nav">
            <li class="sidebar-nav-item <?php echo ($current_admin_page == 'dashboard.php') ? 'active' : ''; ?>">
                <a href="dashboard.php">
                    <span class="sidebar-icon">⚡</span>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="sidebar-nav-item <?php echo ($current_admin_page == 'orders.php' || $current_admin_page == 'order_detail.php') ? 'active' : ''; ?>">
                <a href="orders.php">
                    <span class="sidebar-icon">📋</span>
                    <span>Orders</span>
                    <?php if ($pending_count > 0): ?>
                        <span class="sidebar-badge" title="<?php echo $pending_count; ?> Pending"><?php echo $pending_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="sidebar-nav-item <?php echo ($current_admin_page == 'reports.php') ? 'active' : ''; ?>">
                <a href="reports.php">
                    <span class="sidebar-icon">📊</span>
                    <span>Reports</span>
                </a>
            </li>

            <li class="sidebar-nav-item <?php echo ($current_admin_page == 'items.php' || $current_admin_page == 'item_add.php' || $current_admin_page == 'item_edit.php') ? 'active' : ''; ?>">
                <a href="items.php">
                    <span class="sidebar-icon">🍽️</span>
                    <span>Menu Items</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-bottom">
            <a href="logout.php" title="Sign out of staff panel">
                <span class="sidebar-icon">🚪</span>
                <span>Sign Out</span>
            </a>
        </div>
    </aside>

    <!-- Main Working Area -->
    <div class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button type="button" class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle Navigation">☰</button>
                <h1 class="topbar-title"><?php echo e($page_heading); ?></h1>
            </div>

            <div class="topbar-right">
                <!-- Global Order Search Form -->
                <form action="orders.php" method="GET" class="topbar-search-form">
                    <input type="text" name="search" class="topbar-search-input" placeholder="Search Order # or Phone..." value="<?php echo e($_GET['search'] ?? ''); ?>">
                </form>

                <div class="topbar-user">
                    <div class="topbar-avatar" title="<?php echo e($admin_user); ?>">
                        <?php echo strtoupper(substr($admin_user, 0, 1)); ?>
                    </div>
                    <div style="line-height: 1.2;">
                        <strong style="display: block; font-size: 0.88rem;"><?php echo e($admin_user); ?></strong>
                        <a href="logout.php" style="font-size: 0.75rem; color: #888;">Log out</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dynamic Admin Content -->
        <main class="admin-content">
