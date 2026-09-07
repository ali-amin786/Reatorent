<?php
// includes/header.php - Shared Fast-Food Navbar & Header
if (!isset($conn)) {
    require_once __DIR__ . '/../config/db.php';
}
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cart_functions.php';

$hours_info = check_restaurant_hours();
$cart_count = cart_get_count();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? e($page_title) . ' | ' . SITE_NAME : SITE_NAME . ' | ' . SUB_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Top Info / Operating Hours Bar -->
<div class="top-notice-bar">
    <div class="container notice-flex">
        <div>
            <span>📍 Scheme 33, Karachi</span>
            <span style="margin: 0 8px; opacity: 0.4;">|</span>
            <span>📞 Call: <a href="tel:<?php echo PHONE_NUMBER; ?>" style="color: #FFF; text-decoration: underline;"><?php echo PHONE_NUMBER; ?></a></span>
        </div>
        <div class="badge-status <?php echo $hours_info['badge_class']; ?>">
            <span class="pulse-dot"></span>
            <span><?php echo e($hours_info['status_text']); ?></span>
            <span style="font-size: 0.72rem; opacity: 0.85;">(<?php echo $hours_info['open_time']; ?> - <?php echo $hours_info['close_time']; ?>)</span>
        </div>
    </div>
</div>

<!-- Main Sticky Header (White background, Black text, Red accent) -->
<header class="site-header">
    <div class="container nav-wrap">
        <a href="index.php" class="logo-group">
            <span class="logo-icon">🔥</span>
            <div>
                <div class="brand-name">A1 <span>Peshawari</span> Kabab</div>
                <div class="brand-sub"><?php echo SUB_NAME; ?></div>
            </div>
        </a>

        <button class="nav-toggle" aria-label="Toggle navigation">&#9776;</button>

        <ul class="nav-links">
            <li><a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Home</a></li>
            <li><a href="menu.php" class="<?php echo ($current_page == 'menu.php') ? 'active' : ''; ?>">Full Menu</a></li>
            <li><a href="index.php#about">About & Location</a></li>
            <li><a href="admin/login.php" style="opacity: 0.7;" title="Staff Portal">Login</a></li>
            <li>
                <button type="button" class="cart-nav-btn" id="navbar-cart-btn" aria-label="Open Shopping Cart">
                    <span>🛒 Cart</span>
                    <span class="cart-badge" id="cart-badge-count"><?php echo $cart_count; ?></span>
                </button>
            </li>
        </ul>
    </div>
</header>
