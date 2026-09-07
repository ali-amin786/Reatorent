<?php
// admin/items.php - Fast Menu Items Management with 1-Click Stock Toggle
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Handle 1-Click Toggle via AJAX
if (isset($_GET['action']) && $_GET['action'] === 'toggle_ajax' && isset($_GET['id'])) {
    header('Content-Type: application/json; charset=utf-8');
    $item_id = clean_int($_GET['id']);
    $new_state = isset($_GET['state']) ? clean_int($_GET['state']) : -1;

    if ($new_state === 0 || $new_state === 1) {
        $upd_sql = "UPDATE items SET is_available = $new_state WHERE id = $item_id";
    } else {
        $upd_sql = "UPDATE items SET is_available = IF(is_available = 1, 0, 1) WHERE id = $item_id";
    }

    if (mysqli_query($conn, $upd_sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
    exit;
}

// Handle standard toggle fallback
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $item_id = clean_int($_GET['id']);
    mysqli_query($conn, "UPDATE items SET is_available = IF(is_available = 1, 0, 1) WHERE id = $item_id");
    header("Location: items.php?msg=toggled");
    exit;
}

// Handle Delete (soft-delete if item has historical orders, hard delete if brand new)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $item_id = clean_int($_GET['id']);
    $chk_orders = mysqli_query($conn, "SELECT COUNT(*) AS total FROM order_items WHERE item_id = $item_id");
    $has_orders = mysqli_fetch_assoc($chk_orders)['total'] ?? 0;

    if ($has_orders > 0) {
        // Soft-delete to preserve relational integrity
        mysqli_query($conn, "UPDATE items SET is_available = 0 WHERE id = $item_id");
        $delete_msg = "Item has past customer orders. It was marked <strong>Sold Out (Soft Deleted)</strong> to protect past order receipts.";
    } else {
        $img_q = mysqli_query($conn, "SELECT image_url FROM items WHERE id = $item_id");
        $img_row = mysqli_fetch_assoc($img_q);
        if (!empty($img_row['image_url'])) {
            $path = __DIR__ . '/../assets/uploads/items/' . $img_row['image_url'];
            if (file_exists($path) && strpos($img_row['image_url'], 'item_') === 0) {
                @unlink($path);
            }
        }
        mysqli_query($conn, "DELETE FROM items WHERE id = $item_id");
        $delete_msg = "Menu item permanently deleted.";
    }
}

$page_title = "Menu Items";
require_once __DIR__ . '/header.php';

// Filter parameter
$filter = clean($conn, $_GET['filter'] ?? '');
$where = "WHERE 1=1";
if ($filter === 'sold_out') {
    $where .= " AND i.is_available = 0";
}

$sql = "SELECT i.*, c.name AS category_name 
        FROM items i 
        LEFT JOIN categories c ON i.category_id = c.id 
        $where 
        ORDER BY i.category_id ASC, i.id ASC";
$items_res = mysqli_query($conn, $sql);
?>

<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 20px;">
    <div class="admin-filter-tabs" style="margin-bottom: 0;">
        <a href="items.php" class="filter-tab-btn <?php echo empty($filter) ? 'active' : ''; ?>">
            All Dishes
        </a>
        <a href="items.php?filter=sold_out" class="filter-tab-btn <?php echo ($filter === 'sold_out') ? 'active' : ''; ?>">
            Sold Out Only
        </a>
    </div>

    <!-- Prominent + Add New Item Button -->
    <a href="item_add.php" class="btn-admin btn-admin-primary" style="padding: 9px 18px; font-size: 0.92rem;">
        + Add New Item
    </a>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'toggled'): ?>
    <div style="background: var(--admin-success-light); border: 1px solid rgba(30,142,62,0.3); color: var(--admin-success); padding: 10px 14px; border-radius: var(--radius-sm); margin-bottom: 16px;">
        Item availability updated.
    </div>
<?php endif; ?>

<?php if (!empty($delete_msg)): ?>
    <div style="background: #FFF; border: 1px solid var(--admin-border); padding: 10px 14px; border-radius: var(--radius-sm); margin-bottom: 16px;">
        <?php echo $delete_msg; ?>
    </div>
<?php endif; ?>

<div class="admin-card" style="padding: 0; overflow: hidden;">
    <div class="admin-table-container" style="border: none; border-radius: 0;">
        <table class="admin-data-table">
            <thead>
                <tr>
                    <th style="width: 70px;">Photo</th>
                    <th>Dish Title</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th style="width: 170px;">Stock Availability (1-Click)</th>
                    <th style="text-align: right; width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items_res && mysqli_num_rows($items_res) > 0): ?>
                    <?php while ($item = mysqli_fetch_assoc($items_res)): ?>
                        <?php $is_avail = (bool)$item['is_available']; ?>
                        <tr style="<?php echo !$is_avail ? 'background: #FFFDFD;' : ''; ?>">
                            <td>
                                <img src="../assets/uploads/items/<?php echo e($item['image_url']); ?>" alt="" style="width: 50px; height: 42px; object-fit: cover; border-radius: 4px; border: 1px solid var(--admin-border);">
                            </td>
                            <td>
                                <strong style="display: block; color: var(--admin-text-dark); font-size: 0.95rem;">
                                    <?php echo e($item['title']); ?>
                                </strong>
                                <span style="font-size: 0.78rem; color: var(--admin-text-muted);">
                                    <?php echo e(mb_substr($item['description'], 0, 60)) . (strlen($item['description']) > 60 ? '...' : ''); ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-weight: 500; color: #555;">
                                    <?php echo e($item['category_name'] ?? 'Dish'); ?>
                                </span>
                            </td>
                            <td style="font-weight: 700; color: var(--admin-text-dark);">
                                Rs. <?php echo number_format($item['price'], 0); ?>
                            </td>
                            <td>
                                <!-- 1-Click Instant Availability Switch (No reload) -->
                                <div class="stock-switch-wrap">
                                    <label class="stock-switch">
                                        <input type="checkbox" 
                                               class="stock-toggle-input" 
                                               data-id="<?php echo (int)$item['id']; ?>" 
                                               <?php echo $is_avail ? 'checked' : ''; ?>>
                                        <span class="stock-slider"></span>
                                    </label>
                                    <span class="stock-text <?php echo $is_avail ? 'in-stock' : 'sold-out'; ?>">
                                        <?php echo $is_avail ? 'In Stock' : 'Sold Out'; ?>
                                    </span>
                                </div>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 8px;">
                                    <a href="item_edit.php?id=<?php echo (int)$item['id']; ?>" class="btn-admin btn-admin-secondary" style="padding: 5px 10px; font-size: 0.8rem;">
                                        Edit
                                    </a>
                                    <a href="items.php?action=delete&id=<?php echo (int)$item['id']; ?>" 
                                       class="btn-admin btn-admin-danger" 
                                       style="padding: 5px 10px; font-size: 0.8rem;" 
                                       onclick="return confirm('Are you sure you want to delete / remove this item?');"
                                       title="Delete dish">
                                        🗑️
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 45px; color: var(--admin-text-muted);">
                            No menu items found. Click "+ Add New Item" to create one.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
