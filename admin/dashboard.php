<?php
// admin/dashboard.php - Task-Focused "At a Glance" Staff Screen
$page_title = "Dashboard";
require_once __DIR__ . '/header.php';

// Helper for human-friendly time elapsed (e.g. "10 mins ago")
function time_ago($timestamp) {
    $diff = time() - strtotime($timestamp);
    if ($diff < 60) return "Just now";
    if ($diff < 3600) return floor($diff / 60) . " mins ago";
    if ($diff < 86400) return floor($diff / 3600) . " hrs ago";
    return date('d M, h:i A', strtotime($timestamp));
}

// 1. Snapshot Row Data
// Orders Today
$today_orders_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE DATE(created_at) = CURDATE()");
$today_orders = mysqli_fetch_assoc($today_orders_res)['total'] ?? 0;

// Pending Orders
$pending_orders_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE order_status = 'Pending'");
$pending_orders = mysqli_fetch_assoc($pending_orders_res)['total'] ?? 0;

// Today's Revenue (excluding cancelled orders)
$today_rev_res = mysqli_query($conn, "SELECT SUM(total_amount) AS rev FROM orders WHERE DATE(created_at) = CURDATE() AND order_status != 'Cancelled'");
$today_rev = mysqli_fetch_assoc($today_rev_res)['rev'] ?? 0.0;

// Out-of-Stock Items count
$sold_out_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM items WHERE is_available = 0");
$sold_out_count = mysqli_fetch_assoc($sold_out_res)['total'] ?? 0;

// 2. "Needs Attention" Orders (Pending or Unverified Payment)
$attention_sql = "SELECT o.*, da.area_name 
                  FROM orders o 
                  LEFT JOIN delivery_areas da ON o.delivery_area_id = da.id 
                  WHERE o.order_status = 'Pending' 
                     OR (o.payment_method != 'cod' AND o.order_status NOT IN ('Completed', 'Cancelled'))
                  ORDER BY o.id DESC LIMIT 15";
$attention_res = mysqli_query($conn, $attention_sql);
$attention_count = mysqli_num_rows($attention_res);

// 3. Recent Activity Trail (Last 5 status changes from order_status_log)
$activity_sql = "SELECT l.*, o.customer_name 
                 FROM order_status_log l 
                 LEFT JOIN orders o ON l.order_id = o.id 
                 ORDER BY l.id DESC LIMIT 5";
$activity_res = mysqli_query($conn, $activity_sql);
?>

<!-- 1. Today's Snapshot Row -->
<div class="stat-cards-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-label">Orders Today</div>
        <div class="admin-stat-value"><?php echo number_format($today_orders); ?></div>
        <div class="admin-stat-sub"><?php echo date('d M Y'); ?></div>
    </div>

    <a href="orders.php?status=Pending" class="admin-stat-card <?php echo ($pending_orders > 0) ? 'alert-card' : ''; ?>" title="View pending orders">
        <div class="admin-stat-label">Pending Orders</div>
        <div class="admin-stat-value"><?php echo number_format($pending_orders); ?></div>
        <div class="admin-stat-sub"><?php echo ($pending_orders > 0) ? '⚠️ Action Required' : 'All orders attended'; ?></div>
    </a>

    <div class="admin-stat-card">
        <div class="admin-stat-label">Today's Revenue</div>
        <div class="admin-stat-value">Rs. <?php echo number_format($today_rev, 0); ?></div>
        <div class="admin-stat-sub">Excluding cancelled</div>
    </div>

    <a href="items.php?filter=sold_out" class="admin-stat-card" title="View sold out dishes">
        <div class="admin-stat-label">Out-of-Stock Items</div>
        <div class="admin-stat-value" style="<?php echo ($sold_out_count > 0) ? 'color: var(--admin-warning);' : ''; ?>">
            <?php echo number_format($sold_out_count); ?>
        </div>
        <div class="admin-stat-sub">Click to manage availability</div>
    </a>
