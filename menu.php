<?php
// menu.php - Fast-Food Menu with Instant AJAX Add-To-Cart & Category Filter
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/cart_functions.php';

$page_title = "Our Full Menu";
require_once 'includes/header.php';

// Sanitize category filter
$cat_id = 0;
$cat_where = "";
if (isset($_GET['category_id']) && $_GET['category_id'] !== '') {
    $cat_id = clean_int($_GET['category_id']);
    if ($cat_id > 0) {
        $cat_where = " AND i.category_id = " . $cat_id;
    }
}

// Sanitize search filter
$search = "";
$search_where = "";
if (!empty($_GET['search'])) {
    $search = clean($conn, $_GET['search']);
    $search_where = " AND (i.title LIKE '%$search%' OR i.description LIKE '%$search%')";
}

// Fetch categories
$cat_sql = "SELECT * FROM categories ORDER BY id ASC";
$cat_res = mysqli_query($conn, $cat_sql);

// Query items
$items_sql = "SELECT i.*, c.name AS category_name 
              FROM items i 
              LEFT JOIN categories c ON i.category_id = c.id 
              WHERE 1=1 $cat_where $search_where 
              ORDER BY (i.is_available = 1) DESC, i.category_id ASC, i.id ASC";
$items_res = mysqli_query($conn, $items_sql);
?>

<div class="container" style="padding-top: 35px; padding-bottom: 70px;">
    <!-- Top Bar with Title and Search Input -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">
        <div>
            <h1 style="font-size: 2.2rem; color: var(--color-black); letter-spacing: -0.5px;">Our Hot Street Menu</h1>
            <p style="color: var(--color-text-muted); font-size: 0.95rem;">Select from our sizzling chapli kababs, tandoor naans, and sides.</p>
        </div>

        <!-- Search Box -->
        <form method="GET" action="menu.php" class="search-form-wrap" style="margin: 0; min-width: 300px;">
            <?php if ($cat_id > 0): ?>
                <input type="hidden" name="category_id" value="<?php echo $cat_id; ?>">
            <?php endif; ?>
            <input type="text" name="search" class="search-input" placeholder="Search kabab, naan, raita..." value="<?php echo e($search); ?>">
            <button type="submit" class="btn btn-primary" style="padding: 10px 18px;">🔍</button>
            <?php if (!empty($search)): ?>
                <a href="menu.php<?php echo $cat_id > 0 ? '?category_id=' . $cat_id : ''; ?>" class="btn btn-secondary" style="padding: 10px 14px;" title="Clear search">✕</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Category Filter Pills -->
    <div class="category-filter-bar">
        <a href="menu.php<?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?>" 
           class="category-pill <?php echo ($cat_id === 0) ? 'active' : ''; ?>">
            All Dishes
        </a>
        <?php if ($cat_res && mysqli_num_rows($cat_res) > 0): ?>
            <?php while ($cat = mysqli_fetch_assoc($cat_res)): ?>
                <a href="menu.php?category_id=<?php echo (int)$cat['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                   class="category-pill <?php echo ($cat_id === (int)$cat['id']) ? 'active' : ''; ?>">
                    <?php echo e($cat['name']); ?>
                </a>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- Item Listing Grid (Fast-Food Layout) -->
    <?php if ($items_res && mysqli_num_rows($items_res) > 0): ?>
        <div class="food-grid">
            <?php while ($item = mysqli_fetch_assoc($items_res)): ?>
                <?php $is_avail = (bool)$item['is_available']; ?>
                <div class="food-card <?php echo !$is_avail ? 'out-of-stock' : ''; ?>">
                    <?php if (!$is_avail): ?>
                        <div class="stock-badge">Sold Out</div>
                    <?php endif; ?>

                    <div class="card-img-wrap">
                        <a href="item.php?id=<?php echo (int)$item['id']; ?>">
                            <img src="assets/uploads/items/<?php echo e($item['image_url']); ?>" alt="<?php echo e($item['title']); ?>" loading="lazy">
                        </a>

                        <!-- Circular Red Plus Button (Zero-Redirect AJAX) -->
                        <?php if ($is_avail): ?>
                            <button type="button" 
                                    class="card-plus-btn" 
                                    data-id="<?php echo (int)$item['id']; ?>" 
                                    data-title="<?php echo e($item['title']); ?>"
                                    title="Add to Cart"
                                    aria-label="Add <?php echo e($item['title']); ?> to cart">
                                +
                            </button>
                        <?php else: ?>
                            <button type="button" class="card-plus-btn" disabled title="Sold Out">&times;</button>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <div class="card-category-tag"><?php echo e($item['category_name'] ?? 'Menu Item'); ?></div>
                        <h3 class="card-title">
                            <a href="item.php?id=<?php echo (int)$item['id']; ?>"><?php echo e($item['title']); ?></a>
                        </h3>
                        <p class="card-desc"><?php echo e($item['description']); ?></p>

                        <div class="card-footer">
                            <div class="card-price">PKR <?php echo number_format($item['price'], 0); ?></div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 50px; text-align: center; margin: 40px 0;">
            <div style="font-size: 3rem; margin-bottom: 12px;">🍳</div>
            <h3 style="color: var(--color-black); font-size: 1.4rem;">No Dishes Found</h3>
            <p style="color: var(--color-text-muted); margin-top: 8px;">
                <?php if (!empty($search)): ?>
                    We couldn't find anything matching "<strong><?php echo e($search); ?></strong>". Try searching for another item or clear your search filter.
                <?php else: ?>
                    There are currently no items available in this category.
                <?php endif; ?>
            </p>
            <div style="margin-top: 20px;">
                <a href="menu.php" class="btn btn-primary">View All Dishes</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
