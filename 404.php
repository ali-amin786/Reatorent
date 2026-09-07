<?php
// 404.php - Fast-Food Themed 404 Page
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/cart_functions.php';

http_response_code(404);
$page_title = "Dish Not Found";
require_once 'includes/header.php';
?>

<div class="container" style="padding: 90px 20px; text-align: center;">
    <div style="font-size: 4.5rem; line-height: 1; margin-bottom: 20px;">🍢</div>
    <h1 style="font-size: 2.8rem; color: var(--color-primary); margin-bottom: 12px; font-weight: 900; letter-spacing: -1px;">404 - Dish Not Found</h1>
    <p style="color: var(--color-text-muted); font-size: 1.1rem; max-width: 500px; margin: 0 auto 30px;">
        Looks like the recipe or page you're looking for isn't sizzling on our iron tawa right now.
    </p>
    <div style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;">
        <a href="index.php" class="btn btn-primary">Go to Homepage</a>
        <a href="menu.php" class="btn btn-secondary">Browse Full Menu</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
