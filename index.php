<?php
// index.php - Fast-Food Homepage for A1 Peshawari Chapli Kabab
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/cart_functions.php';

$page_title = "Karachi's Original Peshawari Chapli Kabab";
require_once 'includes/header.php';

// Fetch categories
$cat_sql = "SELECT * FROM categories ORDER BY id ASC";
$cat_res = mysqli_query($conn, $cat_sql);

// Fetch featured items
$featured_sql = "SELECT i.*, c.name AS category_name 
                 FROM items i 
                 LEFT JOIN categories c ON i.category_id = c.id 
                 ORDER BY (i.is_available = 1) DESC, i.id ASC LIMIT 8";
$featured_res = mysqli_query($conn, $featured_sql);
?>

<!-- Hero Section (Fast-Food Sizzling Tawa Banner) -->
<section class="hero-section" style="background-image: url('assets/images/hero-sizzling-kabab.jpg');">
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="hero-content">
            <!-- Live Status Pill -->
            <div class="hero-status-pill">
                <span class="pulse-dot" style="background-color: <?php echo $hours_info['is_open'] ? '#22c55e' : '#ef4444'; ?>;"></span>
                <span><?php echo e($hours_info['status_text']); ?></span>
            </div>

            <h1 class="hero-title">
                Karachi's Original <span class="text-primary">Peshawari Chapli Kabab</span>
            </h1>

            <p class="hero-subtext">
                Fresh off the iron tawa. Delivering hot to Scheme 33, Safoora, Gulshan & nearby sectors till <?php echo $hours_info['close_time']; ?>.
            </p>

            <div class="hero-cta-group">
                <a href="#menu-section" class="btn btn-primary" style="padding: 14px 28px; font-size: 1.05rem;">
                    Order Now &darr;
                </a>
                <a href="menu.php" class="btn btn-white-outline" style="padding: 14px 28px; font-size: 1.05rem;">
                    View Full Menu
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Category Quick Filter Bar -->
<div class="container" id="menu-section" style="margin-top: 40px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 8px;">
        <h2 style="font-size: 1.6rem; color: var(--color-black);">Explore Our Menu</h2>
        <a href="menu.php" style="color: var(--color-primary); font-weight: 700; font-size: 0.9rem;">View All Items &rarr;</a>
    </div>

    <div class="category-filter-bar">
        <a href="menu.php" class="category-pill active">All Dishes</a>
        <?php if ($cat_res && mysqli_num_rows($cat_res) > 0): ?>
            <?php while ($cat = mysqli_fetch_assoc($cat_res)): ?>
                <a href="menu.php?category_id=<?php echo (int)$cat['id']; ?>" class="category-pill">
                    <?php echo e($cat['name']); ?>
                </a>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Featured Dishes Grid (Fast-Food Street Cards) -->
<section class="container" style="margin-bottom: 60px;">
    <div class="food-grid">
        <?php if ($featured_res && mysqli_num_rows($featured_res) > 0): ?>
            <?php while ($item = mysqli_fetch_assoc($featured_res)): ?>
                <?php $is_avail = (bool)$item['is_available']; ?>
                <div class="food-card <?php echo !$is_avail ? 'out-of-stock' : ''; ?>">
                    <?php if (!$is_avail): ?>
                        <div class="stock-badge">Sold Out</div>
                    <?php endif; ?>

                    <div class="card-img-wrap">
                        <a href="item.php?id=<?php echo (int)$item['id']; ?>">
                            <img src="assets/uploads/items/<?php echo e($item['image_url']); ?>" alt="<?php echo e($item['title']); ?>" loading="lazy">
                        </a>

                        <!-- Fast-Food Circular Red Plus Button (Zero-Redirect AJAX) -->
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
                        <div class="card-category-tag"><?php echo e($item['category_name'] ?? 'Specialty'); ?></div>
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
        <?php endif; ?>
    </div>
</section>

<!-- Fast-Food Trust & Fresh Tawa Highlights -->
<section style="background: var(--color-surface); border-top: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border); padding: 50px 0;">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px;">
            <div style="background: var(--color-white); padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--color-border); text-align: center;">
                <div style="font-size: 2.4rem; margin-bottom: 10px;">🥩</div>
                <h3 style="font-size: 1.15rem; margin-bottom: 8px; color: var(--color-black);">Prime Hand-Minced</h3>
                <p style="color: var(--color-text-muted); font-size: 0.88rem;">100% fresh meat coarsely hand-minced with crushed anardana, coriander and real bone marrow fat.</p>
            </div>

            <div style="background: var(--color-white); padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--color-border); text-align: center;">
                <div style="font-size: 2.4rem; margin-bottom: 10px;">🔥</div>
                <h3 style="font-size: 1.15rem; margin-bottom: 8px; color: var(--color-black);">Heavy Iron Tawa Sizzle</h3>
                <p style="color: var(--color-text-muted); font-size: 0.88rem;">Cooked on sizzling cast-iron tawas at high heat, locking in the charred crust and juicy center.</p>
            </div>

            <div style="background: var(--color-white); padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--color-border); text-align: center;">
                <div style="font-size: 2.4rem; margin-bottom: 10px;">🫓</div>
                <h3 style="font-size: 1.15rem; margin-bottom: 8px; color: var(--color-black);">MASHAALLAH Naan House</h3>
                <p style="color: var(--color-text-muted); font-size: 0.88rem;">Baking piping hot Roghani, Kandahari, and Garlic butter naans fresh for every single order.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
