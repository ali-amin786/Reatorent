<?php
// item.php - Single Dish Detail View (Fast-Food Street Style)
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/cart_functions.php';

$item_id = isset($_GET['id']) ? clean_int($_GET['id']) : 0;
if ($item_id <= 0) {
    header("Location: menu.php");
    exit;
}

// Fetch item with category
$sql = "SELECT i.*, c.name AS category_name 
        FROM items i 
        LEFT JOIN categories c ON i.category_id = c.id 
        WHERE i.id = $item_id LIMIT 1";
$res = mysqli_query($conn, $sql);

if (!$res || mysqli_num_rows($res) === 0) {
    header("Location: 404.php");
    exit;
}

$item = mysqli_fetch_assoc($res);
$page_title = $item['title'];
require_once 'includes/header.php';

// Fetch related items
$cat_id = (int)$item['category_id'];
$related_sql = "SELECT * FROM items 
                WHERE category_id = $cat_id AND id != $item_id 
                ORDER BY is_available DESC, id DESC LIMIT 4";
$related_res = mysqli_query($conn, $related_sql);
?>

<div class="container" style="padding-top: 35px; padding-bottom: 70px;">
    <!-- Breadcrumb -->
    <div style="font-size: 0.88rem; margin-bottom: 24px; color: var(--color-text-muted);">
        <a href="index.php" style="color: var(--color-text-muted);">Home</a> &gt; 
        <a href="menu.php" style="color: var(--color-text-muted);">Menu</a> &gt; 
        <a href="menu.php?category_id=<?php echo $cat_id; ?>" style="color: var(--color-text-muted);"><?php echo e($item['category_name']); ?></a> &gt; 
        <span style="color: var(--color-black); font-weight: 600;"><?php echo e($item['title']); ?></span>
    </div>

    <!-- Product Detail Layout -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 40px; background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 36px; box-shadow: var(--shadow-sm);">
        <!-- Left: Dish Photo -->
        <div style="position: relative; border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--color-border); max-height: 420px; background: #F7F7F7;">
            <?php if (!$item['is_available']): ?>
                <div class="stock-badge">Sold Out</div>
            <?php endif; ?>
            <img src="assets/uploads/items/<?php echo e($item['image_url']); ?>" alt="<?php echo e($item['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
        </div>

        <!-- Right: Information & Order Action -->
        <div style="display: flex; flex-direction: column;">
            <div style="color: var(--color-primary); font-weight: 700; text-transform: uppercase; font-size: 0.82rem; letter-spacing: 1px; margin-bottom: 6px;">
                <?php echo e($item['category_name']); ?>
            </div>
            <h1 style="font-size: 2.2rem; color: var(--color-black); line-height: 1.2; margin-bottom: 12px;">
                <?php echo e($item['title']); ?>
            </h1>
            <div style="font-family: var(--font-heading); font-size: 1.9rem; font-weight: 800; color: var(--color-primary); margin-bottom: 16px;">
                PKR <?php echo number_format($item['price'], 0); ?>
            </div>

            <!-- Availability status -->
            <div style="margin-bottom: 20px;">
                <?php if ($item['is_available']): ?>
                    <span class="badge-status badge-open">
                        <span class="pulse-dot"></span> In Stock (Sizzling Fresh)
                    </span>
                <?php else: ?>
                    <span class="badge-status badge-closed">
                        ✕ Currently Out of Stock
                    </span>
                <?php endif; ?>
            </div>

            <p style="color: var(--color-text-dark); font-size: 1rem; line-height: 1.65; margin-bottom: 28px;">
                <?php echo nl2br(e($item['description'])); ?>
            </p>

            <!-- Order / Add to Cart Form (Intercepted by AJAX - No Redirect!) -->
            <?php if ($item['is_available']): ?>
                <form id="item-detail-order-form" style="margin-top: auto;">
                    <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                    
                    <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 6px; background: var(--color-surface); padding: 6px 12px; border-radius: var(--radius-sm); border: 1px solid var(--color-border);">
                            <span style="font-weight: 600; font-size: 0.9rem; margin-right: 8px;">Qty:</span>
                            <button type="button" class="stepper-btn qty-step-btn" data-action="minus">-</button>
                            <input type="number" id="qty" name="quantity" value="1" min="1" max="50" class="qty-input form-control" style="width: 50px; text-align: center; padding: 4px; height: 32px; font-weight: 700; background: #FFF;">
                            <button type="button" class="stepper-btn qty-step-btn" data-action="plus">+</button>
                        </div>

                        <button type="submit" class="btn btn-primary" style="padding: 12px 28px; font-size: 1.05rem;">
                            🛒 Add to Cart
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-warning" style="margin-top: auto;">
                    This item has sold out for the day. Please check back when our kitchen reopens tomorrow or browse our other specials!
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Related Dishes Section -->
    <?php if ($related_res && mysqli_num_rows($related_res) > 0): ?>
        <div style="margin-top: 60px;">
            <h2 style="font-size: 1.5rem; color: var(--color-black); margin-bottom: 20px;">Pairs Great With</h2>
            <div class="food-grid">
                <?php while ($rel = mysqli_fetch_assoc($related_res)): ?>
                    <div class="food-card <?php echo !$rel['is_available'] ? 'out-of-stock' : ''; ?>">
                        <div class="card-img-wrap">
                            <a href="item.php?id=<?php echo (int)$rel['id']; ?>">
                                <img src="assets/uploads/items/<?php echo e($rel['image_url']); ?>" alt="<?php echo e($rel['title']); ?>" loading="lazy">
                            </a>

                            <?php if ($rel['is_available']): ?>
                                <button type="button" 
                                        class="card-plus-btn" 
                                        data-id="<?php echo (int)$rel['id']; ?>" 
                                        data-title="<?php echo e($rel['title']); ?>"
                                        title="Add to Cart">
                                    +
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title">
                                <a href="item.php?id=<?php echo (int)$rel['id']; ?>"><?php echo e($rel['title']); ?></a>
                            </h3>
                            <div class="card-footer">
                                <div class="card-price">PKR <?php echo number_format($rel['price'], 0); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
