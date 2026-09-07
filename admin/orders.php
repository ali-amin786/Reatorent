<?php
// admin/orders.php - Scannable Orders Management with Filter Tabs & Whole-Row Click
$page_title = "Orders";
require_once __DIR__ . '/header.php';

// Filter parameters
$status_filter = clean($conn, $_GET['status'] ?? '');
$search_query = clean($conn, $_GET['search'] ?? '');

$where = "WHERE 1=1";
if (!empty($status_filter)) {
    $where .= " AND o.order_status = '$status_filter'";
}
if (!empty($search_query)) {
    $where .= " AND (o.id = '" . (int)$search_query . "' OR o.customer_phone LIKE '%$search_query%' OR o.customer_name LIKE '%$search_query%')";
}

// Pagination (20 rows per page)
$per_page = 20;
$page = max(1, clean_int($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

// Total count
$count_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders o $where");
$total_rows = mysqli_fetch_assoc($count_res)['total'] ?? 0;
$total_pages = ceil($total_rows / $per_page);

// Fetch orders
$orders_sql = "SELECT o.*, da.area_name 
               FROM orders o 
               LEFT JOIN delivery_areas da ON o.delivery_area_id = da.id 
               $where 
               ORDER BY o.id DESC 
               LIMIT $per_page OFFSET $offset";
$orders_res = mysqli_query($conn, $orders_sql);

$tabs = [
    '' => 'All Orders',
    'Pending' => 'Pending',
    'Preparing' => 'Preparing',
    'Out for Delivery' => 'Out for Delivery',
    'Completed' => 'Completed',
    'Cancelled' => 'Cancelled'
];
?>

<!-- Filter Tabs -->
<div class="admin-filter-tabs">
    <?php foreach ($tabs as $val => $label): ?>
        <?php 
        $tab_url = 'orders.php';
        $params = [];
        if (!empty($val)) $params['status'] = $val;
        if (!empty($search_query)) $params['search'] = $search_query;
        if (!empty($params)) $tab_url .= '?' . http_build_query($params);
        $is_active = ($status_filter === $val);
        ?>
        <a href="<?php echo $tab_url; ?>" class="filter-tab-btn <?php echo $is_active ? 'active' : ''; ?>">
            <?php echo e($label); ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="admin-card" style="padding: 0; overflow: hidden;">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center; background: #FAFAFA;">
        <span style="font-weight: 600; font-size: 0.9rem; color: var(--admin-text-dark);">
            Showing <?php echo $total_rows; ?> orders <?php echo !empty($status_filter) ? 'in <strong>' . e($status_filter) . '</strong>' : ''; ?>
        </span>

        <?php if (!empty($search_query) || !empty($status_filter)): ?>
            <a href="orders.php" style="font-size: 0.82rem; color: var(--admin-primary); font-weight: 500;">
                Clear filters ✕
            </a>
        <?php endif; ?>
    </div>

    <div class="admin-table-container" style="border: none; border-radius: 0;">
        <table class="admin-data-table">
            <thead>
                <tr>
                    <th style="width: 85px;">Order #</th>
                    <th>Customer Name</th>
                    <th>Phone</th>
                    <th>Payment Method</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Time Placed</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders_res && mysqli_num_rows($orders_res) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($orders_res)): ?>
                        <?php 
                        $status = $row['order_status'];
                        $badge_class = 'badge-pending';
                        if ($status === 'Payment Verified') $badge_class = 'badge-verified';
                        elseif ($status === 'Preparing') $badge_class = 'badge-preparing';
                        elseif ($status === 'Out for Delivery') $badge_class = 'badge-out';
                        elseif ($status === 'Completed') $badge_class = 'badge-completed';
                        elseif ($status === 'Cancelled') $badge_class = 'badge-cancelled';

                        $clean_phone = preg_replace('/[^0-9]/', '', $row['customer_phone']);
                        $wa_customer_url = "https://wa.me/92" . ltrim($clean_phone, '0') . "?text=Salam%20" . urlencode($row['customer_name']) . "%2C%20regarding%20your%20Order%20%23" . $row['id'];
                        ?>
                        <tr class="clickable-row" data-href="order_detail.php?id=<?php echo (int)$row['id']; ?>">
                            <td style="font-weight: 700;">#<?php echo (int)$row['id']; ?></td>
                            <td style="font-weight: 600;"><?php echo e($row['customer_name']); ?></td>
                            <td>
                                <a href="tel:<?php echo e($row['customer_phone']); ?>" style="color: inherit; text-decoration: underline;">
                                    <?php echo e($row['customer_phone']); ?>
                                </a>
                                <a href="<?php echo $wa_customer_url; ?>" target="_blank" title="WhatsApp Customer" style="margin-left: 6px; text-decoration: none;">💬</a>
                            </td>
                            <td>
                                <span style="text-transform: uppercase; font-size: 0.78rem; font-weight: 600; color: #555;">
                                    <?php echo e($row['payment_method']); ?>
                                </span>
                            </td>
                            <td style="font-weight: 700; color: var(--admin-text-dark);">
                                Rs. <?php echo number_format($row['total_amount'], 0); ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo e($status); ?>
                                </span>
                            </td>
                            <td style="color: var(--admin-text-muted); font-size: 0.82rem; white-space: nowrap;">
                                <?php echo date('d M, h:i A', strtotime($row['created_at'])); ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="order_detail.php?id=<?php echo (int)$row['id']; ?>" class="btn-admin btn-admin-primary" style="padding: 5px 12px; font-size: 0.8rem;">
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 45px; color: var(--admin-text-muted);">
                            No orders found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination (20 rows per page) -->
<?php if ($total_pages > 1): ?>
    <div class="admin-pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php
            $page_params = ['page' => $i];
            if (!empty($status_filter)) $page_params['status'] = $status_filter;
            if (!empty($search_query)) $page_params['search'] = $search_query;
            $url = 'orders.php?' . http_build_query($page_params);
            ?>
            <a href="<?php echo $url; ?>" class="page-btn <?php echo ($i === $page) ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