</div>

<!-- 2. "Needs Attention" List -->
<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">🚨 Needs Attention Right Now</h2>
            <p style="font-size: 0.82rem; color: var(--admin-text-muted); margin-top: 2px;">
                Orders awaiting confirmation or preparation. Click any row to view & update.
            </p>
        </div>
        <a href="orders.php" class="btn-admin btn-admin-secondary" style="font-size: 0.82rem;">
            View All Orders &rarr;
        </a>
    </div>

    <div class="admin-table-container">
        <table class="admin-data-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Order #</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Sector / Area</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Placed</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($attention_res && $attention_count > 0): ?>
                    <?php while ($ord = mysqli_fetch_assoc($attention_res)): ?>
                        <?php 
                        $status = $ord['order_status'];
                        $badge_class = 'badge-pending';
                        if ($status === 'Payment Verified') $badge_class = 'badge-verified';
                        elseif ($status === 'Preparing') $badge_class = 'badge-preparing';
                        elseif ($status === 'Out for Delivery') $badge_class = 'badge-out';
                        elseif ($status === 'Completed') $badge_class = 'badge-completed';
                        elseif ($status === 'Cancelled') $badge_class = 'badge-cancelled';
                        ?>
                        <tr class="clickable-row" data-href="order_detail.php?id=<?php echo (int)$ord['id']; ?>">
                            <td style="font-weight: 700;">#<?php echo (int)$ord['id']; ?></td>
                            <td style="font-weight: 600;"><?php echo e($ord['customer_name']); ?></td>
                            <td>
                                <a href="tel:<?php echo e($ord['customer_phone']); ?>" style="color: inherit;">
                                    <?php echo e($ord['customer_phone']); ?>
                                </a>
                            </td>
                            <td><?php echo e($ord['area_name'] ?? 'Scheme 33'); ?></td>
                            <td style="font-weight: 700; color: var(--admin-text-dark);">
                                Rs. <?php echo number_format($ord['total_amount'], 0); ?>
                            </td>
                            <td>
                                <span style="text-transform: uppercase; font-size: 0.75rem; font-weight: 600; color: #555;">
                                    <?php echo e($ord['payment_method']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo e($status); ?>
                                </span>
                            </td>
                            <td style="color: var(--admin-text-muted); font-size: 0.82rem; white-space: nowrap;">
                                <?php echo time_ago($ord['created_at']); ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="order_detail.php?id=<?php echo (int)$ord['id']; ?>" class="btn-admin btn-admin-primary" style="padding: 5px 12px; font-size: 0.8rem;">
                                    Open &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px; color: var(--admin-text-muted);">
                            ✨ Great job! There are no pending orders waiting right now.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 3. Recent Activity (Last 5 Status Changes) -->
<?php if ($activity_res && mysqli_num_rows($activity_res) > 0): ?>
    <div class="admin-card" style="margin-bottom: 0;">
        <h3 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 12px; color: var(--admin-text-muted); text-transform: uppercase; letter-spacing: 0.5px;">
            Recent Kitchen Activity
        </h3>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <?php while ($act = mysqli_fetch_assoc($activity_res)): ?>
                <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.88rem; padding: 8px 12px; background: #FAFAFA; border-radius: 6px; border: 1px solid var(--admin-border);">
                    <div>
                        <a href="order_detail.php?id=<?php echo (int)$act['order_id']; ?>" style="font-weight: 600;">
                            Order #<?php echo (int)$act['order_id']; ?>
                        </a>
                        <span>marked <strong><?php echo e($act['new_status']); ?></strong></span>
                        <?php if (!empty($act['changed_by'])): ?>
                            <span style="color: var(--admin-text-muted);">by <?php echo e($act['changed_by']); ?></span>
                        <?php endif; ?>
                    </div>
                    <span style="color: var(--admin-text-muted); font-size: 0.8rem;">
                        <?php echo time_ago($act['changed_at']); ?>
                    </span>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
